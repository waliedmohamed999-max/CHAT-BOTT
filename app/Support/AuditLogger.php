<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class AuditLogger
{
    public static function record(string $action, ?int $storeId = null, ?int $userId = null, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO audit_logs (store_id, user_id, action, entity_type, entity_id, ip_address, user_agent, metadata_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $storeId,
                $userId,
                $action,
                $entityType,
                $entityId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable) {
            error_log('Marketing Center audit log failed: ' . $action);
        }
    }
}
