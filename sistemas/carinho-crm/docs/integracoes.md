# Integrações — CRM

O CRM é a base de leads e clientes. Não é agenda, fatura nem inbox.

## Entrada

| Origem | Função |
|--------|--------|
| Site | Lead de formulário + UTM (`CarinhoSiteService` no sentido inverso para CMS) |
| Marketing | Origem de campanha / conversão |
| Atendimento | Status de conversa, interação, perda |
| Integrações (hub) | WhatsApp→lead, eventos |
| Z-API | Mensagens automáticas do próprio CRM |

## Saída

| Destino | Função |
|---------|--------|
| Operação | Cliente/contrato para alocação |
| Financeiro | Cobrança recorrente / contrato |
| Documentos | Termo, consentimento, assinatura |
| Cuidadores | Consulta de disponibilidade (não é o banco oficial) |
| Site | Depoimentos, FAQ e páginas (`modulo-gestao-conteudo.md`) — auth `X-API-Key` |

Timeouts: 10 s nas internas, 15 s Documentos, 30 s Z-API (`config/integrations.php`). NFRs: [nao-funcionais.md](nao-funcionais.md).
