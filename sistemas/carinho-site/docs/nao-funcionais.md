# Requisitos não funcionais — Site

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).

## Usabilidade

- Canal de conversão: WhatsApp (botão flutuante + mensagem pré-preenchida). Formulários só com dados mínimos e consentimento.
- Páginas legais devem refletir as [políticas financeiras](../../carinho-financeiro/docs/politicas.md) (pagamento adiantado, reembolso 24 h / 6 h).
- Guia da família: [guia-usuario-site.md](guia-usuario-site.md).
- Identidade: [00](../../../00%20-%20Identidade%20da%20Marca.md) e `config/branding.php`.

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Páginas institucionais | Cache Redis; app stateless |
| Formulário de lead | Rate limit 5 req/min; reCAPTCHA v3 |
| API interna | Rate limit 60 req/min |
| Sync CRM | Job a cada 5 min (`sync-leads-to-crm`); não bloquear o POST público |
| Timeout Z-API | connect 3 s, request 10 s |
| Timeout CRM / hub | 10 s (`config/integrations.php`) |
| Assets | CDN e lazy-load documentados no README |

Gargalos: CRM indisponível (lead fica pendente de sync); reCAPTCHA ou Z-API lentos não devem impedir persistir o envio local.

## Integração

Site não é o CRM. Envia lead + UTM; CTA aponta para o número oficial. Destinos: CRM, Atendimento (WhatsApp), Marketing (campanha), Integrações (evento). Detalhe: [integracoes.md](integracoes.md).

## Segurança e LGPD

- HTTPS/HSTS no README do módulo; CSRF; sanitização.
- PII mínima no `form_submissions`; política de privacidade no próprio site.
- Webhooks e API interna com token (`INTERNAL_API_TOKEN` / `X-API-Key`).
- Sem `APP_DEBUG` em produção.

## Disponibilidade

Health: `GET /health` e `GET /health/detailed` (web, públicos). Laravel: `GET /up`. `GET /api/health` público. Backup diário de banco citado na arquitetura do módulo.
