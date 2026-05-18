<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\Validator;
use PDO;

final class PlatformControlCenterService
{
    private const ROLES = [
        'super_admin' => 'Super Admin',
        'platform_admin' => 'Platform Admin',
        'store_owner' => 'Store Owner',
        'store_admin' => 'Store Admin',
        'marketing_manager' => 'Marketing Manager',
        'support_agent' => 'Support Agent',
        'sales_agent' => 'Sales Agent',
        'billing_agent' => 'Billing Agent',
        'viewer' => 'Viewer',
    ];

    private const PERMISSION_GROUPS = [
        'المنصة' => ['saas.admin', 'enterprise.manage', 'developer.manage', 'settings.manage'],
        'واتساب' => ['meta.connect', 'whatsapp.manage', 'templates.manage'],
        'الحملات' => ['campaign.create', 'campaign.launch', 'campaign.approve'],
        'المحادثات' => ['inbox.view', 'inbox.reply', 'inbox.assign'],
        'العملاء' => ['crm.view', 'crm.edit', 'crm.export'],
        'الفواتير' => ['billing.view', 'billing.manage'],
        'التقارير' => ['analytics.view', 'analytics.export'],
        'المستخدمون' => ['workspace.manage', 'users.manage', 'roles.manage'],
    ];

    public function overview(int $storeId): array
    {
        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'system_status' => $this->systemStatus(),
            'general' => $this->general($storeId),
            'whatsapp' => $this->whatsapp($storeId),
            'campaign_limits' => $this->campaignLimits($storeId),
            'quick_replies' => $this->quickReplies($storeId),
            'users' => $this->users($storeId),
            'roles' => $this->roles(),
            'permissions' => $this->permissions(),
            'companies' => $this->companies(),
            'stores' => $this->stores(),
            'departments' => $this->departments($storeId),
            'subscriptions' => $this->subscriptions($storeId),
            'security' => $this->security($storeId),
            'api_keys' => $this->apiKeys($storeId),
            'webhooks' => $this->webhooks($storeId),
            'documents' => $this->documents($storeId),
            'notifications' => $this->notifications($storeId),
            'logs' => $this->logs($storeId),
            'branding' => $this->branding($storeId),
            'ai' => $this->aiSettings($storeId),
            'backup' => $this->backup($storeId),
            'launch' => $this->launch($storeId),
        ];
    }

    public function updateGeneral(int $storeId, array $input): array
    {
        $data = [
            'platform_name' => $this->text($input['platform_name'] ?? 'Marketing Center', 120),
            'default_language' => $this->choice($input['default_language'] ?? 'ar', ['ar', 'en'], 'ar'),
            'timezone' => $this->text($input['timezone'] ?? 'Asia/Riyadh', 80),
            'currency' => strtoupper($this->text($input['currency'] ?? 'SAR', 10)),
            'runtime_mode' => $this->choice($input['runtime_mode'] ?? 'production', ['development', 'testing', 'production'], 'production'),
            'registration_enabled' => $this->bool($input['registration_enabled'] ?? false),
            'store_creation_enabled' => $this->bool($input['store_creation_enabled'] ?? false),
            'support_email' => $this->email($input['support_email'] ?? null),
            'terms_url' => $this->url($input['terms_url'] ?? null),
            'privacy_url' => $this->url($input['privacy_url'] ?? null),
        ];
        $this->saveStoreSetting($storeId, 'general', $data);
        return $this->general($storeId);
    }

    public function updateWhatsapp(int $storeId, array $input): array
    {
        $data = [
            'webhook_url' => $this->url($input['webhook_url'] ?? null),
            'default_sender' => $this->text($input['default_sender'] ?? '', 120),
            'business_hours' => $this->text($input['business_hours'] ?? '09:00-18:00', 80),
            'official_daily_limit' => $this->int($input['official_daily_limit'] ?? 1000, 1, 1000000),
            'qr_daily_limit' => $this->int($input['qr_daily_limit'] ?? 120, 1, 2000),
            'customer_window_hours' => $this->int($input['customer_window_hours'] ?? 24, 1, 72),
            'unsubscribe_keywords' => $this->keywords($input['unsubscribe_keywords'] ?? 'STOP, UNSUBSCRIBE, إلغاء'),
            'unsubscribe_message' => $this->text($input['unsubscribe_message'] ?? 'تم إلغاء اشتراكك من الرسائل التسويقية.', 500),
            'quality_monitoring' => $this->bool($input['quality_monitoring'] ?? true),
            'templates_required_after_window' => $this->bool($input['templates_required_after_window'] ?? true),
        ];
        $this->saveStoreSetting($storeId, 'whatsapp', $data);
        return $this->whatsapp($storeId);
    }

    public function updateCampaignLimits(int $storeId, array $input): array
    {
        $data = [
            'daily_campaigns' => $this->int($input['daily_campaigns'] ?? 20, 1, 10000),
            'daily_messages' => $this->int($input['daily_messages'] ?? 5000, 1, 1000000),
            'batch_size' => $this->int($input['batch_size'] ?? 250, 1, 5000),
            'batch_interval_seconds' => $this->int($input['batch_interval_seconds'] ?? 60, 1, 3600),
            'stop_on_failure_rate' => $this->int($input['stop_on_failure_rate'] ?? 10, 1, 100),
            'retry_failed_messages' => $this->bool($input['retry_failed_messages'] ?? true),
            'random_delay_seconds' => $this->text($input['random_delay_seconds'] ?? '35-90', 20),
            'deduplicate_recipients' => $this->bool($input['deduplicate_recipients'] ?? true),
            'qr_safe_mode' => $this->bool($input['qr_safe_mode'] ?? true),
            'cloud_api_enabled' => $this->bool($input['cloud_api_enabled'] ?? true),
        ];
        $this->saveStoreSetting($storeId, 'campaign_limits', $data);
        return $this->campaignLimits($storeId);
    }

    public function updateQuickReplies(int $storeId, array $input): array
    {
        $data = [
            'welcome_reply' => $this->text($input['welcome_reply'] ?? 'أهلاً بك، كيف نقدر نساعدك؟', 700),
            'away_reply' => $this->text($input['away_reply'] ?? 'وصلتنا رسالتك وسنرد خلال ساعات العمل.', 700),
            'handover_reply' => $this->text($input['handover_reply'] ?? 'تم تحويلك لموظف مختص.', 700),
            'unsubscribe_reply' => $this->text($input['unsubscribe_reply'] ?? 'تم إلغاء الاشتراك بنجاح.', 700),
            'complaint_reply' => $this->text($input['complaint_reply'] ?? 'نعتذر عن تجربتك، سيتم تحويل شكواك للقسم المختص.', 700),
            'followup_reply' => $this->text($input['followup_reply'] ?? 'هل تحتاج أي مساعدة إضافية؟', 700),
            'department_category' => $this->text($input['department_category'] ?? 'عام', 80),
        ];
        $this->saveStoreSetting($storeId, 'quick_replies', $data);
        return $this->quickReplies($storeId);
    }

    public function createUser(int $storeId, array $input): array
    {
        $email = $this->email($input['email'] ?? null);
        if (!$email) {
            throw new \InvalidArgumentException('invalid_email');
        }

        $role = $this->choice($input['role'] ?? 'viewer', array_keys(self::ROLES), 'viewer');
        $password = (string) ($input['password'] ?? bin2hex(random_bytes(6)));
        $name = $this->text($input['name'] ?? $email, 120);
        $departmentId = isset($input['department_id']) && $input['department_id'] !== '' ? (int) $input['department_id'] : null;

        if ($this->tableExists('store_users')) {
            Database::pdo()->prepare(
                'INSERT INTO store_users (store_id, name, email, password_hash, role, department_id, status, two_factor_enabled, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), department_id = VALUES(department_id), status = VALUES(status), updated_at = NOW()'
            )->execute([
                $storeId,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $departmentId,
                $this->choice($input['status'] ?? 'active', ['active', 'disabled', 'invited'], 'active'),
                $this->bool($input['two_factor_enabled'] ?? false) ? 1 : 0,
            ]);
            AuditLogger::record('settings.user_saved', $storeId, Rbac::userId(), 'store_user', null, ['email' => $email, 'role' => $role]);
        }

        return ['users' => $this->users($storeId), 'initial_password' => $password];
    }

    public function updateUser(int $storeId, int $id, array $input): array
    {
        if (!$this->tableExists('store_users')) {
            return ['users' => $this->users($storeId)];
        }

        $sets = [];
        $values = [];
        foreach (['name', 'email', 'role', 'status'] as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = $field . ' = ?';
                $values[] = $field === 'email' ? $this->email($input[$field]) : $this->text($input[$field], 190);
            }
        }
        if (array_key_exists('department_id', $input)) {
            $sets[] = 'department_id = ?';
            $values[] = $input['department_id'] === '' ? null : (int) $input['department_id'];
        }
        if (array_key_exists('two_factor_enabled', $input)) {
            $sets[] = 'two_factor_enabled = ?';
            $values[] = $this->bool($input['two_factor_enabled']) ? 1 : 0;
        }
        if (!empty($input['password'])) {
            $sets[] = 'password_hash = ?';
            $values[] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        }
        if (!$sets) {
            return ['users' => $this->users($storeId)];
        }
        $values[] = $id;
        $values[] = $storeId;
        Database::pdo()->prepare('UPDATE store_users SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ? AND store_id = ?')->execute($values);
        AuditLogger::record('settings.user_updated', $storeId, Rbac::userId(), 'store_user', $id);
        return ['users' => $this->users($storeId)];
    }

    public function deleteUser(int $storeId, int $id): array
    {
        if ($this->tableExists('store_users')) {
            Database::pdo()->prepare("UPDATE store_users SET status = 'disabled', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([$id, $storeId]);
            AuditLogger::record('settings.user_disabled', $storeId, Rbac::userId(), 'store_user', $id);
        }
        return ['users' => $this->users($storeId)];
    }

    public function createRole(array $input): array
    {
        $key = preg_replace('/[^a-z0-9_]/', '_', strtolower((string) ($input['role_key'] ?? 'custom_role'))) ?: 'custom_role';
        $name = $this->text($input['name'] ?? $key, 120);
        if ($this->tableExists('roles')) {
            Database::pdo()->prepare("INSERT INTO roles (role_key, name, description, is_system, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), updated_at = NOW()")
                ->execute([$key, $name, $this->text($input['description'] ?? '', 255)]);
        }
        AuditLogger::record('settings.role_saved', null, Rbac::userId(), 'role', null, ['role_key' => $key]);
        return ['roles' => $this->roles()];
    }

    public function updateRole(int $id, array $input): array
    {
        if ($this->tableExists('roles')) {
            Database::pdo()->prepare('UPDATE roles SET name = COALESCE(?, name), description = COALESCE(?, description), updated_at = NOW() WHERE id = ?')
                ->execute([$input['name'] ?? null, $input['description'] ?? null, $id]);
        }
        return ['roles' => $this->roles()];
    }

    public function updateRolePermissions(string $roleKey, array $permissions): array
    {
        if ($this->tableExists('role_permissions')) {
            $pdo = Database::pdo();
            $pdo->prepare('DELETE FROM role_permissions WHERE role_key = ?')->execute([$roleKey]);
            $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_key, permission_key, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
            foreach (array_values(array_unique(array_map('strval', $permissions))) as $permission) {
                $stmt->execute([$roleKey, $permission, 'Platform Control Center permission']);
            }
        }
        AuditLogger::record('settings.role_permissions_updated', null, Rbac::userId(), 'role', null, ['role_key' => $roleKey, 'permissions' => $permissions]);
        return ['roles' => $this->roles()];
    }

    public function createCompany(array $input): array
    {
        if ($this->tableExists('companies')) {
            Database::pdo()->prepare(
                'INSERT INTO companies (name, registration_number, tax_number, country, city, business_type, website_url, logo_url, verification_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            )->execute([
                $this->text($input['name'] ?? 'شركة جديدة', 190),
                $this->text($input['registration_number'] ?? '', 80),
                $this->text($input['tax_number'] ?? '', 80),
                $this->text($input['country'] ?? '', 80),
                $this->text($input['city'] ?? '', 80),
                $this->text($input['business_type'] ?? '', 120),
                $this->url($input['website_url'] ?? null),
                $this->url($input['logo_url'] ?? null),
                $this->choice($input['verification_status'] ?? 'pending', ['pending', 'verified', 'rejected'], 'pending'),
            ]);
        }
        AuditLogger::record('settings.company_created', null, Rbac::userId(), 'company', null);
        return ['companies' => $this->companies()];
    }

    public function updateCompany(int $id, array $input): array
    {
        if ($this->tableExists('companies')) {
            Database::pdo()->prepare(
                'UPDATE companies SET name = COALESCE(?, name), registration_number = COALESCE(?, registration_number), tax_number = COALESCE(?, tax_number), country = COALESCE(?, country), city = COALESCE(?, city), business_type = COALESCE(?, business_type), website_url = COALESCE(?, website_url), verification_status = COALESCE(?, verification_status), updated_at = NOW() WHERE id = ?'
            )->execute([
                $input['name'] ?? null,
                $input['registration_number'] ?? null,
                $input['tax_number'] ?? null,
                $input['country'] ?? null,
                $input['city'] ?? null,
                $input['business_type'] ?? null,
                $input['website_url'] ?? null,
                $input['verification_status'] ?? null,
                $id,
            ]);
        }
        return ['companies' => $this->companies()];
    }

    public function createStore(array $input): array
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) ($input['slug'] ?? $input['name'] ?? 'store'))) ?: 'store';
        if ($this->tableExists('stores')) {
            Database::pdo()->prepare(
                'INSERT INTO stores (name, slug, plan, status, logo_url, primary_color, secondary_color, custom_domain, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            )->execute([
                $this->text($input['name'] ?? 'متجر جديد', 190),
                $slug,
                $this->choice($input['plan'] ?? 'starter', ['free', 'starter', 'professional', 'enterprise', 'pro'], 'starter'),
                $this->choice($input['status'] ?? 'active', ['active', 'suspended', 'archived'], 'active'),
                $this->url($input['logo_url'] ?? null),
                $this->text($input['primary_color'] ?? '#334a91', 20),
                $this->text($input['secondary_color'] ?? '#5aa9b8', 20),
                $this->text($input['custom_domain'] ?? '', 190),
            ]);
        }
        AuditLogger::record('settings.store_created', null, Rbac::userId(), 'store', null, ['slug' => $slug]);
        return ['stores' => $this->stores()];
    }

    public function updateStore(int $id, array $input): array
    {
        if ($this->tableExists('stores')) {
            $allowed = ['name', 'slug', 'plan', 'status', 'logo_url', 'primary_color', 'secondary_color', 'custom_domain'];
            $sets = [];
            $values = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $input)) {
                    $sets[] = $field . ' = ?';
                    $values[] = $this->text($input[$field], $field === 'custom_domain' ? 190 : 500);
                }
            }
            if ($sets) {
                $values[] = $id;
                Database::pdo()->prepare('UPDATE stores SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?')->execute($values);
            }
        }
        return ['stores' => $this->stores()];
    }

    public function createDepartment(int $storeId, array $input): array
    {
        $this->saveDepartment($storeId, null, $input);
        return ['departments' => $this->departments($storeId)];
    }

    public function updateDepartment(int $storeId, int $id, array $input): array
    {
        $this->saveDepartment($storeId, $id, $input);
        return ['departments' => $this->departments($storeId)];
    }

    public function updateSecurity(int $storeId, array $input): array
    {
        $data = [
            'two_factor_required' => $this->bool($input['two_factor_required'] ?? false),
            'password_min_length' => $this->int($input['password_min_length'] ?? 10, 8, 128),
            'session_timeout_minutes' => $this->int($input['session_timeout_minutes'] ?? 120, 15, 10080),
            'ip_whitelist' => $this->keywords($input['ip_whitelist'] ?? ''),
            'rate_limit_per_minute' => $this->int($input['rate_limit_per_minute'] ?? 120, 10, 5000),
            'csrf_enforced' => $this->bool($input['csrf_enforced'] ?? true),
            'secure_cookies' => $this->bool($input['secure_cookies'] ?? true),
            'csp_enabled' => $this->bool($input['csp_enabled'] ?? true),
        ];
        $this->saveStoreSetting($storeId, 'security', $data);
        return $this->security($storeId);
    }

    public function createApiKey(int $storeId, array $input): array
    {
        return (new MarketplaceService())->createApiKey($storeId, $input);
    }

    public function deleteApiKey(int $storeId, int $id): array
    {
        if ($this->tableExists('developer_api_keys')) {
            Database::pdo()->prepare("UPDATE developer_api_keys SET status = 'revoked', updated_at = NOW() WHERE id = ? AND store_id = ?")->execute([$id, $storeId]);
            AuditLogger::record('settings.api_key_revoked', $storeId, Rbac::userId(), 'developer_api_key', $id);
        }
        return ['api_keys' => $this->apiKeys($storeId)];
    }

    public function testWebhook(int $storeId, array $input): array
    {
        $url = $this->url($input['url'] ?? null);
        if (!$url || !str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('webhook_url_must_be_https');
        }

        if ($this->tableExists('webhook_logs')) {
            Database::pdo()->prepare('INSERT INTO webhook_logs (provider, event_type, payload, signature, processed_at, received_at) VALUES (?, ?, ?, ?, NOW(), NOW())')
                ->execute(['settings', 'test', json_encode(['store_id' => $storeId, 'target' => $url, 'message' => 'Platform Control Center webhook test'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'local-test']);
        }
        AuditLogger::record('settings.webhook_tested', $storeId, Rbac::userId(), 'webhook', null, ['url' => $url]);
        return ['status' => 'passed', 'message' => 'تم تسجيل اختبار Webhook بنجاح. نفذ اختبار تسليم خارجي من بيئة الإنتاج عند توفر الشبكة.', 'url' => $url];
    }

    public function uploadDocument(int $storeId, array $file, string $documentType): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('upload_failed');
        }
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new \RuntimeException('file_too_large');
        }

        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('invalid_file_type');
        }

        $safeType = preg_replace('/[^a-z0-9_-]/', '_', strtolower($documentType)) ?: 'document';
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $dir = dirname(__DIR__, 2) . '/storage/control_center/store_' . $storeId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $targetName = $safeType . '_' . date('YmdHis') . '_' . $safeName . '.' . $extension;
        $target = $dir . '/' . $targetName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('upload_failed');
        }

        if ($this->tableExists('documents')) {
            Database::pdo()->prepare(
                'INSERT INTO documents (store_id, document_type, file_name, file_path, file_mime_type, file_size, status, reviewed_status, uploaded_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            )->execute([$storeId, $safeType, $file['name'], $target, $mime, (int) $file['size'], 'uploaded', 'pending', Rbac::userId()]);
        }
        AuditLogger::record('settings.document_uploaded', $storeId, Rbac::userId(), 'document', null, ['document_type' => $safeType]);
        return ['uploaded' => true, 'file_name' => $file['name'], 'document_type' => $safeType];
    }

    public function logs(int $storeId): array
    {
        return [
            'audit' => $this->fetchRows('audit_logs', 'store_id = ? OR store_id IS NULL', [$storeId], 'created_at DESC', 30),
            'login' => $this->fetchRows($this->tableExists('login_attempts') ? 'login_attempts' : 'login_logs', 'store_id = ? OR store_id IS NULL', [$storeId], 'created_at DESC', 30),
            'webhook' => $this->fetchRows('webhook_logs', '1 = 1', [], 'received_at DESC', 30),
            'failed_jobs' => $this->fetchRows('failed_jobs', 'store_id = ? OR store_id IS NULL', [$storeId], 'failed_at DESC', 30),
            'security' => $this->fetchRows('security_events', 'store_id = ? OR store_id IS NULL', [$storeId], 'created_at DESC', 30),
        ];
    }

    public function health(int $storeId): array
    {
        return [
            'database' => $this->databaseReady(),
            'required_tables' => $this->tableStatus(['stores', 'users', 'store_users', 'role_permissions', 'audit_logs', 'developer_api_keys', 'developer_webhook_endpoints', 'documents', 'system_health_checks']),
            'environment' => $this->environmentStatus(),
            'security' => $this->security($storeId),
            'last_checked_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function launch(int $storeId): array
    {
        try {
            return (new LaunchReadinessService())->overview($storeId);
        } catch (\Throwable) {
            return ['score' => 0, 'status' => 'غير جاهز', 'items' => []];
        }
    }

    private function general(int $storeId): array
    {
        $defaults = [
            'platform_name' => Env::get('NEXT_PUBLIC_APP_NAME', 'Marketing Center'),
            'default_language' => 'ar',
            'timezone' => Env::get('APP_TIMEZONE', 'Asia/Riyadh'),
            'currency' => Env::get('DEFAULT_CURRENCY', 'SAR'),
            'runtime_mode' => Env::get('APP_ENV', 'production'),
            'registration_enabled' => filter_var(Env::get('REGISTRATION_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
            'store_creation_enabled' => filter_var(Env::get('STORE_CREATION_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
            'support_email' => Env::get('SUPPORT_EMAIL', 'support@marketing-center.local'),
            'terms_url' => Env::get('TERMS_URL', ''),
            'privacy_url' => Env::get('PRIVACY_URL', ''),
        ];
        return array_merge($defaults, $this->storeSetting($storeId, 'general'));
    }

    private function whatsapp(int $storeId): array
    {
        $connection = $this->first('whatsapp_connections', 'store_id = ?', [$storeId], 'id DESC') ?: [];
        $qr = $this->first('whatsapp_qr_sessions', 'store_id = ?', [$storeId], 'id DESC') ?: [];
        $defaults = [
            'webhook_url' => rtrim((string) Env::get('PUBLIC_APP_URL', Env::get('APP_URL', '')), '/') . '/api/webhooks/whatsapp',
            'default_sender' => $connection['phone_number_id'] ?? '',
            'business_hours' => '09:00-18:00',
            'official_daily_limit' => 1000,
            'qr_daily_limit' => 120,
            'customer_window_hours' => 24,
            'unsubscribe_keywords' => ['STOP', 'UNSUBSCRIBE', 'إلغاء'],
            'unsubscribe_message' => 'تم إلغاء اشتراكك من الرسائل التسويقية.',
            'quality_monitoring' => true,
            'templates_required_after_window' => true,
            'meta_status' => $connection['status'] ?? 'disconnected',
            'qr_status' => $qr['session_status'] ?? 'disconnected',
            'templates_status' => $this->safeCount('whatsapp_templates', 'store_id = ?', [$storeId]) > 0 ? 'synced' : 'pending',
            'queue_status' => $this->safeCount('failed_jobs', 'store_id = ?', [$storeId]) === 0 ? 'healthy' : 'needs_review',
        ];
        return array_merge($defaults, $this->storeSetting($storeId, 'whatsapp'));
    }

    private function campaignLimits(int $storeId): array
    {
        $defaults = [
            'daily_campaigns' => 20,
            'daily_messages' => 5000,
            'batch_size' => 250,
            'batch_interval_seconds' => 60,
            'stop_on_failure_rate' => 10,
            'retry_failed_messages' => true,
            'random_delay_seconds' => '35-90',
            'deduplicate_recipients' => true,
            'qr_safe_mode' => true,
            'cloud_api_enabled' => true,
        ];
        return array_merge($defaults, $this->storeSetting($storeId, 'campaign_limits'));
    }

    private function quickReplies(int $storeId): array
    {
        $defaults = [
            'welcome_reply' => 'أهلاً بك، كيف نقدر نساعدك؟',
            'away_reply' => 'وصلتنا رسالتك وسنرد خلال ساعات العمل.',
            'handover_reply' => 'تم تحويلك لموظف مختص.',
            'unsubscribe_reply' => 'تم إلغاء الاشتراك بنجاح.',
            'complaint_reply' => 'نعتذر عن تجربتك، سيتم تحويل شكواك للقسم المختص.',
            'followup_reply' => 'هل تحتاج أي مساعدة إضافية؟',
            'department_category' => 'عام',
        ];
        return array_merge($defaults, $this->storeSetting($storeId, 'quick_replies'));
    }

    private function users(int $storeId): array
    {
        $storeUsers = $this->fetchRows('store_users', 'store_id = ?', [$storeId], 'id DESC', 80);
        if ($storeUsers) {
            return array_map([$this, 'sanitizeUserRow'], $storeUsers);
        }
        return array_map([$this, 'sanitizeUserRow'], $this->fetchRows('users', 'store_id = ? OR store_id IS NULL', [$storeId], 'id DESC', 80));
    }

    private function roles(): array
    {
        $rows = $this->fetchRows('roles', '1 = 1', [], 'id ASC', 100);
        if ($rows) {
            return array_map(function (array $role): array {
                $key = (string) ($role['role_key'] ?? '');
                if (isset(self::ROLES[$key])) {
                    $role['name'] = self::ROLES[$key];
                    $role['description'] = $this->roleDescription($key);
                }
                return $role;
            }, $rows);
        }
        $roles = [];
        foreach (self::ROLES as $key => $name) {
            $roles[] = ['role_key' => $key, 'name' => $name, 'description' => 'دور افتراضي داخل مركز التحكم', 'is_system' => 1];
        }
        return $roles;
    }

    private function roleDescription(string $roleKey): string
    {
        return [
            'super_admin' => 'تحكم كامل في المنصة',
            'platform_admin' => 'إدارة تشغيل المنصة',
            'store_owner' => 'مالك المتجر',
            'store_admin' => 'مدير المتجر',
            'marketing_manager' => 'مدير التسويق والحملات',
            'support_agent' => 'موظف الدعم',
            'sales_agent' => 'موظف المبيعات',
            'billing_agent' => 'موظف الحسابات',
            'viewer' => 'مشاهدة فقط',
        ][$roleKey] ?? 'دور مخصص داخل مركز التحكم';
    }

    private function permissions(): array
    {
        $matrix = [];
        foreach (self::PERMISSION_GROUPS as $group => $permissions) {
            foreach ($permissions as $permission) {
                $matrix[] = ['group' => $group, 'permission_key' => $permission, 'label' => $this->permissionLabel($permission)];
            }
        }
        return $matrix;
    }

    private function companies(): array
    {
        $rows = $this->fetchRows('companies', '1 = 1', [], 'id DESC', 50);
        if ($rows) {
            return $rows;
        }
        $stores = $this->stores();
        return array_map(static fn (array $store): array => [
            'id' => $store['id'] ?? null,
            'name' => $store['name'] ?? 'شركة المتجر',
            'country' => '',
            'city' => '',
            'verification_status' => $store['status'] ?? 'active',
            'stores_count' => 1,
        ], $stores);
    }

    private function stores(): array
    {
        return $this->fetchRows('stores', '1 = 1', [], 'id DESC', 100);
    }

    private function departments(int $storeId): array
    {
        $rows = $this->fetchRows('departments', 'store_id = ?', [$storeId], 'priority DESC, id ASC', 80);
        if ($rows) {
            return $rows;
        }
        return [
            ['name' => 'المبيعات', 'slug' => 'sales', 'color' => '#2f80ed', 'priority' => 'high', 'is_active' => 1, 'auto_tag' => 'sales'],
            ['name' => 'الدعم الفني', 'slug' => 'support', 'color' => '#2f9b75', 'priority' => 'normal', 'is_active' => 1, 'auto_tag' => 'support'],
            ['name' => 'الطلبات والشحن', 'slug' => 'orders', 'color' => '#f59e0b', 'priority' => 'normal', 'is_active' => 1, 'auto_tag' => 'orders'],
            ['name' => 'الحسابات والفواتير', 'slug' => 'billing', 'color' => '#8d83c9', 'priority' => 'normal', 'is_active' => 1, 'auto_tag' => 'billing'],
            ['name' => 'الشكاوى', 'slug' => 'complaints', 'color' => '#c94b55', 'priority' => 'urgent', 'is_active' => 1, 'auto_tag' => 'complaint'],
        ];
    }

    private function subscriptions(int $storeId): array
    {
        return [
            'current' => (new SaasPlatformService())->subscription($storeId),
            'usage' => (new SaasPlatformService())->usage($storeId),
            'plans' => (new SaasPlatformService())->plans(),
            'invoices' => (new SaasPlatformService())->invoices($storeId),
            'payment_gateways' => (new SaasPlatformService())->paymentGateways($storeId),
        ];
    }

    private function security(int $storeId): array
    {
        $defaults = [
            'two_factor_required' => false,
            'password_min_length' => 10,
            'session_timeout_minutes' => 120,
            'ip_whitelist' => [],
            'rate_limit_per_minute' => (int) Env::get('RATE_LIMIT_MAX_ATTEMPTS', '120'),
            'csrf_enforced' => Env::get('CSRF_ENFORCE') !== 'false',
            'secure_cookies' => Env::get('APP_ENV', 'local') === 'production',
            'csp_enabled' => true,
            'jwt_secret_present' => Env::get('JWT_SECRET') !== null,
            'encryption_key_present' => Env::get('ENCRYPTION_KEY') !== null,
            'login_attempts_24h' => $this->safeCount('login_attempts', 'created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)', []),
            'active_sessions' => $this->safeCount('login_sessions', 'store_id = ? AND revoked_at IS NULL', [$storeId]),
        ];
        return array_merge($defaults, $this->storeSetting($storeId, 'security'));
    }

    private function apiKeys(int $storeId): array
    {
        return (new MarketplaceService())->apiKeys($storeId);
    }

    private function webhooks(int $storeId): array
    {
        $rows = $this->fetchRows('developer_webhook_endpoints', 'store_id = ?', [$storeId], 'id DESC', 50);
        $rows = array_map(static function (array $row): array {
            unset($row['secret_encrypted'], $row['encrypted_secret']);
            return $row;
        }, $rows);
        $rows[] = [
            'id' => 'meta',
            'url' => rtrim((string) Env::get('PUBLIC_APP_URL', Env::get('APP_URL', '')), '/') . '/api/webhooks/whatsapp',
            'status' => Env::get('META_VERIFY_TOKEN') ? 'configured' : 'missing_verify_token',
            'events_json' => json_encode(['messages', 'message_status', 'template_updates'], JSON_UNESCAPED_UNICODE),
        ];
        return $rows;
    }

    private function documents(int $storeId): array
    {
        $docs = $this->fetchRows('documents', 'store_id = ?', [$storeId], 'id DESC', 80);
        if ($docs) {
            return array_map([$this, 'sanitizeDocumentRow'], $docs);
        }
        return array_map([$this, 'sanitizeDocumentRow'], $this->fetchRows('whatsapp_setup_documents d JOIN whatsapp_setup_profiles p ON p.id = d.setup_profile_id', 'p.store_id = ?', [$storeId], 'd.id DESC', 80));
    }

    private function notifications(int $storeId): array
    {
        return [
            'settings' => array_merge([
                'webhook_failures' => true,
                'low_quality_number' => true,
                'failed_campaigns' => true,
                'subscription_expiry' => true,
                'new_login' => true,
                'queue_errors' => true,
                'email_notifications' => true,
                'in_app_notifications' => true,
            ], $this->storeSetting($storeId, 'notifications')),
            'recent' => $this->fetchRows('notification_logs', 'store_id = ? OR store_id IS NULL', [$storeId], 'created_at DESC', 20),
        ];
    }

    private function branding(int $storeId): array
    {
        $whiteLabel = (new SaasPlatformService())->whiteLabel($storeId);
        return array_merge([
            'product_name' => Env::get('NEXT_PUBLIC_APP_NAME', 'Marketing Center'),
            'favicon_url' => '',
            'login_message' => 'مرحباً بك في مركز التسويق',
            'mobile_theme' => 'premium-light',
            'font_family' => 'Tajawal',
        ], $whiteLabel, $this->storeSetting($storeId, 'branding'));
    }

    private function aiSettings(int $storeId): array
    {
        try {
            $settings = (new ChatbotService())->aiSettings($storeId);
        } catch (\Throwable) {
            $settings = [];
        }
        return array_merge([
            'enabled' => false,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'language' => 'ar',
            'tone' => 'professional',
            'reply_length' => 'medium',
            'usage_limit' => 1000,
            'safety_rules' => 'لا تجب خارج قاعدة المعرفة. عند عدم التأكد حول المحادثة لموظف.',
        ], $settings);
    }

    private function backup(int $storeId): array
    {
        return [
            'jobs' => $this->fetchRows('backup_jobs', 'store_id = ? OR store_id IS NULL', [$storeId], 'created_at DESC', 30),
            'schedule' => array_merge(['enabled' => false, 'frequency' => 'daily', 'retention_days' => 30], $this->storeSetting($storeId, 'backup')),
        ];
    }

    private function saveDepartment(int $storeId, ?int $id, array $input): void
    {
        if (!$this->tableExists('departments')) {
            return;
        }
        $name = $this->text($input['name'] ?? 'قسم جديد', 190);
        $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) ($input['slug'] ?? $name))) ?: 'department';
        $data = [
            $storeId,
            $name,
            $slug,
            $this->text($input['color'] ?? '#2f9b75', 20),
            $this->text($input['welcome_message'] ?? '', 700),
            $this->text($input['away_message'] ?? '', 700),
            $this->text($input['working_hours'] ?? '09:00-18:00', 80),
            $this->choice($input['priority'] ?? 'normal', ['low', 'normal', 'high', 'urgent'], 'normal'),
            $this->bool($input['is_active'] ?? true) ? 1 : 0,
            $this->text($input['auto_tag'] ?? $slug, 120),
        ];
        if ($id) {
            Database::pdo()->prepare('UPDATE departments SET name = ?, slug = ?, color = ?, welcome_message = ?, away_message = ?, working_hours = ?, priority = ?, is_active = ?, auto_tag = ?, updated_at = NOW() WHERE id = ? AND store_id = ?')
                ->execute(array_slice($data, 1) + [9 => $id, 10 => $storeId]);
            AuditLogger::record('settings.department_updated', $storeId, Rbac::userId(), 'department', $id);
            return;
        }
        Database::pdo()->prepare(
            'INSERT INTO departments (store_id, name, slug, color, welcome_message, away_message, working_hours, priority, is_active, auto_tag, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color), welcome_message = VALUES(welcome_message), away_message = VALUES(away_message), working_hours = VALUES(working_hours), priority = VALUES(priority), is_active = VALUES(is_active), auto_tag = VALUES(auto_tag), updated_at = NOW()'
        )->execute($data);
        AuditLogger::record('settings.department_saved', $storeId, Rbac::userId(), 'department', null, ['slug' => $slug]);
    }

    private function storeSetting(int $storeId, string $key): array
    {
        if (!$this->tableExists('store_settings')) {
            return [];
        }
        try {
            $stmt = Database::pdo()->prepare('SELECT setting_value FROM store_settings WHERE store_id = ? AND setting_key = ? LIMIT 1');
            $stmt->execute([$storeId, $key]);
            $value = $stmt->fetchColumn();
            $decoded = is_string($value) ? json_decode($value, true) : null;
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function saveStoreSetting(int $storeId, string $key, array $value): void
    {
        if (!$this->tableExists('store_settings')) {
            return;
        }
        Database::pdo()->prepare(
            'INSERT INTO store_settings (store_id, setting_key, setting_value, setting_type, updated_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_by = VALUES(updated_by), updated_at = NOW()'
        )->execute([$storeId, $key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'json', Rbac::userId()]);
        AuditLogger::record('settings.' . $key . '_updated', $storeId, Rbac::userId(), 'store_settings', $storeId);
    }

    private function fetchRows(string $table, string $where, array $params = [], string $order = 'id DESC', int $limit = 50): array
    {
        $baseTable = trim(strtok($table, ' '));
        if (!$this->tableExists($baseTable)) {
            return [];
        }
        try {
            $stmt = Database::pdo()->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY {$order} LIMIT {$limit}");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function first(string $table, string $where, array $params = [], string $order = 'id DESC'): ?array
    {
        $rows = $this->fetchRows($table, $where, $params, $order, 1);
        return $rows[0] ?? null;
    }

    private function safeCount(string $table, string $where = '1 = 1', array $params = []): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }
        try {
            $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $stmt->execute([$table]);
            return $cache[$table] = ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable) {
            return $cache[$table] = false;
        }
    }

    private function tableStatus(array $tables): array
    {
        $status = [];
        foreach ($tables as $table) {
            $status[$table] = $this->tableExists($table);
        }
        return $status;
    }

    private function environmentStatus(): array
    {
        $required = ['DATABASE_URL', 'JWT_SECRET', 'ENCRYPTION_KEY', 'META_APP_ID', 'META_APP_SECRET', 'META_VERIFY_TOKEN', 'META_WEBHOOK_SECRET', 'WHATSAPP_API_VERSION', 'QUEUE_REDIS_URL'];
        $items = [];
        foreach ($required as $key) {
            $items[$key] = Env::get($key) !== null;
        }
        return $items;
    }

    private function databaseReady(): bool
    {
        try {
            Database::pdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function systemStatus(): array
    {
        $env = $this->environmentStatus();
        $missing = array_keys(array_filter($env, static fn (bool $ready): bool => !$ready));
        return [
            'state' => $missing ? 'needs_review' : 'healthy',
            'label' => $missing ? 'يحتاج مراجعة' : 'مستقر',
            'missing_env' => $missing,
            'database' => $this->databaseReady() ? 'connected' : 'unavailable',
        ];
    }

    private function permissionLabel(string $permission): string
    {
        return [
            'saas.admin' => 'إدارة SaaS',
            'enterprise.manage' => 'إدارة المؤسسة',
            'developer.manage' => 'منصة المطورين',
            'settings.manage' => 'إدارة الإعدادات',
            'meta.connect' => 'ربط Meta',
            'whatsapp.manage' => 'إدارة واتساب',
            'templates.manage' => 'إدارة القوالب',
            'campaign.create' => 'إنشاء حملة',
            'campaign.launch' => 'إطلاق حملة',
            'campaign.approve' => 'اعتماد حملة',
            'inbox.view' => 'عرض المحادثات',
            'inbox.reply' => 'الرد على المحادثات',
            'inbox.assign' => 'توزيع المحادثات',
            'crm.view' => 'عرض العملاء',
            'crm.edit' => 'تعديل العملاء',
            'crm.export' => 'تصدير العملاء',
            'billing.view' => 'عرض الفواتير',
            'billing.manage' => 'إدارة الفواتير',
            'analytics.view' => 'عرض التقارير',
            'analytics.export' => 'تصدير التقارير',
            'workspace.manage' => 'إدارة مساحة العمل',
            'users.manage' => 'إدارة المستخدمين',
            'roles.manage' => 'إدارة الصلاحيات',
        ][$permission] ?? $permission;
    }

    private function sanitizeUserRow(array $row): array
    {
        unset($row['password_hash'], $row['passwordHash'], $row['two_factor_secret_ciphertext'], $row['two_factor_secret'], $row['session_token_hash']);
        return $row;
    }

    private function sanitizeDocumentRow(array $row): array
    {
        unset($row['file_path'], $row['fileUrl'], $row['file_url']);
        return $row;
    }

    private function text(mixed $value, int $max = 255): string
    {
        $value = trim((string) $value);

        return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
    }

    private function email(mixed $value): ?string
    {
        $email = trim((string) $value);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function url(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function int(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private function choice(mixed $value, array $allowed, string $fallback): string
    {
        $value = (string) $value;
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function keywords(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value)));
        }
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
