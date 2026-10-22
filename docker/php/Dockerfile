FROM php:8.4-cli-alpine
RUN docker-php-ext-install pdo_mysql
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/router.php"]
