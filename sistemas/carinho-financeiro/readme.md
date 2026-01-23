# Carinho Financeiro

**Subdomínio:** financeiro.carinho.com.vc  
**Versão:** 2.0

## Descrição

Sistema de controle financeiro completo para a plataforma Carinho com Você. Responsável por gerenciar todo o ciclo financeiro: cobrança de clientes, processamento de pagamentos, repasses aos cuidadores, precificação, conciliação bancária, controle de fluxo de caixa, gestão de contas a pagar, relatórios gerenciais e workflow de aprovações.

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

### 10. Fluxo de Caixa Detalhado (v2.0)
- Registro de todas as movimentações financeiras
- Categorização por tipo (Receita, Despesa) e categoria
- Fluxo diário com saldo acumulado
- Previsão de caixa baseada em faturas a vencer
- Análise por categoria financeira

### 11. Contas a Pagar (v2.0)
- Gestão de obrigações da empresa
- Controle de vencimentos e pagamentos
- Categorização de despesas
- Integração com fluxo de caixa

### 12. Relatórios Gerenciais (v2.0)
- DRE (Demonstrativo de Resultado do Exercício) simplificado
- Aging de recebíveis com análise de risco
- Análise de margem por tipo de serviço
- KPIs financeiros (ticket médio, inadimplência, etc.)

### 13. Provisões - PCLD (v2.0)
- Cálculo automático de Provisão para Créditos de Liquidação Duvidosa
- Percentuais por faixa de aging (consolidados de mercado)
- Baixa e reversão de provisões
- Análise de efetividade da provisão vs perdas reais

### 14. Workflow de Aprovações (v2.0)
- Controle de operações sensíveis por alçada
- Aprovação automática dentro dos limites
- Aprovação manual para valores acima do threshold
- Expiração automática de solicitações pendentes
- Métricas de aprovação

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

### Fluxo de Caixa (v2.0)
- `GET /api/cash-flow/recent` - Transações recentes
- `GET /api/cash-flow/balance` - Saldo do período
- `GET /api/cash-flow/daily` - Fluxo diário
- `GET /api/cash-flow/by-category` - Fluxo por categoria
- `GET /api/cash-flow/forecast` - Previsão de caixa
- `POST /api/cash-flow` - Registra transação manual

### Relatórios (v2.0)
- `GET /api/reports/dre` - Demonstrativo de Resultado
- `GET /api/reports/aging` - Aging de recebíveis
- `GET /api/reports/margin-by-service` - Margem por tipo de serviço
- `GET /api/reports/kpis` - Indicadores financeiros

### Provisões (v2.0)
- `POST /api/provisions/pcld/calculate` - Calcula PCLD
- `POST /api/provisions/pcld/recalculate` - Recalcula PCLD
- `POST /api/provisions/pcld/write-off` - Baixa contra provisão
- `GET /api/provisions/summary` - Resumo de provisões
- `GET /api/provisions/effectiveness` - Análise de efetividade

### Aprovações (v2.0)
- `GET /api/approvals/pending` - Lista pendentes
- `GET /api/approvals/check` - Verifica se requer aprovação
- `GET /api/approvals/status` - Status de aprovação
- `GET /api/approvals/metrics` - Métricas de aprovação
- `POST /api/approvals/request` - Cria solicitação
- `POST /api/approvals/{id}/approve` - Aprova solicitação
- `POST /api/approvals/{id}/reject` - Rejeita solicitação

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
