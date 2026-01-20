# Estrutura de Dados

## Visao geral
Gerencia demanda, alocacao, agenda e execucao do servico com rastreio
de check-in/out e ocorrencias.

## Tabelas principais

### service_requests
- id (bigint, pk)
- client_id (bigint)
- service_type (enum: horista, diario, mensal)
- urgency (enum: hoje, semana, sem_data)
- start_date, end_date
- status (enum: open, scheduled, active, completed, canceled)
- created_at, updated_at

### assignments
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- caregiver_id (bigint)
- status (enum: assigned, confirmed, replaced, canceled)
- assigned_at (datetime)

### schedules
- id (bigint, pk)
- assignment_id (bigint, fk -> assignments.id)
- caregiver_id (bigint)
- client_id (bigint)
- shift_date (date)
- start_time, end_time
- status (enum: planned, in_progress, done, missed)

### checklists
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- checklist_type (enum: start, end)
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
- check_type (enum: in, out)
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
- status (enum: queued, sent, failed)
- sent_at (datetime, nullable)

### emergencies
- id (bigint, pk)
- service_request_id (bigint, fk -> service_requests.id)
- severity (enum: low, medium, high, critical)
- description (text)
- resolved_at (datetime, nullable)

## Indices recomendados
- schedules.caregiver_id, schedules.shift_date
- schedules.client_id, schedules.shift_date
- service_requests.status, service_requests.start_date

## Observacoes de seguranca e desempenho
- Trilha de auditoria para alteracoes de agenda.
- Filas para notificacoes e check-in/out.
- Cache de disponibilidade e agenda por dia.
