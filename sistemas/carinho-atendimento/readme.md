# Carinho Atendimento

**Subdomínio:** atendimento.carinho.com.vc

## Descrição
Central de atendimento digital com gestão completa de conversas, SLA e qualidade.
Padroniza o fluxo de atendimento no WhatsApp, garante respostas dentro do SLA
definido e conecta o atendimento ao CRM e às automações do negócio.

## Módulos Essenciais

### 1. Comunicação Multicanal
- WhatsApp Business com número exclusivo
- E-mail profissional para propostas e contratos
- Inbox unificada com histórico completo

### 2. Níveis de Suporte (N1, N2, N3)
- **N1 - Atendimento:** Primeiro contato, triagem e informações básicas
- **N2 - Suporte:** Questões técnicas, propostas e negociação
- **N3 - Especialista:** Casos complexos, reclamações críticas e emergências

### 3. Gestão de SLA
- Configuração de tempos máximos por prioridade e nível de suporte
- Alertas automáticos quando SLA está em risco
- Dashboard de conversas em risco
- Métricas de cumprimento de SLA

### 4. Checklist de Triagem Padronizado
- Perguntas estruturadas para qualificação do lead
- Campos obrigatórios e opcionais configuráveis
- Cálculo automático de urgência/prioridade
- Resumo para elaboração de propostas

### 5. Scripts de Comunicação
- Biblioteca de scripts por categoria (saudação, qualificação, proposta, etc.)
- Sugestão automática baseada no status da conversa
- Variáveis dinâmicas para personalização
- Scripts por nível de suporte

### 6. Registro de Motivos de Perda
- Motivos padronizados e categorizados
- Campos de observação obrigatórios quando aplicável
- Relatórios de análise de perdas
- Integração com CRM

### 7. Auditoria e Histórico
- Registro completo de todas as ações na conversa
- Histórico de mudanças de status e prioridade
- Controle de atribuições e escalonamentos
- Rastreabilidade completa para supervisão

### 8. Escalonamento Automático
- Escalonamento manual com motivo obrigatório
- Detecção automática de conversas que precisam escalonamento
- Histórico de escalonamentos por conversa
- Distribuição automática para agentes disponíveis

### 9. Notas Internas
- Anotações privadas entre agentes
- Histórico de notas por conversa
- Integração com auditoria

### 10. Automações
- Mensagens automáticas (fora do horário, primeira resposta)
- Lembretes de atendimento
- Solicitação de feedback pós-serviço
- Sincronização com CRM

## Funil de Atendimento

```
Novo → Em Triagem → Proposta Enviada → Aguardando Resposta → Ativo
                 ↘                   ↘                    ↘
                   Perdido ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

## Prioridades

| Código | Label | Descrição |
|--------|-------|-----------|
| low | Baixa | Sem urgência definida |
| normal | Normal | Atendimento padrão |
| high | Alta | Prazo apertado |
| urgent | Urgente | Início imediato necessário |

## Integrações
- WhatsApp Business API (Z-API)
- CRM interno para leads e incidentes
- Módulo de Operação para emergências
- Sistema de e-mail para propostas e contratos
