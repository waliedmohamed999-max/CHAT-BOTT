<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Support\Database;

$password = $argv[1] ?? 'DemoPass123!';
if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = Database::pdo();
$pdo->beginTransaction();

try {
    $pdo->exec("INSERT INTO stores (id, name, slug, plan, status, created_at, updated_at)
        VALUES (1, 'المتجر الرئيسي', 'main-store', 'professional', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug), plan = VALUES(plan), status = VALUES(status), updated_at = NOW()");

    $pdo->exec("INSERT INTO workspaces (id, store_id, name, slug, status, created_at, updated_at)
        VALUES (1, 1, 'مساحة العمل الرئيسية', 'main-workspace', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), updated_at = NOW()");

    $legacyUserId = upsertLegacyUser($pdo, [
        'store_id' => 1,
        'name' => 'مدير النظام الداخلي',
        'email' => 'admin@marketing-center.local',
        'role' => 'owner',
        'password_hash' => $hash,
    ]);

    $member = $pdo->prepare("INSERT INTO workspace_members (store_id, workspace_id, user_id, role, status, created_at, updated_at)
        VALUES (1, 1, ?, 'owner', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE role = 'owner', status = 'active', updated_at = NOW()");
    $member->execute([$legacyUserId]);

    upsertPlatformUser($pdo, [
        'name' => 'مدير المنصة',
        'email' => 'platform@marketing-center.local',
        'role' => 'super_admin',
        'password_hash' => $hash,
    ]);

    upsertStoreUser($pdo, [
        'store_id' => 1,
        'name' => 'مالك المتجر',
        'email' => 'owner@main-store.local',
        'role' => 'owner',
        'password_hash' => $hash,
    ]);

    upsertStoreUser($pdo, [
        'store_id' => 1,
        'name' => 'موظف الدعم',
        'email' => 'agent@main-store.local',
        'role' => 'support_agent',
        'password_hash' => $hash,
    ]);

    upsertStoreUser($pdo, [
        'store_id' => 1,
        'name' => 'مدير بوابة المتجر',
        'email' => 'tenant@main-store.local',
        'role' => 'admin',
        'password_hash' => $hash,
    ]);

    $pdo->commit();

    echo "Demo logins are ready.\n";
    echo "Password for all demo accounts: {$password}\n";
    echo "/login                    admin@marketing-center.local\n";
    echo "/platform/login           platform@marketing-center.local\n";
    echo "/store/login              owner@main-store.local\n";
    echo "/agent/login              agent@main-store.local      store code: main-store\n";
    echo "/tenant/main-store/login  tenant@main-store.local\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Failed to create demo logins: {$e->getMessage()}\n");
    exit(1);
}

function upsertLegacyUser(PDO $pdo, array $user): int
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$user['email']]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $update = $pdo->prepare('UPDATE users SET store_id = ?, name = ?, password_hash = ?, role = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$user['store_id'], $user['name'], $user['password_hash'], $user['role'], (int) $id]);
        return (int) $id;
    }

    $insert = $pdo->prepare('INSERT INTO users (store_id, name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insert->execute([$user['store_id'], $user['name'], $user['email'], $user['password_hash'], $user['role']]);
    return (int) $pdo->lastInsertId();
}

function upsertPlatformUser(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare("INSERT INTO platform_users (name, email, password_hash, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role), status = 'active', updated_at = NOW()");
    $stmt->execute([$user['name'], $user['email'], $user['password_hash'], $user['role']]);
}

function upsertStoreUser(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare("INSERT INTO store_users (store_id, name, email, password_hash, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role), status = 'active', updated_at = NOW()");
    $stmt->execute([$user['store_id'], $user['name'], $user['email'], $user['password_hash'], $user['role']]);
}
