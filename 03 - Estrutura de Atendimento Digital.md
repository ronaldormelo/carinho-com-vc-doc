# Estrutura de atendimento digital

Objetivo: padronizar o WhatsApp, reduzir improviso e registrar tudo no CRM.

## Canais

- WhatsApp Business (principal) via Z-API — sistemas Atendimento e Integrações.
- E-mail do domínio para proposta e contrato.
- Site com CTA e formulários.

## Fluxo

1. Recepção e urgência  
2. Entendimento (perguntas-chave)  
3. Qualificação (serviço, horário, perfil)  
4. Proposta (hora, diária, mensal)  
5. Encaminhamento (match na Operação)  
6. Contrato digital (Documentos)  
7. Pós-serviço (feedback)

Dados mínimos: responsável e telefone; cidade/bairro; tipo de serviço; horários; perfil e condições; preferências de cuidador; urgência.

Checklist operacional: [guia do atendente](sistemas/carinho-atendimento/docs/guia-rapido-atendente.md).

## Automações

Fora do horário; primeira resposta; confirmação de pedido; lembrete de agenda; pedido de feedback.

## Status da conversa

Novo lead → em triagem → proposta enviada → aguardando → contrato/ativo → perdido.

Códigos no Atendimento: `new`, `triage`, `proposal`, `waiting`, `active`, `lost`, `closed`.

## Comunicação

Clara, humana, sem jargão médico. Tom: [00](00%20-%20Identidade%20da%20Marca.md).

## SLA

| Prioridade | 1ª resposta | Resolução |
|------------|-------------|-----------|
| Urgente | 5 min | 1 h |
| Alta | 15 min | 2 h |
| Normal | 30 min | 4 h |
| Baixa | 60 min | 8 h |

Horário comercial padrão: 08:00–18:00 (`America/Sao_Paulo`). Casos urgentes têm prioridade máxima; emergência em campo escala para a Operação.

## Qualidade

Revisão semanal; motivo de perda obrigatório; scripts ajustados com NPS e incidentes.
