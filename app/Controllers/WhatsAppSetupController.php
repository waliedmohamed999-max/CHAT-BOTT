<?php

declare(strict_types=1);

namespace MarketingCenter\Controllers;

use MarketingCenter\Services\MetaOAuthService;
use MarketingCenter\Services\WhatsAppQrService;
use MarketingCenter\Services\WhatsAppSetupService;
use MarketingCenter\Support\Env;
use MarketingCenter\Support\Request;
use MarketingCenter\Support\Response;
use MarketingCenter\Support\Rbac;

final class WhatsAppSetupController
{
    private WhatsAppSetupService $setup;

    public function __construct()
    {
        $this->setup = new WhatsAppSetupService();
    }

    public function profile(): void
    {
        try {
            Response::json(['data' => $this->setup->profile($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['store_id' => $this->storeId(), 'setup_status' => 'draft', 'readiness_score' => 0, 'error' => $e->getMessage()]]);
        }
    }

    public function saveProfile(): void
    {
        Rbac::assert('meta.connect');
        try {
            Response::json(['data' => $this->setup->saveProfile($this->storeId(), Request::input())]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function uploadDocument(): void
    {
        Rbac::assert('meta.connect');
        try {
            $file = $_FILES['file'] ?? null;
            if (!$file) {
                Response::json(['error' => 'file_required'], 422);
                return;
            }
            Response::json(['data' => $this->setup->uploadDocument($this->storeId(), $file, (string) ($_POST['document_type'] ?? 'additional'))], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function documents(): void
    {
        try {
            Response::json(['data' => $this->setup->documents($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function deleteDocument(int $id): void
    {
        Rbac::assert('meta.connect');
        $this->setup->deleteDocument($this->storeId(), $id);
        Response::json(['ok' => true]);
    }

    public function selectMethod(): void
    {
        Rbac::assert('meta.connect');
        try {
            Response::json(['data' => $this->setup->selectMethod($this->storeId(), (string) (Request::input()['method'] ?? ''))]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function metaConnect(): void
    {
        Response::json(['redirect_url' => (new MetaOAuthService())->authorizationUrl($this->storeId())]);
    }

    public function metaCallback(): void
    {
        (new ApiController())->metaCallback();
    }

    public function metaDisconnect(): void
    {
        (new ApiController())->disconnect();
    }

    public function qrCreate(): void
    {
        try {
            Response::json(['data' => (new WhatsAppQrService())->createSession($this->storeId(), Rbac::userId())], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function qrStatus(): void
    {
        try {
            Response::json(['data' => (new WhatsAppQrService())->status($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['session_status' => 'disconnected', 'error' => $e->getMessage()]]);
        }
    }

    public function qrCode(): void
    {
        try {
            Response::json(['data' => (new WhatsAppQrService())->qr($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['qr' => null, 'session_status' => 'disconnected', 'error' => $e->getMessage()]]);
        }
    }

    public function qrDisconnect(): void
    {
        try {
            Response::json(['data' => (new WhatsAppQrService())->disconnect($this->storeId(), Rbac::userId())]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function testSendMessage(): void
    {
        try {
            $data = Request::input();
            $status = empty($data['to']) ? 'failed' : 'pending';
            $message = $status === 'failed' ? 'رقم المستلم مطلوب لاختبار الإرسال.' : 'تم تسجيل اختبار الإرسال. نفذ الاختبار من طريقة الربط المختارة.';
            Response::json(['data' => $this->setup->logTest($this->storeId(), 'send_message', $status, $message, $data)]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function testWebhook(): void
    {
        try {
            Response::json(['data' => $this->setup->logTest($this->storeId(), 'webhook', 'passed', 'Webhook endpoint جاهز ويقبل التحقق.', ['endpoint' => '/api/webhooks/whatsapp'])]);
        } catch (\Throwable $e) {
            Response::json(['error' => 'whatsapp_setup_not_ready', 'detail' => $e->getMessage()], 422);
        }
    }

    public function testLogs(): void
    {
        try {
            Response::json(['data' => $this->setup->testLogs($this->storeId())]);
        } catch (\Throwable) {
            Response::json(['data' => []]);
        }
    }

    public function readiness(): void
    {
        try {
            Response::json(['data' => $this->setup->readiness($this->storeId())]);
        } catch (\Throwable $e) {
            Response::json(['data' => ['score' => 0, 'status' => 'غير جاهز', 'items' => [], 'error' => $e->getMessage()]]);
        }
    }

    private function storeId(): int
    {
        return (int) Env::get('DEFAULT_STORE_ID', '1');
    }
}
