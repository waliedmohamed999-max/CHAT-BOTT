<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\Validator;

final class ChatbotService
{
    private const CONNECTION_SOURCES = ['meta_cloud_api', 'qr_web_session', 'both', 'all_channels', 'whatsapp_cloud', 'whatsapp_qr', 'instagram', 'facebook', 'telegram', 'email', 'sms', 'live_chat'];
    private const DEPARTMENTS = [
        ['name' => 'المبيعات', 'slug' => 'sales', 'color' => '#2fbf71', 'welcome_message' => 'يسعدنا مساعدتك في المبيعات. هل تريد معرفة الأسعار أم العروض أم التحدث مع مستشار مبيعات؟', 'auto_tag' => 'sales', 'priority' => 'high'],
        ['name' => 'الدعم الفني', 'slug' => 'support', 'color' => '#2f80ed', 'welcome_message' => 'من فضلك اختر نوع المشكلة التي تواجهك.', 'auto_tag' => 'support', 'priority' => 'high'],
        ['name' => 'الطلبات والشحن', 'slug' => 'orders', 'color' => '#8b5cf6', 'welcome_message' => 'يمكنك متابعة طلبك من هنا. من فضلك أرسل رقم الطلب.', 'auto_tag' => 'orders', 'priority' => 'normal'],
        ['name' => 'الحسابات والفواتير', 'slug' => 'billing', 'color' => '#f59e0b', 'welcome_message' => 'اختر الخدمة المطلوبة.', 'auto_tag' => 'billing', 'priority' => 'normal'],
        ['name' => 'الشكاوى', 'slug' => 'complaints', 'color' => '#ef4444', 'welcome_message' => 'نأسف لسماع ذلك. من فضلك اكتب تفاصيل الشكوى وسيتم تحويلها للقسم المختص.', 'auto_tag' => 'complaints', 'priority' => 'urgent'],
    ];

    public function builder(int $storeId): array
    {
        return [
            'flow' => $this->defaultDepartmentFlow(),
            'departments' => $this->departments($storeId),
            'preview' => $this->preview($storeId, null),
            'health' => $this->builderHealth($storeId),
            'flows' => $this->flows($storeId),
        ];
    }

