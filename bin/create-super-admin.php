<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use MarketingCenter\Support\Database;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'مدير المنصة';

if (!$email || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php bin/create-super-admin.php admin@example.com StrongPassword123! \"Admin Name\"\n");
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$pdo = Database::pdo();
$pdo->beginTransaction();

try {
    $pdo->exec("INSERT INTO stores (id, name, slug, plan, status, created_at, updated_at)
        VALUES (1, 'المتجر الرئيسي', 'main-store', 'professional', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()");

    $pdo->exec("INSERT INTO workspaces (id, store_id, name, slug, status, created_at, updated_at)
        VALUES (1, 1, 'مساحة العمل الرئيسية', 'main-workspace', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()");

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($userId) {
        $update = $pdo->prepare("UPDATE users SET store_id = 1, name = ?, password_hash = ?, role = 'owner', updated_at = NOW() WHERE id = ?");
        $update->execute([$name, $passwordHash, (int) $userId]);
    } else {
        $insert = $pdo->prepare("INSERT INTO users (store_id, name, email, password_hash, role, created_at, updated_at) VALUES (1, ?, ?, ?, 'owner', NOW(), NOW())");
        $insert->execute([$name, $email, $passwordHash]);
        $userId = (int) $pdo->lastInsertId();
    }

    $member = $pdo->prepare("INSERT INTO workspace_members (store_id, workspace_id, user_id, role, status, created_at, updated_at)
        VALUES (1, 1, ?, 'owner', 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE role = 'owner', status = 'active', updated_at = NOW()");
    $member->execute([(int) $userId]);

    $pdo->commit();
    echo "Super admin is ready: {$email}\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Failed to create super admin: {$e->getMessage()}\n");
    exit(1);
}
