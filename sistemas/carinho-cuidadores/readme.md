# Carinho Cuidadores

**Subdominio:** cuidadores.carinho.com.vc

## Descricao

Sistema de recrutamento e gestao de cuidadores. Padroniza cadastro, triagem e classificacao para garantir oferta confiavel e escalavel de profissionais qualificados para cuidado domiciliar.

## Stack Tecnica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0+
- **Cache e filas:** Redis
- **Storage:** Integracao com sistema Documentos/LGPD

## Modulos Implementados

### 1. Cadastro e Triagem Digital
- Formulario de cadastro com dados pessoais, experiencia e disponibilidade
- Validacao automatica de campos obrigatorios
- Classificacao inicial por tipo de cuidado e regiao

### 2. Gestao de Documentos
- Upload de documentos obrigatorios (RG, CPF, comprovante de endereco)
- Upload de documentos opcionais (certificados, cursos)
- Validacao automatica e manual de documentos
- Integracao com sistema Documentos/LGPD para armazenamento seguro

### 3. Classificacao e Segmentacao
- Tipos de cuidado: Idosos, PCD, TEA, Pos-operatorio
- Niveis de habilidade: Basico, Intermediario, Avancado
- Regioes de atuacao (cidade e bairro)
- Disponibilidade por dia da semana e horario

### 4. Contratos Digitais
- Geracao de termo de responsabilidade
- Envio via WhatsApp e email
- Assinatura digital
- Integracao com sistema Documentos/LGPD

### 5. Ativacao/Desativacao
- Fluxo de triagem e validacao
- Ativacao automatica apos requisitos cumpridos
- Desativacao e bloqueio com motivo

### 6. Banco Pesquisavel
- Busca avancada com filtros multiplos
- Busca rapida por telefone/nome
- Busca por disponibilidade em horario especifico
- Cache de consultas frequentes

### 7. Canal de Comunicacao
- Integracao com Z-API para WhatsApp
- Notificacoes automaticas (boas-vindas, ativacao, documentos)
- Processamento de mensagens recebidas
- Templates de mensagem configurados

### 8. Avaliacoes e Ocorrencias
- Registro de avaliacoes pos-servico (1-5 estrelas)
- Calculo de media e tendencias
- Registro de incidentes/intercorrencias
- Alertas para cuidadores com notas baixas

### 9. Treinamentos
- Registro de cursos e treinamentos
- Controle de conclusao
- Sugestao de cursos disponiveis

## Integracoes

### Externas
- **Z-API (WhatsApp):** Envio de mensagens, documentos e notificacoes

### Internas
- **CRM:** Sincronizacao de dados, historico e performance
- **Operacao:** Disponibilidade, alocacao, check-in/check-out
- **Documentos/LGPD:** Armazenamento seguro, assinatura digital
- **Atendimento:** Comunicados e suporte
- **Integracoes Hub:** Publicacao de eventos para automacoes

## Estrutura de Arquivos

```
carinho-cuidadores/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controllers da API
│   │   └── Middleware/        # Verificacao de token
│   ├── Integrations/          # Clientes de integracao
│   │   ├── WhatsApp/          # Z-API
│   │   ├── Crm/
│   │   ├── Operacao/
│   │   ├── Documentos/
│   │   ├── Atendimento/
│   │   └── Integracoes/
│   ├── Jobs/                  # Jobs assincronos
│   ├── Models/                # Eloquent models
│   └── Services/              # Logica de negocio
├── bootstrap/
├── config/
│   ├── branding.php           # Identidade visual
│   ├── cuidadores.php         # Configuracoes do sistema
│   └── integrations.php       # Configuracoes de APIs
├── database/
│   ├── migrations/
│   ├── schema.sql
│   └── seeders/
├── docs/
│   ├── arquitetura.md
│   ├── atividades.md
│   ├── integracoes.md
│   ├── modulos.md
│   └── nao-funcionais.md
├── public/
│   ├── css/brand.css          # Estilos da marca
│   └── index.php
├── resources/
│   └── views/emails/          # Templates de email
├── routes/
│   ├── api.php                # Rotas da API
│   └── web.php                # Rotas web
├── composer.json
├── docker-compose.yml
├── Dockerfile
└── readme.md
```

## Configuracao

### Variaveis de Ambiente

```env
# Aplicacao
APP_NAME="Carinho Cuidadores"
APP_ENV=production
APP_DEBUG=false

# Banco de dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carinho_cuidadores
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Token interno
INTERNAL_API_TOKEN=seu-token-seguro

# Z-API (WhatsApp)
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=sua-instancia
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token
ZAPI_WEBHOOK_SECRET=seu-webhook-secret

# Integracoes internas
CRM_BASE_URL=https://crm.carinho.com.vc/api
CRM_TOKEN=token-crm
OPERACAO_BASE_URL=https://operacao.carinho.com.vc/api
OPERACAO_TOKEN=token-operacao
DOCUMENTOS_BASE_URL=https://documentos.carinho.com.vc/api
DOCUMENTOS_TOKEN=token-documentos
ATENDIMENTO_BASE_URL=https://atendimento.carinho.com.vc/api
ATENDIMENTO_TOKEN=token-atendimento
INTEGRACOES_BASE_URL=https://integracoes.carinho.com.vc/api
INTEGRACOES_TOKEN=token-integracoes

# Email
EMAIL_FROM=cuidadores@carinho.com.vc
EMAIL_REPLY_TO=contato@carinho.com.vc

# Branding
BRAND_NAME="Carinho com Voce"
BRAND_DOMAIN=carinho.com.vc
```

## Endpoints Principais

### Cuidadores
- `GET /api/caregivers` - Lista cuidadores
- `POST /api/caregivers` - Cadastra cuidador
- `GET /api/caregivers/{id}` - Detalhes do cuidador
- `POST /api/caregivers/{id}/activate` - Ativa cuidador
- `POST /api/caregivers/{id}/deactivate` - Desativa cuidador

### Documentos
- `POST /api/caregivers/{id}/documents` - Upload de documento
- `POST /api/caregivers/{id}/documents/{docId}/approve` - Aprova documento
- `GET /api/documents/pending` - Lista documentos pendentes

### Busca
- `POST /api/search` - Busca avancada
- `GET /api/search/available` - Cuidadores disponiveis

### Webhooks
- `POST /api/webhooks/whatsapp/z-api` - Webhook Z-API

## Filas (Redis)

- `notifications` - Envio de notificacoes
- `documents` - Validacao de documentos
- `contracts` - Processamento de contratos
- `integrations` - Sincronizacao com sistemas
- `messages` - Processamento de mensagens WhatsApp

## Execucao

```bash
# Instalar dependencias
composer install

# Executar migrations
php artisan migrate

# Executar seeders
php artisan db:seed

# Iniciar servidor
php artisan serve

# Processar filas
php artisan queue:work --queue=notifications,documents,contracts,integrations,messages
```

## Docker

```bash
# Build e start
docker-compose up -d

# Logs
docker-compose logs -f app
```

## Seguranca

- Todas as rotas da API protegidas por token interno
- Webhooks com validacao de assinatura
- Documentos armazenados com criptografia
- Logs de acesso e auditoria
- Conformidade com LGPD
