# Arquitetura - Carinho CRM

## Visão Geral

Base única de leads e clientes (crm.carinho.com.vc). Centraliza o pipeline comercial, histórico de atendimento e contratos digitais.

## Stack Tecnológico

| Componente | Tecnologia | Versão |
|------------|------------|--------|
| Linguagem | PHP | 8.2+ |
| Framework | Laravel | 11.x |
| Banco de dados | MySQL | 8.0+ |
| Cache | Redis | 7.x |
| Filas | Redis Queue | - |
| Autenticação | Laravel Sanctum | 4.x |
| Auditoria | Spatie Activity Log | 4.x |
| Permissões | Spatie Laravel Permission | 6.x |
| Exports | Maatwebsite Excel | 3.x |

## Arquitetura da Aplicação

```
┌─────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  API Controllers  │  Webhook Controllers  │  Web Controllers    │
│    (REST API)     │   (Z-API, Internos)   │    (Views)         │
└────────┬──────────┴──────────┬────────────┴──────────┬──────────┘
         │                     │                       │
         ▼                     ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│  Form Requests  │  API Resources  │  Events  │  Jobs  │  Listeners
└────────┬────────┴────────┬────────┴────┬─────┴───┬────┴────┬────┘
         │                 │             │         │         │
         ▼                 ▼             ▼         ▼         ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DOMAIN LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│  Services   │  Repositories  │  Models  │  Traits  │  Policies  │
└────────┬────┴───────┬────────┴─────┬────┴────┬─────┴──────┬─────┘
         │            │              │         │            │
         ▼            ▼              ▼         ▼            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      INFRASTRUCTURE LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  MySQL   │  Redis Cache  │  Redis Queue  │  External APIs       │
└──────────┴───────────────┴───────────────┴──────────────────────┘
```

## Componentes Principais

### 1. Models e Entidades

#### Domínios (Enums)
- `DomainUrgencyLevel` - Níveis de urgência
- `DomainServiceType` - Tipos de serviço
- `DomainLeadStatus` - Status do lead no pipeline
- `DomainDealStatus` - Status do negócio
- `DomainContractStatus` - Status do contrato
- `DomainInteractionChannel` - Canais de interação
- `DomainPatientType` - Tipos de paciente
- `DomainTaskStatus` - Status de tarefas

#### Entidades Principais
- `Lead` - Lead captado
- `Client` - Cliente convertido
- `CareNeed` - Necessidades de cuidado
- `PipelineStage` - Estágio do pipeline
- `Deal` - Negócio/Oportunidade
- `Proposal` - Proposta comercial
- `Contract` - Contrato de serviço
- `Consent` - Consentimento LGPD
- `Task` - Tarefa de follow-up
- `Interaction` - Interação registrada
- `LossReason` - Motivo de perda

### 2. Services (Lógica de Negócio)

```
Services/
├── LeadService.php           # Gestão de leads
├── ClientService.php         # Gestão de clientes
├── DealService.php           # Gestão de deals
├── PipelineService.php       # Pipeline e métricas
├── ContractService.php       # Contratos e aceite digital
├── TaskService.php           # Tarefas e follow-up
├── InteractionService.php    # Interações
├── ReportService.php         # Relatórios e dashboards
└── Integrations/
    ├── ZApiService.php                # WhatsApp via Z-API
    ├── BaseInternalService.php        # Base para integrações
    ├── CarinhoSiteService.php         # Integração Site
    ├── CarinhoMarketingService.php    # Integração Marketing
    ├── CarinhoAtendimentoService.php  # Integração Atendimento
    ├── CarinhoOperacaoService.php     # Integração Operação
    ├── CarinhoFinanceiroService.php   # Integração Financeiro
    ├── CarinhoDocumentosService.php   # Integração Documentos
    └── CarinhoCuidadoresService.php   # Integração Cuidadores
```

### 3. Events e Listeners

#### Eventos Disparados
| Evento | Quando | Listeners |
|--------|--------|-----------|
| `LeadCreated` | Novo lead | Notifica atendimento, Mensagem boas-vindas |
| `LeadStatusChanged` | Status alterado | Atualiza métricas, Sincroniza atendimento |
| `LeadConverted` | Lead → Cliente | Cria cliente, Notifica operação |
| `LeadLost` | Lead perdido | Registra motivo, Atualiza métricas |
| `DealCreated` | Novo deal | Log de atividade |
| `DealStageChanged` | Mudança de estágio | Notifica, Log de atividade |
| `DealWon` | Deal ganho | Cria contrato, Notifica financeiro |
| `ContractSigned` | Contrato assinado | Ativa cliente, Sincroniza financeiro |
| `ContractExpiring` | Contrato expirando | Cria tarefa renovação, Notifica cliente |
| `TaskCreated` | Nova tarefa | Notifica responsável |
| `TaskOverdue` | Tarefa atrasada | Escala tarefa |
| `InteractionRecorded` | Nova interação | Atualiza último contato |

### 4. Jobs Assíncronos

