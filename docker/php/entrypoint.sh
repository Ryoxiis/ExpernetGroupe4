#!/bin/sh
set -eu

if [ "$1" = "apache2-foreground" ]; then
    php -r 'if (strlen(getenv("APP_KEY") ?: "") < 32) { fwrite(STDERR,"APP_KEY manque : exécuter php bin/setup-docker.php sur le poste hôte.\n"); exit(1); }'
    mkdir -p /var/www/html/storage/pedagogy /var/www/html/storage/chat
    chown -R www-data:www-data /var/www/html/storage
    php bin/portal.php migrate
fi

exec docker-php-entrypoint "$@"
