<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class Rbac
{
    private const PERMISSIONS = [
        'owner' => ['*'],
        'super_admin' => ['*'],
        'platform_admin' => ['saas.admin', 'enterprise.manage', 'marketplace.manage', 'developer.manage', 'analytics.view'],
        'operations_team' => ['analytics.view', 'inbox.reply', 'enterprise.manage', 'developer.manage'],
        'admin' => ['meta.connect', 'campaign.create', 'campaign.launch', 'analytics.view', 'inbox.reply', 'templates.manage', 'workspace.manage', 'billing.manage', 'saas.admin', 'marketplace.manage', 'developer.manage', 'enterprise.manage', 'commerce_os.manage'],
        'marketing_manager' => ['campaign.create', 'campaign.launch', 'analytics.view', 'templates.manage'],
        'sales_agent' => ['inbox.reply'],
        'billing_agent' => ['billing.manage', 'inbox.reply'],
        'agent' => ['inbox.reply'],
        'support_agent' => ['inbox.reply'],
        'viewer' => ['analytics.view'],
    ];

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 1);
    }

    public static function role(): string
    {
        if (!empty($_SESSION['role']) && is_string($_SESSION['role'])) {
            return $_SESSION['role'];
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                $stmt = Database::pdo()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $role = $stmt->fetchColumn();
                if (is_string($role) && $role !== '') {
                    $_SESSION['role'] = $role;
                    return $role;
                }
            } catch (\Throwable) {
                return 'viewer';
            }
        }

        return 'owner';
    }

    public static function assert(string $permission): void
    {
        $role = self::role();
        $permissions = self::permissionsForRole($role);
        if (!in_array('*', $permissions, true) && !in_array($permission, $permissions, true)) {
            Response::json(['error' => 'forbidden'], 403);
            exit;
        }
    }

    private static function permissionsForRole(string $role): array
    {
        $fallbackRole = $role === 'agent' ? 'support_agent' : $role;
        $permissions = self::PERMISSIONS[$fallbackRole] ?? [];

        try {
            $stmt = Database::pdo()->prepare('SELECT permission_key FROM role_permissions WHERE role_key = ?');
            $stmt->execute([$role]);
            $databasePermissions = array_values(array_filter(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN))));
            $permissions = array_values(array_unique(array_merge($permissions, $databasePermissions)));
        } catch (\Throwable) {
            return $permissions;
        }

        return $permissions;
    }
}
