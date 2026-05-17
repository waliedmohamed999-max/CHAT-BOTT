<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\SaasPlatformService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class SaasController
{
    private SaasPlatformService $saas;

    public function __construct()
    {
        $this->saas = new SaasPlatformService();
    }

    public function context(): void
    {
        Response::json(['data' => $this->saas->context()]);
    }

    public function switchWorkspace(): void
    {
        $input = Request::input();
        Response::json(['data' => $this->saas->switchWorkspace((int) ($input['store_id'] ?? TenantContext::storeId()), isset($input['workspace_id']) ? (int) $input['workspace_id'] : null)]);
    }

    public function plans(): void
    {
        Response::json(['data' => $this->saas->plans()]);
    }

    public function subscription(): void
    {
        Response::json(['data' => $this->saas->subscription(TenantContext::storeId())]);
    }

    public function updateSubscription(): void
    {
        Rbac::assert('billing.manage');
        try {
            Response::json(['data' => $this->saas->upsertSubscription(TenantContext::storeId(), Request::input())]);
        } catch (\Throwable $e) {
            Response::json($this->error($e), 422);
        }
    }

    public function usage(): void
    {
        Response::json(['data' => $this->saas->usage(TenantContext::storeId())]);
    }

    public function team(): void
    {
        Response::json(['data' => $this->saas->teamMembers(TenantContext::storeId())]);
    }

    public function invite(): void
    {
        Rbac::assert('workspace.manage');
        try {
            Response::json(['id' => $this->saas->inviteMember(TenantContext::storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json($this->error($e), 422);
        }
    }

    public function invoices(): void
    {
        Response::json(['data' => $this->saas->invoices(TenantContext::storeId())]);
    }

    public function paymentGateways(): void
    {
        Response::json(['data' => $this->saas->paymentGateways(TenantContext::storeId())]);
    }

    public function savePaymentGateway(): void
    {
        Rbac::assert('billing.manage');
        try {
            Response::json(['id' => $this->saas->savePaymentGateway(TenantContext::storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json($this->error($e), 422);
        }
    }

    public function whiteLabel(): void
    {
        Response::json(['data' => $this->saas->whiteLabel(TenantContext::storeId())]);
    }

    public function saveWhiteLabel(): void
    {
        Rbac::assert('workspace.manage');
        try {
            Response::json(['data' => $this->saas->saveWhiteLabel(TenantContext::storeId(), Request::input())]);
        } catch (\Throwable $e) {
            Response::json($this->error($e), 422);
        }
    }

    public function superAdmin(): void
    {
        Rbac::assert('saas.admin');
        Response::json(['data' => $this->saas->superAdminOverview()]);
    }

    private function error(\Throwable $e): array
    {
        $message = $e->getMessage();
        if (str_contains($message, 'Unknown database') || str_contains($message, 'Base table or view not found')) {
            return ['error' => 'whatsapp_setup_not_ready', 'details' => 'Import database/schema.sql or database/production_integration_migration.sql before running SaaS platform modules.'];
        }
        return ['error' => $message];
    }
}
