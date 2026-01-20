# Estrutura de Dados

## Visao geral
Gerencia demanda, alocacao, agenda e execucao do servico com rastreio
de check-in/out e ocorrencias.

## Tabelas de dominio

### domain_service_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_urgency_level
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_service_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_assignment_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_schedule_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_checklist_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_check_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_notification_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_emergency_severity
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### service_requests
- id (bigint, pk)
- client_id (bigint)
- service_type_id (tinyint, fk -> domain_service_type.id)
- urgency_id (tinyint, fk -> domain_urgency_level.id)
- start_date, end_date
- status_id (tinyint, fk -> domain_service_status.id)
- created_at, updated_at

### assignments
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- caregiver_id (bigint)
- status_id (tinyint, fk -> domain_assignment_status.id)
- assigned_at (datetime)

### schedules
- id (bigint, pk)
- assignment_id (bigint, fk -> assignments.id)
- caregiver_id (bigint)
- client_id (bigint)
- shift_date (date)
- start_time, end_time
- status_id (tinyint, fk -> domain_schedule_status.id)

### checklists
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- checklist_type_id (tinyint, fk -> domain_checklist_type.id)
- template_json (json)

### checklist_entries
- id (bigint, pk)
- checklist_id (bigint, fk -> checklists.id)
- item_key (varchar)
- completed (bool)
- notes (text, nullable)

### checkins
- id (bigint, pk)
- schedule_id (bigint, fk -> schedules.id)
- check_type_id (tinyint, fk -> domain_check_type.id)
- timestamp (datetime)
- location (varchar, nullable)

### service_logs
- id (bigint, pk)
- schedule_id (bigint, fk -> schedules.id)
- activities_json (json)
- notes (text, nullable)
- created_at

### substitutions
- id (bigint, pk)
- assignment_id (bigint, fk -> assignments.id)
- old_caregiver_id (bigint)
- new_caregiver_id (bigint)
- reason (varchar)
- created_at

### notifications
- id (bigint, pk)
- client_id (bigint)
- schedule_id (bigint, nullable)
- notif_type (varchar)
- status_id (tinyint, fk -> domain_notification_status.id)
- sent_at (datetime, nullable)

### emergencies
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- severity_id (tinyint, fk -> domain_emergency_severity.id)
- description (text)
- resolved_at (datetime, nullable)

## Indices recomendados
- schedules.caregiver_id, schedules.shift_date
- schedules.client_id, schedules.shift_date
- service_requests.status_id, service_requests.start_date

## Observacoes de seguranca e desempenho
- Trilha de auditoria para alteracoes de agenda.
- Filas para notificacoes e check-in/out.
- Cache de disponibilidade e agenda por dia.
