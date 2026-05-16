# VOZ CRM

CRM B2B da VOZ construido em Laravel 12, Vue 3, Inertia.js, TypeScript e Tailwind CSS.

## Stack

- Laravel 12
- Vue 3 + Inertia.js
- TypeScript
- Tailwind CSS
- MySQL via host
- Redis em Docker
- Horizon
- Mailpit

## Setup Local

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
npm run build
```

O MySQL deve rodar fora do Docker e estar acessivel pelo host:

```dotenv
DB_HOST=host.docker.internal
DB_DATABASE=voz_crm
DB_USERNAME=voz_crm
DB_PASSWORD=secret
```

## Docker

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Servicos:

- Aplicacao: `http://localhost`
- Vite: `http://localhost:5173`
- Mailpit: `http://localhost:8025`
- Horizon: `http://localhost/horizon`

## Qualidade

```bash
composer test
composer lint
composer format
npm run lint
npm run typecheck
npm run build
```

## Documentacao do Projeto

- `PRD.md`
- `jr-design-system.md`
- `PLAN.md`
- `docs/ETAPA-0-DECISOES.md`
- `docs/MVP-ESCOPO.md`
- `docs/AMBIENTE.md`
- `docs/ETAPA-1-SETUP.md`
