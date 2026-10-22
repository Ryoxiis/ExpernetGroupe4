<?php
/** Exercises security invariants on a disposable MySQL database, never production data. */
declare(strict_types=1);

use App\Repositories\PedagogyRepository;
use App\Services\PortalAuth;
use App\Services\PedagogyService;
use App\Support\Database;
use App\Support\PortalException;
use App\Support\PortalSecurity;

$app = require __DIR__.'/../bootstrap/app.php';
$config = require PROJECT_ROOT.'/config/pedagogy.php';
$dbConfig = require PROJECT_ROOT.'/config/database.php';
if (!str_ends_with($dbConfig['name'],'_qa') || getenv('APP_ENV') !== 'test') {
    fwrite(STDERR,"Refus : APP_ENV=test et une base dont le nom finit par _qa sont requis.\n");exit(1);
}
$checks=0;
function qaCheck(bool $valid,string $message): void { global $checks; $checks++; if (!$valid) throw new RuntimeException($message); }
function refused(callable $work,int $status): void { try { $work(); throw new RuntimeException('Action acceptée à tort'); } catch(PortalException $e) { qaCheck($e->status===$status,$e->getMessage()); } }
PortalSecurity::start();
$repo=new PedagogyRepository(Database::connect($dbConfig));
$config['mfa']=true;$config['key']=bin2hex(random_bytes(32));
$auth=new PortalAuth($repo,$config);
$suffix=bin2hex(random_bytes(5));$temp=bin2hex(random_bytes(16));
$id=$repo->insert('INSERT INTO portal_users(name,email,role,temporary_hash,temporary_expires,created_at) VALUES(?,?,"student",?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR),UTC_TIMESTAMP())',['Test sécurité','security.'.$suffix.'@expernet.re',password_hash($temp,PASSWORD_DEFAULT)]);
$email='security.'.$suffix.'@expernet.re';
qaCheck($auth->login($email,$temp)==='/connexion/premiere-connexion/','Activation only');
qaCheck($auth->current()===null,'No private session before password change');
refused(fn()=>$auth->changeInitialPassword($temp,$temp),422);
refused(fn()=>$auth->changeInitialPassword('short','short'),422);
$password='Phrase-de-test-securite-2026';
qaCheck($auth->changeInitialPassword($password,$password)==='/connexion/verification/','MFA required after password');
qaCheck($auth->current()===null,'MFA pending cannot access portal');
$setup=$auth->mfaSetup();qaCheck($setup['setup']===true,'TOTP enrollment');
$code=PortalSecurity::totp($setup['secret'],intdiv(time(),30));
$auth->verifyMfa($code);qaCheck($auth->current()['id']===$id,'MFA activates session');
$auth->logout();$auth->login($email,$password);
refused(fn()=>$auth->verifyMfa($code),409);
$_SESSION['pending']['expires']=time()-1;
refused(fn()=>$auth->pending('mfa'),401);
$expired=$repo->insert('INSERT INTO portal_users(name,email,role,temporary_hash,temporary_expires,created_at) VALUES(?,?,"student",?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR),UTC_TIMESTAMP())',['Test expiré','expired.'.$suffix.'@expernet.re',password_hash($temp,PASSWORD_DEFAULT)]);
refused(fn()=>$auth->login('expired.'.$suffix.'@expernet.re',$temp),401);
qaCheck($repo->one('SELECT status FROM portal_users WHERE id=?',[$expired])['status']==='invited','Expired password does not activate');
// Concurrent declarations: two separate DB connections compete for one student/window.
$cohort=$repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES(?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR))',['Concurrence '.$suffix]);
$trainer=$repo->one('SELECT id FROM portal_users WHERE role="trainer" LIMIT 1')['id'];
$repo->execute('INSERT INTO portal_memberships VALUES(?,?)',[$cohort,$id]);
$session=$repo->insert('INSERT INTO portal_sessions(cohort_id,trainer_id,title,starts_at,ends_at,mode,location) VALUES(?,?,"Concurrence",DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR),"remote","Test")',[$cohort,$trainer]);
$window=$repo->insert('INSERT INTO portal_windows(session_id,opened_by,opens_at,closes_at) VALUES(?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))',[$session,$trainer]);
$user=$repo->one('SELECT id,role,activation_version FROM portal_users WHERE id=?',[$id]);
$folder=sys_get_temp_dir().'/expernet-concurrency-'.$suffix;mkdir($folder,0700);
if (!function_exists('pcntl_fork')) { throw new RuntimeException('pcntl requis pour le test de concurrence'); }
$pids=[];
for($i=0;$i<2;$i++) {
    $pid=pcntl_fork();
    if($pid===0) {
        $r=new PedagogyRepository(Database::connect($dbConfig));
        try { (new PedagogyService($r,$config))->mutate($user,'attendance',['window_id'=>$window]);$result='created'; }
        catch(PortalException $e) { $result='refused:'.$e->status; }
        file_put_contents($folder.'/'.$i,$result);exit;
    }
    $pids[]=$pid;
}
foreach($pids as $pid) pcntl_waitpid($pid,$status);
$results=[file_get_contents($folder.'/0'),file_get_contents($folder.'/1')];sort($results);
qaCheck($results===['created','refused:409'],'One concurrent write accepted, duplicate rejected');
// Parent opens a fresh connection after fork (children closed inherited PDO handles).
$repo=new PedagogyRepository(Database::connect($dbConfig));
qaCheck((int)$repo->one('SELECT COUNT(*) AS total FROM portal_attendance WHERE window_id=? AND student_id=?',[$window,$id])['total']===1,'Database uniqueness');
$repo->execute('UPDATE portal_windows SET closes_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE id=?',[$window]);
refused(fn()=>(new PedagogyService($repo,$config))->mutate($user,'attendance',['window_id'=>$window]),409);
echo "$checks contrôles MySQL réussis : première connexion, expiration, MFA TOTP, rejeu et concurrence d’émargement.\n";
