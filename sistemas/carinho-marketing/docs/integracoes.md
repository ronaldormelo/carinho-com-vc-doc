# Integracoes

Documentacao das integracoes externas e internas do sistema Carinho Marketing.

## Meta (Facebook/Instagram)

### Meta Marketing API
**Documentacao:** https://developers.facebook.com/docs/marketing-api/

**Funcionalidades:**
- Gestao de campanhas de anuncios
- Criacao de grupos de anuncios e criativos
- Sincronizacao de metricas (impressoes, cliques, gastos, conversoes)
- Gestao de audiencias

**Credenciais necessarias:**
- `META_APP_ID` - ID do aplicativo
- `META_APP_SECRET` - Secret do aplicativo
- `META_ACCESS_TOKEN` - Token de acesso (longa duracao)
- `META_AD_ACCOUNT_ID` - ID da conta de anuncios
- `META_PIXEL_ID` - ID do pixel de conversao

### Instagram Graph API
**Documentacao:** https://developers.facebook.com/docs/instagram-api/

**Funcionalidades:**
- Publicacao de posts (imagens, videos, carrosseis)
- Leitura de insights do perfil
- Gestao de comentarios
- Busca de hashtags

**Credenciais necessarias:**
- `INSTAGRAM_BUSINESS_ACCOUNT_ID` - ID da conta business

### Facebook Pages API
**Documentacao:** https://developers.facebook.com/docs/pages/

**Funcionalidades:**
- Publicacao de posts
- Agendamento de publicacoes
- Leitura de insights
- Gestao de comentarios

**Credenciais necessarias:**
- `META_PAGE_ID` - ID da pagina

### Conversions API (CAPI)
**Documentacao:** https://developers.facebook.com/docs/marketing-api/conversions-api/

**Funcionalidades:**
- Envio de eventos de conversao server-side
- Lead, Contact, CompleteRegistration, Purchase
- Matching avancado de usuarios

**Eventos implementados:**
- `Lead` - Captura de lead
- `Contact` - Contato via WhatsApp
- `CompleteRegistration` - Cadastro completo
- `PageView` - Visualizacao de pagina
- `ViewContent` - Visualizacao de conteudo

## Google

### Google Ads API
**Documentacao:** https://developers.google.com/google-ads/api/docs/start

**Funcionalidades:**
- Listagem e gestao de campanhas
- Sincronizacao de metricas
- Envio de conversoes offline
- Enhanced Conversions

**Credenciais necessarias:**
- `GOOGLE_ADS_DEVELOPER_TOKEN` - Token de desenvolvedor
- `GOOGLE_ADS_CLIENT_ID` - Client ID OAuth
- `GOOGLE_ADS_CLIENT_SECRET` - Client Secret OAuth
- `GOOGLE_ADS_REFRESH_TOKEN` - Refresh Token
- `GOOGLE_ADS_CUSTOMER_ID` - ID do cliente

### Google Analytics 4 (Measurement Protocol)
**Documentacao:** https://developers.google.com/analytics/devguides/collection/protocol/ga4

**Funcionalidades:**
- Envio de eventos server-side
- Rastreamento de conversoes
- Medicao cross-device

**Credenciais necessarias:**
- `GA_MEASUREMENT_ID` - ID de medicao (G-XXXXX)
- `GA_API_SECRET` - Secret da API

## WhatsApp

### Z-API
**Documentacao:** https://developer.z-api.io/

**Funcionalidades:**
- Envio de mensagens de texto
- Envio de imagens e documentos
- Envio de botoes e listas
- Recepcao de webhooks
- Verificacao de numeros

**Credenciais necessarias:**
- `ZAPI_INSTANCE_ID` - ID da instancia
- `ZAPI_TOKEN` - Token da instancia
- `ZAPI_CLIENT_TOKEN` - Token do cliente (opcional)
- `ZAPI_WEBHOOK_SECRET` - Secret para validacao de webhooks

**Endpoints principais:**
```
POST /instances/{instance}/token/{token}/send-text
POST /instances/{instance}/token/{token}/send-image
POST /instances/{instance}/token/{token}/send-document
POST /instances/{instance}/token/{token}/send-button-list
GET  /instances/{instance}/token/{token}/status
```

## Integracoes Internas

### CRM
**Base URL:** https://crm.carinho.com.vc/api

**Funcionalidades:**
- Envio de leads capturados
- Atualizacao de origem do lead
- Registro de conversoes
- Estatisticas de leads

**Endpoints:**
```
POST /leads - Envia novo lead
PUT  /leads/{id}/source - Atualiza origem
POST /leads/{id}/conversions - Registra conversao
GET  /leads/stats - Estatisticas
```

### Hub de Integracoes
**Base URL:** https://integracoes.carinho.com.vc/api

**Funcionalidades:**
- Disparo de eventos
- Sincronizacao entre sistemas
- Notificacoes

**Eventos disparados:**
- `lead.created` - Novo lead
- `conversion.registered` - Conversao registrada
- `campaign.activated` - Campanha ativada
- `content.published` - Conteudo publicado

### Site
**Base URL:** https://carinho.com.vc/api

**Funcionalidades:**
- Publicacao de landing pages
- Estatisticas de paginas
- Invalidacao de cache

**Endpoints:**
```
POST /landing-pages - Publica LP
PUT  /landing-pages/{slug} - Atualiza LP
DELETE /landing-pages/{slug} - Remove LP
GET  /landing-pages/{slug}/stats - Estatisticas
```

## Autenticacao

### Token Interno
Todas as APIs internas usam autenticacao via header:
```
X-Internal-Token: {INTERNAL_API_TOKEN}
```

### OAuth2 (Google)
Fluxo de refresh token para Google Ads API.

### Long-lived Token (Meta)
Token de acesso de longa duracao para Meta APIs.

## Webhooks

### Webhook WhatsApp (Z-API)
```
POST /api/webhooks/whatsapp/z-api
Header: X-Signature (HMAC-SHA256)
```

### Webhook Meta
```
GET/POST /api/webhooks/meta
Verificacao: hub_verify_token
```

### Webhook Conversao
```
POST /api/webhooks/conversion
Header: X-Internal-Token
```

## Tratamento de Erros

- Retry automatico com backoff exponencial
- Dead letter queue para falhas permanentes
- Logs estruturados de erros
- Alertas para falhas criticas

## Rate Limits

| API | Limite |
|-----|--------|
| Meta Marketing | 200 req/hora |
| Instagram Graph | 200 req/hora |
| Google Ads | 15000 req/dia |
| Z-API | 20 msg/segundo |
| APIs Internas | 60 req/minuto |
