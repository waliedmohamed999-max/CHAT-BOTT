<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class LaunchReadinessService
{
    public function overview(int $storeId): array
    {
        $items = [
            $this->item('اللغة العربية مكتملة', true, 'الواجهة الأساسية تعمل باللغة العربية كلغة افتراضية.'),
            $this->item('RTL مكتمل', true, 'الصفحات تعمل باتجاه RTL من جذر المستند.'),
            $this->item('الدومين HTTPS', $this->httpsReady(), 'رابط الإنتاج يجب أن يستخدم HTTPS ودومين حقيقي غير محلي.'),
            $this->item('أسرار الإنتاج مكتملة', $this->secretsReady(), 'JWT وENCRYPTION_KEY وأسرار Meta/Webhook موجودة وقوية وليست قيماً تجريبية.'),
            $this->item('قاعدة البيانات جاهزة', $this->databaseReady(), 'الجداول الأساسية والعلاقات والفهارس متاحة.'),
            $this->item('واتساب جاهز', $this->whatsappReady($storeId), 'Meta Cloud API وQR Connection Layer جاهزان أو متصلان.'),
            $this->item('Webhook جاهز', $this->webhookReady(), 'توكن التحقق وسر التوقيع وسجل الويب هوك جاهزة على رابط HTTPS.'),
            $this->item('الأمان مفعل', $this->securityReady(), 'التشفير، الهيدرز الأمنية، CSRF، Rate Limit، ومفاتيح البيئة مفعلة.'),
            $this->item('الصلاحيات مكتملة', $this->tablesReady(['users', 'workspace_members', 'audit_logs']), 'RBAC وعزل المتاجر وسجلات التدقيق متاحة.'),
            $this->item('المستندات تعمل', $this->documentsReady(), 'رفع الملفات يعمل خارج public مع تحقق النوع والحجم.'),
            $this->item('الحملات تعمل', $this->tablesReady(['campaigns', 'campaign_messages', 'campaign_message_events']), 'الجداول وQueue Logs الخاصة بالحملات جاهزة.'),
            $this->item('الشات بوت يعمل', $this->tablesReady(['chatbot_flows', 'chatbot_nodes', 'chatbot_edges', 'departments', 'chatbot_sessions']), 'المسارات، الأقسام، والتحويلات محفوظة بجداول مستقلة.'),
            $this->item('الفواتير تعمل', $this->tablesReady(['subscriptions', 'invoices', 'payment_gateways', 'usage_counters']), 'الاشتراكات والفواتير وتتبع الاستخدام جاهزة.'),
            $this->item('لا توجد أخطاء حرجة', $this->criticalLogsClean(), 'لا توجد مؤشرات أخطاء حرجة في ملفات التشغيل المحلية.'),
        ];

        $sections = $this->sectionChecks();
        $operations = $this->operationChecks($storeId);
        $environment = $this->environmentChecklist();
        $blocking = $this->blockingItems($items);
        $alerts = $this->alerts($items, $environment);
        $score = $this->score($items);

        return [
            'score' => $score,
            'status' => $this->status($score, $blocking),
            'items' => $items,
            'blocking' => $blocking,
            'alerts' => $alerts,
            'sections' => $sections,
            'operations' => $operations,
            'environment' => $environment,
        ];
    }

    private function item(string $label, bool $ready, string $description): array
    {
        return [
            'label' => $label,
            'ready' => $ready,
            'state' => $ready ? 'جاهز' : 'يحتاج مراجعة',
            'description' => $description,
        ];
    }

    private function databaseReady(): bool
    {
        try {
            Database::pdo()->query('SELECT 1');
            return $this->tablesReady(['stores', 'users', 'meta_connections', 'contacts', 'campaigns', 'audit_logs']);
        } catch (\Throwable) {
            return false;
        }
    }

    private function whatsappReady(int $storeId): bool
    {
        if (!$this->tablesReady(['whatsapp_connections', 'whatsapp_qr_sessions', 'whatsapp_templates', 'webhook_logs'])) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM whatsapp_connections WHERE store_id = ? AND status = 'connected'");
            $stmt->execute([$storeId]);
            $connectedConnections = (int) $stmt->fetchColumn();

            $qrStmt = Database::pdo()->prepare("SELECT COUNT(*) FROM whatsapp_qr_sessions WHERE store_id = ? AND session_status = 'connected'");
            $qrStmt->execute([$storeId]);
            $connectedQrSessions = (int) $qrStmt->fetchColumn();

            if (($connectedConnections + $connectedQrSessions) > 0) {
                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function webhookReady(): bool
    {
        return $this->httpsReady()
            && $this->tableReady('webhook_logs')
            && $this->secretReady('META_VERIFY_TOKEN', 16)
            && $this->secretReady('META_WEBHOOK_SECRET', 16);
    }

    private function securityReady(): bool
    {
        $authEnabled = Env::get('APP_ENV') === 'production' || Env::get('AUTH_ENFORCE', 'false') === 'true';
        $csrfEnabled = Env::get('APP_ENV') === 'production' || Env::get('CSRF_ENFORCE', 'false') === 'true';

        return $this->secretsReady()
            && $this->httpsReady()
            && $authEnabled
            && $csrfEnabled
            && (int) Env::get('RATE_LIMIT_PER_MINUTE', '0') > 0;
    }

    private function documentsReady(): bool
    {
        $storage = dirname(__DIR__, 2) . '/storage';
        return $this->tablesReady(['whatsapp_setup_profiles', 'whatsapp_setup_documents'])
            && is_dir($storage)
            && is_writable($storage);
    }

    private function criticalLogsClean(): bool
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            return true;
        }

        foreach (glob($logDir . '/*.log') ?: [] as $file) {
            if (is_file($file) && filesize($file) > 0) {
                return false;
            }
        }

        return true;
    }

    private function sectionChecks(): array
    {
        return [
            $this->item('لوحة القيادة', true, 'المؤشرات والتنقل متاحة.'),
            $this->item('واجهة واتساب الرسمية', $this->tablesReady(['meta_connections', 'whatsapp_business_accounts', 'whatsapp_phone_numbers']), 'ربط Meta وWABA وأرقام الإرسال.'),
            $this->item('ربط واتساب بالباركود', $this->tablesReady(['whatsapp_qr_sessions', 'whatsapp_qr_chats', 'whatsapp_qr_messages']), 'جلسات QR ورسائلها.'),
            $this->item('مركز الإعداد', $this->tableReady('whatsapp_setup_profiles'), 'معالج تجهيز واتساب.'),
            $this->item('رفع المستندات', $this->tableReady('whatsapp_setup_documents'), 'رفع المستندات والمتطلبات.'),
            $this->item('منشئ الحملات', $this->tablesReady(['campaigns', 'campaign_messages']), 'بناء الحملات وجدولتها.'),
            $this->item('القوالب', $this->tableReady('whatsapp_templates'), 'قوالب واتساب الرسمية.'),
            $this->item('المحادثات', $this->tablesReady(['conversations', 'messages', 'omni_conversations', 'omni_messages']), 'المحادثات الموحدة.'),
            $this->item('منشئ الشات بوت', $this->tablesReady(['chatbot_flows', 'chatbot_nodes', 'chatbot_edges']), 'منشئ الرد الآلي.'),
            $this->item('الردود التلقائية', $this->tableReady('chatbot_auto_replies'), 'الردود التلقائية.'),
            $this->item('تحويل الأقسام', $this->tablesReady(['departments', 'department_agents', 'conversation_assignments']), 'تحويل الأقسام والموظفين.'),
            $this->item('إدارة العملاء', $this->tablesReady(['contacts', 'contact_segments']), 'إدارة العملاء والشرائح.'),
            $this->item('الأتمتة', $this->tablesReady(['automations', 'automation_steps', 'automation_runs']), 'أتمتة التشغيل والإيرادات.'),
            $this->item('قاعدة معرفة الذكاء', $this->tablesReady(['chatbot_knowledge_base', 'ai_embeddings']), 'قاعدة المعرفة والفهرسة.'),
            $this->item('التحليلات', $this->tableReady('analytics_events'), 'الأحداث والتقارير.'),
            $this->item('الإعدادات', true, 'إعدادات التشغيل والالتزام.'),
            $this->item('المستخدمون والصلاحيات', $this->tablesReady(['users', 'workspace_members']), 'الفريق والصلاحيات.'),
            $this->item('الفوترة', $this->tablesReady(['subscriptions', 'invoices', 'payment_gateways']), 'الاشتراكات والفواتير.'),
            $this->item('إدارة المنصة', $this->tablesReady(['stores', 'subscriptions', 'usage_counters']), 'إدارة المنصة.'),
            $this->item('السجلات', $this->tablesReady(['audit_logs', 'webhook_logs', 'campaign_message_events', 'failed_jobs']), 'سجلات التدقيق والويب هوك والمهام الفاشلة.'),
            $this->item('التنبيهات', $this->tableReady('notification_logs'), 'تنبيهات التشغيل والفواتير والربط.'),
        ];
    }

    private function operationChecks(int $storeId): array
    {
        return [
            $this->item('تسجيل الدخول', $this->tableReady('login_logs'), 'سجلات تسجيل الدخول متاحة.'),
            $this->item('إنشاء متجر', $this->tableReady('stores'), 'جدول المتاجر جاهز.'),
            $this->item('ربط Meta', $this->secretsReady() && $this->envFilled(['META_REDIRECT_URI']) && $this->httpsReady(), 'إعدادات OAuth الأساسية موجودة وتعمل على HTTPS.'),
            $this->item('ربط QR', $this->envFilled(['WHATSAPP_QR_BRIDGE_URL', 'WHATSAPP_QR_BRIDGE_TOKEN']) && $this->tableReady('whatsapp_qr_sessions'), 'Bridge وجداول الجلسات جاهزة.'),
            $this->item('رفع المستندات', $this->documentsReady(), 'التخزين والتحقق جاهزان.'),
            $this->item('إرسال رسالة اختبار', $this->tableReady('setup_test_logs'), 'سجلات الاختبار جاهزة.'),
            $this->item('استقبال Webhook', $this->webhookReady(), 'مسار التحقق وسجل الاستقبال جاهزان.'),
            $this->item('تشغيل الرد الآلي', $this->tableReady('chatbot_event_logs'), 'سجل أحداث البوت متاح.'),
            $this->item('التحويل حسب القسم', $this->tableReady('conversation_assignments'), 'إسناد المحادثات للأقسام متاح.'),
            $this->item('إنشاء حملة', $this->tableReady('campaigns'), 'جدول الحملات جاهز.'),
            $this->item('جدولة حملة', $this->tableReady('campaign_messages'), 'قائمة الرسائل جاهزة.'),
            $this->item('عرض التقارير', $this->tableReady('analytics_events'), 'تحليلات الأحداث جاهزة.'),
            $this->item('إدارة المستخدمين', $this->tableReady('workspace_members'), 'الأعضاء والصلاحيات جاهزون.'),
            $this->item('الاشتراكات والفواتير', $this->tableReady('invoices'), 'الفوترة جاهزة.'),
        ];
    }

    private function environmentChecklist(): array
    {
        $definitions = [
            'APP_ENV' => ['critical' => true, 'equals' => 'production', 'message' => 'APP_ENV يجب أن يكون production قبل الإطلاق.'],
            'APP_DEBUG' => ['critical' => true, 'equals' => 'false', 'message' => 'APP_DEBUG يجب أن يكون false في الإنتاج.'],
            'AUTH_ENFORCE' => ['critical' => true, 'equals' => 'true', 'message' => 'AUTH_ENFORCE يجب أن يكون true لحماية مسارات اللوحة.'],
            'CSRF_ENFORCE' => ['critical' => true, 'equals' => 'true', 'message' => 'CSRF_ENFORCE يجب أن يكون true لحماية الطلبات الحساسة.'],
            'APP_URL' => ['critical' => true, 'https' => true, 'message' => 'APP_URL يجب أن يكون HTTPS ودومين إنتاج حقيقي.'],
            'DATABASE_URL' => ['critical' => true, 'message' => 'رابط قاعدة البيانات مطلوب لتشغيل المنصة.'],
            'JWT_SECRET' => ['critical' => true, 'min' => 32, 'message' => 'JWT_SECRET يجب أن يكون سراً قوياً لا يقل عن 32 حرفاً.'],
            'ENCRYPTION_KEY' => ['critical' => true, 'min' => 32, 'message' => 'ENCRYPTION_KEY مطلوب لتشفير التوكنات وحالة QR ولا يقل عن 32 حرفاً.'],
            'META_APP_ID' => ['critical' => true, 'min' => 5, 'message' => 'META_APP_ID مطلوب لتفعيل Meta Business Login.'],
            'META_APP_SECRET' => ['critical' => true, 'min' => 16, 'message' => 'META_APP_SECRET مطلوب ويجب ألا يكون قيمة تجريبية.'],
            'META_VERIFY_TOKEN' => ['critical' => true, 'min' => 16, 'message' => 'META_VERIFY_TOKEN مطلوب للتحقق من Webhook.'],
            'META_WEBHOOK_SECRET' => ['critical' => true, 'min' => 16, 'message' => 'META_WEBHOOK_SECRET مطلوب للتحقق من توقيع Webhook.'],
            'META_REDIRECT_URI' => ['critical' => true, 'https' => true, 'path_ends' => '/api/meta/callback', 'message' => 'META_REDIRECT_URI يجب أن يكون HTTPS وينتهي بمسار /api/meta/callback.'],
            'WHATSAPP_API_VERSION' => ['critical' => true, 'message' => 'إصدار WhatsApp Graph API مطلوب.'],
            'QUEUE_REDIS_URL' => ['critical' => true, 'message' => 'Redis مطلوب لقوائم الإرسال والمهام الخلفية.'],
            'RATE_LIMIT_PER_MINUTE' => ['critical' => true, 'min_number' => 1, 'message' => 'RATE_LIMIT_PER_MINUTE يجب أن يكون رقماً أكبر من صفر.'],
            'STORAGE_PROVIDER' => ['critical' => false, 'message' => 'مزود التخزين مطلوب لرفع المستندات.'],
            'STORAGE_ACCESS_KEY' => ['critical' => false, 'message' => 'مفتاح التخزين مطلوب عند استخدام مزود غير local.'],
            'STORAGE_SECRET_KEY' => ['critical' => false, 'message' => 'سر التخزين مطلوب عند استخدام مزود غير local.'],
            'PUBLIC_APP_URL' => ['critical' => true, 'https' => true, 'message' => 'PUBLIC_APP_URL يجب أن يكون HTTPS ودومين إنتاج حقيقي.'],
            'NEXT_PUBLIC_APP_NAME' => ['critical' => false, 'message' => 'اسم التطبيق العام مطلوب للواجهة.'],
        ];

        $storageProvider = strtolower((string) Env::get('STORAGE_PROVIDER', 'local'));

        return array_map(function (string $key) use ($definitions, $storageProvider): array {
            $definition = $definitions[$key];
            $critical = (bool) ($definition['critical'] ?? false);
            $required = true;
            if (in_array($key, ['STORAGE_ACCESS_KEY', 'STORAGE_SECRET_KEY'], true) && $storageProvider === 'local') {
                $required = false;
            }

            $ready = $required ? $this->envKeyReady($key, $definition) : true;

            return [
                'key' => $key,
                'ready' => $ready,
                'required' => $required,
                'critical' => $critical,
                'severity' => $critical ? 'critical' : 'warning',
                'message' => $ready ? 'موجود وصالح' : (string) $definition['message'],
                'value_hint' => $this->envHint($key),
            ];
        }, array_keys($definitions));
    }

    private function envKeyReady(string $key, array $definition): bool
    {
        if (isset($definition['equals'])) {
            return strtolower(trim((string) Env::get($key, ''))) === strtolower((string) $definition['equals']);
        }

        if (!empty($definition['https'])) {
            $value = (string) Env::get($key, '');
            if (!$this->httpsValueReady($value)) {
                return false;
            }

            if (isset($definition['path_ends'])) {
                $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
                return str_ends_with($path, (string) $definition['path_ends']);
            }

            return true;
        }

        if (isset($definition['min'])) {
            return $this->secretReady($key, (int) $definition['min']);
        }

        if (isset($definition['min_number'])) {
            return (int) Env::get($key, '0') >= (int) $definition['min_number'];
        }

        return $this->envFilled([$key]);
    }

    private function alerts(array $items, array $environment): array
    {
        $alerts = [];

        foreach ($this->blockingItems($items) as $item) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'مانع إطلاق: ' . ($item['label'] ?? ''),
                'message' => (string) ($item['description'] ?? ''),
            ];
        }

        foreach ($environment as $envItem) {
            if (!empty($envItem['ready'])) {
                continue;
            }

            $alerts[] = [
                'type' => !empty($envItem['critical']) ? 'critical' : 'warning',
                'title' => 'متغير بيئة ناقص: ' . ($envItem['key'] ?? ''),
                'message' => (string) ($envItem['message'] ?? 'أضف المتغير قبل الإطلاق.'),
            ];
        }

        return $alerts;
    }

    private function blockingItems(array $items): array
    {
        $critical = $this->criticalLabels();

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => in_array($item['label'] ?? '', $critical, true) && empty($item['ready'])
        ));
    }

    private function score(array ...$groups): int
    {
        $items = array_merge(...$groups);
        $criticalLabels = $this->criticalLabels();
        $total = 0;
        $ready = 0;

        foreach ($items as $item) {
            $weight = in_array($item['label'] ?? '', $criticalLabels, true) ? 3 : 1;
            $total += $weight;
            if (!empty($item['ready'])) {
                $ready += $weight;
            }
        }

        return (int) round(($ready / max(1, $total)) * 100);
    }

    private function status(int $score, array $blocking): string
    {
        if ($blocking !== []) {
            return $score >= 41 ? 'يحتاج مراجعة' : 'غير جاهز';
        }

        if ($score >= 71) {
            return 'جاهز للإطلاق';
        }

        if ($score >= 41) {
            return 'يحتاج مراجعة';
        }

        return 'غير جاهز';
    }

    private function criticalLabels(): array
    {
        return [
            'الدومين HTTPS',
            'أسرار الإنتاج مكتملة',
            'قاعدة البيانات جاهزة',
            'واتساب جاهز',
            'Webhook جاهز',
            'الأمان مفعل',
            'الصلاحيات مكتملة',
        ];
    }

    private function httpsReady(): bool
    {
        $url = $this->publicUrl();
        return $this->httpsValueReady($url);
    }

    private function httpsValueReady(string $url): bool
    {
        $url = rtrim(trim($url), '/');
        if ($url === '' || $this->looksLikePlaceholder($url)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https'
            && $host !== ''
            && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function publicUrl(): string
    {
        return rtrim((string) (Env::get('PUBLIC_APP_URL') ?? Env::get('APP_URL', '')), '/');
    }

    private function secretsReady(): bool
    {
        foreach ($this->requiredSecrets() as $key => $minLength) {
            if (!$this->secretReady($key, $minLength)) {
                return false;
            }
        }

        return true;
    }

    private function requiredSecrets(): array
    {
        return [
            'JWT_SECRET' => 32,
            'ENCRYPTION_KEY' => 32,
            'META_APP_ID' => 5,
            'META_APP_SECRET' => 16,
            'META_VERIFY_TOKEN' => 16,
            'META_WEBHOOK_SECRET' => 16,
        ];
    }

    private function secretReady(string $key, int $minLength): bool
    {
        $value = trim((string) Env::get($key, ''));
        if (strlen($value) < $minLength) {
            return false;
        }

        return !$this->looksLikePlaceholder($value);
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
        foreach (['change-me', 'change-', 'replace-with', 'your-', 'example', 'placeholder', 'local-development-key', 'test-secret', 'jwt-secret', 'encryption-key'] as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function envHint(string $key): string
    {
        return match ($key) {
            'PUBLIC_APP_URL' => 'https://app.example.com',
            'APP_URL' => 'https://app.example.com',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'AUTH_ENFORCE' => 'true',
            'CSRF_ENFORCE' => 'true',
            'META_REDIRECT_URI' => 'https://app.example.com/api/meta/callback',
            'WHATSAPP_API_VERSION' => 'v23.0',
            'QUEUE_REDIS_URL' => 'redis://127.0.0.1:6379/0',
            'RATE_LIMIT_PER_MINUTE' => '120',
            default => '',
        };
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
}
