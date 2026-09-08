# Ferramentas digitais

Objetivo: operação digital com dados nos **nove sistemas Laravel** deste repositório. Ferramentas de rascunho (WordPress, Notion, Airtable, Google Calendar como sistema de agenda) **não** fazem parte da arquitetura atual.

## Comunicação

- E-mail do domínio (`MAIL_*` em cada app que envia).
- WhatsApp Business via **Z-API**.
- Inbox, etiquetas e histórico: **carinho-atendimento**.

## Captação

- **carinho-site** (Laravel): páginas, formulários, CTA, SEO, GA4/GTM. Host público: `https://carinho.com.vc` (local `http://127.0.0.1:8084`).
- **carinho-marketing**: campanhas Meta/Google, UTM, calendário, biblioteca de marca.

## Base comercial

- **carinho-crm**: pipeline, cliente, tarefas, origem do lead.
- Não há CRM paralelo em planilha como sistema oficial.

## Agenda e campo

- **carinho-operacao**: agenda, match, check-in/out, substituição.
- **carinho-cuidadores**: banco de profissionais e disponibilidade.

## Financeiro e documentos

- **carinho-financeiro**: Stripe, faturas, repasses.
- **carinho-documentos-lgpd**: S3, contratos, LGPD.

## Automação

- **carinho-integracoes**: WhatsApp → CRM; lead → mensagem; cadastro → boas-vindas; feedback pós-serviço; sync CRM ↔ Operação ↔ Financeiro.

## Analytics

GA4 e GTM no Site; Measurement Protocol e Ads no Marketing; UTM ponta a ponta até o CRM.

Mapa de containers e portas: [README.DOCKER.md](README.DOCKER.md). Mapa lógico: [ARCHITECTURE.md](ARCHITECTURE.md).
