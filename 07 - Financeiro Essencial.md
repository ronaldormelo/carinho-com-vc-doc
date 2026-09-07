# Financeiro essencial

Objetivo: caixa, preço e repasse em dia. Sistema: [carinho-financeiro](sistemas/carinho-financeiro/readme.md). Detalhe normativo: [docs/politicas.md](sistemas/carinho-financeiro/docs/politicas.md).

## Princípios

- Conta PJ; separação PF × PJ.
- Registro de entradas e saídas com auditoria.
- Cobrança **sempre adiantada** (pagamento 24 h antes do serviço, padrão do módulo).

## Modelos

Hora; pacote; mensalidade. Precificação e adicionais (noturno, fim de semana, feriado) no Financeiro.

## Pagamento e repasse

Meios: Pix, boleto e cartão via **Stripe**. Repasse semanal às sextas, mínimo R$ 50, liberação 3 dias após a conclusão (política vigente). Comissões-padrão: horista 70%, diário 72%, mensal 75% para o cuidador, com bônus de avaliação e tempo de casa.

## Fiscal

Estrutura para NFS-e está preparada; emissão efetiva depende de `NFSE_*` (ainda futuro no README). Conciliação mensal no próprio módulo.

## Indicadores

Fluxo de caixa; ticket e margem (mínima 25%, alvo 30%); inadimplência; receita recorrente; PCLD.

Não usar ERP externo como sistema oficial: o módulo Laravel é o controle.
