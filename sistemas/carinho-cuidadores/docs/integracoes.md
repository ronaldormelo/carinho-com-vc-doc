# Integracoes do Sistema Carinho Cuidadores

## Visao Geral

O sistema Carinho Cuidadores integra-se com multiplos sistemas internos e externos
para garantir um fluxo de dados consistente e automatizado.

## Integracoes Externas

### WhatsApp (Z-API)

**Provedor:** Z-API (https://z-api.io)  
**Documentacao:** https://developer.z-api.io/

#### Configuracao

```env
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=sua-instancia
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token
ZAPI_WEBHOOK_SECRET=seu-webhook-secret
```

#### Endpoints Utilizados

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/instances/{id}/token/{token}/send-text` | POST | Envio de mensagem de texto |
| `/instances/{id}/token/{token}/send-image` | POST | Envio de imagem |
| `/instances/{id}/token/{token}/send-document` | POST | Envio de documento |
| `/instances/{id}/token/{token}/send-button-list` | POST | Mensagem com botoes |
| `/instances/{id}/token/{token}/status` | GET | Status da instancia |

#### Webhook

- **URL:** `POST /api/webhooks/whatsapp/z-api`
- **Headers:** `X-Zapi-Signature` ou `X-Webhook-Signature`
- **Validacao:** HMAC SHA256

#### Funcionalidades

- Envio de notificacoes automaticas
- Envio de contratos e documentos
- Recebimento de mensagens dos cuidadores
- Processamento de comandos simples (status, ajuda)

---

## Integracoes Internas

### CRM

**Base URL:** `https://crm.carinho.com.vc/api`

#### Endpoints

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/caregivers` | POST | Sincroniza dados do cuidador |
| `/caregivers/{id}/status` | PATCH | Atualiza status |
| `/incidents` | POST | Registra incidente |
| `/ratings` | POST | Sincroniza avaliacao |
| `/caregivers/{id}/history` | GET | Historico do cuidador |

#### Eventos Sincronizados

- Criacao de cuidador
- Alteracao de status
- Registro de incidentes
- Avaliacoes recebidas

---

### Operacao

**Base URL:** `https://operacao.carinho.com.vc/api`

#### Endpoints

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/caregivers/{id}/availability` | POST/GET | Sincroniza disponibilidade |
| `/caregivers/{id}/activated` | POST | Notifica ativacao |
| `/caregivers/{id}/deactivated` | POST | Notifica desativacao |
| `/services/{id}/checkin` | POST | Registra check-in |
| `/services/{id}/checkout` | POST | Registra check-out |
| `/caregivers/available` | POST | Busca cuidadores disponiveis |

#### Funcionalidades

- Sincronizacao de disponibilidade em tempo real
- Notificacao de mudancas de status
- Suporte a check-in/check-out de servicos

---

### Documentos/LGPD

**Base URL:** `https://documentos.carinho.com.vc/api`

#### Endpoints

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/documents/upload` | POST | Upload de documento |
| `/documents/validate` | POST | Validacao automatica |
| `/documents/signed-url` | POST | URL assinada para visualizacao |
| `/contracts` | POST | Cria contrato |
| `/contracts/{id}/sign` | POST | Registra assinatura |
| `/contracts/{id}/signature-url` | GET | URL para assinatura |
| `/data-export` | POST | Exportacao de dados (LGPD) |

#### Funcionalidades

- Armazenamento seguro de documentos
- Validacao automatica com OCR/ML
- Geracao e assinatura digital de contratos
- Conformidade com LGPD

---

### Atendimento

**Base URL:** `https://atendimento.carinho.com.vc/api`

#### Endpoints

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/notifications` | POST | Registra notificacao enviada |
| `/messages` | POST | Envia mensagem via atendimento |
| `/conversations` | POST/GET | Gerencia conversas |
| `/broadcasts` | POST | Envio em massa |
| `/rating-requests` | POST | Solicita avaliacao |

#### Funcionalidades

- Registro centralizado de comunicacoes
- Encaminhamento para atendimento humano
- Envio de comunicados em massa

---

### Hub de Integracoes

**Base URL:** `https://integracoes.carinho.com.vc/api`

#### Endpoints

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/events` | POST | Publica evento |
| `/webhooks` | POST | Registra webhook |

#### Eventos Publicados

| Evento | Descricao |
|--------|-----------|
| `caregiver.created` | Cuidador cadastrado |
| `caregiver.activated` | Cuidador ativado |
| `caregiver.deactivated` | Cuidador desativado |
| `document.uploaded` | Documento enviado |
| `document.verified` | Documento aprovado |
| `contract.signed` | Contrato assinado |
| `rating.received` | Avaliacao recebida |
| `incident.registered` | Incidente registrado |

---

## Autenticacao

### Token Interno

Todas as integracoes internas utilizam token Bearer:

```http
Authorization: Bearer {token}
X-Source: carinho-cuidadores
```

### Z-API

Autenticacao via URL e header opcional:

```http
client-token: {client-token}
```

---

## Tratamento de Erros

### Retry Policy

- **Tentativas:** 3
- **Backoff:** Exponencial (30s, 60s, 120s)
- **Timeout:** Configuravel por integracao

### Logging

Todos os erros de integracao sao registrados:

```php
Log::error('Integracao falhou', [
    'service' => 'crm',
    'endpoint' => '/caregivers',
    'status' => 500,
    'error' => 'Connection timeout',
]);
```

---

## Webhooks Recebidos

### Z-API (WhatsApp)

```http
POST /api/webhooks/whatsapp/z-api
X-Zapi-Signature: sha256=...
```

### Documentos

```http
POST /api/webhooks/documents
X-Internal-Token: {token}
```

Eventos:
- `document.signed` - Documento assinado
- `document.rejected` - Documento rejeitado

### Operacao

```http
POST /api/webhooks/operacao
X-Internal-Token: {token}
```

Eventos:
- `service.completed` - Servico concluido
- `service.started` - Servico iniciado
