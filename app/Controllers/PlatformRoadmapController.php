<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\DevelopmentExecutionService;
use MarketingCenter\Services\PlatformDevelopmentRoadmapService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class PlatformRoadmapController
{
    public function overview(): void
    {
        Response::json([
            'data' => (new PlatformDevelopmentRoadmapService())->overview(TenantContext::storeId()),
        ]);
    }

    public function executionOverview(): void
    {
        Rbac::assert('developer.manage');
        Response::json([
            'data' => (new DevelopmentExecutionService())->dashboard(TenantContext::storeId()),
        ]);
    }

    public function runExecutionTask(): void
    {
        Rbac::assert('developer.manage');
        $input = Request::input();
        Response::json([
            'data' => (new DevelopmentExecutionService())->runTask(
                TenantContext::storeId(),
                (string) ($input['task_key'] ?? '')
            ),
        ]);
    }

    public function runAutoFixes(): void
    {
        Rbac::assert('developer.manage');
        Response::json([
            'data' => (new DevelopmentExecutionService())->runAutoFixes(TenantContext::storeId()),
        ]);
    }
}
