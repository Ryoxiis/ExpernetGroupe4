<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PedagogyRepository;
use App\Support\PortalException;
use App\Support\PortalSecurity;

final class PortalAuth
{
    public function __construct(private readonly PedagogyRepository $repo, private readonly array $config)
    {
    }

    public function current(): ?array
    {
        $session = $_SESSION['portal_user'] ?? null;
        if (!$session) {
            return null;
        }
        $user = $this->repo->one('SELECT id,name,email,role,status,activation_version FROM portal_users WHERE id=?', [$session['id']]);
        if (!$user || $user['status'] !== 'active' || (int) $user['activation_version'] !== $session['version']) {
            unset($_SESSION['portal_user']);
            return null;
        }
        return $user;
    }

    public function requireUser(): array
    {
        return $this->current() ?? throw new PortalException('Connectez-vous pour accéder à votre espace pédagogique.', 401);
    }

    private function attempts(string $identity): array
    {
        $keys = [hash('sha256', 'email:' . $identity), hash('sha256', 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'))];
        foreach ($keys as $key) {
            $this->repo->execute('INSERT INTO portal_login_attempts(fingerprint,attempts,last_attempt) VALUES(?,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE),1,attempts+1),last_attempt=IF(attempts=1,UTC_TIMESTAMP(),last_attempt)', [$key]);
            $row = $this->repo->one('SELECT attempts FROM portal_login_attempts WHERE fingerprint=?', [$key]);
            if ((int) $row['attempts'] > (str_starts_with($identity, 'mfa:') ? 30 : 50)) {
                throw new PortalException('Trop de tentatives. Réessayez dans quinze minutes ou contactez l’administration.', 429);
            }
        }
        return $keys;
    }

    public function login(string $email, string $password): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || strlen($password) > 256) {
            throw new PortalException('Adresse ou mot de passe incorrect.', 401);
        }
        $this->attempts($email);
        $result = $this->repo->transaction(function () use ($email, $password): array {
            $user = $this->repo->one('SELECT * FROM portal_users WHERE email=? FOR UPDATE', [$email]);
            $hash = $user ? ($user['status'] === 'invited' ? $user['temporary_hash'] : $user['password_hash']) : null;
            // Valid dummy hash keeps unknown accounts on the password verification path.
            $valid = password_verify($password, $hash ?: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            if (!$user || !$valid || $user['status'] === 'disabled') {
                throw new PortalException('Adresse ou mot de passe incorrect.', 401);
            }
            if ($user['status'] === 'invited') {
                if (!$user['temporary_expires'] || strtotime($user['temporary_expires'] . ' UTC') < time()) {
                    throw new PortalException('Votre accès temporaire a expiré. Contactez l’administration pour en recevoir un nouveau.', 401);
                }
                $this->repo->execute('UPDATE portal_users SET temporary_hash=NULL,temporary_expires=NULL WHERE id=?', [$user['id']]);
            }
            return $user;
        });
        session_regenerate_id(true);
        unset($_SESSION['portal_user'], $_SESSION['pending']);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        if ($result['status'] === 'invited') {
            $_SESSION['pending'] = ['id' => (int) $result['id'], 'version' => (int) $result['activation_version'], 'stage' => 'password', 'temporary_hash' => $result['temporary_hash'], 'expires' => time() + $this->config['activation_minutes'] * 60];
            return '/connexion/premiere-connexion/';
        }
        return $this->afterPassword($result);
    }

    private function afterPassword(array $user): string
    {
        if ($this->config['mfa']) {
            $_SESSION['pending'] = ['id' => (int) $user['id'], 'version' => (int) $user['activation_version'], 'stage' => 'mfa', 'expires' => time() + 900];
            return '/connexion/verification/';
        }
        $this->complete($user);
        return '/espace-pedagogique/';
    }

    public function pending(string $stage): array
    {
        $pending = $_SESSION['pending'] ?? [];
        if (($pending['stage'] ?? '') !== $stage || ($pending['expires'] ?? 0) < time()) {
            unset($_SESSION['pending']);
            throw new PortalException('Cette étape de connexion a expiré. Reconnectez-vous ou demandez un nouvel accès temporaire.', 401);
        }
        $user = $this->repo->one('SELECT * FROM portal_users WHERE id=?', [$pending['id']]);
        if (!$user || $user['status'] === 'disabled' || (int) $user['activation_version'] !== $pending['version'] || ($stage === 'password' && $user['status'] !== 'invited') || ($stage === 'mfa' && $user['status'] !== 'active')) {
            unset($_SESSION['pending']);
            throw new PortalException('Cet accès n’est plus valide. Contactez l’administration.', 401);
        }
        return $user;
    }

    public function changeInitialPassword(string $password, string $confirmation): string
    {
        $this->validatePassword($password, $confirmation);
        $user = $this->pending('password');
        if (password_verify($password, $_SESSION['pending']['temporary_hash'] ?? '')) {
            throw new PortalException('Choisissez un mot de passe différent de votre accès temporaire.');
        }
        $this->repo->transaction(function () use ($user, $password): void {
            $locked = $this->repo->one('SELECT * FROM portal_users WHERE id=? FOR UPDATE', [$user['id']]);
            if ($locked['status'] !== 'invited' || (int) $locked['activation_version'] !== (int) $user['activation_version']) {
                throw new PortalException('Cette activation a été remplacée. Demandez un nouvel accès.', 409);
            }
            $this->repo->execute('UPDATE portal_users SET password_hash=?,status="active",activation_version=activation_version+1 WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            $this->repo->audit((int) $user['id'], 'account.activated', (int) $user['id']);
        });
        $user['activation_version']++;
        session_regenerate_id(true);
        return $this->afterPassword($user);
    }

    public function validatePassword(string $password, string $confirmation): void
    {
        // 72-byte ceiling avoids bcrypt truncation on PHP builds using PASSWORD_DEFAULT=bcrypt.
        if ($password !== $confirmation || strlen($password) < 12 || strlen($password) > 72) {
            throw new PortalException('Choisissez deux mots de passe identiques, de 12 à 72 octets. Une phrase de passe est recommandée.');
        }
    }

    public function mfaSetup(): array
    {
        $user = $this->pending('mfa');
        if ($user['mfa_secret']) {
            return ['setup' => false];
        }
        $_SESSION['pending']['secret'] ??= PortalSecurity::totpSecret();
        return ['setup' => true, 'secret' => $_SESSION['pending']['secret']];
    }

    public function verifyMfa(string $code): string
    {
        $user = $this->pending('mfa');
        $this->attempts('mfa:' . $user['id']);
        $secret = $user['mfa_secret'] ? PortalSecurity::decrypt($user['mfa_secret'], $this->config['key']) : ($_SESSION['pending']['secret'] ?? '');
        if (!preg_match('/^\d{6}$/D', $code) || !$secret) {
            throw new PortalException('Saisissez le code à six chiffres de votre application.');
        }
        $matched = null;
        for ($offset = -1; $offset <= 1; $offset++) {
            $counter = intdiv(time(), 30) + $offset;
            if (hash_equals(PortalSecurity::totp($secret, $counter), $code)) {
                $matched = $counter;
            }
        }
        if ($matched === null) {
            throw new PortalException('Code incorrect ou expiré. Essayez le code actuellement affiché.');
        }
        $this->repo->transaction(function () use ($user, $secret, $matched): void {
            $current = $this->repo->one('SELECT status,activation_version,mfa_secret,mfa_last_counter FROM portal_users WHERE id=? FOR UPDATE', [$user['id']]);
            if ($current['status'] !== 'active' || (int) $current['activation_version'] !== (int) $user['activation_version'] || ($current['mfa_last_counter'] !== null && (int) $current['mfa_last_counter'] >= $matched) || (!$user['mfa_secret'] && $current['mfa_secret'])) {
                throw new PortalException('Code déjà utilisé ou accès remplacé. Reconnectez-vous.', 409);
            }
            $this->repo->execute('UPDATE portal_users SET mfa_secret=?,mfa_last_counter=? WHERE id=?', [PortalSecurity::encrypt($secret, $this->config['key']), $matched, $user['id']]);
        });
        $this->complete($user);
        return '/espace-pedagogique/';
    }

    private function complete(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['pending']);
        $_SESSION['portal_user'] = ['id' => (int) $user['id'], 'version' => (int) $user['activation_version']];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
