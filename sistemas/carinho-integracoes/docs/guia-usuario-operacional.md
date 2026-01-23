# Guia do Usuário Operacional - Carinho Integrações

**Versão:** 1.0
**Público:** Equipe de Operações

---

## Introdução

Este guia apresenta os procedimentos diários para operação do sistema de integrações. Siga os passos descritos para garantir que todas as integrações funcionem corretamente e os dados fluam entre os sistemas.

**URL do Sistema:** https://integracoes.carinho.com.vc

---

## Sumário

1. [Rotina Diária](#1-rotina-diária)
2. [Monitoramento de Eventos](#2-monitoramento-de-eventos)
3. [Gestão de Falhas](#3-gestão-de-falhas)
4. [Sincronizações](#4-sincronizações)
5. [WhatsApp](#5-whatsapp)
6. [Relatórios](#6-relatórios)

---

## 1. Rotina Diária

### 1.1 Verificação Matinal (08:00)

**Objetivo:** Garantir que o sistema está pronto para o dia de trabalho.

#### Passo 1: Acessar o Dashboard

1. Abra o navegador
2. Acesse: `https://integracoes.carinho.com.vc/dashboard`
3. Verifique se o status geral está **"healthy"** (verde)

```
Se aparecer "healthy" → Sistema OK, continue para o Passo 2
Se aparecer "degraded" → Verifique os alertas (seção 3)
Se aparecer "critical" → Acione suporte imediatamente
```

#### Passo 2: Verificar WhatsApp

1. No dashboard, localize a seção **"whatsapp"**
2. Confirme que `connected: true`

```
Se connected: true → WhatsApp OK
Se connected: false → Siga o procedimento da seção 5.2
```

#### Passo 3: Verificar Alertas

1. Acesse: `https://integracoes.carinho.com.vc/alerts`
2. Verifique se há alertas ativos

```
Se total: 0 → Nenhum alerta, tudo OK
Se há alertas → Siga os procedimentos da seção 3
```

#### Passo 4: Verificar Dead Letter Queue (DLQ)

1. No dashboard, localize **"queues" > "dead_letter"**
2. Verifique o total de itens

```
Se total < 10 → Aceitável
Se total >= 10 → Precisa revisão (seção 3.3)
```

#### Checklist Matinal Resumido

- [ ] Dashboard mostra "healthy"
- [ ] WhatsApp conectado
- [ ] Sem alertas críticos
- [ ] DLQ com menos de 10 itens
- [ ] Sync jobs da noite anterior executados

---

### 1.2 Verificação Vespertina (18:00)

**Objetivo:** Garantir que o dia transcorreu sem problemas.

1. Acesse o dashboard
2. Verifique:
   - Eventos processados do dia
   - Taxa de erro (deve ser < 5%)
   - Itens na fila de retry

---

## 2. Monitoramento de Eventos

### 2.1 Entendendo os Eventos

O sistema processa eventos que fluem entre os sistemas. Os principais são:

| Evento | O que significa |
|--------|----------------|
| `lead.created` | Novo lead chegou (site ou WhatsApp) |
| `client.registered` | Lead virou cliente |
| `service.completed` | Serviço foi finalizado |
| `payment.received` | Pagamento foi confirmado |
| `feedback.received` | Cliente enviou avaliação |

### 2.2 Verificando Status dos Eventos

#### Passo 1: Acessar Estatísticas

1. Acesse: `https://integracoes.carinho.com.vc/dashboard`
2. Localize a seção **"events"**

#### Passo 2: Interpretar os Números

```json
{
  "events": {
    "pending": 5,           // Eventos aguardando processamento
    "today": {
      "total": 150,         // Total de eventos hoje
      "processed": 142,     // Processados com sucesso
      "failed": 3           // Falharam
    }
  }
}
```

**Interpretação:**
- `pending` < 100 → Normal
- `pending` > 100 → Possível acúmulo, verificar workers
- Taxa de sucesso > 95% → Normal
- Taxa de sucesso < 95% → Investigar falhas

### 2.3 Fluxo Feliz: Lead do Site

Quando um cliente preenche o formulário no site:

```
1. Site envia dados → Integrações recebe
2. Integrações registra no CRM
3. Integrações envia WhatsApp de resposta automática
4. Integrações atribui à campanha de marketing (se tiver UTM)

✓ Lead aparece no CRM com origem "site"
✓ Cliente recebe mensagem de boas-vindas no WhatsApp
```

### 2.4 Fluxo Feliz: Mensagem WhatsApp

Quando um cliente envia mensagem pelo WhatsApp:

```
1. Z-API notifica → Integrações recebe
2. Integrações identifica/cria lead no CRM
3. Integrações registra interação
4. Integrações encaminha para Atendimento

✓ Lead/cliente aparece no CRM
✓ Mensagem aparece no sistema de Atendimento
```

### 2.5 Fluxo Feliz: Serviço Finalizado

Quando a operação finaliza um serviço:

```
1. Operação notifica → Integrações recebe
2. Integrações notifica cliente (WhatsApp)
3. Após 2 horas, envia solicitação de feedback
4. Integrações cria fatura no Financeiro
5. Integrações atualiza CRM

✓ Cliente recebe notificação de conclusão
✓ Cliente recebe pedido de avaliação
✓ Fatura criada automaticamente
```

---

## 3. Gestão de Falhas

### 3.1 Entendendo as Filas de Erro

O sistema tem duas filas para tratar falhas:

| Fila | O que contém | Ação automática |
|------|--------------|-----------------|
| **Retry Queue** | Eventos que falharam mas serão tentados novamente | Tenta novamente a cada 1-60 min |
| **Dead Letter Queue (DLQ)** | Eventos que falharam após todas tentativas | Aguarda revisão manual |

### 3.2 Verificando Retry Queue

#### Passo 1: Acessar Informações

1. Acesse: `https://integracoes.carinho.com.vc/dashboard`
2. Localize **"queues" > "retry_queue"**

```json
{
  "retry_queue": {
    "total": 15,      // Total aguardando retry
    "ready": 3        // Prontos para tentar agora
  }
}
```

#### Passo 2: Interpretar

- `total` < 50 → Normal, sistema recuperando sozinho
- `total` > 50 → Pode indicar problema persistente
- Se `total` está crescendo → Algo está impedindo o processamento

**Ação:** Se o total está alto e crescendo, verifique se os sistemas destino estão funcionando.

### 3.3 Revisando Dead Letter Queue (DLQ)

Itens na DLQ precisam de **revisão manual**. São eventos que falharam após 5 tentativas.

#### Passo 1: Listar Itens na DLQ

1. Acesse a API com sua chave:

```
GET https://integracoes.carinho.com.vc/api/dlq
Header: X-API-Key: sua-chave-aqui
```

2. Você verá uma lista como:

```json
{
  "data": [
    {
      "id": 123,
      "event_id": 456,
      "reason": "Failed to create lead in CRM: Connection timeout",
      "created_at": "2026-01-23T10:30:00"
    }
  ]
}
```

#### Passo 2: Analisar o Motivo

Leia o campo `reason` para entender por que falhou:

| Motivo comum | Causa provável | Ação |
|--------------|----------------|------|
| "Connection timeout" | Sistema destino estava fora | Reprocessar |
| "Invalid phone number" | Dados incorretos | Corrigir dados e reprocessar |
| "Client not found" | Referência inválida | Verificar dados no CRM |
| "Max retries exceeded" | Falha persistente | Investigar com desenvolvimento |

#### Passo 3: Reprocessar Item

Se o problema foi temporário (sistema voltou):

```
POST https://integracoes.carinho.com.vc/api/dlq/123/retry
Header: X-API-Key: sua-chave-aqui
```

**Resposta esperada:**
```json
{
  "message": "Item queued for retry",
  "event_id": 456
}
```

#### Passo 4: Arquivar Item

Se o item não deve ser reprocessado (dados inválidos, já tratado manualmente):

```
POST https://integracoes.carinho.com.vc/api/dlq/123/archive
Header: X-API-Key: sua-chave-aqui
```

### 3.4 Fluxo de Decisão para DLQ

```
Encontrou item na DLQ?
│
├─► Motivo é "timeout" ou "connection"?
│   ├─► SIM → Verificar se sistema destino está OK → Reprocessar
│   └─► NÃO → Continuar análise
│
├─► Motivo é "invalid data"?
│   ├─► SIM → Verificar dados no sistema origem
│   │         └─► Se possível corrigir → Reprocessar
│   │         └─► Se não → Arquivar
│   └─► NÃO → Continuar análise
│
└─► Motivo não identificado?
    └─► Escalar para desenvolvimento
```

---

## 4. Sincronizações

### 4.1 Entendendo as Sincronizações

O sistema executa sincronizações periódicas entre sistemas:

| Sync | Frequência | Horário | O que faz |
|------|------------|---------|-----------|
| CRM → Operação | Horária | :00 | Cria agendas de serviço |
| Operação → Financeiro | Diária | 23:00 | Gera faturas de serviços |
| CRM → Financeiro | 2x/dia | 06:00, 18:00 | Configura cobranças |
| Cuidadores → CRM | 4 horas | -- | Atualiza dados de cuidadores |

### 4.2 Verificando Status das Sincronizações

#### Passo 1: Acessar Dashboard

1. Acesse: `https://integracoes.carinho.com.vc/dashboard`
2. Localize a seção **"sync"**

#### Passo 2: Verificar Última Execução

```json
{
  "sync": {
    "sync.crm_operacao": {
      "last_run": "2026-01-23T10:00:00",
      "last_status": "done",
      "recent_failures": 0,
      "duration_seconds": 45
    }
  }
}
```

**Interpretação:**
- `last_status: "done"` → Última execução OK
- `last_status: "failed"` → Última execução falhou
- `recent_failures: 0` → Nenhuma falha recente
- `recent_failures > 2` → Problema persistente, investigar

### 4.3 Fluxo Feliz: Sincronização CRM → Operação

Quando um contrato é assinado no CRM:

```
1. CRM marca contrato como ativo
2. Sync (a cada hora) identifica novo contrato
3. Sync cria agenda na Operação
4. Sync confirma no CRM que foi sincronizado

✓ Agenda aparece na Operação
✓ Contrato no CRM mostra "sincronizado"
```

### 4.4 Fluxo Feliz: Sincronização Operação → Financeiro

Quando serviços são finalizados:

```
1. Operação marca serviços como concluídos
2. Sync (às 23:00) coleta serviços do dia
3. Sync cria faturas no Financeiro
4. Sync marca serviços como "faturados"

✓ Faturas aparecem no Financeiro
✓ Serviços na Operação mostram "faturado"
```

---

## 5. WhatsApp

### 5.1 Verificando Status do WhatsApp

#### Passo 1: Acessar Status

1. Acesse: `https://integracoes.carinho.com.vc/status`
2. Localize a seção **"whatsapp"**

```json
{
  "whatsapp": {
    "connected": true,
    "status": "connected"
  }
}
```

#### Passo 2: Interpretar

- `connected: true` → WhatsApp funcionando normalmente
- `connected: false` → Precisa reconectar (siga 5.2)

### 5.2 Reconectando WhatsApp

**Quando:** WhatsApp mostra `connected: false`

#### Passo 1: Acessar Painel Z-API

1. Acesse o painel da Z-API (URL fornecida pelo administrador)
2. Faça login com suas credenciais

#### Passo 2: Verificar Instância

1. Localize a instância "Carinho"
2. Verifique o status

#### Passo 3: Reconectar

1. Se desconectada, clique em "Conectar"
2. Escaneie o QR code com o WhatsApp do número da empresa
3. Aguarde confirmação de conexão

#### Passo 4: Validar

1. Volte ao dashboard de integrações
2. Verifique se `connected: true`
3. Monitore se as mensagens pendentes estão sendo enviadas

### 5.3 Fluxo Feliz: Envio de Mensagem Automática

Quando um lead é criado:

```
1. Lead criado no sistema
2. Integrações agenda mensagem de boas-vindas
3. Job de notificação envia via Z-API
4. Z-API entrega no WhatsApp do cliente

✓ Cliente recebe mensagem em segundos
✓ Status da mensagem aparece como "sent"
```

### 5.4 Fluxo Feliz: Recebimento de Mensagem

Quando cliente envia mensagem:

```
1. Cliente envia mensagem no WhatsApp
2. Z-API notifica Integrações (webhook)
3. Integrações valida e normaliza mensagem
4. Integrações registra no CRM
5. Integrações encaminha para Atendimento

✓ Mensagem aparece no sistema de Atendimento
✓ Interação registrada no histórico do CRM
```

---

## 6. Relatórios

### 6.1 Relatório Diário

**Objetivo:** Verificar resumo das operações do dia anterior.

#### Passo 1: Acessar Relatório

1. Acesse: `https://integracoes.carinho.com.vc/report/daily`

#### Passo 2: Interpretar

```json
{
  "date": "2026-01-22",
  "events": {
    "total": 245,
    "processed": 240,
    "failed": 5
  },
  "deliveries": {
    "total": 180,
    "success": 178
  },
  "sync_jobs": {
    "sync.crm_operacao": { "total": 24, "success": 24 },
    "sync.operacao_financeiro": { "total": 1, "success": 1 }
  },
  "dead_letter_added": 2
}
```

**Indicadores saudáveis:**
- Taxa de sucesso de eventos > 95%
- Taxa de sucesso de entregas > 95%
- Sync jobs com 100% de sucesso
- DLQ com poucos itens novos

### 6.2 Métricas Semanais

Para análise semanal, compare os relatórios diários e observe:

| Métrica | Esperado | Atenção |
|---------|----------|---------|
| Volume de eventos | Estável ou crescente | Queda brusca pode indicar problema |
| Taxa de sucesso | > 95% | < 90% requer investigação |
| Itens na DLQ | < 20 por semana | > 50 requer ação |
| Sync jobs | 100% sucesso | Falhas recorrentes requerem ação |

---

## 7. Circuit Breakers

### 7.1 O que são Circuit Breakers

Circuit breakers protegem o sistema quando um serviço externo está com problemas. Quando ativados, evitam sobrecarga do sistema com tentativas repetidas.

### 7.2 Verificando Circuit Breakers

#### Passo 1: Acessar Status

1. Acesse: `https://integracoes.carinho.com.vc/circuit-breakers`

```json
{
  "services": {
    "crm": {
      "state": "closed",
      "failure_count": 0
    },
    "whatsapp": {
      "state": "closed",
      "failure_count": 2
    }
  }
}
```

#### Passo 2: Interpretar Estados

| Estado | Significado | Ação |
|--------|-------------|------|
| `closed` | Normal, funcionando | Nenhuma |
| `half_open` | Testando recuperação | Aguardar |
| `open` | Bloqueado | Verificar sistema destino |

### 7.3 Resetando Circuit Breaker

**Quando:** Após confirmar que o sistema destino voltou a funcionar.

```
POST https://integracoes.carinho.com.vc/circuit-breakers/crm/reset
```

**Resposta:**
```json
{
  "message": "Circuit breaker for crm has been reset",
  "status": {
    "state": "closed",
    "failure_count": 0
  }
}
```

---

## 8. Resumo de Boas Práticas

### Faça Sempre

1. **Verifique o dashboard** no início e fim do dia
2. **Revise a DLQ** diariamente - não deixe acumular
3. **Monitore o WhatsApp** - é o canal principal de comunicação
4. **Documente incidentes** para melhoria contínua

### Evite

1. **Ignorar alertas** - eles existem por um motivo
2. **Reprocessar sem análise** - entenda o motivo da falha primeiro
3. **Deixar DLQ crescer** - itens antigos são mais difíceis de resolver
4. **Resetar circuit breakers** sem verificar o sistema destino

### Em Caso de Dúvida

1. Consulte o [Runbook Operacional](./runbook-operacional.md)
2. Verifique os logs do sistema
3. Escale para o desenvolvimento se necessário

---

## 9. Contatos

| Situação | Contato |
|----------|---------|
| Dúvidas operacionais | operacao@carinho.com.vc |
| Problemas técnicos | dev@carinho.com.vc |
| Emergências | Telefone de plantão (ver escalação) |

---

## Glossário Rápido

| Termo | Significado |
|-------|-------------|
| **Dashboard** | Tela com visão geral do sistema |
| **Evento** | Ação que acontece e precisa ser processada |
| **Webhook** | Notificação automática entre sistemas |
| **DLQ** | Fila de eventos que falharam (Dead Letter Queue) |
| **Retry** | Nova tentativa de processar algo que falhou |
| **Sync** | Sincronização periódica de dados |
| **Circuit Breaker** | Proteção que bloqueia requisições para sistema com problema |

---

*Guia mantido pela equipe de operações. Versão 1.0 - Janeiro/2026*
