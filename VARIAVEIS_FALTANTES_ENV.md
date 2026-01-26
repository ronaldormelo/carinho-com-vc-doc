# Variáveis de Ambiente Faltantes ou Sem Valores

Este documento lista todas as variáveis de ambiente que estão **vazias** (sem valores) ou **faltando** nos arquivos `.env.example` de cada sistema.

---

## 🔴 CARINHO CUIDADORES

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação (gerar com `php artisan key:generate`)

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Token Interno
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Integrações Internas - Tokens
- `CRM_TOKEN=` - Token de autenticação do CRM
- `CUIDADORES_TOKEN=` - Token de autenticação do sistema Cuidadores
- `ATENDIMENTO_TOKEN=` - Token de autenticação do Atendimento
- `FINANCEIRO_TOKEN=` - Token de autenticação do Financeiro
- `INTEGRACOES_TOKEN=` - Token de autenticação do Hub de Integrações

#### Mail (Opcional)
- `MAIL_USERNAME=null` - Usuário do servidor SMTP
- `MAIL_PASSWORD=null` - Senha do servidor SMTP
- `MAIL_FROM_ADDRESS=""` - Endereço de email remetente
- `MAIL_FROM_NAME=""` - Nome do remetente

---

## 🔴 CARINHO DOCUMENTOS LGPD

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### AWS S3 Storage
- `AWS_ACCESS_KEY_ID=` - Chave de acesso AWS
- `AWS_SECRET_ACCESS_KEY=` - Chave secreta AWS
- `AWS_URL=` - URL base do bucket S3 (opcional)
- `AWS_ENDPOINT=` - Endpoint customizado (opcional, para S3-compatible)

#### Token Interno
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Integrações Internas - Tokens
- `CRM_TOKEN=` - Token de autenticação do CRM
- `CUIDADORES_TOKEN=` - Token de autenticação do sistema Cuidadores
- `FINANCEIRO_TOKEN=` - Token de autenticação do Financeiro
- `ATENDIMENTO_TOKEN=` - Token de autenticação do Atendimento
- `INTEGRACOES_TOKEN=` - Token de autenticação do Hub de Integrações

#### Mail (Opcional)
- `MAIL_USERNAME=null` - Usuário do servidor SMTP
- `MAIL_PASSWORD=null` - Senha do servidor SMTP
- `MAIL_FROM_ADDRESS=""` - Endereço de email remetente
- `MAIL_FROM_NAME=""` - Nome do remetente

---

## 🔴 CARINHO OPERAÇÃO

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Token Interno
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Integrações Internas - Tokens
- `CRM_TOKEN=` - Token de autenticação do CRM
- `CUIDADORES_TOKEN=` - Token de autenticação do sistema Cuidadores
- `ATENDIMENTO_TOKEN=` - Token de autenticação do Atendimento
- `FINANCEIRO_TOKEN=` - Token de autenticação do Financeiro
- `INTEGRACOES_TOKEN=` - Token de autenticação do Hub de Integrações

#### Mail (Opcional)
- `MAIL_USERNAME=null` - Usuário do servidor SMTP
- `MAIL_PASSWORD=null` - Senha do servidor SMTP
- `MAIL_FROM_ADDRESS=""` - Endereço de email remetente
- `MAIL_FROM_NAME=""` - Nome do remetente

---

## 🔴 CARINHO ATENDIMENTO

### Variáveis Vazias ou com Placeholders (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação
- `APP_NAME=` - Nome da aplicação (faltando)

#### Banco de Dados
- `DB_CONNECTION=` - Tipo de conexão (faltando)
- `DB_HOST=` - Host do banco (faltando)
- `DB_PORT=` - Porta do banco (faltando)
- `DB_DATABASE=` - Nome do banco (faltando)
- `DB_USERNAME=` - Usuário do banco (faltando)
- `DB_PASSWORD=` - Senha do banco (faltando)

#### Redis
- `REDIS_HOST=` - Host do Redis (faltando)
- `REDIS_PASSWORD=` - Senha do Redis (faltando)
- `REDIS_PORT=` - Porta do Redis (faltando)
- `REDIS_DB=` - Database do Redis (faltando)

#### Cache e Queue
- `CACHE_DRIVER=` - Driver de cache (faltando)
- `QUEUE_CONNECTION=` - Conexão de fila (faltando)
- `SESSION_DRIVER=` - Driver de sessão (faltando)

