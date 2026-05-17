<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\AiCommerceOsService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class AiCommerceOsController
{
    private AiCommerceOsService $os;

    public function __construct()
    {
        $this->os = new AiCommerceOsService();
    }

    public function overview(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->overview($this->storeId())]);
    }

    public function agents(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->agents($this->storeId())]);
    }

    public function activateAgent(string $agentKey): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->activateAgent($this->storeId(), $agentKey, Request::input())]);
    }

    public function memory(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->memoryOverview($this->storeId())]);
    }

    public function storeMemory(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->storeMemory($this->storeId(), Request::input())], 201);
    }

    public function decisions(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->decisionEngine($this->storeId())]);
    }

    public function generateExperience(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->generateExperience($this->storeId(), Request::input())], 201);
    }

    public function commandCenter(): void
    {
        Rbac::assert('commerce_os.manage');
        Response::json(['data' => $this->os->commandCenter($this->storeId())]);
    }

    private function storeId(): int
    {
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        return $storeId;
    }
}
