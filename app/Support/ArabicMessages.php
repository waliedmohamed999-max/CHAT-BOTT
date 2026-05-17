<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class ArabicMessages
{
    private const MESSAGES = [
        'unauthenticated' => 'يجب تسجيل الدخول قبل الوصول إلى هذه الصفحة.',
        'invalid_credentials' => 'بيانات تسجيل الدخول غير صحيحة.',
        'account_disabled' => 'هذا الحساب غير مفعل. تواصل مع مدير النظام.',
        'invalid_oauth_state' => 'جلسة الربط مع Meta غير صالحة. أعد المحاولة.',
        'campaign_name_required' => 'اسم الحملة مطلوب.',
        'template_not_approved' => 'لا يمكن الإرسال لأن القالب غير معتمد.',
        'template_not_found' => 'القالب غير موجود.',
        'meta_not_connected' => 'لم يتم ربط حساب Meta بعد.',
        'waba_not_connected' => 'لم يتم ربط حساب WhatsApp Business بعد.',
        'whatsapp_phone_not_connected' => 'لم يتم ربط رقم واتساب للإرسال.',
        'whatsapp_qr_session_not_found' => 'لم يتم إنشاء جلسة واتساب QR بعد.',
        'whatsapp_qr_not_connected' => 'جلسة واتساب QR غير متصلة.',
        'whatsapp_qr_bridge_unavailable' => 'خدمة WhatsApp QR Bridge غير متاحة حالياً.',
        'qr_message_body_required' => 'نص رسالة QR مطلوب عند اختيار الإرسال عبر جلسة QR.',
        'invalid_email' => 'البريد الإلكتروني غير صحيح.',
        'invalid_setup_method' => 'طريقة الربط غير صحيحة.',
        'invalid_test_status' => 'حالة الاختبار غير صحيحة.',
        'file_required' => 'الملف مطلوب.',
        'upload_failed' => 'فشل رفع الملف.',
        'file_too_large' => 'حجم الملف أكبر من الحد المسموح.',
        'invalid_file_type' => 'نوع الملف غير مسموح.',
        'whatsapp_setup_not_ready' => 'إعداد واتساب غير جاهز. تأكد من استيراد قاعدة البيانات وتشغيل الخدمات المطلوبة.',
        'flow_name_required' => 'اسم المسار مطلوب.',
        'invalid_connection_source' => 'مصدر اتصال واتساب غير صحيح.',
        'keyword_required' => 'الكلمة المفتاحية مطلوبة.',
        'auto_reply_required_fields' => 'نوع الرد واسم الرد ونص الرسالة مطلوبة.',
        'phone_number_id_required' => 'معرف رقم واتساب مطلوب.',
        'contacts_array_required' => 'بيانات العملاء يجب أن تكون قائمة.',
        'conversation_id_and_body_required' => 'رقم المحادثة ونص الرد مطلوبان.',
        'conversation_not_found' => 'المحادثة غير موجودة.',
        'invalid_phone_number' => 'رقم الهاتف غير صحيح.',
        'invalid_template_category' => 'نوع القالب غير صحيح.',
        'contact_not_opted_in' => 'لا يمكن الإرسال لعميل غير موافق على استقبال الرسائل.',
        'csrf_token_invalid' => 'انتهت صلاحية الجلسة أو رمز الحماية غير صحيح. حدّث الصفحة ثم أعد المحاولة.',
        'required' => 'هذا الحقل مطلوب.',
        'invalid_value' => 'القيمة غير صحيحة.',
        'not_found' => 'المسار غير موجود.',
        'server_error' => 'حدث خطأ في الخادم.',
        'forbidden' => 'ليست لديك صلاحية لتنفيذ هذا الإجراء.',
        'rate_limited' => 'تم تجاوز حد الطلبات المسموح. حاول لاحقاً.',
        'invalid_webhook_token' => 'رمز Webhook غير صحيح.',
        'invalid_channel' => 'قناة التواصل غير صحيحة.',
        'omnichannel_not_ready' => 'طبقة القنوات الموحدة غير جاهزة. تأكد من تطبيق تحديثات قاعدة البيانات.',
    ];

    public static function get(string $code): string
    {
        return self::MESSAGES[$code] ?? $code;
    }

    public static function enrich(array $payload): array
    {
        if (isset($payload['error']) && is_string($payload['error'])) {
            $payload['message'] = self::get($payload['error']);
        }

        if (isset($payload['errors']) && is_array($payload['errors'])) {
            $translated = [];
            foreach ($payload['errors'] as $field => $error) {
                $translated[$field] = self::get((string) $error);
            }
            $payload['messages'] = $translated;
        }

        return $payload;
    }
}
