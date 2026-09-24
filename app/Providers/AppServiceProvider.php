<?php

namespace App\Providers;

use App\Events\VendedorForaTerritorio;
use App\Events\VisitaConcluida;
use App\Listeners\EnviarAvisoTelegramForaTerritorio;
use App\Listeners\EnviarAvisoTelegramVisitaConcluida;
use App\Contracts\ConsultaReceitaInterface;
use App\Services\Receita\HttpConsultaReceita;
use App\Services\Receita\MockConsultaReceita;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsultaReceitaInterface::class, function ($app) {
            return match (config('prospecta.receita_ws.driver', 'mock')) {
                'http' => $app->make(HttpConsultaReceita::class),
                default => $app->make(MockConsultaReceita::class),
            };
        });
    }

    public function boot(): void
    {
        Event::listen(VendedorForaTerritorio::class, EnviarAvisoTelegramForaTerritorio::class);
        Event::listen(VisitaConcluida::class, EnviarAvisoTelegramVisitaConcluida::class);

        // Windows local: proxy/AV injeta certificado self-signed → cURL 60.
        if ($this->app->environment('local')) {
            Http::globalOptions([
                'verify' => false,
            ]);
        }

        // ngrok HTTPS: evita CSS/JS em http:// (mixed content) só quando o request já é HTTPS
        if (! $this->app->runningInConsole()) {
            $request = request();
            if ($request->isSecure() || str_starts_with((string) $request->header('X-Forwarded-Proto'), 'https')) {
                URL::forceScheme('https');
            }
        }

        // Paths relativos: mesmo build funciona em 127.0.0.1 e no ngrok (sem CORS de host errado)
        Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null) => '/'.ltrim($path, '/'));
    }
}
