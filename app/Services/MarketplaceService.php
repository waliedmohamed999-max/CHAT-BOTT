<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\AuditLogger;
use MarketingCenter\Support\Crypto;
use MarketingCenter\Support\Database;
use MarketingCenter\Support\Rbac;

final class MarketplaceService
{
    public function overview(int $storeId): array
    {
        return [
            'featured_apps' => array_slice($this->catalog($storeId, 'app'), 0, 6),
            'installed' => $this->installedApps($storeId),
            'developer' => $this->developerSummary($storeId),
            'stats' => $this->stats($storeId),
            'categories' => ['Apps', 'Integrations', 'Themes', 'Chatbot Templates', 'Automation Templates', 'AI Packs'],
        ];
    }

    public function catalog(int $storeId, ?string $type = null): array
    {
        try {
            $sql = 'SELECT a.*, COALESCE(i.status, "not_installed") install_status FROM marketplace_apps a LEFT JOIN marketplace_installations i ON i.app_id = a.id AND i.store_id = ? WHERE a.status = "published"';
            $params = [$storeId];
            if ($type) {
                $sql .= ' AND a.app_type = ?';
                $params[] = $type;
            }
            $sql .= ' ORDER BY a.featured DESC, a.rating DESC, a.install_count DESC, a.id DESC';
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            return $rows ?: $this->seedCatalog($type);
        } catch (\Throwable) {
            return $this->seedCatalog($type);
        }
    }

