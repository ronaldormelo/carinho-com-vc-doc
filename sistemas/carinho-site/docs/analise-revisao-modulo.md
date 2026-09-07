# Análise e Revisão do Módulo Carinho-Site

> Documento de **revisão** (janeiro/2026). Não substitui o README, as políticas financeiras nem os NFRs.

**Data:** Janeiro/2026  
**Módulo:** carinho-site (site.carinho.com.vc)

---

## 1. DESCRIÇÃO OBJETIVA DA RESPONSABILIDADE DO MÓDULO

O módulo **carinho-site** é o portal institucional público da empresa Carinho com Você, responsável por:

### Funções Primárias
- **Representação institucional:** Apresentar a empresa, sua proposta de valor, missão e diferenciais ao público
- **Captação de leads:** Converter visitantes em contatos qualificados (clientes e cuidadores)
- **Canal de conversão:** Direcionar potenciais clientes para o WhatsApp como canal principal de vendas
- **Transparência legal:** Disponibilizar políticas, termos e informações legais obrigatórias
- **Suporte informativo:** Esclarecer dúvidas através de FAQ e conteúdo educativo

### Público-Alvo
1. **Famílias** buscando cuidadores para idosos ou pessoas com necessidades especiais
2. **Cuidadores** interessados em trabalhar na plataforma
3. **Público geral** buscando informações sobre serviços de home care

### Integrações Críticas
- CRM (carinho-crm): Sincronização de leads
- Hub de Integrações: Eventos e automações
- WhatsApp (Z-API): Canal principal de conversão
- Google Analytics/GTM: Rastreamento de conversões

---

## 2. AVALIAÇÃO SOB A ÓTICA DE EFICIÊNCIA, CONTROLE E CLAREZA

### 2.1 Pontos Fortes ✓

| Aspecto | Avaliação | Observação |
|---------|-----------|------------|
| **Estrutura de Páginas** | Excelente | Organização clara por tipo de conteúdo |
| **Proposta de Valor** | Muito Boa | Comunicação clara dos benefícios |
| **Formulários** | Muito Boa | Validação frontend e backend, reCAPTCHA |
| **Integração CRM** | Muito Boa | Jobs assíncronos com retry automático |
| **Tracking UTM** | Muito Boa | Rastreamento completo de campanhas |
| **Políticas Legais** | Muito Boa | LGPD bem estruturada |
| **Identidade Visual** | Boa | Consistente com a marca |
| **SEO Básico** | Boa | Meta tags e Schema.org implementados |
| **Segurança** | Boa | Rate limiting, CSRF, HTTPS |
| **Performance** | Boa | Cache Redis, CDN para assets |

### 2.2 Pontos de Atenção ⚠

| Aspecto | Situação | Impacto | Prioridade |
|---------|----------|---------|------------|
| **Acentuação** | Ausente em todo o site | Prejudica credibilidade e leitura | Alta |
| **CNPJ** | Placeholder no footer | Falta de credibilidade institucional | Alta |
| **Telefone fixo** | Inexistente | Reduz confiança de público tradicional | Média |
| **Breadcrumbs** | Não implementado | Navegação menos intuitiva | Média |
| **Schema.org FAQ** | Parcial | SEO não otimizado para FAQ | Média |
| **Acessibilidade** | Básica | Faltam alt texts e ARIA labels | Média |
| **Horário emergência** | Oculto | Informação crítica não destacada | Baixa |

### 2.3 Métricas de Eficiência Recomendadas

Para controle operacional, recomenda-se monitorar:

1. **Conversão de Leads**
   - Taxa de conversão visitante → lead
   - Taxa de conversão lead → cliente
   - Tempo médio de resposta ao lead

2. **Engajamento**
   - Taxa de rejeição por página
   - Tempo médio na página
   - Páginas por sessão

3. **Performance Técnica**
   - Tempo de carregamento (LCP < 2.5s)
   - First Input Delay (FID < 100ms)
   - Cumulative Layout Shift (CLS < 0.1)

