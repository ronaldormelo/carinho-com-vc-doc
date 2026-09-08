# Velocidade por módulo

Números abaixo vêm de `docs/nao-funcionais.md`, READMEs, `config/*.php` e seeders deste repositório. Não são benchmarks medidos em produção. Use-os como **SLO de desenho**: se o código ou o `.env` mudar, atualize este arquivo.

Timeouts HTTP internos típicos: **8–15 s**. Z-API: connect **3 s**, request **10 s** (vários `config/integrations.php`). Meta/Google Ads no Marketing: até **30 s**. Upload Documentos: até **60 s**.

## SLA que o cliente sente

| Experiência | Meta | Origem |
|-------------|------|--------|
| Primeira resposta humana (urgente) | 5 min | `sla_targets` prioridade `urgent`; site; docs 02 e 03 |
| Primeira resposta (alta / normal / baixa) | 15 / 30 / 60 min | Mesmo seeder; [guia do atendente](sistemas/carinho-atendimento/docs/guia-rapido-atendente.md) |
| Resolução (urgente → baixa) | 1 h / 2 h / 4 h / 8 h | Idem |
| Auto-resposta WhatsApp | Imediata (fila) | Docs 02 e 03; jobs de Atendimento/Integrações |
| Horário comercial | 08:00–18:00 (sábado off por padrão) | `config/atendimento.php` |
| Emergência no site (crítico / alto / médio) | 15 min / 30 min / 2 h | README do Site |
| Alocação de cuidador | < 4 h | NFR Operação |
| Busca de substituto | 120 min | `config/operacao.php` |
| Notificação de agenda (lembrete) | 24 h e 2 h antes | Operação |
| Feedback pós-serviço | 2 h após conclusão | README Integrações |
| Pedido LGPD | 15 dias | README Documentos |

Escalonamento interno N1→N2→N3: **15 / 30 / 60 min** sem resposta (`domain_support_level`).

## SLO técnico por módulo

| Módulo | Resposta HTTP (desenho) | Caminho quente | Gargalo | Dependências no caminho crítico |
|--------|-------------------------|----------------|---------|----------------------------------|
| **Site** | Página em cache Redis; formulário com rate limit 5/min. Público: `https://carinho.com.vc` (local `http://127.0.0.1:8084`) | Home, lead, CTA WhatsApp | Sync CRM em job; reCAPTCHA; Z-API se notificar | CRM, Integrações, reCAPTCHA, GA/GTM |
| **Marketing** | API interna; jobs longos (métricas até 300 s, publish 120 s) | UTM, conversão, calendário | APIs Meta/Google (timeout 30 s) | Meta, Google Ads/GA, CRM, Site, Integrações |
| **Atendimento** | Webhook persiste e enfileira (meta < 100 ms de ack) | Inbox, SLA, envio WhatsApp | Instância Z-API; workers; CRM | Z-API, CRM, Operação (emergência), Integrações |
| **CRM** | Listas e dashboard com cache Redis | Lead, kanban, contrato | Integrações 10–15 s; Z-API 30 s | Site, Atendimento, Operação, Financeiro, Documentos, Cuidadores |
| **Cuidadores** | API P95 < 200 ms; busca < 500 ms; webhook ack < 100 ms | Busca por região/disponibilidade | Upload 10 MB; Documentos 15–60 s | Documentos, Operação, CRM, Atendimento, Integrações |
| **Operação** | API P95 < 500 ms; notificação < 5 min | Agenda do dia, match, check-in | Match + HTTP Cuidadores 8 s; Financeiro 10 s | CRM, Cuidadores, Atendimento, Financeiro, Z-API |
| **Financeiro** | Webhook Stripe rápido + fila; Horizon | PIX/boleto, vencimento, repasse sexta | Stripe; conciliação mensal | Stripe, CRM, Operação, Cuidadores, Z-API |
| **Documentos** | URL assinada; export LGPD job até 10 min | Assinatura OTP, upload | S3; Z-API OTP | S3, Z-API, CRM, Cuidadores, Financeiro, Atendimento |
| **Integrações** | Ack de webhook + fila (`integrations-high` 2–5 workers) | WhatsApp→CRM, sync horário | Fan-out para 8 sistemas; DLQ | Todos os módulos + Z-API |

Cache típico: **5 min** (buscas Cuidadores, agenda Operação). Domínios do Atendimento: **12 h**. Feriados: **24 h**. Candidatos de match: **1 min**.

## Filas que atrasam o cliente se pararem

| Fila (nome documentado) | Módulo | Se parar |
|-------------------------|--------|----------|
| mensagens / `whatsapp` / `notifications` | Atendimento, Operação, Integrações | SLA de WhatsApp estoura |
| `emergencies` | Operação | Emergência não escala |
| `integrations-high` | Integrações | Lead e mensagem automática atrasam |
| `documents` / `contracts` | Cuidadores, Documentos | Onboarding do cuidador trava |
| Stripe/webhooks + `ProcessWeeklyPayouts` | Financeiro | Pagamento ou sexta de repasse falha |
| `PublishScheduledContent` / `SyncCampaignMetrics` | Marketing | Post e CPL ficam defasados |

Alertas já sugeridos nos NFRs: taxa de erro > 5%; fila Integrações retry > 100; DLQ crescendo; documentos pendentes > 50; atraso de check-in > 30 min.

## Capacidade e limites

- Paginação padrão **20**, máximo **100** (Cuidadores e Operação).
- Máximo de candidatos no match: **10**.
- Intervalo mínimo entre plantões do mesmo cuidador: **60 min**.
- Antecedência mínima de agenda: **24 h**.
- Duração de plantão: **4–12 h**.
- Check-in: tolerância atraso **15 min**, distância **500 m**.

## Como validar

Não há suíte de carga na raiz. Evidência operacional: health checks (tabela em [contratos-rotas.md](sistemas/carinho-integracoes/docs/contratos-rotas.md)), Horizon no hub, tamanho de filas Redis, e os dashboards de SLA do Atendimento e da Operação.
