<?php
/** Subprocess used only by the isolated concurrency suite. */
declare(strict_types=1);
require __DIR__.'/../bootstrap/environment.php';
require __DIR__.'/../bootstrap/app.php';
if(getenv('APP_ENV')!=='test' || !str_ends_with(getenv('DB_DATABASE')?:'','_qa')) exit(2);
$input=json_decode(stream_get_contents(STDIN),true,16,JSON_THROW_ON_ERROR);
$repo=new App\Repositories\PedagogyRepository(App\Support\Database::connect(require PROJECT_ROOT.'/config/database.php'));
$chat=new App\Services\ChatService($repo,require PROJECT_ROOT.'/config/chat.php');
$user=$repo->one('SELECT * FROM portal_users WHERE id=?',[$input['user_id']]);
$result=match($input['action']) {
    'open'=>['id'=>$chat->open($user,(int)$input['cohort_id'],(int)$input['target_user_id'])],
    'send'=>$chat->send($user,(int)$input['conversation_id'],$input,[]),
};
echo json_encode($result,JSON_THROW_ON_ERROR);
