# PRD — VOZ CRM

## 1. Visão Geral

O **VOZ CRM** será uma plataforma de gestão comercial B2B desenvolvida para centralizar o relacionamento com empresas, organizar o processo de vendas, registrar todo o histórico de comunicação e acompanhar oportunidades comerciais em um pipeline visual no estilo Kanban.

O sistema será utilizado pelo time comercial da VOZ, composto por **Gestor Comercial**, **SDRs** e **Closers**, com foco em prospecção, relacionamento, agendamento de reuniões, acompanhamento de oportunidades e fechamento de negócios.

Como o negócio da VOZ é exclusivamente B2B, a estrutura principal do CRM será baseada no cadastro de **empresas**. Todo contato, ligação, e-mail, conversa de WhatsApp, tarefa, reunião, proposta e oportunidade deverá estar vinculado a uma empresa.

---

## 2. Objetivo do Produto

Criar uma ferramenta comercial centralizada para permitir que a VOZ tenha controle completo sobre sua operação de vendas B2B, desde a prospecção até o fechamento, reunindo em um único lugar:

- Cadastro de empresas e contatos vinculados;
- Histórico completo da empresa;
- Pipeline de negócios;
- Canais de comunicação configuráveis para ligação, e-mail e WhatsApp;
- Ligações usando Twilio como provedor inicial;
- Gestão de e-mails enviados e recebidos usando SMTP como provedor inicial;
- Gestão de conversas via WhatsApp usando Evolution API como provedor inicial;
- Automações comerciais;
- Gestão de atividades, tarefas e follow-ups;
- Dashboards e indicadores de performance comercial.

---

## 3. Público-Alvo Interno

### 3.1 Gestor Comercial

Responsável por acompanhar a operação comercial, visualizar indicadores, distribuir leads, acompanhar performance do time, revisar oportunidades e tomar decisões estratégicas.

Principais necessidades:

- Ter visão geral do funil comercial;
- Acompanhar produtividade de SDRs e Closers;
- Ver indicadores de conversão;
- Identificar gargalos no pipeline;
- Acompanhar oportunidades de maior valor;
- Auditar histórico de atendimento por empresa;
- Criar e ajustar fluxos de automação.

### 3.2 SDR

Responsável pela prospecção, primeiro contato, qualificação da empresa e agendamento de reuniões.

Principais necessidades:

- Cadastrar empresas e contatos;
- Fazer ligações;
- Enviar e-mails;
- Conversar via WhatsApp;
- Registrar interações;
- Qualificar leads;
- Agendar reuniões;
- Mover empresas/oportunidades no pipeline.

### 3.3 Closer

Responsável por conduzir a negociação após a qualificação, apresentar proposta, negociar condições e fechar contrato.

Principais necessidades:

- Visualizar histórico completo da empresa;
- Entender dores, perfil e potencial da carteira;
- Acompanhar reuniões e propostas;
- Registrar negociações;
- Enviar e-mails e mensagens;
- Controlar etapas de fechamento;
- Atualizar status da oportunidade.

---

## 4. Princípios do Produto

O VOZ CRM deve seguir alguns princípios essenciais:

1. **Empresa como centro do relacionamento**  
   Todo histórico deve estar concentrado na empresa, mesmo quando a comunicação ocorrer com contatos diferentes.

2. **Histórico unificado**  
   Ligações, e-mails, WhatsApp, tarefas, reuniões, propostas e alterações no pipeline devem aparecer em uma linha do tempo única.

3. **Foco em vendas B2B**  
   O CRM não deve ser genérico para pessoa física. A estrutura deve considerar empresas, decisores, influenciadores, carteira, ticket médio e inadimplência.

4. **Produtividade comercial**  
   O sistema deve reduzir trabalho manual, automatizar lembretes e facilitar follow-ups.

5. **Gestão orientada a dados**  
   O gestor comercial deve conseguir tomar decisões com base em indicadores claros.

6. **Comunicação integrada**  
   O vendedor deve conseguir ligar, enviar e-mail e conversar por WhatsApp sem perder o histórico da empresa.

7. **Canais extensíveis**  
   O CRM deve separar o canal usado pelo vendedor da tecnologia de integração por trás dele, permitindo trocar ou adicionar provedores no futuro sem redesenhar a experiência comercial.

---

## 5. Estrutura Principal do CRM

A estrutura principal do sistema será composta por:

- Empresas;
- Contatos vinculados à empresa;
- Oportunidades/Negócios;
- Pipeline comercial;
- Atividades e tarefas;
- Ligações;
- E-mails;
- WhatsApp;
- Canais de comunicação;
- Automações;
- Dashboard;
- Usuários e permissões;
- Histórico centralizado.

---

