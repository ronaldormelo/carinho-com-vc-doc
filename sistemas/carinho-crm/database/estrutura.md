# Estrutura de Dados

## Visao geral
Base unica de leads e clientes com pipeline comercial, contratos e
historico de interacoes. Inclui praticas tradicionais consolidadas:
- Classificacao ABC de clientes
- Responsavel financeiro separado
- Contato de emergencia (critico para HomeCare)
- Probabilidade de fechamento em deals
- Revisoes periodicas de clientes
- Alertas de renovacao configuraveis
- Historico de eventos padronizado
- Programa de indicacoes

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

### domain_client_classification (NOVO - Pratica tradicional ABC)
- id (tinyint, pk)
- code (varchar, unique) - A, B, C
- label (varchar)
- description (varchar, nullable)
- priority (tinyint) - ordem de priorizacao

### domain_event_type (NOVO - Historico padronizado)
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)
- category (varchar) - commercial, operational, financial, communication

### domain_review_frequency (NOVO - Revisoes periodicas)
- id (tinyint, pk)
- code (varchar, unique) - monthly, quarterly, etc
- label (varchar)
- days (smallint) - intervalo em dias

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
- phone (varchar) - criptografado
- address (varchar, nullable) - criptografado
- city (varchar)
- preferences_json (json)
- classification_id (tinyint, fk -> domain_client_classification.id) - NOVO
- financial_contact_name (varchar, nullable) - NOVO
- financial_contact_phone (text, nullable) - criptografado - NOVO
- financial_contact_email (text, nullable) - criptografado - NOVO
- financial_contact_cpf_cnpj (varchar, nullable) - NOVO
- emergency_contact_name (varchar, nullable) - NOVO
- emergency_contact_phone (text, nullable) - criptografado - NOVO
- emergency_contact_relationship (varchar, nullable) - NOVO
- review_frequency_id (tinyint, fk -> domain_review_frequency.id) - NOVO
- next_review_date (date, nullable) - NOVO
- last_review_date (date, nullable) - NOVO
- referred_by_client_id (bigint, fk -> clients.id, nullable) - NOVO
- referral_source (varchar, nullable) - NOVO
- internal_notes (text, nullable) - NOVO
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
- probability (tinyint) - % de probabilidade de fechamento - NOVO
- weighted_value (decimal) - valor * probabilidade (calculado) - NOVO
- expected_close_date (date, nullable) - data prevista de fechamento - NOVO
- next_action (varchar, nullable) - proximo passo - NOVO
- next_action_date (date, nullable) - data do proximo passo - NOVO
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
- renewal_alert_days (smallint) - dias de antecedencia para alerta - NOVO
- last_renewal_alert_at (date, nullable) - ultimo alerta enviado - NOVO
- auto_renewal (bool) - renovacao automatica - NOVO
- renewal_count (smallint) - numero de renovacoes - NOVO
- original_contract_id (bigint, fk -> contracts.id, nullable) - NOVO

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

### client_events (NOVO - Timeline estruturada)
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- event_type_id (tinyint, fk -> domain_event_type.id)
- title (varchar)
- description (text, nullable)
- metadata (json, nullable)
- related_id (bigint, nullable) - ID da entidade relacionada
- related_type (varchar, nullable) - tipo da entidade (deal, contract, etc)
- created_by (bigint, nullable)
- occurred_at (datetime)
- created_at, updated_at

### client_reviews (NOVO - Revisoes periodicas)
- id (bigint, pk)
- client_id (bigint, fk -> clients.id)
- reviewed_by (bigint, nullable)
- review_date (date)
- satisfaction_score (tinyint, nullable) - 1 a 5
- service_quality_score (tinyint, nullable) - 1 a 5
- contract_renewal_intent (bool, nullable) - intencao de renovar
- observations (text, nullable)
- action_items (text, nullable) - acoes identificadas
- next_review_date (date, nullable)
- created_at, updated_at

### client_referrals (NOVO - Programa de indicacao)
- id (bigint, pk)
- referrer_client_id (bigint, fk -> clients.id) - quem indicou
- referred_lead_id (bigint, fk -> leads.id, nullable)
- referred_client_id (bigint, fk -> clients.id, nullable)
- referred_name (varchar)
- referred_phone (varchar, nullable)
- status (varchar) - pending, contacted, converted, lost
- notes (text, nullable)
- converted_at (date, nullable)
- created_at, updated_at

## Indices recomendados
- leads.phone, leads.status_id, leads.city
- deals.stage_id, deals.status_id
- deals.expected_close_date, deals.probability - NOVO
- interactions.lead_id, interactions.occurred_at
- clients.classification_id - NOVO
- clients.next_review_date - NOVO
- client_events.client_id, client_events.occurred_at - NOVO
- client_events.event_type_id - NOVO
- client_reviews.client_id, client_reviews.review_date - NOVO
- client_referrals.referrer_client_id, client_referrals.status - NOVO

## Observacoes de seguranca e desempenho
- Criptografia de PII (phone, email, address).
- Novos campos criptografados: financial_contact_phone, financial_contact_email, emergency_contact_phone.
- Auditoria de alteracoes em contratos e leads.
- Cache de dashboards e relatorios.

## Praticas tradicionais implementadas

### Classificacao ABC de Clientes
- A: Alto valor/potencial - Prioridade maxima
- B: Valor medio - Atencao regular  
- C: Valor baixo - Atendimento padrao

### Probabilidade de Fechamento em Deals
- 10%: Baixa - Primeiro contato
- 25%: Media-baixa - Em qualificacao
- 50%: Media - Proposta em analise
- 75%: Media-alta - Negociacao final
- 90%: Alta - Fechamento iminente

### Frequencias de Revisao
- Mensal: 30 dias (recomendado para clientes A)
- Bimestral: 60 dias
- Trimestral: 90 dias (recomendado para clientes B)
- Semestral: 180 dias (recomendado para clientes C)
- Anual: 365 dias

### Alertas de Renovacao
- Configuraveis por contrato
- Padrao: 30 dias antes do vencimento
- Recomendacao baseada na duracao do contrato
