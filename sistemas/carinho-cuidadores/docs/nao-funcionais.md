# Requisitos Nao-Funcionais

## 1. Desempenho

### Metas
- Tempo de resposta da API: < 200ms (P95)
- Tempo de busca de cuidadores: < 500ms
- Processamento de webhook: < 100ms

### Estrategias

#### Cache
- Redis para cache de consultas frequentes
- TTL de 5 minutos para filtros e estatisticas
- Invalidacao seletiva em alteracoes

#### Indexes de Banco
- `idx_caregivers_phone_status` - Busca por telefone
- `idx_caregivers_city` - Filtro por cidade
- `idx_caregiver_regions_city` - Busca por regiao
- `idx_caregiver_availability_day` - Busca por disponibilidade
- `idx_caregiver_documents_status` - Documentos pendentes
- `idx_caregiver_contracts_status` - Contratos ativos
- `idx_caregiver_ratings_date` - Avaliacoes recentes
- `idx_caregiver_incidents_date` - Incidentes recentes

#### Processamento Assincrono
- Validacao de documentos em background
- Sincronizacao com CRM em fila
- Envio de notificacoes em fila dedicada

---

## 2. Escalabilidade

### Arquitetura
- Stateless: sem sessao em servidor
- Horizontal: multiplas instancias via load balancer
- Filas: Redis com workers escalaveis

### Limites Configurados
- Paginacao padrao: 20 itens
- Paginacao maxima: 100 itens
- Upload maximo: 10 MB
- Timeout de API externa: 8-15 segundos

---

## 3. Disponibilidade

### Metas
- Uptime: 99.5%
- RTO (Recovery Time Objective): 1 hora
- RPO (Recovery Point Objective): 1 hora

### Estrategias
- Health check endpoint: `/api/health`
- Monitoramento de banco e cache
- Retry com backoff exponencial para integracoes
- Circuit breaker para falhas consecutivas

---

## 4. Seguranca

### Autenticacao
- Token interno para todas as rotas da API
- Validacao de assinatura em webhooks
- HTTPS obrigatorio

### Autorizacao
- Acesso restrito a RH/Operacao
- Logs de acesso a documentos
- Auditoria de alteracoes de status

### Dados Sensiveis
- Documentos criptografados em repouso
- URLs assinadas para visualizacao
- Validacao de arquivos (antivirus, extensao, tamanho)

### LGPD
- Consentimento para tratamento de dados
- Exportacao de dados sob demanda
- Exclusao com retencao configuravel

---

## 5. Observabilidade

### Logs
- Formato estruturado (JSON)
- Niveis: DEBUG, INFO, WARNING, ERROR
- Contexto: caregiver_id, document_id, job_id

### Metricas Recomendadas
- Cadastros por dia
- Taxa de ativacao
- Tempo medio de triagem
- Documentos pendentes
- Avaliacoes por semana
- Incidentes por mes

### Alertas Sugeridos
- Documentos pendentes > 50
- Fila de notificacoes > 100
- Erro de integracao > 5/minuto
- Tempo de resposta > 1 segundo

---

## 6. Backup e Resiliencia

### Banco de Dados
- Backup diario automatico
- Retencao de 30 dias
- Replicacao para leitura (futuro)

### Documentos
- Versionamento de arquivos
- Redundancia geografica (via sistema Documentos)

### Filas
- Persistencia em Redis
- Retentativa automatica de jobs falhos
- Dead letter queue para falhas permanentes

---

## 7. Integracao entre Sistemas

### Principios
- Cada sistema tem sua responsabilidade especifica
- Comunicacao via APIs REST e eventos
- Eventual consistency aceito

### Responsabilidades

| Sistema | Responsabilidade |
|---------|------------------|
| Cuidadores | Cadastro, triagem, contratos, avaliacao |
| CRM | Historico completo, relacionamento |
| Operacao | Alocacao, servicos, check-in/out |
| Documentos | Armazenamento, assinatura, LGPD |
| Atendimento | Comunicacao, suporte |
| Integracoes | Automacoes, eventos |

### Eventos
- Publicacao assincrona no hub
- Consumidores desacoplados
- Idempotencia em processamento

---

## 8. Configuracao

### Ambiente
- Variaveis de ambiente para configuracoes sensiveis
- Arquivos de config para parametros de negocio
- Feature flags para funcionalidades em teste

### Configuracoes Principais
```php
// config/cuidadores.php
'triagem' => [
    'documentos_obrigatorios' => ['id', 'cpf', 'address'],
    'experiencia_minima_anos' => 0,
    'max_file_size_mb' => 10,
],
'cache' => [
    'enabled' => true,
    'ttl_seconds' => 300,
],
'pagination' => [
    'default_per_page' => 20,
    'max_per_page' => 100,
],
```

---

## 9. Identidade Visual

### Cores
- Primary: #5BBFAD (verde-agua)
- Secondary: #F4F7F9 (cinza claro)
- Accent: #F5C6AA (pessego)
- Text: #1F2933 (cinza escuro)

### Tipografia
- Font: Arial, Helvetica Neue, sans-serif
- Tamanhos: 12px a 24px
- Legibilidade em dispositivos moveis

### Tom de Voz
- Empatico e respeitoso
- Objetivo e simples
- Sem jargoes tecnicos
- Sem infantilizacao

---

## 10. Conformidade

### LGPD
- Politica de privacidade
- Termo de consentimento
- Direito a exclusao
- Retencao configuravel

### Trabalhista
- Termo de responsabilidade claro
- Nao caracterizacao de vinculo
- Registro de aceites
