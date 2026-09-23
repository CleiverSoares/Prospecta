@props([
    'titulo' => 'Prospecta',
    'passo' => null,
])

@php
    $passos = [
        [
            'id' => 'setup',
            'rotulo' => 'Setup',
            'rota' => 'app.setup',
            'icone' => 'setup',
        ],
        [
            'id' => 'area',
            'rotulo' => 'Área',
            'rota' => 'app.area',
            'icone' => 'area',
        ],
        [
            'id' => 'rota',
            'rotulo' => 'Rota',
            'rota' => 'app.rota',
            'icone' => 'rota',
        ],
        [
            'id' => 'checkin',
            'rotulo' => 'Check-in',
            'rota' => 'app.checkin',
            'icone' => 'checkin',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('prospecta.pwa.theme_color', '#0083C1') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} — {{ config('prospecta.pwa.nome', 'Prospecta') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-dvh font-sans text-ink antialiased">
    <div class="pwa-shell min-h-dvh lg:flex">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-surface-line bg-white lg:flex">
            <div class="border-b border-brand-strong/20 bg-gradient-to-br from-brand to-brand-strong px-5 py-4">
                <p class="text-lg font-semibold text-white">Prospecta</p>
                <p class="text-xs text-white/80">Campo · vendedor</p>
            </div>
            <nav class="pwa-nav-side flex flex-1 flex-col gap-1 p-3" aria-label="Fluxo do dia">
                @foreach ($passos as $item)
                    <a
                        href="{{ route($item['rota']) }}"
                        data-ativo="{{ ($passo ?? '') === $item['id'] ? '1' : '0' }}"
                        class="pwa-step relative px-3 py-2.5 text-sm text-ink-soft hover:bg-surface-muted hover:text-ink"
                    >{{ $item['rotulo'] }}</a>
                @endforeach
            </nav>
            <div class="border-t border-surface-line p-4">
                <div class="flex items-center gap-3">
                    @if (auth()->user()?->foto_url)
                        <img src="{{ auth()->user()->foto_url }}" alt="" class="size-10 shrink-0 rounded-full object-cover ring-2 ring-brand/20">
                    @else
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand text-sm font-bold text-white">
                            {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'V', 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ auth()->user()?->name }}</p>
                        <p class="truncate text-xs text-ink-faint">{{ auth()->user()?->unidade?->nome ?: 'Campo' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-brand hover:text-brand-strong">Sair</button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col pb-[calc(5.5rem+env(safe-area-inset-bottom,0px))] lg:pb-0">
            <header class="sticky top-0 z-20 border-b border-surface-line/80 bg-white/95 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur-sm sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-brand">Prospecta</p>
                    <div class="pwa-user-chip" title="{{ auth()->user()?->name }}">
                        @if (auth()->user()?->foto_url)
                            <img src="{{ auth()->user()->foto_url }}" alt="" class="pwa-user-chip__foto">
                        @else
                            <span class="pwa-user-chip__inicial">
                                {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'V', 0, 1)) }}
                            </span>
                        @endif
                        <span class="pwa-user-chip__nome">{{ explode(' ', trim(auth()->user()?->name ?? ''))[0] ?? '' }}</span>
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-2">
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $titulo }}</h1>
                        @isset($subtitulo)
                            <p class="mt-0.5 text-sm text-ink-soft">{{ $subtitulo }}</p>
                        @endisset
                    </div>
                    @isset($acoes)
                        <div class="flex flex-wrap gap-2">{{ $acoes }}</div>
                    @endisset
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-4 sm:px-6 lg:px-8 lg:py-6">
                {{ $slot }}
            </main>
        </div>

        {{-- Tab bar estilo app --}}
        <nav class="pwa-tabbar lg:hidden" aria-label="Fluxo do dia">
            <div class="pwa-tabbar-inner">
                @foreach ($passos as $item)
                    @php $ativo = ($passo ?? '') === $item['id']; @endphp
                    <a
                        href="{{ route($item['rota']) }}"
                        class="pwa-tab {{ $ativo ? 'is-active' : '' }}"
                        data-ativo="{{ $ativo ? '1' : '0' }}"
                    >
                        <span class="pwa-tab-icon" aria-hidden="true">
                            @if ($item['icone'] === 'setup')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                            @elseif ($item['icone'] === 'area')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8 11h6M11 8v6"/></svg>
                            @elseif ($item['icone'] === 'rota')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h6a4 4 0 0 0 0-8H8a4 4 0 0 1 0-8h8"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            @endif
                        </span>
                        <span class="pwa-tab-label">{{ $item['rotulo'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

    <script>
        window.prospectaTracking = {
            url: @js(route('app.localizacao.store')),
            csrf: @js(csrf_token()),
            intervaloMs: @js((int) config('prospecta.tracking.intervalo_ms', 12000)),
            filaKey: 'prospecta.gps_fila',
        };

        (function iniciarTrackingCampo() {
            const cfg = window.prospectaTracking;
            if (!cfg?.url || !navigator.geolocation) return;

            const lerFila = () => {
                try { return JSON.parse(localStorage.getItem(cfg.filaKey) || '[]'); }
                catch (e) { return []; }
            };
            const gravarFila = (itens) => {
                try { localStorage.setItem(cfg.filaKey, JSON.stringify(itens.slice(-80))); }
                catch (e) {}
            };
            const postar = async (payload) => {
                const res = await fetch(cfg.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error('fail');
            };
            const enfileirar = (payload) => {
                const fila = lerFila();
                fila.push(payload);
                gravarFila(fila);
            };
            const drenarFila = async () => {
                const fila = lerFila();
                if (!fila.length || !navigator.onLine) return;
                const restam = [];
                for (const item of fila) {
                    try { await postar(item); }
                    catch (e) { restam.push(item); break; }
                }
                gravarFila(restam);
            };

            let ultimoEnvio = 0;
            const enviar = (pos) => {
                const agora = Date.now();
                if (agora - ultimoEnvio < cfg.intervaloMs) return;
                ultimoEnvio = agora;
                const payload = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    precisao: pos.coords.accuracy ?? null,
                    velocidade: pos.coords.speed != null && pos.coords.speed >= 0 ? pos.coords.speed : null,
                    direcao: pos.coords.heading != null && pos.coords.heading >= 0 ? pos.coords.heading : null,
                };
                if (!navigator.onLine) {
                    enfileirar(payload);
                    return;
                }
                postar(payload).catch(() => enfileirar(payload));
            };

            navigator.geolocation.watchPosition(enviar, () => {}, {
                enableHighAccuracy: true,
                maximumAge: 5000,
                timeout: 20000,
            });

            window.addEventListener('online', () => drenarFila());
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') drenarFila();
            });
            drenarFila();
        })();

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const regs = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(regs.map((r) => r.unregister()));
                    if (window.caches) {
                        const keys = await caches.keys();
                        await Promise.all(keys.map((k) => caches.delete(k)));
                    }
                } catch (e) {}
                navigator.serviceWorker.register('/sw.js?v=9').catch(() => {});
            });
        }
    </script>
    {{ $scripts ?? '' }}
</body>
</html>
