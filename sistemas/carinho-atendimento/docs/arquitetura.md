# Arquitetura

## Visao geral
Central de atendimento digital (atendimento.carinho.com.vc). Integra
WhatsApp Business, padroniza o fluxo de atendimento e registra o funil
de leads com SLA de resposta. Implementa niveis de suporte estruturados,
triagem padronizada e pesquisa de satisfacao.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Inbox unificada com historico e etiquetas.
- Motor de mensagens automaticas e templates.
- Script de atendimento e checklist de triagem estruturado.
- Painel de SLA com metas por prioridade e alertas.
- Registro de motivos de perda para analise comercial.
- Niveis de suporte (N1, N2, N3) com escalonamento.
- Registro de ocorrencias e emergencias categorizado.
- Pesquisa de satisfacao e calculo de NPS.
- Dashboard de metricas operacionais.

## Servicos implementados
- InboxService: Processamento de mensagens entrada/saida.
- FunnelService: Gestao do funil e transicoes de status.
- SlaService: Monitoramento de SLA e alertas.
- EscalationService: Escalonamento entre niveis de suporte.
- TriageService: Checklist de qualificacao de leads.
- IncidentService: Registro e resolucao de incidentes.
- SatisfactionService: Pesquisa NPS e metricas.
- WorkingHoursService: Horario comercial e feriados.
- ConversationHistoryService: Audit trail de acoes.

## Integracoes
- WhatsApp Business API (Z-API) para envio/recebimento.
- CRM para criacao e atualizacao de leads.
- Operacao para repassar emergencias e escalonamentos.
- E-mail profissional para propostas e contratos.

## Dados e armazenamento
- Conversas, mensagens e anexos leves.
- Etiquetas, status e historico do funil.
- Registros de SLA, metas e violacoes.
- Historico completo de acoes por conversa.
- Respostas de triagem estruturadas.
- Pesquisas de satisfacao e feedbacks.
- Calendario de feriados.

## Seguranca e LGPD
- Controle de acesso por atendente e supervisor.
- Mascaramento de dados sensiveis em logs.
- Politica de retencao de conversas.
- Assinatura e validacao de webhooks do WhatsApp.
- Rate limiting para envio de mensagens.
- Audit trail de todas as acoes no sistema.

## Escalabilidade e desempenho
- Fila para envio de mensagens e processar webhooks.
- Cache de tabelas de dominio (12h TTL).
- Cache de feriados (24h TTL).
- Escala horizontal do worker de mensagens.

## Observabilidade e operacao
- Logs estruturados por conversa e evento.
- Alertas para falhas de envio e aumento de backlog.
- Alertas de SLA em risco e violado.
- Dashboard de metricas em tempo real.
- Monitoramento de NPS e satisfacao.

## Backup e resiliencia
- Backup diario de banco.
- Retry e DLQ para mensagens com falha.
