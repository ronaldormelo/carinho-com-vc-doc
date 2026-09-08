# Carinho Operação

**Subdominio:** operacao.carinho.com.vc

## Documentação deste módulo

| Precisa de | Arquivo |
|------------|---------|
| Arquitetura | [docs/arquitetura.md](docs/arquitetura.md) |
| Módulos | [docs/modulos.md](docs/modulos.md) |
| Integrações | [docs/integracoes.md](docs/integracoes.md) |
| NFRs (API P95 500 ms, alocação 4 h) | [docs/nao-funcionais.md](docs/nao-funcionais.md) |
| Manual / guia | [docs/manual-operacional.md](docs/manual-operacional.md), [docs/guia-usuario-operacional.md](docs/guia-usuario-operacional.md) |

Política de **reembolso ao cliente**: [Financeiro — políticas](../carinho-financeiro/docs/politicas.md). Defaults de **horas** em `config/operacao.php` (`CANCEL_FREE_HOURS=24`, `CANCEL_REDUCED_HOURS=6`) seguem essa tabela. As taxas `CANCEL_*_FEE_PERCENT` (30%/50%) são parâmetro interno de operação, **não** o reembolso 100/50/0 que a família vê.

## Descrição

Sistema operacional que conecta cliente e cuidador. Gerencia agenda, alocacao, execucao do serviço e comunicacao de status. Este sistema e o coracao da operação diaria, responsável por garantir que cada atendimento aconteca de forma fluida e com qualidade.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MariaDB 10.11 compartilhado (driver `mysql`, schema `carinho_operacao`)
- **Cache e filas:** Redis
- **Mensageria:** Laravel Horizon

## Modulos Implementados

### 1. Agenda Compartilhada e Agendamentos
- Criacao e gerenciamento de agendamentos por cliente e cuidador
- Validação de disponibilidade com intervalos minimos
- Visualizacao de agenda por período
- Cache de agenda para performance

### 2. Match Cliente x Cuidador
- Motor de match por perfil, disponibilidade e região
- Sistema de pontuacao ponderada (skills, disponibilidade, região, avaliação)
- Match automatico para scores acima do mínimo configurado
- Verificação de compatibilidade baseada em histórico

### 3. Check-in/Check-out e Checklists
- Registro de check-in com validação de localizacao
- Registro de check-out com atividades realizadas
- Checklists configurados de início e fim de atendimento
- Logs de atividades durante o serviço

### 4. Registro de Serviço
- Logs de atividades realizadas
- Notas e observacoes do cuidador
- Histórico completo por agendamento

### 5. Notificações
- Notificação de início e fim de serviço para cliente
- Lembretes de agendamento (24h e 2h antes)
- Notificação de alocacao e substituição de cuidador
- Alertas de emergência
- Suporte a WhatsApp (Z-API), Email e Push

### 6. Substituição e Emergências
- Processo automatico de busca de substituto
- Transferência de agendamentos futuros
- Registro de emergências com severidade
- Escalonamento automatico de emergências não resolvidas

### 7. Políticas de Cancelamento
- A tabela que o cliente vê (reembolso 100% / 50% / 0%) está no Financeiro e no Site.
- Janelas de hora neste módulo: 24 h (sem taxa operacional) e 6 h (taxa reduzida vs integral).
- `CANCEL_*_FEE_PERCENT` default 30%/50% **não** substitui o reembolso publicado.

## Estrutura do Projeto

```
carinho-operacao/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controllers da API
│   │   └── Middleware/         # Middleware de autenticacao
│   ├── Integrations/           # Clientes de integracao
│   │   ├── Atendimento/
│   │   ├── Crm/
│   │   ├── Cuidadores/
│   │   ├── Financeiro/
│   │   └── WhatsApp/
│   ├── Jobs/                   # Jobs assincronos
│   ├── Models/                 # Models Eloquent
│   └── Services/               # Logica de negocio
├── bootstrap/
├── config/
│   ├── branding.php           # Identidade visual
│   ├── integrations.php       # Configuracoes de integracao
│   └── operacao.php           # Configuracoes operacionais
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
│   └── css/brand.css
├── resources/
│   └── views/emails/
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
└── docker-compose.yml
```

## Integracoes

### Sistemas Internos

| Sistema | Função | Base URL |
|---------|--------|----------|
| CRM | Dados de cliente e contrato | crm.carinho.com.vc/api |
| Cuidadores | Disponibilidade e perfil | cuidadores.carinho.com.vc/api |
| Atendimento | Detalhes da demanda e urgência | atendimento.carinho.com.vc/api |
| Financeiro | Cobrança e repasse | financeiro.carinho.com.vc/api |

### APIs Externas

| API | Função | Documentacao |
|-----|--------|--------------|
| Z-API | WhatsApp Business | https://developer.z-api.io/ |

## API Endpoints

### Solicitacoes de Serviço
- `GET /api/service-requests` - Lista solicitacoes
- `GET /api/service-requests/open` - Solicitacoes abertas
- `GET /api/service-requests/urgent` - Solicitacoes urgentes
- `POST /api/service-requests` - Cria solicitação
- `POST /api/service-requests/{id}/process` - Processa alocacao
- `POST /api/service-requests/{id}/cancel` - Cancela solicitação

