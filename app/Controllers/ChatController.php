<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PedagogyRepository;
use App\Services\ChatService;
use App\Services\PortalAuth;
use App\Support\Database;
use App\Support\PortalException;
use App\Support\PortalSecurity;
use App\Support\RequestData;

final class ChatController
{
    public function handle(string $path, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        try {
            PortalSecurity::start();
            $portal = require PROJECT_ROOT.'/config/pedagogy.php';
            $config = require PROJECT_ROOT.'/config/chat.php';
            $repo = new PedagogyRepository(Database::connect(require PROJECT_ROOT.'/config/database.php'));
            $user = (new PortalAuth($repo, $portal))->requireUser();
            if (!in_array($method, ['GET','POST'], true)) {
                throw new PortalException('Méthode non autorisée.', 405);
            }
            $action = trim(substr($path, strlen('/api/discussions/')), '/');
            $service = new ChatService($repo, $config);
            $input = $_POST;
            if ($method === 'POST') {
                if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12 * 1024 * 1024) {
                    throw new PortalException('Requête supérieure à 12 Mo.', 413);
                }
                PortalSecurity::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
                $input = RequestData::read();
            }
            $id = static fn ($v) => ChatService::positive($v);
            $data = match($method.':'.$action) {
                'GET:inbox' => $service->inbox($user),
                'GET:contacts' => ['contacts' => $service->contacts($user, $id($_GET['cohort_id'] ?? null), ChatService::text($_GET['q'] ?? '', 100, true))],
                'GET:history' => $service->history($user, $id($_GET['conversation_id'] ?? null), isset($_GET['before']) ? $id($_GET['before']) : 0, isset($_GET['after']) ? $id($_GET['after']) : 0),
                'GET:administration' => $service->administration($user),
                'POST:open' => ['id' => $service->open($user, $id($input['cohort_id'] ?? null), isset($input['target_user_id']) ? $id($input['target_user_id']) : null)],
                'POST:send' => $service->send($user, $id($input['conversation_id'] ?? null), $input, $_FILES['file'] ?? []),
                'POST:group' => ['id' => $service->group($user, $input)],
                'POST:meeting' => ['id' => $service->meeting($user, $id($input['conversation_id'] ?? null), $input)],
                'POST:join' => ['url' => $service->join($user, $id($input['meeting_id'] ?? null))],
                'POST:ticket' => ['token' => $service->ticket($user)],
                'POST:read' => $this->done(fn () => $service->read($user, $id($input['conversation_id'] ?? null), $id($input['message_id'] ?? null))),
                'POST:permissions' => $this->done(fn () => $service->permissions($user, $input)),
                'GET:file' => $this->file($service, $user, $id($_GET['id'] ?? null), $config),
                default => throw new PortalException('Endpoint introuvable.', 404),
            };
            echo json_encode(['data' => $data], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (PortalException $e) {
            http_response_code($e->status);
            echo json_encode(['error' => true,'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('Chat: '.get_class($e).' '.$e->getCode());
            http_response_code(503);
            echo json_encode(['error' => true,'message' => 'Les discussions sont indisponibles. Vérifiez les migrations ou réessayez.']);
        }
    }
    private function done(callable $work): array
    {
        $work();
        return ['success' => true];
    }
    private function file(ChatService $service, array $user, int $id, array $config): never
    {
        $f = $service->download($user, $id);
        $path = $config['storage'].'/'.$f['stored_name'];
        if (!is_file($path)) {
            throw new PortalException('Fichier indisponible.', 404);
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="document"; filename*=UTF-8\'\''.rawurlencode($f['original_name']));
        header('Content-Length: '.filesize($path));
        header("Content-Security-Policy: default-src 'none'; sandbox");
        session_write_close();
        readfile($path);
        exit;
    }
}
