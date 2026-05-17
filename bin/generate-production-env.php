<?php

declare(strict_types=1);

$domain = rtrim((string) ($argv[1] ?? ''), '/');
$output = $argv[2] ?? '.env.production.generated';

if ($domain === '' || !str_starts_with($domain, 'https://')) {
    fwrite(STDERR, "Usage: php bin/generate-production-env.php https://your-domain.com [.env.production.generated]\n");
    fwrite(STDERR, "The domain must use HTTPS.\n");
    exit(1);
}

$host = parse_url($domain, PHP_URL_HOST);
if (!$host || in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Production domain cannot be localhost.\n");
    exit(1);
}

$secret = static fn (int $bytes = 32): string => bin2hex(random_bytes($bytes));

$content = <<<ENV
APP_NAME="Marketing Center"
APP_ENV=production
APP_DEBUG=false
APP_URL={$domain}
APP_KEY={$secret()}
ENCRYPTION_KEY={$secret()}
JWT_SECRET={$secret()}
CSRF_ENFORCE=true
AUTH_ENFORCE=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketing_center
DB_USERNAME=root
DB_PASSWORD=
DATABASE_URL=mysql://root:@127.0.0.1:3306/marketing_center

META_APP_ID=
META_APP_SECRET=
META_CONFIG_ID=
META_GRAPH_VERSION=v23.0
WHATSAPP_API_VERSION=v23.0
META_REDIRECT_URI={$domain}/api/meta/callback
META_VERIFY_TOKEN={$secret(24)}
META_WEBHOOK_SECRET=
META_WEBHOOK_VERIFY_TOKEN=

DEFAULT_STORE_ID=1
RATE_LIMIT_PER_MINUTE=120
CAMPAIGN_BATCH_SIZE=25
CAMPAIGN_RETRY_LIMIT=3
QUEUE_CONNECTION=redis
QUEUE_REDIS_URL=redis://127.0.0.1:6379/0
STORAGE_PROVIDER=local
STORAGE_ACCESS_KEY=
STORAGE_SECRET_KEY=
PUBLIC_APP_URL={$domain}
NEXT_PUBLIC_APP_NAME="Marketing Center"

WHATSAPP_QR_BRIDGE_URL=http://127.0.0.1:3020
WHATSAPP_QR_BRIDGE_TOKEN={$secret(24)}
WHATSAPP_QR_SAFE_BATCH_SIZE=5
WHATSAPP_QR_MIN_DELAY_SECONDS=2
WHATSAPP_QR_MAX_DELAY_SECONDS=8
SESSION_TIMEOUT_MINUTES=60
OBSERVABILITY_DSN=
BACKUP_STORAGE_URL=
ENV;

if (is_file($output)) {
    fwrite(STDERR, "{$output} already exists. Choose another output path.\n");
    exit(1);
}

file_put_contents($output, $content . PHP_EOL);
echo "Generated {$output}\n";
echo "Fill META_APP_ID, META_APP_SECRET, META_WEBHOOK_SECRET, DATABASE_URL, storage, monitoring, and backup values before launch.\n";
