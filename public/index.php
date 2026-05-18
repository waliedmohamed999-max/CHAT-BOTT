<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Controllers\ApiController;
use MarketingCenter\Controllers\AuthController;
use MarketingCenter\Controllers\SystemController;
use MarketingCenter\Controllers\MarketingController;
use MarketingCenter\Controllers\WebhookController;
use MarketingCenter\Controllers\WhatsAppQrController;
use MarketingCenter\Controllers\WhatsAppSetupController;
use MarketingCenter\Controllers\ChatbotController;
use MarketingCenter\Controllers\OmnichannelController;
use MarketingCenter\Controllers\SaasController;
use MarketingCenter\Controllers\AiIntelligenceController;
use MarketingCenter\Controllers\MarketplaceController;
use MarketingCenter\Controllers\EnterpriseController;
use MarketingCenter\Controllers\AiCommerceOsController;
use MarketingCenter\Controllers\LaunchReadinessController;
use MarketingCenter\Controllers\PlatformRoadmapController;
use MarketingCenter\Controllers\SettingsController;
use MarketingCenter\Support\RateLimiter;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Security;
use MarketingCenter\Support\Env;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$vercelPath = $_GET['__mc_path'] ?? null;
if (is_string($vercelPath) && $vercelPath !== '') {
    $path = '/' . ltrim($vercelPath, '/');
} else {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir && str_starts_with($path, $scriptDir)) {
        $path = substr($path, strlen($scriptDir)) ?: '/';
    }
}

Security::applyHeaders();
RateLimiter::assertAllowed($_SERVER['REMOTE_ADDR'] ?? 'local');
Security::assertCsrfIfNeeded($method, $path);
Security::assertAuthenticatedIfNeeded($method, $path);

$auth = new AuthController();
$system = new SystemController();
$api = new ApiController();
$qr = new WhatsAppQrController();
$setup = new WhatsAppSetupController();
$chatbot = new ChatbotController();
$omni = new OmnichannelController();
$saas = new SaasController();
$aiBi = new AiIntelligenceController();
$marketplace = new MarketplaceController();
$enterprise = new EnterpriseController();
$commerceOs = new AiCommerceOsController();
$launchReadiness = new LaunchReadinessController();
$platformRoadmap = new PlatformRoadmapController();
$settings = new SettingsController();

