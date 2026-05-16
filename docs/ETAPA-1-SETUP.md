# Etapa 1 — Setup do Projeto

Status: implementada.

## Entregas

- Laravel 12 criado na raiz do projeto.
- Vue 3 instalado via Inertia.js e Breeze.
- TypeScript configurado no frontend.
- Horizon instalado.
- Redis definido para cache, sessoes, filas, locks e rate limiting.
- Docker Compose criado sem container MySQL.
- MySQL configurado para uso via `host.docker.internal`.
- Mailpit configurado para desenvolvimento.
- Scripts de build, teste, lint, formatacao e typecheck configurados.
- Seeder inicial de administrador configurado por variaveis de ambiente.

## Comandos Principais

```bash
composer setup
npm run dev
npm run build
npm run lint
npm run format
npm run typecheck
composer test
composer lint
composer format
```

## Docker

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test
```

Servicos:

- Aplicacao: `http://localhost`
- Vite: `http://localhost:5173`
- Mailpit: `http://localhost:8025`
- Horizon: `http://localhost/horizon`

## Banco

O MySQL deve rodar no host, fora do Docker.

```dotenv
DB_HOST=host.docker.internal
DB_DATABASE=voz_crm
DB_USERNAME=voz_crm
DB_PASSWORD=secret
```
