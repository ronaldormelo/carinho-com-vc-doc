# Integracao Z-API (WhatsApp)

## Visao Geral

O sistema Carinho Integracoes utiliza a Z-API como provedor de WhatsApp Business API para envio e recebimento de mensagens.

**Documentacao oficial:** https://developer.z-api.io/

## Configuracao

### Variaveis de Ambiente

```env
# Habilita/desabilita integracao
ZAPI_ENABLED=true

# URL base da API
ZAPI_BASE_URL=https://api.z-api.io

# Credenciais da instancia
ZAPI_INSTANCE_ID=sua-instancia-id
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token

# Secret para validacao de webhooks
ZAPI_WEBHOOK_SECRET=seu-webhook-secret

# Timeouts
ZAPI_CONNECT_TIMEOUT=3
ZAPI_TIMEOUT=10
```

### Configuracao no Painel Z-API

1. Acesse o painel da Z-API
2. Configure o webhook de recepcao:
   - **URL:** `https://integracoes.carinho.com.vc/webhooks/whatsapp`
   - **Metodo:** POST
   - **Eventos:** Todos os eventos de mensagem
3. Configure o secret para validacao HMAC

## Funcionalidades Implementadas

### Envio de Mensagens

#### Mensagem de Texto

```php
$zapi = app(ZApiClient::class);
$zapi->sendTextMessage('5511999999999', 'Ola, tudo bem?');
```

#### Mensagem de Boas-Vindas

```php
$zapi->sendWelcomeMessage('5511999999999', 'João');
// Usa template de config/branding.php
```

#### Resposta Automatica para Lead

```php
$zapi->sendLeadAutoResponse('5511999999999', 'Maria');
// Usa template de config/branding.php
```

#### Solicitacao de Feedback

```php
$zapi->sendFeedbackRequest(
    '5511999999999',
    'Cliente Nome',
    'Cuidador Nome'
);
// Envia mensagem com botoes de avaliacao 1-5
```

#### Mensagem com Imagem

```php
$zapi->sendMediaMessage(
    '5511999999999',
    'https://example.com/imagem.jpg',
    'Legenda opcional'
);
```

#### Mensagem com Documento

```php
$zapi->sendDocument(
    '5511999999999',
    'https://example.com/contrato.pdf',
    'Contrato.pdf'
);
```

#### Mensagem com Botoes

```php
$zapi->sendButtonList('5511999999999', 'Escolha uma opcao:', [
    ['id' => 'opcao_1', 'label' => 'Opcao 1'],
    ['id' => 'opcao_2', 'label' => 'Opcao 2'],
]);
```

#### Mensagem com Link

```php
$zapi->sendLink(
    '5511999999999',
    'Confira nosso site:',
    'https://carinho.com.vc',
    'Carinho com Voce'
);
```

### Recepcao de Mensagens

O webhook recebe o payload da Z-API e normaliza para formato interno:

```php
$normalized = $zapi->normalizeInbound($payload);

// Resultado:
[
    'provider' => 'z-api',
    'event' => 'message',
    'message_id' => 'ABC123',
    'phone' => '5511999999999',
    'name' => 'Joao da Silva',
    'body' => 'Mensagem recebida',
    'media_url' => null,
    'button_response' => null,
    'is_from_me' => false,
    'received_at' => Carbon::instance,
    'raw' => [...], // payload original
]
```

### Validacao de Assinatura

```php
$isValid = $zapi->isSignatureValid($rawPayload, $signature);
```

A validacao usa HMAC-SHA256 com o secret configurado.

### Status da Instancia

```php
$status = $zapi->getInstanceStatus();
// ['status' => 200, 'ok' => true, 'body' => ['connected' => true, ...]]

$isConnected = $zapi->isConnected();
// true ou false
```

### QR Code para Conexao

```php
$qrCode = $zapi->getQrCode();
// Retorna imagem do QR code para escanear
```

### Verificacao de Numero

```php
$result = $zapi->checkNumber('5511999999999');
// Verifica se numero existe no WhatsApp
```

## Jobs de Envio

### SendWhatsAppMessage

Job para envio assincrono:

```php
use App\Jobs\SendWhatsAppMessage;

// Texto simples
SendWhatsAppMessage::dispatch('text', [
    'phone' => '5511999999999',
    'message' => 'Ola!',
]);

// Boas-vindas
SendWhatsAppMessage::dispatch('welcome', [
    'phone' => '5511999999999',
    'name' => 'Joao',
]);

// Feedback
SendWhatsAppMessage::dispatch('feedback_request', [
    'phone' => '5511999999999',
    'client_name' => 'Maria',
    'caregiver_name' => 'Ana',
]);

// Com delay
SendWhatsAppMessage::dispatch('text', [...])->delay(now()->addHours(2));
```

## Fluxos Automaticos

### Mensagem Recebida

1. Z-API envia webhook para `/webhooks/whatsapp`
2. `WhatsAppWebhookController` valida assinatura
3. Normaliza payload
4. Despacha `ProcessWhatsAppInbound`
5. Job busca/cria lead no CRM
6. Registra interacao
7. Encaminha para Atendimento

### Resposta de Feedback

1. Cliente clica em botao de avaliacao
2. Webhook recebe `buttonResponseMessage`
3. Job identifica como feedback
4. Registra rating no CRM
5. Envia mensagem de agradecimento

## Boas Praticas

1. **Nao envie spam:** Respeite as politicas do WhatsApp
2. **Use templates:** Mantenha mensagens consistentes
3. **Valide numeros:** Use `checkNumber()` antes de enviar
4. **Monitore status:** Verifique conexao da instancia
5. **Trate erros:** Jobs tem retry automatico
6. **Respeite horarios:** Evite mensagens fora do horario comercial

## Troubleshooting

### Mensagens nao chegam

1. Verifique se instancia esta conectada
2. Verifique se numero esta correto (com DDI)
3. Verifique logs em `storage/logs/worker-notifications.log`
4. Verifique se `ZAPI_ENABLED=true`

### Webhooks nao funcionam

1. Verifique URL configurada no painel Z-API
2. Verifique se secret esta correto
3. Verifique logs de requisicao
4. Teste com ngrok em desenvolvimento

### Erros de assinatura

1. Verifique `ZAPI_WEBHOOK_SECRET`
2. Em desenvolvimento, pode desabilitar validacao
3. Verifique se payload nao esta sendo modificado

## Limites e Cotas

Consulte a documentacao da Z-API para limites de:
- Mensagens por segundo
- Mensagens por dia
- Tamanho de midia
- Sessoes simultaneas