try {
    if ($method === 'GET' && ($path === '/' || $path === '/login')) { $auth->showLogin(); return; }
    if ($method === 'GET' && $path === '/platform/login') { $auth->showPortalLogin('platform'); return; }
    if ($method === 'GET' && $path === '/store/login') { $auth->showPortalLogin('store'); return; }
    if ($method === 'GET' && $path === '/store/register') { Response::html('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><body style="font-family:Arial;padding:32px">إنشاء حساب متجر جديد سيتم تفعيله من إعدادات الاشتراكات. <a href="/store/login">العودة للدخول</a></body></html>'); return; }
    if ($method === 'GET' && $path === '/agent/login') { $auth->showPortalLogin('agent'); return; }
    if ($method === 'GET' && preg_match('#^/tenant/([a-zA-Z0-9_-]+)/login$#', $path, $m)) { $auth->showPortalLogin('tenant', $m[1]); return; }
    if ($method === 'GET' && $path === '/platform/dashboard') { (new MarketingController())->page('super-admin'); return; }
    if ($method === 'GET' && $path === '/dashboard') { (new MarketingController())->page('overview'); return; }
    if ($method === 'GET' && $path === '/inbox') { (new MarketingController())->page('inbox'); return; }
    if ($method === 'GET' && $path === '/crm/leads') { (new MarketingController())->page('contacts'); return; }
    if ($method === 'GET' && $path === '/billing') { (new MarketingController())->page('saas'); return; }
    if ($method === 'GET' && $path === '/reports') { (new MarketingController())->page('analytics'); return; }
    if ($method === 'GET' && $path === '/auth/callback') { Response::html('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><body>تم استقبال OAuth Callback. اربط المزود المطلوب من إعدادات الإنتاج.</body></html>'); return; }
    if ($method === 'POST' && $path === '/api/auth/login') { $auth->login(); return; }
    if ($method === 'POST' && $path === '/api/auth/platform/login') { $auth->portalLogin('platform'); return; }
    if ($method === 'POST' && $path === '/api/auth/store/login') { $auth->portalLogin('store'); return; }
    if ($method === 'POST' && $path === '/api/auth/agent/login') { $auth->portalLogin('agent'); return; }
    if ($method === 'POST' && $path === '/api/auth/tenant/login') { $auth->portalLogin('tenant'); return; }
    if ($method === 'POST' && $path === '/api/auth/logout') { $auth->logout(); return; }
    if ($method === 'GET' && $path === '/api/auth/me') { $auth->me(); return; }
    if ($method === 'POST' && $path === '/api/auth/forgot-password') { $auth->forgotPassword(); return; }
    if ($method === 'POST' && $path === '/api/auth/reset-password') { $auth->resetPassword(); return; }
    if ($method === 'POST' && $path === '/api/auth/verify-2fa') { $auth->verify2fa(); return; }
    if ($method === 'GET' && preg_match('#^/api/auth/login-branding/([a-zA-Z0-9_-]+)$#', $path, $m)) { $auth->loginBranding($m[1]); return; }
    if ($method === 'GET' && $path === '/api/auth/sessions') { $auth->sessions(); return; }
    if ($method === 'POST' && $path === '/api/auth/sessions/revoke') { $auth->revokeSession(); return; }
    if ($method === 'GET' && $path === '/api/system/queue-status') { $system->queueStatus(); return; }
    if ($method === 'POST' && $path === '/api/system/queue-snapshot') { $system->queueSnapshot(); return; }

    if ($method === 'GET' && preg_match('#^/marketing-center/settings/([a-z0-9-]+)/?$#', $path, $m)) {
        (new MarketingController())->page('settings-' . $m[1]);
        return;
    }

    if ($method === 'GET' && preg_match('#^/marketing-center/?([^/]*)#', $path, $m)) {
        (new MarketingController())->page($m[1] ?: 'overview');
        return;
    }

    if ($method === 'POST' && $path === '/api/meta/connect') { $api->metaConnect(); return; }
    if ($method === 'GET' && $path === '/api/meta/callback') { $api->metaCallback(); return; }
    if ($method === 'POST' && $path === '/api/meta/disconnect') { $api->disconnect(); return; }
    if ($method === 'POST' && $path === '/api/meta/sync-assets') { $api->syncMetaAssets(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp/accounts') { $api->whatsappAccounts(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp/phone-numbers') { $api->phoneNumbers(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp/phone-numbers/primary') { $api->setPrimaryPhone(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp/templates') { $api->templates(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp/templates') { $api->createTemplate(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp/templates/sync') { $api->syncTemplates(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp/send-test') { $api->sendTest(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp/webhook') { (new WebhookController())->verify(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp/webhook') { (new WebhookController())->receive(); return; }
    if ($method === 'GET' && $path === '/api/webhooks/whatsapp') { (new WebhookController())->verify(); return; }
    if ($method === 'POST' && $path === '/api/webhooks/whatsapp') { (new WebhookController())->receive(); return; }
    if ($method === 'GET' && $path === '/api/campaigns') { $api->listCampaigns(); return; }
    if ($method === 'POST' && $path === '/api/campaigns') { $api->createCampaign(); return; }
    if ($method === 'GET' && preg_match('#^/api/campaigns/(\d+)$#', $path, $m)) { $api->campaign((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/campaigns/(\d+)/launch$#', $path, $m)) { $api->launchCampaign((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/campaigns/(\d+)/pause$#', $path, $m)) { $api->pauseCampaign((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/campaigns/(\d+)/resume$#', $path, $m)) { $api->resumeCampaign((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/campaigns/queue/process') { $api->queueProcess(); return; }
    if ($method === 'POST' && preg_match('#^/api/campaigns/(\d+)/queue/process$#', $path, $m)) { $api->queueProcess((int) $m[1]); return; }
    if ($method === 'GET' && preg_match('#^/api/campaigns/(\d+)/progress$#', $path, $m)) { $api->queueProgress((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/campaigns/(\d+)/retry-failed$#', $path, $m)) { $api->retryFailed((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/automation-revenue/overview') { $api->automationRevenueOverview(); return; }
    if ($method === 'GET' && $path === '/api/automation-revenue/templates') { $api->automationRevenueTemplates(); return; }
    if ($method === 'GET' && $path === '/api/automation-revenue/flows') { $api->automationRevenueFlows(); return; }
    if ($method === 'POST' && $path === '/api/automation-revenue/flows') { $api->createAutomationRevenueFlow(); return; }
    if ($method === 'POST' && preg_match('#^/api/automation-revenue/templates/([a-z0-9_-]+)/install$#', $path, $m)) { $api->installAutomationTemplate((string) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/automation-revenue/trigger') { $api->triggerAutomationRevenue(); return; }
    if ($method === 'POST' && $path === '/api/automation-revenue/process') { $api->processAutomationRevenue(); return; }
    if ($method === 'GET' && $path === '/api/analytics') { $api->analytics(); return; }
    if ($method === 'GET' && $path === '/api/saas/context') { $saas->context(); return; }
    if ($method === 'POST' && $path === '/api/saas/switch-workspace') { $saas->switchWorkspace(); return; }
    if ($method === 'GET' && $path === '/api/saas/plans') { $saas->plans(); return; }
    if ($method === 'GET' && $path === '/api/saas/subscription') { $saas->subscription(); return; }
    if ($method === 'POST' && $path === '/api/saas/subscription') { $saas->updateSubscription(); return; }
    if ($method === 'GET' && $path === '/api/saas/usage') { $saas->usage(); return; }
    if ($method === 'GET' && $path === '/api/saas/team') { $saas->team(); return; }
    if ($method === 'POST' && $path === '/api/saas/team/invite') { $saas->invite(); return; }
    if ($method === 'GET' && $path === '/api/saas/invoices') { $saas->invoices(); return; }
    if ($method === 'GET' && $path === '/api/saas/payment-gateways') { $saas->paymentGateways(); return; }
    if ($method === 'POST' && $path === '/api/saas/payment-gateways') { $saas->savePaymentGateway(); return; }
    if ($method === 'GET' && $path === '/api/saas/white-label') { $saas->whiteLabel(); return; }
    if ($method === 'POST' && $path === '/api/saas/white-label') { $saas->saveWhiteLabel(); return; }
    if ($method === 'GET' && $path === '/api/super-admin/overview') { $saas->superAdmin(); return; }
    if ($method === 'GET' && $path === '/api/ai-bi/executive') { $aiBi->executive(); return; }
    if ($method === 'GET' && preg_match('#^/api/ai-bi/customers/(\d+)$#', $path, $m)) { $aiBi->customerProfile((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/customers/rebuild') { $aiBi->rebuildCustomerProfiles(); return; }
    if ($method === 'GET' && $path === '/api/ai-bi/sales-prediction') { $aiBi->salesPrediction(); return; }
    if ($method === 'GET' && $path === '/api/ai-bi/campaign-optimization') { $aiBi->campaignOptimization(); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/campaign-optimization') { $aiBi->campaignOptimization(); return; }
    if ($method === 'POST' && preg_match('#^/api/ai-bi/campaigns/(\d+)/optimize$#', $path, $m)) { $aiBi->campaignOptimization((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/ai-bi/conversations/(\d+)/analyze$#', $path, $m)) { $aiBi->conversationAnalysis((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/ai-bi/alerts') { $aiBi->alerts(); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/knowledge/learn') { $aiBi->knowledgeLearning(); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/automation/ideas') { $aiBi->automationIdeas(); return; }
    if ($method === 'GET' && $path === '/api/ai-bi/analytics2') { $aiBi->analytics2(); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/jobs') { $aiBi->enqueue(); return; }
    if ($method === 'POST' && $path === '/api/ai-bi/jobs/process') { $aiBi->processJobs(); return; }
    if ($method === 'GET' && $path === '/api/marketplace/overview') { $marketplace->overview(); return; }
    if ($method === 'GET' && $path === '/api/marketplace/apps') { $marketplace->catalog(); return; }
    if ($method === 'GET' && $path === '/api/marketplace/installed') { $marketplace->installed(); return; }
    if ($method === 'POST' && preg_match('#^/api/marketplace/apps/(\d+)/install$#', $path, $m)) { $marketplace->install((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/marketplace/apps/(\d+)/uninstall$#', $path, $m)) { $marketplace->uninstall((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/developer/api-keys') { $marketplace->apiKeys(); return; }
    if ($method === 'POST' && $path === '/api/developer/api-keys') { $marketplace->createApiKey(); return; }
    if ($method === 'POST' && $path === '/api/developer/oauth-apps') { $marketplace->createOAuthApp(); return; }
    if ($method === 'POST' && $path === '/api/developer/webhooks') { $marketplace->registerWebhook(); return; }
    if ($method === 'GET' && $path === '/api/developer/plugin-manifest') { $marketplace->pluginManifest(); return; }
    if ($method === 'GET' && $path === '/api/super-admin/marketplace-review') { $marketplace->superAdminReview(); return; }
    if ($method === 'GET' && $path === '/api/enterprise/overview') { $enterprise->overview(); return; }
    if ($method === 'GET' && $path === '/api/enterprise/regions') { $enterprise->regions(); return; }
    if ($method === 'POST' && $path === '/api/enterprise/regions') { $enterprise->saveRegion(); return; }
    if ($method === 'POST' && $path === '/api/enterprise/messaging-providers') { $enterprise->saveMessagingProvider(); return; }
    if ($method === 'POST' && $path === '/api/enterprise/security-policy') { $enterprise->saveSecurityPolicy(); return; }
    if ($method === 'GET' && $path === '/api/enterprise/compliance') { $enterprise->compliance(); return; }
    if ($method === 'GET' && $path === '/api/ai-commerce-os/overview') { $commerceOs->overview(); return; }
    if ($method === 'GET' && $path === '/api/ai-commerce-os/agents') { $commerceOs->agents(); return; }
    if ($method === 'POST' && preg_match('#^/api/ai-commerce-os/agents/([a-z0-9_-]+)/activate$#', $path, $m)) { $commerceOs->activateAgent((string) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/ai-commerce-os/memory') { $commerceOs->memory(); return; }
    if ($method === 'POST' && $path === '/api/ai-commerce-os/memory') { $commerceOs->storeMemory(); return; }
    if ($method === 'GET' && $path === '/api/ai-commerce-os/decisions') { $commerceOs->decisions(); return; }
    if ($method === 'POST' && $path === '/api/ai-commerce-os/generate') { $commerceOs->generateExperience(); return; }
    if ($method === 'GET' && $path === '/api/ai-commerce-os/command-center') { $commerceOs->commandCenter(); return; }
    if ($method === 'POST' && $path === '/api/contacts/import') { $api->importContacts(); return; }
    if ($method === 'GET' && $path === '/api/inbox') { $api->inbox(); return; }
    if ($method === 'POST' && $path === '/api/inbox/reply') { $api->inboxReply(); return; }
    if ($method === 'POST' && preg_match('#^/api/inbox/(\d+)$#', $path, $m)) { $api->updateConversation((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/setup-checklist') { $api->checklist(); return; }
    if ($method === 'GET' && $path === '/api/launch-readiness') { $launchReadiness->overview(); return; }
    if ($method === 'GET' && $path === '/api/platform-roadmap') { $platformRoadmap->overview(); return; }
    if ($method === 'GET' && $path === '/api/development-roadmap') { $platformRoadmap->overview(); return; }
    if ($method === 'GET' && $path === '/api/development-execution') { $platformRoadmap->executionOverview(); return; }
    if ($method === 'POST' && $path === '/api/development-execution/tasks/run') { $platformRoadmap->runExecutionTask(); return; }
    if ($method === 'POST' && $path === '/api/development-execution/tasks/retry') { $platformRoadmap->runExecutionTask(); return; }
    if ($method === 'POST' && $path === '/api/development-execution/tasks/run-all') { $platformRoadmap->runAutoFixes(); return; }
    if ($method === 'GET' && $path === '/api/settings/overview') { $settings->overview(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && $path === '/api/settings/general') { $settings->updateGeneral(); return; }
    if ($method === 'GET' && $path === '/api/settings/whatsapp') { $settings->whatsapp(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && $path === '/api/settings/whatsapp') { $settings->updateWhatsapp(); return; }
    if ($method === 'GET' && $path === '/api/settings/campaign-limits') { $settings->campaignLimits(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && $path === '/api/settings/campaign-limits') { $settings->updateCampaignLimits(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && $path === '/api/settings/quick-replies') { $settings->updateQuickReplies(); return; }
    if ($method === 'GET' && $path === '/api/settings/users') { $settings->users(); return; }
    if ($method === 'POST' && $path === '/api/settings/users') { $settings->createUser(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/users/(\d+)$#', $path, $m)) { $settings->updateUser((int) $m[1]); return; }
    if ($method === 'DELETE' && preg_match('#^/api/settings/users/(\d+)$#', $path, $m)) { $settings->deleteUser((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/roles') { $settings->roles(); return; }
    if ($method === 'POST' && $path === '/api/settings/roles') { $settings->createRole(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/roles/(\d+)$#', $path, $m)) { $settings->updateRole((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/permissions') { $settings->permissions(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/roles/(\d+)/permissions$#', $path, $m)) { $settings->updateRolePermissions((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/companies') { $settings->companies(); return; }
    if ($method === 'POST' && $path === '/api/settings/companies') { $settings->createCompany(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/companies/(\d+)$#', $path, $m)) { $settings->updateCompany((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/stores') { $settings->stores(); return; }
    if ($method === 'POST' && $path === '/api/settings/stores') { $settings->createStore(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/stores/(\d+)$#', $path, $m)) { $settings->updateStore((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/departments') { $settings->departments(); return; }
    if ($method === 'POST' && $path === '/api/settings/departments') { $settings->createDepartment(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && preg_match('#^/api/settings/departments/(\d+)$#', $path, $m)) { $settings->updateDepartment((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/security') { $settings->security(); return; }
    if (in_array($method, ['PUT', 'POST'], true) && $path === '/api/settings/security') { $settings->updateSecurity(); return; }
    if ($method === 'GET' && $path === '/api/settings/api-keys') { $settings->apiKeys(); return; }
    if ($method === 'POST' && $path === '/api/settings/api-keys') { $settings->createApiKey(); return; }
    if ($method === 'DELETE' && preg_match('#^/api/settings/api-keys/(\d+)$#', $path, $m)) { $settings->deleteApiKey((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/settings/webhooks') { $settings->webhooks(); return; }
    if ($method === 'POST' && $path === '/api/settings/webhooks/test') { $settings->testWebhook(); return; }
    if ($method === 'GET' && $path === '/api/settings/documents') { $settings->documents(); return; }
    if ($method === 'POST' && $path === '/api/settings/documents/upload') { $settings->uploadDocument(); return; }
    if ($method === 'GET' && $path === '/api/settings/logs') { $settings->logs(); return; }
    if ($method === 'GET' && $path === '/api/settings/health') { $settings->health(); return; }
    if ($method === 'GET' && $path === '/api/settings/launch-readiness') { $settings->launchReadiness(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/session/create') { $qr->create(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-qr/session/status') { $qr->status(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-qr/session/qr') { $qr->qr(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/session/disconnect') { $qr->disconnect(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/session/reconnect') { $qr->reconnect(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-qr/chats') { $qr->chats(); return; }
    if ($method === 'GET' && preg_match('#^/api/whatsapp-qr/chats/([^/]+)/messages$#', $path, $m)) { $qr->messages(rawurldecode($m[1])); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/send-message') { $qr->sendMessage(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/send-media') { $qr->sendMedia(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-qr/contacts') { $qr->contacts(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-qr/events') { $qr->events(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-qr/bridge-webhook') { $qr->bridgeWebhook(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/profile') { $setup->profile(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/profile') { $setup->saveProfile(); return; }
    if ($method === 'PUT' && $path === '/api/whatsapp-setup/profile') { $setup->saveProfile(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/documents/upload') { $setup->uploadDocument(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/documents') { $setup->documents(); return; }
    if ($method === 'DELETE' && preg_match('#^/api/whatsapp-setup/documents/(\d+)$#', $path, $m)) { $setup->deleteDocument((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/method/select') { $setup->selectMethod(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/meta/connect') { $setup->metaConnect(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/meta/callback') { $setup->metaCallback(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/meta/disconnect') { $setup->metaDisconnect(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/qr/create') { $setup->qrCreate(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/qr/status') { $setup->qrStatus(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/qr/code') { $setup->qrCode(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/qr/disconnect') { $setup->qrDisconnect(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/test/send-message') { $setup->testSendMessage(); return; }
    if ($method === 'POST' && $path === '/api/whatsapp-setup/test/webhook') { $setup->testWebhook(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/test/logs') { $setup->testLogs(); return; }
    if ($method === 'GET' && $path === '/api/whatsapp-setup/readiness') { $setup->readiness(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/overview') { $chatbot->overview(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/builder') { $chatbot->builder(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/diagnostics/routes') { $chatbot->diagnostics(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/flows') { $chatbot->createFlow(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/flows') { $chatbot->flows(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/flows/current') { $chatbot->currentFlow(); return; }
    if ($method === 'GET' && preg_match('#^/api/chatbot/flows/(\d+)$#', $path, $m)) { $chatbot->flow((int) $m[1]); return; }
    if ($method === 'PUT' && preg_match('#^/api/chatbot/flows/(\d+)$#', $path, $m)) { $chatbot->updateFlow((int) $m[1]); return; }
    if ($method === 'DELETE' && preg_match('#^/api/chatbot/flows/(\d+)$#', $path, $m)) { $chatbot->deleteFlow((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/flows/(\d+)/activate$#', $path, $m)) { $chatbot->activateFlow((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/chatbot/nodes') { $chatbot->createNode(); return; }
    if ($method === 'PUT' && preg_match('#^/api/chatbot/nodes/(\d+)$#', $path, $m)) { $chatbot->updateNode((int) $m[1]); return; }
    if ($method === 'DELETE' && preg_match('#^/api/chatbot/nodes/(\d+)$#', $path, $m)) { $chatbot->deleteNode((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/chatbot/edges') { $chatbot->createEdge(); return; }
    if ($method === 'DELETE' && preg_match('#^/api/chatbot/edges/(\d+)$#', $path, $m)) { $chatbot->deleteEdge((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/chatbot/departments') { $chatbot->departments(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/departments') { $chatbot->saveDepartment(); return; }
    if ($method === 'PUT' && preg_match('#^/api/chatbot/departments/(\d+)$#', $path, $m)) { $chatbot->saveDepartment((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/chatbot/keywords') { $chatbot->createKeyword(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/keywords') { $chatbot->keywords(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/auto-replies') { $chatbot->createAutoReply(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/auto-replies') { $chatbot->autoReplies(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/ai/reply') { $chatbot->aiReply(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/ai/classify') { $chatbot->classify(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/ai/settings') { $chatbot->aiSettings(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/ai/settings') { $chatbot->saveAiSettings(); return; }
    if ($method === 'GET' && $path === '/api/chatbot/knowledge-base') { $chatbot->knowledgeBase(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/knowledge-base') { $chatbot->saveKnowledge(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/knowledge-base/upload') { $chatbot->uploadKnowledge(); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/conversations/(\d+)/ai/analyze$#', $path, $m)) { $chatbot->analyzeConversation((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/chatbot/handover') { $chatbot->handover(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/resume') { $chatbot->resume(); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/conversations/(\d+)/pause$#', $path, $m)) { $chatbot->pauseConversation((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/conversations/(\d+)/resume$#', $path, $m)) { $chatbot->resumeConversation((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/conversations/(\d+)/transfer$#', $path, $m)) { $chatbot->transferConversation((int) $m[1]); return; }
    if ($method === 'POST' && preg_match('#^/api/chatbot/conversations/(\d+)/end$#', $path, $m)) { $chatbot->endConversation((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/chatbot/webhook/process') { $chatbot->processWebhook(); return; }
    if ($method === 'POST' && $path === '/api/chatbot/process-message') { $chatbot->processMessage(); return; }
    if ($method === 'GET' && preg_match('#^/api/chatbot/preview/(\d+)$#', $path, $m)) { $chatbot->preview((int) $m[1]); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/overview') { $omni->overview(); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/channels') { $omni->channels(); return; }
    if ($method === 'POST' && $path === '/api/omnichannel/channels/connect') { $omni->connectChannel(); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/conversations') { $omni->conversations(); return; }
    if ($method === 'GET' && preg_match('#^/api/omnichannel/conversations/(\d+)/messages$#', $path, $m)) { $omni->messages((int) $m[1]); return; }
    if ($method === 'POST' && $path === '/api/omnichannel/reply') { $omni->reply(); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/customer-360') { $omni->customer360(); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/analytics') { $omni->analytics(); return; }
    if ($method === 'GET' && $path === '/api/omnichannel/live-chat/config') { $omni->liveChatConfig(); return; }
    if ($method === 'POST' && preg_match('#^/api/omnichannel/webhooks/([a-z_]+)$#', $path, $m)) { $omni->webhook($m[1]); return; }

    Response::json(['error' => 'not_found', 'path' => $path], 404);
} catch (Throwable $e) {
    $payload = ['error' => 'server_error'];
    if (filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)) {
        $payload['detail'] = $e->getMessage();
    }
    Response::json($payload, 500);
}
