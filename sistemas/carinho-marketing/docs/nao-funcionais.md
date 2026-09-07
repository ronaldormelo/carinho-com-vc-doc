# Requisitos não funcionais — Marketing

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).

## Usabilidade

- Operador de mídia: calendário, aprovação de conteúdo, UTM e dashboard de campanha.
- Família não usa este painel; o que ela vê são posts, ads e landings publicadas no Site.
- Biblioteca de marca deve seguir [00](../../../00%20-%20Identidade%20da%20Marca.md) (`#5BBFAD`, Inter/Nunito).
- Guias: [guia-operacional.md](guia-operacional.md), [guia-rapido-referencia.md](guia-rapido-referencia.md).
- Health: `GET /up` e `GET /api/health`. API sem prefixo `/v1`.

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Conversão / UTM | API síncrona curta; evento pesado em fila |
| `SyncCampaignMetrics` | Timeout de job 300 s |
| `PublishScheduledContent` | Timeout de job 120 s |
| Meta / Google Ads | HTTP até 30 s (`config/integrations.php`) |
| Hub / Site / CRM | Timeout 8 s nos clients internos |
| Workers | Laravel Horizon |

Gargalos: cota e latência das APIs Meta/Google; token expirado quebra publicação e CPL. Não bloquear o CRM se a mídia falhar — conversão deve ter retry (`SendConversionEvent`).

## Integração

Origem do lead e CAPI/GA são deste módulo; inbox WhatsApp não é. Destinos: CRM, Site, Integrações, Atendimento (notificação), Z-API, Meta, Google. [integracoes.md](integracoes.md).

## Segurança

- Token interno nas APIs; secrets só em `.env` (`META_*`, `GOOGLE_ADS_*`, `GA_*`).
- Webhooks Meta com `META_WEBHOOK_VERIFY_TOKEN`; Z-API com secret.
- Rate limiting por endpoint (README).
- Contas de anúncio são da empresa: acesso restrito ao time de marketing.

Análise de mercado em `analise-praticas-mercado.md` é referência, não política de mídia.
