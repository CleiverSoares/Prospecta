<?php

namespace App\Services;

use App\Repositories\TelegramInscritoRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookService
{
    public function __construct(
        private readonly TelegramInscritoRepository $telegramInscritoRepository,
        private readonly TelegramAvisoService $telegramAvisoService,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function processar(array $update): void
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chat = $message['chat'] ?? null;
        if (! is_array($chat) || ! isset($chat['id'])) {
            return;
        }

        $chatId = (string) $chat['id'];
        $texto = trim((string) ($message['text'] ?? ''));
        $nome = trim(implode(' ', array_filter([
            $chat['first_name'] ?? null,
            $chat['last_name'] ?? null,
        ]))) ?: null;
        $username = isset($chat['username']) ? (string) $chat['username'] : null;

        $comando = strtolower(explode(' ', $texto)[0] ?? '');
        $comando = explode('@', $comando)[0];

        if (in_array($comando, ['/start', '/inscrever'], true)) {
            $this->telegramInscritoRepository->upsertPorChat($chatId, $nome, $username, true);
            $this->telegramAvisoService->enviarDireto(
                $chatId,
                "Prospecta: inscrição ok.\nVocê vai receber avisos de fora do território e visita feita.\nPara sair: /parar",
            );

            return;
        }

        if (in_array($comando, ['/parar', '/stop', '/sair'], true)) {
            $this->telegramInscritoRepository->desativar($chatId);
            $this->telegramAvisoService->enviarDireto(
                $chatId,
                'Prospecta: avisos desligados neste chat. Para voltar: /start',
            );
        }
    }

    public function registrarWebhook(): bool
    {
        if (! $this->telegramAvisoService->habilitado()) {
            return false;
        }

        $secret = (string) config('prospecta.telegram.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $urlPublica = rtrim((string) config('app.url'), '/').'/webhooks/telegram/'.$secret;
        $token = (string) config('prospecta.telegram.bot_token');
        $api = rtrim((string) config('prospecta.telegram.api_base'), '/')."/bot{$token}/setWebhook";

        try {
            $res = Http::timeout((int) config('prospecta.telegram.timeout', 8))
                ->asForm()
                ->post($api, [
                    'url' => $urlPublica,
                    'allowed_updates' => json_encode(['message']),
                    'drop_pending_updates' => false,
                ]);

            if (! $res->successful() || ! ($res->json('ok') ?? false)) {
                Log::warning('telegram.webhook_falhou', [
                    'status' => $res->status(),
                    'body' => $res->body(),
                    'url' => $urlPublica,
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
