#!/usr/bin/env bash
set -euo pipefail
sed -i 's/\r$//' scripts/quote-dotenv-unquoted-whitespace.sh
chmod +x scripts/quote-dotenv-unquoted-whitespace.sh
tmp="$(mktemp)"
printf '%s\n' \
  'APP_ENV=production' \
  'BRAND_NAME=Carinho com Você' \
  'BRAND_SIGNATURE_NAME=Equipe Carinho' \
  'BRAND_NAME_OK="Carinho com Você"' \
  'EMPTY=' \
  'NO_SPACE=ok' \
  > "$tmp"
bash scripts/quote-dotenv-unquoted-whitespace.sh "$tmp"
echo "--- result ---"
cat "$tmp"
grep -qx 'BRAND_NAME="Carinho com Você"' "$tmp"
grep -qx 'BRAND_SIGNATURE_NAME="Equipe Carinho"' "$tmp"
grep -qx 'BRAND_NAME_OK="Carinho com Você"' "$tmp"
grep -qx 'NO_SPACE=ok' "$tmp"
grep -qx 'APP_ENV=production' "$tmp"
echo PASS
