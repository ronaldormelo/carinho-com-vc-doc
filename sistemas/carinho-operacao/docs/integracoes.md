# Integracoes

## Visao Geral

O sistema Carinho Operacao integra-se com outros sistemas internos e APIs externas para compor o ecossistema completo de servicos.

## Arquitetura de Integracao

```
                    ┌─────────────────┐
                    │   Z-API         │
                    │   (WhatsApp)    │
                    └────────┬────────┘
                             │
┌─────────────┐    ┌────────┴────────┐    ┌─────────────┐
│ Atendimento │◄──►│                 │◄──►│    CRM      │
└─────────────┘    │     OPERACAO    │    └─────────────┘
                   │                 │
┌─────────────┐    │                 │    ┌─────────────┐
│ Cuidadores  │◄──►│                 │◄──►│ Financeiro  │
└─────────────┘    └─────────────────┘    └─────────────┘
```

## Sistemas Internos

### 1. CRM (Customer Relationship Management)

**Base URL:** `https://crm.carinho.com.vc/api`

**Funcoes:**
- Obter dados do cliente
- Sincronizar solicitacoes de servico
- Registrar eventos e feedback
- Consultar preferencias

**Endpoints Utilizados:**
| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /clients/{id} | Dados do cliente |
| GET | /clients/{id}/emergency-contacts | Contatos de emergencia |
| GET | /clients/{id}/preferences | Preferencias |
| POST | /service-requests | Sincroniza solicitacao |
| PATCH | /service-requests/{id}/status | Atualiza status |
| POST | /feedback | Registra feedback |
| POST | /events | Registra evento |

**Cliente:** `App\Integrations\Crm\CrmClient`

### 2. Cuidadores

**Base URL:** `https://cuidadores.carinho.com.vc/api`

**Funcoes:**
- Buscar cuidadores disponiveis
- Consultar disponibilidade
- Notificar sobre alocacoes
- Obter dados de perfil e habilidades

**Endpoints Utilizados:**
| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /caregivers/available | Busca disponiveis |
| GET | /caregivers/{id} | Dados do cuidador |
| GET | /caregivers/{id}/availability | Disponibilidade |
| GET | /caregivers/{id}/skills | Habilidades |
| GET | /caregivers/{id}/regions | Regioes atendidas |
| GET | /caregivers/{id}/rating | Avaliacao media |
| POST | /caregivers/{id}/assignments | Notifica alocacao |
| POST | /caregivers/{id}/events | Registra evento |

**Cliente:** `App\Integrations\Cuidadores\CuidadoresClient`

### 3. Atendimento

**Base URL:** `https://atendimento.carinho.com.vc/api`

**Funcoes:**
- Obter detalhes de demandas
- Atualizar status de demandas
- Notificar sobre alocacao e conclusao
- Registrar ocorrencias

**Endpoints Utilizados:**
| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| GET | /demandas/{id} | Detalhes da demanda |
| GET | /demandas/pendentes | Demandas pendentes |
| GET | /demandas/{id}/history | Historico |
| PATCH | /demandas/{id}/status | Atualiza status |
| POST | /demandas/{id}/allocation | Notifica alocacao |
| POST | /demandas/{id}/completion | Notifica conclusao |
| POST | /demandas/{id}/occurrences | Registra ocorrencia |

**Cliente:** `App\Integrations\Atendimento\AtendimentoClient`

### 4. Financeiro

**Base URL:** `https://financeiro.carinho.com.vc/api`

**Funcoes:**
- Registrar servicos para cobranca
- Notificar conclusao de servico
- Registrar cancelamentos e taxas
- Solicitar repasse para cuidadores
- Registrar horas trabalhadas

**Endpoints Utilizados:**
| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| POST | /services | Registra servico |
| POST | /services/{id}/complete | Finaliza servico |
| GET | /services/{id}/status | Status financeiro |
| POST | /cancellations | Registra cancelamento |
| POST | /repasses | Solicita repasse |
| POST | /hours | Registra horas |
| POST | /events | Notifica evento |

