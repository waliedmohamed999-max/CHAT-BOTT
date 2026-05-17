<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Validator;

final class WhatsAppCloudApiService
{
    public function sendTemplate(string $phoneNumberId, string $token, string $to, string $templateName, string $language, array $components = []): array
    {
        return $this->request($phoneNumberId . '/messages', $token, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    public function sendText(string $phoneNumberId, string $token, string $to, string $body): array
    {
        return $this->request($phoneNumberId . '/messages', $token, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);
    }

    public function createTemplate(string $wabaId, string $token, array $template): array
    {
        return $this->request($wabaId . '/message_templates', $token, $template);
    }

    public function syncTemplates(string $wabaId, string $token): array
    {
        return $this->request($wabaId . '/message_templates', $token, ['limit' => 100], 'GET');
    }

    public function sendTemplateForContact(int $storeId, int $campaignMessageId, string $token, array $phone, array $template, array $contact, array $components = []): array
    {
        if (($template['status'] ?? '') !== 'approved') {
            throw new \RuntimeException('template_not_approved');
        }
        if (($contact['opt_in_status'] ?? '') !== 'opted_in' || !empty($contact['unsubscribed_at'])) {
            throw new \RuntimeException('contact_not_opted_in');
        }

        $result = $this->sendTemplate(
            (string) $phone['phone_number_id'],
            $token,
            Validator::phone((string) $contact['phone']),
            (string) $template['name'],
            (string) ($template['language'] ?? 'en_US'),
            $components
        );

        $messageId = $result['messages'][0]['id'] ?? null;
        $stmt = Database::pdo()->prepare('UPDATE campaign_messages SET provider_message_id = ?, provider_status = ?, queue_status = ?, sent_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([$messageId, 'sent', 'sent', $campaignMessageId]);

        $log = Database::pdo()->prepare('INSERT INTO messages (direction, provider_message_id, sender_phone, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), payload = VALUES(payload)');
        $log->execute(['outbound', $messageId, $phone['display_phone_number'] ?? $phone['phone_number_id'], 'Template: ' . $template['name'], 'sent', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        return $result;
    }

    public function storeSyncedTemplates(int $storeId, int $localWabaId, string $wabaGraphId, string $token): int
    {
        $synced = 0;
        $response = $this->syncTemplates($wabaGraphId, $token);
        $pdo = Database::pdo();
        foreach (($response['data'] ?? []) as $template) {
            $components = $template['components'] ?? [];
            $body = '';
            $header = null;
            $footer = null;
            $buttons = null;
            foreach ($components as $component) {
                if (($component['type'] ?? '') === 'BODY') {
                    $body = $component['text'] ?? '';
                }
                if (($component['type'] ?? '') === 'HEADER') {
                    $header = $component['text'] ?? ($component['format'] ?? null);
                }
                if (($component['type'] ?? '') === 'FOOTER') {
                    $footer = $component['text'] ?? null;
                }
                if (($component['type'] ?? '') === 'BUTTONS') {
                    $buttons = $component['buttons'] ?? [];
                }
            }
            $stmt = $pdo->prepare('INSERT INTO whatsapp_templates (store_id, waba_id, meta_template_id, name, category, language, status, header, body, footer, buttons_json, components_json, rejection_reason, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE meta_template_id = VALUES(meta_template_id), category = VALUES(category), status = VALUES(status), header = VALUES(header), body = VALUES(body), footer = VALUES(footer), buttons_json = VALUES(buttons_json), components_json = VALUES(components_json), rejection_reason = VALUES(rejection_reason), updated_at = NOW()');
            $stmt->execute([
                $storeId,
                $localWabaId,
                $template['id'] ?? null,
                $template['name'] ?? '',
                strtoupper($template['category'] ?? 'MARKETING'),
                $template['language'] ?? 'en_US',
                strtolower($template['status'] ?? 'pending'),
                $header,
                $body,
                $footer,
                json_encode($buttons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($components, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $template['rejected_reason'] ?? null,
            ]);
            $synced++;
        }
        return $synced;
    }

    public function request(string $edge, string $token, array $payload = [], string $method = 'POST'): array
    {
        $url = 'https://graph.facebook.com/' . Env::get('WHATSAPP_API_VERSION', Env::get('META_GRAPH_VERSION', 'v23.0')) . '/' . ltrim($edge, '/');
        if ($method === 'GET' && $payload) {
            $url .= '?' . http_build_query($payload);
        }

        $headers = ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false || $status >= 400) {
            throw new \RuntimeException('WhatsApp Cloud API request failed: ' . ($response ?: curl_error($ch)));
        }

        return json_decode($response, true) ?: [];
    }
}
