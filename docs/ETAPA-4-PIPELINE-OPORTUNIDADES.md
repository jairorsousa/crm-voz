# Etapa 4 — Pipeline e Oportunidades

Status: implementada.

## Entregas

- Migrations de `pipelines`, `pipeline_stages`, `opportunities` e `opportunity_stage_movements`.
- Models `Pipeline`, `PipelineStage`, `Opportunity` e `OpportunityStageMovement`.
- Enum `OpportunityStatus`.
- Pipeline unico padrao com as 9 etapas do PRD.
- CRUD completo de oportunidades.
- Kanban em Vue com drag and drop nativo.
- Endpoint de movimentacao de oportunidade entre etapas.
- Filtros do pipeline por responsavel, etapa, origem, temperatura e previsao de fechamento.
- Totais por etapa: quantidade e valor estimado.
- Registro automatico de mudanca de etapa no historico da empresa.
- Registro de ultima movimentacao em `last_stage_changed_at`.
- Modal obrigatorio para motivo de perda ao mover para `Fechado perdido`.
- Modal obrigatorio para valor/data de fechamento ao mover para `Fechado ganho`.
- Tela da empresa atualizada para listar oportunidades vinculadas.
- Testes feature para etapas padrao, criacao de oportunidade, movimentacao, historico e validacoes de ganho/perda.

## Etapas Padrao

1. Lead novo
2. Primeiro contato
3. Qualificacao
4. Reuniao agendada
5. Reuniao realizada
6. Proposta enviada
7. Negociacao
8. Fechado ganho
9. Fechado perdido

## Rotas

- `pipeline.index`
- `pipeline.move`
- `opportunities.index`
- `opportunities.create`
- `opportunities.store`
- `opportunities.edit`
- `opportunities.update`
- `opportunities.destroy`

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

Todos os comandos acima foram executados com sucesso.
