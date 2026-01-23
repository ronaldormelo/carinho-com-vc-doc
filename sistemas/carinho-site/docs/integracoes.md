# Integracoes do Sistema Carinho Site

## Visao Geral

O Carinho Site integra-se com varios sistemas internos e externos para funcionar adequadamente. Este documento descreve cada integracao, sua configuracao e uso.

## Integracoes Externas

### 1. Z-API (WhatsApp)

**Documentacao oficial:** https://developer.z-api.io/

#### Funcionalidades Utilizadas

- Geracao de links de CTA (wa.me)
- Envio de notificacoes de novos leads (via hub de integracoes)
- Mensagem de boas-vindas para leads urgentes

#### Configuracao

```env
ZAPI_ENABLED=true
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=sua-instancia
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token
```

#### Uso

```php
// Via Service
$whatsapp = app(WhatsAppService::class);

// Gerar URL de CTA
$url = $whatsapp->generateCtaUrl('Mensagem', ['utm_source' => 'site']);

// Enviar notificacao de lead
$whatsapp->sendNewLeadNotification($phone, $name, $urgency);

// Verificar status da instancia
$connected = $whatsapp->isConnected();
```

### 2. Google Analytics 4

**Documentacao:** https://developers.google.com/analytics

#### Configuracao

```env
ANALYTICS_ENABLED=true
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
```

#### Implementacao

O GA4 e carregado via Google Tag Manager no layout principal. Eventos sao disparados automaticamente para:

- Page views
- Cliques em CTAs
- Submissao de formularios
- Conversoes

### 3. Google Tag Manager

**Documentacao:** https://developers.google.com/tag-manager

#### Configuracao

```env
GTM_CONTAINER_ID=GTM-XXXXXX
```

#### Uso

Tags sao gerenciadas diretamente no painel do GTM. O container e carregado no `<head>` e `<body>` do layout.

### 4. reCAPTCHA v3

**Documentacao:** https://developers.google.com/recaptcha/docs/v3

#### Configuracao

```env
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=sua-site-key
RECAPTCHA_SECRET_KEY=sua-secret-key
RECAPTCHA_MIN_SCORE=0.5
```

#### Uso

```php
$recaptcha = app(RecaptchaService::class);
$valid = $recaptcha->verify($token, $ip);
```

#### No Frontend

```javascript
// Token e obtido automaticamente ao submeter formulario
grecaptcha.execute('SITE_KEY', {action: 'submit_lead'})
    .then(token => {
        document.getElementById('recaptcha_token').value = token;
    });
```

## Integracoes Internas

### 1. CRM (carinho-crm)

#### Endpoint Base
```
https://crm.carinho.com.vc/api/v1
```

#### Funcionalidades

| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| POST | /leads | Criar novo lead |
| PUT | /leads/{id} | Atualizar lead |
| GET | /leads?phone=X | Buscar por telefone |
| POST | /leads/{id}/source | Registrar origem UTM |

#### Configuracao

```env
CARINHO_CRM_URL=https://crm.carinho.com.vc
CARINHO_CRM_API_KEY=seu-api-key
```

#### Uso

```php
$crm = app(CrmClient::class);

// Criar lead
$response = $crm->createLead([
    'name' => 'Nome',
    'phone' => '5511999999999',
    'email' => 'email@example.com',
    'city' => 'Sao Paulo',
    'urgency' => 'hoje',
    'service_type' => 'diario',
    'origin' => 'site',
    'type' => 'cliente',
]);

// Buscar lead por telefone
$lead = $crm->findLeadByPhone('5511999999999');

// Registrar origem
$crm->registerLeadSource($leadId, [
    'source' => 'google',
    'medium' => 'cpc',
    'campaign' => 'cuidadores-sp',
]);
```

### 2. Hub de Integracoes (carinho-integracoes)

#### Endpoint Base
```
https://integracoes.carinho.com.vc/api/v1
```

#### Configuracao

```env
CARINHO_INTEGRACOES_URL=https://integracoes.carinho.com.vc
CARINHO_INTEGRACOES_API_KEY=seu-api-key
```

O hub e utilizado para:
- Envio de mensagens WhatsApp (via Z-API centralizado)
- Eventos entre sistemas
- Sincronizacao de dados

### 3. Marketing (carinho-marketing)

#### Endpoint Base
```
https://marketing.carinho.com.vc/api/v1
```

#### Configuracao

```env
CARINHO_MARKETING_URL=https://marketing.carinho.com.vc
CARINHO_MARKETING_API_KEY=seu-api-key
```

Utilizado para:
- Tracking de campanhas
- Registro de conversoes
- Analise de ROI

## Autenticacao

### Token Interno

Todas as chamadas entre sistemas usam token de autenticacao no header:

```
Authorization: Bearer {token}
```

Ou via header customizado:

```
X-Internal-Token: {token}
```

### Configuracao

```env
INTERNAL_API_TOKEN=seu-token-seguro
```

## Webhooks Recebidos

### Do CRM

```
POST /api/webhooks/crm
```

Eventos recebidos:
- `lead_updated`: Lead foi atualizado
- `client_created`: Cliente criado a partir do lead

### Limpeza de Cache

```
POST /api/webhooks/cache/pages/clear
POST /api/webhooks/cache/legal/clear
```

Usado para invalidar cache quando conteudo e atualizado em outros sistemas.

## Tratamento de Erros

### Retry Policy

Jobs de integracao usam retry automatico:

```php
public int $tries = 5;
public array $backoff = [60, 120, 300, 600, 1200];
```

### Logging

Todas as integracoes registram logs:

```php
Log::info('CRM request successful', ['endpoint' => $endpoint]);
Log::warning('CRM request failed', ['status' => $status]);
Log::error('CRM request error', ['error' => $message]);
```

### Dead Letter

Jobs que falham apos todas as tentativas sao registrados para analise posterior.

## Monitoramento

### Health Checks

```bash
# Basico
curl https://site.carinho.com.vc/health

# Detalhado (inclui status das integracoes)
curl https://site.carinho.com.vc/health/detailed
```

### Metricas

Metricas disponiveis:
- Tempo de resposta das APIs
- Taxa de sucesso/falha
- Leads criados por origem
- Conversoes por campanha

## Seguranca

1. **Tokens rotativos:** Alterar periodicamente
2. **Validacao de assinatura:** Em webhooks quando disponivel
3. **Rate limiting:** 60 req/min na API
4. **HTTPS obrigatorio:** Todas as comunicacoes
5. **Logs de auditoria:** Todas as chamadas registradas