---

## 3. PRÁTICAS RECOMENDADAS (CONSOLIDADAS)

### 3.1 Negócio

| Prática | Descrição | Status |
|---------|-----------|--------|
| **Proposta de valor clara** | Comunicar em 5 segundos o que a empresa faz | ✓ Implementado |
| **CTA visível** | Botão de ação principal sempre visível | ✓ Implementado |
| **WhatsApp como canal** | Canal preferido pelo público brasileiro | ✓ Implementado |
| **Depoimentos sociais** | Prova social com avaliações reais | ⚠ Parcial |
| **Transparência de preços** | Informações claras sobre política de pagamento | ✓ Implementado |
| **Informações institucionais** | CNPJ, endereço, telefone fixo | ⚠ Pendente |
| **FAQ estruturado** | Redução de dúvidas e carga no atendimento | ✓ Implementado |

### 3.2 Processos

| Prática | Descrição | Status |
|---------|-----------|--------|
| **Captação estruturada** | Formulários com campos essenciais validados | ✓ Implementado |
| **Registro de consentimento** | LGPD com timestamp de aceite | ✓ Implementado |
| **Sincronização CRM** | Leads enviados automaticamente | ✓ Implementado |
| **Retry automático** | Tratamento de falhas de integração | ✓ Implementado |
| **Rastreamento de origem** | UTM em todas as conversões | ✓ Implementado |
| **Rate limiting** | Proteção contra abuso | ✓ Implementado |
| **Cache de páginas** | Redução de carga no servidor | ✓ Implementado |

### 3.3 Gestão

| Prática | Descrição | Status |
|---------|-----------|--------|
| **Health checks** | Monitoramento de disponibilidade | ✓ Implementado |
| **Logs estruturados** | Auditoria de eventos | ✓ Implementado |
| **Backup diário** | Recuperação de dados | ✓ Documentado |
| **Documentação técnica** | Arquitetura e integrações documentadas | ✓ Implementado |
| **Versionamento de políticas** | Histórico de documentos legais | ⚠ Parcial |
| **Métricas de conversão** | Dashboard com KPIs | ⚠ Parcial |

### 3.4 Marketing

| Prática | Descrição | Status |
|---------|-----------|--------|
| **SEO on-page** | Meta tags, titles, descriptions | ✓ Implementado |
| **Schema.org LocalBusiness** | Dados estruturados para Google | ✓ Implementado |
| **Schema.org FAQ** | Dados estruturados para perguntas | ⚠ Pendente |
| **Open Graph** | Compartilhamento em redes sociais | ✓ Implementado |
| **Google Analytics** | Rastreamento de comportamento | ✓ Implementado |
| **Google Tag Manager** | Gerenciamento centralizado de tags | ✓ Implementado |
| **URLs amigáveis** | Slugs descritivos | ✓ Implementado |
| **Canonical URLs** | Evitar conteúdo duplicado | ✓ Implementado |

---

## 4. AJUSTES RECOMENDADOS

### 4.1 Redução de Desperdícios

| Ajuste | Benefício | Esforço |
|--------|-----------|---------|
| Corrigir acentuação em todo o site | Evita retrabalho de correção futura | Baixo |
| Unificar mensagens de erro | Padronização reduz manutenção | Baixo |
| Remover placeholders genéricos | Credibilidade imediata | Baixo |

### 4.2 Aumento de Produtividade

| Ajuste | Benefício | Esforço |
|--------|-----------|---------|
| Implementar breadcrumbs | Navegação mais eficiente | Baixo |
| Schema.org FAQ | Melhor posicionamento no Google | Baixo |
| Adicionar telefone fixo | Captação de público tradicional | Baixo |

### 4.3 Padronização Operacional

| Ajuste | Benefício | Esforço |
|--------|-----------|---------|
| Criar componente de CTA padronizado | Consistência visual | Médio |
| Padronizar labels de formulário | UX consistente | Baixo |
| Documentar padrões de copy | Tom de voz uniforme | Baixo |

