# Arquitetura

## Visão Geral

Sistema financeiro de cobrança e repasses (financeiro.carinho.com.vc). Controla entradas, saídas, notas e margens com rastreabilidade e conformidade.

## Stack

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de Dados:** MySQL 8.0
- **Cache e Filas:** Redis
- **Gateway de Pagamento:** Stripe
- **Notificações:** Z-API (WhatsApp)

## Componentes Principais

### Módulos de Negócio

```
┌─────────────────────────────────────────────────────────────────┐
│                     CARINHO FINANCEIRO                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐       │
│  │   INVOICES    │  │   PAYMENTS    │  │   PAYOUTS     │       │
│  │  (Faturas)    │  │ (Pagamentos)  │  │  (Repasses)   │       │
│  └───────┬───────┘  └───────┬───────┘  └───────┬───────┘       │
│          │                  │                  │                │
│          └──────────────────┼──────────────────┘                │
│                             │                                   │
│  ┌───────────────┐  ┌──────┴──────┐  ┌───────────────┐         │
│  │   PRICING     │  │  SERVICES   │  │ RECONCILIATION│         │
│  │ (Precificação)│  │ (Serviços)  │  │ (Conciliação) │         │
│  └───────────────┘  └─────────────┘  └───────────────┘         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Fluxo de Dados

```
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│ Operação│────>│ Fatura  │────>│Pagamento│────>│ Repasse │
│(Serviço)│     │(Invoice)│     │(Payment)│     │(Payout) │
└─────────┘     └─────────┘     └─────────┘     └─────────┘
     │               │               │               │
     │               │               │               │
     v               v               v               v
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│   CRM   │     │  Stripe │     │  Stripe │     │ Stripe  │
│(Contrato)     │  (PIX)  │     │(Webhook)│     │(Connect)│
└─────────┘     └─────────┘     └─────────┘     └─────────┘
```

## Services (Camada de Negócio)

| Service | Responsabilidade |
|---------|------------------|
| `InvoiceService` | Criação, cancelamento e gestão de faturas |
| `PaymentService` | Processamento de pagamentos via Stripe |
| `PayoutService` | Cálculo e processamento de repasses |
| `PricingService` | Cálculo de preços e margens |
| `CancellationService` | Políticas de cancelamento e reembolso |
| `ReconciliationService` | Conciliação bancária e relatórios |
| `NotificationService` | Notificações via WhatsApp |

## Integrações

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTEGRAÇÕES                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  EXTERNAS                          INTERNAS                     │
│  ┌───────────────┐                 ┌───────────────┐            │
│  │    STRIPE     │                 │     CRM       │            │
│  │  (Pagamentos) │                 │  (Contratos)  │            │
│  └───────────────┘                 └───────────────┘            │
│  ┌───────────────┐                 ┌───────────────┐            │
│  │    Z-API      │                 │   OPERAÇÃO    │            │
│  │  (WhatsApp)   │                 │  (Serviços)   │            │
│  └───────────────┘                 └───────────────┘            │
│                                    ┌───────────────┐            │
│                                    │  CUIDADORES   │            │
│                                    │(Dados Banc.)  │            │
│                                    └───────────────┘            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Dados e Armazenamento

### Tabelas de Domínio
- `domain_payment_method` - PIX, Boleto, Cartão
- `domain_account_status` - Active, Inactive
- `domain_invoice_status` - Open, Paid, Overdue, Canceled
- `domain_payment_status` - Pending, Paid, Failed, Refunded
- `domain_payout_status` - Open, Paid, Canceled
- `domain_service_type` - Horista, Diário, Mensal
- `domain_owner_type` - Client, Caregiver, Company
- `domain_reconciliation_status` - Open, Closed

### Tabelas Principais
- `billing_accounts` - Contas de cobrança de clientes
- `invoices` - Faturas
- `invoice_items` - Itens das faturas
- `payments` - Pagamentos
- `payouts` - Repasses aos cuidadores
- `payout_items` - Itens dos repasses
- `price_plans` - Planos de preço
- `price_rules` - Regras de precificação
- `bank_accounts` - Contas bancárias
- `reconciliations` - Conciliações
- `fiscal_documents` - Documentos fiscais

## Segurança e LGPD

- Controle de acesso restrito (financeiro/admin)
- Criptografia de dados sensíveis (conta bancária, CPF)
- Auditoria de alterações via Spatie Activity Log
- Idempotência em processamentos de pagamento
- Validação de assinatura em webhooks
- Token interno para comunicação entre sistemas

## Escalabilidade e Desempenho

- Jobs assíncronos para processamento pesado
- Cache de consultas recorrentes (planos de preço)
- Índices otimizados por período e cliente
- Laravel Horizon para monitoramento de filas
- Timeout e retry configurados por integração

## Observabilidade e Operação

- Logs estruturados por canal (payments, whatsapp)
- Alertas para falhas de emissão e inconsistências
- Health checks: `/health` e `/health/detailed`
- Métricas de performance via Horizon Dashboard

## Backup e Resiliência

- Backup diário de banco e documentos fiscais
- Política de retenção definida
- Reconciliação mensal obrigatória
- Idempotência garante consistência em falhas
