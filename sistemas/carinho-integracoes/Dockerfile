FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev \
    supervisor cron \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Configura Apache
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Cria diretorios necessarios
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html/storage

# Copia configuracao do supervisor
COPY supervisor/workers.conf /etc/supervisor/conf.d/carinho-integracoes.conf

# Comando para iniciar Apache e Supervisor
CMD ["sh", "-c", "service supervisor start && apache2-foreground"]
