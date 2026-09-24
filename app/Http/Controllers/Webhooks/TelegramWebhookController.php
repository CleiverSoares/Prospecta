<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\TelegramWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramWebhookService $telegramWebhookService,
    ) {}

    public function __invoke(Request $request, string $segredo): Response
    {
        $esperado = (string) config('prospecta.telegram.webhook_secret');
        if ($esperado === '' || ! hash_equals($esperado, $segredo)) {
            abort(404);
        }

        $this->telegramWebhookService->processar($request->all());

        return response('ok', 200);
    }
}
