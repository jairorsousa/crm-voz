# Etapa 8 — Automacoes Comerciais

Status: implementada.

## Entregas

- Migrations de:
  - `commercial_automations`
  - `automation_executions`
  - `internal_notifications`
- Enums de gatilho, tipo de acao e status de execucao.
- Motor `AutomationEngine` com:
  - Filtro de condicoes.
  - Chave de idempotencia.
  - Lock via cache/Redis.
  - Registro de resultado em `automation_executions`.
  - Registro automatico no historico da empresa.
- Tela `/automacoes` para gestores:
  - Visualizar automacoes.
  - Ativar/pausar automacoes.
  - Executar checks recorrentes manualmente.
  - Ver execucoes recentes e erros.
- Gatilhos implementados:
  - Oportunidade mudou de etapa.
  - Reuniao agendada.
  - Proposta sem resposta.
  - Lead sem interacao.
  - Tarefa vencida.
- Acoes implementadas:
  - Criar atividade.
  - Enviar e-mail automatico.
  - Enviar WhatsApp automatico.
  - Criar notificacao interna.
  - Adicionar anotacao no historico.
- Seed com automacoes comerciais iniciais:
  - Follow-up automatico apos proposta.
  - Follow-up apos reuniao agendada.
  - Proposta sem resposta.
  - Lead sem interacao.
  - Tarefa vencida.
- Comando recorrente:
  - `php artisan crm:run-automation-checks`
- Testes feature para toggle, execucao por mudanca de etapa, idempotencia e checks recorrentes.

## Regras Principais

- Cada execucao usa uma chave unica por automacao, gatilho e evento.
- Eventos repetidos com a mesma chave nao geram tarefas, notificacoes ou mensagens duplicadas.
- Toda automacao executada com sucesso registra `automation.executed` no historico da empresa.
- Checks recorrentes devem rodar por agendamento em producao.
- Acoes de e-mail e WhatsApp reutilizam a estrutura da Etapa 7 e entram na fila de comunicacoes.

## Agendamento Recomendado

```bash
php artisan crm:run-automation-checks
```

Em producao, agende esse comando no scheduler do Laravel ou no cron do container.

## Rotas

- `automations.index`
- `automations.toggle`
- `automations.run-checks`

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
