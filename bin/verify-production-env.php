<?php

declare(strict_types=1);

$path = (string) ($argv[1] ?? '.env');

if (!is_file($path)) {
    fwrite(STDERR, "Environment file not found: {$path}\n");
    fwrite(STDERR, "Usage: php bin/verify-production-env.php [.env]\n");
    exit(1);
}

$env = parseEnvFile($path);
$checks = [];
$warnings = [];

addEqualsCheck($checks, $env, 'APP_ENV', 'production', 'APP_ENV must be production.');
addEqualsCheck($checks, $env, 'APP_DEBUG', 'false', 'APP_DEBUG must be false.');
addEqualsCheck($checks, $env, 'AUTH_ENFORCE', 'true', 'AUTH_ENFORCE must be true.');
addEqualsCheck($checks, $env, 'CSRF_ENFORCE', 'true', 'CSRF_ENFORCE must be true.');
addHttpsCheck($checks, $env, 'APP_URL', 'APP_URL must use HTTPS and a real production domain.');
addHttpsCheck($checks, $env, 'PUBLIC_APP_URL', 'PUBLIC_APP_URL must use HTTPS and a real production domain.');
addFilledCheck($checks, $env, 'DATABASE_URL', 'DATABASE_URL is required.');
addSecretCheck($checks, $env, 'JWT_SECRET', 32, 'JWT_SECRET must be at least 32 characters and not a placeholder.');
addSecretCheck($checks, $env, 'ENCRYPTION_KEY', 32, 'ENCRYPTION_KEY must be at least 32 characters and not a placeholder.');
addSecretCheck($checks, $env, 'META_APP_ID', 5, 'META_APP_ID is required.');
addSecretCheck($checks, $env, 'META_APP_SECRET', 16, 'META_APP_SECRET must be a real Meta secret.');
addSecretCheck($checks, $env, 'META_VERIFY_TOKEN', 16, 'META_VERIFY_TOKEN must be a strong verify token.');
addSecretCheck($checks, $env, 'META_WEBHOOK_SECRET', 16, 'META_WEBHOOK_SECRET must be a real webhook signing secret.');
addMetaRedirectCheck($checks, $env);
addFilledCheck($checks, $env, 'WHATSAPP_API_VERSION', 'WHATSAPP_API_VERSION is required.');
addEqualsCheck($checks, $env, 'QUEUE_CONNECTION', 'redis', 'QUEUE_CONNECTION must be redis in production.');
addFilledCheck($checks, $env, 'QUEUE_REDIS_URL', 'QUEUE_REDIS_URL is required.');
addNumberCheck($checks, $env, 'RATE_LIMIT_PER_MINUTE', 1, 'RATE_LIMIT_PER_MINUTE must be greater than zero.');
addFilledCheck($checks, $env, 'STORAGE_PROVIDER', 'STORAGE_PROVIDER is required.');
addFilledCheck($checks, $env, 'NEXT_PUBLIC_APP_NAME', 'NEXT_PUBLIC_APP_NAME is required.');

$storageProvider = strtolower(getEnvValue($env, 'STORAGE_PROVIDER'));
if ($storageProvider === 'local') {
    $warnings[] = ['key' => 'STORAGE_PROVIDER', 'message' => 'Local storage works, but external/private object storage is recommended for production documents.'];
} else {
    addFilledCheck($checks, $env, 'STORAGE_ACCESS_KEY', 'STORAGE_ACCESS_KEY is required for non-local storage.');
    addFilledCheck($checks, $env, 'STORAGE_SECRET_KEY', 'STORAGE_SECRET_KEY is required for non-local storage.');
}

foreach (['OBSERVABILITY_DSN', 'BACKUP_STORAGE_URL'] as $key) {
    if (!filled($env, $key)) {
        $warnings[] = ['key' => $key, 'message' => "{$key} is recommended before launch."];
    }
}

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ready']));

echo "Production Environment Verification\n";
echo "===================================\n";
echo "File: {$path}\n\n";

foreach ($checks as $check) {
    echo ($check['ready'] ? '[OK] ' : '[FAIL] ') . $check['key'] . ' - ' . $check['message'] . PHP_EOL;
}

if ($warnings !== []) {
    echo "\nWarnings:\n";
    foreach ($warnings as $warning) {
        echo '[WARN] ' . $warning['key'] . ' - ' . $warning['message'] . PHP_EOL;
    }
}

if ($failed !== []) {
    echo "\nResult: NOT READY. Fix the failed environment checks before Phase 2.\n";
    exit(1);
}

echo "\nResult: ENV READY. Run php bin/core-foundation-check.php next.\n";
exit(0);

function parseEnvFile(string $path): array
{
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

function addCheck(array &$checks, string $key, bool $ready, string $message): void
{
    $checks[] = [
        'key' => $key,
        'ready' => $ready,
        'message' => $message,
    ];
}

function addEqualsCheck(array &$checks, array $env, string $key, string $expected, string $message): void
{
    addCheck($checks, $key, strtolower(getEnvValue($env, $key)) === strtolower($expected), $message);
}

function addFilledCheck(array &$checks, array $env, string $key, string $message): void
{
    addCheck($checks, $key, filled($env, $key), $message);
}

function addSecretCheck(array &$checks, array $env, string $key, int $minLength, string $message): void
{
    $value = getEnvValue($env, $key);
    addCheck($checks, $key, strlen($value) >= $minLength && !looksLikePlaceholder($value), $message);
}

function addNumberCheck(array &$checks, array $env, string $key, int $minimum, string $message): void
{
    addCheck($checks, $key, (int) getEnvValue($env, $key) >= $minimum, $message);
}

function addHttpsCheck(array &$checks, array $env, string $key, string $message): void
{
    addCheck($checks, $key, httpsValueReady(getEnvValue($env, $key)), $message);
}

function addMetaRedirectCheck(array &$checks, array $env): void
{
    $value = getEnvValue($env, 'META_REDIRECT_URI');
    $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
    addCheck(
        $checks,
        'META_REDIRECT_URI',
        httpsValueReady($value) && str_ends_with($path, '/api/meta/callback'),
        'META_REDIRECT_URI must use HTTPS and end with /api/meta/callback.'
    );
}

function filled(array $env, string $key): bool
{
    $value = getEnvValue($env, $key);
    return $value !== '' && !looksLikePlaceholder($value);
}

function getEnvValue(array $env, string $key): string
{
    return trim((string) ($env[$key] ?? ''));
}

function httpsValueReady(string $url): bool
{
    $url = rtrim(trim($url), '/');
    if ($url === '' || looksLikePlaceholder($url)) {
        return false;
    }

    $parts = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));

    return $scheme === 'https'
        && $host !== ''
        && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function looksLikePlaceholder(string $value): bool
{
    $lower = strtolower($value);
    foreach (['change-me', 'change-', 'replace-with', 'your-', 'example', 'placeholder', 'local-development-key', 'test-secret', 'jwt-secret', 'encryption-key'] as $fragment) {
        if (str_contains($lower, $fragment)) {
            return true;
        }
    }

    return false;
}
