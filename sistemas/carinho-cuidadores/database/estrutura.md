# Estrutura de Dados

## Visao geral
Armazena cadastro, documentos, disponibilidade e desempenho dos
cuidadores, garantindo base confiavel e pesquisavel.

## Tabelas de dominio

### domain_caregiver_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_document_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_document_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_care_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_skill_level
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_contract_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### caregivers
- id (bigint, pk)
- name (varchar)
- phone (varchar)
- email (varchar, nullable)
- city (varchar)
- status_id (tinyint, fk -> domain_caregiver_status.id)
- experience_years (int)
- profile_summary (text, nullable)
- created_at, updated_at

### caregiver_documents
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- doc_type_id (tinyint, fk -> domain_document_type.id)
- file_url (varchar)
- status_id (tinyint, fk -> domain_document_status.id)
- verified_at (datetime, nullable)

### caregiver_skills
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- care_type_id (tinyint, fk -> domain_care_type.id)
- level_id (tinyint, fk -> domain_skill_level.id)

### caregiver_availability
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- day_of_week (tinyint)
- start_time (time)
- end_time (time)

### caregiver_regions
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- city (varchar)
- neighborhood (varchar, nullable)

### caregiver_contracts
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- contract_id (bigint)
- status_id (tinyint, fk -> domain_contract_status.id)
- signed_at (datetime, nullable)

### caregiver_ratings
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- service_id (bigint)
- score (tinyint)
- comment (text, nullable)
- created_at

### caregiver_incidents
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- service_id (bigint)
- incident_type (varchar)
- notes (text)
- occurred_at (datetime)

### caregiver_training
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- course_name (varchar)
- completed_at (datetime, nullable)

### caregiver_status_history
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- status_id (tinyint, fk -> domain_caregiver_status.id)
- changed_at (datetime)

## Indices recomendados
- caregivers.phone, caregivers.status_id
- caregiver_regions.city
- caregiver_availability.caregiver_id, caregiver_availability.day_of_week

## Observacoes de seguranca e desempenho
- Documentos armazenados com criptografia e URLs assinadas.
- Validacao de arquivos com antivirus e limite de tamanho.
- Cache de filtros por regiao e disponibilidade.