### 4.4 Maior Previsibilidade

| Ajuste | Benefício | Esforço |
|--------|-----------|---------|
| Adicionar selo de segurança | Confiança do visitante | Baixo |
| Destacar SLA de resposta | Expectativa clara | Baixo |
| Mostrar número de clientes/cuidadores | Prova social | Médio |

---

## 5. RISCOS OPERACIONAIS E PONTOS DE ATENÇÃO

### 5.1 Riscos Identificados

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| **Indisponibilidade do WhatsApp** | Média | Alto | Implementar canal alternativo (telefone/e-mail) |
| **Falha na integração CRM** | Baixa | Alto | Jobs com retry e dead letter implementados ✓ |
| **Sobrecarga de formulários** | Baixa | Médio | Rate limiting implementado ✓ |
| **Conteúdo desatualizado** | Média | Médio | Revisão periódica de FAQ e políticas |
| **Perda de credibilidade** | Média | Alto | Corrigir informações incompletas (CNPJ, telefone) |

### 5.2 Pontos de Atenção Críticos

1. **Dependência do WhatsApp**
   - O site depende fortemente do WhatsApp como canal de conversão
   - Recomendação: Manter telefone fixo como backup
   - Ação: Adicionar telefone de contato visível

2. **Informações Institucionais Incompletas**
   - CNPJ com placeholder prejudica credibilidade
   - Ação: Substituir por CNPJ real ou remover seção

3. **Acentuação Ausente**
   - Todo o site está sem acentuação correta
   - Impacto: Prejudica profissionalismo e leitura
   - Ação: Corrigir todas as páginas e componentes

4. **Conformidade LGPD**
   - Política de privacidade bem estruturada
   - Atenção: Manter atualizada com práticas reais

### 5.3 Recomendações de Monitoramento

1. **Diário**
   - Verificar health check do site
   - Monitorar fila de sincronização de leads

2. **Semanal**
   - Analisar taxa de conversão de leads
   - Verificar leads não sincronizados

3. **Mensal**
   - Revisar métricas de SEO
   - Atualizar FAQ com dúvidas recorrentes
   - Verificar validade de certificados SSL

---

## 6. CONCLUSÃO

O módulo **carinho-site** apresenta uma estrutura sólida e bem organizada, seguindo boas práticas de desenvolvimento web e marketing digital. Os principais pontos fortes são:

- Arquitetura clara e bem documentada
- Integração robusta com CRM
- Políticas legais bem estruturadas
- Identidade visual consistente

Os ajustes recomendados são majoritariamente de **baixo esforço** e **alto impacto**, focando principalmente em:

1. Correção de acentuação em todo o site
2. Adição de informações institucionais completas
3. Melhorias incrementais de SEO e acessibilidade

O módulo está apto para operação, desde que os pontos críticos de credibilidade institucional sejam endereçados antes do lançamento público.

---

## 7. PLANO DE AÇÃO IMEDIATO

| # | Ação | Responsável | Prazo | Status |
|---|------|-------------|-------|--------|
| 1 | Corrigir acentuação em todas as páginas | Dev | Imediato | 🔄 Em andamento |
| 2 | Atualizar CNPJ no footer | Comercial | Imediato | ⏳ Pendente dados |
| 3 | Adicionar telefone fixo de contato | Comercial | Imediato | ⏳ Pendente dados |
| 4 | Implementar breadcrumbs | Dev | 1 semana | 🔄 Em andamento |
| 5 | Adicionar Schema.org FAQ | Dev | 1 semana | 🔄 Em andamento |
| 6 | Melhorar acessibilidade | Dev | 2 semanas | ⏳ Pendente |
| 7 | Configurar monitoramento de métricas | Ops | 2 semanas | ⏳ Pendente |

---

*Documento gerado como parte da revisão do módulo carinho-site em conformidade com práticas consolidadas de gestão de serviços e operações de saúde.*