## 6. Módulo de Empresas

### 6.1 Objetivo

Permitir o cadastro completo das empresas prospectadas, em negociação, clientes ou perdidas, centralizando todas as informações comerciais e todo o histórico de relacionamento.

### 6.2 Campos sugeridos para cadastro da empresa

#### Dados básicos

- Razão social;
- Nome fantasia;
- CNPJ;
- Segmento;
- Site;
- Telefone principal;
- E-mail principal;
- WhatsApp principal;
- Cidade;
- Estado;
- Endereço;
- Status da empresa;
- Origem do lead;
- Responsável interno;
- Data de cadastro;
- Data da última interação.

#### Dados comerciais da carteira

- Ticket médio da cobrança;
- Quantidade de clientes inadimplentes;
- Valor total da inadimplência;
- Quantidade aproximada de alunos/clientes;
- Sistema utilizado pela empresa;
- Possui equipe interna de cobrança?;
- Possui integração com ERP?;
- Observações sobre a carteira.

#### Classificação comercial

- Tipo de empresa;
- Porte da empresa;
- Potencial comercial;
- Temperatura do lead;
- Grau de prioridade;
- Perfil de dor;
- Probabilidade de fechamento.

### 6.3 Status da empresa

Sugestão inicial de status:

- Lead novo;
- Em prospecção;
- Qualificado;
- Em negociação;
- Cliente ativo;
- Cliente perdido;
- Sem fit;
- Inativo.

### 6.4 Regras de negócio

- Toda empresa pode ter um ou mais contatos vinculados;
- Toda comunicação deve ser vinculada à empresa;
- Uma empresa pode ter uma ou mais oportunidades comerciais;
- O histórico da empresa deve mostrar interações feitas com qualquer contato vinculado a ela;
- O sistema deve evitar duplicidade de empresas por CNPJ;
- O sistema deve permitir busca por razão social, nome fantasia, CNPJ, telefone, e-mail ou contato.

---

## 7. Módulo de Contatos

### 7.1 Objetivo

Permitir o cadastro de pessoas vinculadas a uma empresa, como decisores, influenciadores, usuários, financeiro, coordenadores ou responsáveis operacionais.

### 7.2 Campos sugeridos

- Nome;
- Cargo;
- Departamento;
- E-mail;
- Telefone;
- WhatsApp;
- LinkedIn;
- Tipo de contato;
- Principal decisor?;
- Recebe comunicações automáticas?;
- Observações;
- Empresa vinculada.

### 7.3 Tipos de contato

- Decisor;
- Influenciador;
- Financeiro;
- Operacional;
- TI;
- Jurídico;
- Outro.

### 7.4 Regras de negócio

- Um contato sempre deve estar vinculado a uma empresa;
- Uma empresa pode ter múltiplos contatos;
- O CRM deve permitir definir um contato principal;
- O histórico individual do contato deve também aparecer no histórico geral da empresa;
- Ao enviar e-mail ou WhatsApp, o usuário deve escolher o contato destinatário, mas o registro deve ficar vinculado à empresa.

---

## 8. Módulo de Pipeline de Negócios

### 8.1 Objetivo

Permitir o acompanhamento visual das oportunidades comerciais em um quadro no estilo Kanban, com etapas configuráveis e movimentação por arrastar e soltar.

### 8.2 Estrutura do pipeline

Cada oportunidade deverá estar vinculada a uma empresa e poderá conter:

- Nome da oportunidade;
- Empresa vinculada;
- Contato principal;
- Responsável;
- Valor estimado;
- Probabilidade de fechamento;
- Previsão de fechamento;
- Etapa atual;
- Origem;
- Status;
- Produtos/serviços de interesse;
- Observações;
- Data de criação;
- Data da última movimentação.

### 8.3 Etapas sugeridas do pipeline

Sugestão inicial:

1. Lead novo;
2. Primeiro contato;
3. Qualificação;
4. Reunião agendada;
5. Reunião realizada;
6. Proposta enviada;
7. Negociação;
8. Fechado ganho;
9. Fechado perdido.

### 8.4 Funcionalidades do pipeline

- Visualização Kanban;
- Movimentação de cards por arrastar e soltar;
- Filtro por responsável;
- Filtro por etapa;
- Filtro por origem;
- Filtro por temperatura do lead;
- Filtro por previsão de fechamento;
- Visualização de valor total por etapa;
- Visualização da quantidade de oportunidades por etapa;
- Registro automático da mudança de etapa no histórico da empresa;
- Possibilidade de configurar automações por mudança de etapa.

### 8.5 Regras de negócio

