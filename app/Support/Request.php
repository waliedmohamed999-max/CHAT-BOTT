<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class Request
{
    public static function input(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST ?: $_GET;
    }

    public static function raw(): string
    {
        return (string) file_get_contents('php://input');
    }
}
