# Como contribuir

Este repositório mistura **documentação de produto** na raiz e **sistemas Laravel** em `sistemas/`. Mudanças devem preservar os contratos já publicados (políticas financeiras, SLA, limites de módulo).

## Documentação

1. Leia [ARCHITECTURE.md](ARCHITECTURE.md#fontes-de-verdade) antes de reescrever uma regra de negócio.
2. Siga [sistemas/PADRAO-DOCUMENTACAO.md](sistemas/PADRAO-DOCUMENTACAO.md) ao alterar um módulo.
3. Português do Brasil no texto. Termos técnicos já usados em inglês (Laravel, Redis, webhook, SLA) permanecem.
4. Não invente endpoint, fila, variável ou sistema que não exista no código.
5. Não cole segredos, dumps nem `.env` real.
6. Se corrigir uma contradição, aponte a fonte canônica e atualize os outros arquivos no mesmo PR.
7. Análises de mercado (`docs/analise-praticas-*.md`) não são contrato operacional: deixe isso explícito no topo do arquivo.

## Código (sistemas)

1. Branch por alteração; commits pequenos.
2. Padrão Laravel (Pint onde o módulo já usa).
3. Testes do módulo quando a pasta `tests/` existir (`php artisan test`).
4. Novas integrações passam pelo hub ou pelos clientes já existentes em `app/Integrations` / `app/Services/Integrations`.
5. Timeouts, tokens e URLs só via `.env` / `config/integrations.php`.

## Revisão

Checklist mínimo: links internos abrem; SLA e política de cancelamento não divergem da fonte de verdade; [PERFORMANCE.md](PERFORMANCE.md) e o `docs/nao-funcionais.md` do módulo continuam coerentes.
