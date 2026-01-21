# Estrutura de Dados

## Visao geral
Gerencia documentos, versoes, assinaturas e consentimentos LGPD com
auditoria completa.

## Tabelas de dominio

### domain_doc_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_owner_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_document_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_signer_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_signature_method
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_access_action
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_request_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_request_status
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

### domain_consent_subject_type
- id (tinyint, pk)
- code (varchar, unique)
- label (varchar)

## Tabelas principais

### document_templates
- id (bigint, pk)
- doc_type_id (tinyint, fk -> domain_doc_type.id)
- version (varchar)
- content (text)
- active (bool)

### documents
- id (bigint, pk)
- owner_type_id (tinyint, fk -> domain_owner_type.id)
- owner_id (bigint)
- template_id (bigint, fk -> document_templates.id)
- status_id (tinyint, fk -> domain_document_status.id)
- created_at, updated_at

### document_versions
- id (bigint, pk)
- document_id (bigint, fk -> documents.id)
- version (varchar)
- file_url (varchar)
- checksum (varchar)
- created_at

### signatures
- id (bigint, pk)
- document_id (bigint, fk -> documents.id)
- signer_type_id (tinyint, fk -> domain_signer_type.id)
- signer_id (bigint)
- signed_at (datetime)
- method_id (tinyint, fk -> domain_signature_method.id)
- ip_address (varchar)

### consents
- id (bigint, pk)
- subject_type_id (tinyint, fk -> domain_consent_subject_type.id)
- subject_id (bigint)
- consent_type (varchar)
- granted_at (datetime)
- source (varchar)
- revoked_at (datetime, nullable)

### access_logs
- id (bigint, pk)
- document_id (bigint, fk -> documents.id)
- actor_id (bigint)
- action_id (tinyint, fk -> domain_access_action.id)
- ip_address (varchar)
- created_at

### retention_policies
- id (bigint, pk)
- doc_type_id (tinyint, fk -> domain_doc_type.id)
- retention_days (int)

### data_requests
- id (bigint, pk)
- subject_type_id (tinyint, fk -> domain_consent_subject_type.id)
- subject_id (bigint)
- request_type_id (tinyint, fk -> domain_request_type.id)
- status_id (tinyint, fk -> domain_request_status.id)
- requested_at, resolved_at

## Indices recomendados
- documents.owner_type_id, documents.owner_id
- signatures.document_id, signatures.signed_at
- access_logs.document_id, access_logs.created_at

## Observacoes de seguranca e desempenho
- Criptografia de documentos e URLs assinadas com expiracao.
- Controle de acesso por perfil e necessidade.
- Retencao automatizada conforme politica LGPD.
