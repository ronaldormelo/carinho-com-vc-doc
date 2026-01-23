# Matriz de Integrações - Carinho Integracoes

**Última Atualização:** Janeiro/2026
**Responsável Técnico:** Equipe de Desenvolvimento

---

## 1. Visão Geral das Integrações

Este documento consolida todas as integrações ativas do módulo `carinho-integracoes`, incluindo endpoints, frequência, SLA esperado e procedimentos de contingência.

---

## 2. Integrações por Sistema

### 2.1 WhatsApp (Z-API)

| Item | Valor |
|------|-------|
| **Provedor** | Z-API |
| **Tipo** | Externo |
| **Criticidade** | Alta |
| **SLA Esperado** | 99.5% disponibilidade |

#### Endpoints

| Direção | Endpoint | Descrição |
|---------|----------|-----------|
| Entrada | `POST /webhooks/whatsapp` | Recebe mensagens |
| Entrada | `POST /webhooks/whatsapp/status` | Status de entrega |
| Saída | `POST /instances/{id}/send-text` | Envia texto |
| Saída | `POST /instances/{id}/send-button-list` | Envia botões |
| Saída | `POST /instances/{id}/send-link` | Envia link |

#### Monitoramento
- Health check: `GET /instances/{id}/status`
- Verificar conexão antes do horário comercial (8h)
- Alerta se desconectado por mais de 15 minutos

#### Contingência
1. Se Z-API indisponível: Registrar mensagem para envio posterior
2. Se instância desconectada: Notificar responsável para reconectar
3. Fallback: Não há (WhatsApp é canal principal)

---

### 2.2 CRM (crm.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Alta |
| **SLA Esperado** | 99.9% disponibilidade |
| **Timeout** | 10 segundos |

#### Endpoints Utilizados

| Método | Endpoint | Descrição | Frequência |
|--------|----------|-----------|------------|
| POST | `/api/v1/leads` | Criar lead | Evento |
| PUT | `/api/v1/leads/{id}` | Atualizar lead | Evento |
| GET | `/api/v1/leads` | Buscar lead | Evento |
| POST | `/api/v1/leads/{id}/interactions` | Registrar interação | Evento |
| POST | `/api/v1/clients` | Criar cliente | Evento |
| GET | `/api/v1/sync/pending-schedules` | Agendas pendentes | Horário |
| GET | `/api/v1/sync/pending-billing-setup` | Setup financeiro | 2x/dia |

#### Eventos Recebidos

| Evento | Ação |
|--------|------|
| `client.registered` | Enviar boas-vindas + setup financeiro |
| `lead.created` | Registrar + auto-resposta |

#### Contingência
1. Se indisponível: Eventos vão para retry queue
2. Após 5 tentativas: Move para DLQ
3. Revisão manual obrigatória

---

### 2.3 Operação (operacao.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Alta |
| **SLA Esperado** | 99.9% disponibilidade |
| **Timeout** | 10 segundos |

#### Endpoints Utilizados

| Método | Endpoint | Descrição | Frequência |
|--------|----------|-----------|------------|
| POST | `/api/v1/schedules` | Criar agenda | Horário (sync) |
| GET | `/api/v1/services/completed` | Serviços finalizados | Diário (sync) |
| PUT | `/api/v1/services/{id}/billed` | Marcar faturado | Diário (sync) |

#### Eventos Recebidos

| Evento | Ação |
|--------|------|
| `service.completed` | Notificar cliente + solicitar feedback |
| `service.cancelled` | Atualizar CRM + ajustar financeiro |

#### Sincronizações

| Nome | Frequência | Horário | Descrição |
|------|------------|---------|-----------|
| `crm_operacao` | Horária | :00 | Cria agendas a partir de contratos |
| `operacao_financeiro` | Diária | 23:00 | Gera faturas de serviços |

---

### 2.4 Financeiro (financeiro.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Alta |
| **SLA Esperado** | 99.9% disponibilidade |
| **Timeout** | 10 segundos |

#### Endpoints Utilizados

| Método | Endpoint | Descrição | Frequência |
|--------|----------|-----------|------------|
| POST | `/api/invoices` | Criar fatura | Diário (sync) |
| POST | `/api/billing/setup` | Setup de cobrança | 2x/dia (sync) |

#### Eventos Recebidos

| Evento | Ação |
|--------|------|
| `payment.received` | Atualizar CRM + Operação |
| `payment.failed` | Notificar CRM |
| `payout.processed` | Notificar cuidador |

#### Sincronizações

| Nome | Frequência | Horário | Descrição |
|------|------------|---------|-----------|
| `operacao_financeiro` | Diária | 23:00 | Cria faturas |
| `crm_financeiro` | 2x/dia | 06:00, 18:00 | Setup de cobrança |

---

### 2.5 Cuidadores (cuidadores.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Média |
| **SLA Esperado** | 99% disponibilidade |
| **Timeout** | 10 segundos |

#### Endpoints Utilizados

| Método | Endpoint | Descrição | Frequência |
|--------|----------|-----------|------------|
| GET | `/api/v1/sync/pending-updates` | Atualizações pendentes | 4h (sync) |
| POST | `/api/v1/sync/confirm/{id}` | Confirmar sync | 4h (sync) |

#### Eventos Enviados

