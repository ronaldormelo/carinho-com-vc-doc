# Arquitetura

## Visao geral
Sistema de gestao de marketing e captacao (marketing.carinho.com.vc).
Organiza calendario editorial, campanhas e landing pages, garantindo
registro de origem do lead e performance previsivel.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Painel de calendario editorial e pauta.
- Gestor de campanhas e criativos por canal.
- Modulo de landing pages e UTM builder.
- Coletor de eventos de conversao.
- Biblioteca de marca (logos, paleta, templates).

## Integracoes
- Meta Ads e Google Ads (sincronizacao de campanhas e eventos).
- CRM para envio de leads e origem.
- Analytics/Tag Manager para mensuracao.

## Dados e armazenamento
- Campanhas, criativos e publicacoes.
- Eventos de conversao e origem do lead.
- Indicadores de desempenho por canal.

## Seguranca e LGPD
- Controle de acesso por perfil (marketing x admin).
- Secrets de APIs armazenados com cofres de segredo.
- Logs de alteracao de campanhas e criativos.
- Retencao de dados alinhada a LGPD.

## Escalabilidade e desempenho
- Processamento assinc. para sincronizacao de metricas.
- Cache de relatorios e dashboards.
- Indexes por periodo, canal e campanha.

## Observabilidade e operacao
- Logs estruturados, metricas de falha de integracao.
- Alertas para queda de conversao e erros de API.

## Backup e resiliencia
- Backup diario de banco.
- Fila com retry e DLQ para falhas de integracao.