- Toda oportunidade deve estar vinculada a uma empresa;
- Uma empresa pode ter mais de uma oportunidade;
- A mudança de etapa deve gerar evento no histórico;
- Algumas etapas podem exigir campos obrigatórios;
- Ao mover para “Fechado perdido”, o sistema deve solicitar motivo da perda;
- Ao mover para “Fechado ganho”, o sistema deve solicitar dados mínimos de fechamento.

---

## 9. Módulo de Histórico Centralizado da Empresa

### 9.1 Objetivo

Criar uma linha do tempo única dentro do cadastro da empresa, reunindo tudo que aconteceu com aquela empresa, independentemente do contato envolvido.

### 9.2 Eventos que devem aparecer no histórico

- Ligações realizadas;
- Tentativas de ligação;
- E-mails enviados;
- E-mails recebidos;
- Mensagens de WhatsApp enviadas;
- Mensagens de WhatsApp recebidas;
- Tarefas criadas;
- Tarefas concluídas;
- Reuniões agendadas;
- Reuniões realizadas;
- Mudanças de etapa no pipeline;
- Propostas enviadas;
- Anotações manuais;
- Alterações importantes no cadastro;
- Execução de automações.

### 9.3 Características da linha do tempo

- Ordenação cronológica;
- Filtros por tipo de interação;
- Filtro por usuário responsável;
- Filtro por contato;
- Busca no histórico;
- Destaque para interações importantes;
- Registro automático sempre que possível.

---

## 10. Módulo de Ligações via Twilio

### 10.1 Objetivo

Permitir que o time comercial faça ligações diretamente pelo CRM por meio de um canal de comunicação do tipo **Ligação**, utilizando a Twilio como provedor inicial de telefonia.

### 10.2 Escopo inicial

O CRM será utilizado apenas para **realizar chamadas**. O sistema não receberá chamadas dentro da plataforma.

### 10.3 Funcionalidades

- Botão para ligar para contatos cadastrados;
- Registro automático da tentativa de ligação;
- Registro da duração da chamada quando disponível;
- Status da ligação;
- Anotação pós-chamada;
- Vinculação da chamada ao contato e à empresa;
- Histórico de ligações por empresa;
- Histórico de ligações por vendedor;
- Indicador de quantidade de ligações realizadas.

### 10.4 Status sugeridos da ligação

- Realizada;
- Não atendida;
- Ocupado;
- Falhou;
- Cancelada;
- Em andamento.

### 10.5 Regras de negócio

- Toda ligação deve estar vinculada a um contato;
- Toda ligação deve aparecer no histórico da empresa;
- Toda ligação deve registrar qual canal de comunicação foi utilizado;
- O CRM não precisa receber chamadas na primeira versão;
- Após a ligação, o vendedor deve poder registrar uma observação;
- O sistema deve permitir identificar qual usuário realizou a chamada.

---

## 11. Módulo de E-mails

### 11.1 Objetivo

Permitir que o time comercial envie e acompanhe e-mails diretamente pelo CRM por meio de canais de comunicação do tipo **E-mail**, além de visualizar e-mails recebidos vinculados à empresa e aos contatos.

### 11.2 Funcionalidades de envio

- Enviar e-mail para contatos cadastrados;
- Enviar e-mail com cópia e cópia oculta;
- Usar modelos de e-mail;
- Anexar arquivos;
- Registrar e-mail enviado no histórico da empresa;
- Associar e-mail à oportunidade comercial;
- Enviar e-mail manualmente;
- Enviar e-mail automaticamente por automações.

### 11.3 Funcionalidades de recebimento

- Visualizar e-mails recebidos;
- Associar e-mails recebidos ao contato e à empresa;
- Exibir respostas dentro da linha do tempo;
- Permitir busca por assunto, remetente ou conteúdo;
- Permitir responder e-mails pelo CRM.

### 11.4 Modelos de e-mail

O sistema deve permitir criar modelos para:

- Primeiro contato;
- Confirmação de reunião;
- Lembrete de reunião;
- Envio de apresentação institucional;
- Envio de proposta;
- Follow-up pós-reunião;
- Follow-up de proposta;
- Reativação de lead;
- Perda de oportunidade.

### 11.5 Regras de negócio

- Todo e-mail enviado deve estar vinculado a uma empresa;
- Todo e-mail enviado deve registrar qual canal de comunicação foi utilizado;
- Um e-mail pode estar vinculado também a uma oportunidade;
- E-mails recebidos devem ser associados automaticamente quando possível, com base no e-mail do contato;
- Quando não for possível associar automaticamente, o sistema deve permitir associação manual;
- Todos os e-mails relevantes devem aparecer no histórico da empresa.

---

## 12. Módulo de WhatsApp via Evolution API

### 12.1 Objetivo

