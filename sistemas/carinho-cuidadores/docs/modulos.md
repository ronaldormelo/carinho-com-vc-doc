# Modulos do Sistema Carinho Cuidadores

## Visao Geral

O sistema e composto por modulos independentes que trabalham em conjunto para
gerenciar todo o ciclo de vida dos cuidadores.

---

## 1. Modulo de Cadastro

### Responsabilidades
- Receber dados do formulario de cadastro
- Validar informacoes basicas
- Criar registro inicial do cuidador
- Disparar notificacao de boas-vindas

### Componentes
- `CaregiverController::store()`
- `CaregiverService::create()`
- `SendCaregiverNotification` (job)
- `SyncCaregiverWithCrm` (job)

### Dados Coletados
- Nome completo
- Telefone (WhatsApp)
- Email (opcional)
- Cidade
- Anos de experiencia
- Resumo do perfil
- Habilidades/tipos de cuidado
- Disponibilidade
- Regioes de atuacao

---

## 2. Modulo de Documentos

### Responsabilidades
- Receber upload de documentos
- Validar formato e tamanho
- Enviar para armazenamento seguro
- Processar validacao automatica
- Permitir validacao manual

### Componentes
- `DocumentController`
- `DocumentValidationService`
- `ProcessDocumentValidation` (job)
- `DocumentosClient` (integracao)

### Tipos de Documento
| Codigo | Descricao | Obrigatorio |
|--------|-----------|-------------|
| `id` | Documento de identidade | Sim |
| `cpf` | CPF | Sim |
| `address` | Comprovante de endereco | Sim |
| `certificate` | Certificado de curso | Nao |
| `other` | Outro documento | Nao |

### Status de Documento
- `pending` - Aguardando validacao
- `verified` - Aprovado
- `rejected` - Rejeitado

---

## 3. Modulo de Triagem

### Responsabilidades
- Verificar elegibilidade para ativacao
- Classificar nivel de prontidao
- Identificar requisitos pendentes

### Componentes
- `TriageService::checkEligibility()`
- `TriageService::classifyReadiness()`

### Criterios de Elegibilidade
1. Documentos obrigatorios aprovados
2. Contrato assinado
3. Perfil completo
4. Disponibilidade cadastrada
5. Regioes de atuacao definidas

### Niveis de Prontidao
| Nivel | Descricao |
|-------|-----------|
| `premium` | Nota >= 4.5, sem incidentes |
| `standard` | Nota >= 4.0, ate 1 incidente |
| `basic` | Elegivel com restricoes |
| `almost_ready` | 1-2 requisitos pendentes |
| `incomplete` | 3+ requisitos pendentes |

---

## 4. Modulo de Contratos

### Responsabilidades
- Gerar contratos/termos digitais
- Enviar para assinatura
- Registrar assinaturas
- Ativar contratos

### Componentes
- `ContractController`
- `ContractService`
- `ProcessContractSign` (job)
- `DocumentosClient` (integracao)

### Tipos de Contrato
- `termo_responsabilidade` - Termo padrao do cuidador
- `contrato_prestacao` - Contrato de prestacao de servicos

### Status de Contrato
- `draft` - Rascunho (gerado)
- `signed` - Assinado
- `active` - Ativo
- `closed` - Encerrado

---

## 5. Modulo de Busca

### Responsabilidades
- Busca avancada com filtros
- Busca rapida por telefone/nome
- Busca por disponibilidade
- Estatisticas do banco

### Componentes
- `SearchController`
- `SearchService`

### Filtros Disponiveis
- Status
- Cidade/Bairro
- Tipo de cuidado
- Nivel de habilidade
- Experiencia minima
- Nota minima
- Dias da semana disponiveis

### Cache
- Filtros: 5 minutos
- Estatisticas: 5 minutos
- Chave: `cuidadores_*`

---

## 6. Modulo de Comunicacao

### Responsabilidades
- Envio de notificacoes via WhatsApp
- Envio de emails
- Processamento de mensagens recebidas
- Registro de comunicacoes

### Componentes
- `NotificationService`
- `WebhookController`
- `ProcessCaregiverMessage` (job)
- `ZApiClient` (integracao)

### Tipos de Notificacao
| Tipo | WhatsApp | Email |
|------|----------|-------|
| `welcome` | Sim | Sim |
| `activated` | Sim | Sim |
| `deactivated` | Sim | Sim |
| `blocked` | Sim | Sim |
| `document_approved` | Sim | Sim |
| `document_rejected` | Sim | Sim |
| `contract_ready` | Sim | Sim |
| `rating_received` | Sim | Nao |

---

## 7. Modulo de Avaliacoes

### Responsabilidades
- Registrar avaliacoes pos-servico
- Calcular metricas (media, tendencia)
- Identificar cuidadores que precisam de atencao
- Ranking de melhores avaliados

### Componentes
- `RatingController`
- `RatingService`
- `SyncRatingWithCrm` (job)

### Metricas
- Media geral
- Distribuicao por nota (1-5)
- Tendencia (ultimos 30 dias)
- Top rated por cidade

---

## 8. Modulo de Ocorrencias

### Responsabilidades
- Registrar incidentes/intercorrencias
- Categorizar por tipo
- Sincronizar com CRM
- Estatisticas de ocorrencias

### Componentes
- `IncidentController`
- `SyncIncidentWithCrm` (job)

### Tipos de Incidente
| Codigo | Descricao |
|--------|-----------|
| `atraso` | Atraso |
| `falta` | Falta sem aviso |
| `comportamento` | Comportamento inadequado |
| `qualidade` | Qualidade do servico |
| `reclamacao` | Reclamacao do cliente |
| `comunicacao` | Falha de comunicacao |
| `outro` | Outro |

---

## 9. Modulo de Treinamentos

### Responsabilidades
- Registrar cursos e treinamentos
- Controlar conclusao
- Sugerir cursos disponiveis

### Componentes
- `TrainingController`

### Cursos Sugeridos
- Cuidados Basicos com Idosos
- Primeiros Socorros
- Cuidados com PCD
- TEA - Transtorno do Espectro Autista
- Cuidados Pos-Operatorios
- Comunicacao e Postura Profissional
- Administracao de Medicamentos

---

## Fluxo Principal

```
Cadastro → Upload Docs → Validacao → Contrato → Assinatura → Ativacao
    ↓          ↓            ↓           ↓           ↓           ↓
  CRM       Storage      Triagem    Documentos   Email/WA   Operacao
```

## Filas de Processamento

| Fila | Jobs | Prioridade |
|------|------|------------|
| `notifications` | SendCaregiverNotification | Alta |
| `documents` | ProcessDocumentValidation | Media |
| `contracts` | ProcessContractSign | Media |
| `integrations` | SyncCaregiverWithCrm, SyncIncidentWithCrm, SyncRatingWithCrm | Baixa |
| `messages` | ProcessCaregiverMessage | Alta |
