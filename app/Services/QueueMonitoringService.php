<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class QueueMonitoringService
{
    public function status(int $storeId): array
    {
        $queues = [
            $this->campaignQueue($storeId),
            $this->automationQueue($storeId),
            $this->aiQueue($storeId),
            $this->failedJobsQueue($storeId),
        ];

        $totals = [
            'pending' => array_sum(array_column($queues, 'pending_count')),
            'processing' => array_sum(array_column($queues, 'processing_count')),
            'failed' => array_sum(array_column($queues, 'failed_count')),
        ];

        return [
            'connection' => $this->connection(),
            'redis_configured' => Env::get('QUEUE_REDIS_URL') !== null,
            'redis_reachable' => $this->connection() === 'redis' ? $this->redisReachable() : null,
            'database_fallback' => $this->connection() === 'database',
            'ready' => $this->ready(),
            'totals' => $totals,
            'queues' => $queues,
        ];
    }

    public function snapshot(int $storeId): array
    {
        $status = $this->status($storeId);
        foreach ($status['queues'] as $queue) {
            $stmt = Database::pdo()->prepare('INSERT INTO queue_monitoring_snapshots (store_id, queue_name, pending_count, processing_count, failed_count, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $storeId,
                $queue['queue_name'],
                (int) $queue['pending_count'],
                (int) $queue['processing_count'],
                (int) $queue['failed_count'],
            ]);
        }

        return $status;
    }

    public function recordFailedJob(?int $storeId, string $queueName, string $jobType, array $payload, \Throwable $exception): void
    {
        try {
            $stmt = Database::pdo()->prepare('INSERT INTO failed_jobs (store_id, queue_name, job_type, payload_json, exception, failed_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $storeId,
                $queueName,
                $jobType,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $exception->getMessage(),
            ]);
        } catch (\Throwable) {
            error_log('Marketing Center failed job logging failed: ' . $jobType);
        }
    }

    public function ready(): bool
    {
        if (!$this->tablesReady(['failed_jobs', 'queue_monitoring_snapshots'])) {
            return false;
        }

        if ($this->connection() === 'database') {
            return true;
        }

        return Env::get('QUEUE_REDIS_URL') !== null && $this->redisReachable();
    }

    private function campaignQueue(int $storeId): array
    {
        return [
            'queue_name' => 'campaigns',
            'pending_count' => $this->count("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = ? AND cm.queue_status IN ('pending','retry')", [$storeId]),
            'processing_count' => $this->count("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = ? AND cm.queue_status = 'processing'", [$storeId]),
            'failed_count' => $this->count("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = ? AND cm.queue_status = 'failed'", [$storeId]),
        ];
    }

    private function automationQueue(int $storeId): array
    {
        return [
            'queue_name' => 'automations',
            'pending_count' => $this->count("SELECT COUNT(*) FROM automation_runs WHERE store_id = ? AND status = 'queued'", [$storeId]),
            'processing_count' => $this->count("SELECT COUNT(*) FROM automation_runs WHERE store_id = ? AND status = 'running'", [$storeId]),
            'failed_count' => $this->count("SELECT COUNT(*) FROM automation_runs WHERE store_id = ? AND status = 'failed'", [$storeId]),
        ];
    }

    private function aiQueue(int $storeId): array
    {
        return [
            'queue_name' => 'ai',
            'pending_count' => $this->count("SELECT COUNT(*) FROM ai_queue_jobs WHERE store_id = ? AND status = 'queued'", [$storeId]),
            'processing_count' => $this->count("SELECT COUNT(*) FROM ai_queue_jobs WHERE store_id = ? AND status = 'processing'", [$storeId]),
            'failed_count' => $this->count("SELECT COUNT(*) FROM ai_queue_jobs WHERE store_id = ? AND status = 'failed'", [$storeId]),
        ];
    }

    private function failedJobsQueue(int $storeId): array
    {
        return [
            'queue_name' => 'failed_jobs',
            'pending_count' => 0,
            'processing_count' => 0,
            'failed_count' => $this->count('SELECT COUNT(*) FROM failed_jobs WHERE store_id = ?', [$storeId]),
        ];
    }

    private function connection(): string
    {
        $connection = strtolower((string) Env::get('QUEUE_CONNECTION', 'database'));
        return in_array($connection, ['database', 'redis'], true) ? $connection : 'database';
    }

    private function redisReachable(): bool
    {
        $url = Env::get('QUEUE_REDIS_URL');
        if (!$url) {
            return false;
        }

        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        $port = (int) ($parts['port'] ?? 6379);
        if ($host === '' || $port <= 0) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'redis'));
        $target = ($scheme === 'rediss' ? 'tls://' : 'tcp://') . $host . ':' . $port;
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($target, $errno, $errstr, 0.5);
        if (!is_resource($socket)) {
            return false;
        }

        fclose($socket);
        return true;
    }

    private function count(string $sql, array $params): int
    {
        try {
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function tablesReady(array $tables): bool
    {
        foreach ($tables as $table) {
            if (!preg_match('/^[a-z0-9_]+$/', $table)) {
                return false;
            }

            try {
                $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
                $stmt->execute([$table]);
                if ((int) $stmt->fetchColumn() === 0) {
                    return false;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }
}
