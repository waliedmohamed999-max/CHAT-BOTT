<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class AuthService
{
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->logLogin(null, null, $email, 'failed');
            throw new \RuntimeException('invalid_credentials');
        }

        $demoIdentity = (new DemoAuthService())->legacyIdentity($email, $password);
        if ($demoIdentity) {
            return $this->startDemoSession($demoIdentity);
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (\Throwable) {
            throw new \RuntimeException('auth_database_unavailable');
        }

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->logLogin(null, null, $email, 'failed');
            throw new \RuntimeException('invalid_credentials');
        }

        $storeId = (int) ($user['store_id'] ?? Env::get('DEFAULT_STORE_ID', '1'));
        $workspaceId = $this->workspaceIdForUser((int) $user['id'], $storeId);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['active_store_id'] = $storeId;
        if ($workspaceId !== null) {
            $_SESSION['active_workspace_id'] = $workspaceId;
        }
        $_SESSION['authenticated_at'] = time();

        Database::pdo()->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([(int) $user['id']]);
        $this->logLogin($storeId, (int) $user['id'], $email, 'success');
        AuditLogger::record('auth.login', $storeId, (int) $user['id'], 'user', (int) $user['id']);

        return $this->sanitizeUser($user, $storeId, $workspaceId);
    }

    public function logout(): void
    {
        $user = $this->currentUser();
        if ($user) {
            AuditLogger::record('auth.logout', (int) ($user['store_id'] ?? 0), (int) $user['id'], 'user', (int) $user['id']);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public function currentUser(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $demoUser = (new DemoAuthService())->sessionUser();
        if ($demoUser) {
            return $demoUser;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        if ($this->sessionExpired()) {
            $this->logout();
            return null;
        }

        $portalUserType = (string) ($_SESSION['portal_user_type'] ?? 'user');
        if ($portalUserType !== 'user') {
            return $this->currentPortalUser($portalUserType, $userId);
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT id, store_id, name, email, role, last_login_at, created_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                return null;
            }

            return $this->sanitizeUser($user, (int) ($user['store_id'] ?? Env::get('DEFAULT_STORE_ID', '1')), $_SESSION['active_workspace_id'] ?? null);
        } catch (\Throwable) {
            return null;
        }
    }

    private function currentPortalUser(string $userType, int $userId): ?array
    {
        $table = match ($userType) {
            'platform_user' => 'platform_users',
            'store_user' => 'store_users',
            default => 'users',
        };

        try {
            $stmt = Database::pdo()->prepare("SELECT id, " . ($table === 'platform_users' ? 'NULL AS store_id' : 'store_id') . ", name, email, role, last_login_at, created_at FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                return null;
            }

            return [
                'id' => (int) $user['id'],
                'store_id' => isset($user['store_id']) ? (int) $user['store_id'] : null,
                'workspace_id' => $_SESSION['active_workspace_id'] ?? null,
                'portal' => $_SESSION['portal_type'] ?? 'store',
                'type' => $userType,
                'name' => (string) ($user['name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'role' => (string) ($user['role'] ?? 'viewer'),
                'last_login_at' => $user['last_login_at'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    private function workspaceIdForUser(int $userId, int $storeId): ?int
    {
        try {
            $stmt = Database::pdo()->prepare("SELECT workspace_id FROM workspace_members WHERE user_id = ? AND store_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId, $storeId]);
            $workspaceId = $stmt->fetchColumn();
            return $workspaceId ? (int) $workspaceId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function sessionExpired(): bool
    {
        $timeout = max(5, (int) Env::get('SESSION_TIMEOUT_MINUTES', '60')) * 60;
        $authenticatedAt = (int) ($_SESSION['authenticated_at'] ?? time());
        return (time() - $authenticatedAt) > $timeout;
    }

    private function sanitizeUser(array $user, int $storeId, int|string|null $workspaceId): array
    {
        return [
            'id' => (int) $user['id'],
            'store_id' => $storeId,
            'workspace_id' => $workspaceId ? (int) $workspaceId : null,
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? 'viewer'),
            'last_login_at' => $user['last_login_at'] ?? null,
        ];
    }

    private function startDemoSession(array $identity): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $identity['id'];
        $_SESSION['role'] = (string) $identity['role'];
        $_SESSION['portal_type'] = 'legacy';
        $_SESSION['portal_user_type'] = (string) $identity['user_type'];
        $_SESSION['active_store_id'] = (int) ($identity['store_id'] ?? Env::get('DEFAULT_STORE_ID', '1'));
        $_SESSION['authenticated_at'] = time();
        $_SESSION['demo_user'] = $this->sanitizeDemoIdentity($identity);

        return $this->sanitizeUser($identity, (int) $_SESSION['active_store_id'], null);
    }

    private function sanitizeDemoIdentity(array $identity): array
    {
        return [
            'id' => (int) $identity['id'],
            'store_id' => isset($identity['store_id']) ? (int) $identity['store_id'] : null,
            'user_type' => (string) $identity['user_type'],
            'name' => (string) $identity['name'],
            'email' => (string) $identity['email'],
            'role' => (string) $identity['role'],
        ];
    }

    private function logLogin(?int $storeId, ?int $userId, string $email, string $status): void
    {
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO login_logs (store_id, user_id, email, ip_address, user_agent, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $storeId,
                $userId,
                $email,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $status,
            ]);
        } catch (\Throwable) {
            error_log('Marketing Center login log failed: ' . $status);
        }
    }
}
