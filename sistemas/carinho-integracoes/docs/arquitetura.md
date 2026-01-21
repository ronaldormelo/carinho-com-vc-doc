# Arquitetura

## Visao geral
Camada de integracao e automacao
(integracoes.carinho.com.vc). Conecta sistemas internos e externos com
eventos, filas e regras de orquestracao.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Registro de eventos e webhooks.
- Mapeamento de payloads e validacoes.
- Filas de processamento assinc.
- Mecanismo de retry e DLQ.
- Painel de monitoramento de integracoes.

## Integracoes
- Site -> CRM (lead).
- Atendimento/WhatsApp -> CRM (status e historico).
- CRM -> Operacao (agenda e alocacao).
- Operacao -> Financeiro (execucao e cobranca).
- Marketing -> CRM (origem e campanha).

## Dados e armazenamento
- Logs de eventos e status de entrega.
- Mapas de transformacao e versoes de payload.
- Registro de erros e tentativas.

## Seguranca e LGPD
- Assinatura e validacao de webhooks.
- Rotacao de chaves e segredos.
- Criptografia de payloads sensiveis.
- Rate limiting e controle de acesso a APIs.

## Escalabilidade e desempenho
- Processamento assinc. com backpressure.
- Idempotencia para evitar duplicidade.
- Particionamento por fila e prioridade.

## Observabilidade e operacao
- Logs estruturados por evento.
- Alertas para falhas e backlog crescente.
- Dashboards por integracao.

## Backup e resiliencia
- Persistencia de eventos para reprocessamento.
- Testes periodicos de replay.
