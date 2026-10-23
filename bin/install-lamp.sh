#!/usr/bin/env bash
# Install the local demonstration as an Apache system site without overwriting a database.
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo 'Exécuter depuis un terminal : sudo bash bin/install-lamp.sh' >&2
    exit 1
fi

source_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
target=/var/www/expernet
configuration=/etc/apache2/sites-available/expernet.conf

for executable in php apache2ctl a2enmod a2ensite mysql rsync; do
    if ! command -v "$executable" >/dev/null; then
        echo "Outil manquant : $executable. Installer apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml php-curl rsync." >&2
        exit 1
    fi
done
if [[ ! -f "$source_root/.env" ]]; then
    echo '.env manque : préparer les accès MySQL persistants avant installation. Voir docs/LAMP.md.' >&2
    exit 1
fi
php -r 'foreach (["pdo_mysql","mbstring","fileinfo","sodium"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR,"Extension PHP manquante : $e\n"); exit(1); } }'

# Verify access before changing Apache or copying anything; never import database.sql over an existing DB.
php -r 'require $argv[1]."/bootstrap/app.php"; $p=App\Support\Database::connect(require $argv[1]."/config/database.php"); $p->query("SELECT id FROM portal_users LIMIT 1");' "$source_root"

install -d -o root -g www-data -m 0750 "$target"
for directory in app bootstrap config data public database bin; do
    install -d -o root -g www-data -m 0750 "$target/$directory"
    rsync -a --safe-links --chown=root:www-data --chmod=D750,F640 "$source_root/$directory/" "$target/$directory/"
done
if [[ -d "$source_root/vendor" ]]; then
    install -d -o root -g www-data -m 0750 "$target/vendor"
    rsync -a --safe-links --chown=root:www-data --chmod=D750,F640 "$source_root/vendor/" "$target/vendor/"
fi
for file in composer.json composer.lock .env.example; do
    if [[ -f "$source_root/$file" ]]; then install -o root -g www-data -m 0640 "$source_root/$file" "$target/$file"; fi
done
if [[ ! -f "$target/.env" ]]; then
    install -o root -g www-data -m 0640 "$source_root/.env" "$target/.env"
fi
install -d -o www-data -g www-data -m 0700 "$target/storage"

# Copy only private uploads, never the local MySQL data directory or runtime logs.
for module in pedagogy chat; do
    storage_root="$(php -r 'require $argv[1]."/bootstrap/app.php"; echo (require $argv[1]."/config/".$argv[2].".php")["storage"];' "$source_root" "$module")"
    install -d -o www-data -g www-data -m 0700 "$target/storage/$module"
    if [[ -d "$storage_root" ]]; then
        rsync -a --ignore-existing --safe-links --chown=www-data:www-data --chmod=D700,F600 "$storage_root/" "$target/storage/$module/"
    fi
done
# The deployed files must stay outside public/, regardless of the source machine's path.
php -r '$p=$argv[1];$s=file_get_contents($p);foreach(["PORTAL_STORAGE"=>"/var/www/expernet/storage/pedagogy","CHAT_STORAGE"=>"/var/www/expernet/storage/chat"] as $k=>$v){$s=preg_match("/^".$k."=.*$/m",$s)?preg_replace("/^".$k."=.*$/m",$k."=".$v,$s):rtrim($s)."\n".$k."=".$v."\n";}file_put_contents($p,$s);' "$target/.env"

php "$target/bin/portal.php" migrate
if [[ -f "$configuration" ]]; then
    cp -p -- "$configuration" "$configuration.backup-$(date +%Y%m%d%H%M%S)"
fi
sed "s|__PROJECT_ROOT__|$target|g" "$source_root/deploy/apache/expernet.conf" > "$configuration"
a2enmod headers rewrite
a2ensite expernet
if ! grep -Eq '^[[:space:]]*127\.0\.0\.1[[:space:]].*\bexpernet\.local\b' /etc/hosts; then
    printf '\n127.0.0.1 expernet.local\n' >> /etc/hosts
fi
apache2ctl configtest
systemctl enable --now mysql apache2
systemctl reload apache2
echo 'Installation terminée : http://expernet.local/ et http://expernet.local/connexion/'
echo 'Le site est limité aux accès locaux. Les autres sites Apache et les comptes existants sont conservés.'