Permitir que o time comercial gerencie conversas de WhatsApp dentro do CRM por meio de canais de comunicação do tipo **WhatsApp**, usando a Evolution API como provedor inicial de integração.

### 12.2 Funcionalidades

- Enviar mensagens de WhatsApp para contatos cadastrados;
- Receber e visualizar mensagens no CRM;
- Exibir conversas por contato;
- Exibir conversas dentro do histórico da empresa;
- Enviar mensagens manuais;
- Enviar mensagens automáticas por fluxos;
- Usar modelos de mensagem;
- Registrar anexos/imagens/documentos quando enviados ou recebidos;
- Identificar qual usuário respondeu ou enviou a mensagem;
- Associar conversa à oportunidade comercial.

### 12.3 Modelos de mensagem WhatsApp

Sugestões iniciais:

- Primeiro contato;
- Confirmação de reunião;
- Lembrete de reunião;
- Agradecimento pós-reunião;
- Follow-up de proposta;
- Reativação de lead;
- Confirmação de recebimento de proposta.

### 12.4 Regras de negócio

- Toda mensagem deve estar vinculada a um contato;
- Toda mensagem deve aparecer no histórico da empresa;
- Toda mensagem deve registrar qual canal de comunicação foi utilizado;
- O sistema deve permitir visualizar o contexto completo da conversa;
- O sistema deve evitar disparos automáticos excessivos;
- O usuário deve conseguir identificar se uma mensagem foi manual ou automática.

---

## 13. Módulo de Canais de Comunicação

### 13.1 Objetivo

Criar uma seção própria para cadastro e gestão dos canais usados nas comunicações comerciais, separando a experiência do usuário comercial da tecnologia de integração utilizada por trás de cada canal.

A seção deve permitir que o administrador cadastre canais do tipo **Ligação**, **WhatsApp** e **E-mail**, informe o provedor correspondente e defina quais usuários podem acessar cada canal.

### 13.2 Conceito

Um canal representa uma origem operacional de comunicação dentro do CRM. Ele possui:

- Tipo do canal;
- Provedor técnico;
- Nome identificável para o time;
- Credenciais e configurações do provedor;
- Usuários com acesso;
- Status ativo ou inativo;
- Indicação de canal padrão quando aplicável;
- Histórico de uso e auditoria.

Para o MVP, os provedores iniciais serão:

- **Ligação:** Twilio;
- **WhatsApp:** Evolution API;
- **E-mail:** SMTP.

A arquitetura deve permitir novos provedores no futuro, como outros serviços de telefonia, outros gateways de WhatsApp ou provedores transacionais de e-mail.

### 13.3 Tipos de canais

#### Ligação

Uso inicial:

- Um canal compartilhado de ligação para todo o time comercial;
- Provedor inicial: Twilio;
- Todos os usuários autorizados poderão usar o mesmo canal de ligação.

Configurações sugeridas:

- Nome do canal;
- Provedor;
- Account SID;
- Auth Token;
- Número de origem;
- URL de webhook;
- Token de validação de webhook;
- Usuários com acesso.

#### WhatsApp

Uso inicial:

- Cada usuário terá seu próprio canal de WhatsApp;
- Provedor inicial: Evolution API;
- O usuário só poderá enviar e responder mensagens pelos canais aos quais tiver acesso;
- Gestores e administradores poderão visualizar e gerenciar os canais conforme permissão.

Configurações sugeridas:

- Nome do canal;
- Provedor;
- URL base da Evolution API;
- API key;
- Instância;
- Número vinculado;
- Token de webhook;
- Usuários com acesso.

#### E-mail

Uso inicial:

- Cada usuário terá seu próprio canal de e-mail;
- Provedor inicial: SMTP;
- O usuário só poderá enviar e responder e-mails pelos canais aos quais tiver acesso;
- O CRM deve preparar a estrutura para múltiplos provedores de e-mail no futuro.

Configurações sugeridas:

- Nome do canal;
- Provedor;
- Host SMTP;
- Porta;
- Usuário;
- Senha;
- Criptografia;
- E-mail remetente;
- Nome do remetente;
- Usuários com acesso.

### 13.4 Permissões e acesso

- Administradores podem criar, editar, ativar, desativar e remover acesso de canais;
- Gestores comerciais podem visualizar canais do time e acompanhar uso, conforme permissão;
- SDRs e Closers só podem utilizar canais ativos aos quais tenham acesso;
- Um canal pode ser individual ou compartilhado;
- O CRM deve permitir definir um canal padrão por usuário e por tipo;
- Ao enviar e-mail, WhatsApp ou realizar ligação, o sistema deve listar apenas canais ativos disponíveis para o usuário.

### 13.5 Regras de negócio

