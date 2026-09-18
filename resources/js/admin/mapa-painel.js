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
            el.style.cssText = `width:${pulse ? 16 : 12}px;height:${pulse ? 16 : 12}px;border-radius:999px;background:${cor};border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.25)${pulse ? ',0 0 0 6px rgba(0,131,193,.25)' : ''}`;
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
                const m = new this.mapboxgl.Marker({ element: this.pinEl('#22c55e', true) })
                    .setLngLat([v.lng, v.lat])
                    .setPopup(new this.mapboxgl.Popup({ offset: 14 }).setHTML(
                        `<strong>${v.nome || 'Vendedor'}</strong><br><span style="font-size:12px">há ${v.idade_segundos ?? 0}s</span>`,
                    ))
                    .addTo(this.mapa);
                this.markersAoVivo.push(m);
            });
            this.aplicarCamadas();
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
                const m = new mapboxgl.Marker({ element: this.pinEl(p.is_cliente ? '#0083C1' : '#e11d48') })
                    .setLngLat([p.lng, p.lat])
                    .setPopup(new mapboxgl.Popup({ offset: 12 }).setHTML(`<strong>${p.nome || 'Lead'}</strong>`))
                    .addTo(this.mapa);
                this.markersProspectos.push(m);
            });

            this.visitas.forEach((v) => {
                if (v.lat == null || v.lng == null) return;
                bounds.extend([v.lng, v.lat]);
                const m = new mapboxgl.Marker({ element: this.pinEl('#64748b') })
                    .setLngLat([v.lng, v.lat])
                    .setPopup(new mapboxgl.Popup({ offset: 12 }).setHTML(`<strong>${v.nome || 'Visita'}</strong><br><span style="font-size:12px">${v.status || ''}</span>`))
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
