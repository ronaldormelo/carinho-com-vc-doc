# Integrações - Carinho Financeiro

Documento técnico das integrações do sistema financeiro.

## 1. Stripe (Gateway de Pagamento)

### Visão Geral

O Stripe é utilizado como gateway de pagamento principal, suportando:
- PIX (instantâneo)
- Boleto bancário
- Cartão de crédito
- Stripe Connect para repasses

**Documentação oficial:** https://stripe.com/docs/api

### Configuração

```env
STRIPE_ENABLED=true
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=brl
```

### Fluxo de Pagamento

```
┌─────────────────────────────────────────────────────────────────┐
│                     FLUXO DE PAGAMENTO                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Cliente]     [Sistema]      [Stripe]       [Webhook]          │
│      │             │              │              │              │
│      │  Solicita   │              │              │              │
│      │  Pagamento  │              │              │              │
│      │────────────>│              │              │              │
│      │             │   Cria       │              │              │
│      │             │ PaymentIntent│              │              │
│      │             │─────────────>│              │              │
│      │             │              │              │              │
│      │             │   Retorna    │              │              │
│      │             │   PIX/Boleto │              │              │
│      │             │<─────────────│              │              │
│      │             │              │              │              │
│      │   Exibe     │              │              │              │
│      │  QR Code    │              │              │              │
│      │<────────────│              │              │              │
│      │             │              │              │              │
│      │   Cliente   │              │              │              │
│      │    Paga     │              │              │              │
│      │─────────────────────────-->│              │              │
│      │             │              │              │              │
│      │             │              │   Webhook    │              │
│      │             │              │   Enviado    │              │
│      │             │              │─────────────>│              │
│      │             │              │              │              │
│      │             │              │   Confirma   │              │
│      │             │<─────────────────────────────│              │
│      │             │              │              │              │
│      │  Notifica   │              │              │              │
│      │   Cliente   │              │              │              │
│      │<────────────│              │              │              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Endpoints Utilizados

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/v1/payment_intents` | POST | Criar pagamento |
| `/v1/payment_intents/{id}` | GET | Consultar status |
| `/v1/payment_intents/{id}/cancel` | POST | Cancelar |
| `/v1/refunds` | POST | Reembolso |
| `/v1/customers` | POST/GET | Gerenciar clientes |
| `/v1/accounts` | POST/GET | Contas Connect |
| `/v1/transfers` | POST | Repasses |

### Webhooks

Eventos tratados:
- `payment_intent.succeeded` - Pagamento confirmado
- `payment_intent.payment_failed` - Pagamento falhou
- `charge.refunded` - Reembolso processado
- `payout.paid` - Repasse enviado
- `account.updated` - Conta Connect atualizada

**Endpoint:** `POST /webhooks/stripe`

### Stripe Connect (Repasses)

Utilizado para enviar pagamentos aos cuidadores:

1. Cuidador cria conta Express
2. Faz onboarding via link fornecido
3. Vincula conta bancária
4. Sistema cria Transfer para a conta
5. Stripe processa payout automaticamente

---

## 2. Z-API (WhatsApp)

### Visão Geral

O Z-API é utilizado para enviar notificações via WhatsApp Business.

**Documentação oficial:** https://developer.z-api.io/

### Configuração

```env
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=sua_instancia
ZAPI_TOKEN=seu_token
ZAPI_CLIENT_TOKEN=client_token
ZAPI_WEBHOOK_SECRET=webhook_secret
```

### Endpoints Utilizados

| Endpoint | Uso |
|----------|-----|
| `/send-text` | Mensagem de texto |
| `/send-image` | Imagem com legenda |
| `/send-document` | Documentos/arquivos |
| `/send-button-list` | Mensagem com botões |
| `/send-link` | Link com preview |
| `/status` | Status da instância |
| `/qr-code` | QR Code para conexão |

### Mensagens Enviadas

| Evento | Destinatário | Template |
|--------|--------------|----------|
| Fatura criada | Cliente | `invoice_created` |
| Pagamento confirmado | Cliente | `payment_confirmed` |
| Lembrete de vencimento | Cliente | `payment_reminder` |
| Fatura vencida | Cliente | `payment_overdue` |
| Repasse processado | Cuidador | `payout_processed` |
| Cancelamento | Cliente | `cancellation_processed` |

### Templates de Mensagem

