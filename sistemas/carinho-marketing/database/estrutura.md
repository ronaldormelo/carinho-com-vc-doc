# Estrutura de Dados

## Visao geral
Organiza calendario editorial, campanhas, landing pages e eventos de
conversao, com registro de origem do lead.

## Tabelas principais

### marketing_channels
- id (bigint, pk)
- name (varchar)
- status (enum: active, inactive)

### social_accounts
- id (bigint, pk)
- channel_id (bigint, fk -> marketing_channels.id)
- handle (varchar)
- profile_url (varchar)
- status (enum: active, inactive)
- created_at, updated_at

### content_calendar
- id (bigint, pk)
- channel_id (bigint, fk -> marketing_channels.id)
- title (varchar)
- scheduled_at (datetime)
- status (enum: draft, scheduled, published, canceled)
- owner_id (bigint, nullable)
- created_at, updated_at

### content_assets
- id (bigint, pk)
- calendar_id (bigint, fk -> content_calendar.id)
- asset_type (enum: image, video, text)
- asset_url (varchar)
- caption (text)
- status (enum: draft, approved, published)
- created_at, updated_at

### campaigns
- id (bigint, pk)
- channel_id (bigint, fk -> marketing_channels.id)
- name (varchar)
- objective (varchar)
- budget (decimal)
- start_date, end_date
- status (enum: planned, active, paused, finished)
- created_at, updated_at

### ad_groups
- id (bigint, pk)
- campaign_id (bigint, fk -> campaigns.id)
- name (varchar)
- targeting_json (json)
- created_at, updated_at

### creatives
- id (bigint, pk)
- ad_group_id (bigint, fk -> ad_groups.id)
- creative_type (enum: image, video, text)
- headline (varchar)
- body (text)
- media_url (varchar, nullable)
- created_at, updated_at

### landing_pages
- id (bigint, pk)
- slug (varchar, unique)
- name (varchar)
- status (enum: draft, published, archived)
- utm_default_id (bigint, fk -> utm_links.id, nullable)
- created_at, updated_at

### utm_links
- id (bigint, pk)
- source (varchar)
- medium (varchar)
- campaign (varchar)
- content (varchar, nullable)
- term (varchar, nullable)
- created_at

### conversion_events
- id (bigint, pk)
- name (varchar)
- event_key (varchar)
- target_url (varchar)
- created_at

### campaign_metrics
- id (bigint, pk)
- campaign_id (bigint, fk -> campaigns.id)
- metric_date (date)
- impressions (int)
- clicks (int)
- spend (decimal)
- leads (int)

## Indices recomendados
- campaigns.channel_id, campaigns.status
- campaign_metrics.campaign_id, campaign_metrics.metric_date
- landing_pages.slug (unique)

## Observacoes de seguranca e desempenho
- Segredos de APIs devem ficar fora do banco (vault).
- Processamento de metricas em jobs assinc.
- Cache de relatorios por periodo e campanha.
