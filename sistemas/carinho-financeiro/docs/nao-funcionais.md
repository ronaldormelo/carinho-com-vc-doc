# Requisitos não funcionais — Financeiro

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).  
Política comercial canônica: [politicas.md](politicas.md).

## Usabilidade

- Cliente: link de Pix/boleto/cartão (Stripe); WhatsApp só para lembrete e confirmação.
- Operador: fatura, inadimplência, aprovação acima da alçada, DRE, aging.
- Cuidador: repasse sexta, mínimo R$ 50, D+3 após conclusão.
- Manual: [manual-operacional.md](manual-operacional.md).

Reembolso publicado: >24 h 100%; 6–24 h 50% (taxa admin 5% no parcial); <6 h 0%. Cancelamento pelo cuidador = 100% ao cliente. A Operação alinha **horas** (24 / 6) a esta tabela; taxas internas de operação não substituem o reembolso.

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Webhook Stripe | Ack rápido + processamento idempotente |
| Precificação / settings | Cache (README) |
| Jobs | Overdue diário; lembrete D-3; payout sexta; conciliação mensal |
| Workers | Horizon |
| Integrações | Timeout no `config/integrations.php` do módulo |

Gargalos: Stripe lento ou webhook duplicado (mitigado por idempotência); geração semanal de payouts; DRE em período longo.

## Integração

Não faz match nem check-in. Recebe serviço da Operação e contrato do CRM; dados bancários do Cuidadores; comprovantes no Documentos. [integracoes.md](integracoes.md).

## Segurança

- Token interno; RBAC financeiro/admin.
- Criptografia de dados bancários/CPF (README).
- Assinatura Stripe e Z-API.
- Alteração de comissão/preço via settings com auditoria — não “hardcode” em deploy.
- Nunca logar `STRIPE_SECRET_KEY` nem payload de cartão.

NFS-e (`NFSE_*`) está documentada como futura: não assumir emissão ativa.
