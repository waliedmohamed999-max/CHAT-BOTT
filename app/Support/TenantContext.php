<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class TenantContext
{
    public static function storeId(): int
    {
        $candidate = $_SESSION['active_store_id'] ?? $_SERVER['HTTP_X_STORE_ID'] ?? $_GET['store_id'] ?? Env::get('DEFAULT_STORE_ID', '1');
        return max(1, (int) $candidate);
    }

    public static function workspaceId(): ?int
    {
        $candidate = $_SESSION['active_workspace_id'] ?? $_SERVER['HTTP_X_WORKSPACE_ID'] ?? $_GET['workspace_id'] ?? null;
        return $candidate ? max(1, (int) $candidate) : null;
    }

    public static function switchStore(int $storeId, ?int $workspaceId = null): void
    {
        $_SESSION['active_store_id'] = max(1, $storeId);
        if ($workspaceId !== null) {
            $_SESSION['active_workspace_id'] = max(1, $workspaceId);
        }
    }

    public static function assertStoreAccess(int $storeId): void
    {
        if (Rbac::role() === 'owner' || Rbac::role() === 'admin') {
            return;
        }
        try {
            $stmt = Database::pdo()->prepare("SELECT id FROM workspace_members WHERE store_id = ? AND user_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$storeId, Rbac::userId()]);
            if ($stmt->fetch()) {
                return;
            }
        } catch (\Throwable) {
            if ($storeId === (int) Env::get('DEFAULT_STORE_ID', '1')) {
                return;
            }
        }
        Response::json(['error' => 'forbidden'], 403);
        exit;
    }
}
