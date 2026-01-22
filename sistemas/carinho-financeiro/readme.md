# Carinho Financeiro

**Subdomínio:** financeiro.carinho.com.vc

## Descrição

Sistema de controle financeiro completo para a plataforma Carinho com Você. Responsável por gerenciar todo o ciclo financeiro: cobrança de clientes, processamento de pagamentos, repasses aos cuidadores, precificação e conciliação bancária.

## Stack Tecnológica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de Dados:** MySQL 8.0
- **Cache e Filas:** Redis
- **Gateway de Pagamento:** Stripe
- **Notificações:** Z-API (WhatsApp)

## Módulos Implementados

### 1. Registro de Entradas e Saídas
- Lançamentos financeiros com rastreabilidade completa
- Auditoria de todas as operações via Activity Log
- Separação clara entre receitas e despesas

### 2. Contas a Receber (Faturas)
- Criação automática e manual de faturas
- Itens detalhados por serviço prestado
- Cálculo automático com adicionais (noturno, fim de semana, feriados)
- Controle de vencimento e inadimplência
- Cancelamento com política de reembolso automática

### 3. Contas a Pagar (Repasses)
- Cálculo automático de comissões por tipo de serviço
- Geração semanal de repasses aos cuidadores
- Processamento via Stripe Connect
- Controle de valor mínimo para repasse

### 4. Pagamentos
- Integração completa com Stripe
- Suporte a PIX, Boleto e Cartão de Crédito
- Webhooks para confirmação automática
- Reembolso parcial e total
- Idempotência em todas as operações

### 5. Precificação
- Preços por hora, pacote e mensalidade
- Regras de adicional (noturno, fim de semana, feriado)
- Descontos para pacotes mensais
- Cálculo de preço mínimo viável
- Planos de preço configuráveis por região

### 6. Comissões e Margens
- Percentual do cuidador: 70% (horista), 72% (diário), 75% (mensal)
- Bônus por avaliação (até +2%)
- Bônus por tempo de casa (até +3%)
- Margem mínima: 25% | Margem alvo: 30%

### 7. Nota Fiscal
- Estrutura preparada para emissão de NFS-e
- Armazenamento de documentos fiscais
- Conciliação com faturas

### 8. Conciliação Bancária
- Processamento mensal automático
- Relatórios de fluxo de caixa
- Indicadores financeiros (ticket médio, inadimplência, margem)
- Alertas de discrepância

### 9. Gestão de Configurações
- Todas as configurações de valores e percentuais armazenadas no banco de dados
- Alteração dinâmica sem necessidade de deploy
- Histórico de todas as alterações com auditoria
- Cache para performance
- Categorias organizadas: Pagamento, Cancelamento, Comissões, Precificação, etc.

## Políticas Financeiras

### Política de Pagamento
- **Tipo:** Sempre ADIANTADO (pré-pago)
- **Prazo:** Pagamento 24h antes do início do serviço
- **Juros por atraso:** 0,033% ao dia (~1% ao mês)
- **Multa por atraso:** 2%

### Política de Cancelamento
| Período | Reembolso |
|---------|-----------|
| Mais de 24h antes | 100% (total) |
| Entre 6h e 24h antes | 50% (parcial) |
| Menos de 6h antes | 0% (sem reembolso) |

- Taxa administrativa de 5% aplica-se aos reembolsos parciais
- Cancelamento pelo cuidador = reembolso total ao cliente

### Política de Repasses
- **Frequência:** Semanal (sextas-feiras)
- **Valor mínimo:** R$ 50,00
- **Liberação:** 3 dias após conclusão do serviço

## Integrações

### Stripe (Pagamentos)
- PaymentIntents para PIX, Boleto e Cartão
- Stripe Connect para repasses aos cuidadores
- Webhooks para eventos de pagamento
- Documentação: https://stripe.com/docs/api

### Z-API (WhatsApp)
- Notificações de fatura criada
- Lembretes de vencimento
- Confirmação de pagamento
- Notificação de repasse processado
- Documentação: https://developer.z-api.io/

### Sistemas Internos
- **CRM:** Contratos e dados de clientes
- **Operação:** Serviços executados
- **Cuidadores:** Dados bancários e avaliações
- **Documentos:** Notas fiscais e comprovantes

## Estrutura de Diretórios

```
carinho-financeiro/
├── app/
│   ├── Events/              # Eventos do sistema
│   ├── Http/
│   │   ├── Controllers/     # Controllers da API
│   │   ├── Middleware/      # Middlewares
│   │   ├── Requests/        # Form Requests
│   │   └── Resources/       # API Resources
│   ├── Integrations/        # Clientes de integração
│   │   ├── Stripe/          # Stripe Payments
│   │   ├── WhatsApp/        # Z-API
│   │   ├── Crm/             # Sistema CRM
│   │   ├── Operacao/        # Sistema Operação
│   │   └── Cuidadores/      # Sistema Cuidadores
│   ├── Jobs/                # Jobs assíncronos
│   ├── Models/              # Eloquent Models
│   └── Services/            # Serviços de negócio
├── config/
│   ├── branding.php         # Identidade visual
│   ├── financeiro.php       # Configurações financeiras
│   └── integrations.php     # Configurações de integração
├── database/
│   ├── migrations/
│   └── schema.sql
├── docs/
│   ├── arquitetura.md
│   ├── atividades.md
│   ├── integracoes.md
│   └── politicas.md
└── routes/
    ├── api.php              # Rotas da API
    └── web.php              # Webhooks e health
```

