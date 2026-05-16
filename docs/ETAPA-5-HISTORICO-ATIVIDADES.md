# Etapa 5 — Historico Centralizado e Atividades

Status: implementada.

## Entregas

- Migration de `activities` com vinculo a empresa, contato, oportunidade, responsavel e criador.
- Models e relacionamentos para atividades em empresas, contatos e oportunidades.
- Enums `ActivityType` e `ActivityStatus`.
- Factory e seed de atividades de exemplo, incluindo tarefas do dia e vencidas.
- CRUD completo de atividades em Vue/Inertia.
- Filtros de atividades por busca, status, tipo, prioridade, responsavel, empresa e periodo.
- Dashboard pessoal de atividades com totais de hoje, vencidas, pendentes e concluidas.
- Acoes rapidas para concluir, cancelar e reagendar atividades.
- Timeline centralizada da empresa com paginacao, busca e filtros por tipo, usuario e contato.
- Registro automatico no historico para criacao, atualizacao, conclusao, cancelamento e reagendamento de atividades.
- Visibilidade por perfil: gestores veem todas as atividades; SDR e Closer veem atividades atribuida ou criadas por eles.
- Tela de detalhes da empresa atualizada com proximas atividades e link para historico completo.

## Rotas

- `activities.index`
- `activities.create`
- `activities.store`
- `activities.edit`
- `activities.update`
- `activities.destroy`
- `activities.complete`
- `activities.cancel`
- `activities.reschedule`
- `companies.timeline`

## Regras Principais

- Atividades vencidas sao destacadas quando ainda estao pendentes e possuem `due_at` menor que a data atual.
- Reagendar uma atividade retorna o status para pendente e limpa datas de conclusao/cancelamento.
- Concluir e cancelar registram a data da acao e criam eventos no historico da empresa.
- O historico da empresa continua sendo a camada unica para rastrear eventos relevantes do CRM.

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
