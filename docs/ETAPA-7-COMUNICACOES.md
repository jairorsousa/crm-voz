# Etapa 7 — Comunicacoes: Twilio, E-mail e WhatsApp

Status: implementada.

## Entregas

- Migrations de:
  - `communication_messages`
  - `communication_templates`
  - `communication_webhook_events`
- Enums de canal, direcao, origem e status de comunicacao.
- Model unico `CommunicationMessage` para ligacoes, e-mails e WhatsApp.
- Templates basicos de e-mail e WhatsApp no seed.
- Tela de ligacoes em `/ligacoes`:
  - Registro de tentativa.
  - Enfileiramento de chamada via Twilio.
  - Falha clara quando Twilio nao esta configurado.
  - Atualizacao manual de status/anotacao.
- Tela de e-mails em `/emails`:
  - Listagem com filtros.
  - Composicao manual.
  - CC, CCO e anexos.
  - Uso opcional de modelos.
  - Envio por job na fila `communications`.
- Tela de WhatsApp em `/whatsapp`:
  - Listagem com filtros.
  - Envio manual.
  - Uso opcional de modelos.
  - Envio por Evolution API em job na fila `communications`.
- Webhooks publicos e idempotentes:
  - `webhooks.twilio.calls`
  - `webhooks.evolution.whatsapp`
- Registro automatico no historico da empresa para:
  - Comunicacao enfileirada.
  - Comunicacao enviada.
  - Comunicacao recebida.
  - Falhas de integracao.
  - Atualizacoes por webhook.
- Tela da empresa atualizada com acoes rapidas de ligar, enviar e-mail e enviar WhatsApp por contato.
- Dashboard atualizado para contar produtividade de ligacoes, e-mails e WhatsApp.
- Testes feature para envio, falhas claras e webhooks idempotentes.

## Configuracoes

Twilio:

```env
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=
TWILIO_VOICE_WEBHOOK_URL=
```

Evolution API:

```env
EVOLUTION_API_URL=
EVOLUTION_API_KEY=
EVOLUTION_INSTANCE=voz
```

Fila:

```env
QUEUE_CONNECTION=redis
```

Os jobs usam a fila logica `communications`. Em producao, rode um worker dedicado para essa fila.

## Rotas

- `calls.index`
- `calls.store`
- `calls.update`
- `emails.index`
- `emails.create`
- `emails.store`
- `whatsapp.index`
- `whatsapp.create`
- `whatsapp.store`
- `webhooks.twilio.calls`
- `webhooks.evolution.whatsapp`

## Regras Principais

- Toda comunicacao exige empresa e contato.
- Oportunidade e opcional, mas quando informada precisa pertencer a mesma empresa.
- Falhas de Twilio/Evolution nao quebram a tela: a mensagem fica como `failed` e o erro aparece no registro.
- Webhooks usam `communication_webhook_events` para evitar processamento duplicado.
- Mensagens recebidas por WhatsApp tentam associar o contato pelo telefone/WhatsApp.

## Validacao

```bash
composer format
composer lint
composer test
npm run format
npm run lint
npm run typecheck
npm run build
php artisan route:list --except-vendor
```

Todos os comandos acima devem passar antes de considerar a etapa pronta.
