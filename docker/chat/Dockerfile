FROM php:8.4-cli-alpine
RUN apk add --no-cache oniguruma-dev libzip-dev && docker-php-ext-install pdo_mysql mbstring zip
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist
COPY . .
CMD ["php", "bin/chat-server.php"]
