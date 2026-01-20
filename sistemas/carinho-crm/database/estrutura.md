# Estrutura de Dados

## Visao geral
Base unica de leads e clientes com pipeline comercial, contratos e
historico de interacoes.

## Tabelas principais

### leads
- id (bigint, pk)
- name (varchar)
- phone (varchar)
- email (varchar, nullable)
- city (varchar)
- urgency (enum: hoje, semana, sem_data)
- service_type (enum: horista, diario, mensal)
- source (varchar)
- status (enum: new, triage, proposal, active, lost)
- utm_id (bigint, nullable)
- created_at, updated_at

### clients
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- primary_contact (varchar)
- phone (varchar)
- address (varchar, nullable)
- city (varchar)
- preferences_json (json)
- created_at, updated_at

### care_needs
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- patient_type (enum: idoso, pcd, tea, pos_operatorio)
- conditions_json (json)
- notes (text, nullable)

### pipeline_stages
- id (bigint, pk)
- name (varchar)
- stage_order (int)
- active (bool)

### deals
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- stage_id (bigint, fk -> pipeline_stages.id)
- value_estimated (decimal)
- status (enum: open, won, lost)
- created_at, updated_at

### proposals
- id (bigint, pk)
- deal_id (bigint, fk -> deals.id)
- service_type (enum: horista, diario, mensal)
- price (decimal)
- notes (text, nullable)
- expires_at (datetime, nullable)

### contracts
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- proposal_id (bigint, fk -> proposals.id)
- status (enum: draft, signed, active, closed)
- signed_at (datetime, nullable)
- start_date, end_date

### consents
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- consent_type (varchar)
- granted_at (datetime)
- source (varchar)

### tasks
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- assigned_to (bigint, nullable)
- due_at (datetime, nullable)
- status (enum: open, done, canceled)
- notes (text, nullable)

### interactions
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- channel (enum: whatsapp, email, phone)
- summary (text)
- occurred_at (datetime)

### loss_reasons
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- reason (varchar)
- details (text, nullable)

## Indices recomendados
- leads.phone, leads.status, leads.city
- deals.stage_id, deals.status
- interactions.lead_id, interactions.occurred_at

## Observacoes de seguranca e desempenho
- Criptografia de PII (phone, email, address).
- Auditoria de alteracoes em contratos e leads.
- Cache de dashboards e relatorios.
