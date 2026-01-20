# Integracoes externas e internas

## WhatsApp (Z-API)
Configuracoes em config/integrations.php:
- ZAPI_BASE_URL
- ZAPI_INSTANCE_ID
- ZAPI_TOKEN
- ZAPI_CLIENT_TOKEN (opcional)
- ZAPI_WEBHOOK_SECRET (opcional)

Endpoints usados:
- POST /instances/{instance_id}/token/{token}/send-text
- POST /instances/{instance_id}/token/{token}/send-image

Webhook:
- POST /api/webhooks/whatsapp/z-api
- Header recomendado: X-Zapi-Signature ou X-Webhook-Signature

## CRM (interno)
Configuracoes em config/integrations.php:
- CRM_BASE_URL
- CRM_TOKEN

Endpoints usados:
- POST /leads (upsert de lead)
- POST /incidents (registro de incidente)

## Operacao (interno)
Configuracoes:
- OPERACAO_BASE_URL
- OPERACAO_TOKEN

Endpoints usados:
- POST /emergencies

## Integracoes (interno)
Configuracoes:
- INTEGRACOES_BASE_URL
- INTEGRACOES_TOKEN

Endpoints usados:
- POST /events

## E-mail
Configuracoes:
- EMAIL_FROM
- EMAIL_REPLY_TO
- BRAND_REPLY_TO

Templates:
- resources/views/emails/proposta.blade.php
- resources/views/emails/contrato.blade.php
