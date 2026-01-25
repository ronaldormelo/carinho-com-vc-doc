# Guia do Usuário Operacional
## Carinho Operação - Passo a Passo dos Fluxos Principais

---

## Sumário

1. [Início do Dia - Rotina Matinal](#1-início-do-dia---rotina-matinal)
2. [Processar Nova Solicitação de Serviço](#2-processar-nova-solicitação-de-serviço)
3. [Acompanhar Agendamentos do Dia](#3-acompanhar-agendamentos-do-dia)
4. [Monitorar Check-ins e Check-outs](#4-monitorar-check-ins-e-check-outs)
5. [Processar Substituição de Cuidador](#5-processar-substituição-de-cuidador)
6. [Tratar Emergência](#6-tratar-emergência)
7. [Aprovar Exceção Operacional](#7-aprovar-exceção-operacional)
8. [Consultar Relatórios](#8-consultar-relatórios)
9. [Verificar Alertas de SLA](#9-verificar-alertas-de-sla)
10. [Encerramento do Dia](#10-encerramento-do-dia)

---

## 1. Início do Dia - Rotina Matinal

### Objetivo
Verificar a situação geral da operação e preparar-se para o dia.

### Passo a Passo

**Passo 1: Verificar Agendamentos de Hoje**
```
GET /api/schedules/today
```

✅ **O que verificar:**
- Total de agendamentos planejados
- Horários de início e fim de cada atendimento
- Cuidadores alocados

**Passo 2: Verificar Alertas de SLA Pendentes**
```
GET /api/sla/alerts
```

✅ **Ação necessária:**
- Se houver alertas críticos, investigar imediatamente
- Alertas de warning devem ser monitorados ao longo do dia

**Passo 3: Verificar Exceções Pendentes de Aprovação**
```
GET /api/exceptions/pending
```

✅ **Ação necessária:**
- Encaminhar para supervisor se houver exceções aguardando

**Passo 4: Verificar Emergências Não Resolvidas**
```
GET /api/emergencies/pending
```

✅ **Ação necessária:**
- Priorizar resolução de emergências pendentes do dia anterior

**Passo 5: Consultar Relatório do Dia Anterior**
```
GET /api/reports/daily?date=YYYY-MM-DD
```

✅ **O que verificar:**
- Taxa de conclusão de agendamentos
- Ocorrências e problemas
- Lições para o dia atual

---

## 2. Processar Nova Solicitação de Serviço

### Objetivo
Receber uma nova demanda e garantir que um cuidador seja alocado.

### Passo a Passo

**Passo 1: Receber Solicitação**

O sistema recebe automaticamente via webhook ou você pode criar manualmente:
```
POST /api/service-requests
{
    "client_id": 12345,
    "service_type_id": 1,        // 1=Horista, 2=Diário, 3=Mensal
    "urgency_id": 2,             // 1=Hoje, 2=Semana, 3=Sem data
    "start_date": "2026-01-25",
    "end_date": "2026-02-25"
}
```

✅ **Verificar antes de prosseguir:**
- Dados do cliente estão completos?
- Tipo de serviço está correto?
- Datas são válidas?

**Passo 2: Buscar Candidatos para Alocação**
```
GET /api/assignments/service-request/{id}/candidates
```

✅ **O que o sistema retorna:**
- Lista de cuidadores ordenados por score
- Score mínimo para auto-match: 70 pontos
- Detalhes de cada candidato (habilidades, região, avaliação)

**Passo 3A: Alocação Automática (Score ≥ 70)**
```
POST /api/service-requests/{id}/process
```

O sistema automaticamente:
- Seleciona o melhor candidato
- Cria a alocação
- Notifica o cuidador
- Aguarda confirmação

**Passo 3B: Alocação Manual (Score < 70 ou preferência)**
```
POST /api/assignments/service-request/{id}/assign
{
    "caregiver_id": 789
}
```

✅ **Quando usar alocação manual:**
- Score abaixo de 70 para todos os candidatos
- Cliente solicitou cuidador específico
- Situação de urgência

**Passo 4: Aguardar Confirmação do Cuidador**

O cuidador tem até **4 horas** para confirmar.

✅ **Monitorar:**
- Se não confirmar, buscar próximo candidato
- Verificar se cuidador visualizou a notificação

**Passo 5: Criar Agendamentos**

Após confirmação:
```
POST /api/schedules
{
    "assignment_id": 456,
    "shifts": [
        {
            "date": "2026-01-25",
            "start_time": "08:00",
            "end_time": "16:00"
        },
        {
            "date": "2026-01-26",
            "start_time": "08:00",
            "end_time": "16:00"
        }
    ]
}
```

✅ **Validações automáticas:**
- Antecedência mínima: 24 horas
- Duração mínima: 4 horas
- Duração máxima: 12 horas
- Intervalo entre atendimentos: 60 minutos

**Passo 6: Verificar Notificação ao Cliente**
```
GET /api/notifications/client/{clientId}/history
```

✅ **Confirmar:**
- Cliente foi notificado sobre a alocação
- Notificação foi enviada com sucesso

### ✅ Fluxo Concluído com Sucesso
- Solicitação criada
- Cuidador alocado e confirmado
- Agendamentos criados
- Cliente notificado

---

## 3. Acompanhar Agendamentos do Dia

### Objetivo
Monitorar os atendimentos planejados para garantir execução conforme esperado.

### Passo a Passo

**Passo 1: Listar Agendamentos de Hoje**
```
GET /api/schedules/today
```

**Passo 2: Verificar Status de Cada Agendamento**

| Status | Significado | Ação |
|--------|-------------|------|
| `planned` | Aguardando início | Monitorar horário |
| `in_progress` | Cuidador fez check-in | Acompanhar execução |
| `done` | Cuidador fez check-out | Verificar registro |
| `missed` | Não executado | Investigar motivo |

**Passo 3: Monitorar Atrasos**
```
GET /api/checkin/delays
```

✅ **Se houver atrasos:**
- Verificar se cuidador está a caminho
- Contatar cuidador se atraso > 15 minutos
- Iniciar substituição se atraso > 2 horas

**Passo 4: Verificar Próximos Agendamentos**
```
GET /api/schedules/upcoming?limit=10
```

✅ **Preparação antecipada:**
- Confirmar que cuidadores estão disponíveis
- Verificar se lembretes foram enviados

---

## 4. Monitorar Check-ins e Check-outs

### Objetivo
Garantir que os atendimentos iniciem e finalizem corretamente.

### Fluxo de Check-in (Cuidador)

**Passo 1: Cuidador Realiza Check-in**
```
POST /api/checkin/schedule/{scheduleId}/in
{
    "location": "-23.5505,-46.6333"
}
```

✅ **Validações automáticas:**
- Tolerância antecipada: 30 minutos antes
- Tolerância de atraso: 15 minutos depois
- Distância máxima: 500 metros do endereço

**Passo 2: Sistema Atualiza Status**
- Agendamento muda para `in_progress`
- Cliente é notificado automaticamente

**Passo 3: Cuidador Preenche Checklist de Início**
```
PATCH /api/checklists/{checklistId}/batch
{
    "items": [
        {"entry_id": 1, "completed": true, "notes": null},
        {"entry_id": 2, "completed": true, "notes": "Cliente bem disposto"},
        {"entry_id": 3, "completed": true, "notes": null},
        {"entry_id": 4, "completed": true, "notes": "Dieta sem sal"},
        {"entry_id": 5, "completed": true, "notes": null}
    ]
}
```

✅ **Itens obrigatórios do checklist de início:**
1. Confirmar chegada ao local
2. Verificar condição do cliente
3. Conferir medicações
4. Anotar necessidades especiais
5. Verificar segurança do ambiente

### Fluxo de Check-out (Cuidador)

**Passo 1: Cuidador Registra Atividades**
```
POST /api/checkin/schedule/{scheduleId}/activities
{
    "activities": [
        "Auxiliou no banho",
        "Administrou medicação das 10h",
        "Preparou almoço",
        "Acompanhou caminhada leve"
    ],
    "notes": "Cliente passou bem o dia, sem intercorrências."
}
```

**Passo 2: Cuidador Preenche Checklist de Fim**
```
PATCH /api/checklists/{checklistId}/batch
{
    "items": [
        {"entry_id": 10, "completed": true, "notes": null},
        {"entry_id": 11, "completed": true, "notes": "Medicação das 10h e 14h"},
        {"entry_id": 12, "completed": true, "notes": "Nenhuma ocorrência"},
        {"entry_id": 13, "completed": true, "notes": null},
        {"entry_id": 14, "completed": true, "notes": "Próximo cuidador às 16h"}
    ]
}
```

✅ **Itens obrigatórios do checklist de fim:**
1. Atividades planejadas concluídas
2. Medicações administradas
3. Relatar ocorrências
4. Cliente em condição estável
5. Notas de passagem de plantão

**Passo 3: Cuidador Realiza Check-out**
```
POST /api/checkin/schedule/{scheduleId}/out
{
    "location": "-23.5505,-46.6333",
    "activities": ["Atividades finalizadas conforme planejado"]
}
```

**Passo 4: Sistema Finaliza**
- Agendamento muda para `done`
- Cliente é notificado automaticamente
- Horas são sincronizadas com Financeiro

### ✅ Fluxo Concluído com Sucesso
- Check-in realizado no horário
- Checklists preenchidos
- Atividades registradas
- Check-out realizado
- Cliente notificado

---

## 5. Processar Substituição de Cuidador

### Objetivo
Trocar o cuidador alocado quando necessário, minimizando impacto ao cliente.

### Quando Substituir
- Cuidador solicitou afastamento
- Atraso superior a 2 horas
- Emergência pessoal do cuidador
- Solicitação do cliente

### Passo a Passo

**Passo 1: Identificar a Alocação**
```
GET /api/assignments/{id}
```

**Passo 2: Buscar Cuidadores Backup da Região**
```
GET /api/backup-caregivers/available?region_code=SP-ZONA-SUL&service_type_id=1
```

✅ **Sistema retorna:**
- Cuidadores disponíveis ordenados por prioridade
- Indicação de disponibilidade imediata

**Passo 3A: Usar Cuidador do Banco de Backup**

Se houver backup disponível:
```
POST /api/assignments/{id}/substitute
{
    "reason": "Cuidador titular solicitou afastamento",
    "new_caregiver_id": 999
}
```

**Passo 3B: Buscar com Expansão de Região**

Se não houver backup na região:
```
GET /api/backup-caregivers/find-best?region_code=SP-ZONA-SUL&service_type_id=1&nearby_regions[]=SP-CENTRO&nearby_regions[]=SP-ZONA-OESTE
```

**Passo 4: Sistema Processa Automaticamente**

O sistema:
- Marca alocação anterior como `replaced`
- Cria nova alocação
- Transfere agendamentos futuros
- Notifica o cliente
- Registra na trilha de auditoria

**Passo 5: Verificar Notificação ao Cliente**
```
GET /api/notifications/client/{clientId}/history
```

✅ **Confirmar:**
- Cliente recebeu notificação sobre substituição
- Nome do novo cuidador foi informado

### ✅ Fluxo Concluído com Sucesso
- Motivo da substituição registrado
- Novo cuidador alocado
- Agendamentos transferidos
- Cliente notificado
- Auditoria registrada

---

## 6. Tratar Emergência

### Objetivo
Registrar e resolver situações de emergência com rapidez e segurança.

### Classificação de Severidade

| Severidade | Tempo Resposta | Exemplos |
|------------|----------------|----------|
| **Low** | 60 min | Dúvida sobre procedimento |
| **Medium** | 30 min | Atraso significativo, mudança de comportamento |
| **High** | 15 min | Queda sem ferimentos, erro de medicação |
| **Critical** | 5 min | Emergência médica, risco à segurança |

### Passo a Passo

**Passo 1: Registrar Emergência**
```
POST /api/emergencies
{
    "service_request_id": 123,
    "severity_id": 3,          // 1=Low, 2=Medium, 3=High, 4=Critical
    "description": "Cliente apresentou tontura e mal-estar após almoço. Sinais vitais estáveis."
}
```

**Passo 2: Sistema Notifica Automaticamente**
- Cliente é notificado
- Se High/Critical: Supervisor é alertado
- Se Critical: Email para emergencia@carinho.com.vc

**Passo 3: Acompanhar Emergência**
```
GET /api/emergencies/{id}
```

✅ **Monitorar:**
- Tempo desde a criação
- Ações tomadas
- Escalonamento automático se necessário

**Passo 4: Registrar Ações Tomadas**

Documente cada ação no sistema durante o atendimento.

**Passo 5: Resolver Emergência**
```
POST /api/emergencies/{id}/resolve
{
    "resolution": "Cliente avaliado por médico, sem necessidade de internação. Orientado repouso."
}
```

**Passo 6: Escalonar se Necessário**

Se a emergência não for resolvida no tempo limite:
```
POST /api/emergencies/{id}/escalate
```

Sistema aumenta a severidade automaticamente:
- Low → Medium
- Medium → High
- High → Critical

### ✅ Fluxo Concluído com Sucesso
- Emergência registrada com severidade correta
- Responsáveis notificados
- Ações documentadas
- Resolução registrada
- Tempo de resposta dentro do SLA

---

## 7. Aprovar Exceção Operacional

### Objetivo
Autorizar situações que fogem do padrão operacional (apenas Supervisores).

### Tipos de Exceção

| Tipo | Descrição | Aprovador |
|------|-----------|-----------|
| `late_checkin` | Check-in atrasado com justificativa | Supervisor |
| `early_checkout` | Saída antes do previsto | Supervisor |
| `schedule_change` | Alteração de agendamento | Supervisor |
| `manual_assignment` | Alocação manual fora do match | Supervisor |
| `fee_waiver` | Isenção de taxa de cancelamento | Coordenador |
| `policy_override` | Outras exceções de política | Coordenador |

### Passo a Passo

**Passo 1: Listar Exceções Pendentes**
```
GET /api/exceptions/pending
```

**Passo 2: Analisar Detalhes da Exceção**

Verificar:
- Tipo de exceção
- Entidade afetada (agendamento, alocação, etc.)
- Descrição/justificativa
- Quem solicitou

**Passo 3A: Aprovar Exceção**
```
POST /api/exceptions/{exceptionId}/approve
{
    "approved_by": 5,          // ID do supervisor
    "notes": "Justificativa válida. Atraso devido a trânsito intenso na região."
}
```

**Passo 3B: Rejeitar Exceção**
```
POST /api/exceptions/{exceptionId}/reject
{
    "rejected_by": 5,          // ID do supervisor
    "notes": "Justificativa insuficiente. Orientar cuidador sobre importância da pontualidade."
}
```

**Passo 4: Sistema Registra na Auditoria**

Toda aprovação/rejeição é registrada automaticamente com:
- Quem aprovou/rejeitou
- Data e hora
- Justificativa

### ✅ Fluxo Concluído com Sucesso
- Exceção analisada
- Decisão registrada com justificativa
- Solicitante notificado
- Auditoria registrada

---

## 8. Consultar Relatórios

### Objetivo
Acompanhar indicadores e performance da operação.

### Relatório Diário

**Quando consultar:** Todo início de dia, para verificar dia anterior.

```
GET /api/reports/daily?date=2026-01-24
```

**O que verificar:**
- Total de agendamentos realizados
- Taxa de conclusão (meta: > 95%)
- Substituições ocorridas
- Emergências tratadas
- Sucesso de notificações

### Relatório Semanal

**Quando consultar:** Toda segunda-feira.

```
GET /api/reports/weekly?week_start=2026-01-20
```

**O que verificar:**
- Tendência da semana (melhorando/piorando/estável)
- Taxa de substituição acumulada
- Performance comparada à semana anterior

### Relatório Mensal

**Quando consultar:** Primeiro dia útil do mês.

```
GET /api/reports/monthly?month=2026-01
```

**O que verificar:**
- Performance geral do mês
- Top 10 cuidadores (por horas trabalhadas)
- Análise de emergências
- Recomendações de melhoria

### Relatório de Exceções

**Quando consultar:** Semanalmente ou quando necessário.

```
GET /api/reports/exceptions?start_date=2026-01-01&end_date=2026-01-31
```

**O que verificar:**
- Volume de exceções por tipo
- Taxa de aprovação
- Padrões recorrentes

---

## 9. Verificar Alertas de SLA

### Objetivo
Identificar e resolver violações de indicadores de performance.

### Passo a Passo

**Passo 1: Verificar Dashboard de SLA**
```
GET /api/sla/dashboard?start_date=2026-01-20&end_date=2026-01-26
```

**O que verificar:**
- Compliance geral (meta: > 90%)
- Métricas fora do SLA
- Tendência (melhorando/piorando/estável)

**Passo 2: Verificar Alertas Pendentes**
```
GET /api/sla/alerts
```

**Passo 3: Priorizar por Severidade**

| Severidade | Ação |
|------------|------|
| `critical` | Ação imediata - escalar para supervisor |
| `warning` | Investigar causa e planejar correção |
| `info` | Monitorar tendência |

**Passo 4: Verificar SLA em Tempo Real**
```
GET /api/sla/realtime
```

**O que verificar:**
- Atrasos de check-in em andamento
- Emergências pendentes acima do tempo

**Passo 5: Confirmar Alerta (Após Tratar)**
```
POST /api/sla/alerts/{alertId}/acknowledge
{
    "user_id": 10
}
```

### Indicadores Monitorados

| Indicador | Meta | Cálculo |
|-----------|------|---------|
| Pontualidade Check-in | ≥ 95% | Check-ins no horário / Total |
| Taxa de Substituição | ≤ 10% | Substituições / Total alocações |
| Taxa de Cancelamento | ≤ 10% | Cancelamentos / Total agendamentos |
| Tempo Resposta Emergência | ≤ 30 min | Média de tempo até resolução |
| Sucesso de Notificações | ≥ 98% | Enviadas com sucesso / Total |

---

## 10. Encerramento do Dia

### Objetivo
Garantir que todos os atendimentos foram finalizados e preparar para o próximo dia.

### Passo a Passo

**Passo 1: Verificar Agendamentos Pendentes**
```
GET /api/schedules/today
```

✅ **Verificar:**
- Todos os agendamentos estão com status `done`?
- Há algum `in_progress` que deveria ter finalizado?

**Passo 2: Resolver Pendências**

Se houver agendamentos não finalizados:
- Contatar cuidador
- Registrar ocorrência se necessário
- Verificar se cliente foi atendido

**Passo 3: Verificar Emergências Pendentes**
```
GET /api/emergencies/pending
```

✅ **Ação:**
- Emergências críticas devem ser resolvidas antes do fim do turno
- Outras podem ser passadas para plantão com briefing

**Passo 4: Verificar Alertas de SLA**
```
GET /api/sla/alerts
```

✅ **Ação:**
- Confirmar alertas já tratados
- Documentar alertas pendentes para próximo turno

**Passo 5: Verificar Agendamentos de Amanhã**
```
GET /api/schedules?start_date=YYYY-MM-DD
```

✅ **Preparação:**
- Verificar se todos têm cuidador alocado
- Verificar se lembretes serão enviados

**Passo 6: Registrar Passagem de Turno**

Documentar para próximo operador:
- Pendências a resolver
- Situações em andamento
- Alertas importantes

---

## Dicas de Boas Práticas

### ✅ Faça Sempre

1. **Verifique alertas de SLA** no início e fim do dia
2. **Documente todas as ações** para rastreabilidade
3. **Notifique o cliente** sobre qualquer mudança
4. **Registre emergências** imediatamente, mesmo se parecer pequena
5. **Consulte o banco de backup** antes de buscar substitutos via match

### ❌ Evite

1. **Ignorar alertas** - mesmo os de baixa severidade indicam tendências
2. **Pular checklists** - são obrigatórios por motivos de segurança
3. **Alocação manual sem justificativa** - sempre documente o motivo
4. **Deixar emergências pendentes** - resolva ou escalone sempre

### 💡 Lembre-se

- O sistema registra **tudo** na trilha de auditoria
- Exceções requerem **aprovação de supervisor**
- Substituições devem ser **comunicadas ao cliente**
- Métricas de SLA são calculadas **automaticamente**

---

## Contatos Úteis

| Situação | Contato |
|----------|---------|
| Dúvidas operacionais | operacao@carinho.com.vc |
| Emergências | emergencia@carinho.com.vc |
| Supervisão | supervisor@carinho.com.vc |
| Suporte técnico | suporte@carinho.com.vc |

---

**Versão:** 1.0  
**Última atualização:** Janeiro 2026  
**Mantido por:** Equipe de Operações
