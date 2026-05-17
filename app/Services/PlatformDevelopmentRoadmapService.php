<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class PlatformDevelopmentRoadmapService
{
    public function overview(int $storeId): array
    {
        $phases = [];
        $previousProductionReady = true;

        foreach ($this->phaseDefinitions() as $index => $definition) {
            $checks = $this->phaseChecks($definition['key'], $storeId);
            $score = $this->score($checks);
            $blocking = $this->blockingChecks($checks);
            $locked = !$previousProductionReady;
            $status = $locked ? 'not_started' : $this->status($score, $blocking);

            $phase = [
                ...$definition,
                'number' => $index + 1,
                'progress' => $score,
                'launch_score' => $score,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'locked' => $locked,
                'gate_message' => $locked ? 'هذه المرحلة مقفلة حتى تكتمل المرحلة السابقة بجاهزية إنتاجية.' : '',
                'checks' => $checks,
                'phase_checklist' => array_map(static fn (array $check): string => (string) $check['label'], $checks),
                'open_issues' => $this->openIssues($checks),
            ];

            $phases[] = $phase;
            if ($status !== 'production_ready') {
                $previousProductionReady = false;
            }
        }

        $currentPhase = $this->currentPhase($phases);
        $completed = array_values(array_filter($phases, static fn (array $phase): bool => in_array($phase['status'], ['completed', 'production_ready'], true)));
        $inDevelopment = array_values(array_filter($phases, static fn (array $phase): bool => in_array($phase['status'], ['in_progress', 'testing'], true)));
        $upcoming = array_values(array_filter($phases, static fn (array $phase): bool => !empty($phase['locked']) || $phase['status'] === 'not_started'));

        return [
            'name' => 'Platform Development Roadmap',
            'current_phase' => $currentPhase,
            'progress' => $this->overallProgress($phases),
            'launch_status' => $currentPhase['status_label'] ?? 'غير جاهز',
            'completed_sections' => $this->phaseSummaries($completed),
            'in_development_sections' => $this->phaseSummaries($inDevelopment),
            'upcoming_sections' => $this->phaseSummaries($upcoming),
            'open_issues' => $currentPhase['open_issues'] ?? [],
            'phase_tests' => $currentPhase['testing_checklist'] ?? [],
            'status_options' => $this->statusOptions(),
            'rules' => [
                'لا يتم بدء مرحلة جديدة قبل اكتمال الحالية بجاهزية إنتاجية.',
                'كل مرحلة لها صفحات وAPIs ونماذج وفحص أمان واختبار وقائمة إطلاق مستقلة.',
                'أي مانع أمني أو نقص في الربط الحقيقي يمنع حالة Production Ready.',
                'التطوير يتم بطريقة Modular Architecture بدون كسر المسارات الحالية.',
            ],
            'phases' => $phases,
        ];
    }

    private function phaseDefinitions(): array
    {
        return [
            [
                'key' => 'core_foundation',
                'title' => 'Core System Foundation',
                'title_ar' => 'المرحلة 1: أساس النظام',
                'objective' => 'تجهيز أساس المنصة: المصادقة، الصلاحيات، تعدد المتاجر، RTL، التعريب، الأمان، السجلات، Queue، ورفع الملفات.',
                'required_pages' => ['تسجيل الدخول', 'لوحة القيادة', 'الإعدادات', 'المستخدمون والصلاحيات', 'إدارة المنصة', 'جاهزية الإطلاق'],
                'required_apis' => ['POST /api/auth/login', 'POST /api/auth/logout', 'GET /api/auth/me', 'GET /api/launch-readiness', 'GET /api/saas/context', 'GET /api/super-admin/overview'],
                'required_models' => ['users', 'stores', 'workspace_members', 'role_permissions', 'audit_logs', 'notification_logs', 'failed_jobs', 'queue_monitoring_snapshots'],
                'ui_ux' => ['Layout RTL', 'Sidebar عربي', 'Navbar', 'Progress Cards', 'Empty/Loading/Error States'],
                'security_checklist' => ['JWT Secret قوي', 'ENCRYPTION_KEY قوي', 'CSRF في الإنتاج', 'Rate Limiting', 'Audit Logs', 'Tenant Isolation'],
                'testing_checklist' => ['اختبار تسجيل الدخول', 'اختبار الصلاحيات', 'اختبار تبديل المتجر', 'اختبار الحماية', 'اختبار رفع ملف آمن'],
                'production_checklist' => ['HTTPS Domain', 'APP_DEBUG=false', 'CSRF_ENFORCE=true', 'Queue يعمل', 'Logs تعمل', 'لا توجد Secrets في الواجهة'],
            ],
            [
                'key' => 'whatsapp_setup',
                'title' => 'WhatsApp Setup Center',
                'title_ar' => 'المرحلة 2: مركز إعداد واتساب',
                'objective' => 'تفعيل الربط الرسمي Meta Cloud API وربط QR ورفع المستندات واختبار الاتصال والويب هوك.',
                'required_pages' => ['مركز إعداد واتساب', 'ربط Meta', 'واتساب', 'ربط واتساب بالباركود', 'جاهزية الإطلاق'],
                'required_apis' => ['POST /api/meta/connect', 'GET /api/meta/callback', 'GET /api/whatsapp/accounts', 'POST /api/whatsapp/send-test', 'GET/POST /api/webhooks/whatsapp', 'POST /api/whatsapp-qr/session/create'],
                'required_models' => ['meta_connections', 'whatsapp_business_accounts', 'whatsapp_phone_numbers', 'whatsapp_connections', 'whatsapp_qr_sessions', 'webhook_logs', 'setup_test_logs'],
                'ui_ux' => ['Setup Wizard', 'QR Card', 'Connection Status', 'Upload Documents', 'Readiness Score'],
                'security_checklist' => ['Token Encryption', 'Webhook Signature', 'Store Isolation', 'Rate Limit', 'No Secret Exposure'],
                'testing_checklist' => ['ربط Meta حقيقي', 'ربط QR Session', 'إرسال Test Message', 'استقبال Webhook', 'مزامنة Templates'],
                'production_checklist' => ['Business Verified', 'HTTPS Webhook', 'Meta Secrets', 'Verify Token', 'Test Message Passed'],
            ],
            [
                'key' => 'unified_inbox',
                'title' => 'Unified Inbox',
                'title_ar' => 'المرحلة 3: صندوق المحادثات الموحد',
                'objective' => 'تشغيل Inbox موحد للمحادثات والردود والتحويلات والملاحظات وحالات الرسائل والوسائط.',
                'required_pages' => ['المحادثات', 'القنوات الموحدة', 'تحويل الأقسام'],
                'required_apis' => ['GET /api/inbox', 'POST /api/inbox/reply', 'POST /api/inbox/{id}', 'GET /api/omnichannel/conversations', 'POST /api/omnichannel/reply'],
                'required_models' => ['conversations', 'messages', 'departments', 'conversation_assignments', 'omni_conversations', 'omni_messages'],
                'ui_ux' => ['Conversation List', 'Chat Panel', 'Assignment Panel', 'Quick Replies', 'Media Viewer'],
                'security_checklist' => ['Store-scoped conversations', 'Agent permissions', 'Attachment validation', 'Audit handovers'],
                'testing_checklist' => ['استقبال رسالة حقيقية', 'الرد من نفس القناة', 'تحويل لموظف', 'بحث وفلترة', 'عرض حالات الرسائل'],
                'production_checklist' => ['Inbox real-time ready', 'Departments configured', 'Notes/Tags enabled', 'Message logs complete'],
            ],
            [
                'key' => 'chatbot_builder',
                'title' => 'Chatbot Builder',
                'title_ar' => 'المرحلة 4: منشئ الشات بوت',
                'objective' => 'بناء شجرة رد آلي مرئية مع WhatsApp Preview وردود تلقائية وكلمات مفتاحية وتحويل بشري وAI Replies.',
                'required_pages' => ['منشئ الشات بوت', 'قاعدة المعرفة', 'تحويل الأقسام'],
                'required_apis' => ['GET /api/chatbot/builder', 'POST /api/chatbot/flows', 'POST /api/chatbot/process-message', 'POST /api/chatbot/handover', 'POST /api/chatbot/resume'],
                'required_models' => ['chatbot_flows', 'chatbot_nodes', 'chatbot_edges', 'chatbot_sessions', 'chatbot_event_logs', 'chatbot_handover_logs'],
                'ui_ux' => ['WhatsApp Preview Phone', 'Flow Canvas', 'Node Settings', 'Auto Save', 'Department Routing'],
                'security_checklist' => ['Bot scoped per store', 'No cross-tenant flow access', 'Human handover audit', 'AI guardrails'],
                'testing_checklist' => ['اختبار Welcome Flow', 'اختبار اختيار قسم', 'اختبار إيقاف البوت', 'اختبار Resume Bot', 'اختبار AI Reply'],
                'production_checklist' => ['Default flow active', 'Fallback reply', 'Human queue configured', 'Bot logs complete'],
            ],
            [
                'key' => 'crm_system',
                'title' => 'CRM System',
                'title_ar' => 'المرحلة 5: نظام إدارة العملاء',
                'objective' => 'إدارة العملاء والوسوم والشرائح والتايم لاين والملاحظات وسجل الشراء وLead Score.',
                'required_pages' => ['العملاء', 'Customer 360', 'Segments'],
                'required_apis' => ['POST /api/contacts/import', 'GET /api/omnichannel/customer-360'],
                'required_models' => ['contacts', 'contact_segments', 'omni_customers', 'omni_customer_identities', 'ai_customer_profiles'],
                'ui_ux' => ['Customer Table', 'Filters', 'Tags', 'Timeline', 'Lead Score'],
                'security_checklist' => ['Opt-in enforcement', 'PII protection', 'Tenant isolation', 'Import validation'],
                'testing_checklist' => ['استيراد عملاء', 'بحث وتصنيف', 'تحديث Tags', 'عرض Timeline', 'منع الإرسال بدون Opt-in'],
                'production_checklist' => ['Customer data complete', 'Import logs', 'Opt-in source stored', 'PII access reviewed'],
            ],
            [
                'key' => 'campaign_builder',
                'title' => 'Campaign Builder',
                'title_ar' => 'المرحلة 6: منشئ الحملات',
                'objective' => 'إنشاء وجدولة وإرسال حملات واتساب عبر قوالب معتمدة وBatch Queue وتحليلات وإعادة المحاولة.',
                'required_pages' => ['الحملات', 'القوالب', 'التحليلات'],
                'required_apis' => ['POST /api/campaigns', 'POST /api/campaigns/{id}/launch', 'POST /api/campaigns/{id}/pause', 'POST /api/campaigns/{id}/resume', 'GET /api/campaigns/{id}/progress'],
                'required_models' => ['campaigns', 'campaign_messages', 'campaign_message_events', 'whatsapp_templates'],
                'ui_ux' => ['Campaign Wizard', 'WhatsApp Preview', 'Audience Builder', 'Schedule', 'Progress Logs'],
                'security_checklist' => ['Approved templates only', 'Opt-in only', 'Rate limits', 'Duplicate prevention', 'Unsubscribe keywords'],
                'testing_checklist' => ['إنشاء حملة', 'جدولة حملة', 'إرسال Batch', 'Pause/Resume', 'Retry Failed'],
                'production_checklist' => ['Templates synced', 'Queue active', 'Cost estimate visible', 'Campaign audit complete'],
            ],
            [
                'key' => 'automation_engine',
                'title' => 'Automation Engine',
                'title_ar' => 'المرحلة 7: محرك الأتمتة',
                'objective' => 'تشغيل أتمتة الإيرادات مثل السلة المتروكة وما بعد الشراء وتذكير الدفع وإعادة تنشيط العملاء.',
                'required_pages' => ['الأتمتة', 'Revenue Engine', 'Workflow Builder'],
                'required_apis' => ['GET /api/automation-revenue/overview', 'POST /api/automation-revenue/flows', 'POST /api/automation-revenue/trigger', 'POST /api/automation-revenue/process'],
                'required_models' => ['automations', 'automation_steps', 'automation_runs', 'automation_revenue_events'],
                'ui_ux' => ['Workflow Cards', 'Trigger Builder', 'Delay/Condition Nodes', 'Revenue Analytics'],
                'security_checklist' => ['Opt-in checks', 'Template window policy', 'Safe QR limits', 'Stop conditions'],
                'testing_checklist' => ['Abandoned Cart Trigger', 'Delay execution', 'Coupon step', 'Human handover', 'Stop condition'],
                'production_checklist' => ['Triggers connected to store', 'Queue workers active', 'Revenue attribution ready'],
            ],
            [
                'key' => 'ai_knowledge',
                'title' => 'AI Knowledge Base',
                'title_ar' => 'المرحلة 8: قاعدة معرفة الذكاء',
                'objective' => 'تفعيل إعدادات AI وقاعدة المعرفة والFAQ والاقتراحات والتلخيص واكتشاف النية ضمن حدود آمنة.',
                'required_pages' => ['AI Knowledge Base', 'AI Assistant', 'Inbox AI Panel'],
                'required_apis' => ['GET /api/chatbot/ai/settings', 'POST /api/chatbot/ai/settings', 'GET /api/chatbot/knowledge-base', 'POST /api/chatbot/ai/reply', 'POST /api/chatbot/ai/classify'],
                'required_models' => ['chatbot_ai_settings', 'chatbot_knowledge_base', 'chatbot_ai_conversation_insights', 'ai_embeddings', 'ai_audit_logs'],
                'ui_ux' => ['Knowledge Upload', 'FAQ Cards', 'AI Settings', 'Suggested Replies', 'Summary Panel'],
                'security_checklist' => ['AI uses knowledge only', 'No sensitive training', 'AI audit logs', 'Human fallback'],
                'testing_checklist' => ['سؤال من قاعدة المعرفة', 'سؤال غير معروف', 'Intent Detection', 'AI Summary', 'Suggested Reply'],
                'production_checklist' => ['Knowledge indexed', 'AI limits configured', 'Fallback to agent active', 'Accuracy reviewed'],
            ],
            [
                'key' => 'analytics_bi',
                'title' => 'Analytics & BI',
                'title_ar' => 'المرحلة 9: التحليلات وذكاء الأعمال',
                'objective' => 'تشغيل تقارير حقيقية ولوحات KPIs وتحليلات توقعية للحملات والعملاء والمحادثات.',
                'required_pages' => ['التحليلات', 'ذكاء الأعمال AI', 'Executive Dashboard'],
                'required_apis' => ['GET /api/analytics', 'GET /api/ai-bi/executive', 'GET /api/ai-bi/sales-prediction', 'GET /api/ai-bi/analytics2'],
                'required_models' => ['analytics_events', 'ai_customer_profiles', 'ai_recommendations', 'ai_smart_alerts'],
                'ui_ux' => ['KPI Cards', 'Charts', 'Funnels', 'Predictive Widgets', 'Smart Alerts'],
                'security_checklist' => ['Role-based reporting', 'Tenant-scoped metrics', 'No raw secrets in reports'],
                'testing_checklist' => ['عرض KPIs', 'حساب Delivery Rate', 'تحليل حملة', 'توقع إيراد', 'Smart Alert'],
                'production_checklist' => ['Events pipeline active', 'Charts load fast', 'Reports export reviewed'],
            ],
            [
                'key' => 'billing_saas',
                'title' => 'Billing & SaaS',
                'title_ar' => 'المرحلة 10: الفوترة والاشتراكات',
                'objective' => 'إدارة الباقات والاشتراكات والفواتير وبوابات الدفع وتتبع الاستخدام والحدود.',
                'required_pages' => ['الباقات والاشتراكات', 'الفواتير', 'بوابات الدفع', 'إدارة المنصة'],
                'required_apis' => ['GET /api/saas/plans', 'GET /api/saas/subscription', 'POST /api/saas/subscription', 'GET /api/saas/usage', 'GET /api/saas/invoices'],
                'required_models' => ['subscriptions', 'invoices', 'payment_gateways', 'usage_counters'],
                'ui_ux' => ['Plans Grid', 'Usage Bars', 'Invoice Table', 'Payment Gateway Forms'],
                'security_checklist' => ['Payment secret isolation', 'Webhook validation', 'Usage enforcement', 'RBAC billing access'],
                'testing_checklist' => ['ترقية باقة', 'إنشاء فاتورة', 'تتبع استخدام', 'فشل دفع', 'حدود الباقة'],
                'production_checklist' => ['Payment gateway live keys', 'Invoice numbering', 'Tax fields', 'Renewal jobs'],
            ],
            [
                'key' => 'marketplace_integrations',
                'title' => 'Marketplace & Integrations',
                'title_ar' => 'المرحلة 11: المتجر والتكاملات',
                'objective' => 'إطلاق Marketplace للتطبيقات والقوالب والإضافات ومفاتيح المطورين وWebhooks وOAuth Apps.',
                'required_pages' => ['المتجر والتطبيقات', 'Developer Portal', 'Super Admin Review'],
                'required_apis' => ['GET /api/marketplace/overview', 'GET /api/marketplace/apps', 'POST /api/marketplace/apps/{id}/install', 'POST /api/developer/api-keys', 'POST /api/developer/webhooks'],
                'required_models' => ['marketplace_apps', 'marketplace_installations', 'marketplace_reviews', 'developer_api_keys', 'developer_oauth_apps', 'developer_webhook_endpoints', 'plugin_events'],
                'ui_ux' => ['App Store', 'Install Flow', 'Ratings', 'Developer Portal', 'Permission Badges'],
                'security_checklist' => ['OAuth scopes', 'App permissions', 'Webhook signing', 'API limits', 'Audit logs'],
                'testing_checklist' => ['تثبيت تطبيق', 'إنشاء API Key', 'تسجيل Webhook', 'إلغاء تثبيت', 'Super Admin approval'],
                'production_checklist' => ['App review policy', 'Sandbox rules', 'Revenue share ready', 'Developer docs'],
            ],
            [
                'key' => 'enterprise_scaling',
                'title' => 'Enterprise & Scaling',
                'title_ar' => 'المرحلة 12: Enterprise والتوسع',
                'objective' => 'تجهيز البنية العالمية: Microservices، Monitoring، Docker، CI/CD، Security Hardening، CDN، وScaling.',
                'required_pages' => ['المنصة العالمية', 'Compliance Center', 'Region Management', 'Monitoring'],
                'required_apis' => ['GET /api/enterprise/overview', 'GET /api/enterprise/regions', 'POST /api/enterprise/security-policy', 'GET /api/enterprise/compliance'],
                'required_models' => ['enterprise_regions', 'enterprise_messaging_providers', 'enterprise_security_policies', 'enterprise_sso_connections', 'enterprise_compliance_records'],
                'ui_ux' => ['Region Map', 'Compliance Cards', 'SLA Dashboard', 'Infrastructure Status'],
                'security_checklist' => ['SSO/SAML', 'IP Whitelist', 'Data residency', 'Encryption at rest', 'Backups'],
                'testing_checklist' => ['Docker compose', 'Region config', 'Security policy', 'Failover plan', 'Monitoring alert'],
                'production_checklist' => ['CI/CD active', 'Backups verified', 'Monitoring DSN', 'Runbooks', 'Scaling test'],
            ],
        ];
    }

    private function phaseChecks(string $phaseKey, int $storeId): array
    {
        return match ($phaseKey) {
            'core_foundation' => [
                $this->check('Authentication', class_exists(AuthService::class) && $this->tablesReady(['users', 'login_logs']), true, 'تسجيل الدخول والخروج وسجلات المحاولات جاهزة.'),
                $this->check('قاعدة البيانات الأساسية', $this->databaseFoundationReady($storeId), true, 'الجداول والفهارس والبيانات الافتراضية الأساسية جاهزة.'),
                $this->check('الصلاحيات وRBAC', $this->rbacFoundationReady(), true, 'الصلاحيات وسجلات التدقيق وSeed الأدوار جاهزة.'),
                $this->check('تعريب وRTL', true, false, 'الواجهة الرئيسية عربية وRTL.'),
                $this->check('الأمان الأساسي', $this->secretsReady() && $this->httpsReady(), true, 'HTTPS وأسرار الإنتاج مطلوبة.'),
                $this->check('AUTH/CSRF وRate Limit', $this->authReady() && $this->csrfReady() && (int) Env::get('RATE_LIMIT_PER_MINUTE', '0') > 0, true, 'يجب تفعيل حماية الجلسات وCSRF وحدود الطلبات في الإنتاج.'),
                $this->check('Queue والسجلات', (new QueueMonitoringService())->ready() && $this->tableReady('notification_logs'), false, 'جداول المراقبة والطوابير جاهزة مع Redis أو Database fallback.'),
                $this->check('رفع الملفات', $this->storageReady() && $this->tablesReady(['whatsapp_setup_documents']), false, 'التخزين وجداول المستندات جاهزة.'),
            ],
            'whatsapp_setup' => [
                $this->check('Meta Cloud API Models', $this->tablesReady(['meta_connections', 'whatsapp_business_accounts', 'whatsapp_phone_numbers']), true, 'جداول الربط الرسمي موجودة.'),
                $this->check('QR Connect Models', $this->tablesReady(['whatsapp_qr_sessions', 'whatsapp_qr_chats', 'whatsapp_qr_messages']), false, 'جداول QR موجودة.'),
                $this->check('أسرار Meta وWebhook', $this->metaSecretsReady(), true, 'أسرار Meta والتوقيع جاهزة.'),
                $this->check('Webhook HTTPS', $this->httpsReady() && $this->tableReady('webhook_logs'), true, 'Webhook يعمل على HTTPS مع سجل استقبال.'),
                $this->check('اتصال واتساب فعلي', $this->connectedWhatsapp($storeId), true, 'يجب وجود اتصال Cloud API أو QR فعلي.'),
                $this->check('اختبار الاتصال', $this->tableReady('setup_test_logs'), false, 'سجلات اختبار الإرسال موجودة.'),
                $this->check('المستندات', $this->tablesReady(['whatsapp_setup_profiles', 'whatsapp_setup_documents']), false, 'ملف الإعداد والمستندات جاهزة.'),
            ],
            'unified_inbox' => [
                $this->check('جداول المحادثات', $this->tablesReady(['conversations', 'messages']), true, 'جداول واتساب الأساسية موجودة.'),
                $this->check('Omnichannel Inbox', $this->tablesReady(['omni_conversations', 'omni_messages', 'omni_channel_accounts']), false, 'المحادثات الموحدة جاهزة.'),
                $this->check('الأقسام والتحويل', $this->tablesReady(['departments', 'department_agents', 'conversation_assignments']), true, 'التحويلات والأقسام جاهزة.'),
                $this->check('واتساب متصل', $this->connectedWhatsapp($storeId), true, 'الاستقبال الحقيقي يحتاج اتصال واتساب.'),
                $this->check('سجلات الرسائل', $this->tableReady('webhook_logs'), false, 'Payloads الويب هوك محفوظة.'),
            ],
            'chatbot_builder' => [
                $this->check('Flow Builder Models', $this->tablesReady(['chatbot_flows', 'chatbot_nodes', 'chatbot_edges']), true, 'نماذج الشجرة موجودة.'),
                $this->check('Auto Replies & Keywords', $this->tablesReady(['chatbot_auto_replies', 'chatbot_keywords']), false, 'الردود والكلمات جاهزة.'),
                $this->check('Chatbot Sessions', $this->tablesReady(['chatbot_sessions', 'chatbot_event_logs']), true, 'جلسات وسجلات البوت جاهزة.'),
                $this->check('Department Routing', $this->tablesReady(['departments', 'conversation_assignments', 'chatbot_handover_logs']), true, 'التحويل للأقسام جاهز.'),
                $this->check('WhatsApp Connected', $this->connectedWhatsapp($storeId), true, 'تشغيل البوت الحقيقي يحتاج اتصال واتساب.'),
            ],
            'crm_system' => [
                $this->check('Contacts & Segments', $this->tablesReady(['contacts', 'contact_segments']), true, 'العملاء والشرائح جاهزة.'),
                $this->check('Customer 360', $this->tablesReady(['omni_customers', 'omni_customer_identities']), false, 'هوية العميل عبر القنوات جاهزة.'),
                $this->check('AI Customer Profile', $this->tableReady('ai_customer_profiles'), false, 'ملف ذكاء العميل جاهز.'),
                $this->check('Opt-in Enforcement', $this->columnReady('contacts', 'opt_in_status'), true, 'حالة موافقة العميل محفوظة.'),
            ],
            'campaign_builder' => [
                $this->check('Campaign Models', $this->tablesReady(['campaigns', 'campaign_messages', 'campaign_message_events']), true, 'جداول الحملات جاهزة.'),
                $this->check('Templates', $this->tableReady('whatsapp_templates'), true, 'قوالب واتساب موجودة.'),
                $this->check('Queue Ready', (new QueueMonitoringService())->ready(), false, 'Queue مراقب وجاهز.'),
                $this->check('WhatsApp Connected', $this->connectedWhatsapp($storeId), true, 'الإرسال الحقيقي يحتاج اتصال واتساب.'),
                $this->check('Opt-in Contacts', $this->columnReady('contacts', 'opt_in_status'), true, 'منع الإرسال لغير الموافقين.'),
            ],
            'automation_engine' => [
                $this->check('Automation Models', $this->tablesReady(['automations', 'automation_steps', 'automation_runs']), true, 'جداول الأتمتة جاهزة.'),
                $this->check('Revenue Events', $this->tableReady('automation_revenue_events'), false, 'تتبع إيرادات الأتمتة جاهز.'),
                $this->check('Queue Ready', $this->envFilled(['QUEUE_REDIS_URL']), true, 'المهام المؤجلة تحتاج Redis.'),
                $this->check('Campaign Integration', $this->tableReady('campaigns'), false, 'الأتمتة مرتبطة بالحملات.'),
            ],
            'ai_knowledge' => [
                $this->check('AI Settings', $this->tableReady('chatbot_ai_settings'), true, 'إعدادات AI محفوظة.'),
                $this->check('Knowledge Base', $this->tableReady('chatbot_knowledge_base'), true, 'قاعدة المعرفة جاهزة.'),
                $this->check('Embeddings', $this->tableReady('ai_embeddings'), false, 'الفهرسة الدلالية جاهزة.'),
                $this->check('AI Audit', $this->tableReady('ai_audit_logs'), true, 'سجلات قرارات AI جاهزة.'),
                $this->check('AI Conversation Insights', $this->tableReady('chatbot_ai_conversation_insights'), false, 'تلخيص وتحليل المحادثات جاهز.'),
            ],
            'analytics_bi' => [
                $this->check('Analytics Events', $this->tableReady('analytics_events'), true, 'مصدر الأحداث جاهز.'),
                $this->check('AI BI Models', $this->tablesReady(['ai_recommendations', 'ai_smart_alerts', 'ai_customer_profiles']), false, 'نماذج ذكاء الأعمال جاهزة.'),
                $this->check('Campaign Events', $this->tableReady('campaign_message_events'), false, 'أحداث الحملات جاهزة.'),
                $this->check('Omnichannel AI Events', $this->tableReady('omni_ai_events'), false, 'أحداث القنوات الموحدة جاهزة.'),
            ],
            'billing_saas' => [
                $this->check('Subscriptions', $this->tablesReady(['subscriptions', 'invoices']), true, 'الاشتراكات والفواتير جاهزة.'),
                $this->check('Payment Gateways', $this->tableReady('payment_gateways'), true, 'بوابات الدفع جاهزة.'),
                $this->check('Usage Tracking', $this->tableReady('usage_counters'), true, 'تتبع الاستخدام جاهز.'),
                $this->check('Tenant Foundation', $this->tablesReady(['stores', 'workspace_members']), true, 'تعدد المتاجر جاهز.'),
            ],
            'marketplace_integrations' => [
                $this->check('Marketplace Models', $this->tablesReady(['marketplace_apps', 'marketplace_installations', 'marketplace_reviews']), true, 'متجر التطبيقات جاهز.'),
                $this->check('Developer Platform', $this->tablesReady(['developer_api_keys', 'developer_oauth_apps', 'developer_webhook_endpoints']), true, 'منصة المطورين جاهزة.'),
                $this->check('Plugin Events', $this->tableReady('plugin_events'), false, 'أحداث الإضافات جاهزة.'),
                $this->check('OAuth Scope Review', $this->tableReady('audit_logs'), true, 'مراجعة الصلاحيات تسجل في Audit Logs.'),
            ],
            'enterprise_scaling' => [
                $this->check('Enterprise Models', $this->tablesReady(['enterprise_regions', 'enterprise_messaging_providers', 'enterprise_security_policies']), true, 'نماذج Enterprise جاهزة.'),
                $this->check('Compliance & SSO', $this->tablesReady(['enterprise_sso_connections', 'enterprise_compliance_records']), false, 'SSO والامتثال جاهزان.'),
                $this->check('Docker Compose', is_file(dirname(__DIR__, 2) . '/docker-compose.enterprise.yml'), true, 'ملف Docker enterprise موجود.'),
                $this->check('Monitoring Env', $this->envFilled(['OBSERVABILITY_DSN']), false, 'مراقبة الإنتاج تحتاج DSN.'),
                $this->check('Backup Env', $this->envFilled(['BACKUP_STORAGE_URL']), true, 'نسخ احتياطي الإنتاج مطلوب.'),
            ],
            default => [],
        };
    }

    private function check(string $label, bool $ready, bool $critical, string $description): array
    {
        return [
            'label' => $label,
            'ready' => $ready,
            'critical' => $critical,
            'state' => $ready ? 'جاهز' : 'يحتاج مراجعة',
            'description' => $description,
        ];
    }

    private function status(int $score, array $blocking): string
    {
        if ($score >= 95 && $blocking === []) {
            return 'production_ready';
        }

        if ($score >= 85 && $blocking === []) {
            return 'completed';
        }

        if ($score >= 65) {
            return 'testing';
        }

        if ($score >= 25) {
            return 'in_progress';
        }

        return 'not_started';
    }

    private function statusLabel(string $status): string
    {
        return $this->statusOptions()[$status] ?? 'غير محدد';
    }

    private function statusOptions(): array
    {
        return [
            'not_started' => 'لم تبدأ',
            'in_progress' => 'قيد التطوير',
            'testing' => 'مرحلة الاختبار',
            'completed' => 'مكتملة',
            'production_ready' => 'جاهزة للإنتاج',
        ];
    }

    private function score(array $checks): int
    {
        $total = 0;
        $ready = 0;

        foreach ($checks as $check) {
            $weight = !empty($check['critical']) ? 3 : 1;
            $total += $weight;
            if (!empty($check['ready'])) {
                $ready += $weight;
            }
        }

        return (int) round(($ready / max(1, $total)) * 100);
    }

    private function blockingChecks(array $checks): array
    {
        return array_values(array_filter($checks, static fn (array $check): bool => !empty($check['critical']) && empty($check['ready'])));
    }

    private function openIssues(array $checks): array
    {
        return array_map(
            static fn (array $check): array => [
                'label' => (string) $check['label'],
                'severity' => !empty($check['critical']) ? 'critical' : 'warning',
                'message' => (string) $check['description'],
            ],
            array_values(array_filter($checks, static fn (array $check): bool => empty($check['ready'])))
        );
    }

    private function currentPhase(array $phases): array
    {
        foreach ($phases as $phase) {
            if (($phase['status'] ?? '') !== 'production_ready') {
                return $phase;
            }
        }

        return $phases[array_key_last($phases)] ?? [];
    }

    private function phaseSummaries(array $phases): array
    {
        return array_map(static fn (array $phase): array => [
            'number' => $phase['number'] ?? 0,
            'title' => $phase['title_ar'] ?? '',
            'status' => $phase['status_label'] ?? '',
            'progress' => $phase['progress'] ?? 0,
            'locked' => $phase['locked'] ?? false,
        ], $phases);
    }

    private function overallProgress(array $phases): int
    {
        if ($phases === []) {
            return 0;
        }

        return (int) round(array_sum(array_column($phases, 'progress')) / count($phases));
    }

    private function connectedWhatsapp(int $storeId): bool
    {
        try {
            $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM whatsapp_connections WHERE store_id = ? AND status = 'connected'");
            $stmt->execute([$storeId]);
            $connections = (int) $stmt->fetchColumn();

            $qr = Database::pdo()->prepare("SELECT COUNT(*) FROM whatsapp_qr_sessions WHERE store_id = ? AND session_status = 'connected'");
            $qr->execute([$storeId]);
            return ($connections + (int) $qr->fetchColumn()) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function metaSecretsReady(): bool
    {
        return $this->secretReady('META_APP_ID', 5)
            && $this->secretReady('META_APP_SECRET', 16)
            && $this->secretReady('META_VERIFY_TOKEN', 16)
            && $this->secretReady('META_WEBHOOK_SECRET', 16)
            && $this->envFilled(['WHATSAPP_API_VERSION']);
    }

    private function databaseFoundationReady(int $storeId): bool
    {
        if (!$this->tablesReady(['stores', 'users', 'workspaces', 'workspace_members', 'role_permissions', 'departments', 'subscriptions'])) {
            return false;
        }

        if (!$this->columnsReady([
            ['users', 'password_hash'],
            ['users', 'role'],
            ['workspace_members', 'role'],
            ['workspace_members', 'status'],
            ['role_permissions', 'permission_key'],
            ['departments', 'slug'],
            ['subscriptions', 'plan_key'],
        ])) {
            return false;
        }

        if (!$this->indexesReady([
            ['users', 'users_store_role_idx'],
            ['workspace_members', 'workspace_members_role_idx'],
            ['role_permissions', 'role_permission_unique'],
            ['departments', 'departments_store_active_idx'],
        ])) {
            return false;
        }

        return $this->countRows('SELECT COUNT(*) FROM stores WHERE id = ? AND status = ?', [$storeId, 'active']) > 0
            && $this->countRows("SELECT COUNT(*) FROM users WHERE store_id = ? AND role = 'owner'", [$storeId]) > 0
            && $this->countRows("SELECT COUNT(*) FROM workspace_members WHERE store_id = ? AND role = 'owner' AND status = 'active'", [$storeId]) > 0
            && $this->countRows('SELECT COUNT(*) FROM subscriptions WHERE store_id = ?', [$storeId]) > 0
            && $this->defaultDepartmentsReady($storeId);
    }

    private function rbacFoundationReady(): bool
    {
        if (!$this->tablesReady(['role_permissions', 'audit_logs'])) {
            return false;
        }

        foreach ([['owner', '*'], ['admin', 'meta.connect'], ['admin', 'campaign.launch'], ['marketing_manager', 'campaign.create'], ['support_agent', 'inbox.reply'], ['viewer', 'analytics.view']] as [$role, $permission]) {
            if ($this->countRows('SELECT COUNT(*) FROM role_permissions WHERE role_key = ? AND permission_key = ?', [$role, $permission]) === 0) {
                return false;
            }
        }

        return true;
    }

    private function defaultDepartmentsReady(int $storeId): bool
    {
        foreach (['sales', 'support', 'orders', 'billing', 'complaints'] as $slug) {
            if ($this->countRows('SELECT COUNT(*) FROM departments WHERE store_id = ? AND slug = ? AND is_active = 1', [$storeId, $slug]) === 0) {
                return false;
            }
        }

        return true;
    }

    private function secretsReady(): bool
    {
        return $this->secretReady('JWT_SECRET', 32)
            && $this->secretReady('ENCRYPTION_KEY', 32);
    }

    private function csrfReady(): bool
    {
        return Env::get('APP_ENV') === 'production' || Env::get('CSRF_ENFORCE', 'false') === 'true';
    }

    private function authReady(): bool
    {
        return Env::get('APP_ENV') === 'production' || Env::get('AUTH_ENFORCE', 'false') === 'true';
    }

    private function httpsReady(): bool
    {
        $url = rtrim((string) (Env::get('PUBLIC_APP_URL') ?? Env::get('APP_URL', '')), '/');
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https'
            && $host !== ''
            && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && !str_contains($host, 'your-domain');
    }

    private function storageReady(): bool
    {
        $storage = dirname(__DIR__, 2) . '/storage';
        return is_dir($storage) && is_writable($storage);
    }

    private function secretReady(string $key, int $minLength): bool
    {
        $value = trim((string) Env::get($key, ''));
        return strlen($value) >= $minLength && !$this->looksLikePlaceholder($value);
    }

    private function envFilled(array $keys): bool
    {
        foreach ($keys as $key) {
            $value = trim((string) Env::get($key, ''));
            if ($value === '' || $this->looksLikePlaceholder($value)) {
                return false;
            }
        }

        return true;
    }

    private function looksLikePlaceholder(string $value): bool
    {
        $lower = strtolower($value);
        foreach (['change-me', 'replace-with', 'your-', 'example', 'placeholder', 'test-secret', 'local-development-key'] as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function tablesReady(array $tables): bool
    {
        foreach ($tables as $table) {
            if (!$this->tableReady($table)) {
                return false;
            }
        }

        return true;
    }

    private function columnsReady(array $columns): bool
    {
        foreach ($columns as [$table, $column]) {
            if (!$this->columnReady($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexesReady(array $indexes): bool
    {
        foreach ($indexes as [$table, $index]) {
            if (!$this->indexReady($table, $index)) {
                return false;
            }
        }

        return true;
    }

    private function tableReady(string $table): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function columnReady(string $table, string $column): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table) || !preg_match('/^[a-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function indexReady(string $table, string $index): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table) || !preg_match('/^[a-z0-9_]+$/', $index)) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
            $stmt->execute([$table, $index]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
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
