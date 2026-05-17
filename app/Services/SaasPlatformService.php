<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class SaasPlatformService
{
    private const PLANS = [
        'free' => ['name' => 'Free', 'monthly_price' => 0, 'yearly_price' => 0, 'messages' => 250, 'team_members' => 1, 'whatsapp_numbers' => 1, 'campaigns' => 2, 'ai_credits' => 100, 'storage_mb' => 100],
        'starter' => ['name' => 'Starter', 'monthly_price' => 29, 'yearly_price' => 290, 'messages' => 3000, 'team_members' => 3, 'whatsapp_numbers' => 1, 'campaigns' => 20, 'ai_credits' => 1000, 'storage_mb' => 1024],
        'professional' => ['name' => 'Professional', 'monthly_price' => 99, 'yearly_price' => 990, 'messages' => 20000, 'team_members' => 10, 'whatsapp_numbers' => 3, 'campaigns' => 150, 'ai_credits' => 10000, 'storage_mb' => 10240],
        'enterprise' => ['name' => 'Enterprise', 'monthly_price' => 299, 'yearly_price' => 2990, 'messages' => 100000, 'team_members' => 50, 'whatsapp_numbers' => 10, 'campaigns' => 1000, 'ai_credits' => 75000, 'storage_mb' => 102400],
    ];

    public function plans(): array
    {
        return self::PLANS;
    }

    public function context(): array
    {
        $storeId = TenantContext::storeId();
        return [
            'active_store_id' => $storeId,
            'active_workspace_id' => TenantContext::workspaceId(),
            'role' => Rbac::role(),
            'stores' => $this->storesForUser(Rbac::userId()),
            'subscription' => $this->subscription($storeId),
            'usage' => $this->usage($storeId),
            'white_label' => $this->whiteLabel($storeId),
        ];
    }

    public function switchWorkspace(int $storeId, ?int $workspaceId = null): array
    {
        TenantContext::assertStoreAccess($storeId);
        TenantContext::switchStore($storeId, $workspaceId);
        AuditLogger::record('tenant.workspace_switched', $storeId, Rbac::userId(), 'store', $storeId, ['workspace_id' => $workspaceId]);
        return $this->context();
    }

    public function storesForUser(int $userId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT s.*, wm.role workspace_role FROM stores s LEFT JOIN workspace_members wm ON wm.store_id = s.id AND wm.user_id = ? WHERE s.id = COALESCE(?, s.id) OR wm.user_id = ? ORDER BY s.id DESC LIMIT 100');
            $stmt->execute([$userId, null, $userId]);
            $rows = $stmt->fetchAll();
            if ($rows) {
                return $rows;
            }
            $fallback = Database::pdo()->query('SELECT * FROM stores ORDER BY id ASC LIMIT 20')->fetchAll();
            return $fallback ?: [];
        } catch (\Throwable) {
            return [['id' => TenantContext::storeId(), 'name' => 'Default Store', 'workspace_role' => Rbac::role()]];
        }
    }

    public function inviteMember(int $storeId, array $data): int
    {
        $email = trim((string) ($data['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid_email');
        }
        $role = (string) ($data['role'] ?? 'agent');
        if (!in_array($role, ['owner', 'admin', 'agent', 'viewer'], true)) {
            throw new \InvalidArgumentException('invalid_role');
        }
        $token = bin2hex(random_bytes(20));
        $stmt = Database::pdo()->prepare('INSERT INTO workspace_invitations (store_id, email, role, invite_token_hash, status, invited_by, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW())');
        $stmt->execute([$storeId, $email, $role, hash('sha256', $token), 'pending', Rbac::userId()]);
        $id = (int) Database::pdo()->lastInsertId();
        AuditLogger::record('workspace.member_invited', $storeId, Rbac::userId(), 'workspace_invitation', $id, ['email' => $email, 'role' => $role]);
        return $id;
    }

    public function teamMembers(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT wm.*, u.name, u.email FROM workspace_members wm LEFT JOIN users u ON u.id = wm.user_id WHERE wm.store_id = ? ORDER BY wm.id DESC');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function subscription(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM subscriptions WHERE store_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$storeId]);
            $row = $stmt->fetch();
            if ($row) {
                $row['limits'] = self::PLANS[$row['plan_key']] ?? self::PLANS['free'];
                return $row;
            }
        } catch (\Throwable) {
        }
        return ['plan_key' => 'free', 'status' => 'trialing', 'billing_cycle' => 'monthly', 'limits' => self::PLANS['free']];
    }

    public function upsertSubscription(int $storeId, array $data): array
    {
        $plan = (string) ($data['plan_key'] ?? 'free');
        if (!isset(self::PLANS[$plan])) {
            throw new \InvalidArgumentException('invalid_plan');
        }
        $cycle = in_array(($data['billing_cycle'] ?? 'monthly'), ['monthly', 'yearly'], true) ? $data['billing_cycle'] : 'monthly';
        $stmt = Database::pdo()->prepare('INSERT INTO subscriptions (store_id, plan_key, status, billing_cycle, trial_ends_at, current_period_starts_at, current_period_ends_at, auto_renew, created_at, updated_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE plan_key = VALUES(plan_key), status = VALUES(status), billing_cycle = VALUES(billing_cycle), auto_renew = VALUES(auto_renew), updated_at = NOW()');
        $stmt->execute([$storeId, $plan, $data['status'] ?? 'active', $cycle, !empty($data['auto_renew']) ? 1 : 0]);
        AuditLogger::record('billing.subscription_updated', $storeId, Rbac::userId(), 'subscription', $storeId, ['plan' => $plan]);
        return $this->subscription($storeId);
    }

    public function usage(int $storeId): array
    {
        $period = date('Y-m');
        try {
            $stmt = Database::pdo()->prepare('SELECT metric_key, SUM(quantity) total FROM usage_counters WHERE store_id = ? AND period_key = ? GROUP BY metric_key');
            $stmt->execute([$storeId, $period]);
            $usage = [];
            foreach ($stmt->fetchAll() as $row) {
                $usage[$row['metric_key']] = (int) $row['total'];
            }
            return $usage + ['messages' => 0, 'ai_credits' => 0, 'campaigns' => 0, 'storage_mb' => 0, 'team_members' => count($this->teamMembers($storeId))];
        } catch (\Throwable) {
            return ['messages' => 0, 'ai_credits' => 0, 'campaigns' => 0, 'storage_mb' => 0, 'team_members' => 0];
        }
    }

    public function incrementUsage(int $storeId, string $metric, int $quantity = 1): void
    {
        $period = date('Y-m');
        try {
            Database::pdo()->prepare('INSERT INTO usage_counters (store_id, metric_key, period_key, quantity, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = NOW()')->execute([$storeId, $metric, $period, $quantity]);
        } catch (\Throwable) {
        }
    }

    public function invoices(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM invoices WHERE store_id = ? ORDER BY id DESC LIMIT 100');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function paymentGateways(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT id, provider, display_name, status, test_mode, created_at, updated_at FROM payment_gateways WHERE store_id = ? ORDER BY id DESC');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function savePaymentGateway(int $storeId, array $data): int
    {
        $provider = (string) ($data['provider'] ?? '');
        if (!in_array($provider, ['stripe', 'paypal', 'moyasar', 'tap', 'myfatoorah'], true)) {
            throw new \InvalidArgumentException('invalid_payment_gateway');
        }
        $credentials = Crypto::encrypt(json_encode($data['credentials'] ?? $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $stmt = Database::pdo()->prepare('INSERT INTO payment_gateways (store_id, provider, display_name, encrypted_credentials, status, test_mode, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), encrypted_credentials = VALUES(encrypted_credentials), status = VALUES(status), test_mode = VALUES(test_mode), updated_at = NOW()');
        $stmt->execute([$storeId, $provider, $data['display_name'] ?? ucfirst($provider), $credentials, $data['status'] ?? 'active', !empty($data['test_mode']) ? 1 : 0]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function whiteLabel(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM white_label_settings WHERE store_id = ? LIMIT 1');
            $stmt->execute([$storeId]);
            return $stmt->fetch() ?: ['logo_url' => null, 'custom_domain' => null, 'primary_color' => '#2f80ed', 'secondary_color' => '#25d366'];
        } catch (\Throwable) {
            return ['logo_url' => null, 'custom_domain' => null, 'primary_color' => '#2f80ed', 'secondary_color' => '#25d366'];
        }
    }

    public function saveWhiteLabel(int $storeId, array $data): array
    {
        $stmt = Database::pdo()->prepare('INSERT INTO white_label_settings (store_id, logo_url, custom_domain, primary_color, secondary_color, email_from_name, email_footer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE logo_url = VALUES(logo_url), custom_domain = VALUES(custom_domain), primary_color = VALUES(primary_color), secondary_color = VALUES(secondary_color), email_from_name = VALUES(email_from_name), email_footer = VALUES(email_footer), updated_at = NOW()');
        $stmt->execute([$storeId, $data['logo_url'] ?? null, $data['custom_domain'] ?? null, $data['primary_color'] ?? '#2f80ed', $data['secondary_color'] ?? '#25d366', $data['email_from_name'] ?? null, $data['email_footer'] ?? null]);
        return $this->whiteLabel($storeId);
    }

    public function superAdminOverview(): array
    {
        try {
            $pdo = Database::pdo();
            return [
                'stores' => (int) $pdo->query('SELECT COUNT(*) FROM stores')->fetchColumn(),
                'active_users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'monthly_revenue' => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status IN ('paid','open') AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn(),
                'subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status IN ('active','trialing')")->fetchColumn(),
                'connected_whatsapps' => (int) $pdo->query("SELECT COUNT(*) FROM whatsapp_connections WHERE status = 'connected'")->fetchColumn(),
                'ai_usage' => (int) $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM usage_counters WHERE metric_key = 'ai_credits'")->fetchColumn(),
            ];
        } catch (\Throwable) {
            return ['stores' => 0, 'active_users' => 0, 'monthly_revenue' => 0, 'subscriptions' => 0, 'connected_whatsapps' => 0, 'ai_usage' => 0];
        }
    }
}
