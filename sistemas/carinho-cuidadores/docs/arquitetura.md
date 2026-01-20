# Arquitetura

## Visao geral
Sistema de recrutamento e gestao de cuidadores
(cuidadores.carinho.com.vc). Padroniza cadastro, validacao, contratos
e comunicacao, garantindo base confiavel e escalavel.

## Stack
- Linguagem: PHP
- Framework: Laravel
- Banco de dados: MySQL
- Cache e filas: Redis
- Storage seguro para documentos

## Componentes principais
- Portal de cadastro e triagem.
- Upload e validacao de documentos.
- Classificacao por tipo de cuidado, regiao e disponibilidade.
- Banco pesquisavel com filtros e status.
- Avaliacao pos-servico e registro de ocorrencias.

## Integracoes
- Documentos/LGPD: armazenamento e assinatura.
- Operacao: disponibilidade e alocacao.
- CRM: historico de atendimento e performance.
- Atendimento: comunicados e orientacoes.

## Dados e armazenamento
- Dados pessoais, experiencia e formacao.
- Disponibilidade, regioes e preferencias.
- Documentos e contratos digitais.
- Avaliacoes e ocorrencias.

## Seguranca e LGPD
- Controle de acesso restrito a RH/operacao.
- Criptografia em repouso para documentos.
- Antivirus e validacao de arquivos.
- Logs de acesso e downloads.
- Retencao e descarte seguro conforme LGPD.

## Escalabilidade e desempenho
- Indexes por regiao, disponibilidade e tipo de cuidado.
- Cache de consultas frequentes.
- Jobs assinc. para validacao documental.

## Observabilidade e operacao
- Logs de status de cadastro e validacao.
- Alertas para falhas de upload e documentos pendentes.

## Backup e resiliencia
- Backup diario de banco e storage.
- Versao e historico de documentos.
