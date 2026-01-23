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

## Controles Operacionais (Implementados)

### Cadastro Completo
- [x] CPF com validação e unicidade
- [x] Data de nascimento com validação de idade mínima
- [x] Endereço completo estruturado
- [x] Contato de emergência
- [x] Origem do cadastro (fonte de recrutamento)
- [x] Campo de indicação (cuidador que indicou)

### Controle de Carga de Trabalho
- [x] Registro de alocações/serviços
- [x] Controle de horas trabalhadas por semana
- [x] Limite máximo de horas semanais (configurável)
- [x] Alertas de sobrecarga
- [x] Histórico de workload semanal
- Endpoints: `/caregivers/{id}/assignments`, `/caregivers/{id}/workload`

### Controle de Documentos
- [x] Data de emissão e vencimento de documentos
- [x] Alertas de documentos vencendo (30 dias)
- [x] Alertas de documentos vencidos
- [x] Escopo para buscar documentos expirados/expirando

### Gestão de Afastamentos
- [x] Tipos: Atestado, Férias, Licença Pessoal, Maternidade
- [x] Fluxo de aprovação
- [x] Verificação de conflitos
- [x] Integração com disponibilidade
- Endpoints: `/caregivers/{id}/leaves`, `/leaves/pending`

### Ocorrências com Severidade
- [x] Níveis: Leve, Moderada, Grave, Crítica
- [x] Sugestão automática de severidade por tipo
- [x] Registro de resolução
- [x] Escopo para ocorrências graves não resolvidas

### Indicadores Operacionais
- [x] Dashboard completo de métricas
- [x] Taxa de ativação
- [x] Taxa de ocupação
- [x] Alertas operacionais centralizados
- [x] Métricas por cidade e tipo de cuidado
- Endpoints: `/metrics/dashboard`, `/metrics/alerts`

## Proximas Melhorias (Sugestoes)

### Curto Prazo
- [ ] Formulario web responsivo para cadastro
- [ ] Pagina de assinatura de contrato
- [ ] Relatórios exportáveis (CSV/PDF)

### Medio Prazo
- [ ] App mobile para cuidadores
- [ ] Sistema de ranking e gamificacao
- [ ] Treinamentos online integrados

### Longo Prazo
- [ ] Machine learning para matching
- [ ] Analise preditiva de desempenho
- [ ] Integracao com planos de saude
