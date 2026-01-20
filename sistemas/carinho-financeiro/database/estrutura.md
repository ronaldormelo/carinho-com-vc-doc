# Estrutura de Dados

## Visao geral
Controla cobranca, repasses, precificacao e documentos fiscais com
rastreabilidade e auditoria.

## Tabelas principais

### billing_accounts
- id (bigint, pk)
- client_id (bigint)
- payment_method (enum: pix, boleto, card)
- status (enum: active, inactive)
- created_at, updated_at

### invoices
- id (bigint, pk)
- client_id (bigint)
- contract_id (bigint)
- period_start, period_end
- status (enum: open, paid, overdue, canceled)
- total_amount (decimal)
- created_at, updated_at

### invoice_items
- id (bigint, pk)
- invoice_id (bigint, fk -> invoices.id)
- service_date (date)
- description (varchar)
- qty (decimal)
- unit_price (decimal)
- amount (decimal)

### payments
- id (bigint, pk)
- invoice_id (bigint, fk -> invoices.id)
- method (enum: pix, boleto, card)
- amount (decimal)
- status (enum: pending, paid, failed, refunded)
- paid_at (datetime, nullable)
- external_id (varchar, nullable)

### payouts
- id (bigint, pk)
- caregiver_id (bigint)
- period_start, period_end
- status (enum: open, paid, canceled)
- total_amount (decimal)
- created_at, updated_at

### payout_items
- id (bigint, pk)
- payout_id (bigint, fk -> payouts.id)
- service_id (bigint)
- amount (decimal)
- commission_percent (decimal)

### price_plans
- id (bigint, pk)
- name (varchar)
- service_type (enum: horista, diario, mensal)
- base_price (decimal)
- active (bool)

### price_rules
- id (bigint, pk)
- plan_id (bigint, fk -> price_plans.id)
- rule_type (varchar)
- value (decimal)
- conditions_json (json)

### bank_accounts
- id (bigint, pk)
- owner_type (enum: client, caregiver, company)
- owner_id (bigint)
- bank_name (varchar)
- account_hash (varchar)
- created_at, updated_at

### reconciliations
- id (bigint, pk)
- period (varchar)
- status (enum: open, closed)
- notes (text, nullable)

### fiscal_documents
- id (bigint, pk)
- invoice_id (bigint, fk -> invoices.id)
- doc_number (varchar)
- issued_at (datetime)
- file_url (varchar)

## Indices recomendados
- invoices.client_id, invoices.status
- payments.status, payments.paid_at
- payouts.caregiver_id, payouts.status

## Observacoes de seguranca e desempenho
- Dados financeiros sensiveis devem ser criptografados.
- Idempotencia em recebimentos e repasses.
- Jobs assinc. para conciliacao e emissao fiscal.
