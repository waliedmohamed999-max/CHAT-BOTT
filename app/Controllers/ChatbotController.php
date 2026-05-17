<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\ChatbotService;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;

final class ChatbotController
{
    private ChatbotService $chatbot;

    public function __construct()
    {
        $this->chatbot = new ChatbotService();
    }

    public function overview(): void
    {
        Response::json(['data' => $this->chatbot->overview($this->storeId())]);
    }

    public function builder(): void
    {
        Response::json(['data' => $this->chatbot->builder($this->storeId())]);
    }

    public function diagnostics(): void
    {
        try {
            Response::json(['data' => $this->chatbot->routeDiagnostics($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function flows(): void
    {
        try {
            Response::json(['data' => $this->chatbot->flows($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function currentFlow(): void
    {
        try {
            Response::json(['data' => $this->chatbot->currentFlow($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function flow(int $id): void
    {
        try {
            Response::json(['data' => $this->chatbot->flow($this->storeId(), $id)]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function createFlow(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->createFlow($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateFlow(int $id): void
    {
        Rbac::assert('campaign.create');
        try {
            $this->chatbot->updateFlow($this->storeId(), $id, Request::input());
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function activateFlow(int $id): void
    {
        Rbac::assert('campaign.create');
        try {
            $this->chatbot->activateFlow($this->storeId(), $id);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function createNode(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->createNode($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateNode(int $id): void
    {
        Rbac::assert('campaign.create');
        try {
            $this->chatbot->updateNode($this->storeId(), $id, Request::input());
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function deleteNode(int $id): void
    {
        Rbac::assert('campaign.create');
        try {
            $this->chatbot->deleteNode($this->storeId(), $id);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function createEdge(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->createEdge($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function deleteEdge(int $id): void
    {
        Rbac::assert('campaign.create');
        try {
            $this->chatbot->deleteEdge($this->storeId(), $id);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function departments(): void
    {
        Response::json(['data' => $this->chatbot->departments($this->storeId())]);
    }

    public function saveDepartment(?int $id = null): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->saveDepartment($this->storeId(), Request::input(), $id)], $id ? 200 : 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function deleteFlow(int $id): void
    {
        Rbac::assert('campaign.create');
        $this->chatbot->deleteFlow($this->storeId(), $id);
        Response::json(['ok' => true]);
    }

    public function keywords(): void
    {
        try {
            Response::json(['data' => $this->chatbot->keywords($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function createKeyword(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->createKeyword($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function autoReplies(): void
    {
        try {
            Response::json(['data' => $this->chatbot->autoReplies($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function createAutoReply(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->createAutoReply($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function aiReply(): void
    {
        Response::json(['data' => $this->chatbot->aiReply($this->storeId(), Request::input())]);
    }

    public function classify(): void
    {
        Response::json(['data' => $this->chatbot->classify($this->storeId(), Request::input())]);
    }

    public function aiSettings(): void
    {
        try {
            Response::json(['data' => $this->chatbot->aiSettings($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function saveAiSettings(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['data' => $this->chatbot->saveAiSettings($this->storeId(), Request::input())]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function knowledgeBase(): void
    {
        try {
            Response::json(['data' => $this->chatbot->knowledgeBase($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function saveKnowledge(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->saveKnowledgeItem($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function uploadKnowledge(): void
    {
        Rbac::assert('campaign.create');
        try {
            Response::json(['id' => $this->chatbot->saveKnowledgeFile($this->storeId(), $_FILES['file'] ?? [], Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function analyzeConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        try {
            $input = Request::input();
            Response::json(['data' => $this->chatbot->conversationAiInsight($this->storeId(), $conversationId, isset($input['message']) ? (string) $input['message'] : null)]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function handover(): void
    {
        Rbac::assert('inbox.reply');
        try {
            Response::json(['id' => $this->chatbot->handover($this->storeId(), Request::input())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function resume(): void
    {
        Rbac::assert('inbox.reply');
        $this->chatbot->resume($this->storeId(), Request::input());
        Response::json(['ok' => true]);
    }

    public function pauseConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        try {
            $this->chatbot->pauseConversationBot($this->storeId(), $conversationId);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function resumeConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        try {
            $this->chatbot->resumeConversationBot($this->storeId(), $conversationId);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function transferConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        try {
            Response::json(['data' => $this->chatbot->transferDepartment($this->storeId(), $conversationId, (string) (Request::input()['department'] ?? 'support'))]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function endConversation(int $conversationId): void
    {
        Rbac::assert('inbox.reply');
        try {
            $this->chatbot->endConversation($this->storeId(), $conversationId);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function processWebhook(): void
    {
        try {
            Response::json(['data' => $this->chatbot->processWebhook($this->storeId(), Request::input())]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function processMessage(): void
    {
        try {
            $input = Request::input();
            if (!empty($input['phone'])) {
                Response::json(['data' => $this->chatbot->handleIncomingWhatsAppMessage(
                    $this->storeId(),
                    isset($input['conversation_id']) ? (int) $input['conversation_id'] : null,
                    isset($input['contact_id']) ? (int) $input['contact_id'] : null,
                    (string) $input['phone'],
                    (string) ($input['body'] ?? $input['message'] ?? ''),
                    (string) ($input['connection_source'] ?? 'meta_cloud_api'),
                    $input
                )]);
                return;
            }
            Response::json(['data' => $this->chatbot->processMessage($this->storeId(), $input)]);
        } catch (\Throwable $e) {
            Response::json($this->chatbotError($e), 422);
        }
    }

    public function preview(?int $flowId = null): void
    {
        Response::json(['data' => $this->chatbot->preview($this->storeId(), $flowId)]);
    }

    private function storeId(): int
    {
        return (int) Env::get('DEFAULT_STORE_ID', '1');
    }

    private function chatbotError(\Throwable $e): array
    {
        $message = $e->getMessage();
        if (str_contains($message, 'Unknown database') || str_contains($message, 'Base table or view not found')) {
            return [
                'error' => 'whatsapp_setup_not_ready',
                'details' => 'Import database/schema.sql or database/production_integration_migration.sql before running the live chatbot runtime.',
            ];
        }

        return ['error' => $message];
    }
}
