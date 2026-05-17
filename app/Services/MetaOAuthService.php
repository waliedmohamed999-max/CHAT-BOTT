<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\AuditLogger;

final class MetaOAuthService
{
    public function authorizationUrl(int $storeId): string
    {
        $_SESSION['meta_oauth_state'] = bin2hex(random_bytes(16));
        $params = [
            'client_id' => Env::get('META_APP_ID', ''),
            'redirect_uri' => Env::get('META_REDIRECT_URI', ''),
            'state' => $_SESSION['meta_oauth_state'] . ':' . $storeId,
            'response_type' => 'code',
            'config_id' => Env::get('META_CONFIG_ID', ''),
            'scope' => 'business_management,whatsapp_business_management,whatsapp_business_messaging,pages_show_list,pages_manage_posts,instagram_basic,instagram_content_publish',
            'extras' => json_encode(['sessionInfoVersion' => 3, 'featureType' => 'whatsapp_business_app_onboarding']),
        ];

        return 'https://www.facebook.com/' . $this->version() . '/dialog/oauth?' . http_build_query($params);
    }

    public function exchangeCode(string $code, int $storeId, array $callbackParams = []): array
    {
        $token = $this->graph('oauth/access_token', [
            'client_id' => Env::get('META_APP_ID', ''),
            'client_secret' => Env::get('META_APP_SECRET', ''),
            'redirect_uri' => Env::get('META_REDIRECT_URI', ''),
            'code' => $code,
        ], null, 'GET');

        $debug = $this->graph('debug_token', [
            'input_token' => $token['access_token'],
            'access_token' => Env::get('META_APP_ID', '') . '|' . Env::get('META_APP_SECRET', ''),
        ], null, 'GET');

        $pdo = Database::pdo();
        $me = $this->graph('me', ['fields' => 'id,name,businesses{id,name}'], $token['access_token'], 'GET');
        $businessId = (string) ($callbackParams['business_id'] ?? $me['businesses']['data'][0]['id'] ?? '');

        $stmt = $pdo->prepare('INSERT INTO meta_connections (store_id, meta_user_id, business_id, token_ciphertext, token_scopes, token_status, expires_at, connected_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), NOW(), NOW())');
        $stmt->execute([
            $storeId,
            (string) ($debug['data']['user_id'] ?? ''),
            $businessId ?: null,
            Crypto::encrypt($token['access_token']),
            implode(',', $debug['data']['scopes'] ?? []),
            !empty($debug['data']['is_valid']) ? 'active' : 'invalid',
            (int) ($debug['data']['expires_at'] ?? 0),
        ]);

        $connectionId = (int) $pdo->lastInsertId();
        $this->syncOwnedWhatsAppAssets($storeId, $connectionId, $token['access_token'], $businessId, $callbackParams);
        AuditLogger::record('meta.connected', $storeId, null, 'meta_connection', $connectionId, ['business_id' => $businessId]);

        return ['token' => $token, 'business_id' => $businessId];
    }

