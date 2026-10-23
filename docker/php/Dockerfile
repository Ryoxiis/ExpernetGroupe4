FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-progress
COPY . .
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/expernet.ini
COPY docker/php/entrypoint.sh /usr/local/bin/expernet-entrypoint
COPY docker/fixtures/guide-architecture.txt /var/www/html/storage/pedagogy/guide-architecture.txt
RUN chmod +x /usr/local/bin/expernet-entrypoint \
    && php -r 'foreach (["pdo_mysql","mbstring","zip","sodium","fileinfo"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR,"Extension manquante : $extension\n"); exit(1); } }'

ENTRYPOINT ["expernet-entrypoint"]
CMD ["apache2-foreground"]
