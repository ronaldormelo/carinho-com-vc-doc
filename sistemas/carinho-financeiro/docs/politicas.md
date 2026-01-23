# Políticas Financeiras

Documento detalhado das políticas financeiras do sistema Carinho Financeiro.

## 1. Política de Pagamento

### Tipo de Cobrança: Pré-pago (Adiantado)

O modelo de cobrança do Carinho com Você é **sempre adiantado**, ou seja, o cliente deve efetuar o pagamento **antes** do serviço ser prestado.

**Motivos:**
- Garante segurança financeira para a operação
- Evita inadimplência
- Confirma o compromisso do cliente
- Permite planejamento do cuidador

### Prazos

| Configuração | Valor Padrão | Variável de Ambiente |
|--------------|--------------|----------------------|
| Antecedência mínima do pagamento | 24 horas | `PAYMENT_ADVANCE_HOURS` |
| Dias de tolerância após vencimento | 0 dias | `PAYMENT_GRACE_DAYS` |

### Encargos por Atraso

Caso o cliente não pague até o vencimento e ainda assim queira realizar o serviço:

| Encargo | Valor | Variável |
|---------|-------|----------|
| Juros por dia | 0,033% | `PAYMENT_LATE_FEE_DAILY` |
| Multa fixa | 2% | `PAYMENT_LATE_PENALTY` |

**Cálculo:**
```
Valor Final = Valor Original + (Valor × Juros × Dias) + (Valor × Multa)
```

---

## 2. Política de Cancelamento

### Tabela de Reembolso

| Antecedência do Cancelamento | Reembolso | Observação |
|------------------------------|-----------|------------|
| Mais de 24 horas | 100% | Cancelamento gratuito |
| Entre 6 e 24 horas | 50% | Reembolso parcial |
| Menos de 6 horas | 0% | Sem direito a reembolso |

### Configurações

| Parâmetro | Valor | Variável |
|-----------|-------|----------|
| Cancelamento gratuito até | 24h antes | `CANCELLATION_FREE_HOURS` |
| Reembolso parcial até | 12h antes | `CANCELLATION_PARTIAL_HOURS` |
| Percentual do reembolso parcial | 50% | `CANCELLATION_PARTIAL_PERCENT` |
| Sem reembolso se menos de | 6h antes | `CANCELLATION_NO_REFUND_HOURS` |
| Taxa administrativa | 5% | `CANCELLATION_ADMIN_FEE` |

### Taxa Administrativa

Aplica-se uma taxa de **5%** sobre o valor do reembolso parcial para cobrir custos operacionais.

**Exemplo:**
- Valor do serviço: R$ 200,00
- Cancelamento com 10h de antecedência
- Reembolso parcial: 50% = R$ 100,00
- Taxa administrativa: 5% de R$ 100,00 = R$ 5,00
- Valor final do reembolso: R$ 95,00

### Cancelamento pelo Cuidador

Quando o cancelamento parte do cuidador:
- Cliente recebe **reembolso total** (100%)
- Sem taxa administrativa
- Empresa assume o prejuízo

---

## 3. Política de Comissões

### Divisão de Valores

O valor cobrado do cliente é dividido entre o cuidador e a empresa:

| Tipo de Serviço | Cuidador | Empresa | Variável |
|-----------------|----------|---------|----------|
| Horista | 70% | 30% | `COMMISSION_HORISTA_CAREGIVER` |
| Diário | 72% | 28% | `COMMISSION_DIARIO_CAREGIVER` |
| Mensal | 75% | 25% | `COMMISSION_MENSAL_CAREGIVER` |

### Bônus por Avaliação

Cuidadores com avaliação média ≥ 4.5 recebem:
- **+2%** no percentual de comissão

### Bônus por Tempo de Casa

| Tempo | Bônus |
|-------|-------|
| 6 meses | +1% |
| 12 meses | +2% |
| 24 meses | +3% |

