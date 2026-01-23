# Modulos essenciais - Carinho Atendimento

Este documento descreve os modulos essenciais implementados no sistema
carinho-atendimento com base na arquitetura, atividades e schema.

## 1) WhatsApp Business (Z-API)
- Recebimento de webhooks com validacao de assinatura.
- Envio de mensagens texto e midia via fila.
- Registro de eventos em webhook_events.
- Normalizacao de payloads de diferentes formatos.

## 2) Inbox unificada, historico e etiquetas
- Conversas centralizadas por contato e canal.
- Mensagens com direcao e status.
- Tags para classificacao rapida (conversation_tags).
- Endpoints internos para listar e detalhar conversas.
- Historico completo de acoes (audit trail) por conversa.
- Anotacoes internas para registro de informacoes.

## 3) Niveis de Suporte (N1, N2, N3)
- N1 (Atendimento): Primeiro contato, triagem basica, duvidas simples.
- N2 (Supervisao): Reclamacoes, casos complexos, excecoes operacionais.
- N3 (Gestao): Emergencias, crises, decisoes estrategicas.
- Escalonamento automatico por tempo de espera.
- Rebaixamento de nivel quando resolvido parcialmente.
- Notificacao automatica em escalonamentos N3.

## 4) Mensagens automaticas
- Regras de automacao (auto_rules) e templates (message_templates).
- Disparo de primeira resposta, fora do horario e feedback.
- Execucao assincrona via fila.
- Lembretes de acompanhamento (follow-up).
- Verificacao completa de horario comercial com feriados.

## 5) Triagem Padronizada
- Checklist de perguntas obrigatorias e opcionais.
- Script de atendimento formatado para atendentes.
- Dicas por pergunta para orientar a coleta de informacoes.
- Progresso de triagem com percentual de conclusao.
- Avanco automatico para proposta quando triagem completa.
- Resumo estruturado para sincronizacao com CRM.

## 6) Funil de atendimento padrao
- Transicoes controladas entre new -> triage -> proposal -> waiting -> active.
- Fechamento em lost ou closed com registro de encerramento.
- Registro obrigatorio de motivo de perda.
- Sincronizacao com CRM nos marcos de proposta e ativacao.
- Estatisticas de conversao e motivos de perda.

## 7) Controle de SLA
- Metas de resposta por prioridade (urgente, alta, normal, baixa).
- Monitoramento de tempo de primeira resposta.
- Alertas de SLA em risco (80% do tempo) e violado.
- Metricas de compliance agregadas por periodo.
- Dashboard de SLA por prioridade.

## 8) Canal de emergencia e suporte
- Registro de incidentes com categoria e severidade.
- Categorias: reclamacao, atraso, qualidade, comunicacao, cobranca, cuidador, emergencia, sugestao.
- Notificacao para operacao em severidade high/critical.
- Fluxo de resolucao com registro de quem resolveu.
- Estatisticas de incidentes por categoria e severidade.

## 9) Pesquisa de Satisfacao (NPS)
- Envio automatico apos encerramento de conversa.
- Escala de 1 a 5 com feedback opcional.
- Calculo automatico de NPS (Net Promoter Score).
- Identificacao de detratores para acompanhamento.
- Metricas de taxa de resposta e satisfacao media.

## 10) E-mail profissional para propostas e contratos
- Cliente de e-mail com templates HTML.
- Suporte para envio de proposta e contrato por fila.

## 11) Integracao com CRM e automacoes
- Sincronizacao de leads e incidentes via filas.
- Notificacao de leads perdidos com motivo.
- Payload enxuto com dados essenciais do atendimento.

## 12) Dashboard de Metricas
- Visao consolidada de SLA, funil, incidentes e satisfacao.
- Filtros por periodo (hoje, semana, mes).
- Metricas de escalonamento entre niveis.
- Status de horario comercial e feriados.

## Identidade visual
- Paleta e tipografia em config/branding.php.
- CSS base em public/css/brand.css.
- Templates de e-mail alinhados ao tom de voz.

## Limites e atribuicoes
- Atendimento registra conversas e SLA, nao substitui CRM.
- CRM guarda lead, contrato e historico comercial.
- Operacao recebe emergencias e faz o escalonamento.
