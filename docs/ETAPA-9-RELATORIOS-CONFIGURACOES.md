# Etapa 9 — Relatorios, Exportacoes e Configuracoes

## Entrega

A Etapa 9 substitui os placeholders de Relatorios e Configuracoes por telas operacionais em Laravel + Vue/Inertia, seguindo o JR Design System ja aplicado no projeto.

## Backend

- Novas tabelas:
  - `crm_settings`: dados da VOZ e configuracoes de integracao em runtime.
  - `crm_option_values`: listas configuraveis para motivos de perda, origens, segmentos e tipos de contato.
  - `report_exports`: auditoria e download de exportacoes geradas.
- Novos models:
  - `CrmSetting`
  - `CrmOptionValue`
  - `ReportExport`
- Novo enum:
  - `ReportExportStatus`
- Novos servicos:
  - `ReportBuilder`: monta catalogo, filtros, indicadores e tabelas de relatorio.
  - `ReportExportService`: gera CSV, Excel compatível e PDF simples.
  - `IntegrationSettings`: aplica configuracoes persistidas sobre valores de `.env`.
- Novo job:
  - `BuildReportExport`, na fila `reports`.

## Relatorios

A tela `/relatorios` entrega filtros por:

- Periodo.
- Usuario.
- Etapa.
- Origem.
- Segmento.
- Status.

Relatorios disponiveis:

- Empresas cadastradas.
- Oportunidades por periodo.
- Ganhas e perdidas.
- Motivos de perda.
- Produtividade por usuario.
- Ligacoes.
- E-mails.
- WhatsApp.
- Reunioes.
- Forecast.
- Carteira potencial.

Exportacoes:

- CSV imediato.
- Excel imediato em arquivo compativel com planilhas.
- PDF para relatorios prioritarios.
- Exportacao em fila para bases maiores, com historico e download posterior.

## Configuracoes

A tela `/configuracoes` permite alterar:

- Dados institucionais da VOZ.
- Credenciais e remetentes de Twilio, Evolution API e e-mail.
- Perfil dos usuarios.
- Nome, cor, ordem e flags das etapas do pipeline.
- Motivos de perda, origens, segmentos e tipos de contato.

As integracoes de ligacao, WhatsApp e e-mail passam a consultar `IntegrationSettings`, entao os ajustes salvos em banco mudam o comportamento sem necessidade de deploy.

Os modelos de e-mail e WhatsApp foram extraidos para uma secao propria em `/modelos`, documentada em `docs/MELHORIA-MODELOS-COMUNICACAO.md`.

## Rotas principais

- `GET /relatorios`
- `GET /relatorios/exportar/{report}/{format}`
- `POST /relatorios/exportacoes/{report}/{format}`
- `GET /relatorios/exportacoes/{export}/download`
- `GET /configuracoes`
- `PATCH /configuracoes/geral`
- `PATCH /configuracoes/integracoes/{integration}`
- `PATCH /configuracoes/usuarios/{user}`
- `PATCH /configuracoes/pipeline/etapas/{stage}`
- `PATCH /configuracoes/modelos/{template}`
- `POST /configuracoes/opcoes`
- `PATCH /configuracoes/opcoes/{option}`

## Testes

Cobertura adicionada em `tests/Feature/CRM/ReportSettingsTest.php`:

- Gestor visualiza relatorios filtrados.
- Exportacao imediata gera CSV baixavel.
- Exportacao em fila gera arquivo e registro concluido.
- Administrador altera configuracoes de runtime.
- Gestor comercial nao acessa configuracoes administrativas.
