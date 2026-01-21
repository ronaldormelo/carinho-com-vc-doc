# Estrutura de Dados

## Visao geral
Base unica de leads e clientes com pipeline comercial, contratos e
historico de interacoes.

## Tabelas de dominio

### domain_urgency_level
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_service_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_lead_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_deal_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_contract_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_interaction_channel
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_patient_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_task_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### leads
- id (bigint, pk)
- name (varchar)
- phone (varchar)
- email (varchar, nullable)
- city (varchar)
- urgency_id (tinyint, fk -> domain_urgency_level.id)
- service_type_id (tinyint, fk -> domain_service_type.id)
- source (varchar)
- status_id (tinyint, fk -> domain_lead_status.id)
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
- patient_type_id (tinyint, fk -> domain_patient_type.id)
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
- status_id (tinyint, fk -> domain_deal_status.id)
- created_at, updated_at

### proposals
- id (bigint, pk)
- deal_id (bigint, fk -> deals.id)
- service_type_id (tinyint, fk -> domain_service_type.id)
- price (decimal)
- notes (text, nullable)
- expires_at (datetime, nullable)

### contracts
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- proposal_id (bigint, fk -> proposals.id)
- status_id (tinyint, fk -> domain_contract_status.id)
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
- status_id (tinyint, fk -> domain_task_status.id)
- notes (text, nullable)

### interactions
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- channel_id (tinyint, fk -> domain_interaction_channel.id)
- summary (text)
- occurred_at (datetime)

### loss_reasons
- id (bigint, pk)
- lead_id (bigint, fk -> leads.id)
- reason (varchar)
- details (text, nullable)

## Indices recomendados
- leads.phone, leads.status_id, leads.city
- deals.stage_id, deals.status_id
- interactions.lead_id, interactions.occurred_at

## Observacoes de seguranca e desempenho
- Criptografia de PII (phone, email, address).
- Auditoria de alteracoes em contratos e leads.
- Cache de dashboards e relatorios.
