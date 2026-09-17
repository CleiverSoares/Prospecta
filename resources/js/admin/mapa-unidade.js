function formatarCep(digitos) {
    const limpo = String(digitos).replace(/\D/g, '').padStart(8, '0').slice(0, 8);

    return `${limpo.slice(0, 5)}-${limpo.slice(5)}`;
}

function poligonoDoDraw(draw) {
    const data = draw.getAll();
    const feature = data.features.find((f) => {
        const coords = f.geometry?.coordinates?.[0];

        return f.geometry?.type === 'Polygon'
            && Array.isArray(coords)
            && coords.length >= 4
            && coords.every((c) => Array.isArray(c) && Number.isFinite(c[0]) && Number.isFinite(c[1]));
    });

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
        temArea: false,
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
                // simple_select evita polígono fantasma (coords null) do draw_polygon vazio
                defaultMode: 'simple_select',
            });

            this.mapa.addControl(this.draw);

            this.mapa.on('load', () => {
                if (this.poligonoInicial?.type === 'Polygon') {
                    this.draw.add({
                        type: 'Feature',
                        properties: {},
                        geometry: this.poligonoInicial,
                    });
                    this.sincronizarCampo();
                    this.ajustarVisao(mapboxgl, this.poligonoInicial);
                    this.temArea = true;
                    this.status = 'Área carregada. Use a lixeira para apagar ou o polígono para redesenhar.';
                } else {
                    this.status = 'Clique no ícone de polígono (canto do mapa) e marque os vértices da área.';
                }
            });

            this.mapa.on('draw.create', () => this.aoDesenhar());
            this.mapa.on('draw.update', () => this.aoDesenhar());
            this.mapa.on('draw.delete', () => this.aoApagar());
            this.mapa.on('draw.modechange', (e) => this.aoMudarModo(e));
        },

        iniciarDesenho() {
            if (!this.draw) {
                return;
            }

            this.erro = '';
            this.draw.deleteAll();
            this.sincronizarCampo();
            this.temArea = false;
            this.draw.changeMode('draw_polygon');
            this.status = 'Clique no mapa para os vértices. Dê duplo clique (ou clique no primeiro ponto) para fechar a área.';
        },

        aoMudarModo(e) {
            if (e.mode === 'draw_polygon' && !this.temArea) {
                this.status = 'Clique no mapa para os vértices. Dê duplo clique para fechar a área.';
            }
        },

        sincronizarCampo() {
            const poligono = poligonoDoDraw(this.draw);
            const campo = this.$refs.poligono;

            if (campo) {
                campo.value = poligono ? JSON.stringify(poligono) : '';
            }

            this.temArea = Boolean(poligono);
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
            const features = this.draw.getAll().features.filter((f) => {
                const coords = f.geometry?.coordinates?.[0];

                return f.geometry?.type === 'Polygon'
                    && Array.isArray(coords)
                    && coords.length >= 4
                    && coords.every((c) => Array.isArray(c) && Number.isFinite(c[0]));
            });

            if (features.length > 1) {
                const manter = features[features.length - 1];
                this.draw.deleteAll();
                this.draw.add(manter);
            }

            this.sincronizarCampo();

            if (!this.temArea) {
                return;
            }

            await this.estimarCeps();
        },

        aoApagar() {
            this.sincronizarCampo();
            this.status = 'Área removida. Clique em “Desenhar área” ou no ícone de polígono para marcar de novo.';
            this.erro = '';
        },

        async estimarCeps() {
            const poligono = poligonoDoDraw(this.draw);

            if (!poligono || !this.estimarUrl) {
                return;
            }

            this.status = 'Estimando CEPs da área…';
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

                this.status = `Área marcada. Faixa estimada: ${formatarCep(dados.cep_inicio)} → ${formatarCep(dados.cep_fim)}`;
            } catch (e) {
                this.erro = e.message || 'Erro ao estimar CEPs.';
                this.status = 'Área marcada, mas a estimativa de CEP falhou.';
            }
        },
    }));
}
