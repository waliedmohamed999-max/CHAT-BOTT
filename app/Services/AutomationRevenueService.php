<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\Validator;

final class AutomationRevenueService
{
    private const FLOW_TEMPLATES = [
        'abandoned_cart' => [
            'name' => 'Abandoned Cart Flow',
            'arabic' => 'استرجاع السلة المتروكة',
            'trigger' => 'cart_abandoned',
            'description' => 'بعد 30 دقيقة تذكير، بعد 6 ساعات كوبون، بعد 24 ساعة تحويل لموظف مبيعات.',
            'steps' => [
                ['type' => 'trigger', 'title' => 'سلة متروكة', 'config' => ['event' => 'cart_abandoned']],
                ['type' => 'delay', 'title' => 'انتظار 30 دقيقة', 'config' => ['minutes' => 30]],
                ['type' => 'condition', 'title' => 'Opt-in + لم يتم الشراء', 'config' => ['opt_in_required' => true, 'stop_if_purchased' => true]],
                ['type' => 'whatsapp_message', 'title' => 'تذكير بالسلة', 'config' => ['template_required_after_24h' => true, 'message' => 'سلتك ما زالت محفوظة. أكمل طلبك قبل انتهاء الكمية.']],
                ['type' => 'delay', 'title' => 'انتظار 6 ساعات', 'config' => ['hours' => 6]],
                ['type' => 'coupon', 'title' => 'كوبون تلقائي', 'config' => ['coupon' => 'CART10', 'discount' => 10]],
                ['type' => 'whatsapp_message', 'title' => 'إرسال الكوبون', 'config' => ['message' => 'استخدم كوبون CART10 لإكمال طلبك بخصم خاص.']],
                ['type' => 'delay', 'title' => 'انتظار 24 ساعة', 'config' => ['hours' => 24]],
                ['type' => 'human_handover', 'title' => 'تحويل لمبيعات', 'config' => ['department' => 'sales']],
                ['type' => 'stop_condition', 'title' => 'إيقاف عند الشراء أو إلغاء الاشتراك', 'config' => ['events' => ['order_paid', 'unsubscribe']]],
            ],
        ],
        'post_purchase' => [
            'name' => 'Post Purchase Flow',
            'arabic' => 'ما بعد الشراء',
            'trigger' => 'order_paid',
            'description' => 'رسالة شكر، تحديث الشحن، طلب تقييم، واقتراح منتجات مشابهة.',
            'steps' => [
                ['type' => 'trigger', 'title' => 'تم الشراء', 'config' => ['event' => 'order_paid']],
                ['type' => 'whatsapp_message', 'title' => 'رسالة شكر', 'config' => ['message' => 'شكراً لطلبك. سنوافيك بتحديثات الشحن أولاً بأول.']],
                ['type' => 'condition', 'title' => 'تحديث الشحن متاح', 'config' => ['requires_tracking' => true]],
                ['type' => 'whatsapp_message', 'title' => 'تحديث الشحن', 'config' => ['template_required_after_24h' => true, 'message' => 'تم تحديث حالة شحنتك.']],
                ['type' => 'delay', 'title' => 'بعد الاستلام', 'config' => ['days_after_delivery' => 1]],
                ['type' => 'whatsapp_message', 'title' => 'طلب تقييم', 'config' => ['message' => 'يسعدنا تقييم تجربتك معنا.']],
                ['type' => 'ai_reply', 'title' => 'اقتراح منتجات مشابهة', 'config' => ['source' => 'knowledge_base_products']],
                ['type' => 'stop_condition', 'title' => 'إيقاف عند الشكوى أو إلغاء الاشتراك', 'config' => ['events' => ['complaint', 'unsubscribe']]],
            ],
        ],
        'payment_reminder' => [
            'name' => 'Payment Reminder Flow',
            'arabic' => 'تذكير الدفع',
            'trigger' => 'payment_pending',
            'description' => 'تذكير بالدفع، إرسال رابط دفع، وتحويل للحسابات عند الفشل.',
            'steps' => [
                ['type' => 'trigger', 'title' => 'دفع معلق', 'config' => ['event' => 'payment_pending']],
                ['type' => 'delay', 'title' => 'انتظار 15 دقيقة', 'config' => ['minutes' => 15]],
                ['type' => 'whatsapp_message', 'title' => 'تذكير بالدفع', 'config' => ['message' => 'طلبك بانتظار الدفع. يمكنك إكمال الدفع من الرابط التالي.']],
                ['type' => 'coupon', 'title' => 'رابط الدفع', 'config' => ['payment_link_variable' => '{{payment_link}}']],
                ['type' => 'condition', 'title' => 'فشل الدفع', 'config' => ['event' => 'payment_failed']],
                ['type' => 'human_handover', 'title' => 'تحويل للحسابات', 'config' => ['department' => 'billing']],
                ['type' => 'stop_condition', 'title' => 'إيقاف عند الدفع', 'config' => ['events' => ['order_paid']]],
            ],
        ],
        'reactivation' => [
            'name' => 'Customer Reactivation',
            'arabic' => 'إعادة تنشيط العملاء',
            'trigger' => 'customer_inactive_30_days',
            'description' => 'عرض خاص للعملاء غير النشطين 30 يوم مع متابعة الردود.',
            'steps' => [
                ['type' => 'trigger', 'title' => 'عميل غير نشط 30 يوم', 'config' => ['inactive_days' => 30]],
                ['type' => 'condition', 'title' => 'Opt-in + ليس لديه طلب مفتوح', 'config' => ['opt_in_required' => true, 'no_open_orders' => true]],
                ['type' => 'coupon', 'title' => 'عرض خاص', 'config' => ['coupon' => 'BACK15', 'discount' => 15]],
                ['type' => 'whatsapp_message', 'title' => 'Win-back Offer', 'config' => ['template_required_after_24h' => true, 'message' => 'اشتقنالك. استخدم BACK15 واحصل على عرض خاص لفترة محدودة.']],
                ['type' => 'ai_reply', 'title' => 'متابعة الردود', 'config' => ['intent' => 'purchase']],
                ['type' => 'human_handover', 'title' => 'تحويل للمبيعات عند نية شراء', 'config' => ['department' => 'sales', 'when_intent' => 'purchase']],
                ['type' => 'stop_condition', 'title' => 'إيقاف عند الشراء أو إلغاء الاشتراك', 'config' => ['events' => ['order_paid', 'unsubscribe']]],
            ],
        ],
    ];

