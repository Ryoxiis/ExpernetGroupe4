<?php
/** Import the bundled demonstration database into a new native MySQL database. */
declare(strict_types=1);

$root = dirname(__DIR__);
if (PHP_SAPI !== 'cli') exit(1);
if (in_array('--help', $argv, true)) {
    echo "Usage : php bin/setup-local-db.php\nCrée expernet depuis database.sql sur MySQL local (3306), puis configure .env.\nUne base expernet existante est refusée. sudo demande le mot de passe Ubuntu.\n";
    exit;
}
require $root . '/bootstrap/environment.php';

function mysqlLocal(string $sql, bool $capture = false): string
{
    $input = tmpfile();
    if ($input === false) throw new RuntimeException('Impossible de préparer le SQL temporaire.');
    fwrite($input, $sql);
    rewind($input);
    $process = proc_open(
        ['sudo', '-n', 'mysql', '--batch', '--skip-column-names', '--default-character-set=utf8mb4'],
        [0 => $input, 1 => $capture ? ['pipe', 'w'] : STDOUT, 2 => STDERR],
        $pipes,
    );
    if (!is_resource($process)) {
        fclose($input);
        throw new RuntimeException('Impossible de lancer MySQL.');
    }
    $output = $capture ? stream_get_contents($pipes[1]) : '';
    if ($capture) fclose($pipes[1]);
    $status = proc_close($process);
    fclose($input);
    if ($status !== 0) throw new RuntimeException('MySQL a refusé l’opération. Consultez le message ci-dessus ; .env reste inchangé.');
    return trim($output);
}

try {
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('L’extension PHP pdo_mysql est requise.');
    }
    $dump = file_get_contents($root . '/database.sql');
    $envPath = $root . '/.env';
    $env = file_get_contents(is_file($envPath) ? $envPath : $root . '/.env.example');
    if ($dump === false || $env === false) throw new RuntimeException('database.sql ou configuration introuvable.');
    if ((is_file($envPath) && !is_writable($envPath)) || !is_writable($root)) {
        throw new RuntimeException('Le dossier du projet et .env doivent être accessibles en écriture.');
    }
    echo "Installation locale des données fictives de database.sql.\nSaisissez votre mot de passe Ubuntu si sudo le demande.\n";
    $auth = proc_open(['sudo', '-v'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($auth) || proc_close($auth) !== 0) throw new RuntimeException('Authentification sudo nécessaire.');
    if (mysqlLocal("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='expernet';", true) !== '') {
        throw new RuntimeException('La base expernet existe déjà : import annulé pour préserver ses données. .env reste inchangé.');
    }

    $user = 'expernet_' . bin2hex(random_bytes(4));
    $password = bin2hex(random_bytes(24));
    // CREATE without IF NOT EXISTS also prevents importing over a newly created database.
    $sql = "CREATE DATABASE `expernet` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n" . $dump;
    // Apply migrations missing from the dump using the existing migration runner afterwards.
    $sql .= "\nCREATE USER '$user'@'127.0.0.1' IDENTIFIED BY '$password';\n";
    $sql .= "GRANT ALL PRIVILEGES ON `expernet`.* TO '$user'@'127.0.0.1';\n";
    mysqlLocal($sql);

    $values = ['DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_DATABASE' => 'expernet', 'DB_USERNAME' => $user, 'DB_PASSWORD' => $password];
    if (getenv('APP_KEY') === false || getenv('APP_KEY') === '') $values['APP_KEY'] = bin2hex(random_bytes(32));
    $lines = explode("\n", $env);
    $replaced = [];
    foreach ($lines as $index => $line) {
        $name = explode('=', ltrim($line), 2)[0];
        if (array_key_exists($name, $values)) {
            $lines[$index] = $name . '=' . $values[$name];
            $replaced[$name] = true;
        }
    }
    foreach ($values as $name => $value) {
        if (!isset($replaced[$name])) {
            $lines[] = $name . '=' . $value;
        }
    }
    $env = implode("\n", $lines);
    // Keep a private backup and replace the configuration only after a successful import.
    if (is_file($envPath)) {
        $backup = $envPath . '.backup-' . bin2hex(random_bytes(4));
        if (!copy($envPath, $backup) || !chmod($backup, 0600)) throw new RuntimeException('Sauvegarde de .env impossible.');
    }
    $temporary = tempnam($root, '.env.');
    if ($temporary === false || file_put_contents($temporary, $env) === false || !chmod($temporary, 0600) || !rename($temporary, $envPath)) {
        throw new RuntimeException('Import effectué, mais écriture de .env impossible.');
    }
    foreach ($values as $name => $value) putenv($name . '=' . $value);
    echo "Base expernet importée ; compte MySQL dédié et .env configurés.\n";
    $argv = [__FILE__, 'migrate'];
    require $root . '/bin/portal.php';
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
