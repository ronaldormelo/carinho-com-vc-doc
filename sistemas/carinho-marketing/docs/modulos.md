# Modulos

Descricao detalhada dos modulos do sistema Carinho Marketing.

## 1. Gestao de Redes Sociais

### Funcionalidades
- Cadastro de contas em redes sociais (Facebook, Instagram, etc.)
- Padronizacao de bio com UTM integrado
- Sincronizacao de perfis com APIs das plataformas
- Gestao de hashtags e mensagens da marca

### Endpoints
```
GET  /api/social-accounts - Lista contas
POST /api/social-accounts - Cria conta
GET  /api/social-accounts/{id} - Detalhes
PUT  /api/social-accounts/{id} - Atualiza
POST /api/social-accounts/{id}/sync-instagram - Sincroniza Instagram
POST /api/social-accounts/{id}/sync-facebook - Sincroniza Facebook
GET  /api/social-accounts/{id}/bio - Bio formatada
GET  /api/social-accounts/channels - Lista canais
```

### Models
- `MarketingChannel` - Canal de marketing
- `SocialAccount` - Conta em rede social

## 2. Calendario Editorial

### Funcionalidades
- Criacao e agendamento de posts
- Gestao de assets (imagens, videos, textos)
- Publicacao automatica em Instagram e Facebook
- Workflow de aprovacao de conteudos
- Estatisticas de publicacoes

### Endpoints
```
GET  /api/calendar - Lista por periodo
GET  /api/calendar/this-week - Itens da semana
POST /api/calendar - Cria item
GET  /api/calendar/{id} - Detalhes
PUT  /api/calendar/{id} - Atualiza
POST /api/calendar/{id}/schedule - Agenda
POST /api/calendar/{id}/cancel-schedule - Cancela agendamento
POST /api/calendar/{id}/publish - Publica
POST /api/calendar/{id}/assets - Adiciona asset
DELETE /api/calendar/{id}/assets/{assetId} - Remove asset
POST /api/calendar/assets/{assetId}/approve - Aprova asset
GET  /api/calendar/stats - Estatisticas
```

### Models
- `ContentCalendar` - Item do calendario
- `ContentAsset` - Asset de conteudo

### Status de Conteudo
1. `draft` - Rascunho
2. `scheduled` - Agendado
3. `published` - Publicado
4. `canceled` - Cancelado

## 3. Gestao de Campanhas

### Funcionalidades
- Criacao de campanhas de Meta Ads e Google Ads
- Gestao de grupos de anuncios e criativos
- Sincronizacao de metricas
- Dashboard de performance
- Calculo de KPIs (CTR, CPC, CPL)

### Endpoints
```
GET  /api/campaigns - Lista campanhas
GET  /api/campaigns/dashboard - Dashboard
POST /api/campaigns - Cria campanha
GET  /api/campaigns/{id} - Detalhes com metricas
PUT  /api/campaigns/{id} - Atualiza
POST /api/campaigns/{id}/activate - Ativa
POST /api/campaigns/{id}/pause - Pausa
POST /api/campaigns/{id}/finish - Finaliza
GET  /api/campaigns/{id}/metrics - Metricas agregadas
GET  /api/campaigns/{id}/metrics/daily - Metricas diarias
POST /api/campaigns/{id}/sync-metrics - Sincroniza metricas
POST /api/campaigns/{campaignId}/ad-groups - Cria grupo
PUT  /api/campaigns/ad-groups/{adGroupId} - Atualiza grupo
POST /api/campaigns/ad-groups/{adGroupId}/creatives - Cria criativo
```

### Models
- `Campaign` - Campanha
- `AdGroup` - Grupo de anuncios
- `Creative` - Criativo
- `CampaignMetric` - Metricas diarias

### Status de Campanha
1. `planned` - Planejada
2. `active` - Ativa
3. `paused` - Pausada
4. `finished` - Finalizada

## 4. Landing Pages e UTM

### Funcionalidades
- Gestao de landing pages
- Builder de links UTM
- Geracao de URLs para WhatsApp
- URLs para bio de redes sociais
- Integracao com site principal

