# Arquitetura

## Visao geral
Sistema publico de presenca digital e captacao de leads
(site.carinho.com.vc). Prioriza informacao clara, conversao rapida para
WhatsApp e registro de origem para o CRM.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis
- Storage de midias: objeto (S3 compativel)
- CDN para ativos estaticos

## Componentes principais
- Front-end publico com paginas institucionais e por publico.
- CMS leve para conteudo, banners e FAQ.
- Formularios de lead (clientes e cuidadores) com validacao.
- Modulo de UTM e tracking de origem.
- Integracao de CTA para WhatsApp.

## Integracoes
- CRM: criacao/atualizacao de leads via API/webhook.
- Atendimento: link direto para WhatsApp.
- Marketing/Analytics: GA, Tag Manager e GMB.

## Dados e armazenamento
- Leads com dados minimos e consentimento LGPD.
- Conteudos, paginas e midias.
- Registro de origem e campanha (UTM).

## Seguranca e LGPD
- HTTPS com HSTS e TLS atualizado.
- Rate limiting e captcha nos formularios.
- Sanitizacao de inputs e protecao CSRF.
- Minimizacao de PII e politica de retencao.
- Logs de acesso e alteracoes de conteudo.

## Escalabilidade e desempenho
- Cache de paginas e fragmentos no Redis.
- CDN e compressao de imagens.
- Aplicacao stateless para escala horizontal.
- Indexes em tabelas de lead e campanha.

## Observabilidade e operacao
- Logs estruturados e alertas de erro.
- Monitoramento de tempo de resposta e conversao.
- Uptime monitoring e health checks.

## Backup e resiliencia
- Backup diario de banco e storage.
- Deploy com rollback e testes de integridade.
