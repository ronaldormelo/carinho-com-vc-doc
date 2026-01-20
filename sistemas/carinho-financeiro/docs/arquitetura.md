# Arquitetura

## Visao geral
Sistema financeiro de cobranca e repasses
(financeiro.carinho.com.vc). Controla entradas, saidas, notas e margens
com rastreabilidade e conformidade.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Contas a receber e a pagar.
- Precificacao por hora, pacote e mensalidade.
- Calculo de comissao e repasse do cuidador.
- Emissao de nota fiscal e conciliacao.
- Relatorios de fluxo de caixa e margem.

## Integracoes
- CRM: contratos e valores acordados.
- Operacao: dados de servicos executados.
- Documentos: notas e comprovantes.
- Gateway de pagamento (futuro), se necessario.

## Dados e armazenamento
- Lancamentos financeiros, recebimentos e repasses.
- Tabelas de precos e regras de comissao.
- Documentos fiscais e comprovantes.

## Seguranca e LGPD
- Controle de acesso restrito (financeiro/admin).
- Criptografia de dados sensiveis.
- Auditoria de alteracoes em lancamentos.
- Idempotencia em processamentos de pagamento.

## Escalabilidade e desempenho
- Jobs assinc. para conciliacao e relatorios.
- Cache de consultas recorrentes.
- Indexes por periodo e cliente.

## Observabilidade e operacao
- Logs de processamento de pagamentos.
- Alertas para falhas de emissao e inconsistencias.

## Backup e resiliencia
- Backup diario de banco e documentos fiscais.
- Politica de retencao e reconcilizacao mensal.
