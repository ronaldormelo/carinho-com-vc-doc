# Manual Operacional - Central de Atendimento Carinho

Este manual descreve os procedimentos padrao para atendimento ao cliente,
seguindo as melhores praticas definidas pelo sistema.

---

## Sumario

1. [Visao Geral do Atendimento](#1-visao-geral-do-atendimento)
2. [Recebendo um Novo Contato](#2-recebendo-um-novo-contato)
3. [Realizando a Triagem](#3-realizando-a-triagem)
4. [Elaborando a Proposta](#4-elaborando-a-proposta)
5. [Acompanhando o Cliente](#5-acompanhando-o-cliente)
6. [Encerrando o Atendimento](#6-encerrando-o-atendimento)
7. [Registrando Incidentes](#7-registrando-incidentes)
8. [Escalonamento entre Niveis](#8-escalonamento-entre-niveis)
9. [Boas Praticas de Comunicacao](#9-boas-praticas-de-comunicacao)
10. [Perguntas Frequentes](#10-perguntas-frequentes)

---

## 1. Visao Geral do Atendimento

### Niveis de Suporte

| Nivel | Responsavel | Atua em |
|-------|-------------|---------|
| N1 | Atendente | Primeiro contato, triagem, duvidas simples, propostas |
| N2 | Supervisor | Reclamacoes, casos complexos, excecoes, negociacoes |
| N3 | Gestao | Emergencias, crises, decisoes estrategicas |

### Funil de Atendimento

```
NOVO → TRIAGEM → PROPOSTA → AGUARDANDO → ATIVO
                    ↓
                 PERDIDO ← (registrar motivo)
```

### Horario de Atendimento

- Segunda a Sexta: 08:00 as 18:00
- Sabados (se configurado): 08:00 as 12:00
- Domingos e Feriados: Sem atendimento (mensagem automatica)

### Metas de Tempo de Resposta (SLA)

| Prioridade | Primeira Resposta | Resolucao |
|------------|-------------------|-----------|
| Urgente | 5 minutos | 1 hora |
| Alta | 15 minutos | 2 horas |
| Normal | 30 minutos | 4 horas |
| Baixa | 60 minutos | 8 horas |

---

## 2. Recebendo um Novo Contato

### Passo 1: Identificar a Mensagem

Quando uma nova mensagem chega:
1. O sistema cria automaticamente uma conversa com status **NOVO**
2. O sistema envia a mensagem automatica de primeira resposta
3. Acesse a inbox e localize a conversa

### Passo 2: Cumprimentar o Cliente

Inicie com uma saudacao acolhedora:

```
Ola, [Nome]! Seja bem-vindo(a) a Carinho.
Meu nome e [Seu Nome] e vou ajuda-lo(a) a encontrar
o cuidador ideal para sua familia.

Como posso ajudar voce hoje?
```

### Passo 3: Avaliar a Prioridade

Identifique rapidamente se ha urgencia:

| Situacao | Prioridade | Acao |
|----------|------------|------|
| Inicio imediato (hoje/amanha) | **Urgente** | Priorizar atendimento |
| Inicio em ate 1 semana | **Alta** | Agilizar triagem |
| Inicio em 2+ semanas | **Normal** | Seguir fluxo padrao |
| Apenas pesquisando | **Baixa** | Triagem basica |

**Como definir a prioridade no sistema:**
1. Acesse a conversa
2. Clique em "Atualizar Status"
3. Selecione a prioridade adequada

---

## 3. Realizando a Triagem

A triagem e o momento de coletar todas as informacoes necessarias
para elaborar uma proposta adequada.

### Passo 1: Iniciar a Triagem

Avance o status da conversa para **TRIAGEM**:
1. Acesse a conversa
2. Clique em "Atualizar Status"
3. Selecione "Triagem"

### Passo 2: Seguir o Script de Perguntas

Colete as informacoes na ordem recomendada. Use o checklist do sistema.

#### Perguntas Obrigatorias (devem ser respondidas)

**1. Nome do Paciente**
```
Para comecarmos, poderia me informar o nome completo
da pessoa que recebera os cuidados?
```
> Dica: Confirme a escrita correta do nome.

**2. Idade do Paciente**
```
Qual a idade do(a) [Nome do Paciente]?
```
> Dica: Importante para definir perfil do cuidador.

**3. Tipo de Cuidado**
```
Que tipo de cuidado o(a) [Nome] precisa?
Por exemplo: companhia, auxilio com higiene, 
administracao de medicamentos, acompanhamento em consultas...
```
> Dica: Detalhe as atividades esperadas do cuidador.

**4. Local do Atendimento**
```
Em qual cidade e bairro sera realizado o atendimento?
```
> Dica: Verificar se atendemos a regiao antes de continuar.

**5. Horario/Turno**
```
Qual o horario ou turno de preferencia?
- Manha (6h-12h)
- Tarde (12h-18h)
- Noite (18h-22h)
- Pernoite (22h-6h)
- Integral (24h)
```
> Dica: Pergunte tambem sobre finais de semana.

**6. Data de Inicio**
```
Para quando precisa iniciar o servico?
```
> Dica: Urgencia influencia disponibilidade e preco.

#### Perguntas Opcionais (importantes para qualificacao)

**7. Necessidades Especiais**
```
O(A) [Nome] possui alguma condicao de saude ou 
necessidade especial que devemos considerar?
Por exemplo: Alzheimer, mobilidade reduzida, 
uso de sondas, oxigenio...
```

**8. Expectativa de Valor**
```
Voce ja tem uma expectativa de investimento mensal?
Isso nos ajuda a indicar opcoes adequadas.
```
> Dica: Nao pressione, deixe o cliente confortavel.

**9. Decisor**
```
Alem de voce, mais alguem participa da decisao
sobre a contratacao?
```
> Dica: Ajuda a entender o processo de decisao.

**10. Como nos Conheceu**
```
Por curiosidade, como voce conheceu a Carinho?
```
> Dica: Informacao importante para marketing.

### Passo 3: Registrar as Respostas

Apos cada resposta do cliente:
1. Acesse "Triagem" na conversa
2. Preencha o campo correspondente
3. Salve a resposta

O sistema mostra o progresso da triagem (ex: 4/6 obrigatorias).

### Passo 4: Verificar Completude

Quando todas as perguntas obrigatorias estiverem respondidas:
- O sistema avanca automaticamente para **PROPOSTA**
- Voce recebe uma notificacao

**Se nao conseguir completar a triagem:**
- Registre uma nota interna com o motivo
- Agende retorno com o cliente

---

## 4. Elaborando a Proposta

### Passo 1: Analisar as Informacoes

Com base na triagem, identifique:
- Perfil do cuidador necessario (experiencia, habilidades)
- Carga horaria semanal
- Faixa de valor adequada
- Disponibilidade na regiao

### Passo 2: Preparar a Proposta

Monte a proposta considerando:

| Item | Detalhamento |
|------|--------------|
| Perfil do cuidador | Experiencia minima, formacao |
| Horarios | Dias e turnos de trabalho |
| Atividades | O que o cuidador fara |
| Valor | Mensal ou por hora, o que inclui |
| Condicoes | Forma de pagamento, reajustes |

### Passo 3: Enviar por WhatsApp

Apresente a proposta de forma clara:

```
[Nome], com base no que conversamos, preparei 
uma proposta para o cuidado do(a) [Paciente]:

📋 PROPOSTA CARINHO

👤 Cuidador(a) com experiencia em [tipo de cuidado]
📅 [Dias da semana], das [horario]
💰 Valor: R$ [valor]/mes

O que esta incluso:
✓ [Atividade 1]
✓ [Atividade 2]
✓ [Atividade 3]

Posso esclarecer alguma duvida sobre a proposta?
```

### Passo 4: Enviar Proposta Formal por E-mail

Para formalizar:
1. Acesse a conversa
2. Clique em "Enviar Proposta por E-mail"
3. Confirme o e-mail do cliente
4. O sistema envia o documento formatado

### Passo 5: Atualizar Status

Apos enviar a proposta:
1. Atualize o status para **AGUARDANDO**
2. Defina uma data para follow-up
3. Adicione etiqueta "proposta-enviada"

---

## 5. Acompanhando o Cliente

### Regra de Follow-up

| Situacao | Tempo para Retorno |
|----------|-------------------|
| Proposta urgente | 4 horas |
| Proposta alta prioridade | 24 horas |
| Proposta normal | 48 horas |
| Proposta baixa prioridade | 72 horas |

### Passo 1: Verificar Pendencias

Antes de entrar em contato:
1. Revise o historico da conversa
2. Verifique anotacoes internas
3. Confirme ultima proposta enviada

### Passo 2: Retomar Contato

Mensagem de follow-up:

```
Ola, [Nome]! Tudo bem?

Estou entrando em contato sobre a proposta que 
enviamos para o cuidado do(a) [Paciente].

Conseguiu analisar? Posso esclarecer alguma duvida 
ou ajustar algum ponto?
```

### Passo 3: Registrar o Retorno

Apos o contato:
1. Adicione nota interna com o resultado
2. Atualize a prioridade se necessario
3. Agende proximo follow-up

### Cenarios de Resposta

**Cliente quer contratar:**
1. Atualize status para **ATIVO**
2. Inicie processo de contratacao
3. Envie contrato por e-mail
4. Sincronize com CRM

**Cliente pediu ajuste na proposta:**
1. Registre os ajustes solicitados
2. Elabore nova proposta
3. Mantenha status em **AGUARDANDO**

**Cliente nao decidiu ainda:**
1. Registre nota com situacao
2. Agende novo follow-up
3. Mantenha status em **AGUARDANDO**

**Cliente desistiu:**
1. Siga o processo de encerramento (secao 6)

---

## 6. Encerrando o Atendimento

### 6.1 Encerramento com Sucesso (ATIVO)

Quando o cliente contrata:

**Passo 1:** Confirmar a contratacao
```
Que otimo, [Nome]! Fico muito feliz em ajudar
sua familia. Vou encaminhar seu contrato agora.
```

**Passo 2:** Enviar contrato
1. Clique em "Enviar Contrato por E-mail"
2. Confirme os dados do cliente
3. Aguarde assinatura digital

**Passo 3:** Atualizar status
1. Atualize para **ATIVO**
2. Adicione etiqueta "contratado"
3. O sistema sincroniza com CRM e Operacao

**Passo 4:** Agradecer e orientar
```
Contrato enviado! Apos a assinatura, nossa equipe
de operacoes entrara em contato para agendar
o inicio do servico.

Qualquer duvida, estou a disposicao.
Obrigado por escolher a Carinho! 💙
```

### 6.2 Encerramento por Perda (PERDIDO)

Quando o cliente nao contrata, e **obrigatorio** registrar o motivo.

**Passo 1:** Identificar o motivo real

Pergunte com empatia:
```
Entendo, [Nome]. Para melhorarmos nosso atendimento,
poderia me contar o que pesou na sua decisao?
```

**Passo 2:** Registrar no sistema

1. Clique em "Marcar como Perdido"
2. Selecione o motivo principal:

| Codigo | Motivo | Quando usar |
|--------|--------|-------------|
| price | Preco acima do orcamento | Cliente achou caro |
| competitor | Escolheu concorrente | Foi para outra empresa |
| no_response | Sem retorno do cliente | Cliente sumiu |
| no_availability | Sem cuidador disponivel | Nao conseguimos atender |
| region | Regiao nao atendida | Fora da area de cobertura |
| requirements | Requisitos nao atendidos | Nao temos perfil adequado |
| postponed | Cliente adiou decisao | Vai contratar depois |
| other | Outro motivo | Especificar nas notas |

3. Adicione notas com detalhes
4. Confirme o encerramento

**Passo 3:** Despedir-se profissionalmente
```
Obrigado pelo contato, [Nome]. 
Caso mude de ideia ou precise de nos no futuro,
estaremos a disposicao.

Desejamos tudo de bom para voce e sua familia! 💙
```

### 6.3 Encerramento Neutro (FECHADO)

Para atendimentos que nao sao vendas (duvidas, informacoes):

1. Atualize status para **FECHADO**
2. Adicione etiqueta apropriada ("duvida-respondida", "informacao")
3. O sistema enviara pesquisa de satisfacao

---

## 7. Registrando Incidentes

Use o registro de incidentes para documentar problemas que
precisam de atencao especial.

### Quando Registrar

| Categoria | Exemplos |
|-----------|----------|
| Reclamacao | Cliente insatisfeito com atendimento anterior |
| Atraso | Demora na resposta, cuidador atrasou |
| Qualidade | Problema com servico prestado |
| Comunicacao | Informacao errada, falha de contato |
| Cobranca | Duvida ou problema com pagamento |
| Cuidador | Problema com profissional alocado |
| Emergencia | Situacao critica de saude/seguranca |
| Sugestao | Ideia de melhoria do cliente |

### Passo a Passo

**Passo 1:** Acesse a conversa do cliente

**Passo 2:** Clique em "Registrar Incidente"

**Passo 3:** Preencha os campos:
- **Severidade**: Baixa, Media, Alta ou Critica
- **Categoria**: Selecione a mais adequada
- **Descricao**: Detalhe o ocorrido

**Passo 4:** Confirme o registro

**Importante:**
- Incidentes **Alta** e **Critica** notificam automaticamente a supervisao
- **Emergencias** acionam a equipe de operacoes imediatamente

### Exemplo de Registro

```
Severidade: Alta
Categoria: Reclamacao
Descricao: Cliente relata que cuidadora Maria nao compareceu
no dia 15/01 sem aviso previo. Familia ficou sem cobertura
por 4 horas ate conseguir substituto. Cliente muito irritado,
solicita desconto na mensalidade.
```

---

## 8. Escalonamento entre Niveis

### Quando Escalonar para N2 (Supervisor)

- Cliente faz reclamacao formal
- Solicitacao de desconto ou excecao
- Duvida sobre contrato ou termos
- Situacao que voce nao sabe resolver
- Atendimento sem resposta por muito tempo

### Quando Escalonar para N3 (Gestao)

- Emergencia medica ou de seguranca
- Cliente ameaca processo ou midia
- Problema grave com cuidador
- Decisao que impacta outros clientes

### Passo a Passo

**Passo 1:** Registre nota interna explicando a situacao

**Passo 2:** Clique em "Escalonar"

**Passo 3:** Informe o motivo do escalonamento

**Passo 4:** Comunique ao cliente
```
[Nome], para melhor atende-lo(a), vou transferir
seu atendimento para [meu supervisor / nossa equipe especializada],
que podera ajuda-lo(a) com mais propriedade.

Em instantes [ele(a) / eles] entrara(ao) em contato.
```

### Escalonamento Automatico

O sistema escalona automaticamente quando:
- N1 sem resposta por mais de 15 minutos
- N2 sem resposta por mais de 30 minutos

---

## 9. Boas Praticas de Comunicacao

### Tom de Voz Carinho

| Sempre | Evitar |
|--------|--------|
| Acolhedor e empático | Frio ou robotico |
| Claro e objetivo | Termos tecnicos |
| Respeitoso | Informal demais |
| Paciente | Apressado |

### Estrutura das Mensagens

1. **Saudacao** - Use o nome do cliente
2. **Contexto** - Retome o assunto se necessario
3. **Conteudo** - Informacao clara e organizada
4. **Fechamento** - Proximos passos ou pergunta

### Emojis (com moderacao)

| Emoji | Uso |
|-------|-----|
| 💙 | Despedida, agradecimento |
| ✓ | Listas de itens |
| 📋 | Documentos, propostas |
| 👤 | Referencias a pessoas |
| 📅 | Datas, agendamentos |

### O que NUNCA fazer

❌ Deixar cliente sem resposta por mais de 30 min (prioridade normal)
❌ Prometer algo que nao podemos cumprir
❌ Discutir com o cliente
❌ Compartilhar dados de outros clientes
❌ Usar girias ou linguagem muito informal
❌ Enviar audios longos sem necessidade

---

## 10. Perguntas Frequentes

### Operacionais

**P: O que fazer se o cliente enviar mensagem fora do horario?**
R: O sistema envia resposta automatica. Na manha seguinte, priorize os
contatos recebidos fora do horario.

**P: Como saber se estou dentro do SLA?**
R: O sistema mostra alertas quando o tempo de resposta esta proximo
do limite. Fique atento a conversas com indicador amarelo (risco) ou
vermelho (violado).

**P: Posso atender mais de uma conversa ao mesmo tempo?**
R: Sim, mas garanta que o tempo de resposta seja respeitado em todas.
Priorize por urgencia e ordem de chegada.

**P: O que fazer se nao souber responder uma duvida?**
R: Registre nota interna, escalone para N2 ou informe ao cliente que
vai verificar e retorna em breve. Nunca invente uma resposta.

### Sobre o Sistema

**P: O cliente ve as notas internas?**
R: Nao. Notas internas sao visiveis apenas para a equipe.

**P: Posso editar uma mensagem enviada?**
R: Nao. Por isso, revise antes de enviar.

**P: O que acontece quando marco como perdido?**
R: A conversa e encerrada e o motivo e registrado para analise.
O cliente pode voltar a nos procurar futuramente.

**P: Como adicionar um feriado local?**
R: Solicite a supervisao, que tem acesso para cadastrar feriados.

---

## Checklist Rapido do Atendente

### Inicio do Turno
- [ ] Verificar conversas pendentes da noite/dia anterior
- [ ] Checar conversas com SLA em risco
- [ ] Revisar follow-ups agendados para hoje

### Durante o Atendimento
- [ ] Responder dentro do SLA
- [ ] Completar triagem antes de fazer proposta
- [ ] Registrar notas importantes
- [ ] Adicionar etiquetas relevantes

### Fim do Turno
- [ ] Verificar se ha conversas sem resposta
- [ ] Registrar notas de pendencias para proximo turno
- [ ] Confirmar follow-ups do dia seguinte

---

## Glossario

| Termo | Significado |
|-------|-------------|
| SLA | Tempo maximo para resposta/resolucao |
| Funil | Etapas do atendimento ate conversao |
| Triagem | Coleta de informacoes para proposta |
| Escalonamento | Passar para nivel superior |
| NPS | Nota de satisfacao do cliente (1-5) |
| Follow-up | Retorno/acompanhamento |
| Inbox | Caixa de entrada de conversas |

---

*Documento atualizado em Janeiro/2026*
*Versao 1.0 - Central de Atendimento Carinho*
