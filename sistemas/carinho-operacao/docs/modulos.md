# Modulos do Sistema

## Visao Geral

O sistema Carinho Operacao e composto por modulos que cobrem todo o ciclo operacional de atendimento, desde a solicitacao ate a conclusao do servico.

## 1. Agenda e Agendamentos

### Responsabilidades
- Criar e gerenciar agendamentos
- Validar disponibilidade de cuidadores
- Aplicar regras de duracao e intervalos
- Manter cache de agenda para performance

### Componentes
- `ScheduleService` - Logica de negocios
- `ScheduleController` - API REST
- `Schedule` - Model Eloquent

### Regras de Negocio
- Antecedencia minima de 24 horas
- Duracao entre 4 e 12 horas
- Intervalo minimo de 60 minutos entre atendimentos
- Validacao de conflito de horarios

### Cache
- Agenda do cuidador: 5 minutos
- Agenda do cliente: 5 minutos
- Invalidacao automatica em alteracoes

## 2. Match Cliente x Cuidador

### Responsabilidades
- Buscar candidatos disponiveis
- Calcular score de compatibilidade
- Realizar match automatico ou manual
- Verificar historico de atendimentos

### Componentes
- `MatchService` - Motor de match
- `AssignmentController` - API de alocacao

### Algoritmo de Score

O score total e calculado com pesos configurados:

| Criterio | Peso | Descricao |
|----------|------|-----------|
| Habilidades | 35% | Match de skills requeridas |
| Disponibilidade | 25% | Agenda disponivel no periodo |
| Regiao | 20% | Proximidade e area de atuacao |
| Avaliacao | 20% | Rating medio do cuidador |

### Match Automatico
- Score minimo: 70 pontos
- Seleciona automaticamente o melhor candidato
- Fallback para selecao manual se nao atingir score

## 3. Check-in/Check-out

### Responsabilidades
- Registrar inicio e fim do atendimento
- Validar localizacao do cuidador
- Gerenciar checklists de inicio e fim
- Registrar atividades realizadas

### Componentes
- `CheckinService` - Logica de check
- `CheckinController` - API REST
- `Checkin` - Model de registro

### Validacoes
- Check-in nao pode ser muito antecipado (30 min)
- Atraso tolerado: 15 minutos
- Localizacao obrigatoria (configuravel)
- Distancia maxima: 500 metros

### Checklists Padrao

**Inicio:**
- Confirmar chegada ao local
- Verificar condicao do cliente
- Conferir medicacoes
- Anotar necessidades especiais
- Verificar seguranca do ambiente

**Fim:**
- Atividades planejadas concluidas
- Medicacoes administradas
- Relatar ocorrencias
- Cliente em condicao estavel
- Notas de passagem de plantao

## 4. Registro de Servico

### Responsabilidades
- Manter logs de atividades
- Armazenar observacoes do cuidador
- Gerar historico do atendimento

### Componentes
- `ServiceLog` - Model de registro
- Integrado ao `CheckinService`

### Estrutura do Log
```json
{
  "activities_json": [
    {
      "activity": "Administracao de medicamento",
      "logged_at": "2026-01-22T10:30:00Z"
    }
  ],
  "notes": "Observacoes gerais do atendimento"
}
```

## 5. Notificacoes

### Responsabilidades
- Notificar eventos do servico
- Enviar lembretes de agendamento
- Alertar sobre emergencias

### Componentes
- `NotificationService` - Orquestracao
- `SendNotification` - Job assincrono
- `SendWhatsAppNotification` - Job WhatsApp
- `ZApiClient` - Cliente Z-API

### Tipos de Notificacao

| Tipo | Descricao | Canal |
|------|-----------|-------|
| service_start | Inicio do atendimento | WhatsApp, Email |
| service_end | Fim do atendimento | WhatsApp, Email |
| caregiver_assigned | Cuidador alocado | WhatsApp, Email |
| caregiver_replaced | Substituicao | WhatsApp, Email |
| schedule_reminder | Lembrete | WhatsApp, Email |
| emergency_alert | Emergencia | WhatsApp, Email, Push |

### Lembretes Automaticos
- 24 horas antes (diario as 08:00)
- 2 horas antes (a cada hora)

## 6. Substituicao

### Responsabilidades
- Processar substituicao de cuidador
- Buscar substituto disponivel
- Transferir agendamentos futuros
- Notificar cliente sobre alteracao

### Componentes
- `SubstitutionService` - Logica de substituicao
- `Substitution` - Model de registro

### Fluxo de Substituicao
1. Identifica necessidade (ausencia, solicitacao, etc)
2. Busca candidatos disponiveis
3. Seleciona melhor substituto
4. Marca alocacao original como substituida
5. Cria nova alocacao
6. Transfere agendamentos futuros
7. Notifica cliente

### Motivos de Substituicao
- `illness` - Doenca do cuidador
- `emergency` - Emergencia pessoal
- `no_show` - Nao comparecimento
- `client_request` - Solicitacao do cliente
- `schedule_conflict` - Conflito de agenda
- `performance` - Questoes de desempenho

## 7. Emergencias

### Responsabilidades
- Registrar emergencias
- Classificar por severidade
- Escalonar automaticamente
- Alertar responsaveis

### Componentes
- `EmergencyService` - Gestao de emergencias
- `ProcessEmergencyAlert` - Job de alerta
- `CheckEmergencyEscalation` - Job de escalonamento

### Niveis de Severidade

| Nivel | Codigo | Tempo de Resposta |
|-------|--------|-------------------|
| Baixa | low | 60 minutos |
| Media | medium | 30 minutos |
| Alta | high | 15 minutos |
| Critica | critical | 5 minutos |

### Escalonamento Automatico
- Verificacao a cada 10 minutos
- Se nao resolvida apos tempo + 10 min, escalona
- Emergencias criticas enviam alerta imediato

## 8. Politicas de Cancelamento

### Responsabilidades
- Calcular taxa de cancelamento
- Aplicar politica conforme antecedencia
- Integrar com Financeiro

### Regras

| Antecedencia | Tipo | Taxa |
|--------------|------|------|
| 48+ horas | Gratuito | 0% |
| 24-48 horas | Reduzido | 30% |
| < 24 horas | Integral | 50% |

### Limite Mensal
- Maximo de 3 cancelamentos por cliente por mes
- Apos limite, taxa integral aplicada

## Dependencias entre Modulos

```
ServiceRequest
    └── Assignment
         └── Schedule
              ├── Checkin
              ├── ServiceLog
              └── Notification

ServiceRequest
    ├── Checklist
    │    └── ChecklistEntry
    └── Emergency

Assignment
    └── Substitution
```