**Exemplo Máximo:**
- Cuidador com contrato mensal (75%)
- Avaliação 4.8 (+2%)
- 2 anos de casa (+3%)
- Total: 80% para o cuidador

---

## 4. Política de Repasses

### Frequência

| Configuração | Valor | Variável |
|--------------|-------|----------|
| Frequência | Semanal | `PAYOUT_FREQUENCY` |
| Dia da semana | Sexta-feira (5) | `PAYOUT_DAY` |

### Regras

| Regra | Valor | Variável |
|-------|-------|----------|
| Valor mínimo para repasse | R$ 50,00 | `PAYOUT_MINIMUM` |
| Dias após serviço para liberar | 3 dias | `PAYOUT_RELEASE_DAYS` |
| Taxa de transferência PIX | R$ 0,00 | `PAYOUT_PIX_FEE` |

### Processo

1. Serviço é concluído
2. Aguarda período de liberação (3 dias)
3. Na sexta-feira, sistema gera repasse
4. Se valor ≥ R$ 50,00, processa transferência
5. Valor transferido via Stripe Connect para conta do cuidador
6. Cuidador notificado via WhatsApp

---

## 5. Política de Precificação

### Preços Base

| Tipo | Preço | Variável |
|------|-------|----------|
| Hora (mínimo) | R$ 35,00 | `PRICING_MIN_HOURLY` |
| Hora (padrão) | R$ 50,00 | `PRICING_HORISTA_HOUR` |
| Diária | R$ 300,00 | `PRICING_DIARIO_DAY` |
| Mensal | R$ 6.000,00 | `PRICING_MENSAL_MONTH` |

### Mínimo por Atendimento

- Horista: mínimo de 4 horas
- Diário: 12 horas
- Mensal: 5 dias/semana, 8h/dia

### Adicionais

| Adicional | Percentual | Variável |
|-----------|------------|----------|
| Noturno (22h-6h) | +20% | `PRICING_NIGHT_SURCHARGE` |
| Fim de semana | +30% | `PRICING_WEEKEND_SURCHARGE` |
| Feriado | +50% | `PRICING_HOLIDAY_SURCHARGE` |

### Descontos

| Desconto | Percentual | Variável |
|----------|------------|----------|
| Pacote mensal | -10% | `PRICING_MONTHLY_DISCOUNT` |

---

## 6. Política de Margem

### Metas

| Indicador | Valor | Variável |
|-----------|-------|----------|
| Margem mínima | 25% | `MARGIN_MINIMUM` |
| Margem alvo | 30% | `MARGIN_TARGET` |
| Alerta se abaixo de | 20% | `MARGIN_ALERT` |

### Cálculo de Preço Mínimo Viável

```
Preço Mínimo = Custo do Cuidador / (1 - Margem Alvo)
```

**Exemplo:**
- Custo do cuidador: R$ 35/hora
- Margem alvo: 30%
- Preço mínimo: R$ 35 / 0,70 = R$ 50/hora

---

## 7. Política de Inadimplência

### Limites

| Configuração | Valor | Variável |
|--------------|-------|----------|
| Crédito inicial PF | R$ 0,00 | `LIMIT_CREDIT_PF` |
| Crédito inicial PJ | R$ 0,00 | `LIMIT_CREDIT_PJ` |
| Bloqueio após dias em atraso | 7 dias | `LIMIT_BLOCK_DAYS` |
| Valor máximo tolerado | R$ 500,00 | `LIMIT_MAX_OVERDUE` |

### Fluxo de Cobrança

1. **D+0:** Fatura vence
2. **D+1:** Notificação de atraso via WhatsApp
3. **D+3:** Segunda notificação
4. **D+7:** Bloqueio do cliente (sem novos agendamentos)
5. **D+15:** Tentativa de cobrança ativa
6. **D+30:** Avaliação para negativação

---

## Histórico de Alterações

| Data | Versão | Alteração |
|------|--------|-----------|
| 2026-01-22 | 1.0 | Documento inicial |
