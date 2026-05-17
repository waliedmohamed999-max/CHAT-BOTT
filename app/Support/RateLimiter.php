<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class RateLimiter
{
    public static function assertAllowed(string $key): void
    {
        $limit = (int) Env::get('RATE_LIMIT_PER_MINUTE', '120');
        $bucket = 'rate_' . sha1($key . date('YmdHi'));
        $_SESSION[$bucket] = ($_SESSION[$bucket] ?? 0) + 1;

        if ($_SESSION[$bucket] > $limit) {
            Response::json(['error' => 'rate_limited'], 429);
            exit;
        }
    }
}
