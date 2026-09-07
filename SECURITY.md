# Segurança do negócio

Este documento cobre o que protege a operação, os dados das famílias e dos cuidadores, e o dinheiro. Não descreve exploits. Procedimentos de incidente operacional estão no [RUNBOOK.md](RUNBOOK.md).

## O que está em jogo

| Ativo | Onde vive | Impacto se vazar ou corromper |
|-------|-----------|-------------------------------|
| Conversas WhatsApp | Atendimento, Integrações, Z-API | Privacidade da família, LGPD |
| Cadastro de cliente e paciente | CRM, Operação | Dados de saúde e endereço |
| Documentos de identidade | Documentos (S3), Cuidadores (metadados) | Fraude, LGPD |
| Dados bancários do cuidador | Financeiro (campos sensíveis) | Desvio de repasse |
| Consentimentos e contratos | Documentos, CRM | Contencioso, auditoria |
| Tokens internos e chaves de API | `.env` de cada sistema | Impersonação de um módulo inteiro |
| Chaves Stripe, Meta, Google, AWS | `.env` Financeiro, Marketing, Documentos | Gasto de mídia, pagamento, arquivos |

Não há modelo multi-tenant de clientes finais: a plataforma é **uma empresa, nove sistemas**. Isolamento é entre **módulos**, não entre famílias no mesmo banco. Autorização humana (atendente vs supervisor vs financeiro) está no CRM (Sanctum + papéis) e nos papéis de Operação/Atendimento.

## Ameaças relevantes (já reconhecidas no código e nos docs)

1. **Token interno fraco ou compartilhado demais** — `changeme-*` em `.env.example` não pode ir para produção. Cada sistema deve ter segredo próprio, ≥ 32 caracteres aleatórios.
2. **Webhook sem assinatura** — Z-API e Stripe validam HMAC/secret. Recusar payload sem assinatura válida.
3. **MariaDB local com root vazio** — o Compose da raiz usa `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1` para desenvolvimento. Isso é inaceitável em produção. O script `mysql/init/init.sql` também cria usuário de desenvolvimento; não reutilize essa senha fora da máquina local.
4. **PII em log** — Atendimento e Operação pedem mascaramento. Jobs e clientes HTTP não devem logar body completo de lead, documento ou cartão.
5. **Formulário público do site** — rate limit (5 req/min no README do Site), reCAPTCHA v3, CSRF.
6. **Upload de documento** — tamanho máximo 10 MB no Cuidadores; arquivo vai ao Documentos/S3, não fica como objeto público.
7. **Link de assinatura e URL pré-assinada S3** — expiração obrigatória; não indexar em busca.
8. **APP_DEBUG=true** — vaza stack e ambiente. Produção: `APP_DEBUG=false`, `APP_ENV=production`.
9. **Portas do Docker publicadas na workstation** — 3306, 6379 e 8080–8088 não devem estar abertas na internet.

## Autenticação e autorização

| Caminho | Mecanismo documentado |
|---------|------------------------|
| API entre sistemas | `INTERNAL_API_TOKEN` / `X-API-Key` / `*_API_KEY` no `.env` |
| Painel CRM | Laravel Sanctum; papéis Spatie Permission |
| Webhooks Z-API | `ZAPI_WEBHOOK_SECRET` |
| Webhooks Stripe | `STRIPE_WEBHOOK_SECRET` |
| Webhooks Meta | `META_WEBHOOK_VERIFY_TOKEN` |
| Formulários do site | Sessão CSRF + reCAPTCHA |
| Assinatura de contrato | OTP WhatsApp, clique ou certificado (Documentos) |

Rotas internas não são públicas. Health checks podem ser públicos; não devem devolver segredos. No hub, `/status`, dashboard e reset de circuit breaker exigem `X-API-Key`.

## LGPD (Lei 13.709/2018)

Base legal, consentimento, exportação e exclusão são responsabilidade do **carinho-documentos-lgpd**, com prazo de **15 dias** para solicitações do titular (README do módulo). O CRM registra consentimento no cadastro. O Site publica política de privacidade e termos.

Regras práticas:

- Coletar o mínimo (dados do atendimento em [03](03%20-%20Estrutura%20de%20Atendimento%20Digital.md)).
- Não usar conversa de WhatsApp para marketing sem base/consentimento específico.
- Pedidos de exclusão passam pelo módulo Documentos (`ProcessDataDeletion`); não apagar só em um schema.
- Retenção de conversas e agenda segue a política do Documentos, não o disco do desenvolvedor.

## Segredos

- Nunca commitar `.env`, chaves Stripe (`sk_live_`, `sk_test_`), JSON de service account Google, nem dumps com PII.
- Rotacionar tokens internos quando um módulo for descomissionado ou um contratado sair.
- Lista nominada do que cada sistema exige: [CATALOGO-SECRETS.md](CATALOGO-SECRETS.md).

## Produção (mínimo)

- TLS em todos os subdomínios; HSTS no site.
- Backup diário dos schemas `carinho_*` (RPO 1 h citado nos NFRs de Operação/Cuidadores).
- Horizon/workers supervisionados; alerta se fila ou DLQ crescer (Integrações).
- Stripe, Z-API e AWS só com chaves de produção no ambiente de produção.
- Usuário de banco com privilégio só nos schemas necessários; root apenas para bootstrap.

## Relato de incidente

1. Isolar o token ou a chave (rotacionar).
2. Ver DLQ e logs do hub ([runbook de Integrações](sistemas/carinho-integracoes/docs/runbook-operacional.md)).
3. Se houver PII exposta, registrar no Documentos e seguir o procedimento de auditoria.
4. Comunicar operação (WhatsApp oficial pode estar comprometido — não usar o mesmo canal se a instância Z-API foi o vetor).
