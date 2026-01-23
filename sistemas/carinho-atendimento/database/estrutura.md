# Estrutura de Dados

## Visão Geral
Centraliza conversas, mensagens, status de atendimento, indicadores de SLA,
níveis de suporte, triagem, scripts e auditoria, integrando WhatsApp e CRM.

## Tabelas de Domínio

### domain_channel
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_conversation_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_priority
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_message_direction
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_message_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_agent_role
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_incident_severity
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_webhook_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_support_level (NOVO)
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)
- description (varchar, nullable)
- max_response_minutes (int)
- max_resolution_minutes (int)

### domain_loss_reason (NOVO)
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)
- requires_notes (bool)

### domain_script_category (NOVO)
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_action_type (NOVO)
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas Principais

### contacts
- id (bigint, pk)
- name (varchar)
- phone (varchar, unique)
- email (varchar, nullable)
- city (varchar, nullable)
- created_at, updated_at

### agents
- id (bigint, pk)
- name (varchar)
- email (varchar, unique)
- role_id (tinyint, fk -> domain_agent_role.id)
- support_level_id (tinyint, fk -> domain_support_level.id) (NOVO)
- max_concurrent_conversations (int, default 5) (NOVO)
- active (bool)
- created_at, updated_at

### conversations
- id (bigint, pk)
- contact_id (bigint, fk -> contacts.id)
- channel_id (tinyint, fk -> domain_channel.id)
- status_id (tinyint, fk -> domain_conversation_status.id)
- priority_id (tinyint, fk -> domain_priority.id)
- support_level_id (tinyint, fk -> domain_support_level.id, default 1) (NOVO)
- assigned_to (bigint, fk -> agents.id, nullable)
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
- severity_id (tinyint, fk -> domain_incident_severity.id)
- notes (text)
- created_at, updated_at

### webhook_events
- id (bigint, pk)
- provider (varchar)
- event_type (varchar)
- payload_json (json)
- received_at (datetime)
- processed_at (datetime, nullable)
- status_id (tinyint, fk -> domain_webhook_status.id)

## Tabelas Novas - Gestão Operacional

### conversation_actions (Auditoria)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- action_type_id (tinyint, fk -> domain_action_type.id)
- agent_id (bigint, fk -> agents.id, nullable)
- old_value (varchar, nullable)
- new_value (varchar, nullable)
- notes (text, nullable)
- created_at

### triage_checklist_items (Checklist de Triagem)
- id (bigint, pk)
- code (varchar, unique)
- question (text)
- field_type (varchar)
- options_json (json, nullable)
- is_required (bool)
- display_order (int)
- active (bool)
- created_at, updated_at

### conversation_triage (Respostas da Triagem)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- checklist_item_id (bigint, fk -> triage_checklist_items.id)
- answer (text, nullable)
- answered_by (bigint, fk -> agents.id, nullable)
- created_at

### communication_scripts (Scripts Padronizados)
- id (bigint, pk)
- code (varchar, unique)
- title (varchar)
- category_id (tinyint, fk -> domain_script_category.id)
- support_level_id (tinyint, fk -> domain_support_level.id, nullable)
- body (text)
- variables_json (json, nullable)
- usage_hint (text, nullable)
- display_order (int)
- active (bool)
- created_at, updated_at

### sla_configurations (Configurações de SLA)
- id (bigint, pk)
- priority_id (tinyint, fk -> domain_priority.id)
- support_level_id (tinyint, fk -> domain_support_level.id)
- max_first_response_minutes (int)
- max_resolution_minutes (int)
- warning_threshold_percent (int, default 80)
- active (bool)
- created_at, updated_at
- unique(priority_id, support_level_id)

### sla_alerts (Alertas de SLA)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- alert_type (varchar)
- threshold_minutes (int)
- actual_minutes (int)
- notified_at (datetime, nullable)
- acknowledged_by (bigint, fk -> agents.id, nullable)
- acknowledged_at (datetime, nullable)
- created_at

### conversation_notes (Notas Internas)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- agent_id (bigint, fk -> agents.id)
- content (text)
- is_private (bool, default true)
- created_at

### escalation_history (Histórico de Escalonamentos)
- id (bigint, pk)
- conversation_id (bigint, fk -> conversations.id)
- from_level_id (tinyint, fk -> domain_support_level.id)
- to_level_id (tinyint, fk -> domain_support_level.id)
- from_agent_id (bigint, fk -> agents.id, nullable)
- to_agent_id (bigint, fk -> agents.id, nullable)
- reason (text, nullable)
- escalated_at (datetime)

## Índices Recomendados
- messages(conversation_id, sent_at)
- conversations(status_id, priority_id)
- conversations(support_level_id)
- contacts(phone) - unique
- conversation_actions(conversation_id, created_at)
- sla_alerts(conversation_id, created_at)
- escalation_history(conversation_id, escalated_at)

## Observações de Segurança e Desempenho
- Assinatura e validação de webhooks do WhatsApp
- Mascarar PII em logs e limitar acesso por perfil
- Fila para envio de mensagens e retry com DLQ
- Cache de configurações de SLA e domínios
- Alertas automáticos para violações de SLA
