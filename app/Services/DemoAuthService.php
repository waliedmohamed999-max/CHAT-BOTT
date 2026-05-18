<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Env;

final class DemoAuthService
{
    private const PASSWORD = 'DemoPass123!';

    public function enabled(): bool
    {
        $explicit = Env::get('DEMO_LOGIN_ENABLED');
        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOL);
        }

        return Env::get('APP_ENV', 'local') !== 'production' && Env::get('DATABASE_URL') === null;
    }

    public function passwordHash(): string
    {
        return password_hash($this->password(), PASSWORD_DEFAULT);
    }

    public function password(): string
    {
        return (string) Env::get('DEMO_LOGIN_PASSWORD', self::PASSWORD);
    }

    public function legacyIdentity(string $email, string $password): ?array
    {
        if (!$this->enabled() || !$this->passwordMatches($password)) {
            return null;
        }

        return match (strtolower(trim($email))) {
            'admin@marketing-center.local' => $this->identity(9001, 'user', 1, 'Demo Owner', $email, 'owner'),
            default => null,
        };
    }

    public function portalIdentity(string $portal, string $email, string $storeCode = ''): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $email = strtolower(trim($email));
        $storeCode = trim($storeCode);

        if ($storeCode !== '' && !in_array($storeCode, ['main-store', 'demo-store'], true)) {
            return null;
        }

        return match ($portal . ':' . $email) {
            'platform:platform@marketing-center.local' => $this->identity(9101, 'platform_user', null, 'Platform Admin', $email, 'super_admin'),
            'store:owner@main-store.local' => $this->identity(9201, 'store_user', 1, 'Store Owner', $email, 'owner'),
            'agent:agent@main-store.local' => $this->identity(9301, 'store_user', 1, 'Support Agent', $email, 'support_agent'),
            'tenant:tenant@main-store.local' => $this->identity(9401, 'store_user', 1, 'Tenant Admin', $email, 'admin'),
            default => null,
        };
    }

    public function sessionUser(): ?array
    {
        if (!$this->enabled() || empty($_SESSION['demo_user']) || !is_array($_SESSION['demo_user'])) {
            return null;
        }

        return [
            'id' => (int) ($_SESSION['demo_user']['id'] ?? 0),
            'store_id' => isset($_SESSION['demo_user']['store_id']) ? (int) $_SESSION['demo_user']['store_id'] : null,
            'workspace_id' => null,
            'portal' => $_SESSION['portal_type'] ?? 'demo',
            'type' => (string) ($_SESSION['demo_user']['user_type'] ?? 'user'),
            'name' => (string) ($_SESSION['demo_user']['name'] ?? 'Demo User'),
            'email' => (string) ($_SESSION['demo_user']['email'] ?? ''),
            'role' => (string) ($_SESSION['demo_user']['role'] ?? 'viewer'),
            'last_login_at' => null,
        ];
    }

    private function passwordMatches(string $password): bool
    {
        return hash_equals($this->password(), $password);
    }

    private function identity(int $id, string $type, ?int $storeId, string $name, string $email, string $role): array
    {
        return [
            'id' => $id,
            'source_id' => $id,
            'user_type' => $type,
            'store_id' => $storeId,
            'store_slug' => $storeId ? 'main-store' : null,
            'store_name' => $storeId ? 'Main Store' : null,
            'name' => $name,
            'email' => strtolower(trim($email)),
            'role' => $role,
            'status' => 'active',
            'two_factor_enabled' => false,
            'password_hash' => $this->passwordHash(),
            'is_demo' => true,
        ];
    }
}
