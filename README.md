# Prospecta

**Prospecção de campo com inteligência de território.**  
Admin web + PWA mobile para o time de vendas — controle de área por unidade/CEP, rota do dia e check-in com evidência.

<p>
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" />
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white" />
  <img alt="PWA" src="https://img.shields.io/badge/PWA-mobile%20first-0A66C2?style=flat-square" />
  <img alt="Status" src="https://img.shields.io/badge/status-em%20construção-yellow?style=flat-square" />
</p>

---

## Por que existe

Times comerciais perdem tempo e geram conflito quando duas unidades pisam na mesma área. O Prospecta deixa explícito **onde cada um pode prospectar**, organiza a **rota do vendedor** e registra a **visita** (foto/áudio) — do desktop do gestor ao bolso do vendedor.

## O que entrega (MVP)

| Área | Capacidade |
|------|------------|
| **Território** | CEP na minha unidade / área livre / bloqueado |
| **Admin** | Unidades no mapa (Mapbox), usuários e papéis (Spatie) |
| **PWA** | Setup diário → área → rota → check-in |
| **Prospectos** | Busca (ReceitaWS mock) + rota (regra vertical / janela de ouro) |

## Arquitetura (resumo)

```
Controller → Service → Repository → Model
```

Queries **somente** no Repository. Authz com **Spatie** (roles/permissions no banco). Código de domínio em **PT-BR**. Segredos só no `.env`.

## Stack

Laravel 13 · MySQL · Blade · Vite · Mapbox · PWA · Spatie Permission · testes automatizados

## Git & qualidade

- Branch por feature (`feature/...`)
- Commits semânticos, merge com testes verdes
- TDD no domínio (território primeiro)

## Setup local

```bash
cp .env.example .env
composer install
php artisan key:generate
# configure DB no .env
php artisan migrate
php artisan serve
```

> Tokens Mapbox e afins só em `.env` — nunca no git. Ver `.env.example`.

## Roadmap (alto nível)

```
Fundação → Domínio → Spatie/Auth → Unidades → Território
    → Prospectos → Rota → Visitas → PWA → Admin → Polimento
```

## Licença

MIT (ou a que o repositório definir). Projeto de portfólio / estudo de produto comercial.
