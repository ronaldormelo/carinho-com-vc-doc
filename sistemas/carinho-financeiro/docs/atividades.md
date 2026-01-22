# Atividades

Lista de atividades para viabilizar o sistema Carinho Financeiro.

## Estrutura Financeira ✅

- [x] Configurar conta bancária PJ e rotina de conciliação
- [x] Definir separação PF x PJ e política de reembolso
- [x] Implementar BillingAccount para gerenciar contas de clientes
- [x] Implementar BankAccount com criptografia de dados sensíveis

## Precificação e Planos ✅

- [x] Definir preços por hora, pacote e mensalidade
- [x] Calcular preço mínimo viável e margem alvo
- [x] Definir comissão ou percentual do cuidador
- [x] Implementar PricingService com cálculos automáticos
- [x] Criar PricePlan e PriceRule para regras flexíveis
- [x] Adicionais: noturno (+20%), fim de semana (+30%), feriado (+50%)
- [x] Desconto para pacote mensal (-10%)

## Contas e Repasses ✅

- [x] Implementar contas a receber (Invoice, InvoiceItem)
- [x] Implementar contas a pagar (Payout, PayoutItem)
- [x] Controlar recebimentos por cliente
- [x] Controlar repasses por cuidador
- [x] Definir prazos e regras de pagamento
- [x] Implementar PayoutService com geração automática semanal
- [x] Integração com Stripe Connect para transferências

## Política de Cancelamento ✅

- [x] Definir regras de cancelamento (24h/12h/6h)
- [x] Implementar cálculo automático de reembolso
- [x] Taxa administrativa para reembolsos parciais
- [x] Reembolso total para cancelamento pelo cuidador
- [x] CancellationService com todas as regras

## Fiscal ✅

- [x] Definir processo de emissão de nota fiscal
- [x] Criar modelo FiscalDocument
- [x] Armazenar comprovantes e documentos fiscais
- [x] Estrutura preparada para integração com NFS-e

## Relatórios ✅

- [x] Implementar ReconciliationService
- [x] Gerar fluxo de caixa mensal
- [x] Monitorar ticket médio, margem e inadimplência
- [x] Indicadores financeiros automáticos
- [x] Alertas de discrepância

## Integrações ✅

- [x] Integração Stripe para pagamentos (PIX, Boleto, Cartão)
- [x] Integração Stripe Connect para repasses
- [x] Integração Z-API para notificações WhatsApp
- [x] Cliente CRM para dados de contratos
- [x] Cliente Operação para serviços executados
- [x] Cliente Cuidadores para dados bancários
- [x] Webhooks para eventos de pagamento

## Jobs Assíncronos ✅

- [x] ProcessStripeWebhook - Processa eventos do Stripe
- [x] ProcessOverdueInvoices - Marca faturas vencidas
- [x] SendDueReminders - Envia lembretes de vencimento
- [x] ProcessWeeklyPayouts - Processa repasses semanais
- [x] SyncServiceToInvoice - Sincroniza serviços para faturamento
- [x] ProcessMonthlyReconciliation - Fecha conciliação mensal

## API ✅

- [x] CRUD de faturas com itens
- [x] Criar e processar pagamentos
- [x] Gerar links PIX e Boleto
- [x] Processar reembolsos
- [x] CRUD de repasses
- [x] Calcular preços e margens
- [x] Relatórios de conciliação
- [x] Health checks

## Documentação ✅

- [x] README completo
- [x] Documentação de políticas
- [x] Documentação de integrações
- [x] Arquivo .env.example
- [x] Comentários no código
