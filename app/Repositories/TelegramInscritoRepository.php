<?php

namespace App\Repositories;

use App\Models\TelegramInscrito;
use Illuminate\Support\Collection;

class TelegramInscritoRepository
{
    public function upsertPorChat(string $chatId, ?string $nome, ?string $username, bool $ativo = true): TelegramInscrito
    {
        return TelegramInscrito::query()->updateOrCreate(
            ['chat_id' => $chatId],
            [
                'nome' => $nome,
                'username' => $username,
                'ativo' => $ativo,
                'inscrito_em' => $ativo ? now() : null,
            ],
        );
    }

    public function desativar(string $chatId): void
    {
        TelegramInscrito::query()
            ->where('chat_id', $chatId)
            ->update(['ativo' => false]);
    }

    /**
     * @return list<string>
     */
    public function chatIdsAtivos(): array
    {
        return TelegramInscrito::query()
            ->where('ativo', true)
            ->pluck('chat_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function contarAtivos(): int
    {
        return TelegramInscrito::query()->where('ativo', true)->count();
    }

    /**
     * @return Collection<int, TelegramInscrito>
     */
    public function listarAtivos(int $limite = 50): Collection
    {
        return TelegramInscrito::query()
            ->where('ativo', true)
            ->orderByDesc('inscrito_em')
            ->limit($limite)
            ->get();
    }
}
