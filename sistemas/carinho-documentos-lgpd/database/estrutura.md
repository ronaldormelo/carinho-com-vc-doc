# Estrutura de Dados

## Visao geral
Gerencia documentos, versoes, assinaturas e consentimentos LGPD com
auditoria completa.

## Tabelas principais

### document_templates
- id (bigint, pk)
- doc_type (enum: contrato_cliente, contrato_cuidador, termos, privacidade)
- version (varchar)
- content (text)
- active (bool)

### documents
- id (bigint, pk)
- owner_type (enum: client, caregiver, company)
- owner_id (bigint)
- template_id (bigint, fk -> document_templates.id)
- status (enum: draft, signed, archived)
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
- signer_type (enum: client, caregiver, company)
- signer_id (bigint)
- signed_at (datetime)
- method (enum: otp, click, certificate)
- ip_address (varchar)

### consents
- id (bigint, pk)
- subject_type (enum: client, caregiver)
- subject_id (bigint)
- consent_type (varchar)
- granted_at (datetime)
- source (varchar)
- revoked_at (datetime, nullable)

### access_logs
- id (bigint, pk)
- document_id (bigint, fk -> documents.id)
- actor_id (bigint)
- action (enum: view, download, sign, delete)
- ip_address (varchar)
- created_at

### retention_policies
- id (bigint, pk)
- doc_type (varchar)
- retention_days (int)

### data_requests
- id (bigint, pk)
- subject_type (enum: client, caregiver)
- subject_id (bigint)
- request_type (enum: export, delete, update)
- status (enum: open, in_progress, done, rejected)
- requested_at, resolved_at

## Indices recomendados
- documents.owner_type, documents.owner_id
- signatures.document_id, signatures.signed_at
- access_logs.document_id, access_logs.created_at

## Observacoes de seguranca e desempenho
- Criptografia de documentos e URLs assinadas com expiracao.
- Controle de acesso por perfil e necessidade.
- Retencao automatizada conforme politica LGPD.