    public function overview(int $storeId): array
    {
        try {
            $pdo = Database::pdo();
            $stats = $pdo->query('SELECT COALESCE(SUM(revenue_amount),0) revenue, SUM(event_type = "cart_recovered") recovered_carts, SUM(event_type = "coupon_used") coupons_used, SUM(event_type = "automation_failed") failed FROM automation_revenue_events WHERE store_id = ' . $storeId)->fetch() ?: [];
            $best = $pdo->query('SELECT a.name, COALESCE(SUM(e.revenue_amount),0) revenue FROM automations a LEFT JOIN automation_revenue_events e ON e.automation_id = a.id WHERE a.store_id = ' . $storeId . ' GROUP BY a.id, a.name ORDER BY revenue DESC LIMIT 1')->fetch();
            $runs = (int) $pdo->query('SELECT COUNT(*) FROM automation_runs WHERE store_id = ' . $storeId)->fetchColumn();
            $converted = (int) $pdo->query("SELECT COUNT(*) FROM automation_runs WHERE store_id = {$storeId} AND status = 'converted'")->fetchColumn();
            return [
                'revenue_generated' => (float) ($stats['revenue'] ?? 0),
                'recovered_carts' => (int) ($stats['recovered_carts'] ?? 0),
                'conversion_rate' => $runs > 0 ? round(($converted / $runs) * 100, 1) : 0,
                'coupons_used' => (int) ($stats['coupons_used'] ?? 0),
                'best_flow' => $best['name'] ?? 'لا توجد بيانات بعد',
                'failed_automations' => (int) ($stats['failed'] ?? 0),
            ];
        } catch (\Throwable) {
            return ['revenue_generated' => 0, 'recovered_carts' => 0, 'conversion_rate' => 0, 'coupons_used' => 0, 'best_flow' => 'لا توجد بيانات بعد', 'failed_automations' => 0];
        }
    }

