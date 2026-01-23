# Atividades

Lista de atividades implementadas no sistema Carinho Integracoes.

## Mapeamento de Eventos

- [x] Mapear eventos do site, atendimento, CRM, operacao e financeiro
- [x] Definir payloads e campos obrigatorios
- [x] Criar tabela `event_mappings` para transformacoes
- [x] Implementar versionamento de mapeamentos

### Tipos de Eventos Suportados

| Evento | Origem | Destinos |
|--------|--------|----------|
| `lead.created` | Site, WhatsApp | CRM, Marketing |
| `lead.updated` | CRM | CRM |
| `client.registered` | CRM | CRM, Financeiro, Marketing |
| `client.updated` | CRM | CRM, Operacao |
| `service.scheduled` | CRM | CRM, Operacao, Cuidadores, Financeiro |
| `service.started` | Operacao | CRM, Operacao |
| `service.completed` | Operacao | CRM, Financeiro, Cuidadores |
| `service.cancelled` | Operacao | CRM, Operacao, Financeiro |
| `payment.received` | Financeiro | CRM, Operacao |
| `payment.failed` | Financeiro | CRM |
| `invoice.created` | Financeiro | CRM |
| `payout.processed` | Financeiro | Cuidadores |
| `whatsapp.inbound` | Z-API | CRM, Atendimento |
| `feedback.received` | Operacao, WhatsApp | CRM, Cuidadores |

## Integracoes Essenciais

### Captura de Lead do Site para o CRM

- [x] Webhook `POST /webhooks/site/lead`
- [x] Job `ProcessLeadCreated`
- [x] Validacao de dados de entrada
- [x] Deduplicacao por telefone/email
- [x] Tracking de UTM

### WhatsApp -> CRM (Criacao e Atualizacao de Lead)

- [x] Integracao com Z-API
- [x] Webhook `POST /webhooks/whatsapp`
- [x] Normalizacao de payload Z-API
- [x] Validacao de assinatura HMAC
- [x] Processamento de respostas de botoes

### CRM -> Operacao (Agenda e Alocacao)

- [x] Sincronizacao `syncCrmToOperacao()`
- [x] Mapeamento de contratos para agendamentos
- [x] Job agendado horario

### Operacao -> Financeiro (Dados de Cobranca e Repasse)

- [x] Sincronizacao `syncOperacaoToFinanceiro()`
- [x] Criacao automatica de faturas
- [x] Calculo de valores com adicionais
- [x] Job agendado diario

## Automacoes

### Lead -> Mensagem Automatica no WhatsApp

- [x] Envio automatico ao criar lead
- [x] Template configuravel em `config/branding.php`
- [x] Job `SendWhatsAppMessage`
- [x] Retry em caso de falha

### Cadastro -> Email de Boas-Vindas

- [x] Envio ao registrar cliente
- [x] Template Blade responsivo
- [x] Job `SendEmail`
- [x] WhatsApp de boas-vindas tambem

### Feedback Automatico Pos-Servico

- [x] Notificacao de servico finalizado
- [x] Solicitacao de feedback com delay de 2h
- [x] WhatsApp com botoes de avaliacao
- [x] Email com link de feedback
- [x] Processamento de resposta de botao

## Observabilidade

### Logs de Integracao e Fila de Erros

- [x] Logging estruturado por evento
- [x] Registro de todas as entregas de webhook
- [x] Tabela `webhook_deliveries` com status
- [x] Middleware `LogRequests`

### Alertas para Falhas Criticas

- [x] Health check basico `/health`
- [x] Health check detalhado `/health/detailed`
- [x] Status completo `/status`
- [x] Metricas de eventos, retry e DLQ

### Dead Letter Queue

- [x] Tabela `dead_letter` para eventos que falharam
- [x] API para listar, reprocessar e arquivar
- [x] Estatisticas por tipo de evento

## Retry e Resiliencia

- [x] Retry com backoff exponencial
- [x] Tabela `retry_queue`
- [x] Job `ProcessRetryQueue` a cada 5 minutos
- [x] Limite configuravel de tentativas
- [x] Move para DLQ apos max tentativas

## Workers e Filas

- [x] Configuracao Horizon para producao
- [x] Configuracao Supervisor alternativa
- [x] Filas por prioridade
- [x] Auto-scaling de workers

## Seguranca

- [x] Autenticacao por API Key
- [x] Validacao de assinatura de webhooks
- [x] Rate limiting por cliente
- [x] Middleware de logging

## Proximos Passos

- [ ] Dashboard visual de monitoramento
- [ ] Alertas via Slack/Discord
- [ ] Metricas Prometheus
- [ ] Testes de carga
- [ ] Documentacao OpenAPI/Swagger
