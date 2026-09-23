<x-layouts.admin titulo="Documentação">
    <x-slot:subtitulo>Como o Prospecta funciona — mapa, campo, prospectos e o que o seed simula</x-slot:subtitulo>

    <div class="mx-auto max-w-3xl space-y-6">

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Seed vs fluxo real</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                O que você vê hoje no mapa (pins cinza, linha azul, meta 8) veio do <strong class="font-semibold text-ink">seed de demo</strong>
                para popular a tela. No dia a dia do vendedor, o mesmo dado nasce do app — não precisa seed.
            </p>
            <ul class="mt-4 space-y-2 text-sm text-ink">
                <li class="flex gap-2"><span class="font-semibold text-brand">Pin cinza (visita)</span> — criado no <strong>check-in</strong> do PWA (`/app/checkin`): GPS ≤100&nbsp;m + status + foto/áudio quando for visita feita.</li>
                <li class="flex gap-2"><span class="font-semibold text-brand">Linha azul (trajeto)</span> — enquanto o app está aberto, o PWA manda GPS a cada ~12&nbsp;s para `/app/localizacao`. Offline: fila no aparelho e sync quando voltar a rede.</li>
                <li class="flex gap-2"><span class="font-semibold text-brand">Pin ao vivo</span> — último GPS recente desse vendedor (janela de minutos no painel).</li>
            </ul>
            <p class="mt-3 text-sm text-ink-soft">
                Limitação: tracking é <strong class="font-medium text-ink">foreground</strong> (app aberto). Com o app fechado no celular, não grava trajeto em background.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">O que é um prospecto?</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                <strong class="font-semibold text-ink">Prospecto</strong> = estabelecimento/empresa que o vendedor pode visitar.
                Não é a visita em si — é o “alvo” no mapa.
            </p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-surface-muted/80 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Lead (pin vermelho)</p>
                    <p class="mt-1 text-sm text-ink">Ainda não é cliente Alterdata. Veio do hunting (Google Places na área) ou cadastro/demo.</p>
                </div>
                <div class="rounded-xl bg-surface-muted/80 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Cliente (pin azul)</p>
                    <p class="mt-1 text-sm text-ink">Já é cliente (`is_cliente`). Check-in pode ser upsell / reforço de relacionamento.</p>
                </div>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                No app: <strong class="font-medium text-ink">Área → Prospectar</strong> busca lugares na cerca/bairro e grava prospectos.
                Depois a <strong class="font-medium text-ink">rota</strong> ordena paradas e o <strong class="font-medium text-ink">check-in</strong> gera a <em>visita</em> ligada a esse prospecto.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Legenda do painel (mapa)</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink">
                <li><span class="inline-block size-2.5 rounded-full bg-[#e11d48] align-middle"></span> Lead — prospecto não cliente</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#0083C1] align-middle"></span> Cliente — prospecto cliente</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#64748b] align-middle"></span> Visita — check-in feito (GPS do momento do check-in)</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#22c55e] align-middle"></span> Ao vivo ok — GPS recente</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#f59e0b] align-middle"></span> Ao vivo laranja — sem sinal / GPS atrasado</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#a855f7] align-middle"></span> Ao vivo roxo — parado</li>
                <li><span class="inline-block size-2.5 rounded-full bg-[#e11d48] align-middle"></span> Ao vivo vermelho — fora do território</li>
                <li><span class="inline-block h-0.5 w-4 bg-[#0ea5e9] align-middle"></span> Trajeto — caminho GPS do dia (não é a lista da meta 8)</li>
            </ul>
            <p class="mt-4 text-sm text-ink-soft">
                <strong class="font-medium text-ink">Agenda → Ver no mapa</strong> abre o painel filtrado naquele vendedor + hoje:
                você vê onde ele fez check-in e por onde o GPS andou. Na Agenda, o progresso principal é <strong class="font-medium text-ink">X/Y no plano</strong>; a meta casa é só o alvo fixo da operação.
            </p>
            <p class="mt-3 text-sm text-ink-soft">
                Nos filtros: a primeira opção (“Todos…”) é <em>sem filtro</em>, não uma pessoa. Logado como gestor, o painel trava no seu escopo e só lista vendedores da sua equipe.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Rota e Agenda (plano publicado)</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-surface-muted/80 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Rota (PWA)</p>
                    <p class="mt-1 text-sm text-ink">Ao <strong class="font-medium">gerar a rota</strong>, o plano é gravado no servidor (paradas do dia). Continua no celular e passa a aparecer na Agenda.</p>
                </div>
                <div class="rounded-xl bg-surface-muted/80 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Agenda (admin/gestor)</p>
                    <p class="mt-1 text-sm text-ink">Mostra o <strong class="font-medium">plano</strong> (pendente/feita) e o progresso <strong class="font-medium">X/Y no plano</strong>. Check-in marca a parada como feita.</p>
                </div>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                Fluxo: Setup → Área → Prospectar → <strong class="font-medium text-ink">Gerar rota</strong> (publica plano) → anda → <strong class="font-medium text-ink">Check-in</strong> (marca parada + visita).
            </p>
            <p class="mt-2 rounded-xl border border-brand/20 bg-brand-soft/50 px-3 py-2 text-sm text-ink">
                <strong class="font-semibold">Meta casa</strong> (ex.: 8) é meta fixa da operação (`META_VISITAS_DIA`).
                <strong class="font-semibold">X/Y no plano</strong> é o tamanho real da rota gerada — não confundir.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Como a sequência da rota é calculada</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                Quem decide a ordem das paradas é o <strong class="font-medium text-ink">Prospecta</strong> (`RotaService`), não o Google.
                O Maps só desenha o caminho na ordem que já veio do servidor.
            </p>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-ink">
                <li><strong>Entrada</strong> — leads marcados na Área + origem do Setup (GPS) + mix %, horas e segmento.</li>
                <li><strong>Filtro de raio</strong> — mix alto (≥80%) corta leads longe da origem; mix baixo permite raio maior (pós-venda).</li>
                <li><strong>Agrupa “prédio”</strong> — pontos a ~50&nbsp;m (ou mesmo endereço) viram um grupo = 1 deslocamento.</li>
                <li><strong>Ordena</strong> — grupos grandes (≥3 leads) primeiro; depois o mais perto da origem em <strong class="font-medium">linha reta</strong> (haversine); dentro do grupo, por latitude.</li>
                <li><strong>Agenda no tempo</strong> — encaixa nas horas do dia, reserva almoço, aplica regras de segmento (ex.: restaurante fora de 11:30–14:00; contábil bloqueia dias 01–05).</li>
                <li><strong>Mapa / Maps / Waze</strong> — waypoints na ordem fixa (sem otimizar rota pelo Google).</li>
            </ol>
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                <strong class="font-semibold">Por que linha reta e não “pela rua”?</strong>
                Distância por rua exigiria Distance Matrix / Directions do Google a cada combinação de leads
                (custo de API, cota e latência). Hoje a ordem é heurística barata e offline-friendly.
                Quem navega (Maps/Waze) já vai pela rua — só a <em>sequência das paradas</em> que ainda é reta + prédio + horário.
                Evoluir para Matrix fica como melhoria futura.
            </p>
            <p class="mt-3 text-xs text-ink-soft">
                Não é o algoritmo “otimizar rota” do Google Directions.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Área: UF → cidade → bairro (autocomplete)</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                No PWA (<code class="text-xs">/app/area</code>) a ordem é fixa:
                <strong class="font-medium text-ink">1. UF</strong> (select) →
                <strong class="font-medium text-ink">2. Cidade</strong> →
                <strong class="font-medium text-ink">3. Bairro</strong> → CEP opcional — ou desenhar cerca no mapa.
            </p>
            <ul class="mt-3 space-y-2 text-sm text-ink">
                <li><strong class="font-medium">Cidade</strong> — lista do <strong class="font-medium">IBGE</strong> filtrada pela UF (ex.: “gua” acha Guapimirim). Não depende do Google.</li>
                <li><strong class="font-medium">Bairro</strong> — <strong class="font-medium">ViaCEP</strong> na cidade + Google só como complemento, exigindo o município certo (não aceita “Rua Teresópolis” em Magé).</li>
                <li>O Google Places sozinho não filtra UF/cidade de verdade (bounds retangulares vazam estado vizinho).</li>
            </ul>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Fluxo do vendedor (PWA)</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-ink">
                <li><strong>Setup</strong> — local, segmento, horas, % prospecção (obrigatório no dia).</li>
                <li><strong>Área</strong> — UF → cidade → bairro (com sugestões) ou cerca no mapa + território permitido.</li>
                <li><strong>Prospectar</strong> — busca leads → grava prospectos.</li>
                <li><strong>Rota</strong> — ordena paradas (ver seção acima), publica plano, abre Maps/Waze.</li>
                <li><strong>Check-in</strong> — perto do pin (≤100&nbsp;m) → cria visita com lat/lng, foto e áudio.</li>
            </ol>
            <p class="mt-3 text-sm text-ink-soft">
                Enquanto isso, o GPS contínuo alimenta o painel ao vivo e o trajeto — o mesmo mecanismo do seed, só que de verdade.
            </p>
            <p class="mt-3 rounded-xl border border-brand/20 bg-brand-soft/40 px-3 py-2 text-xs text-ink-soft">
                Inteligência: <strong class="text-ink">segmento</strong> muda a busca Google;
                <strong class="text-ink">horas</strong> limitam quantas paradas cabem (e a agenda da rota);
                restaurante evita 11:30–14:00 na rota; contábil bloqueia dias 01–05 do mês.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Telas do admin (o que cada uma faz)</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink">
                <li><strong>Painel</strong> — mapa ao vivo: unidades, leads/clientes, visitas, trajeto GPS e pin do vendedor.</li>
                <li><strong>Agenda</strong> — plano do dia por vendedor (X/Y no plano vs meta casa); link “Ver no mapa”.</li>
                <li><strong>Visitas</strong> — auditoria de check-in (status, GPS, foto, áudio).</li>
                <li><strong>Unidades</strong> — território (polígono + faixa de CEP).</li>
                <li><strong>Usuários</strong> — equipe, papel Spatie, unidade, CEP base, foto.</li>
                <li><strong>Papéis</strong> — roles e permissões dinâmicas.</li>
                <li><strong>Integrações</strong> — status Mapbox / Google / Receita (sem expor secrets).</li>
                <li><strong>Documentação</strong> — esta página (fluxo, legendas, regras).</li>
            </ul>
            <p class="mt-3 text-sm text-ink-soft">
                Gestor vê só a própria equipe/unidade. Adm vê tudo. Vendedor não entra no admin — usa o PWA.
            </p>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Território (resumo)</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink">
                <li>CEP da <strong>unidade do vendedor</strong> → ok</li>
                <li>CEP <strong>sem</strong> unidade → área livre → ok</li>
                <li>CEP de <strong>outra</strong> unidade → bloqueado</li>
            </ul>
        </section>

        <section class="rounded-2xl border border-dashed border-brand/35 bg-brand-soft/30 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Logins demo</h2>
            <p class="mt-1 text-sm text-ink-soft">Senha de todos: <code class="text-xs">password</code></p>
            <ul class="mt-3 space-y-1 text-sm text-ink">
                <li><code class="text-xs">adm@prospecta.test</code> — Renata Oliveira (admin)</li>
                <li><code class="text-xs">gestor@prospecta.test</code> — Bruno Carvalho (Filial RJ Barra)</li>
                <li><code class="text-xs">vendedor@prospecta.test</code> — Camila Ferreira (Filial RJ Barra)</li>
                <li><code class="text-xs">vendedor.livre@prospecta.test</code> — Diego Santos (Representação Volta Redonda)</li>
            </ul>
        </section>

    </div>
</x-layouts.admin>
