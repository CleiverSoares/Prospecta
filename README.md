# Prospecta

Field sales com território — PWA do vendedor + admin desktop. Inspiração de capacidade no Salesforce Maps; visual próprio Alterdata (`#0083C1`).

## Stack

Laravel 13 · Spatie Permission · Blade + Alpine · Vite · Mapbox (admin) · Google Places/Maps (campo)

## Subir local

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000** (HTTP, não HTTPS).

## Deploy no Render (Docker)

1. Commit com `Dockerfile`, `docker/entrypoint.sh`, `.dockerignore` (e opcional `render.yaml`).
2. No Render: **New → Web Service** → repo Prospecta → **Language: Docker** (não Node).
3. Crie um **PostgreSQL** (ou MySQL) e ligue ao serviço; preencha `DB_*` (ou use o `render.yaml`).
4. Env vars mínimas:

| Key | Valor |
|-----|--------|
| `APP_KEY` | `php artisan key:generate --show` (ou Generate) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL do serviço (`https://….onrender.com`) |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `pgsql` (ou `mysql`) |
| Mapbox / Google / etc. | iguais ao `.env` local |

5. Health check: `/up`. O entrypoint roda `migrate --force` no boot.

Local opcional: `docker build -t prospecta .` e `docker run -p 8080:8080 -e PORT=8080 -e APP_KEY=… prospecta`.

### Tokens no `.env`

- `MAPBOX_ACCESS_TOKEN` / `VITE_MAPBOX_ACCESS_TOKEN` — painel e unidades
- `GOOGLE_MAPS_API_KEY` / `GOOGLE_PLACES_API_KEY` — PWA, Places, Directions
- `RECEITA_WS_DRIVER=mock|http` — mock por padrão; `http` + URL/token para Receita real
- `AJUDA_ALTERDATA_URL` — link do guia de bolso

### Ngrok

Mantenha `APP_URL=http://127.0.0.1:8000` e assets relativos. O app força HTTPS só quando o request chega via proxy HTTPS. Evite vários `artisan serve` na mesma porta.

## Logins demo (senha `password`)

| E-mail | Nome | Papel / unidade |
|--------|------|-----------------|
| `adm@prospecta.test` | Renata Oliveira | Admin |
| `gestor@prospecta.test` | Bruno Carvalho | Gestor · Filial RJ Barra |
| `vendedor@prospecta.test` | Camila Ferreira | Vendedor · Filial RJ Barra |
| `vendedor.livre@prospecta.test` | Diego Santos | Vendedor · Representação Volta Redonda |

Unidades demo batem com a rede Alterdata (Barra, Volta Redonda, Cabo Frio).
## Fluxos

**Vendedor:** Setup (4 campos, sessão) → Área (bairro/cerca) → Rota (cérebro vertical/janela/blocos) → Maps/Waze → Check-in (GPS 100m + foto + áudio; upsell gate em cliente).

**Admin:** Painel map-first com chips, filtros, camadas (unidades/prospectos/visitas/calor) e placar do dia · Unidades · Usuários · Papéis · Integrações (UI).

## Arquitetura

`Controller → Service → Repository → Model` · queries só em Repository (evolução contínua no painel via `PainelService`).

## Roadmap

Ver [`docs/ROADMAP.md`](docs/ROADMAP.md) (Prospecta V3: layout field-ops, co-piloto, dashboard, offline/cerca, APIs).
