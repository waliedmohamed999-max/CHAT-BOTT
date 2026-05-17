<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Validator;

final class WhatsAppQrService
{
    public function createSession(int $storeId, int $userId): array
    {
        $session = $this->ensureSession($storeId, $userId);
        $bridge = $this->bridge('POST', '/sessions/' . $this->bridgeSessionId($storeId) . '/create', ['storeId' => $storeId, 'userId' => $userId]);
        $this->updateSession($storeId, [
            'session_status' => $bridge['status'] ?? 'waiting_for_scan',
            'last_qr_code' => $bridge['qr'] ?? null,
            'auth_data_encrypted' => isset($bridge['authState']) ? Crypto::encrypt(json_encode($bridge['authState'])) : ($session['auth_data_encrypted'] ?? null),
        ]);
        AuditLogger::record('whatsapp_qr.session_created', $storeId, $userId, 'whatsapp_qr_session', (int) $session['id']);
        return $this->status($storeId);
    }

    public function status(int $storeId): array
    {
        $session = $this->session($storeId);
        if (!$session) {
            return ['connected' => false, 'session_status' => 'disconnected'];
        }

        try {
            $bridge = $this->bridge('GET', '/sessions/' . $this->bridgeSessionId($storeId) . '/status');
            $this->updateSession($storeId, [
                'phone_number' => $bridge['phoneNumber'] ?? $session['phone_number'],
                'display_name' => $bridge['displayName'] ?? $session['display_name'],
                'avatar_url' => $bridge['avatarUrl'] ?? $session['avatar_url'],
                'session_status' => $bridge['status'] ?? $session['session_status'],
                'last_qr_code' => $bridge['qr'] ?? $session['last_qr_code'],
                'last_connected_at' => (($bridge['status'] ?? '') === 'connected') ? date('Y-m-d H:i:s') : $session['last_connected_at'],
            ]);
            $session = $this->session($storeId) ?: $session;
        } catch (\Throwable $e) {
            $session['bridge_error'] = $e->getMessage();
        }

        unset($session['auth_data_encrypted']);
        return $session;
    }

    public function qr(int $storeId): array
    {
        $session = $this->status($storeId);
        return ['qr' => $session['last_qr_code'] ?? null, 'session_status' => $session['session_status'] ?? 'disconnected'];
    }

    public function disconnect(int $storeId, int $userId): array
    {
        try {
            $this->bridge('POST', '/sessions/' . $this->bridgeSessionId($storeId) . '/disconnect');
        } catch (\Throwable $e) {
            error_log('QR bridge disconnect failed: ' . $e->getMessage());
        }
        $this->updateSession($storeId, ['session_status' => 'disconnected', 'disconnected_at' => date('Y-m-d H:i:s'), 'auth_data_encrypted' => null]);
        AuditLogger::record('whatsapp_qr.session_disconnected', $storeId, $userId);
        return $this->status($storeId);
    }

    public function reconnect(int $storeId, int $userId): array
    {
        try {
            $bridge = $this->bridge('POST', '/sessions/' . $this->bridgeSessionId($storeId) . '/reconnect');
            $this->updateSession($storeId, ['session_status' => $bridge['status'] ?? 'authenticating', 'last_qr_code' => $bridge['qr'] ?? null]);
        } catch (\Throwable $e) {
            $this->updateSession($storeId, ['session_status' => 'error']);
            throw $e;
        }
        AuditLogger::record('whatsapp_qr.session_reconnected', $storeId, $userId);
        return $this->status($storeId);
    }

