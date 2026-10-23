<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PedagogyRepository;
use RuntimeException;

/** Deletes only expired chat content; conversations and permissions are preserved. */
final class ChatRetention
{
    public function __construct(private PedagogyRepository $repo, private string $storage) {}

    public function purge(bool $force = false): int
    {
        if (!is_dir($this->storage) && !mkdir($this->storage, 0700, true) && !is_dir($this->storage)) {
            throw new RuntimeException('Stockage des discussions indisponible.');
        }
        $lock = fopen($this->storage . '/.retention.lock', 'c+');
        if (!$lock) {
            throw new RuntimeException('Verrou de purge indisponible.');
        }
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                return 0;
            }
            rewind($lock);
            $last = (int) stream_get_contents($lock);
            if (!$force && $last > time() - 60) {
                return 0;
            }
            $cutoff = gmdate('Y-m-d H:i:s', time() - 86400);
            $removed = 0;
            // Bound each transaction; the scheduled command handles further batches.
            for ($batch = 0; $batch < ($force ? 100 : 1); $batch++) {
                $count = $this->repo->transaction(function () use ($cutoff): int {
                    $rows = $this->repo->all('SELECT id FROM portal_chat_messages WHERE created_at<=? ORDER BY id LIMIT 200 FOR UPDATE', [$cutoff]);
                    if (!$rows) {
                        return 0;
                    }
                    $ids = array_column($rows, 'id');
                    $slots = implode(',', array_fill(0, count($ids), '?'));
                    $files = $this->repo->all("SELECT stored_name FROM portal_chat_files WHERE message_id IN ($slots)", $ids);
                    foreach ($files as $file) {
                        $name = $file['stored_name'];
                        if (!preg_match('/\A[a-f0-9]{48}\z/D', $name)) {
                            throw new RuntimeException('Nom de fichier de discussion invalide.');
                        }
                        // Never unlink a file still referenced by an unexpired message.
                        $shared = $this->repo->one("SELECT id FROM portal_chat_files WHERE stored_name=? AND message_id NOT IN ($slots) LIMIT 1", [$name, ...$ids]);
                        $path = $this->storage . '/' . $name;
                        if (!$shared && (is_file($path) || is_link($path)) && !unlink($path)) {
                            throw new RuntimeException('Suppression de pièce jointe impossible.');
                        }
                    }
                    $this->repo->execute("DELETE FROM portal_chat_files WHERE message_id IN ($slots)", $ids);
                    return $this->repo->execute("DELETE FROM portal_chat_messages WHERE id IN ($slots)", $ids);
                });
                $removed += $count;
                if ($count < 200) {
                    break;
                }
            }
            // Remove the obsolete pre-chat copies as well, without touching other portal data.
            $this->repo->execute('DELETE FROM portal_messages WHERE created_at<=?', [$cutoff]);
            ftruncate($lock, 0);
            rewind($lock);
            fwrite($lock, (string) time());
            return $removed;
        } finally {
            fclose($lock);
        }
    }
}
