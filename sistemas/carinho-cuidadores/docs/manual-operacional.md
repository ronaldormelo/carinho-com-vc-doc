# Manual Operacional - Sistema Carinho Cuidadores

Guia passo a passo para operação do sistema seguindo as melhores práticas.

---

## Índice

1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Cadastro de Novo Cuidador](#2-cadastro-de-novo-cuidador)
3. [Validação de Documentos](#3-validação-de-documentos)
4. [Ativação do Cuidador](#4-ativação-do-cuidador)
5. [Gestão de Disponibilidade](#5-gestão-de-disponibilidade)
6. [Alocação em Serviços](#6-alocação-em-serviços)
7. [Registro de Avaliações](#7-registro-de-avaliações)
8. [Gestão de Ocorrências](#8-gestão-de-ocorrências)
9. [Gestão de Afastamentos](#9-gestão-de-afastamentos)
10. [Monitoramento e Indicadores](#10-monitoramento-e-indicadores)
11. [Alertas e Ações Corretivas](#11-alertas-e-ações-corretivas)

---

## 1. Visão Geral do Sistema

### 1.1 Pipeline do Cuidador

O cuidador passa pelas seguintes etapas:

```
Cadastro → Upload de Documentos → Validação → Contrato → Ativação → Operação
```

### 1.2 Status do Cuidador

| Status | Descrição |
|--------|-----------|
| **Pendente** | Cadastro realizado, aguardando documentação/validação |
| **Ativo** | Pronto para alocação em serviços |
| **Inativo** | Temporariamente indisponível (pode ser reativado) |
| **Bloqueado** | Impedido de atuar (problemas graves) |

### 1.3 Boas Práticas Gerais

- Sempre verifique os **alertas do sistema** no início do dia
- Mantenha documentos atualizados antes do vencimento
- Respeite o **limite de 44 horas semanais** por cuidador
- Registre todas as ocorrências, mesmo as leves
- Solicite avaliação após cada serviço concluído

---

## 2. Cadastro de Novo Cuidador

### 2.1 Pré-requisitos

Antes de iniciar o cadastro, tenha em mãos:
- Documento de identidade (RG)
- CPF
- Comprovante de endereço atualizado
- Certificados de cursos (se houver)
- Dados de contato de emergência

### 2.2 Passo a Passo

**Passo 1: Criar o cadastro básico**

```
POST /api/caregivers
```

Informações obrigatórias:
- Nome completo
- Telefone (WhatsApp)
- Cidade

Informações recomendadas:
- CPF (permite identificação única)
- Data de nascimento (validação de idade)
- Email
- Endereço completo

**Passo 2: Informar dados pessoais completos**

```json
{
  "name": "Maria Silva Santos",
  "phone": "11999998888",
  "cpf": "123.456.789-00",
  "birth_date": "1985-03-15",
  "email": "maria.silva@email.com",
  "city": "São Paulo",
  "address_street": "Rua das Flores",
  "address_number": "123",
  "address_complement": "Apto 45",
  "address_neighborhood": "Centro",
  "address_zipcode": "01234-567",
  "address_state": "SP",
  "experience_years": 5,
  "profile_summary": "Experiência com idosos e pós-operatório",
  "emergency_contact_name": "João Santos",
  "emergency_contact_phone": "11988887777",
  "recruitment_source": "referral"
}
```

**Passo 3: Cadastrar habilidades (tipos de cuidado)**

```
PUT /api/caregivers/{id}/skills/sync
```

```json
{
  "skills": [
    { "care_type_code": "idoso", "level_code": "avancado" },
    { "care_type_code": "pos_operatorio", "level_code": "intermediario" }
  ]
}
```

Tipos disponíveis:
| Código | Descrição |
|--------|-----------|
| `idoso` | Cuidado de Idosos |
| `pcd` | Pessoa com Deficiência |
| `tea` | Transtorno do Espectro Autista |
| `pos_operatorio` | Pós-Operatório |

Níveis:
| Código | Descrição |
|--------|-----------|
| `basico` | Até 1 ano de experiência |
| `intermediario` | 1 a 3 anos de experiência |
| `avancado` | Mais de 3 anos de experiência |

**Passo 4: Cadastrar disponibilidade**

```
PUT /api/caregivers/{id}/availability/sync
```

```json
{
  "availability": [
    { "day_of_week": 1, "start_time": "08:00", "end_time": "18:00" },
    { "day_of_week": 2, "start_time": "08:00", "end_time": "18:00" },
    { "day_of_week": 3, "start_time": "08:00", "end_time": "18:00" },
    { "day_of_week": 4, "start_time": "08:00", "end_time": "18:00" },
    { "day_of_week": 5, "start_time": "08:00", "end_time": "18:00" }
  ]
}
```

Dias da semana:
| Código | Dia |
|--------|-----|
| 0 | Domingo |
| 1 | Segunda-feira |
| 2 | Terça-feira |
| 3 | Quarta-feira |
| 4 | Quinta-feira |
| 5 | Sexta-feira |
| 6 | Sábado |

**Passo 5: Cadastrar regiões de atuação**

```
PUT /api/caregivers/{id}/regions/sync
```

```json
{
  "regions": [
    { "city": "São Paulo", "neighborhood": "Centro" },
    { "city": "São Paulo", "neighborhood": "Consolação" },
    { "city": "São Paulo", "neighborhood": "Bela Vista" }
  ]
}
```

### 2.3 Resultado Esperado

- Cuidador criado com status **Pendente**
- Notificação de boas-vindas enviada via WhatsApp e Email
- Dados sincronizados com o CRM

---

## 3. Validação de Documentos

### 3.1 Documentos Obrigatórios

| Tipo | Código | Obrigatório |
|------|--------|-------------|
| Documento de Identidade | `id` | Sim |
| CPF | `cpf` | Sim |
| Comprovante de Endereço | `address` | Sim |
| Certificado de Curso | `certificate` | Não |
| Outros | `other` | Não |

### 3.2 Passo a Passo

**Passo 1: Enviar documento para upload**

```
POST /api/caregivers/{id}/documents
```

```json
{
  "doc_type_id": 1,
  "file": "[arquivo]",
  "issued_at": "2024-01-15",
  "expires_at": "2034-01-15"
}
```

> **Boa Prática:** Sempre informe a data de vencimento para documentos que expiram (ex: CNH, certificados).

**Passo 2: Aguardar validação automática**

O sistema tenta validar automaticamente. Se não conseguir, o documento fica pendente para revisão manual.

**Passo 3: Validar manualmente (se necessário)**

Consulte documentos pendentes:
```
GET /api/documents/pending
```

Para aprovar:
```
POST /api/caregivers/{id}/documents/{docId}/approve
```

Para rejeitar:
```
POST /api/caregivers/{id}/documents/{docId}/reject
```

```json
{
  "reason": "Documento ilegível. Favor reenviar com melhor qualidade."
}
```

### 3.3 Resultado Esperado

- Documento armazenado com segurança (criptografado)
- Cuidador notificado sobre aprovação/rejeição
- Alerta automático quando documento estiver próximo do vencimento (30 dias)

---

## 4. Ativação do Cuidador

### 4.1 Requisitos para Ativação

O cuidador só pode ser ativado quando:

- [x] Todos os documentos obrigatórios aprovados
- [x] Contrato/Termo de responsabilidade assinado
- [x] Perfil completo (nome, telefone, cidade)
- [x] Pelo menos uma habilidade cadastrada
- [x] Disponibilidade informada
- [x] Região de atuação definida

### 4.2 Passo a Passo

**Passo 1: Verificar elegibilidade**

```
GET /api/caregivers/{id}/eligibility
```

Resposta esperada (quando elegível):
```json
{
  "is_eligible": true,
  "checks": {
    "documents": { "passed": true, "message": "Todos os documentos obrigatórios estão aprovados" },
    "contract": { "passed": true, "message": "Contrato assinado e ativo" },
    "profile": { "passed": true, "message": "Perfil está completo" },
    "availability": { "passed": true, "message": "Disponibilidade cadastrada" },
    "regions": { "passed": true, "message": "Regiões de atuação cadastradas" }
  },
  "missing_requirements": []
}
```

Se houver pendências, resolva-as antes de prosseguir.

**Passo 2: Gerar e enviar contrato**

```
POST /api/caregivers/{id}/contracts
```

```json
{
  "contract_type": "termo_responsabilidade"
}
```

```
POST /api/caregivers/{id}/contracts/{contractId}/send
```

O cuidador receberá o contrato via WhatsApp e Email para assinatura digital.

**Passo 3: Aguardar assinatura**

Após assinatura, o sistema atualiza automaticamente.

**Passo 4: Ativar cuidador**

```
POST /api/caregivers/{id}/activate
```

### 4.3 Resultado Esperado

- Status alterado para **Ativo**
- Cuidador notificado sobre ativação
- Disponível para busca e alocação
- Dados sincronizados com CRM

---

## 5. Gestão de Disponibilidade

### 5.1 Consultar Disponibilidade

```
GET /api/caregivers/{id}/availability
```

### 5.2 Atualizar Disponibilidade

**Adicionar novo horário:**
```
POST /api/caregivers/{id}/availability
```

```json
{
  "day_of_week": 6,
  "start_time": "08:00",
  "end_time": "12:00"
}
```

**Remover horário:**
```
DELETE /api/caregivers/{id}/availability/{availabilityId}
```

**Sincronizar toda a disponibilidade:**
```
PUT /api/caregivers/{id}/availability/sync
```

### 5.3 Boa Prática

Sempre confirme com o cuidador a disponibilidade semanalmente, especialmente após:
- Conclusão de um serviço
- Retorno de afastamento
- Mudança de situação pessoal

---

## 6. Alocação em Serviços

### 6.1 Buscar Cuidadores Disponíveis

**Busca avançada:**
```
POST /api/search
```

```json
{
  "status": "active",
  "city": "São Paulo",
  "care_types": ["idoso"],
  "min_experience": 2,
  "min_rating": 4.0,
  "availability": [1, 2, 3, 4, 5]
}
```

**Busca por disponibilidade e capacidade de horas:**
```
GET /api/metrics/available?required_hours=8&city=São Paulo&care_type=idoso&day_of_week=1
```

Este endpoint retorna apenas cuidadores que:
- Estão ativos
- Não estão afastados
- Têm disponibilidade no dia/horário
- Têm horas disponíveis na semana (não ultrapassaram 44h)

### 6.2 Criar Alocação

```
POST /api/caregivers/{id}/assignments
```

```json
{
  "service_id": 12345,
  "client_id": 67890,
  "started_at": "2026-01-25 08:00:00",
  "ended_at": "2026-01-25 18:00:00",
  "notes": "Cliente no 5º andar. Portaria abre às 7:30."
}
```

### 6.3 Verificações Automáticas

O sistema verifica automaticamente:
- Se o cuidador está **ativo**
- Se **não está afastado** na data
- Se tem **horas disponíveis** na semana

Se alguma verificação falhar, a alocação é rejeitada com mensagem explicativa.

### 6.4 Concluir Alocação

Após o serviço:
```
POST /api/caregivers/{id}/assignments/{assignmentId}/complete
```

```json
{
  "hours_worked": 9.5,
  "ended_at": "2026-01-25 17:30:00"
}
```

### 6.5 Cancelar Alocação

Se necessário cancelar:
```
POST /api/caregivers/{id}/assignments/{assignmentId}/cancel
```

```json
{
  "reason": "Cliente solicitou cancelamento"
}
```

### 6.6 Boa Prática - Controle de Carga

**Limite semanal: 44 horas**

Antes de alocar, verifique a carga atual:
```
GET /api/caregivers/{id}/workload
```

Resposta:
```json
{
  "caregiver_id": 123,
  "max_weekly_hours": 44,
  "current_week": {
    "hours_worked": 32,
    "hours_scheduled": 8,
    "available_hours": 4,
    "utilization_rate": 72.7
  }
}
```

> **Atenção:** Cuidadores com mais de 40 horas geram alerta de sobrecarga.

---

## 7. Registro de Avaliações

### 7.1 Quando Registrar

Após **cada serviço concluído**, solicite avaliação ao cliente.

### 7.2 Registrar Avaliação

```
POST /api/caregivers/{id}/ratings
```

```json
{
  "service_id": 12345,
  "score": 5,
  "comment": "Excelente profissional, muito atenciosa e pontual."
}
```

Escala de notas:
| Nota | Significado |
|------|-------------|
| 5 | Excelente |
| 4 | Bom |
| 3 | Regular |
| 2 | Ruim |
| 1 | Péssimo |

### 7.3 Consultar Resumo de Avaliações

```
GET /api/caregivers/{id}/ratings-summary
```

Resposta:
```json
{
  "total_ratings": 45,
  "average_rating": 4.7,
  "positive_rate": 91.1,
  "recent_trend": "stable",
  "distribution": {
    "1": 0,
    "2": 1,
    "3": 3,
    "4": 12,
    "5": 29
  }
}
```

### 7.4 Boa Prática

- Notas **abaixo de 3** geram alerta automático
- Cuidadores com média **abaixo de 2.5** devem ser revisados
- Acompanhe a **tendência** (improving/stable/declining)

---

## 8. Gestão de Ocorrências

### 8.1 Quando Registrar

Registre ocorrências sempre que houver:
- Atrasos
- Faltas
- Reclamações de clientes
- Problemas de comportamento
- Falhas de comunicação
- Qualquer intercorrência relevante

### 8.2 Tipos de Ocorrência

| Código | Descrição | Severidade Sugerida |
|--------|-----------|---------------------|
| `atraso` | Atraso no serviço | Leve |
| `comunicacao` | Falha de comunicação | Leve |
| `qualidade` | Problema de qualidade | Moderada |
| `reclamacao` | Reclamação do cliente | Moderada |
| `falta` | Falta sem aviso | Grave |
| `comportamento` | Comportamento inadequado | Grave |
| `outro` | Outro tipo | Moderada |

### 8.3 Níveis de Severidade

| Nível | Código | Peso | Ação Recomendada |
|-------|--------|------|------------------|
| Leve | `low` | 1 | Registro e orientação |
| Moderada | `medium` | 2 | Conversa formal e documentação |
| Grave | `high` | 3 | Advertência e plano de ação |
| Crítica | `critical` | 5 | Suspensão imediata e avaliação |

### 8.4 Registrar Ocorrência

```
POST /api/caregivers/{id}/incidents
```

```json
{
  "service_id": 12345,
  "incident_type": "atraso",
  "severity_id": 1,
  "notes": "Cuidadora chegou 30 minutos atrasada. Justificou problema no trânsito."
}
```

> Se `severity_id` não for informado, o sistema sugere automaticamente baseado no tipo.

### 8.5 Resolver Ocorrência

Após tratar a ocorrência:
```
POST /api/caregivers/{id}/incidents/{incidentId}/resolve
```

```json
{
  "resolved_by": "João Supervisor",
  "resolution_notes": "Conversado com a cuidadora. Orientada a sair mais cedo. Primeira ocorrência, sem advertência formal."
}
```

### 8.6 Consultar Ocorrências Recentes

```
GET /api/incidents/recent?days=30
```

### 8.7 Boa Prática

- **Sempre registre**, mesmo ocorrências leves
- Resolva ocorrências **graves em até 7 dias**
- Cuidadores com **3+ ocorrências em 90 dias** devem ser revisados
- Ocorrências críticas não resolvidas aparecem nos **alertas do sistema**

---

## 9. Gestão de Afastamentos

### 9.1 Tipos de Afastamento

| Código | Descrição | Requer Documento |
|--------|-----------|------------------|
| `medical` | Atestado Médico | Sim |
| `vacation` | Férias | Não |
| `personal` | Licença Pessoal | Não |
| `maternity` | Licença Maternidade | Sim |
| `other` | Outro | Não |

### 9.2 Registrar Afastamento

```
POST /api/caregivers/{id}/leaves
```

```json
{
  "leave_type_id": 1,
  "start_date": "2026-02-01",
  "end_date": "2026-02-03",
  "reason": "Atestado médico - gripe",
  "document_url": "https://documentos.../atestado_123.pdf"
}
```

### 9.3 Aprovar Afastamento

O afastamento precisa ser aprovado para entrar em vigor:
```
POST /api/caregivers/{id}/leaves/{leaveId}/approve
```

```json
{
  "approved_by": "Maria Supervisora"
}
```

### 9.4 Consultar Afastamentos

**Pendentes de aprovação:**
```
GET /api/leaves/pending
```

**Ativos no momento:**
```
GET /api/leaves/current
```

**De um cuidador específico:**
```
GET /api/caregivers/{id}/leaves?status=approved
```

### 9.5 Impacto na Operação

Quando um afastamento é aprovado:
- O cuidador **não aparece** nas buscas de disponibilidade
- Tentativas de alocação no período são **bloqueadas**
- O sistema considera automaticamente na gestão de disponibilidade

### 9.6 Boa Prática

- Registre afastamentos **antecipadamente** quando possível
- Exija documento comprobatório para atestados médicos
- Monitore afastamentos frequentes (podem indicar problemas)

---

## 10. Monitoramento e Indicadores

### 10.1 Dashboard Geral

```
GET /api/metrics/dashboard
```

Retorna visão completa:
- Visão geral do banco
- Métricas de ativação
- Métricas de ocupação
- Métricas de qualidade
- Alertas ativos

### 10.2 Indicadores Principais

#### Taxa de Ativação
```
GET /api/metrics/activation?days=30
```

| Indicador | Meta | Alerta |
|-----------|------|--------|
| Taxa de ativação | > 70% | < 50% |
| Tempo médio para ativação | < 7 dias | > 14 dias |

#### Taxa de Ocupação
```
GET /api/metrics/occupancy
```

| Indicador | Meta | Alerta |
|-----------|------|--------|
| Taxa de ocupação | 80% | < 60% ou > 95% |
| Cuidadores ociosos | 0 | > 10% do total |
| Cuidadores sobrecarregados | 0 | > 5% do total |

#### Qualidade
```
GET /api/metrics/quality?days=30
```

| Indicador | Meta | Alerta |
|-----------|------|--------|
| Nota média | > 4.0 | < 3.5 |
| Taxa de avaliações positivas | > 85% | < 75% |
| Ocorrências graves | 0 | > 3/mês |

### 10.3 Métricas por Segmento

**Por cidade:**
```
GET /api/metrics/by-city
```

**Por tipo de cuidado:**
```
GET /api/metrics/by-care-type
```

### 10.4 Boa Prática

- Consulte o dashboard **diariamente** no início do expediente
- Analise tendências **semanalmente**
- Faça revisão completa **mensalmente**

---

## 11. Alertas e Ações Corretivas

### 11.1 Consultar Alertas

```
GET /api/metrics/alerts
```

### 11.2 Tipos de Alerta

| Tipo | Severidade | Ação Recomendada |
|------|------------|------------------|
| `document_expiring` | Warning | Solicitar renovação ao cuidador |
| `document_expired` | Critical | Desativar até regularização |
| `workload_exceeded` | Warning | Redistribuir serviços |
| `incident_unresolved` | Critical | Resolver imediatamente |

### 11.3 Fluxo de Tratamento de Alertas

```
┌─────────────────┐
│ Consultar       │
│ Alertas         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Priorizar por   │
│ Severidade      │
│ (Critical > Warning)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Tomar Ação      │
│ Corretiva       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Documentar      │
│ Resolução       │
└─────────────────┘
```

### 11.4 Ações por Tipo de Alerta

#### Documento Vencendo
1. Notificar cuidador via WhatsApp
2. Agendar lembrete para 15 dias
3. Se não renovar, agendar lembrete para 7 dias
4. Se vencer, desativar cuidador

#### Documento Vencido
1. Desativar cuidador imediatamente
2. Notificar sobre necessidade de regularização
3. Reativar após envio e aprovação do novo documento

#### Carga de Trabalho Excedida
1. Verificar alocações da semana
2. Redistribuir serviços se possível
3. Orientar cuidador sobre limites
4. Registrar se foi escolha do cuidador (hora extra)

#### Ocorrência Grave Não Resolvida
1. Abrir imediatamente para análise
2. Contatar cuidador e cliente
3. Documentar o ocorrido
4. Aplicar medida disciplinar se necessário
5. Marcar como resolvida com notas completas

---

## Checklist Diário do Operador

### Início do Dia

- [ ] Consultar alertas críticos (`GET /api/metrics/alerts`)
- [ ] Verificar documentos vencendo nos próximos 7 dias
- [ ] Verificar afastamentos que terminam hoje
- [ ] Verificar alocações do dia

### Durante o Dia

- [ ] Registrar avaliações dos serviços concluídos
- [ ] Registrar ocorrências imediatamente quando ocorrerem
- [ ] Atualizar disponibilidade quando cuidador informar mudanças
- [ ] Aprovar/rejeitar documentos pendentes

### Fim do Dia

- [ ] Verificar se todas as alocações foram concluídas
- [ ] Verificar ocorrências graves não resolvidas
- [ ] Planejar alocações do dia seguinte

---

## Glossário

| Termo | Definição |
|-------|-----------|
| **Alocação** | Atribuição de um cuidador a um serviço específico |
| **Afastamento** | Período em que o cuidador está indisponível |
| **Ocorrência** | Registro de intercorrência durante operação |
| **Severidade** | Nível de gravidade de uma ocorrência |
| **Workload** | Carga de trabalho semanal do cuidador |
| **Triagem** | Processo de validação para ativação |
| **Elegibilidade** | Condição de estar apto para ativação |

---

## Suporte

Em caso de dúvidas ou problemas:
- Consulte a documentação técnica em `/docs/`
- Verifique os logs do sistema
- Contate a equipe de suporte técnico

---

*Documento atualizado em Janeiro/2026*
*Versão: 2.0 - Inclui controles operacionais avançados*
