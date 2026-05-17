<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Support\Database;

$limit = isset($argv[1]) ? max(1, min(100, (int) $argv[1])) : 20;
$processed = 0;
$failed = 0;
$setupReady = true;

try {
    $jobs = Database::pdo()->query("SELECT * FROM ai_os_events WHERE status = 'queued' AND (available_at IS NULL OR available_at <= NOW()) ORDER BY priority ASC, id ASC LIMIT {$limit}")->fetchAll();
    foreach ($jobs as $job) {
        $jobId = (int) $job['id'];
        try {
            Database::pdo()->prepare("UPDATE ai_os_events SET status = 'processing', attempts = attempts + 1, updated_at = NOW() WHERE id = ?")->execute([$jobId]);
            Database::pdo()->prepare("UPDATE ai_os_events SET status = 'completed', processed_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$jobId]);
            $processed++;
        } catch (Throwable $e) {
            Database::pdo()->prepare("UPDATE ai_os_events SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?")->execute([$e->getMessage(), $jobId]);
            $failed++;
        }
    }
} catch (Throwable $e) {
    $setupReady = false;
}

echo json_encode(['processed' => $processed, 'failed' => $failed, 'setup_ready' => $setupReady], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
