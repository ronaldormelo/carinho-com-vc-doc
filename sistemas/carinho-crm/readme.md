# Carinho CRM

**Subdomínio:** crm.carinho.com.vc

## Descrição

Base única de leads e clientes do ecossistema Carinho com Você. Mantém o pipeline comercial, registra interações e consolida o histórico de atendimentos e serviços.

## Stack Tecnológico

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache e Filas:** Redis
- **Autenticação:** Laravel Sanctum
- **Auditoria:** Spatie Activity Log
- **Permissões:** Spatie Laravel Permission

## Módulos Essenciais

### 1. Cadastro Único
- Dados de leads e clientes com condições especiais e preferências
- Criptografia de campos sensíveis (telefone, e-mail, endereço) - LGPD
- Registro de consentimentos e bases legais

### 2. Pipeline Comercial
- Fluxo: Lead → Triagem → Proposta → Ativo/Perdido
- Estágios configuráveis do pipeline (Kanban)
- Deals com valor estimado e propostas
- Registro de motivos de perda

### 3. Registro de Serviços
- Tipos: Horista, Diário, Mensal
- Propostas com preço e validade
- Conversão automática para contrato

### 4. Histórico de Interações
- Canais: WhatsApp, E-mail, Telefone
- Timeline completa por lead/cliente
- Registro automático via webhooks

### 5. Contratos e Aceite Digital
- Termo de prestação de serviço
- Link único para aceite digital
- Rastreabilidade (IP, User Agent, timestamp)
- Integração com sistema de documentos

### 6. Tarefas e Follow-up
- Criação manual e automática de tarefas
- Atribuição por responsável
- Alertas de vencimento e atraso
- Escalação automática

### 7. Integrações
- **Site:** Captura de leads com UTM
- **Marketing:** Tracking de campanhas
- **Atendimento:** Sincronização de status
- **Operação:** Repasse de dados para alocação
- **Financeiro:** Dados de contrato e cobrança
- **Documentos:** Aceite digital e contratos
- **Cuidadores:** Consulta de disponibilidade
- **WhatsApp (Z-API):** Mensagens automáticas

### 8. Relatórios e KPIs
- Dashboard com métricas em tempo real
- Conversão, ticket médio e origem do lead
- Tempo médio de resposta
- Performance por vendedor
- Exportação em CSV/XLSX

## Estrutura do Projeto

```
carinho-crm/
├── app/
│   ├── Console/           # Comandos e Schedule
│   ├── Events/            # Eventos do sistema
│   ├── Exceptions/        # Handlers de exceção
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/       # Controllers da API REST
│   │   │   └── Webhook/   # Controllers de webhooks
│   │   ├── Middleware/    # Middlewares de segurança
│   │   ├── Requests/      # Form Requests (validação)
│   │   └── Resources/     # API Resources (transformação)
│   ├── Jobs/              # Jobs assíncronos
│   ├── Listeners/         # Listeners de eventos
│   ├── Models/
│   │   └── Domain/        # Models de domínio (enums)
│   ├── Providers/         # Service Providers
│   ├── Repositories/      # Repository Pattern
│   ├── Services/
│   │   ├── Integrations/  # Serviços de integração externa
│   │   └── *.php          # Services de negócio
│   └── Traits/            # Traits reutilizáveis
├── config/                # Configurações
├── database/
│   ├── migrations/        # Migrations
│   └── seeders/           # Seeders
├── resources/
│   ├── css/               # Estilos (identidade visual)
│   └── views/             # Views Blade
├── routes/
│   ├── api.php            # Rotas da API
│   ├── web.php            # Rotas web
│   └── webhooks.php       # Rotas de webhooks
└── tests/                 # Testes automatizados
```

## API Endpoints

### Domínios (Valores de Referência)
- `GET /api/v1/domains` - Todos os valores de domínio

### Leads
- `GET /api/v1/leads` - Lista leads com filtros
- `POST /api/v1/leads` - Cria novo lead
- `GET /api/v1/leads/{id}` - Detalhes do lead
- `PUT /api/v1/leads/{id}` - Atualiza lead
- `POST /api/v1/leads/{id}/advance` - Avança status
- `POST /api/v1/leads/{id}/lost` - Marca como perdido

