# Carinho Site

**Subdominio:** site.carinho.com.vc

## Descricao

Portal institucional do projeto de home care Carinho com Voce. Apresenta a proposta de valor, explica os servicos e capta leads, direcionando o contato para o WhatsApp como canal principal.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache e filas:** Redis
- **Storage de midias:** S3 compativel
- **CDN para ativos estaticos**

## Modulos Implementados

### 1. Paginas Institucionais
- Home com hero, features e depoimentos
- Quem Somos (missao, valores, diferenciais)
- Servicos (horista, diario, mensal)
- Como Funciona (passo a passo)
- Contato (canais e horarios)
- FAQ (perguntas frequentes)

### 2. Paginas por Publico
- Para Clientes: formulario de solicitacao de cuidador
- Para Cuidadores: cadastro para trabalhar na plataforma

### 3. Formularios de Cadastro
- Formulario de lead cliente com validacao
- Formulario de lead cuidador com validacao
- Integracao com reCAPTCHA v3
- Sincronizacao automatica com CRM

### 4. Paginas Legais (Politicas)
- **Politica de Privacidade:** conformidade LGPD, direitos do titular
- **Termos de Uso:** regras de utilizacao dos servicos
- **Politica de Cancelamento:** prazos e reembolsos
- **Politica de Pagamento:** pagamento adiantado, formas aceitas
- **Politica de Emergencias:** canais, SLA, procedimentos
- **Termos para Cuidadores:** comissoes, obrigacoes, repasses

### 5. SEO e Analytics
- Meta tags otimizadas para SEO
- Schema.org JSON-LD para LocalBusiness
- Integracao com Google Analytics 4
- Integracao com Google Tag Manager
- Rastreamento de UTM em toda a navegacao

### 6. CTA para WhatsApp
- Botao flutuante em todas as paginas
- Links com mensagem pre-definida
- Rastreamento de origem (UTM)

### 7. Integracao com CRM
- Envio automatico de leads via webhook
- Sincronizacao de UTM e origem
- Retry automatico em caso de falha

## Politicas Definidas

### Pagamento
- **Tipo:** Sempre ADIANTADO (pre-pago)
- **Prazo:** 24 horas antes do servico
- **Formas:** PIX, boleto, cartao de credito
- **Juros por atraso:** 0,033% ao dia
- **Multa por atraso:** 2%

### Cancelamento
| Prazo | Reembolso |
|-------|-----------|
| Mais de 24h antes | 100% |
| Entre 6h e 24h | 50% |
| Menos de 6h | 0% |

### Comissoes do Cuidador
| Tipo de Servico | Percentual |
|-----------------|------------|
| Horista | 70% |
| Diario | 72% |
| Mensal | 75% |

**Bonus:** Ate +2% por avaliacao, +3% por tempo de casa

### Repasses
- **Frequencia:** Semanal (sextas-feiras)
- **Valor minimo:** R$ 50,00
- **Liberacao:** 3 dias apos conclusao

### Emergencias
| Nivel | Tempo de Resposta |
|-------|-------------------|
| Critico | 15 minutos |
| Alto | 30 minutos |
| Medio | 2 horas |

## Estrutura do Projeto

```
carinho-site/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # Controllers da API
│   │   │   ├── PageController    # Paginas institucionais
│   │   │   ├── LegalController   # Paginas legais
│   │   │   ├── LeadFormController # Formularios
│   │   │   └── HealthController  # Health checks
│   │   ├── Middleware/
│   │   │   ├── TrackUtm          # Rastreamento UTM
│   │   │   ├── VerifyInternalToken
│   │   │   └── RateLimitMiddleware
│   │   └── Requests/             # Form Requests
│   ├── Jobs/
│   │   ├── SyncLeadToCrm         # Sincroniza com CRM
│   │   └── SendLeadNotification  # Notificacoes
│   ├── Models/
│   │   ├── Domain/               # Valores de referencia
│   │   ├── SitePage              # Paginas
│   │   ├── FormSubmission        # Leads
│   │   ├── UtmCampaign           # Campanhas UTM
│   │   ├── LegalDocument         # Documentos legais
│   │   ├── FaqCategory/Item      # FAQ
│   │   └── Testimonial           # Depoimentos
│   ├── Providers/
│   └── Services/
│       ├── CrmClient             # Integracao CRM
│       ├── WhatsAppService       # Z-API
│       └── RecaptchaService      # Validacao
├── config/
│   ├── branding.php              # Identidade visual
│   ├── integrations.php          # Integracoes externas
│   └── site.php                  # Configuracoes do site
├── database/
│   ├── migrations/
│   ├── schema.sql
│   └── seeders/
├── public/
│   └── css/brand.css             # Estilos da marca
├── resources/
│   └── views/
│       ├── layouts/              # Layout base
│       ├── pages/                # Paginas institucionais
│       ├── legal/                # Paginas legais
│       └── partials/             # Componentes
├── routes/
│   ├── web.php                   # Rotas publicas
│   ├── api.php                   # API interna
│   └── console.php               # Comandos
└── docker-compose.yml
```

