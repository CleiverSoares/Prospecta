<?php

namespace App\Services;

use App\Repositories\TelegramInscritoRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAvisoService
{
    public function __construct(
        private readonly TelegramInscritoRepository $telegramInscritoRepository,
    ) {}

    public function habilitado(): bool
    {
        return (bool) config('prospecta.telegram.enabled')
            && filled(config('prospecta.telegram.bot_token'));
    }

    /**
     * @param  list<'adm'|'gestor'>  $destinos
     */
    public function avisar(string $chaveDebounce, string $texto, array $destinos = ['adm', 'gestor']): bool
    {
        if (! $this->habilitado()) {
            return false;
        }

        $minutos = max(1, (int) config('prospecta.telegram.debounce_minutos', 30));
        $cacheKey = 'telegram:aviso:'.$chaveDebounce;

        if (! Cache::add($cacheKey, 1, now()->addMinutes($minutos))) {
            return false;
        }

        $enviou = false;
        foreach ($this->chatIds($destinos) as $chatId) {
            $enviou = $this->enviar($chatId, $texto) || $enviou;
        }

        return $enviou;
    }

    public function enviarDireto(string $chatId, string $texto): bool
    {
        if (! $this->habilitado() || $chatId === '') {
            return false;
        }

        return $this->enviar($chatId, $texto);
    }

    public function contarInscritosAtivos(): int
    {
        return $this->telegramInscritoRepository->contarAtivos();
    }

    /**
     * Env chats (adm/gestor) + quem deu /start no bot.
     *
     * @param  list<'adm'|'gestor'>  $destinos
     * @return list<string>
     */
    private function chatIds(array $destinos): array
    {
        $ids = [];
        foreach ($destinos as $destino) {
            $chat = match ($destino) {
                'adm' => (string) config('prospecta.telegram.chat_adm', ''),
                'gestor' => (string) config('prospecta.telegram.chat_gestor', ''),
                default => '',
            };
            if ($chat !== '') {
                $ids[] = $chat;
            }
        }

        foreach ($this->telegramInscritoRepository->chatIdsAtivos() as $chatId) {
            $ids[] = $chatId;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function enviar(string $chatId, string $texto): bool
    {
        $token = (string) config('prospecta.telegram.bot_token');
        $url = rtrim((string) config('prospecta.telegram.api_base'), '/')."/bot{$token}/sendMessage";

        try {
            $res = Http::timeout((int) config('prospecta.telegram.timeout', 8))
                ->asForm()
                ->post($url, [
                    'chat_id' => $chatId,
                    'text' => $texto,
                    'disable_web_page_preview' => true,
                ]);

            if (! $res->successful()) {
                Log::warning('telegram.aviso_falhou', [
                    'chat_id' => $chatId,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
