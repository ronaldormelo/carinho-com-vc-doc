FROM php:8.2-apache

# Instalacao de dependencias do sistema
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev libcurl4-openssl-dev \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Instalacao do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Configuracao do Apache
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Criacao de diretorios necessarios
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Copia arquivos da aplicacao
COPY . .

# Permissoes
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copia configuracao do supervisor
COPY supervisor/workers.conf /etc/supervisor/conf.d/workers.conf

EXPOSE 80

CMD ["apache2-foreground"]
