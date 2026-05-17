<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\WhatsAppQrService;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;
use MarketingCenter\Support\Validator;

final class WhatsAppQrController
{
    private WhatsAppQrService $service;

    public function __construct()
    {
        $this->service = new WhatsAppQrService();
    }

    public function create(): void
    {
        Rbac::assert('meta.connect');
        try {
            Response::json(['data' => $this->service->createSession($this->storeId(), Rbac::userId())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function status(): void
    {
        try {
            Response::json(['data' => $this->service->status($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['connected' => false, 'session_status' => 'disconnected', 'bridge_error' => $e->getMessage()]]);
        }
    }

    public function qr(): void
    {
        try {
            Response::json(['data' => $this->service->qr($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['qr' => null, 'session_status' => 'disconnected', 'bridge_error' => $e->getMessage()]]);
        }
    }

    public function disconnect(): void
    {
        Rbac::assert('meta.connect');
        try {
            Response::json(['data' => $this->service->disconnect($this->storeId(), Rbac::userId())]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function reconnect(): void
    {
        Rbac::assert('meta.connect');
        try {
            Response::json(['data' => $this->service->reconnect($this->storeId(), Rbac::userId())]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function chats(): void
    {
        try {
            Response::json(['data' => $this->service->chats($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function messages(string $chatId): void
    {
        try {
            Response::json(['data' => $this->service->messages($this->storeId(), $chatId)]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function sendMessage(): void
    {
        Rbac::assert('inbox.reply');
        $data = Request::input();
        $errors = Validator::required($data, ['to', 'body']);
        if ($errors) {
            Response::json(['errors' => $errors], 422);
            return;
        }
        try {
            Response::json(['data' => $this->service->sendMessage($this->storeId(), (string) $data['to'], (string) $data['body'])]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function sendMedia(): void
    {
        Rbac::assert('inbox.reply');
        $data = Request::input();
        $errors = Validator::required($data, ['to', 'media_url']);
        if ($errors) {
            Response::json(['errors' => $errors], 422);
            return;
        }
        try {
            Response::json(['data' => $this->service->sendMedia($this->storeId(), $data)]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function contacts(): void
    {
        try {
            Response::json(['data' => $this->service->contacts($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function events(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        try {
            $status = $this->service->status($this->storeId());
        } catch (\Throwable $e) {
            $status = ['connected' => false, 'session_status' => 'disconnected', 'bridge_error' => $e->getMessage()];
        }
        echo "event: status\n";
        echo 'data: ' . json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        flush();
    }

    public function bridgeWebhook(): void
    {
        $token = $_SERVER['HTTP_X_BRIDGE_TOKEN'] ?? '';
        if (!hash_equals(Env::get('WHATSAPP_QR_BRIDGE_TOKEN', ''), $token)) {
            Response::json(['error' => 'forbidden'], 403);
            return;
        }
        try {
            $this->service->ingestEvent(json_decode(Request::raw(), true) ?: []);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    private function storeId(): int
    {
        return (int) Env::get('DEFAULT_STORE_ID', '1');
    }
}
