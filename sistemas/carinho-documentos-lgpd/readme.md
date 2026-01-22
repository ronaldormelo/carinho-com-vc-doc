# Carinho Documentos e LGPD

**Subdomínio:** documentos.carinho.com.vc

## Descrição

Repositório central de documentos e conformidade LGPD. Organiza contratos, consentimentos e registros para operação segura e aderente à Lei Geral de Proteção de Dados (Lei nº 13.709/2018).

## Módulos Implementados

### 1. Armazenamento em Nuvem
- Upload de documentos para AWS S3 com criptografia AES-256
- Organização por pastas (clientes, cuidadores, contratos)
- URLs pré-assinadas com expiração para downloads seguros
- Versionamento de documentos

### 2. Contratos
- Contratos com clientes (contrato_cliente)
- Contratos com cuidadores (contrato_cuidador)
- Sistema de templates com variáveis dinâmicas
- Assinatura digital com OTP via WhatsApp ou clique

### 3. Termos e Políticas
- Termos de uso publicados
- Política de privacidade
- Templates versionados e auditáveis

### 4. Registro de Consentimento LGPD
- Registro de consentimento com data, hora e fonte
- Tipos: tratamento de dados, marketing, compartilhamento, perfilamento, cookies
- Revogação de consentimento
- Histórico completo por titular

### 5. Assinatura Digital
- Métodos: OTP (WhatsApp), Clique, Certificado digital
- Registro de IP, timestamp e método
- Hash de verificação HMAC-SHA256
- Auditoria completa

### 6. Auditoria e Logs
- Registro de todas as ações (visualização, download, assinatura, exclusão)
- Logs com IP, ator e timestamp
- Relatórios de acesso

### 7. Políticas de Retenção
- Políticas por tipo de documento
- Arquivamento automático após período
- Exclusão segura com prazo de graça

### 8. Solicitações LGPD
- Exportação de dados do titular
- Exclusão de dados
- Atualização de dados
- Prazo de 15 dias conforme LGPD

## Stack Tecnológica

- **Linguagem:** PHP 8.2
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache e filas:** Redis 7
- **Storage:** AWS S3

## Integrações

### Externas
- **AWS S3:** Armazenamento seguro de documentos
- **Z-API:** Envio de mensagens WhatsApp (OTP, notificações)

### Internas (outros sistemas Carinho)
- **CRM:** Notificação de contratos e consentimentos
- **Cuidadores:** Documentos e termos de cuidadores
- **Financeiro:** Notas fiscais e comprovantes
- **Atendimento:** Envio de termos e política
- **Integrações Hub:** Eventos e automações

## Estrutura do Projeto

```
carinho-documentos-lgpd/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # Controllers da API
│   │   └── Middleware/          # Middleware de autenticação
│   ├── Integrations/
│   │   ├── Storage/             # Cliente AWS S3
│   │   ├── WhatsApp/            # Cliente Z-API
│   │   ├── Crm/                 # Cliente CRM
│   │   ├── Cuidadores/          # Cliente Cuidadores
│   │   ├── Financeiro/          # Cliente Financeiro
│   │   ├── Atendimento/         # Cliente Atendimento
│   │   └── Integracoes/         # Cliente Hub
│   ├── Jobs/                    # Jobs assíncronos
│   ├── Models/                  # Models Eloquent
│   └── Services/                # Camada de serviços
├── bootstrap/
├── config/
│   ├── branding.php             # Identidade visual
│   ├── documentos.php           # Configurações de documentos
│   ├── filesystems.php          # Configuração S3
│   └── integrations.php         # Configurações de integração
├── database/
│   ├── migrations/              # Migrações Laravel
│   ├── seeders/                 # Seeders de dados
│   └── schema.sql               # Schema SQL
├── docs/
│   ├── arquitetura.md
│   └── atividades.md
├── public/
│   ├── css/brand.css            # CSS da marca
│   └── index.php
├── resources/
│   └── views/
│       ├── contracts/           # Views de assinatura
│       ├── emails/              # Templates de email
│       └── public/              # Páginas públicas
└── routes/
    ├── api.php                  # Rotas da API
    ├── console.php              # Jobs agendados
    └── web.php                  # Rotas web
```

## Configuração

### Variáveis de Ambiente

```env
# App
APP_URL=https://documentos.carinho.com.vc

# Database
DB_CONNECTION=mysql
DB_DATABASE=carinho_documentos_lgpd

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=sa-east-1
AWS_BUCKET=carinho-documentos

# Z-API (WhatsApp)
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=
ZAPI_TOKEN=
ZAPI_CLIENT_TOKEN=

# Integrações internas
INTERNAL_API_TOKEN=
CRM_BASE_URL=https://crm.carinho.com.vc/api
CUIDADORES_BASE_URL=https://cuidadores.carinho.com.vc/api
FINANCEIRO_BASE_URL=https://financeiro.carinho.com.vc/api
ATENDIMENTO_BASE_URL=https://atendimento.carinho.com.vc/api
INTEGRACOES_BASE_URL=https://integracoes.carinho.com.vc/api
```

## Endpoints Principais

### Documentos
- `POST /api/documents/upload` - Upload de documento
- `GET /api/documents/{id}/signed-url` - URL assinada
- `GET /api/documents/owner/{type}/{id}` - Documentos por proprietário

### Contratos
- `POST /api/contracts` - Criar contrato
- `POST /api/contracts/{id}/sign` - Assinar contrato
- `GET /api/contracts/{id}/status` - Status do contrato

### Consentimentos
- `POST /api/consents` - Registrar consentimento
- `DELETE /api/consents/{id}` - Revogar consentimento
- `GET /api/consents/check/{type}/{id}/{consent}` - Verificar consentimento

### LGPD
- `POST /api/data-requests/export` - Solicitar exportação
- `POST /api/data-requests/delete` - Solicitar exclusão
- `POST /api/data-requests/{id}/process` - Processar solicitação

### Assinaturas
- `POST /api/signatures/send-otp` - Enviar código OTP
- `GET /api/signatures/verify/{id}` - Verificar assinatura

## Segurança

- Criptografia de documentos em repouso (AES-256) e em trânsito (TLS)
- URLs assinadas com expiração para downloads
- Controle de acesso por token entre sistemas
- Auditoria completa de acessos
- Conformidade com LGPD

## Execução

### Docker

```bash
docker-compose up -d
```

### Comandos Úteis

```bash
# Migrações
php artisan migrate

# Seeders
php artisan db:seed

# Queue worker
php artisan queue:work

# Scheduler
php artisan schedule:work
```

## Jobs Agendados

- **03:00** - Processar políticas de retenção
- **04:00** - Limpar documentos expirados
- **Horário** - Sincronizar metadados com storage
