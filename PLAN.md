# PLAN.md — Plano de Desenvolvimento VOZ CRM

## 1. Direcao do Projeto

O VOZ CRM sera construido como uma aplicacao unica em Laravel 12, com frontend em Vue.js 3 dentro do proprio projeto Laravel. A recomendacao tecnica e usar Inertia.js para unir Laravel e Vue sem criar dois repositorios, dois deploys ou uma API separada prematuramente.

O produto deve seguir o PRD com a empresa como entidade central, historico unificado, pipeline comercial visual, comunicacao integrada e dashboards para gestao comercial. O layout deve seguir o `jr-design-system.md`, adaptando seus componentes Blade/Livewire para componentes Vue reutilizaveis.

## 2. Decisoes Tecnicas Principais

### 2.1 Stack

- Backend: Laravel 12.
- Frontend: Vue.js 3 + Inertia.js + TypeScript.
- CSS: Tailwind CSS usando CSS Custom Properties do JR Design System.
- Build: Vite.
- Banco de dados: MySQL externo, acessado via host.
- Cache, sessoes, filas, locks e rate limiting: Redis em Docker.
- Filas: Laravel Queue com Redis.
- Monitoramento de filas: Laravel Horizon.
- Agendamentos: Laravel Scheduler em container proprio.
- Realtime/eventos internos: Laravel events; avaliar Laravel Reverb quando houver necessidade real de atualizacao em tempo real.
- Permissoes: policies nativas do Laravel + pacote de roles/permissoes quando a estrutura estiver estabilizada.
- Testes: Pest ou PHPUnit para backend; Vitest para componentes/composables criticos no frontend; testes de feature para fluxos principais.

### 2.2 Projeto Unico

Estrutura recomendada:

```txt
voz-crm/
├── app/
│   ├── Domain/
│   │   ├── CRM/
│   │   ├── Pipeline/
│   │   ├── Communications/
│   │   ├── Automations/
│   │   └── Reporting/
│   ├── Http/
│   ├── Models/
│   └── Policies/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   └── Jr/
│   │   ├── Layouts/
│   │   ├── Pages/
│   │   ├── Composables/
│   │   └── Types/
│   └── css/
├── routes/
│   ├── web.php
│   └── console.php
├── docker/
├── docker-compose.yml
└── PLAN.md
```

O Laravel sera a fonte de regras de negocio, seguranca, persistencia e autorizacao. O Vue sera responsavel pela experiencia rica de interface: Kanban, filtros, modais, timeline, dashboards e formularios reativos.

## 3. Docker e Ambiente

### 3.1 Servicos Docker

O banco MySQL nao deve subir em Docker. Ele sera externo e acessado pelo host. Os demais servicos devem ser containerizados.

Servicos previstos:

- `nginx`: servidor web.
- `app`: PHP-FPM com extensoes necessarias para Laravel, MySQL, Redis, intl, zip, gd/imagick se houver anexos/imagens.
- `node`: ambiente para Vite em desenvolvimento, ou Node instalado no container `app` se a equipe preferir simplificar.
- `redis`: cache, fila, sessao, rate limit e locks.
- `queue`: worker Laravel para jobs assincronos.
- `horizon`: painel e supervisor de filas.
- `scheduler`: executa `php artisan schedule:work`.
- `mailpit`: captura de e-mails em desenvolvimento.
- `reverb`: opcional para realtime quando o produto precisar de eventos ao vivo no Kanban/chat.

### 3.2 Banco via Host

Configuracao `.env` esperada:

```dotenv
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
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Em Linux, pode ser necessario configurar `extra_hosts` no `docker-compose.yml`:

```yaml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### 3.3 Redis bem configurado

Usos obrigatorios do Redis desde o inicio:

- Cache de dashboard e contadores.
- Cache de permissoes e configuracoes.
- Sessoes da aplicacao.
- Filas de e-mail, WhatsApp, Twilio, automacoes e webhooks.
- Locks para evitar duplicidade de automacoes e disparos.
- Rate limiting por usuario e por integracao externa.

Filas recomendadas:

- `default`: jobs comuns.
- `communications`: e-mail, WhatsApp e Twilio.
- `automations`: execucao de automacoes comerciais.
- `webhooks`: processamento de retornos da Evolution API, Twilio e e-mail.
- `reports`: exportacoes CSV, Excel e PDF.

