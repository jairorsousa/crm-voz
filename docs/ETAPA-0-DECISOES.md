# Etapa 0 — Decisoes de Produto e Arquitetura

Status: concluida para inicio da Etapa 1.

Este documento fecha as decisoes em aberto do `PRD.md` e transforma a Etapa 0 do `PLAN.md` em orientacao executavel para desenvolvimento.

## 1. Decisoes Fechadas

### 1.1 Pipeline

Decisao: o MVP tera um pipeline comercial unico visivel para o usuario.

Implementacao tecnica:

- Criar tabelas `pipelines` e `pipeline_stages` desde o inicio.
- Seedar um pipeline padrao chamado `Pipeline Comercial`.
- Toda oportunidade deve pertencer a uma etapa.
- O modelo deve permitir multiplos pipelines no futuro, mas a interface do MVP exibira apenas o pipeline padrao.

Motivo:

- Reduz complexidade de operacao e configuracao inicial.
- Mantem a arquitetura preparada para evolucao sem refatoracao pesada.

### 1.2 Limites de atuacao do SDR

Decisao: SDR atua ate a etapa `Reuniao agendada`.

Regras:

- SDR pode criar empresas, contatos e oportunidades.
- SDR pode mover oportunidades entre:
  - `Lead novo`
  - `Primeiro contato`
  - `Qualificacao`
  - `Reuniao agendada`
- SDR nao pode mover diretamente para:
  - `Reuniao realizada`
  - `Proposta enviada`
  - `Negociacao`
  - `Fechado ganho`
  - `Fechado perdido`
- Gestor Comercial e Administrador podem mover qualquer etapa.
- Closer atua da etapa `Reuniao agendada` em diante.

Motivo:

- Reflete a separacao operacional do PRD.
- Melhora medicao de produtividade de SDR e Closer.
- Evita que oportunidades avancem para fechamento sem passagem de bastao.

### 1.3 Passagem para Closer

Decisao: ao mover para `Reuniao agendada`, a oportunidade deve ter um Closer definido.

Regra MVP:

- Se a oportunidade ja tiver `closer_id`, manter o responsavel.
- Se nao tiver `closer_id`, exigir selecao do Closer no modal de movimentacao.
- Registrar no historico da empresa:
  - etapa anterior;
  - nova etapa;
  - SDR que fez a mudanca;
  - Closer atribuido;
  - data/hora da passagem.
- Criar automaticamente uma tarefa para o Closer revisar a empresa antes da reuniao.

Evolucao futura:

- Distribuicao automatica por round-robin.
- Distribuicao por carteira, segmento, potencial ou disponibilidade.

### 1.4 Canais de comunicação

Decisao: o CRM tera uma secao propria de canais de comunicacao para administrar Ligacao, WhatsApp e E-mail.

Implementacao:

- Canal do tipo Ligacao usa Twilio como provedor inicial.
- Canal do tipo WhatsApp usa Evolution API como provedor inicial.
- Canal do tipo E-mail usa SMTP como provedor inicial.
- Cada canal guarda tipo, provedor, nome, credenciais, status e usuarios autorizados.
- Credenciais devem ser criptografadas e mascaradas na interface.
- Toda comunicacao deve registrar o canal utilizado.

Motivo:

- Evita misturar configuracao tecnica com uso comercial diario.
- Permite novos provedores no futuro sem redesenhar o CRM.
- Permite controlar quais usuarios podem usar cada origem de comunicacao.

### 1.5 Envio de e-mail

Decisao: o MVP usara canais SMTP individuais por usuario.

Implementacao:

- Cada usuario pode ter seu proprio canal de e-mail.
- O usuario so envia por canais ativos aos quais tiver acesso.
- Campo `sent_by_user_id` identifica o usuario interno.
- Campo `communication_channel_id` identifica o canal usado.
- Preparar o modelo para outros provedores de e-mail no futuro.

Motivo:

- Mantem remetente individual quando necessario para relacionamento comercial.
- Preserva rastreabilidade por usuario e por canal.
- Evita prender o produto a um SMTP unico.

### 1.6 Recebimento de e-mail

Decisao: recebimento completo de e-mail fica fora do MVP operacional inicial.

No MVP:

- Envio manual de e-mails.
- Modelos basicos.
- Registro dos e-mails enviados no historico.
- Estrutura de banco preparada para e-mails recebidos.

Fase 2:

- Sincronizacao de caixa.
- Associacao automatica por remetente/destinatario.
- Resposta dentro do CRM.

### 1.7 WhatsApp

Decisao: o MVP usara canais Evolution API individuais por usuario.

Implementacao:

- Cada usuario pode ter seu proprio canal de WhatsApp.
- O usuario so envia e responde por canais ativos aos quais tiver acesso.
- Usuario logado fica registrado como `sent_by_user_id`.
- Campo `communication_channel_id` identifica o canal usado.
- Mensagens manuais e automaticas devem ser diferenciadas.
- Toda mensagem deve estar vinculada a contato e empresa.
- Gestores e administradores podem gerenciar os canais conforme permissao.

