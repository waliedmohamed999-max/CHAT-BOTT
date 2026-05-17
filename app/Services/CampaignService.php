<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Validator;

final class CampaignService
{
    public function create(array $data, int $storeId, int $userId): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('campaign_name_required');
        }
        $status = !empty($data['scheduled_at']) ? 'scheduled' : 'draft';
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO campaigns (store_id, created_by, name, channel, campaign_type, audience_type, segment_id, template_id, status, scheduled_at, estimated_cost, metadata_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $storeId,
            $userId,
            $name,
            'whatsapp',
            $data['campaign_type'] ?? 'marketing',
            $data['audience_type'] ?? 'segment',
            $data['segment_id'] ?? null,
            $data['template_id'] ?? null,
            $status,
            $data['scheduled_at'] ?? null,
            $data['estimated_cost'] ?? 0,
            json_encode([
                'components' => $data['components'] ?? $this->componentsFromVariables($data['variables'] ?? ''),
                'send_source' => $data['send_source'] ?? 'cloud_api',
                'message_body' => $data['message_body'] ?? null,
                'created_from' => 'marketing_center',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $id = (int) $pdo->lastInsertId();
        AuditLogger::record('campaign.created', $storeId, $userId, 'campaign', $id);
        return $id;
    }

    public function launch(int $campaignId): array
    {
        $pdo = Database::pdo();
        $campaign = $pdo->query('SELECT c.*, t.status template_status FROM campaigns c LEFT JOIN whatsapp_templates t ON t.id = c.template_id WHERE c.id = ' . (int) $campaignId)->fetch();
        if (!$campaign) {
            throw new \RuntimeException('Campaign not found.');
        }
        $metadata = json_decode($campaign['metadata_json'] ?? '[]', true) ?: [];
        $sendSource = $metadata['send_source'] ?? 'cloud_api';
        if ($sendSource !== 'qr_session' && $campaign['template_status'] !== 'approved') {
            throw new \RuntimeException('Only approved WhatsApp templates can be launched.');
        }
        if ($sendSource === 'qr_session' && trim((string) ($metadata['message_body'] ?? '')) === '') {
            throw new \RuntimeException('qr_message_body_required');
        }

        $contacts = $pdo->prepare('SELECT id, phone FROM contacts WHERE store_id = ? AND opt_in_status = ? AND unsubscribed_at IS NULL ORDER BY id ASC');
        $contacts->execute([(int) $campaign['store_id'], 'opted_in']);
        $queued = 0;
        foreach ($contacts->fetchAll() as $contact) {
            Validator::phone((string) $contact['phone']);
            $stmt = $pdo->prepare('INSERT IGNORE INTO campaign_messages (campaign_id, contact_id, recipient_phone, provider_status, queue_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$campaignId, $contact['id'], $contact['phone'], 'queued', 'pending']);
            $queued += $stmt->rowCount();
        }

        $pdo->prepare('UPDATE campaigns SET status = ?, launched_at = NOW(), updated_at = NOW() WHERE id = ?')->execute(['queued', $campaignId]);
        AuditLogger::record('campaign.launched', (int) $campaign['store_id'], null, 'campaign', $campaignId, ['queued' => $queued]);

        return ['queued' => $queued, 'campaign_id' => $campaignId];
    }

    private function componentsFromVariables(mixed $variables): array
    {
        if (is_array($variables)) {
            $values = $variables;
        } elseif (is_string($variables) && trim($variables) !== '') {
            $decoded = json_decode($variables, true);
            $values = is_array($decoded) ? $decoded : [];
        } else {
            $values = [];
        }
        if (!$values) {
            return [];
        }
        return [[
            'type' => 'body',
            'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => (string) $value], array_values($values)),
        ]];
    }
}