## 4. Adaptacao do JR Design System para Vue

O `jr-design-system.md` foi escrito com exemplos Blade/Livewire, mas o projeto usara Vue 3. A regra sera preservar identidade visual, tokens e padroes de interface, migrando a implementacao para componentes Vue.

### 4.1 Tokens visuais

Implementar em `resources/css/app.css`:

- Cores primarias: `--colors-primary-g100`, `--colors-primary-g500`, `--colors-primary-g600`.
- Escala monocromatica: `--colors-mono-*`.
- Semanticas: success, error, info, up, down.
- Dark mode via `data-theme="dark"`.
- Sombras: `shadow-card`, `shadow-dropdown`, `shadow-elevated`.
- Radius: `rounded-pill`, `rounded-xl`, `rounded-2xl`.
- Fonte: Reddit Sans.

### 4.2 Componentes Vue equivalentes

Criar componentes em `resources/js/Components/Jr/`:

- `JrButton.vue`
- `JrInput.vue`
- `JrBadge.vue`
- `JrAlert.vue`
- `JrCard.vue`
- `JrTable.vue`
- `JrModal.vue`
- `JrDropdown.vue`
- `JrAvatar.vue`
- `JrEmptyState.vue`
- `JrIconBox.vue`
- `JrPageHeader.vue`
- `JrStatCard.vue`

### 4.3 Layout principal

Criar:

- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Layouts/GuestLayout.vue`
- `resources/js/Components/AppSidebar.vue`
- `resources/js/Components/AppHeader.vue`

Padroes obrigatorios:

- Sidebar fixa em desktop, overlay no mobile.
- Header sticky com altura `h-16`.
- Main content com `p-6`, responsivo no mobile.
- Dark mode persistido em `localStorage`.
- Material Icons Outlined conforme design system.
- Inputs, botoes e badges sempre `rounded-pill`.
- Cards, tabelas e modais com `rounded-2xl`.

## 5. Modelo de Dominio

### 5.1 Entidades centrais

- `users`: usuarios internos.
- `roles` / `permissions`: perfis Administrador, Gestor Comercial, SDR e Closer.
- `companies`: empresa como entidade central.
- `contacts`: contatos vinculados a empresas.
- `opportunities`: oportunidades comerciais.
- `pipelines`: funis comerciais.
- `pipeline_stages`: etapas do funil.
- `opportunity_stage_movements`: historico de mudancas de etapa.
- `activities`: tarefas, reunioes, follow-ups e compromissos.
- `timeline_events`: historico centralizado da empresa.
- `communication_channels`: canais configuraveis de ligacao, e-mail e WhatsApp.
- `communication_channel_user`: usuarios autorizados por canal.
- `call_logs`: ligacoes via Twilio.
- `email_messages`: e-mails enviados e recebidos.
- `email_templates`: modelos de e-mail.
- `whatsapp_conversations`: conversas por contato/empresa.
- `whatsapp_messages`: mensagens da Evolution API.
- `whatsapp_templates`: modelos de mensagem.
- `automation_rules`: regras de automacao.
- `automation_executions`: logs de execucao.
- `attachments`: anexos e arquivos.
- `audit_logs`: auditoria de alteracoes relevantes.
- `settings`: configuracoes gerais.

### 5.2 Indices e performance no banco

Desde as primeiras migrations, criar indices para:

- `companies.cnpj` unico.
- `companies.responsible_user_id`.
- `companies.status`.
- `companies.lead_temperature`.
- `companies.last_interaction_at`.
- `contacts.company_id`.
- `contacts.email`.
- `contacts.phone`.
- `contacts.whatsapp`.
- `opportunities.company_id`.
- `opportunities.responsible_user_id`.
- `opportunities.pipeline_stage_id`.
- `opportunities.status`.
- `opportunities.expected_close_date`.
- `timeline_events.company_id`.
- `timeline_events.event_type`.
- `timeline_events.occurred_at`.
- `activities.responsible_user_id`.
- `activities.due_at`.
- `activities.status`.

Para busca geral, iniciar com MySQL bem indexado e colunas normalizadas para CNPJ/telefone/e-mail. Se o volume exigir busca mais avancada, adicionar Laravel Scout com Meilisearch em uma fase posterior.

## 6. Plano por Etapas

### Etapa 0 — Alinhamento e Preparacao

Objetivo: transformar PRD e design system em base tecnica executavel.

Status: concluida. Decisoes oficiais em:

- `docs/ETAPA-0-DECISOES.md`
- `docs/MVP-ESCOPO.md`
- `docs/AMBIENTE.md`

Entregas:

- Decisoes em aberto do PRD validadas e fechadas para o MVP.
- MVP definido com pipeline unico na interface e estrutura preparada para multiplos pipelines no futuro.
- Ownership entre SDR e Closer definido.
- Passagem de bastao para Closer definida na etapa `Reuniao agendada`.
- Canais de comunicacao definidos como camada propria para Ligacao, E-mail e WhatsApp.
- Ligacao definida como canal compartilhado do time no MVP, usando Twilio.
- E-mail definido como canal individual por usuario no MVP, usando SMTP.
- WhatsApp definido como canal individual por usuario no MVP, usando Evolution API.
- MySQL definido via host, fora do Docker.
- Docker Compose definido para aplicacao, Redis, filas, scheduler, Horizon e Mailpit.

Criterios de aceite:

- [x] Escopo do MVP congelado.
- [x] Variaveis de ambiente essenciais documentadas.
- [x] Ordem das entregas validada.
- [x] Pontos em aberto do PRD convertidos em decisoes executaveis.

### Etapa 1 — Bootstrap Laravel, Docker e Qualidade

Objetivo: criar a fundacao tecnica do projeto.

Status: implementada. Detalhes em `docs/ETAPA-1-SETUP.md`.

Entregas:

- [x] Criar projeto Laravel 12.
- [x] Instalar Vue 3, Inertia.js, TypeScript, Tailwind e Vite.
- [x] Configurar Docker Compose sem MySQL containerizado.
- [x] Configurar conexao MySQL via `host.docker.internal`.
- [x] Configurar Redis, filas, cache, sessoes e locks.
- [x] Configurar Horizon.
- [x] Configurar Mailpit para desenvolvimento.
- [x] Criar scripts padrao: `setup`, `dev`, `test`, `lint`, `format`.
- [x] Configurar Pint/PHP CS Fixer, ESLint, Prettier e TypeScript.
- [x] Configurar PHPUnit.
- [x] Criar seed inicial de usuario administrador.

Criterios de aceite:

- [x] Docker Compose valido sem MySQL containerizado.
- [x] Laravel configurado para acessar MySQL no host.
- [x] Redis configurado para cache, sessao e fila.
- [x] Vite compila assets Vue.
- [x] Teste inicial passa.

### Etapa 2 — Design System, Layout e Autenticacao

Status: implementada. Detalhes em `docs/ETAPA-2-DESIGN-AUTH.md`.

Objetivo: entregar a casca visual e o acesso seguro.

Entregas:

- [x] Implementar tokens CSS do JR Design System.
- [x] Criar componentes Vue `Jr*`.
- [x] Criar layout autenticado com sidebar, header e dark mode.
- [x] Criar layout guest para login.
- [x] Implementar login, logout, recuperacao de senha e protecao de rotas.
- [x] Criar menus principais: Dashboard, Empresas, Contatos, Pipeline, Oportunidades, Produtos, Atividades, Ligacoes, E-mails, WhatsApp, Canais, Automacoes, Relatorios e Configuracoes.
- [x] Criar perfis: Administrador, Gestor Comercial, SDR e Closer.
- [x] Criar policies iniciais.

Criterios de aceite:

- [x] Usuario consegue autenticar e navegar.
- [x] Layout segue visual do `jr-design-system.md`.
- [x] Dark mode funciona sem flash visual.
- [x] Menus respeitam perfil do usuario.

### Etapa 3 — CRM Core: Empresas e Contatos

Status: implementada. Detalhes em `docs/ETAPA-3-CRM-CORE.md`.

Objetivo: implementar o coracao B2B do produto.

Entregas:

- [x] Migrations, models, factories e seeders de empresas e contatos.
- [x] CRUD completo de empresas.
- [x] CRUD completo de contatos vinculados.
- [x] Validacao de CNPJ unico.
- [x] Normalizacao de CNPJ, telefone, WhatsApp e e-mail.
- [x] Busca por razao social, nome fantasia, CNPJ, telefone, e-mail e contato.
- [x] Filtros por status, origem, segmento, responsavel, temperatura e prioridade.
- [x] Tela principal da empresa com:
  - Cabecalho da empresa.
  - Dados comerciais da carteira.
  - Contatos.
  - Oportunidades.
  - Historico.
  - Atividades.
  - Comunicacoes.
- [x] Registro automatico de eventos relevantes no historico.

Criterios de aceite:

- [x] Toda empresa pode ter multiplos contatos.
- [x] Contato nunca existe sem empresa.
- [x] Duplicidade por CNPJ e bloqueada.
- [x] Busca responde rapidamente com indices.
- [x] A tela da empresa vira o ponto central do CRM.

### Etapa 4 — Pipeline e Oportunidades

Status: implementada. Detalhes em `docs/ETAPA-4-PIPELINE-OPORTUNIDADES.md`.

Objetivo: entregar o funil comercial operacional.

Entregas:

- [x] Migrations de pipeline, etapas e oportunidades.
- [x] Seeder das etapas iniciais:
  - Lead novo.
  - Primeiro contato.
  - Qualificacao.
  - Reuniao agendada.
  - Reuniao realizada.
  - Proposta enviada.
  - Negociacao.
  - Fechado ganho.
  - Fechado perdido.
- [x] CRUD de oportunidades.
- [x] Kanban em Vue com drag and drop.
- [x] Filtros por responsavel, etapa, origem, temperatura e previsao de fechamento.
- [x] Totais por etapa: quantidade e valor estimado.
- [x] Mudanca de etapa com evento automatico no historico.
- [x] Modal obrigatorio para motivo de perda.
- [x] Modal obrigatorio para dados minimos de ganho.
- [x] Registro de ultima movimentacao.
- [x] Cadastro de Produtos e Servicos.
- [x] Vinculo de multiplos produtos em oportunidades.
- [x] Campo livre de observacao sobre produtos mantido para contexto comercial.

Criterios de aceite:

- [x] Toda oportunidade pertence a uma empresa.
- [x] Movimentar card atualiza etapa e registra historico.
- [x] Pipeline funciona bem com muitas oportunidades via paginacao/carga por etapa.
- [x] Gestores podem gerenciar produtos e oportunidades podem selecionar produtos ativos.

### Etapa 4.1 — Produtos e Servicos

Status: implementada.

Objetivo: transformar produtos/servicos de interesse em uma entidade propria para padronizar negociacoes e relatorios futuros.

Entregas:

- [x] Migration, model, factory e seed inicial de produtos.
- [x] CRUD de produtos com nome, slug, categoria, descricao, preco base, status e ordenacao.
- [x] Menu lateral `Produtos` para administradores e gestores comerciais.
- [x] Vinculo N:N entre oportunidades e produtos.
- [x] Seletor de produtos no formulario de oportunidade.
- [x] Campo textual `products_interests` mantido como observacao livre.
- [x] Fechado ganho/perdido exige dados especificos.

### Etapa 5 — Historico Centralizado e Atividades

Status: implementada. Detalhes em `docs/ETAPA-5-HISTORICO-ATIVIDADES.md`.

Objetivo: garantir rastreabilidade e disciplina de follow-up.

Entregas:

- Implementar `timeline_events` como camada unica de historico.
- Criar servico de registro de eventos do dominio.
- Timeline da empresa com paginacao, filtros e busca.
- Criar atividades/tarefas/reunioes/follow-ups.
- Dashboard pessoal de tarefas do dia e vencidas.
- Conclusao, reagendamento e cancelamento de tarefas.
- Registro automatico de criacao/conclusao de tarefas no historico.
- Politicas de visibilidade por perfil.

Criterios de aceite:

- Toda acao importante aparece no historico da empresa.
- Timeline carrega paginada.
- Atividades vencidas ficam destacadas.
- SDR e Closer enxergam suas responsabilidades com clareza.

### Etapa 6 — Dashboard Inicial

Status: implementada. Detalhes em `docs/ETAPA-6-DASHBOARD-INICIAL.md`.

Objetivo: dar visibilidade operacional ao gestor, SDR e Closer.

Entregas:

- Dashboard por perfil.
- Cards gerais:
  - Total de empresas.
  - Leads novos.
  - Oportunidades abertas.
  - Oportunidades ganhas/perdidas.
  - Valor em negociacao.
  - Valor ganho.
  - Taxa de conversao.
  - Atividades vencidas.
- Indicadores de pipeline:
  - Quantidade por etapa.
  - Valor por etapa.
  - Oportunidades paradas.
- Indicadores de produtividade:
  - Ligacoes, e-mails, WhatsApps, reunioes e tarefas por usuario.
- Indicadores de carteira:
  - Valor total de inadimplencia.
  - Ticket medio.
  - Empresas com maior potencial.
- Cache dos calculos com invalidador por evento.

Criterios de aceite:

- Dashboard carrega rapido.
- Indicadores respeitam permissao/perfil.
- Dados agregados usam cache quando possivel.

### Etapa 7 — Comunicacoes: Canais, Twilio, E-mail e WhatsApp

Status: implementada. Detalhes em `docs/ETAPA-7-COMUNICACOES.md`.

Objetivo: integrar canais comerciais mantendo o historico da empresa como fonte central.

Evolucao aprovada apos o MVP base: criar uma secao propria de canais para administrar tipo, provedor, credenciais, status e usuarios com acesso. No MVP, os tipos sao Ligacao, WhatsApp e E-mail; os provedores iniciais sao Twilio, Evolution API e SMTP.

Entregas Canais:

- CRUD de canais de comunicacao.
- Tipo do canal: Ligacao, WhatsApp ou E-mail.
- Provedor tecnico por canal.
- Credenciais criptografadas e mascaradas.
- Usuarios autorizados por canal.
- Canal compartilhado para ligacao do time.
- Canais individuais por usuario para e-mail e WhatsApp.
- Registro do canal usado em cada comunicacao.

Entregas Twilio:

- Configuracao segura de credenciais no canal de Ligacao.
- Botao de ligacao em contato/empresa.
- Registro automatico de tentativa.
- Registro de status e duracao quando disponivel.
- Anotacao pos-chamada.
- Webhook para atualizacao de status.

Entregas E-mail:

- Envio via canal SMTP individual do usuario.
- Modelos basicos de e-mail.
- Envio manual para contatos.
- CC, CCO e anexos.
- Registro no historico da empresa.
- Associacao opcional a oportunidade.
- Estrutura preparada para recebimento e associacao automatica.

Entregas WhatsApp Evolution API:

- Configuracao de instancia por canal de WhatsApp individual do usuario.
- Envio manual de mensagens.
- Recebimento via webhook.
- Conversa por contato.
- Registro no historico da empresa.
- Modelos basicos de mensagem.
- Controle para diferenciar mensagem manual e automatica.

Criterios de aceite:

- Toda comunicacao fica vinculada a contato e empresa.
- Toda comunicacao registra o canal utilizado.
- Falhas de integracao geram logs claros.
- Jobs de comunicacao rodam em fila dedicada.
- Webhooks sao idempotentes.

### Etapa 8 — Automacoes Comerciais

Status: implementada. Detalhes em `docs/ETAPA-8-AUTOMACOES-COMERCIAIS.md`.

Objetivo: reduzir trabalho manual sem criar um construtor complexo no MVP.

Entregas:

- Modelo de automacao baseado em gatilho, condicoes e acoes.
- MVP com automacoes simples por eventos:
  - Oportunidade mudou de etapa.
  - Reuniao agendada.
  - Proposta sem resposta.
  - Lead sem interacao.
  - Tarefa vencida.
- Acoes iniciais:
  - Criar tarefa.
  - Enviar e-mail.
  - Enviar WhatsApp.
  - Notificar usuario interno.
  - Adicionar anotacao no historico.
- Logs em `automation_executions`.
- Locks Redis para evitar duplicidade.
- Tela de ativar/desativar automacoes.

Criterios de aceite:

- Toda automacao executada aparece no historico.
- Nao ha disparo duplicado.
- Gestor consegue ativar/desativar automacoes.
- Logs permitem auditar sucesso e falha.

### Etapa 9 — Relatorios, Exportacoes e Configuracoes

Objetivo: entregar gestao e operacao configuravel.

Status: implementada. Detalhes em `docs/ETAPA-9-RELATORIOS-CONFIGURACOES.md`.

Entregas:

- Configuracoes de:
  - [x] Dados da VOZ.
  - [x] Usuarios.
  - [x] Perfis.
  - [x] Pipeline.
  - [x] Etapas.
  - [x] Motivos de perda.
  - [x] Origens.
  - [x] Segmentos.
  - [x] Tipos de contato.
  - [x] Modelos de e-mail.
  - [x] Modelos de WhatsApp.
  - [x] Integracoes.
- Relatorios:
  - [x] Empresas cadastradas.
  - [x] Oportunidades por periodo.
  - [x] Ganhas/perdidas.
  - [x] Motivos de perda.
  - [x] Produtividade por usuario.
  - [x] Ligacoes.
  - [x] E-mails.
  - [x] WhatsApp.
  - [x] Reunioes.
  - [x] Forecast.
  - [x] Carteira potencial.
- [x] Exportacao CSV e Excel.
- [x] Exportacao PDF para relatorios prioritarios.
- [x] Jobs assincronos para exportacoes pesadas.

Criterios de aceite:

- [x] Relatorios filtram por periodo, usuario, etapa, origem, segmento e status.
- [x] Exportacoes grandes nao travam a interface.
- [x] Configuracoes alteram comportamento sem deploy.

### Etapa 10 — Hardening, Performance e Entrega

Objetivo: preparar o sistema para uso real.

Status: implementada. Detalhes em:

- `docs/ETAPA-10-HARDENING-PERFORMANCE-ENTREGA.md`
- `docs/ENTREGA-SETUP-DEPLOY.md`

Entregas:

- [x] Revisao de policies e autorizacoes.
- [x] Auditoria de alteracoes criticas.
- [x] Criptografia de credenciais de integracao.
- [x] Rate limiting por rota sensivel e integracao.
- [x] Otimizacao de queries com eager loading e indices.
- [x] Paginacao em listagens e timeline.
- [x] Cache de dashboard e configuracoes.
- [x] Testes de feature dos fluxos principais.
- [x] Testes de permissoes.
- [x] Testes dos webhooks.
- [x] Documentacao de setup Docker.
- [x] Documentacao de variaveis de ambiente.
- [x] Checklist de deploy.

Criterios de aceite:

- [x] Fluxos principais cobertos por testes.
- [x] Listagens e dashboard mantem boa performance com volume simulado.
- [x] Ambiente novo sobe seguindo a documentacao.
- [x] Integracoes externas falham de forma segura e rastreavel.

## 7. MVP Recomendado

O MVP deve conter:

- Autenticacao.
- Usuarios e permissoes basicas.
- Layout JR Design System adaptado para Vue.
- Empresas.
- Contatos.
- Oportunidades.
- Pipeline Kanban.
- Historico centralizado.
- Atividades e tarefas manuais.
- Dashboard inicial.
- Canais de comunicacao para Ligacao, WhatsApp e E-mail.
- Twilio para realizar ligacoes em canal compartilhado.
- WhatsApp via Evolution API em canais individuais por usuario.
- Envio de e-mail via SMTP em canais individuais por usuario.
- Modelos basicos de e-mail e WhatsApp.
- Automacoes simples por mudanca de etapa.

Fora do MVP inicial:

- Construtor visual avancado de automacoes.
- Relatorios muito sofisticados.
- Campos personalizados.
- Multiplos pipelines complexos.
- Integracao completa com agenda externa.
- IA para sugestao de proximos passos.
- Recebimento avancado e organizacao profunda de e-mails.

## 8. Ordem Recomendada de Implementacao

1. Docker, Laravel, Vue, Inertia, Redis e qualidade.
2. Design system em Vue e layout autenticado.
3. Auth, usuarios, perfis e policies.
4. Empresas e contatos.
5. Tela principal da empresa.
6. Pipeline e oportunidades.
7. Historico centralizado.
8. Atividades e tarefas.
9. Dashboard inicial.
10. Canais de comunicacao.
11. Integracao Twilio por canal de Ligacao.
12. Integracao WhatsApp Evolution API por canal de usuario.
13. Envio de e-mail SMTP por canal de usuario e modelos.
14. Automacoes simples.
15. Relatorios e exportacoes.
16. Hardening, testes e deploy.

## 9. Padroes de Codigo e Arquitetura

### 9.1 Backend

- Controllers finos.
- Regras em Actions/Services de dominio.
- Validacoes em Form Requests.
- Autorizacao em Policies.
- Eventos de dominio para alimentar timeline, dashboard e automacoes.
- Jobs para tudo que depender de integracao externa.
- DTOs ou objetos de dados para payloads complexos.
- Enums PHP para status, tipos e etapas fixas.
- Observers apenas quando forem simples; regras importantes devem ficar explicitas em actions.

### 9.2 Frontend

- Pages Inertia por modulo.
- Componentes `Jr*` para design system.
- Componentes de dominio por modulo.
- TypeScript para props compartilhadas.
- Composables para filtros, debounce, dark mode e formatacoes.
- Formularios com estado local e erros vindos do Laravel.
- Kanban com atualizacao otimista controlada e rollback em caso de erro.
- Evitar hardcode de cores fora dos tokens.

### 9.3 Nomenclatura de rotas

Padrao:

```txt
dashboard.index
companies.index
companies.show
contacts.index
pipeline.index
opportunities.index
activities.index
communications.calls.index
communications.emails.index
communications.whatsapp.index
channels.index
automations.index
reports.index
settings.index
```

## 10. Seguranca

Implementar desde o inicio:

- Senhas com hash padrao Laravel.
- CSRF em rotas web.
- Policies por recurso.
- Validacao de ownership por responsavel/time.
- Logs de auditoria para alteracoes importantes.
- Criptografia de tokens e credenciais dos canais Twilio/Evolution/SMTP.
- Mascaramento de dados sensiveis em logs.
- Rate limiting para webhooks e envio de comunicacoes.
- Webhooks com assinatura/token quando o provedor permitir.

## 11. Performance

Regras praticas:

- Nunca carregar historico completo sem paginacao.
- Listagens sempre paginadas.
- Dashboard com cache e invalidacao por eventos.
- Kanban carregado por etapa, com limite/paginacao incremental.
- Usar indices desde a primeira migration.
- Usar eager loading planejado.
- Jobs para comunicacoes e exportacoes.
- Evitar consultas agregadas em tempo real sem cache.
- Normalizar CNPJ, telefone e e-mail para busca.

## 12. Decisoes Fechadas da Etapa 0

As decisoes abaixo foram fechadas para o MVP e detalhadas em `docs/ETAPA-0-DECISOES.md`.

- Pipeline: unico no MVP; estrutura de banco preparada para multiplos.
- SDR: pode mover oportunidades ate `Reuniao agendada`.
- Closer: deve ser definido ao mover para `Reuniao agendada`.
- Canais: secao propria para Ligacao, WhatsApp e E-mail, com tipo, provedor, credenciais e usuarios autorizados.
- E-mail: canal SMTP individual por usuario no MVP.
- WhatsApp: canal Evolution API individual por usuario no MVP.
- Ligacao: canal Twilio compartilhado pelo time no MVP, com rastreio do usuario executor.
- Calendario externo: fora do MVP.
- Proposta comercial completa: fora do MVP; MVP registra proposta enviada.
- Campos personalizados: fora do MVP.
- Metas comerciais: fora do MVP.
- Comissoes: fora do MVP.
- Ambiente: Docker para aplicacao/servicos, MySQL via host.

## 13. Checklist de Pronto para Desenvolvimento

- [x] Fechar decisoes da Etapa 0.
- [x] Congelar escopo do MVP.
- [x] Documentar ambiente base.
- [x] Criar projeto Laravel 12.
- [x] Configurar Docker sem MySQL containerizado.
- [x] Configurar `.env.example` com MySQL via host.
- [x] Configurar Redis para cache, sessoes, filas e locks.
- [x] Instalar Vue 3, Inertia e TypeScript.
- [x] Implementar tokens do JR Design System.
- [x] Criar componentes Vue do design system.
- [x] Criar layout principal.
- [x] Implementar auth.
- [x] Criar seed de usuarios/perfis.
- [x] Criar migrations do CRM core.
- [x] Implementar empresas e contatos.
- [x] Implementar pipeline e oportunidades.
- [x] Implementar timeline centralizada.
- [x] Implementar atividades.
- [x] Implementar dashboard inicial.
- [x] Implementar integracoes tecnicas do MVP.
- [ ] Implementar gestao de canais de comunicacao.
- [x] Implementar automacoes simples.
- [x] Criar secao propria para modelos de e-mail e WhatsApp.
- [x] Implementar testes dos fluxos principais.
- [x] Documentar setup e deploy.
