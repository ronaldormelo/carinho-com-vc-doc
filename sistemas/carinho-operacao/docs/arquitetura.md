# Arquitetura

## Visao Geral

Sistema operacional de alocacao e execucao do servico (operacao.carinho.com.vc). Orquestra agenda, match cliente-cuidador, checklists e comunicacao de status.

## Stack Tecnologica

| Componente | Tecnologia | Versao |
|------------|------------|--------|
| Linguagem | PHP | 8.2+ |
| Framework | Laravel | 11.x |
| Banco de dados | MySQL | 8.0+ |
| Cache | Redis | 7.x |
| Filas | Laravel Horizon | 5.x |
| HTTP Client | Guzzle | 7.x |

## Arquitetura em Camadas

```
┌─────────────────────────────────────────────────────────────┐
│                      APRESENTACAO                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ Controllers │  │  Webhooks   │  │   Views     │         │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘         │
└─────────┼────────────────┼────────────────┼─────────────────┘
          │                │                │
┌─────────┼────────────────┼────────────────┼─────────────────┐
│         ▼                ▼                ▼   APLICACAO     │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Services                          │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐  │   │
│  │  │ Schedule │ │  Match   │ │ Checkin  │ │ Notif. │  │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └────────┘  │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐            │   │
│  │  │Emergency │ │  Subst.  │ │ Request  │            │   │
│  │  └──────────┘ └──────────┘ └──────────┘            │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                                 │
│  ┌────────────────────────┼────────────────────────────┐   │
│  │                        ▼          Jobs              │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐            │   │
│  │  │SendNotif │ │Emergency │ │SyncFinan │            │   │
│  │  └──────────┘ └──────────┘ └──────────┘            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
┌───────────────────────────┼─────────────────────────────────┐
│                           ▼           DOMINIO               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                      Models                          │   │
│  │  ServiceRequest, Assignment, Schedule, Checkin,     │   │
│  │  Checklist, ServiceLog, Substitution, Notification, │   │
│  │  Emergency + Domain Tables                           │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
┌───────────────────────────┼─────────────────────────────────┐
│                           ▼        INFRAESTRUTURA           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  MySQL   │  │  Redis   │  │ Z-API    │  │ Internos │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Componentes Principais

### Controllers
Responsaveis por receber requisicoes HTTP, validar entrada e delegar para Services.

- `ServiceRequestController` - Solicitacoes de servico
- `ScheduleController` - Agendamentos
- `CheckinController` - Check-in/out e checklists
- `AssignmentController` - Alocacoes
- `EmergencyController` - Emergencias
- `NotificationController` - Notificacoes
- `WebhookController` - Webhooks externos
- `HealthController` - Health checks

### Services
Contem a logica de negocios isolada dos controllers.

- `ScheduleService` - Gestao de agenda
- `MatchService` - Motor de match
- `CheckinService` - Check-in/out
- `NotificationService` - Notificacoes
- `SubstitutionService` - Substituicoes
- `EmergencyService` - Emergencias
- `ServiceRequestService` - Solicitacoes

### Integrations
Clientes para comunicacao com sistemas externos.

- `CrmClient` - Sistema CRM
- `CuidadoresClient` - Sistema de Cuidadores
- `AtendimentoClient` - Sistema de Atendimento
- `FinanceiroClient` - Sistema Financeiro
- `ZApiClient` - WhatsApp (Z-API)

### Jobs
Processamento assincrono de tarefas.

- `SendNotification` - Envio de notificacoes
- `SendWhatsAppNotification` - WhatsApp especifico
- `ProcessEmergencyAlert` - Alertas de emergencia
- `CheckScheduleDelays` - Verificacao de atrasos
- `SendScheduleReminders` - Lembretes
- `SyncWithFinanceiro` - Sincronizacao financeira
- `CheckEmergencyEscalation` - Escalonamento

### Models
Representacao das entidades de dominio.

**Entidades Principais:**
- `ServiceRequest` - Solicitacao de servico
- `Assignment` - Alocacao de cuidador
- `Schedule` - Agendamento
- `Checkin` - Registro de check-in/out
- `Checklist` / `ChecklistEntry` - Checklists
- `ServiceLog` - Logs de servico
- `Substitution` - Substituicoes
- `Notification` - Notificacoes
- `Emergency` - Emergencias

**Tabelas de Dominio:**
- `DomainServiceType` - Tipos de servico
- `DomainUrgencyLevel` - Niveis de urgencia
- `DomainServiceStatus` - Status de servico
- `DomainAssignmentStatus` - Status de alocacao
- `DomainScheduleStatus` - Status de agenda
- `DomainChecklistType` - Tipos de checklist
- `DomainCheckType` - Tipos de check
- `DomainNotificationStatus` - Status de notificacao
- `DomainEmergencySeverity` - Severidade de emergencia

## Fluxos Principais

### Fluxo de Novo Servico
```
1. Atendimento cria demanda
2. Webhook recebe notificacao
3. ServiceRequest criado
4. MatchService busca candidatos
5. Assignment criado (auto ou manual)
6. Schedules criados
7. Cliente notificado
```

### Fluxo de Atendimento
```
1. Cuidador faz check-in
2. Schedule status -> in_progress
3. Cliente notificado (inicio)
4. Cuidador preenche checklists
5. Cuidador registra atividades
6. Cuidador faz check-out
7. Schedule status -> done
8. Cliente notificado (fim)
9. Horas sincronizadas com Financeiro
```

### Fluxo de Substituicao
```
1. Necessidade identificada (atraso, ausencia)
2. SubstitutionService busca candidatos
3. Novo cuidador selecionado
4. Assignment original -> replaced
5. Novo Assignment criado
6. Schedules futuros transferidos
7. Cliente notificado
```

## Dados e Armazenamento

### MySQL
- Dados transacionais
- Relacionamentos entre entidades
- Historico completo

### Redis
- Cache de agenda (5 min TTL)
- Cache de candidatos (1 min TTL)
- Filas de jobs
- Rate limiting

## Seguranca

### Autenticacao
- Token interno entre sistemas
- Assinatura em webhooks

### Autorizacao
- Middleware por rota
- Verificacao de propriedade de recursos

### Auditoria
- Log de alteracoes de agenda
- Rastreamento de substituicoes
- Historico de notificacoes

## Escalabilidade

### Horizontal
- Multiplas instancias stateless
- Load balancer na frente
- Workers de fila separados

### Vertical
- Configuracoes ajustaveis
- Indices otimizados
- Cache agressivo

## Observabilidade

### Logs
- Estruturados com contexto
- Niveis: INFO, WARNING, ERROR
- Retencao: 90 dias

### Health Checks
- `/api/health` - Basico
- `/api/status` - Com dependencias

### Metricas
- Tempo de resposta
- Taxa de sucesso
- Volume de operacoes
