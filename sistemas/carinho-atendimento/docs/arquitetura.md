# Arquitetura

## Visão Geral
Central de atendimento digital (atendimento.carinho.com.vc). Integra
WhatsApp Business, padroniza o fluxo de atendimento com níveis de suporte (N1, N2, N3),
gestão de SLA, triagem estruturada e auditoria completa.

## Stack
- Linguagem: PHP 8.2+
- Framework: Laravel 10+
- Banco de dados: MySQL 8.0+
- Cache e filas: Redis

## Componentes Principais

### Gestão de Conversas
- Inbox unificada com histórico completo
- Sistema de etiquetas e tags
- Funil de atendimento padronizado
- Atribuição automática de agentes

### Níveis de Suporte
- N1: Atendimento inicial, triagem, informações básicas
- N2: Propostas, negociação, questões técnicas
- N3: Casos complexos, reclamações críticas, emergências
- Escalonamento manual e automático entre níveis

### Gestão de SLA
- Configuração por prioridade e nível de suporte
- Alertas automáticos quando SLA está em risco (threshold configurável)
- Dashboard de conversas em risco
- Métricas de cumprimento de SLA

### Triagem Estruturada
- Checklist padronizado de qualificação
- Campos obrigatórios e opcionais configuráveis
- Cálculo automático de prioridade/urgência
- Resumo para elaboração de propostas

### Scripts de Comunicação
- Biblioteca organizada por categoria
- Scripts por nível de suporte
- Variáveis dinâmicas para personalização
- Sugestão automática baseada no contexto

### Auditoria e Histórico
- Registro de todas as ações em conversas
- Histórico de mudanças de status e prioridade
- Rastreamento de atribuições e escalonamentos
- Notas internas entre agentes

### Registro de Perdas
- Motivos padronizados e categorizados
- Notas obrigatórias quando aplicável
- Relatórios de análise de perdas

## Serviços

| Serviço | Responsabilidade |
|---------|------------------|
| InboxService | Processamento de mensagens inbound/outbound |
| FunnelService | Gestão do funil e transições de status |
| SlaService | Controle de SLA, métricas e alertas |
| TriageService | Gestão do checklist de triagem |
| ScriptService | Biblioteca de scripts de comunicação |
| EscalationService | Escalonamento entre níveis de suporte |
| AuditService | Registro de ações e auditoria |
| NoteService | Notas internas de conversas |
| IncidentService | Registro de incidentes e emergências |

## Integrações

### Externas
- WhatsApp Business API (Z-API) para envio/recebimento
- E-mail (SMTP) para propostas e contratos

### Internas (outros módulos)
- CRM para criação e atualização de leads
- Operação para dados de alocação e emergências
- Integrações para eventos e webhooks

## Dados e Armazenamento
- Conversas, mensagens e anexos
- Etiquetas, status e histórico do funil
- Configurações de SLA e alertas
- Checklist e respostas de triagem
- Scripts de comunicação
- Histórico de ações e escalonamentos
- Notas internas

## Segurança e LGPD
- Controle de acesso por atendente, supervisor e admin
- Mascaramento de dados sensíveis em logs
- Política de retenção de conversas
- Assinatura e validação de webhooks do WhatsApp
- Rate limiting para envio de mensagens
- Auditoria completa de ações

## Escalabilidade e Desempenho
- Fila para envio de mensagens e processamento de webhooks
- Cache de configurações de SLA e domínios (12h TTL)
- Cache de contatos e status frequentes
- Escala horizontal do worker de mensagens
- Índices otimizados para consultas frequentes

## Observabilidade e Operação
- Logs estruturados por conversa e evento
- Alertas para falhas de envio e aumento de backlog
- Monitoramento de SLA e tempo médio de resposta
- Dashboard de conversas em risco
- Alertas de violação de SLA

## Backup e Resiliência
- Backup diário de banco
- Retry com backoff exponencial para mensagens
- Dead Letter Queue (DLQ) para falhas persistentes
- Webhook idempotente com registro de eventos
