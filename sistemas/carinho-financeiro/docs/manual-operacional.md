# Manual Operacional - Carinho Financeiro

## Guia Passo a Passo para Usuários

**Versão:** 2.0  
**Última Atualização:** Janeiro 2026

---

## Sumário

1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Ciclo de Faturamento](#2-ciclo-de-faturamento)
3. [Processamento de Pagamentos](#3-processamento-de-pagamentos)
4. [Gestão de Repasses aos Cuidadores](#4-gestão-de-repasses-aos-cuidadores)
5. [Controle de Fluxo de Caixa](#5-controle-de-fluxo-de-caixa)
6. [Gestão de Contas a Pagar](#6-gestão-de-contas-a-pagar)
7. [Conciliação Mensal](#7-conciliação-mensal)
8. [Relatórios Gerenciais](#8-relatórios-gerenciais)
9. [Gestão de Aprovações](#9-gestão-de-aprovações)
10. [Provisões (PCLD)](#10-provisões-pcld)
11. [Rotinas Diárias, Semanais e Mensais](#11-rotinas-diárias-semanais-e-mensais)

---

## 1. Visão Geral do Sistema

### O que o Sistema Faz

O Carinho Financeiro gerencia todo o ciclo financeiro da empresa:

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Serviço    │───▶│   Fatura    │───▶│  Pagamento  │───▶│   Repasse   │
│  Agendado   │    │   Criada    │    │  Recebido   │    │ ao Cuidador │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

### Princípios Importantes

| Princípio | Descrição |
|-----------|-----------|
| **Pagamento Antecipado** | Cliente sempre paga ANTES do serviço (24h de antecedência) |
| **Transparência** | Todos os valores e taxas são claros para cliente e cuidador |
| **Rastreabilidade** | Toda operação é registrada e pode ser auditada |
| **Aprovações** | Operações acima dos limites requerem aprovação |

---

## 2. Ciclo de Faturamento

### 2.1 Criar Nova Fatura

**Quando usar:** Quando um serviço é agendado e precisa ser cobrado do cliente.

**Passo a passo:**

1. **Acesse** o módulo de Faturas
2. **Clique** em "Nova Fatura"
3. **Preencha os dados obrigatórios:**
   - Cliente (selecione da lista)
   - Contrato vinculado
   - Período do serviço (data início e fim)
4. **Adicione os itens da fatura:**
   - Tipo de serviço (Horista, Diário ou Mensal)
   - Quantidade de horas/dias
   - Cuidador responsável
   - Data do serviço
5. **Verifique os adicionais automáticos:**
   - Adicional noturno (+20%) - se houver horas entre 22h e 6h
   - Adicional fim de semana (+30%) - se for sábado ou domingo
   - Adicional feriado (+50%) - se for feriado
6. **Revise o valor total**
7. **Clique** em "Criar Fatura"

**Resultado esperado:** Fatura criada com status "Em Aberto" e data de vencimento 24h antes do serviço.

> ⚠️ **Atenção:** O sistema calcula automaticamente a data de vencimento. Se o serviço é dia 15/01 às 14h, o vencimento será dia 14/01 às 14h.

---

### 2.2 Adicionar Itens à Fatura Existente

**Quando usar:** Quando precisa incluir serviços adicionais em uma fatura já criada.

**Passo a passo:**

1. **Localize** a fatura desejada (use filtros por cliente ou número)
2. **Verifique** se o status é "Em Aberto" (faturas pagas não podem ser alteradas)
3. **Clique** em "Adicionar Item"
4. **Preencha** os dados do novo serviço
5. **Confirme** a adição

**Resultado esperado:** Item adicionado e valor total recalculado automaticamente.

---

### 2.3 Aplicar Desconto

**Quando usar:** Para conceder desconto ao cliente (cortesia, negociação, etc).

**Passo a passo:**

1. **Abra** a fatura desejada
2. **Clique** em "Aplicar Desconto"
3. **Informe:**
   - Valor ou percentual do desconto
   - Motivo do desconto (obrigatório para auditoria)
4. **Verifique** se requer aprovação:
   - Até 10%: aprovação automática
   - Acima de 10%: requer aprovação do gestor
5. **Confirme** o desconto

**Resultado esperado:** 
- Se dentro do limite: desconto aplicado imediatamente
- Se acima do limite: solicitação de aprovação criada

> 💡 **Dica:** Sempre documente o motivo do desconto. Isso ajuda na análise de políticas comerciais.

---

### 2.4 Cancelar Fatura

**Quando usar:** Quando o serviço é cancelado pelo cliente ou cuidador.

**Passo a passo:**

1. **Localize** a fatura a ser cancelada
2. **Clique** em "Cancelar Fatura"
3. **Informe:**
   - Motivo do cancelamento
   - Quem solicitou (cliente ou cuidador)
4. **O sistema calculará automaticamente o reembolso:**

| Antecedência | Reembolso | Exemplo |
|--------------|-----------|---------|
| Mais de 24h | 100% | Serviço dia 15, cancela dia 13 → reembolso total |
| Entre 6h e 24h | 50% | Serviço dia 15 às 14h, cancela dia 14 às 20h → 50% |
| Menos de 6h | 0% | Serviço dia 15 às 14h, cancela dia 15 às 10h → sem reembolso |

5. **Confirme** o cancelamento

**Resultado esperado:** 
- Fatura marcada como "Cancelada"
- Reembolso processado (se aplicável)
- Cliente notificado via WhatsApp

> ⚠️ **Importante:** Se o cancelamento foi feito pelo CUIDADOR, o cliente sempre recebe 100% de reembolso.

---

## 3. Processamento de Pagamentos

### 3.1 Gerar Link de Pagamento

**Quando usar:** Para enviar link de pagamento ao cliente.

**Passo a passo:**

1. **Abra** a fatura com status "Em Aberto"
2. **Clique** em "Gerar Link de Pagamento"
3. **Selecione o método:**
   - **PIX** (recomendado): Pagamento instantâneo
   - **Boleto**: Vencimento em 3 dias úteis
   - **Cartão**: Checkout online
4. **O sistema gerará:**
   - Para PIX: QR Code + código copia-e-cola
   - Para Boleto: Código de barras + link
   - Para Cartão: Link de checkout
5. **Envie ao cliente** via WhatsApp (botão de envio rápido)

**Resultado esperado:** Link/código gerado e enviado ao cliente.

> 💡 **Dica:** PIX é o método preferencial - confirmação instantânea e sem taxas para o cliente.

---

### 3.2 Confirmar Pagamento Manual

**Quando usar:** Raramente necessário - apenas se o webhook não funcionou.

**Passo a passo:**

1. **Verifique** no extrato bancário/Stripe se o pagamento foi recebido
2. **Localize** a fatura correspondente
3. **Clique** em "Registrar Pagamento Manual"
4. **Informe:**
   - ID da transação no Stripe
   - Data/hora do pagamento
   - Motivo do registro manual
5. **Confirme** a operação

**Resultado esperado:** Fatura marcada como "Paga" e fluxo de repasse iniciado.

> ⚠️ **Atenção:** O registro manual gera alerta para auditoria. Use apenas quando realmente necessário.

---

### 3.3 Processar Reembolso

**Quando usar:** Quando precisa devolver valores ao cliente.

**Passo a passo:**

1. **Localize** o pagamento a ser reembolsado
2. **Clique** em "Processar Reembolso"
3. **Selecione o tipo:**
   - **Total**: Devolve 100% do valor
   - **Parcial**: Informe o valor a devolver
4. **Informe** o motivo do reembolso
5. **Verifique** se requer aprovação:
   - Até R$ 500: aprovação automática
   - Acima de R$ 500: requer aprovação do gestor
6. **Confirme** o reembolso

**Resultado esperado:**
- Reembolso processado via Stripe
- Cliente recebe o valor em 5-10 dias úteis (cartão) ou instantaneamente (PIX)

---

## 4. Gestão de Repasses aos Cuidadores

### 4.1 Entender o Ciclo de Repasse

O ciclo de repasse segue estas regras:

```
Serviço Concluído → Aguarda 3 dias → Entra na fila → Sexta-feira = Repasse
```

**Regras importantes:**
- Repasses são processados toda **sexta-feira**
- Mínimo de **R$ 50** para processar
- Liberação **3 dias** após conclusão do serviço
- Valores ficam acumulados até atingir o mínimo

---

### 4.2 Consultar Repasses de um Cuidador

**Passo a passo:**

1. **Acesse** o módulo de Repasses
2. **Busque** pelo nome ou ID do cuidador
3. **Visualize:**
   - Repasses pagos (histórico)
   - Repasses pendentes (aguardando processamento)
   - Próximo repasse previsto

**Resultado esperado:** Visão completa dos repasses do cuidador.

---

### 4.3 Gerar Repasses do Período

**Quando usar:** Na sexta-feira, para processar os repasses da semana.

**Passo a passo:**

1. **Acesse** o módulo de Repasses
2. **Clique** em "Gerar Repasses"
3. **Confirme** o período (sistema sugere última semana)
4. **Revise** a lista de repasses gerados:
   - Cuidador
   - Quantidade de serviços
   - Valor total
   - Valor do repasse (% do cuidador)
5. **Verifique** pendências:
   - Cuidadores sem conta bancária verificada
   - Valores abaixo do mínimo
6. **Confirme** a geração

**Resultado esperado:** Repasses criados com status "Em Aberto".

---

### 4.4 Processar Transferências

**Quando usar:** Após gerar os repasses, para efetuar as transferências.

**Passo a passo:**

1. **Acesse** a lista de repasses "Em Aberto"
2. **Clique** em "Processar Todos" (ou selecione individualmente)
3. **Revise** os valores totais
4. **Verifique** se há repasses acima de R$ 5.000:
   - Se sim: requer aprovação antes de processar
5. **Confirme** o processamento
6. **Aguarde** a confirmação do Stripe

**Resultado esperado:** 
- Transferências enviadas via Stripe Connect
- Cuidadores notificados via WhatsApp
- Repasses marcados como "Pagos"

> 💡 **Dica:** Processe os repasses sempre no mesmo horário para criar previsibilidade para os cuidadores.

---

### 4.5 Consultar Comissões

**Para verificar os percentuais aplicados:**

| Tipo de Serviço | Cuidador Recebe | Empresa Retém |
|-----------------|-----------------|---------------|
| Horista | 70% | 30% |
| Diário | 72% | 28% |
| Mensal | 75% | 25% |

**Bônus adicionais:**
- Avaliação ≥ 4.5: +2%
- 6+ meses de casa: +1%
- 12+ meses de casa: +2%
- 24+ meses de casa: +3%

**Exemplo:** Cuidador mensal, avaliação 4.8, 2 anos de casa = 75% + 2% + 3% = **80%**

---

## 5. Controle de Fluxo de Caixa

### 5.1 Consultar Saldo do Período

**Passo a passo:**

1. **Acesse** o módulo de Fluxo de Caixa
2. **Selecione** o período desejado
3. **Visualize:**
   - Total de entradas (recebimentos)
   - Total de saídas (repasses, taxas, despesas)
   - Saldo do período

**Resultado esperado:** Visão consolidada das movimentações financeiras.

---

### 5.2 Consultar Fluxo Diário

**Passo a passo:**

1. **Acesse** Fluxo de Caixa > Diário
2. **Selecione** o período
3. **Visualize** dia a dia:
   - Entradas do dia
   - Saídas do dia
   - Saldo do dia
   - Saldo acumulado

**Resultado esperado:** Visão detalhada para identificar dias com maior/menor movimentação.

---

### 5.3 Registrar Transação Manual

**Quando usar:** Para despesas ou receitas que não são automáticas.

**Passo a passo:**

1. **Acesse** Fluxo de Caixa > Nova Transação
2. **Selecione** o tipo:
   - **Entrada**: Receita extra, correção, etc.
   - **Saída**: Despesa operacional, taxa, etc.
3. **Preencha:**
   - Data da transação
   - Categoria (selecione da lista)
   - Descrição detalhada
   - Valor
   - Data de competência (se diferente)
4. **Confirme** o registro

**Resultado esperado:** Transação registrada no fluxo de caixa.

---

### 5.4 Consultar Previsão de Caixa

**Passo a passo:**

1. **Acesse** Fluxo de Caixa > Previsão
2. **Selecione** o período (ex: próximos 30 dias)
3. **Visualize:**
   - Recebimentos esperados (faturas a vencer)
   - Repasses previstos (estimativa)
   - Saldo projetado

**Resultado esperado:** Visão antecipada para planejamento financeiro.

> 💡 **Dica:** Use a previsão de caixa para identificar períodos de baixa liquidez.

---

## 6. Gestão de Contas a Pagar

### 6.1 Cadastrar Conta a Pagar

**Quando usar:** Para registrar despesas da empresa.

**Passo a passo:**

1. **Acesse** Contas a Pagar > Nova
2. **Preencha:**
   - Fornecedor/Beneficiário
   - Descrição da despesa
   - Valor
   - Data de vencimento
   - Categoria (Operacional, Administrativa, Impostos, etc.)
3. **Adicione** documentação (opcional):
   - Número da nota fiscal
   - Código de barras (se boleto)
4. **Confirme** o cadastro

**Resultado esperado:** Conta registrada com status "Em Aberto".

---

### 6.2 Pagar Conta

**Passo a passo:**

1. **Localize** a conta a pagar
2. **Verifique** se requer aprovação:
   - Até R$ 1.000: pode pagar diretamente
   - Acima de R$ 1.000: requer aprovação
3. **Clique** em "Registrar Pagamento"
4. **Informe:**
   - Data do pagamento
   - Valor pago (pode ter desconto ou juros)
   - Conta bancária utilizada
5. **Confirme** o pagamento

**Resultado esperado:** Conta marcada como "Paga" e registrada no fluxo de caixa.

---

## 7. Conciliação Mensal

### 7.1 Processar Conciliação

**Quando usar:** No início de cada mês, para fechar o mês anterior.

**Passo a passo:**

1. **Acesse** Conciliação > Processar
2. **Selecione** o mês a conciliar
3. **Aguarde** o processamento (pode levar alguns minutos)
4. **Revise** os resultados:
   - Total faturado
   - Total recebido
   - Total de repasses
   - Taxas e despesas
   - Saldo final
5. **Verifique** se há discrepâncias:
   - Faturas pagas sem pagamento correspondente
   - Pagamentos sem fatura
6. **Resolva** as discrepâncias (se houver)
7. **Feche** a conciliação

**Resultado esperado:** Mês fechado e conciliado.

> ⚠️ **Atenção:** Uma vez fechada, a conciliação não pode ser reaberta. Resolva todas as pendências antes de fechar.

---

### 7.2 Verificar Discrepâncias

**Passo a passo:**

1. **Acesse** Conciliação > Discrepâncias
2. **Análise** cada item:
   - **Fatura paga sem pagamento**: Verificar se webhook falhou
   - **Pagamento órfão**: Verificar se fatura foi deletada incorretamente
3. **Para cada discrepância:**
   - Investigue a causa
   - Registre a solução
   - Marque como resolvida
4. **Documente** as ações tomadas

**Resultado esperado:** Todas as discrepâncias resolvidas e documentadas.

---

## 8. Relatórios Gerenciais

### 8.1 DRE - Demonstrativo de Resultado

**Quando usar:** Para analisar o resultado financeiro de um período.

**Passo a passo:**

1. **Acesse** Relatórios > DRE
2. **Selecione** o período (mês, trimestre, ano)
3. **Gere** o relatório
4. **Análise** os resultados:

```
RECEITA BRUTA
  (+) Receita de Serviços
  (+) Taxas de Cancelamento
  (+) Juros e Multas
  
(-) DEDUÇÕES
  (-) Reembolsos

(=) RECEITA LÍQUIDA

(-) CUSTOS DOS SERVIÇOS
  (-) Repasses aos Cuidadores
  (-) Taxas de Gateway
  (-) Taxas de Transferência

(=) MARGEM BRUTA (meta: ≥25%)

(-) DESPESAS OPERACIONAIS

(=) RESULTADO OPERACIONAL
```

**Resultado esperado:** Visão clara da lucratividade do período.

> 💡 **Dica:** Compare o DRE mês a mês para identificar tendências.

---

### 8.2 Aging de Recebíveis

**Quando usar:** Para analisar o risco de inadimplência.

**Passo a passo:**

1. **Acesse** Relatórios > Aging
2. **Gere** o relatório
3. **Análise** por faixas de vencimento:

| Faixa | Significado | Ação Recomendada |
|-------|-------------|------------------|
| A Vencer | Faturas ainda não vencidas | Aguardar |
| 1-30 dias | Atraso inicial | Cobrança amigável |
| 31-60 dias | Atraso moderado | Cobrança ativa |
| 61-90 dias | Atraso severo | Notificação formal |
| > 90 dias | Alto risco | Avaliar baixa |

4. **Priorize** as cobranças pelos valores mais altos e mais antigos

**Resultado esperado:** Lista priorizada para ações de cobrança.

---

### 8.3 KPIs Financeiros

**Passo a passo:**

1. **Acesse** Relatórios > KPIs
2. **Selecione** o período
3. **Monitore** os indicadores:

| Indicador | Meta | O que fazer se não atingir |
|-----------|------|---------------------------|
| Margem Bruta | ≥ 25% | Revisar preços ou comissões |
| Inadimplência | ≤ 10% | Intensificar cobranças |
| Ticket Médio | Monitorar | Oferecer pacotes maiores |
| Prazo Recebimento | ≤ 3 dias | Incentivar PIX |

**Resultado esperado:** Visão rápida da saúde financeira.

---

## 9. Gestão de Aprovações

### 9.1 Visualizar Pendências

**Passo a passo:**

1. **Acesse** Aprovações > Pendentes
2. **Visualize** a lista de solicitações aguardando
3. **Para cada item, veja:**
   - Tipo de operação
   - Valor solicitado
   - Limite ultrapassado
   - Quem solicitou
   - Motivo informado

**Resultado esperado:** Lista de itens aguardando sua decisão.

---

### 9.2 Aprovar Solicitação

**Passo a passo:**

1. **Abra** a solicitação pendente
2. **Análise:**
   - O valor é justificável?
   - O motivo é válido?
   - Há histórico similar?
3. **Se aprovar:**
   - Clique em "Aprovar"
   - Adicione comentário (opcional)
4. **A operação será executada automaticamente**

**Resultado esperado:** Operação aprovada e executada.

---

### 9.3 Rejeitar Solicitação

**Passo a passo:**

1. **Abra** a solicitação pendente
2. **Se rejeitar:**
   - Clique em "Rejeitar"
   - **Informe o motivo** (obrigatório)
3. **O solicitante será notificado**

**Resultado esperado:** Operação rejeitada e solicitante informado.

> 💡 **Dica:** Seja claro no motivo da rejeição para evitar retrabalho.

---

### 9.4 Limites de Aprovação

| Operação | Limite Automático | Acima Requer |
|----------|-------------------|--------------|
| Desconto | Até 10% | Aprovação |
| Reembolso | Até R$ 500 | Aprovação |
| Repasse Individual | Até R$ 5.000 | Aprovação |
| Conta a Pagar | Até R$ 1.000 | Aprovação |

---

## 10. Provisões (PCLD)

### 10.1 O que é PCLD

A **Provisão para Créditos de Liquidação Duvidosa** é uma reserva financeira para cobrir perdas com inadimplência. O sistema calcula automaticamente baseado no aging dos recebíveis.

---

### 10.2 Calcular PCLD Mensal

**Quando usar:** No fechamento de cada mês.

**Passo a passo:**

1. **Acesse** Provisões > Calcular PCLD
2. **Selecione** o mês/ano
3. **Clique** em "Calcular"
4. **O sistema aplicará:**

| Faixa de Atraso | Provisão |
|-----------------|----------|
| 1-30 dias | 3% do valor |
| 31-60 dias | 10% do valor |
| 61-90 dias | 30% do valor |
| > 90 dias | 50% do valor |

5. **Revise** o valor calculado
6. **Confirme** a provisão

**Resultado esperado:** PCLD calculada e registrada para o mês.

---

### 10.3 Registrar Baixa (Perda Confirmada)

**Quando usar:** Quando confirmar que um valor não será recebido.

**Passo a passo:**

1. **Acesse** Provisões > Baixa
2. **Selecione** o período da provisão
3. **Informe:**
   - Valor da perda confirmada
   - Motivo da baixa
   - Documentação (se houver)
4. **Confirme** a baixa

**Resultado esperado:** Valor baixado contra a provisão.

---

## 11. Rotinas Diárias, Semanais e Mensais

### 📅 Rotina Diária

| Horário | Atividade | Tempo Estimado |
|---------|-----------|----------------|
| 09:00 | Verificar faturas vencidas | 10 min |
| 09:15 | Verificar aprovações pendentes | 5 min |
| 10:00 | Enviar lembretes de vencimento (automático) | - |
| 17:00 | Verificar pagamentos recebidos | 10 min |

**Checklist diário:**
- [ ] Há faturas vencidas sem cobrança?
- [ ] Há aprovações pendentes há mais de 24h?
- [ ] Todos os pagamentos foram confirmados?

---

### 📅 Rotina Semanal (Sexta-feira)

| Horário | Atividade | Tempo Estimado |
|---------|-----------|----------------|
| 09:00 | Gerar repasses do período | 15 min |
| 09:30 | Revisar e aprovar repasses | 20 min |
| 10:00 | Processar transferências | 10 min |
| 14:00 | Verificar Aging de recebíveis | 15 min |
| 14:30 | Executar cobranças prioritárias | 30 min |

**Checklist semanal:**
- [ ] Todos os repasses foram processados?
- [ ] Cuidadores com conta inválida foram notificados?
- [ ] Clientes com atraso > 7 dias foram cobrados?

---

### 📅 Rotina Mensal (Primeiros 5 dias úteis)

| Dia | Atividade | Tempo Estimado |
|-----|-----------|----------------|
| D+1 | Processar conciliação do mês anterior | 30 min |
| D+2 | Verificar e resolver discrepâncias | 1h |
| D+3 | Calcular PCLD | 15 min |
| D+3 | Gerar DRE | 15 min |
| D+4 | Analisar KPIs | 30 min |
| D+5 | Fechar conciliação | 15 min |

**Checklist mensal:**
- [ ] Conciliação fechada sem discrepâncias?
- [ ] PCLD calculada?
- [ ] DRE revisado e arquivado?
- [ ] KPIs dentro das metas?
- [ ] Relatório enviado à diretoria?

---

## Dicas de Produtividade

### Atalhos Úteis

| Ação | Como fazer rapidamente |
|------|------------------------|
| Buscar fatura | Digite o número no campo de busca global |
| Buscar cliente | Digite nome ou CPF no campo de busca |
| Gerar link PIX | Na fatura, clique no ícone de PIX |
| Ver histórico | Na fatura, clique em "Histórico" |

### Filtros Mais Usados

| Filtro | Quando usar |
|--------|-------------|
| Faturas vencidas | Para priorizar cobranças |
| Repasses pendentes | Para verificar processamento |
| Por cuidador | Para atender dúvidas específicas |
| Por período | Para relatórios |

### Boas Práticas

1. **Documente tudo**: Sempre preencha o campo de observações
2. **Não deixe para depois**: Resolva discrepâncias no mesmo dia
3. **Comunique-se**: Avise cuidadores sobre problemas de conta
4. **Monitore KPIs**: Verifique a margem toda semana
5. **Antecipe problemas**: Use a previsão de caixa

---

## Suporte

### Problemas Comuns

| Problema | Solução |
|----------|---------|
| Pagamento não confirmou | Verificar webhook, registrar manualmente se necessário |
| Repasse não processou | Verificar conta bancária do cuidador |
| Fatura com valor errado | Cancelar e criar nova (se não paga) |
| Desconto não aplicou | Verificar se solicitação de aprovação foi criada |

### Contatos

- **Suporte Técnico**: suporte@carinho.com.vc
- **Gestor Financeiro**: financeiro@carinho.com.vc
- **Emergências**: (11) XXXX-XXXX

---

*Manual elaborado para orientar as operações diárias do setor financeiro, seguindo as melhores práticas definidas pelo sistema Carinho Financeiro v2.0*
