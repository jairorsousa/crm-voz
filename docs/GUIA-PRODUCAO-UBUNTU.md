# Guia de Produção Ubuntu — VOZ CRM

Este guia descreve um caminho recomendado para subir o VOZ CRM em um servidor Ubuntu usando Docker Compose para a aplicação, Nginx, Redis, filas, Horizon e Scheduler. O MySQL será remoto/via host e não ficará neste servidor.

## 1. Arquitetura Recomendada

Serviços no servidor:

- Ubuntu 22.04 ou 24.04 LTS.
- Docker e Docker Compose plugin.
- Nginx no host como proxy reverso local.
- Cloudflare na frente da aplicação, gerenciando proxy e certificados.
- Domínio de produção: `crm.grupovoz.dev.br`.
- MySQL remoto/via host, fora deste servidor.
- Containers do projeto:
  - `nginx`: Nginx interno da aplicação;
  - `app`: PHP-FPM Laravel;
  - `redis`: cache, sessão, filas, locks e rate limiting;
  - `queue`: worker das filas;
  - `horizon`: painel e supervisor de filas;
  - `scheduler`: execução do Laravel Scheduler.

Em produção, não suba os serviços `node` e `mailpit`.

## 2. Preparar o Servidor

Atualize o Ubuntu:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl git unzip ufw nginx
```

Instale Docker:

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Permita seu usuário usar Docker sem `sudo`:

```bash
sudo usermod -aG docker $USER
```

Saia e entre novamente no SSH antes de continuar.

Configure firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 3. Preparar MySQL Remoto

O MySQL não será instalado neste servidor. Use os dados do banco remoto fornecidos pelo host/provedor.

No `.env`, configure:

```dotenv
DB_CONNECTION=mysql
DB_HOST=host-do-mysql
DB_PORT=3306
DB_DATABASE=voz_crm
DB_USERNAME=voz_crm
DB_PASSWORD=senha-do-banco
```

Teste a conexão do servidor com o banco remoto antes de rodar migrations:

```bash
docker compose run --rm app php artisan migrate:status
```

Se o banco remoto exigir whitelist de IP, libere o IP público do servidor Ubuntu no painel do provedor.

## 4. Baixar o Projeto

Escolha um diretório de deploy:

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www
git clone git@github.com:jairorsousa/crm-voz.git voz-crm
cd voz-crm
```

Se o servidor ainda não tiver chave SSH no GitHub, use HTTPS:

```bash
git clone https://github.com/jairorsousa/crm-voz.git voz-crm
```

## 5. Configurar `.env`

Crie o arquivo:

```bash
cp .env.example .env
```

Edite:

```bash
nano .env
```

Valores mínimos para produção:

```dotenv
APP_NAME="VOZ CRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://crm.grupovoz.dev.br
APP_PORT=8080
APP_TIMEZONE=America/Sao_Paulo

DB_CONNECTION=mysql
DB_HOST=host-do-mysql
DB_PORT=3306
DB_DATABASE=voz_crm
DB_USERNAME=voz_crm
DB_PASSWORD=senha-do-banco

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_FORWARD_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=usuario-smtp
MAIL_PASSWORD=senha-smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=crm@grupovoz.dev.br
MAIL_FROM_NAME="${APP_NAME}"

TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_API_KEY=SKxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_API_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TWIML_APP_SID=APxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_CALLER_ID=+5511982403231
TWILIO_WEBHOOK_TOKEN=troque-este-token

SEED_USER_NAME="Administrador VOZ"
SEED_USER_EMAIL=admin@grupovoz.dev.br
SEED_USER_PASSWORD=troque-esta-senha-inicial
```

Não coloque credenciais reais no Git. O `.env` já está ignorado.

## 6. Build e Preparação da Aplicação

Construa a imagem PHP:

```bash
docker compose build app
```

Instale dependências PHP de produção:

```bash
docker compose run --rm app composer install --no-dev --optimize-autoloader
```

Gere a chave da aplicação:

```bash
docker compose run --rm app php artisan key:generate --force
```

Gere os assets Vue/Inertia:

```bash
docker run --rm \
  -v "$PWD":/var/www/html \
  -w /var/www/html \
  node:24-alpine \
  sh -c "npm ci && npm run build"
```

Ajuste permissões:

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

Execute migrations:

```bash
docker compose run --rm app php artisan migrate --force
```

Em ambiente novo, rode o seed inicial:

```bash
docker compose run --rm app php artisan db:seed --force
```

Crie o link de storage:

```bash
docker compose run --rm app php artisan storage:link
```

Otimize Laravel:

```bash
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache
```

## 7. Subir os Containers de Produção

Suba apenas os serviços necessários:

```bash
docker compose up -d nginx app redis queue horizon scheduler
```

Verifique:

```bash
docker compose ps
docker compose logs -f app
```

Teste localmente no servidor:

```bash
curl -I http://127.0.0.1:8080
```

## 8. Nginx do Host Atrás da Cloudflare

A aplicação ficará atrás do proxy da Cloudflare. Os certificados serão gerados pela Cloudflare, então este guia não usa Certbot.

No painel da Cloudflare:

1. Crie o registro DNS `crm.grupovoz.dev.br` apontando para o IP público do servidor Ubuntu.
2. Ative o proxy da Cloudflare no registro.
3. Em **SSL/TLS > Origin Server**, gere um **Origin Certificate** para `crm.grupovoz.dev.br`.
4. Configure o modo SSL/TLS como **Full (strict)**.

No servidor, crie os arquivos do certificado:

```bash
sudo mkdir -p /etc/ssl/cloudflare
sudo nano /etc/ssl/cloudflare/crm.grupovoz.dev.br.pem
sudo nano /etc/ssl/cloudflare/crm.grupovoz.dev.br.key
sudo chmod 600 /etc/ssl/cloudflare/crm.grupovoz.dev.br.key
```

