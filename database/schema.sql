CREATE TABLE IF NOT EXISTS stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    plan VARCHAR(80) NOT NULL DEFAULT 'starter',
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    logo_url VARCHAR(500) NULL,
    primary_color VARCHAR(40) NULL,
    secondary_color VARCHAR(40) NULL,
    custom_domain VARCHAR(190) NULL,
    login_background_url VARCHAR(500) NULL,
    support_url VARCHAR(500) NULL,
    privacy_url VARCHAR(500) NULL,
    terms_url VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stores_custom_domain_idx (custom_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner','admin','marketing_manager','support_agent','viewer') NOT NULL DEFAULT 'viewer',
    two_factor_secret_ciphertext TEXT NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX users_store_role_idx (store_id, role),
    CONSTRAINT users_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'platform_admin',
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX platform_users_role_status_idx (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'viewer',
    department_id BIGINT UNSIGNED NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY store_users_store_email_unique (store_id, email),
    INDEX store_users_store_role_idx (store_id, role, status),
    CONSTRAINT store_users_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    user_type VARCHAR(40) NOT NULL,
    store_id BIGINT UNSIGNED NULL,
    portal_type VARCHAR(40) NOT NULL,
    session_token_hash VARCHAR(128) NULL,
    ip_address VARCHAR(80) NULL,
    user_agent VARCHAR(500) NULL,
    device_name VARCHAR(190) NULL,
    expires_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    INDEX login_sessions_user_idx (user_type, user_id, revoked_at),
    INDEX login_sessions_store_idx (store_id, portal_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    portal_type VARCHAR(40) NOT NULL,
    store_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(80) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    reason VARCHAR(120) NULL,
    created_at TIMESTAMP NULL,
    INDEX login_attempts_email_portal_idx (email, portal_type, created_at),
    INDEX login_attempts_store_idx (store_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    user_type VARCHAR(40) NOT NULL,
    token_hash VARCHAR(128) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    INDEX password_resets_token_idx (token_hash),
    INDEX password_resets_user_idx (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workspaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    status ENUM('active','suspended','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY workspaces_store_slug_unique (store_id, slug),
    CONSTRAINT workspaces_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workspace_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    workspace_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner','admin','agent','viewer') NOT NULL DEFAULT 'agent',
    status ENUM('active','invited','disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY workspace_member_unique (store_id, user_id),
    INDEX workspace_members_role_idx (store_id, role, status),
    CONSTRAINT workspace_members_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT workspace_members_workspace_fk FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE SET NULL,
    CONSTRAINT workspace_members_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workspace_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    role ENUM('owner','admin','agent','viewer') NOT NULL DEFAULT 'agent',
    invite_token_hash VARCHAR(128) NOT NULL,
    status ENUM('pending','accepted','expired','revoked') NOT NULL DEFAULT 'pending',
    invited_by BIGINT UNSIGNED NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX workspace_invites_store_status_idx (store_id, status, expires_at),
    CONSTRAINT workspace_invites_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT workspace_invites_user_fk FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    plan_key ENUM('free','starter','professional','enterprise') NOT NULL DEFAULT 'free',
    status ENUM('trialing','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trialing',
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    trial_ends_at TIMESTAMP NULL,
    current_period_starts_at TIMESTAMP NULL,
    current_period_ends_at TIMESTAMP NULL,
    auto_renew TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY subscriptions_store_unique (store_id),
    INDEX subscriptions_status_idx (status, plan_key),
    CONSTRAINT subscriptions_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    invoice_number VARCHAR(80) NOT NULL,
    status ENUM('draft','open','paid','failed','void') NOT NULL DEFAULT 'draft',
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    due_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY invoices_number_unique (invoice_number),
    INDEX invoices_store_status_idx (store_id, status, created_at),
    CONSTRAINT invoices_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT invoices_subscription_fk FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_gateways (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('stripe','paypal','moyasar','tap','myfatoorah') NOT NULL,
    display_name VARCHAR(190) NULL,
    encrypted_credentials LONGTEXT NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    test_mode TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY payment_gateway_unique (store_id, provider),
    CONSTRAINT payment_gateways_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usage_counters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    metric_key VARCHAR(80) NOT NULL,
    period_key VARCHAR(20) NOT NULL,
    quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY usage_counter_unique (store_id, metric_key, period_key),
    INDEX usage_counter_period_idx (period_key, metric_key),
    CONSTRAINT usage_counters_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS white_label_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    logo_url VARCHAR(500) NULL,
    custom_domain VARCHAR(190) NULL,
    primary_color VARCHAR(20) NULL,
    secondary_color VARCHAR(20) NULL,
    email_from_name VARCHAR(190) NULL,
    email_footer TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY white_label_store_unique (store_id),
    UNIQUE KEY white_label_domain_unique (custom_domain),
    CONSTRAINT white_label_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_apps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_store_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    category VARCHAR(80) NOT NULL,
    app_type ENUM('app','integration','theme','chatbot_template','automation_template','ai_pack') NOT NULL DEFAULT 'app',
    icon VARCHAR(20) NULL,
    short_description TEXT NULL,
    long_description LONGTEXT NULL,
    permissions_json JSON NULL,
    scopes_json JSON NULL,
    manifest_json JSON NULL,
    pricing_model ENUM('free','one_time','subscription','revenue_share') NOT NULL DEFAULT 'free',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    revenue_share_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    install_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft','pending_review','published','rejected','disabled') NOT NULL DEFAULT 'draft',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX marketplace_apps_type_status_idx (app_type, status, featured),
    CONSTRAINT marketplace_apps_developer_store_fk FOREIGN KEY (developer_store_id) REFERENCES stores(id) ON DELETE SET NULL,
    CONSTRAINT marketplace_apps_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_installations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    app_id BIGINT UNSIGNED NOT NULL,
    installed_by BIGINT UNSIGNED NULL,
    status ENUM('active','disabled','uninstalled') NOT NULL DEFAULT 'active',
    permissions_json JSON NULL,
    settings_json JSON NULL,
    installed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY marketplace_installation_unique (store_id, app_id),
    INDEX marketplace_installations_status_idx (store_id, status),
    CONSTRAINT marketplace_installations_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT marketplace_installations_app_fk FOREIGN KEY (app_id) REFERENCES marketplace_apps(id) ON DELETE CASCADE,
    CONSTRAINT marketplace_installations_user_fk FOREIGN KEY (installed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    app_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    review TEXT NULL,
    status ENUM('published','hidden','pending_review') NOT NULL DEFAULT 'pending_review',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX marketplace_reviews_app_idx (app_id, status, rating),
    CONSTRAINT marketplace_reviews_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT marketplace_reviews_app_fk FOREIGN KEY (app_id) REFERENCES marketplace_apps(id) ON DELETE CASCADE,
    CONSTRAINT marketplace_reviews_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS developer_api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    scopes_json JSON NULL,
    rate_limit_per_minute SMALLINT UNSIGNED NOT NULL DEFAULT 120,
    status ENUM('active','revoked') NOT NULL DEFAULT 'active',
    last_used_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX developer_api_keys_store_status_idx (store_id, status),
    CONSTRAINT developer_api_keys_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT developer_api_keys_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS developer_oauth_apps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    client_id VARCHAR(80) NOT NULL UNIQUE,
    encrypted_client_secret LONGTEXT NOT NULL,
    redirect_uris_json JSON NULL,
    scopes_json JSON NULL,
    status ENUM('draft','active','disabled') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX developer_oauth_apps_store_status_idx (store_id, status),
    CONSTRAINT developer_oauth_apps_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT developer_oauth_apps_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS developer_webhook_endpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    app_id BIGINT UNSIGNED NULL,
    url VARCHAR(600) NOT NULL,
    events_json JSON NULL,
    secret_encrypted LONGTEXT NOT NULL,
    status ENUM('active','disabled','failing') NOT NULL DEFAULT 'active',
    last_delivery_at TIMESTAMP NULL,
    failure_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX developer_webhooks_store_status_idx (store_id, status),
    CONSTRAINT developer_webhooks_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT developer_webhooks_app_fk FOREIGN KEY (app_id) REFERENCES marketplace_apps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plugin_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    app_id BIGINT UNSIGNED NULL,
    event_name VARCHAR(120) NOT NULL,
    payload_json JSON NULL,
    status ENUM('queued','delivered','failed') NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    INDEX plugin_events_due_idx (status, event_name, created_at),
    CONSTRAINT plugin_events_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT plugin_events_app_fk FOREIGN KEY (app_id) REFERENCES marketplace_apps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    status ENUM('primary','standby','active','disabled') NOT NULL DEFAULT 'standby',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 10,
    data_residency VARCHAR(80) NOT NULL DEFAULT 'global',
    cdn_endpoint VARCHAR(500) NULL,
    edge_endpoint VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX enterprise_regions_status_idx (status, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_messaging_providers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL,
    region_code VARCHAR(60) NOT NULL DEFAULT 'global',
    status ENUM('active','standby','limited','disabled') NOT NULL DEFAULT 'active',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 1,
    failover_enabled TINYINT(1) NOT NULL DEFAULT 0,
    rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 600,
    config_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY enterprise_provider_unique (store_id, provider, region_code),
    INDEX enterprise_provider_route_idx (store_id, status, priority),
    CONSTRAINT enterprise_provider_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_security_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    sso_enabled TINYINT(1) NOT NULL DEFAULT 0,
    saml_enabled TINYINT(1) NOT NULL DEFAULT 0,
    oauth_enterprise_enabled TINYINT(1) NOT NULL DEFAULT 1,
    soc2_ready TINYINT(1) NOT NULL DEFAULT 0,
    gdpr_enabled TINYINT(1) NOT NULL DEFAULT 1,
    data_residency_region VARCHAR(80) NOT NULL DEFAULT 'global',
    encryption_at_rest TINYINT(1) NOT NULL DEFAULT 1,
    encryption_in_transit TINYINT(1) NOT NULL DEFAULT 1,
    ip_whitelist_json JSON NULL,
    session_timeout_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY enterprise_security_store_unique (store_id),
    CONSTRAINT enterprise_security_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_sso_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider_type ENUM('saml','oidc','oauth') NOT NULL,
    entity_id VARCHAR(300) NULL,
    metadata_url VARCHAR(600) NULL,
    encrypted_config LONGTEXT NULL,
    status ENUM('active','disabled','draft') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX enterprise_sso_store_idx (store_id, provider_type, status),
    CONSTRAINT enterprise_sso_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_compliance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    framework VARCHAR(80) NOT NULL,
    control_key VARCHAR(120) NOT NULL,
    status ENUM('not_started','in_progress','ready','exception') NOT NULL DEFAULT 'not_started',
    evidence_url VARCHAR(600) NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX enterprise_compliance_store_idx (store_id, framework, status),
    CONSTRAINT enterprise_compliance_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT enterprise_compliance_owner_fk FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_voice_integrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL,
    status ENUM('active','disabled','draft') NOT NULL DEFAULT 'draft',
    encrypted_config LONGTEXT NULL,
    recording_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ai_summary_enabled TINYINT(1) NOT NULL DEFAULT 1,
    ivr_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY enterprise_voice_unique (store_id, provider),
    CONSTRAINT enterprise_voice_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_sla_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    department VARCHAR(80) NULL,
    priority VARCHAR(40) NULL,
    first_response_minutes INT UNSIGNED NOT NULL DEFAULT 15,
    resolution_minutes INT UNSIGNED NOT NULL DEFAULT 240,
    escalation_json JSON NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX enterprise_sla_store_idx (store_id, status, department),
    CONSTRAINT enterprise_sla_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_os_agents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    agent_key VARCHAR(80) NOT NULL,
    name VARCHAR(190) NOT NULL,
    role VARCHAR(255) NOT NULL,
    status ENUM('draft','ready','active','paused','disabled') NOT NULL DEFAULT 'ready',
    goals_json JSON NULL,
    permissions_json JSON NULL,
    workflow_json JSON NULL,
    memory_scope VARCHAR(120) NOT NULL DEFAULT 'business_context',
    report_frequency VARCHAR(40) NOT NULL DEFAULT 'daily',
    last_run_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY ai_os_agents_unique (store_id, agent_key),
    INDEX ai_os_agents_status_idx (store_id, status),
    CONSTRAINT ai_os_agents_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_os_memory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    agent_key VARCHAR(80) NULL,
    memory_type ENUM('long_term','conversation','customer','business_context') NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    content LONGTEXT NOT NULL,
    importance_score TINYINT UNSIGNED NOT NULL DEFAULT 50,
    metadata_json JSON NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_os_memory_lookup_idx (store_id, memory_type, subject_type, subject_id),
    FULLTEXT KEY ai_os_memory_text_idx (content),
    CONSTRAINT ai_os_memory_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_decision_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    agent_key VARCHAR(80) NULL,
    decision_type VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    recommendation_json JSON NULL,
    confidence_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending_review','approved','rejected','applied','expired') NOT NULL DEFAULT 'pending_review',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_decision_logs_store_status_idx (store_id, status, decision_type),
    CONSTRAINT ai_decision_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT ai_decision_logs_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_generated_experiences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    experience_type ENUM('campaign','flow','reply','page','report') NOT NULL DEFAULT 'campaign',
    title VARCHAR(190) NOT NULL,
    prompt TEXT NULL,
    output_json JSON NULL,
    status ENUM('draft','pending_review','approved','published','archived') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_generated_experiences_store_idx (store_id, experience_type, status),
    CONSTRAINT ai_generated_experiences_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT ai_generated_experiences_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_agent_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    agent_key VARCHAR(80) NOT NULL,
    report_type VARCHAR(80) NOT NULL,
    summary TEXT NULL,
    metrics_json JSON NULL,
    recommendations_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ai_agent_reports_store_agent_idx (store_id, agent_key, created_at),
    CONSTRAINT ai_agent_reports_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_os_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    agent_key VARCHAR(80) NULL,
    event_type VARCHAR(120) NOT NULL,
    payload_json JSON NULL,
    status ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_os_events_due_idx (status, priority, available_at),
    INDEX ai_os_events_store_type_idx (store_id, event_type, status),
    CONSTRAINT ai_os_events_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    skill_key VARCHAR(120) NOT NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(80) NOT NULL,
    manifest_json JSON NULL,
    status ENUM('draft','published','disabled') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY ai_skills_unique (store_id, skill_key),
    INDEX ai_skills_category_idx (category, status),
    CONSTRAINT ai_skills_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meta_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    meta_user_id VARCHAR(80) NULL,
    business_id VARCHAR(80) NULL,
    token_ciphertext TEXT NOT NULL,
    token_scopes TEXT NULL,
    token_status ENUM('active','expired','invalid','revoked') NOT NULL DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    connected_at TIMESTAMP NULL,
    disconnected_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX meta_connections_store_status_idx (store_id, token_status),
    CONSTRAINT meta_connections_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_business_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    meta_connection_id BIGINT UNSIGNED NULL,
    waba_id VARCHAR(80) NOT NULL,
    business_name VARCHAR(190) NULL,
    verification_status VARCHAR(80) NULL,
    currency VARCHAR(12) NULL,
    timezone_id VARCHAR(80) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY waba_unique (store_id, waba_id),
    CONSTRAINT waba_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT waba_connection_fk FOREIGN KEY (meta_connection_id) REFERENCES meta_connections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_phone_numbers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    waba_id BIGINT UNSIGNED NOT NULL,
    phone_number_id VARCHAR(80) NOT NULL,
    display_phone_number VARCHAR(60) NOT NULL,
    verified_name VARCHAR(190) NULL,
    quality_rating VARCHAR(40) NULL,
    messaging_limit VARCHAR(80) NULL,
    webhook_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    status VARCHAR(80) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY phone_number_unique (store_id, phone_number_id),
    INDEX phone_store_idx (store_id, status),
    CONSTRAINT phone_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT phone_waba_fk FOREIGN KEY (waba_id) REFERENCES whatsapp_business_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    waba_id BIGINT UNSIGNED NULL,
    meta_template_id VARCHAR(120) NULL,
    name VARCHAR(190) NOT NULL,
    category ENUM('MARKETING','UTILITY','AUTHENTICATION') NOT NULL,
    language VARCHAR(20) NOT NULL DEFAULT 'en_US',
    status ENUM('approved','pending','rejected','paused','disabled') NOT NULL DEFAULT 'pending',
    header TEXT NULL,
    body TEXT NOT NULL,
    footer TEXT NULL,
    buttons_json JSON NULL,
    components_json JSON NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY template_unique (store_id, name, language),
    INDEX template_status_idx (store_id, status, category),
    CONSTRAINT templates_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT templates_waba_fk FOREIGN KEY (waba_id) REFERENCES whatsapp_business_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NULL,
    phone VARCHAR(60) NOT NULL,
    country CHAR(2) NULL,
    tags_json JSON NULL,
    opt_in_status ENUM('opted_in','opted_out','unknown') NOT NULL DEFAULT 'unknown',
    opt_in_source VARCHAR(120) NULL,
    opted_in_at TIMESTAMP NULL,
    unsubscribed_at TIMESTAMP NULL,
    last_contact_at TIMESTAMP NULL,
    source VARCHAR(120) NULL,
    orders_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY contact_phone_unique (store_id, phone),
    INDEX contacts_optin_idx (store_id, opt_in_status, unsubscribed_at),
    CONSTRAINT contacts_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_segments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    rules_json JSON NOT NULL,
    contacts_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX segments_store_idx (store_id),
    CONSTRAINT segments_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    channel ENUM('whatsapp','facebook','instagram','tiktok','snapchat','x') NOT NULL DEFAULT 'whatsapp',
    campaign_type VARCHAR(80) NOT NULL,
    audience_type VARCHAR(80) NOT NULL,
    segment_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,
    status ENUM('draft','scheduled','queued','running','paused','completed','failed','cancelled') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    launched_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_cost DECIMAL(12,4) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX campaigns_store_status_idx (store_id, status, scheduled_at),
    CONSTRAINT campaigns_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT campaigns_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT campaigns_segment_fk FOREIGN KEY (segment_id) REFERENCES contact_segments(id) ON DELETE SET NULL,
    CONSTRAINT campaigns_template_fk FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    recipient_phone VARCHAR(60) NOT NULL,
    provider_message_id VARCHAR(190) NULL UNIQUE,
    provider_status ENUM('queued','sent','delivered','read','failed') NOT NULL DEFAULT 'queued',
    queue_status ENUM('pending','processing','sent','retry','failed','cancelled') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    failed_reason TEXT NULL,
    cost DECIMAL(12,4) NOT NULL DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY campaign_contact_unique (campaign_id, contact_id),
    INDEX campaign_messages_queue_idx (queue_status, provider_status, attempts),
    CONSTRAINT campaign_messages_campaign_fk FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT campaign_messages_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    assigned_to BIGINT UNSIGNED NULL,
    status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
    tags_json JSON NULL,
    notes TEXT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX conversations_store_status_idx (store_id, status, last_message_at),
    CONSTRAINT conversations_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT conversations_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT conversations_assigned_fk FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    provider_message_id VARCHAR(190) NULL UNIQUE,
    sender_phone VARCHAR(60) NULL,
    body TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'received',
    payload JSON NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX messages_conversation_idx (conversation_id, sent_at),
    CONSTRAINT messages_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    trigger_event VARCHAR(120) NOT NULL,
    status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX automations_store_status_idx (store_id, status),
    CONSTRAINT automations_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    parent_step_id BIGINT UNSIGNED NULL,
    step_type ENUM('condition','delay','send_template','tag','webhook') NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    config_json JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX automation_steps_order_idx (automation_id, sort_order),
    CONSTRAINT automation_steps_automation_fk FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    automation_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    status ENUM('queued','running','completed','converted','failed','stopped') NOT NULL DEFAULT 'queued',
    current_step INT UNSIGNED NOT NULL DEFAULT 0,
    context_json JSON NULL,
    next_run_at TIMESTAMP NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX automation_runs_due_idx (store_id, status, next_run_at),
    INDEX automation_runs_contact_idx (contact_id, automation_id, status),
    CONSTRAINT automation_runs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT automation_runs_automation_fk FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
    CONSTRAINT automation_runs_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_revenue_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    automation_id BIGINT UNSIGNED NULL,
    automation_run_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    revenue_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(120) NULL,
    order_id VARCHAR(190) NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX automation_revenue_store_event_idx (store_id, event_type, created_at),
    INDEX automation_revenue_flow_idx (automation_id, created_at),
    CONSTRAINT automation_revenue_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT automation_revenue_automation_fk FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE SET NULL,
    CONSTRAINT automation_revenue_run_fk FOREIGN KEY (automation_run_id) REFERENCES automation_runs(id) ON DELETE SET NULL,
    CONSTRAINT automation_revenue_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('facebook','instagram','tiktok','snapchat','x') NOT NULL,
    provider_account_id VARCHAR(190) NOT NULL,
    name VARCHAR(190) NULL,
    token_ciphertext TEXT NULL,
    token_status VARCHAR(40) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY social_account_unique (store_id, provider, provider_account_id),
    CONSTRAINT social_accounts_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    social_account_id BIGINT UNSIGNED NULL,
    caption TEXT NULL,
    media_json JSON NULL,
    status ENUM('draft','scheduled','published','failed') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    analytics_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX social_posts_schedule_idx (store_id, status, scheduled_at),
    CONSTRAINT social_posts_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT social_posts_account_fk FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    campaign_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    revenue DECIMAL(12,4) NOT NULL DEFAULT 0,
    properties_json JSON NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX analytics_store_type_idx (store_id, event_type, occurred_at),
    CONSTRAINT analytics_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT analytics_campaign_fk FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    CONSTRAINT analytics_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_customer_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    purchase_probability TINYINT UNSIGNED NOT NULL DEFAULT 0,
    churn_probability TINYINT UNSIGNED NOT NULL DEFAULT 0,
    best_contact_time VARCHAR(80) NULL,
    preferred_products_json JSON NULL,
    average_spend DECIMAL(12,2) NOT NULL DEFAULT 0,
    engagement_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    customer_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sentiment_score SMALLINT NOT NULL DEFAULT 0,
    lifetime_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    conversion_probability TINYINT UNSIGNED NOT NULL DEFAULT 0,
    generated_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY ai_customer_profiles_contact_unique (store_id, contact_id),
    INDEX ai_customer_profiles_scores_idx (store_id, purchase_probability, churn_probability),
    CONSTRAINT ai_customer_profiles_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT ai_customer_profiles_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_recommendations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    recommendation_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    payload_json JSON NULL,
    status ENUM('pending_review','approved','rejected','applied') NOT NULL DEFAULT 'pending_review',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_recommendations_store_type_idx (store_id, recommendation_type, status, created_at),
    CONSTRAINT ai_recommendations_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT ai_recommendations_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_smart_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    alert_type VARCHAR(120) NOT NULL,
    severity ENUM('info','warning','high','critical') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    payload_json JSON NULL,
    status ENUM('open','acknowledged','resolved') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    INDEX ai_smart_alerts_store_status_idx (store_id, status, severity, created_at),
    CONSTRAINT ai_smart_alerts_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_embeddings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    source_type ENUM('conversation','knowledge','product','campaign','review') NOT NULL,
    source_id BIGINT UNSIGNED NULL,
    content_hash VARCHAR(64) NOT NULL,
    content_text LONGTEXT NULL,
    embedding_json JSON NULL,
    metadata_json JSON NULL,
    indexed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    UNIQUE KEY ai_embeddings_unique (store_id, source_type, source_id, content_hash),
    FULLTEXT KEY ai_embeddings_text_idx (content_text),
    CONSTRAINT ai_embeddings_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_queue_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    job_type VARCHAR(120) NOT NULL,
    status ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
    payload_json JSON NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ai_queue_jobs_due_idx (status, priority, available_at),
    INDEX ai_queue_jobs_store_type_idx (store_id, job_type, status),
    CONSTRAINT ai_queue_jobs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NULL,
    entity_id BIGINT UNSIGNED NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ai_audit_logs_store_action_idx (store_id, action, created_at),
    CONSTRAINT ai_audit_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT ai_audit_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhook_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(40) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    payload JSON NOT NULL,
    signature VARCHAR(190) NULL,
    processed_at TIMESTAMP NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX webhook_logs_event_idx (provider, event_type, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(120) NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(60) NULL,
    user_agent VARCHAR(255) NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX audit_logs_store_action_idx (store_id, action, created_at),
    CONSTRAINT audit_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    CONSTRAINT audit_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_message_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_message_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX campaign_message_events_idx (campaign_message_id, event_type, created_at),
    CONSTRAINT campaign_message_events_message_fk FOREIGN KEY (campaign_message_id) REFERENCES campaign_messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_qr_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    phone_number VARCHAR(60) NULL,
    display_name VARCHAR(190) NULL,
    avatar_url TEXT NULL,
    session_status ENUM('waiting_for_scan','qr_scanned','authenticating','connected','disconnected','expired','error') NOT NULL DEFAULT 'disconnected',
    auth_data_encrypted LONGTEXT NULL,
    last_qr_code LONGTEXT NULL,
    last_connected_at TIMESTAMP NULL,
    disconnected_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX whatsapp_qr_sessions_store_status_idx (store_id, session_status),
    CONSTRAINT whatsapp_qr_sessions_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT whatsapp_qr_sessions_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_qr_chats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    chat_id VARCHAR(190) NOT NULL,
    name VARCHAR(190) NULL,
    is_group TINYINT(1) NOT NULL DEFAULT 0,
    unread_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_message TEXT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY whatsapp_qr_chats_unique (session_id, chat_id),
    INDEX whatsapp_qr_chats_last_idx (session_id, last_message_at),
    CONSTRAINT whatsapp_qr_chats_session_fk FOREIGN KEY (session_id) REFERENCES whatsapp_qr_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_qr_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    chat_id VARCHAR(190) NOT NULL,
    message_id VARCHAR(190) NOT NULL,
    from_phone VARCHAR(80) NULL,
    to_phone VARCHAR(80) NULL,
    body TEXT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'text',
    media_url TEXT NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'received',
    message_timestamp TIMESTAMP NULL,
    raw_payload JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY whatsapp_qr_messages_unique (session_id, message_id),
    INDEX whatsapp_qr_messages_chat_idx (session_id, chat_id, message_timestamp),
    CONSTRAINT whatsapp_qr_messages_session_fk FOREIGN KEY (session_id) REFERENCES whatsapp_qr_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_setup_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    selected_method ENUM('meta_cloud_api','qr_web_session') NULL,
    setup_status ENUM('draft','in_progress','ready','blocked') NOT NULL DEFAULT 'draft',
    business_name VARCHAR(190) NULL,
    store_name VARCHAR(190) NULL,
    country VARCHAR(80) NULL,
    city VARCHAR(120) NULL,
    business_type VARCHAR(120) NULL,
    website_url TEXT NULL,
    store_url TEXT NULL,
    facebook_url TEXT NULL,
    instagram_url TEXT NULL,
    official_email VARCHAR(190) NULL,
    official_phone VARCHAR(60) NULL,
    whatsapp_phone VARCHAR(60) NULL,
    business_description TEXT NULL,
    has_meta_business TINYINT(1) NOT NULL DEFAULT 0,
    is_business_verified TINYINT(1) NOT NULL DEFAULT 0,
    has_privacy_policy TINYINT(1) NOT NULL DEFAULT 0,
    has_terms TINYINT(1) NOT NULL DEFAULT 0,
    readiness_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY whatsapp_setup_profiles_store_unique (store_id),
    CONSTRAINT whatsapp_setup_profiles_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_setup_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setup_profile_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(120) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_url TEXT NOT NULL,
    file_mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    upload_status ENUM('uploaded','failed','deleted') NOT NULL DEFAULT 'uploaded',
    reviewed_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX whatsapp_setup_documents_profile_idx (setup_profile_id, document_type),
    CONSTRAINT whatsapp_setup_documents_profile_fk FOREIGN KEY (setup_profile_id) REFERENCES whatsapp_setup_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    connection_type ENUM('meta_cloud_api','qr_web_session') NOT NULL,
    status ENUM('pending','connected','disconnected','expired','error') NOT NULL DEFAULT 'pending',
    display_name VARCHAR(190) NULL,
    phone_number VARCHAR(60) NULL,
    avatar_url TEXT NULL,
    meta_business_id VARCHAR(80) NULL,
    waba_id VARCHAR(80) NULL,
    phone_number_id VARCHAR(80) NULL,
    encrypted_access_token LONGTEXT NULL,
    encrypted_qr_auth_state LONGTEXT NULL,
    webhook_status VARCHAR(60) NULL,
    last_connected_at TIMESTAMP NULL,
    disconnected_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX whatsapp_connections_store_type_idx (store_id, connection_type, status),
    CONSTRAINT whatsapp_connections_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS setup_test_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NULL,
    test_type VARCHAR(120) NOT NULL,
    status ENUM('passed','failed','warning','pending') NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    raw_payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX setup_test_logs_store_idx (store_id, test_type, created_at),
    CONSTRAINT setup_test_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT setup_test_logs_connection_fk FOREIGN KEY (connection_id) REFERENCES whatsapp_connections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_flows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    connection_source ENUM('meta_cloud_api','qr_web_session','both','all_channels','whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL DEFAULT 'both',
    status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
    trigger_type VARCHAR(80) NOT NULL DEFAULT 'manual',
    trigger_value VARCHAR(190) NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX chatbot_flows_store_status_idx (store_id, status, connection_source),
    CONSTRAINT chatbot_flows_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_flows_user_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flow_id BIGINT UNSIGNED NOT NULL,
    node_key VARCHAR(80) NOT NULL,
    node_type ENUM('message','question','condition','delay','ai_reply','human_handover','api_request','tag','campaign','end') NOT NULL,
    title VARCHAR(190) NULL,
    message TEXT NULL,
    options_json JSON NULL,
    department_id BIGINT UNSIGNED NULL,
    config_json JSON NOT NULL,
    position_x INT NOT NULL DEFAULT 0,
    position_y INT NOT NULL DEFAULT 0,
    settings_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY chatbot_nodes_key_unique (flow_id, node_key),
    CONSTRAINT chatbot_nodes_flow_fk FOREIGN KEY (flow_id) REFERENCES chatbot_flows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_edges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flow_id BIGINT UNSIGNED NOT NULL,
    source_node_key VARCHAR(80) NOT NULL,
    target_node_key VARCHAR(80) NOT NULL,
    source_node_id BIGINT UNSIGNED NULL,
    target_node_id BIGINT UNSIGNED NULL,
    condition_json JSON NULL,
    option_value VARCHAR(190) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX chatbot_edges_flow_idx (flow_id, source_node_key),
    CONSTRAINT chatbot_edges_flow_fk FOREIGN KEY (flow_id) REFERENCES chatbot_flows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_keywords (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(190) NOT NULL,
    match_type ENUM('contains','equals','starts_with','regex') NOT NULL DEFAULT 'contains',
    action_type ENUM('reply','flow','ai','handover') NOT NULL DEFAULT 'reply',
    reply_text TEXT NULL,
    flow_id BIGINT UNSIGNED NULL,
    connection_source ENUM('meta_cloud_api','qr_web_session','both','all_channels','whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL DEFAULT 'both',
    status ENUM('active','paused') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY chatbot_keywords_unique (store_id, keyword),
    CONSTRAINT chatbot_keywords_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_keywords_flow_fk FOREIGN KEY (flow_id) REFERENCES chatbot_flows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_auto_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    reply_type ENUM('welcome','away','offline','first_reply','keyword','order_status','faq','smart_ai') NOT NULL,
    name VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    conditions_json JSON NULL,
    connection_source ENUM('meta_cloud_api','qr_web_session','both','all_channels','whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL DEFAULT 'both',
    status ENUM('active','paused') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX chatbot_auto_replies_store_idx (store_id, reply_type, status),
    CONSTRAINT chatbot_auto_replies_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    conversation_id BIGINT UNSIGNED NULL,
    connection_source ENUM('meta_cloud_api','qr_web_session','whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    flow_id BIGINT UNSIGNED NULL,
    current_node_key VARCHAR(80) NULL,
    current_node_id BIGINT UNSIGNED NULL,
    selected_department_id BIGINT UNSIGNED NULL,
    bot_paused TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('bot_active','human_handover','paused','completed') NOT NULL DEFAULT 'bot_active',
    context_json JSON NULL,
    collected_data JSON NULL,
    last_interaction_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX chatbot_sessions_store_status_idx (store_id, status, last_interaction_at),
    CONSTRAINT chatbot_sessions_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_sessions_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT chatbot_sessions_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    CONSTRAINT chatbot_sessions_flow_fk FOREIGN KEY (flow_id) REFERENCES chatbot_flows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#2fbf71',
    welcome_message TEXT NULL,
    away_message TEXT NULL,
    working_hours VARCHAR(80) NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    auto_tag VARCHAR(120) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY departments_store_slug_unique (store_id, slug),
    INDEX departments_store_active_idx (store_id, is_active, priority),
    CONSTRAINT departments_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS department_agents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY department_agent_unique (department_id, user_id),
    CONSTRAINT department_agents_department_fk FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    CONSTRAINT department_agents_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversation_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    agent_id BIGINT UNSIGNED NULL,
    status ENUM('queued','assigned','resolved','cancelled') NOT NULL DEFAULT 'queued',
    assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX conversation_assignments_status_idx (department_id, status, assigned_at),
    CONSTRAINT conversation_assignments_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT conversation_assignments_department_fk FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT conversation_assignments_agent_fk FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_event_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    chatbot_session_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX chatbot_event_logs_conversation_idx (conversation_id, created_at),
    INDEX chatbot_event_logs_store_event_idx (store_id, event_type, created_at),
    CONSTRAINT chatbot_event_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_event_logs_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    CONSTRAINT chatbot_event_logs_session_fk FOREIGN KEY (chatbot_session_id) REFERENCES chatbot_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_ai_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL DEFAULT 'openai',
    model VARCHAR(120) NULL,
    encrypted_settings LONGTEXT NULL,
    system_prompt TEXT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY chatbot_ai_settings_store_unique (store_id),
    CONSTRAINT chatbot_ai_settings_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_knowledge_base (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    category VARCHAR(120) NULL,
    question TEXT NULL,
    answer TEXT NOT NULL,
    tags_json JSON NULL,
    source_type ENUM('manual','file') NOT NULL DEFAULT 'manual',
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    file_mime_type VARCHAR(120) NULL,
    file_size BIGINT UNSIGNED NULL,
    status ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FULLTEXT KEY chatbot_kb_search_idx (title, question, answer),
    CONSTRAINT chatbot_kb_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_ai_conversation_insights (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NOT NULL,
    intent ENUM('purchase','support','complaint','order_status','invoice') NOT NULL DEFAULT 'support',
    sentiment ENUM('positive','neutral','negative') NOT NULL DEFAULT 'neutral',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    lead_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    summary TEXT NULL,
    suggested_replies_json JSON NULL,
    recommended_next_action VARCHAR(255) NULL,
    raw_payload_json JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX chatbot_ai_insights_conversation_idx (conversation_id, created_at),
    INDEX chatbot_ai_insights_store_intent_idx (store_id, intent, priority),
    CONSTRAINT chatbot_ai_insights_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_ai_insights_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_handover_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    chatbot_session_id BIGINT UNSIGNED NULL,
    conversation_id BIGINT UNSIGNED NULL,
    assigned_to BIGINT UNSIGNED NULL,
    reason TEXT NULL,
    status ENUM('queued','assigned','resolved','cancelled') NOT NULL DEFAULT 'queued',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX chatbot_handover_store_status_idx (store_id, status, created_at),
    CONSTRAINT chatbot_handover_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_handover_session_fk FOREIGN KEY (chatbot_session_id) REFERENCES chatbot_sessions(id) ON DELETE SET NULL,
    CONSTRAINT chatbot_handover_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    CONSTRAINT chatbot_handover_user_fk FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    flow_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    connection_source ENUM('meta_cloud_api','qr_web_session','whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NULL,
    keyword VARCHAR(190) NULL,
    status VARCHAR(80) NULL,
    response_time_ms INT UNSIGNED NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX chatbot_analytics_store_event_idx (store_id, event_type, created_at),
    CONSTRAINT chatbot_analytics_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT chatbot_analytics_flow_fk FOREIGN KEY (flow_id) REFERENCES chatbot_flows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_channel_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    provider_account_id VARCHAR(190) NOT NULL,
    display_name VARCHAR(190) NULL,
    status ENUM('pending','connected','disconnected','error') NOT NULL DEFAULT 'pending',
    encrypted_credentials LONGTEXT NULL,
    permissions_json JSON NULL,
    webhook_status ENUM('pending','verified','failed','disabled') NOT NULL DEFAULT 'pending',
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY omni_channel_unique (store_id, channel, provider_account_id),
    INDEX omni_channel_store_status_idx (store_id, channel, status),
    CONSTRAINT omni_channel_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    primary_name VARCHAR(190) NULL,
    primary_email VARCHAR(190) NULL,
    primary_phone VARCHAR(80) NULL,
    ai_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lifetime_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    tags_json JSON NULL,
    attributes_json JSON NULL,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX omni_customers_store_idx (store_id, primary_phone, primary_email),
    CONSTRAINT omni_customers_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT omni_customers_contact_fk FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_customer_identities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    omni_customer_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    external_id VARCHAR(190) NOT NULL,
    display_name VARCHAR(190) NULL,
    avatar_url TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY omni_identity_unique (store_id, channel, external_id),
    INDEX omni_identity_customer_idx (omni_customer_id, channel),
    CONSTRAINT omni_identity_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT omni_identity_customer_fk FOREIGN KEY (omni_customer_id) REFERENCES omni_customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    omni_customer_id BIGINT UNSIGNED NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    external_thread_id VARCHAR(190) NULL,
    customer_name VARCHAR(190) NULL,
    subject VARCHAR(190) NULL,
    status ENUM('open','pending','closed','spam') NOT NULL DEFAULT 'open',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    assigned_to BIGINT UNSIGNED NULL,
    bot_status ENUM('active','paused','handover') NOT NULL DEFAULT 'active',
    sentiment VARCHAR(40) NULL,
    intent VARCHAR(80) NULL,
    tags_json JSON NULL,
    notes TEXT NULL,
    last_message TEXT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX omni_conversations_store_idx (store_id, status, channel, last_message_at),
    INDEX omni_conversations_assignee_idx (assigned_to, status),
    CONSTRAINT omni_conversations_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT omni_conversations_customer_fk FOREIGN KEY (omni_customer_id) REFERENCES omni_customers(id) ON DELETE SET NULL,
    CONSTRAINT omni_conversations_assignee_fk FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    direction ENUM('inbound','outbound','internal_note') NOT NULL,
    provider_message_id VARCHAR(190) NULL,
    body TEXT NULL,
    attachment_json JSON NULL,
    voice_note_url TEXT NULL,
    status VARCHAR(60) NOT NULL DEFAULT 'received',
    ai_generated TINYINT(1) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY omni_message_provider_unique (channel, provider_message_id),
    INDEX omni_messages_conversation_idx (conversation_id, created_at),
    INDEX omni_messages_store_channel_idx (store_id, channel, created_at),
    CONSTRAINT omni_messages_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT omni_messages_conversation_fk FOREIGN KEY (conversation_id) REFERENCES omni_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_automation_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    trigger_channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NULL,
    trigger_event VARCHAR(120) NOT NULL,
    conditions_json JSON NULL,
    actions_json JSON NOT NULL,
    status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX omni_automation_store_idx (store_id, status, trigger_event),
    CONSTRAINT omni_automation_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_ai_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NULL,
    event_type VARCHAR(120) NOT NULL,
    intent VARCHAR(80) NULL,
    sentiment VARCHAR(40) NULL,
    confidence DECIMAL(5,2) NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX omni_ai_store_event_idx (store_id, event_type, created_at),
    CONSTRAINT omni_ai_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT omni_ai_conversation_fk FOREIGN KEY (conversation_id) REFERENCES omni_conversations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omni_webhook_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('whatsapp_cloud','whatsapp_qr','instagram','facebook','telegram','email','sms','live_chat') NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    signature VARCHAR(190) NULL,
    payload_json JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX omni_webhook_store_channel_idx (store_id, channel, received_at),
    CONSTRAINT omni_webhook_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO stores (id, name, slug, plan, status, created_at) VALUES (1, 'Default Store', 'default-store', 'pro', 'active', NOW());

CREATE TABLE IF NOT EXISTS role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(80) NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY role_permission_unique (role_key, permission_key),
    INDEX role_permissions_role_idx (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NULL,
    ip_address VARCHAR(60) NULL,
    user_agent VARCHAR(255) NULL,
    status ENUM('success','failed','blocked') NOT NULL DEFAULT 'success',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX login_logs_store_status_idx (store_id, status, created_at),
    INDEX login_logs_email_idx (email, created_at),
    CONSTRAINT login_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    CONSTRAINT login_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    notification_type VARCHAR(120) NOT NULL,
    channel ENUM('in_app','email','sms','webhook','slack') NOT NULL DEFAULT 'in_app',
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    status ENUM('queued','sent','failed','read') NOT NULL DEFAULT 'queued',
    payload_json JSON NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX notification_logs_store_status_idx (store_id, status, created_at),
    CONSTRAINT notification_logs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    CONSTRAINT notification_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    queue_name VARCHAR(120) NOT NULL DEFAULT 'default',
    job_type VARCHAR(120) NULL,
    payload_json JSON NULL,
    exception LONGTEXT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX failed_jobs_queue_idx (queue_name, failed_at),
    INDEX failed_jobs_store_idx (store_id, failed_at),
    CONSTRAINT failed_jobs_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS queue_monitoring_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    queue_name VARCHAR(120) NOT NULL,
    pending_count INT UNSIGNED NOT NULL DEFAULT 0,
    processing_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX queue_monitoring_queue_idx (queue_name, created_at),
    CONSTRAINT queue_monitoring_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
