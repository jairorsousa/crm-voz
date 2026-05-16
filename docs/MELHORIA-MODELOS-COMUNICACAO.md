# Melhoria — Modelos de Comunicacao

## Contexto

Os modelos de e-mail e WhatsApp estavam dentro de Configuracoes. Isso funcionava tecnicamente, mas misturava uma rotina operacional com ajustes administrativos.

## Decisao

Foi criada uma secao propria para modelos em `/modelos`, acessivel pelo menu lateral como **Modelos** para Administrador e Gestor Comercial.

## Entrega

- Listagem paginada de modelos.
- Filtros por busca, canal e status.
- Cards de resumo:
  - Total.
  - E-mail.
  - WhatsApp.
  - Ativos.
- Criacao de modelos de e-mail e WhatsApp.
- Edicao de nome, canal, assunto, mensagem e status.
- Ativar/pausar modelo rapidamente.
- Remocao de modelo.
- Auditoria das acoes:
  - `communication.template.created`
  - `communication.template.updated`
  - `communication.template.toggled`
  - `communication.template.deleted`

## Regras

- E-mail exige assunto.
- WhatsApp nao usa assunto; o campo e limpo automaticamente.
- O nome do modelo deve ser unico por canal.
- Apenas Administrador e Gestor Comercial podem gerenciar modelos.
- SDR e Closer continuam usando modelos ativos nas telas de envio, mas nao gerenciam o catalogo.

## Arquivos principais

- `app/Http/Controllers/CRM/CommunicationTemplateController.php`
- `resources/js/Pages/Templates/Index.vue`
- `resources/js/Pages/Templates/Form.vue`
- `tests/Feature/CRM/CommunicationTemplateTest.php`
