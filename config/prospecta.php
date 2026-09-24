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

    'ibge' => [
        'url_base' => env('IBGE_URL_BASE', 'https://servicodados.ibge.gov.br/api/v1/localidades'),
    ],

    'rota' => [
        'faixa_lng' => (float) env('ROTA_FAIXA_LNG', 0.004),
        // Limite de paradas (legado: ROTA_JANELA_OURO ainda funciona como alias)
        'limite_paradas' => (int) env('ROTA_LIMITE_PARADAS', env('ROTA_JANELA_OURO', 12)),
        'janela_ouro' => (int) env('ROTA_JANELA_OURO', 12),
        'predio_metros' => (float) env('ROTA_PREDIO_METROS', 50),
        'raio_prospeccao_km' => (float) env('ROTA_RAIO_PROSPECCAO_KM', 2),
        'raio_pos_venda_km' => (float) env('ROTA_RAIO_POS_VENDA_KM', 15),
        'duracao_visita_min' => (int) env('ROTA_DURACAO_VISITA_MIN', 30),
        'velocidade_kmh' => (float) env('ROTA_VELOCIDADE_KMH', 20),
        'almoco_inicio' => env('ROTA_ALMOCO_INICIO', '12:00'),
        'almoco_duracao_min' => (int) env('ROTA_ALMOCO_DURACAO_MIN', 60),
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

    'ajuda_alterdata_url' => env('AJUDA_ALTERDATA_URL', 'https://ajuda.alterdata.com.br'),

    'cerca_dias' => (int) env('CERCA_TEMPORARIA_DIAS', 30),

    'tracking' => [
        'intervalo_ms' => (int) env('TRACKING_INTERVALO_MS', 12000),
        'janela_minutos' => (int) env('TRACKING_JANELA_MINUTOS', 15),
        'alerta_sem_sinal_segundos' => (int) env('TRACKING_ALERTA_SEM_SINAL_SEGUNDOS', 180),
        'alerta_parado_ms' => (float) env('TRACKING_ALERTA_PARADO_MS', 1.0),
    ],

    'meta_visitas_dia' => (int) env('META_VISITAS_DIA', 8),

    /*
    | Portfolio / Render: habilita GET/POST /admin/demo para regenerar GPS ao vivo
    | sem artisan CLI. Manter false em produção real.
    */
    'demo_seed_enabled' => filter_var(env('DEMO_SEED_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | local = disco do container (some no Render sleep)
    | supabase = Storage REST (persiste; bucket público)
    */
    'storage' => [
        'driver' => env('PROSPECTA_STORAGE_DRIVER', 'local'),
        'supabase' => [
            'url' => env('SUPABASE_URL'),
            'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'bucket' => env('SUPABASE_STORAGE_BUCKET', 'prospecta'),
        ],
    ],

    /*
    | Avisos operacionais via Bot API do Telegram (adm / gestor).
    | Chat IDs: converse com o bot e use getUpdates, ou um grupo com o bot.
    */
    'telegram' => [
        'enabled' => filter_var(env('TELEGRAM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_adm' => env('TELEGRAM_CHAT_ADM'),
        'chat_gestor' => env('TELEGRAM_CHAT_GESTOR'),
        'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 8),
        'debounce_minutos' => (int) env('TELEGRAM_DEBOUNCE_MINUTOS', 30),
        'avisos' => [
            'fora_territorio' => filter_var(env('TELEGRAM_AVISO_FORA_TERRITORIO', true), FILTER_VALIDATE_BOOLEAN),
            'visita_concluida' => filter_var(env('TELEGRAM_AVISO_VISITA', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

];