### Endpoints (Landing Pages)
```
GET  /api/landing-pages - Lista
GET  /api/landing-pages/published - Apenas publicadas
POST /api/landing-pages - Cria
GET  /api/landing-pages/{id} - Detalhes
PUT  /api/landing-pages/{id} - Atualiza
POST /api/landing-pages/{id}/publish - Publica
POST /api/landing-pages/{id}/archive - Arquiva
POST /api/landing-pages/{id}/utm - Define UTM padrao
GET  /api/landing-pages/{id}/stats - Estatisticas
GET  /api/landing-pages/{id}/url - Gera URL
```

### Endpoints (UTM)
```
GET  /api/utm - Lista links
POST /api/utm - Cria link
GET  /api/utm/{id} - Detalhes
POST /api/utm/build - Gera URL
POST /api/utm/build-whatsapp - Gera URL WhatsApp
POST /api/utm/build-bio - Gera URL bio
POST /api/utm/build-campaign - Gera URL campanha
POST /api/utm/parse - Extrai UTM de URL
GET  /api/utm/sources - Sources disponiveis
GET  /api/utm/mediums - Mediums disponiveis
```

### Models
- `LandingPage` - Landing page
- `UtmLink` - Link UTM

## 5. Conversoes e Rastreamento

### Funcionalidades
- Registro de eventos de conversao
- Integracao com Facebook CAPI
- Integracao com Google Ads Conversions
- Integracao com Google Analytics
- Estatisticas por origem

### Endpoints
```
POST /api/conversions/lead - Registra lead
POST /api/conversions/contact - Registra contato
POST /api/conversions/registration - Registra cadastro
GET  /api/conversions/events - Lista eventos
POST /api/conversions/events - Cria evento
GET  /api/conversions/stats - Estatisticas
```

### Models
- `ConversionEvent` - Evento configurado
- `LeadSource` - Origem do lead

### Tipos de Conversao
- `Lead` - Captura de lead
- `Contact` - Contato via WhatsApp
- `CompleteRegistration` - Cadastro completo
- `InitiateCheckout` - Inicio de contratacao
- `Purchase` - Contratacao finalizada

## 6. Biblioteca de Marca

### Funcionalidades
- Gestao de logos e icones
- Templates para posts e stories
- Paleta de cores e tipografia
- Tom de voz e mensagens-chave
- Temas de conteudo
- Geracao de CSS de branding

### Endpoints
```
GET  /api/brand/config - Configuracoes completas
GET  /api/brand/colors - Paleta de cores
GET  /api/brand/typography - Tipografia
GET  /api/brand/voice - Tom de voz
GET  /api/brand/messages - Mensagens-chave
GET  /api/brand/hashtags - Hashtags
GET  /api/brand/social-bio - Bio padrao
GET  /api/brand/content-themes - Temas de conteudo
GET  /api/brand/css - CSS gerado
GET  /api/brand/assets - Lista assets
GET  /api/brand/assets/logos - Lista logos
GET  /api/brand/assets/logos/primary - Logo principal
GET  /api/brand/assets/templates - Lista templates
POST /api/brand/assets - Cria asset
GET  /api/brand/assets/{id} - Detalhes
PUT  /api/brand/assets/{id} - Atualiza
POST /api/brand/assets/{id}/activate - Ativa
POST /api/brand/assets/{id}/deactivate - Desativa
```

### Models
- `BrandAsset` - Ativo da marca

### Tipos de Assets
- `logo` - Logos
- `icon` - Icones
- `template` - Templates
- `typography` - Tipografia
- `color` - Cores
- `pattern` - Padroes

## 7. Controle de Orcamento e Aprovacoes

### Funcionalidades
- Aprovacao de orcamento para campanhas acima do limite
- Limites de gastos diarios, mensais e totais
- Alertas automaticos em 70%, 90% e 100% do limite
- Pausa automatica de campanhas ao atingir limite
- Historico de aprovacoes

### Endpoints (Orcamento)
```
GET  /api/budget/summary - Resumo de orcamento
GET  /api/budget/global-limit - Limite global
PUT  /api/budget/global-limit - Define limite global
GET  /api/budget/alerts - Lista alertas nao reconhecidos
POST /api/budget/alerts/check - Verifica e dispara alertas
POST /api/budget/alerts/{id}/acknowledge - Reconhece alerta
GET  /api/budget/campaigns/{id}/limit - Limite da campanha
PUT  /api/budget/campaigns/{id}/limit - Define limite da campanha
GET  /api/budget/campaigns/{id}/can-activate - Verifica se pode ativar
```