- As credenciais dos canais devem ser armazenadas de forma criptografada;
- Desativar um canal deve impedir novos envios, mas preservar todo o histórico já registrado;
- Toda comunicação enviada ou recebida deve registrar o canal utilizado;
- Webhooks recebidos devem identificar o canal correspondente antes de criar eventos no histórico;
- Automações devem usar o canal padrão do responsável quando a ação for individual;
- Para canais compartilhados, como ligação no MVP, o sistema deve registrar o usuário executor mesmo que o canal seja comum ao time;
- O sistema deve permitir testar conexão do canal antes de ativá-lo;
- O modelo deve aceitar novos provedores sem exigir mudança estrutural no cadastro de empresas, contatos, histórico ou automações.

### 13.6 Critérios de aceite

- O administrador consegue cadastrar um canal escolhendo tipo e provedor;
- O formulário exibe campos específicos conforme o tipo de canal;
- O administrador consegue definir usuários com acesso ao canal;
- Usuários só visualizam e utilizam canais ativos liberados para eles;
- Ligações, e-mails e mensagens de WhatsApp ficam vinculados ao canal usado;
- O canal compartilhado de ligação pode ser usado por todos os usuários autorizados;
- Canais individuais de e-mail e WhatsApp podem ser atribuídos a cada usuário;
- Credenciais sensíveis não aparecem em texto aberto após salvas.

---

## 14. Módulo de Automações Comerciais

### 14.1 Objetivo

Permitir a criação de fluxos automáticos para reduzir trabalho manual, padronizar o relacionamento comercial e garantir que leads e oportunidades recebam comunicações no momento correto.

### 14.2 Conceito

As automações serão baseadas em gatilhos, condições e ações.

Exemplo:

> Quando uma oportunidade entrar na etapa “Reunião agendada”, enviar automaticamente um lembrete por e-mail e WhatsApp para o contato principal.

### 14.3 Gatilhos sugeridos

- Empresa criada;
- Contato criado;
- Oportunidade criada;
- Oportunidade mudou de etapa;
- Reunião agendada;
- Reunião próxima do horário;
- Proposta enviada;
- Proposta sem resposta há X dias;
- Lead sem interação há X dias;
- Tarefa vencida;
- E-mail recebido;
- WhatsApp recebido.

### 14.4 Condições sugeridas

- Etapa atual do pipeline;
- Origem do lead;
- Responsável;
- Temperatura do lead;
- Valor estimado da oportunidade;
- Potencial comercial da empresa;
- Segmento da empresa;
- Status da empresa;
- Existência de contato principal;
- Data da última interação.

### 14.5 Ações sugeridas

- Enviar e-mail;
- Enviar WhatsApp;
- Criar tarefa;
- Criar lembrete;
- Alterar responsável;
- Alterar etapa;
- Adicionar anotação no histórico;
- Notificar usuário interno;
- Notificar gestor;
- Agendar follow-up.

### 14.6 Exemplos de automações iniciais

#### Automação 1 — Reunião agendada

Quando uma oportunidade entrar na etapa “Reunião agendada”:

- Enviar WhatsApp de confirmação;
- Enviar e-mail com data, horário e link da reunião;
- Criar tarefa para o responsável revisar a empresa antes da reunião.

#### Automação 2 — Lembrete antes da reunião

Quando faltar 1 hora para uma reunião:

- Enviar WhatsApp de lembrete para o contato principal;
- Notificar o responsável interno.

#### Automação 3 — Pós-reunião

Quando uma oportunidade entrar em “Reunião realizada”:

- Criar tarefa para enviar proposta;
- Enviar mensagem de agradecimento;
- Definir prazo de follow-up.

#### Automação 4 — Proposta sem resposta

Quando uma proposta estiver há 3 dias sem resposta:

- Criar tarefa de follow-up para o closer;
- Enviar WhatsApp educado perguntando se o cliente conseguiu avaliar.

#### Automação 5 — Lead parado

Quando uma oportunidade ficar mais de 7 dias sem interação:

- Notificar o responsável;
- Criar tarefa de retomada;
- Sinalizar oportunidade como parada.

### 14.7 Regras de negócio

- Toda automação executada deve ser registrada no histórico da empresa;
- O gestor comercial deve poder criar, editar, ativar e desativar automações;
- O sistema deve evitar duplicidade de envio;
- O usuário deve conseguir identificar se uma comunicação foi enviada manualmente ou por automação;
- Automações de e-mail e WhatsApp devem usar um canal ativo disponível para o responsável ou um canal explicitamente configurado na regra;
- Automações críticas devem ter logs de execução.

---

## 15. Módulo de Atividades, Tarefas e Agenda

### 15.1 Objetivo