## API Endpoints

### Publicas

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /health | Health check basico |
| POST | /lead/cliente | Submissao de lead cliente |
| POST | /lead/cuidador | Submissao de lead cuidador |

### API Interna (autenticada)

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /api/leads | Lista leads |
| GET | /api/leads/stats | Estatisticas de leads |
| GET | /api/leads/{id} | Detalhes do lead |
| POST | /api/leads/{id}/mark-synced | Marca como sincronizado |
| GET | /api/domains | Valores de dominio |
| GET | /api/settings | Configuracoes do site |
| POST | /api/webhooks/crm | Webhook do CRM |
| POST | /api/webhooks/cache/pages/clear | Limpa cache |

## Integracoes

### Externas

| API | Funcao | Documentacao |
|-----|--------|--------------|
| Z-API | WhatsApp CTA e notificacoes | https://developer.z-api.io/ |
| Google Analytics | Tracking de conversao | https://analytics.google.com/ |
| Google Tag Manager | Gerenciamento de tags | https://tagmanager.google.com/ |
| reCAPTCHA v3 | Protecao anti-spam | https://developers.google.com/recaptcha |

### Sistemas Internos

| Sistema | Funcao |
|---------|--------|
| CRM | Recebe leads e UTM |
| Atendimento | Redirecionamento WhatsApp |
| Marketing | Tracking de campanhas |
| Integracoes Hub | Eventos e automacoes |

## Instalacao

```bash
# Clone o repositorio
git clone [repo-url]
cd carinho-site

# Instale dependencias
composer install

# Configure ambiente
cp .env.example .env
php artisan key:generate

# Configure banco de dados e Redis no .env

# Execute migrations
php artisan migrate

# Popule dados iniciais
php artisan db:seed

# Inicie o servidor
php artisan serve
```

## Variaveis de Ambiente

```env
# App
APP_URL=https://site.carinho.com.vc

# Database
DB_DATABASE=carinho_site

# Redis
REDIS_HOST=127.0.0.1

# Identidade
BRAND_WHATSAPP=5511999999999

# Z-API
ZAPI_ENABLED=true
ZAPI_INSTANCE_ID=
ZAPI_TOKEN=
ZAPI_CLIENT_TOKEN=

# Analytics
GA4_MEASUREMENT_ID=
GTM_CONTAINER_ID=

# reCAPTCHA
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

# Integracoes
CARINHO_CRM_URL=https://crm.carinho.com.vc
CARINHO_CRM_API_KEY=
```

## Seguranca

- HTTPS com HSTS e TLS atualizado
- Rate limiting em formularios (5 req/min)
- Rate limiting na API (60 req/min)
- Validacao reCAPTCHA v3
- Sanitizacao de inputs
- Protecao CSRF
- Token interno para webhooks

## Performance

- Cache de paginas no Redis
- CDN para assets estaticos
- Compressao de imagens
- Lazy loading de imagens
- Aplicacao stateless para escala horizontal

## Identidade Visual

As cores e tipografia seguem o padrao da marca:

- **Primary:** #5BBFAD (Verde Carinho)
- **Secondary:** #F4F7F9
- **Accent:** #F5C6AA (Pessego)
- **Text:** #1F2933

Veja `public/css/brand.css` e `config/branding.php` para detalhes completos.

## Jobs Agendados

| Job | Frequencia | Descricao |
|-----|------------|-----------|
| sync-leads-to-crm | 5 minutos | Sincroniza leads pendentes |
| clear-cache | Diario 03:00 | Limpa cache |

## Monitoramento

- Health check: `GET /health`
- Health detalhado: `GET /health/detailed`
- Logs estruturados em `storage/logs`

## Contribuicao

1. Crie uma branch para sua feature
2. Faca commits atomicos com mensagens claras
3. Siga o padrao de codigo (Laravel Pint)
4. Envie um Pull Request

## Licenca

Proprietary - Carinho com Voce
