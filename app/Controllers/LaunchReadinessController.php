<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\LaunchReadinessService;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\TenantContext;

final class LaunchReadinessController
{
    public function overview(): void
    {
        Response::json([
            'data' => (new LaunchReadinessService())->overview(TenantContext::storeId()),
        ]);
    }
}
