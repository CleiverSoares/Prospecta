<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prospecta — integrações e produto (somente via .env)
    |--------------------------------------------------------------------------
    */

    'mapbox' => [
        'token' => env('MAPBOX_ACCESS_TOKEN'),
        'style_url' => env('MAPBOX_STYLE_URL', 'mapbox://styles/mapbox/streets-v12'),
        'token_front' => env('VITE_MAPBOX_ACCESS_TOKEN'),
        'style_url_front' => env('VITE_MAPBOX_STYLE_URL'),
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
        'places_api_key' => env('GOOGLE_PLACES_API_KEY', env('GOOGLE_MAPS_API_KEY')),
        'directions_base_url' => env(
            'GOOGLE_MAPS_DIRECTIONS_BASE_URL',
            'https://www.google.com/maps/dir/'
        ),
    ],

    'viacep' => [
        'url_base' => env('VIACEP_URL_BASE', 'https://viacep.com.br/ws'),
    ],

    'rota' => [
        'faixa_lng' => (float) env('ROTA_FAIXA_LNG', 0.004),
        'janela_ouro' => (int) env('ROTA_JANELA_OURO', 12),
    ],

    'receita_ws' => [
        'driver' => env('RECEITA_WS_DRIVER', 'mock'),
        'url_base' => env('RECEITA_WS_URL_BASE'),
        'token' => env('RECEITA_WS_TOKEN'),
        'timeout' => (int) env('RECEITA_WS_TIMEOUT', 10),
    ],

    'pwa' => [
        'nome' => env('PWA_NOME', env('APP_NAME', 'Prospecta')),
        'theme_color' => env('PWA_THEME_COLOR', '#0083C1'),
        'background_color' => env('PWA_BACKGROUND_COLOR', '#ffffff'),
    ],

    'integracoes_fake' => [
        'ploomes' => env('INTEGRACAO_PLOOMES_LABEL', 'Ploomes'),
        'rd' => env('INTEGRACAO_RD_LABEL', 'RD Station'),
        'active' => env('INTEGRACAO_ACTIVE_LABEL', 'ActiveCampaign'),
    ],

];
