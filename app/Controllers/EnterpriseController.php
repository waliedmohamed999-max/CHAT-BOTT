<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\EnterprisePlatformService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class EnterpriseController
{
    private EnterprisePlatformService $enterprise;

    public function __construct()
    {
        $this->enterprise = new EnterprisePlatformService();
    }

    public function overview(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->overview($this->storeId())]);
    }

    public function regions(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->regions()]);
    }

    public function saveRegion(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->upsertRegion(Request::input())]);
    }

    public function saveMessagingProvider(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->saveMessagingProvider($this->storeId(), Request::input())]);
    }

    public function saveSecurityPolicy(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->saveSecurityPolicy($this->storeId(), Request::input())]);
    }

    public function compliance(): void
    {
        Rbac::assert('enterprise.manage');
        Response::json(['data' => $this->enterprise->complianceCenter($this->storeId())]);
    }

    private function storeId(): int
    {
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        return $storeId;
    }
}
