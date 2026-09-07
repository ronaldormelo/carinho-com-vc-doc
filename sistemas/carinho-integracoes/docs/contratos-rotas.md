# Contratos de rota (verificados no código)

Fonte: arquivos `routes/*.php` de cada módulo. Clientes HTTP em `app/Integrations` e `app/Services/Integrations` devem seguir esta tabela.

Prefixo Laravel padrão: `/api` + caminho do arquivo. Só o **CRM** adiciona `/v1` (`bootstrap/app.php`).

Clientes do hub e dos módulos foram alinhados a estas rotas (set/2026). Métodos sem destino equivalente retornam HTTP 501 interno (`not_implemented`) em vez de chamar path fantasma.

## Health (não unificado)

| Módulo | Laravel `withRouting(health:)` | Health do módulo |
|--------|-------------------------------|------------------|
| Site | `GET /up` | `GET /health`, `GET /health/detailed` (público). `GET /api/health` público (token interno não se aplica) |
| Marketing | `GET /up` | `GET /api/health` |
| Atendimento | `GET /up` | `GET /api/health` |
| CRM | `GET /up` | `GET /health` (web) |
| Cuidadores | `GET /up` | `GET /api/health` |
| Operação | `GET /up` | `GET /api/health`, `GET /api/status` |
| Financeiro | `GET /up` | `GET /health`, `GET /health/detailed` |
| Documentos | `GET /up` | `GET /api/health`, `GET /api/up` |
| Integrações | `GET /health` (bootstrap) | `GET /health`, `GET /health/detailed` públicos. `/status`, `/dashboard`, `/alerts`, reset de circuit breaker exigem `X-API-Key` |

## CRM (canônico `/api/v1`)

| Uso | Rota real | Auth |
|-----|-----------|------|
| Ingestão pública de lead | `POST /api/v1/public/leads` | throttle `webhooks` |
| API autenticada | `/api/v1/leads`, `/clients`, `/deals`, … | Sanctum |
| Webhooks internos | `/webhooks/internal/...` | `X-API-Key` + `X-Service-Origin` (`verify.internal`) |
| Z-API | `/webhooks/zapi/message` | throttle |
| Aceite digital | `GET/POST /contract/{token}/sign` e `/accept` | sessão web + CSRF |

Não existem no CRM: `POST /incidents`, `POST /caregivers`, `GET /api/v1/sync/pending-schedules`.

## Atendimento (`/api`, sem v1)

Rotas reais: `GET /inbox`, `POST /inbox`, `PATCH /inbox/{conversation}/status`, `POST /inbox/{conversation}/incident`, `POST /conversations/{conversation}/messages`, `GET /metrics/*`, `POST /webhooks/whatsapp/z-api`.

Não existem: `/demandas/*`, `/api/v1/conversations`.

## Operação (`/api`, sem v1)

Rotas reais: `/service-requests`, `/schedules`, `/checkin/schedule/{id}/in|out`, `/assignments/...`, `/emergencies`, `/notifications`.

Não existem: `/api/v1/services`, `/api/v1/allocation/search`.

## Financeiro (`/api`, sem v1)

Rotas reais: `/invoices`, `/payments`, `/payouts`, `/pricing`, `/settings`, `/webhooks/stripe`.

Não existem: `/services`, `/cancellations`, `/repasses`, `/hours`.

## Cuidadores (`/api`, sem v1)

Rotas reais: `/caregivers`, `/search`, `/webhooks/whatsapp/z-api`.

Não existem: `/api/v1/sync/pending-updates`.

## Site (`/api` e web)

Público: `POST /lead/cliente`, `/lead/cuidador`, `/lead/investidor` (5/min). Interno: `/api/leads`, `/api/content/*` (CMS do CRM), webhooks de cache.

Não existem: `/api/v1/leads/confirm`, `/landing-pages` (landings ficam no Marketing).

## Marketing (`/api`, sem v1)

Rotas reais: `/calendar`, `/campaigns`, `/landing-pages`, `/utm`, `/conversions`, `/budget`, `/partnerships`, `/referrals`, `/reports/roi`, `/webhooks/meta`.

O hub chama `/api/campaigns/{id}/sync-metrics`, `/api/utm`, `/api/conversions/lead`, `/api/webhooks/conversion` (sem `/v1`).

## Documentos (`/api`, sem v1)

Rotas reais: `/documents`, `/contracts`, `/consents`, `/data-requests`, `/signatures`.

## Como evoluir o hub

Não inventar rota no destino para “fazer o cliente passar”. Ajustar o cliente HTTP ao caminho da tabela acima. Métodos sem destino equivalente retornam 501 (`not_implemented`) no cliente, sem HTTP para path fantasma.

Sincronizações em lote do hub (`SyncService`) que dependiam de `/api/v1/sync/*` inexistentes no CRM/Cuidadores estão desligadas (job completa sem chamar fantasma).

## Débitos conhecidos (código, não documentação)

- Fatura no Financeiro exige `contract_id`; serviço concluído sem contrato não gera invoice.
- Webhook Documentos→CRM `contract-signed` exige `exists:contracts,id` **do CRM** — ID do módulo Documentos não mapeia automaticamente.
- Várias rotas do CRM (`reviews`, `referrals`, `classification`, `needs-review`, etc.) apontam para métodos ausentes em `ClientController` (exceto `events`/`logEvent`, que existem).
- Download/PDF de contrato em Documentos respondem “Não implementado”.
- Health paths não unificados (`/health`, `/api/health`, `/up`).
