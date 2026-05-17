<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\WebhookService;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;

final class WebhookController
{
    public function verify(): void
    {
        $challenge = (new WebhookService())->verifyChallenge($_GET);
        if ($challenge === null) {
            Response::json(['error' => 'invalid_webhook_token'], 403);
            return;
        }
        header('Content-Type: text/plain');
        echo $challenge;
    }

    public function receive(): void
    {
        $raw = Request::raw();
        $service = new WebhookService();
        try {
            $service->assertSignature($raw);
            $service->ingest(json_decode($raw, true) ?: [], $raw);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
