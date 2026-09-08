# Guia do Usuário Operacional - Carinho CRM

Manual prático para operação diária do sistema de gestão de relacionamento com clientes.

> **Runtime:** as rotas HTTP de classificacao ABC, revisoes e indicacoes existem em `ClientController` e leem/gravam as tabelas `domain_client_classification`, `client_reviews` e `client_referrals`. Login local de desenvolvimento: ver README (`ChangeMeLocal!` / `CRM_ADMIN_PASSWORD`).

---

## Sumário

1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Fluxo 1: Recebimento e Cadastro de Leads](#2-fluxo-1-recebimento-e-cadastro-de-leads)
3. [Fluxo 2: Qualificação e Triagem](#3-fluxo-2-qualificação-e-triagem)
4. [Fluxo 3: Criação de Proposta](#4-fluxo-3-criação-de-proposta)
5. [Fluxo 4: Fechamento do Negócio](#5-fluxo-4-fechamento-do-negócio)
6. [Fluxo 5: Cadastro Completo do Cliente](#6-fluxo-5-cadastro-completo-do-cliente)
7. [Fluxo 6: Gestão de Contratos](#7-fluxo-6-gestão-de-contratos)
8. [Fluxo 7: Revisões Periódicas](#8-fluxo-7-revisões-periódicas)
9. [Fluxo 8: Programa de Indicações](#9-fluxo-8-programa-de-indicações)
10. [Fluxo 9: Acompanhamento de Renovações](#10-fluxo-9-acompanhamento-de-renovações)
11. [Rotinas Diárias](#11-rotinas-diárias)
12. [Boas Práticas](#12-boas-práticas)

---

## 1. Visão Geral do Sistema

O Carinho CRM é o sistema central de gestão de relacionamento com clientes. Ele organiza todo o ciclo de vida do cliente, desde o primeiro contato até a renovação de contratos.

### Jornada do Cliente no Sistema

```
┌─────────┐    ┌──────────┐    ┌──────────┐    ┌─────────┐    ┌──────────┐
│  LEAD   │───▶│ TRIAGEM  │───▶│ PROPOSTA │───▶│ CLIENTE │───▶│ CONTRATO │
│  Novo   │    │Qualificar│    │  Enviar  │    │ Ativo   │    │  Ativo   │
└─────────┘    └──────────┘    └──────────┘    └─────────┘    └──────────┘
                                                     │
                                                     ▼
                                              ┌──────────────┐
                                              │   REVISÃO    │
                                              │  Periódica   │
                                              └──────────────┘
```

### Principais Entidades

| Entidade | Descrição |
|----------|-----------|
| **Lead** | Pessoa interessada que ainda não é cliente |
| **Cliente** | Lead convertido com cadastro completo |
| **Deal** | Oportunidade de negócio em andamento |
| **Proposta** | Oferta comercial enviada ao lead |
| **Contrato** | Acordo formal de prestação de serviço |
| **Tarefa** | Atividade de acompanhamento |
| **Interação** | Registro de contato (WhatsApp, telefone, e-mail) |

---

## 2. Fluxo 1: Recebimento e Cadastro de Leads

### Quando usar
Sempre que um novo interessado entrar em contato ou for captado pelo site/marketing.

### Passo a Passo

#### Passo 1: Verificar se o lead já existe
1. Acesse a tela de **Leads**
2. Pesquise pelo **telefone** ou **nome** do interessado
3. Se encontrar, atualize o registro existente
4. Se não encontrar, prossiga para o cadastro

#### Passo 2: Cadastrar novo lead
1. Clique em **Novo Lead**
2. Preencha os campos obrigatórios:

| Campo | O que preencher | Exemplo |
|-------|-----------------|---------|
| **Nome** | Nome completo do interessado | Maria Silva Santos |
| **Telefone** | Com DDD, apenas números | 11987654321 |
| **Cidade** | Cidade de atendimento | São Paulo |
| **Urgência** | Quando precisa do serviço | Hoje / Semana / Sem data |
| **Tipo de Serviço** | Modalidade desejada | Horista / Diário / Mensal |
| **Origem** | De onde veio o lead | Site, WhatsApp, Indicação |

3. Clique em **Salvar**

#### Passo 3: Registrar primeira interação
1. Na tela do lead, clique em **Nova Interação**
2. Selecione o **canal** (WhatsApp, Telefone, E-mail)
3. Descreva o resumo do contato
4. Clique em **Salvar**

#### Passo 4: Criar tarefa de follow-up
1. Clique em **Nova Tarefa**
2. Defina o tipo: **Primeiro Contato** ou **Retornar Ligação**
3. Defina a **data de vencimento** (máximo 24h para leads urgentes)
4. Atribua a si mesmo ou a um colega
5. Clique em **Salvar**

### ✅ Resultado Esperado
- Lead cadastrado com status **Novo**
- Primeira interação registrada
- Tarefa de follow-up agendada

---

## 3. Fluxo 2: Qualificação e Triagem

### Quando usar
Após o primeiro contato com o lead, para entender suas necessidades.

### Passo a Passo

#### Passo 1: Contatar o lead
1. Acesse a lista de **Tarefas Pendentes**
2. Localize a tarefa de contato do lead
3. Realize o contato (preferencialmente WhatsApp ou telefone)

#### Passo 2: Coletar informações de qualificação
Durante o contato, colete as seguintes informações:

| Informação | Pergunta Sugerida |
|------------|-------------------|
| **Quem precisa do cuidado** | "O cuidado é para quem?" |
| **Tipo de paciente** | "É idoso, PCD, pós-operatório?" |
| **Condições especiais** | "Tem alguma condição que precisamos saber?" |
| **Horários desejados** | "Qual horário precisa do cuidador?" |
| **Frequência** | "Quantas vezes por semana?" |
| **Orçamento** | "Tem um valor em mente?" |
| **Decisor** | "Quem vai decidir sobre a contratação?" |

#### Passo 3: Registrar a interação
1. Clique em **Nova Interação**
2. Registre todas as informações coletadas
3. Seja detalhado no resumo

#### Passo 4: Atualizar status do lead
1. Se o lead está qualificado, clique em **Avançar Status**
2. O status mudará de **Novo** para **Triagem**

#### Passo 5: Criar Deal (Oportunidade)
1. Clique em **Criar Deal**
2. Preencha:

| Campo | O que preencher |
|-------|-----------------|
| **Valor Estimado** | Valor mensal estimado do serviço |
| **Probabilidade** | % de chance de fechar (ver tabela abaixo) |
| **Data Prevista** | Quando espera fechar o negócio |
| **Próximo Passo** | Qual a próxima ação necessária |

**Tabela de Probabilidade:**

| Situação | Probabilidade |
|----------|---------------|
| Primeiro contato, só coletou informações | 10% |
| Interessado, aguardando proposta | 25% |
| Proposta enviada, em análise | 50% |
| Negociando valores/condições | 75% |
| Aguardando apenas assinatura | 90% |

### ✅ Resultado Esperado
- Lead com status **Triagem**
- Deal criado com probabilidade e valor
- Informações de qualificação registradas

---

## 4. Fluxo 3: Criação de Proposta

### Quando usar
Quando o lead demonstra interesse real e solicita valores.

### Passo a Passo

#### Passo 1: Montar a proposta
1. Acesse o **Deal** do lead
2. Clique em **Nova Proposta**
3. Preencha:

| Campo | O que preencher |
|-------|-----------------|
| **Tipo de Serviço** | Horista, Diário ou Mensal |
| **Valor** | Preço mensal do serviço |
| **Observações** | Detalhes específicos (horários, frequência) |
| **Validade** | Data limite para aceite (sugestão: 7 dias) |

#### Passo 2: Atualizar o Deal
1. Atualize a **probabilidade** para **50%**
2. Atualize o **próximo passo** para "Aguardando resposta da proposta"
3. Defina a **data da próxima ação** (follow-up em 2-3 dias)

#### Passo 3: Enviar a proposta
1. Clique em **Enviar Proposta**
2. Selecione o canal (WhatsApp ou E-mail)
3. Personalize a mensagem se necessário
4. Clique em **Enviar**

#### Passo 4: Registrar o envio
1. A interação será registrada automaticamente
2. Verifique se aparece na timeline do lead

#### Passo 5: Avançar status
1. Clique em **Avançar Status**
2. O status mudará de **Triagem** para **Proposta**

#### Passo 6: Criar tarefa de follow-up
1. Crie uma tarefa para **Acompanhar Proposta**
2. Data: 2-3 dias úteis após o envio

### ✅ Resultado Esperado
- Lead com status **Proposta**
- Proposta criada e enviada
- Deal com probabilidade 50%
- Tarefa de follow-up agendada

---

## 5. Fluxo 4: Fechamento do Negócio

### Quando usar
Quando o lead aceita a proposta e deseja contratar.

### Passo a Passo

#### Passo 1: Confirmar aceite
1. Registre a interação de aceite
2. Anote os detalhes acordados (valor final, data de início)

#### Passo 2: Atualizar o Deal
1. Atualize a **probabilidade** para **90%**
2. Atualize o **próximo passo** para "Enviar contrato para assinatura"

#### Passo 3: Marcar Deal como Ganho
1. Clique em **Marcar como Ganho**
2. Confirme a ação

> **O que acontece automaticamente:**
> - Lead é convertido em Cliente
> - Contrato é criado em status Rascunho
> - Notificação é enviada para o Financeiro
> - Notificação é enviada para a Operação

#### Passo 4: Gerar link de assinatura
1. Acesse o **Contrato** criado
2. Clique em **Gerar Link de Assinatura**
3. Envie o link para o cliente (WhatsApp ou E-mail)

#### Passo 5: Registrar a conversão
1. Registre uma interação: "Proposta aceita, contrato enviado para assinatura"

### ✅ Resultado Esperado
- Lead convertido em **Cliente**
- Contrato em status **Rascunho**
- Link de assinatura enviado
- Deal marcado como **Ganho**

---

## 6. Fluxo 5: Cadastro Completo do Cliente

### Quando usar
Imediatamente após a conversão do lead em cliente.

### Passo a Passo

#### Passo 1: Acessar o cadastro do cliente
1. Vá para **Clientes**
2. Localize o cliente recém-convertido
3. Clique em **Editar**

#### Passo 2: Preencher classificação ABC
1. Localize o campo **Classificação**
2. Selecione baseado nos critérios:

| Classificação | Critérios |
|---------------|-----------|
| **A** | Valor alto (>R$3.000/mês) OU potencial de indicações OU cliente estratégico |
| **B** | Valor médio (R$1.500-3.000/mês) E bom relacionamento |
| **C** | Valor baixo (<R$1.500/mês) OU relacionamento inicial |

#### Passo 3: Preencher Responsável Financeiro
**⚠️ IMPORTANTE: Preencha sempre que for diferente do contato principal**

| Campo | O que preencher |
|-------|-----------------|
| **Nome** | Nome completo do responsável financeiro |
| **Telefone** | Telefone para cobrança |
| **E-mail** | E-mail para envio de faturas |
| **CPF/CNPJ** | Documento para nota fiscal |

#### Passo 4: Preencher Contato de Emergência
**⚠️ OBRIGATÓRIO: Crítico para serviços de HomeCare**

| Campo | O que preencher |
|-------|-----------------|
| **Nome** | Nome do contato de emergência |
| **Telefone** | Telefone para emergências |
| **Parentesco** | Relação com o paciente (filho, cônjuge, etc.) |

#### Passo 5: Preencher Necessidades de Cuidado
1. Clique em **Adicionar Necessidade de Cuidado**
2. Preencha:

| Campo | O que preencher |
|-------|-----------------|
| **Tipo de Paciente** | Idoso, PCD, TEA, Pós-operatório |
| **Condições** | Diabetes, Alzheimer, Mobilidade reduzida, etc. |
| **Observações** | Detalhes importantes para o cuidador |

#### Passo 6: Configurar Revisão Periódica
1. Localize o campo **Frequência de Revisão**
2. Selecione baseado na classificação:

| Classificação | Frequência Recomendada |
|---------------|------------------------|
| **A** | Mensal |
| **B** | Trimestral |
| **C** | Semestral |

3. A **data da próxima revisão** será calculada automaticamente

#### Passo 7: Registrar Consentimento LGPD
1. Clique em **Adicionar Consentimento**
2. Selecione: "Uso de dados para prestação de serviço"
3. Selecione: "Comunicação via WhatsApp"
4. Clique em **Salvar**

#### Passo 8: Verificar completude
1. Acesse **Verificar Cadastro**
2. Revise os itens pendentes
3. Complete o que estiver faltando

**Meta: 80% de completude mínima**

### ✅ Resultado Esperado
- Cliente com classificação ABC definida
- Responsável financeiro cadastrado
- Contato de emergência cadastrado
- Necessidades de cuidado registradas
- Revisão periódica agendada
- Consentimentos LGPD registrados
- Cadastro com 80%+ de completude

---

## 7. Fluxo 6: Gestão de Contratos

### Quando usar
Para acompanhar contratos desde a criação até a assinatura e ativação.

### Passo a Passo: Contrato Pendente de Assinatura

#### Passo 1: Monitorar contratos pendentes
1. Acesse **Contratos**
2. Filtre por status **Rascunho**
3. Ordene por data de criação

#### Passo 2: Reenviar link de assinatura (se necessário)
1. Se o cliente não assinou em 48h:
2. Clique no contrato
3. Clique em **Reenviar Link**
4. Registre a interação

#### Passo 3: Processar assinatura
1. Quando o cliente assinar, o status mudará para **Assinado**
2. Revise os dados do contrato
3. Clique em **Ativar Contrato**

> **O que acontece automaticamente:**
> - Status muda para **Ativo**
> - Notificação enviada para Operação (alocação de cuidador)
> - Notificação enviada para Financeiro (cobrança)
> - Evento registrado na timeline do cliente

### Passo a Passo: Configurar Alerta de Renovação

#### Passo 1: Acessar configurações do contrato
1. Abra o contrato ativo
2. Clique em **Configurações de Renovação**

#### Passo 2: Definir dias de alerta
1. Preencha o campo **Dias de Antecedência para Alerta**
2. Recomendações:

| Duração do Contrato | Dias de Alerta |
|---------------------|----------------|
| Até 1 mês | 7 dias |
| 1-3 meses | 15 dias |
| 3-6 meses | 30 dias |
| 6-12 meses | 45 dias |
| Mais de 1 ano | 60 dias |

#### Passo 3: Configurar renovação automática (opcional)
1. Se o cliente concordar, marque **Renovação Automática**
2. Documente o aceite na interação

### ✅ Resultado Esperado
- Contrato com status correto
- Alerta de renovação configurado
- Histórico de ações registrado

---

## 8. Fluxo 7: Revisões Periódicas

### Quando usar
Nas datas agendadas de revisão de clientes.

### Passo a Passo

#### Passo 1: Verificar revisões pendentes
1. Acesse **Revisões Pendentes** (menu ou dashboard)
2. Veja a lista de clientes com revisão vencida ou próxima

#### Passo 2: Preparar-se para a revisão
1. Abra o cadastro do cliente
2. Revise:
   - Última revisão (se houver)
   - Timeline de eventos recentes
   - Interações dos últimos 30 dias
   - Status do contrato

#### Passo 3: Realizar contato de revisão
1. Entre em contato com o cliente (telefone ou WhatsApp)
2. Use o roteiro de revisão:

**Roteiro de Revisão:**

```
"Olá [NOME], tudo bem? 
Sou [SEU NOME] da Carinho com Você.
Estou ligando para nossa conversa de acompanhamento.
Gostaria de saber como está sendo o serviço para vocês."

Perguntas:
1. "Como está o atendimento do(a) cuidador(a)?"
2. "O horário e frequência estão adequados?"
3. "Tem alguma necessidade que não estamos atendendo?"
4. "Numa escala de 1 a 5, qual sua satisfação geral?"
5. "Pretendem continuar com nossos serviços?"
6. "Conhece alguém que possa precisar dos nossos serviços?"
```

#### Passo 4: Registrar a revisão
1. Clique em **Nova Revisão** no cadastro do cliente
2. Preencha:

| Campo | O que preencher |
|-------|-----------------|
| **Data da Revisão** | Data do contato |
| **Nota de Satisfação** | 1 a 5 (baseado na resposta) |
| **Nota de Qualidade** | 1 a 5 (qualidade do serviço) |
| **Intenção de Renovar** | Sim/Não |
| **Observações** | Resumo da conversa |
| **Ações Identificadas** | O que precisa ser feito |

#### Passo 5: Identificar e tratar alertas

**🔴 RISCO DE CHURN (satisfação ≤ 2 ou sem intenção de renovar):**
1. Crie tarefa urgente: "Tratar insatisfação - [NOME]"
2. Envolva o supervisor
3. Proponha ações de correção

**🟢 CLIENTE PROMOTOR (satisfação ≥ 4 e intenção de renovar):**
1. Pergunte sobre indicações
2. Se houver, registre no programa de indicações
3. Agradeça e valorize o feedback

#### Passo 6: Agendar próxima revisão
1. A próxima revisão é agendada automaticamente
2. Verifique se a data está correta
3. Ajuste se necessário

### ✅ Resultado Esperado
- Revisão registrada com notas
- Ações identificadas tratadas
- Próxima revisão agendada
- Indicações capturadas (se houver)

---

## 9. Fluxo 8: Programa de Indicações

### Quando usar
Quando um cliente indica alguém ou quando você identifica uma oportunidade de indicação.

### Passo a Passo: Registrar Indicação

#### Passo 1: Capturar a indicação
1. Durante revisão ou contato, pergunte sobre indicações
2. Colete os dados do indicado:
   - Nome completo
   - Telefone
   - Relação com o cliente (amigo, parente, vizinho)

#### Passo 2: Registrar no sistema
1. Acesse o cadastro do cliente que indicou
2. Clique em **Nova Indicação**
3. Preencha:

| Campo | O que preencher |
|-------|-----------------|
| **Nome do Indicado** | Nome completo |
| **Telefone** | Telefone de contato |
| **Observações** | Como o cliente descreveu a necessidade |

4. Clique em **Salvar**

#### Passo 3: Agradecer o cliente
1. Registre uma interação agradecendo a indicação
2. Informe que entrarão em contato com o indicado

### Passo a Passo: Trabalhar Indicação

#### Passo 1: Verificar indicações pendentes
1. Acesse **Indicações Pendentes**
2. Priorize por data (mais antigas primeiro)

#### Passo 2: Contatar o indicado
1. Entre em contato mencionando quem indicou:

```
"Olá [NOME], tudo bem?
Sou [SEU NOME] da Carinho com Você.
O(a) [NOME DO CLIENTE] me passou seu contato 
e disse que vocês podem estar precisando 
de serviços de cuidador. Posso ajudar?"
```

2. Após o contato, clique em **Marcar como Contatado**

#### Passo 3: Vincular ao lead (se houver interesse)
1. Se o indicado demonstrar interesse, crie um Lead
2. Volte à indicação e clique em **Vincular Lead**
3. Selecione o lead criado

#### Passo 4: Registrar conversão (se fechar)
1. Quando o indicado virar cliente
2. Clique em **Marcar como Convertido**
3. O sistema vincula automaticamente ao cliente

#### Passo 5: Tratar indicação perdida
1. Se o indicado não tiver interesse
2. Clique em **Marcar como Perdido**
3. Informe o motivo

### ✅ Resultado Esperado
- Indicação registrada e rastreada
- Cliente que indicou reconhecido
- Conversão acompanhada

---

## 10. Fluxo 9: Acompanhamento de Renovações

### Quando usar
Quando contratos estão próximos do vencimento.

### Passo a Passo

#### Passo 1: Verificar contratos expirando
1. Acesse **Contratos Expirando** (dashboard ou menu)
2. Veja a lista ordenada por data de vencimento

#### Passo 2: Analisar o cliente
1. Abra o cadastro do cliente
2. Revise:
   - Histórico de revisões
   - Última nota de satisfação
   - Intenção de renovar (última revisão)
   - Histórico de pagamentos (se disponível)

#### Passo 3: Preparar proposta de renovação
1. Se a última revisão indicou intenção de renovar:
   - Prepare renovação nas mesmas condições ou com reajuste
2. Se não indicou:
   - Prepare proposta especial ou desconto

#### Passo 4: Contatar o cliente
1. Ligue ou envie mensagem:

```
"Olá [NOME], tudo bem?
Sou [SEU NOME] da Carinho com Você.
O contrato de vocês está chegando ao fim 
no dia [DATA]. Gostaria de renovar conosco?
Preparei uma proposta especial para a renovação."
```

#### Passo 5: Processar a renovação
1. Se o cliente aceitar:
   - Acesse o contrato atual
   - Clique em **Criar Renovação**
   - Ajuste valores se necessário
   - Envie para assinatura

2. Se o cliente recusar:
   - Registre o motivo
   - Tente negociar condições
   - Se definitivo, encerre o contrato

#### Passo 6: Registrar o resultado
1. Registre a interação com o resultado
2. Se renovado, acompanhe a assinatura do novo contrato

### ✅ Resultado Esperado
- Renovações tratadas antes do vencimento
- Histórico de renovações mantido
- Motivos de não renovação documentados

---

## 11. Rotinas Diárias

### Início do Dia (Primeira Hora)

#### Checklist Matinal

- [ ] **Verificar Tarefas do Dia**
  - Acesse "Minhas Tarefas"
  - Priorize por vencimento e urgência

- [ ] **Verificar Tarefas Atrasadas**
  - Acesse "Tarefas Atrasadas"
  - Trate imediatamente ou reagende

- [ ] **Verificar Leads Urgentes**
  - Acesse "Leads Urgentes" (urgência = Hoje)
  - Priorize contato imediato

- [ ] **Verificar Revisões Pendentes**
  - Acesse "Revisões Pendentes"
  - Agende os contatos do dia

### Durante o Dia

#### A cada contato realizado:
1. Registre a interação imediatamente
2. Atualize o status se necessário
3. Crie tarefa de follow-up se precisar

#### A cada 2 horas:
1. Verifique novas mensagens/notificações
2. Atualize tarefas concluídas

### Final do Dia (Última Hora)

#### Checklist de Encerramento

- [ ] **Atualizar Tarefas**
  - Marque como concluídas as realizadas
  - Reagende as não realizadas (com justificativa)

- [ ] **Revisar Pipeline**
  - Verifique deals sem atividade há mais de 3 dias
  - Atualize probabilidades se necessário

- [ ] **Preparar Amanhã**
  - Revise tarefas do dia seguinte
  - Identifique prioridades

---

## 12. Boas Práticas

### Cadastro de Leads

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Verifique se o lead já existe antes de cadastrar | Cadastrar duplicados |
| Preencha todos os campos obrigatórios | Deixar campos em branco "para depois" |
| Registre a origem correta | Usar "Outros" sem especificar |
| Crie tarefa de follow-up imediato | Deixar lead sem próximo passo |

### Gestão do Pipeline

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Atualize probabilidade conforme avança | Deixar sempre em 50% |
| Defina data prevista de fechamento | Deixar sem previsão |
| Documente o próximo passo | Deixar "aguardando" sem ação definida |
| Trate leads parados há mais de 7 dias | Ignorar leads inativos |

### Cadastro de Clientes

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Preencha contato de emergência sempre | Deixar em branco |
| Classifique o cliente (ABC) no primeiro dia | Classificar só depois |
| Configure revisão periódica | Deixar sem agendamento |
| Registre consentimentos LGPD | Operar sem consentimento |

### Comunicação

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Registre TODA interação | Fazer contato sem registrar |
| Seja específico no resumo | Escrever só "falei com cliente" |
| Use linguagem profissional | Usar gírias ou abreviações excessivas |
| Responda em até 4h (horário comercial) | Deixar mensagens sem resposta |

### Revisões Periódicas

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Siga o roteiro de revisão | Improvisar sem estrutura |
| Registre notas de satisfação | Pular avaliação numérica |
| Trate riscos de churn imediatamente | Deixar para depois |
| Peça indicações para clientes satisfeitos | Perder oportunidade |

### Indicações

| ✅ Faça | ❌ Não Faça |
|---------|------------|
| Contate indicações em até 48h | Deixar esfriando |
| Mencione quem indicou no primeiro contato | Fazer contato frio |
| Atualize o status no sistema | Deixar sem acompanhamento |
| Agradeça o cliente que indicou | Esquecer de reconhecer |

---

## Glossário

| Termo | Significado |
|-------|-------------|
| **Lead** | Pessoa interessada que ainda não é cliente |
| **Deal** | Oportunidade de negócio/venda em andamento |
| **Pipeline** | Funil de vendas com etapas definidas |
| **Triagem** | Processo de qualificação do lead |
| **Classificação ABC** | Segmentação de clientes por valor (A=alto, B=médio, C=baixo) |
| **NPS** | Net Promoter Score - indicador de satisfação |
| **Churn** | Perda/cancelamento de cliente |
| **Follow-up** | Acompanhamento/retorno ao cliente |
| **Forecast** | Previsão de vendas/receita |

---

## Suporte

Em caso de dúvidas ou problemas:
1. Consulte este manual
2. Pergunte ao supervisor
3. Registre sugestões de melhoria

---

*Documento atualizado em Janeiro/2026*
*Versão 1.0*
