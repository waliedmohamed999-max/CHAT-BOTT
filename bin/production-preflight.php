<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envPath = (string) ($argv[1] ?? '.env');
$envAbsolutePath = is_file($envPath) ? realpath($envPath) : $root . DIRECTORY_SEPARATOR . ltrim($envPath, DIRECTORY_SEPARATOR);
$results = [];

echo "Marketing Center Production Preflight\n";
echo "=====================================\n";
echo "Environment file: {$envPath}\n\n";

$syntax = phpSyntaxCheck($root);
addResult($results, 'PHP Syntax', $syntax['ok'], $syntax['message'], $syntax['details']);

$env = runCommand([PHP_BINARY, $root . '/bin/verify-production-env.php', $envPath], $root);
addResult($results, 'Production Environment', $env['exit_code'] === 0, 'Checks .env safety, HTTPS, secrets, auth, CSRF, and queue settings.', $env['output']);

$databaseFoundation = runCommand([PHP_BINARY, $root . '/bin/database-foundation-check.php'], $root);
addResult($results, 'Database Foundation', $databaseFoundation['exit_code'] === 0, 'Checks core tables, indexes, default roles, departments, owner, workspace, and subscription seed.', $databaseFoundation['output']);

require $root . '/app/bootstrap.php';

if (is_file($envAbsolutePath)) {
    MarketingCenter\Support\Env::load((string) $envAbsolutePath);
}

try {
    MarketingCenter\Support\Database::pdo()->query('SELECT 1');
    addResult($results, 'Database Connection', true, 'Database connection is reachable.');
} catch (Throwable $exception) {
    addResult($results, 'Database Connection', false, 'Database connection failed.', [$exception->getMessage()]);
}

try {
    $storeId = (int) (MarketingCenter\Support\Env::get('DEFAULT_STORE_ID', '1') ?? '1');
    $queue = (new MarketingCenter\Services\QueueMonitoringService())->status($storeId);
    addResult(
        $results,
        'Queue Monitoring',
        !empty($queue['ready']),
        'Queue monitoring tables and connection are available.',
        [
            'connection=' . (string) ($queue['connection'] ?? 'unknown'),
            'redis_reachable=' . (($queue['redis_reachable'] ?? null) === null ? 'not_applicable' : (!empty($queue['redis_reachable']) ? 'yes' : 'no')),
            'pending=' . (string) (($queue['totals']['pending'] ?? 0)),
            'processing=' . (string) (($queue['totals']['processing'] ?? 0)),
            'failed=' . (string) (($queue['totals']['failed'] ?? 0)),
        ]
    );
} catch (Throwable $exception) {
    addResult($results, 'Queue Monitoring', false, 'Queue monitoring failed.', [$exception->getMessage()]);
}

try {
    $storeId = (int) (MarketingCenter\Support\Env::get('DEFAULT_STORE_ID', '1') ?? '1');
    $roadmap = (new MarketingCenter\Services\PlatformDevelopmentRoadmapService())->overview($storeId);
    $phase = $roadmap['current_phase'] ?? [];
    $ready = ($phase['status'] ?? '') === 'production_ready';
    $details = [
        'current_phase=' . (string) ($phase['title_ar'] ?? 'unknown'),
        'status=' . (string) ($phase['status_label'] ?? 'unknown'),
        'launch_score=' . (string) ((int) ($phase['launch_score'] ?? 0)) . '%',
    ];

    foreach (($phase['open_issues'] ?? []) as $issue) {
        $details[] = strtoupper((string) ($issue['severity'] ?? 'warning')) . ': ' . (string) ($issue['label'] ?? '') . ' - ' . (string) ($issue['message'] ?? '');
    }

    addResult($results, 'Phase Gate', $ready, 'Current phase must be Production Ready before Phase 2.', $details);
} catch (Throwable $exception) {
    addResult($results, 'Phase Gate', false, 'Roadmap phase gate failed.', [$exception->getMessage()]);
}

foreach ($results as $result) {
    echo ($result['ok'] ? '[OK] ' : '[FAIL] ') . $result['name'] . ' - ' . $result['message'] . PHP_EOL;
    foreach ($result['details'] as $detail) {
        echo '  ' . $detail . PHP_EOL;
    }
    echo PHP_EOL;
}

$failed = array_values(array_filter($results, static fn (array $result): bool => !$result['ok']));

if ($failed !== []) {
    echo "Result: NOT READY. Phase 2 remains blocked until failed checks pass.\n";
    exit(1);
}

echo "Result: READY. Phase 1 gate passed.\n";
exit(0);

function addResult(array &$results, string $name, bool $ok, string $message, array $details = []): void
{
    $results[] = [
        'name' => $name,
        'ok' => $ok,
        'message' => $message,
        'details' => $details,
    ];
}

function phpSyntaxCheck(string $root): array
{
    $directories = ['app', 'public', 'resources', 'bin'];
    $files = [];
    $errors = [];

    foreach ($directories as $directory) {
        $path = $root . DIRECTORY_SEPARATOR . $directory;
        if (!is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach ($files as $file) {
        $command = [PHP_BINARY, '-l', $file];
        $result = runCommand($command, $root);
        if ($result['exit_code'] !== 0) {
            $errors[] = $file . ': ' . implode(' ', $result['output']);
        }
    }

    return [
        'ok' => $errors === [],
        'message' => $errors === [] ? 'PHP syntax OK for ' . count($files) . ' files.' : 'PHP syntax errors found.',
        'details' => $errors,
    ];
}

function runCommand(array $parts, string $cwd): array
{
    $command = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $parts));
    $output = [];
    $exitCode = 0;
    $previousCwd = getcwd();

    chdir($cwd);
    exec($command . ' 2>&1', $output, $exitCode);
    if ($previousCwd !== false) {
        chdir($previousCwd);
    }

    return [
        'exit_code' => $exitCode,
        'output' => $output,
    ];
}
