# Entrega — Setup, Ambiente e Deploy

## Setup local com Docker

1. Copie `.env.example` para `.env`.
2. Configure o MySQL externo no host:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
3. Suba os servicos Docker:
   - `docker compose up -d`
4. Instale dependencias e gere assets:
   - `composer install`
   - `npm install`
   - `npm run build`
5. Prepare a aplicacao:
   - `php artisan key:generate`
   - `php artisan migrate --force`
   - `CACHE_STORE=array php artisan db:seed --force` quando o PHP CLI local nao tiver `phpredis`.

## Servicos esperados

- App Laravel/PHP.
- Vite em desenvolvimento.
- Redis para cache, fila, sessao, locks e rate limits.
- Queue worker para `default`, `communications`, `automations`, `reports` e `webhooks`.
- Horizon para supervisao.
- Scheduler para `schedule:work`.
- Mailpit em desenvolvimento.
- MySQL externo, fora do Docker.

## Variaveis essenciais

Aplicacao:

- `APP_KEY`
- `APP_ENV`
- `APP_URL`
- `APP_TIMEZONE=America/Sao_Paulo`

Banco:

- `DB_CONNECTION=mysql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Redis e filas:

- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `REDIS_CLIENT=phpredis`
- `REDIS_HOST=redis`
- `HORIZON_PREFIX=voz_crm_horizon:`

E-mail:

- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Twilio:

- `TWILIO_ACCOUNT_SID`
- `TWILIO_AUTH_TOKEN`
- `TWILIO_FROM_NUMBER`
- `TWILIO_VOICE_WEBHOOK_URL`
- `TWILIO_WEBHOOK_TOKEN`

Evolution API:

- `EVOLUTION_API_URL`
- `EVOLUTION_API_KEY`
- `EVOLUTION_INSTANCE`
- `EVOLUTION_WEBHOOK_TOKEN`

Seed inicial:

- `SEED_USER_NAME`
- `SEED_USER_EMAIL`
- `SEED_USER_PASSWORD`

## Checklist de deploy

- [ ] `.env` criado com `APP_KEY` real.
- [ ] `APP_DEBUG=false`.
- [ ] MySQL externo acessivel pelo app.
- [ ] Redis acessivel e com prefixos definidos.
- [ ] Migrations executadas.
- [ ] Seed inicial executado em ambiente novo.
- [ ] `npm run build` executado.
- [ ] Queue workers ativos.
- [ ] Horizon ativo e protegido.
- [ ] Scheduler ativo.
- [ ] Storage link criado se anexos publicos forem habilitados: `php artisan storage:link`.
- [ ] Webhooks configurados no provedor.
- [ ] Tokens de webhook definidos quando o provedor permitir.
- [ ] Credenciais de integracao cadastradas em Configuracoes.
- [ ] Rotina de backup do MySQL definida.
- [ ] Logs centralizados ou persistidos fora do container.
- [ ] `php artisan test`, `vendor/bin/pint --test`, `npm run lint` e `npm run build` executados antes da entrega.

## Validacao operacional

- Login com usuario administrador seedado.
- Criacao de empresa e contato.
- Criacao e movimentacao de oportunidade no pipeline.
- Criacao/conclusao/reagendamento de atividade.
- Envio de e-mail, WhatsApp e tentativa de ligacao em ambiente com credenciais.
- Recebimento de webhook com idempotencia.
- Execucao de automacoes comerciais.
- Abertura de dashboard.
- Exportacao de relatorio CSV, Excel e PDF.
- Alteracao de configuracoes sem deploy.
