# ---- deps stage: install PHP deps with official Composer image ----
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Install without running scripts to avoid needing app bootstrapped during build
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

# Bring in vendor from the deps stage
COPY --from=vendor /app/vendor ./vendor

# Copy the rest of your app
COPY . .

EXPOSE 9000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
