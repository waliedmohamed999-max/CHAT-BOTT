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

ALTER TABLE stores
    ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS primary_color VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS secondary_color VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS custom_domain VARCHAR(190) NULL,
    ADD COLUMN IF NOT EXISTS login_background_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS support_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS privacy_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS terms_url VARCHAR(500) NULL,
    ADD INDEX IF NOT EXISTS stores_custom_domain_idx (custom_domain);

INSERT INTO platform_users (name, email, password_hash, role, status, created_at, updated_at)
SELECT name, email, password_hash, 'super_admin', 'active', NOW(), NOW()
FROM users
WHERE role = 'owner'
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status), updated_at = NOW();
