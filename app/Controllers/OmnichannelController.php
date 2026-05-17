<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\OmnichannelService;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;

final class OmnichannelController
{
    private OmnichannelService $service;

    public function __construct()
    {
        $this->service = new OmnichannelService();
    }

    public function overview(): void
    {
        Response::json(['data' => $this->service->overview($this->storeId())]);
    }

    public function channels(): void
    {
        Response::json(['data' => $this->service->channels($this->storeId())]);
    }

    public function connectChannel(): void
    {
        try {
            $id = $this->service->connectChannel($this->storeId(), Request::input());
            Response::json(['data' => ['id' => $id]], 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Response::json(['error' => 'omnichannel_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function conversations(): void
    {
        Response::json(['data' => $this->service->conversations($this->storeId(), Request::input())]);
    }

    public function messages(int $conversationId): void
    {
        Response::json(['data' => $this->service->messages($this->storeId(), $conversationId)]);
    }

    public function reply(): void
    {
        try {
            Response::json(['data' => $this->service->reply($this->storeId(), Request::input())], 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Response::json(['error' => 'omnichannel_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function customer360(): void
    {
        $input = Request::input();
        $contactId = isset($input['contact_id']) ? (int) $input['contact_id'] : null;
        Response::json(['data' => $this->service->customer360($this->storeId(), $contactId)]);
    }

    public function analytics(): void
    {
        Response::json(['data' => $this->service->analytics($this->storeId())]);
    }

    public function liveChatConfig(): void
    {
        Response::json(['data' => $this->service->liveChatConfig($this->storeId())]);
    }

    public function webhook(string $channel): void
    {
        try {
            Response::json(['data' => $this->service->processWebhook($this->storeId(), $channel, Request::input())]);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    private function storeId(): int
    {
        return (int) Env::get('DEFAULT_STORE_ID', '1');
    }
}
