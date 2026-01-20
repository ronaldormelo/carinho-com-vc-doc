# Arquitetura

## Visao geral
Base unica de leads e clientes (crm.carinho.com.vc). Centraliza o
pipeline comercial, historico de atendimento e contratos digitais.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Pipeline lead -> atendimento -> contrato -> ativo.
- Cadastro estruturado de clientes e condicoes especiais.
- Registro de origem do lead e historico de interacoes.
- Modulo de tarefas e follow-up.
- Relatorios de conversao, ticket medio e SLA.

## Integracoes
- Site e Marketing: criacao de lead com UTM.
- Atendimento: atualizacao de status e historico.
- Operacao: repasse de dados para alocacao.
- Financeiro: dados de contrato e cobranca.
- Documentos: aceite digital e contratos.

## Dados e armazenamento
- Leads, clientes, contratos e propostas.
- Historico de contatos e anotacoes.
- Indicadores e motivos de perda.

## Seguranca e LGPD
- Controle de acesso por perfil e equipes.
- Consentimento e base legal registrados.
- Criptografia de campos sensiveis.
- Auditoria de alteracoes (quem, quando, o que).
- Exportacao e exclusao de dados sob solicitacao.

## Escalabilidade e desempenho
- Indexes em campos de busca (telefone, status, cidade).
- Cache de listas e dashboards.
- Jobs assinc. para relatorios e exportacoes.

## Observabilidade e operacao
- Logs estruturados e trilhas de auditoria.
- Alertas para falhas de integracao e backlog.

## Backup e resiliencia
- Backup diario e teste de restore.
- Retencao de backups conforme politica LGPD.
