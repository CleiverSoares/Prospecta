import { poligonoValidoDoDraw, resumirProspectosNaArea } from './geo';

function formatarCep(digitos) {
    const limpo = String(digitos).replace(/\D/g, '').padStart(8, '0').slice(0, 8);

    return `${limpo.slice(0, 5)}-${limpo.slice(5)}`;
}

export function registrarMapaUnidade(Alpine) {
    Alpine.data('mapaUnidade', (config) => ({
        token: config.token || '',
        styleUrl: config.styleUrl || 'mapbox://styles/mapbox/streets-v12',
        estimarUrl: config.estimarUrl || '',
        csrf: config.csrf || '',
        poligonoInicial: config.poligonoInicial || null,
        prospectos: Array.isArray(config.prospectos) ? config.prospectos : [],
        status: '',
        erro: '',
        temArea: false,
        resumo: { total: 0, clientes: 0, leads: 0 },
        mapa: null,
        draw: null,
        mapboxgl: null,
        markersPins: [],

        init() {
            if (!this.token) {
                this.erro = 'Configure MAPBOX_ACCESS_TOKEN no .env e rode npm run build.';

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

            await import('@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css');

            this.mapboxgl = mapboxgl;
            mapboxgl.accessToken = this.token;

            this.mapa = new mapboxgl.Map({
                container,
                style: this.styleUrl,
                center: [-43.2, -22.95],
                zoom: 11,
            });

            this.mapa.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

            this.draw = new MapboxDraw({
                displayControlsDefault: false,
                controls: {
                    polygon: true,
                    trash: true,
                },
                defaultMode: 'simple_select',
            });

            this.mapa.addControl(this.draw);

            this.mapa.on('load', () => {
                this.desenharPins();
                requestAnimationFrame(() => this.mapa?.resize());

                if (this.poligonoInicial?.type === 'Polygon') {
                    this.draw.add({
                        type: 'Feature',
                        properties: {},
                        geometry: this.poligonoInicial,
                    });
                    this.sincronizarCampo();
                    this.atualizarResumo();
                    this.ajustarVisao(this.poligonoInicial);
                    this.temArea = true;
                    this.status = 'Área carregada. Ajuste os vértices ou redesenhe — formato livre (não precisa ser quadrado).';
                    this.estimarCeps();
                } else {
                    this.status = 'Veja os pins e desenhe um polígono livre em volta deles (qualquer formato).';
                    this.ajustarVisaoPins();
                }
            });

            this.mapa.on('draw.create', () => this.aoDesenhar());
            this.mapa.on('draw.update', () => this.aoDesenhar());
            this.mapa.on('draw.delete', () => this.aoApagar());
            this.mapa.on('draw.modechange', (e) => this.aoMudarModo(e));
        },

        pinEl(cor) {
            const el = document.createElement('div');
            el.style.cssText = `width:11px;height:11px;border-radius:999px;background:${cor};border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.25)`;

            return el;
        },

        desenharPins() {
            if (!this.mapa || !this.mapboxgl) return;
            this.markersPins.forEach((m) => m.remove());
            this.markersPins = [];

            this.prospectos.forEach((p) => {
                if (p.lat == null || p.lng == null) return;
                const m = new this.mapboxgl.Marker({
                    element: this.pinEl(p.is_cliente ? '#0083C1' : '#16a34a'),
                })
                    .setLngLat([p.lng, p.lat])
                    .setPopup(new this.mapboxgl.Popup({ offset: 10 }).setHTML(
                        `<strong>${p.nome || 'Lead'}</strong>${p.is_cliente ? '<br><span style="font-size:12px">Cliente</span>' : ''}`,
                    ))
                    .addTo(this.mapa);
                this.markersPins.push(m);
            });
        },

        ajustarVisaoPins() {
            if (!this.mapboxgl || !this.prospectos.length) return;
            const bounds = new this.mapboxgl.LngLatBounds();
            let ok = false;
            this.prospectos.forEach((p) => {
                if (p.lat == null || p.lng == null) return;
                bounds.extend([p.lng, p.lat]);
                ok = true;
            });
            if (ok && !bounds.isEmpty()) {
                this.mapa.fitBounds(bounds, { padding: 48, maxZoom: 13 });
            }
        },

        iniciarDesenho() {
            if (!this.draw) return;

            this.erro = '';
            this.draw.deleteAll();
            this.sincronizarCampo();
            this.temArea = false;
            this.resumo = { total: 0, clientes: 0, leads: 0 };
            this.draw.changeMode('draw_polygon');
            this.status = 'Clique nos vértices em volta dos pins. Duplo clique (ou feche no 1º ponto) para terminar.';
        },

        aoMudarModo(e) {
            if (e.mode === 'draw_polygon' && !this.temArea) {
                this.status = 'Polígono livre: clique pelos vértices e feche a área.';
            }
        },

        sincronizarCampo() {
            const poligono = poligonoValidoDoDraw(this.draw);
            const campo = this.$refs.poligono;

            if (campo) {
                campo.value = poligono ? JSON.stringify(poligono) : '';
            }

            this.temArea = Boolean(poligono);
        },

        atualizarResumo() {
            const poligono = poligonoValidoDoDraw(this.draw);
            this.resumo = poligono
                ? resumirProspectosNaArea(this.prospectos, poligono)
                : { total: 0, clientes: 0, leads: 0 };
        },

        ajustarVisao(poligono) {
            const coords = poligono?.coordinates?.[0];
            if (!coords?.length || !this.mapboxgl) return;

            const bounds = coords.reduce(
                (b, c) => b.extend(c),
                new this.mapboxgl.LngLatBounds(coords[0], coords[0]),
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
            this.atualizarResumo();

            if (!this.temArea) return;

            await this.estimarCeps();
        },

        aoApagar() {
            this.sincronizarCampo();
            this.resumo = { total: 0, clientes: 0, leads: 0 };
            this.status = 'Área removida. Desenhe de novo em volta dos pins.';
            this.erro = '';
        },

        async estimarCeps() {
            const poligono = poligonoValidoDoDraw(this.draw);
            if (!poligono || !this.estimarUrl) return;

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

                if (inicio) inicio.value = formatarCep(dados.cep_inicio);
                if (fim) fim.value = formatarCep(dados.cep_fim);

                const n = this.resumo.total;
                this.status = n
                    ? `${n} lead(s) na área · CEP ${formatarCep(dados.cep_inicio)} → ${formatarCep(dados.cep_fim)}`
                    : `Área marcada · CEP ${formatarCep(dados.cep_inicio)} → ${formatarCep(dados.cep_fim)}`;
            } catch (e) {
                this.erro = e.message || 'Erro ao estimar CEPs.';
                this.status = this.resumo.total
                    ? `${this.resumo.total} lead(s) na área (CEP não estimado).`
                    : 'Área marcada, mas a estimativa de CEP falhou.';
            }
        },
    }));
}
