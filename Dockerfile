# Production image for PaaS (Railway, Fly.io, тощо). Локальна розробка — compose.yaml + docker/php/Dockerfile.
# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --legacy-peer-deps
COPY . .
RUN npm run build

FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl opcache pcntl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 8080
ENV PORT=8080

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
