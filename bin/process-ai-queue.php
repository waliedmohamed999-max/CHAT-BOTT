<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Services\AiBusinessIntelligenceService;

$limit = isset($argv[1]) ? (int) $argv[1] : 20;
$result = (new AiBusinessIntelligenceService())->processQueuedJobs($limit);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
