<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Services\CampaignQueueService;
use MarketingCenter\Services\QueueMonitoringService;
use MarketingCenter\Support\Env;

$storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
$campaignId = isset($argv[1]) ? (int) $argv[1] : null;

try {
    $result = (new CampaignQueueService())->process($storeId, $campaignId);
    (new QueueMonitoringService())->snapshot($storeId);
    echo json_encode([
        'ok' => true,
        'store_id' => $storeId,
        'campaign_id' => $campaignId,
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    (new QueueMonitoringService())->recordFailedJob($storeId, 'campaigns', 'process_campaign_queue', ['campaign_id' => $campaignId], $e);
    error_log('Marketing Center queue failed: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
