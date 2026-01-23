# Guia Rapido do Atendente

Referencia rapida para consulta durante o atendimento.

---

## Fluxo Principal (Caminho Feliz)

```
1. NOVO CONTATO
   └─> Saudar cliente
   └─> Definir prioridade
   └─> Avancar para TRIAGEM

2. TRIAGEM
   └─> Perguntar: Nome, Idade, Tipo de Cuidado
   └─> Perguntar: Local, Horario, Data de Inicio
   └─> Registrar respostas no sistema
   └─> Avancar para PROPOSTA (automatico)

3. PROPOSTA
   └─> Analisar informacoes da triagem
   └─> Montar proposta adequada
   └─> Enviar por WhatsApp
   └─> Enviar por e-mail (formalizar)
   └─> Avancar para AGUARDANDO

4. AGUARDANDO
   └─> Fazer follow-up conforme prazo
   └─> Cliente aceitou? → ATIVO
   └─> Cliente recusou? → PERDIDO (registrar motivo)

5. ATIVO
   └─> Enviar contrato por e-mail
   └─> Aguardar assinatura
   └─> Agradecer e orientar proximos passos
```

---

## Tempos de Resposta (SLA)

| Prioridade | 1a Resposta | Resolucao |
|------------|-------------|-----------|
| 🔴 Urgente | 5 min | 1h |
| 🟠 Alta | 15 min | 2h |
| 🟡 Normal | 30 min | 4h |
| 🟢 Baixa | 60 min | 8h |

---

## Checklist de Triagem

### Obrigatorias ✓
1. Nome do paciente
2. Idade do paciente
3. Tipo de cuidado necessario
4. Cidade/bairro
5. Horario/turno
6. Data de inicio

### Opcionais
7. Necessidades especiais
8. Expectativa de valor
9. Quem decide
10. Como conheceu

---

## Motivos de Perda

| Codigo | Usar quando... |
|--------|----------------|
| `price` | Achou caro |
| `competitor` | Fechou com concorrente |
| `no_response` | Sumiu, nao responde |
| `no_availability` | Nao temos cuidador |
| `region` | Nao atendemos a regiao |
| `requirements` | Nao atendemos o perfil |
| `postponed` | Adiou para depois |
| `other` | Outro (especificar) |

---

## Quando Escalonar

### Para N2 (Supervisor)
- Reclamacao formal
- Pedido de desconto
- Duvida de contrato
- Nao sei resolver
- Atendimento parado

### Para N3 (Gestao)
- Emergencia
- Ameaca de processo
- Problema grave
- Crise

---

## Categorias de Incidente

| Categoria | Exemplo |
|-----------|---------|
| Reclamacao | Insatisfacao com servico |
| Atraso | Cuidador nao chegou |
| Qualidade | Servico mal feito |
| Comunicacao | Info errada |
| Cobranca | Problema pagamento |
| Cuidador | Falta, comportamento |
| Emergencia | Urgencia medica |
| Sugestao | Ideia de melhoria |

---

## Mensagens Modelo

### Saudacao Inicial
```
Ola, [Nome]! Seja bem-vindo(a) a Carinho.
Sou [Seu Nome] e vou ajuda-lo(a) a encontrar
o cuidador ideal. Como posso ajudar?
```

### Follow-up
```
Ola, [Nome]! Estou retornando sobre a proposta
que enviamos. Conseguiu analisar? Posso ajudar
com alguma duvida?
```

### Encerramento Positivo
```
Contrato enviado! Nossa equipe de operacoes
entrara em contato para agendar o inicio.
Obrigado por escolher a Carinho! 💙
```

### Encerramento Negativo
```
Obrigado pelo contato, [Nome]. Caso precise
de nos no futuro, estaremos a disposicao.
Desejamos tudo de bom! 💙
```

### Escalonamento
```
Para melhor atende-lo(a), vou transferir
para [supervisor/equipe especializada],
que entrara em contato em instantes.
```

---

## Acoes Rapidas

| Preciso... | Faco... |
|------------|---------|
| Mudar prioridade | Inbox > Conversa > Atualizar Status |
| Registrar triagem | Inbox > Conversa > Triagem > Preencher |
| Enviar proposta email | Inbox > Conversa > Enviar Proposta |
| Marcar como perdido | Inbox > Conversa > Marcar como Perdido |
| Registrar incidente | Inbox > Conversa > Registrar Incidente |
| Adicionar nota | Inbox > Conversa > Adicionar Nota |
| Escalonar | Inbox > Conversa > Escalonar |
| Ver historico | Inbox > Conversa > Historico |

---

## Horario de Atendimento

- **Seg-Sex**: 08:00 - 18:00
- **Sabado**: 08:00 - 12:00 (se habilitado)
- **Domingo/Feriado**: Fechado (msg automatica)

---

## Numeros Uteis

| Situacao | Acao |
|----------|------|
| Emergencia medica | Escalonar N3 imediatamente |
| Duvida operacional | Consultar supervisor |
| Problema no sistema | Registrar e avisar TI |

---

*Cole este guia proximo ao seu monitor para consulta rapida!*
