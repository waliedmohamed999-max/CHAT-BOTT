<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\MarketplaceService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class MarketplaceController
{
    private MarketplaceService $marketplace;

    public function __construct()
    {
        $this->marketplace = new MarketplaceService();
    }

    public function overview(): void
    {
        Response::json(['data' => $this->marketplace->overview($this->storeId())]);
    }

    public function catalog(): void
    {
        Response::json(['data' => $this->marketplace->catalog($this->storeId(), $_GET['type'] ?? null)]);
    }

    public function installed(): void
    {
        Response::json(['data' => $this->marketplace->installedApps($this->storeId())]);
    }

    public function install(int $appId): void
    {
        Rbac::assert('marketplace.manage');
        Response::json(['data' => $this->marketplace->install($this->storeId(), $appId)]);
    }

    public function uninstall(int $appId): void
    {
        Rbac::assert('marketplace.manage');
        Response::json(['data' => $this->marketplace->uninstall($this->storeId(), $appId)]);
    }

    public function apiKeys(): void
    {
        Response::json(['data' => $this->marketplace->apiKeys($this->storeId())]);
    }

    public function createApiKey(): void
    {
        Rbac::assert('developer.manage');
        Response::json(['data' => $this->marketplace->createApiKey($this->storeId(), Request::input())], 201);
    }

    public function createOAuthApp(): void
    {
        Rbac::assert('developer.manage');
        Response::json(['data' => $this->marketplace->createOAuthApp($this->storeId(), Request::input())], 201);
    }

    public function registerWebhook(): void
    {
        Rbac::assert('developer.manage');
        Response::json(['data' => $this->marketplace->registerWebhook($this->storeId(), Request::input())], 201);
    }

    public function pluginManifest(): void
    {
        Response::json(['data' => $this->marketplace->pluginManifest()]);
    }

    public function superAdminReview(): void
    {
        Rbac::assert('saas.admin');
        Response::json(['data' => $this->marketplace->superAdminReview()]);
    }

    private function storeId(): int
    {
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        return $storeId;
    }
}
