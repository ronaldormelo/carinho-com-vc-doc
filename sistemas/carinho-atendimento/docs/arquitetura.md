# Arquitetura

## Visao geral
Central de atendimento digital (atendimento.carinho.com.vc). Integra
WhatsApp Business, padroniza o fluxo de atendimento e registra o funil
de leads com SLA de resposta.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Inbox unificada com historico e etiquetas.
- Motor de mensagens automaticas e templates.
- Script de atendimento e checklist de triagem.
- Painel de SLA, tempo de resposta e motivos de perda.
- Registro de ocorrencias e emergencias.

## Integracoes
- WhatsApp Business API para envio/recebimento.
- CRM para criacao e atualizacao de leads.
- Operacao para repassar dados de alocacao.
- E-mail profissional para propostas e contratos.

## Dados e armazenamento
- Conversas, mensagens e anexos leves.
- Etiquetas, status e historico do funil.
- Registros de SLA e ocorrencias.

## Seguranca e LGPD
- Controle de acesso por atendente e supervisor.
- Mascaramento de dados sensiveis em logs.
- Politica de retencao de conversas.
- Assinatura e validacao de webhooks do WhatsApp.
- Rate limiting para envio de mensagens.

## Escalabilidade e desempenho
- Fila para envio de mensagens e processar webhooks.
- Cache de contatos e status frequentes.
- Escala horizontal do worker de mensagens.

## Observabilidade e operacao
- Logs estruturados por conversa e evento.
- Alertas para falhas de envio e aumento de backlog.
- Monitoramento de SLA e tempo medio de resposta.

## Backup e resiliencia
- Backup diario de banco.
- Retry e DLQ para mensagens com falha.
