# Carinho com Você

Repositório da plataforma digital de home care **Carinho com Você** (`carinho.com.vc`).

A empresa conecta famílias a cuidadores qualificados com atendimento pelo WhatsApp, contrato digital e operação rastreável. Este repositório descreve a identidade do negócio e os nove sistemas Laravel que executam o fluxo ponta a ponta.

**Fluxo comercial:** lead → triagem → proposta → contrato → alocação → execução → feedback → renovação.

---

## Para quem é cada documento

| Você precisa… | Comece por |
|---------------|------------|
| Entender a marca, a proposta e o cliente ideal | [00 — Identidade da marca](00%20-%20Identidade%20da%20Marca.md) e [01 — Proposta de valor](01%20-%20Proposta%20de%20Valor%20Central.md) |
| Operar atendimento, prazos e o que o cliente vê | [03 — Atendimento](03%20-%20Estrutura%20de%20Atendimento%20Digital.md), [PERFORMANCE.md](PERFORMANCE.md) e o guia do módulo |
| Cobrar, reembolsar ou repassar | [Políticas financeiras](sistemas/carinho-financeiro/docs/politicas.md) (fonte de verdade comercial) |
| Subir o ambiente local | [README.DOCKER.md](README.DOCKER.md) |
| Tratar dados, tokens e incidentes de segurança | [SECURITY.md](SECURITY.md) e [CATALOGO-SECRETS.md](CATALOGO-SECRETS.md) |
| Integrar um sistema a outro | [ARCHITECTURE.md](ARCHITECTURE.md) e [matriz de integrações](sistemas/carinho-integracoes/docs/matriz-integracoes.md) |
| Diagnosticar fila, WhatsApp ou sync | [RUNBOOK.md](RUNBOOK.md) |

Glossário de termos: [GLOSSARIO.md](GLOSSARIO.md). Como contribuir: [CONTRIBUTING.md](CONTRIBUTING.md).

---

## O que o cliente percebe

O canal principal é o **WhatsApp**. O site explica o serviço e captura o lead; o fechamento, a confirmação de agenda, o check-in e o pós-serviço acontecem no mesmo número oficial.

Promessas que a operação precisa cumprir (detalhadas em [PERFORMANCE.md](PERFORMANCE.md)):

- Primeira resposta humana em até **5 minutos** no horário comercial para casos urgentes (meta também usada como SLA comercial no site).
- Emergências operacionais: crítico **15 min**, alto **30 min**, médio **2 h** (políticas do site).
- Pagamento **adiantado**; reembolso conforme a tabela do Financeiro, não conforme defaults isolados de um módulo.
- Substituição de cuidador com busca limitada a **120 minutos** no módulo Operação.

Horário comercial padrão do atendimento: **08:00–18:00**, `America/Sao_Paulo` (`config/atendimento.php`). Fora desse horário vale mensagem automática; o SLA humano não corre da mesma forma.

---

## Sistemas

Índice detalhado: [sistemas/readme.md](sistemas/readme.md). Padrão de documentação por módulo: [sistemas/PADRAO-DOCUMENTACAO.md](sistemas/PADRAO-DOCUMENTACAO.md).

| Sistema | Subdomínio | Porta local | Papel |
|---------|------------|-------------|--------|
| [Site](sistemas/carinho-site/readme.md) | site.carinho.com.vc | 8084 | Presença pública, leads, páginas legais |
| [Marketing](sistemas/carinho-marketing/readme.md) | marketing.carinho.com.vc | 8086 | Campanhas, UTM, redes, biblioteca de marca |
| [Atendimento](sistemas/carinho-atendimento/readme.md) | atendimento.carinho.com.vc | 8080 | Inbox WhatsApp, funil, SLA |
| [CRM](sistemas/carinho-crm/readme.md) | crm.carinho.com.vc | 8085 | Leads, clientes, pipeline, contratos |
| [Cuidadores](sistemas/carinho-cuidadores/readme.md) | cuidadores.carinho.com.vc | 8081 | Cadastro, triagem, banco pesquisável |
| [Operação](sistemas/carinho-operacao/readme.md) | operacao.carinho.com.vc | 8083 | Agenda, match, check-in, substituição |
| [Financeiro](sistemas/carinho-financeiro/readme.md) | financeiro.carinho.com.vc | 8087 | Fatura, Stripe, repasse, políticas |
| [Documentos e LGPD](sistemas/carinho-documentos-lgpd/readme.md) | documentos.carinho.com.vc | 8082 | Contratos, consentimento, S3 |
| [Integrações](sistemas/carinho-integracoes/readme.md) | integracoes.carinho.com.vc | 8088 | Hub de eventos, Z-API, sync, DLQ |

