# Carinho Site

**Host de produção:** https://carinho.com.vc (apex, sem prefixo `site.`)  
**Local (Docker):** http://127.0.0.1:8084

## Hostname e redirect

O site institucional roda na **raiz do domínio**. Os demais sistemas continuam em subdomínio (`crm.carinho.com.vc`, `atendimento.carinho.com.vc`, etc.).

| Ambiente | URL vigente |
|----------|-------------|
| Produção | `https://carinho.com.vc` |
| Docker local | `http://127.0.0.1:8084` |

**Redirect 301:** `http(s)://site.carinho.com.vc/{path}` → `https://carinho.com.vc/{path}` (query string preservada).

Implementado neste módulo em:

- `apache-config.conf` (VirtualHost `ServerName site.carinho.com.vc`)
- `public/.htaccess` (mod_rewrite por `HTTP_HOST`)
- middleware `RedirectLegacySiteHost` (aplica-se mesmo atrás de proxy que entrega o Host legado no vhost padrão)

Este repositório **não** contém o DNS, o certificado TLS nem o reverse proxy do provedor de nuvem. Em produção, o operador deve apontar o apex `carinho.com.vc` para este app e manter `site.carinho.com.vc` apenas o tempo necessário para o 301 (mesmo container/app ou proxy na frente). Cookie de sessão do site fica no host `carinho.com.vc`; **não** usar `SESSION_DOMAIN=.carinho.com.vc` (não compartilhar cookie com CRM/Sanctum).

## Documentação deste módulo

[Arquitetura](docs/arquitetura.md) · [Módulos](docs/modulos.md) · [Integrações](docs/integracoes.md) · [NFRs](docs/nao-funcionais.md) · [Guia do visitante](docs/guia-usuario-site.md) · [Atividades](docs/atividades.md)

`docs/analise-revisao-modulo.md` é revisão pontual (jan/2026), não contrato.

Health: `GET /up` (Laravel), `GET /health` e `GET /health/detailed` (público). `GET /api/health` também é público. API interna (`/api/leads`, `/api/content/*`) exige token, salvo health.

Há `POST /lead/investidor` além de cliente e cuidador. CMS de depoimentos/FAQ: `/api/content/*` (CRM). Política de cancelamento no código (`config/site.php`) segue o Financeiro (24 h / 6 h), não a Operação. Horista no site declara mínimo 2 h; precificação/agenda usam 4 h — a família vê o texto legal do Financeiro/site; operação não agenda abaixo de 4 h.

## Descrição

Portal institucional do projeto de home care Carinho com Você. Apresenta a proposta de valor, explica os serviços e capta leads, direcionando o contato para o WhatsApp como canal principal.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MariaDB 10.11 compartilhado (driver `mysql`, schema `carinho_site`)
- **Cache e filas:** Redis
- **Storage de midias:** S3 compativel
- **CDN para ativos estaticos**

## Modulos Implementados

### 1. Páginas Institucionais
- Home com hero, features e depoimentos
- Quem Somos (missao, valores, diferenciais)
- Serviços (horista, diário, mensal)
- Como Funciona (passo a passo)
- Contato (canais e horários)
- FAQ (perguntas frequentes)

### 2. Páginas por Público
- Para Clientes: formulário de solicitação de cuidador
- Para Cuidadores: cadastro para trabalhar na plataforma

### 3. Formularios de Cadastro
- Formulário de lead cliente com validação
- Formulário de lead cuidador com validação
- Integracao com reCAPTCHA v3
- Sincronizacao automatica com CRM

### 4. Páginas Legais (Políticas)
- **Política de Privacidade:** conformidade LGPD, direitos do titular
- **Termos de Uso:** regras de utilizacao dos serviços
- **Política de Cancelamento:** prazos e reembolsos
- **Política de Pagamento:** pagamento adiantado, formas aceitas
- **Política de Emergências:** canais, SLA, procedimentos
- **Termos para Cuidadores:** comissões, obrigacoes, repasses

### 5. SEO e Analytics
- Meta tags otimizadas para SEO
- Schema.org JSON-LD para LocalBusiness
- Integracao com Google Analytics 4
- Integracao com Google Tag Manager
- Rastreamento de UTM em toda a navegacao

### 6. CTA para WhatsApp
- Botao flutuante em todas as páginas
- Links com mensagem pre-definida
- Rastreamento de origem (UTM)

### 7. Integracao com CRM
- Envio automatico de leads via webhook
- Sincronizacao de UTM e origem
- Retry automatico em caso de falha

## Políticas Definidas

