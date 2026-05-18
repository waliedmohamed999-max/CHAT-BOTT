<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class AuthPortalService
{
    private const PLATFORM_ROLES = ['super_admin', 'platform_admin', 'operations_team'];
    private const STORE_ROLES = ['owner', 'admin', 'marketing_manager'];
    private const AGENT_ROLES = ['support_agent', 'sales_agent', 'billing_agent', 'viewer', 'agent'];

    public function __construct()
    {
        try {
            $this->ensurePortalTables();
        } catch (\Throwable) {
            // Login pages must stay reachable even before the production database is attached.
        }
    }

    public function portalConfig(string $portal, ?string $slug = null): array
    {
        $branding = $slug ? $this->brandingForSlug($slug) : null;

        return match ($portal) {
            'platform' => [
                'portal' => 'platform',
                'title' => 'دخول إدارة المنصة',
                'subtitle' => 'بوابة مخصصة لـ Super Admin وفريق تشغيل المنصة فقط.',
                'badge' => 'Platform Control',
                'endpoint' => '/api/auth/platform/login',
                'accent' => '#334a91',
                'secondary' => '#5a69b8',
                'logo' => 'MC',
                'show_store_code' => false,
                'show_store_select' => false,
                'allow_register' => false,
                'security_note' => 'هذه البوابة لا تقبل حسابات المتاجر أو الموظفين. يتم تسجيل IP والجهاز ومحاولات الدخول.',
            ],
            'agent' => [
                'portal' => 'agent',
                'title' => 'دخول الموظفين',
                'subtitle' => 'للدعم والمبيعات والحسابات مع توجيه مباشر لصندوق المحادثات أو المهام.',
                'badge' => 'Agent Workspace',
                'endpoint' => '/api/auth/agent/login',
                'accent' => '#128c7e',
                'secondary' => '#25d366',
                'logo' => 'AG',
                'show_store_code' => true,
                'show_store_select' => false,
                'allow_register' => false,
                'security_note' => 'يتم تقييد الموظف بمتجره وقسمه ولا يمكنه دخول صفحات مالك المتجر.',
            ],
            'tenant' => [
                'portal' => 'tenant',
                'title' => 'دخول ' . ($branding['store_name'] ?? 'المتجر'),
                'subtitle' => $branding['welcome_message'] ?? 'بوابة دخول مخصصة بهوية المتجر.',
                'badge' => 'White Label',
                'endpoint' => '/api/auth/tenant/login',
                'accent' => $branding['primary_color'] ?? '#128c7e',
                'secondary' => $branding['secondary_color'] ?? '#25d366',
                'logo' => $branding['logo_text'] ?? 'WL',
                'slug' => $slug,
                'store_name' => $branding['store_name'] ?? $slug,
                'support_url' => $branding['support_url'] ?? '#',
                'privacy_url' => $branding['privacy_url'] ?? '#',
                'terms_url' => $branding['terms_url'] ?? '#',
                'background_url' => $branding['background_url'] ?? null,
                'show_store_code' => false,
                'show_store_select' => false,
                'allow_register' => false,
                'security_note' => 'كل جلسة مرتبطة بالمتجر الحالي فقط مع عزل كامل للبيانات.',
            ],
            default => [
                'portal' => 'store',
                'title' => 'دخول المتجر',
                'subtitle' => 'بوابة أصحاب المتاجر ومديري التسويق لإدارة الحملات والعملاء.',
                'badge' => 'Store Dashboard',
                'endpoint' => '/api/auth/store/login',
                'accent' => '#2f80ed',
                'secondary' => '#5aa9b8',
                'logo' => 'ST',
                'show_store_code' => false,
                'show_store_select' => true,
                'allow_register' => true,
                'security_note' => 'بعد الدخول يتم توجيهك للمتجر المصرح لك فقط حسب الدور والصلاحيات.',
            ],
        };
    }

    public function login(string $portal, array $input): array
    {
        $portal = $this->normalizePortal($portal);
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $storeCode = trim((string) ($input['store_code'] ?? $input['store_slug'] ?? $input['tenant_slug'] ?? ''));
        $remember = !empty($input['remember']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->recordAttempt($email, $portal, null, false, 'invalid_input');
            throw new \RuntimeException('invalid_credentials');
        }

        $this->assertNotLocked($email, $portal);

        $identity = $this->identityForPortal($portal, $email, $storeCode);
        if (!$identity || !password_verify($password, (string) $identity['password_hash'])) {
            $this->recordAttempt($email, $portal, $identity['store_id'] ?? null, false, 'invalid_credentials');
            throw new \RuntimeException('invalid_credentials');
        }

        if (!$this->roleAllowed($portal, (string) $identity['role'])) {
            $this->recordAttempt($email, $portal, $identity['store_id'] ?? null, false, 'portal_forbidden');
            throw new \RuntimeException('portal_forbidden');
        }

        if (($identity['status'] ?? 'active') !== 'active') {
            $this->recordAttempt($email, $portal, $identity['store_id'] ?? null, false, 'account_disabled');
            throw new \RuntimeException('account_disabled');
        }

        $this->startPortalSession($portal, $identity, $remember);
        $this->touchLastLogin($identity);
        $this->recordSession($identity, $remember);
        $this->recordAttempt($email, $portal, $identity['store_id'] ?? null, true, 'success');

        AuditLogger::record('auth.portal_login', (int) ($identity['store_id'] ?? 0), (int) $identity['id'], (string) $identity['user_type'], (int) $identity['id'], ['portal' => $portal]);

        return [
            'authenticated' => true,
            'portal' => $portal,
            'redirect' => $this->redirectForRole((string) $identity['role']),
            'requires_2fa' => (bool) ($identity['two_factor_enabled'] ?? false),
            'access_token' => $this->jwtForIdentity($portal, $identity),
            'user' => $this->sanitizeIdentity($identity),
        ];
    }

    public function me(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return [
            'authenticated' => !empty($_SESSION['user_id']),
            'portal' => $_SESSION['portal_type'] ?? 'legacy',
            'user_type' => $_SESSION['portal_user_type'] ?? 'user',
            'store_id' => $_SESSION['active_store_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
        ];
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionId = session_id();
        try {
            Database::pdo()->prepare('UPDATE login_sessions SET revoked_at = NOW() WHERE session_token_hash = ? AND revoked_at IS NULL')->execute([hash('sha256', $sessionId)]);
        } catch (\Throwable) {
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public function loginBranding(string $slug): array
    {
        return $this->brandingForSlug($slug) ?? [
            'slug' => $slug,
            'store_name' => 'متجر غير معروف',
            'primary_color' => '#334a91',
            'secondary_color' => '#5aa9b8',
            'logo_text' => 'MC',
            'welcome_message' => 'بوابة دخول مخصصة.',
        ];
    }

    public function sessions(): array
    {
        try {
            $stmt = Database::pdo()->query('SELECT id, user_id, user_type, store_id, portal_type, ip_address, device_name, expires_at, revoked_at, created_at FROM login_sessions ORDER BY id DESC LIMIT 100');
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function revokeSession(int $id): void
    {
        Database::pdo()->prepare('UPDATE login_sessions SET revoked_at = NOW() WHERE id = ?')->execute([$id]);
    }

    public function loginAttempts(?int $limit = 100): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT email, portal_type, store_id, ip_address, success, reason, created_at FROM login_attempts ORDER BY id DESC LIMIT ?');
            $stmt->bindValue(1, max(1, min(250, (int) $limit)), \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function forgotPassword(array $input): array
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('invalid_email');
        }

        $identity = $this->identityForPortal($this->normalizePortal((string) ($input['portal'] ?? 'store')), $email, (string) ($input['store_code'] ?? ''));
        if ($identity) {
            $token = bin2hex(random_bytes(32));
            Database::pdo()->prepare('INSERT INTO password_resets (user_id, user_type, token_hash, expires_at, created_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())')
                ->execute([(int) $identity['id'], (string) $identity['user_type'], hash('sha256', $token)]);
        }

        return ['ok' => true, 'message' => 'إذا كان البريد مسجلاً سيتم إرسال رابط إعادة التعيين.'];
    }

    public function resetPassword(array $input): array
    {
        return ['ok' => true, 'message' => 'تم تجهيز مسار إعادة التعيين الآمن. اربطه بخدمة البريد قبل الإنتاج.'];
    }

    public function verify2fa(array $input): array
    {
        return ['ok' => true, 'message' => 'تم تجهيز خطوة 2FA، ويتم تفعيلها عند ربط مزود التحقق.'];
    }

    private function identityForPortal(string $portal, string $email, string $storeCode = ''): ?array
    {
        if ($portal === 'platform') {
            return $this->platformIdentity($email);
        }

        $storeId = null;
        if ($storeCode !== '') {
            $storeId = $this->storeIdForSlug($storeCode);
            if (!$storeId) {
                return null;
            }
        }

        $identity = $this->storeUserIdentity($email, $storeId);
        if ($identity) {
            return $identity;
        }

        $roles = $portal === 'agent' ? self::AGENT_ROLES : array_merge(self::STORE_ROLES, self::AGENT_ROLES);
        return $this->legacyUserIdentity($email, $storeId, $roles, false);
    }

    private function platformIdentity(string $email): ?array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT *, id AS source_id FROM platform_users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user) {
                return null;
            }
            $user['user_type'] = 'platform_user';
            $user['store_id'] = null;
            return $user;
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeUserIdentity(string $email, ?int $storeId): ?array
    {
        try {
            $sql = 'SELECT su.*, su.id AS source_id, s.slug store_slug, s.name store_name FROM store_users su LEFT JOIN stores s ON s.id = su.store_id WHERE su.email = ?';
            $params = [$email];
            if ($storeId !== null) {
                $sql .= ' AND su.store_id = ?';
                $params[] = $storeId;
            }
            $sql .= ' ORDER BY su.id DESC LIMIT 1';
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch();
            if (!$user) {
                return null;
            }
            $user['user_type'] = 'store_user';
            return $user;
        } catch (\Throwable) {
            return null;
        }
    }

    private function legacyUserIdentity(string $email, ?int $storeId, array $roles, bool $platformOnly): ?array
    {
        try {
            $sql = 'SELECT u.*, u.id AS source_id, s.slug store_slug, s.name store_name FROM users u LEFT JOIN stores s ON s.id = u.store_id WHERE u.email = ?';
            $params = [$email];
            if ($storeId !== null) {
                $sql .= ' AND u.store_id = ?';
                $params[] = $storeId;
            }
            if ($platformOnly) {
                $sql .= ' AND (u.store_id IS NULL OR u.role = "owner")';
            }
            $sql .= ' ORDER BY u.id DESC LIMIT 1';
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch();
            if (!$user || !in_array((string) $user['role'], $roles, true)) {
                return null;
            }
            $user['user_type'] = 'user';
            $user['status'] = 'active';
            $user['two_factor_enabled'] = !empty($user['two_factor_secret_ciphertext']);
            return $user;
        } catch (\Throwable) {
            return null;
        }
    }

    private function roleAllowed(string $portal, string $role): bool
    {
        return match ($portal) {
            'platform' => in_array($role, self::PLATFORM_ROLES, true),
            'agent' => in_array($role, self::AGENT_ROLES, true),
            default => in_array($role, array_merge(self::STORE_ROLES, self::AGENT_ROLES), true),
        };
    }

    private function startPortalSession(string $portal, array $identity, bool $remember): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $identity['id'];
        $_SESSION['role'] = (string) $identity['role'];
        $_SESSION['portal_type'] = $portal;
        $_SESSION['portal_user_type'] = (string) $identity['user_type'];
        $_SESSION['active_store_id'] = isset($identity['store_id']) ? (int) $identity['store_id'] : null;
        $_SESSION['authenticated_at'] = time();
        $_SESSION['remember_login'] = $remember;
    }

    private function touchLastLogin(array $identity): void
    {
        $table = match ((string) $identity['user_type']) {
            'platform_user' => 'platform_users',
            'store_user' => 'store_users',
            default => 'users',
        };

        try {
            Database::pdo()->prepare("UPDATE {$table} SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([(int) $identity['id']]);
        } catch (\Throwable) {
        }
    }

    private function recordSession(array $identity, bool $remember): void
    {
        try {
            Database::pdo()->prepare('INSERT INTO login_sessions (user_id, user_type, store_id, portal_type, session_token_hash, ip_address, user_agent, device_name, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())')
                ->execute([
                    (int) $identity['id'],
                    (string) $identity['user_type'],
                    $identity['store_id'] ?? null,
                    $_SESSION['portal_type'] ?? 'legacy',
                    hash('sha256', session_id()),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    $this->deviceName(),
                    $remember ? 43200 : max(5, (int) Env::get('SESSION_TIMEOUT_MINUTES', '60')),
                ]);
        } catch (\Throwable) {
        }
    }

    private function recordAttempt(string $email, string $portal, ?int $storeId, bool $success, string $reason): void
    {
        try {
            Database::pdo()->prepare('INSERT INTO login_attempts (email, portal_type, store_id, ip_address, success, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')
                ->execute([$email, $portal, $storeId, $_SERVER['REMOTE_ADDR'] ?? null, $success ? 1 : 0, $reason]);
        } catch (\Throwable) {
        }
    }

    private function assertNotLocked(string $email, string $portal): void
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND portal_type = ? AND success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
            $stmt->execute([$email, $portal]);
            if ((int) $stmt->fetchColumn() >= 8) {
                throw new \RuntimeException('account_temporarily_locked');
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable) {
        }
    }

    private function redirectForRole(string $role): string
    {
        return match ($role) {
            'super_admin', 'platform_admin', 'operations_team' => '/platform/dashboard',
            'owner', 'admin' => '/dashboard',
            'marketing_manager' => '/marketing-center',
            'support_agent', 'agent' => '/inbox',
            'sales_agent' => '/crm/leads',
            'billing_agent' => '/billing',
            'viewer' => '/reports',
            default => '/marketing-center',
        };
    }

    private function sanitizeIdentity(array $identity): array
    {
        return [
            'id' => (int) $identity['id'],
            'type' => (string) $identity['user_type'],
            'store_id' => isset($identity['store_id']) ? (int) $identity['store_id'] : null,
            'store_slug' => $identity['store_slug'] ?? null,
            'name' => (string) ($identity['name'] ?? ''),
            'email' => (string) ($identity['email'] ?? ''),
            'role' => (string) ($identity['role'] ?? 'viewer'),
        ];
    }

    private function jwtForIdentity(string $portal, array $identity): ?string
    {
        $secret = (string) Env::get('JWT_SECRET', '');
        if (strlen($secret) < 32) {
            return null;
        }

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => rtrim((string) Env::get('APP_URL', 'marketing-center'), '/'),
            'sub' => (string) $identity['id'],
            'typ' => (string) $identity['user_type'],
            'portal' => $portal,
            'role' => (string) $identity['role'],
            'store_id' => $identity['store_id'] ?? null,
            'iat' => time(),
            'exp' => time() + (max(5, (int) Env::get('SESSION_TIMEOUT_MINUTES', '60')) * 60),
        ];

        $segments = [
            $this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function base64Url(string|false $value): string
    {
        return rtrim(strtr(base64_encode((string) $value), '+/', '-_'), '=');
    }

    private function brandingForSlug(string $slug): ?array
    {
        $storeId = $this->storeIdForSlug($slug);
        if (!$storeId) {
            return null;
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM stores WHERE id = ? LIMIT 1');
            $stmt->execute([$storeId]);
            $store = $stmt->fetch() ?: [];
            return [
                'slug' => (string) ($store['slug'] ?? $slug),
                'store_id' => $storeId,
                'store_name' => (string) ($store['name'] ?? $slug),
                'logo_url' => $store['logo_url'] ?? null,
                'logo_text' => mb_strtoupper(mb_substr((string) ($store['slug'] ?? 'WL'), 0, 2)),
                'primary_color' => $store['primary_color'] ?? '#128c7e',
                'secondary_color' => $store['secondary_color'] ?? '#25d366',
                'background_url' => $store['login_background_url'] ?? null,
                'support_url' => $store['support_url'] ?? '#',
                'privacy_url' => $store['privacy_url'] ?? '#',
                'terms_url' => $store['terms_url'] ?? '#',
                'welcome_message' => 'مرحباً بك في بوابة ' . (string) ($store['name'] ?? $slug),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeIdForSlug(string $slug): ?int
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT id FROM stores WHERE slug = ? OR custom_domain = ? LIMIT 1');
            $stmt->execute([$slug, $slug]);
            $id = $stmt->fetchColumn();
            return $id ? (int) $id : null;
        } catch (\Throwable) {
            try {
                $stmt = Database::pdo()->prepare('SELECT id FROM stores WHERE slug = ? LIMIT 1');
                $stmt->execute([$slug]);
                $id = $stmt->fetchColumn();
                return $id ? (int) $id : null;
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function normalizePortal(string $portal): string
    {
        return in_array($portal, ['platform', 'store', 'agent', 'tenant'], true) ? $portal : 'store';
    }

    private function deviceName(): string
    {
        $agent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'هاتف';
        }
        if (str_contains($agent, 'windows')) {
            return 'Windows';
        }
        if (str_contains($agent, 'mac')) {
            return 'Mac';
        }
        return 'متصفح';
    }

    private function ensurePortalTables(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS platform_users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(60) NOT NULL DEFAULT 'platform_admin',
            two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX platform_users_role_status_idx (role, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS store_users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            store_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(60) NOT NULL DEFAULT 'viewer',
            department_id BIGINT UNSIGNED NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            UNIQUE KEY store_users_store_email_unique (store_id, email),
            INDEX store_users_store_role_idx (store_id, role, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_sessions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            user_type VARCHAR(40) NOT NULL,
            store_id BIGINT UNSIGNED NULL,
            portal_type VARCHAR(40) NOT NULL,
            session_token_hash VARCHAR(128) NULL,
            ip_address VARCHAR(80) NULL,
            user_agent VARCHAR(500) NULL,
            device_name VARCHAR(190) NULL,
            expires_at TIMESTAMP NULL,
            revoked_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            INDEX login_sessions_user_idx (user_type, user_id, revoked_at),
            INDEX login_sessions_store_idx (store_id, portal_type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            portal_type VARCHAR(40) NOT NULL,
            store_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(80) NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            reason VARCHAR(120) NULL,
            created_at TIMESTAMP NULL,
            INDEX login_attempts_email_portal_idx (email, portal_type, created_at),
            INDEX login_attempts_store_idx (store_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            user_type VARCHAR(40) NOT NULL,
            token_hash VARCHAR(128) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            INDEX password_resets_token_idx (token_hash),
            INDEX password_resets_user_idx (user_type, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM platform_users')->fetchColumn();
            if ($count === 0) {
                $pdo->exec("INSERT INTO platform_users (name, email, password_hash, role, status, created_at, updated_at)
                    SELECT name, email, password_hash, 'super_admin', 'active', NOW(), NOW()
                    FROM users
                    WHERE role = 'owner'
                    ORDER BY id ASC
                    LIMIT 1");
            }
        } catch (\Throwable) {
        }
    }
}
