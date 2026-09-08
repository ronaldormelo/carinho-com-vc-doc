#!/bin/bash
set -e
# O bind-mount ./:/var/www/html traz o entrypoint do git. A imagem só precisa deste wrapper uma vez.
if [ -f /var/www/html/docker-entrypoint.sh ]; then
  tr -d '\r' < /var/www/html/docker-entrypoint.sh > /tmp/docker-entrypoint.host.sh
  exec /bin/bash /tmp/docker-entrypoint.host.sh "$@"
fi
exec /usr/local/bin/docker-entrypoint.image.sh "$@"
