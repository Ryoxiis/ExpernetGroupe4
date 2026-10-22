<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PedagogyRepository;
use App\Services\PedagogyService;
use App\Services\PortalAuth;
use App\Support\Database;
use App\Support\PortalException;
use App\Support\PortalSecurity;
use App\Support\View;
use App\Support\RequestData;

final class PedagogyController
{
    private array $config;
    private ?PedagogyRepository $repo = null;
    private ?PortalAuth $auth = null;
    private ?PedagogyService $service = null;

    public function __construct(private readonly View $view)
    {
        $this->config = require PROJECT_ROOT . '/config/pedagogy.php';
    }

    public function handles(string $path): bool
    {
        return str_starts_with($path, '/connexion/') || str_starts_with($path, '/espace-pedagogique/') || str_starts_with($path, '/api/pedagogie/');
    }

    private function connect(): void
    {
        if ($this->repo) {
            return;
        }
        $this->repo = new PedagogyRepository(Database::connect(require PROJECT_ROOT . '/config/database.php'));
        $this->auth = new PortalAuth($this->repo, $this->config);
        $this->service = new PedagogyService($this->repo, $this->config);
    }

    private function redirect(string $path): void
    {
        header('Location: ' . url($path), true, 303);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function render(string $template, array $data = []): void
    {
        // Le contenu de la page est inséré dans la structure commune du portail.
        $shared = ['csrf' => PortalSecurity::csrf(), 'config' => $this->config];
        $body = $this->view->render('pedagogy/' . $template, $data + $shared);
        echo $this->view->render('pedagogy/layout', $shared + [
            'view' => $this->view,
            'body' => $body,
            'title' => $data['title'] ?? 'Espace pédagogique',
            'user' => $data['user'] ?? null,
        ]);
    }

    public function handle(string $path, string $method): void
    {
        PortalSecurity::start();
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        $chatConfig = require PROJECT_ROOT . '/config/chat.php';
        $wsUrl = $chatConfig['ws_url'];
        $wsSource = preg_match('~^wss?://[a-zA-Z0-9.\\[\\]:-]+(?:/[a-zA-Z0-9/_-]*)?$~D', $wsUrl) ? ' ' . $wsUrl : '';
        header("Content-Security-Policy: default-src 'self'; connect-src 'self'" . $wsSource . "; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        $api = str_starts_with($path, '/api/pedagogie/');
        try {
            if (!in_array($method, ['GET', 'POST', 'HEAD'], true)) {
                header('Allow: GET, POST, HEAD');
                throw new PortalException('Méthode non autorisée.', 405);
            }
            $input = $method === 'POST' ? RequestData::read() : [];
            if ($method === 'POST') {
                PortalSecurity::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? null));
            }
            if ($path === '/connexion/deconnexion/' && $method === 'POST') {
                $_SESSION = [];
                session_regenerate_id(true);
                $this->redirect('/connexion/');
                return;
            }
            if ($path === '/connexion/' && $method !== 'POST') {
                // The login form remains available even before MySQL is installed.
                if (isset($_SESSION['portal_user'])) {
                    $this->connect();
                    if ($this->auth->current()) {
                        $this->redirect('/espace-pedagogique/');
                        return;
                    }
                }
                $this->render('login', ['title' => 'Connexion à votre espace pédagogique', 'stage' => 'login']);
                return;
            }
            if ($path === '/connexion/aide/' && $method !== 'POST') {
                $this->render('login', ['title' => 'Retrouver mon accès', 'stage' => 'help']);
                return;
            }
            $this->connect();
            if ($path === '/connexion/' && $method === 'POST') {
                $this->redirect($this->auth->login((string) ($input['email'] ?? ''), (string) ($input['password'] ?? '')));
                return;
            }
            if ($path === '/connexion/premiere-connexion/') {
                $this->auth->pending('password');
                if ($method === 'POST') {
                    $this->redirect($this->auth->changeInitialPassword((string) ($input['password'] ?? ''), (string) ($input['confirmation'] ?? '')));
                    return;
                }
                $this->render('login', ['title' => 'Choisir mon mot de passe', 'stage' => 'password']);
                return;
            }
            if ($path === '/connexion/verification/') {
                if ($method === 'POST') {
                    $this->redirect($this->auth->verifyMfa(trim((string) ($input['code'] ?? ''))));
                    return;
                }
                $this->render('login', ['title' => 'Vérifier mon identité', 'stage' => 'mfa', 'mfa' => $this->auth->mfaSetup()]);
                return;
            }
            $user = $this->auth->requireUser();
            if ($api) {
                $action = trim(substr($path, strlen('/api/pedagogie/')), '/');
                if ($method !== 'POST' && $action === 'etat') {
                    $this->json(['user' => $user, 'csrf' => PortalSecurity::csrf(), 'demo' => $this->config['demo'], 'data' => $this->service->state($user)]);
                } elseif ($method === 'POST') {
                    $this->json($this->service->mutate($user, $action, $input, $_FILES), 201);
                } else {
                    throw new PortalException('Endpoint introuvable.', 404);
                }
                return;
            }
            if ($method === 'POST') {
                if ($path !== '/espace-pedagogique/action/') {
                    throw new PortalException('Action introuvable.', 404);
                }
                $_SESSION['portal_flash'] = $this->service->mutate($user, (string) ($input['action'] ?? ''), $input, $_FILES);
                $page = (string) ($input['return'] ?? 'accueil');
                $allowed = array_keys($this->config['pages']);
                $this->redirect('/espace-pedagogique/' . (in_array($page, $allowed, true) ? $page . '/' : '') . ($page === 'seance' ? '?id=' . (int) ($input['session_id'] ?? 0) : ''));
                return;
            }
            if ($path === '/espace-pedagogique/document/') {
                $resource = $this->service->resource($user, (int) ($_GET['id'] ?? 0));
                $file = $this->config['storage'] . '/' . $resource['stored_name'];
                if (!is_file($file)) {
                    throw new PortalException('Le fichier n’est plus disponible.', 404);
                }
                header('Content-Type: ' . $resource['mime']);
                header('Content-Disposition: attachment; filename="document.' . pathinfo($resource['stored_name'], PATHINFO_EXTENSION) . '"; filename*=UTF-8\'\'' . rawurlencode($resource['original_name']));
                header('Content-Length: ' . filesize($file));
                readfile($file);
                return;
            }
            $state = $this->service->state($user);
            if ($path === '/espace-pedagogique/calendrier/') {
                $this->calendar($state['sessions']);
                return;
            }
            if ($path === '/espace-pedagogique/attestation/') {
                $this->render('attestation', ['title' => 'Relevé des présences', 'user' => $user, 'state' => $state]);
                return;
            }
            $page = trim(substr($path, strlen('/espace-pedagogique/')), '/') ?: 'accueil';
            $allowed = array_keys($this->config['pages']);
            if (!in_array($page, $allowed, true)) {
                throw new PortalException('Page introuvable.', 404);
            }
            if (($page === 'administration' && $user['role'] !== 'admin') || ($page === 'classes' && $user['role'] === 'student')) {
                throw new PortalException('Vous ne disposez pas des droits nécessaires.', 403);
            }
            $session = $page === 'seance' ? $this->service->session($user, (int) ($_GET['id'] ?? 0)) : null;
            $flash = $_SESSION['portal_flash'] ?? null;
            unset($_SESSION['portal_flash']);
            $this->render('portal', ['title' => 'Mon espace pédagogique', 'user' => $user, 'state' => $state, 'page' => $page, 'session' => $session, 'flash' => $flash]);
        } catch (PortalException $error) {
            if ($api) {
                $this->json(['error' => $error->getMessage()], $error->status);
                return;
            }
            http_response_code($error->status);
            if (str_starts_with($path, '/connexion/') && !str_contains($path, 'deconnexion')) {
                $stage = $path === '/connexion/premiere-connexion/' && isset($_SESSION['pending']) ? 'password' : ($path === '/connexion/verification/' && isset($_SESSION['pending']) ? 'mfa' : 'login');
                $data = ['title' => 'Connexion à votre espace pédagogique', 'stage' => $stage, 'error' => $error->getMessage()];
                if ($stage === 'mfa') {
                    $data['mfa'] = $this->auth->mfaSetup();
                }
                $this->render('login', $data);
            } elseif ($error->status === 401) {
                $this->redirect('/connexion/');
            } else {
                $this->render('error', ['title' => 'Action non effectuée', 'message' => $error->getMessage()]);
            }
        } catch (\PDOException $error) {
            error_log('Pedagogy database error: ' . $error->getCode());
            if ($api) {
                $this->json(['error' => 'Le service pédagogique est indisponible. Réessayez ou contactez l’administration.'], 503);
            } else {
                http_response_code(503);
                $this->render('error', ['title' => 'Espace momentanément indisponible', 'message' => 'La base pédagogique n’est pas disponible. Réessayez plus tard ou contactez l’administration.']);
            }
        }
    }

