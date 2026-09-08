#!/usr/bin/env bash
set -euo pipefail
sed -i 's/\r$//' scripts/ensure-app-key.sh
chmod +x scripts/ensure-app-key.sh
tmp="$(mktemp)"
printf '%s\n' 'APP_ENV=production' 'APP_KEY=' 'APP_DEBUG=false' > "$tmp"
bash scripts/ensure-app-key.sh "$tmp"
key="$(grep -E '^APP_KEY=' "$tmp" | cut -d= -f2-)"
case "$key" in
  base64:*) ;;
  *) echo "APP_KEY inválida" >&2; exit 1 ;;
esac
if [ "${#key}" -lt 20 ]; then
  echo "APP_KEY curta demais" >&2
  exit 1
fi
bash scripts/ensure-app-key.sh "$tmp"
key2="$(grep -E '^APP_KEY=' "$tmp" | cut -d= -f2-)"
if [ "$key" != "$key2" ]; then
  echo "APP_KEY foi regenerada indevidamente" >&2
  exit 1
fi
echo "PASS ensure-app-key"
