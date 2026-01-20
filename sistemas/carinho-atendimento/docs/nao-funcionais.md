# Requisitos nao funcionais

## Performance
- Webhooks gravam evento e processam via fila.
- Envios de WhatsApp e e-mail sao assincronos.
- Lookup de dominios em cache para reduzir consultas.

## Integracao com outros sistemas
- CRM: apenas sincroniza lead e incidentes.
- Operacao: recebe alertas de emergencia.
- Integracoes: recebe eventos para automacoes ponta a ponta.

## Seguranca e LGPD
- Validacao de assinatura de webhook.
- Token interno para rotas sensiveis.
- Logs devem mascarar dados sensiveis (PII).

## Escalabilidade
- Workers horizontais para mensagens e webhooks.
- Timeouts curtos em integracoes externas.