**Cliente:** `App\Integrations\Financeiro\FinanceiroClient`

## APIs Externas

### Z-API (WhatsApp Business)

**Documentacao:** https://developer.z-api.io/

**Base URL:** `https://api.z-api.io`

**Funcoes:**
- Enviar mensagens de texto
- Enviar mensagens com botoes
- Enviar documentos e imagens
- Receber mensagens via webhook
- Gerenciar status da instancia

**Endpoints Utilizados:**
| Metodo | Endpoint | Descricao |
|--------|----------|-----------|
| POST | /send-text | Envia texto |
| POST | /send-image | Envia imagem |
| POST | /send-document | Envia documento |
| POST | /send-button-list | Envia com botoes |
| POST | /send-link | Envia link com preview |
| GET | /status | Status da instancia |
| GET | /qr-code | QR Code para conexao |
| GET | /disconnect | Desconecta instancia |

**Cliente:** `App\Integrations\WhatsApp\ZApiClient`

**Configuracao:**
```env
ZAPI_BASE_URL=https://api.z-api.io
ZAPI_INSTANCE_ID=seu-instance-id
ZAPI_TOKEN=seu-token
ZAPI_CLIENT_TOKEN=seu-client-token
ZAPI_WEBHOOK_SECRET=seu-webhook-secret
```

**Tipos de Notificacao via WhatsApp:**
- Inicio de servico
- Fim de servico
- Lembrete de agendamento
- Alocacao de cuidador
- Substituicao de cuidador
- Confirmacao com botoes

## Webhooks Recebidos

O sistema Operacao recebe webhooks de:

### WhatsApp (Z-API)
**Endpoint:** `POST /api/webhooks/whatsapp`

Eventos tratados:
- `message` - Mensagem recebida
- `status` - Status de mensagem
- `delivery` - Confirmacao de entrega

Validacao por assinatura HMAC-SHA256.

### Atendimento
**Endpoint:** `POST /api/webhooks/atendimento`

Eventos tratados:
- `demanda_criada` - Nova demanda
- `demanda_atualizada` - Atualizacao
- `demanda_cancelada` - Cancelamento

### Cuidadores
**Endpoint:** `POST /api/webhooks/cuidadores`

Eventos tratados:
- `disponibilidade_atualizada` - Mudanca de disponibilidade
- `cuidador_indisponivel` - Cuidador ficou indisponivel

## Autenticacao

### Token Interno
Comunicacao entre sistemas usa token Bearer:
```
Authorization: Bearer {INTERNAL_API_TOKEN}
```

Ou via header customizado:
```
X-Internal-Token: {INTERNAL_API_TOKEN}
```

### Headers Padrao
Todas as requisicoes incluem:
```
Content-Type: application/json
Accept: application/json
X-Source: carinho-operacao
```

## Tratamento de Erros

Todas as integracoes seguem o padrao de resposta:

```php
[
    'status' => 200,        // HTTP status
    'ok' => true,           // Sucesso boolean
    'body' => [...],        // Corpo da resposta
    'error' => null,        // Mensagem de erro (se houver)
]
```

### Retry Policy
- Timeout: configuravel por integracao (5-15s)
- Jobs com retry: 3 tentativas
- Backoff exponencial: 5s, 15s, 30s

### Logging
Todas as falhas sao logadas com:
- Path da requisicao
- Metodo HTTP
- Status code
- Mensagem de erro

## Monitoramento

### Health Check das Integracoes
Verificar conectividade:
- `GET /api/health` - Status geral
- `GET /api/status` - Status detalhado (DB, Cache)

### Metricas Sugeridas
- Taxa de sucesso por integracao
- Tempo de resposta medio
- Volume de requisicoes
- Erros por tipo
