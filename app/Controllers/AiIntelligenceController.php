<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\AiBusinessIntelligenceService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\TenantContext;

final class AiIntelligenceController
{
    private AiBusinessIntelligenceService $ai;

    public function __construct()
    {
        $this->ai = new AiBusinessIntelligenceService();
    }

    public function executive(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->executiveDashboard($this->storeId())]);
    }

    public function customerProfile(int $contactId): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->customerProfile($this->storeId(), $contactId)]);
    }

    public function rebuildCustomerProfiles(): void
    {
        Rbac::assert('analytics.view');
        $input = Request::input();
        Response::json(['data' => $this->ai->rebuildCustomerProfiles($this->storeId(), (int) ($input['limit'] ?? 200))]);
    }

    public function salesPrediction(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->salesPrediction($this->storeId())]);
    }

    public function campaignOptimization(?int $campaignId = null): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->campaignOptimization($this->storeId(), $campaignId)]);
    }

    public function conversationAnalysis(int $conversationId): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->conversationAnalysis($this->storeId(), $conversationId)]);
    }

    public function alerts(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->smartAlerts($this->storeId())]);
    }

    public function knowledgeLearning(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->knowledgeLearning($this->storeId())]);
    }

    public function automationIdeas(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->automationIdeas($this->storeId())]);
    }

    public function analytics2(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => $this->ai->analytics2($this->storeId())]);
    }

    public function enqueue(): void
    {
        Rbac::assert('analytics.view');
        $input = Request::input();
        Response::json(['data' => $this->ai->enqueueJob(
            $this->storeId(),
            (string) ($input['job_type'] ?? 'customer_profile_rebuild'),
            (array) ($input['payload'] ?? []),
            (int) ($input['priority'] ?? 5)
        )]);
    }

    public function processJobs(): void
    {
        Rbac::assert('analytics.view');
        $input = Request::input();
        Response::json(['data' => $this->ai->processQueuedJobs((int) ($input['limit'] ?? 20))]);
    }

    private function storeId(): int
    {
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        return $storeId;
    }
}
