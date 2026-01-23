# Atividades

Lista de atividades implementadas no sistema Carinho Cuidadores.

## Status: Implementado

### Captacao e Cadastro

- [x] Criar formulario de cadastro digital
  - Endpoint: `POST /api/caregivers`
  - Controller: `CaregiverController::store()`

- [x] Definir campos de experiencia, formacao e disponibilidade
  - Campos: name, phone, email, city, experience_years, profile_summary
  - Relacionamentos: skills, availability, regions

- [x] Coletar documentos obrigatorios
  - Endpoint: `POST /api/caregivers/{id}/documents`
  - Tipos: id, cpf, address, certificate, other

### Triagem e Validacao

- [x] Definir criterios minimos de curadoria
  - Service: `TriageService::checkEligibility()`
  - Criterios: documentos, contrato, perfil, disponibilidade, regioes

- [x] Criar processo de validacao documental
  - Job: `ProcessDocumentValidation`
  - Integracao: `DocumentosClient::validate()`

- [x] Classificar por tipo de cuidado, regiao e disponibilidade
  - Models: `CaregiverSkill`, `CaregiverRegion`, `CaregiverAvailability`
  - Service: `SearchService::search()`

### Contratos e Ativacao

- [x] Implementar contrato digital e termo de responsabilidade
  - Service: `ContractService::createContract()`
  - Template: `resources/views/emails/termo_responsabilidade.blade.php`

- [x] Criar fluxo de ativacao/desativacao
  - Endpoints: `POST /api/caregivers/{id}/activate`, `deactivate`, `block`
  - Service: `CaregiverService::changeStatus()`

- [x] Manter banco pesquisavel com filtros
  - Endpoint: `POST /api/search`
  - Service: `SearchService`
  - Cache: Redis com TTL de 5 minutos

### Operacao e Comunicacao

- [x] Criar canal exclusivo de comunicacao com cuidadores
  - Integracao: `ZApiClient` (WhatsApp via Z-API)
  - Job: `ProcessCaregiverMessage`

- [x] Definir checklists de inicio e fim do atendimento
  - Integracao: `OperacaoClient::checkin()`, `checkout()`

- [x] Registrar ocorrencias e intercorrencias
  - Endpoint: `POST /api/caregivers/{id}/incidents`
  - Job: `SyncIncidentWithCrm`

### Qualidade

- [x] Implementar avaliacao pos-servico
  - Endpoint: `POST /api/caregivers/{id}/ratings`
  - Service: `RatingService::processRatingImpact()`

- [x] Monitorar nota media e taxa de substituicao
  - Endpoint: `GET /api/caregivers/{id}/ratings-summary`
  - Service: `RatingService::getSummary()`

- [x] Criar plano de melhoria e treinamento quando aplicavel
  - Endpoint: `POST /api/caregivers/{id}/trainings`
  - Cursos sugeridos disponiveis

## Integrações Implementadas

### WhatsApp (Z-API)
- [x] Envio de mensagens de texto
- [x] Envio de documentos
- [x] Recebimento de webhooks
- [x] Validacao de assinatura
- [x] Processamento de comandos simples

### CRM
- [x] Sincronizacao de cuidadores
- [x] Sincronizacao de avaliacoes
- [x] Sincronizacao de incidentes
- [x] Historico de status

### Operacao
- [x] Sincronizacao de disponibilidade
- [x] Notificacao de ativacao/desativacao
- [x] Suporte a check-in/check-out

### Documentos/LGPD
- [x] Upload de documentos
- [x] Validacao automatica
- [x] URLs assinadas
- [x] Criacao de contratos
- [x] Assinatura digital

### Atendimento
- [x] Registro de notificacoes
- [x] Encaminhamento de mensagens
- [x] Criacao de conversas

### Hub de Integracoes
- [x] Publicacao de eventos
- [x] Eventos padronizados

## Notificacoes Implementadas

| Evento | WhatsApp | Email |
|--------|----------|-------|
| Cadastro (boas-vindas) | Sim | Sim |
| Ativacao | Sim | Sim |
| Desativacao | Sim | Sim |
| Bloqueio | Sim | Sim |
| Documento aprovado | Sim | Sim |
| Documento rejeitado | Sim | Sim |
| Contrato pronto | Sim | Sim |
| Avaliacao recebida | Sim | Nao |

## Proximas Melhorias (Sugestoes)

### Curto Prazo
- [ ] Dashboard de metricas para admin
- [ ] Formulario web responsivo para cadastro
- [ ] Pagina de assinatura de contrato

### Medio Prazo
- [ ] App mobile para cuidadores
- [ ] Sistema de ranking e gamificacao
- [ ] Treinamentos online integrados

### Longo Prazo
- [ ] Machine learning para matching
- [ ] Analise preditiva de desempenho
- [ ] Integracao com planos de saude
