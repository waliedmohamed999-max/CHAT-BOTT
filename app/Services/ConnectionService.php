<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;

final class ConnectionService
{
    public function activeConnection(int $storeId): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM meta_connections WHERE store_id = ? AND token_status = 'active' AND disconnected_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$storeId]);
        $connection = $stmt->fetch();
        return $connection ?: null;
    }

    public function accessToken(int $storeId): string
    {
        $connection = $this->activeConnection($storeId);
        if (!$connection) {
            throw new \RuntimeException('meta_not_connected');
        }
        return Crypto::decrypt($connection['token_ciphertext']);
    }

    public function primaryPhone(int $storeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_phone_numbers WHERE store_id = ? AND is_primary = 1 ORDER BY id DESC LIMIT 1');
        $stmt->execute([$storeId]);
        $phone = $stmt->fetch();
        if (!$phone) {
            $stmt = Database::pdo()->prepare('SELECT * FROM whatsapp_phone_numbers WHERE store_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$storeId]);
            $phone = $stmt->fetch();
        }
        if (!$phone) {
            throw new \RuntimeException('whatsapp_phone_not_connected');
        }
        return $phone;
    }

    public function checklist(int $storeId): array
    {
        $pdo = Database::pdo();
        $metaConnected = (bool) $this->activeConnection($storeId);
        $wabaConnected = (bool) $pdo->query('SELECT id FROM whatsapp_business_accounts WHERE store_id = ' . (int) $storeId . ' LIMIT 1')->fetch();
        $phoneConnected = (bool) $pdo->query('SELECT id FROM whatsapp_phone_numbers WHERE store_id = ' . (int) $storeId . ' LIMIT 1')->fetch();
        $webhookVerified = (bool) $pdo->query("SELECT id FROM whatsapp_phone_numbers WHERE store_id = " . (int) $storeId . " AND webhook_status = 'verified' LIMIT 1")->fetch();
        $testSent = (bool) $pdo->query("SELECT id FROM messages WHERE direction = 'outbound' AND status IN ('sent','queued') LIMIT 1")->fetch();
        $templatesSynced = (bool) $pdo->query('SELECT id FROM whatsapp_templates WHERE store_id = ' . (int) $storeId . ' LIMIT 1')->fetch();
        $campaignReady = (bool) $pdo->query("SELECT id FROM campaigns WHERE store_id = " . (int) $storeId . " AND status IN ('draft','scheduled','queued','running') LIMIT 1")->fetch();

        return [
            'Meta Connected' => $metaConnected,
            'WABA Connected' => $wabaConnected,
            'Phone Number Connected' => $phoneConnected,
            'Webhook Verified' => $webhookVerified,
            'Test Message Sent' => $testSent,
            'Templates Synced' => $templatesSynced,
            'First Campaign Ready' => $campaignReady,
        ];
    }
}
