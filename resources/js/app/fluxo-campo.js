export function registrarFluxoCampo(Alpine) {
    Alpine.data('ondeProspectar', (config) => ({
        prospectarUrl: config.prospectarUrl,
        municipiosUrl: config.municipiosUrl || null,
        bairrosUrl: config.bairrosUrl || null,
        detalheUrl: config.detalheUrl || null,
        csrf: config.csrf,
        bairro: '',
        cidade: '',
        uf: '',
        cep: '',
        ufs: [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
        ],
        municipiosUf: [],
        carregandoMunicipios: false,
        segmento: 'MISTO',
        status: '',
        erro: '',
        buscando: false,
        localResolvido: '',
        avisos: [],
        territorio: null,
        prospectos: [],
        selecionadosIds: [],
        mapa: null,
        desenhando: false,
        vertices: [],
        vertexMarkers: [],
        polyPreview: null,
        poligono: null,
        polyOverlay: null,
        markers: [],
        clickListener: null,
        acService: null,
        placesService: null,
        cidadeBounds: null,
        cidadeCentro: null,
        sugestoesCidade: [],
        sugestoesBairro: [],
        idxCidade: -1,
        idxBairro: -1,
        avisoCidade: '',
        avisoBairro: '',
        debounceCidade: null,
        debounceBairro: null,
        placesDiv: null,

        init() {
            try {
                const setup = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
                this.segmento = setup.segmento || 'MISTO';
                if (!setup.local || !setup.segmento || !setup.horas) {
                    this.erro = 'Complete o Setup do dia antes de prospectar.';
                }
            } catch (e) {}

            this.restaurarAreaSalva();
            this.$nextTick(() => this.aguardarMaps(() => {
                this.iniciarMapa();
                this.iniciarPlaces();
                if (this.$refs.cidadeInput && this.cidade) {
                    this.$refs.cidadeInput.value = this.cidade;
                }
                if (this.$refs.bairroInput && this.bairro) {
                    this.$refs.bairroInput.value = this.bairro;
                }
                if (this.uf) this.carregarMunicipiosUf(this.uf);
                if (this.cidade && this.uf) this.centralizarCidadeNoMapa(this.cidade);
            }));
        },

        restaurarAreaSalva() {
            try {
                const salva = JSON.parse(localStorage.getItem('prospecta.area') || '{}');
                if (salva.uf) this.uf = String(salva.uf).toUpperCase().slice(0, 2);
                if (salva.confirmada && salva.cidade) this.cidade = salva.cidade;
                if (salva.confirmada && salva.bairro) this.bairro = salva.bairro;
                if (salva.cep) this.cep = salva.cep;
            } catch (e) {}
        },

        persistirArea({ confirmada = false } = {}) {
            const prev = (() => {
                try { return JSON.parse(localStorage.getItem('prospecta.area') || '{}'); }
                catch (e) { return {}; }
            })();
            const cidadeOk = Boolean(String(this.cidade || '').trim()) && (confirmada || prev.confirmada);
            localStorage.setItem('prospecta.area', JSON.stringify({
                uf: this.uf,
                cidade: cidadeOk ? this.cidade : '',
                bairro: cidadeOk ? (this.bairro || '') : '',
                cep: this.cep,
                confirmada: cidadeOk,
            }));
        },

        aguardarMaps(cb, n = 60) {
            if (window.google?.maps?.Map) {
                cb();
                return;
            }
            if (n <= 0) {
                this.erro = 'Google Maps não carregou. Verifique a chave e APIs (Maps).';
                return;
            }
            setTimeout(() => this.aguardarMaps(cb, n - 1), 120);
        },

        iniciarMapa() {
            const el = this.$refs.mapa;
            if (!el || this.mapa) return;

            this.mapa = new google.maps.Map(el, {
                center: { lat: -22.9068, lng: -43.1729 },
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                gestureHandling: 'greedy',
            });
        },

        iniciarPlaces() {
            if (!window.google?.maps?.places?.AutocompleteService) return;
            this.acService = new google.maps.places.AutocompleteService();
            this.placesDiv = this.placesDiv || document.createElement('div');
            this.placesService = new google.maps.places.PlacesService(this.mapa || this.placesDiv);
        },

        normalizarTexto(texto) {
            return String(texto || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        },

        async carregarMunicipiosUf(uf) {
            if (!this.municipiosUrl || !uf) {
                this.municipiosUf = [];
                return;
            }
            this.carregandoMunicipios = true;
            this.municipiosUf = [];
            try {
                const res = await fetch(`${this.municipiosUrl}?uf=${encodeURIComponent(uf)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const dados = await res.json();
                this.municipiosUf = Array.isArray(dados.municipios) ? dados.municipios : [];
            } catch (e) {
                this.municipiosUf = [];
                this.avisoCidade = 'Não deu pra carregar cidades do IBGE. Tente de novo.';
            } finally {
                this.carregandoMunicipios = false;
            }
        },

        aoMudarUf() {
            this.uf = (this.uf || '').toUpperCase();
            this.cidade = '';
            this.bairro = '';
            this.cidadeBounds = null;
            this.cidadeCentro = null;
            this.sugestoesCidade = [];
            this.sugestoesBairro = [];
            this.avisoCidade = '';
            this.avisoBairro = '';
            this.idxCidade = -1;
            this.idxBairro = -1;
            if (this.$refs.cidadeInput) this.$refs.cidadeInput.value = '';
            if (this.$refs.bairroInput) this.$refs.bairroInput.value = '';
            this.persistirArea();
            this.carregarMunicipiosUf(this.uf);
            this.status = this.uf ? `UF ${this.uf}. Digite a cidade (lista IBGE).` : '';
        },

        aoDigitarCidade(valor) {
            this.cidade = valor;
            this.bairro = '';
            this.sugestoesBairro = [];
            this.avisoBairro = '';
            if (!String(valor || '').trim()) {
                this.limparCidadeNoStorage();
            }
            clearTimeout(this.debounceCidade);
            this.debounceCidade = setTimeout(() => this.buscarSugestoesCidade(valor), 120);
        },

        aoDigitarBairro(valor) {
            this.bairro = valor;
            if (!String(valor || '').trim()) {
                this.persistirArea();
            }
            clearTimeout(this.debounceBairro);
            this.debounceBairro = setTimeout(() => this.buscarSugestoesBairro(valor), 220);
        },

        limparCidadeNoStorage() {
            this.cidade = '';
            this.bairro = '';
            this.cidadeBounds = null;
            this.sugestoesCidade = [];
            this.sugestoesBairro = [];
            this.avisoCidade = '';
            this.avisoBairro = '';
            if (this.$refs.bairroInput) this.$refs.bairroInput.value = '';
            this.persistirArea();
        },

        buscarSugestoesCidade(texto) {
            const q = this.normalizarTexto(texto);
            this.idxCidade = -1;
            this.avisoCidade = '';
            if (!this.uf || q.length < 2) {
                this.sugestoesCidade = [];
                return;
            }
            if (this.carregandoMunicipios) {
                this.avisoCidade = 'Carregando cidades do IBGE…';
                this.sugestoesCidade = [];
                return;
            }
            if (!this.municipiosUf.length) {
                this.avisoCidade = 'Lista de cidades vazia — troque a UF e volte.';
                this.sugestoesCidade = [];
                return;
            }

            const hits = this.municipiosUf
                .filter((m) => this.normalizarTexto(m.nome).startsWith(q)
                    || this.normalizarTexto(m.nome).includes(q))
                .slice(0, 10);

            // Prefixo primeiro (gua → Guapimirim antes de coisas no meio do nome)
            hits.sort((a, b) => {
                const an = this.normalizarTexto(a.nome);
                const bn = this.normalizarTexto(b.nome);
                const ap = an.startsWith(q) ? 0 : 1;
                const bp = bn.startsWith(q) ? 0 : 1;
                if (ap !== bp) return ap - bp;
                return an.localeCompare(bn);
            });

            this.sugestoesCidade = hits.slice(0, 8).map((m) => ({
                placeId: `ibge:${m.id}`,
                label: `${m.nome} - ${this.uf}`,
                main: m.nome,
                secondary: `${this.uf}, Brasil`,
                ibge: true,
            }));

            if (!this.sugestoesCidade.length && q.length >= 2) {
                this.avisoCidade = `Nenhuma cidade de ${this.uf} para “${texto}”.`;
            }
        },

        extrairMunicipioDescricao(desc) {
            const d = String(desc || '');
            let m = d.match(/,\s*([^,]+?)\s*-\s*([A-Z]{2})\s*,\s*Brasil/i);
            if (m) return { cidade: m[1].trim(), uf: m[2].toUpperCase() };
            m = d.match(/^([^,]+?)\s*-\s*([A-Z]{2})\s*,\s*Brasil/i);
            if (m) return { cidade: m[1].trim(), uf: m[2].toUpperCase() };
            return null;
        },

        predictionNoMunicipio(pred, cidade, uf) {
            const info = this.extrairMunicipioDescricao(pred?.description || '');
            if (!info) return false;
            if (info.uf !== String(uf || '').toUpperCase()) return false;
            const a = this.normalizarTexto(info.cidade);
            const b = this.normalizarTexto(cidade);
            return a === b || a.includes(b) || b.includes(a);
        },

        pedirPredicoes(req) {
            return new Promise((resolve) => {
                if (!this.acService) {
                    resolve([]);
                    return;
                }
                this.acService.getPlacePredictions(req, (preds, status) => {
                    if (status === google.maps.places.PlacesServiceStatus.OK && preds?.length) {
                        resolve(preds);
                        return;
                    }
                    resolve([]);
                });
            });
        },

        async buscarSugestoesBairro(texto) {
            const q = String(texto || '').trim();
            const qn = this.normalizarTexto(q);
            const token = `${q}|${this.cidade}|${this.uf}|${Date.now()}`;
            this._buscaBairroToken = token;
            this.idxBairro = -1;
            this.avisoBairro = '';
            if (!this.uf || !this.cidade || qn.length < 2) {
                this.sugestoesBairro = [];
                return;
            }

            // Garante centro da cidade pro bias (prefixo curto tipo "varz")
            if (!this.cidadeCentro) {
                this.centralizarCidadeNoMapa(this.cidade);
            }

            const viaCep = [];
            if (this.bairrosUrl && qn.length >= 3) {
                try {
                    const url = `${this.bairrosUrl}?uf=${encodeURIComponent(this.uf)}`
                        + `&cidade=${encodeURIComponent(this.cidade)}`
                        + `&q=${encodeURIComponent(q)}`;
                    const res = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    const dados = await res.json();
                    (dados.bairros || []).forEach((nome) => {
                        const nn = this.normalizarTexto(nome);
                        if (!nn.startsWith(qn) && !nn.includes(qn)) return;
                        viaCep.push({
                            placeId: `viacep:${nn}`,
                            label: `${nome}, ${this.cidade} - ${this.uf}`,
                            main: nome,
                            secondary: `${this.cidade} - ${this.uf}`,
                            viacep: true,
                        });
                    });
                } catch (e) {}
            }

            if (this._buscaBairroToken !== token) return;

            let googleHits = [];
            if (this.acService) {
                const bias = {};
                if (this.cidadeCentro?.lat != null && window.google?.maps?.LatLng) {
                    bias.location = new google.maps.LatLng(this.cidadeCentro.lat, this.cidadeCentro.lng);
                    bias.radius = 28000;
                } else if (this.cidadeBounds) {
                    bias.bounds = this.cidadeBounds;
                }

                // input curto + bias na cidade (não colocar o nome da cidade no input — vira "Rua Teresópolis" em outro município)
                const predListas = await Promise.all([
                    this.pedirPredicoes({
                        input: q,
                        types: ['geocode'],
                        componentRestrictions: { country: 'br' },
                        ...bias,
                    }),
                    this.pedirPredicoes({
                        input: `Bairro ${q}`,
                        types: ['geocode'],
                        componentRestrictions: { country: 'br' },
                        ...bias,
                    }),
                    this.pedirPredicoes({
                        input: `${q}, ${this.cidade} - ${this.uf}`,
                        types: ['geocode'],
                        componentRestrictions: { country: 'br' },
                        ...bias,
                    }),
                ]);

                const vistoG = new Set();
                const mesclaG = [];
                for (const lista of predListas) {
                    for (const p of lista || []) {
                        if (!p?.place_id || vistoG.has(p.place_id)) continue;
                        vistoG.add(p.place_id);
                        mesclaG.push(p);
                    }
                }

                googleHits = mesclaG
                    .filter((p) => this.ehSugestaoBairro(p, qn))
                    .map((p) => ({
                        placeId: p.place_id,
                        label: p.description,
                        main: p.structured_formatting?.main_text || p.description,
                        secondary: p.structured_formatting?.secondary_text || '',
                        viacep: false,
                    }));
            }

            if (this._buscaBairroToken !== token) return;

            const visto = new Set();
            const mesclado = [];
            for (const s of [...viaCep, ...googleHits]) {
                const key = this.normalizarTexto(s.main);
                if (!key || visto.has(key)) continue;
                // Prefere nomes que começam com o que digitou
                visto.add(key);
                mesclado.push(s);
            }

            mesclado.sort((a, b) => {
                const an = this.normalizarTexto(a.main);
                const bn = this.normalizarTexto(b.main);
                const ap = an.startsWith(qn) ? 0 : 1;
                const bp = bn.startsWith(qn) ? 0 : 1;
                if (ap !== bp) return ap - bp;
                return an.localeCompare(bn);
            });

            this.sugestoesBairro = mesclado.slice(0, 8);
            if (!this.sugestoesBairro.length && qn.length >= 2) {
                this.avisoBairro = `Nenhum bairro em ${this.cidade}/${this.uf} para “${q}” — continue um pouco.`;
            }
        },

        ehSugestaoBairro(pred, qn) {
            const types = pred?.types || [];
            if (types.includes('route') || types.includes('street_address') || types.includes('establishment')) {
                return false;
            }
            if (!this.predictionNoMunicipio(pred, this.cidade, this.uf)) {
                return false;
            }
            const main = this.normalizarTexto(pred?.structured_formatting?.main_text || pred?.description || '');
            if (!main) return false;
            // "varz" casa com "varzea"
            return main.startsWith(qn) || (qn.length >= 3 && main.includes(qn));
        },

        focarSugestao(tipo, delta) {
            const lista = tipo === 'cidade' ? this.sugestoesCidade : this.sugestoesBairro;
            if (!lista.length) return;
            const key = tipo === 'cidade' ? 'idxCidade' : 'idxBairro';
            let n = this[key] + delta;
            if (n < 0) n = lista.length - 1;
            if (n >= lista.length) n = 0;
            this[key] = n;
        },

        confirmarSugestaoAtiva(tipo) {
            if (tipo === 'cidade') {
                const s = this.sugestoesCidade[this.idxCidade] || this.sugestoesCidade[0];
                if (s) this.escolherSugestaoCidade(s);
                return;
            }
            const s = this.sugestoesBairro[this.idxBairro] || this.sugestoesBairro[0];
            if (s) this.escolherSugestaoBairro(s);
        },

        centralizarCidadeNoMapa(nomeCidade) {
            if (!window.google?.maps?.Geocoder || !nomeCidade || !this.uf) return;
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode(
                {
                    address: `${nomeCidade}, ${this.uf}, Brasil`,
                    componentRestrictions: { country: 'BR', administrativeArea: this.uf },
                },
                (results, status) => {
                    if (status !== 'OK' || !results?.[0]?.geometry) return;
                    this.cidadeBounds = results[0].geometry.viewport || results[0].geometry.bounds || null;
                    const loc = results[0].geometry.location;
                    if (loc) {
                        this.cidadeCentro = { lat: loc.lat(), lng: loc.lng() };
                        if (this.mapa) {
                            this.mapa.setCenter(loc);
                            this.mapa.setZoom(12);
                        }
                    }
                },
            );
        },

        escolherSugestaoCidade(s) {
            this.sugestoesCidade = [];
            this.avisoCidade = '';
            this.idxCidade = -1;
            this.cidade = s.main;
            if (this.$refs.cidadeInput) this.$refs.cidadeInput.value = this.cidade;
            this.bairro = '';
            this.sugestoesBairro = [];
            this.cidadeCentro = null;
            if (this.$refs.bairroInput) this.$refs.bairroInput.value = '';
            this.centralizarCidadeNoMapa(this.cidade);
            this.persistirArea({ confirmada: true });
            this.status = `Cidade: ${this.cidade}/${this.uf}. Agora o bairro.`;
        },

        escolherSugestaoBairro(s) {
            this.sugestoesBairro = [];
            this.avisoBairro = '';
            this.idxBairro = -1;
            this.bairro = s.main;
            if (this.$refs.bairroInput) this.$refs.bairroInput.value = this.bairro;
            this.persistirArea({ confirmada: true });
            this.status = `Área: ${this.bairro}, ${this.cidade}/${this.uf}`;
            if (window.google?.maps?.Geocoder && this.mapa) {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    { address: `${this.bairro}, ${this.cidade}, ${this.uf}, Brasil` },
                    (results, status) => {
                        if (status === 'OK' && results?.[0]?.geometry?.location) {
                            this.mapa.setCenter(results[0].geometry.location);
                            this.mapa.setZoom(15);
                        }
                    },
                );
            }
        },

        toggleDesenho() {
            if (this.desenhando) {
                this.pararDesenho(false);
                return;
            }
            this.limparCerca();
            this.desenhando = true;
            this.status = 'Clique no mapa para marcar vértices. Mín. 3 pontos, depois “Fechar cerca”.';
            this.erro = '';
            this.clickListener = this.mapa.addListener('click', (e) => this.adicionarVertice(e.latLng));
        },

        adicionarVertice(latLng) {
            if (!this.desenhando || !latLng) return;
            this.vertices.push(latLng);
            this.vertexMarkers.push(new google.maps.Marker({
                map: this.mapa,
                position: latLng,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 5,
                    fillColor: '#0083C1',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                },
            }));
            this.atualizarPreview();
            this.status = `${this.vertices.length} ponto(s). ${this.vertices.length >= 3 ? 'Pode fechar a cerca.' : 'Continue clicando…'}`;
        },

        atualizarPreview() {
            if (this.polyPreview) this.polyPreview.setMap(null);
            if (this.vertices.length < 2) return;
            this.polyPreview = new google.maps.Polyline({
                map: this.mapa,
                path: this.vertices,
                strokeColor: '#006EA3',
                strokeOpacity: 0.9,
                strokeWeight: 2,
            });
        },

        fecharCerca() {
            if (this.vertices.length < 3) {
                this.erro = 'Marque pelo menos 3 pontos no mapa.';
                return;
            }
            this.pararDesenho(true);
            this.polyOverlay = new google.maps.Polygon({
                map: this.mapa,
                paths: this.vertices,
                fillColor: '#0083C1',
                fillOpacity: 0.2,
                strokeWeight: 2,
                strokeColor: '#006EA3',
                editable: true,
            });
            this.poligono = this.pathParaGeoJson(this.polyOverlay.getPath());
            google.maps.event.addListener(this.polyOverlay.getPath(), 'set_at', () => {
                this.poligono = this.pathParaGeoJson(this.polyOverlay.getPath());
            });
            google.maps.event.addListener(this.polyOverlay.getPath(), 'insert_at', () => {
                this.poligono = this.pathParaGeoJson(this.polyOverlay.getPath());
            });
            this.status = 'Cerca pronta. Clique em Buscar leads.';
        },

        pararDesenho(manterVertices) {
            this.desenhando = false;
            if (this.clickListener) {
                google.maps.event.removeListener(this.clickListener);
                this.clickListener = null;
            }
            if (this.polyPreview) {
                this.polyPreview.setMap(null);
                this.polyPreview = null;
            }
            this.vertexMarkers.forEach((m) => m.setMap(null));
            this.vertexMarkers = [];
            if (!manterVertices) this.vertices = [];
        },

        limparCerca() {
            this.pararDesenho(false);
            if (this.polyOverlay) {
                this.polyOverlay.setMap(null);
                this.polyOverlay = null;
            }
            this.poligono = null;
            this.vertices = [];
        },

        pathParaGeoJson(path) {
            const coords = [];
            for (let i = 0; i < path.getLength(); i++) {
                const p = path.getAt(i);
                coords.push([p.lng(), p.lat()]);
            }
            if (coords.length) coords.push(coords[0]);
            return { type: 'Polygon', coordinates: [coords] };
        },

        limparPins() {
            this.markers.forEach((m) => m.setMap(null));
            this.markers = [];
        },

        get selecionados() {
            return this.prospectos.filter((p) => this.selecionadosIds.includes(p.id));
        },

        get todosSelecionados() {
            return this.prospectos.length > 0
                && this.selecionadosIds.length === this.prospectos.length;
        },

        estaSelecionado(id) {
            return this.selecionadosIds.includes(id);
        },

        marcarTodos() {
            this.selecionadosIds = this.prospectos.map((p) => p.id);
            this.atualizarEstiloPins();
            this.persistirSelecao();
        },

        limparSelecao() {
            this.selecionadosIds = [];
            this.atualizarEstiloPins();
            this.persistirSelecao();
        },

        toggleLead(id) {
            if (this.selecionadosIds.includes(id)) {
                this.selecionadosIds = this.selecionadosIds.filter((x) => x !== id);
            } else {
                this.selecionadosIds = [...this.selecionadosIds, id];
            }
            this.atualizarEstiloPins();
            this.persistirSelecao();
        },

        persistirSelecao() {
            localStorage.setItem('prospecta.leads', JSON.stringify(this.selecionados));
            localStorage.setItem('prospecta.leads_todos', JSON.stringify(this.prospectos));
        },

        atualizarEstiloPins() {
            this.markers.forEach((m) => {
                const id = m.__prospectoId;
                const sel = this.estaSelecionado(id);
                m.setIcon({
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: sel ? '#0083C1' : '#94A3B8',
                    fillOpacity: sel ? 1 : 0.55,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                });
                m.setOpacity(sel ? 1 : 0.55);
            });
        },

        mensagemValidacao(dados) {
            if (dados?.errors) {
                const primeiro = Object.values(dados.errors).flat()[0];
                if (primeiro) return primeiro;
            }
            return dados?.message || 'Falha na busca.';
        },

        async buscarLeads() {
            if (this.buscando) return;
            this.erro = '';
            this.buscando = true;
            this.status = 'Checando território e buscando empresas no Google Places…';

            try {
                const setup = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
                if (!setup.local || !setup.segmento || !setup.horas) {
                    throw new Error('Complete o Setup do dia (4 campos).');
                }

                if (this.desenhando && this.vertices.length >= 3) {
                    this.fecharCerca();
                }

                const uf = (this.uf || '').trim().toUpperCase();
                const body = {
                    bairro: (this.bairro || '').trim() || null,
                    cidade: (this.cidade || '').trim() || null,
                    uf: uf.length === 2 ? uf : null,
                    cep: (this.cep || '').trim() || null,
                    segmento: this.segmento || setup.segmento,
                    horas: setup.horas || null,
                    poligono: this.poligono,
                    raio_metros: Number(setup.mixProspeccao) >= 80 ? 2000 : 3500,
                };

                if (!body.bairro && !body.cidade && !body.cep && !body.poligono) {
                    throw new Error('Informe UF/cidade/bairro, CEP ou desenhe e feche a cerca no mapa.');
                }

                this.persistirArea({ confirmada: true });

                const resposta = await fetch(this.prospectarUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(body),
                });

                const dados = await resposta.json();
                if (!resposta.ok) {
                    throw new Error(this.mensagemValidacao(dados));
                }

                this.territorio = dados.territorio;
                this.prospectos = dados.prospectos || [];
                this.selecionadosIds = this.prospectos.map((p) => p.id);
                this.persistirSelecao();
                localStorage.setItem('prospecta.area', JSON.stringify({
                    uf: this.uf,
                    bairro: this.bairro,
                    cidade: this.cidade,
                    cep: this.cep,
                    confirmada: true,
                    poligono: this.poligono,
                    centro: dados.centro,
                    territorio: dados.territorio,
                }));

                this.limparPins();
                const info = new google.maps.InfoWindow();
                const bounds = new google.maps.LatLngBounds();
                this.prospectos.forEach((p, i) => {
                    if (p.lat == null) return;
                    const m = new google.maps.Marker({
                        map: this.mapa,
                        position: { lat: p.lat, lng: p.lng },
                        label: { text: String(i + 1), color: '#fff', fontWeight: '700' },
                        title: p.razao_social,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 12,
                            fillColor: '#0083C1',
                            fillOpacity: 1,
                            strokeColor: '#fff',
                            strokeWeight: 2,
                        },
                    });
                    m.__prospectoId = p.id;
                    m.addListener('click', async () => {
                        this.toggleLead(p.id);
                        const sel = this.estaSelecionado(p.id) ? '✓ Na rota' : '○ Fora da rota';
                        const linhasBase = [
                            `<strong>${i + 1}. ${p.razao_social || ''}</strong>`,
                            `<span style="color:#0083C1">${sel} (clique de novo para alternar)</span>`,
                            p.cnpj ? `CNPJ/ID: ${p.cnpj}` : null,
                            p.endereco ? `Endereço: ${p.endereco}` : null,
                            p.telefone ? `Telefone: ${p.telefone}` : null,
                        ].filter(Boolean);
                        info.setContent(`<div style="max-width:260px;font:12px/1.4 system-ui">${linhasBase.join('<br>')}</div>`);
                        info.open({ map: this.mapa, anchor: m });

                        if (!this.detalheUrl || !p.google_place_id) return;
                        try {
                            const resposta = await fetch(this.detalheUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                },
                                body: JSON.stringify({ place_id: p.google_place_id }),
                            });
                            const dadosDetalhe = await resposta.json();
                            const l = dadosDetalhe.lugar || {};
                            const ricos = [
                                `<strong>${i + 1}. ${l.nome || p.razao_social || ''}</strong>`,
                                `<span style="color:#0083C1">${this.estaSelecionado(p.id) ? '✓ Na rota' : '○ Fora da rota'}</span>`,
                                p.cnpj ? `CNPJ/ID: ${p.cnpj}` : null,
                                l.endereco || p.endereco ? `Endereço: ${l.endereco || p.endereco}` : null,
                                l.telefone || p.telefone ? `Tel: ${l.telefone || p.telefone}` : null,
                                l.website ? `Site: ${l.website}` : null,
                                l.rating != null ? `Nota: ${l.rating}${l.total_avaliacoes ? ' (' + l.total_avaliacoes + ')' : ''}` : null,
                                l.aberto_agora != null ? (l.aberto_agora ? 'Aberto agora' : 'Fechado agora') : null,
                                l.resumo || null,
                            ].filter(Boolean);
                            info.setContent(`<div style="max-width:260px;font:12px/1.45 system-ui">${ricos.join('<br>')}</div>`);
                            Object.assign(p, l, { razao_social: l.nome || p.razao_social });
                            this.persistirSelecao();
                        } catch (e) {}
                    });
                    this.markers.push(m);
                    bounds.extend(m.getPosition());
                });
                if (dados.centro) {
                    this.mapa.setCenter(dados.centro);
                    if (this.prospectos.length) this.mapa.fitBounds(bounds);
                    else this.mapa.setZoom(14);
                }

                const motivo = dados.territorio?.motivo === 'area_livre'
                    ? 'Área sem cobertura — pode prospectar.'
                    : 'Área liberada.';
                this.localResolvido = dados.local_resolvido || '';
                this.avisos = Array.isArray(dados.avisos) ? dados.avisos : [];
                const consulta = dados.consulta ? ` Busca Google: “${dados.consulta}”.` : '';
                this.status = `${motivo} ${this.prospectos.length} empresa(s).${consulta} Marque quais entram na rota.`;
            } catch (e) {
                this.erro = e.message || 'Erro ao prospectar.';
                this.status = '';
                this.localResolvido = '';
                this.avisos = [];
            } finally {
                this.buscando = false;
            }
        },

        irParaRota() {
            if (!this.prospectos.length) {
                this.erro = 'Busque leads antes de gerar a rota.';
                return;
            }
            if (!this.selecionadosIds.length) {
                this.erro = 'Selecione pelo menos 1 lead para a rota.';
                return;
            }
            this.persistirSelecao();
            window.location.href = config.rotaUrl;
        },
    }));

    Alpine.data('rotaDia', (config) => ({
        gerarUrl: config.gerarUrl,
        detalheUrl: config.detalheUrl,
        csrf: config.csrf,
        itens: [],
        selecionado: null,
        carregandoDetalhe: false,
        urlMaps: null,
        urlWaze: null,
        status: '',
        erro: '',
        avisos: [],
        gps: null,
        mapa: null,
        renderer: null,
        markers: [],
        infoWindow: null,
        carroMarker: null,
        seguirNoMapa: false,
        itemFocado: null,
        _rotaDesenhada: false,

        init() {
            const setup = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
            if (!setup.local || !setup.segmento || !setup.horas) {
                this.erro = 'Setup incompleto. Volte e preencha os 4 campos.';
                return;
            }
            this.$nextTick(() => this.aguardarMaps(() => {
                this.iniciarMapa();
                this.carregarGps();
                this.gerar();
            }));
        },

        aguardarMaps(cb, n = 40) {
            if (window.google?.maps) { cb(); return; }
            if (n <= 0) { this.erro = 'Google Maps não carregou.'; return; }
            setTimeout(() => this.aguardarMaps(cb, n - 1), 150);
        },

        iniciarMapa() {
            this.mapa = new google.maps.Map(this.$refs.mapa, {
                center: { lat: -22.9, lng: -43.2 },
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                gestureHandling: 'greedy',
            });
            this.renderer = new google.maps.DirectionsRenderer({
                map: this.mapa,
                suppressMarkers: true,
                preserveViewport: true,
                polylineOptions: { strokeColor: '#0083C1', strokeWeight: 5 },
            });
            this.infoWindow = new google.maps.InfoWindow();
            this.infoWindow.addListener('closeclick', () => {
                this.selecionado = null;
            });
        },

        iconeCarro() {
            const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44"><circle cx="22" cy="22" r="20" fill="#0083C1" stroke="#fff" stroke-width="3"/><path fill="#fff" d="M12 27h2.2l1.1-3.2h13.4L30 27h2v-1.6c0-.7-.4-1.3-1-1.5l-1.5-4.2c-.3-.8-1-1.3-1.8-1.3H16.3c-.8 0-1.5.5-1.8 1.3L13 23.9c-.6.2-1 .8-1 1.5V27zm5.2-8.5h9.6l1.1 3.1H16.3l1.1-3.1zM15.5 29.5a1.8 1.8 0 110-3.6 1.8 1.8 0 010 3.6zm13 0a1.8 1.8 0 110-3.6 1.8 1.8 0 010 3.6z"/></svg>';
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(44, 44),
                anchor: new google.maps.Point(22, 22),
            };
        },

        distanciaMetrosRota(a, b) {
            if (!a || !b || a.lat == null || b.lat == null) return null;
            const R = 6371000;
            const toRad = (d) => (d * Math.PI) / 180;
            const dLat = toRad(b.lat - a.lat);
            const dLng = toRad(b.lng - a.lng);
            const lat1 = toRad(a.lat);
            const lat2 = toRad(b.lat);
            const h = Math.sin(dLat / 2) ** 2
                + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(h));
        },

        atualizarCarro() {
            if (!this.mapa || !this.gps) return;
            if (!this.carroMarker) {
                this.carroMarker = new google.maps.Marker({
                    map: this.mapa,
                    position: this.gps,
                    icon: this.iconeCarro(),
                    title: 'Você (GPS)',
                    zIndex: 9999,
                });
            } else {
                this.carroMarker.setPosition(this.gps);
            }
            if (!this.seguirNoMapa) return;

            // Longe da parada: câmera cola no vendedor (navegação).
            // Perto (<2,5 km): enquadra você + próximo pin.
            const prox = this.itemFocado || this.itens[0];
            const dist = prox?.lat != null
                ? this.distanciaMetrosRota(this.gps, { lat: prox.lat, lng: prox.lng })
                : null;

            if (prox?.lat != null && dist != null && dist < 2500) {
                const b = new google.maps.LatLngBounds();
                b.extend(this.gps);
                b.extend({ lat: prox.lat, lng: prox.lng });
                this.mapa.fitBounds(b, 100);
                const z = this.mapa.getZoom();
                if (z > 17) this.mapa.setZoom(17);
                if (z < 14) this.mapa.setZoom(14);
            } else {
                this.mapa.panTo(this.gps);
                if ((this.mapa.getZoom() || 0) < 14) {
                    this.mapa.setZoom(15);
                }
            }
        },

        carregarGps() {
            if (!navigator.geolocation) {
                this.erro = 'GPS indisponível neste dispositivo.';
                return;
            }
            navigator.geolocation.watchPosition((pos) => {
                this.gps = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                this.atualizarCarro();
                if (this.itens.length) this.tracar(false);
            }, () => {
                this.erro = 'Permita a localização para ver seu pin no mapa.';
            }, { enableHighAccuracy: true, maximumAge: 5000 });
        },

        toggleSeguir() {
            this.seguirNoMapa = !this.seguirNoMapa;
            if (this.seguirNoMapa) {
                this.itemFocado = this.itens[0] || null;
                this.atualizarCarro();
                this.status = 'Seguindo você no mapa (câmera no GPS). Perto da parada, enquadra o pin.';
            } else {
                this.status = `${this.itens.length} paradas na janela de ouro.`;
            }
        },

        focarItem(item) {
            this.itemFocado = item;
            this.selecionado = { ...item };
            if (!this.mapa || item.lat == null) {
                this.carregarDetalhe(item);
                return;
            }
            this.mapa.panTo({ lat: item.lat, lng: item.lng });
            this.mapa.setZoom(16);
            this.abrirInfo(item);
            this.carregarDetalhe(item);
        },

        fecharDetalhe() {
            this.selecionado = null;
            this.infoWindow?.close();
        },

        horarioCurto(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },

        async carregarDetalhe(item) {
            const placeId = item.google_place_id || item.place_id;
            if (!placeId || !this.detalheUrl) return;
            this.carregandoDetalhe = true;
            try {
                const resposta = await fetch(this.detalheUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ place_id: placeId }),
                });
                const dados = await resposta.json();
                if (!resposta.ok) throw new Error(dados.message || 'Falha ao buscar detalhes.');
                const lugar = dados.lugar || {};
                this.selecionado = {
                    ...item,
                    ...lugar,
                    razao_social: lugar.nome || item.razao_social,
                    google_place_id: lugar.place_id || placeId,
                    guia_bolso: item.guia_bolso,
                    cnpj: item.cnpj,
                    id: item.id,
                    ordem: item.ordem,
                    is_cliente: item.is_cliente,
                };
                // Atualiza lista/localStorage com dados ricos
                this.itens = this.itens.map((i) => i.id === item.id ? { ...i, ...this.selecionado } : i);
                localStorage.setItem('prospecta.rota', JSON.stringify(this.itens));
                this.abrirInfo(this.selecionado);
            } catch (e) {
                this.erro = e.message;
            } finally {
                this.carregandoDetalhe = false;
            }
        },

        htmlInfo(item) {
            const foto = item.foto_thumb || item.foto || (item.fotos && item.fotos[0]?.url_thumb) || (item.fotos && item.fotos[0]?.url);
            const img = foto
                ? `<img src="${foto}" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block">`
                : '';
            const linhas = [
                img,
                `<strong>${item.ordem || ''}. ${item.razao_social || item.nome || ''}</strong>`,
                item.cnpj ? `CNPJ/ID: ${item.cnpj}` : null,
                item.endereco ? `Endereço: ${item.endereco}` : null,
                item.telefone ? `Tel: ${item.telefone}` : null,
                item.website ? `Site: ${item.website}` : null,
                item.rating != null ? `Nota: ${item.rating}${item.total_avaliacoes ? ' (' + item.total_avaliacoes + ')' : ''}` : null,
                item.aberto_agora != null ? (item.aberto_agora ? 'Aberto agora' : 'Fechado agora') : null,
                item.status_negocio ? `Status: ${item.status_negocio}` : null,
                item.resumo ? item.resumo : null,
                (item.types || []).length ? `Tipos: ${item.types.slice(0, 4).join(', ')}` : null,
                item.guia_bolso ? `<em>${item.guia_bolso}</em>` : null,
            ].filter(Boolean);
            return `<div style="max-width:280px;font:12px/1.45 system-ui">${linhas.join('<br>')}</div>`;
        },

        abrirInfo(item, marker = null) {
            if (!this.infoWindow) return;
            this.infoWindow.setContent(this.htmlInfo(item));
            if (marker) {
                this.infoWindow.open({ map: this.mapa, anchor: marker });
            } else if (item.lat != null) {
                this.infoWindow.setPosition({ lat: item.lat, lng: item.lng });
                this.infoWindow.open(this.mapa);
            }
        },

        async gerar() {
            const leads = JSON.parse(localStorage.getItem('prospecta.leads') || '[]');
            if (!leads.length) {
                this.erro = 'Nenhum lead. Volte em Prospectar e busque empresas.';
                return;
            }

            this.status = 'Otimizando rota…';
            try {
                const setup = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
                const resposta = await fetch(this.gerarUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        prospecto_ids: leads.map((l) => l.id),
                        local: setup.local,
                        segmento: setup.segmento,
                        horas: setup.horas,
                        mix_prospeccao: setup.mixProspeccao,
                        origem_lat: this.gps?.lat ?? null,
                        origem_lng: this.gps?.lng ?? null,
                    }),
                });
                const dados = await resposta.json();
                if (!resposta.ok) {
                    throw new Error(dados.message || dados.errors?.local?.[0] || 'Falha ao gerar rota.');
                }

                const extra = Object.fromEntries(leads.map((l) => [l.id, l]));
                this.itens = (dados.itens || []).map((i) => ({
                    ...extra[i.id],
                    ...i,
                    guia_bolso: i.guia_bolso || extra[i.id]?.guia_bolso || 'Sugestão: confirme decisor e porte na visita.',
                    rating: extra[i.id]?.rating ?? i.rating ?? null,
                    types: extra[i.id]?.types || i.types || [],
                }));
                this.avisos = dados.avisos || [];
                localStorage.setItem('prospecta.rota', JSON.stringify(this.itens));
                this.urlMaps = dados.url_maps;
                this.urlWaze = dados.url_waze;
                localStorage.setItem('prospecta.rota_meta', JSON.stringify({
                    url_maps: dados.url_maps,
                    url_waze: dados.url_waze,
                    blocos: dados.blocos || null,
                }));
                if (navigator.serviceWorker?.controller) {
                    navigator.serviceWorker.controller.postMessage({
                        type: 'CACHE_ROTA',
                        urls: ['/app/rota', '/app/checkin', '/build/manifest.json'].filter(Boolean),
                    });
                }
                this._rotaDesenhada = false;
                this.renderPins();
                this.tracar(true);
                this.status = `${this.itens.length} paradas na janela de ouro.`;
            } catch (e) {
                this.erro = e.message;
                this.status = '';
            }
        },

        renderPins() {
            this.markers.forEach((m) => m.setMap(null));
            this.markers = [];
            this.itens.forEach((item) => {
                if (item.lat == null) return;
                const cor = item.visitado ? '#94A3B8' : (item.is_cliente ? '#0083C1' : '#E11D48');
                const marker = new google.maps.Marker({
                    map: this.mapa,
                    position: { lat: item.lat, lng: item.lng },
                    label: { text: String(item.ordem), color: '#fff', fontWeight: '700' },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 14,
                        fillColor: cor,
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                    },
                    title: item.razao_social,
                });
                marker.addListener('click', () => { this.selecionado = { ...item }; this.abrirInfo(item, marker); this.carregarDetalhe(item); });
                this.markers.push(marker);
            });
        },

        tracar(force = true) {
            if (!this.itens.length || !this.renderer) return;
            const paradas = this.itens.filter((i) => i.lat != null);
            if (!paradas.length) return;
            const origem = this.gps || { lat: paradas[0].lat, lng: paradas[0].lng };
            const destino = { lat: paradas.at(-1).lat, lng: paradas.at(-1).lng };
            const waypoints = paradas.slice(0, -1).map((p) => ({
                location: { lat: p.lat, lng: p.lng },
                stopover: true,
            }));

            if (force || !this._rotaDesenhada) {
                new google.maps.DirectionsService().route({
                    origin: origem,
                    destination: destino,
                    waypoints: waypoints.slice(0, 23),
                    travelMode: google.maps.TravelMode.DRIVING,
                }, (r, s) => {
                    if (s === 'OK') {
                        this.renderer.setDirections(r);
                        this._rotaDesenhada = true;
                        // Só enquadra a rota inteira se NÃO estiver seguindo o vendedor
                        if (!this.seguirNoMapa && r.routes?.[0]?.bounds) {
                            this.mapa.fitBounds(r.routes[0].bounds, 56);
                        } else if (this.seguirNoMapa) {
                            this.atualizarCarro();
                        }
                    }
                });
            }

            const pts = [origem, ...paradas.map((p) => ({ lat: p.lat, lng: p.lng }))];
            this.urlMaps = 'https://www.google.com/maps/dir/' + pts.map((p) => `${p.lat},${p.lng}`).join('/');
        },

        adicionarNaRota(lead) {
            if (!lead?.id || this.itens.some((i) => i.id === lead.id)) return;
            const item = {
                ...lead,
                ordem: this.itens.length + 1,
                guia_bolso: lead.guia_bolso || 'Incluído na rota (oportunidade no caminho).',
            };
            this.itens.push(item);
            localStorage.setItem('prospecta.rota', JSON.stringify(this.itens));
            this.renderPins();
            this.tracar(true);
            this.status = `Parada extra adicionada: ${item.razao_social || item.nome || 'lead'}.`;
        },

        leadsForaDaRota() {
            const leads = JSON.parse(localStorage.getItem('prospecta.leads') || '[]');
            const ids = new Set(this.itens.map((i) => i.id));
            return leads.filter((l) => l.id && !ids.has(l.id)).slice(0, 8);
        },
    }));

    Alpine.data('checkinCampo', (config) => ({
        storeUrl: config.storeUrl,
        receitaUrl: config.receitaUrl,
        csrf: config.csrf,
        receitaDriver: config.receitaDriver || 'mock',
        itens: [],
        atual: null,
        status: 'FEITA',
        gps: null,
        distancia: null,
        foto: null,
        fotoPreview: null,
        gravando: false,
        recorder: null,
        chunks: [],
        audioBlob: null,
        msg: '',
        erro: '',
        upsellAck: false,
        cnpjConsulta: '',
        consultandoReceita: false,
        receitaMsg: '',
        salvando: false,
        mapa: null,
        carroMarker: null,
        pinMarker: null,
        raioCirculo: null,

        get noLocal() {
            return this.distancia != null && this.distancia <= 100;
        },

        get ordemAtual() {
            if (!this.atual || !this.itens.length) return 1;
            const idx = this.itens.findIndex((i) => i.id === this.atual.id);
            return (idx >= 0 ? idx : 0) + 1;
        },

        init() {
            this.itens = JSON.parse(localStorage.getItem('prospecta.rota') || '[]');
            this.atual = this.itens[0] || null;
            if (this.atual?.cnpj && !String(this.atual.cnpj).startsWith('G')) {
                this.cnpjConsulta = this.atual.cnpj;
            }
            this.$nextTick(() => {
                this.$nextTick(() => this.aguardarMaps(() => {
                    this.iniciarMapa();
                    this.carregarGps();
                }));
            });
            this.$watch('atual', () => {
                this.upsellAck = false;
                this.foto = null;
                this.fotoPreview = null;
                this.audioBlob = null;
                this.erro = '';
                this.msg = '';
                this.atualizarPinMapa();
                this.enquadrarMapa();
            });
        },

        // MOCK Receita: desligado na UI (RECEITA_WS_DRIVER=mock, sem chave).
        // Reativar com RECEITA_WS_DRIVER=http + token + bloco no checkin.blade.php.
        // async consultarReceita() { ... fetch(this.receitaUrl) ... },

        aguardarMaps(cb, n = 40) {
            if (window.google?.maps) { cb(); return; }
            if (n <= 0) return;
            setTimeout(() => this.aguardarMaps(cb, n - 1), 150);
        },

        iniciarMapa() {
            if (!this.$refs.mapaMini || this.mapa) return;
            const centro = this.atual?.lat != null
                ? { lat: this.atual.lat, lng: this.atual.lng }
                : { lat: -22.9, lng: -43.2 };
            this.mapa = new google.maps.Map(this.$refs.mapaMini, {
                center: centro,
                zoom: 16,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                gestureHandling: 'greedy',
            });
            this.atualizarPinMapa();
            requestAnimationFrame(() => {
                google.maps.event.trigger(this.mapa, 'resize');
                this.enquadrarMapa();
            });
        },

        atualizarPinMapa() {
            if (!this.mapa || !window.google?.maps) return;
            if (this.atual?.lat != null) {
                const pos = { lat: this.atual.lat, lng: this.atual.lng };
                if (!this.pinMarker) {
                    this.pinMarker = new google.maps.Marker({
                        map: this.mapa,
                        position: pos,
                        title: this.atual.razao_social,
                        zIndex: 10,
                    });
                } else {
                    this.pinMarker.setPosition(pos);
                    this.pinMarker.setMap(this.mapa);
                }
                if (this.raioCirculo) this.raioCirculo.setMap(null);
                this.raioCirculo = new google.maps.Circle({
                    map: this.mapa,
                    center: pos,
                    radius: 100,
                    strokeColor: '#0083C1',
                    strokeOpacity: 0.9,
                    strokeWeight: 2,
                    fillColor: '#0083C1',
                    fillOpacity: 0.12,
                });
            }
        },

        enquadrarMapa() {
            if (!this.mapa || !window.google?.maps) return;
            if (this.gps && this.atual?.lat != null) {
                const dist = this.distanciaMetros(this.gps, this.atual);
                if (dist != null && dist < 2500) {
                    const b = new google.maps.LatLngBounds();
                    b.extend(this.gps);
                    b.extend({ lat: this.atual.lat, lng: this.atual.lng });
                    this.mapa.fitBounds(b, 72);
                    const z = this.mapa.getZoom();
                    if (z > 18) this.mapa.setZoom(18);
                    return;
                }
                this.mapa.panTo(this.gps);
                if ((this.mapa.getZoom() || 0) < 14) this.mapa.setZoom(15);
                return;
            }
            if (this.atual?.lat != null) {
                this.mapa.panTo({ lat: this.atual.lat, lng: this.atual.lng });
                this.mapa.setZoom(16);
            } else if (this.gps) {
                this.mapa.panTo(this.gps);
            }
        },

        iconeCarro() {
            const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 44 44"><circle cx="22" cy="22" r="20" fill="#0083C1" stroke="#fff" stroke-width="3"/><path fill="#fff" d="M12 27h2.2l1.1-3.2h13.4L30 27h2v-1.6c0-.7-.4-1.3-1-1.5l-1.5-4.2c-.3-.8-1-1.3-1.8-1.3H16.3c-.8 0-1.5.5-1.8 1.3L13 23.9c-.6.2-1 .8-1 1.5V27zm5.2-8.5h9.6l1.1 3.1H16.3l1.1-3.1zM15.5 29.5a1.8 1.8 0 110-3.6 1.8 1.8 0 010 3.6zm13 0a1.8 1.8 0 110-3.6 1.8 1.8 0 010 3.6z"/></svg>';
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(40, 40),
                anchor: new google.maps.Point(20, 20),
            };
        },

        carregarGps() {
            if (!navigator.geolocation) {
                this.erro = 'GPS obrigatório — ative a localização.';
                return;
            }
            navigator.geolocation.watchPosition((p) => {
                this.gps = { lat: p.coords.latitude, lng: p.coords.longitude };
                this.distancia = this.distanciaMetros(this.gps, this.atual);
                if (this.mapa) {
                    if (!this.carroMarker) {
                        this.carroMarker = new google.maps.Marker({
                            map: this.mapa,
                            position: this.gps,
                            icon: this.iconeCarro(),
                            title: 'Você',
                            zIndex: 999,
                        });
                    } else {
                        this.carroMarker.setPosition(this.gps);
                    }
                    this.enquadrarMapa();
                }
            }, () => {
                this.erro = 'Permita o GPS para fazer check-in.';
            }, { enableHighAccuracy: true, maximumAge: 3000 });
        },

        distanciaMetros(a, b) {
            if (!a || !b || a.lat == null || b.lat == null) return null;
            const R = 6371000;
            const toRad = (d) => (d * Math.PI) / 180;
            const dLat = toRad(b.lat - a.lat);
            const dLng = toRad(b.lng - a.lng);
            const x = Math.sin(dLat / 2) ** 2
                + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(x));
        },

        async onFoto(e) {
            const arquivo = e.target.files?.[0] || null;
            this.erro = '';
            if (this.fotoPreview) URL.revokeObjectURL(this.fotoPreview);
            this.foto = null;
            this.fotoPreview = null;
            if (!arquivo) return;

            try {
                this.foto = await this.comprimirFotoJpeg(arquivo);
                this.fotoPreview = URL.createObjectURL(this.foto);
            } catch (err) {
                this.erro = err?.message || 'Não foi possível processar a foto. Tente de novo.';
                e.target.value = '';
            }
        },

        /**
         * Converte/redimensiona para JPEG — evita HEIC e fotos > limite do PHP no Render.
         */
        comprimirFotoJpeg(arquivo, maxLado = 1600, qualidade = 0.82) {
            return new Promise((resolve, reject) => {
                const url = URL.createObjectURL(arquivo);
                const img = new Image();
                img.onload = () => {
                    try {
                        let { width: w, height: h } = img;
                        const maior = Math.max(w, h);
                        if (maior > maxLado) {
                            const escala = maxLado / maior;
                            w = Math.round(w * escala);
                            h = Math.round(h * escala);
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        if (!ctx) {
                            reject(new Error('Não foi possível gerar a foto neste aparelho.'));
                            return;
                        }
                        ctx.drawImage(img, 0, 0, w, h);
                        canvas.toBlob(
                            (blob) => {
                                URL.revokeObjectURL(url);
                                if (!blob) {
                                    reject(new Error('Falha ao gerar JPEG da fachada.'));
                                    return;
                                }
                                resolve(new File([blob], 'fachada.jpg', { type: 'image/jpeg' }));
                            },
                            'image/jpeg',
                            qualidade,
                        );
                    } catch (err) {
                        URL.revokeObjectURL(url);
                        reject(err);
                    }
                };
                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('Não deu para abrir a foto. Tire de novo pela câmera.'));
                };
                img.src = url;
            });
        },

        async toggleAudio() {
            if (this.gravando) {
                this.recorder?.stop();
                this.gravando = false;
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.chunks = [];
                this.recorder = new MediaRecorder(stream);
                this.recorder.ondataavailable = (ev) => this.chunks.push(ev.data);
                this.recorder.onstop = () => {
                    this.audioBlob = new Blob(this.chunks, { type: 'audio/webm' });
                    stream.getTracks().forEach((t) => t.stop());
                };
                this.recorder.start();
                this.gravando = true;
                setTimeout(() => {
                    if (this.gravando) this.toggleAudio();
                }, 15000);
            } catch (e) {
                this.erro = 'Não foi possível acessar o microfone.';
            }
        },

        async salvar() {
            if (!this.atual) {
                this.erro = 'Sem parada na rota.';
                return;
            }
            if (this.atual.is_cliente && !this.upsellAck) {
                this.erro = 'Confirme que leu a oportunidade de upsell antes do check-in.';
                return;
            }
            if (!this.gps) {
                this.erro = 'GPS obrigatório. Ative a localização.';
                return;
            }
            this.distancia = this.distanciaMetros(this.gps, this.atual);
            if (this.distancia == null) {
                this.erro = 'Parada sem coordenadas — volte e regenere a rota.';
                return;
            }
            if (this.distancia > 100) {
                this.erro = `Você está a ${Math.round(this.distancia)}m do pin. Aproxime-se (máx. 100m).`;
                return;
            }
            if (this.status === 'FEITA' && !this.foto) {
                this.erro = 'Foto da fachada é obrigatória em visita feita.';
                return;
            }
            if (this.status === 'FEITA' && !this.audioBlob) {
                this.erro = 'Grave o áudio de até 15s para visita feita.';
                return;
            }
            this.erro = '';
            this.msg = '';
            this.salvando = true;
            const fd = new FormData();
            fd.append('prospecto_id', this.atual.id);
            fd.append('status', this.status);
            fd.append('checkin_lat', this.gps.lat);
            fd.append('checkin_lng', this.gps.lng);
            if (this.foto) fd.append('foto', this.foto, this.foto.name || 'fachada.jpg');
            if (this.audioBlob) {
                const audioTipo = this.audioBlob.type || 'audio/webm';
                const audioExt = audioTipo.includes('mp4') ? 'm4a' : 'webm';
                fd.append('audio', this.audioBlob, `resumo.${audioExt}`);
            }

            try {
                const resposta = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: fd,
                });
                const dados = await resposta.json().catch(() => ({}));
                if (!resposta.ok) {
                    if (resposta.status === 413) {
                        throw new Error('Arquivo grande demais para o servidor. Tire a foto de novo.');
                    }
                    const msg = dados.errors
                        ? Object.values(dados.errors).flat()[0]
                        : dados.message;
                    throw new Error(msg || 'Falha ao salvar a visita.');
                }
                this.msg = 'Visita salva.';
                this.itens = this.itens.filter((i) => i.id !== this.atual.id);
                localStorage.setItem('prospecta.rota', JSON.stringify(this.itens));
                this.atual = this.itens[0] || null;
                this.status = 'FEITA';
                if (!this.atual) {
                    this.msg = 'Rota concluída — todas as visitas salvas.';
                }
            } catch (e) {
                this.erro = e.message;
            } finally {
                this.salvando = false;
            }
        },
    }));
}
