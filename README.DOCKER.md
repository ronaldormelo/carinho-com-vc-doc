# Docker — Carinho com Você

MariaDB e Redis **compartilhados** na raiz; um container de aplicação (e o Redis prefixado) por sistema.

Arquivo da raiz: **`docker-compose.yml`** (não existe `docker-compose.mysql.yml`).

## 1. Infra compartilhada

Na raiz do repositório:

```bash
docker compose up -d
```

Sobe:

- `carinho-mariadb` (MariaDB 10.11) — porta `3306`
- `carinho-redis` (Redis 7) — porta `6379`
- rede `carinho-network`

Na **primeira** criação do datadir, o entrypoint executa `mysql/init/init.sql` (montado em `/docker-entrypoint-initdb.d`). Isso cria os schemas `carinho_*` e o usuário de desenvolvimento definido nesse script.

O datadir padrão do Compose é o bind mount `/var/lib/mariadb-data` no host. Apagar o volume nomeado `carinho-mariadb-data` **não** apaga esse bind. Root vazio (`MARIADB_ALLOW_EMPTY_ROOT_PASSWORD`) vale só para máquina local — [SECURITY.md](SECURITY.md).

## 2. Cada sistema

```bash
cd sistemas/carinho-atendimento
docker compose up -d
```

Repita para: `carinho-cuidadores`, `carinho-documentos-lgpd`, `carinho-operacao`, `carinho-site`, `carinho-crm`, `carinho-marketing`, `carinho-financeiro`, `carinho-integracoes`.

O app precisa da rede `carinho-network` (já referenciada nos Compose dos módulos) e de `DB_HOST=carinho-mariadb`.

## Portas

| Sistema | Porta do app | Prefixo Redis |
|---------|--------------|---------------|
| Atendimento | 8080 | `carinho_atendimento:` |
| Cuidadores | 8081 | `carinho_cuidadores:` |
| Documentos | 8082 | `carinho_documentos_lgpd:` |
| Operação | 8083 | `carinho_operacao:` |
| Site | 8084 | `carinho_site:` |
| CRM | 8085 | `carinho_crm:` |
| Marketing | 8086 | `carinho_marketing:` |
| Financeiro | 8087 | `carinho_financeiro:` |
| Integrações | 8088 | `carinho_integracoes:` |
| MariaDB | 3306 | — |
| Redis | 6379 | — |

## `.env` do app

```env
DB_HOST=carinho-mariadb
DB_PORT=3306
DB_DATABASE=carinho_[nome_do_schema]
DB_CONNECTION=mysql

REDIS_HOST=carinho-redis
REDIS_PORT=6379
REDIS_PREFIX=carinho_[nome_do_sistema]:
```

Schemas criados pelo init: `carinho_atendimento`, `carinho_cuidadores`, `carinho_documentos_lgpd`, `carinho_operacao`, `carinho_site`, `carinho_crm`, `carinho_marketing`, `carinho_financeiro`, `carinho_integracoes`.

Segredos: [CATALOGO-SECRETS.md](CATALOGO-SECRETS.md). Composer: o `docker-entrypoint.sh` de cada app instala dependências **só na primeira subida** (`vendor/autoload.php` ausente). `APP_KEY` é gerada por `ensure-app-key.sh` **somente se estiver vazia** — nunca `key:generate --force`. Workers (`queue:work`) pulam `package:discover` no start. Os Compose forçam esse script do bind-mount (a layer da imagem ainda pode estar antiga até um `docker compose build`).

`composer.json` de cada app tem `config.audit.block-insecure: false`. Isso é **workaround local/CI para Composer 2.10** (Laravel 11.x é EOL e o audit bloqueia o install). **Não** é política de produção: em produção o lock deve instalar sem desligar o bloqueio, ou os apps devem sair do 11.x. Preferir `COMPOSER_NO_SECURITY_BLOCKING=1` no ambiente de build quando possível.

O seed `ChangeMeLocal!` (`CRM_ADMIN_PASSWORD`) vale **somente** para `DevLocalSeeder` / README de desenvolvimento (`administrador@carinho.com.vc`). **Inaceitável em produção.** Não commitar `.env`.

Se o `.env` local usa `DB_USERNAME=root` e `DB_PASSWORD=` (MariaDB com root vazio neste repo), **não** declare `DB_PASSWORD: ${DB_PASSWORD:-carinho}` no Compose: o `:-` trata senha vazia como ausente, injeta `carinho` e o login quebra com `1045 using password: YES`. O CRM não sobrescreve usuário/senha no Compose — lê o `.env` montado.

## Comandos

```bash
docker compose logs -f app          # dentro da pasta do sistema
docker compose down                 # para o sistema
# na raiz:
docker compose down                 # para MariaDB e Redis

docker exec -it carinho-[sistema]-app bash
docker exec -it carinho-[sistema]-app php artisan migrate:status
docker exec -it carinho-[sistema]-app composer install
```

Rebuild: `docker compose up -d --build --force-recreate --remove-orphans` na pasta do sistema. `up -d` sozinho não recria container já existente nem aplica `.env` novo.

## Problemas frequentes

**App não conecta no banco.** `docker ps` deve mostrar `carinho-mariadb`; `docker network ls` deve mostrar `carinho-network`; o serviço do app deve declarar essa network externa.

**Init SQL não rodou.** Scripts em `/docker-entrypoint-initdb.d` só executam com datadir **vazio**. Se o bind `/var/lib/mariadb-data` já existia, crie os schemas à mão a partir de `mysql/init/init.sql`.

**Porta ocupada.** Altere `APP_PORT` no `.env` do módulo.

**Redis.** Um Redis compartilhado; o prefixo `REDIS_PREFIX` evita colisão de chaves entre apps.
