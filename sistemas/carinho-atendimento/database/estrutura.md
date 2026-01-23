# Estrutura de Dados

## Visao geral
Centraliza conversas, mensagens, status de atendimento e indicadores de SLA,
integrando WhatsApp e CRM. Inclui controles de escalonamento, triagem,
historico de acoes e pesquisa de satisfacao.

## Tabelas de dominio

### domain_channel
- id (tinyint, pk)
- code (varchar, unique): whatsapp, email, phone
- label (varchar)

### domain_conversation_status
- id (tinyint, pk)
- code (varchar, unique): new, triage, proposal, waiting, active, lost, closed
- label (varchar)

### domain_priority
- id (tinyint, pk)
- code (varchar, unique): low, normal, high, urgent
- label (varchar)

### domain_message_direction
- id (tinyint, pk)
- code (varchar, unique): inbound, outbound
- label (varchar)

### domain_message_status
- id (tinyint, pk)
- code (varchar, unique): queued, sent, delivered, failed
- label (varchar)

### domain_agent_role
- id (tinyint, pk)
- code (varchar, unique): agent, supervisor, admin
- label (varchar)

### domain_incident_severity
- id (tinyint, pk)
- code (varchar, unique): low, medium, high, critical
- label (varchar)

### domain_webhook_status
- id (tinyint, pk)
- code (varchar, unique): pending, processed, failed
- label (varchar)

### domain_support_level (NOVO)
- id (tinyint, pk)
- code (varchar, unique): n1, n2, n3
- label (varchar)
- escalation_minutes (int): tempo para escalonamento automatico

### domain_loss_reason (NOVO)
- id (tinyint, pk)
- code (varchar, unique): price, competitor, no_response, no_availability, region, requirements, postponed, other
- label (varchar)

### domain_incident_category (NOVO)
- id (tinyint, pk)
- code (varchar, unique): complaint, delay, quality, communication, billing, caregiver, emergency, suggestion, other
- label (varchar)

### domain_action_type (NOVO)
- id (tinyint, pk)
- code (varchar, unique): status_change, priority_change, assignment, escalation, note, tag, incident, closure
- label (varchar)

## Tabelas principais

### contacts
- id (bigint, pk)
- name (varchar)
- phone (varchar, unique)
- email (varchar, nullable)
- city (varchar, nullable)
- created_at, updated_at

### conversations
- id (bigint, pk)
- contact_id (bigint, fk -> contacts.id)
- channel_id (tinyint, fk -> domain_channel.id)
- status_id (tinyint, fk -> domain_conversation_status.id)
- priority_id (tinyint, fk -> domain_priority.id)
- support_level_id (tinyint, fk -> domain_support_level.id) (NOVO)
- assigned_to (bigint, nullable)
- loss_reason_id (tinyint, fk -> domain_loss_reason.id, nullable) (NOVO)
- loss_notes (text, nullable) (NOVO)
- started_at, closed_at
- created_at, updated_at

### messages
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- direction_id (tinyint, fk -> domain_message_direction.id)
- body (text)
- media_url (varchar, nullable)
- sent_at (datetime)
- status_id (tinyint, fk -> domain_message_status.id)

### tags
- id (bigint, pk)
- name (varchar, unique)

### conversation_tags
- conversation_id (bigint, fk -> conversations.id)
- tag_id (bigint, fk -> tags.id)

### agents
- id (bigint, pk)
- name (varchar)
- email (varchar, unique)
- role_id (tinyint, fk -> domain_agent_role.id)
- active (bool)
- created_at, updated_at

### message_templates
- id (bigint, pk)
- template_key (varchar, unique)
- body (text)
- language (varchar)
- created_at, updated_at

### auto_rules
- id (bigint, pk)
- trigger_key (varchar)
- template_id (bigint, fk -> message_templates.id)
- enabled (bool)
- created_at, updated_at

### sla_metrics
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- first_response_at (datetime)
- response_time_sec (int)
- resolved_at (datetime, nullable)

### sla_targets (NOVO)
- id (tinyint, pk)
- priority_id (tinyint, fk -> domain_priority.id)
- first_response_minutes (int)
- resolution_minutes (int)

### incidents
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- severity_id (tinyint, fk -> domain_incident_severity.id)
- category_id (tinyint, fk -> domain_incident_category.id) (NOVO)
- notes (text)
- resolution (text, nullable) (NOVO)
- resolved_at (datetime, nullable) (NOVO)
- resolved_by (bigint, fk -> agents.id, nullable) (NOVO)
- created_at, updated_at

### webhook_events
- id (bigint, pk)
- provider (varchar)
- event_type (varchar)
- payload_json (json)
- received_at (datetime)
- processed_at (datetime, nullable)
- status_id (tinyint, fk -> domain_webhook_status.id)

### conversation_history (NOVO)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- action_type_id (tinyint, fk -> domain_action_type.id)
- agent_id (bigint, fk -> agents.id, nullable)
- old_value (varchar, nullable)
- new_value (varchar, nullable)
- notes (text, nullable)
- created_at (datetime)

### triage_checklist (NOVO)
- id (bigint, pk)
- item_key (varchar)
- item_label (varchar)
- item_order (tinyint)
- required (bool)
- active (bool)

### conversation_triage (NOVO)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- checklist_id (bigint, fk -> triage_checklist.id)
- response (text, nullable)
- completed_at (datetime, nullable)
- completed_by (bigint, fk -> agents.id, nullable)

### holidays (NOVO)
- id (int, pk)
- date (date, unique)
- description (varchar)
- year_recurring (bool): feriado se repete todo ano

### satisfaction_surveys (NOVO)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- score (tinyint, nullable): 1-5
- feedback (text, nullable)
- sent_at (datetime)
- responded_at (datetime, nullable)

## Indices recomendados
- messages.conversation_id, messages.sent_at
- conversations.status_id, conversations.priority_id
- contacts.phone (unique)
- conversation_history.conversation_id, conversation_history.created_at
- conversation_triage.conversation_id
- holidays.date
- incidents.category_id, incidents.severity_id

## Observacoes de seguranca e desempenho
- Assinatura e validacao de webhooks do WhatsApp.
- Mascarar PII em logs e limitar acesso por perfil.
- Fila para envio de mensagens e retry com DLQ.
- Cache de 12h para tabelas de dominio via DomainLookup.
- Cache diario para lista de feriados.
