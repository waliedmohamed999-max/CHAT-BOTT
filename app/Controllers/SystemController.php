<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\QueueMonitoringService;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class SystemController
{
    public function queueStatus(): void
    {
        Rbac::assert('analytics.view');
        Response::json([
            'data' => (new QueueMonitoringService())->status(TenantContext::storeId()),
        ]);
    }

    public function queueSnapshot(): void
    {
        Rbac::assert('analytics.view');
        Response::json([
            'data' => (new QueueMonitoringService())->snapshot(TenantContext::storeId()),
        ]);
    }
}
