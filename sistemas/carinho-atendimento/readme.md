# Carinho Atendimento

**Subdomínio:** atendimento.carinho.com.vc  
**Porta local:** 8080

Central de atendimento digital. Padroniza o WhatsApp, mede SLA e sincroniza o funil com o CRM. A família não usa este painel: fala no WhatsApp oficial; o atendente opera a inbox.

## Documentação deste módulo

| Precisa de | Arquivo |
|------------|---------|
| Arquitetura e serviços | [docs/arquitetura.md](docs/arquitetura.md) |
| Módulos (inbox, N1–N3, NPS) | [docs/modulos.md](docs/modulos.md) |
| Fluxos | [docs/fluxos-atendimento.md](docs/fluxos-atendimento.md) |
| Guia rápido (SLA, triagem, perda) | [docs/guia-rapido-atendente.md](docs/guia-rapido-atendente.md) |
| Manual | [docs/manual-operacional.md](docs/manual-operacional.md) |
| Integrações | [docs/integracoes.md](docs/integracoes.md) |
| Usabilidade, segurança, velocidade | [docs/nao-funcionais.md](docs/nao-funcionais.md) |
| Atividades / schema | [docs/atividades.md](docs/atividades.md), [database/estrutura.md](database/estrutura.md) |

Plataforma: [PERFORMANCE.md](../../PERFORMANCE.md) · [SECURITY.md](../../SECURITY.md) · [03 — Atendimento](../../03%20-%20Estrutura%20de%20Atendimento%20Digital.md)

## Stack

- PHP 8.2+, Laravel 11
- MariaDB 10.11 compartilhado (driver `mysql`, schema `carinho_atendimento`)
- Redis (filas de mensagem e webhook)
- `laravel/horizon` está no composer; **não há** `config/horizon.php` versionado — worker: `queue:work`. WhatsApp: Z-API

Health: `GET /up` (Laravel) e `GET /api/health`. API **sem** `/v1`. Inbox real: `/api/inbox`, não `/demandas`.

## O que faz

- Inbox unificada, histórico, etiquetas e audit trail
- Mensagens automáticas (fora do horário, primeira resposta, follow-up)
- Funil `new → triage → proposal → waiting → active | lost | closed`
- SLA por prioridade (seed `sla_targets`)
- Níveis N1/N2/N3 e incidentes (emergência notifica Operação)
- NPS após encerramento
- E-mail de proposta/contrato (fila)
- Sync de lead e incidente com o CRM

Não é cadastro mestre do cliente (CRM), não agenda plantão (Operação) e não cobra (Financeiro).

## SLA (seed)

| Prioridade | Código | 1ª resposta | Resolução |
|------------|--------|-------------|-----------|
| Baixa | `low` | 60 min | 8 h |
| Normal | `normal` | 30 min | 4 h |
| Alta | `high` | 15 min | 2 h |
| Urgente | `urgent` | 5 min | 1 h |

Horário comercial: 08:00–18:00, `America/Sao_Paulo` (`config/atendimento.php`). Escalonamento N1/N2/N3: 15 / 30 / 60 min.

## Integrações

| Destino | Função |
|---------|--------|
| Z-API | Envio e webhook de WhatsApp |
| CRM | Lead, status, motivo de perda |
| Operação | Emergência high/critical |
| Integrações | Eventos ponta a ponta |
| E-mail | Proposta e contrato |

## Segurança

- Token interno nas rotas sensíveis
- Assinatura de webhook Z-API
- Rate limit de envio
- PII mascarada em logs
- Papéis: atendente, supervisor, admin (`domain_agent_role`)

## Performance

Webhook grava e enfileira. Envio WhatsApp/e-mail assíncrono. Cache de domínio 12 h e feriados 24 h. Workers horizontais. Detalhe: [nao-funcionais.md](docs/nao-funcionais.md).

## Health

- Laravel: `GET /up`
- Módulo: `GET /api/health`

## Docker

Na raiz: `docker compose up -d`. Neste diretório: `docker compose up -d`. Guia: [README.DOCKER.md](../../README.DOCKER.md).
