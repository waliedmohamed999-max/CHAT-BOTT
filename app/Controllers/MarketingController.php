<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\MetaOAuthService;
use MarketingCenter\Services\ConnectionService;
use MarketingCenter\Services\DevelopmentExecutionService;
use MarketingCenter\Services\LaunchReadinessService;
use MarketingCenter\Services\PlatformControlCenterService;
use MarketingCenter\Services\PlatformDevelopmentRoadmapService;
use MarketingCenter\Services\SaasPlatformService;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\TenantContext;

final class MarketingController
{
    public function page(string $page = 'overview'): void
    {
        $allowed = ['overview', 'omnichannel', 'setup-checklist', 'launch-readiness', 'platform-roadmap', 'development-roadmap', 'whatsapp-setup-center', 'connect-meta', 'whatsapp-setup', 'whatsapp-qr', 'chatbot-builder', 'campaign-builder', 'templates', 'contacts', 'inbox', 'automation', 'social', 'analytics', 'ai-intelligence', 'marketplace', 'enterprise', 'ai-commerce-os', 'settings', 'saas', 'super-admin'];
        $page = $page === 'launch-readiness' ? 'setup-checklist' : $page;
        $page = $page === 'development-roadmap' ? 'platform-roadmap' : $page;
        $page = in_array($page, $allowed, true) ? $page : 'overview';
        $storeId = TenantContext::storeId();
        TenantContext::assertStoreAccess($storeId);
        $data = $this->dashboardData($storeId);
        $connectUrl = (new MetaOAuthService())->authorizationUrl($storeId);
        $connection = null;
        $phones = [];
        $checklist = [];
        $qrSession = ['session_status' => 'disconnected'];
        $setupProfile = ['readiness_score' => 0];
        $setupReadiness = ['score' => 0, 'status' => 'غير جاهز', 'items' => []];
        $chatbotOverview = ['auto_replies' => 0, 'active_flows' => 0, 'handover_count' => 0, 'top_keywords' => []];
        $chatbotBuilder = ['flow' => [], 'departments' => [], 'preview' => [], 'health' => ['score' => 0, 'items' => []], 'flows' => []];
        $omnichannelOverview = ['channels' => [], 'open_conversations' => 0, 'unassigned' => 0, 'ai_resolved' => 0, 'first_response_time' => '0د'];
        $omnichannelConversations = [];
        $customer360 = [];
        $saasContext = [];
        $superAdminOverview = [];
        $aiExecutive = ['hot_leads' => 0, 'churn_risk_customers' => 0, 'conversation_volume' => 0, 'potential_loss' => 0, 'growth_opportunities' => [], 'top_problems' => [], 'alerts' => [], 'sales_forecast' => ['next_30_days_revenue' => 0, 'winning_campaign_probability' => 0]];
        $marketplaceOverview = ['featured_apps' => [], 'installed' => [], 'developer' => [], 'stats' => ['apps' => 0, 'installed' => 0, 'api_keys' => 0, 'webhooks' => 0], 'categories' => []];
        $enterpriseOverview = ['readiness_score' => 0, 'infrastructure' => ['regions' => []], 'scalability' => [], 'messaging_gateway' => [], 'security' => [], 'ai' => [], 'crm' => [], 'analytics' => [], 'voice' => [], 'automation' => [], 'devops' => [], 'admin' => []];
        $commerceOsOverview = ['agents' => [], 'memory' => [], 'decision_engine' => ['decisions' => []], 'command_center' => [], 'commerce_os' => [], 'generated_experiences' => [], 'voice_ai' => [], 'marketplace' => [], 'infrastructure' => [], 'self_improving' => [], 'future_ready' => [], 'readiness_score' => 0];
        $launchReadiness = ['score' => 0, 'status' => 'غير جاهز', 'items' => [], 'sections' => [], 'operations' => [], 'environment' => []];
        $platformRoadmap = ['name' => 'Platform Development Roadmap', 'progress' => 0, 'current_phase' => [], 'completed_sections' => [], 'in_development_sections' => [], 'upcoming_sections' => [], 'open_issues' => [], 'phase_tests' => [], 'phases' => []];
        $developmentExecution = ['stats' => [], 'findings' => [], 'tasks' => [], 'logs' => [], 'recommendations' => [], 'config' => []];
        $controlCenter = ['system_status' => [], 'general' => [], 'whatsapp' => [], 'campaign_limits' => [], 'quick_replies' => [], 'users' => [], 'roles' => [], 'permissions' => [], 'companies' => [], 'stores' => [], 'departments' => [], 'subscriptions' => [], 'security' => [], 'api_keys' => [], 'webhooks' => [], 'documents' => [], 'notifications' => [], 'logs' => [], 'branding' => [], 'ai' => [], 'backup' => [], 'launch' => []];
        $loginPortalSessions = [];
        $loginPortalAttempts = [];
        $loginPortalStores = [];
        try {
            $connectionService = new ConnectionService();
            $connection = $connectionService->activeConnection($storeId);
            $checklist = $connectionService->checklist($storeId);
            $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_phone_numbers WHERE store_id = ? ORDER BY is_primary DESC, id DESC');
            $stmt->execute([$storeId]);
            $phones = $stmt->fetchAll();
            $qrSession = (new \MarketingCenter\Services\WhatsAppQrService())->status($storeId);
            $setupService = new \MarketingCenter\Services\WhatsAppSetupService();
            $setupProfile = $setupService->profile($storeId);
            $setupReadiness = $setupService->readiness($storeId);
            $chatbotService = new \MarketingCenter\Services\ChatbotService();
            $chatbotOverview = $chatbotService->overview($storeId);
            $chatbotBuilder = $chatbotService->builder($storeId);
            $omniService = new \MarketingCenter\Services\OmnichannelService();
            $omnichannelOverview = $omniService->overview($storeId);
            $omnichannelConversations = $omniService->conversations($storeId);
            $customer360 = $omniService->customer360($storeId);
            $saas = new SaasPlatformService();
            $saasContext = $saas->context();
            $superAdminOverview = $saas->superAdminOverview();
            $aiExecutive = (new \MarketingCenter\Services\AiBusinessIntelligenceService())->executiveDashboard($storeId);
            $marketplaceOverview = (new \MarketingCenter\Services\MarketplaceService())->overview($storeId);
            $enterpriseOverview = (new \MarketingCenter\Services\EnterprisePlatformService())->overview($storeId);
            $commerceOsOverview = (new \MarketingCenter\Services\AiCommerceOsService())->overview($storeId);
            $launchReadiness = (new LaunchReadinessService())->overview($storeId);
            $platformRoadmap = (new PlatformDevelopmentRoadmapService())->overview($storeId);
            $developmentExecution = (new DevelopmentExecutionService())->dashboard($storeId);
            $controlCenter = (new PlatformControlCenterService())->overview($storeId);
            $portalService = new \MarketingCenter\Services\AuthPortalService();
            $loginPortalSessions = $portalService->sessions();
            $loginPortalAttempts = $portalService->loginAttempts(40);
            $loginPortalStores = Database::pdo()->query('SELECT id, name, slug, status FROM stores ORDER BY id DESC LIMIT 50')->fetchAll();
        } catch (\Throwable) {
            $checklist = [];
            try {
                $launchReadiness = (new LaunchReadinessService())->overview($storeId);
                $platformRoadmap = (new PlatformDevelopmentRoadmapService())->overview($storeId);
                $developmentExecution = (new DevelopmentExecutionService())->dashboard($storeId);
                $controlCenter = (new PlatformControlCenterService())->overview($storeId);
            } catch (\Throwable) {
                $launchReadiness = ['score' => 0, 'status' => 'غير جاهز', 'items' => [], 'sections' => [], 'operations' => [], 'environment' => []];
                $platformRoadmap = ['name' => 'Platform Development Roadmap', 'progress' => 0, 'current_phase' => [], 'completed_sections' => [], 'in_development_sections' => [], 'upcoming_sections' => [], 'open_issues' => [], 'phase_tests' => [], 'phases' => []];
                $developmentExecution = ['stats' => [], 'findings' => [], 'tasks' => [], 'logs' => [], 'recommendations' => [], 'config' => []];
                $controlCenter = ['system_status' => [], 'general' => [], 'whatsapp' => [], 'campaign_limits' => [], 'quick_replies' => [], 'users' => [], 'roles' => [], 'permissions' => [], 'companies' => [], 'stores' => [], 'departments' => [], 'subscriptions' => [], 'security' => [], 'api_keys' => [], 'webhooks' => [], 'documents' => [], 'notifications' => [], 'logs' => [], 'branding' => [], 'ai' => [], 'backup' => [], 'launch' => []];
            }
        }

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/marketing-center.php';
        Response::html((string) ob_get_clean());
    }

    private function dashboardData(int $storeId): array
    {
        try {
            $pdo = Database::pdo();
            return [
                'campaigns' => (int) $pdo->query('SELECT COUNT(*) FROM campaigns WHERE store_id = ' . $storeId)->fetchColumn(),
                'sent' => (int) $pdo->query("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = {$storeId} AND provider_status IN ('sent','delivered','read')")->fetchColumn(),
                'success' => (int) $pdo->query("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = {$storeId} AND provider_status IN ('delivered','read')")->fetchColumn(),
                'failed' => (int) $pdo->query("SELECT COUNT(*) FROM campaign_messages cm JOIN campaigns c ON c.id = cm.campaign_id WHERE c.store_id = {$storeId} AND provider_status = 'failed'")->fetchColumn(),
                'contacts' => (int) $pdo->query('SELECT COUNT(*) FROM contacts WHERE store_id = ' . $storeId)->fetchColumn(),
            ];
        } catch (\Throwable) {
            return ['campaigns' => 0, 'sent' => 0, 'success' => 0, 'failed' => 0, 'contacts' => 0];
        }
    }
}
