# Requisitos Nao Funcionais

## Performance

### Cache

O sistema utiliza Redis para cache de dados frequentemente acessados:

| Dado | TTL | Chave |
|------|-----|-------|
| Agenda do cuidador | 5 min | `schedule:caregiver:{id}:{start}:{end}` |
| Agenda do cliente | 5 min | `schedule:client:{id}:{start}:{end}` |
| Candidatos disponiveis | 1 min | `available_caregivers:{request_id}` |

### Invalidacao
- Cache invalidado automaticamente em alteracoes
- Pattern-based invalidation para limpeza em lote

### Filas

Jobs assincronos para operacoes nao-criticas:

| Fila | Jobs | Proposito |
|------|------|-----------|
| notifications | SendNotification | Envio de notificacoes |
| whatsapp | SendWhatsAppNotification | Mensagens WhatsApp |
| emergencies | ProcessEmergencyAlert, CheckEmergencyEscalation | Gestao de emergencias |
| financeiro | SyncWithFinanceiro | Sincronizacao financeira |
| monitoring | CheckScheduleDelays, SendScheduleReminders | Monitoramento |

### Indices de Banco

Indices criados para queries frequentes:
- `schedules(caregiver_id, shift_date)`
- `schedules(client_id, shift_date)`
- `schedules(shift_date, status_id)`
- `service_requests(status_id, start_date)`
- `emergencies(severity_id, resolved_at)`
- `notifications(notif_type, status_id)`

## Escalabilidade

### Horizontal
- Stateless design permite multiplas instancias
- Filas distribuidas via Redis
- Cache compartilhado

### Vertical
- Configuracoes de timeout ajustaveis
- Batch processing para operacoes em massa

### Limites
- Paginacao padrao: 20 itens
- Max candidatos por match: 10
- Max notificacoes pendentes por query: 100

## Resiliencia

### Retry Policy
- Jobs: 3 tentativas com backoff exponencial
- Integracoes: timeout configuravel (5-15s)
- Fallback para canal de notificacao alternativo

### Circuit Breaker (Recomendado)
- Abrir apos 5 falhas consecutivas
- Half-open apos 30 segundos
- Fechar apos 3 sucessos

### Graceful Degradation
- Notificacoes: fallback email se WhatsApp falhar
- Match: manual se automatico falhar
- Cache: bypass se Redis indisponivel

## Observabilidade

### Logging

Niveis utilizados:
- `INFO`: Operacoes bem-sucedidas
- `WARNING`: Falhas recuperaveis, atrasos
- `ERROR`: Falhas de integracao, excecoes

Contexto padrao:
```php
Log::info('Operacao realizada', [
    'service_request_id' => $id,
    'action' => 'create',
    'user_id' => $userId,
]);
```

### Metricas (Recomendado)

| Metrica | Tipo | Descricao |
|---------|------|-----------|
| schedule_created | counter | Agendamentos criados |
| checkin_performed | counter | Check-ins realizados |
| match_score | histogram | Distribuicao de scores |
| notification_sent | counter | Notificacoes enviadas |
| emergency_created | counter | Emergencias registradas |
| api_response_time | histogram | Tempo de resposta |

### Health Checks

`GET /api/health` - Basico (sempre rapido)
```json
{
  "status": "healthy",
  "service": "carinho-operacao",
  "timestamp": "2026-01-22T10:00:00Z"
}
```

`GET /api/status` - Detalhado (verifica dependencias)
```json
{
  "status": "healthy",
  "checks": {
    "database": {"healthy": true},
    "cache": {"healthy": true}
  }
}
```

### Alertas Sugeridos

| Condicao | Severidade | Acao |
|----------|------------|------|
| Health check falhou | Critica | PagerDuty |
| Taxa de erro > 5% | Alta | Slack |
| Atraso > 30 min | Media | Email |
| Fila > 1000 jobs | Media | Slack |

## Seguranca

### Autenticacao
- Token interno para comunicacao entre sistemas
- Validacao de assinatura em webhooks
- Bearer token via header Authorization

### Autorizacao
- Middleware `internal.token` para rotas protegidas
- Webhooks publicos com validacao de assinatura

### Dados Sensiveis
- Telefones normalizados (sem formatacao)
- Localizacao armazenada como string
- Logs sem dados pessoais completos

### LGPD
- Dados operacionais retidos conforme politica
- Auditoria de alteracoes de agenda
- Possibilidade de anonimizacao

## Backup e Recuperacao

### Banco de Dados
- Backup diario automatico
- Retencao: 30 dias
- Point-in-time recovery disponivel

### Logs
- Retencao: 90 dias
- Arquivamento em storage frio apos 30 dias

### Disaster Recovery
- RTO (Recovery Time Objective): 4 horas
- RPO (Recovery Point Objective): 1 hora

## Integracao Continua

### Requisitos de Deploy
- Todos os testes passando
- Lint sem erros
- Migrations aplicadas
- Seeders executados (se necessario)

### Feature Flags (Recomendado)
- Habilitar/desabilitar notificacoes WhatsApp
- Modo de match (auto/manual)
- Validacao de localizacao

## SLA

### Disponibilidade
- Target: 99.5%
- Manutencao programada: fora do horario comercial

### Tempo de Resposta
- API: < 500ms (p95)
- Notificacoes: < 5 minutos
- Alocacao: < 4 horas

### Metricas de Negocio
- Taxa de substituicao: < 10%
- Taxa de cancelamento: < 10%
- Avaliacao minima de cuidador: 3.5 estrelas
