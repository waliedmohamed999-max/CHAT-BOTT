<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\CampaignService;
use MarketingCenter\Services\CampaignQueueService;
use MarketingCenter\Services\AnalyticsService;
use MarketingCenter\Services\AutomationRevenueService;
use MarketingCenter\Services\ConnectionService;
use MarketingCenter\Services\MetaOAuthService;
use MarketingCenter\Services\WhatsAppCloudApiService;
use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\Validator;

final class ApiController
{
    public function metaConnect(): void
    {
        Rbac::assert('meta.connect');
        Response::json(['redirect_url' => (new MetaOAuthService())->authorizationUrl((int) Env::get('DEFAULT_STORE_ID', '1'))]);
    }

    public function metaCallback(): void
    {
        $state = (string) ($_GET['state'] ?? '');
        [$token, $storeId] = array_pad(explode(':', $state, 2), 2, Env::get('DEFAULT_STORE_ID', '1'));
        if (!hash_equals($_SESSION['meta_oauth_state'] ?? '', $token)) {
            Response::json(['error' => 'invalid_oauth_state'], 422);
            return;
        }
        (new MetaOAuthService())->exchangeCode((string) ($_GET['code'] ?? ''), (int) $storeId, $_GET);
        header('Location: ' . Env::get('APP_URL', '') . '/marketing-center/connect-meta?connected=1');
    }

    public function disconnect(): void
    {
        Rbac::assert('meta.connect');
        Database::pdo()->prepare('UPDATE meta_connections SET token_status = ?, disconnected_at = NOW() WHERE store_id = ?')->execute(['revoked', (int) Env::get('DEFAULT_STORE_ID', '1')]);
        AuditLogger::record('meta.disconnected', (int) Env::get('DEFAULT_STORE_ID', '1'), Rbac::userId());
        Response::json(['ok' => true]);
    }

