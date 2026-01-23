# Integrações

Documentação das integrações do sistema Carinho Documentos e LGPD.

## Integrações Externas

### AWS S3

**Documentação:** https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-examples.html

**Cliente:** `App\Integrations\Storage\S3StorageClient`

**Configuração:**
```php
// config/integrations.php
'aws' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'sa-east-1'),
    'bucket' => env('AWS_BUCKET', 'carinho-documentos'),
    'encryption' => 'AES256',
    'signed_url_expiration' => 60, // minutos
],
```

**Métodos Principais:**
- `upload($file, $path, $metadata)` - Upload de arquivo
- `download($path)` - Download de arquivo
- `getSignedUrl($path, $expiration)` - URL pré-assinada
- `delete($path)` - Exclusão de arquivo
- `exists($path)` - Verifica existência

**Estrutura de Pastas:**
```
carinho-documentos/
├── clients/{client_id}/
├── caregivers/{caregiver_id}/
├── contracts/{year}/{month}/
├── templates/
└── exports/
```

### Z-API (WhatsApp)

**Documentação:** https://developer.z-api.io/

**Cliente:** `App\Integrations\WhatsApp\ZApiClient`

**Configuração:**
```php
// config/integrations.php
'whatsapp' => [
    'provider' => 'z-api',
    'base_url' => env('ZAPI_BASE_URL', 'https://api.z-api.io'),
    'instance_id' => env('ZAPI_INSTANCE_ID'),
    'token' => env('ZAPI_TOKEN'),
    'client_token' => env('ZAPI_CLIENT_TOKEN'),
    'timeout' => 10,
],
```

**Endpoints Utilizados:**
- `POST /instances/{instance}/token/{token}/send-text` - Mensagem de texto
- `POST /instances/{instance}/token/{token}/send-document` - Envio de documento
- `POST /instances/{instance}/token/{token}/send-link` - Link com preview
- `GET /instances/{instance}/token/{token}/status` - Status da instância

**Métodos Principais:**
- `sendTextMessage($phone, $message)` - Enviar texto
- `sendContractLink($phone, $url, $name)` - Link de contrato
- `sendOtpCode($phone, $code)` - Código OTP
- `sendSignatureConfirmation($phone, $type, $url)` - Confirmação
- `sendDocument($phone, $url, $filename)` - Documento

## Integrações Internas

### CRM

**Cliente:** `App\Integrations\Crm\CrmClient`

**Base URL:** `https://crm.carinho.com.vc/api`

**Webhooks Enviados:**
- `POST /webhooks/documents/contract-created`
- `POST /webhooks/documents/contract-signed`
- `POST /webhooks/documents/consent-updated`
- `POST /webhooks/documents/data-request`

**Payload de Contrato Criado:**
```json
{
    "document_id": 123,
    "owner_type": "client",
    "owner_id": 456,
    "contract_type": "contrato_cliente",
    "signature_url": "https://...",
    "created_at": "2026-01-22T10:00:00Z",
    "source": "carinho-documentos-lgpd"
}
```

### Cuidadores

**Cliente:** `App\Integrations\Cuidadores\CuidadoresClient`

**Base URL:** `https://cuidadores.carinho.com.vc/api`

**Webhooks Enviados:**
- `POST /webhooks/documents/contract-created`
- `POST /webhooks/documents/contract-signed`
- `POST /webhooks/documents/document-uploaded`

**Métodos:**
- `notifyContractCreated($data)` - Notifica contrato
- `notifyContractSigned($data)` - Notifica assinatura
- `notifyDocumentUploaded($data)` - Notifica upload
- `getCaregiver($id)` - Obter dados do cuidador
- `updateDocumentStatus($id, $type, $status)` - Atualizar status

### Financeiro

**Cliente:** `App\Integrations\Financeiro\FinanceiroClient`

**Base URL:** `https://financeiro.carinho.com.vc/api`

**Webhooks Enviados:**
- `POST /webhooks/documents/invoice-uploaded`
- `POST /webhooks/documents/receipt-uploaded`

**Métodos:**
- `notifyInvoiceUploaded($data)` - Nota fiscal
- `notifyReceiptUploaded($data)` - Comprovante
- `storeInvoice($content, $metadata)` - Armazenar nota

### Atendimento

**Cliente:** `App\Integrations\Atendimento\AtendimentoClient`

**Base URL:** `https://atendimento.carinho.com.vc/api`

**Webhooks Enviados:**
- `POST /webhooks/documents/terms-sent`
- `POST /webhooks/documents/privacy-sent`

**Métodos:**
- `notifyTermsSent($data)` - Termos enviados
- `notifyPrivacySent($data)` - Privacidade enviada
- `sendNotification($data)` - Enviar notificação
- `sendDocument($data)` - Enviar documento

### Hub de Integrações

**Cliente:** `App\Integrations\Integracoes\IntegracoesClient`

**Base URL:** `https://integracoes.carinho.com.vc/api`

**Eventos Publicados:**
- `documents.contract.created`
- `documents.contract.signed`
- `documents.consent.granted`
- `documents.consent.revoked`
- `documents.lgpd.request`

**Métodos:**
- `publishEvent($event, $data)` - Publicar evento
- `triggerAutomation($automation, $data)` - Disparar automação

## Autenticação

### Token Interno

Todas as integrações internas usam token Bearer para autenticação:

```http
Authorization: Bearer {INTERNAL_API_TOKEN}
X-Source: carinho-documentos-lgpd
```

### Middleware

```php
// app/Http/Middleware/VerifyInternalToken.php
$token = $request->bearerToken() ?? $request->header('X-Internal-Token');
```

## Tratamento de Erros

Todas as integrações retornam array padronizado:

```php
[
    'status' => 200,        // HTTP status code
    'ok' => true,           // Sucesso ou falha
    'body' => [...],        // Resposta JSON
    'error' => null,        // Mensagem de erro (se houver)
]
```

## Logs

Todas as requisições são logadas:

```php
Log::info('Request successful', ['path' => $path, 'status' => $status]);
Log::warning('Request failed', ['path' => $path, 'error' => $error]);
Log::error('Request error', ['path' => $path, 'exception' => $e->getMessage()]);
```

## Timeouts

| Integração | Timeout | Connect Timeout |
|------------|---------|-----------------|
| AWS S3     | 60s     | 10s             |
| Z-API      | 10s     | 3s              |
| CRM        | 8s      | -               |
| Cuidadores | 8s      | -               |
| Financeiro | 8s      | -               |
| Atendimento| 8s      | -               |
| Integrações| 8s      | -               |
