#!/bin/bash
set -e

# Copiar .env.example para .env se .env não existir (ANTES de instalar dependências)
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Copiando .env.example para .env..."
    cp .env.example .env
fi

echo "Instalando dependências do Composer..."
# Instalar sem scripts primeiro (para evitar erro do artisan)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts


echo "Executando scripts do Composer..."
php artisan package:discover --ansi

echo "Gerando chave de aplicação (APP_KEY)..."
php artisan key:generate --force --ansi


# Criar diretórios necessários se não existirem
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Ajustar permissões (permissões podem falhar sem quebrar o container)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Executar comando original
exec "$@"
