# Etapa 2 — Design System, Layout e Autenticacao

Status: implementada.

## Entregas

- Tokens do JR Design System aplicados em `resources/css/app.css` e `tailwind.config.js`.
- Fonte Reddit Sans e Material Icons Outlined carregados no layout raiz.
- Dark mode com `data-theme="dark"` e persistencia em `localStorage`.
- Componentes Vue JR criados em `resources/js/Components/Jr/`.
- Layout autenticado criado com sidebar fixa no desktop e overlay no mobile.
- Header sticky com menu de usuario, logout, perfil e troca de tema.
- Layout guest modernizado para login, registro e recuperacao de senha.
- Telas de autenticacao atualizadas para usar componentes JR.
- Rotas protegidas criadas para os modulos principais do CRM.
- Perfis iniciais criados: Administrador, Gestor Comercial, SDR e Closer.
- Policy inicial criada para filtrar acesso a menus e rotas por perfil.
- Seeder atualizado para criar usuarios iniciais dos quatro perfis.

## Componentes JR

- `JrButton`
- `JrInput`
- `JrBadge`
- `JrAlert`
- `JrCard`
- `JrTable`
- `JrModal`
- `JrDropdown`
- `JrAvatar`
- `JrEmptyState`
- `JrIconBox`
- `JrPageHeader`
- `JrStatCard`

## Rotas Criadas

- `/dashboard`
- `/empresas`
- `/contatos`
- `/pipeline`
- `/oportunidades`
- `/atividades`
- `/ligacoes`
- `/emails`
- `/whatsapp`
- `/automacoes`
- `/relatorios`
- `/configuracoes`

## Perfis

- Administrador: acesso completo.
- Gestor Comercial: acesso operacional e gerencial, incluindo automacoes e relatorios.
- SDR: acesso operacional comercial.
- Closer: acesso operacional comercial.

## Validacao

```bash
npm run lint
npm run typecheck
npm run build
composer lint
composer test
```

Todos os comandos acima foram executados com sucesso.
