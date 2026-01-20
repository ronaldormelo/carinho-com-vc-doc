# Estrutura de Dados

## Visao geral
Centraliza conversas, mensagens, status de atendimento e indicadores de SLA,
integrando WhatsApp e CRM.

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
- channel (enum: whatsapp, email)
- status (enum: new, triage, proposal, waiting, active, lost, closed)
- priority (enum: low, normal, high, urgent)
- assigned_to (bigint, nullable)
- started_at, closed_at
- created_at, updated_at

### messages
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- direction (enum: inbound, outbound)
- body (text)
- media_url (varchar, nullable)
- sent_at (datetime)
- status (enum: queued, sent, delivered, failed)

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
- role (enum: agent, supervisor, admin)
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

### incidents
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- severity (enum: low, medium, high, critical)
- notes (text)
- created_at, updated_at

### webhook_events
- id (bigint, pk)
- provider (varchar)
- event_type (varchar)
- payload_json (json)
- received_at (datetime)
- processed_at (datetime, nullable)
- status (enum: pending, processed, failed)

## Indices recomendados
- messages.conversation_id, messages.sent_at
- conversations.status, conversations.priority
- contacts.phone (unique)

## Observacoes de seguranca e desempenho
- Assinatura e validacao de webhooks do WhatsApp.
- Mascarar PII em logs e limitar acesso por perfil.
- Fila para envio de mensagens e retry com DLQ.
