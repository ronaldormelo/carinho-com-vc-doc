# Carinho Operacao

**Subdominio:** operacao.carinho.com.vc

## Descricao

Sistema operacional que conecta cliente e cuidador. Gerencia agenda, alocacao, execucao do servico e comunicacao de status. Este sistema e o coracao da operacao diaria, responsavel por garantir que cada atendimento aconteca de forma fluida e com qualidade.

## Stack Tecnologica

- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0+
- **Cache e filas:** Redis
- **Mensageria:** Laravel Horizon

## Modulos Implementados

### 1. Agenda Compartilhada e Agendamentos
- Criacao e gerenciamento de agendamentos por cliente e cuidador
- Validacao de disponibilidade com intervalos minimos
- Visualizacao de agenda por periodo
- Cache de agenda para performance

### 2. Match Cliente x Cuidador
- Motor de match por perfil, disponibilidade e regiao
- Sistema de pontuacao ponderada (skills, disponibilidade, regiao, avaliacao)
- Match automatico para scores acima do minimo configurado
- Verificacao de compatibilidade baseada em historico

### 3. Check-in/Check-out e Checklists
- Registro de check-in com validacao de localizacao
- Registro de check-out com atividades realizadas
- Checklists configurados de inicio e fim de atendimento
- Logs de atividades durante o servico

### 4. Registro de Servico
- Logs de atividades realizadas
- Notas e observacoes do cuidador
- Historico completo por agendamento

### 5. Notificacoes
- Notificacao de inicio e fim de servico para cliente
- Lembretes de agendamento (24h e 2h antes)
- Notificacao de alocacao e substituicao de cuidador
- Alertas de emergencia
- Suporte a WhatsApp (Z-API), Email e Push

### 6. Substituicao e Emergencias
- Processo automatico de busca de substituto
- Transferencia de agendamentos futuros
- Registro de emergencias com severidade
- Escalonamento automatico de emergencias nao resolvidas

### 7. Politicas de Cancelamento
- Cancelamento gratuito (48h+ de antecedencia)
- Taxa reduzida (24-48h de antecedencia)
- Taxa integral (menos de 24h)
- Integracao com Financeiro para cobranca

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

| Sistema | Funcao | Base URL |
|---------|--------|----------|
| CRM | Dados de cliente e contrato | crm.carinho.com.vc/api |
| Cuidadores | Disponibilidade e perfil | cuidadores.carinho.com.vc/api |
| Atendimento | Detalhes da demanda e urgencia | atendimento.carinho.com.vc/api |
| Financeiro | Cobranca e repasse | financeiro.carinho.com.vc/api |

### APIs Externas

| API | Funcao | Documentacao |
|-----|--------|--------------|
| Z-API | WhatsApp Business | https://developer.z-api.io/ |

## API Endpoints

### Solicitacoes de Servico
- `GET /api/service-requests` - Lista solicitacoes
- `GET /api/service-requests/open` - Solicitacoes abertas
- `GET /api/service-requests/urgent` - Solicitacoes urgentes
- `POST /api/service-requests` - Cria solicitacao
- `POST /api/service-requests/{id}/process` - Processa alocacao
- `POST /api/service-requests/{id}/cancel` - Cancela solicitacao

### Agendamentos
- `GET /api/schedules` - Lista agendamentos
- `GET /api/schedules/today` - Agendamentos de hoje
- `POST /api/schedules` - Cria agendamentos
- `POST /api/schedules/check-availability` - Verifica disponibilidade
- `GET /api/schedules/{id}/cancellation-policy` - Politica de cancelamento

### Check-in/Check-out
- `POST /api/checkin/schedule/{id}/in` - Realiza check-in
- `POST /api/checkin/schedule/{id}/out` - Realiza check-out
- `POST /api/checkin/schedule/{id}/activities` - Registra atividades
- `GET /api/checkin/delays` - Verifica atrasos

### Alocacoes
- `GET /api/assignments/service-request/{id}/candidates` - Busca candidatos
- `POST /api/assignments/service-request/{id}/assign` - Aloca cuidador
- `POST /api/assignments/{id}/substitute` - Substitui cuidador

### Emergencias
- `GET /api/emergencies/pending` - Emergencias pendentes
- `GET /api/emergencies/critical` - Emergencias criticas
- `POST /api/emergencies` - Registra emergencia
- `POST /api/emergencies/{id}/resolve` - Resolve emergencia

### Notificacoes
- `GET /api/notifications/pending` - Notificacoes pendentes
- `GET /api/notifications/client/{id}/history` - Historico do cliente
- `POST /api/notifications/{id}/retry` - Reenvia notificacao

## Configuracoes Principais

### Agendamento (`config/operacao.php`)
- Antecedencia minima: 24 horas
- Duracao minima: 4 horas
- Duracao maxima: 12 horas
- Intervalo entre atendimentos: 60 minutos

### Match
- Peso de habilidades: 35%
- Peso de disponibilidade: 25%
- Peso de regiao: 20%
- Peso de avaliacao: 20%
- Score minimo para auto-match: 70

### Check-in
- Tolerancia antecipada: 30 minutos
- Tolerancia de atraso: 15 minutos
- Validacao de localizacao: ativada
- Distancia maxima: 500 metros

### Cancelamento
- Gratuito: 48+ horas antes
- Taxa reduzida (30%): 24-48 horas
- Taxa integral (50%): menos de 24 horas

## Instalacao

```bash
# Clone o repositorio
git clone [repo-url]
cd carinho-operacao

# Instale dependencias
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

| Tarefa | Frequencia | Descricao |
|--------|------------|-----------|
| CheckScheduleDelays | 5 minutos | Verifica atrasos em check-ins |
| SendScheduleReminders (24h) | Diario 08:00 | Envia lembretes 24h antes |
| SendScheduleReminders (2h) | Horario | Envia lembretes 2h antes |
| CheckEmergencyEscalation | 10 minutos | Escalona emergencias pendentes |

## Seguranca e LGPD

- Controle de acesso por papel (operacao x supervisor)
- Token interno para comunicacao entre sistemas
- Registro de auditoria de alteracoes de agenda
- Retencao de dados operacionais conforme politica
- Logs de eventos operacionais

## Monitoramento

- Health check: `GET /api/health`
- Status detalhado: `GET /api/status`
- Alertas para atrasos, faltas e falta de cuidador
- Metricas de SLA e ocupacao

## Identidade Visual

As cores e tipografia seguem o padrao da marca Carinho com Voce:

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
- Emergencias: emergencia@carinho.com.vc