    private function calendar(array $sessions): void
    {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="planning-expernet.ics"');
        $escape = static fn (string $s): string => str_replace(["\\", "\r", "\n", ',', ';'], ['\\\\', '', '\\n', '\\,', '\\;'], $s);
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Expernet//Portail pedagogique//FR'];
        foreach ($sessions as $session) {
            array_push($lines, 'BEGIN:VEVENT', 'UID:session-' . $session['id'] . '@expernet.local', 'DTSTAMP:' . gmdate('Ymd\THis\Z'), 'DTSTART:' . gmdate('Ymd\THis\Z', strtotime($session['starts_at'] . ' UTC')), 'DTEND:' . gmdate('Ymd\THis\Z', strtotime($session['ends_at'] . ' UTC')), 'SUMMARY:' . $escape($session['title']), 'LOCATION:' . $escape($session['location']), 'END:VEVENT');
        }
        $lines[] = 'END:VCALENDAR';
        foreach ($lines as $line) {
            // RFC 5545 line folding without cutting UTF-8 characters.
            while (strlen($line) > 73) {
                $part = mb_strcut($line, 0, 73, 'UTF-8');
                echo $part . "\r\n ";
                $line = substr($line, strlen($part));
            }
            echo $line . "\r\n";
        }
    }
}
