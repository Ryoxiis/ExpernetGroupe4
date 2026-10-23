<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PedagogyRepository;
use App\Support\PortalException;

final class PedagogyService
{
    private PedagogyRepository $repo;
    private array $config;

    public function __construct(PedagogyRepository $repo, array $config)
    {
        $this->repo = $repo;
        $this->config = $config;
    }

    private function text(array $input, string $key, int $max = 180): string
    {
        $value = trim((string) ($input[$key] ?? ''));
        if ($value === '' || mb_strlen($value) > $max) {
            throw new PortalException('Le champ « ' . $key . ' » est obligatoire et limité à ' . $max . ' caractères.');
        }
        return $value;
    }

    private function id(array $input, string $key): int
    {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false || $value === null) {
            throw new PortalException('Sélectionnez une valeur valide pour « ' . $key . ' ».');
        }
        return $value;
    }

    private function date(array $input, string $key): string
    {
        $value = $this->text($input, $key, 30);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new \DateTimeZone('Indian/Reunion'));
        if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
            throw new PortalException('Date ou heure invalide.');
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function requireRole(array $user, array $roles): void
    {
        if (!in_array($user['role'], $roles, true)) {
            throw new PortalException('Vous ne disposez pas des droits nécessaires.', 403);
        }
    }

    // Vérifie d'abord le rôle, puis l'accès à la promotion choisie.
    private function authorizedCohort(array $user, array $input, array $roles): int
    {
        $this->requireRole($user, $roles);
        $cohort = $this->id($input, 'cohort_id');
        $this->cohort($user, $cohort);
        return $cohort;
    }

    public function cohort(array $user, int $id): array
    {
        $row = $this->repo->one('SELECT * FROM portal_cohorts WHERE id=?', [$id]);
        if (!$row || ($user['role'] !== 'admin' && !$this->repo->one('SELECT user_id FROM portal_memberships WHERE cohort_id=? AND user_id=?', [$id, $user['id']]))) {
            throw new PortalException('Cette promotion est inaccessible.', 403);
        }
        return $row;
    }

    public function session(array $user, int $id): array
    {
        $row = $this->repo->one('SELECT * FROM portal_sessions WHERE id=?', [$id]);
        if (!$row) {
            throw new PortalException('Séance introuvable.', 404);
        }
        if ($user['role'] === 'trainer') {
            if ((int) $row['trainer_id'] !== (int) $user['id']) {
                throw new PortalException('Cette séance ne vous est pas affectée.', 403);
            }
        } else {
            $this->cohort($user, (int) $row['cohort_id']);
        }
        return $row;
    }

    public function assignment(array $user, int $id): array
    {
        $row = $this->repo->one('SELECT * FROM portal_assignments WHERE id=?', [$id]);
        if (!$row) {
            throw new PortalException('Devoir introuvable.', 404);
        }
        $this->cohort($user, (int) $row['cohort_id']);
        if ($user['role'] === 'trainer' && (int) $row['trainer_id'] !== (int) $user['id']) {
            throw new PortalException('Ce devoir ne vous est pas affecté.', 403);
        }
        return $row;
    }

    private function student(int $id, int $cohort): void
    {
        if (!$this->repo->one('SELECT u.id FROM portal_users u JOIN portal_memberships m ON m.user_id=u.id WHERE u.id=? AND u.role="student" AND m.cohort_id=?', [$id, $cohort])) {
            throw new PortalException('Cet étudiant n’appartient pas à la promotion.', 403);
        }
    }

    public function state(array $user): array
    {
        $cohorts = $user['role'] === 'admin' ? $this->repo->all('SELECT * FROM portal_cohorts ORDER BY name') : $this->repo->all('SELECT c.* FROM portal_cohorts c JOIN portal_memberships m ON m.cohort_id=c.id WHERE m.user_id=? ORDER BY c.name', [$user['id']]);
        $ids = array_column($cohorts, 'id');
        $in = $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';
        $sessions = $this->repo->all("SELECT s.*,u.name AS trainer_name,c.name AS cohort_name FROM portal_sessions s JOIN portal_users u ON u.id=s.trainer_id JOIN portal_cohorts c ON c.id=s.cohort_id WHERE s.cohort_id IN ($in)" . ($user['role'] === 'trainer' ? ' AND s.trainer_id=?' : '') . ' ORDER BY s.starts_at', [...$ids, ...($user['role'] === 'trainer' ? [$user['id']] : [])]);
        $assignments = $this->repo->all("SELECT a.*,c.name AS cohort_name FROM portal_assignments a JOIN portal_cohorts c ON c.id=a.cohort_id WHERE a.cohort_id IN ($in)" . ($user['role'] === 'trainer' ? ' AND a.trainer_id=?' : '') . ' ORDER BY a.due_at', [...$ids, ...($user['role'] === 'trainer' ? [$user['id']] : [])]);
        $assignmentIds = array_column($assignments, 'id');
        $ain = $assignmentIds ? implode(',', array_fill(0, count($assignmentIds), '?')) : 'NULL';
        $sessionIds = array_column($sessions, 'id');
        $sin = $sessionIds ? implode(',', array_fill(0, count($sessionIds), '?')) : 'NULL';
        $windows = $this->repo->all("SELECT w.* FROM portal_windows w WHERE w.session_id IN ($sin) ORDER BY w.opens_at DESC", $sessionIds);
        $wid = array_column($windows, 'id');
        $win = $wid ? implode(',', array_fill(0, count($wid), '?')) : 'NULL';
        $isStudent = $user['role'] === 'student';
        $own = $isStudent ? ' AND student_id=?' : '';
        $ownParams = $isStudent ? [$user['id']] : [];
        $attendance = $this->repo->all("SELECT * FROM portal_attendance WHERE window_id IN ($win)$own", [...$wid, ...$ownParams]);
        $grades = $this->repo->all("SELECT * FROM portal_grades WHERE assignment_id IN ($ain)$own" . ($isStudent ? ' AND published=1' : ''), [...$assignmentIds, ...$ownParams]);
        $submissions = $this->repo->all("SELECT * FROM portal_submissions WHERE assignment_id IN ($ain)$own", [...$assignmentIds, ...$ownParams]);
        $people = $user['role'] === 'admin' ? $this->repo->all('SELECT id,name,email,role,status FROM portal_users ORDER BY name') : $this->repo->all("SELECT DISTINCT u.id,u.name,u.role FROM portal_users u JOIN portal_memberships m ON m.user_id=u.id WHERE m.cohort_id IN ($in)" . ($isStudent ? ' AND (u.id=? OR u.role="trainer")' : '') . ' ORDER BY u.name', [...$ids, ...$ownParams]);
        $exams = $this->repo->all("SELECT e.* FROM portal_exams e WHERE e.cohort_id IN ($in)" . ($user['role'] === 'trainer' ? ' AND EXISTS(SELECT 1 FROM portal_jury j WHERE j.exam_id=e.id AND j.user_id=?)' : '') . ' ORDER BY e.starts_at', [...$ids, ...($user['role'] === 'trainer' ? [$user['id']] : [])]);
        $examIds = array_column($exams, 'id');
        $ein = $examIds ? implode(',', array_fill(0, count($examIds), '?')) : 'NULL';
        $criteria = $this->repo->all("SELECT * FROM portal_competencies WHERE exam_id IN ($ein)", $examIds);
        $criteriaIds = array_column($criteria, 'id');
        $cin = $criteriaIds ? implode(',', array_fill(0, count($criteriaIds), '?')) : 'NULL';
        return compact('cohorts', 'sessions', 'assignments', 'windows', 'attendance', 'grades', 'submissions', 'people', 'exams', 'criteria') + [
            'resources' => $this->repo->all("SELECT id,cohort_id,session_id,author_id,title,original_name,created_at FROM portal_resources WHERE cohort_id IN ($in) ORDER BY created_at DESC", $ids),
            'memberships' => $this->repo->all("SELECT * FROM portal_memberships WHERE cohort_id IN ($in)" . ($isStudent ? ' AND user_id=?' : ''), [...$ids, ...$ownParams]),
            'messages' => $this->repo->all("SELECT m.*,u.name AS author_name FROM portal_messages m JOIN portal_users u ON u.id=m.author_id WHERE m.cohort_id IN ($in) ORDER BY m.created_at DESC LIMIT 100", $ids),
            'unavailability' => $user['role'] === 'student' ? [] : $this->repo->all('SELECT * FROM portal_unavailability' . ($user['role'] === 'trainer' ? ' WHERE trainer_id=?' : '') . ' ORDER BY starts_at DESC', $user['role'] === 'trainer' ? [$user['id']] : []),
            'journal' => $this->repo->all("SELECT * FROM portal_journal WHERE cohort_id IN ($in)" . $own . ' ORDER BY created_at DESC LIMIT 100', [...$ids, ...$ownParams]),
            'feedback' => $user['role'] === 'admin' ? $this->repo->all('SELECT cohort_id,AVG(rating) AS average,COUNT(*) AS total FROM portal_feedback GROUP BY cohort_id') : ($isStudent ? $this->repo->all('SELECT * FROM portal_feedback WHERE student_id=?', [$user['id']]) : []),
            'jury' => $this->repo->all("SELECT j.exam_id,u.name FROM portal_jury j JOIN portal_users u ON u.id=j.user_id WHERE j.exam_id IN ($ein)", $examIds),
            'evaluations' => $this->repo->all("SELECT * FROM portal_evaluations WHERE competency_id IN ($cin)$own", [...$criteriaIds, ...$ownParams]),
            'audit' => $user['role'] === 'admin' ? $this->repo->all('SELECT a.*,u.name AS actor_name FROM portal_audit a JOIN portal_users u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 30') : [],
        ];
    }

    public function mutate(array $user, string $action, array $input, array $files = []): array
    {
        return $this->repo->transaction(function () use ($user, $action, $input, $files): array {
            // Serialize permission changes and business writes with account deactivation.
            $actor = $this->repo->one('SELECT status,role,activation_version FROM portal_users WHERE id=? FOR UPDATE', [$user['id']]);
            if (!$actor || $actor['status'] !== 'active' || $actor['role'] !== $user['role'] || (int) $actor['activation_version'] !== (int) $user['activation_version']) {
                throw new PortalException('Votre session a été révoquée. Reconnectez-vous.', 401);
            }
            switch ($action) {
                case 'cohorts':
                    $result = $this->createCohort($user, $input);
                    break;
                case 'users':
                    $result = $this->createUser($user, $input);
                    break;
                case 'invitations':
                    $result = $this->invite($user, $input);
                    break;
                case 'accounts':
                    $result = $this->account($user, $input);
                    break;
                case 'memberships':
                    $result = $this->membership($user, $input);
                    break;
                case 'sessions':
                    $result = $this->createSession($user, $input);
                    break;
                case 'unavailability':
                    $result = $this->unavailability($user, $input);
                    break;
                case 'windows':
                    $result = $this->openWindow($user, $input);
                    break;
                case 'attendance':
                    $result = $this->declareAttendance($user, $input);
                    break;
                case 'attendance-review':
                    $result = $this->reviewAttendance($user, $input);
                    break;
                case 'assignments':
                    $result = $this->createAssignment($user, $input);
                    break;
                case 'submissions':
                    $result = $this->submit($user, $input);
                    break;
                case 'grades':
                    $result = $this->grade($user, $input);
                    break;
                case 'resources':
                    $result = $this->upload($user, $input, $files);
                    break;
                case 'messages':
                    $result = $this->message($user, $input);
                    break;
                case 'exams':
                    $result = $this->exam($user, $input);
                    break;
                case 'evaluations':
                    $result = $this->evaluate($user, $input);
                    break;
                case 'journal':
                    $result = $this->journal($user, $input);
                    break;
                case 'feedback':
                    $result = $this->feedback($user, $input);
                    break;
                default:
                    throw new PortalException('Action introuvable.', 404);
            }
            return $result + ['message' => 'Enregistrement effectué.'];
        });
    }

    private function createCohort(array $user, array $input): array
    {
        $this->requireRole($user, ['admin']);
        $start = $this->text($input, 'starts_on', 10);
        $end = $this->text($input, 'ends_on', 10);
        foreach ([$start, $end] as $date) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                throw new PortalException('Dates de promotion invalides.');
            }
        }
        if ($end < $start) {
            throw new PortalException('La fin doit suivre le début de la formation.');
        }
        return ['id' => $this->repo->insert('INSERT INTO portal_cohorts(name,starts_on,ends_on) VALUES(?,?,?)', [$this->text($input, 'name', 120), $start, $end])];
    }

    private function createUser(array $user, array $input): array
    {
        $this->requireRole($user, ['admin']);
        $email = strtolower($this->text($input, 'email', 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || substr(strrchr($email, '@') ?: '', 1) !== $this->config['email_domain']) {
            throw new PortalException('Utilisez une adresse interne @' . $this->config['email_domain'] . '.');
        }
        $role = $this->text($input, 'role', 20);
        if (!in_array($role, ['student', 'trainer', 'admin'], true)) {
            throw new PortalException('Rôle invalide.');
        }
        if ($this->repo->one('SELECT id FROM portal_users WHERE email=?', [$email])) {
            throw new PortalException('Un compte utilise déjà cette adresse.', 409);
        }
        $id = $this->repo->insert('INSERT INTO portal_users(name,email,role,created_at) VALUES(?,?,?,UTC_TIMESTAMP())', [$this->text($input, 'name', 120), $email, $role]);
        return $this->issueInvitation($user, $id);
    }

    private function invite(array $user, array $input): array
    {
        $this->requireRole($user, ['admin']);
        return $this->issueInvitation($user, $this->id($input, 'user_id'));
    }

    private function issueInvitation(array $actor, int $id): array
    {
        $target = $this->repo->one('SELECT id,status FROM portal_users WHERE id=? FOR UPDATE', [$id]);
        if (!$target || $target['status'] === 'disabled' || $id === (int) $actor['id']) {
            throw new PortalException('Compte désactivé, introuvable ou action sur votre propre compte refusée.');
        }
        $secret = bin2hex(random_bytes(12));
        $expires = gmdate('Y-m-d H:i:s', time() + $this->config['temporary_hours'] * 3600);
        $this->repo->execute('UPDATE portal_users SET status="invited",password_hash=NULL,temporary_hash=?,temporary_expires=?,activation_version=activation_version+1,mfa_secret=NULL,mfa_last_counter=NULL WHERE id=?', [password_hash($secret, PASSWORD_DEFAULT), $expires, $id]);
        $this->repo->audit((int) $actor['id'], 'account.invited', $id);
        return ['id' => $id, 'temporary_password' => $secret, 'expires_at' => $expires, 'message' => 'Accès temporaire créé. Aucun e-mail envoyé : transmettez ce secret par le canal interne autorisé. Il ne sera affiché qu’une fois.'];
    }

    private function account(array $user, array $input): array
    {
        $this->requireRole($user, ['admin']);
        $id = $this->id($input, 'user_id');
        if ($id === (int) $user['id']) {
            throw new PortalException('Vous ne pouvez pas désactiver votre propre compte.');
        }
        if (!$this->repo->one('SELECT id FROM portal_users WHERE id=?', [$id])) {
            throw new PortalException('Compte introuvable.', 404);
        }
        $this->repo->execute('UPDATE portal_users SET status="disabled",activation_version=activation_version+1,temporary_hash=NULL,temporary_expires=NULL WHERE id=?', [$id]);
        $this->repo->audit((int) $user['id'], 'account.disabled', $id);
        return [];
    }

    private function membership(array $user, array $input): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['admin']);
        $id = $this->id($input, 'user_id');
        if (!$this->repo->one('SELECT id FROM portal_users WHERE id=? AND role IN ("student","trainer") AND status <> "disabled"', [$id])) {
            throw new PortalException('Choisissez un étudiant ou formateur actif ou invité.');
        }
        $this->repo->execute('INSERT IGNORE INTO portal_memberships(cohort_id,user_id) VALUES(?,?)', [$cohort, $id]);
        $this->repo->audit((int) $user['id'], 'membership.assigned', $id, ['cohort_id' => $cohort]);
        return [];
    }

    private function createSession(array $user, array $input): array
    {
        $this->requireRole($user, ['admin']);
        $this->repo->one('SELECT name FROM portal_locks WHERE name="schedule" FOR UPDATE');
        $cohort = $this->id($input, 'cohort_id');
        $this->cohort($user, $cohort);
        $trainer = $this->id($input, 'trainer_id');
        // Lock trainer so two concurrent session creations cannot ignore each other.
        if (!$this->repo->one('SELECT id FROM portal_users WHERE id=? AND role="trainer" AND status<>"disabled" FOR UPDATE', [$trainer])) {
            throw new PortalException('Formateur indisponible ou invalide.');
        }
        $this->repo->one('SELECT id FROM portal_cohorts WHERE id=? FOR UPDATE', [$cohort]);
        if (!$this->repo->one('SELECT user_id FROM portal_memberships WHERE cohort_id=? AND user_id=?', [$cohort, $trainer])) {
            throw new PortalException('Affectez d’abord le formateur à cette promotion.');
        }
        $start = $this->date($input, 'starts_at');
        $end = $this->date($input, 'ends_at');
        if ($end <= $start) {
            throw new PortalException('La fin de séance doit suivre son début.');
        }
        $mode = $this->text($input, 'mode', 20);
        if (!in_array($mode, ['onsite', 'remote', 'company'], true)) {
            throw new PortalException('Modalité invalide.');
        }
        $location = $this->text($input, 'location');
        $meeting = trim((string) ($input['meeting_url'] ?? ''));
        if ($meeting !== '' && (!filter_var($meeting, FILTER_VALIDATE_URL) || parse_url($meeting, PHP_URL_SCHEME) !== 'https' || strlen($meeting) > 500)) {
            throw new PortalException('Le lien de réunion doit être une adresse HTTPS valide.');
        }
        // Shared schedule lock serializes room conflicts across different administrators.
        if ($this->repo->one('SELECT id FROM portal_sessions WHERE starts_at < ? AND ends_at > ? AND (trainer_id=? OR cohort_id=? OR (mode="onsite" AND ?="onsite" AND location=?))', [$end, $start, $trainer, $cohort, $mode, $location]) || $this->repo->one('SELECT id FROM portal_unavailability WHERE trainer_id=? AND starts_at < ? AND ends_at > ?', [$trainer, $end, $start])) {
            throw new PortalException('Conflit : formateur, promotion ou salle déjà occupé, ou formateur indisponible.', 409);
        }
        $id = $this->repo->insert('INSERT INTO portal_sessions(cohort_id,trainer_id,title,starts_at,ends_at,mode,location,meeting_url) VALUES(?,?,?,?,?,?,?,?)', [$cohort, $trainer, $this->text($input, 'title'), $start, $end, $mode, $location, $meeting ?: null]);
        $this->repo->audit((int) $user['id'], 'session.created', $id);
        return ['id' => $id];
    }

    private function unavailability(array $user, array $input): array
    {
        $this->requireRole($user, ['trainer']);
        $start = $this->date($input, 'starts_at');
        $end = $this->date($input, 'ends_at');
        if ($end <= $start) {
            throw new PortalException('La fin doit suivre le début.');
        }
        $id = $this->repo->insert('INSERT INTO portal_unavailability(trainer_id,starts_at,ends_at,reason) VALUES(?,?,?,?)', [$user['id'], $start, $end, $this->text($input, 'reason', 250)]);
        $conflicts = $this->repo->all('SELECT id FROM portal_sessions WHERE trainer_id=? AND starts_at < ? AND ends_at > ?', [$user['id'], $end, $start]);
        $this->repo->audit((int) $user['id'], 'unavailability.created', $id, ['conflicts' => array_column($conflicts, 'id')]);
        return ['id' => $id, 'message' => 'Indisponibilité immédiatement enregistrée.' . ($conflicts ? ' Attention : ' . count($conflicts) . ' séance(s) en conflit. Elles sont conservées ; contactez l’administration.' : '')];
    }

    private function openWindow(array $user, array $input): array
    {
        $this->requireRole($user, ['trainer', 'admin']);
        $session = $this->session($user, $this->id($input, 'session_id'));
        $now = gmdate('Y-m-d H:i:s');
        if ($now < $session['starts_at'] || $now >= $session['ends_at']) {
            throw new PortalException('L’émargement ne peut être ouvert que pendant la séance.');
        }
        $this->repo->one('SELECT id FROM portal_sessions WHERE id=? FOR UPDATE', [$session['id']]);
        if ($this->repo->one('SELECT id FROM portal_windows WHERE session_id=? AND closes_at>UTC_TIMESTAMP()', [$session['id']])) {
            throw new PortalException('Une période d’émargement est déjà ouverte.', 409);
        }
        $closes = min($session['ends_at'], gmdate('Y-m-d H:i:s', time() + 900));
        return ['id' => $this->repo->insert('INSERT INTO portal_windows(session_id,opened_by,opens_at,closes_at) VALUES(?,?,UTC_TIMESTAMP(),?)', [$session['id'], $user['id'], $closes]), 'message' => 'Émargement ouvert pour 15 minutes au maximum, jusqu’à la fin de la séance.'];
    }

    private function window(array $user, int $id): array
    {
        $window = $this->repo->one('SELECT * FROM portal_windows WHERE id=? FOR UPDATE', [$id]);
        if (!$window) {
            throw new PortalException('Période d’émargement introuvable.', 404);
        }
        $this->session($user, (int) $window['session_id']);
        return $window;
    }

    private function declareAttendance(array $user, array $input): array
    {
        $this->requireRole($user, ['student']);
        $window = $this->window($user, $this->id($input, 'window_id'));
        $now = gmdate('Y-m-d H:i:s');
        if ($now < $window['opens_at'] || $now >= $window['closes_at']) {
            throw new PortalException('Cette période d’émargement est fermée.', 409);
        }
        if ($this->repo->one('SELECT id FROM portal_attendance WHERE window_id=? AND student_id=?', [$window['id'], $user['id']])) {
            throw new PortalException('Votre présence a déjà été enregistrée pour cette période.', 409);
        }
        $id = $this->repo->insert('INSERT INTO portal_attendance(window_id,student_id,status,declared_at,updated_at) VALUES(?,?,"declared",UTC_TIMESTAMP(),UTC_TIMESTAMP())', [$window['id'], $user['id']]);
        $this->repo->audit((int) $user['id'], 'attendance.declared', $id);
        return ['id' => $id, 'message' => 'Présence déclarée à ' . date('H:i') . ', en attente de validation du formateur.'];
    }

    private function reviewAttendance(array $user, array $input): array
    {
        $this->requireRole($user, ['trainer', 'admin']);
        $window = $this->window($user, $this->id($input, 'window_id'));
        $session = $this->session($user, (int) $window['session_id']);
        $student = $this->id($input, 'student_id');
        $this->student($student, (int) $session['cohort_id']);
        $status = $this->text($input, 'status', 20);
        if (!in_array($status, ['validated', 'late', 'absent', 'excused'], true)) {
            throw new PortalException('Statut de présence invalide.');
        }
        $reason = $this->text($input, 'reason', 500);
        $previous = $this->repo->one('SELECT * FROM portal_attendance WHERE window_id=? AND student_id=?', [$window['id'], $student]);
        $this->repo->execute('INSERT INTO portal_attendance(window_id,student_id,status,updated_at) VALUES(?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE status=VALUES(status),updated_at=UTC_TIMESTAMP()', [$window['id'], $student, $status]);
        $this->repo->audit((int) $user['id'], 'attendance.reviewed', (int) $window['id'], ['student_id' => $student, 'before' => $previous, 'after' => $status, 'reason' => $reason]);
        return [];
    }

    private function createAssignment(array $user, array $input): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['trainer', 'admin']);
        $trainer = (int) $user['id'];
        if ($user['role'] === 'admin') {
            throw new PortalException('La création de devoirs est réservée aux formateurs affectés.');
        }
        $session = !empty($input['session_id']) ? $this->session($user, $this->id($input, 'session_id')) : null;
        if ($session && (int) $session['cohort_id'] !== $cohort) {
            throw new PortalException('La séance ne correspond pas à la promotion.');
        }
        return ['id' => $this->repo->insert('INSERT INTO portal_assignments(cohort_id,trainer_id,session_id,title,description,due_at) VALUES(?,?,?,?,?,?)', [$cohort, $trainer, $session['id'] ?? null, $this->text($input, 'title'), $this->text($input, 'description', 10000), $this->date($input, 'due_at')])];
    }

    private function submit(array $user, array $input): array
    {
        $this->requireRole($user, ['student']);
        $assignment = $this->assignment($user, $this->id($input, 'assignment_id'));
        if ($assignment['due_at'] < gmdate('Y-m-d H:i:s')) {
            throw new PortalException('L’échéance de ce devoir est passée. Contactez le formateur.');
        }
        $this->repo->execute('INSERT INTO portal_submissions(assignment_id,student_id,body,submitted_at) VALUES(?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE body=VALUES(body),submitted_at=UTC_TIMESTAMP()', [$assignment['id'], $user['id'], $this->text($input, 'body', 20000)]);
        return ['message' => 'Votre travail a été enregistré. Vous pouvez le modifier jusqu’à l’échéance.'];
    }

    private function grade(array $user, array $input): array
    {
        $this->requireRole($user, ['trainer', 'admin']);
        $assignment = $this->assignment($user, $this->id($input, 'assignment_id'));
        $student = $this->id($input, 'student_id');
        $this->student($student, (int) $assignment['cohort_id']);
        $value = filter_var($input['value'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value === null || $value < 0 || $value > (float) $assignment['max_grade']) {
            throw new PortalException('La note doit être comprise entre 0 et ' . $assignment['max_grade'] . '.');
        }
        $comment = trim((string) ($input['comment'] ?? ''));
        if (mb_strlen($comment) > 5000) {
            throw new PortalException('Commentaire trop long.');
        }
        $previous = $this->repo->one('SELECT * FROM portal_grades WHERE assignment_id=? AND student_id=?', [$assignment['id'], $student]);
        $published = !empty($input['published']) ? 1 : 0;
        $this->repo->execute('INSERT INTO portal_grades(assignment_id,student_id,value,comment,published,updated_at) VALUES(?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE value=VALUES(value),comment=VALUES(comment),published=VALUES(published),updated_at=UTC_TIMESTAMP()', [$assignment['id'], $student, $value, $comment, $published]);
        $this->repo->audit((int) $user['id'], 'grade.saved', (int) $assignment['id'], ['student_id' => $student, 'before' => $previous, 'after' => $value, 'published' => $published]);
        return ['message' => $published ? 'Note enregistrée et publiée à l’étudiant.' : 'Brouillon enregistré. Cette note est invisible à l’étudiant.'];
    }

    private function upload(array $user, array $input, array $files): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['trainer', 'admin']);
        $session = !empty($input['session_id']) ? $this->session($user, $this->id($input, 'session_id')) : null;
        if ($session && (int) $session['cohort_id'] !== $cohort) {
            throw new PortalException('Séance et promotion incompatibles.');
        }
        $title = $this->text($input, 'title');
        $file = $files['document'] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new PortalException('Choisissez un fichier PDF, texte ou image de 5 Mo maximum.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['application/pdf' => 'pdf', 'text/plain' => 'txt', 'image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!isset($allowed[$mime])) {
            throw new PortalException('Format refusé. Formats acceptés : PDF, TXT, PNG et JPG.');
        }
        $directory = $this->config['storage'];
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new PortalException('Stockage momentanément indisponible.', 503);
        }
        $name = bin2hex(random_bytes(24)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            throw new PortalException('Le transfert a échoué. Aucun document enregistré.', 503);
        }
        chmod($directory . '/' . $name, 0600);
        return ['id' => $this->repo->insert('INSERT INTO portal_resources(cohort_id,session_id,author_id,title,stored_name,original_name,mime,created_at) VALUES(?,?,?,?,?,?,?,UTC_TIMESTAMP())', [$cohort, $session['id'] ?? null, $user['id'], $title, $name, mb_substr(basename($file['name']), 0, 190), $mime])];
    }

    public function resource(array $user, int $id): array
    {
        $row = $this->repo->one('SELECT * FROM portal_resources WHERE id=?', [$id]);
        if (!$row) {
            throw new PortalException('Document introuvable.', 404);
        }
        $this->cohort($user, (int) $row['cohort_id']);
        return $row;
    }

    private function message(array $user, array $input): array
    {
        throw new PortalException('Utilisez le nouvel espace Discussions pour envoyer un message.', 410);
    }

    private function exam(array $user, array $input): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['admin']);
        $jury = array_unique(array_map('intval', (array) ($input['jury'] ?? [])));
        if (!$jury || count($jury) > 20) {
            throw new PortalException('Sélectionnez au moins un membre du jury (20 maximum).');
        }
        foreach ($jury as $member) {
            if (!$this->repo->one('SELECT u.id FROM portal_users u JOIN portal_memberships m ON m.user_id=u.id WHERE u.id=? AND u.role="trainer" AND u.status<>"disabled" AND m.cohort_id=?', [$member, $cohort])) {
                throw new PortalException('Les membres du jury doivent être des formateurs affectés à cette promotion.');
            }
        }
        $lines = explode("\n", $this->text($input, 'criteria', 5000));
        $criteria = [];
        foreach ($lines as $line) {
            $criterion = trim($line);
            // Même comportement que le filtrage précédent : ignorer les lignes vides et "0".
            if ($criterion === '' || $criterion === '0') {
                continue;
            }
            if (mb_strlen($criterion) > 250) {
                throw new PortalException('Maximum 20 compétences de 250 caractères chacune.');
            }
            $criteria[] = $criterion;
        }
        if (count($criteria) > 20) {
            throw new PortalException('Maximum 20 compétences de 250 caractères chacune.');
        }
        $id = $this->repo->insert('INSERT INTO portal_exams(cohort_id,title,starts_at,location) VALUES(?,?,?,?)', [$cohort, $this->text($input, 'title'), $this->date($input, 'starts_at'), $this->text($input, 'location')]);
        foreach ($jury as $member) {
            $this->repo->execute('INSERT INTO portal_jury(exam_id,user_id) VALUES(?,?)', [$id, $member]);
        }
        foreach ($criteria as $criterion) {
            $this->repo->execute('INSERT INTO portal_competencies(exam_id,label) VALUES(?,?)', [$id, $criterion]);
        }
        $this->repo->audit((int) $user['id'], 'exam.created', $id);
        return ['id' => $id];
    }

    private function evaluate(array $user, array $input): array
    {
        $this->requireRole($user, ['trainer', 'admin']);
        $criterion = $this->repo->one('SELECT c.*,e.cohort_id FROM portal_competencies c JOIN portal_exams e ON e.id=c.exam_id WHERE c.id=?', [$this->id($input, 'competency_id')]);
        if (!$criterion || ($user['role'] !== 'admin' && !$this->repo->one('SELECT user_id FROM portal_jury WHERE exam_id=? AND user_id=?', [$criterion['exam_id'], $user['id']]))) {
            throw new PortalException('Cette grille ne vous est pas affectée.', 403);
        }
        $this->cohort($user, (int) $criterion['cohort_id']);
        $student = $this->id($input, 'student_id');
        $this->student($student, (int) $criterion['cohort_id']);
        $level = $this->text($input, 'level', 20);
        if (!in_array($level, ['not_acquired', 'developing', 'acquired'], true)) {
            throw new PortalException('Niveau de compétence invalide.');
        }
        $comment = $this->text($input, 'comment', 5000);
        $before = $this->repo->one('SELECT * FROM portal_evaluations WHERE competency_id=? AND student_id=?', [$criterion['id'], $student]);
        $this->repo->execute('INSERT INTO portal_evaluations(competency_id,student_id,evaluator_id,level,comment,updated_at) VALUES(?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE evaluator_id=VALUES(evaluator_id),level=VALUES(level),comment=VALUES(comment),updated_at=UTC_TIMESTAMP()', [$criterion['id'], $student, $user['id'], $level, $comment]);
        $this->repo->audit((int) $user['id'], 'competency.evaluated', (int) $criterion['id'], ['student_id' => $student, 'before' => $before, 'after' => $level]);
        return [];
    }

    private function journal(array $user, array $input): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['student']);
        return ['id' => $this->repo->insert('INSERT INTO portal_journal(cohort_id,student_id,body,created_at) VALUES(?,?,?,UTC_TIMESTAMP())', [$cohort, $user['id'], $this->text($input, 'body', 10000)])];
    }

    private function feedback(array $user, array $input): array
    {
        $cohort = $this->authorizedCohort($user, $input, ['student']);
        $rating = $this->id($input, 'rating');
        if ($rating > 5) {
            throw new PortalException('Choisissez une note entre 1 et 5.');
        }
        $this->repo->execute('INSERT INTO portal_feedback(cohort_id,student_id,rating,body,updated_at) VALUES(?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE rating=VALUES(rating),body=VALUES(body),updated_at=UTC_TIMESTAMP()', [$cohort, $user['id'], $rating, $this->text($input, 'body', 5000)]);
        return ['message' => 'Votre avis a été enregistré. Il est identifié et réservé à l’administration ; il n’est pas anonyme.'];
    }
}
