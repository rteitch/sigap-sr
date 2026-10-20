# ==============================================================================
# Stage 1: Build Frontend Assets (Vite, Vue 3, Tailwind v4)
# ==============================================================================
FROM node:22-alpine AS frontend-builder
WORKDIR /app

# Cache package installation
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit

# Copy sources required by Vite build
COPY vite.config.js ./
COPY resources resources/
COPY public public/

# Compile production assets to /app/public/build
RUN npm run build

# ==============================================================================
# Stage 2: Install PHP Composer Dependencies
# ==============================================================================
FROM composer:2 AS composer-builder
WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-scripts

# Copy code needed for autoload classmap generation
COPY app app/
COPY bootstrap bootstrap/
COPY config config/
COPY database database/
COPY routes routes/

RUN composer dump-autoload --optimize --classmap-authoritative --ignore-platform-reqs --no-scripts

# ==============================================================================
# Stage 3: Production Runtime (PHP 8.3 FPM + Nginx + Supervisord on Alpine)
# ==============================================================================
FROM php:8.3-fpm-alpine AS runtime

LABEL maintainer="Rizal Taufiq Hidayat <https://github.com/rteitch>"
LABEL description="SIGAP-SR Production Container"

# Install system dependencies & libraries for PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    sqlite-libs \
    oniguruma \
    icu-libs \
    tzdata \
    && apk add --no-cache --virtual .build-deps \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        sqlite-dev \
        oniguruma-dev \
        icu-dev \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
    && echo "Asia/Jakarta" > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        opcache \
        pcntl \
        bcmath \
        intl \
    && apk del --no-cache .build-deps \
    && rm -rf /var/cache/apk/* /tmp/*

WORKDIR /var/www/html

# Copy server and process manager configurations
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-sigap.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy application source code
COPY . .

# Copy optimized PHP vendor from composer-builder
COPY --from=composer-builder /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from frontend-builder
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Discover production packages (clean host dev artifacts)
RUN rm -f /var/www/html/bootstrap/cache/*.php && \
    php artisan package:discover --ansi

# Prepare storage & framework cache directories with proper permissions
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/www/html/database \
             /var/log/supervisor \
             /run/nginx \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -f http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
