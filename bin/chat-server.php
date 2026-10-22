<?php

/** CLI WebSocket daemon. Run behind a TLS reverse proxy in production. */
declare(strict_types=1);
require __DIR__.'/../bootstrap/environment.php';
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../bootstrap/app.php';
$origins = array_values(array_filter(array_map('trim', explode(',', getenv('CHAT_ALLOWED_ORIGINS') ?: ''))));
if (!$origins) {
    fwrite(STDERR, "Configure CHAT_ALLOWED_ORIGINS with explicit origins before starting the WebSocket daemon.\n");
    exit(1);
}
$port = filter_var(getenv('CHAT_WS_PORT') ?: '8094', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1024,'max_range' => 65535]]);
if (!$port) {
    fwrite(STDERR, "Invalid CHAT_WS_PORT.\n");
    exit(1);
}
$repo = new App\Repositories\PedagogyRepository(App\Support\Database::connect(require PROJECT_ROOT.'/config/database.php'));
$handler = new App\Services\ChatSocket($repo, $origins);
$loop = React\EventLoop\Loop::get();
$socket = new React\Socket\SocketServer((getenv('CHAT_WS_BIND') ?: '127.0.0.1').':'.$port, [], $loop);
$server = new Ratchet\Server\IoServer(new Ratchet\Http\HttpServer(new Ratchet\WebSocket\WsServer($handler)), $socket, $loop);
$loop->addPeriodicTimer(1, fn () => $handler->tick());
echo "Chat WebSocket listening on port $port\n";
$loop->run();
