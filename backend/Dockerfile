# ---- deps stage: install PHP deps with official Composer image ----
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# No scripts; we just need vendor for seeding the runtime volume once
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

# ---- app stage: PHP runtime that serves Laravel over HTTP for dev ----
FROM php:8.2-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www

# System libs + pdo_pgsql
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Make sure composer is available in the final image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy vendor from deps stage to a *seed* location (not the live vendor volume path)
# We'll copy from here into the mounted vendor volume on first boot if it's empty.
COPY --from=vendor /app/vendor /opt/vendor-seed

# Copy app (needed for first boot / when not bind-mounted)
COPY . .

EXPOSE 9000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
