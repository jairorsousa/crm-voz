# Etapa 3 — CRM Core: Empresas e Contatos

Status: implementada.

## Entregas

- Migrations de `companies`, `contacts` e `timeline_events`.
- Models `Company`, `Contact` e `TimelineEvent`.
- Factories de empresas e contatos.
- Seeder atualizado para criar empresas e contatos de exemplo quando a base estiver vazia.
- CRUD completo de empresas.
- CRUD completo de contatos.
- Contatos sempre vinculados a empresas por chave estrangeira obrigatoria.
- CNPJ normalizado para 14 digitos, validado por digitos verificadores e unico no banco.
- Telefone, WhatsApp e e-mail normalizados nos FormRequests.
- Busca de empresas por razao social, nome fantasia, CNPJ, telefone, e-mail e dados de contato.
- Filtros de empresas por status, origem, segmento, responsavel, temperatura e prioridade.
- Busca/filtro de contatos por texto, empresa e tipo.
- Tela central da empresa com cabecalho, carteira, contatos, historico recente, oportunidades, atividades e comunicacoes.
- Eventos automaticos no historico para criacao/alteracao de empresa e criacao/alteracao/remocao de contato.
- Testes feature cobrindo criacao, normalizacao, CNPJ unico, contato principal, busca por contato e obrigatoriedade de empresa.

## Rotas

- `companies.index`
- `companies.create`
- `companies.store`
- `companies.show`
- `companies.edit`
- `companies.update`
- `companies.destroy`
- `contacts.index`
- `contacts.create`
- `contacts.store`
- `contacts.edit`
- `contacts.update`
- `contacts.destroy`

## Validacao

```bash
composer lint
composer test
npm run lint
npm run typecheck
npm run build
php artisan route:list --except-vendor
```

Todos os comandos acima foram executados com sucesso.

## Observacao

As secoes de oportunidades, atividades e comunicacoes ja aparecem na tela da empresa como pontos de integracao, mas a implementacao funcional delas fica nas proximas etapas do plano.
