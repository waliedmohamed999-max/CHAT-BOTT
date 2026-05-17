<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        if ($status >= 400 && !filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)) {
            unset($payload['detail'], $payload['exception'], $payload['trace']);
        }
        $payload = ArabicMessages::enrich($payload);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}
