function formatarCep(digitos) {
    const limpo = String(digitos).replace(/\D/g, '').padStart(8, '0').slice(0, 8);

    return `${limpo.slice(0, 5)}-${limpo.slice(5)}`;
}

function poligonoDoDraw(draw) {
    const data = draw.getAll();
    const feature = data.features.find((f) => f.geometry?.type === 'Polygon');

    return feature ? feature.geometry : null;
}

export function registrarMapaUnidade(Alpine) {
    Alpine.data('mapaUnidade', (config) => ({
        token: config.token || '',
        styleUrl: config.styleUrl || 'mapbox://styles/mapbox/streets-v12',
        estimarUrl: config.estimarUrl || '',
        csrf: config.csrf || '',
        poligonoInicial: config.poligonoInicial || null,
        status: '',
        erro: '',
        mapa: null,
        draw: null,

        init() {
            if (!this.token) {
                this.erro = 'Configure VITE_MAPBOX_ACCESS_TOKEN e MAPBOX_ACCESS_TOKEN no .env.';

                return;
            }

            this.$nextTick(() => this.iniciarMapa());
        },

        async iniciarMapa() {
            const container = this.$refs.mapa;

            if (!container || this.mapa) {
                return;
            }

            const [{ default: mapboxgl }, { default: MapboxDraw }] = await Promise.all([
                import('mapbox-gl'),
                import('@mapbox/mapbox-gl-draw'),
            ]);

            await Promise.all([
                import('mapbox-gl/dist/mapbox-gl.css'),
                import('@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css'),
            ]);

            mapboxgl.accessToken = this.token;

            this.mapa = new mapboxgl.Map({
                container,
                style: this.styleUrl,
                center: [-43.94, -19.92],
                zoom: 11,
            });

            this.mapa.addControl(new mapboxgl.NavigationControl(), 'top-right');

            this.draw = new MapboxDraw({
                displayControlsDefault: false,
                controls: {
                    polygon: true,
                    trash: true,
                },
                defaultMode: 'draw_polygon',
            });

            this.mapa.addControl(this.draw);

            if (this.poligonoInicial?.type === 'Polygon') {
                this.draw.add({
                    type: 'Feature',
                    properties: {},
                    geometry: this.poligonoInicial,
                });
                this.draw.changeMode('simple_select');
                this.sincronizarCampo();
                this.ajustarVisao(mapboxgl, this.poligonoInicial);
            }

            this.mapa.on('draw.create', () => this.aoDesenhar());
            this.mapa.on('draw.update', () => this.aoDesenhar());
            this.mapa.on('draw.delete', () => this.aoApagar());
        },

        sincronizarCampo() {
            const poligono = poligonoDoDraw(this.draw);
            const campo = this.$refs.poligono;

            if (campo) {
                campo.value = poligono ? JSON.stringify(poligono) : '';
            }
        },

        ajustarVisao(mapboxgl, poligono) {
            const coords = poligono?.coordinates?.[0];

            if (!coords?.length) {
                return;
            }

            const bounds = coords.reduce(
                (b, c) => b.extend(c),
                new mapboxgl.LngLatBounds(coords[0], coords[0]),
            );

            this.mapa.fitBounds(bounds, { padding: 48, maxZoom: 14 });
        },

        async aoDesenhar() {
            const features = this.draw.getAll().features.filter((f) => f.geometry?.type === 'Polygon');

            if (features.length > 1) {
                const manter = features[features.length - 1];
                this.draw.deleteAll();
                this.draw.add(manter);
            }

            this.sincronizarCampo();
            await this.estimarCeps();
        },

        aoApagar() {
            this.sincronizarCampo();
            this.status = '';
            this.erro = '';
        },

        async estimarCeps() {
            const poligono = poligonoDoDraw(this.draw);

            if (!poligono || !this.estimarUrl) {
                return;
            }

            this.status = 'Estimando CEPs…';
            this.erro = '';

            try {
                const resposta = await fetch(this.estimarUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ poligono_geojson: poligono }),
                });

                const dados = await resposta.json();

                if (!resposta.ok) {
                    const msg = dados?.message
                        || dados?.errors?.poligono_geojson?.[0]
                        || 'Falha ao estimar CEPs.';
                    throw new Error(msg);
                }

                const inicio = document.getElementById('cep_inicio');
                const fim = document.getElementById('cep_fim');

                if (inicio) {
                    inicio.value = formatarCep(dados.cep_inicio);
                }

                if (fim) {
                    fim.value = formatarCep(dados.cep_fim);
                }

                this.status = `Faixa estimada: ${formatarCep(dados.cep_inicio)} → ${formatarCep(dados.cep_fim)}`;
            } catch (e) {
                this.erro = e.message || 'Erro ao estimar CEPs.';
                this.status = '';
            }
        },
    }));
}
