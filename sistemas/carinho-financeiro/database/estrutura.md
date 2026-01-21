# Estrutura de Dados

## Visao geral
Controla cobranca, repasses, precificacao e documentos fiscais com
rastreabilidade e auditoria.

## Tabelas de dominio

### domain_payment_method
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_account_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_invoice_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_payment_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_payout_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_service_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_owner_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_reconciliation_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### billing_accounts
- id (bigint, pk)
- client_id (bigint)
- payment_method_id (tinyint, fk -> domain_payment_method.id)
- status_id (tinyint, fk -> domain_account_status.id)
- created_at, updated_at

### invoices
- id (bigint, pk)
- client_id (bigint)
- contract_id (bigint)
- period_start, period_end
- status_id (tinyint, fk -> domain_invoice_status.id)
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
- method_id (tinyint, fk -> domain_payment_method.id)
- amount (decimal)
- status_id (tinyint, fk -> domain_payment_status.id)
- paid_at (datetime, nullable)
- external_id (varchar, nullable)

### payouts
- id (bigint, pk)
- caregiver_id (bigint)
- period_start, period_end
- status_id (tinyint, fk -> domain_payout_status.id)
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
- service_type_id (tinyint, fk -> domain_service_type.id)
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
- owner_type_id (tinyint, fk -> domain_owner_type.id)
- owner_id (bigint)
- bank_name (varchar)
- account_hash (varchar)
- created_at, updated_at

### reconciliations
- id (bigint, pk)
- period (varchar)
- status_id (tinyint, fk -> domain_reconciliation_status.id)
- notes (text, nullable)

### fiscal_documents
- id (bigint, pk)
- invoice_id (bigint, fk -> invoices.id)
- doc_number (varchar)
- issued_at (datetime)
- file_url (varchar)

## Indices recomendados
- invoices.client_id, invoices.status_id
- payments.status_id, payments.paid_at
- payouts.caregiver_id, payouts.status_id

## Observacoes de seguranca e desempenho
- Dados financeiros sensiveis devem ser criptografados.
- Idempotencia em recebimentos e repasses.
- Jobs assinc. para conciliacao e emissao fiscal.
