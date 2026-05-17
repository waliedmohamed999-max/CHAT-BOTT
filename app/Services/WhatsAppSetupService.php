<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Validator;

final class WhatsAppSetupService
{
    private const ALLOWED_MIME = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function profile(int $storeId): array
    {
        $profile = $this->findProfile($storeId);
        return $profile ?: ['store_id' => $storeId, 'setup_status' => 'draft', 'readiness_score' => 0];
    }

    public function saveProfile(int $storeId, array $data): array
    {
        $profile = $this->ensureProfile($storeId);
        $allowed = [
            'selected_method', 'business_name', 'store_name', 'country', 'city', 'business_type',
            'website_url', 'store_url', 'facebook_url', 'instagram_url', 'official_email',
            'official_phone', 'whatsapp_phone', 'business_description', 'has_meta_business',
            'is_business_verified', 'has_privacy_policy', 'has_terms',
        ];

        if (!empty($data['official_email']) && !filter_var($data['official_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid_email');
        }
        foreach (['official_phone', 'whatsapp_phone'] as $phoneField) {
            if (!empty($data[$phoneField])) {
                $data[$phoneField] = Validator::phone((string) $data[$phoneField]);
            }
        }
        foreach (['selected_method'] as $field) {
            if (!empty($data[$field])) {
                Validator::enum((string) $data[$field], ['meta_cloud_api', 'qr_web_session'], 'invalid_setup_method');
            }
        }

        $sets = [];
        $values = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = $field . ' = ?';
                $values[] = in_array($field, ['has_meta_business', 'is_business_verified', 'has_privacy_policy', 'has_terms'], true) ? (int) (bool) $data[$field] : $data[$field];
            }
        }
        $score = $this->calculateReadiness($storeId, array_merge($profile, $data));
        $sets[] = 'readiness_score = ?';
        $values[] = $score;
        $sets[] = 'setup_status = ?';
        $values[] = $score >= 71 ? 'ready' : 'in_progress';
        $values[] = $storeId;

