# Procedimentos de Auditoria LGPD

## Visão Geral

Este documento estabelece os procedimentos formais de auditoria para o módulo Carinho Documentos e LGPD, garantindo conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).

## 1. Auditorias Programadas

### 1.1 Auditoria Diária (Automatizada)

**Horário:** 09:00 e 15:00
**Job:** `CheckLgpdDeadlines`
**Responsável:** Sistema automatizado

**Verificações:**
- [ ] Solicitações LGPD vencidas (prazo > 15 dias)
- [ ] Solicitações próximas do vencimento (≤ 3 dias)
- [ ] Alertas críticos pendentes

**Ações Automáticas:**
1. Gerar alertas no CRM para solicitações críticas
2. Publicar eventos no Hub de Integrações
3. Registrar em log de auditoria

### 1.2 Auditoria Semanal (Automatizada)

**Dia/Horário:** Segunda-feira, 06:00
**Job:** `ProcessComplianceAudit`
**Responsável:** Sistema automatizado + Gestor de Compliance

**Verificações:**
- [ ] Score de conformidade geral
- [ ] Taxa de cumprimento de prazos LGPD
- [ ] Status das políticas de retenção
- [ ] Métricas de consentimentos
- [ ] Indicadores de risco

**Ações:**
1. Gerar relatório consolidado de compliance
2. Notificar gestão sobre não-conformidades
3. Criar tickets para correções necessárias

### 1.3 Auditoria Mensal (Manual)

**Período:** Primeiro dia útil do mês
**Responsável:** Gestor de Compliance + DPO
**Duração:** 2-4 horas

**Checklist:**
- [ ] Revisar relatórios semanais do mês anterior
- [ ] Verificar todas as solicitações LGPD processadas
- [ ] Validar logs de acesso a dados sensíveis
- [ ] Conferir status de consentimentos ativos
- [ ] Verificar execução das políticas de retenção
- [ ] Documentar não-conformidades encontradas
- [ ] Planejar ações corretivas

### 1.4 Auditoria Trimestral (Manual)

**Período:** Primeira semana do trimestre
**Responsável:** DPO + Equipe de TI + Jurídico
**Duração:** 1-2 dias

**Escopo:**
1. **Revisão de Processos**
   - [ ] Fluxo de registro de consentimentos
   - [ ] Processo de atendimento a solicitações LGPD
   - [ ] Procedimentos de exclusão de dados
   - [ ] Mecanismos de segurança

2. **Verificação Técnica**
   - [ ] Criptografia de documentos (AES-256)
   - [ ] URLs assinadas funcionando corretamente
   - [ ] Logs de auditoria íntegros
   - [ ] Backups funcionais

3. **Documentação**
   - [ ] Políticas atualizadas
   - [ ] Termos de uso publicados
   - [ ] Política de privacidade vigente
   - [ ] Contratos conformes

---

## 2. Procedimentos de Verificação

### 2.1 Verificação de Solicitações LGPD

**Endpoint:** `GET /api/data-requests?pending_only=true`

**Procedimento:**
1. Acessar lista de solicitações pendentes
2. Verificar dias restantes para prazo (≤ 15 dias)
3. Priorizar solicitações por urgência
4. Documentar status de cada solicitação

**Critérios de Conformidade:**
- ✅ Conforme: Todas as solicitações atendidas em até 15 dias
- ⚠️ Atenção: Solicitações com menos de 5 dias para vencer
- ❌ Não Conforme: Qualquer solicitação vencida

### 2.2 Verificação de Consentimentos

**Endpoint:** `GET /api/consents/history/{type}/{id}`

**Procedimento:**
1. Selecionar amostra de titulares (mínimo 5%)
2. Verificar histórico completo de consentimentos
3. Confirmar existência de prova de consentimento
4. Validar que revogações estão documentadas

**Critérios de Conformidade:**
- ✅ Conforme: Data, hora, fonte e IP registrados
- ❌ Não Conforme: Consentimento sem evidência

### 2.3 Verificação de Logs de Acesso

**Endpoint:** `GET /api/access-logs/report`

**Procedimento:**
1. Gerar relatório do período auditado
2. Verificar acessos a documentos sensíveis
3. Identificar padrões anômalos
4. Validar integridade dos logs

**Critérios de Conformidade:**
- ✅ Conforme: Todos os acessos registrados com IP e timestamp
- ❌ Não Conforme: Acessos sem log ou logs incompletos

### 2.4 Verificação de Políticas de Retenção

**Endpoint:** `GET /api/compliance/retention-status`

**Procedimento:**
1. Verificar status de cada tipo de documento
2. Identificar documentos expirados não arquivados
3. Confirmar execução do job de arquivamento

**Critérios de Conformidade:**
- ✅ Conforme: Nenhum documento expirado pendente
- ❌ Não Conforme: Documentos além do prazo de retenção

---

## 3. Relatórios de Conformidade

### 3.1 Dashboard de Conformidade

**Endpoint:** `GET /api/compliance/dashboard`

