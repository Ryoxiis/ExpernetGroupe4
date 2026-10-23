<?php

/** Local Apache + PHP server; uses the persistent MySQL configured in .env. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);
$port = filter_var($argv[1] ?? '8088', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1024, 'max_range' => 65535]]);
if (!$port) {
    fwrite(STDERR, "Usage : php bin/serve-apache.php [port] [--check]\n");
    exit(1);
}

$app = require $root . '/bootstrap/app.php';
try {
    $db = App\Support\Database::connect(require $root . '/config/database.php');
    $db->query('SELECT id FROM portal_users LIMIT 1');
    $db->query('SELECT name FROM portal_locks LIMIT 1');
} catch (Throwable) {
    fwrite(STDERR, "MySQL est indisponible ou les migrations manquent. Vérifiez .env, puis php bin/serve-portal.php migrate.\n");
    exit(1);
}

$apache = '/usr/sbin/apache2';
$modules = '/usr/lib/apache2/modules';
$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$phpModule = $modules . '/libphp' . $phpVersion . '.so';
if (!is_executable($apache) || !is_file($phpModule)) {
    fwrite(STDERR, "Apache avec mod_php $phpVersion est requis. Voir docs/LAMP.md pour l’installation système.\n");
    exit(1);
}
if (str_contains($root, "\n") || str_contains($root, '"')) {
    fwrite(STDERR, "Chemin de projet incompatible avec la configuration Apache.\n");
    exit(1);
}
$runtime = $root . '/storage/apache-local';
if (!is_dir($runtime) && !mkdir($runtime, 0700, true) && !is_dir($runtime)) {
    fwrite(STDERR, "Impossible de préparer les journaux Apache.\n");
    exit(1);
}
$config = "ServerRoot \"$runtime\"\nServerName 127.0.0.1\nListen 127.0.0.1:$port\n";
$config .= "PidFile \"$runtime/apache.pid\"\nDefaultRuntimeDir \"$runtime\"\n";
foreach (['mpm_prefork', 'authz_core', 'authz_host', 'dir', 'mime', 'rewrite', 'headers'] as $module) {
    $config .= "LoadModule {$module}_module $modules/mod_{$module}.so\n";
}
$config .= "LoadModule php_module $phpModule\nPHPIniDir /etc/php/$phpVersion/apache2\n";
$config .= "TypesConfig /etc/mime.types\nDocumentRoot \"$root/public\"\nDirectoryIndex index.php\n";
$config .= "ErrorLog \"$runtime/error.log\"\nCustomLog \"$runtime/access.log\" combined\n";
$config .= "<Directory />\nRequire all denied\n</Directory>\n";
$config .= "<Directory \"$root/public\">\nRequire local\nOptions -Indexes -Includes -ExecCGI\nAllowOverride None\nFallbackResource /index.php\n</Directory>\n";
$config .= "<FilesMatch \"\\.php$\">\nSetHandler application/x-httpd-php\n</FilesMatch>\n";
$config .= "<FilesMatch \"^\\.\">\nRequire all denied\n</FilesMatch>\n";
$config .= "Header always set X-Content-Type-Options nosniff\n";
$config .= "php_admin_flag display_errors Off\nphp_admin_flag log_errors On\nphp_admin_value upload_max_filesize 10M\nphp_admin_value post_max_size 12M\n";
$path = $runtime . '/apache.conf';
file_put_contents($path, $config);
chmod($path, 0600);

$command = [$apache, '-f', $path, in_array('--check', $argv, true) ? '-t' : '-DFOREGROUND'];
echo "Apache local : http://127.0.0.1:$port/\nConnexion : http://127.0.0.1:$port/connexion/\n";
echo "Base MySQL persistante définie dans .env. Gardez ce terminal ouvert ; Ctrl+C arrête cet Apache local.\n";
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
exit(is_resource($process) ? proc_close($process) : 1);
