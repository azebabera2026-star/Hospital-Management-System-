# ─────────────────────────────────────────────────────────────────────────────
# MediCore HMS — Dockerfile (PHP 8.2-FPM)
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    composer \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        opcache

# Copy custom PHP and nginx configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/hms.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# App
WORKDIR /var/www/html
COPY . .
# Run composer install if composer.json exists
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Remove XAMPP / dev artefacts that should not be in the image
RUN rm -rf .git .env.example docker *.md *.pdf *.png

# Ensure writable session/cache dirs
RUN mkdir -p /tmp/hms_rate_limits \
    && chown -R www-data:www-data /var/www/html /tmp/hms_rate_limits

USER www-data

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