    public function listCampaigns(): void
    {
        $stmt = Database::pdo()->prepare('SELECT id, name, channel, campaign_type, status, scheduled_at, launched_at, estimated_cost FROM campaigns WHERE store_id = ? ORDER BY id DESC LIMIT 100');
        $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function createCampaign(): void
    {
        Rbac::assert('campaign.create');
        $data = Request::input();
        try {
            $id = (new CampaignService())->create($data, (int) Env::get('DEFAULT_STORE_ID', '1'), Rbac::userId());
            Response::json(['id' => $id], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function launchCampaign(int $id): void
    {
        Rbac::assert('campaign.launch');
        try {
            Response::json((new CampaignService())->launch($id));
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function pauseCampaign(int $id): void
    {
        Database::pdo()->prepare('UPDATE campaigns SET status = ?, updated_at = NOW() WHERE id = ?')->execute(['paused', $id]);
        AuditLogger::record('campaign.paused', (int) Env::get('DEFAULT_STORE_ID', '1'), Rbac::userId(), 'campaign', $id);
        Response::json(['ok' => true]);
    }

    public function resumeCampaign(int $id): void
    {
        Database::pdo()->prepare('UPDATE campaigns SET status = ?, updated_at = NOW() WHERE id = ?')->execute(['queued', $id]);
        AuditLogger::record('campaign.resumed', (int) Env::get('DEFAULT_STORE_ID', '1'), Rbac::userId(), 'campaign', $id);
        Response::json(['ok' => true]);
    }

    public function templates(): void
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_templates WHERE store_id = ? ORDER BY updated_at DESC LIMIT 100');
        $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function createTemplate(): void
    {
        Rbac::assert('templates.manage');
        $data = Request::input();
        $errors = Validator::required($data, ['name', 'category', 'body']);
        if ($errors) {
            Response::json(['errors' => $errors], 422);
            return;
        }
        try {
            $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
            $connection = new ConnectionService();
            $token = $connection->accessToken($storeId);
            $waba = Database::pdo()->query('SELECT * FROM whatsapp_business_accounts WHERE store_id = ' . $storeId . ' ORDER BY id DESC LIMIT 1')->fetch();
            if (!$waba) {
                throw new \RuntimeException('waba_not_connected');
            }
            $payload = [
                'name' => preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $data['name'])),
                'category' => Validator::enum(strtoupper((string) $data['category']), ['MARKETING', 'UTILITY', 'AUTHENTICATION'], 'invalid_template_category'),
                'language' => $data['language'] ?? 'en_US',
                'components' => $data['components'] ?? [['type' => 'BODY', 'text' => (string) $data['body']]],
            ];
            $meta = (new WhatsAppCloudApiService())->createTemplate($waba['waba_id'], $token, $payload);
            $stmt = Database::pdo()->prepare('INSERT INTO whatsapp_templates (store_id, waba_id, meta_template_id, name, category, language, status, body, components_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), body = VALUES(body), components_json = VALUES(components_json), updated_at = NOW()');
            $stmt->execute([$storeId, $waba['id'], $meta['id'] ?? null, $payload['name'], $payload['category'], $payload['language'], strtolower($meta['status'] ?? 'pending'), $data['body'], json_encode($payload['components'])]);
            Response::json(['id' => Database::pdo()->lastInsertId(), 'meta' => $meta], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function sendTest(): void
    {
        $data = Request::input();
        $errors = Validator::required($data, ['to', 'template_name']);
        if ($errors) {
            Response::json(['errors' => $errors], 422);
            return;
        }
        try {
            $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
            $connection = new ConnectionService();
            $token = $connection->accessToken($storeId);
            $phone = !empty($data['phone_number_id'])
                ? ['phone_number_id' => $data['phone_number_id'], 'display_phone_number' => $data['phone_number_id']]
                : $connection->primaryPhone($storeId);
            $templateStmt = Database::pdo()->prepare('SELECT * FROM whatsapp_templates WHERE store_id = ? AND name = ? AND status = ? LIMIT 1');
            $templateStmt->execute([$storeId, $data['template_name'], 'approved']);
            if (!$templateStmt->fetch()) {
                throw new \RuntimeException('template_not_approved');
            }
            $result = (new WhatsAppCloudApiService())->sendTemplate($phone['phone_number_id'], $token, Validator::phone((string) $data['to']), $data['template_name'], $data['language'] ?? 'en_US', $data['components'] ?? []);
            Database::pdo()->prepare('INSERT INTO messages (direction, provider_message_id, sender_phone, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute(['outbound', $result['messages'][0]['id'] ?? null, $phone['display_phone_number'] ?? $phone['phone_number_id'], 'Test template: ' . $data['template_name'], 'sent', json_encode($result)]);
            AuditLogger::record('whatsapp.test_sent', $storeId, Rbac::userId());
            Response::json($result);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function whatsappAccounts(): void
    {
        $stmt = Database::pdo()->prepare('SELECT id, waba_id, business_name, verification_status, currency, timezone_id FROM whatsapp_business_accounts WHERE store_id = ? ORDER BY id DESC');
        $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function phoneNumbers(): void
    {
        $stmt = Database::pdo()->prepare('SELECT id, phone_number_id, display_phone_number, verified_name, quality_rating, messaging_limit, webhook_status, status FROM whatsapp_phone_numbers WHERE store_id = ? ORDER BY id DESC');
        $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function setPrimaryPhone(): void
    {
        $data = Request::input();
        $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
        if (empty($data['phone_number_id'])) {
            Response::json(['error' => 'phone_number_id_required'], 422);
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE whatsapp_phone_numbers SET is_primary = 0 WHERE store_id = ?')->execute([$storeId]);
        $pdo->prepare('UPDATE whatsapp_phone_numbers SET is_primary = 1, updated_at = NOW() WHERE store_id = ? AND phone_number_id = ?')->execute([$storeId, $data['phone_number_id']]);
        Response::json(['ok' => true]);
    }

    public function syncMetaAssets(): void
    {
        try {
            $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
            $connectionService = new ConnectionService();
            $connection = $connectionService->activeConnection($storeId);
            if (!$connection) {
                throw new \RuntimeException('meta_not_connected');
            }
            $assets = (new MetaOAuthService())->syncOwnedWhatsAppAssets($storeId, (int) $connection['id'], $connectionService->accessToken($storeId), $connection['business_id']);
            Response::json(['synced' => count($assets)]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function syncTemplates(): void
    {
        try {
            $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
            $token = (new ConnectionService())->accessToken($storeId);
            $wabas = Database::pdo()->query('SELECT * FROM whatsapp_business_accounts WHERE store_id = ' . $storeId)->fetchAll();
            $synced = 0;
            $api = new WhatsAppCloudApiService();
            foreach ($wabas as $waba) {
                $synced += $api->storeSyncedTemplates($storeId, (int) $waba['id'], $waba['waba_id'], $token);
            }
            Response::json(['synced' => $synced]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function campaign(int $id): void
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM campaigns WHERE store_id = ? AND id = ?');
        $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1'), $id]);
        $campaign = $stmt->fetch();
        Response::json($campaign ? ['data' => $campaign] : ['error' => 'not_found'], $campaign ? 200 : 404);
    }

    public function importContacts(): void
    {
        $data = Request::input();
        $rows = $data['contacts'] ?? [];
        if (!is_array($rows)) {
            Response::json(['error' => 'contacts_array_required'], 422);
            return;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO contacts (store_id, name, phone, country, tags_json, opt_in_status, opt_in_source, opted_in_at, source, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), tags_json = VALUES(tags_json), opt_in_status = VALUES(opt_in_status), updated_at = NOW()');
        $imported = 0;
        foreach ($rows as $row) {
            if (empty($row['phone'])) {
                continue;
            }
            $optIn = ($row['opt_in_status'] ?? 'unknown') === 'opted_in' ? 'opted_in' : 'unknown';
            $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1'), $row['name'] ?? null, $row['phone'], $row['country'] ?? null, json_encode($row['tags'] ?? []), $optIn, $row['opt_in_source'] ?? null, $optIn === 'opted_in' ? date('Y-m-d H:i:s') : null, $row['source'] ?? 'import']);
            $imported++;
        }
        Response::json(['imported' => $imported]);
    }

    public function inbox(): void
    {
        try {
            $stmt = Database::pdo()->prepare("SELECT c.id, c.status, c.tags_json, c.notes, c.assigned_to, c.last_message_at, ct.name contact_name, ct.phone, cs.status bot_status, cs.current_node_key current_flow_node, cs.bot_paused, d.name department_name, d.slug department_slug, ca.status assignment_status, ai.intent customer_intent, ai.sentiment, ai.priority ai_priority, ai.lead_score, ai.summary ai_summary, ai.suggested_replies_json, ai.recommended_next_action FROM conversations c LEFT JOIN contacts ct ON ct.id = c.contact_id LEFT JOIN chatbot_sessions cs ON cs.conversation_id = c.id LEFT JOIN conversation_assignments ca ON ca.conversation_id = c.id AND ca.status IN ('queued','assigned') LEFT JOIN departments d ON d.id = ca.department_id LEFT JOIN chatbot_ai_conversation_insights ai ON ai.id = (SELECT ai2.id FROM chatbot_ai_conversation_insights ai2 WHERE ai2.conversation_id = c.id ORDER BY ai2.id DESC LIMIT 1) WHERE c.store_id = ? ORDER BY c.last_message_at DESC LIMIT 100");
            $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
            Response::json(['data' => $stmt->fetchAll()]);
        } catch (\Throwable) {
            try {
                $stmt = Database::pdo()->prepare('SELECT c.id, c.status, c.tags_json, c.last_message_at, ct.name contact_name, ct.phone FROM conversations c LEFT JOIN contacts ct ON ct.id = c.contact_id WHERE c.store_id = ? ORDER BY c.last_message_at DESC LIMIT 100');
                $stmt->execute([(int) Env::get('DEFAULT_STORE_ID', '1')]);
                Response::json(['data' => $stmt->fetchAll()]);
            } catch (\Throwable) {
                Response::json(['data' => []]);
            }
        }
    }

    public function inboxReply(): void
    {
        Rbac::assert('inbox.reply');
        $data = Request::input();
        if (empty($data['conversation_id']) || empty($data['body'])) {
            Response::json(['error' => 'conversation_id_and_body_required'], 422);
            return;
        }
        try {
            $storeId = (int) Env::get('DEFAULT_STORE_ID', '1');
            $conversation = Database::pdo()->prepare('SELECT c.*, ct.phone FROM conversations c LEFT JOIN contacts ct ON ct.id = c.contact_id WHERE c.id = ? AND c.store_id = ?');
            $conversation->execute([(int) $data['conversation_id'], $storeId]);
            $row = $conversation->fetch();
            if (!$row) {
                throw new \RuntimeException('conversation_not_found');
            }
            $connection = new ConnectionService();
            $phone = $connection->primaryPhone($storeId);
            $result = (new WhatsAppCloudApiService())->sendText($phone['phone_number_id'], $connection->accessToken($storeId), Validator::phone($row['phone']), trim((string) $data['body']));
            $stmt = Database::pdo()->prepare('INSERT INTO messages (conversation_id, direction, provider_message_id, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([(int) $data['conversation_id'], 'outbound', $result['messages'][0]['id'] ?? null, trim((string) $data['body']), 'sent', json_encode($result)]);
            Response::json(['id' => Database::pdo()->lastInsertId(), 'status' => 'sent']);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        $data = Request::input();
        $stmt = Database::pdo()->prepare('UPDATE conversations SET assigned_to = COALESCE(?, assigned_to), tags_json = COALESCE(?, tags_json), notes = COALESCE(?, notes), updated_at = NOW() WHERE id = ? AND store_id = ?');
        $stmt->execute([
            $data['assigned_to'] ?? null,
            isset($data['tags']) ? json_encode((array) $data['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $data['notes'] ?? null,
            $conversationId,
            (int) Env::get('DEFAULT_STORE_ID', '1'),
        ]);
        Response::json(['ok' => true]);
    }

    public function queueProcess(?int $campaignId = null): void
    {
        Response::json((new CampaignQueueService())->process((int) Env::get('DEFAULT_STORE_ID', '1'), $campaignId));
    }

    public function queueProgress(int $campaignId): void
    {
        Response::json(['data' => (new CampaignQueueService())->progress($campaignId)]);
    }

    public function retryFailed(int $campaignId): void
    {
        Response::json(['retried' => (new CampaignQueueService())->retryFailed($campaignId)]);
    }

    public function automationRevenueOverview(): void
    {
        Response::json(['data' => (new AutomationRevenueService())->overview((int) Env::get('DEFAULT_STORE_ID', '1'))]);
    }

    public function automationRevenueTemplates(): void
    {
        Response::json(['data' => (new AutomationRevenueService())->templates()]);
    }

    public function automationRevenueFlows(): void
    {
        Response::json(['data' => (new AutomationRevenueService())->flows((int) Env::get('DEFAULT_STORE_ID', '1'))]);
    }

    public function installAutomationTemplate(string $templateKey): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => (new AutomationRevenueService())->installTemplate((int) Env::get('DEFAULT_STORE_ID', '1'), $templateKey)], 201);
        } catch (\Throwable $e) {
            Response::json($this->automationError($e), 422);
        }
    }

    public function createAutomationRevenueFlow(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => (new AutomationRevenueService())->saveFlow((int) Env::get('DEFAULT_STORE_ID', '1'), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json($this->automationError($e), 422);
        }
    }

    public function triggerAutomationRevenue(): void
    {
        try {
            Response::json((new AutomationRevenueService())->trigger((int) Env::get('DEFAULT_STORE_ID', '1'), Request::input()));
        } catch (\Throwable $e) {
            Response::json($this->automationError($e), 422);
        }
    }

    public function processAutomationRevenue(): void
    {
        Response::json((new AutomationRevenueService())->processDue((int) Env::get('DEFAULT_STORE_ID', '1')));
    }

    public function checklist(): void
    {
        Response::json(['data' => (new ConnectionService())->checklist((int) Env::get('DEFAULT_STORE_ID', '1'))]);
    }

    public function analytics(): void
    {
        Rbac::assert('analytics.view');
        Response::json(['data' => (new AnalyticsService())->summary((int) Env::get('DEFAULT_STORE_ID', '1'))]);
    }

    private function automationError(\Throwable $e): array
    {
        $message = $e->getMessage();
        if (str_contains($message, 'Unknown database') || str_contains($message, 'Base table or view not found')) {
            return [
                'error' => 'whatsapp_setup_not_ready',
                'details' => 'Import database/schema.sql or database/production_integration_migration.sql before running Automation Revenue Engine.',
            ];
        }
        return ['error' => $message];
    }
}
