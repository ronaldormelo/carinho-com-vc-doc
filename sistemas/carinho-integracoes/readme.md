# Carinho Integracoes

**Subdominio:** integracoes.carinho.com.vc

## Documentação deste módulo

[Arquitetura](docs/arquitetura.md) · [Matriz](docs/matriz-integracoes.md) · [Contratos de rota](docs/contratos-rotas.md) · [Z-API](docs/integracao-zapi.md) · [NFRs](docs/nao-funcionais.md) · [Runbook](docs/runbook-operacional.md) · [Guia](docs/guia-usuario-operacional.md)

`docs/analise-praticas-mercado.md` é referência, não contrato de fila.

## Descrição

Camada de automacao e integracao do fluxo ponta a ponta. Conecta site, atendimento, CRM, operação e financeiro para reduzir trabalho manual.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MariaDB 10.11 compartilhado (driver `mysql`)
- **Cache e Filas:** Redis
- **Workers:** Laravel Horizon / Supervisor (config `config/horizon.php` neste módulo)
- **Autenticacao:** API Key (`X-API-Key`)
- **Webhooks:** `spatie/laravel-webhook-client` e `spatie/laravel-webhook-server` no composer; validação HMAC no middleware do módulo

## Modulos Essenciais

### 1. WhatsApp -> CRM (Captura e Registro)

Integracao com Z-API para capturar mensagens recebidas e registrar automaticamente no CRM.

**Fluxo:**
1. Mensagem recebida via webhook do Z-API
2. Normaliza payload da mensagem
3. Busca ou cria lead no CRM
4. Registra interacao no histórico
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

Após conclusão do serviço, solicita feedback do cliente automaticamente.

**Fluxo:**
1. Serviço finalizado na operação
2. Webhook dispara `service.completed`
3. Job `ProcessServiceCompleted` e acionado
4. Notifica cliente da finalizacao
5. Após 2h, envia solicitação de feedback via WhatsApp e Email
6. Registra feedback no CRM e sistema de cuidadores

### 5. Sincronizacao entre Sistemas

Mantem dados consistentes entre CRM, Operação e Financeiro.

**Sincronizacoes:**
- **CRM -> Operação:** Contratos e agendamentos (horário)
- **Operação -> Financeiro:** Serviços executados para faturamento (diário)
- **CRM -> Financeiro:** Setup de cobrança recorrente (2x/dia)
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
7. **DLQ:** Após max tentativas, move para Dead Letter Queue

## Integracao com Z-API (WhatsApp)

**Documentacao:** https://developer.z-api.io/

### Funcionalidades Implementadas

- Envio de mensagens de texto
- Envio de mensagens com botoes
- Envio de imagens e documentos
- Envio de links com preview
- Recepcao de webhooks de mensagens
- Validação de assinatura HMAC
- Verificação de status da instancia

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
- **Método:** POST

## API Endpoints

### Eventos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/events` | Lista eventos com filtros |
| POST | `/api/events` | Cria novo evento |
| GET | `/api/events/stats` | Estatisticas de eventos |
| GET | `/api/events/{id}` | Detalhes do evento |
| POST | `/api/events/{id}/retry` | Reprocessa evento |

### Sincronizacao

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/sync/jobs` | Lista jobs de sync |
| POST | `/api/sync/start` | Inicia sincronizacao |
| GET | `/api/sync/stats` | Estatisticas de sync |
| GET | `/api/sync/jobs/{id}` | Detalhes do job |

### Mapeamentos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/mappings` | Lista mapeamentos |
| POST | `/api/mappings` | Cria mapeamento |
| POST | `/api/mappings/test` | Testa transformacao |
| GET | `/api/mappings/{type}/{system}` | Mapeamento atual |
| GET | `/api/mappings/{type}/{system}/versions` | Versoes |

### Dead Letter Queue

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/dlq` | Lista items na DLQ |
| GET | `/api/dlq/stats` | Estatisticas DLQ |
| POST | `/api/dlq/{id}/retry` | Reprocessa item |
| POST | `/api/dlq/{id}/archive` | Arquiva item |
| DELETE | `/api/dlq/{id}` | Remove item |

### Webhooks Recebidos

| Endpoint | Sistema | Descrição |
|----------|---------|-----------|
| `POST /webhooks/whatsapp` | Z-API | Mensagens WhatsApp |
| `POST /webhooks/site/lead` | Site | Novos leads |
| `POST /webhooks/crm/client-registered` | CRM | Clientes cadastrados |
| `POST /webhooks/operacao/service-completed` | Operação | Serviços finalizados |
| `POST /webhooks/financeiro/payment` | Financeiro | Pagamentos |
| `POST /webhooks/financeiro/payout` | Financeiro | Repasses |
| `POST /webhooks/cuidadores/feedback` | Cuidadores | Feedback |

## Segurança

### Autenticacao

- **API:** Header `X-API-Key` obrigatorio
- **Webhooks:** Validação de assinatura HMAC-SHA256

### Rate Limiting

- 60 requisicoes por minuto por API Key
- Headers de resposta indicam limite e restante

### LGPD

- Logs não armazenam dados sensiveis
- Payloads podem ser criptografados
- Eventos podem ser anonimizados após processamento

## Performance

### Estrategias

- Processamento assincrono via filas
- Backpressure com prioridades de fila
- Idempotencia por `idempotency_key`
- Cache de mapeamentos e configurações
- Indices otimizados no banco

### Filas por Prioridade

1. `integrations-high` - Webhooks críticos (2-5 workers)
2. `integrations` - Processamento padrão (3-10 workers)
3. `notifications` - WhatsApp/Email (2 workers)
4. `integrations-low` - Sync batch (1 worker)
5. `integrations-retry` - Reprocessamento (1 worker)

## Instalacao

```bash
cd sistemas/carinho-integracoes
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

- `GET /health` - Check básico (público)
- `GET /health/detailed` - Check com dependências (público)
- `GET /status` - Status completo (`X-API-Key`)
- `GET /dashboard`, `/alerts`, `POST /circuit-breakers/{service}/reset` — operador, com API key

Acesse `/horizon` para monitorar filas (proteger em produção).

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

Proprietary - Carinho com Você
