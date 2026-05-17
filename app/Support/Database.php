<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $databaseUrl = Env::get('DATABASE_URL');
        if ($databaseUrl) {
            $parts = parse_url($databaseUrl);
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $parts['host'] ?? '127.0.0.1',
                (string) ($parts['port'] ?? 3306),
                ltrim($parts['path'] ?? '/marketing_center', '/')
            );
            $username = urldecode($parts['user'] ?? 'root');
            $password = urldecode($parts['pass'] ?? '');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST', '127.0.0.1'),
                Env::get('DB_PORT', '3306'),
                Env::get('DB_DATABASE', 'marketing_center')
            );
            $username = Env::get('DB_USERNAME', 'root');
            $password = Env::get('DB_PASSWORD', '');
        }

        self::$pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
