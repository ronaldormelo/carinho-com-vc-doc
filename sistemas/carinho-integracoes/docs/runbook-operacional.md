# Runbook Operacional - Carinho Integracoes

**Versão:** 1.0
**Última Atualização:** Janeiro/2026

---

## 1. Visão Geral

Este documento descreve os procedimentos operacionais para manutenção e troubleshooting do módulo `carinho-integracoes`.

### Informações Básicas

| Item | Valor |
|------|-------|
| **URL** | https://integracoes.carinho.com.vc |
| **Health Check** | GET /health |
| **Dashboard** | GET /dashboard |
| **Logs** | /var/log/carinho-integracoes/ |
| **Workers** | Horizon ou Supervisor |

---

## 2. Checklist Diário

### 2.1 Verificação Matinal (08:00)

- [ ] Verificar status do WhatsApp (`GET /status`)
- [ ] Verificar se há alertas ativos (`GET /alerts`)
- [ ] Verificar DLQ (Dead Letter Queue)
- [ ] Verificar execução dos sync jobs da noite anterior
- [ ] Verificar tamanho das filas

### 2.2 Verificação Vespertina (18:00)

- [ ] Verificar taxa de erro do dia
- [ ] Verificar eventos pendentes
- [ ] Verificar retry queue

### Comandos Úteis

```bash
# Verificar status geral
curl https://integracoes.carinho.com.vc/status | jq

# Verificar alertas
curl https://integracoes.carinho.com.vc/alerts | jq

# Verificar dashboard completo
curl https://integracoes.carinho.com.vc/dashboard | jq

# Verificar circuit breakers
curl https://integracoes.carinho.com.vc/circuit-breakers | jq
```

---

## 3. Procedimentos de Troubleshooting

### 3.1 WhatsApp Desconectado

**Sintoma:** Status do WhatsApp mostra `connected: false`

**Impacto:** Mensagens não são enviadas nem recebidas

**Procedimento:**

1. Verificar status da instância no painel Z-API
2. Se desconectada, escanear QR code para reconectar
3. Verificar se há mensagens pendentes na fila `notifications`
4. Após reconectar, monitorar envio das mensagens pendentes

```bash
# Verificar status
curl https://integracoes.carinho.com.vc/status | jq '.whatsapp'

# Se reconectou, verificar fila
curl https://integracoes.carinho.com.vc/health/detailed | jq '.checks.queues.sizes.notifications'
```

**Tempo estimado:** 5-10 minutos

---

### 3.2 Dead Letter Queue (DLQ) com Itens

**Sintoma:** Alertas indicam itens na DLQ

**Impacto:** Eventos não processados, dados inconsistentes

**Procedimento:**

1. Listar itens na DLQ
2. Analisar motivo das falhas
3. Corrigir causa raiz se necessário
4. Reprocessar itens válidos
5. Arquivar itens inválidos

```bash
# Listar itens na DLQ
curl -H "X-API-Key: {KEY}" \
  https://integracoes.carinho.com.vc/api/dlq | jq

# Ver detalhes de um item
curl -H "X-API-Key: {KEY}" \
  https://integracoes.carinho.com.vc/api/dlq/{id} | jq

# Reprocessar item
curl -X POST -H "X-API-Key: {KEY}" \
  https://integracoes.carinho.com.vc/api/dlq/{id}/retry

# Arquivar item (após análise)
curl -X POST -H "X-API-Key: {KEY}" \
  https://integracoes.carinho.com.vc/api/dlq/{id}/archive
```

**Tempo estimado:** 15-30 minutos por item

---

### 3.3 Circuit Breaker Aberto

**Sintoma:** Alertas indicam circuit breaker aberto para um serviço

**Impacto:** Requisições para o serviço são bloqueadas

**Procedimento:**

1. Identificar qual serviço está com circuit breaker aberto
2. Verificar se o serviço destino está funcionando
3. Se serviço recuperou, resetar circuit breaker manualmente
4. Monitorar se não abre novamente

```bash
# Verificar circuit breakers
curl https://integracoes.carinho.com.vc/circuit-breakers | jq

# Resetar circuit breaker de um serviço
curl -X POST https://integracoes.carinho.com.vc/circuit-breakers/crm/reset

# Monitorar por 5 minutos
watch -n 30 'curl -s https://integracoes.carinho.com.vc/circuit-breakers | jq ".services.crm"'
```

**Tempo estimado:** 5-15 minutos

---

### 3.4 Sync Job Falhando

**Sintoma:** Sync job com status `failed`

**Impacto:** Dados não sincronizados entre sistemas

**Procedimento:**

1. Verificar logs do job específico
2. Identificar causa da falha
3. Se for problema temporário, aguardar próxima execução
4. Se for problema persistente, escalar para desenvolvimento

```bash
# Verificar status dos sync jobs
curl https://integracoes.carinho.com.vc/status | jq '.sync_jobs'

# Executar sync manualmente (via artisan)
php artisan schedule:run --job=sync-crm-operacao

# Verificar logs
tail -f /var/log/carinho-integracoes/sync.log
```

