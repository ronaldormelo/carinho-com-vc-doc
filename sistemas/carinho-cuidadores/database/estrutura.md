# Estrutura de Dados

## Visao geral
Armazena cadastro, documentos, disponibilidade e desempenho dos
cuidadores, garantindo base confiavel e pesquisavel.

## Tabelas principais

### caregivers
- id (bigint, pk)
- name (varchar)
- phone (varchar)
- email (varchar, nullable)
- city (varchar)
- status (enum: pending, active, inactive, blocked)
- experience_years (int)
- profile_summary (text, nullable)
- created_at, updated_at

### caregiver_documents
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- doc_type (enum: id, cpf, address, certificate, other)
- file_url (varchar)
- status (enum: pending, verified, rejected)
- verified_at (datetime, nullable)

### caregiver_skills
- id (bigint, pk)
- caregiver_id (bigint, fk -> caregivers.id)
- care_type (enum: idoso, pcd, tea, pos_operatorio)
- level (enum: basico, intermediario, avancado)

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
- status (enum: draft, signed, active, closed)
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
- status (enum: pending, active, inactive, blocked)
- changed_at (datetime)

## Indices recomendados
- caregivers.phone, caregivers.status
- caregiver_regions.city
- caregiver_availability.caregiver_id, caregiver_availability.day_of_week

## Observacoes de seguranca e desempenho
- Documentos armazenados com criptografia e URLs assinadas.
- Validacao de arquivos com antivirus e limite de tamanho.
- Cache de filtros por regiao e disponibilidade.
