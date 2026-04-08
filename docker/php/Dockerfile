FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl pcntl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY docker/php/docker-entrypoint.sh /usr/local/bin/app-docker-entrypoint.sh
RUN chmod +x /usr/local/bin/app-docker-entrypoint.sh

ENTRYPOINT ["app-docker-entrypoint.sh"]
CMD ["php-fpm"]