    public function templates(): array
    {
        return self::FLOW_TEMPLATES;
    }

    public function flows(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM automations WHERE store_id = ? ORDER BY id DESC LIMIT 100');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function installTemplate(int $storeId, string $templateKey): int
    {
        if (!isset(self::FLOW_TEMPLATES[$templateKey])) {
            throw new \InvalidArgumentException('automation_template_not_found');
        }
        $template = self::FLOW_TEMPLATES[$templateKey];
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO automations (store_id, name, trigger_event, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $template['arabic'], $template['trigger'], 'active']);
        $automationId = (int) $pdo->lastInsertId();
        $this->replaceSteps($automationId, $template['steps']);
        AuditLogger::record('automation.revenue_template_installed', $storeId, Rbac::userId(), 'automation', $automationId, ['template' => $templateKey]);
        return $automationId;
    }

    public function saveFlow(int $storeId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('automation_name_required');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO automations (store_id, name, trigger_event, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $name, $data['trigger_event'] ?? 'manual', $data['status'] ?? 'draft']);
        $automationId = (int) $pdo->lastInsertId();
        $this->replaceSteps($automationId, is_array($data['steps'] ?? null) ? $data['steps'] : []);
        AuditLogger::record('automation.revenue_flow_created', $storeId, Rbac::userId(), 'automation', $automationId);
        return $automationId;
    }

    public function trigger(int $storeId, array $event): array
    {
        $eventType = (string) ($event['event_type'] ?? $event['trigger_event'] ?? '');
        if ($eventType === '') {
            throw new \InvalidArgumentException('automation_event_required');
        }
        $contactId = isset($event['contact_id']) ? (int) $event['contact_id'] : null;
        $contact = $this->contact($storeId, $contactId, (string) ($event['phone'] ?? ''));
        if (!$contact || ($contact['opt_in_status'] ?? '') !== 'opted_in' || !empty($contact['unsubscribed_at'])) {
            $this->event($storeId, null, null, $contactId, 'automation_blocked_opt_in', 0, $event);
            return ['queued' => 0, 'blocked' => 'opt_in_required'];
        }

        $stmt = Database::pdo()->prepare("SELECT * FROM automations WHERE store_id = ? AND trigger_event = ? AND status = 'active'");
        $stmt->execute([$storeId, $eventType]);
        $queued = 0;
        foreach ($stmt->fetchAll() as $automation) {
            $runId = $this->createRun($storeId, (int) $automation['id'], (int) $contact['id'], $event);
            $this->event($storeId, (int) $automation['id'], $runId, (int) $contact['id'], 'automation_triggered', 0, $event);
            $queued++;
        }
        return ['queued' => $queued, 'event_type' => $eventType];
    }

    public function processDue(int $storeId, int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare("SELECT r.*, a.name automation_name FROM automation_runs r JOIN automations a ON a.id = r.automation_id WHERE r.store_id = ? AND r.status IN ('queued','running') AND r.next_run_at <= NOW() ORDER BY r.next_run_at ASC LIMIT " . max(1, min(100, $limit)));
        $stmt->execute([$storeId]);
        $processed = 0;
        $failed = 0;
        foreach ($stmt->fetchAll() as $run) {
            try {
                $this->processRun($storeId, $run);
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                Database::pdo()->prepare("UPDATE automation_runs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?")->execute([$e->getMessage(), $run['id']]);
                $this->event($storeId, (int) $run['automation_id'], (int) $run['id'], (int) $run['contact_id'], 'automation_failed', 0, ['error' => $e->getMessage()]);
            }
        }
        return ['processed' => $processed, 'failed' => $failed];
    }

    private function replaceSteps(int $automationId, array $steps): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM automation_steps WHERE automation_id = ?')->execute([$automationId]);
        $stmt = $pdo->prepare('INSERT INTO automation_steps (automation_id, step_type, sort_order, config_json, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        foreach (array_values($steps) as $index => $step) {
            $stmt->execute([$automationId, $this->mapStepType((string) ($step['type'] ?? 'condition')), $index + 1, json_encode($step, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    private function mapStepType(string $type): string
    {
        return match ($type) {
            'delay' => 'delay',
            'condition', 'stop_condition' => 'condition',
            'whatsapp_message', 'ai_reply', 'coupon' => 'send_template',
            'human_handover' => 'tag',
            default => 'webhook',
        };
    }

    private function contact(int $storeId, ?int $contactId, string $phone): ?array
    {
        if ($contactId) {
            $stmt = Database::pdo()->prepare('SELECT * FROM contacts WHERE store_id = ? AND id = ? LIMIT 1');
            $stmt->execute([$storeId, $contactId]);
            return $stmt->fetch() ?: null;
        }
        if ($phone !== '') {
            $stmt = Database::pdo()->prepare('SELECT * FROM contacts WHERE store_id = ? AND phone = ? LIMIT 1');
            $stmt->execute([$storeId, Validator::phone($phone)]);
            return $stmt->fetch() ?: null;
        }
        return null;
    }

    private function createRun(int $storeId, int $automationId, int $contactId, array $payload): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO automation_runs (store_id, automation_id, contact_id, status, current_step, context_json, next_run_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())');
        $stmt->execute([$storeId, $automationId, $contactId, 'queued', 0, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        return (int) Database::pdo()->lastInsertId();
    }

    private function processRun(int $storeId, array $run): void
    {
        $steps = Database::pdo()->prepare('SELECT * FROM automation_steps WHERE automation_id = ? ORDER BY sort_order ASC');
        $steps->execute([(int) $run['automation_id']]);
        $rows = $steps->fetchAll();
        $index = (int) $run['current_step'];
        $step = $rows[$index] ?? null;
        if (!$step) {
            Database::pdo()->prepare("UPDATE automation_runs SET status = 'completed', updated_at = NOW() WHERE id = ?")->execute([$run['id']]);
            return;
        }
        $config = json_decode((string) $step['config_json'], true) ?: [];
        $type = (string) ($config['type'] ?? $step['step_type']);
        if ($type === 'delay') {
            $minutes = (int) ($config['config']['minutes'] ?? 0);
            $hours = (int) ($config['config']['hours'] ?? 0);
            $delayMinutes = max(1, $minutes + ($hours * 60));
            Database::pdo()->prepare("UPDATE automation_runs SET status = 'running', current_step = current_step + 1, next_run_at = DATE_ADD(NOW(), INTERVAL {$delayMinutes} MINUTE), updated_at = NOW() WHERE id = ?")->execute([$run['id']]);
            return;
        }
        $this->event($storeId, (int) $run['automation_id'], (int) $run['id'], (int) $run['contact_id'], 'automation_step_executed', (float) ($config['config']['revenue'] ?? 0), $config);
        Database::pdo()->prepare("UPDATE automation_runs SET status = 'running', current_step = current_step + 1, next_run_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$run['id']]);
    }

    private function event(int $storeId, ?int $automationId, ?int $runId, ?int $contactId, string $eventType, float $revenue, array $payload): void
    {
        try {
            Database::pdo()->prepare('INSERT INTO automation_revenue_events (store_id, automation_id, automation_run_id, contact_id, event_type, revenue_amount, payload_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')->execute([
                $storeId,
                $automationId,
                $runId,
                $contactId,
                $eventType,
                $revenue,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable) {
        }
    }
}
