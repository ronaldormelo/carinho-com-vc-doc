# Arquitetura

## Visao geral
Sistema de gestao de marketing e captacao (marketing.carinho.com.vc).
Organiza calendario editorial, campanhas e landing pages, garantindo
registro de origem do lead e performance previsivel.

## Stack
- Linguagem: PHP 8.2
- Framework: Laravel 11
- Banco de dados: MySQL 8.0
- Cache e filas: Redis
- Workers: Laravel Horizon

## Componentes principais

### Controllers
- `ContentCalendarController` - Gestao do calendario editorial
- `CampaignController` - Gestao de campanhas de anuncios
- `LandingPageController` - Gestao de landing pages
- `UtmController` - Builder de links UTM
- `ConversionController` - Registro de conversoes
- `SocialAccountController` - Gestao de contas sociais
- `BrandLibraryController` - Biblioteca de marca
- `WebhookController` - Recepcao de webhooks externos

### Services
- `ContentCalendarService` - Logica de calendario e publicacao
- `CampaignService` - Logica de campanhas e metricas
- `LandingPageService` - Logica de landing pages
- `UtmBuilderService` - Construcao de links UTM
- `ConversionService` - Processamento de conversoes
- `SocialAccountService` - Sincronizacao de contas
- `BrandLibraryService` - Gestao de ativos da marca

### Integracoes Externas
- `Meta/MetaAdsClient` - Facebook/Instagram Ads API
- `Meta/InstagramClient` - Instagram Graph API
- `Meta/FacebookPageClient` - Facebook Pages API
- `Meta/ConversionApiClient` - Facebook Conversions API
- `Google/GoogleAdsClient` - Google Ads API
- `Google/GoogleAnalyticsClient` - GA4 Measurement Protocol
- `WhatsApp/ZApiClient` - Z-API para WhatsApp

### Integracoes Internas
- `Internal/CrmClient` - Sistema CRM
- `Internal/IntegracoesClient` - Hub de integracoes
- `Internal/SiteClient` - Site principal

### Jobs Assincronos
- `SyncCampaignMetrics` - Sincronizacao de metricas
- `SyncAllCampaignsMetrics` - Sincronizacao em lote
- `PublishScheduledContent` - Publicacao agendada
- `SendConversionEvent` - Envio de eventos
- `NotifyLeadCreated` - Notificacao de leads

## Fluxo de Dados

### Captacao de Lead
```
Landing Page -> Formulario -> ConversionController
    -> LeadSource (registro origem)
    -> ConversionService
        -> Facebook CAPI (evento Lead)
        -> Google Ads (conversao)
        -> Google Analytics (evento)
        -> CRM (envio lead)
        -> Integracoes Hub (evento)
```

### Publicacao de Conteudo
```
ContentCalendar (agendado) -> PublishScheduledContent Job
    -> ContentCalendarService.publish()
        -> Instagram/Facebook API (publicacao)
        -> Atualizacao de status
        -> Integracoes Hub (evento)
```

### Sincronizacao de Metricas
```
Scheduler (diario) -> SyncAllCampaignsMetrics Job
    -> SyncCampaignMetrics Job (por campanha)
        -> Meta Ads API / Google Ads API
        -> CampaignMetric (persistencia)
```

## Dados e armazenamento

### Tabelas Principais
- `marketing_channels` - Canais de marketing
- `social_accounts` - Contas em redes sociais
- `content_calendar` - Calendario editorial
- `content_assets` - Assets de conteudo
- `campaigns` - Campanhas de anuncios
- `ad_groups` - Grupos de anuncios
- `creatives` - Criativos
- `utm_links` - Links UTM
- `landing_pages` - Landing pages
- `conversion_events` - Eventos de conversao
- `campaign_metrics` - Metricas de campanha
- `brand_assets` - Ativos da marca
- `lead_sources` - Origem de leads

### Tabelas de Dominio
- `domain_channel_status` - Status de canais
- `domain_content_status` - Status de conteudo
- `domain_asset_status` - Status de assets
- `domain_campaign_status` - Status de campanhas
- `domain_creative_type` - Tipos de criativos
- `domain_landing_status` - Status de landing pages

## Seguranca e LGPD
- Controle de acesso por token interno
- Rate limiting por endpoint
- Validacao de webhooks externos
- Secrets de APIs em variaveis de ambiente
- Hash de dados sensiveis para APIs de conversao
- Logs de alteracao de campanhas e criativos
- Retencao de dados alinhada a LGPD

## Escalabilidade e desempenho
- Processamento assincrono para sincronizacao de metricas
- Cache de relatorios e dashboards (Redis)
- Workers dedicados para filas
- Indices otimizados por periodo, canal e campanha
- Rate limiting para protecao de APIs

## Observabilidade e operacao
- Health check em `/api/health`
- Logs estruturados em JSON
- Metricas de falha de integracao
- Alertas para queda de conversao e erros de API
- Supervisor para gestao de workers

## Backup e resiliencia
- Backup diario de banco de dados
- Fila com retry e backoff para falhas
- Dead letter queue para jobs falhados
- Timeout configuravel por integracao