    public function installedApps(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT i.*, a.name, a.slug, a.category, a.app_type, a.icon, a.permissions_json FROM marketplace_installations i JOIN marketplace_apps a ON a.id = i.app_id WHERE i.store_id = ? ORDER BY i.installed_at DESC');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function install(int $storeId, int $appId): array
    {
        try {
            $app = $this->app($appId);
            if (!$app) {
                return ['installed' => false, 'message' => 'التطبيق غير موجود'];
            }
            $this->ensureCatalogAppExists($app);
            $permissions = json_decode((string) ($app['permissions_json'] ?? '[]'), true) ?: [];
            $stmt = Database::pdo()->prepare("INSERT INTO marketplace_installations (store_id, app_id, installed_by, status, permissions_json, settings_json, installed_at, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, ?, NOW(), NOW(), NOW()) ON DUPLICATE KEY UPDATE status = 'active', permissions_json = VALUES(permissions_json), updated_at = NOW()");
            $stmt->execute([
                $storeId,
                $appId,
                Rbac::userId(),
                json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode(['sandbox' => true, 'api_limits' => ['requests_per_minute' => 120]], JSON_UNESCAPED_UNICODE),
            ]);
            Database::pdo()->prepare('UPDATE marketplace_apps SET install_count = install_count + 1 WHERE id = ?')->execute([$appId]);
            AuditLogger::record('marketplace.app_installed', $storeId, Rbac::userId(), 'marketplace_app', $appId, ['permissions' => $permissions]);
            return ['installed' => true, 'review_required' => false, 'permissions' => $permissions];
        } catch (\Throwable) {
            return ['installed' => false, 'message' => 'قاعدة بيانات Marketplace غير مفعلة بعد'];
        }
    }

    public function uninstall(int $storeId, int $appId): array
    {
        try {
            Database::pdo()->prepare("UPDATE marketplace_installations SET status = 'uninstalled', updated_at = NOW() WHERE store_id = ? AND app_id = ?")->execute([$storeId, $appId]);
            AuditLogger::record('marketplace.app_uninstalled', $storeId, Rbac::userId(), 'marketplace_app', $appId);
        } catch (\Throwable) {
        }
        return ['uninstalled' => true];
    }

    public function createApiKey(int $storeId, array $input): array
    {
        $plainKey = 'mc_' . bin2hex(random_bytes(24));
        $hash = hash('sha256', $plainKey);
        $scopes = $input['scopes'] ?? ['read:contacts', 'write:webhooks'];
        if (is_string($scopes)) {
            $scopes = array_filter(array_map('trim', explode(',', $scopes)));
        }
        try {
            $stmt = Database::pdo()->prepare("INSERT INTO developer_api_keys (store_id, name, key_hash, scopes_json, rate_limit_per_minute, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())");
            $stmt->execute([
                $storeId,
                trim((string) ($input['name'] ?? 'Production API Key')),
                $hash,
                json_encode(array_values($scopes), JSON_UNESCAPED_UNICODE),
                max(10, min(1000, (int) ($input['rate_limit_per_minute'] ?? 120))),
                Rbac::userId(),
            ]);
            AuditLogger::record('developer.api_key_created', $storeId, Rbac::userId(), 'developer_api_key', (int) Database::pdo()->lastInsertId());
        } catch (\Throwable) {
            return ['created' => false, 'message' => 'قاعدة بيانات Developer Platform غير مفعلة بعد'];
        }
        return ['created' => true, 'api_key' => $plainKey, 'warning' => 'سيظهر المفتاح مرة واحدة فقط'];
    }

    public function apiKeys(int $storeId): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT id, name, scopes_json, rate_limit_per_minute, status, last_used_at, created_at FROM developer_api_keys WHERE store_id = ? ORDER BY id DESC');
            $stmt->execute([$storeId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function createOAuthApp(int $storeId, array $input): array
    {
        $clientId = 'app_' . bin2hex(random_bytes(12));
        $clientSecret = bin2hex(random_bytes(24));
        try {
            $stmt = Database::pdo()->prepare("INSERT INTO developer_oauth_apps (store_id, name, client_id, encrypted_client_secret, redirect_uris_json, scopes_json, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, NOW(), NOW())");
            $stmt->execute([
                $storeId,
                trim((string) ($input['name'] ?? 'OAuth App')),
                $clientId,
                Crypto::encrypt($clientSecret),
                json_encode(array_values((array) ($input['redirect_uris'] ?? [])), JSON_UNESCAPED_SLASHES),
                json_encode(array_values((array) ($input['scopes'] ?? ['read:profile'])), JSON_UNESCAPED_UNICODE),
                Rbac::userId(),
            ]);
            return ['created' => true, 'client_id' => $clientId, 'client_secret' => $clientSecret, 'status' => 'draft'];
        } catch (\Throwable) {
            return ['created' => false, 'message' => 'تعذر إنشاء OAuth App قبل تفعيل الجداول'];
        }
    }

    public function registerWebhook(int $storeId, array $input): array
    {
        try {
            $stmt = Database::pdo()->prepare("INSERT INTO developer_webhook_endpoints (store_id, app_id, url, events_json, secret_encrypted, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())");
            $stmt->execute([
                $storeId,
                isset($input['app_id']) ? (int) $input['app_id'] : null,
                (string) ($input['url'] ?? ''),
                json_encode(array_values((array) ($input['events'] ?? ['message.created'])), JSON_UNESCAPED_UNICODE),
                Crypto::encrypt((string) ($input['secret'] ?? bin2hex(random_bytes(16)))),
            ]);
            return ['registered' => true, 'id' => (int) Database::pdo()->lastInsertId()];
        } catch (\Throwable) {
            return ['registered' => false, 'message' => 'تعذر تسجيل Webhook قبل تفعيل الجداول'];
        }
    }

    public function pluginManifest(): array
    {
        return [
            'loader' => 'isolated_manifest_loader',
            'sandbox' => ['no_direct_db', 'signed_webhooks_only', 'scoped_api_tokens', 'rate_limits'],
            'extension_points' => ['custom_widgets', 'chatbot_nodes', 'ai_actions', 'automation_steps', 'analytics_cards'],
            'event_bus' => ['message.created', 'contact.updated', 'campaign.launched', 'order.paid', 'bot.handover'],
            'api_gateway' => ['oauth2', 'api_keys', 'scopes', 'audit_logs'],
        ];
    }

    public function superAdminReview(): array
    {
        try {
            $apps = Database::pdo()->query("SELECT * FROM marketplace_apps WHERE status IN ('draft','pending_review') ORDER BY id DESC LIMIT 100")->fetchAll();
            $reviews = Database::pdo()->query('SELECT r.*, a.name app_name FROM marketplace_reviews r JOIN marketplace_apps a ON a.id = r.app_id ORDER BY r.id DESC LIMIT 50')->fetchAll();
            return ['pending_apps' => $apps, 'latest_reviews' => $reviews];
        } catch (\Throwable) {
            return ['pending_apps' => [], 'latest_reviews' => []];
        }
    }

    private function stats(int $storeId): array
    {
        try {
            $pdo = Database::pdo();
            return [
                'apps' => (int) $pdo->query("SELECT COUNT(*) FROM marketplace_apps WHERE status = 'published'")->fetchColumn(),
                'installed' => (int) $pdo->query("SELECT COUNT(*) FROM marketplace_installations WHERE store_id = {$storeId} AND status = 'active'")->fetchColumn(),
                'api_keys' => (int) $pdo->query("SELECT COUNT(*) FROM developer_api_keys WHERE store_id = {$storeId} AND status = 'active'")->fetchColumn(),
                'webhooks' => (int) $pdo->query("SELECT COUNT(*) FROM developer_webhook_endpoints WHERE store_id = {$storeId} AND status = 'active'")->fetchColumn(),
            ];
        } catch (\Throwable) {
            return ['apps' => count($this->seedCatalog()), 'installed' => 0, 'api_keys' => 0, 'webhooks' => 0];
        }
    }

    private function developerSummary(int $storeId): array
    {
        return [
            'api_keys' => $this->apiKeys($storeId),
            'plugin_system' => $this->pluginManifest(),
            'docs' => [
                ['title' => 'Authentication', 'path' => '/docs/api/authentication'],
                ['title' => 'Webhook Events', 'path' => '/docs/api/webhooks'],
                ['title' => 'Custom Chatbot Nodes', 'path' => '/docs/plugins/chatbot-nodes'],
                ['title' => 'AI Actions SDK', 'path' => '/docs/sdk/ai-actions'],
            ],
        ];
    }

    private function app(int $appId): ?array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM marketplace_apps WHERE id = ? LIMIT 1');
            $stmt->execute([$appId]);
            $app = $stmt->fetch();
            if ($app) {
                return $app;
            }
        } catch (\Throwable) {
        }
        foreach ($this->seedCatalog() as $app) {
            if ((int) $app['id'] === $appId) {
                return $app;
            }
        }
        return null;
    }

    private function ensureCatalogAppExists(array $app): void
    {
        try {
            $stmt = Database::pdo()->prepare("INSERT IGNORE INTO marketplace_apps (id, name, slug, category, app_type, icon, short_description, permissions_json, pricing_model, rating, featured, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())");
            $stmt->execute([
                (int) $app['id'],
                (string) $app['name'],
                (string) $app['slug'],
                (string) $app['category'],
                (string) $app['app_type'],
                (string) ($app['icon'] ?? 'AP'),
                (string) ($app['short_description'] ?? ''),
                (string) ($app['permissions_json'] ?? '[]'),
                (string) ($app['pricing_model'] ?? 'free'),
                (float) ($app['rating'] ?? 0),
                (int) ($app['featured'] ?? 0),
            ]);
        } catch (\Throwable) {
        }
    }

    private function seedCatalog(?string $type = null): array
    {
        $apps = [
            ['id' => 1, 'name' => 'Shopify Integration', 'slug' => 'shopify', 'category' => 'Commerce', 'app_type' => 'integration', 'icon' => 'SH', 'short_description' => 'مزامنة الطلبات والعملاء والسلة المتروكة من Shopify.', 'rating' => 4.9, 'pricing_model' => 'subscription', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:orders","read:customers","write:automations"]'],
            ['id' => 2, 'name' => 'WooCommerce', 'slug' => 'woocommerce', 'category' => 'Commerce', 'app_type' => 'integration', 'icon' => 'WC', 'short_description' => 'ربط منتجات وطلبات WooCommerce مع CRM والأتمتة.', 'rating' => 4.8, 'pricing_model' => 'free', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:orders","read:products"]'],
            ['id' => 3, 'name' => 'Salla', 'slug' => 'salla', 'category' => 'Commerce', 'app_type' => 'integration', 'icon' => 'SA', 'short_description' => 'تكامل سلة للمتاجر العربية مع رسائل واتساب.', 'rating' => 4.8, 'pricing_model' => 'subscription', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:orders","read:customers"]'],
            ['id' => 4, 'name' => 'Zid', 'slug' => 'zid', 'category' => 'Commerce', 'app_type' => 'integration', 'icon' => 'ZD', 'short_description' => 'ربط زد مع الحملات والشات بوت.', 'rating' => 4.7, 'pricing_model' => 'subscription', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:orders","read:products"]'],
            ['id' => 5, 'name' => 'Stripe', 'slug' => 'stripe', 'category' => 'Payments', 'app_type' => 'app', 'icon' => 'ST', 'short_description' => 'مدفوعات وفواتير وروابط دفع داخل المحادثة.', 'rating' => 4.9, 'pricing_model' => 'revenue_share', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:payments","write:payment_links"]'],
            ['id' => 6, 'name' => 'PayPal', 'slug' => 'paypal', 'category' => 'Payments', 'app_type' => 'app', 'icon' => 'PP', 'short_description' => 'تحصيل المدفوعات وإرسال الروابط للعملاء.', 'rating' => 4.6, 'pricing_model' => 'revenue_share', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:payments","write:payment_links"]'],
            ['id' => 7, 'name' => 'Google Sheets', 'slug' => 'google-sheets', 'category' => 'Productivity', 'app_type' => 'app', 'icon' => 'GS', 'short_description' => 'تصدير العملاء والحملات تلقائياً إلى Google Sheets.', 'rating' => 4.7, 'pricing_model' => 'free', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:contacts","read:campaigns"]'],
            ['id' => 8, 'name' => 'Slack', 'slug' => 'slack', 'category' => 'Team', 'app_type' => 'app', 'icon' => 'SL', 'short_description' => 'تنبيهات الفريق والتحويلات والـ Smart Alerts.', 'rating' => 4.8, 'pricing_model' => 'free', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:alerts","write:notifications"]'],
            ['id' => 9, 'name' => 'Zoom', 'slug' => 'zoom', 'category' => 'Meetings', 'app_type' => 'app', 'icon' => 'ZM', 'short_description' => 'إنشاء اجتماعات للفرص عالية القيمة من Inbox.', 'rating' => 4.5, 'pricing_model' => 'free', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["write:meetings"]'],
            ['id' => 10, 'name' => 'Telegram', 'slug' => 'telegram', 'category' => 'Messaging', 'app_type' => 'integration', 'icon' => 'TG', 'short_description' => 'قناة Telegram ضمن Omnichannel Inbox.', 'rating' => 4.7, 'pricing_model' => 'free', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:messages","write:messages"]'],
            ['id' => 11, 'name' => 'TikTok Ads', 'slug' => 'tiktok-ads', 'category' => 'Ads', 'app_type' => 'integration', 'icon' => 'TT', 'short_description' => 'استيراد الحملات والجماهير من TikTok Ads.', 'rating' => 4.6, 'pricing_model' => 'subscription', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:ads","read:audiences"]'],
            ['id' => 12, 'name' => 'Snapchat Ads', 'slug' => 'snapchat-ads', 'category' => 'Ads', 'app_type' => 'integration', 'icon' => 'SC', 'short_description' => 'قياس أداء Snapchat وإعادة الاستهداف.', 'rating' => 4.5, 'pricing_model' => 'subscription', 'featured' => 0, 'install_status' => 'not_installed', 'permissions_json' => '["read:ads","read:audiences"]'],
            ['id' => 13, 'name' => 'Google Analytics', 'slug' => 'google-analytics', 'category' => 'Analytics', 'app_type' => 'integration', 'icon' => 'GA', 'short_description' => 'ربط زيارات الموقع بالـ CRM والحملات.', 'rating' => 4.8, 'pricing_model' => 'free', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:analytics"]'],
            ['id' => 14, 'name' => 'Meta Ads', 'slug' => 'meta-ads', 'category' => 'Ads', 'app_type' => 'integration', 'icon' => 'MA', 'short_description' => 'مزامنة الإعلانات والجماهير مع WhatsApp Campaigns.', 'rating' => 4.9, 'pricing_model' => 'subscription', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:ads","write:audiences"]'],
            ['id' => 15, 'name' => 'Sales AI Agent Pack', 'slug' => 'sales-ai-agent', 'category' => 'AI', 'app_type' => 'ai_pack', 'icon' => 'AI', 'short_description' => 'شخصية مبيعات جاهزة للشات بوت وقاعدة معرفة مبيعات.', 'rating' => 4.9, 'pricing_model' => 'subscription', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["read:knowledge","write:ai_actions"]'],
            ['id' => 16, 'name' => 'Abandoned Cart Automation', 'slug' => 'abandoned-cart-template', 'category' => 'Templates', 'app_type' => 'automation_template', 'icon' => 'AT', 'short_description' => 'قالب استرجاع السلة المتروكة جاهز للتثبيت.', 'rating' => 4.8, 'pricing_model' => 'one_time', 'featured' => 1, 'install_status' => 'not_installed', 'permissions_json' => '["write:automations"]'],
        ];
        if (!$type) {
            return $apps;
        }
        return array_values(array_filter($apps, static fn (array $app): bool => $app['app_type'] === $type || ($type === 'app' && in_array($app['app_type'], ['app', 'integration'], true))));
    }
}
