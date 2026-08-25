# syntax=docker/dockerfile:1
#
# Imagem PHP da aplicacao Feira Esquerda Livre.
# Usada por dois servicos do compose: "app" (php-fpm) e "queue" (worker).
FROM php:8.3-fpm-bookworm

# Bibliotecas de sistema exigidas pelas extensoes compiladas abaixo.
# default-mysql-client entra para permitir "docker compose exec app mysql ...".
# fonts-liberation: Liberation Sans e metricamente compativel com Arial, usada
# pelo ProductShareImageService (fora do Windows nao existe C:/Windows/Fonts).
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        fonts-liberation \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Extensoes exigidas pelo projeto:
#   pdo_mysql -> MySQL 8.4          bcmath  -> calculos de checkout/frete
#   gd        -> intervention/image  intl   -> locale pt_BR
#   zip + dom -> barryvdh/dompdf     exif   -> orientacao de imagens
#   pcntl     -> queue:work          opcache-> performance
# mbstring, fileinfo, ctype, curl, dom, json e openssl ja vem compilados na imagem base.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip

# phpredis: o Redis esta disponivel no ambiente mesmo que o projeto
# ainda use CACHE_STORE=database / QUEUE_CONNECTION=database.
RUN pecl install redis-6.2.0 && docker-php-ext-enable redis

# Composer dentro do container (nenhum Composer global no Windows e necessario).
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_HOME=/tmp/composer

COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint-app
RUN chmod +x /usr/local/bin/docker-entrypoint-app

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint-app"]
CMD ["php-fpm"]
