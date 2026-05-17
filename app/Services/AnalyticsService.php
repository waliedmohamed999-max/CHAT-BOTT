<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;

final class AnalyticsService
{
    public function summary(int $storeId): array
    {
        try {
            $pdo = Database::pdo();
        } catch (\Throwable $e) {
            error_log('Analytics database unavailable: ' . $e->getMessage());
            return $this->emptySummary();
        }
        $row = $pdo->query("SELECT
            COUNT(*) total,
            SUM(provider_status IN ('sent','delivered','read')) sent,
            SUM(provider_status IN ('delivered','read')) delivered,
            SUM(provider_status = 'read') read_count,
            SUM(provider_status = 'failed') failed
            FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = " . (int) $storeId)->fetch() ?: [];

        $total = max(1, (int) ($row['total'] ?? 0));
        $replies = (int) $pdo->query("SELECT COUNT(*) FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE c.store_id = " . (int) $storeId . " AND m.direction = 'inbound'")->fetchColumn();
        $revenue = (float) $pdo->query("SELECT COALESCE(SUM(revenue),0) FROM analytics_events WHERE store_id = " . (int) $storeId . " AND event_type = 'conversion'")->fetchColumn();
        $clicks = (int) $pdo->query("SELECT COUNT(*) FROM analytics_events WHERE store_id = " . (int) $storeId . " AND event_type = 'click'")->fetchColumn();
        $conversions = (int) $pdo->query("SELECT COUNT(*) FROM analytics_events WHERE store_id = " . (int) $storeId . " AND event_type = 'conversion'")->fetchColumn();

        $best = $pdo->query("SELECT c.id, c.name, COUNT(cm.id) sent_count, SUM(cm.provider_status = 'read') read_messages FROM campaigns c LEFT JOIN campaign_messages cm ON cm.campaign_id = c.id WHERE c.store_id = " . (int) $storeId . " GROUP BY c.id, c.name ORDER BY read_messages DESC, sent_count DESC LIMIT 5")->fetchAll();

        return [
            'delivery_rate' => round(((int) ($row['delivered'] ?? 0) / $total) * 100, 2),
            'read_rate' => round(((int) ($row['read_count'] ?? 0) / $total) * 100, 2),
            'failed_rate' => round(((int) ($row['failed'] ?? 0) / $total) * 100, 2),
            'reply_rate' => round(($replies / $total) * 100, 2),
            'ctr' => round(($clicks / $total) * 100, 2),
            'conversion_rate' => round(($conversions / $total) * 100, 2),
            'revenue_generated' => $revenue,
            'best_campaigns' => $best,
        ];
    }

    private function emptySummary(): array
    {
        return [
            'delivery_rate' => 0,
            'read_rate' => 0,
            'failed_rate' => 0,
            'reply_rate' => 0,
            'ctr' => 0,
            'conversion_rate' => 0,
            'revenue_generated' => 0,
            'best_campaigns' => [],
        ];
    }
}
