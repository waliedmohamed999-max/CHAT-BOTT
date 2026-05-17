<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Services\PlatformDevelopmentRoadmapService;
use MarketingCenter\Support\TenantContext;

$roadmap = (new PlatformDevelopmentRoadmapService())->overview(TenantContext::storeId());
$phase = $roadmap['current_phase'] ?? [];

echo "Core System Foundation Check\n";
echo "============================\n";
echo 'Current phase: ' . ($phase['title_ar'] ?? 'unknown') . PHP_EOL;
echo 'Status: ' . ($phase['status_label'] ?? 'unknown') . PHP_EOL;
echo 'Launch score: ' . (int) ($phase['launch_score'] ?? 0) . "%\n\n";

foreach (($phase['checks'] ?? []) as $check) {
    $mark = !empty($check['ready']) ? '[OK]' : (!empty($check['critical']) ? '[BLOCKER]' : '[WARN]');
    echo $mark . ' ' . ($check['label'] ?? '') . ' - ' . ($check['description'] ?? '') . PHP_EOL;
}

if (!empty($phase['open_issues'])) {
    echo "\nOpen issues before moving to Phase 2:\n";
    foreach ($phase['open_issues'] as $issue) {
        echo '- ' . strtoupper((string) ($issue['severity'] ?? 'warning')) . ': ' . ($issue['label'] ?? '') . ' - ' . ($issue['message'] ?? '') . PHP_EOL;
    }
}

echo "\nRequired production env for Phase 1:\n";
echo "- Generate a production template: php bin/generate-production-env.php https://your-real-domain.com\n";
echo "- Verify the final .env file: php bin/verify-production-env.php .env\n";
echo "- Run the full phase gate: php bin/production-preflight.php .env\n";
echo "- APP_ENV=production\n";
echo "- APP_DEBUG=false\n";
echo "- AUTH_ENFORCE=true\n";
echo "- CSRF_ENFORCE=true\n";
echo "- RATE_LIMIT_PER_MINUTE=120 or another positive production limit\n";
echo "- PUBLIC_APP_URL=https://your-real-domain.com\n";
echo "- JWT_SECRET and ENCRYPTION_KEY: random values with 32+ characters\n";
echo "- META_APP_ID, META_APP_SECRET, META_VERIFY_TOKEN, and META_WEBHOOK_SECRET: real Meta values\n";
echo "- QUEUE_REDIS_URL=redis://host:6379/0\n";
echo "\nPhase 2 remains blocked until all critical Phase 1 checks are production ready.\n";

exit(($phase['status'] ?? '') === 'production_ready' ? 0 : 1);
