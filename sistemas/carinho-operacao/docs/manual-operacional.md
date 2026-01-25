# Manual Operacional - Carinho Operação

## Índice

1. [Visão Geral](#1-visão-geral)
2. [Processos Operacionais](#2-processos-operacionais)
3. [Fluxos de Trabalho](#3-fluxos-de-trabalho)
4. [Checklists Padronizados](#4-checklists-padronizados)
5. [Tratamento de Exceções](#5-tratamento-de-exceções)
6. [Indicadores e SLA](#6-indicadores-e-sla)
7. [Procedimentos de Emergência](#7-procedimentos-de-emergência)
8. [Comunicação](#8-comunicação)
9. [Relatórios](#9-relatórios)
10. [Glossário](#10-glossário)

---

## 1. Visão Geral

### 1.1 Objetivo do Sistema

O sistema **Carinho Operação** é responsável pela execução prática dos serviços de HomeCare, gerenciando:

- Agenda de atendimentos
- Alocação de cuidadores
- Controle de execução (check-in/check-out)
- Comunicação com clientes
- Tratamento de exceções e emergências

### 1.2 Responsabilidades da Equipe

| Papel | Responsabilidades |
|-------|-------------------|
| **Operador** | Monitorar agendamentos, processar solicitações, verificar atrasos |
| **Supervisor** | Aprovar exceções, resolver escalonamentos, analisar indicadores |
| **Coordenador** | Gestão estratégica, análise de relatórios, definição de políticas |

### 1.3 Horários de Operação

- **Operação Regular**: Segunda a Sexta, 07:00 às 22:00
- **Plantão**: Sábados, Domingos e Feriados, 08:00 às 20:00
- **Emergências**: 24 horas (via canal de emergência)

---

## 2. Processos Operacionais

### 2.1 Ciclo de Vida do Atendimento

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ SOLICITAÇÃO │───►│  ALOCAÇÃO   │───►│ AGENDAMENTO │
└─────────────┘    └─────────────┘    └─────────────┘
                                             │
┌─────────────┐    ┌─────────────┐           ▼
│  CONCLUSÃO  │◄───│  EXECUÇÃO   │◄──────────┘
└─────────────┘    └─────────────┘
```

### 2.2 Status de Solicitação

| Status | Descrição | Ação Necessária |
|--------|-----------|-----------------|
| **Open** | Nova solicitação | Processar alocação |
| **Scheduled** | Cuidador alocado | Aguardar confirmação |
| **Active** | Serviço em andamento | Monitorar execução |
| **Completed** | Serviço finalizado | Nenhuma |
| **Canceled** | Solicitação cancelada | Processar taxa se aplicável |

### 2.3 Status de Agendamento

| Status | Descrição |
|--------|-----------|
| **Planned** | Agendado, aguardando execução |
| **In Progress** | Cuidador realizou check-in |
| **Done** | Cuidador realizou check-out |
| **Missed** | Não executado (falta/cancelamento) |

---

## 3. Fluxos de Trabalho

### 3.1 Fluxo de Nova Solicitação

1. **Recebimento**
   - Sistema recebe solicitação via webhook do Atendimento
   - Validar dados obrigatórios (cliente, tipo de serviço, datas)

2. **Busca de Cuidador**
   - Sistema executa match automático
   - Critérios: habilidades (35%), disponibilidade (25%), região (20%), avaliação (20%)
   - Se score ≥ 70: alocação automática
   - Se score < 70: lista candidatos para seleção manual

3. **Alocação**
   - Cuidador é notificado da alocação
   - Aguarda confirmação em até 4 horas
   - Se não confirmar: buscar próximo candidato

4. **Criação de Agenda**
   - Após confirmação, criar agendamentos
   - Validar disponibilidade e intervalos mínimos
   - Notificar cliente sobre alocação

### 3.2 Fluxo de Execução do Atendimento

1. **Lembrete (24h antes)**
   - Sistema envia lembrete automático via WhatsApp
   - Confirmar dados do agendamento

2. **Lembrete (2h antes)**
   - Segundo lembrete enviado
   - Cuidador deve confirmar disponibilidade

3. **Check-in**
   - Cuidador registra chegada no app
   - Validação de horário (tolerância: 30min antes, 15min depois)
   - Validação de localização (raio de 500m)
   - Cliente é notificado do início

4. **Durante o Atendimento**
   - Cuidador preenche checklist de início
   - Registra atividades realizadas
   - Registra observações importantes

5. **Check-out**
   - Cuidador preenche checklist de fim
   - Registra atividades finais
   - Sistema calcula horas trabalhadas
   - Cliente é notificado do fim

### 3.3 Fluxo de Substituição

**Gatilhos:**
- Cuidador solicita afastamento
- Atraso superior a 2 horas sem justificativa
- Emergência do cuidador

**Processo:**
1. Identificar necessidade de substituição
2. Consultar banco de cuidadores backup da região
3. Se não houver backup: buscar via match normal com urgência
4. Alocar substituto e transferir agendamentos futuros
5. Notificar cliente sobre substituição
6. Registrar motivo na trilha de auditoria

---

## 4. Checklists Padronizados

### 4.1 Checklist de Início de Atendimento

| Item | Obrigatório | Observação |
|------|-------------|------------|
| Confirmar chegada ao local | ✅ | Registro de horário |
| Verificar condição do cliente | ✅ | Estado geral de saúde |
| Conferir medicações | ✅ | Lista e horários |
| Anotar necessidades especiais | ✅ | Restrições, preferências |
| Verificar segurança do ambiente | ✅ | Riscos identificados |

### 4.2 Checklist de Fim de Atendimento

| Item | Obrigatório | Observação |
|------|-------------|------------|
| Atividades planejadas concluídas | ✅ | Listar não realizadas |
| Medicações administradas | ✅ | Confirmar horários |
| Relatar ocorrências | ✅ | Mesmo se não houver |
| Cliente em condição estável | ✅ | Verificar sinais vitais se aplicável |
| Notas de passagem de plantão | ✅ | Para próximo cuidador |

### 4.3 Atividades Padrão por Tipo de Serviço

**Acompanhamento Geral:**
- Companhia e conversação
- Auxílio em atividades diárias
- Monitoramento de bem-estar
- Preparação de refeições leves

**Cuidado Especializado:**
- Administração de medicamentos
- Higiene pessoal assistida
- Mobilidade e transferências
- Monitoramento de sinais vitais

---

## 5. Tratamento de Exceções

### 5.1 Tipos de Exceção

| Tipo | Descrição | Aprovador |
|------|-----------|-----------|
| **Check-in Atrasado** | Atraso > 15 min com justificativa | Supervisor |
| **Check-out Antecipado** | Saída antes do horário previsto | Supervisor |
| **Alteração de Agendamento** | Mudança de data/horário | Operador (< 24h: Supervisor) |
| **Alocação Manual** | Bypass do match automático | Supervisor |
| **Isenção de Taxa** | Cancelamento sem cobrança | Coordenador |
| **Exceção de Política** | Outras situações | Coordenador |

### 5.2 Processo de Aprovação

1. Operador identifica necessidade de exceção
2. Registra solicitação no sistema com:
   - Tipo de exceção
   - Entidade afetada
   - Descrição detalhada
   - Justificativa
3. Sistema notifica aprovador
4. Aprovador analisa e decide:
   - **Aprovar**: Registra motivo, ação executada
   - **Rejeitar**: Registra motivo, orienta próximos passos
5. Sistema registra na trilha de auditoria

### 5.3 Prazos de Resposta

| Severidade | Prazo |
|------------|-------|
| Urgente | 30 minutos |
| Normal | 4 horas |
| Baixa | 24 horas |

---

## 6. Indicadores e SLA

### 6.1 Indicadores Chave (KPIs)

| Indicador | Meta | Cálculo |
|-----------|------|---------|
| **Pontualidade Check-in** | ≥ 95% | Check-ins no horário / Total |
| **Taxa de Substituição** | ≤ 10% | Substituições / Total alocações |
| **Taxa de Cancelamento** | ≤ 10% | Cancelamentos / Total agendamentos |
| **Tempo Resposta Emergência** | ≤ 30 min | Média de tempo até resolução |
| **Sucesso de Notificações** | ≥ 98% | Notificações enviadas / Total |
| **Taxa de Ocupação** | ≥ 60% | Horas trabalhadas / Capacidade |

### 6.2 Monitoramento de SLA

**Dashboard em Tempo Real:**
- Acessar via `/api/sla/realtime`
- Atualização a cada 5 minutos
- Alertas automáticos para violações

**Alertas:**
| Severidade | Condição | Ação |
|------------|----------|------|
| **Info** | Variação < 15% do target | Monitorar |
| **Warning** | Variação 15-30% do target | Investigar |
| **Critical** | Variação > 30% do target | Ação imediata |

### 6.3 Relatórios Periódicos

| Relatório | Frequência | Responsável |
|-----------|------------|-------------|
| Diário | Todo dia 08:00 | Sistema (automático) |
| Semanal | Segunda 09:00 | Supervisor |
| Mensal | 1º dia útil | Coordenador |

---

## 7. Procedimentos de Emergência

### 7.1 Classificação de Emergências

| Severidade | Descrição | Tempo Resposta |
|------------|-----------|----------------|
| **Low** | Situação não crítica, pode aguardar | 60 minutos |
| **Medium** | Requer atenção, mas não imediata | 30 minutos |
| **High** | Situação séria, ação necessária | 15 minutos |
| **Critical** | Risco à saúde/segurança, ação imediata | 5 minutos |

### 7.2 Tipos Comuns de Emergência

- **Emergência médica**: Cliente com sintomas graves
- **Queda do paciente**: Cliente sofreu queda
- **Erro de medicação**: Dosagem incorreta administrada
- **Mudança comportamental**: Alteração súbita de comportamento
- **Falha de equipamento**: Equipamento médico com defeito
- **Cuidador indisponível**: Ausência sem aviso
- **Preocupação com segurança**: Situação de risco no local

### 7.3 Procedimento de Escalonamento

1. **Registro**: Cuidador ou operador registra emergência
2. **Classificação**: Sistema atribui severidade inicial
3. **Notificação**: 
   - Low/Medium: Operador de plantão
   - High: Supervisor + Operador
   - Critical: Coordenador + Supervisor + Email de emergência
4. **Acompanhamento**: Status atualizado a cada ação
5. **Escalonamento automático**: Se não resolvido no tempo limite
6. **Resolução**: Registro de ações tomadas e resultado

### 7.4 Contatos de Emergência

| Situação | Contato |
|----------|---------|
| Emergência operacional | emergencia@carinho.com.vc |
| Emergência médica | SAMU 192 |
| Supervisão | supervisor@carinho.com.vc |

---

## 8. Comunicação

### 8.1 Canais de Comunicação

| Canal | Uso | Prioridade |
|-------|-----|------------|
| **WhatsApp** | Notificações para cliente | Principal |
| **Email** | Confirmações, documentos | Secundário |
| **Push** | Lembretes no app | Complementar |

### 8.2 Templates de Mensagem

**Início de Serviço:**
```
Olá [NOME_CLIENTE]! 
O cuidador [NOME_CUIDADOR] acabou de chegar para o atendimento.
Horário: [HORA_CHECKIN]
Qualquer dúvida, estamos à disposição.
- Equipe Carinho com Você
```

**Fim de Serviço:**
```
Olá [NOME_CLIENTE]!
O atendimento de hoje foi finalizado.
Horário: [HORA_CHECKOUT]
Agradecemos a confiança!
- Equipe Carinho com Você
```

**Substituição:**
```
Olá [NOME_CLIENTE]!
Informamos que houve uma alteração no seu atendimento.
O cuidador [NOME_ANTERIOR] foi substituído por [NOME_NOVO].
Motivo: [MOTIVO]
Estamos à disposição para esclarecimentos.
- Equipe Carinho com Você
```

### 8.3 Boas Práticas de Comunicação

- Sempre usar tom empático e profissional
- Manter cliente informado proativamente
- Responder dúvidas em até 4 horas
- Escalar para supervisor se cliente insatisfeito

---

## 9. Relatórios

### 9.1 Relatório Diário

**Conteúdo:**
- Total de agendamentos do dia
- Agendamentos concluídos vs cancelados
- Atrasos identificados
- Substituições realizadas
- Emergências registradas
- Notificações enviadas

**Acesso:** `GET /api/reports/daily?date=YYYY-MM-DD`

### 9.2 Relatório Semanal

**Conteúdo:**
- Resumo da semana
- Tendências (melhorando/piorando/estável)
- Performance por cuidador
- Análise de exceções
- Compliance de SLA

**Acesso:** `GET /api/reports/weekly?week_start=YYYY-MM-DD`

### 9.3 Relatório Mensal

**Conteúdo:**
- Resumo do mês
- Performance por região
- Top 10 cuidadores
- Análise de substituições
- Análise de emergências
- Recomendações de melhoria

**Acesso:** `GET /api/reports/monthly?month=YYYY-MM`

---

## 10. Glossário

| Termo | Definição |
|-------|-----------|
| **Alocação** | Atribuição de cuidador a uma solicitação de serviço |
| **Assignment** | Registro de alocação no sistema |
| **Backup** | Cuidador reserva para substituições rápidas |
| **Check-in** | Registro de chegada do cuidador ao local |
| **Check-out** | Registro de saída do cuidador |
| **Checklist** | Lista de verificação padronizada |
| **Match** | Processo de encontrar cuidador adequado |
| **Schedule** | Agendamento de atendimento |
| **Service Request** | Solicitação de serviço |
| **SLA** | Acordo de Nível de Serviço (metas operacionais) |
| **Substituição** | Troca de cuidador alocado |

---

## Histórico de Revisões

| Versão | Data | Alteração |
|--------|------|-----------|
| 1.0 | Jan 2026 | Versão inicial |

---

**Documento mantido pela Equipe de Operações**  
**Última atualização:** Janeiro 2026