Permitir que o time comercial organize seus próximos passos, reuniões, follow-ups e compromissos relacionados às empresas e oportunidades.

### 15.2 Tipos de atividades

- Ligação;
- E-mail;
- WhatsApp;
- Reunião;
- Follow-up;
- Envio de proposta;
- Revisão de contrato;
- Outro.

### 15.3 Funcionalidades

- Criar tarefa manual;
- Criar tarefa automática;
- Definir responsável;
- Definir data e hora;
- Definir prioridade;
- Marcar como concluída;
- Reagendar;
- Cancelar;
- Vincular à empresa;
- Vincular à oportunidade;
- Exibir tarefas no dashboard do usuário;
- Exibir tarefas vencidas;
- Exibir tarefas do dia.

### 15.4 Regras de negócio

- Toda tarefa deve ter um responsável;
- Tarefas vencidas devem aparecer em destaque;
- A conclusão da tarefa deve ser registrada no histórico da empresa;
- O sistema deve permitir filtrar tarefas por usuário, data, status e tipo.

---

## 16. Módulo de Dashboard

### 16.1 Objetivo

Fornecer uma visão clara da operação comercial, permitindo acompanhar performance, produtividade, conversão, oportunidades e previsão de receita.

### 16.2 Indicadores gerais

- Total de empresas cadastradas;
- Total de leads novos;
- Total de oportunidades abertas;
- Total de oportunidades ganhas;
- Total de oportunidades perdidas;
- Valor total em negociação;
- Valor total ganho;
- Taxa de conversão geral;
- Ticket médio das oportunidades;
- Tempo médio de fechamento;
- Oportunidades paradas;
- Atividades vencidas.

### 16.3 Indicadores de pipeline

- Quantidade de oportunidades por etapa;
- Valor total por etapa;
- Conversão entre etapas;
- Tempo médio por etapa;
- Gargalos do funil;
- Previsão de fechamento por mês.

### 16.4 Indicadores de produtividade

- Ligações realizadas por usuário;
- E-mails enviados por usuário;
- WhatsApps enviados por usuário;
- Reuniões agendadas;
- Reuniões realizadas;
- Tarefas concluídas;
- Follow-ups realizados;
- Leads qualificados por SDR;
- Fechamentos por Closer.

### 16.5 Indicadores de carteira prospectada

Com base nos campos cadastrados na empresa:

- Soma do valor total de inadimplência das empresas em negociação;
- Média de ticket médio da cobrança;
- Total estimado de clientes inadimplentes nas empresas cadastradas;
- Potencial de receita por carteira;
- Empresas com maior valor de inadimplência;
- Empresas com maior quantidade de inadimplentes.

### 16.6 Visões por perfil

#### Gestor Comercial

- Visão geral do funil;
- Ranking de vendedores;
- Atividades atrasadas;
- Oportunidades sem interação;
- Valor total por etapa;
- Conversão por usuário;
- Forecast comercial.

#### SDR

- Leads atribuídos;
- Tarefas do dia;
- Empresas sem contato;
- Reuniões agendadas;
- Leads qualificados;
- Atividades pendentes.

#### Closer

- Oportunidades em negociação;
- Propostas enviadas;
- Follow-ups pendentes;
- Previsão de fechamento;
- Oportunidades paradas;
- Valor estimado em negociação.

---

## 17. Módulo de Usuários e Permissões

### 17.1 Perfis iniciais

- Administrador;
- Gestor Comercial;
- SDR;
- Closer.

### 17.2 Permissões sugeridas

#### Administrador

- Acesso total ao sistema;
- Gestão de canais de comunicação;
- Gestão de usuários;
- Gestão de permissões;
- Gestão de automações;
- Configuração do pipeline.

#### Gestor Comercial

- Acesso ao dashboard geral;
- Visualizar empresas e oportunidades do time;
- Acompanhar histórico;
- Criar e editar automações;
- Redistribuir responsáveis;
- Gerar relatórios.

#### SDR

- Visualizar empresas sob sua responsabilidade;
- Criar empresas e contatos;
- Criar e movimentar oportunidades em etapas iniciais;
- Fazer ligações;
- Enviar e-mails;
- Enviar WhatsApps;
- Criar tarefas.

#### Closer

- Visualizar oportunidades atribuídas;
- Visualizar histórico completo da empresa;
- Enviar propostas;
- Movimentar oportunidades nas etapas finais;
- Registrar negociações;
- Fechar oportunidades como ganhas ou perdidas.

---

## 18. Relatórios

### 18.1 Relatórios sugeridos

- Relatório de empresas cadastradas;
- Relatório de oportunidades por período;
- Relatório de oportunidades ganhas;
- Relatório de oportunidades perdidas;
- Relatório de motivos de perda;
- Relatório de produtividade por usuário;
- Relatório de ligações;
- Relatório de e-mails;
- Relatório de WhatsApp;
- Relatório de reuniões;
- Relatório de forecast;
- Relatório de carteira potencial.

### 18.2 Exportações

- Exportar para Excel;
- Exportar para CSV;
- Exportar relatório em PDF;
- Filtrar por período, usuário, etapa, origem, segmento e status.

---

## 19. Configurações do Sistema

### 19.1 Configurações gerais

- Dados da empresa VOZ;
- Usuários;
- Perfis de acesso;
- Pipeline;
- Etapas do funil;
- Motivos de perda;
- Origens de lead;
- Segmentos;
- Tipos de contato;
- Modelos de e-mail;
- Modelos de WhatsApp;
- Automações;
- Canais de comunicação.

### 19.2 Configurações de pipeline

- Criar pipeline;
- Editar etapas;
- Definir ordem das etapas;
- Definir etapas obrigatórias;
- Definir campos obrigatórios por etapa;
- Definir automações por etapa.

---

## 20. Integrações Técnicas

As integrações técnicas devem ser acessadas pela seção de **Canais de Comunicação**. O usuário comercial não deve precisar lidar diretamente com nomes de provedores, chaves ou detalhes técnicos durante o uso diário.

### 20.1 Twilio

Provedor inicial para canais do tipo **Ligação**.

Uso previsto:

- Realização de chamadas pelo CRM;
- Registro de chamadas;
- Captura de status da ligação quando disponível;
- Captura de duração da ligação quando disponível.

Fora do escopo inicial:

- Recebimento de chamadas pelo CRM;
- Gravação de chamadas;
- URA;
- Call center receptivo.

### 20.2 Evolution API

Provedor inicial para canais do tipo **WhatsApp**.

Uso previsto:

- Envio de mensagens WhatsApp;
- Recebimento de mensagens WhatsApp;
- Sincronização de conversas;
- Registro das mensagens no histórico da empresa;
- Envio de mensagens automáticas por fluxos.

### 20.3 SMTP

Provedor inicial para canais do tipo **E-mail**.

Uso previsto:

- Envio de e-mails;
- Recebimento de e-mails quando configurado;
- Associação de e-mails com contatos e empresas;
- Histórico de conversas por e-mail;
- Uso de modelos.

---

## 21. Requisitos Não Funcionais

### 21.1 Segurança

- Autenticação de usuários;
- Controle de permissões por perfil;
- Registro de logs de ações importantes;
- Proteção de dados sensíveis;
- Criptografia de credenciais de integração;
- Controle de acesso aos históricos comerciais;
- Auditoria de alterações relevantes.

### 21.2 Performance

- Listagem rápida de empresas;
- Busca eficiente por empresa, contato, CNPJ, telefone e e-mail;
- Pipeline com boa performance mesmo com muitas oportunidades;
- Histórico carregado de forma paginada;
- Dashboard otimizado para grandes volumes.

### 21.3 Usabilidade

- Interface limpa e moderna;
- Acesso rápido ao histórico da empresa;
- Botões de ação visíveis: ligar, enviar e-mail, enviar WhatsApp, criar tarefa;
- Pipeline visual fácil de usar;
- Dashboard claro e objetivo;
- Redução de cliques para ações comerciais frequentes.

### 21.4 Rastreabilidade

- Registrar quem criou, alterou ou movimentou uma oportunidade;
- Registrar comunicações automáticas;
- Registrar mudanças de etapa;
- Registrar execução de automações;
- Registrar alterações críticas em cadastros.

---

## 22. MVP Sugerido

Para a primeira versão, recomenda-se desenvolver um MVP robusto, mas sem tentar construir tudo de uma vez.

### 22.1 Funcionalidades essenciais do MVP

1. Cadastro de empresas;
2. Cadastro de contatos vinculados;
3. Pipeline Kanban;
4. Cadastro de oportunidades;
5. Histórico centralizado da empresa;
6. Registro manual de atividades;
7. Canais de comunicação para ligação, WhatsApp e e-mail;
8. Ligações via canal Twilio compartilhado pelo time;
9. Envio e recebimento de WhatsApp via canais Evolution API por usuário;
10. Envio de e-mails via canais SMTP por usuário;
11. Modelos básicos de e-mail e WhatsApp;
12. Automações simples por mudança de etapa;
13. Dashboard inicial;
14. Usuários e permissões básicas.

### 22.2 Funcionalidades para segunda fase

- Recebimento completo e organização avançada de e-mails;
- Novos provedores de canais de comunicação;
- Relatórios avançados;
- Automação visual estilo construtor de fluxos;
- Forecast comercial avançado;
- Análise de performance por vendedor;
- SLA de follow-up;
- Integração com agenda externa;
- Campos personalizados;
- Múltiplos pipelines;
- IA para sugestão de próximos passos.