**Informações Disponíveis:**
- Resumo de solicitações pendentes
- Alertas ativos
- Score de conformidade atual
- Solicitações recentes

### 3.2 Relatório Completo

**Endpoint:** `GET /api/compliance/report?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`

**Seções do Relatório:**
1. Métricas de Solicitações LGPD
2. Métricas de Consentimentos
3. Métricas de Documentos
4. Auditoria de Acessos
5. Status de Retenção
6. Score de Conformidade
7. Indicadores de Risco
8. Recomendações

### 3.3 Score de Conformidade

**Endpoint:** `GET /api/compliance/score?period=month`

**Composição do Score (0-100):**

| Componente | Peso | Descrição |
|------------|------|-----------|
| Prazo LGPD | 40% | Taxa de cumprimento do prazo de 15 dias |
| Retenção | 25% | Conformidade com políticas de arquivamento |
| Auditoria | 20% | Integridade dos logs de acesso |
| Consentimentos | 15% | Registro correto de base legal |

**Classificação:**
- 90-100: Excelente
- 75-89: Bom
- 60-74: Adequado
- 40-59: Atenção
- 0-39: Crítico

---

## 4. Tratamento de Não-Conformidades

### 4.1 Severidade Crítica

**Exemplos:**
- Solicitação LGPD vencida
- Vazamento de dados
- Falha de criptografia

**Ações:**
1. Notificação imediata ao DPO
2. Criação de incidente de segurança
3. Ação corretiva em até 24 horas
4. Documentação completa do ocorrido
5. Comunicação à ANPD se necessário

### 4.2 Severidade Alta

**Exemplos:**
- Solicitação LGPD próxima do vencimento
- Score de conformidade abaixo de 60
- Falha em job de auditoria

**Ações:**
1. Notificação ao gestor responsável
2. Plano de ação em até 48 horas
3. Monitoramento intensificado
4. Relatório de correção

### 4.3 Severidade Média

**Exemplos:**
- Documentos expirados não arquivados
- Taxa de assinatura baixa
- Logs incompletos

**Ações:**
1. Registro no backlog de melhorias
2. Correção planejada para próximo sprint
3. Monitoramento regular

### 4.4 Severidade Baixa

**Exemplos:**
- Pequenos desvios de processo
- Melhorias de documentação

**Ações:**
1. Registro para revisão trimestral
2. Inclusão em plano de melhoria contínua

---

## 5. Responsabilidades

### 5.1 Encarregado de Dados (DPO)

- Supervisionar todas as auditorias
- Aprovar relatórios de conformidade
- Comunicar com ANPD quando necessário
- Definir políticas de proteção de dados

### 5.2 Gestor de Compliance

- Executar auditorias manuais
- Revisar relatórios automatizados
- Coordenar ações corretivas
- Manter documentação atualizada

### 5.3 Equipe de TI

- Manter sistemas de auditoria funcionais
- Garantir execução dos jobs agendados
- Implementar correções técnicas
- Monitorar alertas do sistema

### 5.4 Gestores de Operação

- Atender solicitações LGPD dentro do prazo
- Validar processos operacionais
- Reportar anomalias
- Participar de auditorias quando convocados

---

## 6. Documentação e Registro

### 6.1 Registros Obrigatórios

1. **Logs de Auditoria** (retenção: 5 anos)
   - Todas as verificações realizadas
   - Não-conformidades identificadas
   - Ações corretivas implementadas

2. **Relatórios de Conformidade** (retenção: 5 anos)
   - Relatórios semanais automatizados
   - Relatórios mensais de auditoria
   - Relatórios trimestrais completos

3. **Evidências de Consentimento** (retenção: 10 anos)
   - Data, hora, IP e fonte
   - Versão do termo aceito
   - Prova de revogação quando aplicável

### 6.2 Armazenamento

- Relatórios armazenados no S3 com criptografia
- Logs em banco de dados com backup diário
- Documentos sensíveis com controle de acesso

---

## 7. Revisão deste Documento

**Frequência:** Semestral ou quando houver mudança significativa na LGPD
**Responsável:** DPO
**Última Revisão:** Janeiro/2026
**Próxima Revisão:** Julho/2026

---

## Anexos

### A. Checklist de Auditoria Mensal

```
[ ] Revisar dashboard de conformidade
[ ] Verificar solicitações pendentes
[ ] Conferir score de conformidade
[ ] Validar execução de jobs agendados
[ ] Revisar indicadores de risco
[ ] Documentar não-conformidades
[ ] Definir ações corretivas
[ ] Aprovar relatório do período
```

### B. Contatos de Emergência

| Função | Nome | Contato |
|--------|------|---------|
| DPO | [A definir] | dpo@carinho.com.vc |
| TI | [A definir] | ti@carinho.com.vc |
| Jurídico | [A definir] | juridico@carinho.com.vc |

### C. Links Úteis

- Dashboard: `https://documentos.carinho.com.vc/api/compliance/dashboard`
- ANPD: https://www.gov.br/anpd/pt-br
- LGPD: http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm
