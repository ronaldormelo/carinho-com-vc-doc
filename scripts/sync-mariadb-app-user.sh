#!/usr/bin/env bash
# Garante schemas e o usuário de aplicação no MariaDB já existente.
# Lê DB_DATABASE/DB_USERNAME/DB_PASSWORD dos .env já copiados em sistemas/<sistema>/.env.
#
# O init.sql só roda com datadir vazio. Em deploy o volume já existe:
# sem este passo os bancos e a senha nova não entram no MariaDB.
set -euo pipefail

DEPLOY_PATH="${1:?Informe o caminho do repositório na VPS}"
CONTAINER="${MARIADB_CONTAINER:-carinho-mariadb}"

env_value() {
  local file="$1"
  local key="$2"
  grep -E "^${key}=" "$file" | tail -n1 | cut -d= -f2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

add_unique() {
  local candidate="$1"
  local existing
  for existing in "${databases[@]+"${databases[@]}"}"; do
    if [ "$existing" = "$candidate" ]; then
      return 0
    fi
  done
  databases+=("$candidate")
}

systems=(
  carinho-site
  carinho-marketing
  carinho-atendimento
  carinho-crm
  carinho-cuidadores
  carinho-operacao
  carinho-financeiro
  carinho-documentos-lgpd
  carinho-integracoes
)

ref_user=""
ref_pass=""
databases=()

for system in "${systems[@]}"; do
  file="$DEPLOY_PATH/sistemas/$system/.env"
  if [ ! -f "$file" ]; then
    echo "Arquivo .env ausente: $file" >&2
    exit 1
  fi
  user="$(env_value "$file" DB_USERNAME)"
  pass="$(env_value "$file" DB_PASSWORD)"
  db="$(env_value "$file" DB_DATABASE)"
  if [ -z "$user" ]; then
    echo "DB_USERNAME vazio em $system" >&2
    exit 1
  fi
  if [ -z "$pass" ]; then
    echo "DB_PASSWORD vazio em $system" >&2
    exit 1
  fi
  if [ -z "$db" ]; then
    echo "DB_DATABASE vazio em $system" >&2
    exit 1
  fi
  if [[ ! "$user" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "DB_USERNAME inválido em $system" >&2
    exit 1
  fi
  if [[ ! "$pass" =~ ^[A-Za-z0-9._-]+$ ]]; then
    echo "DB_PASSWORD contém caracteres incompatíveis com o sync SQL em $system" >&2
    exit 1
  fi
  if [[ ! "$db" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "DB_DATABASE inválido em $system" >&2
    exit 1
  fi
  if [ -z "$ref_user" ]; then
    ref_user="$user"
    ref_pass="$pass"
  else
    if [ "$user" != "$ref_user" ] || [ "$pass" != "$ref_pass" ]; then
      echo "DB_USERNAME/DB_PASSWORD divergentes em $system (MariaDB compartilhado exige o mesmo usuário)" >&2
      exit 1
    fi
  fi
  add_unique "$db"
done

if [ "${#databases[@]}" -eq 0 ]; then
  echo "Nenhum DB_DATABASE encontrado." >&2
  exit 1
fi

if ! docker exec "$CONTAINER" mariadb-admin ping --silent >/dev/null 2>&1; then
  echo "MariaDB ($CONTAINER) não está respondendo." >&2
  exit 1
fi

if ! docker exec "$CONTAINER" mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1; then
  echo "MariaDB aceitou ping, mas ainda não executa SQL como root." >&2
  exit 1
fi

echo "Sincronizando schemas e usuário de aplicação no MariaDB (senha não é exibida)"

sql_lines=(
  "CREATE USER IF NOT EXISTS '${ref_user}'@'%' IDENTIFIED BY '${ref_pass}';"
  "ALTER USER '${ref_user}'@'%' IDENTIFIED BY '${ref_pass}';"
)
for db in "${databases[@]}"; do
  sql_lines+=(
    "CREATE DATABASE IF NOT EXISTS ${db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    "GRANT ALL PRIVILEGES ON ${db}.* TO '${ref_user}'@'%';"
  )
done
sql_lines+=("FLUSH PRIVILEGES;")

sql="$(printf '%s\n' "${sql_lines[@]}")"

printf '%s\n' "$sql" | docker exec -i "$CONTAINER" mariadb -uroot
echo "Schemas e usuário MariaDB sincronizados"
