# Requisitos não funcionais — Integrações

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).  
Operação diária: [runbook-operacional.md](runbook-operacional.md).  
Contratos: [matriz-integracoes.md](matriz-integracoes.md).

## Usabilidade

- Operador de TI usa `/health` (público), `/horizon`, DLQ e, com `X-API-Key`, `/status` e reset de circuit breaker.
- Guia: [guia-usuario-operacional.md](guia-usuario-operacional.md).

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Webhook | Persistir evento e responder; processar na fila |
| `integrations-high` | 2–5 workers (webhooks críticos) |
| `integrations` | 3–10 workers |
| `notifications` | 2 workers (WhatsApp/e-mail) |
| `integrations-low` / retry | 1 worker cada |
| Timeout para módulos | 10 s (matriz e `config/integrations.php`) |
| Z-API | connect 3 s, request 10 s |
| Idempotência | `idempotency_key` |
| Circuit breaker | Implementado no código do módulo |

Gargalos: Z-API desconectado; um destino lento (CRM/Operação/Financeiro); DLQ sem revisão. Expectativa da matriz para CRM/Operação/Financeiro: 99,9% — meta de chamada, não SLO medido. Disponibilidade de desenho dos NFRs de Operação/Cuidadores: 99,5%.

Sync documentado: CRM→Operação (horário); Operação→Financeiro (23:00); CRM→Financeiro (06:00 e 18:00); Cuidadores→CRM (4 h).

## Integração

Único lugar para orquestração ponta a ponta. Módulos não devem reimplementar o mesmo fluxo de lead→WhatsApp→CRM. [arquitetura.md](arquitetura.md), [integracao-zapi.md](integracao-zapi.md), [contratos-rotas.md](contratos-rotas.md) (rotas reais vs clientes).

## Segurança

- API: `X-API-Key`; 60 req/min.
- Webhooks: HMAC-SHA256.
- Logs sem PII completa; payload pode ser criptografado; anonimização após processamento (README).
- Secrets Z-API e `CARINHO_*_API_KEY` só em `.env`.
