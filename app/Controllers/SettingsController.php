<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\PlatformControlCenterService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class SettingsController
{
    private PlatformControlCenterService $settings;

    public function __construct()
    {
        $this->settings = new PlatformControlCenterService();
    }

    public function overview(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())]);
    }

    public function updateGeneral(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateGeneral($this->storeId(), Request::input()));
    }

    public function whatsapp(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['whatsapp'] ?? []]);
    }

    public function updateWhatsapp(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateWhatsapp($this->storeId(), Request::input()));
    }

    public function campaignLimits(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['campaign_limits'] ?? []]);
    }

    public function updateCampaignLimits(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateCampaignLimits($this->storeId(), Request::input()));
    }

    public function updateQuickReplies(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateQuickReplies($this->storeId(), Request::input()));
    }

    public function users(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['users'] ?? []]);
    }

    public function createUser(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->createUser($this->storeId(), Request::input()), 201);
    }

    public function updateUser(int $id): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateUser($this->storeId(), $id, Request::input()));
    }

    public function deleteUser(int $id): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->deleteUser($this->storeId(), $id));
    }

    public function roles(): void
    {
        Rbac::assert('workspace.manage');
        $overview = $this->settings->overview($this->storeId());
        Response::json(['data' => ['roles' => $overview['roles'] ?? [], 'permissions' => $overview['permissions'] ?? []]]);
    }

    public function createRole(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->createRole(Request::input()), 201);
    }

    public function updateRole(int $id): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateRole($id, Request::input()));
    }

    public function permissions(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['permissions'] ?? []]);
    }

    public function updateRolePermissions(int $id): void
    {
        $this->guardSettings();
        $input = Request::input();
        $roleKey = (string) ($input['role_key'] ?? $id);
        $permissions = $input['permissions'] ?? [];
        if (is_string($permissions)) {
            $permissions = array_filter(array_map('trim', explode(',', $permissions)));
        }
        $this->respond(fn (): array => $this->settings->updateRolePermissions($roleKey, (array) $permissions));
    }

    public function companies(): void
    {
        Rbac::assert('saas.admin');
        Response::json(['data' => $this->settings->overview($this->storeId())['companies'] ?? []]);
    }

    public function createCompany(): void
    {
        Rbac::assert('saas.admin');
        $this->respond(fn (): array => $this->settings->createCompany(Request::input()), 201);
    }

    public function updateCompany(int $id): void
    {
        Rbac::assert('saas.admin');
        $this->respond(fn (): array => $this->settings->updateCompany($id, Request::input()));
    }

    public function stores(): void
    {
        Rbac::assert('saas.admin');
        Response::json(['data' => $this->settings->overview($this->storeId())['stores'] ?? []]);
    }

    public function createStore(): void
    {
        Rbac::assert('saas.admin');
        $this->respond(fn (): array => $this->settings->createStore(Request::input()), 201);
    }

    public function updateStore(int $id): void
    {
        Rbac::assert('saas.admin');
        $this->respond(fn (): array => $this->settings->updateStore($id, Request::input()));
    }

    public function departments(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['departments'] ?? []]);
    }

    public function createDepartment(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->createDepartment($this->storeId(), Request::input()), 201);
    }

    public function updateDepartment(int $id): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateDepartment($this->storeId(), $id, Request::input()));
    }

    public function security(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['security'] ?? []]);
    }

    public function updateSecurity(): void
    {
        $this->guardSettings();
        $this->respond(fn (): array => $this->settings->updateSecurity($this->storeId(), Request::input()));
    }

    public function apiKeys(): void
    {
        Rbac::assert('developer.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['api_keys'] ?? []]);
    }

    public function createApiKey(): void
    {
        Rbac::assert('developer.manage');
        $this->respond(fn (): array => $this->settings->createApiKey($this->storeId(), Request::input()), 201);
    }

    public function deleteApiKey(int $id): void
    {
        Rbac::assert('developer.manage');
        $this->respond(fn (): array => $this->settings->deleteApiKey($this->storeId(), $id));
    }

    public function webhooks(): void
    {
        Rbac::assert('developer.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['webhooks'] ?? []]);
    }

    public function testWebhook(): void
    {
        Rbac::assert('developer.manage');
        $this->respond(fn (): array => $this->settings->testWebhook($this->storeId(), Request::input()));
    }

    public function documents(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->overview($this->storeId())['documents'] ?? []]);
    }

    public function uploadDocument(): void
    {
        $this->guardSettings();
        $file = $_FILES['file'] ?? $_FILES['document'] ?? null;
        if (!is_array($file)) {
            Response::json(['error' => 'missing_file'], 422);
            return;
        }
        $type = (string) (Request::input()['document_type'] ?? 'general');
        $this->respond(fn (): array => $this->settings->uploadDocument($this->storeId(), $file, $type), 201);
    }

    public function logs(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->logs($this->storeId())]);
    }

    public function health(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->health($this->storeId())]);
    }

    public function launchReadiness(): void
    {
        Rbac::assert('workspace.manage');
        Response::json(['data' => $this->settings->launch($this->storeId())]);
    }

    private function guardSettings(): void
    {
        Rbac::assert('workspace.manage');
    }

    private function storeId(): int
    {
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        return $storeId;
    }

    /**
     * @param callable(): array $callback
     */
    private function respond(callable $callback, int $status = 200): void
    {
        try {
            Response::json(['data' => $callback(), 'message' => 'تم حفظ الإعدادات بنجاح'], $status);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }
}