### Clientes
- `GET /api/v1/clients` - Lista clientes
- `POST /api/v1/clients` - Cria cliente
- `GET /api/v1/clients/{id}` - Detalhes do cliente
- `POST /api/v1/clients/{id}/care-needs` - Adiciona necessidade
- `POST /api/v1/clients/{id}/consents` - Registra consentimento

### Pipeline e Deals
- `GET /api/v1/pipeline/stages` - Estágios do pipeline
- `GET /api/v1/pipeline/board` - Visão Kanban
- `GET /api/v1/pipeline/metrics` - Métricas
- `GET /api/v1/deals` - Lista deals
- `POST /api/v1/deals/{id}/won` - Marca como ganho

### Contratos
- `GET /api/v1/contracts` - Lista contratos
- `POST /api/v1/contracts/{id}/sign` - Registra assinatura
- `GET /api/v1/contracts/expiring-soon` - Contratos expirando

### Tarefas
- `GET /api/v1/tasks` - Lista tarefas
- `GET /api/v1/tasks/my-tasks` - Minhas tarefas
- `POST /api/v1/tasks/{id}/complete` - Marca como concluída

### Relatórios
- `GET /api/v1/reports/dashboard` - Dashboard
- `GET /api/v1/reports/conversion` - Taxa de conversão
- `GET /api/v1/reports/lead-sources` - Origens dos leads

## Integrações Externas

### Z-API (WhatsApp)
Documentação: https://developer.z-api.io/

Funcionalidades implementadas:
- Envio de mensagens de texto
- Mensagens com botões e listas
- Envio de documentos e imagens
- Verificação de número válido
- Webhooks de mensagens recebidas
- Mensagens automáticas (boas-vindas, follow-up)

### Sistemas Internos Carinho
Todos os sistemas do ecossistema são integrados via API REST com autenticação por API Key.

| Sistema | Função no CRM |
|---------|--------------|
| Site | Recebe leads dos formulários |
| Marketing | Tracking de UTM e campanhas |
| Atendimento | Sincronização de status e interações |
| Operação | Repasse de dados para alocação |
| Financeiro | Dados de contrato e cobrança |
| Documentos | Contratos e consentimentos LGPD |
| Cuidadores | Consulta de disponibilidade |

## Segurança e LGPD

### Medidas Implementadas
- Criptografia de dados sensíveis (AES-256)
- Auditoria completa de alterações
- Controle de acesso por perfil (RBAC)
- Registro de consentimentos
- Headers de segurança (CSP, HSTS, etc.)
- Sanitização de inputs
- Rate limiting em APIs

### Conformidade LGPD
- Registro de base legal por tratamento
- Exportação de dados do titular
- Exclusão/anonimização de dados
- Log de acesso a dados pessoais

## Performance

### Estratégias Implementadas
- Cache Redis para dashboards e listas
- Filas para processamento assíncrono
- Índices otimizados no banco
- Eager loading de relacionamentos
- Paginação em todas as listagens

### Jobs Agendados
- Verificação de contratos expirando (diário)
- Verificação de tarefas atrasadas (4h)
- Sincronização com sistemas externos (horário)
- Geração de relatórios (diário)

## Instalação

```bash
# Clone o repositório
git clone [repo-url]
cd carinho-crm

# Instale dependências
composer install

# Configure ambiente
cp .env.example .env
php artisan key:generate

# Configure banco de dados e Redis no .env

# Execute migrations
php artisan migrate

# Inicie o servidor
php artisan serve
```

## Variáveis de Ambiente

```env
# Banco de dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=carinho_crm
DB_USERNAME=carinho
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1

# Z-API
ZAPI_ENABLED=true
ZAPI_INSTANCE_ID=
ZAPI_TOKEN=
ZAPI_CLIENT_TOKEN=

# Integrações internas
CARINHO_SITE_URL=https://site.carinho.com.vc
CARINHO_SITE_API_KEY=

# ... demais integrações
```

## Testes

```bash
# Executar todos os testes
php artisan test

# Executar com cobertura
php artisan test --coverage
```

## Contribuição

1. Crie uma branch para sua feature
2. Faça commits atômicos com mensagens claras
3. Adicione testes para novas funcionalidades
4. Envie um Pull Request

## Licença

Proprietary - Carinho com Você
