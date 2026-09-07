# Modelo operacional

## Fluxo macro

Lead → atendimento → triagem → proposta → contrato → alocação → execução → feedback → renovação.

## Papéis

| Papel | Sistema | Faz |
|-------|---------|-----|
| Atendimento | carinho-atendimento | Lead, triagem, proposta |
| Operação | carinho-operacao | Match, agenda, check-in, substituição |
| Financeiro | carinho-financeiro | Cobrança e repasse |
| Suporte / emergência | Atendimento N2/N3 + Operação | Incidentes e campo |
| Documentos | carinho-documentos-lgpd | Contrato e LGPD |
| Hub | carinho-integracoes | Eventos e sync |

## Cuidador no plantão

Checklist de início (chegada); checklist de fim (atividades e ocorrências); registro simples; canal de emergência.

## Cliente no plantão

Notificação de início e fim; canal de emergência; feedback com nota.

## Políticas

Cancelamento, pagamento, comissão e reembolso: **[políticas do Financeiro](sistemas/carinho-financeiro/docs/politicas.md)**. Substituição: teto de 120 min na Operação. Escalonamento de emergência: fila `emergencies`.

## Rotinas

Confirmação diária da agenda; atraso (job a cada 5 min); reposição; comunicação só no WhatsApp oficial.

## Indicadores

SLA de atendimento; conversão; substituição (< 10% como meta da Operação); ocupação; NPS.
