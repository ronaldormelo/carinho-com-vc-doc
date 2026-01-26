# Carinho Integracoes

**Subdominio:** integracoes.carinho.com.vc

## Descricao

Camada de automacao e integracao do fluxo ponta a ponta. Conecta site, atendimento, CRM, operacao e financeiro para reduzir trabalho manual.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache e Filas:** Redis
- **Workers:** Laravel Horizon / Supervisor
- **Autenticacao:** API Key
- **Webhooks:** Spatie Laravel Webhook

## Modulos Essenciais

### 1. WhatsApp -> CRM (Captura e Registro)

Integracao com Z-API para capturar mensagens recebidas e registrar automaticamente no CRM.

**Fluxo:**
1. Mensagem recebida via webhook do Z-API
2. Normaliza payload da mensagem
3. Busca ou cria lead no CRM
4. Registra interacao no historico
5. Encaminha para sistema de atendimento

**Endpoint:** `POST /webhooks/whatsapp`

### 2. Lead -> Mensagem Automatica

Ao criar um novo lead, envia automaticamente mensagem de resposta via WhatsApp.

**Fluxo:**
1. Lead criado (via site, WhatsApp ou manual)
2. Job `ProcessLeadCreated` e acionado
3. Registra lead no CRM com dados de origem
4. Envia mensagem automatica de boas-vindas
5. Atribui a campanha de marketing se houver UTM

### 3. Cadastro -> Email de Boas-Vindas

Ao converter lead em cliente, envia email de boas-vindas e configura integracao financeira.

**Fluxo:**
1. Cliente cadastrado no CRM
2. Webhook dispara `client.registered`
3. Job `ProcessClientRegistered` e acionado
4. Envia email de boas-vindas
5. Envia WhatsApp de boas-vindas
6. Configura cliente no sistema financeiro

### 4. Feedback Automatico Pos-Servico

Apos conclusao do servico, solicita feedback do cliente automaticamente.

**Fluxo:**
1. Servico finalizado na operacao
2. Webhook dispara `service.completed`
3. Job `ProcessServiceCompleted` e acionado
4. Notifica cliente da finalizacao
5. Apos 2h, envia solicitacao de feedback via WhatsApp e Email
6. Registra feedback no CRM e sistema de cuidadores

### 5. Sincronizacao entre Sistemas

Mantem dados consistentes entre CRM, Operacao e Financeiro.

**Sincronizacoes:**
- **CRM -> Operacao:** Contratos e agendamentos (horario)
- **Operacao -> Financeiro:** Servicos executados para faturamento (diario)
- **CRM -> Financeiro:** Setup de cobranca recorrente (2x/dia)
- **Cuidadores -> CRM:** Atualizacoes de cuidadores (4h)

## Arquitetura

### Componentes Principais

```
┌─────────────────────────────────────────────────────────────────┐
│                        CARINHO INTEGRACOES                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   Webhooks   │    │     API      │    │   Workers    │      │
│  │  Controller  │    │  Controller  │    │  (Horizon)   │      │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘      │
│         │                   │                   │               │
│  ┌──────▼───────────────────▼───────────────────▼───────┐      │
│  │              Event Processor Service                  │      │
│  │  - Recebe eventos                                     │      │
│  │  - Valida e persiste                                  │      │
│  │  - Aplica mapeamentos                                 │      │
│  │  - Despacha para sistemas alvo                        │      │
│  └──────────────────────────┬───────────────────────────┘      │
│                             │                                   │
│  ┌──────────────────────────▼───────────────────────────┐      │
│  │                    Job Queue (Redis)                  │      │
│  │  - integrations-high (webhooks criticos)              │      │
│  │  - integrations (processamento padrao)                │      │
│  │  - notifications (WhatsApp/Email)                     │      │
│  │  - integrations-low (sync batch)                      │      │
│  │  - integrations-retry (reprocessamento)               │      │
│  └──────────────────────────┬───────────────────────────┘      │
│                             │                                   │
└─────────────────────────────┼───────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          │                   │                   │
    ┌─────▼─────┐       ┌─────▼─────┐       ┌─────▼─────┐
    │    CRM    │       │  OPERACAO │       │FINANCEIRO │
    └───────────┘       └───────────┘       └───────────┘
```

