import { poligonoValidoDoDraw, resumirProspectosNaArea } from './geo';

export function registrarMapaPainel(Alpine) {
    Alpine.data('mapaPainel', (config) => ({
        token: config.token || '',
        styleUrl: config.styleUrl || 'mapbox://styles/mapbox/streets-v12',
        unidades: Array.isArray(config.unidades) ? config.unidades : [],
        prospectos: Array.isArray(config.prospectos) ? config.prospectos : [],
        visitas: Array.isArray(config.visitas) ? config.visitas : [],
        aoVivo: Array.isArray(config.aoVivo) ? config.aoVivo : [],
        aoVivoUrl: config.aoVivoUrl || '',
        trajetoUrlBase: config.trajetoUrlBase || '',
        rascunhoUrl: config.rascunhoUrl || '',
        csrf: config.csrf || '',
        podeCriarUnidade: Boolean(config.podeCriarUnidade),
        filtros: config.filtros || {},
        janelaMinutos: config.janelaMinutos || 15,
        camadas: { unidades: true, prospectos: true, visitas: true, calor: false, aoVivo: true },
        erro: '',
        carregando: true,
        selecionando: false,
        temSelecao: false,
        enviandoUnidade: false,
        resumo: { total: 0, clientes: 0, leads: 0 },
        poligonoSelecao: null,
        mapa: null,
        mapboxgl: null,
        draw: null,
        markersProspectos: [],
        markersVisitas: [],
        markersAoVivo: [],
        pollTimer: null,

        init() {
            if (!this.token) {
                this.carregando = false;
                this.erro = 'Configure MAPBOX_ACCESS_TOKEN no .env e rode npm run build.';
                return;
            }
            this.$nextTick(() => this.iniciarMapa());
            this.$watch('camadas.aoVivo', (on) => {
                if (on) this.iniciarPoll();
                else this.pararPoll();
            });
        },

        destroy() {
            this.pararPoll();
        },

        toggleCamada(nome) {
            this.camadas[nome] = !this.camadas[nome];
            this.aplicarCamadas();
        },

        aplicarCamadas() {
            if (!this.mapa) return;
            const visU = this.camadas.unidades ? 'visible' : 'none';
            if (this.mapa.getLayer('unidades-fill')) this.mapa.setLayoutProperty('unidades-fill', 'visibility', visU);
            if (this.mapa.getLayer('unidades-line')) this.mapa.setLayoutProperty('unidades-line', 'visibility', visU);
            if (this.mapa.getLayer('calor-heat')) {
                this.mapa.setLayoutProperty('calor-heat', 'visibility', this.camadas.calor ? 'visible' : 'none');
            }

            this.markersProspectos.forEach((m) => {
                m.getElement().style.display = this.camadas.prospectos ? '' : 'none';
            });
            this.markersVisitas.forEach((m) => {
                m.getElement().style.display = this.camadas.visitas ? '' : 'none';
            });
            this.markersAoVivo.forEach((m) => {
                m.getElement().style.display = this.camadas.aoVivo ? '' : 'none';
            });
        },

        pinEl(cor, pulse = false) {
            const el = document.createElement('div');
            el.className = pulse ? 'mapa-pin mapa-pin--pulse' : 'mapa-pin';
            el.style.cssText = [
                'width:28px',
                'height:36px',
                'cursor:pointer',
                'transform-origin:center bottom',
                'filter:drop-shadow(0 2px 4px rgba(0,0,0,.35))',
            ].join(';');
            el.innerHTML = `
                <svg viewBox="0 0 28 36" width="28" height="36" aria-hidden="true" style="display:block;overflow:visible">
                    <path fill="${cor}" stroke="#fff" stroke-width="1.6"
                        d="M14 1.2C7.04 1.2 1.4 6.84 1.4 13.8c0 9.3 12.6 20.8 12.6 20.8S26.6 23.1 26.6 13.8C26.6 6.84 20.96 1.2 14 1.2z"/>
                    <circle cx="14" cy="13.2" r="4.2" fill="#fff"/>
                </svg>
            `;
            if (pulse) {
                el.style.animation = 'mapa-pin-pulse 1.6s ease-out infinite';
            }
            return el;
        },

        limparAoVivo() {
            this.markersAoVivo.forEach((m) => m.remove());
            this.markersAoVivo = [];
        },

        desenharAoVivo(lista) {
            if (!this.mapa || !this.mapboxgl) return;
            this.limparAoVivo();
            (lista || []).forEach((v) => {
                if (v.lat == null || v.lng == null) return;
                const cor = this.corAlerta(v.status || v.alertas?.[0]);
                const m = new this.mapboxgl.Marker({ element: this.pinEl(cor, true), anchor: 'bottom' })
                    .setLngLat([v.lng, v.lat])
                    .setPopup(new this.mapboxgl.Popup({ offset: 28, maxWidth: '280px' }).setHTML(
                        this.htmlPopupVendedor(v),
                    ))
                    .addTo(this.mapa);
                m.getElement()?.addEventListener('click', () => this.carregarTrajeto(v.user_id));
                this.markersAoVivo.push(m);
            });
            this.aplicarCamadas();
            if (this.filtros?.vendedor_id) {
                this.carregarTrajeto(this.filtros.vendedor_id);
            }
        },

        corAlerta(status) {
            if (status === 'fora_territorio') return '#e11d48';
            if (status === 'sem_sinal') return '#f59e0b';
            if (status === 'parado') return '#a855f7';
            return '#22c55e';
        },

        async carregarTrajeto(userId) {
            if (!userId || !this.trajetoUrlBase || !this.mapa) return;
            try {
                const url = `${this.trajetoUrlBase.replace(/\/$/, '')}/${userId}/trajeto?minutos=180`;
                const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) return;
                const dados = await res.json();
                this.desenharTrajeto(dados.pontos || []);
            } catch (e) {}
        },

        desenharTrajeto(pontos) {
            if (!this.mapa || !this.mapa.getStyle) return;
            const coords = (pontos || [])
                .filter((p) => p.lat != null && p.lng != null)
                .map((p) => [p.lng, p.lat]);

            const geo = {
                type: 'Feature',
                properties: {},
                geometry: { type: 'LineString', coordinates: coords },
            };

            if (this.mapa.getSource('trajeto-vendedor')) {
                this.mapa.getSource('trajeto-vendedor').setData(geo);
            } else {
                this.mapa.addSource('trajeto-vendedor', { type: 'geojson', data: geo });
                this.mapa.addLayer({
                    id: 'trajeto-vendedor-line',
                    type: 'line',
                    source: 'trajeto-vendedor',
                    paint: {
                        'line-color': '#0ea5e9',
                        'line-width': 3,
                        'line-opacity': 0.85,
                    },
                });
            }

            if (coords.length >= 2) {
                const bounds = coords.reduce(
                    (b, c) => b.extend(c),
                    new this.mapboxgl.LngLatBounds(coords[0], coords[0]),
                );
                this.mapa.fitBounds(bounds, { padding: 48, maxZoom: 14 });
            }
        },

        htmlPopupVendedor(v) {
            const nome = this.escaparHtml(v.nome || 'Vendedor');
            const unidade = v.unidade_nome
                ? this.escaparHtml(
                    [v.unidade_tipo, v.unidade_nome].filter(Boolean).join(' · '),
                )
                : 'Sem unidade';
            const idade = this.formatarIdade(v.idade_segundos);
            const alerta = (v.alertas || []).length
                ? `<div style="font-size:11px;color:#e11d48;margin-top:4px">${this.escaparHtml((v.alertas || []).join(' · '))}</div>`
                : '';
            const foto = v.foto_url
                ? `<img src="${this.escaparAttr(v.foto_url)}" alt="" width="48" height="48" style="width:48px;height:48px;border-radius:9999px;object-fit:cover;flex-shrink:0;border:2px solid #e2e8f0">`
                : `<div style="width:48px;height:48px;border-radius:9999px;background:#0ea5e9;color:#071018;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0">${this.iniciais(v.nome)}</div>`;

            return `<div style="display:flex;gap:12px;align-items:center;min-width:200px;padding:2px 0">
                ${foto}
                <div style="min-width:0">
                    <div style="font-weight:700;font-size:14px;color:#0f172a;line-height:1.2">${nome}</div>
                    <div style="font-size:12px;color:#475569;margin-top:3px;line-height:1.3">${unidade}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px">${idade}</div>
                    ${alerta}
                </div>
            </div>`;
        },

        iniciais(nome) {
            const partes = String(nome || 'V').trim().split(/\s+/).filter(Boolean);
            const letras = (partes[0]?.[0] || 'V') + (partes[1]?.[0] || '');
            return this.escaparHtml(letras.toUpperCase());
        },

        formatarIdade(segundos) {
            const s = Math.max(0, Math.floor(Number(segundos) || 0));
            if (s < 60) return `há ${s}s`;
            if (s < 3600) return `há ${Math.floor(s / 60)} min`;
            return `há ${Math.floor(s / 3600)} h`;
        },

        escaparHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        escaparAttr(valor) {
            return this.escaparHtml(valor).replace(/`/g, '');
        },

        async puxarAoVivo() {
            if (!this.aoVivoUrl || !this.camadas.aoVivo) return;
            const params = new URLSearchParams({ minutos: String(this.janelaMinutos) });
            ['unidade_id', 'gestor_id', 'vendedor_id'].forEach((k) => {
                if (this.filtros?.[k]) params.set(k, this.filtros[k]);
            });
            try {
                const res = await fetch(`${this.aoVivoUrl}?${params}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const dados = await res.json();
                this.aoVivo = dados.vendedores || [];
                this.desenharAoVivo(this.aoVivo);
            } catch (e) {}
        },

        iniciarPoll() {
            this.pararPoll();
            this.puxarAoVivo();
            this.pollTimer = setInterval(() => this.puxarAoVivo(), 8000);
        },

        pararPoll() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async iniciarMapa() {
            const container = this.$refs.mapa;
            if (!container || this.mapa) return;

            try {
                const [{ default: mapboxgl }, { default: MapboxDraw }] = await Promise.all([
                    import('mapbox-gl'),
                    import('@mapbox/mapbox-gl-draw'),
                ]);
                await import('@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css');

                this.mapboxgl = mapboxgl;
                mapboxgl.accessToken = this.token;

                this.mapa = new mapboxgl.Map({
                    container,
                    style: this.styleUrl,
                    center: [-43.5, -22.7],
                    zoom: 8,
                    attributionControl: true,
                });
                this.mapa.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

                this.draw = new MapboxDraw({
                    displayControlsDefault: false,
                    controls: {},
                    defaultMode: 'simple_select',
                });
                this.mapa.addControl(this.draw);

                this.mapa.on('draw.create', () => this.aoSelecionarArea());
                this.mapa.on('draw.update', () => this.aoSelecionarArea());
                this.mapa.on('draw.delete', () => this.limparSelecao());

                this.mapa.on('error', (e) => {
                    const msg = e?.error?.message || 'Falha ao carregar tiles do Mapbox.';
                    this.erro = msg;
                    this.carregando = false;
                });

                this.mapa.on('load', () => {
                    this.carregando = false;
                    this.erro = '';
                    requestAnimationFrame(() => {
                        this.mapa?.resize();
                        this.desenharCamadasIniciais(mapboxgl);
                    });
                });
            } catch (e) {
                this.carregando = false;
                this.erro = e?.message || 'Não foi possível carregar o Mapbox. Rode npm.cmd run build.';
            }
        },

        iniciarSelecaoArea() {
            if (!this.draw || !this.podeCriarUnidade) return;
            this.selecionando = true;
            this.limparSelecao();
            this.draw.changeMode('draw_polygon');
        },

        limparSelecao() {
            this.temSelecao = false;
            this.selecionando = false;
            this.poligonoSelecao = null;
            this.resumo = { total: 0, clientes: 0, leads: 0 };
            if (this.draw) this.draw.deleteAll();
        },

        aoSelecionarArea() {
            const features = this.draw.getAll().features.filter((f) => f.geometry?.type === 'Polygon');
            if (features.length > 1) {
                const manter = features[features.length - 1];
                this.draw.deleteAll();
                this.draw.add(manter);
            }

            const poligono = poligonoValidoDoDraw(this.draw);
            if (!poligono) {
                this.limparSelecao();
                return;
            }

            this.poligonoSelecao = poligono;
            this.resumo = resumirProspectosNaArea(this.prospectos, poligono);
            this.temSelecao = true;
            this.selecionando = false;
        },

        criarUnidadeDaArea() {
            if (!this.poligonoSelecao || !this.rascunhoUrl || this.enviandoUnidade) return;
            this.enviandoUnidade = true;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.rascunhoUrl;
            form.style.display = 'none';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = this.csrf;
            form.appendChild(csrf);

            const poly = document.createElement('input');
            poly.type = 'hidden';
            poly.name = 'poligono_geojson';
            poly.value = JSON.stringify(this.poligonoSelecao);
            form.appendChild(poly);

            document.body.appendChild(form);
            form.submit();
        },

        desenharCamadasIniciais(mapboxgl) {
            if (!this.mapa) return;

            const features = this.unidades
                .filter((u) => u.poligono?.type === 'Polygon')
                .map((u) => ({
                    type: 'Feature',
                    properties: { nome: u.nome || 'Unidade' },
                    geometry: u.poligono,
                }));

            const bounds = new mapboxgl.LngLatBounds();

            if (features.length) {
                this.mapa.addSource('unidades-territorio', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features },
                });
                this.mapa.addLayer({
                    id: 'unidades-fill',
                    type: 'fill',
                    source: 'unidades-territorio',
                    paint: { 'fill-color': '#0083C1', 'fill-opacity': 0.22 },
                });
                this.mapa.addLayer({
                    id: 'unidades-line',
                    type: 'line',
                    source: 'unidades-territorio',
                    paint: { 'line-color': '#006ea3', 'line-width': 2.2 },
                });

                features.forEach((f) => {
                    (f.geometry.coordinates?.[0] || []).forEach((c) => {
                        if (Array.isArray(c) && Number.isFinite(c[0])) bounds.extend(c);
                    });
                });

                this.mapa.on('click', 'unidades-fill', (e) => {
                    const nome = e.features?.[0]?.properties?.nome;
                    if (!nome) return;
                    new mapboxgl.Popup().setLngLat(e.lngLat).setHTML(`<strong>${nome}</strong>`).addTo(this.mapa);
                });
            }

            this.prospectos.forEach((p) => {
                if (p.lat == null || p.lng == null) return;
                bounds.extend([p.lng, p.lat]);
                const m = new mapboxgl.Marker({ element: this.pinEl(p.is_cliente ? '#0083C1' : '#e11d48'), anchor: 'bottom' })
                    .setLngLat([p.lng, p.lat])
                    .setPopup(new mapboxgl.Popup({ offset: 28 }).setHTML(
                        `<strong>${p.nome || (p.is_cliente ? 'Cliente' : 'Lead')}</strong>`
                        + `<br><span style="font-size:12px;color:#64748b">${p.is_cliente ? 'Cliente' : 'Lead'}</span>`,
                    ))
                    .addTo(this.mapa);
                this.markersProspectos.push(m);
            });

            this.visitas.forEach((v) => {
                if (v.lat == null || v.lng == null) return;
                bounds.extend([v.lng, v.lat]);
                const m = new mapboxgl.Marker({ element: this.pinEl('#64748b'), anchor: 'bottom' })
                    .setLngLat([v.lng, v.lat])
                    .setPopup(new mapboxgl.Popup({ offset: 28 }).setHTML(`<strong>${v.nome || 'Visita'}</strong><br><span style="font-size:12px">${v.status || ''}</span>`))
                    .addTo(this.mapa);
                this.markersVisitas.push(m);
            });

            const heatPoints = this.prospectos
                .filter((p) => p.lat != null && !p.is_cliente)
                .map((p) => ({
                    type: 'Feature',
                    properties: { weight: 1 },
                    geometry: { type: 'Point', coordinates: [p.lng, p.lat] },
                }));

            if (heatPoints.length) {
                this.mapa.addSource('calor-oportunidade', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: heatPoints },
                });
                this.mapa.addLayer({
                    id: 'calor-heat',
                    type: 'heatmap',
                    source: 'calor-oportunidade',
                    layout: { visibility: 'none' },
                    paint: {
                        'heatmap-weight': 1,
                        'heatmap-intensity': 1.1,
                        'heatmap-radius': 28,
                        'heatmap-opacity': 0.7,
                        'heatmap-color': [
                            'interpolate', ['linear'], ['heatmap-density'],
                            0, 'rgba(0,131,193,0)',
                            0.4, 'rgba(0,131,193,0.45)',
                            0.8, 'rgba(225,29,72,0.65)',
                            1, 'rgba(225,29,72,0.9)',
                        ],
                    },
                });
            }

            if (!bounds.isEmpty()) {
                this.mapa.fitBounds(bounds, { padding: 64, maxZoom: 12 });
            }

            this.desenharAoVivo(this.aoVivo);
            this.aplicarCamadas();
            if (this.camadas.aoVivo) this.iniciarPoll();
            this.mapa.resize();
        },
    }));
}