| Job | Frequência | Função |
|-----|------------|--------|
| `CheckExpiringContractsJob` | Diário 8h | Verifica contratos expirando |
| `CheckOverdueTasksJob` | 4 horas | Verifica tarefas atrasadas |
| `SyncWithExternalSystemsJob` | Horário | Sincroniza com sistemas |
| `GenerateDailyReportJob` | Diário 6h | Gera relatórios |
| `ExportReportJob` | Sob demanda | Exporta dados |

## Integrações

### Diagrama de Integrações

```
                    ┌──────────────────┐
                    │   Z-API          │
                    │   (WhatsApp)     │
                    └────────┬─────────┘
                             │
                             ▼
┌──────────┐     ┌───────────────────────┐     ┌──────────┐
│  Site    │────▶│                       │◀────│ Marketing│
└──────────┘     │                       │     └──────────┘
                 │                       │
┌──────────┐     │    CARINHO CRM       │     ┌──────────┐
│Atendimento│◀──▶│                       │◀──▶│ Operação │
└──────────┘     │   crm.carinho.com.vc │     └──────────┘
                 │                       │
┌──────────┐     │                       │     ┌──────────┐
│Financeiro│◀───│                       │────▶│Documentos│
└──────────┘     └───────────────────────┘     └──────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │   Cuidadores     │
                    └──────────────────┘
```

### Fluxo de Dados: Novo Lead

```
[Site/WhatsApp] 
     │
     ▼
[Webhook CRM] ──► [LeadService.createLead()]
     │
     ▼
[Event: LeadCreated]
     │
     ├──► [NotifyAtendimentoNewLead] ──► [Atendimento API]
     │
     └──► [SendLeadWelcomeMessage] ──► [Z-API WhatsApp]
```

### Fluxo de Dados: Deal Ganho

```
[DealController.markAsWon()]
     │
     ▼
[Event: DealWon]
     │
     ├──► [CreateContractFromDeal] ──► [Novo Contrato]
     │
     └──► [NotifyFinanceiroNewContract] ──► [Financeiro API]
```

## Dados e Armazenamento

### Modelo de Dados Simplificado

```
leads (1) ─────────────── (1) clients
  │                            │
  │ (1:N)                      │ (1:N)
  ▼                            ▼
deals ──────────────────── contracts
  │                            │
  │ (1:N)                      │ (1:N)
  ▼                            ▼
proposals                  consents

leads (1:N) tasks
leads (1:N) interactions
leads (1:1) loss_reasons
clients (1:N) care_needs
```

### Índices Otimizados

```sql
-- Leads
CREATE INDEX idx_leads_status_city ON leads (status_id, city);
CREATE INDEX idx_leads_created ON leads (created_at);
CREATE INDEX idx_leads_source ON leads (source);

-- Deals
CREATE INDEX idx_deals_stage_status ON deals (stage_id, status_id);

-- Tasks
CREATE INDEX idx_tasks_assignee ON tasks (assigned_to, status_id, due_at);
CREATE INDEX idx_tasks_due ON tasks (due_at, status_id);

-- Interactions
CREATE INDEX idx_interactions_lead_time ON interactions (lead_id, occurred_at);
```

## Segurança e LGPD

### Camadas de Segurança

1. **Autenticação:** Laravel Sanctum (tokens API)
2. **Autorização:** Policies + Gates + Spatie Permissions
3. **Criptografia:** AES-256 para campos sensíveis
4. **Auditoria:** Spatie Activity Log
5. **Headers:** CSP, HSTS, X-Frame-Options, etc.
6. **Sanitização:** Middleware para inputs
7. **Rate Limiting:** Por endpoint e por usuário

### Middleware Stack

```
Global:
├── SecureHeaders
└── HandleCors

API:
├── EnsureFrontendRequestsAreStateful (Sanctum)
├── ThrottleRequests
├── SubstituteBindings
├── SanitizeInput
└── LogAuditTrail

Webhooks:
└── VerifyInternalWebhook
```

## Escalabilidade e Desempenho

### Estratégias de Cache

| Tipo | TTL | Chave |
|------|-----|-------|
| Dashboard | 5 min | `reports:dashboard` |
| Pipeline Board | 1 min | `pipeline:board` |
| Relatórios | 10 min | `reports:*` |
| Domínios | 1 hora | `model:domain_*:all` |

### Filas

```
crm-default       # Jobs gerais
crm-notifications # Notificações
crm-integrations  # Integrações externas
crm-reports       # Geração de relatórios
crm-exports       # Exportações
```

## Observabilidade

### Logs

```
logs/
├── crm.log           # Log geral
├── integrations.log  # Integrações externas
├── audit.log         # Trilha de auditoria
├── whatsapp.log      # Z-API/WhatsApp
└── emergency.log     # Erros críticos
```

### Métricas Monitoradas

- Taxa de conversão de leads
- Tempo médio de resposta
- Deals por estágio
- Tarefas atrasadas
- Contratos expirando
- Erros de integração

## Backup e Resiliência

- Backup diário do MySQL
- Retenção conforme LGPD (dados pessoais)
- Teste de restore mensal
- Logs mantidos por 365 dias (auditoria)
