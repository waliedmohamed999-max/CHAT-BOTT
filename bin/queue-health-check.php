<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Services\QueueMonitoringService;
use MarketingCenter\Support\Env;

$storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
$service = new QueueMonitoringService();
$status = $service->snapshot($storeId);

echo "Queue Health Check\n";
echo "==================\n";
echo 'Connection: ' . $status['connection'] . PHP_EOL;
echo 'Ready: ' . (!empty($status['ready']) ? 'yes' : 'no') . PHP_EOL;
echo 'Redis configured: ' . (!empty($status['redis_configured']) ? 'yes' : 'no') . PHP_EOL;
echo 'Database fallback: ' . (!empty($status['database_fallback']) ? 'yes' : 'no') . PHP_EOL;
echo 'Pending: ' . (int) ($status['totals']['pending'] ?? 0) . PHP_EOL;
echo 'Processing: ' . (int) ($status['totals']['processing'] ?? 0) . PHP_EOL;
echo 'Failed: ' . (int) ($status['totals']['failed'] ?? 0) . PHP_EOL;

foreach ($status['queues'] as $queue) {
    echo '- ' . $queue['queue_name'] . ': pending=' . (int) $queue['pending_count'] . ', processing=' . (int) $queue['processing_count'] . ', failed=' . (int) $queue['failed_count'] . PHP_EOL;
}

exit(!empty($status['ready']) ? 0 : 1);
