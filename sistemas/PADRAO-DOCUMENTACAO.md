# Padrão de documentação dos módulos

Cada pasta em `sistemas/carinho-*` deve permitir que um operador ou um desenvolvedor responda, sem caçar em código:

1. **Para quem é** (família, atendente, RH, financeiro, integração).
2. **O que o módulo faz e o que não faz** (limites em [ARCHITECTURE.md](../ARCHITECTURE.md)).
3. **Como usar** (guia ou README com o caminho feliz e o erro comum).
4. **Segurança** (token, webhook, PII, LGPD).
5. **Velocidade** (SLO, fila, timeout, gargalo) — arquivo `docs/nao-funcionais.md`.
6. **Integração** (`docs/integracoes.md` ou seção equivalente + matriz do hub).

## Arquivos esperados

| Arquivo | Função |
|---------|--------|
| `readme.md` | Entrada do módulo: subdomínio, stack, módulos, links |
| `docs/arquitetura.md` | Componentes e dados |
| `docs/nao-funcionais.md` | Performance, segurança, limites |
| `docs/integracoes.md` | Contratos com outros sistemas (quando o módulo chama alguém) |
| [contratos-rotas.md](carinho-integracoes/docs/contratos-rotas.md) | Rotas HTTP verificadas (hub) |
| Guia operacional / de usuário | Quem opera o dia a dia |

Análises `analise-praticas-*.md` são **referência**, não política. Manuais longos não substituem a fonte de verdade financeira ou de SLA da raiz.

Stack a declarar: PHP 8.2+ / Laravel 11; banco **MariaDB 10.11 compartilhado** (driver `mysql`); Redis com prefixo próprio.

Health checks **não** são um único path. A tabela canônica está em [carinho-integracoes/docs/contratos-rotas.md](carinho-integracoes/docs/contratos-rotas.md). Não documente `/api/v1` onde o `routes/*.php` do módulo não tem esse prefixo (exceção: CRM).

Contratos HTTP: o arquivo `routes/*.php` do destino vence o cliente em `app/Integrations`. Se divergirem, deixe a divergência explícita — não invente endpoint.
