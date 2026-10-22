FROM php:8.4-cli-alpine
RUN apk add --no-cache oniguruma-dev libzip-dev && docker-php-ext-install pdo_mysql mbstring zip
RUN printf "upload_max_filesize=10M\npost_max_size=12M\n" > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/router.php"]
