# Estrutura de Dados

## Visao geral
Armazena conteudo do site, midias, formularios e registros de lead,
com foco em captacao e rastreio de origem.

## Tabelas principais

### site_pages
- id (bigint, pk)
- slug (varchar, unique)
- title (varchar)
- status (enum: draft, published, archived)
- seo_title (varchar)
- seo_description (varchar)
- content_json (json)
- published_at (datetime)
- created_at, updated_at, deleted_at

### page_sections
- id (bigint, pk)
- page_id (bigint, fk -> site_pages.id)
- type (varchar)
- content_json (json)
- sort_order (int)
- created_at, updated_at

### media_assets
- id (bigint, pk)
- file_name (varchar)
- mime_type (varchar)
- size_bytes (int)
- storage_path (varchar)
- checksum (varchar)
- created_at, updated_at

### lead_forms
- id (bigint, pk)
- name (varchar)
- target_type (enum: cliente, cuidador)
- fields_json (json)
- active (bool)
- created_at, updated_at

### form_submissions
- id (bigint, pk)
- form_id (bigint, fk -> lead_forms.id)
- utm_id (bigint, fk -> utm_campaigns.id)
- name (varchar)
- phone (varchar)
- email (varchar, nullable)
- city (varchar)
- urgency (enum: hoje, semana, sem_data)
- service_type (enum: horista, diario, mensal)
- consent_at (datetime)
- payload_json (json)
- created_at

### utm_campaigns
- id (bigint, pk)
- source (varchar)
- medium (varchar)
- campaign (varchar)
- content (varchar, nullable)
- term (varchar, nullable)
- created_at

### legal_documents
- id (bigint, pk)
- doc_type (enum: privacy, terms)
- version (varchar)
- content (text)
- published_at (datetime)

### site_settings
- id (bigint, pk)
- setting_key (varchar, unique)
- setting_value (text)
- updated_at

### redirects
- id (bigint, pk)
- from_path (varchar, unique)
- to_url (varchar)
- status_code (int)
- created_at, updated_at

## Indices recomendados
- site_pages.slug (unique)
- form_submissions.phone, form_submissions.created_at
- utm_campaigns.source, utm_campaigns.campaign

## Observacoes de seguranca e desempenho
- PII (phone, email) deve ser criptografada em repouso.
- Rate limiting e captcha nos formularios para evitar abuso.
- Cache de paginas publicas e CDN para midias.