#### Token Interno
- `INTERNAL_API_TOKEN=changeme-internal-token` - ⚠️ **Placeholder, precisa ser alterado**

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=changeme-instance` - ⚠️ **Placeholder, precisa ser alterado**
- `ZAPI_TOKEN=changeme-token` - ⚠️ **Placeholder, precisa ser alterado**
- `ZAPI_CLIENT_TOKEN=changeme-client-token` - ⚠️ **Placeholder, precisa ser alterado**
- `ZAPI_WEBHOOK_SECRET=changeme-webhook-secret` - ⚠️ **Placeholder, precisa ser alterado**

#### Integrações Internas - Tokens
- `CRM_TOKEN=changeme-crm-token` - ⚠️ **Placeholder, precisa ser alterado**
- `OPERACAO_TOKEN=changeme-operacao-token` - ⚠️ **Placeholder, precisa ser alterado**
- `INTEGRACOES_TOKEN=changeme-integracoes-token` - ⚠️ **Placeholder, precisa ser alterado**

#### Timeouts (Faltando)
- `INTERNAL_API_TIMEOUT=` - Timeout para API interna
- `ZAPI_TIMEOUT=` - Timeout para Z-API
- `ZAPI_CONNECT_TIMEOUT=` - Timeout de conexão Z-API
- `CRM_TIMEOUT=` - Timeout para CRM
- `OPERACAO_TIMEOUT=` - Timeout para Operação
- `ATENDIMENTO_TIMEOUT=` - Timeout para Atendimento
- `INTEGRACOES_TIMEOUT=` - Timeout para Integrações

#### Logging (Faltando)
- `LOG_CHANNEL=` - Canal de log
- `LOG_LEVEL=` - Nível de log

#### Mail (Faltando)
- `MAIL_MAILER=` - Driver de email
- `MAIL_HOST=` - Host SMTP
- `MAIL_PORT=` - Porta SMTP
- `MAIL_USERNAME=` - Usuário SMTP
- `MAIL_PASSWORD=` - Senha SMTP
- `MAIL_ENCRYPTION=` - Criptografia SMTP
- `MAIL_FROM_ADDRESS=` - Endereço remetente
- `MAIL_FROM_NAME=` - Nome remetente

---

## 🔴 CARINHO SITE

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Google Analytics / Tag Manager
- `GA4_MEASUREMENT_ID=` - ID de medição Google Analytics 4
- `GTM_CONTAINER_ID=` - ID do container Google Tag Manager

#### Google Meu Negócio
- `GMB_PLACE_ID=` - ID do lugar no Google Meu Negócio

#### reCAPTCHA v3
- `RECAPTCHA_SITE_KEY=` - Chave pública do reCAPTCHA
- `RECAPTCHA_SECRET_KEY=` - Chave secreta do reCAPTCHA

#### Integrações Internas - API Keys
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas
- `CARINHO_CRM_API_KEY=` - Chave de API do CRM
- `CARINHO_ATENDIMENTO_API_KEY=` - Chave de API do Atendimento
- `CARINHO_MARKETING_API_KEY=` - Chave de API do Marketing
- `CARINHO_INTEGRACOES_API_KEY=` - Chave de API do Hub de Integrações

---

## 🔴 CARINHO MARKETING

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Token Interno
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Meta (Facebook/Instagram)
- `META_APP_ID=` - ID da aplicação Meta
- `META_APP_SECRET=` - Secret da aplicação Meta
- `META_ACCESS_TOKEN=` - Token de acesso Meta
- `META_AD_ACCOUNT_ID=` - ID da conta de anúncios
- `META_PAGE_ID=` - ID da página Facebook
- `META_INSTAGRAM_ACCOUNT_ID=` - ID da conta Instagram
- `META_PIXEL_ID=` - ID do Pixel do Facebook
- `META_WEBHOOK_VERIFY_TOKEN=` - Token de verificação de webhook

#### Instagram
- `INSTAGRAM_BUSINESS_ACCOUNT_ID=` - ID da conta comercial Instagram

#### Google Ads
- `GOOGLE_ADS_DEVELOPER_TOKEN=` - Token de desenvolvedor Google Ads
- `GOOGLE_ADS_CLIENT_ID=` - ID do cliente Google Ads
- `GOOGLE_ADS_CLIENT_SECRET=` - Secret do cliente Google Ads
- `GOOGLE_ADS_REFRESH_TOKEN=` - Token de refresh Google Ads
- `GOOGLE_ADS_CUSTOMER_ID=` - ID do cliente Google Ads
- `GOOGLE_ADS_LOGIN_CUSTOMER_ID=` - ID de login do cliente

#### Google Analytics
- `GA_MEASUREMENT_ID=` - ID de medição Google Analytics
- `GA_API_SECRET=` - Secret da API Google Analytics
- `GA_PROPERTY_ID=` - ID da propriedade Google Analytics
- `GA_SERVICE_ACCOUNT_JSON=` - JSON da conta de serviço Google Analytics

#### Google Tag Manager
- `GTM_CONTAINER_ID=` - ID do container Google Tag Manager

#### Integrações Internas - Tokens
- `CRM_TOKEN=` - Token de autenticação do CRM
- `INTEGRACOES_TOKEN=` - Token de autenticação do Hub de Integrações
- `SITE_TOKEN=` - Token de autenticação do Site
- `ATENDIMENTO_TOKEN=` - Token de autenticação do Atendimento

---

## 🔴 CARINHO INTEGRAÇÕES

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Mail (SMTP)
- `MAIL_USERNAME=` - Usuário do servidor SMTP
- `MAIL_PASSWORD=` - Senha do servidor SMTP

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Sistemas Internos Carinho - API Keys
- `CARINHO_SITE_API_KEY=` - Chave de API do Site
- `CARINHO_CRM_API_KEY=` - Chave de API do CRM
- `CARINHO_ATENDIMENTO_API_KEY=` - Chave de API do Atendimento
- `CARINHO_OPERACAO_API_KEY=` - Chave de API da Operação
- `CARINHO_FINANCEIRO_API_KEY=` - Chave de API do Financeiro
- `CARINHO_CUIDADORES_API_KEY=` - Chave de API do Cuidadores
- `CARINHO_DOCUMENTOS_API_KEY=` - Chave de API do Documentos
- `CARINHO_MARKETING_API_KEY=` - Chave de API do Marketing

---

## 🔴 CARINHO FINANCEIRO

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Token Interno
- `INTERNAL_API_TOKEN=` - Token para comunicação entre sistemas

#### Stripe - Gateway de Pagamento
- `STRIPE_SECRET_KEY=sk_test_...` - ⚠️ **Placeholder, precisa ser alterado**
- `STRIPE_PUBLISHABLE_KEY=pk_test_...` - ⚠️ **Placeholder, precisa ser alterado**
- `STRIPE_WEBHOOK_SECRET=whsec_...` - ⚠️ **Placeholder, precisa ser alterado**

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_SECRET=` - Secret para validação de webhooks

