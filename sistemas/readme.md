# Sistemas da plataforma

Nove aplicações Laravel no domínio `carinho.com.vc`. Visão de produto: [README da raiz](../README.md). Limites e integrações: [ARCHITECTURE.md](../ARCHITECTURE.md). Padrão desta pasta: [PADRAO-DOCUMENTACAO.md](PADRAO-DOCUMENTACAO.md).

Banco no Docker da raiz: **MariaDB 10.11** (driver `mysql` nos apps). Redis 7 compartilhado, prefixo por sistema.

| # | Sistema | Subdomínio | Porta | Objetivo | Documentação |
|---|---------|------------|-------|----------|--------------|
| 1 | Site | site.carinho.com.vc | 8084 | Presença e captura de leads | [readme](carinho-site/readme.md) |
| 2 | Marketing | marketing.carinho.com.vc | 8086 | Campanhas, UTM, marca | [readme](carinho-marketing/readme.md) |
| 3 | Atendimento | atendimento.carinho.com.vc | 8080 | WhatsApp, funil, SLA | [readme](carinho-atendimento/readme.md) |
| 4 | CRM | crm.carinho.com.vc | 8085 | Leads, clientes, pipeline | [readme](carinho-crm/readme.md) |
| 5 | Cuidadores | cuidadores.carinho.com.vc | 8081 | Recrutamento e banco | [readme](carinho-cuidadores/readme.md) |
| 6 | Operação | operacao.carinho.com.vc | 8083 | Agenda, match, campo | [readme](carinho-operacao/readme.md) |
| 7 | Financeiro | financeiro.carinho.com.vc | 8087 | Cobrança e repasse | [readme](carinho-financeiro/readme.md) |
| 8 | Documentos e LGPD | documentos.carinho.com.vc | 8082 | Contratos e titular | [readme](carinho-documentos-lgpd/readme.md) |
| 9 | Integrações | integracoes.carinho.com.vc | 8088 | Eventos, Z-API, sync | [readme](carinho-integracoes/readme.md) |

Velocidade e SLA: [PERFORMANCE.md](../PERFORMANCE.md). Segurança: [SECURITY.md](../SECURITY.md). Contratos HTTP verificados no código: [contratos-rotas.md](carinho-integracoes/docs/contratos-rotas.md).

Health (desenho, não unificado): Site `/health` + `/up`; demais em geral `/up` (Laravel) e `/api/health` ou `/health` no módulo. CRM: `/up` + `/health`. Integrações: `/health` público; status operacional com `X-API-Key`.

## Módulos essenciais (produto)

Resumo do que cada sistema **deve** cobrir. O detalhe implementado está no `readme.md` e em `docs/modulos.md` de cada pasta.

1. **Site** — páginas institucionais e por público; formulários; políticas; SEO local; CTA WhatsApp; UTM; analytics.
2. **Marketing** — redes; calendário; anúncios Meta/Google; origem do lead; biblioteca de marca.
3. **Atendimento** — WhatsApp; inbox; automações; funil; emergência; e-mail de proposta; CRM.
4. **CRM** — cadastro único; pipeline; tipo de serviço; histórico; aceite; tarefas.
5. **Cuidadores** — cadastro; documentos; classificação; contrato; banco pesquisável; ocorrências.
6. **Operação** — agenda; match; check-in/out; notificações; substituição.
7. **Financeiro** — receber/pagar; precificação; comissão; Stripe; conciliação.
8. **Documentos** — S3; contratos; consentimento; assinatura; auditoria.
9. **Integrações** — WhatsApp→CRM; mensagens automáticas; boas-vindas; feedback; sync.