Cole o certificado no arquivo `.pem` e a chave privada no arquivo `.key`.

Crie o arquivo:

```bash
sudo nano /etc/nginx/sites-available/voz-crm
```

Conteúdo:

```nginx
server {
    listen 80;
    server_name crm.grupovoz.dev.br;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name crm.grupovoz.dev.br;

    ssl_certificate /etc/ssl/cloudflare/crm.grupovoz.dev.br.pem;
    ssl_certificate_key /etc/ssl/cloudflare/crm.grupovoz.dev.br.key;

    client_max_body_size 32m;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header CF-Connecting-IP $http_cf_connecting_ip;
    }
}
```

Ative:

```bash
sudo ln -s /etc/nginx/sites-available/voz-crm /etc/nginx/sites-enabled/voz-crm
sudo nginx -t
sudo systemctl reload nginx
```

Depois, confirme no `.env`:

```dotenv
APP_URL=https://crm.grupovoz.dev.br
```

Se a aplicação estiver publicada por Cloudflare Tunnel, o hostname público deve apontar para o serviço interno do Compose:

```txt
crm.grupovoz.dev.br -> http://nginx:80
```

O Laravel está configurado para confiar nos headers do proxy. Isso é necessário para evitar erro de mixed content, onde a tela abre em HTTPS mas o login tenta chamar `http://crm.grupovoz.dev.br/login`.

E rode:

```bash
docker compose run --rm app php artisan config:cache
docker compose restart app queue horizon scheduler
```

## 9. Configurar Canais de Comunicação

Depois de logar como administrador:

1. Acesse `https://crm.grupovoz.dev.br/canais`.
2. Crie ou revise o canal de Ligação:
   - Tipo: Ligação;
   - Provedor: Twilio;
   - Compartilhado com o time.
3. Crie canais de WhatsApp por usuário:
   - Tipo: WhatsApp;
   - Provedor: Evolution API;
   - Usuário com acesso.
4. Crie canais de E-mail por usuário:
   - Tipo: E-mail;
   - Provedor: SMTP;
   - Usuário com acesso.

Configure os webhooks nos provedores apontando para:

```txt
https://crm.grupovoz.dev.br/webhooks/twilio/calls
https://crm.grupovoz.dev.br/webhooks/evolution/whatsapp
```

Para ligar e falar pelo CRM usando Twilio Voice no navegador, configure o TwiML App da Twilio com:

```txt
Voice Request URL: https://crm.grupovoz.dev.br/twilio/voice
Method: POST
```

Depois, em `Canais > Ligação VOZ`, preencha `Account SID`, `API Key SID`, `API Secret`, `TwiML App SID` e `Caller ID`. O navegador pedirá permissão de microfone quando o usuário clicar em `Ligar pelo CRM`.

Se usar token de webhook, envie no header:

```txt
X-VOZ-Webhook-Token: seu-token
```

Ou como query string:

```txt
?token=seu-token
```

## 10. Rotina de Deploy de Atualização

Para publicar novas versões:

```bash
cd /var/www/voz-crm
git pull origin main

docker compose build app
docker compose run --rm app composer install --no-dev --optimize-autoloader

docker run --rm \
  -v "$PWD":/var/www/html \
  -w /var/www/html \
  node:24-alpine \
  sh -c "npm ci && npm run build"

docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache

docker compose up -d nginx app redis queue horizon scheduler
docker compose restart queue horizon scheduler
```

## 11. Backups

Crie diretório:

```bash
sudo mkdir -p /var/backups/voz-crm
sudo chown -R $USER:$USER /var/backups/voz-crm
```

Backup manual:

```bash
mysqldump -h host-do-mysql -P 3306 -u voz_crm -p voz_crm | gzip > /var/backups/voz-crm/voz_crm_$(date +%F_%H-%M).sql.gz
```

Adicione ao `crontab`:

```bash
crontab -e
```

Exemplo diário às 02:00:

```cron
0 2 * * * mysqldump -h host-do-mysql -P 3306 -u voz_crm -p'SUA_SENHA' voz_crm | gzip > /var/backups/voz-crm/voz_crm_$(date +\%F_\%H-\%M).sql.gz
```

Recomendações:

- Enviar backups para outro servidor, S3 ou storage externo.
- Testar restauração periodicamente.
- Não manter apenas backup local no mesmo servidor.

## 12. Logs e Monitoramento

Ver logs:

```bash
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f horizon
docker compose logs -f scheduler
```

Status dos serviços:

```bash
docker compose ps
```

Horizon:

```txt
https://crm.grupovoz.dev.br/horizon
```

Em produção, o Horizon permite acesso ao e-mail definido em `SEED_USER_EMAIL`.

## 13. Checklist Final

- [ ] DNS `crm.grupovoz.dev.br` aponta para o IP do servidor.
- [ ] Proxy da Cloudflare ativo.
- [ ] Certificado gerenciado pela Cloudflare.
- [ ] Docker e Docker Compose instalados.
- [ ] MySQL remoto acessível pelo servidor.
- [ ] `.env` configurado com `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` gerada.
- [ ] `composer install --no-dev` executado.
- [ ] `npm run build` executado.
- [ ] Migrations executadas.
- [ ] Seed inicial executado.
- [ ] Containers `nginx`, `app`, `redis`, `queue`, `horizon` e `scheduler` ativos.
- [ ] Nginx do host proxyando para `127.0.0.1:8080`.
- [ ] Canais de comunicação configurados.
- [ ] Webhooks configurados.
- [ ] Backup automático do MySQL configurado.
- [ ] Login administrador testado.
- [ ] Dashboard, empresas, pipeline, canais, automações e relatórios testados.