#### Integrações Internas - Tokens
- `CRM_TOKEN=` - Token de autenticação do CRM
- `OPERACAO_TOKEN=` - Token de autenticação da Operação
- `DOCUMENTOS_TOKEN=` - Token de autenticação do Documentos
- `CUIDADORES_TOKEN=` - Token de autenticação do Cuidadores
- `INTEGRACOES_TOKEN=` - Token de autenticação do Hub de Integrações

#### Dados da Empresa
- `COMPANY_CNPJ=` - CNPJ da empresa
- `COMPANY_ADDRESS=` - Endereço da empresa
- `COMPANY_PHONE=` - Telefone da empresa
- `COMPANY_IM=` - Inscrição Municipal (opcional)

#### Fiscal - NFS-e (Futuro)
- `NFSE_BASE_URL=` - URL base do provedor NFS-e
- `NFSE_API_KEY=` - Chave de API do provedor NFS-e

#### Mail (SMTP)
- `MAIL_HOST=` - Host do servidor SMTP
- `MAIL_USERNAME=` - Usuário do servidor SMTP
- `MAIL_PASSWORD=` - Senha do servidor SMTP

---

## 🔴 CARINHO CRM

### Variáveis Vazias (Precisam ser preenchidas):

#### Aplicação
- `APP_KEY=` - Chave de criptografia da aplicação

#### Banco de Dados
- `DB_PASSWORD=` - Senha do banco de dados

#### Z-API (WhatsApp)
- `ZAPI_INSTANCE_ID=` - ID da instância Z-API
- `ZAPI_TOKEN=` - Token de autenticação Z-API
- `ZAPI_CLIENT_TOKEN=` - Token do cliente Z-API
- `ZAPI_WEBHOOK_URL=` - URL do webhook (já tem valor padrão, mas pode precisar ajuste)