        Database::pdo()->prepare('UPDATE whatsapp_setup_profiles SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE store_id = ?')->execute($values);
        AuditLogger::record('whatsapp_setup.profile_saved', $storeId, null, 'whatsapp_setup_profile', (int) $profile['id']);
        return $this->profile($storeId);
    }

    public function selectMethod(int $storeId, string $method): array
    {
        return $this->saveProfile($storeId, ['selected_method' => Validator::enum($method, ['meta_cloud_api', 'qr_web_session'], 'invalid_setup_method')]);
    }

    public function documents(int $storeId): array
    {
        $profile = $this->ensureProfile($storeId);
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_setup_documents WHERE setup_profile_id = ? AND upload_status <> ? ORDER BY id DESC');
        $stmt->execute([(int) $profile['id'], 'deleted']);
        return $stmt->fetchAll();
    }

    public function uploadDocument(int $storeId, array $file, string $documentType): array
    {
        $profile = $this->ensureProfile($storeId);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('upload_failed');
        }
        if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
            throw new \RuntimeException('file_too_large');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('invalid_file_type');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $dir = dirname(__DIR__, 2) . '/storage/whatsapp_setup/store_' . $storeId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fileName = $documentType . '_' . date('YmdHis') . '_' . $safeName . '.' . $extension;
        $target = $dir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('upload_failed');
        }

        $stmt = Database::pdo()->prepare('INSERT INTO whatsapp_setup_documents (setup_profile_id, document_type, file_name, file_url, file_mime_type, file_size, upload_status, reviewed_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([(int) $profile['id'], $documentType, $file['name'], $target, $mime, (int) $file['size'], 'uploaded', 'pending']);
        AuditLogger::record('whatsapp_setup.document_uploaded', $storeId, null, 'whatsapp_setup_document', (int) Database::pdo()->lastInsertId(), ['document_type' => $documentType]);
        $this->refreshReadiness($storeId);
        return ['id' => Database::pdo()->lastInsertId(), 'file_name' => $file['name'], 'document_type' => $documentType, 'upload_status' => 'uploaded'];
    }

    public function deleteDocument(int $storeId, int $documentId): void
    {
        $profile = $this->ensureProfile($storeId);
        $stmt = Database::pdo()->prepare('UPDATE whatsapp_setup_documents SET upload_status = ?, updated_at = NOW() WHERE id = ? AND setup_profile_id = ?');
        $stmt->execute(['deleted', $documentId, (int) $profile['id']]);
        AuditLogger::record('whatsapp_setup.document_deleted', $storeId, null, 'whatsapp_setup_document', $documentId);
        $this->refreshReadiness($storeId);
    }

    public function readiness(int $storeId): array
    {
        $profile = $this->ensureProfile($storeId);
        $score = $this->calculateReadiness($storeId, $profile);
        Database::pdo()->prepare('UPDATE whatsapp_setup_profiles SET readiness_score = ?, setup_status = ?, updated_at = NOW() WHERE store_id = ?')->execute([$score, $score >= 71 ? 'ready' : ($score >= 41 ? 'in_progress' : 'draft'), $storeId]);
        return [
            'score' => $score,
            'status' => $score >= 71 ? 'جاهز للإطلاق' : ($score >= 41 ? 'يحتاج مراجعة' : 'غير جاهز'),
            'items' => $this->readinessItems($storeId, $profile),
        ];
    }

    public function logTest(int $storeId, string $testType, string $status, string $message, array $payload = []): array
    {
        Validator::enum($status, ['passed', 'failed', 'warning', 'pending'], 'invalid_test_status');
        $connectionId = $this->activeConnectionId($storeId);
        $stmt = Database::pdo()->prepare('INSERT INTO setup_test_logs (store_id, connection_id, test_type, status, message, raw_payload, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$storeId, $connectionId, $testType, $status, $message, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        return ['id' => Database::pdo()->lastInsertId(), 'test_type' => $testType, 'status' => $status, 'message' => $message];
    }

    public function testLogs(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM setup_test_logs WHERE store_id = ? ORDER BY id DESC LIMIT 100');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function syncConnections(int $storeId): void
    {
        $pdo = Database::pdo();
        $meta = $pdo->prepare("SELECT mc.business_id, mc.token_ciphertext, mc.token_status, wba.waba_id, wpn.phone_number_id, wpn.display_phone_number, wpn.verified_name, wpn.webhook_status FROM meta_connections mc LEFT JOIN whatsapp_business_accounts wba ON wba.meta_connection_id = mc.id LEFT JOIN whatsapp_phone_numbers wpn ON wpn.waba_id = wba.id WHERE mc.store_id = ? ORDER BY mc.id DESC LIMIT 1");
        $meta->execute([$storeId]);
        if ($row = $meta->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO whatsapp_connections (store_id, connection_type, status, display_name, phone_number, meta_business_id, waba_id, phone_number_id, encrypted_access_token, webhook_status, last_connected_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())');
            $stmt->execute([$storeId, 'meta_cloud_api', $row['token_status'] === 'active' ? 'connected' : 'pending', $row['verified_name'], $row['display_phone_number'], $row['business_id'], $row['waba_id'], $row['phone_number_id'], $row['token_ciphertext'], $row['webhook_status']]);
        }

        $qr = $pdo->prepare('SELECT * FROM whatsapp_qr_sessions WHERE store_id = ? ORDER BY id DESC LIMIT 1');
        $qr->execute([$storeId]);
        if ($row = $qr->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO whatsapp_connections (store_id, connection_type, status, display_name, phone_number, avatar_url, encrypted_qr_auth_state, last_connected_at, disconnected_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$storeId, 'qr_web_session', $row['session_status'] === 'connected' ? 'connected' : $row['session_status'], $row['display_name'], $row['phone_number'], $row['avatar_url'], $row['auth_data_encrypted'], $row['last_connected_at'], $row['disconnected_at']]);
        }
    }

    private function ensureProfile(int $storeId): array
    {
        $profile = $this->findProfile($storeId);
        if ($profile) {
            return $profile;
        }
        Database::pdo()->prepare('INSERT INTO whatsapp_setup_profiles (store_id, setup_status, created_at, updated_at) VALUES (?, ?, NOW(), NOW())')->execute([$storeId, 'draft']);
        return $this->findProfile($storeId) ?: [];
    }

    private function findProfile(int $storeId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_setup_profiles WHERE store_id = ? LIMIT 1');
        $stmt->execute([$storeId]);
        $profile = $stmt->fetch();
        return $profile ?: null;
    }

    private function refreshReadiness(int $storeId): void
    {
        $profile = $this->ensureProfile($storeId);
        $score = $this->calculateReadiness($storeId, $profile);
        Database::pdo()->prepare('UPDATE whatsapp_setup_profiles SET readiness_score = ?, updated_at = NOW() WHERE store_id = ?')->execute([$score, $storeId]);
    }

    private function calculateReadiness(int $storeId, array $profile): int
    {
        $items = $this->readinessItems($storeId, $profile);
        $ready = count(array_filter($items));
        return (int) round(($ready / max(1, count($items))) * 100);
    }

    private function readinessItems(int $storeId, array $profile): array
    {
        return [
            'business_profile_complete' => !empty($profile['business_name']) && !empty($profile['official_email']) && !empty($profile['whatsapp_phone']),
            'documents_uploaded' => $this->documentsCount($storeId) >= 2,
            'method_selected' => !empty($profile['selected_method']),
            'connection_successful' => (bool) $this->activeConnectionId($storeId),
            'test_message_successful' => $this->hasPassedTest($storeId, 'send_message'),
            'webhook_ready' => $this->hasPassedTest($storeId, 'webhook') || ($profile['selected_method'] ?? '') === 'qr_web_session',
            'templates_ready' => ($profile['selected_method'] ?? '') === 'qr_web_session' || $this->hasPassedTest($storeId, 'templates'),
            'opt_in_enabled' => true,
            'unsubscribe_ready' => true,
            'queue_enabled' => true,
            'campaign_limits_enabled' => true,
        ];
    }

    private function documentsCount(int $storeId): int
    {
        $profile = $this->findProfile($storeId);
        if (!$profile) {
            return 0;
        }
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM whatsapp_setup_documents WHERE setup_profile_id = ? AND upload_status = ?');
        $stmt->execute([(int) $profile['id'], 'uploaded']);
        return (int) $stmt->fetchColumn();
    }

    private function hasPassedTest(int $storeId, string $testType): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM setup_test_logs WHERE store_id = ? AND test_type = ? AND status = ? LIMIT 1');
        $stmt->execute([$storeId, $testType, 'passed']);
        return (bool) $stmt->fetchColumn();
    }

    private function activeConnectionId(int $storeId): ?int
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM whatsapp_connections WHERE store_id = ? AND status = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$storeId, 'connected']);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }
}
