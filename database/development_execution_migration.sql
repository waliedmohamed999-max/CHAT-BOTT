CREATE TABLE IF NOT EXISTS development_execution_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    task_key VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    priority VARCHAR(30) NOT NULL DEFAULT 'normal',
    payload_json JSON NULL,
    result_json JSON NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY development_execution_tasks_store_key_unique (store_id, task_key),
    INDEX development_execution_tasks_status_idx (status, priority)
);

CREATE TABLE IF NOT EXISTS development_execution_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    task_key VARCHAR(120) NULL,
    level VARCHAR(30) NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context_json JSON NULL,
    created_at TIMESTAMP NULL,
    INDEX development_execution_logs_store_created_idx (store_id, created_at),
    INDEX development_execution_logs_task_idx (task_key, created_at)
);

CREATE TABLE IF NOT EXISTS development_execution_findings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    finding_key VARCHAR(160) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'missing',
    severity VARCHAR(40) NOT NULL DEFAULT 'warning',
    fixable TINYINT(1) NOT NULL DEFAULT 0,
    recommendation TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY development_execution_findings_store_key_unique (store_id, finding_key),
    INDEX development_execution_findings_status_idx (status, severity)
);