<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

use MarketingCenter\Services\AuthService;

final class Security
{
    private const CSRF_SESSION_KEY = '_marketing_center_csrf_token';

    public static function applyHeaders(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    }

    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION[self::CSRF_SESSION_KEY]) || !is_string($_SESSION[self::CSRF_SESSION_KEY])) {
            $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_SESSION_KEY];
    }

    public static function assertCsrfIfNeeded(string $method, string $path): void
    {
        if (!self::shouldEnforceCsrf($method, $path)) {
            return;
        }

        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!is_string($provided) || !hash_equals(self::csrfToken(), $provided)) {
            Response::json(['error' => 'csrf_token_invalid'], 419);
            exit;
        }
    }

    public static function assertAuthenticatedIfNeeded(string $method, string $path): void
    {
        if (!self::shouldEnforceAuth($path)) {
            return;
        }

        if ((new AuthService())->isAuthenticated()) {
            return;
        }

        if (str_starts_with($path, '/api/')) {
            Response::json(['error' => 'unauthenticated'], 401);
            exit;
        }

        header('Location: ' . self::loginUrl());
        exit;
    }

    private static function shouldEnforceCsrf(string $method, string $path): bool
    {
        if (in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        foreach (self::csrfExemptPaths() as $exemptPath) {
            if ($path === $exemptPath || str_starts_with($path, $exemptPath . '/')) {
                return false;
            }
        }

        $explicit = Env::get('CSRF_ENFORCE');
        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOL);
        }

        return Env::get('APP_ENV', 'local') === 'production';
    }

    private static function shouldEnforceAuth(string $path): bool
    {
        foreach (self::authExemptPaths() as $exemptPath) {
            if ($path === $exemptPath || str_starts_with($path, $exemptPath . '/')) {
                return false;
            }
        }

        $explicit = Env::get('AUTH_ENFORCE');
        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOL);
        }

        return Env::get('APP_ENV', 'local') === 'production';
    }

    private static function loginUrl(): string
    {
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        return $appUrl !== '' ? $appUrl . '/login' : '/login';
    }

    private static function csrfExemptPaths(): array
    {
        return [
            '/api/auth/platform/login',
            '/api/auth/store/login',
            '/api/auth/agent/login',
            '/api/auth/tenant/login',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/auth/verify-2fa',
            '/api/whatsapp/webhook',
            '/api/webhooks/whatsapp',
            '/api/whatsapp-qr/bridge-webhook',
            '/api/omnichannel/webhooks',
            '/api/meta/callback',
            '/api/whatsapp-setup/meta/callback',
        ];
    }

    private static function authExemptPaths(): array
    {
        return [
            '/',
            '/login',
            '/platform/login',
            '/store/login',
            '/store/register',
            '/agent/login',
            '/tenant',
            '/auth/callback',
            '/api/auth/login',
            '/api/auth/platform/login',
            '/api/auth/store/login',
            '/api/auth/agent/login',
            '/api/auth/tenant/login',
            '/api/auth/me',
            '/api/auth/login-branding',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/auth/verify-2fa',
            '/api/whatsapp/webhook',
            '/api/webhooks/whatsapp',
            '/api/whatsapp-qr/bridge-webhook',
            '/api/omnichannel/webhooks',
            '/api/meta/callback',
            '/api/whatsapp-setup/meta/callback',
        ];
    }
}
