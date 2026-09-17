export function registrarMapaPainel(Alpine) {
    Alpine.data('mapaPainel', (config) => ({
        token: config.token || '',
        styleUrl: config.styleUrl || 'mapbox://styles/mapbox/streets-v12',
        unidades: Array.isArray(config.unidades) ? config.unidades : [],
        erro: '',
        mapa: null,

        init() {
            if (!this.token) {
                this.erro = 'Configure VITE_MAPBOX_ACCESS_TOKEN e MAPBOX_ACCESS_TOKEN no .env para ver o mapa.';

                return;
            }

            if (!this.unidades.length) {
                this.erro = 'Nenhuma unidade com polígono cadastrado.';

                return;
            }

            this.$nextTick(() => this.iniciarMapa());
        },

        async iniciarMapa() {
            const container = this.$refs.mapa;

            if (!container || this.mapa) {
                return;
            }

            const { default: mapboxgl } = await import('mapbox-gl');
            await import('mapbox-gl/dist/mapbox-gl.css');

            mapboxgl.accessToken = this.token;

            this.mapa = new mapboxgl.Map({
                container,
                style: this.styleUrl,
                center: [-43.5, -22.7],
                zoom: 8,
            });

            this.mapa.addControl(new mapboxgl.NavigationControl(), 'top-right');

            this.mapa.on('load', () => {
                const features = this.unidades
                    .filter((u) => u.poligono?.type === 'Polygon')
                    .map((u) => ({
                        type: 'Feature',
                        properties: { nome: u.nome || 'Unidade' },
                        geometry: u.poligono,
                    }));

                if (!features.length) {
                    this.erro = 'Nenhuma unidade com polígono válido para exibir.';

                    return;
                }

                this.mapa.addSource('unidades-territorio', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features,
                    },
                });

                this.mapa.addLayer({
                    id: 'unidades-fill',
                    type: 'fill',
                    source: 'unidades-territorio',
                    paint: {
                        'fill-color': '#0083C1',
                        'fill-opacity': 0.22,
                    },
                });

                this.mapa.addLayer({
                    id: 'unidades-line',
                    type: 'line',
                    source: 'unidades-territorio',
                    paint: {
                        'line-color': '#0083C1',
                        'line-width': 2,
                    },
                });

                const bounds = new mapboxgl.LngLatBounds();

                features.forEach((f) => {
                    (f.geometry.coordinates?.[0] || []).forEach((c) => {
                        if (Array.isArray(c) && Number.isFinite(c[0]) && Number.isFinite(c[1])) {
                            bounds.extend(c);
                        }
                    });
                });

                if (!bounds.isEmpty()) {
                    this.mapa.fitBounds(bounds, { padding: 48, maxZoom: 12 });
                }

                this.mapa.on('click', 'unidades-fill', (e) => {
                    const nome = e.features?.[0]?.properties?.nome;
                    if (!nome) {
                        return;
                    }

                    new mapboxgl.Popup()
                        .setLngLat(e.lngLat)
                        .setHTML(`<strong>${nome}</strong>`)
                        .addTo(this.mapa);
                });

                this.mapa.on('mouseenter', 'unidades-fill', () => {
                    this.mapa.getCanvas().style.cursor = 'pointer';
                });

                this.mapa.on('mouseleave', 'unidades-fill', () => {
                    this.mapa.getCanvas().style.cursor = '';
                });
            });
        },
    }));
}