---

## 23. Sugestão de Menus do Sistema

- Dashboard;
- Empresas;
- Contatos;
- Pipeline;
- Oportunidades;
- Atividades;
- Ligações;
- E-mails;
- WhatsApp;
- Canais;
- Automações;
- Relatórios;
- Configurações.

---

## 24. Tela Principal da Empresa

A tela da empresa deve ser uma das partes mais importantes do CRM.

### 24.1 Estrutura sugerida

#### Cabeçalho

- Nome fantasia;
- Razão social;
- CNPJ;
- Status;
- Responsável;
- Temperatura do lead;
- Botões rápidos: ligar, e-mail, WhatsApp, tarefa, nova oportunidade.

#### Bloco de dados comerciais

- Ticket médio da cobrança;
- Quantidade de clientes inadimplentes;
- Valor total da inadimplência;
- Potencial comercial;
- Sistema utilizado;
- Segmento;
- Porte.

#### Abas

- Visão geral;
- Contatos;
- Oportunidades;
- Histórico;
- Atividades;
- E-mails;
- WhatsApp;
- Ligações;
- Arquivos;
- Observações.

---

## 25. Critérios de Sucesso

O VOZ CRM será considerado bem-sucedido se conseguir:

- Centralizar o histórico comercial das empresas;
- Reduzir perda de informações entre SDR e Closer;
- Melhorar acompanhamento do funil comercial;
- Aumentar disciplina de follow-up;
- Dar visibilidade ao gestor comercial;
- Permitir comunicação integrada por telefone, e-mail e WhatsApp;
- Automatizar lembretes e ações repetitivas;
- Ajudar a priorizar empresas com maior potencial de carteira;
- Melhorar taxa de conversão comercial.

---

## 26. Pontos em Aberto para Validação

Algumas decisões ainda precisam ser validadas antes da especificação técnica:

1. O CRM terá apenas um pipeline ou múltiplos pipelines?
2. O SDR poderá mover oportunidade até qual etapa?
3. O Closer assume automaticamente após “Reunião agendada” ou será atribuído manualmente?
4. O CRM precisa integrar com calendário externo?
5. O sistema terá proposta comercial dentro dele ou apenas registro de proposta enviada?
6. O gestor poderá criar campos personalizados?
7. Haverá metas comerciais por usuário?
8. Haverá controle de comissões?
9. Quais provedores adicionais de ligação, WhatsApp ou e-mail devem ser considerados após o MVP?

---

## 27. Recomendações Estratégicas

### 27.1 Priorizar a empresa como entidade central

Como o modelo da VOZ é B2B, o cadastro da empresa deve ser tratado como o coração do CRM. O contato é importante, mas o histórico comercial precisa pertencer à empresa.

### 27.2 Separar claramente SDR e Closer

O CRM deve refletir a operação comercial real. O SDR qualifica e agenda. O Closer negocia e fecha. Isso ajuda a medir produtividade e conversão de cada etapa.

### 27.3 Criar histórico automático desde o início

Tudo que acontecer no CRM deve alimentar a linha do tempo da empresa. Isso evita perda de contexto e melhora a passagem de bastão entre vendedores.

### 27.4 Começar com automações simples

Na primeira versão, as automações podem ser baseadas em eventos simples, como mudança de etapa. Depois, o sistema pode evoluir para um construtor visual de fluxos.

### 27.5 Dashboard focado em gestão comercial

O dashboard deve ajudar o gestor a responder rapidamente:

- Quantas oportunidades temos?
- Quanto temos em negociação?
- Onde o funil está travando?
- Quem está performando melhor?
- Quais empresas têm maior potencial?
- Quais oportunidades estão paradas?

---

## 28. Resumo Executivo

O VOZ CRM será uma plataforma comercial B2B criada para centralizar o relacionamento com empresas, organizar oportunidades em um pipeline visual, integrar comunicação por ligação, e-mail e WhatsApp, automatizar tarefas comerciais e fornecer indicadores estratégicos para gestão.

O diferencial do produto será a visão unificada da empresa: todos os contatos, conversas, ligações, e-mails, reuniões, propostas e oportunidades estarão concentrados em um único histórico, permitindo que SDRs, Closers e gestores tenham clareza total sobre cada negociação.

A primeira versão deve focar em cadastro de empresas e contatos, pipeline Kanban, histórico centralizado, comunicação integrada, automações simples e dashboard comercial. Com essa base, o VOZ CRM poderá evoluir para relatórios avançados, automações visuais, inteligência comercial e previsões de fechamento.
