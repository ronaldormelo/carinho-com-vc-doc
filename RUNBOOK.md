# Runbook da plataforma

Procedimentos curtos para o operador. O detalhe de filas, DLQ e Z-API está no [runbook de Integrações](sistemas/carinho-integracoes/docs/runbook-operacional.md).

## Ordem de checagem (manhã)

1. WhatsApp (Z-API) conectado — status no hub (`GET /status` em integracoes.carinho.com.vc).
2. Health dos nove apps (`/health` ou `/api/health`).
3. Filas Redis / Horizon: `notifications`, `integrations-high`, `emergencies`.
4. DLQ do hub vazia ou revisada.
5. Faturas vencidas e repasse (sexta) no Financeiro.

Fora do horário comercial o Atendimento deve responder com template automático (`config/atendimento.php`).

## Sintomas → módulo

| Sintoma | Onde olhar primeiro |
|---------|---------------------|
| Família não recebe WhatsApp | Integrações + Z-API; depois Atendimento (fila de envio) |
| Lead do site não aparece no CRM | Site job `SyncLeadToCrm`; webhook `POST /webhooks/site/lead` no hub |
| Atendente sem conversa nova | Webhook Z-API no hub/Atendimento; assinatura `ZAPI_WEBHOOK_SECRET` |
| Sem cuidador na agenda | Operação match; Cuidadores disponibilidade; antecedência 24 h |
| Check-in recusado | GPS 500 m; tolerância 15 min; token do app do cuidador |
| Pagamento PIX não baixa | Webhook Stripe no Financeiro; idempotência |
| Contrato não assina | Documentos OTP; Z-API; link expirado |
| Pedido LGPD parado | Jobs `ProcessDataExport` / `ProcessDataDeletion` (timeout de export 10 min) |
| Post não publicou | Marketing `PublishScheduledContent`; token Meta |

## Infra local

```bash
# Na raiz: MariaDB + Redis
docker compose up -d

# Um sistema
cd sistemas/carinho-atendimento
docker compose up -d
```

Compose da raiz **não** se chama `docker-compose.mysql.yml`. Guia completo: [README.DOCKER.md](README.DOCKER.md).

MariaDB não sobe → nada autentica. Redis não sobe → sessão/cache/fila falham mesmo com HTTP 200 no app.

## Emergência em campo

1. Registrar no Atendimento (severidade high/critical notifica Operação).
2. Operação: `POST /api/emergencies` e fila `emergencies` (escalonamento a cada 10 min).
3. Substituição: teto de 120 min de busca.
4. Cliente e cuidador só pelo WhatsApp oficial.

## Contatos documentados nos módulos

- Operação: operacao@carinho.com.vc
- Emergência: emergencia@carinho.com.vc
- Cuidadores (e-mail de sistema): cuidadores@carinho.com.vc

Atualize estes endereços no `.env` (`MAIL_FROM_*`, `EMAIL_REPLY_TO`) — os valores dos READMEs são os padrões de código, não um SOC 24×7.
