# Modulos essenciais - Carinho Atendimento

Este documento descreve os modulos essenciais implementados no sistema
carinho-atendimento com base na arquitetura, atividades e schema.

## 1) WhatsApp Business (Z-API)
- Recebimento de webhooks com validacao de assinatura.
- Envio de mensagens texto e midia via fila.
- Registro de eventos em webhook_events.

## 2) Inbox unificada, historico e etiquetas
- Conversas centralizadas por contato e canal.
- Mensagens com direcao e status.
- Tags para classificacao rapida (conversation_tags).
- Endpoints internos para listar e detalhar conversas.

## 3) Mensagens automaticas
- Regras de automacao (auto_rules) e templates (message_templates).
- Disparo de primeira resposta, fora do horario e feedback.
- Execucao assincrona via fila.

## 4) Funil de atendimento padrao
- Transicoes controladas entre new -> triage -> proposal -> waiting -> active.
- Fechamento em lost ou closed com registro de encerramento.
- Sincronizacao com CRM nos marcos de proposta e ativacao.

## 5) Canal de emergencia e suporte
- Registro de incidentes em incidents.
- Notificacao para operacao em severidade high/critical.
- Registro de incidente no CRM quando necessario.

## 6) E-mail profissional para propostas e contratos
- Cliente de e-mail com templates HTML.
- Suporte para envio de proposta e contrato por fila.

## 7) Integracao com CRM e automacoes
- Sincronizacao de leads e incidentes via filas.
- Payload enxuto com dados essenciais do atendimento.

## Identidade visual
- Paleta e tipografia em config/branding.php.
- CSS base em public/css/brand.css.
- Templates de e-mail alinhados ao tom de voz.

## Limites e atribuicoes
- Atendimento registra conversas e SLA, nao substitui CRM.
- CRM guarda lead, contrato e historico comercial.
- Operacao recebe emergencias e faz o escalonamento.