Stack comum: **PHP 8.2+ / Laravel 11**, **Redis 7**, filas (Horizon onde documentado). Banco compartilhado no Docker da raiz: **MariaDB 10.11** (protocolo MySQL; os `.env` usam `DB_CONNECTION=mysql`).

---

## Documentos de negócio (raiz)

Estes arquivos são o contrato de produto. Quando divergirem de um README de módulo, prevalece a fonte de verdade indicada em [ARCHITECTURE.md](ARCHITECTURE.md#fontes-de-verdade).

| Arquivo | Conteúdo |
|---------|----------|
| [00 - Identidade da Marca.md](00%20-%20Identidade%20da%20Marca.md) | Nome, tom, paleta implementada, tipografia |
| [01 - Proposta de Valor Central.md](01%20-%20Proposta%20de%20Valor%20Central.md) | Serviços, ICP, KPIs, fluxo ponta a ponta |
| [02 - Estratégia de Aquisição de Clientes.md](02%20-%20Estratégia%20de%20Aquisição%20de%20Clientes.md) | Canais, UTM, SLA de lead |
| [03 - Estrutura de Atendimento Digital.md](03%20-%20Estrutura%20de%20Atendimento%20Digital.md) | Funil WhatsApp, dados mínimos, SLA |
| [04 - Gestão de Cuidadores.md](04%20-%20Gestão%20de%20Cuidadores.md) | Pipeline do profissional |
| [05 - Gestão de Clientes.md](05%20-%20Gestão%20de%20Clientes.md) | Cadastro, plano de cuidado, LGPD |
| [06 - Definição do Modelo Operacional.md](06%20-%20Definição%20do%20Modelo%20Operacional.md) | Papéis, check-in, políticas |
| [07 - Financeiro Essencial.md](07%20-%20Financeiro%20Essencial.md) | Cobrança, Pix/boleto/cartão, indicadores |
| [08 - Jurídico Essencial.md](08%20-%20Jurídico%20Essencial.md) | Contratos, LGPD, logs |
| [09 - Ferramentas Digitais.md](09%20-%20Ferramentas%20Digitais.md) | Mapa da stack real (não ferramentas de rascunho) |

---

## Segurança e velocidade

- Ameaças, LGPD, tokens e o que nunca deve ir para o Git: [SECURITY.md](SECURITY.md).
- Catálogo de variáveis secretas por sistema: [CATALOGO-SECRETS.md](CATALOGO-SECRETS.md).
- Latência, filas, timeouts e gargalos por módulo: [PERFORMANCE.md](PERFORMANCE.md).
- Visão de componentes e integrações: [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Infraestrutura local

MariaDB e Redis compartilhados estão em [`docker-compose.yml`](docker-compose.yml) na raiz. Cada sistema tem o próprio `docker-compose.yml` em `sistemas/<nome>/`.

Passo a passo: [README.DOCKER.md](README.DOCKER.md). Exemplo de variáveis da infra compartilhada: [`.env.example`](.env.example).

O diretório `mysql/init/` cria os nove bancos na **primeira** inicialização do volume. A pasta `logos/` é reserva para o kit de marca; os tokens oficiais de cor e fonte estão em `config/branding.php` de cada sistema.
