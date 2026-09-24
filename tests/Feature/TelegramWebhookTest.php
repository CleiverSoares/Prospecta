<?php

namespace Tests\Feature;

use App\Models\TelegramInscrito;
use App\Services\TelegramAvisoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $segredo = 'segredo-teste-webhook';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'prospecta.telegram.enabled' => true,
            'prospecta.telegram.bot_token' => '123:ABC',
            'prospecta.telegram.webhook_secret' => $this->segredo,
            'prospecta.telegram.chat_adm' => '',
            'prospecta.telegram.chat_gestor' => '',
            'prospecta.telegram.api_base' => 'https://api.telegram.org',
        ]);
    }

    public function test_start_inscreve_chat_e_responde(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson(route('webhooks.telegram', ['segredo' => $this->segredo]), [
            'message' => [
                'text' => '/start',
                'chat' => [
                    'id' => 5028590014,
                    'first_name' => 'Juninho',
                    'username' => 'JuninhoSoaress',
                    'type' => 'private',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('telegram_inscritos', [
            'chat_id' => '5028590014',
            'ativo' => true,
            'username' => 'JuninhoSoaress',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && (string) $request['chat_id'] === '5028590014'
            && str_contains($request['text'], 'inscrição ok'));
    }

    public function test_parar_desativa_inscricao(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        TelegramInscrito::query()->create([
            'chat_id' => '99',
            'nome' => 'Teste',
            'ativo' => true,
            'inscrito_em' => now(),
        ]);

        $this->postJson(route('webhooks.telegram', ['segredo' => $this->segredo]), [
            'message' => [
                'text' => '/parar',
                'chat' => ['id' => 99, 'type' => 'private'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('telegram_inscritos', [
            'chat_id' => '99',
            'ativo' => false,
        ]);
    }

    public function test_segredo_invalido_retorna_404(): void
    {
        $this->postJson(route('webhooks.telegram', ['segredo' => 'errado']), [
            'message' => ['text' => '/start', 'chat' => ['id' => 1]],
        ])->assertNotFound();
    }

    public function test_aviso_inclui_inscrito_ativo(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        TelegramInscrito::query()->create([
            'chat_id' => '777',
            'ativo' => true,
            'inscrito_em' => now(),
        ]);

        app(TelegramAvisoService::class)->avisar('auto:1', 'ping', ['adm']);

        Http::assertSent(fn ($request) => (string) $request['chat_id'] === '777');
    }
}