### Endpoints (Aprovacoes)
```
GET  /api/approvals/pending - Aprovacoes pendentes
POST /api/approvals/request - Solicita aprovacao
POST /api/approvals/{id}/approve - Aprova solicitacao
POST /api/approvals/{id}/reject - Rejeita solicitacao
GET  /api/approvals/campaigns/{id}/history - Historico
```

### Regras de Aprovacao
- Campanhas ate R$ 500: aprovacao automatica
- Campanhas acima de R$ 500: requer aprovacao gerencial

## 8. Parcerias Locais

### Funcionalidades
- Cadastro de parceiros (clinicas, hospitais, cuidadores, condominios)
- Codigo de indicacao unico por parceiro
- Rastreamento de indicacoes
- Calculo de comissao
- Relatorio de performance por parceria

### Endpoints
```
GET  /api/partnerships - Lista parcerias
POST /api/partnerships - Cria parceria
GET  /api/partnerships/stats - Estatisticas
GET  /api/partnerships/commissions/pending - Comissoes pendentes
GET  /api/partnerships/{id} - Detalhes
PUT  /api/partnerships/{id} - Atualiza
POST /api/partnerships/{id}/activate - Ativa
POST /api/partnerships/{id}/deactivate - Desativa
GET  /api/partnerships/{id}/referrals - Lista indicacoes
POST /api/partnerships/referrals - Registra indicacao
POST /api/partnerships/referrals/{id}/convert - Marca convertido
POST /api/partnerships/referrals/{id}/pay-commission - Paga comissao
```

### Tipos de Parceria
1. `clinic` - Clinicas
2. `hospital` - Hospitais
3. `caregiver` - Cuidadores
4. `condominium` - Condominios
5. `pharmacy` - Farmacias
6. `other` - Outros

## 9. Indicacoes de Clientes

### Funcionalidades
- Programa de indicacao para clientes satisfeitos
- Codigo de indicacao unico por cliente
- Beneficios configuraveis (desconto, bonus)
- Limite de indicacoes por mes
- Rastreamento de conversoes

### Endpoints
```
GET  /api/referrals/program - Configuracao do programa
PUT  /api/referrals/program - Atualiza configuracao
GET  /api/referrals/stats - Estatisticas
GET  /api/referrals/benefits/pending - Beneficios pendentes
POST /api/referrals/code - Cria codigo para cliente
POST /api/referrals/register - Registra lead indicado
POST /api/referrals/{id}/convert - Marca convertido
POST /api/referrals/{id}/apply-benefit - Aplica beneficio
GET  /api/referrals/customer/{id} - Info do cliente
GET  /api/referrals/customer/{id}/list - Lista indicacoes
```

### Configuracao do Programa
- Tipo de beneficio: discount, bonus, gift
- Valor do beneficio para quem indica
- Valor minimo do contrato
- Maximo de indicacoes por mes

## 10. Relatorios de ROI

### Funcionalidades
- ROI consolidado por periodo
- ROI por canal de marketing
- ROI por fonte UTM
- Performance de campanhas
- Comparativo entre periodos
- Tendencias mensais

### Endpoints
```
GET  /api/reports/roi/consolidated - Relatorio consolidado
GET  /api/reports/roi/comparison - Comparativo
GET  /api/reports/roi/current-month - Mes atual
GET  /api/reports/roi/monthly - Relatorio mensal
GET  /api/reports/roi/quarterly - Relatorio trimestral
```

### Metricas Calculadas
- CPL (Custo por Lead) - pago e total
- CAC (Custo de Aquisicao de Cliente)
- ROI (Retorno sobre Investimento)
- Payback (dias para recuperar investimento)
- LTV (Valor do tempo de vida do cliente)

## Autenticacao

Todas as rotas protegidas requerem o header:
```
X-Internal-Token: {token}
```

Ou:
```
Authorization: Bearer {token}
```

## Rate Limiting

Limite padrao: 60 requisicoes por minuto por token.

Headers de resposta:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```