    public function chats(int $storeId): array
    {
        $session = $this->requireSession($storeId);
        try {
            $bridge = $this->bridge('GET', '/sessions/' . $this->bridgeSessionId($storeId) . '/chats');
            foreach (($bridge['data'] ?? []) as $chat) {
                $this->upsertChat((int) $session['id'], $chat);
            }
        } catch (\Throwable $e) {
            error_log('QR bridge chats failed: ' . $e->getMessage());
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_qr_chats WHERE session_id = ? ORDER BY last_message_at DESC, id DESC LIMIT 150');
        $stmt->execute([(int) $session['id']]);
        return $stmt->fetchAll();
    }

    public function messages(int $storeId, string $chatId): array
    {
        $session = $this->requireSession($storeId);
        try {
            $bridge = $this->bridge('GET', '/sessions/' . $this->bridgeSessionId($storeId) . '/chats/' . rawurlencode($chatId) . '/messages');
            foreach (($bridge['data'] ?? []) as $message) {
                $this->upsertMessage((int) $session['id'], $message);
            }
        } catch (\Throwable $e) {
            error_log('QR bridge messages failed: ' . $e->getMessage());
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_qr_messages WHERE session_id = ? AND chat_id = ? ORDER BY message_timestamp ASC, id ASC LIMIT 300');
        $stmt->execute([(int) $session['id'], $chatId]);
        return $stmt->fetchAll();
    }

    public function sendMessage(int $storeId, string $to, string $body): array
    {
        $session = $this->requireConnectedSession($storeId);
        $phone = Validator::phone($to);
        $result = $this->bridge('POST', '/sessions/' . $this->bridgeSessionId($storeId) . '/send-message', ['to' => $phone, 'body' => $body]);
        $this->upsertMessage((int) $session['id'], [
            'chatId' => $result['chatId'] ?? ($phone . '@s.whatsapp.net'),
            'messageId' => $result['messageId'] ?? sha1($phone . $body . microtime(true)),
            'from' => $session['phone_number'] ?? null,
            'to' => $phone,
            'body' => $body,
            'type' => 'text',
            'direction' => 'outbound',
            'status' => $result['status'] ?? 'sent',
            'timestamp' => time(),
            'rawPayload' => $result,
        ]);
        return $result;
    }

    public function sendMedia(int $storeId, array $data): array
    {
        $session = $this->requireConnectedSession($storeId);
        $data['to'] = Validator::phone((string) ($data['to'] ?? ''));
        $result = $this->bridge('POST', '/sessions/' . $this->bridgeSessionId($storeId) . '/send-media', $data);
        $this->upsertMessage((int) $session['id'], [
            'chatId' => $result['chatId'] ?? ($data['to'] . '@s.whatsapp.net'),
            'messageId' => $result['messageId'] ?? sha1(json_encode($data) . microtime(true)),
            'from' => $session['phone_number'] ?? null,
            'to' => $data['to'],
            'body' => $data['caption'] ?? null,
            'type' => $data['type'] ?? 'media',
            'mediaUrl' => $data['media_url'] ?? null,
            'direction' => 'outbound',
            'status' => $result['status'] ?? 'sent',
            'timestamp' => time(),
            'rawPayload' => $result,
        ]);
        return $result;
    }

    public function contacts(int $storeId): array
    {
        $this->requireSession($storeId);
        return $this->bridge('GET', '/sessions/' . $this->bridgeSessionId($storeId) . '/contacts')['data'] ?? [];
    }

    public function ingestEvent(array $event): void
    {
        $storeId = (int) ($event['storeId'] ?? Env::get('DEFAULT_STORE_ID', '1'));
        $session = $this->ensureSession($storeId, null);
        if (($event['type'] ?? '') === 'status') {
            $this->updateSession($storeId, [
                'session_status' => $event['status'] ?? 'disconnected',
                'phone_number' => $event['phoneNumber'] ?? $session['phone_number'],
                'display_name' => $event['displayName'] ?? $session['display_name'],
                'avatar_url' => $event['avatarUrl'] ?? $session['avatar_url'],
                'last_qr_code' => $event['qr'] ?? $session['last_qr_code'],
                'last_connected_at' => (($event['status'] ?? '') === 'connected') ? date('Y-m-d H:i:s') : $session['last_connected_at'],
            ]);
        }
        if (($event['type'] ?? '') === 'message') {
            $message = $event['message'] ?? [];
            $this->upsertMessage((int) $session['id'], $message);
            $this->upsertChat((int) $session['id'], $event['chat'] ?? ['chatId' => $message['chatId'] ?? 'unknown']);
            if (($message['direction'] ?? 'inbound') === 'inbound') {
                try {
                    $phone = (string) ($message['from'] ?? preg_replace('/@.*/', '', (string) ($message['chatId'] ?? '')));
                    $body = (string) ($message['body'] ?? '');
                    $contactId = $this->upsertInboxContact($storeId, $phone);
                    $conversationId = $this->upsertInboxConversation($storeId, $contactId);
                    Database::pdo()->prepare('INSERT INTO messages (conversation_id, direction, provider_message_id, sender_phone, body, status, payload, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE body = VALUES(body), status = VALUES(status), payload = VALUES(payload)')->execute([
                        $conversationId,
                        'inbound',
                        $message['messageId'] ?? $message['id'] ?? sha1(json_encode($message)),
                        $phone,
                        $body,
                        'received',
                        json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        (int) ($message['timestamp'] ?? time()),
                    ]);
                    (new ChatbotService())->handleIncomingWhatsAppMessage($storeId, $conversationId, $contactId, $phone, $body, 'qr_web_session', $message);
                } catch (\Throwable $e) {
                    error_log('QR chatbot runtime failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function session(int $storeId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_qr_sessions WHERE store_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$storeId]);
        $session = $stmt->fetch();
        return $session ?: null;
    }

    private function ensureSession(int $storeId, ?int $userId): array
    {
        $session = $this->session($storeId);
        if ($session) {
            return $session;
        }
        $stmt = Database::pdo()->prepare('INSERT INTO whatsapp_qr_sessions (store_id, user_id, session_status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$storeId, $userId, 'waiting_for_scan']);
        return $this->session($storeId) ?: [];
    }

    private function requireSession(int $storeId): array
    {
        $session = $this->session($storeId);
        if (!$session) {
            throw new \RuntimeException('whatsapp_qr_session_not_found');
        }
        return $session;
    }

    private function requireConnectedSession(int $storeId): array
    {
        $session = $this->requireSession($storeId);
        if ($session['session_status'] !== 'connected') {
            throw new \RuntimeException('whatsapp_qr_not_connected');
        }
        return $session;
    }

    private function updateSession(int $storeId, array $data): void
    {
        $allowed = ['phone_number', 'display_name', 'avatar_url', 'session_status', 'auth_data_encrypted', 'last_qr_code', 'last_connected_at', 'disconnected_at'];
        $sets = [];
        $values = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = $field . ' = ?';
                $values[] = $data[$field];
            }
        }
        if (!$sets) {
            return;
        }
        $values[] = $storeId;
        Database::pdo()->prepare('UPDATE whatsapp_qr_sessions SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE store_id = ?')->execute($values);
    }

    private function upsertChat(int $sessionId, array $chat): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO whatsapp_qr_chats (session_id, chat_id, name, is_group, unread_count, last_message, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), NOW(), NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), is_group = VALUES(is_group), unread_count = VALUES(unread_count), last_message = VALUES(last_message), last_message_at = VALUES(last_message_at), updated_at = NOW()');
        $stmt->execute([
            $sessionId,
            $chat['chatId'] ?? $chat['id'] ?? 'unknown',
            $chat['name'] ?? null,
            !empty($chat['isGroup']) ? 1 : 0,
            (int) ($chat['unreadCount'] ?? 0),
            $chat['lastMessage'] ?? null,
            (int) ($chat['lastMessageAt'] ?? time()),
        ]);
    }

    private function upsertMessage(int $sessionId, array $message): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO whatsapp_qr_messages (session_id, chat_id, message_id, from_phone, to_phone, body, type, media_url, direction, status, message_timestamp, raw_payload, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, NOW()) ON DUPLICATE KEY UPDATE body = VALUES(body), status = VALUES(status), raw_payload = VALUES(raw_payload)');
        $stmt->execute([
            $sessionId,
            $message['chatId'] ?? 'unknown',
            $message['messageId'] ?? $message['id'] ?? sha1(json_encode($message)),
            $message['from'] ?? null,
            $message['to'] ?? null,
            $message['body'] ?? null,
            $message['type'] ?? 'text',
            $message['mediaUrl'] ?? null,
            $message['direction'] ?? 'inbound',
            $message['status'] ?? 'received',
            (int) ($message['timestamp'] ?? time()),
            json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function bridge(string $method, string $path, array $payload = []): array
    {
        $base = rtrim(Env::get('WHATSAPP_QR_BRIDGE_URL', 'http://127.0.0.1:3020'), '/');
        $ch = curl_init($base . $path);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Bridge-Token: ' . Env::get('WHATSAPP_QR_BRIDGE_TOKEN', ''),
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false || $status >= 400) {
            throw new \RuntimeException('whatsapp_qr_bridge_unavailable');
        }
        return json_decode((string) $response, true) ?: [];
    }

    private function bridgeSessionId(int $storeId): string
    {
        return 'store-' . $storeId;
    }

    private function upsertInboxContact(int $storeId, string $phone): int
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: $phone;
        Database::pdo()->prepare('INSERT INTO contacts (store_id, phone, opt_in_status, source, last_contact_at, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW(), NOW()) ON DUPLICATE KEY UPDATE last_contact_at = NOW(), updated_at = NOW()')->execute([$storeId, $phone, 'unknown', 'whatsapp_qr']);
        $stmt = Database::pdo()->prepare('SELECT id FROM contacts WHERE store_id = ? AND phone = ? LIMIT 1');
        $stmt->execute([$storeId, $phone]);
        return (int) $stmt->fetchColumn();
    }

    private function upsertInboxConversation(int $storeId, int $contactId): int
    {
        $stmt = Database::pdo()->prepare("SELECT id FROM conversations WHERE store_id = ? AND contact_id = ? AND status <> 'closed' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$storeId, $contactId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        Database::pdo()->prepare('INSERT INTO conversations (store_id, contact_id, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())')->execute([$storeId, $contactId, 'open']);
        return (int) Database::pdo()->lastInsertId();
    }
}
