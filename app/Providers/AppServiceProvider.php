<?php

namespace App\Providers;

use App\Contracts\ConsultaReceitaInterface;
use App\Services\Receita\HttpConsultaReceita;
use App\Services\Receita\MockConsultaReceita;
use Illuminate\Support\Facades\Http;
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
        // Windows local: proxy/AV injeta certificado self-signed → cURL 60.
        if ($this->app->environment('local')) {
            Http::globalOptions([
                'verify' => false,
            ]);
        }
    }
}
