# Arquitetura

## Visao Geral

Camada de integracao e automacao (integracoes.carinho.com.vc). Conecta sistemas internos e externos com eventos, filas e regras de orquestracao.

## Stack

- Linguagem: PHP 8.2+
- Framework: Laravel 11
- Banco de dados: MySQL 8.0
- Cache e filas: Redis
- Workers: Laravel Horizon / Supervisor

## Componentes Principais

### Event Processor

Servico central que:
- Recebe e valida eventos
- Persiste com idempotencia
- Aplica mapeamentos de transformacao
- Despacha para sistemas alvo
- Gerencia retry e DLQ

### Workers (Horizon)

Processa filas com prioridades:
- `integrations-high`: Webhooks criticos (2-5 procs)
- `integrations`: Padrao (3-10 procs)
- `notifications`: WhatsApp/Email (2 procs)
- `integrations-low`: Sync batch (1 proc)
- `integrations-retry`: Reprocessamento (1 proc)

### Webhook Handlers

Recebe eventos de:
- Z-API (WhatsApp)
- Site (leads)
- CRM (clientes)
- Operacao (servicos)
- Financeiro (pagamentos)

### Integration Clients

Clientes para sistemas:
- ZApiClient (WhatsApp)
- CrmClient
- OperacaoClient
- FinanceiroClient
- CuidadoresClient
- AtendimentoClient
- SiteClient
- MarketingClient
- DocumentosClient

## Integracoes

### Entrada (Webhooks)

```
Site         ─────┐
WhatsApp     ─────┤
CRM          ─────┼────► Event Processor ────► Jobs Queue
Operacao     ─────┤
Financeiro   ─────┘
```

### Saida (Webhooks)

```
                        ┌────► CRM
Event Processor ────────┼────► Operacao
                        ├────► Financeiro
                        ├────► Cuidadores
                        └────► Atendimento
```

## Fluxos Principais

### Lead Criado

```
Site/WhatsApp → Webhook → ProcessLeadCreated
    ├─► Registra no CRM
    ├─► Envia WhatsApp auto-resposta
    └─► Atribui campanha marketing
```

### Cliente Cadastrado

```
CRM → Webhook → ProcessClientRegistered
    ├─► Envia Email boas-vindas
    ├─► Envia WhatsApp boas-vindas
    └─► Setup no Financeiro
```

### Servico Completado

```
Operacao → Webhook → ProcessServiceCompleted
    ├─► Notifica cliente
    ├─► Solicita feedback (2h delay)
    ├─► Cria fatura no Financeiro
    └─► Atualiza CRM
```

## Dados e Armazenamento

### Tabelas Principais

- `integration_events`: Eventos recebidos
- `event_mappings`: Regras de transformacao
- `webhook_endpoints`: Destinos de entrega
- `webhook_deliveries`: Registro de entregas
- `retry_queue`: Eventos para reprocessar
- `dead_letter`: Eventos que falharam
- `sync_jobs`: Jobs de sincronizacao
- `api_keys`: Chaves de autenticacao
- `rate_limits`: Controle de limite

### Indices Recomendados

- `integration_events(status_id, created_at)`
- `webhook_deliveries(endpoint_id, status_id)`
- `retry_queue(next_retry_at)`

## Seguranca e LGPD

- Autenticacao via API Key
- Validacao de assinatura em webhooks
- Rate limiting por cliente
- Rotacao de secrets
- Logs sem dados sensiveis
- Criptografia de payloads sensiveis

## Escalabilidade e Desempenho

- Processamento assincrono com backpressure
- Idempotencia para evitar duplicidade
- Particionamento por fila e prioridade
- Cache de mapeamentos
- Conexoes persistentes com sistemas

## Observabilidade e Operacao

- Logs estruturados por evento
- Health checks em `/health`
- Status completo em `/status`
- Horizon dashboard em `/horizon`
- Alertas para falhas e backlog crescente

## Backup e Resiliencia

- Persistencia de eventos para reprocessamento
- Retry com backoff exponencial
- Dead Letter Queue para investigacao
- Testes periodicos de replay