Motivo:

- Reflete o uso operacional em que cada vendedor pode ter sua propria origem de WhatsApp.
- Evita confusao em conversas quando varios usuarios respondem pelo mesmo numero.
- Mantem rastreabilidade por usuario e por canal dentro do CRM.

### 1.8 Ligacao

Decisao: o MVP usara um canal Twilio compartilhado por todo o time comercial.

Implementacao:

- Um canal de Ligacao compartilhado.
- Todos os usuarios autorizados podem usar o canal.
- Campo `communication_channel_id` identifica o canal usado.
- Usuario executor fica registrado na ligacao.

Motivo:

- Ligacao pode iniciar com uma origem comum sem impedir auditoria por usuario.
- Mantem arquitetura preparada para multiplos canais de telefonia no futuro.

### 1.9 Calendario externo

Decisao: integracao com calendario externo fica fora do MVP.

No MVP:

- Reunioes serao atividades internas do CRM.
- A agenda interna tera data, hora, responsavel, empresa, contato e oportunidade.

Fase 2:

- Google Calendar ou Microsoft Outlook.

### 1.10 Proposta comercial

Decisao: no MVP, o sistema registra proposta enviada, mas nao gera proposta comercial completa.

No MVP:

- Campo/atividade de proposta enviada.
- Anexo opcional.
- Valor estimado e observacoes na oportunidade.
- Registro no historico.

Fase 2:

- Geracao de proposta.
- Templates.
- Aprovacoes.
- Assinatura digital.

### 1.11 Campos personalizados

Decisao: fora do MVP.

No MVP:

- Usar campos comerciais definidos no PRD.
- Permitir configuracoes por listas controladas: origem, segmento, tipo de contato, motivo de perda e etapas.

Fase 2:

- Campos personalizados por empresa/oportunidade.

### 1.12 Metas comerciais

Decisao: fora do MVP.

No MVP:

- Dashboard mostra produtividade e resultados.
- Nao havera cadastro formal de metas.

Fase 2:

- Metas por usuario, periodo e tipo de indicador.

### 1.13 Comissoes

Decisao: fora do MVP.

Motivo:

- Nao esta entre funcionalidades essenciais do PRD.
- Pode exigir regras financeiras/comerciais especificas ainda nao definidas.

## 2. Escopo Congelado do MVP

O MVP sera considerado fechado com:

- Autenticacao.
- Usuarios e perfis basicos.
- Layout JR Design System em Vue 3.
- Empresas.
- Contatos.
- Oportunidades.
- Pipeline Kanban unico.
- Historico centralizado da empresa.
- Atividades e tarefas internas.
- Dashboard inicial por perfil.
- Canais de comunicacao para Ligacao, WhatsApp e E-mail.
- Twilio para chamadas realizadas em canal compartilhado.
- WhatsApp via Evolution API em canais individuais por usuario.
- Envio de e-mails via canais SMTP individuais por usuario.
- Modelos basicos de e-mail e WhatsApp.
- Automacoes simples por eventos do pipeline.

## 3. Premissas Tecnicas para Etapa 1

- Laravel 12 sera instalado na raiz do projeto.
- Vue 3 sera usado dentro do Laravel via Inertia.js.
- TypeScript sera usado no frontend.
- MySQL sera externo, acessado por `host.docker.internal`.
- Redis ficara em Docker.
- Filas, cache, sessoes, locks e rate limiting usarao Redis.
- Mailpit sera usado apenas em desenvolvimento.
- Docker Compose nao deve subir MySQL.

## 4. Criterios de Aceite da Etapa 0

- [x] Escopo do MVP congelado.
- [x] Pipeline unico definido para o MVP.
- [x] Modelo preparado para multiplos pipelines no futuro.
- [x] Responsabilidades de SDR e Closer definidas.
- [x] Passagem de bastao definida.
- [x] Estrategia inicial de canais definida.
- [x] Estrategia inicial de e-mail definida por canal individual.
- [x] Estrategia inicial de WhatsApp definida por canal individual.
- [x] Estrategia inicial de ligacao definida por canal compartilhado.
- [x] Calendario externo decidido como fase 2.
- [x] Proposta comercial completa decidida como fase 2.
- [x] Campos personalizados, metas e comissoes decididos como fase 2.
- [x] Ambiente base da Etapa 1 definido.

## 5. Riscos Conhecidos

- Credenciais reais de Twilio, Evolution API e SMTP ainda precisam ser fornecidas em ambiente seguro.
- MySQL via host depende de configuracao local do desenvolvedor.
- Canais individuais de WhatsApp e e-mail exigem processo claro de onboarding por usuario.
- Recebimento completo de e-mails fora do MVP pode limitar visao unificada no primeiro uso.

## 6. Proxima Etapa

Iniciar Etapa 1:

1. Criar projeto Laravel 12.
2. Configurar Docker Compose.
3. Configurar Redis.
4. Configurar MySQL via host.
5. Instalar Vue 3, Inertia, TypeScript, Tailwind e Vite.
6. Criar setup inicial de qualidade, testes e scripts.