## API Endpoints

### Faturas
- `GET /api/invoices` - Lista faturas
- `POST /api/invoices` - Cria fatura
- `GET /api/invoices/{id}` - Detalhes da fatura
- `POST /api/invoices/{id}/items` - Adiciona item
- `POST /api/invoices/{id}/cancel` - Cancela fatura

### Pagamentos
- `GET /api/payments` - Lista pagamentos
- `POST /api/payments` - Cria pagamento
- `POST /api/payments/invoice/{id}/generate-link` - Gera link PIX/Boleto
- `POST /api/payments/{id}/refund` - Processa reembolso

### Repasses
- `GET /api/payouts` - Lista repasses
- `POST /api/payouts` - Cria repasse
- `POST /api/payouts/generate` - Gera repasses do período
- `POST /api/payouts/{id}/process` - Processa transferência

### Precificação
- `POST /api/pricing/calculate` - Calcula preço
- `POST /api/pricing/simulate` - Simula preços
- `GET /api/pricing/cancellation-policy` - Política de cancelamento
- `GET /api/pricing/commission-config` - Configuração de comissões

### Conciliação
- `POST /api/reconciliation/process` - Processa conciliação
- `GET /api/reconciliation/cash-flow` - Fluxo de caixa
- `GET /api/reconciliation/indicators` - Indicadores financeiros

### Configurações
- `GET /api/settings` - Lista todas configurações
- `GET /api/settings/categories` - Lista categorias
- `GET /api/settings/category/{code}` - Configurações de uma categoria
- `GET /api/settings/{key}` - Detalhes de uma configuração
- `PUT /api/settings/{key}` - Atualiza configuração
- `POST /api/settings/{key}/restore` - Restaura valor padrão
- `GET /api/settings/{key}/history` - Histórico de alterações
- `GET /api/settings/config/commission` - Configurações de comissão
- `GET /api/settings/config/pricing` - Configurações de preço
- `GET /api/settings/config/cancellation` - Política de cancelamento

## Jobs Agendados

| Job | Frequência | Descrição |
|-----|------------|-----------|
| ProcessOverdueInvoices | Diário | Marca faturas vencidas |
| SendDueReminders | Diário | Envia lembretes (3 dias antes) |
| ProcessWeeklyPayouts | Semanal (sexta) | Processa repasses |
| ProcessMonthlyReconciliation | Mensal | Fecha conciliação |

## Configuração

1. Clone o repositório
2. Copie `.env.example` para `.env`
3. Configure as variáveis de ambiente (Stripe, Z-API, banco)
4. Execute `composer install`
5. Execute `php artisan migrate`
6. Execute `php artisan db:seed --class=SettingsSeeder` para criar configurações padrão
7. Configure as filas com `php artisan horizon`

## Gestão de Configurações

As configurações de valores e percentuais são armazenadas no banco de dados, permitindo alterações dinâmicas sem deploy:

### Categorias de Configuração

| Categoria | Descrição |
|-----------|-----------|
| `payment` | Prazos e encargos de pagamento |
| `cancellation` | Políticas de cancelamento e reembolso |
| `commission` | Percentuais de comissão por tipo de serviço |
| `pricing` | Valores base e adicionais |
| `margin` | Margens mínima e alvo |
| `payout` | Configurações de repasse |
| `fiscal` | Configurações fiscais |
| `limits` | Limites de crédito e inadimplência |
| `bonus` | Bônus por avaliação e tempo de casa |

### Exemplo de Alteração via API

```bash
# Alterar comissão do cuidador horista para 72%
curl -X PUT https://financeiro.carinho.com.vc/api/settings/commission_horista \
  -H "Authorization: Bearer TOKEN" \
  -d '{"value": 72, "reason": "Ajuste de mercado"}'

# Consultar histórico de alterações
curl https://financeiro.carinho.com.vc/api/settings/commission_horista/history
```

## Segurança

- Autenticação via token interno entre sistemas
- Criptografia de dados bancários sensíveis
- Validação de assinatura em webhooks (Stripe, Z-API)
- Auditoria de todas as operações financeiras
- Idempotência em processamentos de pagamento

## Observabilidade

- Logs estruturados de todas as operações
- Alertas para falhas de pagamento
- Métricas de performance via Horizon
- Health checks: `/health` e `/health/detailed`
