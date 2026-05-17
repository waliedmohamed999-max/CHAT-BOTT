<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Rbac;

final class AiCommerceOsService
{
    public function overview(int $storeId): array
    {
        return [
            'agents' => $this->agents($storeId),
            'memory' => $this->memoryOverview($storeId),
            'decision_engine' => $this->decisionEngine($storeId),
            'command_center' => $this->commandCenter($storeId),
            'commerce_os' => $this->commerceModules(),
            'generated_experiences' => $this->generatedExperiences($storeId),
            'voice_ai' => $this->voiceAi(),
            'marketplace' => $this->aiMarketplace(),
            'infrastructure' => $this->aiInfrastructure(),
            'self_improving' => $this->selfImprovingSystem(),
            'future_ready' => $this->futureReady(),
            'readiness_score' => $this->readinessScore($storeId),
        ];
    }

    public function agents(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM ai_os_agents WHERE store_id = ? ORDER BY id ASC');
            $stmt->execute([$storeId]);
            $agents = $stmt->fetchAll();
            if ($agents) {
                return array_map(fn (array $agent): array => $this->decodeAgent($agent), $agents);
            }
        } catch (\Throwable) {
        }

        return $this->seedAgents();
    }

    public function activateAgent(int $storeId, string $agentKey, array $input = []): array
    {
        $agent = null;
        foreach ($this->seedAgents() as $seed) {
            if ($seed['agent_key'] === $agentKey) {
                $agent = $seed;
                break;
            }
        }
        if (!$agent) {
            return ['activated' => false, 'message' => 'Agent غير موجود'];
        }

        try {
            $stmt = Database::pdo()->prepare("INSERT INTO ai_os_agents (store_id, agent_key, name, role, status, goals_json, permissions_json, workflow_json, memory_scope, report_frequency, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE status = 'active', goals_json = VALUES(goals_json), permissions_json = VALUES(permissions_json), workflow_json = VALUES(workflow_json), updated_at = NOW()");
            $stmt->execute([
                $storeId,
                $agent['agent_key'],
                $agent['name'],
                $agent['role'],
                json_encode($input['goals'] ?? $agent['goals'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($input['permissions'] ?? $agent['permissions'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($input['workflow'] ?? $agent['workflow'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $agent['memory_scope'],
                $input['report_frequency'] ?? $agent['report_frequency'],
            ]);
            AuditLogger::record('ai_commerce_os.agent_activated', $storeId, Rbac::userId(), 'ai_os_agent', null, ['agent_key' => $agentKey]);
            return ['activated' => true, 'agent_key' => $agentKey];
        } catch (\Throwable) {
            return ['activated' => false, 'message' => 'جداول AI Commerce OS غير مفعلة بعد'];
        }
    }

    public function memoryOverview(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT memory_type, COUNT(*) total FROM ai_os_memory WHERE store_id = ? GROUP BY memory_type');
            $stmt->execute([$storeId]);
            $counts = [];
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['memory_type']] = (int) $row['total'];
            }
        } catch (\Throwable) {
            $counts = [];
        }

        return [
            'long_term_memory' => $counts['long_term'] ?? 0,
            'conversation_memory' => $counts['conversation'] ?? 0,
            'customer_memory' => $counts['customer'] ?? 0,
            'business_context_memory' => $counts['business_context'] ?? 0,
            'vector_database' => Env::get('VECTOR_DATABASE_URL') ? 'configured' : 'planned',
            'knowledge_graph' => Env::get('KNOWLEDGE_GRAPH_URL') ? 'configured' : 'planned',
            'isolation' => 'tenant_scoped_memory',
        ];
    }

    public function storeMemory(int $storeId, array $input): array
    {
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO ai_os_memory (store_id, agent_key, memory_type, subject_type, subject_id, content, importance_score, metadata_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $storeId,
                $input['agent_key'] ?? null,
                $input['memory_type'] ?? 'business_context',
                $input['subject_type'] ?? null,
                $input['subject_id'] ?? null,
                (string) ($input['content'] ?? ''),
                (int) ($input['importance_score'] ?? 50),
                json_encode($input['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return ['stored' => true, 'id' => (int) Database::pdo()->lastInsertId()];
        } catch (\Throwable) {
            return ['stored' => false, 'message' => 'تعذر حفظ الذاكرة قبل تفعيل جداول AI Commerce OS'];
        }
    }

    public function decisionEngine(int $storeId): array
    {
        $decisions = [
            ['type' => 'marketing', 'title' => 'إطلاق حملة استرجاع العملاء غير النشطين', 'impact' => 'زيادة متوقعة في الإيراد 8-14%', 'confidence' => 82, 'action' => 'generate_winback_campaign', 'requires_approval' => true],
            ['type' => 'sales', 'title' => 'رفع أولوية العملاء ذوي نية الشراء العالية', 'impact' => 'تحسين سرعة تحويل الفرص', 'confidence' => 88, 'action' => 'prioritize_hot_leads', 'requires_approval' => true],
            ['type' => 'support', 'title' => 'تصعيد المحادثات ذات Sentiment سلبي', 'impact' => 'تقليل مخاطر الشكاوى', 'confidence' => 90, 'action' => 'escalate_negative_conversations', 'requires_approval' => true],
            ['type' => 'campaign', 'title' => 'إيقاف الحملات ذات قراءة أقل من 15%', 'impact' => 'حماية جودة رقم واتساب', 'confidence' => 79, 'action' => 'pause_weak_campaigns', 'requires_approval' => true],
        ];
        $this->storeDecisionLogs($storeId, $decisions);

        return [
            'mode' => 'human_review_required',
            'decisions' => $decisions,
            'guardrails' => ['no_auto_send_without_opt_in', 'no_campaign_launch_without_template_approval', 'no_cross_tenant_memory', 'all_actions_reviewable'],
        ];
    }

    public function generateExperience(int $storeId, array $input): array
    {
        $type = (string) ($input['type'] ?? 'campaign');
        $prompt = (string) ($input['prompt'] ?? 'زيادة المبيعات عبر واتساب');
        $experience = match ($type) {
            'flow' => [
                'title' => 'Flow ذكي: ' . $prompt,
                'steps' => ['Trigger', 'Condition', 'AI Reply', 'WhatsApp Message', 'Human Approval', 'Stop Condition'],
                'channels' => ['whatsapp_cloud', 'whatsapp_qr', 'email'],
            ],
            'reply' => [
                'title' => 'رد ذكي قابل للمراجعة',
                'body' => 'أفهم طلبك. سأراجع بيانات الطلب والمنتجات المتاحة ثم أوصلك بأفضل خيار مناسب.',
                'rules' => ['استخدم قاعدة المعرفة فقط', 'حوّل لموظف عند عدم التأكد'],
            ],
            'page' => [
                'title' => 'صفحة عرض AI Generated',
                'sections' => ['Hero', 'Offer', 'Social Proof', 'FAQ', 'WhatsApp CTA'],
            ],
            'report' => [
                'title' => 'Executive AI Report',
                'sections' => ['Revenue', 'Risks', 'Opportunities', 'Agent Performance', 'Next Actions'],
            ],
            default => [
                'title' => 'حملة AI Generated',
                'message' => 'عرض مخصص للعملاء ذوي احتمالية شراء مرتفعة مع CTA واحد.',
                'segment' => 'high_purchase_probability_opt_in',
                'schedule' => '19:30 - 21:00',
            ],
        };

        try {
            $stmt = Database::pdo()->prepare("INSERT INTO ai_generated_experiences (store_id, experience_type, title, prompt, output_json, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'draft', ?, NOW(), NOW())");
            $stmt->execute([
                $storeId,
                $type,
                $experience['title'],
                $prompt,
                json_encode($experience, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                Rbac::userId(),
            ]);
        } catch (\Throwable) {
        }

        return ['experience' => $experience, 'review_required' => true];
    }

    public function commandCenter(int $storeId): array
    {
        $bi = (new AiBusinessIntelligenceService())->executiveDashboard($storeId);

        return [
            'recommendations' => $this->decisionEngine($storeId)['decisions'],
            'revenue_insights' => $bi['sales_forecast'] ?? [],
            'business_risks' => $bi['top_problems'] ?? ['لا توجد مخاطر حرجة حالياً'],
            'opportunities' => $bi['growth_opportunities'] ?? [],
            'ai_alerts' => $bi['alerts'] ?? [],
        ];
    }

    public function generatedExperiences(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM ai_generated_experiences WHERE store_id = ? ORDER BY id DESC LIMIT 20');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [
                ['experience_type' => 'campaign', 'title' => 'AI Campaign Generator', 'status' => 'ready'],
                ['experience_type' => 'flow', 'title' => 'AI Flow Builder', 'status' => 'ready'],
                ['experience_type' => 'report', 'title' => 'AI Executive Report', 'status' => 'ready'],
            ];
        }
    }

    public function aiMarketplace(): array
    {
        return [
            'agents_store' => ['Sales Agent Pro', 'Support Agent Pro', 'Recovery Agent', 'Analytics Agent'],
            'templates' => ['Win-back Campaign', 'Post Purchase Flow', 'Complaint Recovery', 'VIP Nurture'],
            'skills' => ['Order Lookup', 'Product Recommender', 'Payment Follow-up', 'Shipping Tracker'],
            'workflows' => ['Autonomous Revenue Recovery', 'AI Support Triage', 'AI Campaign Optimization'],
        ];
    }

    public function aiInfrastructure(): array
    {
        return [
            'distributed_ai_workers' => Env::get('AI_AGENT_WORKER_CONCURRENCY', '4'),
            'gpu_queues' => Env::get('GPU_QUEUE_URL') ? 'configured' : 'planned',
            'vector_databases' => Env::get('VECTOR_DATABASE_URL') ? 'configured' : 'planned',
            'knowledge_graphs' => Env::get('KNOWLEDGE_GRAPH_URL') ? 'configured' : 'planned',
            'multi_region_ai' => Env::get('ENTERPRISE_ENABLED_REGIONS', 'us-east-1'),
        ];
    }

    public function selfImprovingSystem(): array
    {
        return [
            'learns_from' => ['campaign_performance', 'agent_responses', 'customer_feedback', 'conversion_events', 'failed_flows'],
            'improves' => ['reply_quality', 'conversion_rate', 'flow_paths', 'send_time', 'routing_accuracy'],
            'review_model' => 'AI proposes, human approves, system measures outcome',
        ];
    }

    public function futureReady(): array
    {
        return [
            'api_first_architecture' => true,
            'headless_mode' => Env::get('HEADLESS_API_ENABLED', 'true') === 'true',
            'mobile_apps' => ['ios', 'android'],
            'desktop_apps' => ['windows', 'macos'],
            'public_apis' => Env::get('PUBLIC_API_BASE_URL', '/api'),
            'ai_sdk' => Env::get('AI_SDK_VERSION', 'v1'),
        ];
    }

    private function commerceModules(): array
    {
        return ['CRM', 'Messaging', 'Marketing', 'AI', 'Orders', 'Payments', 'Shipping', 'Analytics', 'Automation'];
    }

    private function voiceAi(): array
    {
        return [
            'features' => ['AI Voice Calls', 'Voice Bots', 'Voice Assistant', 'Call Summaries', 'Voice Sentiment'],
            'providers' => ['Twilio Voice', 'Vonage', 'Zoom Phone', 'Custom SIP'],
            'status' => Env::get('VOICE_PROVIDER') ? 'configured' : 'planned',
        ];
    }

    private function readinessScore(int $storeId): int
    {
        $score = 45;
        $score += Env::get('VECTOR_DATABASE_URL') ? 10 : 0;
        $score += Env::get('GPU_QUEUE_URL') ? 10 : 0;
        $score += Env::get('KNOWLEDGE_GRAPH_URL') ? 10 : 0;
        $score += Env::get('QUEUE_REDIS_URL') ? 10 : 0;
        $score += count($this->agents($storeId)) >= 6 ? 10 : 0;
        $score += Env::get('HEADLESS_API_ENABLED', 'true') === 'true' ? 5 : 0;
        return min(100, $score);
    }

    private function seedAgents(): array
    {
        return [
            ['agent_key' => 'sales', 'name' => 'AI Sales Agent', 'role' => 'تحويل المحادثات إلى مبيعات', 'status' => 'ready', 'memory_scope' => 'customer_business', 'report_frequency' => 'daily', 'permissions' => ['read:crm', 'read:products', 'suggest:campaigns'], 'goals' => ['زيادة التحويل', 'اقتراح منتجات', 'تحويل للموظف عند الحاجة'], 'workflow' => ['detect_purchase_intent', 'recommend_product', 'create_offer', 'handover_if_high_value']],
            ['agent_key' => 'support', 'name' => 'AI Support Agent', 'role' => 'حل مشاكل العملاء من قاعدة المعرفة', 'status' => 'ready', 'memory_scope' => 'conversation_customer', 'report_frequency' => 'daily', 'permissions' => ['read:knowledge', 'read:orders', 'suggest:reply'], 'goals' => ['تقليل وقت الرد', 'تصعيد الشكاوى', 'تحسين رضا العملاء'], 'workflow' => ['classify_issue', 'search_knowledge', 'reply_or_escalate', 'summarize']],
            ['agent_key' => 'marketing', 'name' => 'AI Marketing Agent', 'role' => 'توليد وتحسين الحملات', 'status' => 'ready', 'memory_scope' => 'business_campaign', 'report_frequency' => 'weekly', 'permissions' => ['read:analytics', 'suggest:campaigns', 'suggest:segments'], 'goals' => ['اقتراح حملات', 'اختيار أفضل Segment', 'تحسين وقت الإرسال'], 'workflow' => ['analyze_segments', 'generate_campaign', 'estimate_impact', 'request_approval']],
            ['agent_key' => 'recovery', 'name' => 'AI Recovery Agent', 'role' => 'استرجاع السلة والعملاء غير النشطين', 'status' => 'ready', 'memory_scope' => 'customer_order', 'report_frequency' => 'daily', 'permissions' => ['read:orders', 'suggest:coupon', 'suggest:handover'], 'goals' => ['استرجاع الإيراد', 'تقليل خسارة العملاء', 'متابعة الدفع'], 'workflow' => ['detect_recovery_event', 'generate_offer', 'schedule_followup', 'handover_after_timeout']],
            ['agent_key' => 'analytics', 'name' => 'AI Analytics Agent', 'role' => 'تحليل الأداء والتوقعات', 'status' => 'ready', 'memory_scope' => 'business_analytics', 'report_frequency' => 'daily', 'permissions' => ['read:analytics', 'read:campaigns', 'generate:reports'], 'goals' => ['توقع الإيرادات', 'كشف المخاطر', 'إرسال Executive Reports'], 'workflow' => ['collect_metrics', 'detect_anomalies', 'forecast', 'publish_report']],
            ['agent_key' => 'operations', 'name' => 'AI Operations Agent', 'role' => 'تشغيل داخلي وسلاسل موافقات', 'status' => 'ready', 'memory_scope' => 'business_operations', 'report_frequency' => 'weekly', 'permissions' => ['read:workflows', 'suggest:automation', 'monitor:sla'], 'goals' => ['تحسين SLA', 'تنظيم المهام الداخلية', 'كشف الاختناقات'], 'workflow' => ['monitor_sla', 'detect_bottleneck', 'suggest_workflow', 'notify_owner']],
        ];
    }

    private function decodeAgent(array $agent): array
    {
        $agent['permissions'] = json_decode((string) ($agent['permissions_json'] ?? '[]'), true) ?: [];
        $agent['goals'] = json_decode((string) ($agent['goals_json'] ?? '[]'), true) ?: [];
        $agent['workflow'] = json_decode((string) ($agent['workflow_json'] ?? '[]'), true) ?: [];
        return $agent;
    }

    private function storeDecisionLogs(int $storeId, array $decisions): void
    {
        try {
            $stmt = Database::pdo()->prepare("INSERT INTO ai_decision_logs (store_id, decision_type, title, recommendation_json, confidence_score, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'pending_review', NOW(), NOW())");
            foreach ($decisions as $decision) {
                $stmt->execute([
                    $storeId,
                    $decision['type'],
                    $decision['title'],
                    json_encode($decision, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    (int) $decision['confidence'],
                ]);
            }
        } catch (\Throwable) {
        }
    }
}
