# Carinho Marketing

**Subdominio:** marketing.carinho.com.vc

## Descricao
Sistema de presenca digital e aquisicao de leads. Organiza a comunicacao
nas redes sociais, campanhas e landing pages, garantindo captacao
consistente e rastreavel.

## Modulos Implementados

### 1. Gestao de Redes Sociais
- Cadastro e gestao de contas em redes sociais
- Padrao de bio com UTM integrado
- Sincronizacao de perfis com Instagram e Facebook
- Hashtags e mensagens padronizadas da marca

### 2. Calendario Editorial
- Criacao e agendamento de posts
- Gestao de assets (imagens, videos, textos)
- Publicacao automatica em Instagram e Facebook
- Workflow de aprovacao de conteudos

### 3. Gestao de Campanhas
- Campanhas de Meta Ads (Facebook/Instagram)
- Campanhas de Google Ads
- Grupos de anuncios e criativos
- Sincronizacao de metricas (impressoes, cliques, gastos, leads)
- Dashboard de performance

### 4. Landing Pages e UTM
- Gestao de landing pages
- Builder de links UTM
- URLs para WhatsApp com rastreamento
- Integracao com site principal

### 5. Conversoes e Rastreamento
- Facebook Conversions API
- Google Ads Conversions
- Google Analytics Measurement Protocol
- Registro de origem do lead
- Estatisticas por fonte/canal

### 6. Biblioteca de Marca
- Logos e icones
- Templates para posts e stories
- Paleta de cores e tipografia
- Tom de voz e mensagens-chave
- Temas de conteudo

## Integracoes Externas

### Meta (Facebook/Instagram)
- **Meta Marketing API** - Gestao de campanhas e anuncios
- **Instagram Graph API** - Publicacao de conteudo e insights
- **Facebook Pages API** - Gestao de pagina e posts
- **Conversions API** - Envio de eventos de conversao

### Google
- **Google Ads API** - Gestao de campanhas e conversoes
- **Google Analytics Data API** - Relatorios e metricas
- **Measurement Protocol GA4** - Envio de eventos

### WhatsApp
- **Z-API** - Envio de mensagens e webhooks

### Sistemas Internos
- **CRM** - Envio de leads e origem
- **Integracoes Hub** - Eventos e automacoes
- **Site** - Landing pages e formularios
- **Atendimento** - Notificacoes

## Stack Tecnologica
- **Linguagem:** PHP 8.2
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache/Filas:** Redis
- **Queue Worker:** Laravel Horizon

## Estrutura de Pastas

```
carinho-marketing/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Integrations/
│   │   ├── Google/
│   │   ├── Internal/
│   │   ├── Meta/
│   │   └── WhatsApp/
│   ├── Jobs/
│   ├── Models/
│   │   └── Domain/
│   ├── Providers/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── docs/
├── nginx/
├── public/
├── routes/
├── storage/
└── supervisor/
```

## Endpoints da API

### Health Check
- `GET /api/health` - Status do sistema

### Calendario Editorial
- `GET /api/calendar` - Lista itens do calendario
- `POST /api/calendar` - Cria item
- `POST /api/calendar/{id}/schedule` - Agenda publicacao
- `POST /api/calendar/{id}/publish` - Publica conteudo

### Campanhas
- `GET /api/campaigns` - Lista campanhas
- `GET /api/campaigns/dashboard` - Dashboard de performance
- `POST /api/campaigns` - Cria campanha
- `POST /api/campaigns/{id}/activate` - Ativa campanha
- `POST /api/campaigns/{id}/sync-metrics` - Sincroniza metricas

### Landing Pages
- `GET /api/landing-pages` - Lista landing pages
- `POST /api/landing-pages` - Cria landing page
- `POST /api/landing-pages/{id}/publish` - Publica

### UTM Builder
- `POST /api/utm/build` - Gera URL com UTM
- `POST /api/utm/build-whatsapp` - Gera URL WhatsApp

### Conversoes
- `POST /api/conversions/lead` - Registra lead
- `POST /api/conversions/contact` - Registra contato
- `GET /api/conversions/stats` - Estatisticas

### Contas Sociais
- `GET /api/social-accounts` - Lista contas
- `POST /api/social-accounts/{id}/sync-instagram` - Sincroniza Instagram

### Biblioteca de Marca
- `GET /api/brand/config` - Configuracoes de branding
- `GET /api/brand/colors` - Paleta de cores
- `GET /api/brand/assets` - Lista assets

### Webhooks
- `POST /api/webhooks/whatsapp/z-api` - Webhook Z-API
- `POST /api/webhooks/meta` - Webhook Meta
- `POST /api/webhooks/conversion` - Webhook de conversao

## Configuracao

### Variaveis de Ambiente
Copie o arquivo `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

### Principais Variaveis
- `INTERNAL_API_TOKEN` - Token para comunicacao entre sistemas
- `ZAPI_*` - Credenciais da Z-API (WhatsApp)
- `META_*` - Credenciais do Meta (Facebook/Instagram)
- `GOOGLE_ADS_*` - Credenciais do Google Ads
- `GA_*` - Credenciais do Google Analytics

### Docker

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## Jobs Assincronos

- `SyncCampaignMetrics` - Sincroniza metricas de campanhas
- `SyncAllCampaignsMetrics` - Sincroniza todas as campanhas ativas
- `PublishScheduledContent` - Publica conteudos agendados
- `SendConversionEvent` - Envia eventos de conversao
- `NotifyLeadCreated` - Notifica sobre novos leads

## Seguranca
- Autenticacao via token interno
- Rate limiting por endpoint
- Validacao de webhooks
- Secrets em variaveis de ambiente

## Monitoramento
- Health check em `/api/health`
- Logs estruturados
- Metricas de falha de integracao
- Alertas para erros de API
