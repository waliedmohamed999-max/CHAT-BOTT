<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class CampaignQueueService
{
    public function process(int $storeId, ?int $campaignId = null): array
    {
        $pdo = Database::pdo();
        $batchSize = max(1, (int) Env::get('CAMPAIGN_BATCH_SIZE', '25'));
        $retryLimit = max(1, (int) Env::get('CAMPAIGN_RETRY_LIMIT', '3'));
        $connection = new ConnectionService();
        $token = null;
        $phone = null;

        $campaignSql = "SELECT * FROM campaigns WHERE store_id = ? AND status IN ('queued','running','scheduled') AND (scheduled_at IS NULL OR scheduled_at <= NOW())";
        $params = [$storeId];
        if ($campaignId) {
            $campaignSql .= ' AND id = ?';
            $params[] = $campaignId;
        }
        $campaignSql .= ' ORDER BY scheduled_at IS NULL DESC, scheduled_at ASC, id ASC LIMIT 5';
        $campaignStmt = $pdo->prepare($campaignSql);
        $campaignStmt->execute($params);

        $sent = 0;
        $failed = 0;
        $logs = [];
        $api = new WhatsAppCloudApiService();

        foreach ($campaignStmt->fetchAll() as $campaign) {
            if ($campaign['status'] === 'scheduled' && !$this->hasQueuedMessages((int) $campaign['id'])) {
                (new CampaignService())->launch((int) $campaign['id']);
            }
            $pdo->prepare("UPDATE campaigns SET status = 'running', updated_at = NOW() WHERE id = ? AND status <> 'paused'")->execute([$campaign['id']]);
            $metadata = json_decode($campaign['metadata_json'] ?? '[]', true) ?: [];
            $sendSource = $metadata['send_source'] ?? 'cloud_api';
            $template = $sendSource === 'qr_session' ? [] : $this->template((int) $campaign['template_id']);
            $components = $metadata['components'] ?? [];
            $safeBatchSize = $sendSource === 'qr_session' ? max(1, min($batchSize, (int) Env::get('WHATSAPP_QR_SAFE_BATCH_SIZE', '5'))) : $batchSize;

            $messages = $pdo->prepare("SELECT cm.*, c.phone, c.opt_in_status, c.unsubscribed_at FROM campaign_messages cm JOIN contacts c ON c.id = cm.contact_id WHERE cm.campaign_id = ? AND cm.queue_status IN ('pending','retry') AND cm.attempts < ? ORDER BY cm.id ASC LIMIT " . $safeBatchSize);
            $messages->execute([(int) $campaign['id'], $retryLimit]);

            foreach ($messages->fetchAll() as $message) {
                try {
                    $pdo->prepare("UPDATE campaign_messages SET queue_status = 'processing', attempts = attempts + 1, updated_at = NOW() WHERE id = ? AND queue_status IN ('pending','retry')")->execute([(int) $message['id']]);
                    if ($sendSource === 'qr_session') {
                        $this->sendQrCampaignMessage($storeId, (int) $message['id'], $message, (string) $metadata['message_body']);
                    } else {
                        $token ??= $connection->accessToken($storeId);
                        $phone ??= $connection->primaryPhone($storeId);
                        $api->sendTemplateForContact($storeId, (int) $message['id'], $token, $phone, $template, $message, $components);
                    }
                    $this->event((int) $message['id'], 'sent', ['campaign_id' => (int) $campaign['id']]);
                    $sent++;
                    $logs[] = ['message_id' => (int) $message['id'], 'status' => 'sent'];
                } catch (\Throwable $e) {
                    $failed++;
                    $nextStatus = ((int) $message['attempts'] + 1) >= $retryLimit ? 'failed' : 'retry';
                    $providerStatus = $nextStatus === 'failed' ? 'failed' : 'queued';
                    $pdo->prepare('UPDATE campaign_messages SET queue_status = ?, provider_status = ?, failed_reason = ?, updated_at = NOW() WHERE id = ?')->execute([$nextStatus, $providerStatus, $e->getMessage(), (int) $message['id']]);
                    $this->event((int) $message['id'], $nextStatus, ['error' => $e->getMessage()]);
                    $logs[] = ['message_id' => (int) $message['id'], 'status' => $nextStatus, 'error' => $e->getMessage()];
                }
            }

            $remaining = $pdo->prepare("SELECT COUNT(*) FROM campaign_messages WHERE campaign_id = ? AND queue_status IN ('pending','retry','processing')");
            $remaining->execute([(int) $campaign['id']]);
            if ((int) $remaining->fetchColumn() === 0) {
                $pdo->prepare("UPDATE campaigns SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([(int) $campaign['id']]);
            }
            if ($sendSource === 'qr_session' && $failed > $sent && $failed >= 3) {
                $pdo->prepare("UPDATE campaigns SET status = 'paused', updated_at = NOW() WHERE id = ?")->execute([(int) $campaign['id']]);
            }
        }

        AuditLogger::record('campaign.queue_processed', $storeId, null, $campaignId ? 'campaign' : null, $campaignId, ['sent' => $sent, 'failed' => $failed]);
        return ['sent' => $sent, 'failed' => $failed, 'logs' => $logs];
    }

    public function progress(int $campaignId): array
    {
        $stmt = Database::pdo()->prepare("SELECT queue_status, provider_status, COUNT(*) total FROM campaign_messages WHERE campaign_id = ? GROUP BY queue_status, provider_status");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function retryFailed(int $campaignId): int
    {
        $stmt = Database::pdo()->prepare("UPDATE campaign_messages SET queue_status = 'retry', provider_status = 'queued', failed_reason = NULL, updated_at = NOW() WHERE campaign_id = ? AND queue_status = 'failed'");
        $stmt->execute([$campaignId]);
        return $stmt->rowCount();
    }

    private function template(int $templateId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_templates WHERE id = ?');
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \RuntimeException('template_not_found');
        }
        if ($template['status'] !== 'approved') {
            throw new \RuntimeException('template_not_approved');
        }
        return $template;
    }

    private function hasQueuedMessages(int $campaignId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM campaign_messages WHERE campaign_id = ? LIMIT 1');
        $stmt->execute([$campaignId]);
        return (bool) $stmt->fetchColumn();
    }

    private function event(int $campaignMessageId, string $eventType, array $payload): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO campaign_message_events (campaign_message_id, event_type, payload, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$campaignMessageId, $eventType, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    private function sendQrCampaignMessage(int $storeId, int $campaignMessageId, array $message, string $body): void
    {
        if (($message['opt_in_status'] ?? '') !== 'opted_in' || !empty($message['unsubscribed_at'])) {
            throw new \RuntimeException('contact_not_opted_in');
        }
        $result = (new WhatsAppQrService())->sendMessage($storeId, (string) $message['phone'], $body);
        Database::pdo()->prepare('UPDATE campaign_messages SET provider_message_id = ?, provider_status = ?, queue_status = ?, sent_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([
            $result['messageId'] ?? null,
            'sent',
            'sent',
            $campaignMessageId,
        ]);
        $min = (int) Env::get('WHATSAPP_QR_MIN_DELAY_SECONDS', '2');
        $max = max($min, (int) Env::get('WHATSAPP_QR_MAX_DELAY_SECONDS', '8'));
        sleep(random_int($min, $max));
    }
}
