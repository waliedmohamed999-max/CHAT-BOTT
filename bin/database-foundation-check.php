<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Support\Database;
use MarketingCenter\Support\Env;

$storeId = (int) (Env::get('DEFAULT_STORE_ID', '1') ?? '1');
$checks = [];
$warnings = [];

try {
    Database::pdo()->query('SELECT 1');
    addCheck($checks, 'Database connection', true, 'Connection is reachable.');
} catch (Throwable $exception) {
    addCheck($checks, 'Database connection', false, $exception->getMessage());
    printResults($checks, $warnings);
    exit(1);
}

foreach ([
    'stores',
    'users',
    'workspaces',
    'workspace_members',
    'role_permissions',
    'audit_logs',
    'login_logs',
    'notification_logs',
    'failed_jobs',
    'queue_monitoring_snapshots',
    'departments',
    'subscriptions',
    'whatsapp_setup_documents',
] as $table) {
    addCheck($checks, "Table {$table}", tableExists($table), "Required table {$table} exists.");
}

foreach ([
    ['users', 'password_hash'],
    ['users', 'role'],
    ['workspace_members', 'role'],
    ['workspace_members', 'status'],
    ['role_permissions', 'permission_key'],
    ['departments', 'slug'],
    ['departments', 'is_active'],
    ['subscriptions', 'plan_key'],
] as [$table, $column]) {
    addCheck($checks, "Column {$table}.{$column}", columnExists($table, $column), "Required column {$table}.{$column} exists.");
}

foreach ([
    ['users', 'users_store_role_idx'],
    ['workspace_members', 'workspace_members_role_idx'],
    ['role_permissions', 'role_permission_unique'],
    ['departments', 'departments_store_active_idx'],
] as [$table, $index]) {
    addCheck($checks, "Index {$table}.{$index}", indexExists($table, $index), "Required index {$table}.{$index} exists.");
}

addCheck($checks, 'Active store seed', countRows('SELECT COUNT(*) FROM stores WHERE id = ? AND status = ?', [$storeId, 'active']) > 0, 'Default active store exists.');
addCheck($checks, 'Owner user seed', countRows("SELECT COUNT(*) FROM users WHERE store_id = ? AND role = 'owner'", [$storeId]) > 0, 'At least one owner user exists.');
addCheck($checks, 'Workspace member seed', countRows("SELECT COUNT(*) FROM workspace_members WHERE store_id = ? AND role = 'owner' AND status = 'active'", [$storeId]) > 0, 'Owner is attached to an active workspace.');
addCheck($checks, 'Subscription seed', countRows('SELECT COUNT(*) FROM subscriptions WHERE store_id = ?', [$storeId]) > 0, 'Store subscription seed exists.');

$requiredPermissions = [
    ['owner', '*'],
    ['admin', 'meta.connect'],
    ['admin', 'campaign.launch'],
    ['marketing_manager', 'campaign.create'],
    ['support_agent', 'inbox.reply'],
    ['viewer', 'analytics.view'],
];

foreach ($requiredPermissions as [$role, $permission]) {
    addCheck(
        $checks,
        "Permission {$role}:{$permission}",
        countRows('SELECT COUNT(*) FROM role_permissions WHERE role_key = ? AND permission_key = ?', [$role, $permission]) > 0,
        "Role permission {$role}:{$permission} exists."
    );
}

foreach (['sales', 'support', 'orders', 'billing', 'complaints'] as $department) {
    addCheck(
        $checks,
        "Department {$department}",
        countRows('SELECT COUNT(*) FROM departments WHERE store_id = ? AND slug = ? AND is_active = 1', [$storeId, $department]) > 0,
        "Default department {$department} exists and is active."
    );
}

if (countRows("SELECT COUNT(*) FROM users WHERE email = 'admin@marketing-center.local'", []) > 0) {
    $warnings[] = 'Default local admin email exists. Replace it with a real production owner before launch.';
}

printResults($checks, $warnings);
$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ready']));
exit($failed === [] ? 0 : 1);

function addCheck(array &$checks, string $label, bool $ready, string $message): void
{
    $checks[] = [
        'label' => $label,
        'ready' => $ready,
        'message' => $message,
    ];
}

function printResults(array $checks, array $warnings): void
{
    echo "Database Foundation Check\n";
    echo "=========================\n";

    foreach ($checks as $check) {
        echo ($check['ready'] ? '[OK] ' : '[FAIL] ') . $check['label'] . ' - ' . $check['message'] . PHP_EOL;
    }

    if ($warnings !== []) {
        echo "\nWarnings:\n";
        foreach ($warnings as $warning) {
            echo '[WARN] ' . $warning . PHP_EOL;
        }
    }

    $failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ready']));
    echo "\nResult: " . ($failed === [] ? 'READY' : 'NOT READY') . "\n";
}

function tableExists(string $table): bool
{
    if (!safeIdentifier($table)) {
        return false;
    }

    return countRows('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]) > 0;
}

function columnExists(string $table, string $column): bool
{
    if (!safeIdentifier($table) || !safeIdentifier($column)) {
        return false;
    }

    return countRows('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]) > 0;
}

function indexExists(string $table, string $index): bool
{
    if (!safeIdentifier($table) || !safeIdentifier($index)) {
        return false;
    }

    return countRows('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?', [$table, $index]) > 0;
}

function countRows(string $sql, array $params): int
{
    try {
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function safeIdentifier(string $identifier): bool
{
    return preg_match('/^[a-z0-9_]+$/', $identifier) === 1;
}
