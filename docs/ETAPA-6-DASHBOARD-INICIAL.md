# Etapa 6 — Dashboard Inicial

Status: implementada.

## Entregas

- Controller dedicado para `/dashboard`.
- Servico `DashboardMetrics` para centralizar os calculos do dashboard.
- Dashboard por perfil:
  - Administrador e Gestor Comercial veem a operacao consolidada.
  - SDR e Closer veem empresas, oportunidades e atividades da propria responsabilidade.
- Cards executivos:
  - Total de empresas.
  - Leads novos.
  - Oportunidades abertas.
  - Oportunidades ganhas/perdidas.
  - Valor em negociacao.
  - Valor ganho.
  - Oportunidades paradas.
  - Atividades vencidas.
- Indicadores de pipeline por etapa:
  - Quantidade de oportunidades.
  - Valor estimado.
  - Oportunidades paradas por mais de 7 dias.
- Indicadores de produtividade mensal por usuario:
  - Reunioes.
  - Tarefas.
  - Follow-ups.
  - Atividades concluidas.
  - Contadores preparados para ligacoes, e-mails e WhatsApp.
- Indicadores de carteira:
  - Valor total de inadimplencia.
  - Ticket medio de cobranca.
  - Total estimado de clientes inadimplentes.
  - Empresas com maior potencial por inadimplencia.
- Listas operacionais:
  - Atividades pendentes de hoje.
  - Oportunidades abertas paradas.
- Cache versionado dos calculos com TTL de 5 minutos.
- Invalidacao automatica do cache quando empresas, oportunidades, movimentacoes, atividades ou eventos de timeline sao alterados.
- Testes feature para agregacao de gestor e escopo de SDR.

## Rotas

- `dashboard`

## Regras Principais

- Gestores usam escopo global.
- SDR e Closer usam escopo por `responsible_user_id` em empresas/oportunidades e visibilidade propria em atividades.
- Oportunidade parada e uma oportunidade aberta sem mudanca de etapa ha mais de 7 dias.
- Produtividade usa atividades atualizadas no mes corrente.
- Canais externos ficam com contadores preparados ate os modulos de comunicacao serem implementados.

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
