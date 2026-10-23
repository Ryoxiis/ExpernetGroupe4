<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PedagogyRepository;
use App\Support\Text;
use App\Support\Dates;
use App\Support\PortalException;

final class ChatService
{
    private PedagogyRepository $repo;
    private array $config;

    public function __construct(PedagogyRepository $repo, array $config)
    {
        $this->repo = $repo;
        $this->config = $config;
    }

    public static function positive(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            throw new PortalException('Identifiant invalide.', 422);
        }
        return $id;
    }

    public static function text(mixed $value, int $max, bool $empty = false): string
    {
        if (!is_string($value)) {
            throw new PortalException('Texte invalide.', 422);
        }
        $value = trim($value);
        if ((!$empty && $value === '') || mb_strlen($value) > $max) {
            throw new PortalException('Texte vide ou trop long.', 422);
        }
        return $value;
    }

    private function policy(int $cohort): array
    {
        return $this->repo->one('SELECT * FROM portal_chat_policies WHERE cohort_id=?', [$cohort]) ?? ['enabled' => 1,'direct_enabled' => 1,'files_enabled' => 1,'meetings_enabled' => 1];
    }

    private function allowed(array $user, int $cohort): bool
    {
        if ($user['status'] !== 'active') {
            return false;
        }
        $access = $this->repo->one('SELECT allowed FROM portal_chat_access WHERE cohort_id=? AND user_id=?', [$cohort,$user['id']]);
        if ($access) {
            return (bool) $access['allowed'];
        }
        return (bool) $this->repo->one('SELECT user_id FROM portal_memberships WHERE cohort_id=? AND user_id=?', [$cohort,$user['id']]);
    }

    public function cohorts(array $user): array
    {
        // Ne retourne que les promotions auxquelles la personne peut participer.
        $cohorts = $this->repo->all('SELECT id,name FROM portal_cohorts ORDER BY name');
        $visible = [];
        foreach ($cohorts as $cohort) {
            $id = (int) $cohort['id'];
            if ($this->allowed($user, $id) && $this->policy($id)['enabled']) {
                $visible[] = $cohort;
            }
        }
        return $visible;
    }

    public function conversation(array $user, int $id): array
    {
        $c = $this->repo->one('SELECT * FROM portal_chat_conversations WHERE id=?', [$id]);
        if (!$c || !$this->allowed($user, (int)$c['cohort_id'])) {
            throw new PortalException('Conversation inaccessible.', 403);
        }
        $policy = $this->policy((int)$c['cohort_id']);
        if (!$policy['enabled'] || ($c['kind'] === 'direct' && !$policy['direct_enabled'])) {
            throw new PortalException('Échanges désactivés par l’administration.', 403);
        }
        if (in_array($c['kind'], ['direct','group'], true)) {
            $members = $this->repo->all('SELECT u.* FROM portal_chat_participants p JOIN portal_users u ON u.id=p.user_id WHERE p.conversation_id=?', [$id]);
            $participantIds = [];
            foreach ($members as $member) {
                $participantIds[] = (int) $member['id'];
            }
            if (!in_array((int) $user['id'], $participantIds, true)) {
                throw new PortalException('Conversation privée.', 403);
            }
            foreach ($members as $member) {
                if ($c['kind'] === 'direct' && !$this->allowed($member, (int)$c['cohort_id'])) {
                    throw new PortalException('Un participant ne dispose plus des droits nécessaires.', 403);
                }
            }
        }
        return $c + ['policy' => $policy];
    }

    public function contacts(array $user, int $cohort, string $query): array
    {
        if (!$this->allowed($user, $cohort) || !$this->policy($cohort)['enabled'] || !$this->policy($cohort)['direct_enabled']) {
            throw new PortalException('Recherche inaccessible.', 403);
        }
        if (mb_strlen($query) < 2) {
            return [];
        }
        return $this->repo->all('SELECT u.id,u.name,u.role FROM portal_users u WHERE u.status="active" AND u.id<>? AND LOCATE(?,u.name)>0
            AND (EXISTS(SELECT 1 FROM portal_chat_access a WHERE a.cohort_id=? AND a.user_id=u.id AND a.allowed=1)
            OR (NOT EXISTS(SELECT 1 FROM portal_chat_access a WHERE a.cohort_id=? AND a.user_id=u.id)
            AND EXISTS(SELECT 1 FROM portal_memberships m WHERE m.cohort_id=? AND m.user_id=u.id))) ORDER BY u.name,u.id LIMIT 50', [$user['id'],$query,$cohort,$cohort,$cohort]);
    }

    public function open(array $user, int $cohort, ?int $target): int
    {
        if (!$this->allowed($user, $cohort) || !$this->policy($cohort)['enabled']) {
            throw new PortalException('Formation inaccessible.', 403);
        }
        if ($target !== null) {
            $other = $this->repo->one('SELECT * FROM portal_users WHERE id=?', [$target]);
            if ($target === (int)$user['id'] || !$other || !$this->allowed($other, $cohort) || !$this->policy($cohort)['direct_enabled']) {
                throw new PortalException('Contact non autorisé.', 403);
            }
        }
        return $this->repo->transaction(function () use ($user, $cohort, $target): int {
            $ids = [(int)$user['id'],$target];
            sort($ids);
            $key = $target === null ? 'cohort:'.$cohort : 'direct:'.$cohort.':'.implode(':', $ids);
            $this->repo->execute('INSERT IGNORE INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) SELECT id,?,?,name,UTC_TIMESTAMP() FROM portal_cohorts WHERE id=?', [$target === null ? 'cohort' : 'direct',$key,$cohort]);
            $id = (int)$this->repo->one('SELECT id FROM portal_chat_conversations WHERE direct_key=?', [$key])['id'];
            if ($target !== null) {
                foreach ($ids as $member) {
                    $this->repo->execute('INSERT IGNORE INTO portal_chat_participants(conversation_id,user_id) VALUES(?,?)', [$id,$member]);
                }
            }
            return $id;
        });
    }

    public function group(array $user, array $input): int
    {
        $this->administration($user);
        $cohort = self::positive($input['cohort_id'] ?? null);
        $title = self::text($input['title'] ?? '', 120);
        if (!is_array($input['user_ids'] ?? null) || count($input['user_ids']) < 2 || count($input['user_ids']) > 100) {
            throw new PortalException('Sélectionnez entre 2 et 100 participants.', 422);
        }
        $members = [];
        foreach ($input['user_ids'] as $value) {
            $id = self::positive($value);
            if (!in_array($id, $members, true)) {
                $members[] = $id;
            }
        }
        if (count($members) < 2) {
            throw new PortalException('Deux participants distincts sont nécessaires.', 422);
        }
        foreach ($members as $uid) {
            $member = $this->repo->one('SELECT * FROM portal_users WHERE id=?', [$uid]);
            if (!$member || !$this->allowed($member, $cohort)) {
                throw new PortalException('Chaque participant doit être autorisé dans cette formation.', 403);
            }
        }
        return $this->repo->transaction(function () use ($user, $input, $cohort, $title, $members): int {
            if (isset($input['group_id'])) {
                $id = self::positive($input['group_id']);
                $c = $this->repo->one('SELECT * FROM portal_chat_conversations WHERE id=? FOR UPDATE', [$id]);
                if (!$c || $c['kind'] !== 'group' || (int)$c['cohort_id'] !== $cohort) {
                    throw new PortalException('Groupe introuvable dans cette formation.', 404);
                }
                $this->repo->execute('UPDATE portal_chat_conversations SET title=?,updated_at=UTC_TIMESTAMP() WHERE id=?', [$title,$id]);
                $current = $this->repo->all('SELECT user_id FROM portal_chat_participants WHERE conversation_id=?', [$id]);
                foreach ($current as $participant) {
                    if (!in_array((int)$participant['user_id'], $members, true)) {
                        $this->repo->execute('DELETE FROM portal_chat_participants WHERE conversation_id=? AND user_id=?', [$id,$participant['user_id']]);
                    }
                }
            } else {
                $id = $this->repo->insert('INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES(?,"group",?,?,UTC_TIMESTAMP())', [$cohort,'group:'.bin2hex(random_bytes(24)),$title]);
            }
            foreach ($members as $uid) {
                $this->repo->execute('INSERT IGNORE INTO portal_chat_participants(conversation_id,user_id) VALUES(?,?)', [$id,$uid]);
            }
            $this->repo->audit((int)$user['id'], 'chat.group', $id, ['cohort_id' => $cohort,'members' => $members]);
            return $id;
        });
    }

    public function inbox(array $user): array
    {
        $cohorts = $this->cohorts($user);
        foreach ($cohorts as $c) {
            $this->open($user, (int)$c['id'], null);
        }
        $rows = $this->repo->all('SELECT c.*, (SELECT CASE WHEN content="" THEN "Pièce jointe" ELSE content END FROM portal_chat_messages m WHERE m.conversation_id=c.id AND m.created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR) ORDER BY m.id DESC LIMIT 1) AS last_message,
            (SELECT COUNT(*) FROM portal_chat_messages m WHERE m.conversation_id=c.id AND m.created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR) AND m.sender_id<>? AND m.id>COALESCE((SELECT last_read_id FROM portal_chat_participants p WHERE p.conversation_id=c.id AND p.user_id=?),0)) AS unread_count
            FROM portal_chat_conversations c WHERE c.kind="cohort" OR EXISTS(SELECT 1 FROM portal_chat_participants p WHERE p.conversation_id=c.id AND p.user_id=?) ORDER BY c.updated_at DESC,c.id DESC', [$user['id'],$user['id'],$user['id']]);
        $result = [];
        foreach ($rows as $c) {
            try {
                $this->conversation($user, (int)$c['id']);
            } catch (PortalException) {
                continue;
            }
            if ($c['kind'] === 'direct') {
                $c['title'] = $this->repo->one('SELECT u.name FROM portal_chat_participants p JOIN portal_users u ON u.id=p.user_id WHERE p.conversation_id=? AND p.user_id<>?', [$c['id'],$user['id']])['name'];
            }
            unset($c['direct_key']);
            $result[] = $c;
        }
        return ['conversations' => $result,'cohorts' => $cohorts,'meetings_available' => $this->meetingsAvailable(),'ws_url' => $this->config['ws_url']];
    }

    public function history(array $user, int $id, int $before = 0, int $after = 0): array
    {
        $c = $this->conversation($user, $id);
        $params = [$id];
        $cursor = '';
        if ($before > 0) {
            $cursor = ' AND m.id<?';
            $params[] = $before;
        } elseif ($after > 0) {
            $cursor = ' AND m.id>?';
            $params[] = $after;
        }
        $order = $after > 0 ? 'ASC' : 'DESC';
        $messages = $this->repo->all('SELECT m.*,u.name AS sender_name,f.id AS file_id,f.original_name AS file_name,f.size AS file_size FROM portal_chat_messages m JOIN portal_users u ON u.id=m.sender_id LEFT JOIN portal_chat_files f ON f.message_id=m.id WHERE m.conversation_id=? AND m.created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR)'.$cursor.' ORDER BY m.id '.$order.' LIMIT 50', $params);
        $meetings = $this->repo->all('SELECT id,title,starts_at FROM portal_chat_meetings WHERE conversation_id=? ORDER BY starts_at DESC LIMIT 20', [$id]);
        return ['expires_before' => gmdate('Y-m-d H:i:s', time() - 86400),'messages' => $after > 0 ? $messages : array_reverse($messages),'has_more' => count($messages) === 50,'meetings' => $c['policy']['meetings_enabled'] ? $meetings : [],'policy' => $c['policy']];
    }

    public function send(array $user, int $id, array $input, array $upload): array
    {
        $c = $this->conversation($user, $id);
        $body = self::text($input['content'] ?? '', 5000, true);
        $key = self::text($input['client_key'] ?? '', 64);
        if (strlen($key) < 16 || !Text::containsOnly($key, Text::LETTERS . Text::DIGITS . '-')) {
            throw new PortalException('Clé d’envoi invalide.', 422);
        }
        $old = $this->repo->one('SELECT id,conversation_id FROM portal_chat_messages WHERE sender_id=? AND client_key=?', [$user['id'],$key]);
        if ($old) {
            if ((int)$old['conversation_id'] !== $id) {
                throw new PortalException('Clé déjà utilisée.', 409);
            }
            return ['id' => $old['id']];
        }
        $file = null;
        $path = null;
        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (!$c['policy']['files_enabled']) {
                throw new PortalException('Fichiers désactivés.', 403);
            }
            if (($upload['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name']) || $upload['size'] > $this->config['max_file_bytes'] || $upload['size'] < 1) {
                throw new PortalException('Fichier invalide ou supérieur à 10 Mo.', 422);
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
            $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf' => 'application/pdf','png' => 'image/png','jpg' => 'image/jpeg','jpeg' => 'image/jpeg','txt' => 'text/plain'];
            if (in_array($extension, ['docx','xlsx','pptx'], true)) {
                $office = ['docx' => ['word/document.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],'xlsx' => ['xl/workbook.xml','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],'pptx' => ['ppt/presentation.xml','application/vnd.openxmlformats-officedocument.presentationml.presentation']];
                $zip = new \ZipArchive();
                if ($zip->open($upload['tmp_name']) !== true) {
                    throw new PortalException('Document bureautique invalide.', 422);
                }
                $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($office[$extension][0]) !== false;
                $expanded = 0;
                for ($i = 0;$i < $zip->numFiles;$i++) {
                    $stat = $zip->statIndex($i);
                    $expanded += $stat['size'];
                    if (str_contains(strtolower($stat['name']), 'vbaproject') || $expanded > 100 * 1024 * 1024 || $zip->numFiles > 10000) {
                        $valid = false;
                    }
                }
                $zip->close();
                if (!$valid) {
                    throw new PortalException('Document invalide, trop volumineux ou contenant des macros.', 422);
                }
                $mime = $office[$extension][1];
            } elseif (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) {
                throw new PortalException('Formats autorisés : PDF, PNG, JPEG, TXT, DOCX, XLSX et PPTX.', 422);
            }
            $scanner = $this->config['antivirus_binary'] ?? '';
            if ($scanner !== '') {
                $process = proc_open([$scanner,'--no-summary',$upload['tmp_name']], [0 => ['pipe','r'],1 => ['file','/dev/null','w'],2 => ['file','/dev/null','w']], $pipes);
                if (!is_resource($process)) {
                    throw new PortalException('Analyse antivirus indisponible.', 503);
                }
                fclose($pipes[0]);
                $scan = proc_close($process);
                if ($scan !== 0) {
                    throw new PortalException($scan === 1 ? 'Fichier rejeté par l’antivirus.' : 'Analyse antivirus indisponible.', $scan === 1 ? 422 : 503);
                }
            } elseif ($this->config['require_scan'] ?? false) {
                throw new PortalException('L’antivirus doit être configuré avant d’autoriser les fichiers.', 503);
            }
            $name = bin2hex(random_bytes(24));
            $dir = $this->config['storage'];
            if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
                throw new PortalException('Stockage indisponible.', 503);
            }
            $path = $dir.'/'.$name;
            if (!move_uploaded_file($upload['tmp_name'], $path)) {
                throw new PortalException('Stockage indisponible.', 503);
            }
            chmod($path, 0600);
            $file = [$name,mb_substr(str_replace(["\r","\n","\\",'/'], '_', basename($upload['name'])), 0, 190),$mime,$upload['size']];
        }
        if ($body === '' && !$file) {
            throw new PortalException('Écrivez un message ou ajoutez un fichier.', 422);
        }
        try {
            $result = $this->repo->transaction(function () use ($user, $id, $body, $key, $file): array {
                $this->repo->one('SELECT id FROM portal_users WHERE id=? FOR UPDATE', [$user['id']]);
                $existing = $this->repo->one('SELECT id,conversation_id FROM portal_chat_messages WHERE sender_id=? AND client_key=?', [$user['id'],$key]);
                if ($existing) {
                    if ((int)$existing['conversation_id'] !== $id) {
                        throw new PortalException('Clé déjà utilisée.', 409);
                    }
                    return ['id' => $existing['id'],'duplicate' => true];
                }
                $this->conversation($user, $id);
                $count = $this->repo->one('SELECT COUNT(*) AS total FROM portal_chat_messages WHERE sender_id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE)', [$user['id']]);
                if ($count['total'] >= 30) {
                    throw new PortalException('Trop de messages. Réessayez dans une minute.', 429);
                }
                $mid = $this->repo->insert('INSERT INTO portal_chat_messages(conversation_id,sender_id,content,client_key,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())', [$id,$user['id'],$body,$key]);
                if ($file) {
                    $this->repo->execute('INSERT INTO portal_chat_files(message_id,stored_name,original_name,mime,size) VALUES(?,?,?,?,?)', [$mid,...$file]);
                }
                $this->repo->execute('UPDATE portal_chat_conversations SET updated_at=UTC_TIMESTAMP() WHERE id=?', [$id]);
                return ['id' => $mid];
            });
            if (($result['duplicate'] ?? false) && $path && is_file($path)) {
                unlink($path);
            }
            return $result;
        } catch (\Throwable $e) {
            if ($path && is_file($path)) {
                unlink($path);
            }throw $e;
        }
    }

    public function read(array $user, int $id, int $message): void
    {
        $this->conversation($user, $id);
        if (!$this->repo->one('SELECT id FROM portal_chat_messages WHERE conversation_id=? AND id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR)', [$id,$message])) {
            throw new PortalException('Message inaccessible.', 403);
        }
        $this->repo->execute('INSERT INTO portal_chat_participants(conversation_id,user_id,last_read_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE last_read_id=GREATEST(last_read_id,?)', [$id,$user['id'],$message,$message]);
    }

    public function download(array $user, int $id): array
    {
        $f = $this->repo->one('SELECT f.*,m.conversation_id FROM portal_chat_files f JOIN portal_chat_messages m ON m.id=f.message_id WHERE f.id=? AND m.created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR)', [$id]);
        if (!$f) {
            throw new PortalException('Fichier introuvable.', 404);
        }
        $c = $this->conversation($user, (int)$f['conversation_id']);
        if (!$c['policy']['files_enabled']) {
            throw new PortalException('Fichiers désactivés.', 403);
        }
        return $f;
    }

    public function administration(array $user): array
    {
        if ($user['role'] !== 'admin') {
            throw new PortalException('Administration uniquement.', 403);
        }
        return ['cohorts' => $this->repo->all('SELECT id,name FROM portal_cohorts ORDER BY name'),'users' => $this->repo->all('SELECT id,name,role,status FROM portal_users ORDER BY name'),'policies' => $this->repo->all('SELECT * FROM portal_chat_policies'),'access' => $this->repo->all('SELECT * FROM portal_chat_access'),'memberships' => $this->repo->all('SELECT * FROM portal_memberships'),'groups' => $this->repo->all('SELECT id,cohort_id,title FROM portal_chat_conversations WHERE kind="group"'),'participants' => $this->repo->all('SELECT p.* FROM portal_chat_participants p JOIN portal_chat_conversations c ON c.id=p.conversation_id WHERE c.kind="group"')];
    }

    public function permissions(array $user, array $input): void
    {
        $this->administration($user);
        $cohort = self::positive($input['cohort_id'] ?? null);
        if (!$this->repo->one('SELECT id FROM portal_cohorts WHERE id=?', [$cohort])) {
            throw new PortalException('Formation introuvable.', 404);
        }
        $this->repo->transaction(function () use ($user, $input, $cohort): void {
            if (isset($input['user_id'])) {
                $uid = self::positive($input['user_id']);
                if (!in_array($input['access'] ?? '', ['inherit','allow','deny'], true)) {
                    throw new PortalException('Permission invalide.', 422);
                }
                if (!$this->repo->one('SELECT id FROM portal_users WHERE id=?', [$uid])) {
                    throw new PortalException('Utilisateur introuvable.', 404);
                }
                if ($input['access'] === 'inherit') {
                    $this->repo->execute('DELETE FROM portal_chat_access WHERE cohort_id=? AND user_id=?', [$cohort,$uid]);
                } else {
                    $this->repo->execute('INSERT INTO portal_chat_access VALUES(?,?,?) ON DUPLICATE KEY UPDATE allowed=?', [$cohort,$uid,(int)($input['access'] === 'allow'),(int)($input['access'] === 'allow')]);
                }
            } else {
                $flags = [];
                foreach (['enabled','direct_enabled','files_enabled','meetings_enabled'] as $flag) {
                    if (!in_array($input[$flag] ?? null, [0,1,'0','1'], true)) {
                        throw new PortalException('Permission invalide.', 422);
                    }
                    $flags[] = (int)$input[$flag];
                }
                $this->repo->execute('INSERT INTO portal_chat_policies VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=?,direct_enabled=?,files_enabled=?,meetings_enabled=?', [$cohort,...$flags,...$flags]);
            }
            $this->repo->audit((int)$user['id'], 'chat.permissions', $cohort, $input);
        });
    }

    public function meetingsAvailable(): bool
    {
        $url = parse_url($this->config['meeting_url']);
        return is_array($url) && ($url['scheme'] ?? '') === 'https' && !empty($url['host'])
            && empty($url['user']) && empty($url['pass']) && empty($url['query']) && empty($url['fragment'])
            && empty($url['path']) && $this->config['meeting_app_id'] !== '' && strlen($this->config['meeting_secret']) >= 32;
    }

    public function meeting(array $user, int $id, array $input): int
    {
        $c = $this->conversation($user, $id);
        if (!$c['policy']['meetings_enabled'] || !$this->meetingsAvailable()) {
            throw new PortalException('Visioconférence non configurée ou non autorisée.', 403);
        }
        $title = self::text($input['title'] ?? 'Appel', 120);
        $date = self::text($input['starts_at'] ?? gmdate('Y-m-d\TH:i:s\Z'), 30);
        $when = Dates::isoTimestamp($date);
        if ($when === null) {
            throw new PortalException('Date invalide.', 422);
        }
        return $this->repo->transaction(function () use ($id, $user, $title, $when): int {
            $this->repo->one('SELECT id FROM portal_users WHERE id=? FOR UPDATE', [$user['id']]);
            $count = $this->repo->one('SELECT COUNT(*) AS total FROM portal_chat_messages WHERE sender_id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE)', [$user['id']]);
            if ($count['total'] >= 30) {
                throw new PortalException('Trop de messages ou d’appels. Réessayez dans une minute.', 429);
            }
            $this->conversation($user, $id);
            $mid = $this->repo->insert('INSERT INTO portal_chat_meetings(conversation_id,creator_id,title,starts_at,room) VALUES(?,?,?,?,?)', [$id,$user['id'],$title,$when->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),bin2hex(random_bytes(24))]);
            $this->repo->execute('INSERT INTO portal_chat_messages(conversation_id,sender_id,content,client_key,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())', [$id,$user['id'],'Réunion : '.$title.' · '.$when->setTimezone(new \DateTimeZone('Indian/Reunion'))->format('d/m/Y à H:i'),'meeting:'.$mid]);
            $this->repo->execute('UPDATE portal_chat_conversations SET updated_at=UTC_TIMESTAMP() WHERE id=?', [$id]);
            $this->repo->audit((int)$user['id'], 'chat.meeting', $mid);
            return $mid;
        });
    }

    public function join(array $user, int $id): string
    {
        $m = $this->repo->one('SELECT * FROM portal_chat_meetings WHERE id=?', [$id]);
        if (!$m) {
            throw new PortalException('Réunion introuvable.', 404);
        }
        $c = $this->conversation($user, (int)$m['conversation_id']);
        if (!$c['policy']['meetings_enabled'] || !$this->meetingsAvailable()) {
            throw new PortalException('Visioconférence indisponible.', 403);
        }
        // JWT attend du JSON encodé en base64 compatible avec une URL.
        $encode = function (array $data): string {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $base64 = base64_encode($json);
            return rtrim(strtr($base64, '+/', '-_'), '=');
        };
        $header = $encode(['alg' => 'HS256','typ' => 'JWT']);
        $claims = $encode(['aud' => 'jitsi','iss' => $this->config['meeting_app_id'],'sub' => parse_url($this->config['meeting_url'], PHP_URL_HOST),'room' => $m['room'],'iat' => time(),'nbf' => time() - 10,'exp' => time() + 300,'context' => ['user' => ['id' => (string)$user['id'],'name' => $user['name']]]]);
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$claims, $this->config['meeting_secret'], true)), '+/', '-_'), '=');
        return $this->config['meeting_url'].'/'.$m['room'].'?jwt='.$header.'.'.$claims.'.'.$signature;
    }

    public function ticket(array $user): string
    {
        $token = bin2hex(random_bytes(32));
        $this->repo->execute('DELETE FROM portal_chat_tickets WHERE expires_at<UTC_TIMESTAMP()');
        $this->repo->execute('INSERT INTO portal_chat_tickets VALUES(?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 MINUTE))', [hash('sha256', $token),$user['id'],$user['activation_version']]);
        return $token;
    }
}
