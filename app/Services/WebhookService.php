<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class WebhookService
{
    public function verifyChallenge(array $query): ?string
    {
        if (($query['hub_mode'] ?? $query['hub.mode'] ?? '') !== 'subscribe') {
            return null;
        }

        $token = $query['hub_verify_token'] ?? $query['hub.verify_token'] ?? '';
        if (!hash_equals(Env::get('META_VERIFY_TOKEN', Env::get('META_WEBHOOK_VERIFY_TOKEN', 'change-webhook-token')), (string) $token)) {
            return null;
        }

        return (string) ($query['hub_challenge'] ?? $query['hub.challenge'] ?? '');
    }

    public function assertSignature(string $rawPayload): void
    {
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        if ($signature === '') {
            throw new \RuntimeException('Missing webhook signature.');
        }

        $secret = Env::get('META_WEBHOOK_SECRET', Env::get('META_APP_SECRET', ''));
        $expected = 'sha256=' . hash_hmac('sha256', $rawPayload, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid webhook signature.');
        }
    }

    public function ingest(array $payload, string $rawPayload): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO webhook_logs (provider, event_type, payload, signature, received_at) VALUES (?, ?, ?, ?, NOW())');
        $eventType = $payload['entry'][0]['changes'][0]['field'] ?? 'unknown';
        $stmt->execute(['meta', $eventType, $rawPayload, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null]);
        $webhookLogId = (int) $pdo->lastInsertId();

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                try {
                    $this->storeIncomingMessages($value);
                    $this->storeStatuses($value);
                    $this->storeTemplateUpdates($value);
                } catch (\Throwable $e) {
                    error_log('Webhook payload processing failed: ' . $e->getMessage());
                }
            }
        }

        $pdo->prepare('UPDATE webhook_logs SET processed_at = NOW() WHERE id = ?')->execute([$webhookLogId]);
    }

    private function storeIncomingMessages(array $value): void
    {
        $pdo = Database::pdo();
        foreach (($value['messages'] ?? []) as $message) {
            $contactWaId = $message['from'] ?? '';
            $body = $message['text']['body'] ?? json_encode($message, JSON_UNESCAPED_UNICODE);
            $storeId = $this->storeIdFromPhoneNumber($value['metadata']['phone_number_id'] ?? null);
            $contactId = $this->upsertContact($storeId, $contactWaId, $value['contacts'][0]['profile']['name'] ?? null, $body);
            $conversationId = $this->conversation($storeId, $contactId);
            $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, direction, provider_message_id, sender_phone, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE payload = VALUES(payload), status = VALUES(status)');
            $stmt->execute([$conversationId, 'inbound', $message['id'] ?? '', $contactWaId, $body, 'received', json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) ($message['timestamp'] ?? time())]);
            $pdo->prepare('UPDATE conversations SET last_message_at = FROM_UNIXTIME(?), updated_at = NOW() WHERE id = ?')->execute([(int) ($message['timestamp'] ?? time()), $conversationId]);
            try {
                (new ChatbotService())->handleIncomingWhatsAppMessage($storeId, $conversationId, $contactId, $contactWaId, $body, 'meta_cloud_api', $message);
            } catch (\Throwable $e) {
                error_log('Cloud chatbot runtime failed: ' . $e->getMessage());
            }
        }
    }

    private function storeStatuses(array $value): void
    {
        $pdo = Database::pdo();
        foreach (($value['statuses'] ?? []) as $status) {
            $stmt = $pdo->prepare('UPDATE campaign_messages SET provider_status = ?, failed_reason = ?, updated_at = NOW() WHERE provider_message_id = ?');
            $stmt->execute([$status['status'] ?? 'unknown', $status['errors'][0]['title'] ?? null, $status['id'] ?? '']);
            $pdo->prepare('UPDATE messages SET status = ?, payload = ? WHERE provider_message_id = ?')->execute([$status['status'] ?? 'unknown', json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status['id'] ?? '']);
        }
    }

    private function storeTemplateUpdates(array $value): void
    {
        $event = $value['message_template_id'] ?? $value['event'] ?? null;
        if (!$event && empty($value['message_template_name'])) {
            return;
        }
        $status = strtolower((string) ($value['message_template_status'] ?? $value['status'] ?? 'pending'));
        if (!in_array($status, ['approved', 'pending', 'rejected', 'paused', 'disabled'], true)) {
            $status = 'pending';
        }
        $stmt = Database::pdo()->prepare('UPDATE whatsapp_templates SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE meta_template_id = ? OR name = ?');
        $stmt->execute([$status, $value['reason'] ?? null, $value['message_template_id'] ?? '', $value['message_template_name'] ?? '']);
    }

    private function storeIdFromPhoneNumber(?string $phoneNumberId): int
    {
        if ($phoneNumberId) {
            $stmt = Database::pdo()->prepare('SELECT store_id FROM whatsapp_phone_numbers WHERE phone_number_id = ? LIMIT 1');
            $stmt->execute([$phoneNumberId]);
            $storeId = $stmt->fetchColumn();
            if ($storeId) {
                return (int) $storeId;
            }
        }
        return (int) Env::get('DEFAULT_STORE_ID', '1');
    }

    private function upsertContact(int $storeId, string $phone, ?string $name, string $body): int
    {
        $lower = mb_strtolower(trim($body));
        $unsubscribeKeywords = array_map('trim', explode(',', Env::get('UNSUBSCRIBE_KEYWORDS', 'stop,unsubscribe,إلغاء')));
        $unsubscribedAt = in_array($lower, array_map('mb_strtolower', $unsubscribeKeywords), true) ? date('Y-m-d H:i:s') : null;
        $optInStatus = $unsubscribedAt ? 'opted_out' : 'unknown';

        $stmt = Database::pdo()->prepare('INSERT INTO contacts (store_id, name, phone, opt_in_status, unsubscribed_at, last_contact_at, source, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE name = COALESCE(VALUES(name), name), unsubscribed_at = COALESCE(VALUES(unsubscribed_at), unsubscribed_at), opt_in_status = IF(VALUES(unsubscribed_at) IS NULL, opt_in_status, VALUES(opt_in_status)), last_contact_at = NOW(), updated_at = NOW()');
        $stmt->execute([$storeId, $name, $phone, $optInStatus, $unsubscribedAt, 'whatsapp_webhook']);

        $lookup = Database::pdo()->prepare('SELECT id FROM contacts WHERE store_id = ? AND phone = ?');
        $lookup->execute([$storeId, $phone]);
        return (int) $lookup->fetchColumn();
    }

    private function conversation(int $storeId, int $contactId): int
    {
        $lookup = Database::pdo()->prepare("SELECT id FROM conversations WHERE store_id = ? AND contact_id = ? AND status <> 'closed' ORDER BY id DESC LIMIT 1");
        $lookup->execute([$storeId, $contactId]);
        $id = $lookup->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $stmt = Database::pdo()->prepare('INSERT INTO conversations (store_id, contact_id, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())');
        $stmt->execute([$storeId, $contactId, 'open']);
        return (int) Database::pdo()->lastInsertId();
    }
}
