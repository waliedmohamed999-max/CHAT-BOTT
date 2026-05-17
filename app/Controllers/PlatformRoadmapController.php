<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\PlatformDevelopmentRoadmapService;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\TenantContext;

final class PlatformRoadmapController
{
    public function overview(): void
    {
        Response::json([
            'data' => (new PlatformDevelopmentRoadmapService())->overview(TenantContext::storeId()),
        ]);
    }
}
