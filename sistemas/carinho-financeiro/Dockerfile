FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev dos2unix telnet \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Wrapper: bind-mount usa o entrypoint do git (nao depende de layer antiga da imagem).
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.image.sh
COPY docker-entrypoint-wrapper.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.image.sh /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.image.sh /usr/local/bin/docker-entrypoint.sh

# Copiar configuração do Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
