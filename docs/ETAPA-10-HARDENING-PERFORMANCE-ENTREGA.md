# Etapa 10 — Hardening, Performance e Entrega

## Entrega

A Etapa 10 prepara o MVP para uso real, reforçando seguranca, auditoria, performance, limites operacionais e documentacao de entrega.

## Seguranca e autorizacao

- Registros de empresas, contatos, oportunidades, pipeline, atividades e comunicacoes agora respeitam escopo por responsavel.
- Administradores e gestores comerciais continuam com visao de time.
- SDRs e Closers acessam apenas registros ligados a sua responsabilidade ou criados/atribuídos a eles, conforme o modulo.
- Rotas sensiveis receberam rate limiting:
  - Comunicacoes: `throttle:communications`.
  - Configuracoes: `throttle:settings`.
  - Relatorios/exportacoes: `throttle:reports`.
  - Webhooks: `throttle:webhooks`.
- Webhooks de Twilio e Evolution API aceitam token opcional via `X-VOZ-Webhook-Token` ou `?token=`.
  - Quando o token nao esta configurado, o webhook segue aberto para ambientes de desenvolvimento.
  - Quando configurado, requests sem token correto retornam `403`.

## Criptografia de credenciais

- Credenciais sensiveis em `crm_settings` agora sao criptografadas com `Crypt::encryptString`.
- Campos criptografados:
  - `integrations.twilio.auth_token`
  - `integrations.twilio.webhook_token`
  - `integrations.evolution.key`
  - `integrations.evolution.webhook_token`
  - `integrations.mail.password`
- A tela de configuracoes mascara esses campos e preserva o valor anterior quando o input fica vazio.
- Valores antigos sao criptografados pela migration `2026_05_14_000012_create_audit_logs_and_hardening_indexes.php`.

## Auditoria

Nova tabela `audit_logs` para alteracoes criticas:

- Dados gerais da VOZ.
- Integracoes.
- Perfil de usuarios.
- Etapas do pipeline.
- Modelos de comunicacao.
- Opcoes configuraveis.

Dados sensiveis sao redigidos como `[redacted]` antes de serem salvos no log.

## Performance

Foram adicionados indices compostos para consultas frequentes:

- Empresas por responsavel, status, origem, segmento e criacao.
- Contatos por empresa, tipo e contato principal.
- Oportunidades por responsavel, status, forecast, origem e etapa.
- Atividades por responsavel, tipo, status e prazo.
- Comunicacoes por canal, usuario, status, origem e criacao.
- Timeline por empresa, tipo e ocorrencia.

O dashboard e as configuracoes seguem usando cache; mutations relevantes invalidam o cache de dashboard.

## Documentacao de entrega

Documentacao operacional adicionada em:

- `docs/ENTREGA-SETUP-DEPLOY.md`
- `.env.example`

O arquivo cobre:

- Setup Docker.
- Variaveis de ambiente.
- Redis, filas, Horizon e Scheduler.
- Checklist de deploy.
- Rotina de validacao.

## Testes

Cobertura adicionada em `tests/Feature/CRM/HardeningTest.php`:

- Credenciais de integracao sao criptografadas, mascaradas e auditadas.
- Webhook com token configurado bloqueia requests sem token.
- Usuario nao gestor nao acessa registros de outros responsaveis por URL direta.
- Rotas sensiveis possuem rate limiting configurado.

Tambem foram mantidos os testes dos fluxos principais, permissoes, webhooks, comunicacoes, pipeline, dashboard, empresas, contatos, atividades, automacoes e relatorios.
