# Modulos

Descricao detalhada dos modulos do sistema Carinho Marketing.

## 1. Gestao de Redes Sociais

### Funcionalidades
- Cadastro de contas em redes sociais (Facebook, Instagram, etc.)
- Padronizacao de bio com UTM integrado
- Sincronizacao de perfis com APIs das plataformas
- Gestao de hashtags e mensagens da marca

### Endpoints
```
GET  /api/social-accounts - Lista contas
POST /api/social-accounts - Cria conta
GET  /api/social-accounts/{id} - Detalhes
PUT  /api/social-accounts/{id} - Atualiza
POST /api/social-accounts/{id}/sync-instagram - Sincroniza Instagram
POST /api/social-accounts/{id}/sync-facebook - Sincroniza Facebook
GET  /api/social-accounts/{id}/bio - Bio formatada
GET  /api/social-accounts/channels - Lista canais
```

### Models
- `MarketingChannel` - Canal de marketing
- `SocialAccount` - Conta em rede social

## 2. Calendario Editorial

### Funcionalidades
- Criacao e agendamento de posts
- Gestao de assets (imagens, videos, textos)
- Publicacao automatica em Instagram e Facebook
- Workflow de aprovacao de conteudos
- Estatisticas de publicacoes

### Endpoints
```
GET  /api/calendar - Lista por periodo
GET  /api/calendar/this-week - Itens da semana
POST /api/calendar - Cria item
GET  /api/calendar/{id} - Detalhes
PUT  /api/calendar/{id} - Atualiza
POST /api/calendar/{id}/schedule - Agenda
POST /api/calendar/{id}/cancel-schedule - Cancela agendamento
POST /api/calendar/{id}/publish - Publica
POST /api/calendar/{id}/assets - Adiciona asset
DELETE /api/calendar/{id}/assets/{assetId} - Remove asset
POST /api/calendar/assets/{assetId}/approve - Aprova asset
GET  /api/calendar/stats - Estatisticas
```

### Models
- `ContentCalendar` - Item do calendario
- `ContentAsset` - Asset de conteudo

### Status de Conteudo
1. `draft` - Rascunho
2. `scheduled` - Agendado
3. `published` - Publicado
4. `canceled` - Cancelado

## 3. Gestao de Campanhas

### Funcionalidades
- Criacao de campanhas de Meta Ads e Google Ads
- Gestao de grupos de anuncios e criativos
- Sincronizacao de metricas
- Dashboard de performance
- Calculo de KPIs (CTR, CPC, CPL)

### Endpoints
```
GET  /api/campaigns - Lista campanhas
GET  /api/campaigns/dashboard - Dashboard
POST /api/campaigns - Cria campanha
GET  /api/campaigns/{id} - Detalhes com metricas
PUT  /api/campaigns/{id} - Atualiza
POST /api/campaigns/{id}/activate - Ativa
POST /api/campaigns/{id}/pause - Pausa
POST /api/campaigns/{id}/finish - Finaliza
GET  /api/campaigns/{id}/metrics - Metricas agregadas
GET  /api/campaigns/{id}/metrics/daily - Metricas diarias
POST /api/campaigns/{id}/sync-metrics - Sincroniza metricas
POST /api/campaigns/{campaignId}/ad-groups - Cria grupo
PUT  /api/campaigns/ad-groups/{adGroupId} - Atualiza grupo
POST /api/campaigns/ad-groups/{adGroupId}/creatives - Cria criativo
```

### Models
- `Campaign` - Campanha
- `AdGroup` - Grupo de anuncios
- `Creative` - Criativo
- `CampaignMetric` - Metricas diarias

### Status de Campanha
1. `planned` - Planejada
2. `active` - Ativa
3. `paused` - Pausada
4. `finished` - Finalizada

## 4. Landing Pages e UTM

### Funcionalidades
- Gestao de landing pages
- Builder de links UTM
- Geracao de URLs para WhatsApp
- URLs para bio de redes sociais
- Integracao com site principal