    public function syncOwnedWhatsAppAssets(int $storeId, int $connectionId, string $accessToken, ?string $businessId = null, array $hints = []): array
    {
        $wabas = [];
        if ($businessId) {
            foreach (['owned_whatsapp_business_accounts', 'client_whatsapp_business_accounts'] as $edge) {
                try {
                    $result = $this->graph($businessId . '/' . $edge, [
                        'fields' => 'id,name,currency,timezone_id,account_review_status,phone_numbers{id,display_phone_number,verified_name,quality_rating,messaging_limit_tier,status}',
                        'limit' => 100,
                    ], $accessToken, 'GET');
                    $wabas = array_merge($wabas, $result['data'] ?? []);
                } catch (\Throwable $e) {
                    error_log('WABA sync edge failed: ' . $edge . ' ' . $e->getMessage());
                }
            }
        }

        if (!$wabas && !empty($hints['waba_id'])) {
            $wabas[] = ['id' => (string) $hints['waba_id'], 'name' => 'WhatsApp Business Account'];
        }

        $pdo = Database::pdo();
        foreach ($wabas as $waba) {
            $stmt = $pdo->prepare('INSERT INTO whatsapp_business_accounts (store_id, meta_connection_id, waba_id, business_name, verification_status, currency, timezone_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE meta_connection_id = VALUES(meta_connection_id), business_name = VALUES(business_name), verification_status = VALUES(verification_status), currency = VALUES(currency), timezone_id = VALUES(timezone_id), updated_at = NOW()');
            $stmt->execute([
                $storeId,
                $connectionId,
                (string) $waba['id'],
                $waba['name'] ?? null,
                $waba['account_review_status'] ?? null,
                $waba['currency'] ?? null,
                $waba['timezone_id'] ?? null,
            ]);
            $localWabaId = (int) $pdo->lastInsertId();
            if ($localWabaId === 0) {
                $lookup = $pdo->prepare('SELECT id FROM whatsapp_business_accounts WHERE store_id = ? AND waba_id = ?');
                $lookup->execute([$storeId, (string) $waba['id']]);
                $localWabaId = (int) $lookup->fetchColumn();
            }

            $phones = $waba['phone_numbers']['data'] ?? [];
            if (!$phones && !empty($hints['phone_number_id'])) {
                $phones[] = ['id' => (string) $hints['phone_number_id'], 'display_phone_number' => 'Connected phone'];
            }
            $this->storePhoneNumbers($storeId, $localWabaId, $phones);
            $this->subscribeAppToWaba((string) $waba['id'], $accessToken);
        }

        return $wabas;
    }

    private function storePhoneNumbers(int $storeId, int $localWabaId, array $phones): void
    {
        $pdo = Database::pdo();
        foreach ($phones as $phone) {
            $isPrimary = $pdo->prepare('SELECT COUNT(*) FROM whatsapp_phone_numbers WHERE store_id = ?');
            $isPrimary->execute([$storeId]);
            $stmt = $pdo->prepare('INSERT INTO whatsapp_phone_numbers (store_id, waba_id, phone_number_id, display_phone_number, verified_name, quality_rating, messaging_limit, webhook_status, status, is_primary, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE display_phone_number = VALUES(display_phone_number), verified_name = VALUES(verified_name), quality_rating = VALUES(quality_rating), messaging_limit = VALUES(messaging_limit), status = VALUES(status), updated_at = NOW()');
            $stmt->execute([
                $storeId,
                $localWabaId,
                (string) $phone['id'],
                (string) ($phone['display_phone_number'] ?? ''),
                $phone['verified_name'] ?? null,
                $phone['quality_rating'] ?? null,
                $phone['messaging_limit_tier'] ?? ($phone['messaging_limit'] ?? null),
                'verified',
                $phone['status'] ?? null,
                ((int) $isPrimary->fetchColumn()) === 0 ? 1 : 0,
            ]);
        }
    }

    private function subscribeAppToWaba(string $wabaId, string $accessToken): void
    {
        try {
            $this->graph($wabaId . '/subscribed_apps', [], $accessToken, 'POST');
        } catch (\Throwable $e) {
            error_log('WABA subscribed_apps failed: ' . $e->getMessage());
        }
    }

    public function graph(string $edge, array $params = [], ?string $accessToken = null, string $method = 'GET'): array
    {
        $url = 'https://graph.facebook.com/' . $this->version() . '/' . ltrim($edge, '/');
        $headers = ['Accept: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init($method === 'GET' ? $url . '?' . http_build_query($params) : $url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false || $status >= 400) {
            throw new \RuntimeException('Meta Graph API request failed: ' . ($response ?: curl_error($ch)));
        }

        return json_decode($response, true) ?: [];
    }

    private function version(): string
    {
        return Env::get('WHATSAPP_API_VERSION', Env::get('META_GRAPH_VERSION', 'v23.0'));
    }
}