| Evento | Destino | Descrição |
|--------|---------|-----------|
| `payout.processed` | Cuidadores | Repasse processado |
| `service.completed` | Cuidadores | Registrar avaliação |
| `caregiver.assigned` | Cuidadores | Atribuição de serviço |

#### Sincronizações

| Nome | Frequência | Descrição |
|------|------------|-----------|
| `cuidadores_crm` | 4 horas | Sincroniza atualizações de cuidadores |

---

### 2.6 Atendimento (atendimento.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Média |
| **SLA Esperado** | 99% disponibilidade |
| **Timeout** | 10 segundos |

#### Eventos Enviados

| Evento | Descrição |
|--------|-----------|
| `whatsapp.inbound` | Mensagem recebida para atendimento |
| `whatsapp.status` | Status de entrega de mensagem |

---

### 2.7 Marketing (marketing.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Baixa |
| **SLA Esperado** | 95% disponibilidade |
| **Timeout** | 10 segundos |

#### Endpoints Utilizados

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/v1/campaigns/find-by-utm` | Buscar campanha por UTM |
| POST | `/api/v1/campaigns/{id}/leads` | Atribuir lead a campanha |

#### Eventos Enviados

| Evento | Descrição |
|--------|-----------|
| `lead.created` | Novo lead para atribuição |
| `client.registered` | Conversão de cliente |

---

### 2.8 Site (site.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Média |
| **SLA Esperado** | 99% disponibilidade |
| **Timeout** | 10 segundos |

#### Eventos Recebidos

| Evento | Endpoint | Descrição |
|--------|----------|-----------|
| `lead.created` | `POST /webhooks/site/lead` | Lead do formulário |

---

### 2.9 Documentos LGPD (documentos.carinho.com.vc)

| Item | Valor |
|------|-------|
| **Tipo** | Interno |
| **Criticidade** | Baixa |
| **SLA Esperado** | 95% disponibilidade |
| **Timeout** | 10 segundos |

#### Uso
- Atualmente não há integrações ativas
- Reservado para futuras integrações de documentos e consentimentos

---

## 3. Fluxo de Eventos

### 3.1 Tipos de Eventos

| Evento | Origem | Destinos |
|--------|--------|----------|
| `lead.created` | Site, WhatsApp | CRM, Marketing |
| `lead.updated` | CRM | CRM |
| `client.registered` | CRM | CRM, Financeiro, Marketing |
| `client.updated` | CRM | CRM, Operação |
| `service.scheduled` | CRM | CRM, Operação, Cuidadores, Financeiro |
| `service.started` | Operação | CRM, Operação |
| `service.completed` | Operação | CRM, Financeiro, Cuidadores |
| `service.cancelled` | Operação | CRM, Operação, Financeiro |
| `payment.received` | Financeiro | CRM, Operação |
| `payment.failed` | Financeiro | CRM |
| `invoice.created` | Financeiro | CRM |
| `payout.processed` | Financeiro | Cuidadores |
| `whatsapp.inbound` | Z-API | CRM, Atendimento |
| `feedback.received` | Operação, WhatsApp | CRM, Cuidadores |

---

## 4. SLAs e Métricas

### 4.1 SLA por Prioridade de Fila

| Fila | SLA Processamento | Volume Esperado |
|------|-------------------|-----------------|
| `integrations-high` | < 5 segundos | 100/hora |
| `integrations` | < 30 segundos | 500/hora |
| `notifications` | < 60 segundos | 200/hora |
| `integrations-low` | < 5 minutos | 50/hora |
| `integrations-retry` | < 10 minutos | 20/hora |

### 4.2 Métricas de Monitoramento

| Métrica | Threshold Alerta | Threshold Crítico |
|---------|------------------|-------------------|
| Eventos pendentes | > 100 | > 500 |
| Retry queue | > 50 | > 200 |
| Dead Letter Queue | > 10 | > 50 |
| Taxa de erro | > 5% | > 15% |

---

## 5. Procedimentos Operacionais

### 5.1 Checklist Diário

- [ ] Verificar status do WhatsApp às 7:30
- [ ] Verificar DLQ (Dead Letter Queue)
- [ ] Verificar execução dos sync jobs da noite anterior
- [ ] Verificar tamanho das filas

### 5.2 Procedimento de Falha

1. **Identificar:** Verificar logs e status do evento
2. **Classificar:** Determinar se é erro de integração ou dados
3. **Corrigir:** Se dados, corrigir e reprocessar; Se integração, aguardar retry
4. **Monitorar:** Acompanhar reprocessamento
5. **Documentar:** Registrar causa raiz se recorrente

### 5.3 Reprocessamento de DLQ

```bash
# Listar itens na DLQ
GET /api/dlq

# Reprocessar item específico
POST /api/dlq/{id}/retry

# Arquivar item (após análise)
POST /api/dlq/{id}/archive
```

---

## 6. Contatos de Escalação

| Nível | Responsável | Contato | Quando Acionar |
|-------|-------------|---------|----------------|
| 1 | Operação | operacao@carinho.com.vc | Falhas pontuais |
| 2 | Desenvolvimento | dev@carinho.com.vc | Falhas recorrentes |
| 3 | Gestão | gestao@carinho.com.vc | Indisponibilidade crítica |

---

*Documento mantido pela equipe de desenvolvimento. Atualizar sempre que houver mudanças nas integrações.*