### Agendamentos
- `GET /api/schedules` - Lista agendamentos
- `GET /api/schedules/today` - Agendamentos de hoje
- `POST /api/schedules` - Cria agendamentos
- `POST /api/schedules/check-availability` - Verifica disponibilidade
- `GET /api/schedules/{id}/cancellation-policy` - Política de cancelamento

### Check-in/Check-out
- `POST /api/checkin/schedule/{id}/in` - Realiza check-in
- `POST /api/checkin/schedule/{id}/out` - Realiza check-out
- `POST /api/checkin/schedule/{id}/activities` - Registra atividades
- `GET /api/checkin/delays` - Verifica atrasos

### Alocacoes
- `GET /api/assignments/service-request/{id}/candidates` - Busca candidatos
- `POST /api/assignments/service-request/{id}/assign` - Aloca cuidador
- `POST /api/assignments/{id}/substitute` - Substitui cuidador

### Emergências
- `GET /api/emergencies/pending` - Emergências pendentes
- `GET /api/emergencies/critical` - Emergências criticas
- `POST /api/emergencies` - Registra emergência
- `POST /api/emergencies/{id}/resolve` - Resolve emergência

### Notificações
- `GET /api/notifications/pending` - Notificações pendentes
- `GET /api/notifications/client/{id}/history` - Histórico do cliente
- `POST /api/notifications/{id}/retry` - Reenvia notificação

## Configurações Principais

### Agendamento (`config/operacao.php`)
- Antecedência mínima: 24 horas
- Duracao mínima: 4 horas
- Duracao máxima: 12 horas
- Intervalo entre atendimentos: 60 minutos

### Match
- Peso de habilidades: 35%
- Peso de disponibilidade: 25%
- Peso de região: 20%
- Peso de avaliação: 20%
- Score mínimo para auto-match: 70

### Check-in
- Tolerancia antecipada: 30 minutos
- Tolerancia de atraso: 15 minutos
- Validação de localizacao: ativada
- Distancia máxima: 500 metros

### Cancelamento
Valores de `config/operacao.php` (horas alinhadas ao Financeiro; taxas só para operação interna):
- `CANCEL_FREE_HOURS` default **24**
- `CANCEL_REDUCED_HOURS` default **6**
- Taxas default 30% / 50% (não publicar como reembolso)
Fonte de verdade comercial: `sistemas/carinho-financeiro/docs/politicas.md`.

Health: `GET /up` (Laravel) e `GET /api/health` + `GET /api/status` (módulo). Rotas de API **sem** `/v1`.

## Instalacao

```bash
cd sistemas/carinho-operacao
composer install

# Configure ambiente
cp .env.example .env
php artisan key:generate

# Execute migrations
php artisan migrate

# Popule dados de dominio
php artisan db:seed

# Inicie servidor
php artisan serve
```

## Variaveis de Ambiente

```env
# Banco de dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carinho_operacao
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Token interno
INTERNAL_API_TOKEN=seu-token-seguro

# Z-API (WhatsApp)
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=seu-instance-id
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token

# Integracoes internas
CRM_BASE_URL=https://crm.carinho.com.vc/api
CRM_TOKEN=token-crm

CUIDADORES_BASE_URL=https://cuidadores.carinho.com.vc/api
CUIDADORES_TOKEN=token-cuidadores

ATENDIMENTO_BASE_URL=https://atendimento.carinho.com.vc/api
ATENDIMENTO_TOKEN=token-atendimento

FINANCEIRO_BASE_URL=https://financeiro.carinho.com.vc/api
FINANCEIRO_TOKEN=token-financeiro
```

## Tarefas Agendadas

| Tarefa | Frequência | Descrição |
|--------|------------|-----------|
| CheckScheduleDelays | 5 minutos | Verifica atrasos em check-ins |
| SendScheduleReminders (24h) | Diário 08:00 | Envia lembretes 24h antes |
| SendScheduleReminders (2h) | Horário | Envia lembretes 2h antes |
| CheckEmergencyEscalation | 10 minutos | Escalona emergências pendentes |

## Segurança e LGPD

- Controle de acesso por papel (operação x supervisor)
- Token interno para comunicacao entre sistemas
- Registro de auditoria de alterações de agenda
- Retencao de dados operacionais conforme política
- Logs de eventos operacionais

## Monitoramento

- Health check: `GET /api/health`
- Status detalhado: `GET /api/status`
- Alertas para atrasos, faltas e falta de cuidador
- Metricas de SLA e ocupacao

## Identidade Visual

As cores e tipografia seguem o padrão da marca Carinho com Você:

- **Primary:** #5BBFAD (Verde Carinho)
- **Secondary:** #F4F7F9
- **Accent:** #F5C6AA
- **Text:** #1F2933
- **Success:** #38A169
- **Warning:** #D69E2E
- **Danger:** #E53E3E

Veja `public/css/brand.css` para os estilos completos.

## Suporte

- Email: operacao@carinho.com.vc
- Emergências: emergencia@carinho.com.vc