#### Sistemas Internos Carinho - API Keys
- `CARINHO_SITE_API_KEY=` - Chave de API do Site
- `CARINHO_MARKETING_API_KEY=` - Chave de API do Marketing
- `CARINHO_ATENDIMENTO_API_KEY=` - Chave de API do Atendimento
- `CARINHO_OPERACAO_API_KEY=` - Chave de API da Operação
- `CARINHO_FINANCEIRO_API_KEY=` - Chave de API do Financeiro
- `CARINHO_DOCUMENTOS_API_KEY=` - Chave de API do Documentos
- `CARINHO_CUIDADORES_API_KEY=` - Chave de API do Cuidadores

#### Webhooks
- `WEBHOOK_SECRET=` - Secret compartilhado para validação de webhooks

#### Mail (SMTP)
- `MAIL_USERNAME=` - Usuário do servidor SMTP
- `MAIL_PASSWORD=` - Senha do servidor SMTP

#### AWS S3 (Opcional)
- `AWS_ACCESS_KEY_ID=` - Chave de acesso AWS
- `AWS_SECRET_ACCESS_KEY=` - Chave secreta AWS

---

## 📋 RESUMO GERAL

### Variáveis Críticas (Todos os Sistemas)
1. **APP_KEY** - ⚠️ **CRÍTICO**: Necessário em todos os sistemas
2. **DB_PASSWORD** - ⚠️ **CRÍTICO**: Senha do banco de dados
3. **INTERNAL_API_TOKEN** - ⚠️ **CRÍTICO**: Token para comunicação entre sistemas

### Variáveis de Integração Externa
1. **Z-API** (WhatsApp) - Usado em: Cuidadores, Documentos, Operação, Atendimento, Site, Marketing, Integrações, Financeiro, CRM
   - `ZAPI_INSTANCE_ID`
   - `ZAPI_TOKEN`
   - `ZAPI_CLIENT_TOKEN`
   - `ZAPI_WEBHOOK_SECRET`

2. **AWS S3** - Usado em: Documentos, CRM
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`

3. **Stripe** - Usado em: Financeiro
   - `STRIPE_SECRET_KEY`
   - `STRIPE_PUBLISHABLE_KEY`
   - `STRIPE_WEBHOOK_SECRET`

4. **Google Services** - Usado em: Site, Marketing
   - Google Analytics: `GA4_MEASUREMENT_ID`, `GA_MEASUREMENT_ID`, `GA_API_SECRET`, `GA_PROPERTY_ID`
   - Google Tag Manager: `GTM_CONTAINER_ID`
   - Google Ads: `GOOGLE_ADS_*`
   - Google Meu Negócio: `GMB_PLACE_ID`

5. **Meta (Facebook/Instagram)** - Usado em: Marketing
   - `META_APP_ID`, `META_APP_SECRET`, `META_ACCESS_TOKEN`, etc.

6. **reCAPTCHA** - Usado em: Site
   - `RECAPTCHA_SITE_KEY`
   - `RECAPTCHA_SECRET_KEY`

### Variáveis de Integração Interna (Tokens)
Todos os sistemas precisam de tokens para comunicação entre si:
- `CRM_TOKEN`
- `CUIDADORES_TOKEN`
- `OPERACAO_TOKEN`
- `DOCUMENTOS_TOKEN`
- `ATENDIMENTO_TOKEN`
- `FINANCEIRO_TOKEN`
- `INTEGRACOES_TOKEN`
- `MARKETING_TOKEN` (quando aplicável)
- `SITE_TOKEN` (quando aplicável)

### Variáveis de Email (Opcional mas Recomendado)
- `MAIL_HOST`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

---

## ⚠️ OBSERVAÇÕES IMPORTANTES

1. **APP_KEY**: Deve ser gerado com `php artisan key:generate` em cada sistema
2. **Tokens Internos**: Devem ser strings aleatórias seguras (mínimo 32 caracteres)
3. **Placeholders**: Variáveis com valores como `changeme-*` devem ser substituídas por valores reais
4. **Secrets**: Todas as chaves secretas devem ser mantidas em segurança e nunca commitadas
5. **Ambiente de Produção**: Em produção, `APP_DEBUG` deve ser `false` e `APP_ENV` deve ser `production`
