# Arquitetura da plataforma

Visão dos nove sistemas Laravel e de como se comunicam. Detalhes de endpoint, fila e contingência estão na [matriz de integrações](sistemas/carinho-integracoes/docs/matriz-integracoes.md) e nos `docs/arquitetura.md` de cada módulo.

## Fontes de verdade

Quando dois documentos divergirem, use esta ordem:

| Assunto | Fonte canônica | Não usar como contrato |
|---------|----------------|------------------------|
| Identidade visual (cores, fontes, tom) | [00 - Identidade da Marca.md](00%20-%20Identidade%20da%20Marca.md) e `config/branding.php` | Paletas antigas em rascunhos |
| Política de pagamento, reembolso, comissão e repasse | [sistemas/carinho-financeiro/docs/politicas.md](sistemas/carinho-financeiro/docs/politicas.md) | Defaults isolados de `config/operacao.php` se diferentes |
| SLA de primeira resposta no WhatsApp | Seed `sla_targets` + [guia do atendente](sistemas/carinho-atendimento/docs/guia-rapido-atendente.md) | Texto genérico “5 minutos” sem prioridade |
| Contratos, consentimento, retenção e direitos do titular | Módulo Documentos/LGPD | Cópias resumidas em outros READMEs |
| Eventos entre sistemas, retry e DLQ | [carinho-integracoes](sistemas/carinho-integracoes/readme.md) | Integrações ponta a ponta reimplementadas em um único módulo |
| Segredos e variáveis | [CATALOGO-SECRETS.md](CATALOGO-SECRETS.md) + `.env.example` de cada sistema | Valores de exemplo em README |

A Operação usa as **mesmas janelas de horas** da tabela comercial (24 h / 6 h) em `CANCEL_FREE_HOURS` / `CANCEL_REDUCED_HOURS`. Taxas operacionais (`CANCEL_*_FEE_PERCENT`) **não** são o reembolso publicado: o cliente vê a tabela do [Financeiro](sistemas/carinho-financeiro/docs/politicas.md) e do site.

## Contexto

Famílias contratam cuidado domiciliar (horista, diário, mensal). O WhatsApp é o canal único de contato. Os sistemas não compartilham um único banco de negócio: cada um tem schema próprio (`carinho_*`) no MariaDB compartilhado e troca dados por **API REST autenticada** e **eventos no hub**.

```
Família / cuidador
        │
        ▼
   carinho-site  ──leads/UTM──►  carinho-crm
        │                              │
        └──WhatsApp CTA──► carinho-atendimento ◄──Z-API── WhatsApp
                                   │
                                   ▼
                         carinho-integracoes (hub)
                     ┌─────────────┼─────────────┐
                     ▼             ▼             ▼
              carinho-operacao  financeiro   cuidadores
                     │             │             │
                     └──────► documentos-lgpd ◄──┘
                     marketing ──UTM/conversão──► CRM / Site
```

## Stack compartilhada

| Camada | Tecnologia verificada no repositório |
|--------|--------------------------------------|
| Aplicação | PHP 8.2+, Laravel 11 |
| HTTP | Nginx/Apache por container de app (porta 80 interna) |
| Banco | MariaDB 10.11 no Compose da raiz; apps usam driver `mysql` |
| Cache e fila | Redis 7; prefixo por sistema (`REDIS_PREFIX`) |
| Workers | Laravel Queue / Horizon (Marketing, Operação, Integrações, Financeiro) |
| Autenticação interna | Token (`INTERNAL_API_TOKEN`, `X-API-Key` ou equivalente) |
| WhatsApp | Z-API (`https://developer.z-api.io/`) |
| Pagamento | Stripe (Financeiro) |
| Arquivos | AWS S3 (Documentos; CRM opcional) |
| Rede Docker | `carinho-network` |

Não há WordPress, Notion, Airtable nem Google Calendar como sistemas oficiais. O que existia em rascunhos antigos foi substituído pelos módulos acima.

## Hostnames públicos

O **Site** é o único módulo no **apex** `https://carinho.com.vc`. Os outros oito sistemas permanecem em subdomínio (`crm.carinho.com.vc`, `atendimento.carinho.com.vc`, `marketing.carinho.com.vc`, `cuidadores.carinho.com.vc`, `operacao.carinho.com.vc`, `financeiro.carinho.com.vc`, `documentos.carinho.com.vc`, `integracoes.carinho.com.vc`).