### Fluxo de Eventos

1. **Entrada:** Webhook ou API recebe evento
2. **Persistencia:** Evento salvo em `integration_events`
3. **Processamento:** Job processa evento assincronamente
4. **Mapeamento:** Transforma payload para sistema alvo
5. **Entrega:** Envia webhook para sistemas destino
6. **Retry:** Em caso de falha, adiciona a fila de retry
7. **DLQ:** Apos max tentativas, move para Dead Letter Queue

## Integracao com Z-API (WhatsApp)

**Documentacao:** https://developer.z-api.io/

### Funcionalidades Implementadas

- Envio de mensagens de texto
- Envio de mensagens com botoes
- Envio de imagens e documentos
- Envio de links com preview
- Recepcao de webhooks de mensagens
- Validacao de assinatura HMAC
- Verificacao de status da instancia

### Configuracao

```env
ZAPI_ENABLED=true
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=sua-instancia
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token
ZAPI_WEBHOOK_SECRET=seu-secret
```

### Webhook URL

Configure no painel do Z-API:
- **URL:** `https://integracoes.carinho.com.vc/webhooks/whatsapp`
- **Metodo:** POST

## API Endpoints

### Eventos

| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | `/api/events` | Lista eventos com filtros |
| POST | `/api/events` | Cria novo evento |
| GET | `/api/events/stats` | Estatisticas de eventos |
| GET | `/api/events/{id}` | Detalhes do evento |
| POST | `/api/events/{id}/retry` | Reprocessa evento |

### Sincronizacao

| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | `/api/sync/jobs` | Lista jobs de sync |
| POST | `/api/sync/start` | Inicia sincronizacao |
| GET | `/api/sync/stats` | Estatisticas de sync |
| GET | `/api/sync/jobs/{id}` | Detalhes do job |

### Mapeamentos

| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | `/api/mappings` | Lista mapeamentos |
| POST | `/api/mappings` | Cria mapeamento |
| POST | `/api/mappings/test` | Testa transformacao |
| GET | `/api/mappings/{type}/{system}` | Mapeamento atual |
| GET | `/api/mappings/{type}/{system}/versions` | Versoes |

### Dead Letter Queue

| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | `/api/dlq` | Lista items na DLQ |
| GET | `/api/dlq/stats` | Estatisticas DLQ |
| POST | `/api/dlq/{id}/retry` | Reprocessa item |
| POST | `/api/dlq/{id}/archive` | Arquiva item |
| DELETE | `/api/dlq/{id}` | Remove item |

### Webhooks Recebidos

| Endpoint | Sistema | Descricao |
|----------|---------|-----------|
| `POST /webhooks/whatsapp` | Z-API | Mensagens WhatsApp |
| `POST /webhooks/site/lead` | Site | Novos leads |
| `POST /webhooks/crm/client-registered` | CRM | Clientes cadastrados |
| `POST /webhooks/operacao/service-completed` | Operacao | Servicos finalizados |
| `POST /webhooks/financeiro/payment` | Financeiro | Pagamentos |
| `POST /webhooks/financeiro/payout` | Financeiro | Repasses |
| `POST /webhooks/cuidadores/feedback` | Cuidadores | Feedback |

## Seguranca

### Autenticacao

- **API:** Header `X-API-Key` obrigatorio
- **Webhooks:** Validacao de assinatura HMAC-SHA256

### Rate Limiting

- 60 requisicoes por minuto por API Key
- Headers de resposta indicam limite e restante

### LGPD

- Logs nao armazenam dados sensiveis
- Payloads podem ser criptografados
- Eventos podem ser anonimizados apos processamento

## Performance

### Estrategias