    public function activateFlow(int $storeId, int $flowId): void
    {
        $diagnostics = $this->routeDiagnostics($storeId);
        if (empty($diagnostics['ready'])) {
            throw new \RuntimeException('chatbot_route_diagnostics_failed');
        }

        Database::pdo()->prepare("UPDATE chatbot_flows SET status = 'paused', updated_at = NOW() WHERE store_id = ?")->execute([$storeId]);
        Database::pdo()->prepare("UPDATE chatbot_flows SET status = 'active', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([$flowId, $storeId]);
        AuditLogger::record('chatbot.flow_activated', $storeId, Rbac::userId(), 'chatbot_flow', $flowId);
    }

    public function createNode(int $storeId, array $data): int
    {
        $flowId = (int) ($data['flow_id'] ?? 0);
        $type = $this->normalizeNodeType((string) ($data['type'] ?? $data['node_type'] ?? 'message'));
        $title = trim((string) ($data['title'] ?? 'عقدة جديدة'));
        $message = (string) ($data['message'] ?? '');
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_nodes (flow_id, node_key, node_type, title, message, options_json, department_id, config_json, position_x, position_y, settings_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $flowId,
            $data['node_key'] ?? uniqid('node_'),
            $type,
            $title,
            $message,
            json_encode($data['options'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['department_id'] ?? null,
            json_encode($data['config'] ?? [], JSON_UNESCAPED_UNICODE),
            (int) ($data['position_x'] ?? 120),
            (int) ($data['position_y'] ?? 120),
            json_encode($data['settings'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function updateNode(int $storeId, int $nodeId, array $data): void
    {
        $stmt = Database::pdo()->prepare('UPDATE chatbot_nodes n JOIN chatbot_flows f ON f.id = n.flow_id SET n.title = COALESCE(?, n.title), n.node_type = COALESCE(?, n.node_type), n.message = ?, n.options_json = ?, n.department_id = ?, n.settings_json = ?, n.updated_at = NOW() WHERE n.id = ? AND f.store_id = ?');
        $stmt->execute([
            $data['title'] ?? null,
            isset($data['type']) || isset($data['node_type']) ? $this->normalizeNodeType((string) ($data['type'] ?? $data['node_type'])) : null,
            $data['message'] ?? null,
            json_encode($data['options'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['department_id'] ?? null,
            json_encode($data['settings'] ?? [], JSON_UNESCAPED_UNICODE),
            $nodeId,
            $storeId,
        ]);
    }

    public function deleteNode(int $storeId, int $nodeId): void
    {
        Database::pdo()->prepare('DELETE n FROM chatbot_nodes n JOIN chatbot_flows f ON f.id = n.flow_id WHERE n.id = ? AND f.store_id = ?')->execute([$nodeId, $storeId]);
    }

    public function createEdge(int $storeId, array $data): int
    {
        $flowId = (int) ($data['flow_id'] ?? 0);
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_edges (flow_id, source_node_key, target_node_key, source_node_id, target_node_id, condition_json, option_value, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $flowId,
            $data['source'] ?? $data['source_node_key'] ?? '',
            $data['target'] ?? $data['target_node_key'] ?? '',
            $data['source_node_id'] ?? null,
            $data['target_node_id'] ?? null,
            json_encode($data['condition'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['option_value'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function deleteEdge(int $storeId, int $edgeId): void
    {
        Database::pdo()->prepare('DELETE e FROM chatbot_edges e JOIN chatbot_flows f ON f.id = e.flow_id WHERE e.id = ? AND f.store_id = ?')->execute([$edgeId, $storeId]);
    }

    public function departments(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM departments WHERE store_id = ? ORDER BY priority DESC, id ASC');
            $stmt->execute([$storeId]);
            $rows = $stmt->fetchAll();
            return $rows ?: self::DEPARTMENTS;
        } catch (\Throwable) {
            return self::DEPARTMENTS;
        }
    }

    public function saveDepartment(int $storeId, array $data, ?int $id = null): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('department_name_required');
        }
        $slug = trim((string) ($data['slug'] ?? preg_replace('/\s+/u', '-', $name)));
        if ($id) {
            $stmt = Database::pdo()->prepare('UPDATE departments SET name = ?, slug = ?, color = ?, welcome_message = ?, away_message = ?, working_hours = ?, priority = ?, is_active = ?, auto_tag = ?, updated_at = NOW() WHERE id = ? AND store_id = ?');
            $stmt->execute([$name, $slug, $data['color'] ?? '#2fbf71', $data['welcome_message'] ?? '', $data['away_message'] ?? '', $data['working_hours'] ?? '09:00-18:00', $data['priority'] ?? 'normal', !empty($data['is_active']) ? 1 : 0, $data['auto_tag'] ?? $slug, $id, $storeId]);
            return $id;
        }
        $stmt = Database::pdo()->prepare('INSERT INTO departments (store_id, name, slug, color, welcome_message, away_message, working_hours, priority, is_active, auto_tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $name, $slug, $data['color'] ?? '#2fbf71', $data['welcome_message'] ?? '', $data['away_message'] ?? '', $data['working_hours'] ?? '09:00-18:00', $data['priority'] ?? 'normal', !empty($data['is_active']) ? 1 : 0, $data['auto_tag'] ?? $slug]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function preview(int $storeId, ?int $flowId): array
    {
        return [
            ['direction' => 'bot', 'body' => "أهلاً بك 👋\nكيف يمكننا مساعدتك اليوم؟", 'time' => '09:41', 'buttons' => ['المبيعات', 'الدعم الفني', 'الطلبات والشحن', 'الحسابات والفواتير', 'الشكاوى', 'التحدث مع موظف']],
            ['direction' => 'customer', 'body' => 'المبيعات', 'time' => '09:42'],
            ['direction' => 'bot', 'body' => 'يسعدنا مساعدتك في المبيعات. هل تريد معرفة الأسعار أم العروض أم التحدث مع مستشار مبيعات؟', 'time' => '09:42', 'buttons' => ['الأسعار', 'العروض', 'مستشار مبيعات']],
        ];
    }

    public function processMessage(int $storeId, array $payload): array
    {
        $message = trim((string) ($payload['body'] ?? $payload['message'] ?? ''));
        $department = $this->departmentForMessage($message);
        if ($department) {
            return [
                'action' => in_array($department['slug'], ['complaints', 'human'], true) ? 'handover' : 'department_reply',
                'department' => $department['slug'],
                'queue' => $this->queueName($department['slug']),
                'tag' => $department['auto_tag'] ?? $department['slug'],
                'bot_paused' => in_array($department['slug'], ['complaints', 'human'], true),
                'reply' => $department['welcome_message'],
                'buttons' => $this->departmentButtons((string) $department['slug']),
            ];
        }
        return $this->processWebhook($storeId, ['body' => $message, 'connection_source' => $payload['connection_source'] ?? 'both']);
    }

    public function routeDiagnostics(int $storeId): array
    {
        $departmentTests = [
            ['label' => 'المبيعات', 'expected_department' => 'sales', 'expected_action' => 'department_reply'],
            ['label' => 'الدعم الفني', 'expected_department' => 'support', 'expected_action' => 'department_reply'],
            ['label' => 'الطلبات والشحن', 'expected_department' => 'orders', 'expected_action' => 'department_reply'],
            ['label' => 'الحسابات والفواتير', 'expected_department' => 'billing', 'expected_action' => 'department_reply'],
            ['label' => 'الشكاوى', 'expected_department' => 'complaints', 'expected_action' => 'handover'],
            ['label' => 'التحدث مع موظف', 'expected_department' => 'human', 'expected_action' => 'handover'],
        ];

        $routes = [];
        foreach ($departmentTests as $test) {
            try {
                $result = $this->processMessage($storeId, ['body' => $test['label'], 'connection_source' => 'both']);
                $passed = ($result['department'] ?? null) === $test['expected_department'] && ($result['action'] ?? null) === $test['expected_action'];
                $routes[] = [
                    'label' => $test['label'],
                    'expected_department' => $test['expected_department'],
                    'department' => $result['department'] ?? null,
                    'action' => $result['action'] ?? null,
                    'queue' => $result['queue'] ?? null,
                    'passed' => $passed,
                ];
            } catch (\Throwable $e) {
                $routes[] = [
                    'label' => $test['label'],
                    'expected_department' => $test['expected_department'],
                    'department' => null,
                    'action' => 'failed',
                    'queue' => null,
                    'passed' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $intentTests = [
            ['message' => 'أريد معرفة سعر المنتج', 'expected_intent' => 'purchase', 'expected_department' => 'sales'],
            ['message' => 'أريد تتبع الشحن', 'expected_intent' => 'order_status', 'expected_department' => 'orders'],
            ['message' => 'عندي مشكلة في الدفع', 'expected_intent' => 'invoice', 'expected_department' => 'billing'],
            ['message' => 'أريد تقديم شكوى', 'expected_intent' => 'complaint', 'expected_department' => 'complaints'],
            ['message' => 'أريد التحدث مع موظف', 'expected_intent' => 'support', 'expected_department' => 'support', 'expected_human' => true],
        ];

        $intents = [];
        foreach ($intentTests as $test) {
            $result = $this->classifyMessage($test['message']);
            $passed = ($result['intent'] ?? null) === $test['expected_intent']
                && ($result['department'] ?? null) === $test['expected_department']
                && (!array_key_exists('expected_human', $test) || (bool) $result['needs_human'] === (bool) $test['expected_human']);
            $intents[] = [
                'message' => $test['message'],
                'expected_intent' => $test['expected_intent'],
                'intent' => $result['intent'] ?? null,
                'expected_department' => $test['expected_department'],
                'department' => $result['department'] ?? null,
                'needs_human' => (bool) ($result['needs_human'] ?? false),
                'passed' => $passed,
            ];
        }

        $total = count($routes) + count($intents);
        $passed = count(array_filter([...$routes, ...$intents], static fn (array $item): bool => !empty($item['passed'])));

        return [
            'score' => (int) round(($passed / max(1, $total)) * 100),
            'ready' => $passed === $total,
            'passed' => $passed,
            'total' => $total,
            'routes' => $routes,
            'intents' => $intents,
            'warnings' => $passed === $total ? [] : ['بعض مسارات الأقسام أو تصنيفات AI تحتاج مراجعة قبل نشر البوت.'],
        ];
    }

    public function handleIncomingWhatsAppMessage(int $storeId, ?int $conversationId, ?int $contactId, string $customerPhone, string $body, string $source, array $payload = []): array
    {
        $source = Validator::enum($source, ['meta_cloud_api', 'qr_web_session', 'whatsapp_cloud', 'whatsapp_qr'], 'invalid_connection_source');
        $contactId = $contactId ?: $this->ensureBotContact($storeId, $customerPhone);
        $conversationId = $conversationId ?: $this->ensureBotConversation($storeId, $contactId);
        $session = $this->sessionForConversation($storeId, $conversationId, $contactId, $source);

        $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'customer_message_received', ['body' => $body, 'source' => $source]);

        if (!empty($session['bot_paused']) || ($session['status'] ?? '') === 'human_handover') {
            $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'bot_skipped_paused', ['body' => $body]);
            return ['action' => 'skipped', 'reason' => 'bot_paused', 'conversation_id' => $conversationId];
        }

        $department = $this->departmentForMessage($body);
        if ($department) {
            $reply = (string) $department['welcome_message'];
            $handover = in_array($department['slug'], ['complaints', 'human'], true);
            $departmentId = $this->ensureDepartment($storeId, $department);

            $this->updateSessionDepartment((int) $session['id'], $departmentId, $handover);
            $this->assignConversation($storeId, $conversationId, $departmentId, $department['slug'], $handover);
            $this->tagConversation($conversationId, $department['auto_tag'] ?? $department['slug'], $department['slug']);
            $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'department_selected', ['department' => $department['slug'], 'queue' => $this->queueName($department['slug'])]);

            $send = $this->dispatchBotReply($storeId, $conversationId, $customerPhone, $reply, $source);
            if ($handover) {
                $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'human_handover_created', ['department' => $department['slug']]);
            }
            return ['action' => $handover ? 'handover' : 'department_reply', 'reply' => $reply, 'buttons' => $this->departmentButtons((string) $department['slug']), 'send' => $send, 'conversation_id' => $conversationId, 'department' => $department['slug'], 'queue' => $this->queueName($department['slug'])];
        }

        if (empty($session['current_node_key']) || ($session['current_node_key'] ?? '') === 'start') {
            $reply = "أهلاً بك 👋\nكيف يمكننا مساعدتك اليوم؟\n\n- المبيعات\n- الدعم الفني\n- الطلبات والشحن\n- الحسابات والفواتير\n- الشكاوى\n- التحدث مع موظف";
            $this->advanceSession((int) $session['id'], 'departments', ['welcome_sent_at' => date('c')]);
            $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'welcome_sent', ['source' => $source]);
            $send = $this->dispatchBotReply($storeId, $conversationId, $customerPhone, $reply, $source);
            return ['action' => 'welcome', 'reply' => $reply, 'send' => $send, 'conversation_id' => $conversationId];
        }

        $reply = 'تم استلام رسالتك. اختر أحد الأقسام أو اكتب "التحدث مع موظف" للتحويل لخدمة العملاء.';
        $ai = $this->aiAgentResponse($storeId, [
            'message' => $body,
            'conversation_id' => $conversationId,
            'connection_source' => $source,
            'context' => $payload,
        ]);
        $this->saveConversationAiInsight($storeId, $conversationId, $ai);
        $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'ai_agent_analyzed_message', $ai);

        if (!empty($ai['needs_human'])) {
            $departmentSlug = (string) ($ai['department'] ?? 'support');
            $department = $this->departmentBySlug($departmentSlug) ?: self::DEPARTMENTS[1];
            $departmentId = $this->ensureDepartment($storeId, $department);
            $this->updateSessionDepartment((int) $session['id'], $departmentId, true);
            $this->assignConversation($storeId, $conversationId, $departmentId, $department['slug'], true);
            $this->tagConversation($conversationId, 'ai_handover', $department['slug']);
            $aiReply = (string) ($ai['reply'] ?? 'سيتم تحويلك لموظف مختص لمساعدتك بدقة.');
            $send = $this->dispatchBotReply($storeId, $conversationId, $customerPhone, $aiReply, $source);
            $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'ai_handover_created', ['department' => $department['slug'], 'intent' => $ai['intent'] ?? null]);
            return ['action' => 'ai_handover', 'reply' => $aiReply, 'send' => $send, 'conversation_id' => $conversationId, 'department' => $department['slug'], 'ai' => $ai];
        }

        if (($ai['action'] ?? '') === 'reply' && !empty($ai['reply'])) {
            $send = $this->dispatchBotReply($storeId, $conversationId, $customerPhone, (string) $ai['reply'], $source);
            $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'ai_reply_sent', ['intent' => $ai['intent'] ?? null, 'confidence' => $ai['confidence'] ?? null]);
            return ['action' => 'ai_reply', 'reply' => $ai['reply'], 'send' => $send, 'conversation_id' => $conversationId, 'ai' => $ai];
        }

        $this->logBotEvent($storeId, $conversationId, (int) $session['id'], 'fallback_reply_sent', ['body' => $body]);
        $send = $this->dispatchBotReply($storeId, $conversationId, $customerPhone, $reply, $source);
        return ['action' => 'fallback', 'reply' => $reply, 'send' => $send, 'conversation_id' => $conversationId];
    }

    public function pauseConversationBot(int $storeId, int $conversationId): void
    {
        try {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET bot_paused = 1, status = 'paused', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        } catch (\Throwable) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'paused', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        }
        $this->logBotEvent($storeId, $conversationId, null, 'bot_paused_manually', []);
    }

    public function resumeConversationBot(int $storeId, int $conversationId): void
    {
        try {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET bot_paused = 0, status = 'bot_active', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        } catch (\Throwable) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'bot_active', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        }
        $this->logBotEvent($storeId, $conversationId, null, 'bot_resumed_manually', []);
    }

    public function endConversation(int $storeId, int $conversationId): void
    {
        Database::pdo()->prepare("UPDATE conversations SET status = 'closed', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([$conversationId, $storeId]);
        try {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'completed', bot_paused = 1, updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        } catch (\Throwable) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'completed', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        }
        $this->logBotEvent($storeId, $conversationId, null, 'conversation_ended', []);
    }

    public function transferDepartment(int $storeId, int $conversationId, string $departmentSlug): array
    {
        $department = $this->departmentForMessage($departmentSlug) ?: $this->departmentBySlug($departmentSlug);
        if (!$department) {
            throw new \InvalidArgumentException('department_not_found');
        }
        $departmentId = $this->ensureDepartment($storeId, $department);
        $this->assignConversation($storeId, $conversationId, $departmentId, $department['slug'], true);
        try {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET selected_department_id = ?, bot_paused = 1, status = 'human_handover', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$departmentId, $storeId, $conversationId]);
        } catch (\Throwable) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'human_handover', updated_at = NOW() WHERE store_id = ? AND conversation_id = ?")->execute([$storeId, $conversationId]);
        }
        $this->logBotEvent($storeId, $conversationId, null, 'department_transferred', ['department' => $department['slug']]);
        return ['department' => $department['slug'], 'queue' => $this->queueName($department['slug'])];
    }

    private function builderHealth(int $storeId): array
    {
        $items = [
            [
                'label' => 'الأقسام',
                'ready' => count($this->departments($storeId)) >= 5,
                'message' => 'أقسام المبيعات والدعم والطلبات والفواتير والشكاوى مهيأة.',
            ],
            [
                'label' => 'مسار منشور',
                'ready' => $this->countRows("SELECT COUNT(*) FROM chatbot_flows WHERE store_id = ? AND status = 'active'", [$storeId]) > 0,
                'message' => 'يوجد مسار شات بوت منشور.',
            ],
            [
                'label' => 'ردود تلقائية',
                'ready' => $this->countRows('SELECT COUNT(*) FROM chatbot_auto_replies WHERE store_id = ?', [$storeId]) > 0,
                'message' => 'تم حفظ رد تلقائي واحد على الأقل.',
            ],
            [
                'label' => 'كلمات مفتاحية',
                'ready' => $this->countRows('SELECT COUNT(*) FROM chatbot_keywords WHERE store_id = ?', [$storeId]) > 0,
                'message' => 'تم ضبط كلمات مفتاحية لتشغيل الردود أو التحويل.',
            ],
            [
                'label' => 'قاعدة المعرفة',
                'ready' => $this->countRows('SELECT COUNT(*) FROM chatbot_knowledge_base WHERE store_id = ?', [$storeId]) > 0,
                'message' => 'قاعدة معرفة AI تحتوي على معلومات للرد الذكي.',
            ],
            [
                'label' => 'سجلات التشغيل',
                'ready' => $this->countRows('SELECT COUNT(*) FROM chatbot_event_logs WHERE store_id = ?', [$storeId]) > 0,
                'message' => 'توجد سجلات تشغيل للبوت أو الاختبارات.',
            ],
        ];

        $ready = count(array_filter($items, static fn (array $item): bool => !empty($item['ready'])));

        return [
            'score' => (int) round(($ready / max(1, count($items))) * 100),
            'items' => $items,
        ];
    }

    public function flows(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_flows WHERE store_id = ? ORDER BY id DESC LIMIT 100');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function currentFlow(int $storeId): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM chatbot_flows WHERE store_id = ? ORDER BY FIELD(status, 'active', 'draft', 'paused'), id DESC LIMIT 1");
        $stmt->execute([$storeId]);
        $flow = $stmt->fetch();
        return $flow ? $this->flowWithGraph($flow) : null;
    }

    public function flow(int $storeId, int $flowId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_flows WHERE id = ? AND store_id = ? LIMIT 1');
        $stmt->execute([$flowId, $storeId]);
        $flow = $stmt->fetch();
        return $flow ? $this->flowWithGraph($flow) : null;
    }

    public function createFlow(int $storeId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('flow_name_required');
        }
        $source = Validator::enum((string) ($data['connection_source'] ?? 'both'), self::CONNECTION_SOURCES, 'invalid_connection_source');
        $status = $this->normalizeFlowStatus((string) ($data['status'] ?? 'draft'));
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_flows (store_id, name, description, connection_source, status, trigger_type, trigger_value, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $name, $data['description'] ?? null, $source, $status === 'active' ? 'draft' : $status, $data['trigger_type'] ?? 'manual', $data['trigger_value'] ?? null, Rbac::userId()]);
        $flowId = (int) Database::pdo()->lastInsertId();
        $this->saveFlowGraph($flowId, $data['nodes'] ?? $this->defaultNodes(), $data['edges'] ?? []);
        if ($status === 'active') {
            $this->activateFlow($storeId, $flowId);
        }
        AuditLogger::record('chatbot.flow_created', $storeId, Rbac::userId(), 'chatbot_flow', $flowId);
        return $flowId;
    }

    public function updateFlow(int $storeId, int $flowId, array $data): void
    {
        $source = Validator::enum((string) ($data['connection_source'] ?? 'both'), self::CONNECTION_SOURCES, 'invalid_connection_source');
        $status = isset($data['status']) ? $this->normalizeFlowStatus((string) $data['status']) : null;
        $stmt = Database::pdo()->prepare('UPDATE chatbot_flows SET name = COALESCE(?, name), description = ?, connection_source = ?, status = COALESCE(?, status), trigger_type = COALESCE(?, trigger_type), trigger_value = ?, version = version + 1, updated_at = NOW() WHERE id = ? AND store_id = ?');
        $stmt->execute([$data['name'] ?? null, $data['description'] ?? null, $source, $status === 'active' ? 'draft' : $status, $data['trigger_type'] ?? null, $data['trigger_value'] ?? null, $flowId, $storeId]);
        if (isset($data['nodes']) || isset($data['edges'])) {
            Database::pdo()->prepare('DELETE FROM chatbot_nodes WHERE flow_id = ?')->execute([$flowId]);
            Database::pdo()->prepare('DELETE FROM chatbot_edges WHERE flow_id = ?')->execute([$flowId]);
            $this->saveFlowGraph($flowId, $data['nodes'] ?? [], $data['edges'] ?? []);
        }
        if ($status === 'active') {
            $this->activateFlow($storeId, $flowId);
        }
        AuditLogger::record('chatbot.flow_updated', $storeId, Rbac::userId(), 'chatbot_flow', $flowId);
    }

    public function deleteFlow(int $storeId, int $flowId): void
    {
        Database::pdo()->prepare('DELETE FROM chatbot_flows WHERE id = ? AND store_id = ?')->execute([$flowId, $storeId]);
        AuditLogger::record('chatbot.flow_deleted', $storeId, Rbac::userId(), 'chatbot_flow', $flowId);
    }

    public function keywords(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_keywords WHERE store_id = ? ORDER BY id DESC LIMIT 150');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function createKeyword(int $storeId, array $data): int
    {
        $keyword = trim((string) ($data['keyword'] ?? ''));
        if ($keyword === '') {
            throw new \InvalidArgumentException('keyword_required');
        }
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_keywords (store_id, keyword, match_type, action_type, reply_text, flow_id, connection_source, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE match_type = VALUES(match_type), action_type = VALUES(action_type), reply_text = VALUES(reply_text), flow_id = VALUES(flow_id), connection_source = VALUES(connection_source), status = VALUES(status), updated_at = NOW()');
        $stmt->execute([$storeId, $keyword, $data['match_type'] ?? 'contains', $data['action_type'] ?? 'reply', $data['reply_text'] ?? null, $data['flow_id'] ?? null, $data['connection_source'] ?? 'both', $data['status'] ?? 'active']);
        return (int) Database::pdo()->lastInsertId();
    }

    public function autoReplies(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_auto_replies WHERE store_id = ? ORDER BY id DESC LIMIT 150');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function createAutoReply(int $storeId, array $data): int
    {
        $errors = Validator::required($data, ['reply_type', 'name', 'message']);
        if ($errors) {
            throw new \InvalidArgumentException('auto_reply_required_fields');
        }
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_auto_replies (store_id, reply_type, name, message, conditions_json, connection_source, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $data['reply_type'], $data['name'], $data['message'], json_encode($data['conditions'] ?? []), $data['connection_source'] ?? 'both', $data['status'] ?? 'active']);
        return (int) Database::pdo()->lastInsertId();
    }

    public function aiReply(int $storeId, array $data): array
    {
        return $this->aiAgentResponse($storeId, $data);
        $message = trim((string) ($data['message'] ?? ''));
        $context = trim((string) ($data['context'] ?? ''));
        try {
            $kb = $this->searchKnowledge($storeId, $message);
        } catch (\Throwable) {
            $kb = [];
        }
        $reply = $kb ? $kb[0]['answer'] : 'شكراً لتواصلك. تم استلام رسالتك وسيتم الرد عليك قريباً.';
        try {
            $this->analytics($storeId, 'ai_reply_generated', null, $data['connection_source'] ?? null, null, 'generated', ['message' => $message, 'context' => $context]);
        } catch (\Throwable) {
            // Analytics must not block generated replies.
        }
        return ['reply' => $reply, 'confidence' => $kb ? 0.82 : 0.45, 'language' => preg_match('/[\x{0600}-\x{06FF}]/u', $message) ? 'ar' : 'en'];
    }

    public function classify(int $storeId, array $data): array
    {
        return $this->classifyMessage((string) ($data['message'] ?? ''));
        $message = mb_strtolower((string) ($data['message'] ?? ''));
        $intent = str_contains($message, 'سعر') || str_contains($message, 'price') ? 'pricing' : (str_contains($message, 'طلب') || str_contains($message, 'order') ? 'order_status' : 'general');
        return ['intent' => $intent, 'sentiment' => 'neutral', 'needs_human' => str_contains($message, 'موظف') || str_contains($message, 'agent')];
    }

    public function aiSettings(int $storeId): array
    {
        $defaults = $this->defaultAiSettings();
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_ai_settings WHERE store_id = ? LIMIT 1');
            $stmt->execute([$storeId]);
            $row = $stmt->fetch();
            if (!$row) {
                return $defaults;
            }
            $settings = [];
            if (!empty($row['encrypted_settings'])) {
                $settings = json_decode(Crypto::decrypt((string) $row['encrypted_settings']), true) ?: [];
            }
            return array_merge($defaults, $settings, [
                'enabled' => (bool) $row['enabled'],
                'provider' => $row['provider'] ?? $defaults['provider'],
                'model' => $row['model'] ?? $defaults['model'],
                'system_prompt' => $row['system_prompt'] ?? $defaults['system_prompt'],
            ]);
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public function saveAiSettings(int $storeId, array $data): array
    {
        $settings = [
            'tone' => (string) ($data['tone'] ?? 'مهني ودود'),
            'language' => (string) ($data['language'] ?? 'auto'),
            'reply_length' => (string) ($data['reply_length'] ?? 'short'),
            'daily_limit' => max(1, (int) ($data['daily_limit'] ?? 500)),
            'forbidden_topics' => (string) ($data['forbidden_topics'] ?? ''),
            'handover_after_minutes' => max(1, (int) ($data['handover_after_minutes'] ?? 10)),
            'min_confidence' => min(0.95, max(0.3, ((float) ($data['min_confidence'] ?? 70)) / 100)),
            'strict_knowledge_only' => true,
        ];
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_ai_settings (store_id, provider, model, encrypted_settings, system_prompt, enabled, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE provider = VALUES(provider), model = VALUES(model), encrypted_settings = VALUES(encrypted_settings), system_prompt = VALUES(system_prompt), enabled = VALUES(enabled), updated_at = NOW()');
        $stmt->execute([
            $storeId,
            $data['provider'] ?? 'internal_knowledge_agent',
            $data['model'] ?? 'knowledge-only',
            Crypto::encrypt(json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $data['system_prompt'] ?? 'أجب فقط من قاعدة المعرفة. عند عدم التأكد حول المحادثة لموظف.',
            !empty($data['enabled']) && $data['enabled'] !== '0' ? 1 : 0,
        ]);
        AuditLogger::record('chatbot.ai_settings_saved', $storeId, Rbac::userId(), 'chatbot_ai_settings', $storeId);
        return $this->aiSettings($storeId);
    }

    public function knowledgeBase(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_knowledge_base WHERE store_id = ? ORDER BY id DESC LIMIT 200');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function saveKnowledgeItem(int $storeId, array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));
        if ($title === '' || $answer === '') {
            throw new \InvalidArgumentException('knowledge_required_fields');
        }
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_knowledge_base (store_id, title, category, question, answer, tags_json, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $title, $data['category'] ?? 'faq', $data['question'] ?? null, $answer, json_encode($this->normalizeTags((string) ($data['tags'] ?? '')), JSON_UNESCAPED_UNICODE), $data['status'] ?? 'active']);
        $id = (int) Database::pdo()->lastInsertId();
        AuditLogger::record('chatbot.knowledge_created', $storeId, Rbac::userId(), 'chatbot_knowledge_base', $id);
        return $id;
    }

    public function saveKnowledgeFile(int $storeId, array $file, array $data): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('file_required');
        }
        if ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('file_too_large');
        }
        $allowed = [
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'text/plain' => 'txt',
        ];
        $mime = (string) ($file['type'] ?? '');
        if (!isset($allowed[$mime])) {
            throw new \InvalidArgumentException('invalid_file_type');
        }
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'chatbot-knowledge' . DIRECTORY_SEPARATOR . $storeId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename((string) $file['name']));
        $path = $dir . DIRECTORY_SEPARATOR . date('YmdHis') . '_' . $safeName;
        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            throw new \RuntimeException('upload_failed');
        }
        $title = trim((string) ($data['title'] ?? $file['name']));
        $answer = trim((string) ($data['summary'] ?? 'تم رفع ملف معرفة. لا يستخدمه AI للرد إلا بعد تلخيص محتواه داخل قاعدة المعرفة.'));
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO chatbot_knowledge_base (store_id, title, category, question, answer, tags_json, status, source_type, file_name, file_path, file_mime_type, file_size, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$storeId, $title, $data['category'] ?? 'documents', $data['question'] ?? null, $answer, json_encode($this->normalizeTags((string) ($data['tags'] ?? '')), JSON_UNESCAPED_UNICODE), 'draft', 'file', $file['name'], $path, $mime, (int) $file['size']]);
        } catch (\Throwable) {
            $stmt = Database::pdo()->prepare('INSERT INTO chatbot_knowledge_base (store_id, title, category, question, answer, tags_json, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$storeId, $title, $data['category'] ?? 'documents', $data['question'] ?? null, $answer, json_encode($this->normalizeTags((string) ($data['tags'] ?? '')), JSON_UNESCAPED_UNICODE), 'draft']);
        }
        $id = (int) Database::pdo()->lastInsertId();
        AuditLogger::record('chatbot.knowledge_file_uploaded', $storeId, Rbac::userId(), 'chatbot_knowledge_base', $id);
        return $id;
    }

    public function conversationAiInsight(int $storeId, int $conversationId, ?string $message = null): array
    {
        if ($message !== null && trim($message) !== '') {
            $insight = $this->aiAgentResponse($storeId, ['message' => $message, 'conversation_id' => $conversationId]);
            $this->saveConversationAiInsight($storeId, $conversationId, $insight);
            return $insight;
        }
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_ai_conversation_insights WHERE store_id = ? AND conversation_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$storeId, $conversationId]);
            $row = $stmt->fetch();
            if ($row) {
                return [
                    'intent' => $row['intent'],
                    'sentiment' => $row['sentiment'],
                    'priority' => $row['priority'],
                    'lead_score' => (int) $row['lead_score'],
                    'summary' => $row['summary'],
                    'suggested_replies' => json_decode((string) ($row['suggested_replies_json'] ?? '[]'), true) ?: [],
                    'recommended_next_action' => $row['recommended_next_action'],
                ];
            }
        } catch (\Throwable) {
        }
        return $this->aiAgentResponse($storeId, ['message' => 'أحتاج حالة طلبي']);
    }

    public function handover(int $storeId, array $data): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_handover_logs (store_id, chatbot_session_id, conversation_id, assigned_to, reason, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$storeId, $data['chatbot_session_id'] ?? null, $data['conversation_id'] ?? null, $data['assigned_to'] ?? null, $data['reason'] ?? 'Manual handover', 'queued']);
        if (!empty($data['chatbot_session_id'])) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'human_handover', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([(int) $data['chatbot_session_id'], $storeId]);
        }
        return (int) Database::pdo()->lastInsertId();
    }

    public function resume(int $storeId, array $data): void
    {
        if (!empty($data['chatbot_session_id'])) {
            Database::pdo()->prepare("UPDATE chatbot_sessions SET status = 'bot_active', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([(int) $data['chatbot_session_id'], $storeId]);
        }
    }

    public function processWebhook(int $storeId, array $payload): array
    {
        $message = (string) ($payload['message']['body'] ?? $payload['body'] ?? '');
        $source = Validator::enum((string) ($payload['connection_source'] ?? $payload['channel'] ?? 'meta_cloud_api'), self::CONNECTION_SOURCES, 'invalid_connection_source');
        try {
            $keyword = $this->matchKeyword($storeId, $message, $source);
            if ($keyword) {
                $this->analytics($storeId, 'keyword_matched', (int) ($keyword['flow_id'] ?? 0) ?: null, $source, $keyword['keyword'], 'matched', $payload);
                return ['action' => $keyword['action_type'], 'reply' => $keyword['reply_text'], 'flow_id' => $keyword['flow_id']];
            }
            $auto = $this->firstAutoReply($storeId, $source);
            if ($auto) {
                $this->analytics($storeId, 'auto_reply_sent', null, $source, null, 'sent', $payload);
                return ['action' => 'reply', 'reply' => $auto['message']];
            }
        } catch (\Throwable) {
            // Keep inbound webhooks non-blocking while migrations are being applied.
        }
        $ai = $this->aiReply($storeId, ['message' => $message, 'connection_source' => $source]);
        return ['action' => 'ai_reply', 'reply' => $ai['reply'], 'confidence' => $ai['confidence']];
    }

    public function overview(int $storeId): array
    {
        try {
            $pdo = Database::pdo();
            return [
                'auto_replies' => (int) $pdo->query('SELECT COUNT(*) FROM chatbot_auto_replies WHERE store_id = ' . $storeId)->fetchColumn(),
                'active_flows' => (int) $pdo->query("SELECT COUNT(*) FROM chatbot_flows WHERE store_id = {$storeId} AND status = 'active'")->fetchColumn(),
                'handover_count' => (int) $pdo->query('SELECT COUNT(*) FROM chatbot_handover_logs WHERE store_id = ' . $storeId)->fetchColumn(),
                'top_keywords' => $pdo->query('SELECT keyword, COUNT(*) total FROM chatbot_analytics WHERE store_id = ' . $storeId . " AND keyword IS NOT NULL GROUP BY keyword ORDER BY total DESC LIMIT 5")->fetchAll(),
            ];
        } catch (\Throwable) {
            return ['auto_replies' => 0, 'active_flows' => 0, 'handover_count' => 0, 'top_keywords' => []];
        }
    }

    private function saveFlowGraph(int $flowId, array $nodes, array $edges): void
    {
        foreach ($nodes as $node) {
            $config = $node['config'] ?? [];
            $stmt = Database::pdo()->prepare('INSERT INTO chatbot_nodes (flow_id, node_key, node_type, title, message, options_json, department_id, config_json, position_x, position_y, settings_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $flowId,
                $node['node_key'] ?? uniqid('node_'),
                $this->normalizeNodeType((string) ($node['node_type'] ?? $node['type'] ?? 'message')),
                $node['title'] ?? null,
                $node['message'] ?? ($config['text'] ?? null),
                json_encode($node['options'] ?? ($config['options'] ?? []), JSON_UNESCAPED_UNICODE),
                $node['department_id'] ?? null,
                json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (int) ($node['position_x'] ?? $node['x'] ?? 0),
                (int) ($node['position_y'] ?? $node['y'] ?? 0),
                json_encode($node['settings'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
        foreach ($edges as $edge) {
            $stmt = Database::pdo()->prepare('INSERT INTO chatbot_edges (flow_id, source_node_key, target_node_key, condition_json, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $flowId,
                $edge['source'] ?? $edge['source_node_key'] ?? '',
                $edge['target'] ?? $edge['target_node_key'] ?? '',
                json_encode($edge['condition'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    private function flowWithGraph(array $flow): array
    {
        $nodes = Database::pdo()->prepare('SELECT * FROM chatbot_nodes WHERE flow_id = ? ORDER BY id ASC');
        $nodes->execute([(int) $flow['id']]);
        $edges = Database::pdo()->prepare('SELECT * FROM chatbot_edges WHERE flow_id = ? ORDER BY id ASC');
        $edges->execute([(int) $flow['id']]);

        $flow['nodes'] = array_map(function (array $node): array {
            $node['options'] = json_decode((string) ($node['options_json'] ?? '[]'), true) ?: [];
            $node['config'] = json_decode((string) ($node['config_json'] ?? '{}'), true) ?: [];
            $node['settings'] = json_decode((string) ($node['settings_json'] ?? '{}'), true) ?: [];
            unset($node['options_json'], $node['config_json'], $node['settings_json']);
            return $node;
        }, $nodes->fetchAll());

        $flow['edges'] = array_map(function (array $edge): array {
            $edge['condition'] = json_decode((string) ($edge['condition_json'] ?? '{}'), true) ?: [];
            unset($edge['condition_json']);
            return $edge;
        }, $edges->fetchAll());

        return $flow;
    }

    private function defaultNodes(): array
    {
        return [
            ['node_key' => 'start', 'node_type' => 'message', 'title' => 'رسالة البداية', 'config' => ['text' => 'مرحباً، كيف نقدر نساعدك؟'], 'position_x' => 80, 'position_y' => 120],
            ['node_key' => 'ai_reply', 'node_type' => 'ai_reply', 'title' => 'رد ذكي', 'config' => ['use_knowledge_base' => true], 'position_x' => 360, 'position_y' => 120],
        ];
    }

    private function normalizeNodeType(string $type): string
    {
        return match ($type) {
            'start', 'list', 'buttons', 'button', 'text', 'department_reply' => 'message',
            'handover' => 'human_handover',
            'api' => 'api_request',
            'ai', 'aiReply' => 'ai_reply',
            default => in_array($type, ['message', 'question', 'condition', 'delay', 'ai_reply', 'human_handover', 'api_request', 'tag', 'campaign', 'end'], true) ? $type : 'message',
        };
    }

    private function normalizeFlowStatus(string $status): string
    {
        return in_array($status, ['draft', 'active', 'paused'], true) ? $status : 'draft';
    }

    private function defaultDepartmentFlow(): array
    {
        return [
            'name' => 'مسار واتساب الافتراضي للأقسام',
            'status' => 'active',
            'nodes' => [
                ['key' => 'start', 'type' => 'start', 'title' => 'بداية المسار', 'x' => 900, 'y' => 80, 'message' => 'بداية المحادثة'],
                ['key' => 'welcome', 'type' => 'message', 'title' => 'رسالة الترحيب', 'x' => 640, 'y' => 80, 'message' => "أهلاً بك 👋\nكيف يمكننا مساعدتك اليوم؟"],
                ['key' => 'departments', 'type' => 'list', 'title' => 'اختيار القسم', 'x' => 380, 'y' => 80, 'message' => 'اختر القسم المناسب', 'options' => ['المبيعات', 'الدعم الفني', 'الطلبات والشحن', 'الحسابات والفواتير', 'الشكاوى', 'التحدث مع موظف']],
                ['key' => 'sales', 'type' => 'department_reply', 'title' => 'المبيعات', 'x' => 120, 'y' => -80, 'message' => 'يسعدنا مساعدتك في المبيعات. هل تريد معرفة الأسعار أم العروض أم التحدث مع مستشار مبيعات؟', 'options' => ['الأسعار', 'العروض', 'مستشار مبيعات']],
                ['key' => 'support', 'type' => 'department_reply', 'title' => 'الدعم الفني', 'x' => 120, 'y' => 80, 'message' => 'من فضلك اختر نوع المشكلة التي تواجهك.', 'options' => ['مشكلة في الطلب', 'مشكلة في الدفع', 'مشكلة في الحساب', 'تحويل للدعم الفني']],
                ['key' => 'orders', 'type' => 'question', 'title' => 'الطلبات والشحن', 'x' => 120, 'y' => 240, 'message' => 'يمكنك متابعة طلبك من هنا. من فضلك أرسل رقم الطلب.', 'save_as' => 'order_number'],
                ['key' => 'billing', 'type' => 'department_reply', 'title' => 'الحسابات والفواتير', 'x' => 120, 'y' => 400, 'message' => 'اختر الخدمة المطلوبة.', 'options' => ['طلب فاتورة', 'مشكلة دفع', 'مراجعة حساب']],
                ['key' => 'complaints', 'type' => 'human_handover', 'title' => 'الشكاوى', 'x' => 120, 'y' => 560, 'message' => 'نأسف لسماع ذلك. من فضلك اكتب تفاصيل الشكوى وسيتم تحويلها للقسم المختص.'],
                ['key' => 'handover', 'type' => 'human_handover', 'title' => 'التحدث مع موظف', 'x' => 120, 'y' => 720, 'message' => 'سيتم تحويلك الآن إلى أحد ممثلي خدمة العملاء.'],
                ['key' => 'end', 'type' => 'end', 'title' => 'نهاية المسار', 'x' => -130, 'y' => 320, 'message' => 'تم إنهاء المسار'],
            ],
            'edges' => [
                ['from' => 'start', 'to' => 'welcome'],
                ['from' => 'welcome', 'to' => 'departments'],
                ['from' => 'departments', 'to' => 'sales', 'label' => 'المبيعات'],
                ['from' => 'departments', 'to' => 'support', 'label' => 'الدعم الفني'],
                ['from' => 'departments', 'to' => 'orders', 'label' => 'الطلبات والشحن'],
                ['from' => 'departments', 'to' => 'billing', 'label' => 'الحسابات والفواتير'],
                ['from' => 'departments', 'to' => 'complaints', 'label' => 'الشكاوى'],
                ['from' => 'departments', 'to' => 'handover', 'label' => 'التحدث مع موظف'],
            ],
        ];
    }

    private function departmentForMessage(string $message): ?array
    {
        if ($this->messageHasAny($message, ['المبيعات', 'بيع', 'شراء', 'السعر', 'الأسعار', 'العروض', 'مستشار مبيعات', 'sales', 'price', 'offer', 'Ø§Ù„Ù…Ø¨ÙŠØ¹Ø§Øª', 'Ø³Ø¹Ø±', 'Ø¹Ø±ÙˆØ¶'])) {
            return self::DEPARTMENTS[0];
        }
        if ($this->messageHasAny($message, ['الدعم', 'الدعم الفني', 'مشكلة', 'عطل', 'لا يعمل', 'support', 'issue', 'problem', 'Ø§Ù„Ø¯Ø¹Ù…', 'Ù…Ø´ÙƒÙ„Ø©'])) {
            return self::DEPARTMENTS[1];
        }
        if ($this->messageHasAny($message, ['الطلبات', 'الشحن', 'تتبع', 'رقم الطلب', 'متابعة طلب', 'order', 'shipping', 'tracking', 'delivery', 'Ø§Ù„Ø·Ù„Ø¨Ø§Øª', 'Ø§Ù„Ø´Ø­Ù†'])) {
            return self::DEPARTMENTS[2];
        }
        if ($this->messageHasAny($message, ['الفواتير', 'الحسابات', 'فاتورة', 'دفع', 'الدفع', 'billing', 'invoice', 'payment', 'Ø§Ù„ÙÙˆØ§ØªÙŠØ±', 'Ø§Ù„Ø­Ø³Ø§Ø¨Ø§Øª'])) {
            return self::DEPARTMENTS[3];
        }
        if ($this->messageHasAny($message, ['الشكاوى', 'شكوى', 'سيء', 'زعلان', 'غاضب', 'استرجاع', 'ارجاع', 'complaint', 'refund', 'bad', 'Ø§Ù„Ø´ÙƒØ§ÙˆÙ‰', 'Ø´ÙƒÙˆÙ‰'])) {
            return self::DEPARTMENTS[4];
        }
        if ($this->messageHasAny($message, ['التحدث مع موظف', 'موظف', 'إنسان', 'انسان', 'مشرف', 'خدمة العملاء', 'agent', 'human', 'representative', 'Ù…ÙˆØ¸Ù'])) {
            return ['slug' => 'human', 'welcome_message' => 'سيتم تحويلك الآن إلى أحد ممثلي خدمة العملاء.', 'auto_tag' => 'human_handover'];
        }
        return null;
    }

    private function messageHasAny(string $message, array $keywords): bool
    {
        $haystacks = array_unique(array_filter([
            mb_strtolower($message),
            $this->repairMojibake(mb_strtolower($message)),
        ]));

        foreach ($haystacks as $haystack) {
            foreach ($keywords as $keyword) {
                $needle = mb_strtolower((string) $keyword);
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function repairMojibake(string $value): string
    {
        if (!function_exists('iconv') || (!str_contains($value, 'Ø') && !str_contains($value, 'Ù'))) {
            return $value;
        }

        $bytes = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if ($bytes === false || $bytes === '') {
            return $value;
        }

        $decoded = @iconv('UTF-8', 'UTF-8//IGNORE', $bytes);
        return is_string($decoded) && $decoded !== '' ? mb_strtolower($decoded) : $value;
    }

    private function queueName(string $slug): string
    {
        return match ($slug) {
            'sales' => 'طابور المبيعات',
            'support' => 'طابور الدعم الفني',
            'orders' => 'طابور الطلبات',
            'billing' => 'طابور الحسابات',
            'complaints' => 'طابور الشكاوى',
            default => 'طابور الدعم الفني',
        };
    }

    private function departmentButtons(string $slug): array
    {
        return match ($slug) {
            'sales' => ['الأسعار', 'العروض', 'مستشار مبيعات'],
            'support' => ['مشكلة في الطلب', 'مشكلة في الدفع', 'مشكلة في الحساب', 'تحويل للدعم الفني'],
            'billing' => ['طلب فاتورة', 'مشكلة دفع', 'مراجعة حساب'],
            default => [],
        };
    }

    private function ensureBotContact(int $storeId, string $phone): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO contacts (store_id, phone, opt_in_status, source, last_contact_at, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW(), NOW()) ON DUPLICATE KEY UPDATE last_contact_at = NOW(), updated_at = NOW()');
        $stmt->execute([$storeId, $phone, 'unknown', 'chatbot_runtime']);
        $lookup = Database::pdo()->prepare('SELECT id FROM contacts WHERE store_id = ? AND phone = ? LIMIT 1');
        $lookup->execute([$storeId, $phone]);
        return (int) $lookup->fetchColumn();
    }

    private function ensureBotConversation(int $storeId, int $contactId): int
    {
        $stmt = Database::pdo()->prepare("SELECT id FROM conversations WHERE store_id = ? AND contact_id = ? AND status <> 'closed' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$storeId, $contactId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        Database::pdo()->prepare('INSERT INTO conversations (store_id, contact_id, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())')->execute([$storeId, $contactId, 'open']);
        return (int) Database::pdo()->lastInsertId();
    }

    private function sessionForConversation(int $storeId, int $conversationId, int $contactId, string $source): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM chatbot_sessions WHERE store_id = ? AND conversation_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$storeId, $conversationId]);
        $session = $stmt->fetch();
        if ($session) {
            return $session;
        }
        $normalizedSource = in_array($source, ['whatsapp_qr', 'qr_web_session'], true) ? 'qr_web_session' : 'meta_cloud_api';
        $insert = Database::pdo()->prepare("INSERT INTO chatbot_sessions (store_id, contact_id, conversation_id, connection_source, current_node_key, status, context_json, last_interaction_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'bot_active', ?, NOW(), NOW(), NOW())");
        $insert->execute([$storeId, $contactId, $conversationId, $normalizedSource, 'start', json_encode([])]);
        $this->logBotEvent($storeId, $conversationId, (int) Database::pdo()->lastInsertId(), 'conversation_opened', ['source' => $source]);
        $stmt->execute([$storeId, $conversationId]);
        return $stmt->fetch() ?: [];
    }

    private function advanceSession(int $sessionId, string $nodeKey, array $context): void
    {
        Database::pdo()->prepare('UPDATE chatbot_sessions SET current_node_key = ?, context_json = ?, last_interaction_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$nodeKey, json_encode($context, JSON_UNESCAPED_UNICODE), $sessionId]);
    }

    private function updateSessionDepartment(int $sessionId, int $departmentId, bool $handover): void
    {
        try {
            Database::pdo()->prepare('UPDATE chatbot_sessions SET selected_department_id = ?, bot_paused = ?, status = ?, current_node_key = ?, last_interaction_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([
                $departmentId,
                $handover ? 1 : 0,
                $handover ? 'human_handover' : 'bot_active',
                $handover ? 'handover' : 'department_reply',
                $sessionId,
            ]);
        } catch (\Throwable) {
            Database::pdo()->prepare('UPDATE chatbot_sessions SET status = ?, current_node_key = ?, last_interaction_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([
                $handover ? 'human_handover' : 'bot_active',
                $handover ? 'handover' : 'department_reply',
                $sessionId,
            ]);
        }
    }

    private function ensureDepartment(int $storeId, array $department): int
    {
        $slug = (string) ($department['slug'] ?? 'support');
        try {
            $lookup = Database::pdo()->prepare('SELECT id FROM departments WHERE store_id = ? AND slug = ? LIMIT 1');
            $lookup->execute([$storeId, $slug]);
            $id = $lookup->fetchColumn();
            if ($id) {
                return (int) $id;
            }
            return $this->saveDepartment($storeId, [
                'name' => $department['name'] ?? $slug,
                'slug' => $slug,
                'color' => $department['color'] ?? '#2fbf71',
                'welcome_message' => $department['welcome_message'] ?? '',
                'away_message' => 'نحن خارج أوقات العمل حالياً، سيتم الرد عليك في أقرب وقت.',
                'working_hours' => '09:00-18:00',
                'priority' => $department['priority'] ?? 'normal',
                'is_active' => 1,
                'auto_tag' => $department['auto_tag'] ?? $slug,
            ]);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function departmentBySlug(string $slug): ?array
    {
        foreach (self::DEPARTMENTS as $department) {
            if ($department['slug'] === $slug || $department['name'] === $slug) {
                return $department;
            }
        }
        return null;
    }

    private function assignConversation(int $storeId, int $conversationId, int $departmentId, string $departmentSlug, bool $pauseBot): void
    {
        try {
            Database::pdo()->prepare('INSERT INTO conversation_assignments (conversation_id, department_id, status, assigned_at) VALUES (?, ?, ?, NOW())')->execute([$conversationId, $departmentId ?: null, 'queued']);
        } catch (\Throwable) {
            // Assignment table may not be migrated yet.
        }
        $notes = 'تم تحويل المحادثة إلى ' . $this->queueName($departmentSlug);
        try {
            Database::pdo()->prepare('UPDATE conversations SET notes = CONCAT(COALESCE(notes, ""), ?), updated_at = NOW() WHERE id = ? AND store_id = ?')->execute(["\n" . $notes, $conversationId, $storeId]);
        } catch (\Throwable) {
        }
        AuditLogger::record('chatbot.assignment_created', $storeId, null, 'conversation', $conversationId, ['department' => $departmentSlug, 'bot_paused' => $pauseBot]);
    }

    private function tagConversation(int $conversationId, string $tag, string $department): void
    {
        try {
            Database::pdo()->prepare('UPDATE conversations SET tags_json = ?, updated_at = NOW() WHERE id = ?')->execute([json_encode(['department:' . $department, $tag], JSON_UNESCAPED_UNICODE), $conversationId]);
        } catch (\Throwable) {
        }
    }

    private function dispatchBotReply(int $storeId, int $conversationId, string $customerPhone, string $body, string $source): array
    {
        $providerMessageId = 'bot_' . sha1($conversationId . $body . microtime(true));
        $status = 'queued';
        $result = [];
        try {
            if (in_array($source, ['qr_web_session', 'whatsapp_qr'], true)) {
                $result = (new WhatsAppQrService())->sendMessage($storeId, $customerPhone, $body);
                $providerMessageId = $result['messageId'] ?? $providerMessageId;
                $status = $result['status'] ?? 'sent';
            } else {
                $connection = new ConnectionService();
                $phone = $connection->primaryPhone($storeId);
                $result = (new WhatsAppCloudApiService())->sendText($phone['phone_number_id'], $connection->accessToken($storeId), Validator::phone($customerPhone), $body);
                $providerMessageId = $result['messages'][0]['id'] ?? $providerMessageId;
                $status = 'sent';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $result = ['error' => $e->getMessage()];
            error_log('Chatbot reply dispatch failed: ' . $e->getMessage());
        }

        try {
            Database::pdo()->prepare('INSERT INTO messages (conversation_id, direction, provider_message_id, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), payload = VALUES(payload)')->execute([
                $conversationId,
                'outbound',
                $providerMessageId,
                $body,
                $status,
                json_encode(['bot' => true, 'source' => $source, 'result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            Database::pdo()->prepare('UPDATE conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$conversationId]);
        } catch (\Throwable) {
        }

        return ['status' => $status, 'provider_message_id' => $providerMessageId, 'provider_result' => $result];
    }

    private function logBotEvent(int $storeId, int $conversationId, ?int $sessionId, string $eventType, array $payload): void
    {
        try {
            Database::pdo()->prepare('INSERT INTO chatbot_event_logs (store_id, conversation_id, chatbot_session_id, event_type, payload_json, created_at) VALUES (?, ?, ?, ?, ?, NOW())')->execute([
                $storeId,
                $conversationId,
                $sessionId,
                $eventType,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable) {
            AuditLogger::record('chatbot.' . $eventType, $storeId, null, 'conversation', $conversationId, $payload);
        }
    }

    private function aiAgentResponse(int $storeId, array $data): array
    {
        $message = trim((string) ($data['message'] ?? ''));
        $classification = $this->classifyMessage($message);
        $settings = $this->aiSettings($storeId);
        $kb = [];
        try {
            $kb = $this->searchKnowledge($storeId, $message, $classification['intent']);
        } catch (\Throwable) {
            $kb = [];
        }

        $confidence = $kb ? min(0.95, 0.68 + (count($kb) * 0.08)) : 0.22;
        $minConfidence = (float) ($settings['min_confidence'] ?? 0.7);
        $enabled = (bool) ($settings['enabled'] ?? true);
        $source = $data['connection_source'] ?? null;

        $reply = null;
        $needsHuman = !$enabled || !$kb || $confidence < $minConfidence || $classification['needs_human'];
        if (!$needsHuman) {
            $reply = $this->composeKnowledgeReply($message, $kb, $classification, $settings);
        } else {
            $reply = 'حتى لا أقدم معلومة غير مؤكدة، سيتم تحويلك لموظف مختص لمساعدتك بدقة.';
        }

        $result = array_merge($classification, [
            'action' => $needsHuman ? 'handover' : 'reply',
            'reply' => $reply,
            'confidence' => round($confidence, 2),
            'needs_human' => $needsHuman,
            'department' => $classification['department'],
            'knowledge_items' => array_map(static fn (array $item): array => [
                'id' => $item['id'] ?? null,
                'title' => $item['title'] ?? '',
                'category' => $item['category'] ?? '',
            ], $kb),
            'summary' => $this->summarizeForAgent($message, $classification, $kb),
            'suggested_replies' => $this->suggestReplies($classification, $kb),
            'recommended_next_action' => $needsHuman ? 'تحويل تلقائي إلى ' . $this->queueName((string) $classification['department']) : 'إرسال الرد المقترح من قاعدة المعرفة',
            'language' => preg_match('/[\x{0600}-\x{06FF}]/u', $message) ? 'ar' : 'en',
        ]);

        try {
            $this->analytics($storeId, 'ai_agent_analyzed', null, is_string($source) ? $source : null, null, $needsHuman ? 'handover' : 'reply', $result);
        } catch (\Throwable) {
        }

        return $result;
    }

    private function defaultAiSettings(): array
    {
        return [
            'enabled' => true,
            'provider' => 'internal_knowledge_agent',
            'model' => 'knowledge-only',
            'tone' => 'مهني ودود',
            'language' => 'auto',
            'reply_length' => 'short',
            'daily_limit' => 500,
            'forbidden_topics' => '',
            'handover_after_minutes' => 10,
            'min_confidence' => 0.7,
            'strict_knowledge_only' => true,
            'system_prompt' => 'أجب فقط من قاعدة المعرفة. عند عدم التأكد حول المحادثة لموظف.',
        ];
    }

    private function classifyMessage(string $message): array
    {
        $normalized = mb_strtolower($message);
        $intent = 'support';
        $department = 'support';
        $priority = 'normal';
        $sentiment = 'neutral';

        if ($this->messageHasAny($normalized, ['شراء', 'اشتري', 'اطلب', 'سعر', 'اسعار', 'أسعار', 'عرض', 'عروض', 'منتج', 'منتجات', 'price', 'buy', 'offer', 'product'])) {
            $intent = 'purchase';
            $department = 'sales';
            $priority = 'high';
        }
        if ($this->messageHasAny($normalized, ['طلب', 'الطلب', 'الشحن', 'شحن', 'تتبع', 'توصيل', 'track', 'order', 'tracking', 'delivery'])) {
            $intent = 'order_status';
            $department = 'orders';
        }
        if ($this->messageHasAny($normalized, ['فاتورة', 'فواتير', 'invoice', 'billing', 'دفع', 'الدفع', 'payment', 'مدفوعات'])) {
            $intent = 'invoice';
            $department = 'billing';
        }
        if ($this->messageHasAny($normalized, ['شكوى', 'سيء', 'زعلان', 'غاضب', 'complaint', 'bad', 'refund', 'استرجاع', 'ارجاع', 'إرجاع'])) {
            $intent = 'complaint';
            $department = 'complaints';
            $priority = 'urgent';
            $sentiment = 'negative';
        }
        if ($this->messageHasAny($normalized, ['مشكلة', 'عطل', 'لا يعمل', 'دعم', 'الدعم', 'support', 'issue', 'problem'])) {
            if ($intent === 'support') {
                $department = 'support';
            }
            $priority = $priority === 'normal' ? 'high' : $priority;
        }

        $needsHuman = $this->messageHasAny($normalized, ['موظف', 'إنسان', 'انسان', 'مشرف', 'agent', 'human', 'representative']);
        $leadScore = match ($intent) {
            'purchase' => 86,
            'order_status' => 58,
            'invoice' => 54,
            'complaint' => 38,
            default => 50,
        };

        return [
            'intent' => $intent,
            'sentiment' => $sentiment,
            'priority' => $priority,
            'department' => $department,
            'needs_human' => $needsHuman,
            'lead_score' => $leadScore,
        ];
    }

    private function composeKnowledgeReply(string $message, array $kb, array $classification, array $settings): string
    {
        $answer = trim((string) ($kb[0]['answer'] ?? ''));
        if ($answer === '') {
            return 'سيتم تحويلك لموظف مختص لمساعدتك بدقة.';
        }
        if (($classification['intent'] ?? '') === 'order_status' && preg_match('/\b\d{4,}\b/u', $message, $m)) {
            return $answer . "\n\nرقم الطلب المستلم: " . $m[0] . "\nإذا لم تكن هذه المعلومة كافية سيتم تحويلك لفريق الطلبات.";
        }
        if (($classification['intent'] ?? '') === 'purchase' && count($kb) > 1) {
            $products = array_slice(array_column($kb, 'title'), 0, 3);
            return $answer . "\n\nاقتراحات مناسبة: " . implode('، ', $products);
        }
        return $answer;
    }

    private function summarizeForAgent(string $message, array $classification, array $kb): string
    {
        $source = $kb ? 'تم العثور على عناصر معرفة مرتبطة: ' . implode('، ', array_slice(array_column($kb, 'title'), 0, 3)) : 'لا توجد معلومة موثوقة في قاعدة المعرفة.';
        return 'ملخص AI: نية العميل ' . $classification['intent'] . '، الأولوية ' . $classification['priority'] . '. رسالة العميل: "' . mb_substr($message, 0, 160) . '". ' . $source;
    }

    private function suggestReplies(array $classification, array $kb): array
    {
        if ($kb) {
            return [
                (string) ($kb[0]['answer'] ?? ''),
                'هل ترغب أن أوضح لك التفاصيل أو أحولك لموظف مختص؟',
                'تم تسجيل طلبك وسيتابع معك الفريق المختص.',
            ];
        }
        return [
            'سأحولك لموظف مختص للتأكد من التفاصيل.',
            'من فضلك أرسل رقم الطلب أو تفاصيل إضافية.',
            'تم استلام رسالتك وسيتم الرد عليك قريباً.',
        ];
    }

    private function saveConversationAiInsight(int $storeId, int $conversationId, array $insight): void
    {
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO chatbot_ai_conversation_insights (store_id, conversation_id, intent, sentiment, priority, lead_score, summary, suggested_replies_json, recommended_next_action, raw_payload_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $storeId,
                $conversationId,
                $insight['intent'] ?? 'support',
                $insight['sentiment'] ?? 'neutral',
                $insight['priority'] ?? 'normal',
                (int) ($insight['lead_score'] ?? 0),
                $insight['summary'] ?? null,
                json_encode($insight['suggested_replies'] ?? [], JSON_UNESCAPED_UNICODE),
                $insight['recommended_next_action'] ?? null,
                json_encode($insight, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable) {
        }
    }

    private function normalizeTags(string $tags): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,،]/u', $tags) ?: [])));
    }

    private function matchKeyword(int $storeId, string $message, string $source): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM chatbot_keywords WHERE store_id = ? AND status = 'active' AND connection_source IN (?, 'both', 'all_channels')");
        $stmt->execute([$storeId, $source]);
        foreach ($stmt->fetchAll() as $keyword) {
            $needle = (string) $keyword['keyword'];
            $matched = match ($keyword['match_type']) {
                'equals' => trim($message) === $needle,
                'starts_with' => str_starts_with($message, $needle),
                'regex' => @preg_match('/' . $needle . '/u', $message) === 1,
                default => str_contains(mb_strtolower($message), mb_strtolower($needle)),
            };
            if ($matched) {
                return $keyword;
            }
        }
        return null;
    }

    private function firstAutoReply(int $storeId, string $source): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM chatbot_auto_replies WHERE store_id = ? AND status = 'active' AND connection_source IN (?, 'both', 'all_channels') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$storeId, $source]);
        $reply = $stmt->fetch();
        return $reply ?: null;
    }

    private function searchKnowledge(int $storeId, string $query, ?string $intent = null): array
    {
        $category = match ($intent) {
            'purchase' => ['products', 'offers', 'sales', 'faq'],
            'order_status' => ['orders', 'shipping', 'faq'],
            'invoice' => ['payment', 'billing', 'faq'],
            'complaint' => ['returns', 'refunds', 'support', 'faq'],
            default => ['faq', 'support', 'policies'],
        };
        $words = array_values(array_filter(preg_split('/\s+/u', trim($query)) ?: [], static fn (string $word): bool => mb_strlen($word) > 2));
        $like = '%' . ($words[0] ?? $query) . '%';
        $placeholders = implode(',', array_fill(0, count($category), '?'));
        $stmt = Database::pdo()->prepare("SELECT * FROM chatbot_knowledge_base WHERE store_id = ? AND status = 'active' AND ((title LIKE ? OR question LIKE ? OR answer LIKE ? OR category IN ({$placeholders})) OR tags_json LIKE ?) ORDER BY FIELD(category, {$placeholders}) DESC, id DESC LIMIT 5");
        $stmt->execute(array_merge([$storeId, $like, $like, $like], $category, [$like], $category));
        return $stmt->fetchAll();
    }

    private function analytics(int $storeId, string $eventType, ?int $flowId, ?string $source, ?string $keyword, ?string $status, array $payload): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO chatbot_analytics (store_id, flow_id, event_type, connection_source, keyword, status, payload_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$storeId, $flowId, $eventType, $source, $keyword, $status, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    private function countRows(string $sql, array $params): int
    {
        try {
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
