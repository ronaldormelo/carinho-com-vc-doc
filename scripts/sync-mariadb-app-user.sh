#!/usr/bin/env bash
# Sincroniza o usuário de aplicação do MariaDB com DB_USERNAME/DB_PASSWORD
# dos .env já copiados em sistemas/<sistema>/.env.
#
# O init.sql só roda com datadir vazio. Em deploy, o volume já existe:
# sem este passo a senha nova nos .env não entra no banco.
set -euo pipefail

DEPLOY_PATH="${1:?Informe o caminho do repositório na VPS}"
CONTAINER="${MARIADB_CONTAINER:-carinho-mariadb}"

env_value() {
  local file="$1"
  local key="$2"
  grep -E "^${key}=" "$file" | tail -n1 | cut -d= -f2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
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

for system in "${systems[@]}"; do
  file="$DEPLOY_PATH/sistemas/$system/.env"
  if [ ! -f "$file" ]; then
    echo "Arquivo .env ausente: $file" >&2
    exit 1
  fi
  user="$(env_value "$file" DB_USERNAME)"
  pass="$(env_value "$file" DB_PASSWORD)"
  if [ -z "$user" ]; then
    echo "DB_USERNAME vazio em $system" >&2
    exit 1
  fi
  if [ -z "$pass" ]; then
    echo "DB_PASSWORD vazio em $system" >&2
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
  if [ -z "$ref_user" ]; then
    ref_user="$user"
    ref_pass="$pass"
  else
    if [ "$user" != "$ref_user" ] || [ "$pass" != "$ref_pass" ]; then
      echo "DB_USERNAME/DB_PASSWORD divergentes em $system (MariaDB compartilhado exige o mesmo usuário)" >&2
      exit 1
    fi
  fi
done

if ! docker exec "$CONTAINER" mariadb-admin ping --silent >/dev/null 2>&1; then
  echo "MariaDB ($CONTAINER) não está respondendo." >&2
  exit 1
fi

echo "Sincronizando usuário de aplicação no MariaDB (senha não é exibida)"

sql="$(
  printf '%s\n' \
    "CREATE USER IF NOT EXISTS '${ref_user}'@'%' IDENTIFIED BY '${ref_pass}';" \
    "ALTER USER '${ref_user}'@'%' IDENTIFIED BY '${ref_pass}';" \
    "GRANT ALL PRIVILEGES ON carinho_*.* TO '${ref_user}'@'%';" \
    "FLUSH PRIVILEGES;"
)"

printf '%s\n' "$sql" | docker exec -i "$CONTAINER" mariadb -uroot
echo "Usuário MariaDB sincronizado"
