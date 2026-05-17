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
