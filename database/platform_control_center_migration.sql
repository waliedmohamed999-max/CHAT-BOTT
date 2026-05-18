SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS platform_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value JSON NULL,
    setting_type VARCHAR(40) NOT NULL DEFAULT 'json',
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX platform_settings_secret_idx (is_secret, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(120) NOT NULL,
    setting_value JSON NULL,
    setting_type VARCHAR(40) NOT NULL DEFAULT 'json',
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY store_settings_unique (store_id, setting_key),
    INDEX store_settings_key_idx (setting_key, updated_at),
    CONSTRAINT store_settings_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT store_settings_user_fk FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(120) NOT NULL UNIQUE,
    permission_group VARCHAR(80) NOT NULL,
    label VARCHAR(160) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX permissions_group_idx (permission_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    registration_number VARCHAR(80) NULL,
    tax_number VARCHAR(80) NULL,
    country VARCHAR(80) NULL,
    city VARCHAR(80) NULL,
    business_type VARCHAR(120) NULL,
    website_url VARCHAR(500) NULL,
    logo_url VARCHAR(500) NULL,
    verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX companies_verification_idx (verification_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE stores
    ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS primary_color VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS secondary_color VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS custom_domain VARCHAR(190) NULL,
    ADD COLUMN IF NOT EXISTS login_background_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS support_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS privacy_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS terms_url VARCHAR(500) NULL;

ALTER TABLE stores
    ADD COLUMN IF NOT EXISTS company_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS tax_number VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS business_type VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS country VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS city VARCHAR(80) NULL,
    ADD INDEX IF NOT EXISTS stores_company_idx (company_id);

ALTER TABLE departments
    ADD COLUMN IF NOT EXISTS sla_minutes INT UNSIGNED NULL AFTER priority,
    ADD COLUMN IF NOT EXISTS auto_assignment TINYINT(1) NOT NULL DEFAULT 1 AFTER auto_tag,
    ADD COLUMN IF NOT EXISTS round_robin TINYINT(1) NOT NULL DEFAULT 1 AFTER auto_assignment,
    ADD COLUMN IF NOT EXISTS max_conversations_per_agent INT UNSIGNED NULL AFTER round_robin;

CREATE TABLE IF NOT EXISTS department_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'agent',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY department_members_unique (department_id, user_id),
    INDEX department_members_user_idx (user_id, is_active),
    CONSTRAINT department_members_department_fk FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    CONSTRAINT department_members_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_key VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    monthly_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    yearly_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    limits_json JSON NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    plan_key VARCHAR(80) NOT NULL DEFAULT 'free',
    status ENUM('trialing','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trialing',
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    usage_json JSON NULL,
    trial_ends_at TIMESTAMP NULL,
    current_period_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY store_subscriptions_store_unique (store_id),
    INDEX store_subscriptions_status_idx (status, plan_key),
    CONSTRAINT store_subscriptions_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    scopes_json JSON NULL,
    status ENUM('active','revoked') NOT NULL DEFAULT 'active',
    last_used_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX api_keys_store_status_idx (store_id, status),
    CONSTRAINT api_keys_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT api_keys_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhook_endpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    url VARCHAR(600) NOT NULL,
    events_json JSON NULL,
    secret_encrypted LONGTEXT NULL,
    status ENUM('active','disabled','failing') NOT NULL DEFAULT 'active',
    last_delivery_at TIMESTAMP NULL,
    failure_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX webhook_endpoints_store_status_idx (store_id, status),
    CONSTRAINT webhook_endpoints_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    ip_address VARCHAR(80) NULL,
    user_agent VARCHAR(500) NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX security_events_store_severity_idx (store_id, severity, created_at),
    CONSTRAINT security_events_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    CONSTRAINT security_events_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('in_app','email','sms','webhook','slack') NOT NULL DEFAULT 'in_app',
    event_key VARCHAR(120) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    config_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY notification_settings_unique (store_id, channel, event_key),
    CONSTRAINT notification_settings_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(120) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(700) NOT NULL,
    file_mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('uploaded','deleted') NOT NULL DEFAULT 'uploaded',
    reviewed_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX documents_store_type_idx (store_id, document_type, reviewed_status),
    CONSTRAINT documents_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT documents_user_fk FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS file_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    asset_type VARCHAR(80) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(700) NOT NULL,
    file_mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    access_scope ENUM('private','team','public') NOT NULL DEFAULT 'private',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX file_assets_store_type_idx (store_id, asset_type, created_at),
    CONSTRAINT file_assets_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    backup_type ENUM('database','files','full') NOT NULL DEFAULT 'full',
    status ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
    file_path VARCHAR(700) NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX backup_jobs_store_status_idx (store_id, status, created_at),
    CONSTRAINT backup_jobs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL DEFAULT 'openai',
    model VARCHAR(120) NULL,
    encrypted_api_key LONGTEXT NULL,
    settings_json JSON NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY ai_settings_store_unique (store_id),
    CONSTRAINT ai_settings_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_health_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    check_key VARCHAR(120) NOT NULL,
    status ENUM('passed','failed','warning','pending') NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    payload_json JSON NULL,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX system_health_checks_store_idx (store_id, check_key, checked_at),
    CONSTRAINT system_health_checks_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (role_key, name, description, is_system, created_at, updated_at) VALUES
('super_admin', 'Super Admin', 'تحكم كامل في المنصة', 1, NOW(), NOW()),
('platform_admin', 'Platform Admin', 'إدارة تشغيل المنصة', 1, NOW(), NOW()),
('store_owner', 'Store Owner', 'مالك المتجر', 1, NOW(), NOW()),
('store_admin', 'Store Admin', 'مدير المتجر', 1, NOW(), NOW()),
('marketing_manager', 'Marketing Manager', 'مدير التسويق والحملات', 1, NOW(), NOW()),
('support_agent', 'Support Agent', 'موظف الدعم', 1, NOW(), NOW()),
('sales_agent', 'Sales Agent', 'موظف المبيعات', 1, NOW(), NOW()),
('billing_agent', 'Billing Agent', 'موظف الحسابات', 1, NOW(), NOW()),
('viewer', 'Viewer', 'مشاهدة فقط', 1, NOW(), NOW());

INSERT IGNORE INTO permissions (permission_key, permission_group, label, description, created_at, updated_at) VALUES
('settings.manage', 'المنصة', 'إدارة الإعدادات', 'تعديل إعدادات مركز التحكم', NOW(), NOW()),
('users.manage', 'المستخدمون', 'إدارة المستخدمين', 'إضافة وتعديل وتعطيل المستخدمين', NOW(), NOW()),
('roles.manage', 'المستخدمون', 'إدارة الأدوار', 'تعديل مصفوفة الصلاحيات', NOW(), NOW()),
('whatsapp.manage', 'واتساب', 'إدارة واتساب', 'إدارة إعدادات واتساب والربط', NOW(), NOW()),
('campaign.approve', 'الحملات', 'اعتماد الحملات', 'مراجعة الحملات قبل الإطلاق', NOW(), NOW()),
('inbox.view', 'المحادثات', 'عرض المحادثات', 'مشاهدة المحادثات الموحدة', NOW(), NOW()),
('inbox.assign', 'المحادثات', 'توزيع المحادثات', 'تحويل المحادثات للفرق والموظفين', NOW(), NOW()),
('crm.view', 'العملاء', 'عرض العملاء', 'مشاهدة بيانات العملاء', NOW(), NOW()),
('crm.edit', 'العملاء', 'تعديل العملاء', 'تعديل بيانات العملاء', NOW(), NOW()),
('crm.export', 'العملاء', 'تصدير العملاء', 'تصدير بيانات العملاء', NOW(), NOW()),
('analytics.export', 'التقارير', 'تصدير التقارير', 'تصدير التحليلات والسجلات', NOW(), NOW()),
('billing.view', 'الفواتير', 'عرض الفواتير', 'مشاهدة الاشتراكات والفواتير', NOW(), NOW());

INSERT IGNORE INTO subscription_plans (plan_key, name, monthly_price, yearly_price, limits_json, status, created_at, updated_at) VALUES
('free', 'Free', 0, 0, JSON_OBJECT('messages', 250, 'team_members', 1, 'whatsapp_numbers', 1, 'campaigns', 2, 'ai_credits', 100, 'storage_mb', 100), 'active', NOW(), NOW()),
('starter', 'Starter', 29, 290, JSON_OBJECT('messages', 3000, 'team_members', 3, 'whatsapp_numbers', 1, 'campaigns', 20, 'ai_credits', 1000, 'storage_mb', 1024), 'active', NOW(), NOW()),
('professional', 'Professional', 99, 990, JSON_OBJECT('messages', 20000, 'team_members', 10, 'whatsapp_numbers', 3, 'campaigns', 150, 'ai_credits', 10000, 'storage_mb', 10240), 'active', NOW(), NOW()),
('enterprise', 'Enterprise', 299, 2990, JSON_OBJECT('messages', 100000, 'team_members', 50, 'whatsapp_numbers', 10, 'campaigns', 1000, 'ai_credits', 75000, 'storage_mb', 102400), 'active', NOW(), NOW());