- Processamento assincrono via filas
- Backpressure com prioridades de fila
- Idempotencia por `idempotency_key`
- Cache de mapeamentos e configuracoes
- Indices otimizados no banco

### Filas por Prioridade

1. `integrations-high` - Webhooks criticos (2-5 workers)
2. `integrations` - Processamento padrao (3-10 workers)
3. `notifications` - WhatsApp/Email (2 workers)
4. `integrations-low` - Sync batch (1 worker)
5. `integrations-retry` - Reprocessamento (1 worker)

## Instalacao

```bash
# Clone o repositorio
git clone [repo-url]
cd carinho-integracoes

# Instale dependencias
composer install

# Configure ambiente
cp .env.example .env
php artisan key:generate

# Configure banco de dados e Redis no .env

# Execute migrations
php artisan migrate

# Crie API Keys e endpoints (anote os secrets!)
php artisan db:seed

# Inicie Horizon (workers)
php artisan horizon

# OU usando Docker
docker-compose up -d
```

## Estrutura de Diretorios

```
carinho-integracoes/
├── app/
│   ├── Events/                 # Eventos do sistema
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # Controllers da API
│   │   │   └── Webhook/        # Controllers de webhooks
│   │   └── Middleware/         # Middlewares
│   ├── Jobs/                   # Jobs assincronos
│   ├── Models/
│   │   └── Domain/             # Models de dominio
│   ├── Providers/              # Service Providers
│   └── Services/
│       ├── Email/              # Servico de email
│       ├── Integrations/       # Clientes de integracao
│       │   ├── Atendimento/
│       │   ├── Crm/
│       │   ├── Cuidadores/
│       │   ├── Documentos/
│       │   ├── Financeiro/
│       │   ├── Marketing/
│       │   ├── Operacao/
│       │   ├── Site/
│       │   └── WhatsApp/       # Z-API Client
│       ├── EventProcessor.php  # Processador central
│       └── SyncService.php     # Servico de sync
├── config/
│   ├── branding.php            # Identidade visual
│   ├── horizon.php             # Configuracao Horizon
│   ├── integrations.php        # Configuracoes de integracao
│   └── queue.php               # Filas
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       └── emails/             # Templates de email
├── routes/
│   ├── api.php                 # Rotas da API
│   ├── console.php             # Agendamentos
│   └── web.php                 # Webhooks e health
├── supervisor/                 # Configuracoes Supervisor
└── docs/
    ├── arquitetura.md
    └── atividades.md
```

## Variaveis de Ambiente

```env
# Aplicacao
APP_NAME="Carinho Integracoes"
APP_ENV=production
APP_KEY=
APP_DEBUG=false

# Banco de dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=carinho_integracoes
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis

# Z-API (WhatsApp)
ZAPI_ENABLED=true
ZAPI_INSTANCE_ID=
ZAPI_TOKEN=
ZAPI_CLIENT_TOKEN=
ZAPI_WEBHOOK_SECRET=

# Sistemas Internos
CARINHO_CRM_URL=https://crm.carinho.com.vc
CARINHO_CRM_API_KEY=
CARINHO_OPERACAO_URL=https://operacao.carinho.com.vc
CARINHO_OPERACAO_API_KEY=
CARINHO_FINANCEIRO_URL=https://financeiro.carinho.com.vc
CARINHO_FINANCEIRO_API_KEY=
# ... demais sistemas
```

## Monitoramento

### Health Checks

- `GET /health` - Check basico
- `GET /health/detailed` - Check com dependencias
- `GET /status` - Status completo do sistema

### Horizon Dashboard

Acesse `/horizon` para monitorar filas em tempo real.

### Alertas Recomendados

- Retry queue > 100 items
- Dead letter queue crescendo
- Sync jobs falhando
- Taxa de erro > 5%

## Contribuicao

1. Crie uma branch para sua feature
2. Faca commits atomicos com mensagens claras
3. Adicione testes para novas funcionalidades
4. Envie um Pull Request

## Licenca

Proprietary - Carinho com Voce
