<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\AuthService;
use MarketingCenter\Services\AuthPortalService;
use MarketingCenter\Support\Response;

final class AuthController
{
    public function showLogin(): void
    {
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/login.php';
        Response::html((string) ob_get_clean());
    }

    public function showPortalLogin(string $portal, ?string $slug = null): void
    {
        $config = (new AuthPortalService())->portalConfig($portal, $slug);
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/login-portal.php';
        Response::html((string) ob_get_clean());
    }

    public function login(): void
    {
        $input = $this->input();

        try {
            $user = (new AuthService())->login((string) ($input['email'] ?? ''), (string) ($input['password'] ?? ''));
            Response::json(['data' => ['authenticated' => true, 'user' => $user]]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function portalLogin(string $portal): void
    {
        $input = $this->input();
        try {
            Response::json(['data' => (new AuthPortalService())->login($portal, $input)]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function logout(): void
    {
        (new AuthPortalService())->logout();
        Response::json(['data' => ['authenticated' => false]]);
    }

    public function me(): void
    {
        $user = (new AuthService())->currentUser();
        Response::json(['data' => ['authenticated' => $user !== null, 'user' => $user, 'portal' => (new AuthPortalService())->me()]]);
    }

    public function loginBranding(string $slug): void
    {
        Response::json(['data' => (new AuthPortalService())->loginBranding($slug)]);
    }

    public function sessions(): void
    {
        Response::json(['data' => (new AuthPortalService())->sessions()]);
    }

    public function revokeSession(): void
    {
        $input = $this->input();
        (new AuthPortalService())->revokeSession((int) ($input['id'] ?? 0));
        Response::json(['ok' => true]);
    }

    public function forgotPassword(): void
    {
        try {
            Response::json(['data' => (new AuthPortalService())->forgotPassword($this->input())]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function resetPassword(): void
    {
        Response::json(['data' => (new AuthPortalService())->resetPassword($this->input())]);
    }

    public function verify2fa(): void
    {
        Response::json(['data' => (new AuthPortalService())->verify2fa($this->input())]);
    }

    private function input(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }
}
