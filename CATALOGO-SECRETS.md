# Catálogo de segredos e variáveis

Lista do que cada sistema **precisa** para funcionar. Os arquivos `.env.example` deixam a maior parte vazia de propósito: isso não é um inventário de “produção quebrada”, é o modelo para preencher **fora do Git**.

Nunca commite valores reais. Geração de `APP_KEY`: `php artisan key:generate` em cada app.

Tokens internos: string aleatória, mínimo 32 caracteres, **distinta** por destino. O nome da variável muda entre módulos (`INTERNAL_API_TOKEN`, `CRM_TOKEN`, `CARINHO_CRM_API_KEY`): o que importa é o par URL + chave no `config/integrations.php` de cada lado.

## Todos os sistemas

| Variável | Função |
|----------|--------|
| `APP_KEY` | Criptografia Laravel |
| `APP_DEBUG` | `false` em produção |
| `DB_PASSWORD` | Senha do schema `carinho_*` |
| `INTERNAL_API_TOKEN` (ou equivalente) | API entre módulos |

Redis: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PREFIX` (prefixo único por app no Redis compartilhado).

## WhatsApp (Z-API)

Presente em Site, Marketing, Atendimento, CRM, Cuidadores, Operação, Financeiro, Documentos e Integrações.

- `ZAPI_INSTANCE_ID`, `ZAPI_TOKEN`, `ZAPI_CLIENT_TOKEN`
- `ZAPI_WEBHOOK_SECRET` (validação de entrada)
- Timeouts: `ZAPI_TIMEOUT`, `ZAPI_CONNECT_TIMEOUT` quando o `.env.example` do módulo declarar

## Integrações internas

Cada app declara só os destinos que chama. Exemplos de nomes já usados no repositório: `CRM_TOKEN`, `OPERACAO_TOKEN`, `CUIDADORES_TOKEN`, `ATENDIMENTO_TOKEN`, `FINANCEIRO_TOKEN`, `DOCUMENTOS_TOKEN`, `INTEGRACOES_TOKEN`, `SITE_TOKEN`, `MARKETING_TOKEN`, e o conjunto `CARINHO_*_API_KEY` no Site, CRM e Integrações.

Preencha os pares que o `config/integrations.php` do módulo lista. Não invente destino.

## Por sistema (além do comum)

**Documentos:** `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` (e endpoint se S3-compatible).

**Financeiro:** `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`. Placeholders `sk_test_...` no example devem ser trocados. NFS-e (`NFSE_*`) está marcado como futuro no README — não tratar como ativo.

**Marketing:** `META_*`, `GOOGLE_ADS_*`, `GA_*`, `GTM_CONTAINER_ID`, `INSTAGRAM_BUSINESS_ACCOUNT_ID`.

**Site:** `GA4_MEASUREMENT_ID`, `GTM_CONTAINER_ID`, `GMB_PLACE_ID`, `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`.

**CRM:** `WEBHOOK_SECRET`; AWS S3 opcional.

**E-mail (quando o módulo envia):** `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

Valores de desenvolvimento do Compose da raiz (usuário de banco criado em `mysql/init/init.sql`) **não** são credenciais de produção. Infra compartilhada: [`.env.example`](.env.example) na raiz.
