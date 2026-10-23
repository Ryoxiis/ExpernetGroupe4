<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PedagogyRepository;
use App\Support\Text;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/** Push invalidations only. All content is fetched through the authenticated HTTP API. */
final class ChatSocket implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private array $authenticated = [];
    private string $revision = '';
    private PedagogyRepository $repo;
    private array $origins;

    public function __construct(PedagogyRepository $repo, array $origins)
    {
        $this->repo = $repo;
        $this->origins = $origins;
        $this->clients = new \SplObjectStorage();
    }
    public function onOpen(ConnectionInterface $conn): void
    {
        $origin = $conn->httpRequest->getHeaderLine('Origin');
        if (!in_array($origin, $this->origins, true) || count($this->clients) >= 500) {
            $conn->close();
            return;
        }
        $this->clients->attach($conn, time());
    }
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (!$this->clients->contains($from) || isset($this->authenticated[$from->resourceId]) || strlen($msg) > 512) {
            $from->close();
            return;
        }
        try {
            $data = json_decode($msg, true, 8, JSON_THROW_ON_ERROR);
            $token = $data['token'] ?? null;
            $validToken = is_string($token)
                && strlen($token) === 64
                && Text::containsOnly($token, 'abcdef0123456789');
            if (($data['type'] ?? '') !== 'auth' || !$validToken) {
                $from->close();
                return;
            }
            $user = $this->repo->transaction(function () use ($data): ?array {
                $hash = hash('sha256', $data['token']);
                $ticket = $this->repo->one('SELECT t.*,u.status,u.activation_version AS current_version FROM portal_chat_tickets t JOIN portal_users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>UTC_TIMESTAMP() FOR UPDATE', [$hash]);
                if (!$ticket) {
                    return null;
                }
                $this->repo->execute('DELETE FROM portal_chat_tickets WHERE token_hash=?', [$hash]);
                if ($ticket['status'] !== 'active' || (int)$ticket['activation_version'] !== (int)$ticket['current_version']) {
                    return null;
                }
                return ['id' => (int)$ticket['user_id'],'version' => (int)$ticket['current_version'],'expires' => time() + 900];
            });
            if (!$user) {
                $from->close();
                return;
            }
            $this->authenticated[$from->resourceId] = $user;
            $from->send(json_encode(['event' => 'ready']));
        } catch (\Throwable) {
            $from->close();
        }
    }
    public function tick(): void
    {
        try {
            $revision = json_encode([
                $this->repo->one('SELECT MAX(id) AS id FROM portal_chat_messages'),
                $this->repo->one('SELECT MAX(id) AS id FROM portal_chat_meetings'),
                $this->repo->one('SELECT MAX(id) AS id FROM portal_audit WHERE action="chat.permissions"'),
            ]);
            $changed = $revision !== $this->revision;
            $this->revision = $revision;
            foreach ($this->clients as $conn) {
                $user = $this->authenticated[$conn->resourceId] ?? null;
                if (!$user) {
                    if (time() - (int)$this->clients[$conn] > 5) {
                        $conn->close();
                    }continue;
                }
                $current = $this->repo->one('SELECT status,activation_version FROM portal_users WHERE id=?', [$user['id']]);
                if (!$current || $current['status'] !== 'active' || (int)$current['activation_version'] !== $user['version'] || time() > $user['expires']) {
                    $conn->close();
                    continue;
                }
                if ($changed) {
                    $conn->send('{"event":"refresh"}');
                }
            }
        } catch (\Throwable $e) {
            error_log('Chat WS database unavailable');
            foreach ($this->clients as $conn) {
                $conn->close();
            }
        }
    }
    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->clients->detach($conn);
        }
        unset($this->authenticated[$conn->resourceId]);
    }
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }
}
