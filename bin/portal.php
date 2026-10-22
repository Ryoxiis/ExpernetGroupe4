<?php
/** Local CLI: migrate, bootstrap administrator, or seed fictitious demonstration data. */
declare(strict_types=1);

use App\Repositories\PedagogyRepository;
use App\Support\Database;

$app = require __DIR__ . '/../bootstrap/app.php';
$config = require PROJECT_ROOT . '/config/pedagogy.php';
$repo = new PedagogyRepository(Database::connect(require PROJECT_ROOT . '/config/database.php'));
$command = $argv[1] ?? 'help';
if ($command === 'migrate') {
    // No destructive schema reset. Each migration runs once; DDL is idempotent for retry.
    $repo->execute('CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(100) PRIMARY KEY,applied_at DATETIME NOT NULL)');
    foreach (glob(PROJECT_ROOT . '/database/migrations/*.sql') as $file) {
        $version = basename($file);
        if ($repo->one('SELECT version FROM schema_migrations WHERE version=?', [$version])) continue;
        foreach (explode(';', file_get_contents($file)) as $statement) {
            if (trim($statement) !== '') $repo->execute($statement);
        }
        $repo->execute('INSERT INTO schema_migrations VALUES(?,UTC_TIMESTAMP())', [$version]);
        echo "Migration appliquée : $version\n";
    }
    exit;
}
if ($command === 'bootstrap-admin') {
    if ($repo->one('SELECT id FROM portal_users WHERE role="admin"')) {
        fwrite(STDERR, "Un administrateur existe déjà. Utilisez son espace de gestion.\n"); exit(1);
    }
    $email = strtolower($argv[2] ?? '');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || substr(strrchr($email,'@') ?: '',1) !== $config['email_domain']) {
        fwrite(STDERR, "Usage : php bin/portal.php bootstrap-admin adresse@{$config['email_domain']}\n"); exit(1);
    }
    $password = bin2hex(random_bytes(12));
    $repo->execute('INSERT INTO portal_users(name,email,role,temporary_hash,temporary_expires,created_at) VALUES(?,?,"admin",?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 HOUR),UTC_TIMESTAMP())', ['Administration Expernet', $email,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Compte créé : $email\nAccès temporaire (24 h, affiché une fois) : $password\nAucun e-mail envoyé.\n";
    exit;
}
if ($command === 'seed-demo') {
    if (!$config['demo']) { fwrite(STDERR,"PORTAL_DEMO doit être activé pour charger des données fictives.\n"); exit(1); }
    if ($repo->one('SELECT id FROM portal_users LIMIT 1')) { fwrite(STDERR,"La base contient déjà des comptes ; aucune donnée modifiée.\n"); exit(1); }
    $result = $repo->transaction(function () use ($repo,$config): array {
        $accounts=[];
        foreach (['admin'=>'Camille Admin','trainer'=>'Alex Formateur','student'=>'Sam Étudiant','student2'=>'Lou Étudiante','trainer2'=>'Charlie Formateur'] as $key=>$name) {
            $role = rtrim($key,'2'); $password=bin2hex(random_bytes(12)); $email=$key.'.demo@'.$config['email_domain'];
            $id=$repo->insert('INSERT INTO portal_users(name,email,role,temporary_hash,temporary_expires,created_at) VALUES(?,?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 HOUR),UTC_TIMESTAMP())',[$name,$email,$role,password_hash($password,PASSWORD_DEFAULT)]);
            $accounts[$key]=['id'=>$id,'email'=>$email,'temporary_password'=>$password];
        }
        $cohort=$repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES("CDA · Promotion Horizon",CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 YEAR))');
        $other=$repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES("Réseaux · Promotion Azur",CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 YEAR))');
        foreach (['trainer','student'] as $key) $repo->execute('INSERT INTO portal_memberships VALUES(?,?)',[$cohort,$accounts[$key]['id']]);
        foreach (['trainer2','student2'] as $key) $repo->execute('INSERT INTO portal_memberships VALUES(?,?)',[$other,$accounts[$key]['id']]);
        $now=time();
        $session=$repo->insert('INSERT INTO portal_sessions(cohort_id,trainer_id,title,starts_at,ends_at,mode,location) VALUES(?,?,?,?,?,"remote","À distance · lien à renseigner")',[$cohort,$accounts['trainer']['id'],'Concevoir une application PHP',gmdate('Y-m-d H:i:s',$now-1800),gmdate('Y-m-d H:i:s',$now+7200)]);
        $repo->execute('INSERT INTO portal_sessions(cohort_id,trainer_id,title,starts_at,ends_at,mode,location) VALUES(?,?,?,?,?,"onsite","Salle Océan")',[$cohort,$accounts['trainer']['id'],'Base de données et requêtes SQL',gmdate('Y-m-d H:i:s',$now+86400),gmdate('Y-m-d H:i:s',$now+86400+12600)]);
        $repo->execute('INSERT INTO portal_sessions(cohort_id,trainer_id,title,starts_at,ends_at,mode,location) VALUES(?,?,?,?,?,"onsite","Salle Azur")',[$other,$accounts['trainer2']['id'],'Sécuriser un réseau',gmdate('Y-m-d H:i:s',$now-1800),gmdate('Y-m-d H:i:s',$now+7200)]);
        $repo->execute('INSERT INTO portal_assignments(cohort_id,trainer_id,session_id,title,description,due_at) VALUES(?,?,?,?,?,?)',[$cohort,$accounts['trainer']['id'],$session,'Première API PHP','Décrivez votre schéma de données et les endpoints du planning. Déposez votre texte ou un lien vers votre rendu.',gmdate('Y-m-d H:i:s',$now+172800)]);
        $repo->execute('INSERT INTO portal_messages(cohort_id,author_id,body,created_at) VALUES(?,?,?,UTC_TIMESTAMP())',[$cohort,$accounts['trainer']['id'],'Bienvenue dans votre espace pédagogique. Votre planning et vos ressources sont réunis ici.']);
        $directory=$config['storage']; if (!is_dir($directory)) mkdir($directory,0700,true);
        $filename=bin2hex(random_bytes(24)).'.txt';file_put_contents($directory.'/'.$filename,"Support fictif Expernet\nObjectif : relier une séance, ses ressources et son suivi.\n");chmod($directory.'/'.$filename,0600);
        $repo->execute('INSERT INTO portal_resources(cohort_id,session_id,author_id,title,stored_name,original_name,mime,created_at) VALUES(?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$cohort,$session,$accounts['trainer']['id'],'Le guide de notre séance',$filename,'guide-seance.txt','text/plain']);
        return $accounts;
    });
    if (in_array('--json',$argv,true)) echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    else foreach ($result as $account) echo $account['email'].' | accès temporaire : '.$account['temporary_password']."\n";
    exit;
}
echo "Commandes : migrate | bootstrap-admin adresse@expernet.re | seed-demo [--json]\n";
