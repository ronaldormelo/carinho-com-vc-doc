# Arquitetura

## Visao geral
Sistema operacional de alocacao e execucao do servico
(operacao.carinho.com.vc). Orquestra agenda, match cliente-cuidador,
checklists e comunicacao de status.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis

## Componentes principais
- Agenda compartilhada e escalas.
- Motor de match por perfil e disponibilidade.
- Check-in/out e checklists de inicio e fim.
- Registro de atividades e ocorrencias.
- Notificacoes de status para clientes.

## Integracoes
- CRM: dados de cliente e contrato.
- Cuidadores: disponibilidade e perfil.
- Atendimento: detalhes da demanda e urgencia.
- Financeiro: dados para cobranca e repasse.

## Dados e armazenamento
- Agendamentos, escalas e alocacoes.
- Checklists e registros de execucao.
- Ocorrencias e historico de substituicoes.

## Seguranca e LGPD
- Controle de acesso por papel (operacao x supervisor).
- Registro de auditoria de alteracoes de agenda.
- Retencao de dados operacionais conforme politica.

## Escalabilidade e desempenho
- Filas para notificacoes e check-in/out.
- Cache de disponibilidade e agenda.
- Indexes por data, cuidador e cliente.

## Observabilidade e operacao
- Logs de eventos operacionais.
- Alertas para atrasos, faltas e falta de cuidador.

## Backup e resiliencia
- Backup diario de banco.
- Reprocessamento de eventos com falha.
