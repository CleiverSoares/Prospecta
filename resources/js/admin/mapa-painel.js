export function registrarMapaPainel(Alpine) {
    Alpine.data('mapaPainel', (config) => ({
        token: config.token || '',
        styleUrl: config.styleUrl || 'mapbox://styles/mapbox/streets-v12',
        unidades: Array.isArray(config.unidades) ? config.unidades : [],
        prospectos: Array.isArray(config.prospectos) ? config.prospectos : [],
        visitas: Array.isArray(config.visitas) ? config.visitas : [],
        aoVivo: Array.isArray(config.aoVivo) ? config.aoVivo : [],
        aoVivoUrl: config.aoVivoUrl || '',
        filtros: config.filtros || {},
        janelaMinutos: config.janelaMinutos || 15,
        camadas: { unidades: true, prospectos: true, visitas: true, calor: false, aoVivo: true },
        erro: '',
        mapa: null,
        mapboxgl: null,
        markersProspectos: [],
        markersVisitas: [],
        markersAoVivo: [],
        pollTimer: null,

        init() {
            if (!this.token) {
                this.erro = 'Configure VITE_MAPBOX_ACCESS_TOKEN e MAPBOX_ACCESS_TOKEN no .env para ver o mapa.';
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

            const { default: mapboxgl } = await import('mapbox-gl');
            await import('mapbox-gl/dist/mapbox-gl.css');
            this.mapboxgl = mapboxgl;
            mapboxgl.accessToken = this.token;

            this.mapa = new mapboxgl.Map({
                container,
                style: this.styleUrl,
                center: [-43.5, -22.7],
                zoom: 8,
            });
            this.mapa.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

            this.mapa.on('load', () => {
                const features = this.unidades
                    .filter((u) => u.poligono?.type === 'Polygon')
                    .map((u) => ({
                        type: 'Feature',
                        properties: { nome: u.nome || 'Unidade' },
                        geometry: u.poligono,
                    }));

                if (features.length) {
                    this.mapa.addSource('unidades-territorio', {
                        type: 'geojson',
                        data: { type: 'FeatureCollection', features },
                    });
                    this.mapa.addLayer({
                        id: 'unidades-fill',
                        type: 'fill',
                        source: 'unidades-territorio',
                        paint: { 'fill-color': '#0083C1', 'fill-opacity': 0.2 },
                    });
                    this.mapa.addLayer({
                        id: 'unidades-line',
                        type: 'line',
                        source: 'unidades-territorio',
                        paint: { 'line-color': '#0083C1', 'line-width': 2 },
                    });

                    const bounds = new mapboxgl.LngLatBounds();
                    features.forEach((f) => {
                        (f.geometry.coordinates?.[0] || []).forEach((c) => {
                            if (Array.isArray(c) && Number.isFinite(c[0])) bounds.extend(c);
                        });
                    });
                    if (!bounds.isEmpty()) this.mapa.fitBounds(bounds, { padding: 56, maxZoom: 12 });

                    this.mapa.on('click', 'unidades-fill', (e) => {
                        const nome = e.features?.[0]?.properties?.nome;
                        if (!nome) return;
                        new mapboxgl.Popup().setLngLat(e.lngLat).setHTML(`<strong>${nome}</strong>`).addTo(this.mapa);
                    });
                } else if (!this.prospectos.length && !this.visitas.length && !this.aoVivo.length) {
                    this.erro = 'Sem polígonos nem pins para exibir ainda.';
                }

                this.prospectos.forEach((p) => {
                    if (p.lat == null || p.lng == null) return;
                    const m = new mapboxgl.Marker({ element: this.pinEl(p.is_cliente ? '#0083C1' : '#e11d48') })
                        .setLngLat([p.lng, p.lat])
                        .setPopup(new mapboxgl.Popup({ offset: 12 }).setHTML(`<strong>${p.nome || 'Lead'}</strong>`))
                        .addTo(this.mapa);
                    this.markersProspectos.push(m);
                });

                this.visitas.forEach((v) => {
                    if (v.lat == null || v.lng == null) return;
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

                this.desenharAoVivo(this.aoVivo);
                this.aplicarCamadas();
                if (this.camadas.aoVivo) this.iniciarPoll();
            });
        },
    }));
}