Docker local do Site: `http://127.0.0.1:8084` (porta, não hostname de produção). `APP_URL` de produção: `https://carinho.com.vc`.

**Redirect 301:** `site.carinho.com.vc` → `https://carinho.com.vc` (path e query preservados). Implementado no módulo Site (`apache-config.conf`, `public/.htaccess`, middleware `RedirectLegacySiteHost`). Este repositório **não** define o DNS, o TLS nem o reverse proxy da nuvem; o operador deve apontar o apex ao app do Site e manter o hostname legado só para o 301.

Índice de hosts e portas: [README.md](README.md#sistemas) e [sistemas/readme.md](sistemas/readme.md).

## Responsabilidades (limites)

| Sistema | É dono de | Não é dono de |
|---------|-----------|----------------|
| Site | Páginas públicas, formulários, UTM na sessão, CTA | Pipeline comercial, cobrança |
| Marketing | Campanhas, calendário, conversões de mídia | Inbox de WhatsApp |
| Atendimento | Conversa, SLA, triagem, incidente | Cadastro mestre do cliente |
| CRM | Lead, cliente, deal, tarefa, origem | Execução do plantão |
| Cuidadores | Profissional, documentos de RH, avaliação | Agenda do dia |
| Operação | Solicitação, match, agenda, check-in, emergência em campo | Precificação e NF |
| Financeiro | Fatura, pagamento, repasse, DRE | Match de cuidador |
| Documentos | Arquivo, assinatura, consentimento, pedido LGPD | Conteúdo editorial do site |
| Integrações | Orquestração, mapeamento, retry, DLQ | Regra de negócio de um domínio |

O CRM pode editar depoimentos/FAQ do site via API (`docs/modulo-gestao-conteudo.md` no CRM): isso é CMS operacional, não transfere a titularidade dos dados de lead.

## Comunicação

- **Síncrona:** REST entre módulos, timeout típico **8–15 s** (Z-API e Meta/Google Ads até **30 s**).
- **Assíncrona:** jobs Redis; webhooks gravam e enfileiram (não processam regra pesada no request).
- **Consistência:** eventual. Falha vai para retry e, no hub, para DLQ após o máximo de tentativas.
- **Idempotência:** o hub usa `idempotency_key` em eventos.

Contratos de URL, frequência de sync e contingência: [matriz-integracoes.md](sistemas/carinho-integracoes/docs/matriz-integracoes.md).

## Dados e isolamento

- Um schema por sistema. Não há consulta cruzada SQL entre módulos.
- PII de cliente e cuidador aparece em CRM, Cuidadores, Atendimento, Operação e Documentos. Mascarar em logs (ver [SECURITY.md](SECURITY.md)).
- Documentos binários (RG, contratos) ficam no S3 do módulo Documentos, não no disco do Cuidadores.

## Disponibilidade de referência

Módulos Operação e Cuidadores documentam **99,5%** de uptime, RPO **1 h**. RTO: Cuidadores **1 h**, Operação **4 h**. A matriz do hub lista **99,9%** como expectativa de chamada a CRM/Operação/Financeiro — é meta de integração, não medição publicada.

Health checks: a maioria expõe `GET /health` ou `GET /api/health` (e variante `detailed`), além do `GET /up` do Laravel. Tabela por módulo: [contratos-rotas.md](sistemas/carinho-integracoes/docs/contratos-rotas.md).

## Decisões já tomadas (ADR curto)

1. **Nove apps em vez de monólito** — limites de domínio e deploy independente; custo: sync e tokens.
2. **WhatsApp via Z-API no hub e nos módulos de conversa** — canal único; risco: instância desconectada (runbook).
3. **MariaDB compartilhado, Redis compartilhado com prefixo** — simples no Docker; risco: um Redis saturado afeta todos.
4. **Stripe no Financeiro** — PIX, boleto, cartão e Connect para repasse.
5. **Pagamento adiantado** — reduz inadimplência; cancela serviço se não houver pagamento no prazo.
