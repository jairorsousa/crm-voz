# Ambiente de Desenvolvimento

Este documento define o ambiente esperado para iniciar a Etapa 1.

## 1. Regra Principal

O projeto deve rodar em Docker, exceto o banco MySQL.

MySQL sera acessado pelo host da maquina usando `host.docker.internal`.

## 2. Servicos Docker

Servicos planejados:

- `nginx`: entrada HTTP.
- `app`: PHP-FPM com Laravel 12.
- `node`: Vite em desenvolvimento, se nao for embutido no `app`.
- `redis`: cache, sessoes, filas, locks e rate limiting.
- `queue`: worker Laravel.
- `horizon`: monitoramento das filas.
- `scheduler`: Laravel Scheduler.
- `mailpit`: captura de e-mails em desenvolvimento.

Servico explicitamente fora do Docker:

- `mysql`.

## 3. Variaveis Base

Arquivo esperado na Etapa 1: `.env.example`.

```dotenv
APP_NAME="VOZ CRM"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=voz_crm
DB_USERNAME=voz_crm
DB_PASSWORD=secret

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=crm@voz.local
MAIL_FROM_NAME="${APP_NAME}"

TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=

EVOLUTION_API_URL=
EVOLUTION_API_KEY=
EVOLUTION_INSTANCE=voz
```

## 4. Filas Redis

Filas padrao:

- `default`
- `communications`
- `automations`
- `webhooks`
- `reports`

Uso:

- `default`: tarefas gerais.
- `communications`: Twilio, e-mail e WhatsApp.
- `automations`: regras comerciais.
- `webhooks`: retornos externos.
- `reports`: exportacoes.

## 5. Configuracao MySQL Host

Banco esperado:

```sql
CREATE DATABASE voz_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'voz_crm'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON voz_crm.* TO 'voz_crm'@'%';
FLUSH PRIVILEGES;
```

Observacao:

- Em desenvolvimento local, o usuario/senha podem mudar.
- A aplicacao deve ler tudo do `.env`.
- Nao versionar credenciais reais.

## 6. Qualidade e Scripts Esperados

Scripts a criar na Etapa 1:

- `composer test`
- `composer lint`
- `composer format`
- `npm run dev`
- `npm run build`
- `npm run lint`
- `npm run format`
- `npm run typecheck`

Comandos Docker a documentar:

- subir ambiente;
- parar ambiente;
- executar migrations;
- rodar testes;
- acessar container app;
- acompanhar filas/Horizon.

## 7. Criterios de Aceite do Ambiente

- Laravel acessa MySQL no host.
- Redis responde para cache, sessao e fila.
- Horizon abre no ambiente local.
- Mailpit captura e-mails.
- Vite compila Vue.
- Teste inicial roda em container.
- Nenhum container MySQL e criado.

