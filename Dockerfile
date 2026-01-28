# ============================================
# Stage 1: Composer Dependencies
# ============================================
FROM php:8.4-cli AS composer-builder

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libzip-dev libxml2-dev \
    libjpeg62-turbo-dev libfreetype6-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install mbstring exif pcntl bcmath gd zip pdo_mysql

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# ============================================
# Stage 2: Frontend Build
# ============================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

# ============================================
# Stage 3: Runtime
# ============================================
FROM phpswoole/swoole:6.0-php8.4 AS runtime

ARG user=tentacle
ARG uid=1000

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libzip-dev libxml2-dev \
    libjpeg62-turbo-dev libfreetype6-dev \
    zip unzip ca-certificates default-mysql-client supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install mbstring exif pcntl bcmath gd zip pdo_mysql

RUN useradd -G www-data,root -u $uid -d /home/$user $user && \
    mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www/html

COPY --from=composer-builder --chown=$user:$user /app/vendor ./vendor
COPY --chown=$user:$user . .
COPY --from=frontend-builder --chown=$user:$user /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache && \
    chown -R $user:$user storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

COPY docker/supervisord/supervisord.conf /etc/supervisord.conf
COPY --chown=$user:$user docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER $user
EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
