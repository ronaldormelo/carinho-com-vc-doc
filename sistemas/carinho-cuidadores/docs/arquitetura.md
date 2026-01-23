# Arquitetura

## Visao Geral

Sistema de recrutamento e gestao de cuidadores (cuidadores.carinho.com.vc).
Padroniza cadastro, validacao, contratos e comunicacao, garantindo base
confiavel e escalavel de profissionais para cuidado domiciliar.

## Stack Tecnica

| Componente | Tecnologia |
|------------|------------|
| Linguagem | PHP 8.2+ |
| Framework | Laravel 11 |
| Banco de dados | MySQL 8.0+ |
| Cache | Redis |
| Filas | Redis (Laravel Queue) |
| Storage | Integracao com Documentos/LGPD |

## Arquitetura de Camadas

```
┌─────────────────────────────────────────────────────────────┐
│                       ROTAS (API/Web)                       │
├─────────────────────────────────────────────────────────────┤
│                      CONTROLLERS                            │
│  CaregiverController, DocumentController, SearchController  │
├─────────────────────────────────────────────────────────────┤
│                        SERVICES                             │
│  CaregiverService, TriageService, NotificationService       │
├─────────────────────────────────────────────────────────────┤
│                     MODELS (Eloquent)                       │
│  Caregiver, CaregiverDocument, CaregiverSkill, etc.        │
├─────────────────────────────────────────────────────────────┤
│                       INTEGRACOES                           │
│  ZApiClient, CrmClient, OperacaoClient, DocumentosClient   │
├─────────────────────────────────────────────────────────────┤
│                   JOBS (Background)                         │
│  SendNotification, ProcessDocument, SyncWithCrm            │
└─────────────────────────────────────────────────────────────┘
```

## Componentes Principais

### Controllers
Responsaveis por receber requisicoes HTTP, validar entrada e retornar respostas JSON padronizadas.

- `CaregiverController` - CRUD e acoes de status
- `DocumentController` - Upload e validacao de documentos
- `SearchController` - Busca avancada de cuidadores
- `ContractController` - Gestao de contratos
- `RatingController` - Avaliacoes
- `IncidentController` - Ocorrencias
- `WebhookController` - Recebimento de webhooks

### Services
Contem a logica de negocio, isolada dos controllers.

- `CaregiverService` - Criacao, atualizacao, mudanca de status
- `TriageService` - Verificacao de elegibilidade
- `DocumentValidationService` - Validacao de documentos
- `ContractService` - Geracao e assinatura de contratos
- `SearchService` - Busca com filtros e cache
- `NotificationService` - Envio de notificacoes
- `RatingService` - Processamento de avaliacoes

### Integrations
Clientes HTTP para comunicacao com sistemas externos e internos.

- `ZApiClient` - WhatsApp via Z-API
- `CrmClient` - Sistema CRM interno
- `OperacaoClient` - Sistema de Operacao
- `DocumentosClient` - Sistema Documentos/LGPD
- `AtendimentoClient` - Sistema de Atendimento
- `IntegracoesClient` - Hub de eventos

### Jobs
Processamento assincrono em background via Redis.

- `SendCaregiverNotification` - Envio de notificacoes
- `ProcessDocumentValidation` - Validacao de documentos
- `ProcessContractSign` - Pos-processamento de assinatura
- `SyncCaregiverWithCrm` - Sincronizacao com CRM
- `SyncRatingWithCrm` - Sincronizacao de avaliacoes
- `SyncIncidentWithCrm` - Sincronizacao de incidentes
- `ProcessCaregiverMessage` - Mensagens WhatsApp

## Modelo de Dados

### Entidade Principal: Caregiver

```
caregivers
├── caregiver_documents (1:N)
├── caregiver_skills (1:N)
├── caregiver_availability (1:N)
├── caregiver_regions (1:N)
├── caregiver_contracts (1:N)
├── caregiver_ratings (1:N)
├── caregiver_incidents (1:N)
├── caregiver_training (1:N)
└── caregiver_status_history (1:N)
```

### Tabelas de Dominio
- `domain_caregiver_status` - Status do cuidador
- `domain_document_type` - Tipos de documento
- `domain_document_status` - Status de documento
- `domain_care_type` - Tipos de cuidado
- `domain_skill_level` - Niveis de habilidade
- `domain_contract_status` - Status de contrato

## Fluxo de Dados

### Cadastro de Cuidador
```
POST /api/caregivers
    → CaregiverController::store()
    → CaregiverService::create()
    → Caregiver::create()
    → SendCaregiverNotification (job)
    → SyncCaregiverWithCrm (job)
```

### Upload de Documento
```
POST /api/caregivers/{id}/documents
    → DocumentController::store()
    → DocumentValidationService::uploadDocument()
    → DocumentosClient::upload()
    → ProcessDocumentValidation (job)
```

### Ativacao de Cuidador
```
POST /api/caregivers/{id}/activate
    → CaregiverController::activate()
    → CaregiverService::changeStatus()
    → TriageService::checkEligibility()
    → SendCaregiverNotification (job)
    → SyncCaregiverWithCrm (job)
```

## Integracoes

### Arquitetura de Integracoes
```
                    ┌─────────────────┐
                    │  carinho-       │
                    │  cuidadores     │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐        ┌────▼────┐        ┌────▼────┐
    │  Z-API  │        │   CRM   │        │Operacao │
    │(WhatsApp)│        │         │        │         │
    └─────────┘        └─────────┘        └─────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐        ┌────▼────┐        ┌────▼────┐
    │Documentos│        │Atendim. │        │Integr.  │
    │  /LGPD  │        │         │        │   Hub   │
    └─────────┘        └─────────┘        └─────────┘
```

## Seguranca

### Autenticacao
- Token interno via header `X-Internal-Token` ou `Authorization: Bearer`
- Middleware `VerifyInternalToken` para todas as rotas protegidas

### Webhooks
- Validacao de assinatura HMAC para Z-API
- Token interno para webhooks de sistemas internos

### Dados Sensiveis
- Documentos armazenados em sistema dedicado com criptografia
- URLs assinadas para acesso temporario
- Logs de auditoria para acessos

## Escalabilidade

### Estrategias
- Stateless: sem estado de sessao no servidor
- Cache: Redis para consultas frequentes
- Filas: Processamento assincrono
- Indexes: Otimizados para filtros comuns

### Limites
- Paginacao: 20-100 itens por pagina
- Upload: 10 MB maximo
- Timeout: 8-15 segundos para APIs externas

## Observabilidade

### Logs
- Formato estruturado (JSON)
- Contexto: caregiver_id, document_id, job_id
- Niveis: DEBUG, INFO, WARNING, ERROR

### Monitoramento
- Health check: `GET /api/health`
- Verificacao de banco e cache
- Metricas de filas

## Backup e Recuperacao

- Backup diario automatico do banco
- Retencao de 30 dias
- Versionamento de documentos no sistema externo
