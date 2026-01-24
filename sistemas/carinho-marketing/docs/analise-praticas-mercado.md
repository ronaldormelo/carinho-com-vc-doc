# Análise do Módulo Carinho Marketing - Práticas de Mercado

**Data da Análise:** Janeiro/2026
**Versão:** 1.0

---

## 1. Descrição Objetiva da Responsabilidade do Módulo

O módulo **carinho-marketing** é responsável pela **presença digital e captação de leads** da operação Carinho com Você. Suas principais responsabilidades incluem:

### 1.1 Escopo Funcional

| Área | Responsabilidade |
|------|------------------|
| **Identidade de Marca** | Centralizar e padronizar elementos visuais, tom de voz e mensagens-chave |
| **Presença Digital** | Gerenciar contas em redes sociais e manter consistência de comunicação |
| **Calendário Editorial** | Organizar, aprovar e publicar conteúdos de forma programada |
| **Mídia Paga** | Gerenciar campanhas de anúncios no Meta Ads e Google Ads |
| **Captação de Leads** | Landing pages, UTM tracking e registro de origem |
| **Rastreamento** | Conversões via Facebook CAPI, Google Ads e Analytics |
| **Métricas** | Dashboard de performance e KPIs de marketing |

### 1.2 Integrações Principais

- **Meta (Facebook/Instagram):** Marketing API, Conversions API, Graph API
- **Google:** Ads API, Analytics 4 (Measurement Protocol)
- **WhatsApp:** Z-API para links e webhooks
- **Sistemas Internos:** CRM, Hub de Integrações, Site

---

## 2. Avaliação sob Ótica de Eficiência, Controle e Clareza

### 2.1 Pontos Fortes Identificados

| Aspecto | Avaliação | Observação |
|---------|-----------|------------|
| **Arquitetura** | ✅ Excelente | Separação clara entre Controllers, Services e Integrations |
| **Tabelas de Domínio** | ✅ Excelente | Status bem definidos evitam valores hardcoded |
| **Rastreamento UTM** | ✅ Muito Bom | Builder de UTM integrado com registro de origem |
| **Calendário Editorial** | ✅ Muito Bom | Workflow de aprovação básico implementado |
| **Biblioteca de Marca** | ✅ Bom | Centralização de assets e guidelines |
| **Integrações Externas** | ✅ Bom | Meta e Google bem integrados |
| **Conversões** | ✅ Bom | CAPI e Enhanced Conversions implementados |

### 2.2 Lacunas Identificadas (Antes dos Ajustes)

| Lacuna | Impacto | Risco |
|--------|---------|-------|
| Ausência de aprovação de orçamento | Gastos não autorizados | **Alto** |
| Sem limites de gastos diários/mensais | Estouro de budget | **Alto** |
| Falta de histórico de alterações | Dificuldade de auditoria | **Médio** |
| Sem gestão de parcerias locais | Perda de canal importante | **Médio** |
| Indicações não rastreadas como canal | Submensuração de ROI | **Médio** |
| Ausência de alertas operacionais | Reação tardia a problemas | **Médio** |
| Sem relatório de ROI consolidado | Decisões sem base | **Médio** |

### 2.3 Avaliação de Padronização

| Critério | Status | Comentário |
|----------|--------|------------|
| Nomenclatura de endpoints | ✅ Padronizado | RESTful consistente |
| Validação de dados | ✅ Implementado | Validation rules no controller |
| Tratamento de erros | ✅ Implementado | Try-catch com logs estruturados |
| Rate limiting | ✅ Implementado | 60 req/minuto por token |
| Documentação de API | ⚠️ Parcial | Apenas em markdown, sem OpenAPI |

---

## 3. Práticas Recomendadas (Consolidadas de Mercado)

### 3.1 Práticas de Negócio

| Prática | Descrição | Prioridade |
|---------|-----------|------------|
| **Aprovação de orçamento** | Campanhas acima de R$ 500 requerem aprovação gerencial | Alta |
| **Limite de gastos** | Definir teto diário e mensal por campanha e geral | Alta |
| **Parcerias locais** | Registrar e medir indicações de clínicas, hospitais e cuidadores | Alta |
| **ROI por canal** | Calcular retorno real considerando ticket médio e recorrência | Alta |
| **Revisão semanal** | Pausa automática de campanhas com CPL acima do limite | Média |
| **Sazonalidade** | Ajustar orçamento em períodos de alta demanda (inverno, férias) | Média |

