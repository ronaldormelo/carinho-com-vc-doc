#!/bin/bash
set -e

# Copiar .env.example para .env se .env não existir (ANTES de instalar dependências)
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Copiando .env.example para .env..."
    cp .env.example .env
fi

if [ -f .env ] && [ -f quote-dotenv.sh ]; then
    echo "Citando valores com espaço no .env (phpdotenv)..."
    tr -d '\r' < quote-dotenv.sh > /tmp/quote-dotenv.sh
    bash /tmp/quote-dotenv.sh .env
fi

# Criar diretórios necessários ANTES de executar qualquer comando do artisan
echo "Criando diretórios necessários..."
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo "Limpando cache de packages (evita Collision com composer --no-dev)..."
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php

# Ajustar permissões (permissões podem falhar sem quebrar o container)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

export COMPOSER_NO_BLOCKING="${COMPOSER_NO_BLOCKING:-1}"
export COMPOSER_NO_SECURITY_BLOCKING="${COMPOSER_NO_SECURITY_BLOCKING:-1}"
if [ ! -f vendor/autoload.php ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev
else
    echo "vendor/autoload.php presente; pulando composer install"
fi


echo "Limpando cache de packages (evita providers de --dev como Collision)..."
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php

if [ -f .env ]; then
    if [ -z "${APP_KEY:-}" ]; then
        unset APP_KEY || true
    fi
    if [ -f ensure-app-key.sh ]; then
        tr -d '\r' < ensure-app-key.sh > /tmp/ensure-app-key.sh
        bash /tmp/ensure-app-key.sh .env
    fi
    APP_KEY="$(grep -E '^APP_KEY=' .env | tail -n1 | cut -d= -f2- | tr -d '\r' | tr -d '"' | tr -d "'" | tr -d ' ')"
    if [ -n "$APP_KEY" ]; then
        export APP_KEY
    fi
fi

# Fila/scheduler: não rediscover a cada start (OOM 137). APP_KEY só se vazia (ensure-app-key.sh).
case " $* " in
  *"queue:work"*|*"schedule:run"*|*"horizon"*)
    if [ -f vendor/autoload.php ]; then
      echo "Worker/scheduler: pulando package:discover"
      exec "$@"
    fi
    ;;
esac

echo "Executando scripts do Composer..."
php artisan package:discover --ansi

# Executar comando original
exec "$@"
