<?php
/** Start the local server with explicit configuration, without shell evaluation of .env. */
declare(strict_types=1);
$root=dirname(__DIR__);
require $root.'/bootstrap/environment.php';
if (in_array($argv[1] ?? '', ['migrate', 'seed-demo', 'bootstrap-admin'], true)) {
    require $root . '/bin/portal.php';
    exit;
}
$app=require $root.'/bootstrap/app.php';
try {
    $db=App\Support\Database::connect(require $root.'/config/database.php');
    $db->query('SELECT id FROM portal_users LIMIT 1');
    $db->query('SELECT name FROM portal_locks LIMIT 1');
} catch(Throwable) {
    fwrite(STDERR,"Base pédagogique indisponible ou migrations manquantes. Vérifiez MySQL et DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD dans .env.\nPuis exécutez les migrations avec la même configuration : php bin/serve-portal.php migrate\n");exit(1);
}
$port=filter_var($argv[1]??'8080',FILTER_VALIDATE_INT,['options'=>['min_range'=>1024,'max_range'=>65535]]);
if (!$port) { fwrite(STDERR,"Usage : php bin/serve-portal.php [port]\n");exit(1); }
echo "Expernet : http://127.0.0.1:$port/\nConnexion : http://127.0.0.1:$port/connexion/\nGardez ce terminal ouvert. Ctrl+C arrête le serveur.\n";
$process=proc_open([PHP_BINARY,'-d','upload_max_filesize=10M','-d','post_max_size=12M','-S','127.0.0.1:'.$port,'-t',$root.'/public',$root.'/public/router.php'],[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root);
exit(is_resource($process)?proc_close($process):1);
