<?php
/** Isolated integration suite. Never runs against the portal database. */
declare(strict_types=1);
require __DIR__.'/../bootstrap/environment.php';
require __DIR__.'/../bootstrap/app.php';
use App\Repositories\PedagogyRepository;
use App\Services\ChatService;
use App\Support\Database;
use App\Support\PortalException;
if(getenv('APP_ENV')!=='test' || !str_ends_with(getenv('DB_DATABASE')?:'','_qa')) {fwrite(STDERR,"Use APP_ENV=test and a dedicated DB_DATABASE ending in _qa.\n");exit(1);}
$config=require PROJECT_ROOT.'/config/database.php';
$repo=new PedagogyRepository(Database::connect($config));
foreach(glob(PROJECT_ROOT.'/database/migrations/*.sql') as $file) foreach(explode(';',file_get_contents($file)) as $sql) if(trim($sql)!=='') $repo->execute($sql);
$chatConfig=require PROJECT_ROOT.'/config/chat.php';
$service=new ChatService($repo,$chatConfig);
$run=bin2hex(random_bytes(6));$checks=0;
function check(bool $ok,string $label): void {global $checks;if(!$ok) throw new RuntimeException($label);$checks++;}
function denied(callable $fn,int $status=403): void {try {$fn();}catch(PortalException $e){check($e->status===$status,'Expected '.$status.', got '.$e->status);return;}throw new RuntimeException('Expected rejection');}
$users=[];
foreach(['admin','student','student','trainer','student'] as $i=>$role) {
    $id=$repo->insert('INSERT INTO portal_users(name,email,role,password_hash,status,created_at) VALUES(?,?,?,?,"active",UTC_TIMESTAMP())',['Chat QA '.$run.' '.$i,'chat-'.$run.'-'.$i.'@example.test',$role,password_hash('Fictitious-chat-QA-only-2026',PASSWORD_DEFAULT)]);
    $users[]=$repo->one('SELECT * FROM portal_users WHERE id=?',[$id]);
}
[$admin,$a,$b,$trainer,$outsider]=$users;
$cohort=$repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES(?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR))',['Chat QA '.$run]);
$other=$repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES(?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR))',['Other QA '.$run]);
foreach([$a,$b,$trainer] as $u) $repo->execute('INSERT INTO portal_memberships VALUES(?,?)',[$cohort,$u['id']]);
$repo->execute('INSERT INTO portal_memberships VALUES(?,?)',[$other,$outsider['id']]);
$channel=$service->open($a,$cohort,null);
check($channel===$service->open($b,$cohort,null),'One formation channel');
$private=$service->open($a,$cohort,(int)$b['id']);
check($private===$service->open($b,$cohort,(int)$a['id']),'One direct pair');
denied(fn()=>$service->history($outsider,$private));
denied(fn()=>$service->history($trainer,$private));
denied(fn()=>$service->history($admin,$private));
denied(fn()=>$service->contacts($a,$other,'QA'));
check(count($service->contacts($a,$cohort,'Chat QA '.$run))===2,'Contacts limited to formation');
$service->permissions($admin,['cohort_id'=>$cohort,'user_id'=>$admin['id'],'access'=>'allow']);
check(count($service->contacts($a,$cohort,'Chat QA '.$run))===3,'Explicit administrative contact');
denied(fn()=>$service->history($admin,$private));
$msg=$service->send($a,$private,['content'=>'<script>alert(1)</script>','client_key'=>'test-key-'.$run.'-one'],[]);
check($msg['id']===$service->send($a,$private,['content'=>'retry','client_key'=>'test-key-'.$run.'-one'],[])['id'],'Idempotent retry');
check($service->history($b,$private)['messages'][0]['content']==='<script>alert(1)</script>','Content stored without HTML mutation');
$conv=array_values(array_filter($service->inbox($b)['conversations'],fn($c)=>(int)$c['id']===$private))[0];
check((int)$conv['unread_count']===1,'Unread message');
$service->read($b,$private,(int)$msg['id']);
$conv=array_values(array_filter($service->inbox($b)['conversations'],fn($c)=>(int)$c['id']===$private))[0];
check((int)$conv['unread_count']===0,'Read cursor');
$otherMsg=$service->send($outsider,$service->open($outsider,$other,null),['content'=>'Other','client_key'=>'test-key-'.$run.'-other'],[]);
denied(fn()=>$service->read($b,$private,(int)$otherMsg['id']));
denied(fn()=>$service->permissions($a,['cohort_id'=>$cohort]),403);
$service->permissions($admin,['cohort_id'=>$cohort,'user_id'=>$b['id'],'access'=>'deny']);
denied(fn()=>$service->history($b,$private));
denied(fn()=>$service->send($a,$private,['content'=>'blocked','client_key'=>'test-key-'.$run.'-blocked'],[]));
$service->permissions($admin,['cohort_id'=>$cohort,'user_id'=>$b['id'],'access'=>'inherit']);
check(count($service->history($b,$private)['messages'])===1,'Inherited access restored');
$service->permissions($admin,['cohort_id'=>$cohort,'enabled'=>1,'direct_enabled'=>0,'files_enabled'=>0,'meetings_enabled'=>0]);
denied(fn()=>$service->history($b,$private));
check($service->history($a,$channel)['policy']['files_enabled']==0,'Formation policy');
$service->permissions($admin,['cohort_id'=>$cohort,'enabled'=>1,'direct_enabled'=>1,'files_enabled'=>1,'meetings_enabled'=>1]);
$repo->execute('UPDATE portal_users SET status="disabled" WHERE id=?',[$b['id']]);
denied(fn()=>$service->history($a,$private));
$repo->execute('UPDATE portal_users SET status="active" WHERE id=?',[$b['id']]);
$meetingConfig=$chatConfig+[];$meetingConfig['meeting_url']='https://meet.example.test';$meetingConfig['meeting_app_id']='chat-qa';$meetingConfig['meeting_secret']=str_repeat('qa-only-',8);
$meet=new ChatService($repo,$meetingConfig);
$meeting=$meet->meeting($a,$private,['title'=>'QA réunion','starts_at'=>'2026-10-23T10:00:00+04:00']);
$url=$meet->join($b,$meeting);
check(str_starts_with($url,'https://meet.example.test/'),'Private configured meeting');
parse_str(parse_url($url,PHP_URL_QUERY),$query);$parts=explode('.',$query['jwt']);
$claims=json_decode(base64_decode(strtr($parts[1],'-_','+/')),true);
check($claims['context']['user']['id']===(string)$b['id'] && $claims['exp']-time()<=300,'Room scoped short JWT');
check($parts[2]===rtrim(strtr(base64_encode(hash_hmac('sha256',$parts[0].'.'.$parts[1],$meetingConfig['meeting_secret'],true)),'+/','-_'),'='),'JWT signature');
denied(fn()=>$meet->join($trainer,$meeting));
denied(fn()=>$meet->meeting($a,$private,['title'=>'QA','starts_at'=>'tomorrow']),422);
if(!$service->meetingsAvailable()) denied(fn()=>$service->meeting($a,$private,['title'=>'QA']),403);
$ticket=$service->ticket($a);check(strlen($ticket)===64,'WebSocket ticket');
check($repo->one('SELECT user_id FROM portal_chat_tickets WHERE token_hash=?',[hash('sha256',$ticket)])['user_id']==$a['id'],'Only ticket hash stored');
// Cursor catches up without skipping messages; directly seeded to avoid rate limiter.
for($i=0;$i<61;$i++) $repo->execute('INSERT INTO portal_chat_messages(conversation_id,sender_id,content,client_key,created_at) VALUES(?,?,?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 2 MINUTE))',[$private,$a['id'],'Page '.$i,'page-'.$run.'-'.$i]);
$latest=$service->history($b,$private);check(count($latest['messages'])===50 && $latest['has_more'],'Latest history page');
$older=$service->history($b,$private,(int)$latest['messages'][0]['id']);check(count($older['messages'])===13,'Older cursor');
$catchup=$service->history($b,$private,0,(int)$msg['id']);check(count($catchup['messages'])===50 && $catchup['has_more'],'Catch-up first page');
check(count($service->history($b,$private,0,(int)end($catchup['messages'])['id'])['messages'])===12,'Catch-up remainder');
// Administrators define custom groups; management grants no conversation access.
$group=$service->group($admin,['cohort_id'=>$cohort,'title'=>'Projet QA','user_ids'=>[$a['id'],$b['id']]]);
check($service->history($a,$group)['messages']===[], 'Group member access');
denied(fn()=>$service->history($admin,$group));
denied(fn()=>$service->history($trainer,$group));
denied(fn()=>$service->group($a,['cohort_id'=>$cohort,'title'=>'No','user_ids'=>[$a['id'],$b['id']]]));
denied(fn()=>$service->group($admin,['cohort_id'=>$cohort,'title'=>'No','user_ids'=>[$a['id'],$outsider['id']]]));
$service->group($admin,['group_id'=>$group,'cohort_id'=>$cohort,'title'=>'Projet QA modifié','user_ids'=>[$a['id'],$trainer['id']]]);
denied(fn()=>$service->history($b,$group));
check($service->history($trainer,$group)['messages']===[],'Group membership update');
// Two independent PHP processes race the same direct pair and then the same send key.
function race(array $jobs): array {
    $running=[];
    foreach($jobs as $input) {
        $process=proc_open([PHP_BINARY,__DIR__.'/chat_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)) throw new RuntimeException('Cannot start concurrency worker');
        fwrite($pipes[0],json_encode($input,JSON_THROW_ON_ERROR));fclose($pipes[0]);
        $running[]=[$process,$pipes];
    }
    $result=[];
    foreach($running as [$process,$pipes]) {
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0) throw new RuntimeException('Concurrency worker: '.$err);
        $result[]=json_decode($out,true,16,JSON_THROW_ON_ERROR);
    }
    return $result;
}
$pairs=race([
    ['action'=>'open','user_id'=>$a['id'],'cohort_id'=>$cohort,'target_user_id'=>$trainer['id']],
    ['action'=>'open','user_id'=>$trainer['id'],'cohort_id'=>$cohort,'target_user_id'=>$a['id']],
]);
check($pairs[0]['id']===$pairs[1]['id'],'Concurrent pair uniqueness');
$job=['action'=>'send','user_id'=>$a['id'],'conversation_id'=>$channel,'content'=>'Concurrent retry','client_key'=>'race-'.$run];
$sent=race([$job,$job]);
check($sent[0]['id']===$sent[1]['id'],'Concurrent send idempotency');
check((int)$repo->one('SELECT COUNT(*) AS total FROM portal_chat_messages WHERE sender_id=? AND client_key=?',[$a['id'],$job['client_key']])['total']===1,'One stored concurrent message');
// Fixtures stay isolated for HTTP/browser tests. Output contains only fictitious QA identities.
if(in_array('--fixture', $argv,true)) file_put_contents('/tmp/expernet-chat-fixture.json',json_encode(['users'=>array_map(fn($u)=>array_intersect_key($u,array_flip(['id','email','role'])),$users),'password'=>'Fictitious-chat-QA-only-2026','cohort'=>$cohort,'private'=>$private,'channel'=>$channel],JSON_THROW_ON_ERROR));
echo "$checks collaboration MySQL checks passed. Fixtures: $run (QA database only).\n";
