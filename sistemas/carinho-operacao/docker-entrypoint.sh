#!/bin/bash
set -e

# Copiar .env.example para .env se .env não existir (ANTES de instalar dependências)
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Copiando .env.example para .env..."
    cp .env.example .env
fi

# Criar diretórios necessários ANTES de executar qualquer comando do artisan
echo "Criando diretórios necessários..."
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Ajustar permissões (permissões podem falhar sem quebrar o container)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

echo "Instalando dependências do Composer..."
# Instalar sem scripts primeiro (para evitar erro do artisan)
# Timeout 0: unzip do laravel/framework em bind mount Windows ultrapassa os 300s padrão.
export COMPOSER_PROCESS_TIMEOUT=0
composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev


echo "Executando scripts do Composer..."
php artisan package:discover --ansi

current_key="${APP_KEY:-}"
if [ -z "$current_key" ] && [ -f .env ]; then
    current_key="$(grep -E '^APP_KEY=' .env | tail -n1 | cut -d= -f2- | tr -d '\r' | tr -d '"' | tr -d "'")"
fi
if [ -z "$current_key" ]; then
    echo "Gerando chave de aplicação (APP_KEY)..."
    php artisan key:generate --force --ansi
else
    echo "APP_KEY já definida; não regenerar."
fi

# Executar comando original
exec "$@"
