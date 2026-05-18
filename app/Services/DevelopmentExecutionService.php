<?php

declare(strict_types=1);

namespace MarketingCenter\Services;

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

final class DevelopmentExecutionService
{
    private string $root;
    private string $runtimeDir;
    private string $tasksFile;
    private string $logsFile;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        $this->runtimeDir = $this->root . '/storage/development-execution';
        $this->tasksFile = $this->runtimeDir . '/tasks.json';
        $this->logsFile = $this->runtimeDir . '/logs.jsonl';
    }

    public function dashboard(int $storeId): array
    {
        $findings = $this->scan($storeId);
        $tasks = $this->mergeGeneratedTasks($this->loadTasks(), $this->tasksForFindings($findings));
        $logs = $this->readLogs();
        $stats = $this->stats($findings, $tasks);

        $this->persistTasks($tasks);

        return [
            'title' => 'AI Development & Completion Center',
            'write_enabled' => $this->writeEnabled(),
            'shell_enabled' => $this->shellEnabled(),
            'runtime_writable' => $this->runtimeWritable(),
            'config' => [
                'write_enabled' => $this->writeEnabled(),
                'shell_enabled' => $this->shellEnabled(),
                'runtime_writable' => $this->runtimeWritable(),
                'max_tasks_per_run' => max(1, (int) Env::get('DEVELOPMENT_EXECUTION_MAX_TASKS_PER_RUN', '5')),
            ],
            'stats' => $stats,
            'findings' => $findings,
            'tasks' => array_values($tasks),
            'logs' => $logs,
            'recommendations' => $this->recommendations($findings),
        ];
    }

    public function runTask(int $storeId, string $taskKey): array
    {
        $tasks = $this->mergeGeneratedTasks($this->loadTasks(), $this->tasksForFindings($this->scan($storeId)));
        if (!isset($tasks[$taskKey])) {
            throw new \RuntimeException('task_not_found');
        }

        $task = $tasks[$taskKey];
        $task['status'] = 'running';
        $task['started_at'] = date(DATE_ATOM);
        $task['attempts'] = (int) ($task['attempts'] ?? 0) + 1;
        $tasks[$taskKey] = $task;
        $this->persistTasks($tasks);
        $this->log($storeId, $taskKey, 'info', 'بدأ تنفيذ مهمة الإصلاح.', ['task' => $task['title'] ?? $taskKey]);

        try {
            $result = $this->execute($storeId, $taskKey);
            $task['status'] = 'completed';
            $task['completed_at'] = date(DATE_ATOM);
            $task['result'] = $result;
            $task['last_error'] = null;
            $this->log($storeId, $taskKey, 'success', 'اكتملت مهمة الإصلاح.', $result);
        } catch (\Throwable $e) {
            $task['status'] = $e->getMessage() === 'manual_intervention_required' ? 'manual_required' : 'failed';
            $task['last_error'] = $e->getMessage();
            $task['completed_at'] = date(DATE_ATOM);
            $this->log($storeId, $taskKey, 'error', 'فشلت مهمة الإصلاح.', ['error' => $e->getMessage()]);
        }

        $tasks[$taskKey] = $task;
        $this->persistTasks($tasks);

        return [
            'task' => $task,
            'dashboard' => $this->dashboard($storeId),
        ];
    }

    public function runAutoFixes(int $storeId): array
    {
        $dashboard = $this->dashboard($storeId);
        $limit = max(1, (int) Env::get('DEVELOPMENT_EXECUTION_MAX_TASKS_PER_RUN', '5'));
        $executed = [];

        foreach ($dashboard['tasks'] as $task) {
            if (count($executed) >= $limit) {
                break;
            }

            if (!in_array((string) ($task['status'] ?? 'pending'), ['pending', 'failed'], true)) {
                continue;
            }

            if (empty($task['auto_fix'])) {
                continue;
            }

            $executed[] = $this->runTask($storeId, (string) $task['key'])['task'];
        }

        return [
            'executed' => $executed,
            'dashboard' => $this->dashboard($storeId),
        ];
    }

    private function scan(int $storeId): array
    {
        return array_values(array_merge(
            $this->scanPages(),
            $this->scanApis(),
            $this->scanDatabase(),
            $this->scanSecurity(),
            $this->scanEnvironment(),
            $this->scanWorkersAndWebhooks(),
            $this->scanTestingAndBuild(),
            $this->scanRuntime($storeId)
        ));
    }

    private function scanPages(): array
    {
        $view = $this->read('resources/views/marketing-center.php');
        $controller = $this->read('app/Controllers/MarketingController.php');
        $pages = [
            'overview', 'whatsapp-setup-center', 'connect-meta', 'whatsapp-setup', 'whatsapp-qr',
            'chatbot-builder', 'campaign-builder', 'templates', 'contacts', 'inbox', 'automation',
            'analytics', 'settings', 'saas', 'super-admin', 'platform-roadmap',
        ];

        $findings = [];
        foreach ($pages as $page) {
            $ready = str_contains($view, "\$page === '{$page}'") || str_contains($view, 'href="<?= htmlspecialchars($appUrl) ?>/marketing-center/' . $page);
            $allowed = str_contains($controller, "'" . $page . "'");
            $findings[] = $this->finding(
                'page_' . $page,
                'الصفحات',
                'صفحة ' . $page,
                $ready && $allowed,
                true,
                'أضف قسم UI ومسار الصفحة داخل MarketingController.',
                'write_completion_report'
            );
        }

        return $findings;
    }

    private function scanApis(): array
    {
        $index = $this->read('public/index.php');
        $apis = [
            '/api/platform-roadmap',
            '/api/development-roadmap',
            '/api/launch-readiness',
            '/api/chatbot/builder',
            '/api/whatsapp-setup/profile',
            '/api/whatsapp-qr/session/status',
            '/api/inbox',
            '/api/campaigns',
            '/api/analytics',
            '/api/development-execution',
        ];

        $findings = [];
        foreach ($apis as $api) {
            $findings[] = $this->finding(
                'api_' . preg_replace('/[^a-z0-9]+/i', '_', trim($api, '/')),
                'APIs',
                $api,
                str_contains($index, $api),
                true,
                'أضف Route في public/index.php واربطه بالـ Controller المناسب.',
                'write_route_inventory'
            );
        }

        return $findings;
    }

    private function scanDatabase(): array
    {
        $schema = $this->read('database/schema.sql') . "\n" . $this->read('database/development_execution_migration.sql');
        $tables = [
            'users', 'stores', 'workspace_members', 'role_permissions', 'audit_logs',
            'campaigns', 'campaign_messages', 'conversations', 'messages',
            'chatbot_flows', 'chatbot_nodes', 'chatbot_edges',
            'development_execution_tasks', 'development_execution_logs', 'development_execution_findings',
        ];

        $findings = [];
        foreach ($tables as $table) {
            $isDevelopmentTable = in_array($table, ['development_execution_tasks', 'development_execution_logs', 'development_execution_findings'], true);
            $schemaReady = (bool) preg_match('/CREATE TABLE IF NOT EXISTS\s+`?' . preg_quote($table, '/') . '`?/i', $schema);
            $databaseReady = !$isDevelopmentTable || !$this->databaseAvailable() || $this->databaseTableExists($table);
            $findings[] = $this->finding(
                'model_' . $table,
                'قاعدة البيانات',
                'Model/Table: ' . $table,
                $schemaReady && $databaseReady,
                $isDevelopmentTable,
                $schemaReady && !$databaseReady
                    ? 'طبّق Migration على قاعدة البيانات لإنشاء الجداول الفعلية.'
                    : 'أنشئ Migration يحتوي على الجدول والعلاقات والفهارس المطلوبة.',
                $schemaReady && !$databaseReady ? 'apply_development_execution_migration' : 'create_development_execution_migration'
            );
        }

        return $findings;
    }

    private function scanSecurity(): array
    {
        $security = $this->read('app/Support/Security.php');
        $index = $this->read('public/index.php');
        $rbac = $this->read('app/Support/Rbac.php');
        $tenant = $this->read('app/Support/TenantContext.php');
        $setup = $this->read('app/Services/WhatsAppSetupService.php');

        return [
            $this->finding('security_csrf', 'الأمان', 'CSRF Protection', str_contains($security, 'assertCsrfIfNeeded'), true, 'فعّل CSRF على طلبات POST/PUT/DELETE.', 'write_completion_report'),
            $this->finding('security_rate_limit', 'الأمان', 'Rate Limiting', str_contains($index, 'RateLimiter::assertAllowed'), true, 'اربط RateLimiter في Front Controller.', 'write_completion_report'),
            $this->finding('security_headers', 'الأمان', 'Security Headers / CSP', str_contains($security, 'Content-Security-Policy') && str_contains($security, 'X-Frame-Options'), true, 'أضف Security Headers الأساسية.', 'write_completion_report'),
            $this->finding('security_rbac', 'الأمان', 'RBAC Permissions', str_contains($rbac, 'developer.manage') && str_contains($rbac, 'inbox.reply'), true, 'راجع مصفوفة الصلاحيات وأضف مفاتيح التطوير والتنفيذ.', 'write_completion_report'),
            $this->finding('security_tenant', 'الأمان', 'Tenant Isolation', str_contains($tenant, 'assertStoreAccess'), true, 'اربط جميع الموارد بسياق المتجر النشط.', 'write_completion_report'),
            $this->finding('security_upload_validation', 'الأمان', 'Upload Validation', str_contains($setup, 'allowed') || str_contains($setup, 'fileMimeType'), false, 'أضف فحص MIME والحجم والامتداد لكل رفع ملفات.', 'write_completion_report'),
        ];
    }

    private function scanEnvironment(): array
    {
        $env = $this->read('.env.example');
        $keys = [
            'DATABASE_URL', 'JWT_SECRET', 'ENCRYPTION_KEY', 'META_APP_ID', 'META_APP_SECRET',
            'META_VERIFY_TOKEN', 'META_WEBHOOK_SECRET', 'WHATSAPP_API_VERSION', 'QUEUE_REDIS_URL',
            'PUBLIC_APP_URL', 'DEVELOPMENT_EXECUTION_WRITE_ENABLED', 'DEVELOPMENT_EXECUTION_SHELL_ENABLED',
        ];

        $findings = [];
        foreach ($keys as $key) {
            $findings[] = $this->finding(
                'env_' . strtolower($key),
                'Environment',
                'ENV: ' . $key,
                (bool) preg_match('/^' . preg_quote($key, '/') . '=/m', $env),
                true,
                'أضف المتغير إلى .env.example ووثقه قبل الإطلاق.',
                'sync_env_example'
            );
        }

        return $findings;
    }

    private function scanWorkersAndWebhooks(): array
    {
        return [
            $this->finding('worker_campaign_queue', 'Queue Workers', 'Campaign Queue Worker', is_file($this->path('bin/process-campaign-queue.php')), true, 'أنشئ Worker لمعالجة حملات واتساب تدريجياً.', 'write_completion_report'),
            $this->finding('worker_ai_queue', 'Queue Workers', 'AI Queue Worker', is_file($this->path('bin/process-ai-queue.php')), false, 'أنشئ Worker لمعالجة مهام AI الخلفية.', 'write_completion_report'),
            $this->finding('webhook_whatsapp', 'Webhooks', 'WhatsApp Webhook Receiver', is_file($this->path('app/Controllers/WebhookController.php')) && is_file($this->path('app/Services/WebhookService.php')), true, 'أنشئ Controller وService لاستقبال Webhooks.', 'write_completion_report'),
            $this->finding('webhook_logs', 'Logs', 'Webhook Logs Model', str_contains($this->read('database/schema.sql'), 'webhook_logs'), true, 'أضف جدول webhook_logs لتسجيل كل Payload.', 'create_development_execution_migration'),
            $this->finding('notifications', 'Notifications', 'Notification Logs', str_contains($this->read('database/schema.sql'), 'notification_logs'), false, 'أضف سجل تنبيهات وربطه بالأحداث المهمة.', 'create_development_execution_migration'),
        ];
    }

    private function scanTestingAndBuild(): array
    {
        $package = json_decode($this->read('package.json'), true) ?: [];
        $scripts = is_array($package['scripts'] ?? null) ? $package['scripts'] : [];

        return [
            $this->finding('build_script', 'Build', 'Build Script', isset($scripts['build']) && is_file($this->path('scripts/vercel-build.mjs')), true, 'أضف build script موحد للنشر.', 'run_static_preflight'),
            $this->finding('vercel_config', 'Build', 'Vercel Config', is_file($this->path('vercel.json')) && str_contains($this->read('vercel.json'), 'outputDirectory'), true, 'راجع إعداد Vercel والـ routes.', 'run_static_preflight'),
            $this->finding('pwa_assets', 'UI/UX', 'PWA Assets', is_file($this->path('public/manifest.webmanifest')) && is_file($this->path('public/sw.js')), false, 'أضف Manifest وService Worker للجوال.', 'run_static_preflight'),
            $this->finding('smoke_tests', 'Testing', 'Smoke Test Manifest', is_file($this->path('tests/smoke/route-smoke-checklist.md')), false, 'أنشئ قائمة Smoke Tests للمسارات الحرجة.', 'create_smoke_tests'),
            $this->finding('last_preflight', 'Testing', 'Last Static Preflight', is_file($this->path('storage/development-execution/preflight.json')), false, 'شغّل فحص Preflight بعد كل إصلاح.', 'run_static_preflight'),
        ];
    }

    private function scanRuntime(int $storeId): array
    {
        return [
            $this->finding('runtime_storage', 'Runtime', 'Storage Directories', is_dir($this->path('storage')) && is_writable($this->path('storage')), true, 'أنشئ مجلدات runtime قابلة للكتابة.', 'create_runtime_directories'),
            $this->finding('runtime_execution_dir', 'Runtime', 'Development Execution Runtime', is_dir($this->runtimeDir) && is_writable($this->runtimeDir), true, 'أنشئ مجلد تشغيل محرك التطوير.', 'create_runtime_directories'),
            $this->finding('runtime_db_connection', 'Runtime', 'Database Connection', $this->databaseAvailable(), true, 'اربط DATABASE_URL أو شغّل MySQL محلياً.', 'apply_development_execution_migration'),
            $this->finding('runtime_failed_jobs', 'Self-Healing', 'Failed Jobs Repair Hook', str_contains($this->read('database/schema.sql'), 'failed_jobs'), false, 'أضف Hook لتنظيف وإعادة جدولة failed_jobs.', 'repair_failed_jobs'),
        ];
    }

    private function tasksForFindings(array $findings): array
    {
        $tasks = [];
        foreach ($findings as $finding) {
            if (empty($finding['fixable']) || empty($finding['task_key']) || ($finding['status'] ?? '') === 'complete') {
                continue;
            }

            $key = (string) $finding['task_key'];
            $tasks[$key] ??= [
                'key' => $key,
                'category' => $finding['category'],
                'title' => $this->taskTitle($key),
                'status' => 'pending',
                'priority' => $finding['severity'] === 'critical' ? 'high' : 'normal',
                'auto_fix' => in_array($key, ['create_runtime_directories', 'create_development_execution_migration', 'apply_development_execution_migration', 'sync_env_example', 'write_route_inventory', 'write_completion_report', 'create_smoke_tests', 'run_static_preflight'], true),
                'findings' => [],
                'attempts' => 0,
            ];
            $tasks[$key]['findings'][] = $finding['key'];
        }

        $tasks['run_static_preflight'] ??= [
            'key' => 'run_static_preflight',
            'category' => 'Testing',
            'title' => $this->taskTitle('run_static_preflight'),
            'status' => 'pending',
            'priority' => 'high',
            'auto_fix' => true,
            'findings' => ['last_preflight'],
            'attempts' => 0,
        ];

        return $tasks;
    }

    private function execute(int $storeId, string $taskKey): array
    {
        return match ($taskKey) {
            'create_runtime_directories' => $this->createRuntimeDirectories(),
            'create_development_execution_migration' => $this->createDevelopmentExecutionMigration(),
            'apply_development_execution_migration' => $this->applyDevelopmentExecutionMigration(),
            'sync_env_example' => $this->syncEnvExample(),
            'write_route_inventory' => $this->writeRouteInventory(),
            'write_completion_report' => $this->writeCompletionReport($storeId),
            'create_smoke_tests' => $this->createSmokeTests(),
            'run_static_preflight' => $this->runStaticPreflight(),
            'repair_failed_jobs' => $this->repairFailedJobs(),
            default => throw new \RuntimeException('manual_intervention_required'),
        };
    }

    private function createRuntimeDirectories(): array
    {
        $this->assertWriteEnabled();
        $created = [];
        foreach (['storage', 'storage/development-execution', 'storage/logs', 'storage/uploads', 'storage/queue', 'storage/framework/views'] as $dir) {
            $path = $this->path($dir);
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
                $created[] = $dir;
            }
        }

        return ['created' => $created, 'runtime_dir' => 'storage/development-execution'];
    }

    private function createDevelopmentExecutionMigration(): array
    {
        $this->assertWriteEnabled();
        $path = $this->path('database/development_execution_migration.sql');
        if (!is_file($path)) {
            $this->write($path, $this->developmentMigrationSql());
            return ['created' => 'database/development_execution_migration.sql'];
        }

        return ['created' => false, 'message' => 'ملف Migration موجود بالفعل.'];
    }

    private function applyDevelopmentExecutionMigration(): array
    {
        if (!$this->databaseAvailable()) {
            throw new \RuntimeException('manual_intervention_required');
        }

        $sql = is_file($this->path('database/development_execution_migration.sql'))
            ? $this->read('database/development_execution_migration.sql')
            : $this->developmentMigrationSql();

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $pdo = Database::pdo();
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        return ['applied_statements' => count($statements)];
    }

    private function syncEnvExample(): array
    {
        $this->assertWriteEnabled();
        $path = $this->path('.env.example');
        $content = is_file($path) ? (string) file_get_contents($path) : '';
        $required = [
            'DEVELOPMENT_EXECUTION_WRITE_ENABLED=false',
            'DEVELOPMENT_EXECUTION_SHELL_ENABLED=false',
            'DEVELOPMENT_EXECUTION_AUTO_REPAIR=false',
            'DEVELOPMENT_EXECUTION_MAX_TASKS_PER_RUN=5',
        ];
        $added = [];

        foreach ($required as $line) {
            [$key] = explode('=', $line, 2);
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
                $content .= "\n" . $line;
                $added[] = $key;
            }
        }

        if ($added !== []) {
            $this->write($path, ltrim($content));
        }

        return ['added' => $added];
    }

    private function writeRouteInventory(): array
    {
        $this->assertWriteEnabled();
        $index = $this->read('public/index.php');
        preg_match_all("/\\\$path === '([^']+)'/", $index, $matches);
        $routes = array_values(array_unique($matches[1] ?? []));
        sort($routes);
        $path = $this->path('storage/development-execution/route-inventory.json');
        $this->write($path, json_encode(['generated_at' => date(DATE_ATOM), 'routes' => $routes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ['routes' => count($routes), 'file' => 'storage/development-execution/route-inventory.json'];
    }

    private function writeCompletionReport(int $storeId): array
    {
        $this->assertWriteEnabled();
        $findings = $this->scan($storeId);
        $missing = array_values(array_filter($findings, static fn (array $finding): bool => $finding['status'] !== 'complete'));
        $lines = [
            '# Development Completion Report',
            '',
            'Generated at: ' . date(DATE_ATOM),
            'Store ID: ' . $storeId,
            '',
            '## Missing Or Needs Review',
        ];

        foreach ($missing as $finding) {
            $lines[] = '- [' . strtoupper((string) $finding['severity']) . '] ' . $finding['category'] . ': ' . $finding['title'] . ' - ' . $finding['recommendation'];
        }

        if ($missing === []) {
            $lines[] = '- No open findings.';
        }

        $path = $this->path('storage/development-execution/completion-report.md');
        $this->write($path, implode("\n", $lines) . "\n");

        return ['open_findings' => count($missing), 'file' => 'storage/development-execution/completion-report.md'];
    }

    private function createSmokeTests(): array
    {
        $this->assertWriteEnabled();
        $path = $this->path('tests/smoke/route-smoke-checklist.md');
        if (is_file($path)) {
            return ['created' => false, 'file' => 'tests/smoke/route-smoke-checklist.md'];
        }

        $content = "# Smoke Test Checklist\n\n"
            . "- GET /login returns 200\n"
            . "- POST /api/auth/login authenticates a demo or database user\n"
            . "- GET /marketing-center returns 200 after login\n"
            . "- GET /platform/dashboard returns 200 for platform users\n"
            . "- GET /inbox returns 200 for agents\n"
            . "- GET /api/development-execution returns scan results\n"
            . "- npm run build completes successfully\n";
        $this->write($path, $content);

        return ['created' => true, 'file' => 'tests/smoke/route-smoke-checklist.md'];
    }

    private function runStaticPreflight(): array
    {
        $checks = [
            'vercel_json' => json_decode($this->read('vercel.json'), true) !== null,
            'manifest_json' => json_decode($this->read('public/manifest.webmanifest'), true) !== null,
            'front_controller' => is_file($this->path('public/index.php')),
            'app_js' => is_file($this->path('public/assets/app.js')),
            'app_css' => is_file($this->path('public/assets/app.css')),
        ];

        $shell = [];
        if ($this->shellEnabled()) {
            $shell['php_lint_marketing'] = $this->runCommand([PHP_BINARY, '-l', $this->path('resources/views/marketing-center.php')]);
            $shell['php_lint_auth'] = $this->runCommand([PHP_BINARY, '-l', $this->path('app/Services/AuthService.php')]);
            $shell['npm_build'] = $this->runCommand(['npm', 'run', 'build']);
        }

        $result = [
            'generated_at' => date(DATE_ATOM),
            'checks' => $checks,
            'shell' => $shell,
            'passed' => !in_array(false, $checks, true) && $this->shellResultsPassed($shell),
        ];

        $this->assertRuntimeWritable();
        $this->write($this->path('storage/development-execution/preflight.json'), json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $result;
    }

    private function repairFailedJobs(): array
    {
        if (!$this->databaseAvailable()) {
            throw new \RuntimeException('manual_intervention_required');
        }

        try {
            $updated = Database::pdo()->exec("UPDATE failed_jobs SET status = 'retry_ready', updated_at = NOW() WHERE status IN ('failed', 'error')");
            return ['retry_ready' => (int) $updated];
        } catch (\Throwable $e) {
            throw new \RuntimeException('manual_intervention_required', 0, $e);
        }
    }

    private function finding(string $key, string $category, string $title, bool $ready, bool $critical, string $recommendation, ?string $taskKey = null): array
    {
        return [
            'key' => $key,
            'category' => $category,
            'title' => $title,
            'status' => $ready ? 'complete' : 'missing',
            'status_label' => $ready ? 'مكتمل' : 'ناقص',
            'severity' => $ready ? 'ok' : ($critical ? 'critical' : 'warning'),
            'fixable' => !$ready && $taskKey !== null,
            'task_key' => $ready ? null : $taskKey,
            'recommendation' => $recommendation,
        ];
    }

    private function mergeGeneratedTasks(array $stored, array $generated): array
    {
        foreach ($generated as $key => $task) {
            if (isset($stored[$key])) {
                $stored[$key] = array_replace($task, $stored[$key], [
                    'findings' => array_values(array_unique(array_merge($task['findings'] ?? [], $stored[$key]['findings'] ?? []))),
                ]);
                continue;
            }

            $stored[$key] = $task;
        }

        return $stored;
    }

    private function loadTasks(): array
    {
        if (!is_file($this->tasksFile)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->tasksFile), true);
        if (!is_array($decoded)) {
            return [];
        }

        $tasks = [];
        foreach ($decoded as $task) {
            if (is_array($task) && isset($task['key'])) {
                $tasks[(string) $task['key']] = $task;
            }
        }

        return $tasks;
    }

    private function persistTasks(array $tasks): void
    {
        try {
            $this->assertRuntimeWritable();
            $this->write($this->tasksFile, json_encode(array_values($tasks), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable) {
            // The dashboard must stay readable on read-only deployments.
        }
    }

    private function log(int $storeId, string $taskKey, string $level, string $message, array $context = []): void
    {
        try {
            $this->assertRuntimeWritable();
            $entry = [
                'created_at' => date(DATE_ATOM),
                'store_id' => $storeId,
                'task_key' => $taskKey,
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ];
            file_put_contents($this->logsFile, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable) {
        }
    }

    private function readLogs(): array
    {
        if (!is_file($this->logsFile)) {
            return [];
        }

        $lines = array_slice(file($this->logsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -30);
        return array_values(array_filter(array_map(static function (string $line): ?array {
            $decoded = json_decode($line, true);
            return is_array($decoded) ? $decoded : null;
        }, $lines)));
    }

    private function stats(array $findings, array $tasks): array
    {
        $missing = array_values(array_filter($findings, static fn (array $finding): bool => $finding['status'] !== 'complete'));
        $critical = array_values(array_filter($missing, static fn (array $finding): bool => $finding['severity'] === 'critical'));

        return [
            'total_findings' => count($findings),
            'complete' => count($findings) - count($missing),
            'missing' => count($missing),
            'critical' => count($critical),
            'fixable' => count(array_filter($missing, static fn (array $finding): bool => !empty($finding['fixable']))),
            'pending_tasks' => count(array_filter($tasks, static fn (array $task): bool => ($task['status'] ?? 'pending') === 'pending')),
            'running_tasks' => count(array_filter($tasks, static fn (array $task): bool => ($task['status'] ?? '') === 'running')),
            'completed_tasks' => count(array_filter($tasks, static fn (array $task): bool => ($task['status'] ?? '') === 'completed')),
            'failed_tasks' => count(array_filter($tasks, static fn (array $task): bool => ($task['status'] ?? '') === 'failed')),
        ];
    }

    private function recommendations(array $findings): array
    {
        $open = array_values(array_filter($findings, static fn (array $finding): bool => $finding['status'] !== 'complete'));
        return array_slice(array_map(static fn (array $finding): string => $finding['title'] . ': ' . $finding['recommendation'], $open), 0, 8);
    }

    private function taskTitle(string $key): string
    {
        return [
            'create_runtime_directories' => 'إنشاء مجلدات التشغيل والسجلات',
            'create_development_execution_migration' => 'إنشاء Migration لمحرك التنفيذ',
            'apply_development_execution_migration' => 'تطبيق Migration على قاعدة البيانات',
            'sync_env_example' => 'تحديث متغيرات البيئة المطلوبة',
            'write_route_inventory' => 'إنشاء فهرس المسارات الفعلية',
            'write_completion_report' => 'إنشاء تقرير النواقص والإصلاحات',
            'create_smoke_tests' => 'إنشاء Smoke Test Checklist',
            'run_static_preflight' => 'تشغيل فحص Preflight ثابت',
            'repair_failed_jobs' => 'تجهيز Failed Jobs لإعادة المحاولة',
        ][$key] ?? $key;
    }

    private function developmentMigrationSql(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS development_execution_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    task_key VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    priority VARCHAR(30) NOT NULL DEFAULT 'normal',
    payload_json JSON NULL,
    result_json JSON NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY development_execution_tasks_store_key_unique (store_id, task_key),
    INDEX development_execution_tasks_status_idx (status, priority)
);

CREATE TABLE IF NOT EXISTS development_execution_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    task_key VARCHAR(120) NULL,
    level VARCHAR(30) NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context_json JSON NULL,
    created_at TIMESTAMP NULL,
    INDEX development_execution_logs_store_created_idx (store_id, created_at),
    INDEX development_execution_logs_task_idx (task_key, created_at)
);

CREATE TABLE IF NOT EXISTS development_execution_findings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NULL,
    finding_key VARCHAR(160) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'missing',
    severity VARCHAR(40) NOT NULL DEFAULT 'warning',
    fixable TINYINT(1) NOT NULL DEFAULT 0,
    recommendation TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY development_execution_findings_store_key_unique (store_id, finding_key),
    INDEX development_execution_findings_status_idx (status, severity)
);
SQL;
    }

    private function shellResultsPassed(array $results): bool
    {
        foreach ($results as $result) {
            if (!is_array($result) || (int) ($result['exit_code'] ?? 1) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function runCommand(array $parts): array
    {
        $command = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $parts));
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'command' => implode(' ', $parts),
            'exit_code' => $exitCode,
            'output' => array_slice($output, -20),
        ];
    }

    private function databaseAvailable(): bool
    {
        try {
            Database::pdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function databaseTableExists(string $table): bool
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function runtimeWritable(): bool
    {
        if (!is_dir($this->runtimeDir)) {
            return is_writable(dirname($this->runtimeDir));
        }

        return is_writable($this->runtimeDir);
    }

    private function assertRuntimeWritable(): void
    {
        if (!is_dir($this->runtimeDir)) {
            mkdir($this->runtimeDir, 0775, true);
        }

        if (!is_writable($this->runtimeDir)) {
            throw new \RuntimeException('manual_intervention_required');
        }
    }

    private function writeEnabled(): bool
    {
        $default = Env::get('APP_ENV', 'local') === 'production' ? 'false' : 'true';
        return filter_var(Env::get('DEVELOPMENT_EXECUTION_WRITE_ENABLED', $default), FILTER_VALIDATE_BOOL);
    }

    private function shellEnabled(): bool
    {
        return filter_var(Env::get('DEVELOPMENT_EXECUTION_SHELL_ENABLED', 'false'), FILTER_VALIDATE_BOOL);
    }

    private function assertWriteEnabled(): void
    {
        if (!$this->writeEnabled()) {
            throw new \RuntimeException('manual_intervention_required');
        }
    }

    private function read(string $relative): string
    {
        $path = $this->path($relative);
        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private function write(string $path, string $content): void
    {
        $this->assertInsideProject($path);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, $content);
    }

    private function path(string $relative): string
    {
        return $this->root . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    }

    private function assertInsideProject(string $path): void
    {
        $dir = realpath(dirname($path)) ?: dirname($path);
        $root = realpath($this->root) ?: $this->root;
        if (!str_starts_with(str_replace('\\', '/', $dir), str_replace('\\', '/', $root))) {
            throw new \RuntimeException('manual_intervention_required');
        }
    }
}