### 3.2 Práticas de Processos

| Prática | Descrição | Implementação |
|---------|-----------|---------------|
| **Workflow de aprovação em 2 níveis** | Criador → Revisor → Aprovador final | Para campanhas > R$ 500 |
| **Checklist pré-publicação** | Verificar ortografia, links, UTM e compliance | Obrigatório |
| **Histórico de alterações** | Log de quem alterou o quê e quando | Auditoria completa |
| **Backup de criativos** | Manter versão original de todos os assets | 12 meses mínimo |
| **Padrão de nomenclatura** | `[ANO]-[MÊS]-[CANAL]-[OBJETIVO]-[SEGMENTO]` | Obrigatório |

### 3.3 Práticas de Gestão

| Prática | Descrição | Frequência |
|---------|-----------|------------|
| **Reunião de performance** | Revisar KPIs e ajustar estratégia | Semanal |
| **Relatório gerencial** | CPL, CAC, conversão e ROI consolidado | Semanal |
| **Auditoria de gastos** | Verificar gastos vs. orçamento aprovado | Diária (automatizada) |
| **Revisão de criativos** | Atualizar peças com baixa performance | Quinzenal |
| **Calibração de metas** | Ajustar CPL e CAC alvo com base em histórico | Mensal |

### 3.4 Práticas de Marketing Institucional

| Prática | Descrição | Observação |
|---------|-----------|------------|
| **Prova social** | Depoimentos reais (anonimizados) como conteúdo principal | 40% do calendário |
| **Conteúdo educativo** | Dicas de cuidado e bem-estar para famílias | 30% do calendário |
| **Serviços** | Apresentação clara dos tipos de atendimento | 20% do calendário |
| **Institucional** | Valores, equipe e diferenciais | 10% do calendário |
| **Frequência moderada** | 2-3 posts/semana, priorizar qualidade | Evitar excesso |
| **Resposta rápida** | Comentários respondidos em até 2 horas | Horário comercial |

---

## 4. Ajustes Implementados

### 4.1 Controle de Aprovação de Orçamento

**Arquivo:** `app/Services/CampaignApprovalService.php`

- Campanhas acima do limite requerem aprovação antes de ativar
- Registro de quem aprovou, quando e justificativa
- Níveis: Automático (até limite) → Supervisor → Gerência

### 4.2 Limites de Gastos e Alertas

**Arquivo:** `app/Services/BudgetControlService.php`

- Limite diário e mensal configurável por campanha
- Limite global da conta de marketing
- Alertas automáticos em 70%, 90% e 100% do limite
- Pausa automática opcional ao atingir 100%

### 4.3 Histórico de Alterações

**Tabela:** `campaign_audit_log`

- Registro completo de alterações em campanhas
- Campos: user_id, action, field, old_value, new_value, timestamp
- Retenção de 24 meses

### 4.4 Gestão de Parcerias Locais

**Arquivo:** `app/Services/PartnershipService.php`
**Tabela:** `marketing_partnerships`

- Cadastro de parceiros (clínicas, hospitais, cuidadores, condomínios)
- Tracking de indicações por parceiro
- Cálculo de comissão/bonificação quando aplicável
- Relatório de performance por parceria

### 4.5 Canal de Indicações

**Arquivo:** `app/Services/ReferralService.php`
**Tabela:** `referral_sources`

- Registro separado de leads por indicação
- Link de indicação único por cliente satisfeito
- Tracking de conversão de indicados
- Programa de benefícios (quando ativado)

### 4.6 Relatório de ROI Consolidado

**Arquivo:** `app/Services/RoiReportService.php`

- ROI por canal (Google, Meta, Orgânico, Indicação, Parceria)
- Consideração de ticket médio e recorrência
- Payback period por tipo de lead
- Comparativo mensal e trimestral

---

## 5. Riscos Operacionais e Pontos de Atenção

