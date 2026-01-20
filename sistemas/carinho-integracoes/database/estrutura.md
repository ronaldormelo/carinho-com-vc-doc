# Estrutura de Dados

## Visao geral
Camada de eventos e integracoes entre sistemas internos e externos,
com filas, retries e DLQ.

## Tabelas de dominio

### domain_api_key_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_endpoint_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_event_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_delivery_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_job_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### api_keys
- id (bigint, pk)
- name (varchar)
- key_hash (varchar)
- permissions_json (json)
- status_id (tinyint, fk -> domain_api_key_status.id)
- last_used_at (datetime, nullable)

### webhook_endpoints
- id (bigint, pk)
- system_name (varchar)
- url (varchar)
- secret (varchar)
- status_id (tinyint, fk -> domain_endpoint_status.id)
- created_at, updated_at

### integration_events
- id (bigint, pk)
- event_type (varchar)
- source_system (varchar)
- payload_json (json)
- status_id (tinyint, fk -> domain_event_status.id)
- created_at, updated_at

### event_mappings
- id (bigint, pk)
- event_type (varchar)
- target_system (varchar)
- mapping_json (json)
- version (varchar)

### webhook_deliveries
- id (bigint, pk)
- endpoint_id (bigint, fk -> webhook_endpoints.id)
- event_id (bigint, fk -> integration_events.id)
- status_id (tinyint, fk -> domain_delivery_status.id)
- attempts (int)
- last_attempt_at (datetime, nullable)
- response_code (int, nullable)

### retry_queue
- id (bigint, pk)
- event_id (bigint, fk -> integration_events.id)
- next_retry_at (datetime)
- attempts (int)

### dead_letter
- id (bigint, pk)
- event_id (bigint, fk -> integration_events.id)
- reason (text)
- created_at

### sync_jobs
- id (bigint, pk)
- job_type (varchar)
- status_id (tinyint, fk -> domain_job_status.id)
- started_at, finished_at

### rate_limits
- id (bigint, pk)
- client_id (bigint)
- window_start (datetime)
- count (int)

## Indices recomendados
- integration_events.status_id, integration_events.created_at
- webhook_deliveries.endpoint_id, webhook_deliveries.status_id
- retry_queue.next_retry_at

## Observacoes de seguranca e desempenho
- Assinatura e validacao de webhooks.
- Idempotencia por event_id para evitar duplicidade.
- Particionamento de eventos por data em alto volume.