```php
// config/branding.php
'messages' => [
    'invoice_created' => 'Olá! Sua fatura está disponível...',
    'payment_confirmed' => 'Pagamento confirmado! Obrigado...',
    'payment_reminder' => 'Lembrete: sua fatura vence em {days} dias...',
    // ...
]
```

---

## 3. Integrações Internas

### CRM (carinho-crm)

**Base URL:** `https://crm.carinho.com.vc/api`

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/contracts/{id}` | GET | Dados do contrato |
| `/clients/{id}` | GET | Dados do cliente |
| `/webhooks/internal` | POST | Notificar eventos |

**Eventos enviados:**
- `invoice.created` - Fatura criada
- `payment.confirmed` - Pagamento confirmado
- `invoice.overdue` - Fatura vencida

### Operação (carinho-operacao)

**Base URL:** `https://operacao.carinho.com.vc/api`

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/services` | GET | Lista serviços |
| `/services/{id}` | GET | Detalhes do serviço |
| `/services/mark-invoiced` | POST | Marca como faturado |
| `/services/pending-invoicing` | GET | Pendentes de faturamento |

**Eventos recebidos:**
- `service.completed` - Serviço finalizado

### Cuidadores (carinho-cuidadores)

**Base URL:** `https://cuidadores.carinho.com.vc/api`

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/caregivers/{id}` | GET | Dados do cuidador |
| `/caregivers/{id}/bank-account` | GET | Conta bancária |
| `/caregivers/{id}/rating` | GET | Avaliação média |
| `/webhooks/internal` | POST | Notificar eventos |

**Eventos enviados:**
- `payout.processed` - Repasse processado

**Eventos recebidos:**
- `caregiver.bank_updated` - Dados bancários atualizados

### Documentos (carinho-documentos-lgpd)

**Base URL:** `https://documentos.carinho.com.vc/api`

| Endpoint | Método | Uso |
|----------|--------|-----|
| `/documents` | POST | Upload de documentos |
| `/documents/{id}` | GET | Download |

---

## 4. Webhooks Internos

### Endpoint

`POST /webhooks/internal`

**Headers:**
```
Authorization: Bearer {INTERNAL_API_TOKEN}
Content-Type: application/json
```

### Payload

```json
{
  "event": "service.completed",
  "payload": {
    "service_id": 123,
    "client_id": 456,
    "caregiver_id": 789,
    "contract_id": 101,
    "hours": 4,
    "date": "2026-01-22",
    "timestamp": "2026-01-22T18:00:00Z"
  }
}
```

### Eventos Tratados

| Evento | Ação |
|--------|------|
| `service.completed` | Adiciona à fatura |
| `contract.activated` | Cria conta de cobrança |
| `caregiver.bank_updated` | Atualiza conta Stripe |

---

## 5. Autenticação

### Token Interno

Todas as chamadas entre sistemas usam Bearer Token:

```php
// config/integrations.php
'internal' => [
    'token' => env('INTERNAL_API_TOKEN'),
]
```

**Middleware:** `verify.internal.token`

### Validação de Webhooks

**Stripe:** Validação HMAC-SHA256 da assinatura
```php
$this->stripeClient->validateWebhookSignature($payload, $signature);
```

**Z-API:** Validação opcional por secret
```php
$this->whatsAppClient->isSignatureValid($payload, $signature);
```

---

## 6. Retry e Resiliência

### Políticas

| Serviço | Tentativas | Backoff |
|---------|------------|---------|
| Stripe | 3 | Exponencial |
| Z-API | 3 | Linear (1s) |
| Internos | 3 | Exponencial |

### Timeouts

| Serviço | Connect | Read |
|---------|---------|------|
| Stripe | 10s | 30s |
| Z-API | 3s | 10s |
| CRM | 5s | 8s |
| Operação | 5s | 8s |
| Cuidadores | 5s | 8s |

### Idempotência

Todas as operações de pagamento usam chaves de idempotência:

```php
$payment->idempotency_key = Payment::generateIdempotencyKey();
```

---

## 7. Logs e Monitoramento

### Canais de Log

- `single` - Log geral
- `whatsapp` - Comunicações WhatsApp
- `payments` - Operações de pagamento

### Métricas

Monitoradas via Laravel Horizon:
- Jobs processados/falhas
- Tempo de processamento
- Filas pendentes

### Health Checks

```
GET /health          # Check básico
GET /health/detailed # Check com dependências
```