### 5.1 Riscos de Alto Impacto

| Risco | Causa Provável | Mitigação |
|-------|----------------|-----------|
| **Estouro de orçamento** | Campanha sem limite configurado | Obrigar limite antes de ativar |
| **Bloqueio de conta de anúncios** | Violação de políticas das plataformas | Checklist de compliance |
| **Perda de acesso às APIs** | Token expirado ou revogado | Alertas de expiração |
| **Dados de conversão incorretos** | Pixel mal configurado | Testes periódicos de eventos |

### 5.2 Riscos de Médio Impacto

| Risco | Causa Provável | Mitigação |
|-------|----------------|-----------|
| **CPL elevado** | Segmentação muito ampla ou criativo fraco | Revisão semanal obrigatória |
| **Leads não qualificados** | Landing page genérica | Formulário com campos de qualificação |
| **Conteúdo fora de padrão** | Falta de revisão | Workflow de aprovação |
| **Falha de integração** | API instável | Retry com backoff + Dead Letter Queue |

### 5.3 Pontos de Atenção Operacional

1. **Tokens de API:** Monitorar expiração (Meta: 60 dias, Google: refresh token)
2. **Rate Limits:** Meta permite 200 req/hora, ajustar sincronização
3. **Compliance LGPD:** Hash de dados pessoais nas APIs de conversão
4. **Backup de criativos:** Assets devem ser versionados e preservados
5. **Sazonalidade:** Ajustar orçamento para períodos de alta (inverno, fim de ano)

---

## 6. Indicadores de Sucesso (KPIs)

### 6.1 KPIs Primários

| Indicador | Meta | Frequência |
|-----------|------|------------|
| **CPL (Custo por Lead)** | < R$ 50 | Diário |
| **CAC (Custo de Aquisição)** | < R$ 150 | Semanal |
| **Taxa de Conversão Lead→Contrato** | > 20% | Semanal |
| **Tempo de Resposta** | < 5 minutos | Tempo real |
| **ROI de Marketing** | > 3x | Mensal |

### 6.2 KPIs Secundários

| Indicador | Meta | Frequência |
|-----------|------|------------|
| **CTR de Anúncios** | > 1,5% | Diário |
| **Engajamento Redes Sociais** | > 3% | Semanal |
| **Taxa de Indicação** | > 10% dos clientes | Mensal |
| **NPS de Atendimento** | > 70 | Mensal |

---

## 7. Checklist de Conformidade

### 7.1 Antes de Lançar Campanha

- [ ] Orçamento aprovado por responsável
- [ ] Limite diário configurado
- [ ] UTM definido e testado
- [ ] Criativos revisados (ortografia, marca)
- [ ] Link de destino funcionando
- [ ] Pixel/eventos de conversão testados
- [ ] Segmentação validada

### 7.2 Revisão Semanal

- [ ] CPL dentro do limite
- [ ] Gastos vs. orçamento aprovado
- [ ] Campanhas pausadas justificadas
- [ ] Leads respondidos em tempo
- [ ] Origem dos leads registrada no CRM

### 7.3 Revisão Mensal

- [ ] ROI por canal calculado
- [ ] Criativos de baixa performance substituídos
- [ ] Parcerias ativas medidas
- [ ] Indicações rastreadas
- [ ] Relatório gerencial enviado

---

## 8. Conclusão

O módulo **carinho-marketing** apresenta uma base técnica sólida, com arquitetura bem organizada e integrações essenciais implementadas. Os ajustes realizados focaram em:

1. **Controles financeiros** - Aprovação e limites de gastos
2. **Auditoria** - Histórico completo de alterações
3. **Canais tradicionais** - Parcerias locais e indicações
4. **Visibilidade gerencial** - ROI consolidado

Essas melhorias alinham o módulo com práticas tradicionais e consolidadas de mercado, priorizando **controle operacional**, **previsibilidade de gastos** e **rastreabilidade completa** do funil de aquisição.

---

**Próximos Passos Recomendados:**

1. Implementar testes automatizados (PHPUnit)
2. Dashboard visual com gráficos de tendência
3. Relatórios automáticos por email (semanal)
4. Integração com contabilidade para fechamento mensal