### Pagamento
- **Tipo:** Sempre ADIANTADO (pré-pago)
- **Prazo:** 24 horas antes do serviço
- **Formas:** PIX, boleto, cartão de crédito
- **Juros por atraso:** 0,033% ao dia
- **Multa por atraso:** 2%

### Cancelamento
| Prazo | Reembolso |
|-------|-----------|
| Mais de 24h antes | 100% |
| Entre 6h e 24h | 50% |
| Menos de 6h | 0% |

### Comissões do Cuidador
| Tipo de Serviço | Percentual |
|-----------------|------------|
| Horista | 70% |
| Diário | 72% |
| Mensal | 75% |

**Bônus:** Até +2% por avaliação, +3% por tempo de casa

### Repasses
- **Frequência:** Semanal (sextas-feiras)
- **Valor mínimo:** R$ 50,00
- **Liberação:** 3 dias após conclusão

### Emergências
| Nivel | Tempo de Resposta |
|-------|-------------------|
| Critico | 15 minutos |
| Alto | 30 minutos |
| Médio | 2 horas |

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

### Públicas

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /health | Health check basico |
| POST | /lead/cliente | Submissao de lead cliente |
| POST | /lead/cuidador | Submissao de lead cuidador |
| POST | /lead/investidor | Submissão de lead investidor |
| GET | /whatsapp | Redirect CTA com UTM da sessão |

### API Interna (autenticada)

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /api/leads | Lista leads |
| GET | /api/leads/stats | Estatisticas de leads |
| GET | /api/leads/{id} | Detalhes do lead |
| POST | /api/leads/{id}/mark-synced | Marca como sincronizado |
| GET | /api/domains | Valores de dominio |
| GET | /api/settings | Configurações do site |
| POST | /api/webhooks/crm | Webhook do CRM |
| POST | /api/webhooks/cache/pages/clear | Limpa cache de páginas |
| GET | /api/content/* | CMS (depoimentos, FAQ, páginas) — chamado pelo CRM |

## Integracoes

### Externas

| API | Função | Documentacao |
|-----|--------|--------------|
| Z-API | WhatsApp CTA e notificações | https://developer.z-api.io/ |
| Google Analytics | Tracking de conversao | https://analytics.google.com/ |
| Google Tag Manager | Gerenciamento de tags | https://tagmanager.google.com/ |
| reCAPTCHA v3 | Protecao anti-spam | https://developers.google.com/recaptcha |

### Sistemas Internos

| Sistema | Função |
|---------|--------|
| CRM | Recebe leads e UTM |
| Atendimento | Redirecionamento WhatsApp |
| Marketing | Tracking de campanhas |
| Integracoes Hub | Eventos e automacoes |

## Instalacao

```bash
cd sistemas/carinho-site
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
# App — producao (apex). Local Docker: http://127.0.0.1:8084
APP_URL=https://carinho.com.vc

# Database
DB_DATABASE=carinho_site

# Redis
REDIS_HOST=127.0.0.1

# Identidade
BRAND_WHATSAPP=5589999771471
BRAND_WHATSAPP_DISPLAY="(89) 99977-1471"

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

## Segurança

- HTTPS com HSTS e TLS atualizado
- Rate limiting em formularios (5 req/min)
- Rate limiting na API (60 req/min)
- Validação reCAPTCHA v3
- Sanitizacao de inputs
- Protecao CSRF
- Token interno para webhooks

## Performance

- Cache de páginas no Redis
- CDN para assets estaticos
- Compressao de imagens
- Lazy loading de imagens
- Aplicacao stateless para escala horizontal

## Identidade Visual

As cores e tipografia seguem o padrão da marca:

- **Primary:** #5BBFAD (Verde Carinho)
- **Secondary:** #F4F7F9
- **Accent:** #F5C6AA (Pêssego)
- **Text:** #1F2933

Veja `public/css/brand.css` e `config/branding.php` para detalhes completos.

## Jobs Agendados

| Job | Frequência | Descrição |
|-----|------------|-----------|
| sync-leads-to-crm | 5 minutos | Sincroniza leads pendentes |
| clear-cache | Diário 03:00 | Limpa cache |

## Monitoramento

- Health check: `GET /health`
- Health detalhado: `GET /health/detailed`
- Logs estruturados em `storage/logs`

## Contribuicao

1. Crie uma branch para sua feature
2. Faca commits atomicos com mensagens claras
3. Siga o padrão de código (Laravel Pint)
4. Envie um Pull Request

## Licenca

Proprietary - Carinho com Você
