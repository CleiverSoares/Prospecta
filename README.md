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

### Tokens no `.env`

- `MAPBOX_ACCESS_TOKEN` / `VITE_MAPBOX_ACCESS_TOKEN` — painel e unidades
- `GOOGLE_MAPS_API_KEY` / `GOOGLE_PLACES_API_KEY` — PWA, Places, Directions
- `RECEITA_WS_DRIVER=mock|http` — mock por padrão; `http` + URL/token para Receita real
- `AJUDA_ALTERDATA_URL` — link do guia de bolso

### Ngrok

Mantenha `APP_URL=http://127.0.0.1:8000` e assets relativos. O app força HTTPS só quando o request chega via proxy HTTPS. Evite vários `artisan serve` na mesma porta.

## Logins demo (senha `password`)

| E-mail | Papel |
|--------|--------|
| `adm@prospecta.test` | Admin |
| `gestor@prospecta.test` | Gestor Filial Rio |
| `vendedor@prospecta.test` | Vendedor PWA |
| `vendedor.livre@prospecta.test` | Área livre |

## Fluxos

**Vendedor:** Setup (4 campos, sessão) → Área (bairro/cerca) → Rota (cérebro vertical/janela/blocos) → Maps/Waze → Check-in (GPS 100m + foto + áudio; upsell gate em cliente).

**Admin:** Painel map-first com chips, filtros, camadas (unidades/prospectos/visitas/calor) e placar do dia · Unidades · Usuários · Papéis · Integrações (UI).

## Arquitetura

`Controller → Service → Repository → Model` · queries só em Repository (evolução contínua no painel via `PainelService`).

## Roadmap

Ver [`docs/ROADMAP.md`](docs/ROADMAP.md) (Prospecta V3: layout field-ops, co-piloto, dashboard, offline/cerca, APIs).
