#!/usr/bin/env bash
# Gera APP_KEY no .env sem bootstrap do Laravel (artisan precisa da chave).
# Uso: ensure-app-key.sh [arquivo.env]
set -euo pipefail

file="${1:-.env}"
if [ ! -f "$file" ]; then
  echo "arquivo não encontrado: $file" >&2
  exit 1
fi

read_key() {
  grep -E '^APP_KEY=' "$file" | tail -n1 | cut -d= -f2- | tr -d '\r' | tr -d '"' | tr -d "'" | tr -d ' '
}

current="$(read_key || true)"
if [ "${#current}" -ge 20 ]; then
  exit 0
fi

if command -v openssl >/dev/null 2>&1; then
  generated="base64:$(openssl rand -base64 32 | tr -d '\n')"
elif command -v php >/dev/null 2>&1; then
  generated="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
else
  echo "openssl ou php é necessário para gerar APP_KEY" >&2
  exit 1
fi

if grep -qE '^APP_KEY=' "$file"; then
  sed -i "s#^APP_KEY=.*#APP_KEY=${generated}#" "$file"
else
  printf '\nAPP_KEY=%s\n' "$generated" >> "$file"
fi

echo "APP_KEY gerada e gravada em $(basename "$file") (valor não exibido)."
