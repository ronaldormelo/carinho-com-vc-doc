# Requisitos não funcionais — Atendimento

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).

## Usabilidade

- Atendente: inbox, script de triagem, prioridade, motivo de perda. Caminho feliz no [guia-rapido-atendente.md](guia-rapido-atendente.md).
- Família: só o WhatsApp oficial e auto-resposta fora do horário. Não pedir cadastro neste sistema.
- Supervisor: SLA em risco (80% do prazo), N2/N3, incidentes.
- Linguagem alinhada à [identidade](../../../00%20-%20Identidade%20da%20Marca.md).

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Webhook inbound | Persistir e ack; processar em fila (meta de ack < 100 ms). Health: `GET /up` e `GET /api/health`. Horizon no composer sem `config/horizon.php` versionado. |
| Envio WhatsApp / e-mail | Assíncrono |
| Cache | Domínios 12 h; feriados 24 h |
| SLA urgente | 5 min / 1 h (ver seed) |
| Timeout Z-API | connect 3 s, request 10 s (padrão das integrações) |
| Workers | Escala horizontal da fila de mensagens |

Gargalos: instância Z-API desconectada; backlog da fila; CRM lento no sync (job, não no request da inbox). Fora do horário o SLA humano não substitui o template automático.

## Integração

CRM recebe lead e incidente; Operação recebe emergência; hub recebe eventos. Este módulo não chama Financeiro nem Cuidadores no caminho feliz. [integracoes.md](integracoes.md).

## Segurança e LGPD

- Validação de assinatura do webhook.
- Token interno em rotas sensíveis.
- Logs sem PII completa.
- Retenção de conversas alinhada ao Documentos.
- Audit trail de ações na conversa.

## Escalabilidade e backup

Workers horizontais; retry e DLQ para mensagem com falha; backup diário do schema (arquitetura do módulo).
