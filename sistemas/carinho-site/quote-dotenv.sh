#!/usr/bin/env bash
# phpdotenv (Laravel) rejeita valor com espaço se não estiver entre aspas.
# Uso: quote-dotenv-unquoted-whitespace.sh arquivo.env
set -euo pipefail

if [ "$#" -lt 1 ]; then
  echo "uso: $0 arquivo.env" >&2
  exit 1
fi

file=$1
if [ ! -f "$file" ]; then
  echo "arquivo não encontrado: $file" >&2
  exit 1
fi

tmp="$(mktemp)"
cleanup() { rm -f "$tmp"; }
trap cleanup EXIT

awk '
function is_quoted(s) {
  c = substr(s, 1, 1)
  return (c == "\"" || c == sprintf("%c", 39))
}
{
  raw = $0
  sub(/\r$/, "", raw)
  if (raw ~ /^[[:space:]]*$/ || raw ~ /^[[:space:]]*#/) {
    print raw
    next
  }
  work = raw
  prefix = ""
  if (match(work, /^export[[:space:]]+/)) {
    prefix = substr(work, 1, RLENGTH)
    work = substr(work, RLENGTH + 1)
  }
  eq = index(work, "=")
  if (eq == 0) {
    print raw
    next
  }
  key = substr(work, 1, eq - 1)
  val = substr(work, eq + 1)
  if (val == "" || is_quoted(val) || val !~ /[[:space:]]/) {
    print raw
    next
  }
  gsub(/\\/, "\\\\", val)
  gsub(/"/, "\\\"", val)
  print prefix key "=\"" val "\""
}
' "$file" > "$tmp"

mv "$tmp" "$file"
trap - EXIT
