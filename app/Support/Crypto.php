<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class Crypto
{
    public static function encrypt(string $plainText): string
    {
        $key = hash('sha256', Env::get('ENCRYPTION_KEY', Env::get('APP_KEY', 'local-development-key')), true);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Invalid encrypted payload.');
        }

        $key = hash('sha256', Env::get('ENCRYPTION_KEY', Env::get('APP_KEY', 'local-development-key')), true);
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new \RuntimeException('Unable to decrypt payload.');
        }

        return $plain;
    }
}
