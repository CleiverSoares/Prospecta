<?php

namespace App\Console\Commands;

use App\Services\TelegramWebhookService;
use Illuminate\Console\Command;

class TelegramConfigurarWebhookCommand extends Command
{
    protected $signature = 'telegram:configurar-webhook';

    protected $description = 'Registra o webhook do bot Telegram na URL pública do app';

    public function handle(TelegramWebhookService $telegramWebhookService): int
    {
        if ($telegramWebhookService->registrarWebhook()) {
            $this->info('Webhook Telegram registrado em '.config('app.url').'/webhooks/telegram/***');

            return self::SUCCESS;
        }

        $this->error('Falha ao registrar webhook. Confira TELEGRAM_ENABLED, token, TELEGRAM_WEBHOOK_SECRET e APP_URL https.');

        return self::FAILURE;
    }
}