### Endpoints (Landing Pages)
```
GET  /api/landing-pages - Lista
GET  /api/landing-pages/published - Apenas publicadas
POST /api/landing-pages - Cria
GET  /api/landing-pages/{id} - Detalhes
PUT  /api/landing-pages/{id} - Atualiza
POST /api/landing-pages/{id}/publish - Publica
POST /api/landing-pages/{id}/archive - Arquiva
POST /api/landing-pages/{id}/utm - Define UTM padrao
GET  /api/landing-pages/{id}/stats - Estatisticas
GET  /api/landing-pages/{id}/url - Gera URL
```

### Endpoints (UTM)
```
GET  /api/utm - Lista links
POST /api/utm - Cria link
GET  /api/utm/{id} - Detalhes
POST /api/utm/build - Gera URL
POST /api/utm/build-whatsapp - Gera URL WhatsApp
POST /api/utm/build-bio - Gera URL bio
POST /api/utm/build-campaign - Gera URL campanha
POST /api/utm/parse - Extrai UTM de URL
GET  /api/utm/sources - Sources disponiveis
GET  /api/utm/mediums - Mediums disponiveis
```

### Models
- `LandingPage` - Landing page
- `UtmLink` - Link UTM

## 5. Conversoes e Rastreamento

### Funcionalidades
- Registro de eventos de conversao
- Integracao com Facebook CAPI
- Integracao com Google Ads Conversions
- Integracao com Google Analytics
- Estatisticas por origem

### Endpoints
```
POST /api/conversions/lead - Registra lead
POST /api/conversions/contact - Registra contato
POST /api/conversions/registration - Registra cadastro
GET  /api/conversions/events - Lista eventos
POST /api/conversions/events - Cria evento
GET  /api/conversions/stats - Estatisticas
```

### Models
- `ConversionEvent` - Evento configurado
- `LeadSource` - Origem do lead

### Tipos de Conversao
- `Lead` - Captura de lead
- `Contact` - Contato via WhatsApp
- `CompleteRegistration` - Cadastro completo
- `InitiateCheckout` - Inicio de contratacao
- `Purchase` - Contratacao finalizada

## 6. Biblioteca de Marca

### Funcionalidades
- Gestao de logos e icones
- Templates para posts e stories
- Paleta de cores e tipografia
- Tom de voz e mensagens-chave
- Temas de conteudo
- Geracao de CSS de branding

### Endpoints
```
GET  /api/brand/config - Configuracoes completas
GET  /api/brand/colors - Paleta de cores
GET  /api/brand/typography - Tipografia
GET  /api/brand/voice - Tom de voz
GET  /api/brand/messages - Mensagens-chave
GET  /api/brand/hashtags - Hashtags
GET  /api/brand/social-bio - Bio padrao
GET  /api/brand/content-themes - Temas de conteudo
GET  /api/brand/css - CSS gerado
GET  /api/brand/assets - Lista assets
GET  /api/brand/assets/logos - Lista logos
GET  /api/brand/assets/logos/primary - Logo principal
GET  /api/brand/assets/templates - Lista templates
POST /api/brand/assets - Cria asset
GET  /api/brand/assets/{id} - Detalhes
PUT  /api/brand/assets/{id} - Atualiza
POST /api/brand/assets/{id}/activate - Ativa
POST /api/brand/assets/{id}/deactivate - Desativa
```

### Models
- `BrandAsset` - Ativo da marca

### Tipos de Assets
- `logo` - Logos
- `icon` - Icones
- `template` - Templates
- `typography` - Tipografia
- `color` - Cores
- `pattern` - Padroes

## Autenticacao

Todas as rotas protegidas requerem o header:
```
X-Internal-Token: {token}
```

Ou:
```
Authorization: Bearer {token}
```

## Rate Limiting

Limite padrao: 60 requisicoes por minuto por token.

Headers de resposta:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```
