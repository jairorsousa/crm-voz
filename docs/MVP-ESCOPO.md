# MVP — Escopo Congelado

Este documento define o escopo do primeiro produto utilizavel do VOZ CRM.

## 1. Objetivo do MVP

Entregar uma plataforma comercial B2B capaz de centralizar empresas, contatos, oportunidades, pipeline, historico, atividades e comunicacoes essenciais, permitindo que SDRs, Closers e Gestores tenham uma operacao comercial rastreavel e organizada.

## 2. Personas do MVP

### Administrador

- Configura usuarios, perfis, pipeline, listas e integracoes.
- Tem acesso total.

### Gestor Comercial

- Acompanha dashboard e pipeline do time.
- Redistribui responsaveis.
- Acessa historico completo.
- Configura automacoes simples.

### SDR

- Cadastra empresas e contatos.
- Qualifica leads.
- Agenda reunioes.
- Move oportunidades ate `Reuniao agendada`.
- Usa ligacao, e-mail e WhatsApp.

### Closer

- Assume oportunidades a partir de `Reuniao agendada`.
- Conduz reuniao, proposta e negociacao.
- Fecha oportunidade como ganha ou perdida.

## 3. Modulos Dentro do MVP

### Autenticacao e Usuarios

- Login e logout.
- Recuperacao de senha.
- Perfis: Administrador, Gestor Comercial, SDR e Closer.
- Permissoes basicas por policy.

### Empresas

- Cadastro completo conforme PRD.
- Busca por razao social, nome fantasia, CNPJ, telefone, e-mail e contato.
- Filtros por status, origem, segmento, responsavel, temperatura e prioridade.
- Validacao de CNPJ unico.

### Contatos

- Contatos sempre vinculados a empresa.
- Contato principal.
- Tipo de contato.
- Preferencias basicas de comunicacao.

### Oportunidades e Pipeline

- Pipeline unico.
- Kanban com drag and drop.
- Etapas padrao do PRD.
- Totais por etapa.
- Regras de permissao por perfil.
- Motivo obrigatorio em `Fechado perdido`.
- Dados minimos obrigatorios em `Fechado ganho`.

### Historico Centralizado

- Timeline por empresa.
- Eventos automaticos de:
  - criacao/alteracao de empresa;
  - criacao/alteracao de contato;
  - mudanca de etapa;
  - tarefa criada/concluida;
  - ligacao;
  - e-mail enviado;
  - WhatsApp enviado/recebido;
  - automacao executada.
- Paginacao e filtros.

### Atividades

- Tarefas manuais e automaticas.
- Reunioes internas do CRM.
- Follow-ups.
- Responsavel, data, hora, prioridade e status.
- Destaque para vencidas.

### Dashboard

- Visao por perfil.
- Indicadores gerais.
- Indicadores de pipeline.
- Indicadores de produtividade.
- Indicadores de carteira prospectada.
- Cache de agregados.

### Canais de comunicacao

- Secao propria para cadastrar canais.
- Tipos iniciais: Ligacao, WhatsApp e E-mail.
- Provedores iniciais: Twilio, Evolution API e SMTP.
- Credenciais criptografadas.
- Usuarios autorizados por canal.
- Registro do canal utilizado em cada comunicacao.

### Ligacoes Twilio

- Canal compartilhado para o time comercial.
- Realizacao de chamadas.
- Registro de tentativa.
- Status e duracao quando disponiveis.
- Anotacao pos-chamada.
- Historico por empresa e usuario.

### E-mails

- Envio manual.
- Canal SMTP individual por usuario.
- CC, CCO e anexos.
- Modelos basicos.
- Registro no historico.

### WhatsApp Evolution API

- Canal Evolution API individual por usuario.
- Envio manual.
- Recebimento via webhook.
- Modelos basicos.
- Registro no historico.
- Identificacao de usuario interno.

### Automacoes Simples

- Baseadas em eventos.
- Gatilhos iniciais:
  - oportunidade mudou de etapa;
  - reuniao agendada;
  - proposta sem resposta;
  - lead sem interacao;
  - tarefa vencida.
- Acoes iniciais:
  - criar tarefa;
  - enviar e-mail;
  - enviar WhatsApp;
  - notificar usuario;
  - registrar no historico.

## 4. Fora do MVP

- Multiplos pipelines na interface.
- Construtor visual de automacoes.
- Recebimento avancado de e-mails.
- Integracao com calendario externo.
- Gerador completo de propostas.
- Campos personalizados.
- Metas comerciais.
- Comissoes.
- IA para proximos passos.
- Forecast avancado.
- SLA de follow-up.

## 5. Definicao de Pronto do MVP

O MVP estara pronto quando:

- Um usuario conseguir cadastrar empresa e contatos.
- Um SDR conseguir criar e qualificar oportunidade.
- A oportunidade aparecer no Kanban.
- A movimentacao de etapa gerar historico.
- A passagem para Closer estiver controlada.
- O Closer conseguir fechar como ganho/perdido.
- Atividades puderem ser criadas, vencidas e concluidas.
- Ligacoes, e-mails e WhatsApp gerarem eventos no historico.
- O gestor visualizar indicadores essenciais.
- Automacoes simples rodarem em fila com logs.
- Permissoes basicas impedirem acoes indevidas por perfil.