**Tempo estimado:** 10-30 minutos

---

### 3.5 Alta Taxa de Erro

**Sintoma:** Taxa de erro > 5%

**Impacto:** Muitos eventos não processados

**Procedimento:**

1. Identificar tipo de evento com mais falhas
2. Verificar se é um sistema específico
3. Verificar logs para entender o padrão
4. Tomar ação conforme causa identificada

```bash
# Ver eventos por tipo hoje
curl https://integracoes.carinho.com.vc/dashboard | jq '.events.by_type'

# Ver eventos por origem
curl https://integracoes.carinho.com.vc/dashboard | jq '.events.by_source'

# Verificar logs de erro
grep ERROR /var/log/carinho-integracoes/laravel.log | tail -100
```

**Tempo estimado:** 15-60 minutos

---

### 3.6 Fila Muito Grande

**Sintoma:** Tamanho da fila > 500 itens

**Impacto:** Atraso no processamento de eventos

**Procedimento:**

1. Verificar se workers estão rodando
2. Se workers parados, reiniciar
3. Se workers rodando, verificar se há gargalo
4. Considerar aumentar número de workers temporariamente

```bash
# Verificar tamanho das filas
curl https://integracoes.carinho.com.vc/health/detailed | jq '.checks.queues'

# Verificar workers (Horizon)
php artisan horizon:status

# Reiniciar workers
php artisan horizon:terminate && php artisan horizon

# Ou via Supervisor
supervisorctl restart carinho-integracoes:*
```

**Tempo estimado:** 10-20 minutos

---

### 3.7 Banco de Dados Lento

**Sintoma:** Latência do banco > 100ms

**Impacto:** Processamento lento de eventos

**Procedimento:**

1. Verificar se há queries lentas
2. Verificar uso de recursos do servidor
3. Se necessário, escalar para DBA

```bash
# Verificar latência
curl https://integracoes.carinho.com.vc/health/detailed | jq '.checks.database.latency_ms'

# Verificar queries lentas (MySQL)
mysql -e "SHOW PROCESSLIST"

# Verificar índices
mysql -e "SHOW INDEX FROM integration_events"
```

**Tempo estimado:** 30-60 minutos

---

## 4. Procedimentos de Manutenção

### 4.1 Rotação de API Keys

**Frequência:** Trimestral

```bash
# Gerar nova API key
php artisan integrations:generate-api-key --name="Sistema X"

# Revogar API key antiga (após atualizar sistemas)
php artisan integrations:revoke-api-key --id=123
```

### 4.2 Limpeza de Dados Antigos

**Frequência:** Automático (diário)

O sistema limpa automaticamente:
- Eventos processados > 30 dias
- Rate limits expirados

Para limpeza manual:

```bash
# Limpar eventos antigos
php artisan integrations:cleanup --days=30

# Limpar DLQ arquivada
php artisan integrations:cleanup-dlq --archived --days=90
```

### 4.3 Backup de Configurações

**Frequência:** Semanal

```bash
# Exportar mapeamentos
php artisan integrations:export-mappings > mappings_backup.json

# Exportar endpoints
php artisan integrations:export-endpoints > endpoints_backup.json
```

---

## 5. Contatos de Escalação

| Nível | Responsável | Contato | Quando Acionar |
|-------|-------------|---------|----------------|
| 1 | Operação | operacao@carinho.com.vc | Falhas pontuais |
| 2 | Desenvolvimento | dev@carinho.com.vc | Falhas recorrentes |
| 3 | Gestão | gestao@carinho.com.vc | Indisponibilidade crítica |

### Critérios de Escalação

- **Nível 1 → 2:** Problema persiste por mais de 30 minutos
- **Nível 2 → 3:** Sistema indisponível por mais de 1 hora

---

## 6. Métricas de SLA

| Métrica | Normal | Alerta | Crítico |
|---------|--------|--------|---------|
| Taxa de sucesso | > 95% | 90-95% | < 90% |
| Eventos pendentes | < 100 | 100-500 | > 500 |
| DLQ | < 10 | 10-50 | > 50 |
| Latência DB | < 50ms | 50-100ms | > 100ms |
| Tempo de fila | < 30s | 30-60s | > 60s |

---

## 7. Glossário

| Termo | Descrição |
|-------|-----------|
| **DLQ** | Dead Letter Queue - fila de eventos que falharam após todas tentativas |
| **Circuit Breaker** | Mecanismo que bloqueia requisições para serviços indisponíveis |
| **Sync Job** | Job de sincronização periódica entre sistemas |
| **Retry Queue** | Fila de eventos aguardando nova tentativa |
| **Horizon** | Dashboard de monitoramento de filas do Laravel |

---

*Documento mantido pela equipe de operações. Atualizar sempre que houver mudanças nos procedimentos.*
