FROM php:8.2-apache

# Instalacao de dependencias do sistema
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev libcurl4-openssl-dev dos2unix telnet \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Instalacao do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar e configurar entrypoint ANTES do WORKDIR para garantir que não seja sobrescrito pelo volume
COPY docker-entrypoint.sh /tmp/docker-entrypoint.sh
RUN chmod +x /tmp/docker-entrypoint.sh && \
    sed -i 's/\r$//' /tmp/docker-entrypoint.sh && \
    dos2unix /tmp/docker-entrypoint.sh 2>/dev/null || true && \
    mv /tmp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

# Configuracao do Apache
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copia configuracao do supervisor
COPY supervisor/workers.conf /etc/supervisor/conf.d/workers.conf

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
