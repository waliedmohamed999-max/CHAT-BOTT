<?php
$title = 'مركز التسويق';
$scriptBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (isset($_GET['__mc_path'])) {
    $scriptBase = '';
}
$appUrl = rtrim(\MarketingCenter\Support\Env::get('APP_URL', $scriptBase), '/');
$nav = [
    'overview' => ['label' => 'مركز القيادة', 'icon' => 'OV'],
    'omnichannel' => ['label' => 'القنوات الموحدة', 'icon' => 'OC'],
    'setup-checklist' => ['label' => 'جاهزية الإطلاق', 'icon' => 'GO'],
    'platform-roadmap' => ['label' => 'خارطة التطوير', 'icon' => 'RM'],
    'whatsapp-setup-center' => ['label' => 'مركز إعداد واتساب', 'icon' => 'WS'],
    'connect-meta' => ['label' => 'ربط Meta', 'icon' => 'ME'],
    'whatsapp-setup' => ['label' => 'واتساب', 'icon' => 'WA'],
    'whatsapp-qr' => ['label' => 'ربط واتساب بالباركود', 'icon' => 'QR'],
    'chatbot-builder' => ['label' => 'منشئ الشات بوت', 'icon' => 'BOT'],
    'campaign-builder' => ['label' => 'الحملات', 'icon' => 'CA'],
    'templates' => ['label' => 'القوالب', 'icon' => 'TP'],
    'contacts' => ['label' => 'العملاء', 'icon' => 'CRM'],
    'inbox' => ['label' => 'المحادثات', 'icon' => 'IN'],
    'automation' => ['label' => 'الأتمتة', 'icon' => 'AU'],
    'social' => ['label' => 'السوشيال', 'icon' => 'SO'],
    'analytics' => ['label' => 'التحليلات', 'icon' => 'AN'],
    'ai-intelligence' => ['label' => 'ذكاء الأعمال AI', 'icon' => 'AI'],
    'marketplace' => ['label' => 'المتجر والتطبيقات', 'icon' => 'MP'],
    'enterprise' => ['label' => 'المنصة العالمية', 'icon' => 'EN'],
    'ai-commerce-os' => ['label' => 'نظام التجارة الذكي', 'icon' => 'OS'],
    'saas' => ['label' => 'الباقات والاشتراكات', 'icon' => 'SA'],
    'super-admin' => ['label' => 'إدارة المنصة', 'icon' => 'SU'],
    'settings' => ['label' => 'الإعدادات', 'icon' => 'ST'],
];
$settingsPageLabels = [
    'general' => 'الإعدادات العامة',
    'whatsapp' => 'إعدادات واتساب',
    'campaigns' => 'الحملات والحدود',
    'quick-replies' => 'الردود السريعة',
    'users' => 'المستخدمون',
    'roles' => 'الأدوار والصلاحيات',
    'companies' => 'الشركات والمتاجر',
    'departments' => 'الفرق والأقسام',
    'billing' => 'الاشتراكات والباقات',
    'security' => 'الأمان والجلسات',
    'developer' => 'Webhooks & API',
    'documents' => 'الملفات والمستندات',
    'notifications' => 'التنبيهات والإشعارات',
    'logs' => 'السجلات والمراقبة',
    'branding' => 'الهوية والـ White Label',
    'ai' => 'إعدادات الذكاء الاصطناعي',
    'backup' => 'النسخ الاحتياطي',
    'launch' => 'إعدادات الإطلاق',
];
$cards = [
    ['إجمالي الحملات', $data['campaigns'], '+12%', 'حملات واتساب والسوشيال النشطة'],
    ['الرسائل المرسلة', $data['sent'], '+28%', 'مرسلة، مستلمة، ومقروءة'],
    ['الرسائل الناجحة', $data['success'], '94%', 'معدل جودة التسليم'],
    ['الرسائل الفاشلة', $data['failed'], '-3%', 'أخطاء تحتاج متابعة'],
    ['جهات الاتصال', $data['contacts'], '+7%', 'عملاء لديهم موافقة محفوظة'],
];
$checklistLabels = [
    'Meta Connected' => 'تم ربط Meta',
    'WABA Connected' => 'تم ربط حساب WhatsApp Business',
    'Phone Number Connected' => 'تم ربط رقم واتساب',
    'Webhook Verified' => 'تم التحقق من Webhook',
    'Test Message Sent' => 'تم إرسال رسالة اختبار',
    'Templates Synced' => 'تمت مزامنة القوالب',
    'First Campaign Ready' => 'أول حملة جاهزة',
];
$currentLabel = $nav[$page]['label'] ?? 'مركز القيادة';
if (str_starts_with($page, 'settings-')) {
    $currentLabel = $settingsPageLabels[substr($page, 9)] ?? $nav['settings']['label'];
}
$chatbotTabs = ['نظرة عامة','مسارات البوت','الردود التلقائية','المساعد الذكي','الكلمات المفتاحية','المحفزات','التحويل لموظف','قاعدة المعرفة','التحليلات','الإعدادات'];
$sessionStatusLabels = [
    'waiting_for_scan' => 'بانتظار المسح',
    'qr_scanned' => 'تم مسح الباركود',
    'authenticating' => 'جاري التحقق',
    'connected' => 'متصل',
    'disconnected' => 'غير متصل',
    'expired' => 'منتهي',
    'pending' => 'قيد الانتظار',
];
$channelLabels = [
    'whatsapp_cloud' => 'واتساب الرسمي',
    'whatsapp_qr' => 'واتساب باركود',
    'instagram' => 'إنستجرام',
    'facebook' => 'ماسنجر',
    'telegram' => 'تيليجرام',
    'email' => 'البريد',
    'sms' => 'SMS',
    'live_chat' => 'دردشة الموقع',
];
$channelIcons = [
    'whatsapp_cloud' => 'WA',
    'whatsapp_qr' => 'QR',
    'instagram' => 'IG',
    'facebook' => 'FB',
    'telegram' => 'TG',
    'email' => 'EM',
    'sms' => 'SM',
    'live_chat' => 'LC',
];
$labelText = static function (?string $value): string {
    $map = [
        'free' => 'مجانية',
        'starter' => 'البداية',
        'professional' => 'احترافية',
        'enterprise' => 'مؤسسية',
        'trialing' => 'تجريبي',
        'active' => 'نشط',
        'disabled' => 'معطل',
        'uninstalled' => 'تمت الإزالة',
        'pending_review' => 'قيد المراجعة',
        'published' => 'منشور',
        'app' => 'تطبيق',
        'integration' => 'تكامل',
        'theme' => 'ثيم',
        'chatbot_template' => 'قالب بوت',
        'automation_template' => 'قالب أتمتة',
        'ai_pack' => 'حزمة ذكاء',
        'one_time' => 'دفع مرة واحدة',
        'subscription' => 'اشتراك',
        'revenue_share' => 'مشاركة إيراد',
        'connected' => 'متصل',
        'disconnected' => 'غير متصل',
        'synced' => 'متزامن',
        'healthy' => 'سليم',
        'needs_review' => 'يحتاج مراجعة',
        'missing_verify_token' => 'توكن التحقق ناقص',
        'configured' => 'مهيأ',
        'ready' => 'جاهز',
        'draft' => 'مسودة',
        'normal' => 'عادي',
        'high' => 'مرتفع',
        'urgent' => 'عاجل',
        'low' => 'منخفض',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'uploaded' => 'مرفوع',
        'super_admin' => 'مدير عام',
        'platform_admin' => 'مدير المنصة',
        'store_owner' => 'مالك المتجر',
        'store_admin' => 'مدير المتجر',
        'marketing_manager' => 'مدير التسويق',
        'support_agent' => 'موظف دعم',
        'sales_agent' => 'موظف مبيعات',
        'billing_agent' => 'موظف حسابات',
        'viewer' => 'مشاهد',
    ];

    return $map[$value ?? ''] ?? (string) $value;
};
?>
<!doctype html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(\MarketingCenter\Support\Security::csrfToken()) ?>">
    <meta name="theme-color" content="#334a91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Marketing Center">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="manifest" href="<?= htmlspecialchars($appUrl) ?>/manifest.webmanifest">
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/app.css">
</head>
<body>
<div class="ambient ambient-one"></div>
<div class="ambient ambient-two"></div>

<header class="mobile-app-header">
    <button class="mobile-header-btn" data-mobile-menu type="button" aria-label="فتح القائمة">☰</button>
    <div class="mobile-brand">
        <span>MC</span>
        <div>
            <strong><?= htmlspecialchars($currentLabel) ?></strong>
            <small>منصة تسويق ذكية</small>
        </div>
    </div>
    <div class="mobile-header-actions">
        <button class="mobile-header-btn" id="mobileSearch" type="button" aria-label="البحث">⌕</button>
        <button class="mobile-header-btn notification-dot" type="button" aria-label="التنبيهات">N</button>
        <button class="mobile-avatar" type="button" aria-label="الملف الشخصي">MC</button>
    </div>
</header>
<div class="mobile-drawer-backdrop" id="mobileDrawerBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span class="brand-mark">MC</span>
        <div class="brand-copy">
            <strong>مركز التسويق</strong>
            <small>مساحة نمو ذكية</small>
        </div>
    </div>
    <button class="collapse-btn" id="collapseSidebar" type="button" title="طي القائمة">⇄</button>
    <nav class="side-nav">
        <?php foreach ($nav as $key => $item): ?>
            <?php $isActiveNav = $page === $key || ($key === 'settings' && str_starts_with($page, 'settings-')); ?>
            <a class="<?= $isActiveNav ? 'active' : '' ?>" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/<?= $key ?>" title="<?= htmlspecialchars($item['label']) ?>">
                <span class="nav-icon"><?= htmlspecialchars($item['icon']) ?></span>
                <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-card">
        <span>رؤية ذكية</span>
        <strong>أفضل وقت للإرسال اليوم بين 7:30 و9:00 مساءً</strong>
        <small>مبني على نشاط العملاء الأخير</small>
    </div>
</aside>

<main class="shell">
    <header class="topbar">
        <div class="page-title">
            <p class="eyebrow">منصة تسويق ذكية متعددة القنوات</p>
            <h1><?= htmlspecialchars($currentLabel) ?></h1>
        </div>
        <div class="top-actions">
            <button class="icon-btn mobile-menu" id="mobileMenu" type="button" title="فتح القائمة">MN</button>
            <button class="search-trigger" id="openCommand" type="button"><span>بحث سريع أو أمر</span><kbd>⌘K</kbd></button>
            <button class="icon-btn notification-dot" type="button" title="التنبيهات">N</button>
            <button class="icon-btn" id="themeToggle" type="button" title="تبديل الوضع">◐</button>
            <a class="primary" href="<?= htmlspecialchars($connectUrl) ?>">ربط Meta</a>
        </div>
    </header>

    <?php if ($page === 'overview'): ?>
        <section class="hero-command">
            <div>
                <span class="premium-pill">مركز قيادة التسويق التنفيذي</span>
                <h2>لوحة قيادة موحدة للحملات، المحادثات، الإيرادات، وذكاء العملاء.</h2>
                <p>راقب الأداء اللحظي، اكتشف فرص النمو، وشغّل الحملات عبر WhatsApp وMeta من مساحة عمل واحدة.</p>
            </div>
            <div class="ai-orbit">
                <span>AI</span>
                <b>+18%</b>
                <small>توقع نمو التحويل</small>
            </div>
        </section>

        <section class="metric-grid">
            <?php foreach ($cards as $card): ?>
                <article class="metric-card lift-card">
                    <span><?= htmlspecialchars($card[0]) ?></span>
                    <strong class="counter" data-count="<?= (int) $card[1] ?>"><?= number_format((int) $card[1]) ?></strong>
                    <div><em><?= htmlspecialchars($card[2]) ?></em><small><?= htmlspecialchars($card[3]) ?></small></div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="workspace-grid">
            <article class="panel wide">
                <div class="panel-head"><div><h2>اتجاه الإيرادات والتفاعل</h2><span>تحليل مباشر للحملات النشطة</span></div><button class="ghost-btn">تصدير</button></div>
                <div class="chart-stage">
                    <div class="line-chart"><i style="height:42%"></i><i style="height:61%"></i><i style="height:54%"></i><i style="height:78%"></i><i style="height:69%"></i><i style="height:86%"></i><i style="height:74%"></i><i style="height:92%"></i></div>
                    <div class="chart-summary"><strong>94.2%</strong><span>معدل تسليم</span><p>القراءة ارتفعت 11% مقارنة بآخر 7 أيام.</p></div>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>رؤى AI</h2><span>اقتراحات قابلة للتنفيذ</span></div></div>
                <ul class="insight-list">
                    <li><b>فرصة</b><span>شريحة VIP تستجيب أكثر للقوالب المختصرة.</span></li>
                    <li><b>تنبيه</b><span>لا ترسل للحملات الجديدة قبل مزامنة القوالب.</span></li>
                    <li><b>توقع</b><span>أفضل حملة قادمة: كوبون ما بعد الشراء.</span></li>
                </ul>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>نشاط مباشر</h2><span>آخر أحداث النظام</span></div></div>
                <ul class="activity-feed">
                    <li><i></i><span>Webhook receiver جاهز لاستقبال الرسائل</span><small>الآن</small></li>
                    <li><i></i><span>Queue يتحقق من Opt-in قبل الإرسال</span><small>قبل 3 د</small></li>
                    <li><i></i><span>التوكنات مشفرة داخل قاعدة البيانات</span><small>آمن</small></li>
                </ul>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'connect-meta'): ?>
        <section class="panel-grid">
            <article class="panel wide integration-card">
                <div class="panel-head"><div><h2>تسجيل دخول Meta للأعمال</h2><span>ربط رسمي عبر OAuth والتسجيل المضمن</span></div><span class="status-pill <?= $connection ? 'ok' : 'pending' ?>"><?= $connection ? 'متصل' : 'غير متصل' ?></span></div>
                <p class="copy">اربط Business Portfolio وحساب WhatsApp Business وأرقام الإرسال من Meta بشكل مشفر وقابل للتدقيق.</p>
                <div class="step-row"><b>1</b><span>ربط Meta</span><b>2</b><span>اختيار Business</span><b>3</b><span>ربط WABA</span><b>4</b><span>تأكيد Webhook</span></div>
                <div class="button-row">
                    <a class="primary" href="<?= htmlspecialchars($connectUrl) ?>">ربط Meta</a>
                    <button data-api="/api/meta/disconnect" class="danger api-post">فصل الربط</button>
                    <button data-api="/api/meta/sync-assets" class="secondary api-post">تحديث الصلاحيات</button>
                </div>
            </article>
            <article class="panel">
                <div class="kv"><span>معرف النشاط التجاري</span><strong><?= htmlspecialchars($connection['business_id'] ?? 'بانتظار OAuth') ?></strong></div>
                <div class="kv"><span>معرف حساب واتساب للأعمال</span><strong><?= htmlspecialchars($phones[0]['waba_id'] ?? 'بانتظار التسجيل المضمن') ?></strong></div>
                <div class="kv"><span>معرف رقم الهاتف</span><strong><?= htmlspecialchars($phones[0]['phone_number_id'] ?? 'غير مربوط') ?></strong></div>
                <div class="kv"><span>حالة التوكن</span><strong><?= htmlspecialchars($connection['token_status'] ?? 'التخزين المشفر جاهز') ?></strong></div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'whatsapp-setup'): ?>
        <section class="panel-grid">
            <article class="panel wide form-panel">
                <div class="panel-head"><div><h2>إعداد رقم واتساب الرسمي</h2><span>واجهة Cloud API، الجودة، الحدود، والاختبار</span></div><span class="status-pill ok">الواجهة جاهزة</span></div>
                <div class="setup-grid">
                    <div class="kv"><span>اسم النشاط التجاري</span><strong><?= htmlspecialchars($phones[0]['verified_name'] ?? 'من ملف WABA') ?></strong></div>
                    <div class="kv"><span>جودة الرقم</span><strong><?= htmlspecialchars($phones[0]['quality_rating'] ?? 'تتم مزامنتها من Meta') ?></strong></div>
                    <div class="kv"><span>حد الإرسال</span><strong><?= htmlspecialchars($phones[0]['messaging_limit'] ?? 'تتم مزامنته من Meta') ?></strong></div>
                    <div class="kv"><span>Webhook</span><strong><?= htmlspecialchars($appUrl) ?>/api/webhooks/whatsapp</strong></div>
                </div>
                <form class="stack ajax-form compact-form" data-endpoint="/api/whatsapp/send-test">
                    <input name="phone_number_id" placeholder="معرف رقم الهاتف اختياري">
                    <input name="to" placeholder="رقم المستلم مثال 9665xxxxxxx">
                    <input name="template_name" placeholder="اسم قالب معتمد">
                    <button class="primary">إرسال اختبار</button>
                </form>
                <div class="advanced-settings settings-deck">
                    <article><h3>الملف التجاري</h3><label>اسم العرض<input placeholder="اسم النشاط الظاهر للعملاء"></label><label>وصف مختصر<input placeholder="وصف رسمي للنشاط"></label><label>رابط الموقع<input placeholder="https://example.com"></label></article>
                    <article><h3>جودة الرقم</h3><label>تنبيه الجودة<select><option>إرسال تنبيه عند الانخفاض</option><option>إيقاف التنبيه</option></select></label><label>حد الإرسال اليومي<input placeholder="حسب حدود Meta"></label><label>إيقاف الحملات عند الجودة المنخفضة<select><option>مفعل</option><option>معطل</option></select></label></article>
                    <article><h3>نافذة خدمة العملاء</h3><label>مدة النافذة<input value="24 ساعة"></label><label>رد خارج النافذة<select><option>استخدم قالب معتمد فقط</option></select></label><label>تذكير قبل الإغلاق<input value="قبل ساعة"></label></article>
                    <article><h3>الحماية والامتثال</h3><label>منع غير الموافقين<select><option>إجباري</option></select></label><label>كلمات الإلغاء<input value="STOP, إلغاء, توقف"></label><label>سجل التدقيق<select><option>مفعل</option></select></label></article>
                    <article><h3>الردود السريعة</h3><label>رد الترحيب<input value="أهلاً بك، كيف يمكننا مساعدتك؟"></label><label>رد الطلب<input value="أرسل رقم الطلب وسنراجع حالته."></label><label>رد الشحن<input value="سيتم إرسال رابط التتبع عند توفره."></label></article>
                    <article><h3>التنبيهات التشغيلية</h3><label>فشل الويب هوك<select><option>تنبيه فوري</option></select></label><label>فشل الإرسال<select><option>تنبيه عند تكرار الخطأ</option></select></label><label>انتهاء التوكن<select><option>تنبيه قبل الانتهاء</option></select></label></article>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'whatsapp-setup-center'): ?>
        <section class="setup-center-hero panel wide">
            <div>
                <span class="premium-pill">إعداد واتساب الموحد</span>
                <h2>مركز إعداد واتساب الموحد</h2>
                <p>اختر طريقة الربط، جهّز بيانات النشاط والمستندات، اختبر الاتصال، وتأكد من جاهزية الإطلاق من تجربة واحدة.</p>
            </div>
            <div class="launch-score">
                <strong><?= (int) ($setupReadiness['score'] ?? 0) ?></strong>
                <span>درجة الجاهزية</span>
                <small><?= htmlspecialchars($setupReadiness['status'] ?? 'غير جاهز') ?></small>
            </div>
        </section>
        <section class="setup-wizard panel wide">
            <div class="wizard-steps" data-wizard-tabs>
                <button class="active" data-step="1">1. طريقة الربط</button>
                <button data-step="2">2. بيانات النشاط</button>
                <button data-step="3">3. المستندات</button>
                <button data-step="4">4. الربط</button>
                <button data-step="5">5. الاختبار</button>
                <button data-step="6">6. الجاهزية</button>
            </div>

            <div class="wizard-panel active" data-step-panel="1">
                <div class="method-cards">
                    <article>
                        <span class="premium-pill">رسمي</span>
                        <h3>واجهة Meta Cloud API لواتساب</h3>
                        <p>مناسب للحملات الرسمية، القوالب، Webhooks، والإرسال التجاري المنظم.</p>
                        <ul><li>يدعم القوالب الرسمية</li><li>أفضل للحملات الكبيرة</li><li>يحتاج توثيق النشاط</li></ul>
                        <button class="primary setup-method" data-method="meta_cloud_api">المتابعة عبر Meta</button>
                    </article>
                    <article>
                        <span class="premium-pill">ربط سريع</span>
                        <h3>جلسة واتساب بالباركود</h3>
                        <p>ربط سريع بالباركود للمحادثات والردود اليومية بدون إعداد Meta كامل.</p>
                        <ul><li>باركود سريع</li><li>مناسب للردود</li><li>غير مفضل للحملات الكبيرة</li></ul>
                        <button class="secondary setup-method" data-method="qr_web_session">المتابعة بالباركود</button>
                    </article>
                </div>
                <div class="comparison-table">
                    <span>الميزة</span><b>الواجهة الرسمية</b><b>جلسة الباركود</b>
                    <span>القوالب الرسمية</span><b>نعم</b><b>لا</b>
                    <span>الحملات الكبيرة</span><b>مناسب</b><b>محدود</b>
                    <span>سرعة الربط</span><b>متوسط</b><b>سريع</b>
                    <span>توثيق النشاط</span><b>مطلوب غالباً</b><b>غير مطلوب</b>
                </div>
            </div>

            <div class="wizard-panel" data-step-panel="2">
                <form class="setup-profile-form">
                    <input name="business_name" placeholder="اسم النشاط التجاري" value="<?= htmlspecialchars($setupProfile['business_name'] ?? '') ?>">
                    <input name="store_name" placeholder="اسم المتجر" value="<?= htmlspecialchars($setupProfile['store_name'] ?? '') ?>">
                    <input name="country" placeholder="الدولة" value="<?= htmlspecialchars($setupProfile['country'] ?? '') ?>">
                    <input name="city" placeholder="المدينة" value="<?= htmlspecialchars($setupProfile['city'] ?? '') ?>">
                    <input name="business_type" placeholder="نوع النشاط" value="<?= htmlspecialchars($setupProfile['business_type'] ?? '') ?>">
                    <input name="website_url" placeholder="رابط الموقع الإلكتروني" value="<?= htmlspecialchars($setupProfile['website_url'] ?? '') ?>">
                    <input name="store_url" placeholder="رابط المتجر" value="<?= htmlspecialchars($setupProfile['store_url'] ?? '') ?>">
                    <input name="facebook_url" placeholder="رابط صفحة فيسبوك" value="<?= htmlspecialchars($setupProfile['facebook_url'] ?? '') ?>">
                    <input name="instagram_url" placeholder="رابط إنستجرام" value="<?= htmlspecialchars($setupProfile['instagram_url'] ?? '') ?>">
                    <input name="official_email" placeholder="البريد الإلكتروني الرسمي" value="<?= htmlspecialchars($setupProfile['official_email'] ?? '') ?>">
                    <input name="official_phone" placeholder="رقم الهاتف الرسمي" value="<?= htmlspecialchars($setupProfile['official_phone'] ?? '') ?>">
                    <input name="whatsapp_phone" placeholder="رقم واتساب المطلوب ربطه" value="<?= htmlspecialchars($setupProfile['whatsapp_phone'] ?? '') ?>">
                    <textarea name="business_description" placeholder="وصف النشاط التجاري"><?= htmlspecialchars($setupProfile['business_description'] ?? '') ?></textarea>
                    <label><input type="checkbox" name="has_meta_business" value="1" <?= !empty($setupProfile['has_meta_business']) ? 'checked' : '' ?>> يوجد Meta Business Manager</label>
                    <label><input type="checkbox" name="is_business_verified" value="1" <?= !empty($setupProfile['is_business_verified']) ? 'checked' : '' ?>> الحساب موثق</label>
                    <label><input type="checkbox" name="has_privacy_policy" value="1" <?= !empty($setupProfile['has_privacy_policy']) ? 'checked' : '' ?>> يوجد Privacy Policy</label>
                    <label><input type="checkbox" name="has_terms" value="1" <?= !empty($setupProfile['has_terms']) ? 'checked' : '' ?>> يوجد Terms & Conditions</label>
                    <button class="primary">حفظ بيانات النشاط</button>
                </form>
            </div>

            <div class="wizard-panel" data-step-panel="3">
                <div class="upload-grid">
                    <?php foreach (['commercial_register' => 'السجل التجاري', 'tax_card' => 'البطاقة الضريبية', 'identity' => 'الهوية أو جواز السفر', 'logo' => 'شعار النشاط', 'domain_ownership' => 'إثبات ملكية الموقع', 'meta_business' => 'بيانات Meta Business', 'privacy_policy' => 'سياسة الخصوصية', 'terms' => 'الشروط والأحكام', 'additional' => 'مستند إضافي'] as $type => $label): ?>
                        <form class="upload-card setup-upload-form" enctype="multipart/form-data">
                            <input type="hidden" name="document_type" value="<?= htmlspecialchars($type) ?>">
                            <b><?= htmlspecialchars($label) ?></b>
                            <span>PDF / PNG / JPG / DOCX حتى 8MB</span>
                            <input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.docx">
                            <button class="secondary">رفع الملف</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="wizard-panel" data-step-panel="4">
                <div class="setup-connect-grid">
                    <article>
                        <h3>الربط الرسمي Meta</h3>
                        <div class="checklist mini"><div><b>✓</b><span>تطبيق Meta للمطورين</span></div><div><b>✓</b><span>مدير الأعمال</span></div><div><b>!</b><span>توثيق النشاط</span></div><div><b>!</b><span>مزامنة القوالب</span></div></div>
                        <a class="primary" href="<?= htmlspecialchars($connectUrl) ?>">ربط Meta</a>
                    </article>
                    <article>
                        <h3>الربط بالباركود QR</h3>
                        <div class="qr-box small" id="setupQrBox"><div class="qr-placeholder"><b>QR</b><span>إنشاء جلسة باركود</span></div></div>
                        <div class="button-row"><button data-api="/api/whatsapp-setup/qr/create" class="secondary api-post">إنشاء باركود</button><button data-api="/api/whatsapp-setup/qr/disconnect" class="danger api-post">فصل الجلسة</button></div>
                    </article>
                </div>
            </div>

            <div class="wizard-panel" data-step-panel="5">
                <div class="test-grid">
                    <form class="setup-test-form">
                        <h3>إرسال رسالة اختبار</h3>
                        <input name="to" placeholder="رقم المستلم">
                        <input name="body" placeholder="نص الرسالة">
                        <button class="primary">تشغيل الاختبار</button>
                    </form>
                    <article><h3>الويب هوك</h3><p>اختبار جاهزية الويب هوك واستقبال تحديثات الحالة.</p><button data-api="/api/whatsapp-setup/test/webhook" class="secondary api-post">اختبار الويب هوك</button></article>
                    <article><h3>القوالب</h3><p>اختبار مزامنة القوالب عند استخدام الواجهة الرسمية.</p><button data-api="/api/whatsapp/templates/sync" class="secondary api-post">مزامنة القوالب</button></article>
                </div>
                <div class="result-cards"><span class="status-pill pending">قيد الانتظار</span><span class="status-pill ok">ناجح</span><span class="status-pill danger-state">فشل</span><span class="status-pill pending">تحذير</span></div>
            </div>

            <div class="wizard-panel" data-step-panel="6">
                <div class="readiness-board">
                    <div class="launch-score large"><strong><?= (int) ($setupReadiness['score'] ?? 0) ?></strong><span>من 100</span><small><?= htmlspecialchars($setupReadiness['status'] ?? 'غير جاهز') ?></small></div>
                    <div class="checklist">
                        <?php foreach (($setupReadiness['items'] ?? []) as $item => $ready): ?>
                            <div class="<?= $ready ? 'ready' : '' ?>"><b><?= $ready ? '✓' : '!' ?></b><span><?= htmlspecialchars($item) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'whatsapp-qr'): ?>
        <section class="panel wide">
            <div class="panel-head">
                <div><h2>ربط واتساب بالباركود</h2><span>جلسة واتساب ويب منفصلة بجانب الربط الرسمي</span></div>
                <span class="status-pill <?= ($qrSession['session_status'] ?? '') === 'connected' ? 'ok' : 'pending' ?>" id="qrStatusPill"><?= htmlspecialchars($sessionStatusLabels[$qrSession['session_status'] ?? 'disconnected'] ?? 'غير متصل') ?></span>
            </div>
            <div class="warning-banner">
                <b>تنبيه مهم</b>
                <span>الربط الرسمي عبر الواجهة السحابية هو الخيار الأفضل للحملات والقوالب والالتزام. جلسة الباركود مناسبة للمحادثات والتشغيل الخفيف، ولا تُستخدم لقوالب Meta الرسمية أو الإرسال الجماعي الكبير.</span>
            </div>
            <div class="connect-choice">
                <article><b>الواجهة الرسمية من واتساب</b><span>رسمية، تدعم القوالب، الويب هوك، جودة الرقم، وحدود Meta.</span></article>
                <article class="selected"><b>جلسة واتساب بالباركود</b><span>جلسة تشبه واتساب ويب للمحادثات والردود والتحكم الخفيف في الرقم.</span></article>
            </div>
        </section>
        <section class="qr-layout">
            <article class="panel qr-card">
                <div class="panel-head"><div><h2>جلسة الباركود</h2><span>امسح الكود من تطبيق واتساب</span></div></div>
                <div class="qr-box" id="qrBox">
                    <?php if (!empty($qrSession['last_qr_code'])): ?>
                        <img src="<?= htmlspecialchars($qrSession['last_qr_code']) ?>" alt="WhatsApp QR Code">
                    <?php else: ?>
                        <div class="qr-placeholder"><b>QR</b><span>اضغط إنشاء جلسة لبدء الربط</span></div>
                    <?php endif; ?>
                </div>
                <div class="button-row">
                    <button data-api="/api/whatsapp-qr/session/create" class="primary api-post qr-action">إنشاء جلسة باركود</button>
                    <button data-api="/api/whatsapp-qr/session/reconnect" class="secondary api-post qr-action">إعادة الاتصال</button>
                    <button data-api="/api/whatsapp-qr/session/disconnect" class="danger api-post qr-action">فصل الجلسة</button>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>بيانات الرقم</h2><span>حالة الجهاز المتصل</span></div></div>
                <div class="device-profile">
                    <div class="device-avatar"><?= !empty($qrSession['avatar_url']) ? '<img src="' . htmlspecialchars($qrSession['avatar_url']) . '" alt="avatar">' : 'WA' ?></div>
                    <div><strong><?= htmlspecialchars($qrSession['display_name'] ?? 'غير متصل') ?></strong><span><?= htmlspecialchars($qrSession['phone_number'] ?? 'لا يوجد رقم مربوط') ?></span></div>
                </div>
                <div class="kv"><span>حالة الجلسة</span><strong id="qrStatusText"><?= htmlspecialchars($sessionStatusLabels[$qrSession['session_status'] ?? 'disconnected'] ?? 'غير متصل') ?></strong></div>
                <div class="kv"><span>آخر اتصال</span><strong><?= htmlspecialchars($qrSession['last_connected_at'] ?? 'لم يتصل بعد') ?></strong></div>
                <div class="kv"><span>التحديث المباشر</span><strong>SSE: /api/whatsapp-qr/events</strong></div>
            </article>
            <article class="panel wide">
                <div class="panel-head"><div><h2>حدود الاستخدام الآمن</h2><span>حماية الرقم والجلسة</span></div></div>
                <div class="launch-grid">
                    <div><b>1</b><span>استخدم QR للمحادثات والردود، وليس حملات ضخمة.</span></div>
                    <div><b>2</b><span>يتم تطبيق Batch صغير وDelay عشوائي عند الإرسال عبر QR.</span></div>
                    <div><b>3</b><span>تظل الواجهة السحابية الرسمية هي المصدر الرسمي للقوالب والحملات الكبيرة.</span></div>
                    <div><b>4</b><span>بيانات الجلسة مشفرة ولا تظهر في الواجهة.</span></div>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'campaign-builder'): ?>
        <section class="builder">
            <form class="panel form-panel ajax-form campaign-studio" data-endpoint="/api/campaigns">
                <div class="panel-head"><div><h2>منشئ الحملات المرئي</h2><span>أنشئ حملة واتساب خطوة بخطوة</span></div><span class="premium-pill">بمساعدة الذكاء الاصطناعي</span></div>
                <div class="progress-steps"><span class="active">الجمهور</span><span class="active">القالب</span><span>الجدولة</span><span>الإطلاق</span></div>
                <input name="name" placeholder="اسم الحملة">
                <select name="campaign_type"><option value="marketing">تسويقية</option><option value="utility">خدمية</option><option value="reactivation">إعادة تنشيط</option></select>
                <select name="send_source"><option value="cloud_api">الواجهة الرسمية Cloud API</option><option value="qr_session">جلسة واتساب بالباركود</option></select>
                <select name="audience_type"><option value="segment">شريحة عملاء</option><option value="csv_import">استيراد CSV</option><option value="all_opted_in">كل العملاء الموافقين</option></select>
                <input name="template_id" placeholder="رقم القالب المعتمد">
                <textarea name="message_body" placeholder="نص رسالة جلسة الباركود عند اختيار الإرسال عبر الباركود"></textarea>
                <textarea name="variables" placeholder="متغيرات القالب بصيغة JSON"></textarea>
                <input type="datetime-local" name="scheduled_at">
                <input name="estimated_cost" placeholder="التكلفة التقديرية">
                <div class="ai-suggestion"><b>اقتراح AI</b><span>ابدأ بشريحة العملاء الذين اشتروا خلال آخر 30 يوم لرفع معدل التحويل.</span></div>
                <div class="warning-banner compact-warning"><b>جلسة الباركود</b><span>عند اختيار الباركود سيتم استخدام حدود آمنة وتأخير عشوائي، ولا يتم استخدام القوالب الرسمية.</span></div>
                <div class="button-row"><button class="secondary">حفظ كمسودة</button><button class="primary">جدولة</button><button type="button" class="secondary">إرسال اختبار</button><button type="button" class="danger">إطلاق الحملة</button></div>
            </form>
            <aside class="phone-preview">
                <div class="wa-device">
                    <div class="wa-statusbar"><span>9:41</span><span>•••</span></div>
                    <div class="wa-chat-head">
                        <span class="wa-avatar">MC</span>
                        <div><strong>مركز التسويق</strong><small>واتساب للأعمال</small></div>
                    </div>
                    <div class="wa-chat-body">
                        <div class="wa-date">اليوم</div>
                        <article class="wa-template">
                            <div class="wa-template-label">قالب تسويقي</div>
                            <h3>عرض حصري لك يا {{1}}</h3>
                            <p>مرحباً {{1}}، عرضك جاهز. استخدم الكود <b>SAVE20</b> قبل نهاية اليوم.</p>
                            <small>للانسحاب من الرسائل التسويقية اكتب STOP.</small>
                            <div class="wa-time">09:41 ✓✓</div>
                            <div class="wa-buttons">
                                <button type="button">عرض العرض</button>
                                <button type="button">التواصل مع الدعم</button>
                            </div>
                        </article>
                        <div class="wa-compliance">سيتم الإرسال فقط للعملاء الموافقين وبقالب معتمد من Meta.</div>
                    </div>
                </div>
            </aside>
        </section>
    <?php endif; ?>

    <?php if ($page === 'chatbot-builder'): ?>
        <section class="panel wide chatbot-hero">
            <div>
                <span class="premium-pill">منشئ الشات بوت والردود التلقائية</span>
                <h2>ابنِ بوت واتساب ذكي بدون كود</h2>
                <p>ردود تلقائية، منشئ مسارات، كلمات مفتاحية، ردود ذكية، تحويل لموظف، قاعدة معرفة، وتحليلات موحدة تعمل مع واتساب، إنستجرام، ماسنجر، تيليجرام، البريد، SMS، ودردشة الموقع.</p>
            </div>
            <div class="chatbot-mode">
                <span>مصدر الاتصال</span>
                <select id="chatbotConnectionSource">
                    <option value="all_channels">كل القنوات</option>
                    <option value="both">واتساب الرسمي + QR</option>
                    <option value="meta_cloud_api">WhatsApp Cloud API</option>
                    <option value="qr_web_session">WhatsApp QR Session</option>
                    <option value="instagram">إنستجرام</option>
                    <option value="facebook">ماسنجر</option>
                    <option value="telegram">تيليجرام</option>
                    <option value="email">البريد</option>
                    <option value="sms">SMS</option>
                    <option value="live_chat">دردشة الموقع</option>
                </select>
            </div>
        </section>
        <section class="metric-grid">
            <article class="metric-card lift-card"><span>الردود التلقائية</span><strong><?= (int) ($chatbotOverview['auto_replies'] ?? 0) ?></strong><small>ردود نشطة</small></article>
            <article class="metric-card lift-card"><span>مسارات نشطة</span><strong><?= (int) ($chatbotOverview['active_flows'] ?? 0) ?></strong><small>سيناريوهات البوت</small></article>
            <article class="metric-card lift-card"><span>تحويل للبشر</span><strong><?= (int) ($chatbotOverview['handover_count'] ?? 0) ?></strong><small>تحويل لموظف</small></article>
            <article class="metric-card lift-card"><span>نسبة الحل</span><strong>0%</strong><small>معدل حل المحادثات</small></article>
            <article class="metric-card lift-card"><span>وقت الرد</span><strong>0ث</strong><small>متوسط وقت الرد</small></article>
        </section>
        <?php $chatbotHealth = $chatbotBuilder['health'] ?? ['score' => 0, 'items' => []]; ?>
        <section class="panel wide chatbot-health-panel">
            <div class="panel-head">
                <div>
                    <h2>جاهزية مسار البوت</h2>
                    <span>فحص سريع للربط، الأقسام، الردود، الكلمات المفتاحية، قاعدة المعرفة، وسجلات التشغيل.</span>
                </div>
                <span class="status-pill <?= ((int) ($chatbotHealth['score'] ?? 0)) >= 70 ? 'ok' : 'pending' ?>"><?= (int) ($chatbotHealth['score'] ?? 0) ?>%</span>
            </div>
            <div class="chatbot-health-grid">
                <?php foreach (($chatbotHealth['items'] ?? []) as $item): ?>
                    <article class="<?= !empty($item['ready']) ? 'ready' : 'review' ?>">
                        <b><?= !empty($item['ready']) ? '✓' : '!' ?></b>
                        <strong><?= htmlspecialchars($item['label'] ?? '') ?></strong>
                        <span><?= htmlspecialchars($item['message'] ?? '') ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel wide chatbot-diagnostics-panel" id="chatbotDiagnosticsPanel">
            <div class="panel-head">
                <div>
                    <h2>تشخيص مسارات الرد الآلي</h2>
                    <span>اختبار سريع للأقسام، التحويل لموظف، وتصنيف نية العميل قبل نشر البوت.</span>
                </div>
                <div class="button-row">
                    <span class="status-pill pending" id="chatbotDiagnosticsScore">لم يتم الفحص</span>
                    <button class="secondary run-chatbot-diagnostics" type="button">تشغيل التشخيص</button>
                </div>
            </div>
            <div class="chatbot-diagnostics-grid" id="chatbotDiagnosticsResults">
                <article><b>الأقسام</b><span>يفحص المبيعات، الدعم، الطلبات، الفواتير، الشكاوى، والتحويل لموظف.</span></article>
                <article><b>تصنيف AI</b><span>يتأكد من توجيه أسئلة السعر والشحن والدفع والشكوى للقسم المناسب.</span></article>
                <article><b>جاهزية النشر</b><span>لا تنشر البوت قبل وصول التشخيص إلى 100% في البيئة الحقيقية.</span></article>
            </div>
        </section>
        <section class="panel wide whatsapp-bot-builder">
            <div class="builder-actions">
                <span class="bot-status-toggle"><i></i> الحالة: منشط</span>
                <button class="secondary bot-preview-run" type="button">معاينة المحادثة</button>
                <button class="secondary bot-customer-test" type="button">تشغيل تجربة كعميل</button>
                <button class="secondary bot-all-depts-test" type="button">فحص كل الأقسام</button>
                <button class="secondary load-current-flow" type="button">تحميل آخر مسار</button>
                <button class="primary chatbot-create-flow" type="button" data-flow-status="draft">حفظ المسار</button>
                <button class="primary chatbot-publish-flow" type="button" data-flow-status="active">نشر البوت</button>
                <button class="secondary chatbot-pause-flow" type="button" data-flow-status="paused">إيقاف البوت</button>
                <button class="secondary auto-arrange-flow" type="button">إعادة ترتيب الشجرة</button>
                <button class="secondary add-department-btn" type="button">إضافة قسم</button>
                <button class="danger test-handover-btn" type="button">اختبار التحويل</button>
            </div>
            <div class="wa-builder-layout">
                <aside class="wa-preview-phone">
                    <div class="wa-phone-frame">
                        <div class="wa-phone-top"></div>
                        <div class="wa-preview-header">
                            <span class="wa-avatar store-avatar">MC</span>
                            <div><strong>متجري الإلكتروني</strong><small>متصل الآن</small></div>
                            <button type="button">☎</button><button type="button">⌕</button><button type="button">⋮</button>
                        </div>
                        <div class="wa-preview-chat" id="botPreviewChat">
                            <div class="wa-day">اليوم</div>
                            <div class="wa-msg customer"><p>السلام عليكم</p><time>11:20 AM ✓✓</time></div>
                            <div class="wa-msg bot"><p>أهلاً بك 👋<br>كيف يمكننا مساعدتك اليوم؟</p><time>09:41 ✓✓</time></div>
                            <div class="wa-quick-replies">
                                <button data-preview-choice="المبيعات">المبيعات</button>
                                <button data-preview-choice="الدعم الفني">الدعم الفني</button>
                                <button data-preview-choice="الطلبات والشحن">الطلبات والشحن</button>
                                <button data-preview-choice="الحسابات والفواتير">الحسابات والفواتير</button>
                                <button data-preview-choice="الشكاوى">الشكاوى</button>
                                <button data-preview-choice="التحدث مع موظف">التحدث مع موظف</button>
                            </div>
                            <div class="typing-indicator"><i></i><i></i><i></i></div>
                        </div>
                        <div class="wa-input-bar">
                            <button class="wa-attach-btn" type="button">＋</button>
                            <input id="waPreviewInput" value="" placeholder="اكتب رسالة اختبار">
                            <button class="wa-preview-send" type="button">إرسال</button>
                        </div>
                    </div>
                </aside>

                <aside class="bot-flow-tree">
                    <div class="panel-head"><div><h2>شجرة الردود</h2><span>قم ببناء تدفق الردود الآلية خطوة بخطوة</span></div></div>
                    <input class="flow-search" placeholder="بحث في التدفق">
                    <button class="primary add-flow-step" type="button">+ إضافة خطوة جديدة</button>
                    <div class="flow-step-list">
                        <article class="active" data-step-key="welcome"><span>⌂</span><div><b>الرسالة الترحيبية</b><small>بداية المحادثة</small></div><em></em></article>
                        <article data-step-key="departments"><span>☷</span><div><b>القائمة الرئيسية</b><small>6 خيارات</small></div><em class="green"></em></article>
                        <article data-step-key="sales"><span>▣</span><div><b>المبيعات والاستفسارات</b><small>3 خيارات</small></div><em class="purple"></em></article>
                        <article data-step-key="orders"><span>▣</span><div><b>تتبع الطلب</b><small>ينتظر رقم الطلب</small></div><em class="blue"></em></article>
                        <article data-step-key="billing"><span>▣</span><div><b>الدفع وطرق الدفع</b><small>3 خيارات</small></div><em class="orange"></em></article>
                        <article data-step-key="support"><span>?</span><div><b>الدعم والأسئلة الشائعة</b><small>4 خيارات</small></div><em class="pink"></em></article>
                        <article data-step-key="complaints"><span>!</span><div><b>الشكاوى</b><small>تحويل للقسم المختص</small></div><em class="pink"></em></article>
                        <article data-step-key="handover"><span>♙</span><div><b>تحدث مع موظف</b><small>تحويل لموظف</small></div><em class="blue"></em></article>
                        <article data-step-key="end"><span>⚑</span><div><b>نهاية المحادثة</b><small>إنهاء المحادثة</small></div></article>
                    </div>
                </aside>

                <main class="bot-flow-canvas">
                    <div class="canvas-title"><h2>محرر تدفق الردود</h2><span>اسحب وأفلت لإنشاء تدفق الردود الآلية</span></div>
                    <div class="flow-canvas-toolbar">
                        <button type="button" class="secondary flow-pan-btn">⌘</button>
                        <button type="button" class="secondary flow-reset-position">↔</button>
                        <button type="button" class="secondary flow-zoom-label">100%</button>
                        <span class="status-pill ok">المسار مفعل</span>
                        <button type="button" class="secondary add-node-btn">إضافة عقدة</button>
                        <button type="button" class="secondary flow-zoom-in">تكبير +</button>
                        <button type="button" class="secondary flow-zoom-out">تصغير -</button>
                        <button type="button" class="secondary auto-arrange-flow">ترتيب تلقائي</button>
                    </div>
                    <div class="department-flow-map" id="departmentFlowMap">
                        <svg viewBox="0 0 980 760" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M860 80 C790 80 760 80 700 80" />
                            <path d="M610 80 C540 80 510 80 450 80" />
                            <path d="M380 105 C330 40 260 -45 210 -30" />
                            <path d="M380 120 C310 120 265 120 210 120" />
                            <path d="M380 135 C320 190 270 250 210 270" />
                            <path d="M380 150 C315 290 275 410 210 430" />
                            <path d="M380 165 C310 390 270 570 210 590" />
                            <path d="M380 180 C310 520 270 720 210 720" />
                        </svg>
                        <article class="dept-node start" data-node-title="بداية المسار" data-node-message="بداية المحادثة" style="--x:820px;--y:42px"><b>بداية المسار</b><span>المحفز: أول رسالة</span></article>
                        <article class="dept-node message" data-node-title="رسالة الترحيب" data-node-message="أهلاً بك 👋 كيف يمكننا مساعدتك اليوم؟" style="--x:565px;--y:42px"><b>رسالة الترحيب</b><span>رسالة بداية المحادثة</span></article>
                        <article class="dept-node list selected" data-node-title="اختيار القسم" data-node-message="اختر القسم المناسب" style="--x:315px;--y:42px"><b>اختيار القسم</b><span>6 اختيارات</span></article>
                        <article class="dept-node sales" data-node-title="المبيعات" data-node-message="يسعدنا مساعدتك في المبيعات. هل تريد معرفة الأسعار أم العروض أم التحدث مع مستشار مبيعات؟" style="--x:50px;--y:-34px"><b>المبيعات</b><span>طابور المبيعات</span></article>
                        <article class="dept-node support" data-node-title="الدعم الفني" data-node-message="من فضلك اختر نوع المشكلة التي تواجهك." style="--x:50px;--y:116px"><b>الدعم الفني</b><span>طابور الدعم الفني</span></article>
                        <article class="dept-node orders" data-node-title="الطلبات والشحن" data-node-message="يمكنك متابعة طلبك من هنا. من فضلك أرسل رقم الطلب." style="--x:50px;--y:266px"><b>الطلبات والشحن</b><span>ينتظر رقم الطلب</span></article>
                        <article class="dept-node billing" data-node-title="الحسابات والفواتير" data-node-message="اختر الخدمة المطلوبة." style="--x:50px;--y:416px"><b>الحسابات والفواتير</b><span>طابور الحسابات</span></article>
                        <article class="dept-node complaints" data-node-title="الشكاوى" data-node-message="نأسف لسماع ذلك. من فضلك اكتب تفاصيل الشكوى وسيتم تحويلها للقسم المختص." style="--x:50px;--y:566px"><b>الشكاوى</b><span>طابور الشكاوى</span></article>
                        <article class="dept-node handover" data-node-title="التحدث مع موظف" data-node-message="سيتم تحويلك الآن إلى أحد ممثلي خدمة العملاء." style="--x:50px;--y:696px"><b>التحدث مع موظف</b><span>تحويل بشري</span></article>
                    </div>
                </main>

                <aside class="bot-node-settings">
                    <div class="panel-head"><div><h2>إضافة مكون</h2><span>اسحب المكون إلى المخطط</span></div></div>
                    <div class="component-palette">
                        <button type="button" data-component-type="message"><span>▤</span> رسالة نصية</button>
                        <button type="button" data-component-type="buttons"><span>☷</span> زر / أزرار</button>
                        <button type="button" data-component-type="question"><span>?</span> سؤال</button>
                        <button type="button" data-component-type="condition"><span>⌘</span> شروط</button>
                        <button type="button" data-component-type="human_handover"><span>♙</span> تحويل لموظف</button>
                        <button type="button" data-component-type="delay"><span>◴</span> تأخير</button>
                        <button type="button" data-component-type="api_request"><span>API</span> طلب API</button>
                        <button type="button" data-component-type="end"><span>⚑</span> نهاية</button>
                    </div>
                    <div class="panel-head settings-title"><div><h2>إعدادات العقدة</h2><span>أي تعديل يظهر فوراً في واتساب Preview</span></div></div>
                    <label>اسم العقدة<input id="nodeTitleInput" value="اختيار القسم"></label>
                    <label>نوع العقدة<select id="nodeTypeInput"><option value="message">رسالة نصية / قائمة</option><option value="question">سؤال ينتظر رد</option><option value="condition">شرط</option><option value="delay">تأخير</option><option value="ai_reply">رد ذكي</option><option value="human_handover">تحويل لموظف</option><option value="api_request">طلب API</option><option value="end">رسالة نهاية</option></select></label>
                    <label>نص الرسالة<textarea id="nodeMessageInput">أهلاً بك 👋
كيف يمكننا مساعدتك اليوم؟</textarea></label>
                    <label>أزرار الرد<textarea id="nodeButtonsInput">المبيعات
الدعم الفني
الطلبات والشحن
الحسابات والفواتير
الشكاوى
التحدث مع موظف</textarea></label>
                    <label>القسم المرتبط<select id="nodeDepartmentInput"><option>المبيعات</option><option>الدعم الفني</option><option>الطلبات والشحن</option><option>الحسابات والفواتير</option><option>الشكاوى</option></select></label>
                    <label>Tag تلقائي<input id="nodeTagInput" value="department_selected"></label>
                    <div class="settings-checks">
                        <label><input type="checkbox"> تحويل لموظف</label>
                        <label><input type="checkbox" checked> ينتظر رد العميل</label>
                        <label><input type="checkbox"> حفظ رد العميل</label>
                        <label><input type="checkbox" checked> العقدة مفعلة</label>
                    </div>
                    <label>الخطوة التالية<select id="nodeNextInput"><option value="department_reply">عقدة رد القسم</option><option value="human_handover">تحويل لموظف</option><option value="end">نهاية المسار</option></select></label>
                    <div class="button-row"><button class="primary save-node-settings" type="button">حفظ الإعدادات</button><button class="danger delete-node-btn" type="button">حذف العقدة</button></div>
                    <div class="autosave-state">Auto Save جاهز</div>
                    <div class="bot-settings-menu">
                        <b>إعدادات البوت</b>
                        <a href="#chatbotFlowInfo">الإعدادات العامة</a>
                        <a href="#departmentRouting">ساعات العمل</a>
                        <a href="#legacyAutoReplies">الردود الافتراضية</a>
                        <a href="#legacyKeywords">الكلمات المفتاحية</a>
                        <a href="#chatbotInboxActions">الإشعارات</a>
                        <a href="#aiAgentStudio">تكامل القنوات</a>
                    </div>
                </aside>
            </div>
        </section>

        <section class="chatbot-bottom-grid" id="chatbotFlowInfo">
            <article class="panel"><div class="panel-head"><div><h2>حالات التدفق</h2></div></div><div class="flow-status-cards"><span class="danger-state">معطل <b id="pausedFlowCount">0</b></span><span class="ok">منشور <b id="activeFlowCount"><?= (int) ($chatbotOverview['active_flows'] ?? 0) ?></b></span><span class="pending">مسودة <b id="draftFlowCount">0</b></span><span>العقد <b id="flowNodeCount">0</b></span></div></article>
            <article class="panel"><div class="panel-head"><div><h2>معلومات التدفق</h2></div></div><div class="kv"><span>الاسم</span><strong id="currentFlowName">التدفق الرئيسي</strong></div><div class="kv"><span>الحالة</span><strong id="currentFlowStatus">منشط</strong></div><div class="kv"><span>آخر تعديل</span><strong id="currentFlowUpdatedAt">غير متاح</strong></div></article>
        </section>

        <section class="panel wide chatbot-flow-library" id="chatbotFlowLibrary">
            <div class="panel-head">
                <div>
                    <h2>مكتبة مسارات الشات بوت</h2>
                    <span>إدارة النسخ المحفوظة، تحميل مسار سابق، أو إنشاء نسخة آمنة للتعديل قبل النشر.</span>
                </div>
                <div class="button-row">
                    <button class="secondary refresh-flow-library" type="button">تحديث المكتبة</button>
                    <button class="primary duplicate-current-flow" type="button">نسخ المسار الحالي</button>
                </div>
            </div>
            <div class="chatbot-flow-library-list" id="chatbotFlowLibraryList">
                <?php if (empty($chatbotBuilder['flows'])): ?>
                    <article class="empty-flow-library">
                        <b>لا توجد مسارات محفوظة بعد</b>
                        <span>اضغط حفظ المسار لإنشاء أول نسخة قابلة للتحميل والنشر.</span>
                    </article>
                <?php else: ?>
                    <?php foreach (($chatbotBuilder['flows'] ?? []) as $flow): ?>
                        <?php $status = (string) ($flow['status'] ?? 'draft'); ?>
                        <article class="flow-library-item <?= htmlspecialchars($status) ?>" data-flow-id="<?= (int) ($flow['id'] ?? 0) ?>">
                            <div>
                                <strong><?= htmlspecialchars($flow['name'] ?? 'مسار بدون اسم') ?></strong>
                                <span><?= htmlspecialchars($flow['connection_source'] ?? 'both') ?> · الإصدار <?= (int) ($flow['version'] ?? 1) ?></span>
                            </div>
                            <span class="status-pill <?= $status === 'active' ? 'ok' : ($status === 'paused' ? 'danger-state' : 'pending') ?>"><?= $status === 'active' ? 'منشور' : ($status === 'paused' ? 'متوقف' : 'مسودة') ?></span>
                            <small><?= htmlspecialchars($flow['updated_at'] ?? $flow['created_at'] ?? 'غير متاح') ?></small>
                            <div class="button-row">
                                <button class="secondary load-flow-by-id" type="button" data-flow-id="<?= (int) ($flow['id'] ?? 0) ?>">تحميل</button>
                                <button class="secondary duplicate-flow-by-id" type="button" data-flow-id="<?= (int) ($flow['id'] ?? 0) ?>">نسخ</button>
                                <button class="danger delete-flow-by-id" type="button" data-flow-id="<?= (int) ($flow['id'] ?? 0) ?>">حذف</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel wide department-routing" id="departmentRouting">
            <div class="panel-head"><div><h2>توجيه الأقسام</h2><span>تحويل ذكي حسب القسم مع الطوابير والوسوم وإيقاف البوت تلقائياً.</span></div></div>
            <div class="department-grid">
                <?php foreach ([
                    ['المبيعات', 'طابور المبيعات', '#2fbf71', 'sales', 'يسعدنا مساعدتك في المبيعات.'],
                    ['الدعم الفني', 'طابور الدعم الفني', '#2f80ed', 'support', 'من فضلك اختر نوع المشكلة التي تواجهك.'],
                    ['الطلبات والشحن', 'طابور الطلبات', '#8b5cf6', 'orders', 'أرسل رقم الطلب لمتابعة الشحن.'],
                    ['الحسابات والفواتير', 'طابور الحسابات', '#f59e0b', 'billing', 'اختر الخدمة المطلوبة.'],
                    ['الشكاوى', 'طابور الشكاوى', '#ef4444', 'complaints', 'سيتم تحويل الشكوى للقسم المختص.'],
                ] as $dept): ?>
                    <article style="--dept-color: <?= htmlspecialchars($dept[2]) ?>">
                        <b><?= htmlspecialchars($dept[0]) ?></b><span><?= htmlspecialchars($dept[1]) ?></span>
                        <input value="<?= htmlspecialchars($dept[4]) ?>">
                        <input value="09:00-18:00">
                        <input value="<?= htmlspecialchars($dept[3]) ?>">
                        <select><option>مفعل</option><option>معطل</option></select>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel wide ai-agent-studio" id="aiAgentStudio">
            <div class="panel-head"><div><h2>وكيل المبيعات والدعم الذكي</h2><span>وكيل ذكي للرد من قاعدة المعرفة فقط ويعمل مع WhatsApp Cloud API و QR Session</span></div><span class="status-pill ok">معرفة موثقة فقط</span></div>
            <div class="ai-agent-grid">
                <article>
                    <h3>قدرات الوكيل</h3>
                    <div class="capability-list">
                        <span>الرد الذكي من قاعدة المعرفة</span>
                        <span>قراءة المنتجات والأسعار</span>
                        <span>الشحن والدفع والاسترجاع</span>
                        <span>متابعة الطلب برقم الطلب</span>
                        <span>اقتراح منتجات مناسبة</span>
                        <span>تلخيص المحادثة قبل التحويل</span>
                        <span>تصنيف النية والأولوية</span>
                        <span>تحويل تلقائي للقسم المناسب</span>
                    </div>
                </article>
                <article class="ai-policy-card">
                    <h3>قواعد الأمان</h3>
                    <p>لا يرسل AI أي معلومة غير موجودة في قاعدة المعرفة. عند عدم التأكد أو ضعف الثقة يتم تحويل العميل لموظف مختص تلقائياً.</p>
                    <div class="ai-rule-row"><b>مصادر واتساب</b><span>Cloud API + QR Session</span></div>
                    <div class="ai-rule-row"><b>قرار التحويل</b><span>حسب النية والثقة والأولوية</span></div>
                    <div class="ai-rule-row"><b>الردود</b><span>مختصرة، موثقة، بدون اختراع</span></div>
                </article>
                <form class="ai-settings-form compact-form">
                    <h3>إعدادات AI</h3>
                    <label><input type="checkbox" name="enabled" value="1" checked> تفعيل الرد الذكي</label>
                    <select name="tone"><option value="مهني ودود">لهجة مهنية ودودة</option><option value="رسمي">رسمي</option><option value="مختصر">مختصر ومباشر</option></select>
                    <select name="language"><option value="auto">لغة العميل تلقائياً</option><option value="ar">العربية</option><option value="en">English</option></select>
                    <select name="reply_length"><option value="short">رد قصير</option><option value="medium">رد متوسط</option><option value="detailed">رد تفصيلي</option></select>
                    <input name="daily_limit" value="500" placeholder="حد استخدام AI اليومي">
                    <input name="min_confidence" value="70" placeholder="أقل درجة ثقة للرد">
                    <input name="handover_after_minutes" value="10" placeholder="وقت التحويل لموظف بالدقائق">
                    <textarea name="forbidden_topics" placeholder="ممنوعات الرد: خصومات غير معتمدة، وعود شحن غير مؤكدة، بيانات حساسة"></textarea>
                    <button class="primary">حفظ إعدادات AI</button>
                </form>
            </div>
        </section>

        <section class="panel wide ai-knowledge-base" id="aiKnowledgeBase">
            <div class="panel-head"><div><h2>قاعدة معرفة الذكاء الاصطناعي</h2><span>مصدر الحقيقة الوحيد لردود المبيعات والدعم</span></div><button class="secondary" type="button" data-api="/api/chatbot/knowledge-base" id="refreshKnowledgeBase">تحديث القائمة</button></div>
            <div class="knowledge-layout">
                <form class="knowledge-form compact-form">
                    <h3>إضافة معلومة</h3>
                    <select name="category">
                        <option value="faq">الأسئلة الشائعة</option>
                        <option value="policies">سياسات المتجر</option>
                        <option value="products">المنتجات والأسعار</option>
                        <option value="offers">العروض</option>
                        <option value="shipping">الشحن</option>
                        <option value="payment">الدفع</option>
                        <option value="returns">الاسترجاع</option>
                        <option value="orders">حالة الطلبات</option>
                    </select>
                    <input name="title" placeholder="عنوان المعلومة">
                    <textarea name="question" placeholder="السؤال أو الكلمات التي يبحث عنها العميل"></textarea>
                    <textarea name="answer" placeholder="الإجابة المعتمدة التي يمكن للـ AI استخدامها"></textarea>
                    <input name="tags" placeholder="وسوم مثل: سعر، شحن، استرجاع">
                    <button class="primary">حفظ في قاعدة المعرفة</button>
                </form>
                <form class="knowledge-upload-form compact-form" enctype="multipart/form-data">
                    <h3>رفع ملف معرفة</h3>
                    <select name="category"><option value="documents">ملفات PDF / DOCX</option><option value="policies">سياسات</option><option value="products">كتالوج منتجات</option></select>
                    <input name="title" placeholder="اسم الملف داخل قاعدة المعرفة">
                    <textarea name="summary" placeholder="ملخص معتمد لمحتوى الملف. لن يرد AI من الملف إلا بهذا الملخص أو بعد تحويله لمعلومات معرفة."></textarea>
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.txt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
                    <button class="secondary">رفع الملف بأمان</button>
                </form>
                <div class="knowledge-cards" id="knowledgeCards">
                    <article><b>الأسئلة الشائعة</b><span>الأسعار، التوصيل، الدفع، العروض</span></article>
                    <article><b>المنتجات</b><span>الاسم، السعر، المخزون، البدائل</span></article>
                    <article><b>السياسات</b><span>الشحن، الاسترجاع، الضمان، الخصوصية</span></article>
                    <article><b>ملفات</b><span>PDF و DOCX محفوظة خارج public folder</span></article>
                </div>
            </div>
        </section>

        <section class="panel wide chatbot-shell legacy-chatbot-tabs">
            <div class="chatbot-tabs">
                <?php foreach ($chatbotTabs as $index => $tab): ?>
                    <button class="<?= $index === 0 ? 'active' : '' ?>" data-chatbot-tab="<?= $index ?>"><?= htmlspecialchars($tab) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="chatbot-tab active" data-chatbot-panel="0">
                <div class="workspace-grid">
                    <article class="panel"><div class="panel-head"><div><h2>أداء البوت</h2><span>أداء مباشر للردود والمسارات</span></div></div><div class="line-chart compact"><i style="height:40%"></i><i style="height:65%"></i><i style="height:58%"></i><i style="height:80%"></i><i style="height:74%"></i></div></article>
                    <article class="panel"><div class="panel-head"><div><h2>أكثر الكلمات</h2><span>الكلمات الأعلى تكراراً</span></div></div><ul class="insight-list"><li><b>السعر</b><span>0 مرة</span></li><li><b>التوصيل</b><span>0 مرة</span></li><li><b>العروض</b><span>0 مرة</span></li></ul></article>
                </div>
            </div>
            <div class="chatbot-tab" data-chatbot-panel="1">
                <div class="flow-toolbar"><button class="secondary">تكبير</button><button class="secondary">تصغير</button><button class="secondary">ترتيب تلقائي</button><button class="primary chatbot-create-flow">حفظ المسار</button></div>
                <div class="flow-builder">
                    <div class="flow-node message"><b>عقدة رسالة</b><span>نص، صورة، فيديو، أزرار</span></div>
                    <div class="flow-edge"></div>
                    <div class="flow-node condition"><b>عقدة شرط</b><span>إذا / وإلا + كلمات مفتاحية</span></div>
                    <div class="flow-edge"></div>
                    <div class="flow-node ai"><b>عقدة رد ذكي</b><span>السياق + قاعدة المعرفة</span></div>
                    <div class="flow-node handover"><b>تحويل لموظف</b><span>إسناد لموظف دعم</span></div>
                    <aside class="node-settings"><h3>إعدادات العقدة</h3><input placeholder="عنوان العقدة"><textarea placeholder="الرسالة / التوجيه / الشروط"></textarea><button class="primary">تطبيق</button></aside>
                </div>
            </div>
            <div class="chatbot-tab" data-chatbot-panel="2" id="legacyAutoReplies">
                <form class="chatbot-auto-form compact-form">
                    <select name="reply_type"><option value="welcome">رسالة ترحيب</option><option value="away">رسالة عدم التواجد</option><option value="offline">رسالة خارج الدوام</option><option value="first_reply">أول رد</option><option value="keyword">رد حسب كلمة مفتاحية</option><option value="order_status">رد حالة الطلب</option><option value="faq">رد الأسئلة الشائعة</option><option value="smart_ai">رد ذكي</option></select>
                    <input name="name" placeholder="اسم الرد">
                    <textarea name="message" placeholder="نص الرد التلقائي"></textarea>
                    <select name="connection_source"><option value="all_channels">كل القنوات</option><option value="both">واتساب الرسمي + الباركود</option><option value="instagram">إنستجرام</option><option value="facebook">ماسنجر</option><option value="telegram">تيليجرام</option><option value="email">البريد</option><option value="sms">SMS</option><option value="live_chat">دردشة الموقع</option></select>
                    <button class="primary">حفظ الرد</button>
                </form>
            </div>
            <div class="chatbot-tab" data-chatbot-panel="3">
                <div class="ai-lab"><textarea id="aiPrompt" placeholder="اكتب رسالة العميل لاختبار رد AI"></textarea><button class="primary" id="testAiReply">توليد رد AI</button><div class="ai-output" id="aiOutput">سيظهر الرد المقترح هنا.</div></div>
            </div>
            <div class="chatbot-tab" data-chatbot-panel="4" id="legacyKeywords">
                <form class="chatbot-keyword-form compact-form"><input name="keyword" placeholder="مثال: السعر"><select name="match_type"><option value="contains">يحتوي على</option><option value="equals">يساوي تماماً</option><option value="starts_with">يبدأ بـ</option><option value="regex">تعبير Regex</option></select><select name="action_type"><option value="reply">تشغيل رد</option><option value="flow">تشغيل مسار</option><option value="ai">تشغيل الذكاء الاصطناعي</option><option value="handover">إسناد لموظف</option></select><textarea name="reply_text" placeholder="الرد أو التعليمات"></textarea><button class="primary">حفظ الكلمة</button></form>
            </div>
            <div class="chatbot-tab" data-chatbot-panel="5"><div class="launch-grid"><div><b>1</b><span>عند وصول رسالة</span></div><div><b>2</b><span>افحص الكلمات المفتاحية</span></div><div><b>3</b><span>شغل مسار أو رد تلقائي</span></div><div><b>4</b><span>رد ذكي أو تحويل لموظف</span></div></div></div>
            <div class="chatbot-tab" data-chatbot-panel="6" id="chatbotInboxActions"><div class="warning-banner"><b>التحويل لموظف</b><span>عند التحويل لموظف يتم إيقاف البوت مؤقتاً للمحادثة ثم يمكن استئنافه من صندوق المحادثات.</span></div><button class="secondary" type="button" data-scroll-target="#departmentRouting">قواعد الإسناد التلقائي</button></div>
            <div class="chatbot-tab" data-chatbot-panel="7"><div class="upload-grid"><article class="upload-card"><b>الأسئلة الشائعة</b><span>أسئلة وأجوبة</span></article><article class="upload-card"><b>السياسات</b><span>الشحن، الإرجاع، الدفع</span></article><article class="upload-card"><b>المنتجات</b><span>المنتجات والعروض</span></article></div></div>
            <div class="chatbot-tab" data-chatbot-panel="8"><div class="funnel"><span style="width:100%">الرسائل</span><span style="width:80%">الردود التلقائية</span><span style="width:52%">تم حلها</span><span style="width:20%">تحويل لموظف</span></div></div>
            <div class="chatbot-tab" data-chatbot-panel="9">
                <div class="advanced-settings">
                    <article><h3>تشغيل البوت</h3><label>حالة البوت<select><option>مفعل</option><option>متوقف مؤقتاً</option></select></label><label>مصدر الاتصال الافتراضي<select><option>الواجهة الرسمية + الباركود</option><option>الواجهة الرسمية</option><option>جلسة الباركود</option></select></label></article>
                    <article><h3>الذكاء الاصطناعي</h3><label>الرد الذكي<select><option>مفعل</option><option>للاقتراح فقط</option><option>معطل</option></select></label><label>درجة الثقة المطلوبة<input value="70%"></label></article>
                    <article><h3>الحماية من السبام</h3><label>حد الرسائل لكل عميل<input value="5 رسائل / 10 دقائق"></label><label>كلمات الإيقاف<input value="إلغاء, توقف, stop"></label></article>
                    <article><h3>التحويل لموظف</h3><label>مهلة التحويل<input value="30 دقيقة"></label><label>فريق الإسناد<select><option>الدعم</option><option>المبيعات</option><option>الطلبات</option></select></label></article>
                </div>
                <div class="channel-grid mini-channel-grid">
                    <?php foreach ($channelLabels as $key => $label): ?>
                        <article class="channel-card"><b><?= htmlspecialchars($channelIcons[$key] ?? 'CH') ?></b><strong><?= htmlspecialchars($label) ?></strong><span>رد مخصص لهذه القناة</span></article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'templates'): ?>
        <section class="panel-grid">
            <article class="panel wide form-panel">
                <div class="panel-head"><div><h2>إدارة القوالب الذكية</h2><span>إنشاء ومزامنة قوالب Meta</span></div><button type="button" data-api="/api/whatsapp/templates/sync" class="secondary api-post">مزامنة القوالب</button></div>
                <form class="stack ajax-form compact-form" data-endpoint="/api/whatsapp/templates">
                    <input name="name" placeholder="اسم_القالب">
                    <select name="category"><option value="MARKETING">تسويقي</option><option value="UTILITY">خدمي</option><option value="AUTHENTICATION">توثيق</option></select>
                    <input name="language" value="ar">
                    <textarea name="body" placeholder="نص الرسالة مع المتغيرات مثل {{1}}"></textarea>
                    <input name="footer" placeholder="التذييل">
                    <button class="primary">إرسال للمراجعة</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>حالات القوالب</h2><span>مزامنة مباشرة</span></div></div>
                <div class="table-like premium-table"><span>القالب</span><span>الحالة</span><span>النوع</span><span>welcome_offer</span><span class="status-pill ok">معتمد</span><span>تسويقي</span><span>otp_login</span><span class="status-pill pending">قيد المراجعة</span><span>توثيق</span></div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'contacts'): ?>
        <section class="panel wide">
            <div class="panel-head"><div><h2>ذكاء العملاء</h2><span>CRM مع إشارات ذكية وسلوك الشراء</span></div><button class="ghost-btn">إجراءات جماعية</button></div>
            <div class="toolbar"><input placeholder="بحث بالاسم أو الهاتف أو الوسم"><button class="secondary">استيراد CSV / Excel</button><button class="secondary">تصدير</button></div>
            <div class="data-table premium-data"><b>العميل</b><b>الهاتف</b><b>النقاط</b><b>القيمة</b><b>الموافقة</b><b>احتمال التحويل</b><span>سارة</span><span>9665xxxxxxx</span><span class="score">92</span><span>4,800 ر.س</span><span class="status-pill ok">موافق</span><span>78%</span><span>عمر</span><span>9715xxxxxxx</span><span class="score">61</span><span>1,120 ر.س</span><span class="status-pill danger-state">محظور</span><span>24%</span></div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'inbox'): ?>
        <section class="inbox">
            <aside class="panel conversations">
                <div class="panel-head"><div><h2>Inbox موحد</h2><span>واتساب، إنستجرام، ماسنجر، تيليجرام، بريد، SMS، ودردشة الموقع</span></div></div>
                <div class="toolbar compact-toolbar"><input placeholder="بحث ذكي"><select><option>كل القنوات</option><option>واتساب</option><option>إنستجرام</option><option>البريد</option><option>Live Chat</option></select></div>
                <?php foreach (array_slice($omnichannelConversations ?? [], 0, 5) as $conversation): ?>
                    <?php $channel = $conversation['channel'] ?? 'whatsapp_cloud'; ?>
                    <button class="<?= ($conversation['priority'] ?? '') === 'high' ? 'active' : '' ?>"><span class="channel-badge"><?= htmlspecialchars($channelIcons[$channel] ?? 'CH') ?></span><?= htmlspecialchars($conversation['customer_name'] ?? 'عميل') ?><small><?= htmlspecialchars($conversation['last_message'] ?? $conversation['subject'] ?? '') ?></small></button>
                <?php endforeach; ?>
            </aside>
            <article class="panel chat">
                <div class="chat-header"><strong>محادثة موحدة</strong><span class="status-pill ok">الرد من نفس القناة</span></div>
                <div class="bubble inbound"><span class="channel-badge">WA</span> أحتاج حالة طلبي.</div>
                <div class="bubble inbound"><span class="channel-badge">IG</span> هل الكوبون يعمل على نفس المنتج؟</div>
                <div class="bubble outbound"><span class="channel-badge">WA</span> شكراً لك، نراجع الطلب الآن.</div>
                <div class="ai-suggestion"><b>رد مقترح</b><span>طلبك قيد التجهيز وسيتم إرسال رابط التتبع خلال دقائق. تم تصنيف المحادثة: دعم الطلبات.</span></div>
                <textarea placeholder="اكتب الرد، أو أضف ملاحظة داخلية، أو أرفق ملفاً"></textarea>
                <div class="button-row"><button class="secondary">رد سريع</button><button class="secondary">ملاحظة داخلية</button><button class="secondary">إرفاق</button><button class="primary">إرسال الرد</button></div>
                <div class="button-row bot-control-row">
                    <button data-api="/api/chatbot/conversations/1/pause" class="secondary api-post">إيقاف البوت</button>
                    <button data-api="/api/chatbot/conversations/1/resume" class="secondary api-post">استئناف البوت</button>
                    <button data-api="/api/chatbot/conversations/1/transfer" class="secondary api-post">تحويل القسم</button>
                    <button data-api="/api/chatbot/conversations/1/end" class="danger api-post">إنهاء المحادثة</button>
                </div>
            </article>
            <aside class="panel customer-timeline">
                <div class="panel-head"><div><h2>ملف العميل 360</h2><span>سجل العميل عبر كل القنوات</span></div></div>
                <div class="kv"><span>مُسند إلى</span><strong>موظف الدعم</strong></div>
                <div class="kv"><span>AI Score</span><strong><?= (int) ($customer360['ai_score'] ?? 0) ?>%</strong></div>
                <div class="kv"><span>القسم</span><strong>طابور الطلبات</strong></div>
                <div class="kv"><span>حالة البوت</span><strong>نشط / متوقف</strong></div>
                    <div class="kv"><span>المسار الحالي</span><strong>مسار واتساب الافتراضي</strong></div>
                <div class="kv"><span>القنوات</span><strong>WA، IG، Email، Live Chat</strong></div>
                <div class="kv"><span>الوسوم</span><strong>VIP، طلب، مهتم بالعروض</strong></div>
                <div class="ai-inbox-panel">
                    <b>رد ذكي مقترح</b>
                    <p>طلبك قيد التجهيز، وسنرسل رابط التتبع فور تحديث شركة الشحن. إذا رغبت يمكنني تحويلك لفريق الطلبات.</p>
                    <div class="ai-meta-grid">
                        <span><em>نية العميل</em><strong>متابعة طلب</strong></span>
                        <span><em>المشاعر</em><strong>محايد</strong></span>
                        <span><em>درجة العميل</em><strong>58%</strong></span>
                        <span><em>الأولوية</em><strong>عادية</strong></span>
                    </div>
                    <b>ملخص ذكي</b>
                    <p>العميل يسأل عن حالة الطلب ويحتاج رداً من فريق الطلبات إذا لم تكن بيانات التتبع متاحة في قاعدة المعرفة.</p>
                    <b>الإجراء التالي المقترح</b>
                    <p>اطلب رقم الطلب أو حوّل المحادثة إلى طابور الطلبات.</p>
                    <button class="secondary ai-analyze-conversation" type="button" data-conversation-id="1">تحليل المحادثة</button>
                </div>
                <div class="timeline"><span>العميل فتح المحادثة</span><span>البوت أرسل الترحيب</span><span>العميل اختار القسم</span><span>تم التحويل للقسم</span><span>الموظف استلم المحادثة</span></div>
                <textarea placeholder="ملاحظات داخلية"></textarea>
            </aside>
        </section>
    <?php endif; ?>

    <?php if ($page === 'automation'): ?>
        <section class="panel wide revenue-engine-hero">
            <div>
                <span class="premium-pill">Automation Revenue Engine</span>
                <h2>محرك أتمتة الإيرادات للواتساب والشات بوت والمتجر</h2>
                <p>استرجاع السلال المتروكة، متابعة ما بعد الشراء، تذكير الدفع، تحديثات الشحن، طلب التقييم، إعادة تنشيط العملاء، كوبونات تلقائية، وعروض حسب سلوك العميل مع احترام Opt-in وقوالب Meta.</p>
            </div>
            <div class="button-row">
                <button class="primary api-post" data-api="/api/automation-revenue/process">تشغيل الأتمتة الآن</button>
                <button class="secondary api-post" data-api="/api/automation-revenue/templates/abandoned_cart/install">تثبيت مسار السلة</button>
            </div>
        </section>

        <section class="metric-grid revenue-metrics">
            <article class="metric-card lift-card"><span>الإيراد المحقق</span><strong>0 ر.س</strong><small>من مسارات الإيرادات</small></article>
            <article class="metric-card lift-card"><span>Recovered Carts</span><strong>0</strong><small>سلال تم استرجاعها</small></article>
            <article class="metric-card lift-card"><span>Conversion Rate</span><strong>0%</strong><small>نسبة التحويل</small></article>
            <article class="metric-card lift-card"><span>Coupons Used</span><strong>0</strong><small>كوبونات مستخدمة</small></article>
            <article class="metric-card lift-card"><span>Failed Automations</span><strong>0</strong><small>تحتاج مراجعة</small></article>
        </section>

        <section class="panel wide revenue-flow-builder">
            <div class="panel-head"><div><h2>منشئ مسارات العمل جاهز</h2><span>محفز، شرط، تأخير، رسالة واتساب، رد ذكي، كوبون، تحويل لموظف، شرط إيقاف</span></div></div>
            <div class="revenue-flow-grid">
                <?php foreach ([
                    ['abandoned_cart', 'مسار السلة المتروكة', 'بعد 30 دقيقة تذكير، بعد 6 ساعات كوبون، بعد 24 ساعة تحويل لمبيعات.', ['محفز: سلة متروكة', 'تأخير: 30 دقيقة', 'تذكير واتساب', 'تأخير: 6 ساعات', 'كوبون', 'تحويل للمبيعات']],
                    ['post_purchase', 'مسار ما بعد الشراء', 'رسالة شكر، تحديث الشحن، طلب تقييم، واقتراح منتجات مشابهة.', ['محفز: شراء', 'رسالة شكر', 'تحديث الشحن', 'تأخير بعد الاستلام', 'طلب تقييم', 'منتجات مشابهة بالذكاء']],
                    ['payment_reminder', 'مسار تذكير الدفع', 'تذكير بالدفع، رابط دفع، وتحويل للحسابات لو فشل الدفع.', ['محفز: دفع معلق', 'تأخير: 15 دقيقة', 'رابط دفع', 'شرط: فشل', 'تحويل للحسابات']],
                    ['reactivation', 'Customer Reactivation', 'غير نشط 30 يوم، عرض خاص، متابعة الردود، Win-back.', ['Trigger: 30 يوم', 'Condition: Opt-in', 'Coupon BACK15', 'Template Meta', 'AI Reply', 'Handover مبيعات']],
                ] as $flow): ?>
                    <article class="revenue-flow-card">
                        <div><b><?= htmlspecialchars($flow[1]) ?></b><span><?= htmlspecialchars($flow[2]) ?></span></div>
                        <ol>
                            <?php foreach ($flow[3] as $step): ?><li><?= htmlspecialchars($step) ?></li><?php endforeach; ?>
                        </ol>
                        <div class="button-row">
                            <button class="primary api-post" data-api="/api/automation-revenue/templates/<?= htmlspecialchars($flow[0]) ?>/install">تثبيت</button>
                            <button class="secondary" type="button">معاينة</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel wide revenue-compliance">
            <div class="panel-head"><div><h2>قواعد التشغيل الآمن</h2><span>Production Guardrails للحملات والأتمتة</span></div></div>
            <div class="launch-grid">
                <div><b>موافقة العميل</b><span>أي مسار لا يرسل إلا لعميل موافق وغير ملغٍ للاشتراك.</span></div>
                <div><b>Meta Templates</b><span>الرسائل خارج نافذة 24 ساعة تعتمد على قالب Meta معتمد فقط.</span></div>
                <div><b>QR Limits</b><span>عند استخدام QR يتم إرسال دفعات صغيرة مع حدود آمنة وتأخير.</span></div>
                <div><b>Stop Condition</b><span>الإيقاف عند الشراء، الدفع، إلغاء الاشتراك، الشكوى، أو رد الموظف.</span></div>
            </div>
            <form class="automation-trigger-form compact-form">
                <select name="event_type"><option value="cart_abandoned">سلة متروكة</option><option value="order_paid">تم الشراء</option><option value="payment_pending">دفع معلق</option><option value="customer_inactive_30_days">عميل غير نشط 30 يوم</option></select>
                <input name="phone" placeholder="رقم العميل للاختبار">
                <input name="order_id" placeholder="رقم الطلب / السلة">
                <input name="cart_value" placeholder="قيمة السلة">
                <button class="secondary">اختبار Trigger</button>
            </form>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>استوديو الأتمتة</h2><span>شروط، تأخير، أحداث، وسوم</span></div></div>
            <div class="workflow"><div>حدث: موافقة جديدة</div><div>تأخير: 10 دقائق</div><div>شرط: لديه طلب</div><div>إرسال قالب معتمد</div><div>إضافة وسم</div></div>
            <div class="panel-head soft-head"><div><h2>أتمتة متعددة القنوات</h2><span>تصعيد ذكي بين واتساب والبريد والرسائل والدردشة</span></div></div>
            <div class="launch-grid">
                <div><b>1</b><span>إذا لم يرد العميل على واتساب خلال ساعتين، أرسل بريد متابعة.</span></div>
                <div><b>2</b><span>إذا تجاهل البريد، أرسل SMS مختصر مع رابط الطلب.</span></div>
                <div><b>3</b><span>إذا زار الموقع، افتح دردشة مباشرة بنفس سياق المحادثة.</span></div>
                <div><b>4</b><span>إذا ظهرت نية شراء عالية، حوّل المحادثة تلقائياً لفريق المبيعات.</span></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'omnichannel'): ?>
        <section class="panel wide omni-hero">
            <div>
                <span class="premium-pill">Omnichannel Communication Platform</span>
                <h2>مركز تواصل موحد لكل القنوات</h2>
                <p>واتساب الرسمي، جلسات الباركود، إنستجرام، ماسنجر، تيليجرام، البريد، SMS، ودردشة الموقع داخل Inbox وCRM وشات بوت واحد.</p>
            </div>
            <div class="omni-score">
                <strong><?= count($omnichannelOverview['channels'] ?? []) ?></strong>
                <span>قنوات جاهزة</span>
                <small>كل قناة تعمل كـ Module مستقل</small>
            </div>
        </section>

        <section class="metric-grid">
            <article class="metric-card lift-card"><span>محادثات مفتوحة</span><strong><?= (int) ($omnichannelOverview['open_conversations'] ?? 0) ?></strong><small>كل القنوات</small></article>
            <article class="metric-card lift-card"><span>غير مسندة</span><strong><?= (int) ($omnichannelOverview['unassigned'] ?? 0) ?></strong><small>تحتاج توزيع</small></article>
            <article class="metric-card lift-card"><span>حلها الذكاء الاصطناعي</span><strong><?= (int) ($omnichannelOverview['ai_resolved'] ?? 0) ?></strong><small>بدون تدخل بشري</small></article>
            <article class="metric-card lift-card"><span>أول رد</span><strong><?= htmlspecialchars($omnichannelOverview['first_response_time'] ?? '0د') ?></strong><small>متوسط وقت الاستجابة</small></article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>القنوات المتصلة</h2><span>نظام Channel Adapter قابل للتوسع</span></div></div>
            <div class="channel-grid">
                <?php foreach (($omnichannelOverview['channels'] ?? []) as $channel): ?>
                    <article class="channel-card">
                        <b><?= htmlspecialchars($channel['icon'] ?? 'CH') ?></b>
                        <strong><?= htmlspecialchars($channel['arabic'] ?? $channel['label'] ?? '') ?></strong>
                        <span><?= htmlspecialchars($channel['label'] ?? '') ?></span>
                        <em class="status-pill <?= ($channel['status'] ?? '') === 'connected' || ($channel['status'] ?? '') === 'ready' ? 'ok' : 'pending' ?>"><?= ($channel['status'] ?? '') === 'connected' ? 'متصل' : (($channel['status'] ?? '') === 'ready' ? 'جاهز' : 'جاهز للربط') ?></em>
                    </article>
                <?php endforeach; ?>
            </div>
            <form class="omni-connect-form compact-form">
                <select name="channel"><option value="instagram">إنستجرام</option><option value="facebook">ماسنجر فيسبوك</option><option value="telegram">تيليجرام</option><option value="email">البريد الإلكتروني</option><option value="sms">SMS</option><option value="live_chat">دردشة الموقع</option></select>
                <input name="display_name" placeholder="اسم الحساب أو القناة">
                <input name="provider_account_id" placeholder="معرف الحساب الخارجي">
                <input name="access_token" placeholder="Token / Bot Token / SMTP Password">
                <button class="primary">حفظ قناة</button>
            </form>
        </section>

        <section class="omni-inbox-grid">
            <aside class="panel conversations">
                <div class="panel-head"><div><h2>Inbox موحد</h2><span>بحث، فلاتر، إسناد، وملاحظات داخلية</span></div></div>
                <div class="toolbar compact-toolbar"><input placeholder="بحث في كل القنوات"><select><option>كل القنوات</option><option>واتساب</option><option>إنستجرام</option><option>البريد</option></select></div>
                <?php foreach (($omnichannelConversations ?? []) as $conversation): ?>
                    <?php $channel = $conversation['channel'] ?? 'whatsapp_cloud'; ?>
                    <button class="<?= ($conversation['priority'] ?? '') === 'high' ? 'active' : '' ?>">
                        <span class="channel-badge"><?= htmlspecialchars($channelIcons[$channel] ?? 'CH') ?></span>
                        <?= htmlspecialchars($conversation['customer_name'] ?? 'عميل') ?>
                        <small><?= htmlspecialchars($conversation['last_message'] ?? $conversation['subject'] ?? '') ?></small>
                    </button>
                <?php endforeach; ?>
            </aside>
            <article class="panel chat omni-chat">
                <div class="chat-header"><strong>محادثة موحدة</strong><span class="status-pill ok">Smart Routing</span></div>
                <div class="bubble inbound"><span class="channel-badge">WA</span> أحتاج معرفة حالة طلبي.</div>
                <div class="ai-suggestion"><b>رد مقترح</b><span>طلبك قيد المراجعة، وسنرسل لك رابط التتبع فور تحديث الشحن.</span></div>
                <div class="bubble outbound"><span class="channel-badge">EM</span> تم إرسال بريد متابعة عند عدم الرد.</div>
                <textarea placeholder="اكتب الرد وسيتم إرساله من نفس القناة المحددة"></textarea>
                <div class="button-row"><button class="secondary">ملاحظة داخلية</button><button class="secondary">إرفاق ملف</button><button class="secondary">رسالة صوتية</button><button class="primary">إرسال من نفس القناة</button></div>
            </article>
            <aside class="panel customer-timeline">
                <div class="panel-head"><div><h2>Customer 360</h2><span>كل القنوات والطلبات والقيمة</span></div></div>
                <div class="kv"><span>العميل</span><strong><?= htmlspecialchars($customer360['contact']['name'] ?? $customer360['contact']['primary_name'] ?? 'عميل') ?></strong></div>
                <div class="kv"><span>AI Score</span><strong><?= (int) ($customer360['ai_score'] ?? 0) ?>%</strong></div>
                <div class="kv"><span>القيمة العمرية</span><strong><?= number_format((float) ($customer360['lifetime_value'] ?? 0)) ?> ر.س</strong></div>
                <div class="timeline">
                    <?php foreach (($customer360['timeline'] ?? []) as $event): ?><span><?= htmlspecialchars($event) ?></span><?php endforeach; ?>
                </div>
            </aside>
        </section>

        <section class="workspace-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>الشات بوت الموحد</h2><span>نفس المسارات والـ AI وقاعدة المعرفة لكل قناة</span></div></div>
                <div class="launch-grid compact-launch">
                    <div><b>AI</b><span>تصنيف النية والمشاعر واقتراح الردود.</span></div>
                    <div><b>المسار</b><span>تخصيص رسالة مختلفة لكل قناة داخل نفس المسار.</span></div>
                    <div><b>KB</b><span>قاعدة معرفة واحدة للواتساب والدردشة والبريد.</span></div>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>Live Chat Widget</h2><span>ودجت للموقع مع AI وFAQ وجمع Leads</span></div></div>
                <div class="live-widget-preview">
                    <div class="widget-head">دردشة المتجر <span>متصل</span></div>
                    <p>أهلاً بك، كيف نقدر نساعدك؟</p>
                    <button>ابدأ محادثة</button>
                    <button>الانتقال إلى واتساب</button>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>تحليلات القنوات</h2><span>الأداء، الموظفون، رضا العملاء، ودقة AI</span></div></div>
                <div class="funnel"><span style="width:100%">واتساب</span><span style="width:72%">إنستجرام</span><span style="width:56%">البريد</span><span style="width:34%">Live Chat</span></div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'social'): ?>
        <section class="panel-grid">
            <article class="panel wide"><div class="panel-head"><div><h2>إدارة السوشيال ميديا</h2><span>فيسبوك، إنستجرام، تيك توك، سناب شات، X</span></div></div><div class="calendar"><?php for ($i = 1; $i <= 30; $i++): ?><span><?= $i ?></span><?php endfor; ?></div></article>
            <article class="panel form-panel"><input placeholder="العنوان"><textarea placeholder="نص المنشور"></textarea><button class="secondary">توليد نص AI</button><button class="primary">جدولة المنشور</button></article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'analytics'): ?>
        <section class="metric-grid analytics-grid">
            <?php foreach (['معدل التسليم', 'معدل القراءة', 'معدل الرد', 'CTR', 'معدل التحويل', 'الإيراد المحقق'] as $metric): ?>
                <article class="metric-card lift-card"><span><?= $metric ?></span><strong>0%</strong><small>بانتظار بيانات الحملات الفعلية</small></article>
            <?php endforeach; ?>
        </section>
        <section class="workspace-grid">
            <article class="panel wide"><div class="panel-head"><div><h2>تحليل القمع</h2><span>من الوصول إلى التحويل</span></div></div><div class="funnel"><span style="width:100%">تم الإرسال</span><span style="width:82%">تم التسليم</span><span style="width:61%">تمت القراءة</span><span style="width:34%">تم النقر</span><span style="width:18%">تم التحويل</span></div></article>
            <article class="panel"><div class="panel-head"><div><h2>توصيات ذكية</h2><span>توقع الإيرادات</span></div></div><p class="copy">رفع وتيرة الإرسال 15% للحملات الخدمية قد يزيد الإيراد المتوقع بدون التأثير على جودة الرقم.</p></article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'ai-intelligence'): ?>
        <?php
            $forecast = $aiExecutive['sales_forecast'] ?? [];
            $alerts = $aiExecutive['alerts'] ?? [];
            $opportunities = $aiExecutive['growth_opportunities'] ?? [];
            $topProblems = $aiExecutive['top_problems'] ?? [];
            $bestAgents = $aiExecutive['best_agents'] ?? [];
        ?>
        <section class="panel wide ai-bi-hero">
            <div>
                <span class="premium-pill">AI Business Intelligence Layer</span>
                <h2>طبقة ذكاء أعمال فوق العملاء، الواتساب، البوت، الحملات، الموظفين، والإيرادات.</h2>
                <p>كل توصية هنا قابلة للمراجعة قبل التنفيذ. الذكاء الاصطناعي يساعد الفريق ولا يستبدله، ولا يعتمد على أي معلومة خارج بيانات المنصة وقاعدة المعرفة.</p>
            </div>
            <div class="ai-bi-score">
                <small>ثقة التوقع</small>
                <strong><?= (int) ($forecast['winning_campaign_probability'] ?? 0) ?>%</strong>
                <span>جاهز للمراجعة البشرية</span>
            </div>
        </section>

        <section class="metric-grid ai-bi-metrics">
            <article class="metric-card lift-card"><span>عملاء جاهزون للشراء</span><strong><?= (int) ($aiExecutive['hot_leads'] ?? 0) ?></strong><small>Conversion Probability أعلى من 70%</small></article>
            <article class="metric-card lift-card"><span>خطر ترك الخدمة</span><strong><?= (int) ($aiExecutive['churn_risk_customers'] ?? 0) ?></strong><small>Churn Probability مرتفع</small></article>
            <article class="metric-card lift-card"><span>توقع 30 يوم</span><strong><?= number_format((float) ($forecast['next_30_days_revenue'] ?? 0)) ?></strong><small>Revenue Forecast</small></article>
            <article class="metric-card lift-card"><span>حجم المحادثات</span><strong><?= (int) ($aiExecutive['conversation_volume'] ?? 0) ?></strong><small>مصدر تحليل النية والمزاج</small></article>
            <article class="metric-card lift-card"><span>خسارة محتملة</span><strong><?= number_format((float) ($aiExecutive['potential_loss'] ?? 0)) ?></strong><small>من العملاء المعرضين للترك</small></article>
        </section>

        <section class="workspace-grid ai-bi-grid">
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Customer Intelligence</h2><span>Customer AI Profile لكل عميل</span></div></div>
                <div class="ai-feature-list">
                    <span>احتمالية الشراء</span><span>احتمالية ترك الخدمة</span><span>أفضل وقت للتواصل</span><span>Lifetime Value</span><span>Sentiment Score</span><span>Conversion Probability</span>
                </div>
                <button class="primary api-post" data-api="/api/ai-bi/customers/rebuild">إعادة بناء ملفات العملاء</button>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Sales Prediction</h2><span>توقعات المبيعات والمنتجات الأكثر طلباً</span></div></div>
                <div class="predictive-chart"><i style="height:35%"></i><i style="height:48%"></i><i style="height:62%"></i><i style="height:78%"></i><i style="height:90%"></i></div>
                <div class="kv"><span>توقع 7 أيام</span><strong><?= number_format((float) ($forecast['next_7_days_revenue'] ?? 0)) ?></strong></div>
                <div class="kv"><span>عملاء محتملون</span><strong><?= (int) ($forecast['likely_buyers'] ?? 0) ?></strong></div>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Campaign Optimization</h2><span>تحسين الحملات بدون تنفيذ تلقائي غير مراجع</span></div></div>
                <ul class="insight-list">
                    <li><b>وقت الإرسال</b><span>اقتراح أفضل نافذة حسب نشاط العملاء.</span></li>
                    <li><b>Segment</b><span>استهداف أصحاب Opt-in واحتمالية شراء مرتفعة.</span></li>
                    <li><b>حماية الجودة</b><span>اقتراح إيقاف الحملات الضعيفة قبل التأثير على الرقم.</span></li>
                </ul>
                <button class="secondary api-post" data-api="/api/ai-bi/campaign-optimization">توليد توصيات الحملة</button>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Conversation Analysis</h2><span>تحليل المحادثات والنية والمزاج</span></div></div>
                <div class="ai-feature-list">
                    <span>تحليل المشاعر</span><span>اكتشاف النية</span><span>اكتشاف الشكاوى</span><span>خطر التصعيد</span><span>جودة العميل المحتمل</span><span>القسم المقترح</span>
                </div>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Agent Assistant</h2><span>مساعد داخل Inbox للموظف</span></div></div>
                <p class="copy">يقترح ردوداً، يلخص المحادثة، يحدد نية العميل، يوصي بمنتجات أو تحويل قسم، ويترك القرار النهائي للموظف.</p>
                <div class="ai-agent-mini"><span>AI Suggested Reply</span><span>AI Summary</span><span>Next Best Action</span></div>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>Smart Alerts</h2><span>تنبيهات قابلة للتنفيذ</span></div></div>
                <ul class="smart-alert-list">
                    <?php foreach ($alerts as $alert): ?>
                        <li class="<?= htmlspecialchars($alert['severity'] ?? 'info') ?>"><b><?= htmlspecialchars($alert['type'] ?? 'alert') ?></b><span><?= htmlspecialchars($alert['message'] ?? '') ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>AI Knowledge Learning</h2><span>تعلم من المحادثات والأسئلة المتكررة</span></div></div>
                <p class="copy">يستخرج الأسئلة المتكررة وردود الموظفين ويقترح إضافتها إلى قاعدة المعرفة بعد المراجعة.</p>
                <button class="secondary api-post" data-api="/api/ai-bi/knowledge/learn">تحليل الأسئلة المتكررة</button>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>ذكاء الأتمتة</h2><span>توليد مسارات وحملات قابلة للمراجعة</span></div></div>
                <p class="copy">يقترح مسارات استرجاع العملاء، المبيعات، الشكاوى، وWin-back بدون إطلاق تلقائي إلا بعد موافقة المستخدم.</p>
                <button class="primary api-post" data-api="/api/ai-bi/automation/ideas">اقتراح مسارات ذكية</button>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>Analytics 2.0</h2><span>Predictive / Funnel / Journey / Cohort</span></div></div>
                <div class="funnel ai-funnel"><span style="width:100%">Sent</span><span style="width:82%">Delivered</span><span style="width:61%">Read</span><span style="width:24%">Clicked</span><span style="width:9%">Converted</span></div>
            </article>
            <article class="panel ai-bi-card">
                <div class="panel-head"><div><h2>Executive View</h2><span>قرارات إدارية مختصرة</span></div></div>
                <ul class="insight-list">
                    <?php foreach (array_slice($topProblems, 0, 2) as $problem): ?><li><b>مشكلة</b><span><?= htmlspecialchars($problem) ?></span></li><?php endforeach; ?>
                    <?php foreach (array_slice($opportunities, 0, 2) as $opportunity): ?><li><b>فرصة</b><span><?= htmlspecialchars($opportunity) ?></span></li><?php endforeach; ?>
                    <?php foreach (array_slice($bestAgents, 0, 2) as $agent): ?><li><b><?= htmlspecialchars($agent['name'] ?? 'Agent') ?></b><span>Score <?= (int) ($agent['score'] ?? 0) ?>%</span></li><?php endforeach; ?>
                </ul>
            </article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>Architecture الجاهزة للإنتاج</h2><span>AI Services Layer / Queue Workers / Embeddings Store / Vector Search / Knowledge Indexing</span></div></div>
            <div class="launch-grid">
                <div><b>AI Services Layer</b><span>خدمات مستقلة للتحليل والتوقعات والتوصيات بدون تغيير منطق الواتساب الحالي.</span></div>
                <div><b>AI Queue Workers</b><span>جدولة مهام التحليل الثقيلة في ai_queue_jobs مع الأولوية والمحاولات.</span></div>
                <div><b>Embeddings Store</b><span>جدول ai_embeddings لفهرسة المعرفة والمحادثات والمنتجات مع عزل store_id.</span></div>
                <div><b>Reviewable Actions</b><span>كل توصية تحفظ بحالة pending_review قبل أي تطبيق.</span></div>
                <div><b>Data Protection</b><span>لا يتم كشف أسرار أو تدريب على بيانات حساسة، وكل شيء محكوم بـ RBAC وAudit Logs.</span></div>
                <div><b>البحث المتجهي جاهز</b><span>دعم FULLTEXT حالياً وقابل للترقية إلى قاعدة متجهات خارجية.</span></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'setup-checklist'): ?>
        <?php
            $launchItems = $launchReadiness['items'] ?? [];
            $launchSections = $launchReadiness['sections'] ?? [];
            $launchOperations = $launchReadiness['operations'] ?? [];
            $launchEnv = $launchReadiness['environment'] ?? [];
            $launchAlerts = $launchReadiness['alerts'] ?? [];
            $launchBlocking = $launchReadiness['blocking'] ?? [];
            $launchScore = (int) ($launchReadiness['score'] ?? 0);
            $launchStatus = (string) ($launchReadiness['status'] ?? 'غير جاهز');
            $launchIsReady = $launchStatus === 'جاهز للإطلاق' && $launchBlocking === [];
        ?>
        <section class="panel wide launch-readiness-hero">
            <div class="panel-head">
                <div>
                    <h2>جاهزية الإطلاق</h2>
                    <span>فحص موحد للغة، RTL، قاعدة البيانات، واتساب، الأمان، الصلاحيات، الفواتير، والسجلات.</span>
                </div>
                <span class="status-pill <?= $launchIsReady ? 'ok' : ($launchScore >= 41 ? 'pending' : 'danger-state') ?>"><?= htmlspecialchars($launchStatus) ?></span>
            </div>
            <div class="readiness-score-card">
                <strong><?= $launchScore ?>%</strong>
                <span>درجة الجاهزية الحالية</span>
                <div class="readiness-meter"><i style="width: <?= max(0, min(100, $launchScore)) ?>%"></i></div>
                <small>0-40 غير جاهز، 41-70 يحتاج مراجعة، 71-100 جاهز للإطلاق بشرط عدم وجود موانع حرجة.</small>
            </div>
        </section>

        <?php if ($launchAlerts !== []): ?>
            <section class="panel wide launch-alert-panel">
                <div class="panel-head"><div><h2>موانع وتنبيهات الإطلاق</h2><span>هذه البنود تمنع الإطلاق أو تحتاج ضبطاً قبل الإنتاج.</span></div></div>
                <div class="launch-alert-list">
                    <?php foreach ($launchAlerts as $alert): ?>
                        <article class="<?= htmlspecialchars($alert['type'] ?? 'warning') ?>">
                            <b><?= htmlspecialchars($alert['title'] ?? 'تنبيه') ?></b>
                            <span><?= htmlspecialchars($alert['message'] ?? '') ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="panel wide">
            <div class="panel-head"><div><h2>فحص المتطلبات الحرجة</h2><span>هذه البنود يجب أن تكون خضراء قبل أي إطلاق فعلي.</span></div></div>
            <div class="checklist launch-checklist">
                <?php foreach ($launchItems as $item): ?>
                    <div class="<?= !empty($item['ready']) ? 'ready' : '' ?>">
                        <b><?= !empty($item['ready']) ? '✓' : '!' ?></b>
                        <span><?= htmlspecialchars($item['label'] ?? '') ?><small><?= htmlspecialchars($item['description'] ?? '') ?></small></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="workspace-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>اكتمال الأقسام</h2><span>مراجعة كل صفحات مركز التسويق المطلوبة.</span></div></div>
                <div class="launch-grid compact-launch">
                    <?php foreach ($launchSections as $section): ?>
                        <div class="<?= !empty($section['ready']) ? 'ready-box' : 'review-box' ?>">
                            <b><?= htmlspecialchars($section['label'] ?? '') ?></b>
                            <span><?= htmlspecialchars($section['state'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>اختبار المسارات</h2><span>قائمة تشغيل قبل التسليم النهائي.</span></div></div>
                <div class="launch-grid compact-launch">
                    <?php foreach ($launchOperations as $operation): ?>
                        <div class="<?= !empty($operation['ready']) ? 'ready-box' : 'review-box' ?>">
                            <b><?= htmlspecialchars($operation['label'] ?? '') ?></b>
                            <span><?= htmlspecialchars($operation['description'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>متغيرات البيئة المطلوبة</h2><span>لا يتم عرض أي أسرار؛ يظهر فقط هل المتغير موجود أم لا.</span></div><a class="ghost-btn" href="<?= htmlspecialchars($appUrl) ?>/api/launch-readiness">API الجاهزية</a></div>
            <div class="launch-env-command">
                <b>أمر تجهيز بيئة الإنتاج</b>
                <code>php bin/generate-production-env.php https://your-domain.com</code>
                <code>php bin/verify-production-env.php .env</code>
                <code>php bin/database-foundation-check.php</code>
                <code>php bin/production-preflight.php .env</code>
                <small>يولّد ملف .env.production.generated بأسرار عشوائية أولية، ثم يجب ملء قيم Meta وقاعدة البيانات والتخزين والمراقبة الحقيقية، وبعدها يشغّل Preflight بوابة المرحلة الأولى.</small>
            </div>
            <div class="env-grid">
                <?php foreach ($launchEnv as $envItem): ?>
                    <span class="<?= !empty($envItem['ready']) ? 'ready' : (!empty($envItem['critical']) ? 'critical' : 'warning') ?>">
                        <b><?= htmlspecialchars($envItem['key'] ?? '') ?></b>
                        <em><?= !empty($envItem['ready']) ? 'موجود' : (!empty($envItem['required']) ? 'ناقص' : 'اختياري') ?></em>
                        <?php if (empty($envItem['ready'])): ?>
                            <small><?= htmlspecialchars($envItem['message'] ?? '') ?></small>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>جاهزية الإنتاج</h2><span>جاهزية الربط الرسمي</span></div></div>
            <div class="checklist">
                <?php foreach ($checklist as $label => $ready): ?>
                    <div class="<?= $ready ? 'ready' : '' ?>"><b><?= $ready ? '✓' : '!' ?></b><span><?= htmlspecialchars($checklistLabels[$label] ?? $label) ?></span></div>
                <?php endforeach; ?>
            </div>
            <div class="button-row"><button data-api="/api/meta/sync-assets" class="secondary api-post">مزامنة أصول Meta</button><button data-api="/api/whatsapp/templates/sync" class="secondary api-post">مزامنة القوالب</button><button data-api="/api/campaigns/queue/process" class="primary api-post">تشغيل قائمة الإرسال</button></div>
        </section>
        <section class="panel wide">
            <div class="panel-head"><div><h2>خطة الإطلاق الرسمي</h2><span>Meta وWhatsApp Cloud API</span></div></div>
            <div class="launch-grid">
                <div><b>1</b><span>أنشئ تطبيق Meta Developer من نوع Business وأضف WhatsApp وFacebook Login وWebhooks.</span></div>
                <div><b>2</b><span>فعّل Business Manager موثقاً واربط صفحة Facebook وحساب Instagram Business عند الحاجة.</span></div>
                <div><b>3</b><span>فعّل الواجهة السحابية لواتساب واحفظ معرف رقم الهاتف ومعرف حساب واتساب للأعمال داخل الربط.</span></div>
                <div><b>4</b><span>استخدم Callback URL: <?= htmlspecialchars($appUrl) ?>/api/webhooks/whatsapp مع Verify Token.</span></div>
                <div><b>5</b><span>أنشئ قوالب Marketing وUtility ثم نفّذ مزامنة القوالب.</span></div>
                <div><b>6</b><span>اختبر الإرسال واستقبال الردود وحالات sent / delivered / read / failed.</span></div>
                <div><b>7</b><span>لا تطلق حملات إلا لعملاء لديهم Opt-in محفوظ.</span></div>
                <div><b>8</b><span>شغّل Queue كل دقيقة: php bin/process-campaign-queue.php.</span></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'platform-roadmap'): ?>
        <?php
            $roadmapCurrent = $platformRoadmap['current_phase'] ?? [];
            $roadmapPhases = $platformRoadmap['phases'] ?? [];
            $roadmapCompleted = $platformRoadmap['completed_sections'] ?? [];
            $roadmapInDevelopment = $platformRoadmap['in_development_sections'] ?? [];
            $roadmapUpcoming = $platformRoadmap['upcoming_sections'] ?? [];
            $roadmapIssues = $platformRoadmap['open_issues'] ?? [];
            $roadmapTests = $platformRoadmap['phase_tests'] ?? [];
            $roadmapProgress = (int) ($platformRoadmap['progress'] ?? 0);
            $statusClass = static function (string $status): string {
                return match ($status) {
                    'production_ready', 'completed' => 'ok',
                    'testing' => 'pending',
                    'in_progress' => 'active',
                    default => 'muted',
                };
            };
            $devExecution = $developmentExecution ?? [];
            $devStats = $devExecution['stats'] ?? [];
            $devConfig = $devExecution['config'] ?? [];
            $devOpenFindings = array_values(array_filter($devExecution['findings'] ?? [], static fn (array $finding): bool => ($finding['status'] ?? 'complete') !== 'complete'));
            $devFindings = array_slice($devOpenFindings, 0, 12);
            $devTasks = array_slice($devExecution['tasks'] ?? [], 0, 12);
            $devLogs = array_slice($devExecution['logs'] ?? [], 0, 10);
            $devRecommendations = array_slice($devExecution['recommendations'] ?? [], 0, 8);
            $taskStatusClass = static function (string $status): string {
                return match ($status) {
                    'completed', 'fixed', 'complete' => 'ok',
                    'running', 'fixing' => 'active',
                    'failed', 'critical', 'missing' => 'critical',
                    'manual_required', 'warning' => 'pending',
                    default => 'muted',
                };
            };
        ?>
        <section class="panel wide roadmap-hero">
            <div>
                <span class="premium-pill">AI Development & Completion Center</span>
                <h2>تطوير تدريجي منظم يمنع الانتقال للمرحلة التالية قبل اكتمال الحالية بجاهزية إنتاجية.</h2>
                <p>كل مرحلة تحتوي على الهدف، الصفحات، APIs، النماذج، مراجعة الأمان، اختبارات التشغيل، وقائمة إطلاق مستقلة قابلة للمراجعة.</p>
            </div>
            <div class="roadmap-score">
                <strong><?= $roadmapProgress ?>%</strong>
                <span>نسبة الإنجاز الإجمالية</span>
                <div class="readiness-meter"><i style="width: <?= max(0, min(100, $roadmapProgress)) ?>%"></i></div>
                <small><?= htmlspecialchars($platformRoadmap['launch_status'] ?? 'غير جاهز') ?></small>
            </div>
        </section>

        <section class="panel wide dev-execution-center">
            <div class="panel-head">
                <div>
                    <h2>مركز التنفيذ والإصلاح الذكي</h2>
                    <span>يفحص ملفات المشروع، المسارات، قاعدة البيانات، البيئة، الأمان، الاختبارات، والـ Workers ثم يحول النواقص إلى مهام قابلة للتنفيذ.</span>
                </div>
                <div class="dev-exec-toolbar">
                    <a class="ghost-btn" href="<?= htmlspecialchars($appUrl) ?>/api/development-execution">عرض API</a>
                    <button class="secondary" type="button" onclick="window.location.reload()">تحديث الفحص</button>
                    <button class="primary dev-exec-run-all" type="button">تشغيل الإصلاحات الآمنة</button>
                </div>
            </div>

            <div class="dev-exec-status-grid">
                <article><span>النواقص المكتشفة</span><strong><?= (int) ($devStats['missing'] ?? 0) ?></strong><small>من <?= (int) ($devStats['total_findings'] ?? 0) ?> عنصر مفحوص</small></article>
                <article><span>حرجة</span><strong><?= (int) ($devStats['critical'] ?? 0) ?></strong><small>تمنع الجاهزية إن لم تعالج</small></article>
                <article><span>مهام معلقة</span><strong><?= (int) ($devStats['pending_tasks'] ?? 0) ?></strong><small>جاهزة للتنفيذ الآمن</small></article>
                <article><span>تم إصلاحها</span><strong><?= (int) ($devStats['completed_tasks'] ?? 0) ?></strong><small>آخر تشغيلات ناجحة</small></article>
                <article><span>وضع الكتابة</span><strong><?= !empty($devConfig['write_enabled']) ? 'مفعل' : 'مغلق' ?></strong><small><?= !empty($devConfig['write_enabled']) ? 'يسمح بمهام محددة مسبقاً' : 'فحص فقط لحماية الإنتاج' ?></small></article>
                <article><span>أوامر النظام</span><strong><?= !empty($devConfig['shell_enabled']) ? 'مفعلة' : 'مغلقة' ?></strong><small>تظل مغلقة افتراضياً في الإنتاج</small></article>
            </div>

            <div class="dev-exec-grid">
                <article class="dev-exec-card">
                    <div class="dev-exec-card-head">
                        <b>قائمة النواقص</b>
                        <span><?= (int) ($devStats['fixable'] ?? 0) ?> قابلة للإصلاح</span>
                    </div>
                    <div class="dev-exec-findings">
                        <?php if ($devFindings === []): ?>
                            <div class="dev-exec-empty">لا توجد نواقص حالياً. شغّل الفحص بعد أي تعديل كبير للتأكد.</div>
                        <?php endif; ?>
                        <?php foreach ($devFindings as $finding): ?>
                            <div class="dev-exec-finding <?= htmlspecialchars($taskStatusClass((string) ($finding['severity'] ?? $finding['status'] ?? 'muted'))) ?>">
                                <div>
                                    <b><?= htmlspecialchars($finding['title'] ?? '') ?></b>
                                    <span><?= htmlspecialchars($finding['recommendation'] ?? $finding['description'] ?? '') ?></span>
                                    <small><?= htmlspecialchars($finding['category'] ?? '') ?> · <?= htmlspecialchars($finding['status'] ?? '') ?></small>
                                </div>
                                <?php if (!empty($finding['fixable']) && !empty($finding['task_key'])): ?>
                                    <button class="ghost-btn dev-exec-run" type="button" data-task-key="<?= htmlspecialchars((string) $finding['task_key']) ?>">إصلاح</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="dev-exec-card">
                    <div class="dev-exec-card-head">
                        <b>Execution Queue</b>
                        <span><?= (int) ($devStats['running_tasks'] ?? 0) ?> قيد التشغيل</span>
                    </div>
                    <div class="dev-exec-task-queue">
                        <?php if ($devTasks === []): ?>
                            <div class="dev-exec-empty">لا توجد مهام تنفيذ مسجلة بعد. سيتم إنشاؤها تلقائياً من نتائج الفحص.</div>
                        <?php endif; ?>
                        <?php foreach ($devTasks as $task): ?>
                            <div class="dev-exec-task <?= htmlspecialchars($taskStatusClass((string) ($task['status'] ?? 'pending'))) ?>">
                                <div>
                                    <b><?= htmlspecialchars($task['title'] ?? $task['task_key'] ?? '') ?></b>
                                    <span><?= htmlspecialchars($task['description'] ?? '') ?></span>
                                    <small><?= htmlspecialchars($task['category'] ?? '') ?> · <?= htmlspecialchars($task['status'] ?? '') ?> · أولوية <?= htmlspecialchars((string) ($task['priority'] ?? 'normal')) ?></small>
                                </div>
                                <?php if (!empty($task['task_key']) && in_array(($task['status'] ?? 'pending'), ['pending', 'failed', 'manual_required'], true)): ?>
                                    <button class="ghost-btn dev-exec-run" type="button" data-task-key="<?= htmlspecialchars((string) $task['task_key']) ?>">تشغيل</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="dev-exec-grid compact">
                <article class="dev-exec-card">
                    <div class="dev-exec-card-head"><b>سجل الإصلاح</b><span>آخر الأحداث</span></div>
                    <div class="dev-exec-logs">
                        <?php if ($devLogs === []): ?><div class="dev-exec-empty">لا توجد سجلات تنفيذ بعد.</div><?php endif; ?>
                        <?php foreach ($devLogs as $log): ?>
                            <span><b><?= htmlspecialchars($log['created_at'] ?? '') ?></b><?= htmlspecialchars(($log['task_key'] ?? 'scan') . ' - ' . ($log['message'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="dev-exec-card">
                    <div class="dev-exec-card-head"><b>توصيات AI</b><span>قرارات تحتاج مراجعة</span></div>
                    <div class="dev-exec-recommendations">
                        <?php if ($devRecommendations === []): ?><div class="dev-exec-empty">لا توجد توصيات جديدة.</div><?php endif; ?>
                        <?php foreach ($devRecommendations as $recommendation): ?>
                            <span><?= htmlspecialchars((string) $recommendation) ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </section>

        <section class="metric-grid roadmap-metrics">
            <article class="metric-card lift-card"><span>المرحلة الحالية</span><strong><?= (int) ($roadmapCurrent['number'] ?? 1) ?></strong><small><?= htmlspecialchars($roadmapCurrent['title_ar'] ?? 'غير محددة') ?></small></article>
            <article class="metric-card lift-card"><span>حالة المرحلة</span><strong><?= htmlspecialchars($roadmapCurrent['status_label'] ?? 'غير جاهز') ?></strong><small><?= htmlspecialchars($roadmapCurrent['gate_message'] ?? 'قابلة للعمل والمراجعة') ?></small></article>
            <article class="metric-card lift-card"><span>أقسام مكتملة</span><strong><?= count($roadmapCompleted) ?></strong><small>مكتملة أو جاهزة للإنتاج</small></article>
            <article class="metric-card lift-card"><span>قيد التطوير</span><strong><?= count($roadmapInDevelopment) ?></strong><small>In Progress / Testing</small></article>
            <article class="metric-card lift-card"><span>المراحل القادمة</span><strong><?= count($roadmapUpcoming) ?></strong><small>مقفلة حتى اكتمال الحالية</small></article>
        </section>

        <section class="workspace-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>المرحلة الحالية</h2><span><?= htmlspecialchars($roadmapCurrent['title'] ?? '') ?></span></div><span class="status-pill <?= htmlspecialchars($statusClass((string) ($roadmapCurrent['status'] ?? 'not_started'))) ?>"><?= htmlspecialchars($roadmapCurrent['status_label'] ?? '') ?></span></div>
                <p class="copy"><?= htmlspecialchars($roadmapCurrent['objective'] ?? '') ?></p>
                <div class="readiness-score-card roadmap-current-score">
                    <strong><?= (int) ($roadmapCurrent['launch_score'] ?? 0) ?>%</strong>
                    <span>Launch Score للمرحلة</span>
                    <div class="readiness-meter"><i style="width: <?= max(0, min(100, (int) ($roadmapCurrent['launch_score'] ?? 0))) ?>%"></i></div>
                </div>
                <div class="roadmap-list-grid">
                    <div><b>الصفحات المطلوبة</b><?php foreach (($roadmapCurrent['required_pages'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                    <div><b>APIs المطلوبة</b><?php foreach (($roadmapCurrent['required_apis'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                    <div><b>Database Models</b><?php foreach (($roadmapCurrent['required_models'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                    <div><b>UI/UX</b><?php foreach (($roadmapCurrent['ui_ux'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>مشاكل المرحلة واختباراتها</h2><span>لا يتم الانتقال قبل إغلاق الموانع الحرجة.</span></div></div>
                <div class="roadmap-issue-list">
                    <?php if ($roadmapIssues === []): ?>
                        <article class="ok"><b>لا توجد مشاكل مفتوحة</b><span>المرحلة جاهزة للمراجعة النهائية.</span></article>
                    <?php endif; ?>
                    <?php foreach ($roadmapIssues as $issue): ?>
                        <article class="<?= htmlspecialchars($issue['severity'] ?? 'warning') ?>">
                            <b><?= htmlspecialchars($issue['label'] ?? '') ?></b>
                            <span><?= htmlspecialchars($issue['message'] ?? '') ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="roadmap-test-list">
                    <h3>اختبارات المرحلة</h3>
                    <?php foreach ($roadmapTests as $test): ?>
                        <span><?= htmlspecialchars($test) ?></span>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>قواعد العمل المرحلي</h2><span>نظام يمنع التوسع العشوائي ويحافظ على Production Ready لكل قسم.</span></div><a class="ghost-btn" href="<?= htmlspecialchars($appUrl) ?>/api/platform-roadmap">API الخارطة</a></div>
            <div class="launch-grid roadmap-rules">
                <?php foreach (($platformRoadmap['rules'] ?? []) as $rule): ?>
                    <div><b>✓</b><span><?= htmlspecialchars($rule) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>المراحل الاثنتا عشرة</h2><span>كل مرحلة مستقلة، قابلة للاختبار، ولها Launch Score خاص.</span></div></div>
            <div class="roadmap-phase-grid">
                <?php foreach ($roadmapPhases as $phase): ?>
                    <article class="<?= !empty($phase['locked']) ? 'locked' : '' ?>">
                        <div class="roadmap-phase-head">
                            <span><?= (int) ($phase['number'] ?? 0) ?></span>
                            <div>
                                <b><?= htmlspecialchars($phase['title_ar'] ?? '') ?></b>
                                <small><?= htmlspecialchars($phase['title'] ?? '') ?></small>
                            </div>
                            <em class="<?= htmlspecialchars($statusClass((string) ($phase['status'] ?? 'not_started'))) ?>"><?= htmlspecialchars($phase['status_label'] ?? '') ?></em>
                        </div>
                        <p><?= htmlspecialchars($phase['objective'] ?? '') ?></p>
                        <div class="readiness-meter"><i style="width: <?= max(0, min(100, (int) ($phase['progress'] ?? 0))) ?>%"></i></div>
                        <div class="roadmap-checks">
                            <?php foreach (($phase['checks'] ?? []) as $check): ?>
                                <span class="<?= !empty($check['ready']) ? 'ready' : (!empty($check['critical']) ? 'critical' : 'warning') ?>">
                                    <?= !empty($check['ready']) ? '✓' : '!' ?> <?= htmlspecialchars($check['label'] ?? '') ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <details>
                            <summary>تفاصيل المرحلة</summary>
                            <div class="roadmap-detail-columns">
                                <div><b>Security Review</b><?php foreach (($phase['security_checklist'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                                <div><b>Testing Checklist</b><?php foreach (($phase['testing_checklist'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                                <div><b>Production Checklist</b><?php foreach (($phase['production_checklist'] ?? []) as $entry): ?><span><?= htmlspecialchars($entry) ?></span><?php endforeach; ?></div>
                            </div>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'marketplace'): ?>
        <?php
            $marketStats = $marketplaceOverview['stats'] ?? ['apps' => 0, 'installed' => 0, 'api_keys' => 0, 'webhooks' => 0];
            $featuredApps = $marketplaceOverview['featured_apps'] ?? [];
            $installedApps = $marketplaceOverview['installed'] ?? [];
            $developer = $marketplaceOverview['developer'] ?? [];
            $pluginSystem = $developer['plugin_system'] ?? [];
        ?>
        <section class="panel wide marketplace-hero">
            <div>
                <span class="premium-pill">متجر التطبيقات والتكاملات</span>
                <h2>حوّل مركز التسويق إلى منصة قابلة للتوسع عبر التطبيقات والإضافات والتكاملات.</h2>
                <p>كل تطبيق يعمل بصلاحيات محددة، ونطاقات OAuth، وحدود طلبات، وWebhooks موقعة، ومراجعة إدارة المنصة قبل النشر العام.</p>
            </div>
            <div class="marketplace-actions">
                <button class="primary marketplace-api-key" type="button">إنشاء API Key</button>
                <button class="secondary marketplace-oauth-app" type="button">إنشاء OAuth App</button>
            </div>
        </section>

        <section class="metric-grid">
            <article class="metric-card lift-card"><span>تطبيقات منشورة</span><strong><?= (int) ($marketStats['apps'] ?? 0) ?></strong><small>تطبيقات / تكاملات / حزم ذكاء</small></article>
            <article class="metric-card lift-card"><span>مثبتة في المتجر</span><strong><?= (int) ($marketStats['installed'] ?? 0) ?></strong><small>تعمل بصلاحيات معزولة</small></article>
            <article class="metric-card lift-card"><span>API Keys</span><strong><?= (int) ($marketStats['api_keys'] ?? 0) ?></strong><small>مفاتيح مطورين نشطة</small></article>
            <article class="metric-card lift-card"><span>Webhooks</span><strong><?= (int) ($marketStats['webhooks'] ?? 0) ?></strong><small>نقاط استقبال طرف ثالث</small></article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>متجر التطبيقات</h2><span>تطبيقات مميزة جاهزة للتثبيت</span></div><button class="ghost-btn" type="button">عرض الكل</button></div>
            <div class="app-store-grid">
                <?php foreach ($featuredApps as $app): ?>
                    <?php $permissions = json_decode((string) ($app['permissions_json'] ?? '[]'), true) ?: []; ?>
                    <article class="market-app-card">
                        <div class="app-card-top"><span class="app-icon"><?= htmlspecialchars($app['icon'] ?? 'AP') ?></span><div><b><?= htmlspecialchars($app['name'] ?? 'تطبيق') ?></b><small><?= htmlspecialchars($app['category'] ?? 'تكامل') ?></small></div></div>
                        <p><?= htmlspecialchars($app['short_description'] ?? '') ?></p>
                        <div class="app-meta"><span>★ <?= htmlspecialchars((string) ($app['rating'] ?? '4.8')) ?></span><span><?= htmlspecialchars($labelText($app['pricing_model'] ?? 'free')) ?></span><span><?= htmlspecialchars($labelText($app['app_type'] ?? 'app')) ?></span></div>
                        <div class="scope-row">
                            <?php foreach (array_slice($permissions, 0, 3) as $scope): ?><em><?= htmlspecialchars($scope) ?></em><?php endforeach; ?>
                        </div>
                        <button class="primary api-post" data-api="/api/marketplace/apps/<?= (int) ($app['id'] ?? 0) ?>/install">تثبيت</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="workspace-grid marketplace-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>متجر القوالب</h2><span>قوالب جاهزة للتجارة والردود والتحليلات</span></div></div>
                <div class="market-list">
                    <span>مسارات الشات بوت</span><span>قوالب الحملات</span><span>أتمتة العملاء</span><span>تخطيطات المحادثات</span><span>لوحات التحليلات</span>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>محرك الثيمات</h2><span>متجر الثيمات وثيمات الهوية البيضاء</span></div></div>
                <div class="theme-preview-row"><i style="--c:#243763"></i><i style="--c:#25d366"></i><i style="--c:#7c3aed"></i><i style="--c:#0ea5e9"></i></div>
                <p class="copy">يدعم تخصيص الهوية، الألوان، الشعار، الدومين، وثيمات قابلة للمراجعة قبل النشر.</p>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>متجر الذكاء الاصطناعي</h2><span>شخصيات / وكلاء / حزم معرفة</span></div></div>
                <div class="market-list">
                    <span>وكلاء مبيعات ذكيون</span><span>وكلاء دعم ذكيون</span><span>حزم معرفة ذكية</span><span>إجراءات ذكاء مخصصة</span>
                </div>
            </article>
        </section>

        <section class="workspace-grid marketplace-grid">
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>بوابة المطورين</h2><span>مفاتيح API وتطبيقات OAuth وحزم SDK</span></div></div>
                <form class="developer-api-key-form compact-form">
                    <input name="name" placeholder="اسم المفتاح">
                    <input name="scopes" value="read:contacts,write:webhooks,read:campaigns">
                    <input name="rate_limit_per_minute" value="120">
                    <button class="primary">إنشاء API Key</button>
                </form>
                <div class="market-list compact">
                    <?php foreach (($developer['docs'] ?? []) as $doc): ?><span><?= htmlspecialchars($doc['title'] ?? '') ?></span><?php endforeach; ?>
                </div>
            </article>
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>نظام Webhook</h2><span>أحداث موقعة للتطبيقات الخارجية</span></div></div>
                <form class="developer-webhook-form compact-form">
                    <input name="url" placeholder="https://app.example.com/webhook">
                    <input name="events" value="message.created,contact.updated,campaign.launched">
                    <input name="secret" placeholder="Webhook Secret اختياري">
                    <button class="secondary">تسجيل Webhook</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>نظام الإضافات</h2><span>محمل إضافات وبيئة عزل آمنة</span></div></div>
                <div class="plugin-manifest">
                    <?php foreach (($pluginSystem['extension_points'] ?? []) as $point): ?><span><?= htmlspecialchars($point) ?></span><?php endforeach; ?>
                </div>
                <ul class="insight-list">
                    <li><b>العزل الآمن</b><span>لا وصول مباشر لقاعدة البيانات، فقط بوابة API بصلاحيات.</span></li>
                    <li><b>ناقل الأحداث</b><span><?= htmlspecialchars(implode(' / ', array_slice($pluginSystem['event_bus'] ?? [], 0, 4))) ?></span></li>
                </ul>
            </article>
        </section>

        <section class="workspace-grid marketplace-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>التطبيقات المثبتة</h2><span>التطبيقات المثبتة حالياً</span></div></div>
                <?php if (!$installedApps): ?>
                    <p class="copy">لا توجد تطبيقات مثبتة بعد. ابدأ من متجر التطبيقات بالأعلى.</p>
                <?php else: ?>
                    <div class="installed-app-list">
                        <?php foreach ($installedApps as $app): ?>
                            <div><b><?= htmlspecialchars($app['name'] ?? 'تطبيق') ?></b><span><?= htmlspecialchars($labelText($app['status'] ?? 'active')) ?></span><button class="danger api-post" data-api="/api/marketplace/apps/<?= (int) ($app['app_id'] ?? 0) ?>/uninstall">إزالة</button></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>الفوترة ومشاركة الإيراد</h2><span>فوترة التطبيقات / مشاركة الاشتراكات / الإحالات</span></div></div>
                <div class="launch-grid compact-launch">
                    <div><b>فوترة التطبيق</b><span>اشتراك شهري أو دفع مرة واحدة.</span></div>
                    <div><b>مشاركة الإيراد</b><span>نسبة منصة ومطور قابلة للإعداد.</span></div>
                    <div><b>نظام الإحالات</b><span>تتبع إحالات التطبيقات والقوالب.</span></div>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>تحكم إدارة المنصة</h2><span>مراجعة التطبيقات قبل النشر</span></div></div>
                <div class="market-list">
                    <span>مراجعة التطبيقات</span><span>الموافقة على الإضافات</span><span>إدارة التطبيقات المميزة</span><span>تعطيل تطبيق مخالف</span>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'enterprise'): ?>
        <?php
            $infra = $enterpriseOverview['infrastructure'] ?? [];
            $scale = $enterpriseOverview['scalability'] ?? [];
            $gateway = $enterpriseOverview['messaging_gateway'] ?? [];
            $security = $enterpriseOverview['security'] ?? [];
            $voice = $enterpriseOverview['voice'] ?? [];
            $devops = $enterpriseOverview['devops'] ?? [];
            $admin = $enterpriseOverview['admin'] ?? [];
        ?>
        <section class="panel wide enterprise-hero">
            <div>
                <span class="premium-pill">منصة عالمية بمستوى المؤسسات</span>
                <h2>بنية عالمية قابلة للتوسع لملايين الرسائل والمحادثات عبر Regions وQueues وFailover.</h2>
                <p>هذه الطبقة تضيف خطة تشغيل مؤسسية فوق النظام الحالي: تعدد المناطق، الأمان، بوابة الرسائل، الذكاء الاصطناعي، الصوت، التشغيل، ومركز الامتثال بدون كسر أي خدمة قائمة.</p>
            </div>
            <div class="enterprise-readiness">
                <small>جاهزية المؤسسات</small>
                <strong><?= (int) ($enterpriseOverview['readiness_score'] ?? 0) ?>%</strong>
                <span>مخطط إنتاج</span>
            </div>
        </section>

        <section class="metric-grid">
            <article class="metric-card lift-card"><span>المناطق</span><strong><?= count($infra['regions'] ?? []) ?></strong><small>توجيه جغرافي + إقامة البيانات</small></article>
            <article class="metric-card lift-card"><span>زمن التعافي</span><strong><?= htmlspecialchars($infra['failover_rto'] ?? '15m') ?></strong><small>تعافٍ تلقائي</small></article>
            <article class="metric-card lift-card"><span>نقطة التعافي</span><strong><?= htmlspecialchars($infra['failover_rpo'] ?? '5m') ?></strong><small>نسخ احتياطي وتكرار</small></article>
            <article class="metric-card lift-card"><span>مزودو الرسائل</span><strong><?= count($gateway['providers'] ?? []) ?></strong><small>Failover متعدد المزودين</small></article>
            <article class="metric-card lift-card"><span>ضوابط الأمان</span><strong><?= count($security['controls'] ?? []) ?></strong><small>SOC2 / GDPR / SSO</small></article>
        </section>

        <section class="workspace-grid enterprise-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>البنية العالمية</h2><span>مناطق متعددة / CDN / Edge / توجيه جغرافي</span></div></div>
                <div class="region-map">
                    <?php foreach (($infra['regions'] ?? []) as $region): ?>
                        <div><b><?= htmlspecialchars($region['region_code'] ?? '') ?></b><span><?= htmlspecialchars($region['name'] ?? '') ?></span><em><?= htmlspecialchars($region['status'] ?? '') ?></em></div>
                    <?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>معمارية التوسع</h2><span>خدمات مصغرة + أحداث تشغيل</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (array_merge($scale['architecture'] ?? [], $scale['event_driven'] ?? []) as $item): ?><span><?= htmlspecialchars($item) ?></span><?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>محرك الرسائل المتقدم</h2><span>توجيه / إعادة محاولة / تحسين / Failover</span></div></div>
                <ul class="insight-list">
                    <?php foreach (($gateway['routing_rules'] ?? []) as $rule): ?><li><b>قاعدة</b><span><?= htmlspecialchars($rule) ?></span></li><?php endforeach; ?>
                </ul>
            </article>
        </section>

        <section class="workspace-grid enterprise-grid">
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>أمان المؤسسات</h2><span>SSO / SAML / GDPR / قائمة IP المسموحة</span></div></div>
                <form class="enterprise-security-form compact-form">
                    <label><input type="checkbox" name="sso_enabled" value="1"> تفعيل SSO</label>
                    <label><input type="checkbox" name="saml_enabled" value="1"> تفعيل SAML</label>
                    <label><input type="checkbox" name="oauth_enterprise_enabled" value="1" checked> OAuth للمؤسسات</label>
                    <label><input type="checkbox" name="soc2_ready" value="1" checked> جاهزية SOC2</label>
                    <label><input type="checkbox" name="gdpr_enabled" value="1" checked> امتثال GDPR</label>
                    <input name="data_residency_region" placeholder="EU / US / GCC" value="global">
                    <input name="session_timeout_minutes" value="60">
                    <button class="primary">حفظ سياسة الأمان</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>ذكاء المؤسسات</h2><span>نماذج خاصة / ذاكرة / صوت / تعدد اللغات</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($enterpriseOverview['ai']['features'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
                <p class="copy">AI Memory معزولة لكل Tenant، ولا يوجد تدريب بين المتاجر، وكل الأفعال قابلة للمراجعة.</p>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>الصوت ومركز الاتصال</h2><span>VoIP / تسجيل / ملخصات ذكية / IVR</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($voice['features'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="workspace-grid enterprise-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>إدارة عملاء متقدمة</h2><span>مسارات بيع / فرص / توقعات</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($enterpriseOverview['crm'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>تحليلات متقدمة</h2><span>ذكاء أعمال لحظي / مستودع بيانات / تقارير تنفيذية</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($enterpriseOverview['analytics'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>أتمتة المؤسسات</h2><span>أقسام / موافقات / اتفاقيات خدمة</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($enterpriseOverview['automation'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="workspace-grid enterprise-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>التشغيل والاعتمادية</h2><span>CI/CD / Kubernetes / مراقبة / نسخ احتياطي</span></div></div>
                <div class="launch-grid compact-launch">
                    <?php foreach (($devops['runtime'] ?? []) as $item): ?><div><b><?= htmlspecialchars($item) ?></b><span>جاهز كطبقة نشر خارجية</span></div><?php endforeach; ?>
                    <?php foreach (($devops['observability'] ?? []) as $item): ?><div><b><?= htmlspecialchars($item) ?></b><span>مراقبة وتشخيص إنتاجي</span></div><?php endforeach; ?>
                </div>
            </article>
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>مزود الرسائل</h2><span>إضافة مزود رسائل مع Failover</span></div></div>
                <form class="enterprise-provider-form compact-form">
                    <select name="provider"><option value="whatsapp_cloud">WhatsApp Cloud</option><option value="email">Email</option><option value="sms">SMS</option><option value="telegram">Telegram</option></select>
                    <input name="region_code" value="global">
                    <input name="priority" value="1">
                    <input name="rate_limit_per_minute" value="600">
                    <label><input type="checkbox" name="failover_enabled" value="1" checked> Failover Enabled</label>
                    <button class="secondary">حفظ المزود</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>إدارة المؤسسات</h2><span>المستأجر / المنطقة / الامتثال / الاستخدام</span></div></div>
                <div class="enterprise-pills">
                    <span>Tenant Management</span><span>Region Management</span><span>Usage Control</span><span>Compliance Center</span><span>Partner Portals</span><span>Multi Branding</span>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($page === 'ai-commerce-os'): ?>
        <?php
            $osAgents = $commerceOsOverview['agents'] ?? [];
            $osMemory = $commerceOsOverview['memory'] ?? [];
            $osDecision = $commerceOsOverview['decision_engine'] ?? ['decisions' => []];
            $osCommand = $commerceOsOverview['command_center'] ?? [];
            $osInfra = $commerceOsOverview['infrastructure'] ?? [];
            $futureReady = $commerceOsOverview['future_ready'] ?? [];
        ?>
        <section class="panel wide commerce-os-hero">
            <div>
                <span class="premium-pill">نظام تشغيل التجارة والتواصل الذكي</span>
                <h2>نظام تشغيل ذكي يوحد التواصل، المبيعات، التسويق، خدمة العملاء، التحليلات، والتشغيل الداخلي.</h2>
                <p>الـ AI Agents تعمل كمساعدين مستقلين بصلاحيات وذاكرة وأهداف وتقارير، وكل قرار مؤثر يبقى قابلاً للمراجعة البشرية قبل التنفيذ.</p>
            </div>
            <div class="commerce-os-readiness">
                <small>OS Readiness</small>
                <strong><?= (int) ($commerceOsOverview['readiness_score'] ?? 0) ?>%</strong>
                <span>Autonomous but reviewable</span>
            </div>
        </section>

        <section class="metric-grid">
            <article class="metric-card lift-card"><span>AI Agents</span><strong><?= count($osAgents) ?></strong><small>Sales / Support / Marketing / Recovery / Analytics / Ops</small></article>
            <article class="metric-card lift-card"><span>Long-term Memory</span><strong><?= (int) ($osMemory['long_term_memory'] ?? 0) ?></strong><small>ذاكرة استراتيجية لكل متجر</small></article>
            <article class="metric-card lift-card"><span>Customer Memory</span><strong><?= (int) ($osMemory['customer_memory'] ?? 0) ?></strong><small>تفضيلات وسلوك وملخصات</small></article>
            <article class="metric-card lift-card"><span>AI Decisions</span><strong><?= count($osDecision['decisions'] ?? []) ?></strong><small>كلها pending review</small></article>
            <article class="metric-card lift-card"><span>تجارب مولدة</span><strong><?= count($commerceOsOverview['generated_experiences'] ?? []) ?></strong><small>حملات / مسارات / تقارير</small></article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>AI Autonomous Agents</h2><span>كل Agent له ذاكرة وصلاحيات وأهداف Workflow خاص</span></div><button class="ghost-btn commerce-generate-btn" type="button">توليد حملة AI</button></div>
            <div class="os-agent-grid">
                <?php foreach ($osAgents as $agent): ?>
                    <article class="os-agent-card">
                        <div class="app-card-top"><span class="app-icon"><?= htmlspecialchars(strtoupper(substr($agent['agent_key'] ?? 'ai', 0, 2))) ?></span><div><b><?= htmlspecialchars($agent['name'] ?? 'AI Agent') ?></b><small><?= htmlspecialchars($agent['role'] ?? '') ?></small></div></div>
                        <div class="scope-row">
                            <?php foreach (array_slice((array) ($agent['goals'] ?? []), 0, 3) as $goal): ?><em><?= htmlspecialchars($goal) ?></em><?php endforeach; ?>
                        </div>
                        <div class="os-workflow">
                            <?php foreach (array_slice((array) ($agent['workflow'] ?? []), 0, 4) as $step): ?><span><?= htmlspecialchars($step) ?></span><?php endforeach; ?>
                        </div>
                        <button class="primary api-post" data-api="/api/ai-commerce-os/agents/<?= htmlspecialchars($agent['agent_key'] ?? 'sales') ?>/activate">تفعيل Agent</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="workspace-grid commerce-os-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>AI Memory System</h2><span>Long-term / Conversation / Customer / Business Context</span></div></div>
                <div class="memory-orbit">
                    <span>Long-term</span><span>Conversation</span><span>Customer</span><span>Business Context</span>
                </div>
                <p class="copy">الذاكرة معزولة لكل Tenant، ومهيأة للـ Vector DB وKnowledge Graph عند تشغيل البنية العالمية.</p>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>AI Decision Engine</h2><span>قرارات تسويقية وتشغيلية قابلة للمراجعة</span></div></div>
                <ul class="insight-list">
                    <?php foreach (array_slice($osDecision['decisions'] ?? [], 0, 4) as $decision): ?>
                        <li><b><?= htmlspecialchars($decision['type'] ?? 'decision') ?></b><span><?= htmlspecialchars($decision['title'] ?? '') ?> - ثقة <?= (int) ($decision['confidence'] ?? 0) ?>%</span></li>
                    <?php endforeach; ?>
                </ul>
                <button class="secondary api-post" data-api="/api/ai-commerce-os/decisions">تحديث القرارات</button>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>نظام التجارة الموحد</h2><span>العملاء + الرسائل + الطلبات + المدفوعات</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($commerceOsOverview['commerce_os'] ?? []) as $module): ?><span><?= htmlspecialchars($module) ?></span><?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="workspace-grid commerce-os-grid">
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>تجارب مولدة بالذكاء</h2><span>حملات، مسارات، ردود، صفحات، تقارير</span></div></div>
                <form class="ai-commerce-generate-form compact-form">
                    <select name="type"><option value="campaign">حملة</option><option value="flow">مسار</option><option value="reply">رد</option><option value="page">صفحة</option><option value="report">تقرير</option></select>
                    <input name="prompt" value="زيادة المبيعات عبر واتساب للعملاء ذوي احتمالية شراء مرتفعة">
                    <button class="primary">توليد تجربة</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>مركز أوامر الذكاء</h2><span>توصيات / مخاطر / فرص</span></div></div>
                <ul class="insight-list">
                    <?php foreach (array_slice($osCommand['business_risks'] ?? [], 0, 2) as $risk): ?><li><b>مخاطر</b><span><?= htmlspecialchars(is_array($risk) ? ($risk['message'] ?? '') : $risk) ?></span></li><?php endforeach; ?>
                    <?php foreach (array_slice($osCommand['opportunities'] ?? [], 0, 2) as $opp): ?><li><b>فرصة</b><span><?= htmlspecialchars(is_array($opp) ? ($opp['message'] ?? '') : $opp) ?></span></li><?php endforeach; ?>
                </ul>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>الذكاء الصوتي</h2><span>مكالمات / بوتات / مساعد / ملخصات</span></div></div>
                <div class="enterprise-pills">
                    <?php foreach (($commerceOsOverview['voice_ai']['features'] ?? []) as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="workspace-grid commerce-os-grid">
            <article class="panel">
                <div class="panel-head"><div><h2>متجر الذكاء الاصطناعي</h2><span>وكلاء / قوالب / مهارات / مسارات عمل</span></div></div>
                <div class="market-list">
                    <?php foreach (array_merge($commerceOsOverview['marketplace']['agents_store'] ?? [], $commerceOsOverview['marketplace']['skills'] ?? []) as $item): ?><span><?= htmlspecialchars($item) ?></span><?php endforeach; ?>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>بنية الذكاء العالمية</h2><span>Workers / GPU / قاعدة متجهات / رسم معرفي</span></div></div>
                <div class="launch-grid compact-launch">
                    <div><b>عمال موزعون</b><span><?= htmlspecialchars((string) ($osInfra['distributed_ai_workers'] ?? '4')) ?></span></div>
                    <div><b>طوابير GPU</b><span><?= htmlspecialchars((string) ($osInfra['gpu_queues'] ?? 'planned')) ?></span></div>
                    <div><b>قاعدة المتجهات</b><span><?= htmlspecialchars((string) ($osInfra['vector_databases'] ?? 'planned')) ?></span></div>
                    <div><b>الرسم المعرفي</b><span><?= htmlspecialchars((string) ($osInfra['knowledge_graphs'] ?? 'planned')) ?></span></div>
                </div>
            </article>
            <article class="panel">
                <div class="panel-head"><div><h2>جاهزية المستقبل</h2><span>API-first / Headless / جوال / سطح مكتب / SDK</span></div></div>
                <div class="enterprise-pills">
                    <span>API-first</span><span>Headless <?= !empty($futureReady['headless_mode']) ? 'مفعل' : 'معطل' ?></span><span>تطبيقات جوال</span><span>تطبيقات سطح مكتب</span><span>واجهات API عامة</span><span>AI SDK <?= htmlspecialchars((string) ($futureReady['ai_sdk'] ?? 'v1')) ?></span>
                </div>
            </article>
        </section>

        <section class="panel wide">
            <div class="panel-head"><div><h2>نظام يتحسن ذاتياً</h2><span>يتعلم من الأداء ويحسن الردود والتحويلات ومسارات العمل</span></div></div>
            <div class="launch-grid">
                <?php foreach (($commerceOsOverview['self_improving']['learns_from'] ?? []) as $source): ?><div><b><?= htmlspecialchars($source) ?></b><span>مصدر تعلم قابل للقياس والمراجعة</span></div><?php endforeach; ?>
                <?php foreach (($commerceOsOverview['self_improving']['improves'] ?? []) as $target): ?><div><b><?= htmlspecialchars($target) ?></b><span>تحسين تدريجي بعد موافقة ومراقبة النتائج</span></div><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'saas'): ?>
        <?php $subscription = $saasContext['subscription'] ?? ['plan_key' => 'free', 'limits' => []]; $usage = $saasContext['usage'] ?? []; ?>
        <section class="panel wide saas-hero">
            <div>
                <span class="premium-pill">منصة SaaS متعددة المستأجرين</span>
                <h2>إدارة المتاجر، الاشتراكات، الفريق، الفوترة، والهوية البيضاء من مكان واحد</h2>
                <p>كل متجر معزول ببياناته واتصالاته وحملاته وبوته وصلاحياته. يمكن تبديل مساحة العمل وتتبع الاستخدام والاشتراك بدون التأثير على أي متجر آخر.</p>
            </div>
            <form class="workspace-switch-form compact-form">
                <select name="store_id">
                    <?php foreach (($saasContext['stores'] ?? [['id' => 1, 'name' => 'المتجر الرئيسي']]) as $store): ?>
                        <option value="<?= (int) ($store['id'] ?? 1) ?>" <?= (int) ($store['id'] ?? 0) === (int) ($saasContext['active_store_id'] ?? 1) ? 'selected' : '' ?>><?= htmlspecialchars($store['name'] ?? ('متجر #' . ($store['id'] ?? 1))) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="primary">تبديل مساحة العمل</button>
            </form>
        </section>

        <section class="metric-grid">
            <article class="metric-card lift-card"><span>الباقة الحالية</span><strong><?= htmlspecialchars($labelText($subscription['plan_key'] ?? 'free')) ?></strong><small><?= htmlspecialchars($labelText($subscription['status'] ?? 'trialing')) ?></small></article>
            <article class="metric-card lift-card"><span>الرسائل</span><strong><?= (int) ($usage['messages'] ?? 0) ?></strong><small>من <?= (int) (($subscription['limits']['messages'] ?? 0)) ?></small></article>
            <article class="metric-card lift-card"><span>استخدام الذكاء</span><strong><?= (int) ($usage['ai_credits'] ?? 0) ?></strong><small>من <?= (int) (($subscription['limits']['ai_credits'] ?? 0)) ?></small></article>
            <article class="metric-card lift-card"><span>أعضاء الفريق</span><strong><?= (int) ($usage['team_members'] ?? 0) ?></strong><small>من <?= (int) (($subscription['limits']['team_members'] ?? 0)) ?></small></article>
            <article class="metric-card lift-card"><span>التخزين</span><strong><?= (int) ($usage['storage_mb'] ?? 0) ?>MB</strong><small>من <?= (int) (($subscription['limits']['storage_mb'] ?? 0)) ?>MB</small></article>
        </section>

        <section class="panel wide plans-grid-section">
            <div class="panel-head"><div><h2>الباقات والقيود</h2><span>مجانية / بداية / احترافية / مؤسسية</span></div></div>
            <div class="plans-grid">
                <?php foreach ([
                    ['free', 'مجانية', '$0', '250 رسالة، عضو واحد، رقم واتساب واحد'],
                    ['starter', 'البداية', '$29', '3,000 رسالة، 3 موظفين، 20 حملة'],
                    ['professional', 'احترافية', '$99', '20,000 رسالة، 10 موظفين، 3 أرقام واتساب'],
                    ['enterprise', 'مؤسسية', '$299', '100,000 رسالة، 50 موظف، هوية بيضاء كاملة'],
                ] as $plan): ?>
                    <article>
                        <b><?= htmlspecialchars($plan[1]) ?></b>
                        <strong><?= htmlspecialchars($plan[2]) ?><small>/شهري</small></strong>
                        <span><?= htmlspecialchars($plan[3]) ?></span>
                        <button class="primary api-post" data-api="/api/saas/subscription">اختيار الباقة</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="workspace-grid">
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>أعضاء الفريق</h2><span>دعوة أعضاء وصلاحيات مستقلة لكل متجر</span></div></div>
                <form class="team-invite-form compact-form">
                    <input name="email" placeholder="email@example.com">
                    <select name="role"><option value="admin">مدير</option><option value="agent">موظف</option><option value="viewer">مشاهد</option></select>
                    <button class="primary">إرسال دعوة</button>
                </form>
                <div class="launch-grid compact-launch"><div><b>المالك</b><span>كل الصلاحيات</span></div><div><b>المدير</b><span>إدارة الربط والحملات</span></div><div><b>الموظف</b><span>الرد على العملاء</span></div><div><b>المشاهد</b><span>قراءة التقارير فقط</span></div></div>
            </article>
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>الفوترة وبوابات الدفع</h2><span>Stripe / PayPal / Moyasar / Tap / MyFatoorah</span></div></div>
                <form class="payment-gateway-form compact-form">
                    <select name="provider"><option value="stripe">Stripe</option><option value="paypal">PayPal</option><option value="moyasar">Moyasar</option><option value="tap">Tap</option><option value="myfatoorah">MyFatoorah</option></select>
                    <input name="display_name" placeholder="اسم بوابة الدفع">
                    <input name="api_key" placeholder="API Key">
                    <label><input type="checkbox" name="test_mode" value="1" checked> وضع الاختبار</label>
                    <button class="primary">حفظ البوابة</button>
                </form>
            </article>
            <article class="panel form-panel">
                <div class="panel-head"><div><h2>الهوية البيضاء</h2><span>الشعار، الدومين، الألوان، وهوية البريد</span></div></div>
                <form class="white-label-form compact-form">
                    <input name="logo_url" placeholder="رابط الشعار المخصص">
                    <input name="custom_domain" placeholder="app.yourbrand.com">
                    <input name="primary_color" value="#2f80ed">
                    <input name="secondary_color" value="#25d366">
                    <input name="email_from_name" placeholder="اسم البريد">
                    <textarea name="email_footer" placeholder="تذييل البريد"></textarea>
                    <button class="primary">حفظ الهوية</button>
                </form>
            </article>
        </section>

        <section class="panel wide">
                <div class="panel-head"><div><h2>جاهزية الإنتاج</h2><span>Queue، Redis، Caching، Workers، Docker، CI/CD، Monitoring</span></div></div>
            <div class="launch-grid">
                <div><b>عزل المستأجرين</b><span>كل الاستعلامات تعمل بـ store_id وسياق مساحة العمل.</span></div>
                <div><b>حدود الاستخدام</b><span>قيود الرسائل والذكاء والحملات والتخزين حسب الباقة.</span></div>
                <div><b>التنبيهات</b><span>تنبيهات الفوترة، الانتهاء، فصل واتساب، وتقارير الحملات.</span></div>
                <div><b>التوسع</b><span>Workers وRedis وCaching جاهزة كطبقة تشغيل خارجية.</span></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'super-admin'): ?>
        <section class="panel wide saas-hero">
            <div>
                <span class="premium-pill">لوحة إدارة المنصة</span>
                <h2>لوحة تحكم مالك المنصة</h2>
                <p>متابعة كل المتاجر، الأرباح، الاشتراكات، المستخدمين النشطين، أرقام واتساب المتصلة، واستخدام AI على مستوى المنصة.</p>
            </div>
        </section>
        <section class="metric-grid">
            <article class="metric-card lift-card"><span>كل المتاجر</span><strong><?= (int) ($superAdminOverview['stores'] ?? 0) ?></strong><small>مستأجرون</small></article>
            <article class="metric-card lift-card"><span>الأرباح الشهرية</span><strong><?= number_format((float) ($superAdminOverview['monthly_revenue'] ?? 0)) ?></strong><small>MRR</small></article>
            <article class="metric-card lift-card"><span>الاشتراكات</span><strong><?= (int) ($superAdminOverview['subscriptions'] ?? 0) ?></strong><small>نشط / تجريبي</small></article>
            <article class="metric-card lift-card"><span>المستخدمون النشطون</span><strong><?= (int) ($superAdminOverview['active_users'] ?? 0) ?></strong><small>مستخدمون</small></article>
            <article class="metric-card lift-card"><span>أرقام واتساب المتصلة</span><strong><?= (int) ($superAdminOverview['connected_whatsapps'] ?? 0) ?></strong><small>Cloud + QR</small></article>
            <article class="metric-card lift-card"><span>استخدام الذكاء</span><strong><?= (int) ($superAdminOverview['ai_usage'] ?? 0) ?></strong><small>أرصدة</small></article>
        </section>
        <section class="panel wide login-portals-admin">
            <div class="panel-head">
                <div>
                    <h2>إدارة بوابات الدخول</h2>
                    <span>متابعة روابط دخول المتاجر، الجلسات النشطة، ومحاولات الدخول لكل بوابة.</span>
                </div>
                <div class="button-row">
                    <a class="secondary" href="<?= htmlspecialchars($appUrl) ?>/platform/login">بوابة المنصة</a>
                    <a class="secondary" href="<?= htmlspecialchars($appUrl) ?>/store/login">بوابة المتجر</a>
                    <a class="secondary" href="<?= htmlspecialchars($appUrl) ?>/agent/login">بوابة الموظفين</a>
                </div>
            </div>
            <div class="login-portal-grid">
                <article>
                    <h3>روابط المتاجر</h3>
                    <div class="mini-table">
                        <span>المتجر</span><span>الرابط</span><span>الحالة</span>
                        <?php foreach (($loginPortalStores ?? []) as $store): ?>
                            <strong><?= htmlspecialchars($store['name'] ?? '') ?></strong>
                            <a href="<?= htmlspecialchars($appUrl) ?>/tenant/<?= htmlspecialchars($store['slug'] ?? '') ?>/login">/tenant/<?= htmlspecialchars($store['slug'] ?? '') ?>/login</a>
                            <em><?= htmlspecialchars($store['status'] ?? 'active') ?></em>
                        <?php endforeach; ?>
                        <?php if (empty($loginPortalStores)): ?><strong>لا توجد متاجر</strong><span>أضف متجر أولاً</span><em>غير متاح</em><?php endif; ?>
                    </div>
                </article>
                <article>
                    <h3>الجلسات النشطة</h3>
                    <div class="mini-table">
                        <span>المستخدم</span><span>البوابة</span><span>الجهاز</span>
                        <?php foreach (array_slice(($loginPortalSessions ?? []), 0, 8) as $session): ?>
                            <strong>#<?= (int) ($session['user_id'] ?? 0) ?> <?= htmlspecialchars($session['user_type'] ?? '') ?></strong>
                            <span><?= htmlspecialchars($session['portal_type'] ?? '') ?></span>
                            <em><?= htmlspecialchars($session['device_name'] ?? 'متصفح') ?></em>
                        <?php endforeach; ?>
                        <?php if (empty($loginPortalSessions)): ?><strong>لا توجد جلسات</strong><span>لم يتم تسجيل دخول بعد</span><em>آمن</em><?php endif; ?>
                    </div>
                </article>
                <article>
                    <h3>محاولات الدخول</h3>
                    <div class="mini-table">
                        <span>البريد</span><span>البوابة</span><span>النتيجة</span>
                        <?php foreach (array_slice(($loginPortalAttempts ?? []), 0, 8) as $attempt): ?>
                            <strong><?= htmlspecialchars($attempt['email'] ?? '') ?></strong>
                            <span><?= htmlspecialchars($attempt['portal_type'] ?? '') ?></span>
                            <em class="<?= !empty($attempt['success']) ? 'ok' : 'danger-text' ?>"><?= !empty($attempt['success']) ? 'نجاح' : 'فشل' ?></em>
                        <?php endforeach; ?>
                        <?php if (empty($loginPortalAttempts)): ?><strong>لا توجد محاولات</strong><span>السجل فارغ</span><em>جاهز</em><?php endif; ?>
                    </div>
                </article>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($page === 'settings' || str_starts_with($page, 'settings-')): ?>
        <?php
            $cc = $controlCenter ?? [];
            $ccGeneral = $cc['general'] ?? [];
            $ccWhatsapp = $cc['whatsapp'] ?? [];
            $ccLimits = $cc['campaign_limits'] ?? [];
            $ccReplies = $cc['quick_replies'] ?? [];
            $ccUsers = $cc['users'] ?? [];
            $ccRoles = $cc['roles'] ?? [];
            $ccPermissions = $cc['permissions'] ?? [];
            $ccCompanies = $cc['companies'] ?? [];
            $ccStores = $cc['stores'] ?? [];
            $ccDepartments = $cc['departments'] ?? [];
            $ccSubscriptions = $cc['subscriptions'] ?? [];
            $ccSecurity = $cc['security'] ?? [];
            $ccApiKeys = $cc['api_keys'] ?? [];
            $ccWebhooks = $cc['webhooks'] ?? [];
            $ccDocuments = $cc['documents'] ?? [];
            $ccNotifications = $cc['notifications'] ?? [];
            $ccLogs = $cc['logs'] ?? [];
            $ccBranding = $cc['branding'] ?? [];
            $ccAi = $cc['ai'] ?? [];
            $ccBackup = $cc['backup'] ?? [];
            $ccLaunch = $cc['launch'] ?? [];
            $ccStatus = $cc['system_status'] ?? [];
            $settingSections = [
                'general' => 'الإعدادات العامة',
                'whatsapp' => 'إعدادات واتساب',
                'campaigns' => 'الحملات والحدود',
                'quick-replies' => 'الردود السريعة',
                'users' => 'المستخدمون',
                'roles' => 'الأدوار والصلاحيات',
                'companies' => 'الشركات والمتاجر',
                'departments' => 'الفرق والأقسام',
                'billing' => 'الاشتراكات والباقات',
                'security' => 'الأمان والجلسات',
                'developer' => 'Webhooks & API',
                'documents' => 'الملفات والمستندات',
                'notifications' => 'التنبيهات والإشعارات',
                'logs' => 'السجلات والمراقبة',
                'branding' => 'الهوية والـ White Label',
                'ai' => 'إعدادات الذكاء الاصطناعي',
                'backup' => 'النسخ الاحتياطي',
                'launch' => 'إعدادات الإطلاق',
            ];
            $settingSections = $settingsPageLabels;
            $settingsOverview = $page === 'settings';
            $activeSetting = str_starts_with($page, 'settings-') ? substr($page, 9) : '';
            $activeSetting = array_key_exists($activeSetting, $settingSections) ? $activeSetting : 'general';
            $settingDescriptions = [
                'general' => 'هوية المنصة، اللغة، المنطقة الزمنية، التسجيل، الروابط الرسمية ووضع التشغيل.',
                'whatsapp' => 'Webhook، Meta، QR، القوالب، حدود الإرسال، الاشتراك وجودة الرقم.',
                'campaigns' => 'حدود الحملات، Batch Sending، Retry، منع التكرار، QR Safe Mode وCloud API.',
                'quick-replies' => 'رسائل الترحيب، خارج الدوام، التحويل، الشكاوى، المتابعة وإلغاء الاشتراك.',
                'users' => 'إضافة المستخدمين، الأدوار، الأقسام، الجلسات النشطة وآخر تسجيل دخول.',
                'roles' => 'مصفوفة صلاحيات كاملة للصفحات والـ APIs والحملات والواتساب والفواتير.',
                'companies' => 'الشركات والمتاجر، التوثيق، المالك، الدومينات، الروابط وحالة الاشتراك.',
                'departments' => 'فرق المبيعات والدعم والشحن والحسابات والشكاوى مع SLA والتحويل التلقائي.',
                'billing' => 'الباقات، حدود الاستخدام، الفواتير، AI Credits وطرق الدفع.',
                'security' => '2FA، CSRF، CSP، Secure Cookies، الجلسات، Login Attempts والأسرار.',
                'developer' => 'API Keys، Webhooks، Verify Tokens، Callback URLs وسجلات آخر Payload.',
                'documents' => 'رفع ومراجعة مستندات الشركات، التوثيق، اللوجوهات، الشروط والخصوصية.',
                'notifications' => 'تنبيهات الربط، الحملات، الاشتراكات، الجلسات، Queue وWebhook.',
                'logs' => 'Audit Logs، Login Logs، Webhook Logs، Campaign Logs، Queue وAPI Logs.',
                'branding' => 'الشعار، الألوان، صفحة الدخول، الدومين المخصص، البريد وPWA Theme.',
                'ai' => 'مزود الذكاء، الموديل، حدود الاستخدام، قواعد السلامة وKnowledge Base.',
                'backup' => 'نسخ قاعدة البيانات والملفات، Restore Points، الجدولة وسياسة الاحتفاظ.',
                'launch' => 'فحص البيئة، الأمان، قاعدة البيانات، Queue، Webhook، Build ووضع الإنتاج.',
            ];
            $settingStats = [
                'general' => $ccGeneral['runtime_mode'] ?? 'production',
                'whatsapp' => 'Meta ' . $labelText($ccWhatsapp['meta_status'] ?? 'disconnected'),
                'campaigns' => ($ccLimits['daily_messages'] ?? 0) . ' رسالة/يوم',
                'quick-replies' => '6 ردود',
                'users' => count((array) $ccUsers) . ' مستخدم',
                'roles' => count((array) $ccRoles) . ' أدوار',
                'companies' => count((array) $ccStores) . ' متجر',
                'departments' => count((array) $ccDepartments) . ' أقسام',
                'billing' => $labelText($ccSubscriptions['current']['plan_key'] ?? 'free'),
                'security' => (!empty($ccSecurity['jwt_secret_present']) && !empty($ccSecurity['encryption_key_present'])) ? 'أسرار مكتملة' : 'يحتاج أسرار',
                'developer' => (count((array) $ccApiKeys) + count((array) $ccWebhooks)) . ' تكامل',
                'documents' => count((array) $ccDocuments) . ' ملف',
                'notifications' => count((array) ($ccNotifications['settings'] ?? [])) . ' قناة',
                'logs' => count((array) ($ccLogs['audit'] ?? [])) . ' سجل',
                'branding' => $ccBranding['product_name'] ?? 'Marketing Center',
                'ai' => !empty($ccAi['enabled']) ? 'مفعل' : 'معطل',
                'backup' => count((array) ($ccBackup['jobs'] ?? [])) . ' عمليات',
                'launch' => ((int) ($ccLaunch['score'] ?? 0)) . '%',
            ];
            $settingIcons = [
                'general' => 'GE', 'whatsapp' => 'WA', 'campaigns' => 'CA', 'quick-replies' => 'QR',
                'users' => 'US', 'roles' => 'RB', 'companies' => 'CO', 'departments' => 'DP',
                'billing' => 'BI', 'security' => 'SE', 'developer' => 'API', 'documents' => 'DO',
                'notifications' => 'NO', 'logs' => 'LG', 'branding' => 'BR', 'ai' => 'AI',
                'backup' => 'BK', 'launch' => 'GO',
            ];
            $settingCategories = [
                'general' => 'core', 'whatsapp' => 'messaging', 'campaigns' => 'messaging', 'quick-replies' => 'messaging',
                'users' => 'access', 'roles' => 'access', 'security' => 'access',
                'companies' => 'operations', 'departments' => 'operations', 'billing' => 'operations',
                'developer' => 'governance', 'documents' => 'governance', 'notifications' => 'governance', 'logs' => 'governance',
                'branding' => 'experience', 'ai' => 'experience', 'backup' => 'governance', 'launch' => 'governance',
            ];
            $settingCategoryLabels = [
                'all' => 'كل المسارات',
                'core' => 'الأساس',
                'messaging' => 'واتساب والحملات',
                'access' => 'الوصول والأمان',
                'operations' => 'التشغيل',
                'governance' => 'الحوكمة',
                'experience' => 'التجربة والذكاء',
            ];
            $settingHealth = [
                'general' => ['class' => 'ok', 'label' => 'جاهز', 'progress' => 92],
                'whatsapp' => ['class' => (($ccWhatsapp['meta_status'] ?? '') === 'connected' || ($ccWhatsapp['qr_status'] ?? '') === 'connected') ? 'ok' : 'pending', 'label' => (($ccWhatsapp['meta_status'] ?? '') === 'connected' || ($ccWhatsapp['qr_status'] ?? '') === 'connected') ? 'متصل' : 'يحتاج ربط', 'progress' => (($ccWhatsapp['meta_status'] ?? '') === 'connected' || ($ccWhatsapp['qr_status'] ?? '') === 'connected') ? 86 : 54],
                'campaigns' => ['class' => ((int) ($ccLimits['daily_messages'] ?? 0) > 0) ? 'ok' : 'pending', 'label' => ((int) ($ccLimits['daily_messages'] ?? 0) > 0) ? 'محددة' : 'اضبط الحدود', 'progress' => ((int) ($ccLimits['daily_messages'] ?? 0) > 0) ? 88 : 58],
                'quick-replies' => ['class' => 'ok', 'label' => 'جاهزة', 'progress' => 84],
                'users' => ['class' => count((array) $ccUsers) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccUsers) > 0 ? 'نشط' : 'أضف مستخدمين', 'progress' => count((array) $ccUsers) > 0 ? 82 : 45],
                'roles' => ['class' => count((array) $ccRoles) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccRoles) > 0 ? 'مكتمل' : 'راجع RBAC', 'progress' => count((array) $ccRoles) > 0 ? 90 : 50],
                'companies' => ['class' => count((array) $ccStores) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccStores) > 0 ? 'متاجر فعالة' : 'أضف متجر', 'progress' => count((array) $ccStores) > 0 ? 84 : 48],
                'departments' => ['class' => count((array) $ccDepartments) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccDepartments) > 0 ? 'مهيأ' : 'أضف أقسام', 'progress' => count((array) $ccDepartments) > 0 ? 80 : 44],
                'billing' => ['class' => 'pending', 'label' => 'يحتاج مراجعة', 'progress' => 64],
                'security' => ['class' => (!empty($ccSecurity['jwt_secret_present']) && !empty($ccSecurity['encryption_key_present'])) ? 'ok' : 'danger', 'label' => (!empty($ccSecurity['jwt_secret_present']) && !empty($ccSecurity['encryption_key_present'])) ? 'محمي' : 'أسرار ناقصة', 'progress' => (!empty($ccSecurity['jwt_secret_present']) && !empty($ccSecurity['encryption_key_present'])) ? 90 : 38],
                'developer' => ['class' => count((array) $ccWebhooks) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccWebhooks) > 0 ? 'Webhooks نشطة' : 'اختبر الربط', 'progress' => count((array) $ccWebhooks) > 0 ? 78 : 52],
                'documents' => ['class' => count((array) $ccDocuments) > 0 ? 'ok' : 'pending', 'label' => count((array) $ccDocuments) > 0 ? 'مرفوعة' : 'بانتظار ملفات', 'progress' => count((array) $ccDocuments) > 0 ? 76 : 42],
                'notifications' => ['class' => 'ok', 'label' => 'مضبوطة', 'progress' => 78],
                'logs' => ['class' => count((array) ($ccLogs['audit'] ?? [])) > 0 ? 'ok' : 'pending', 'label' => count((array) ($ccLogs['audit'] ?? [])) > 0 ? 'يسجل' : 'قليل السجلات', 'progress' => count((array) ($ccLogs['audit'] ?? [])) > 0 ? 82 : 56],
                'branding' => ['class' => 'ok', 'label' => 'متناسقة', 'progress' => 74],
                'ai' => ['class' => !empty($ccAi['enabled']) ? 'ok' : 'pending', 'label' => !empty($ccAi['enabled']) ? 'AI مفعل' : 'AI معطل', 'progress' => !empty($ccAi['enabled']) ? 82 : 50],
                'backup' => ['class' => count((array) ($ccBackup['jobs'] ?? [])) > 0 ? 'ok' : 'pending', 'label' => count((array) ($ccBackup['jobs'] ?? [])) > 0 ? 'مجدول' : 'أنشئ نسخة', 'progress' => count((array) ($ccBackup['jobs'] ?? [])) > 0 ? 80 : 46],
                'launch' => ['class' => ((int) ($ccLaunch['score'] ?? 0) >= 80) ? 'ok' : (((int) ($ccLaunch['score'] ?? 0) >= 60) ? 'pending' : 'danger'), 'label' => ((int) ($ccLaunch['score'] ?? 0) >= 80) ? 'جاهز' : 'قيد المراجعة', 'progress' => max(0, min(100, (int) ($ccLaunch['score'] ?? 0)))],
            ];
        ?>
        <section class="panel wide control-center-hero">
            <div>
                <span class="premium-pill">Platform Control Center</span>
                <h2>مركز تحكم المنصة</h2>
                <p>إدارة الإعدادات والصلاحيات والشركات والتشغيل والربط من مكان واحد مع سجل تدقيق لكل تعديل.</p>
            </div>
            <div class="control-hero-actions">
                <span class="status-pill <?= ($ccStatus['state'] ?? '') === 'healthy' ? 'ok' : 'pending' ?>">حالة النظام: <?= htmlspecialchars($ccStatus['label'] ?? 'يحتاج مراجعة') ?></span>
                <small>آخر تحديث: <?= htmlspecialchars($cc['generated_at'] ?? date('Y-m-d H:i:s')) ?></small>
                <div class="button-row">
                    <button class="primary settings-save-trigger" type="button">حفظ التغييرات</button>
                    <button class="ghost-btn control-test-settings" type="button" data-api="/api/settings/health">اختبار الإعدادات</button>
                    <button class="ghost-btn control-export-report" type="button">تصدير تقرير الإعدادات</button>
                    <button class="danger ghost-btn control-safe-mode" type="button">الوضع الآمن</button>
                </div>
            </div>
        </section>

        <?php if ($settingsOverview): ?>
            <section class="settings-command-panel panel" aria-label="أدوات مركز تحكم المنصة">
                <div>
                    <span class="premium-pill">Control Routes</span>
                    <h3>مسارات التحكم التشغيلية</h3>
                    <p>اختر القسم المطلوب لإدارته. كل بطاقة تفتح مساراً مستقلاً وتعرض حالة القسم الحالية قبل الدخول.</p>
                </div>
                <div class="settings-hub-tools">
                    <label class="settings-hub-search">
                        <span>بحث سريع</span>
                        <input type="search" placeholder="ابحث عن مسار، صلاحية، واتساب، Webhook..." data-settings-search>
                    </label>
                    <div class="settings-filter-pills" role="list" aria-label="تصفية أقسام الإعدادات">
                        <?php foreach ($settingCategoryLabels as $categoryKey => $categoryLabel): ?>
                            <button class="<?= $categoryKey === 'all' ? 'active' : '' ?>" type="button" data-settings-filter="<?= htmlspecialchars($categoryKey) ?>"><?= htmlspecialchars($categoryLabel) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="settings-hub-grid settings-route-grid" aria-label="أقسام مركز تحكم المنصة">
                <?php foreach ($settingSections as $key => $label): ?>
                    <?php
                        $routePath = '/marketing-center/settings/' . $key;
                        $health = $settingHealth[$key] ?? ['class' => 'pending', 'label' => 'قيد المراجعة', 'progress' => 50];
                        $category = $settingCategories[$key] ?? 'core';
                    ?>
                    <a class="settings-hub-card settings-route-card"
                       href="<?= htmlspecialchars($appUrl) . htmlspecialchars($routePath) ?>"
                       data-settings-card
                       data-category="<?= htmlspecialchars($category) ?>"
                       data-search="<?= htmlspecialchars($label . ' ' . ($settingDescriptions[$key] ?? '') . ' ' . $routePath . ' ' . ($settingStats[$key] ?? '')) ?>">
                        <span class="settings-card-accent"></span>
                        <span class="settings-hub-icon"><?= htmlspecialchars($settingIcons[$key] ?? 'ST') ?></span>
                        <span class="settings-route-main">
                            <span class="settings-route-topline">
                                <strong><?= htmlspecialchars($label) ?></strong>
                                <span class="status-pill <?= htmlspecialchars($health['class']) ?>"><?= htmlspecialchars($health['label']) ?></span>
                            </span>
                            <p><?= htmlspecialchars($settingDescriptions[$key] ?? 'إدارة هذا القسم من مركز التحكم.') ?></p>
                            <span class="settings-route-path"><?= htmlspecialchars($routePath) ?></span>
                            <span class="settings-route-progress" aria-hidden="true"><i style="width: <?= (int) $health['progress'] ?>%"></i></span>
                        </span>
                        <span class="settings-route-side">
                            <em><?= htmlspecialchars((string) ($settingStats[$key] ?? 'جاهز')) ?></em>
                            <b>فتح المسار <span>←</span></b>
                        </span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="settings-route-toolbar panel">
                <a class="ghost-btn" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/settings">كل الأقسام</a>
                <div>
                    <span class="premium-pill">مسار مستقل</span>
                    <h2><?= htmlspecialchars($settingSections[$activeSetting] ?? 'الإعدادات') ?></h2>
                    <p><?= htmlspecialchars($settingDescriptions[$activeSetting] ?? 'إدارة القسم المحدد من مركز التحكم.') ?></p>
                </div>
                <span class="status-pill active"><?= htmlspecialchars((string) ($settingStats[$activeSetting] ?? 'جاهز')) ?></span>
            </section>

        <section class="control-center-layout settings-detail-layout" data-active-setting="<?= htmlspecialchars($activeSetting) ?>">
            <aside class="control-sidebar panel">
                <input class="control-search" type="search" placeholder="ابحث داخل الإعدادات">
                <nav>
                    <?php foreach ($settingSections as $key => $label): ?>
                        <a class="<?= $activeSetting === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/settings/<?= htmlspecialchars($key) ?>" data-control-link><?= htmlspecialchars($label) ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="control-side-status">
                    <strong>Audit Trail</strong>
                    <span><?= count((array) ($ccLogs['audit'] ?? [])) ?> سجل تدقيق متاح</span>
                </div>
            </aside>

            <div class="control-content">
                <section class="panel control-section" id="settings-general" data-control-section>
                    <div class="panel-head"><div><h2>الإعدادات العامة</h2><span>اسم المنصة، اللغة، العملة، وضع التشغيل وروابط الالتزام.</span></div><span class="status-pill ok">نشط</span></div>
                    <form class="ajax-form control-form" data-endpoint="/api/settings/general" data-method="PUT">
                        <div class="control-grid">
                            <label>اسم المنصة<input name="platform_name" value="<?= htmlspecialchars((string) ($ccGeneral['platform_name'] ?? 'Marketing Center')) ?>"></label>
                            <label>اللغة الافتراضية<select name="default_language"><option value="ar" <?= ($ccGeneral['default_language'] ?? 'ar') === 'ar' ? 'selected' : '' ?>>العربية</option><option value="en" <?= ($ccGeneral['default_language'] ?? '') === 'en' ? 'selected' : '' ?>>الإنجليزية</option></select></label>
                            <label>المنطقة الزمنية<input name="timezone" value="<?= htmlspecialchars((string) ($ccGeneral['timezone'] ?? 'Asia/Riyadh')) ?>"></label>
                            <label>العملة الافتراضية<input name="currency" value="<?= htmlspecialchars((string) ($ccGeneral['currency'] ?? 'SAR')) ?>"></label>
                            <label>وضع التشغيل<select name="runtime_mode"><option value="development" <?= ($ccGeneral['runtime_mode'] ?? '') === 'development' ? 'selected' : '' ?>>Development</option><option value="testing" <?= ($ccGeneral['runtime_mode'] ?? '') === 'testing' ? 'selected' : '' ?>>Testing</option><option value="production" <?= ($ccGeneral['runtime_mode'] ?? 'production') === 'production' ? 'selected' : '' ?>>Production</option></select></label>
                            <label>البريد الرسمي للدعم<input name="support_email" value="<?= htmlspecialchars((string) ($ccGeneral['support_email'] ?? '')) ?>"></label>
                            <label>رابط الشروط<input name="terms_url" value="<?= htmlspecialchars((string) ($ccGeneral['terms_url'] ?? '')) ?>"></label>
                            <label>رابط الخصوصية<input name="privacy_url" value="<?= htmlspecialchars((string) ($ccGeneral['privacy_url'] ?? '')) ?>"></label>
                            <label class="toggle-line"><input type="checkbox" name="registration_enabled" value="1" <?= !empty($ccGeneral['registration_enabled']) ? 'checked' : '' ?>> تفعيل التسجيل</label>
                            <label class="toggle-line"><input type="checkbox" name="store_creation_enabled" value="1" <?= !empty($ccGeneral['store_creation_enabled']) ? 'checked' : '' ?>> تفعيل إنشاء المتاجر</label>
                        </div>
                        <button class="primary" type="submit">حفظ الإعدادات العامة</button>
                    </form>
                </section>

                <section class="panel control-section" id="settings-whatsapp" data-control-section>
                    <div class="panel-head"><div><h2>إعدادات واتساب</h2><span>الربط الرسمي، جلسة QR، Webhooks، الجودة، الاشتراك وحدود الإرسال.</span></div><span class="status-pill <?= ($ccWhatsapp['meta_status'] ?? '') === 'connected' ? 'ok' : 'pending' ?>">Meta: <?= htmlspecialchars($labelText($ccWhatsapp['meta_status'] ?? 'disconnected')) ?></span></div>
                    <form class="ajax-form control-form" data-endpoint="/api/settings/whatsapp" data-method="PUT">
                        <div class="control-grid">
                            <label>رابط Webhook<input name="webhook_url" value="<?= htmlspecialchars((string) ($ccWhatsapp['webhook_url'] ?? ($appUrl . '/api/webhooks/whatsapp'))) ?>"></label>
                            <label>المرسل الافتراضي<input name="default_sender" value="<?= htmlspecialchars((string) ($ccWhatsapp['default_sender'] ?? '')) ?>"></label>
                            <label>ساعات العمل<input name="business_hours" value="<?= htmlspecialchars((string) ($ccWhatsapp['business_hours'] ?? '09:00-18:00')) ?>"></label>
                            <label>حد الإرسال الرسمي يومياً<input name="official_daily_limit" type="number" value="<?= htmlspecialchars((string) ($ccWhatsapp['official_daily_limit'] ?? 1000)) ?>"></label>
                            <label>حد جلسة الباركود يومياً<input name="qr_daily_limit" type="number" value="<?= htmlspecialchars((string) ($ccWhatsapp['qr_daily_limit'] ?? 120)) ?>"></label>
                            <label>نافذة خدمة العملاء<input name="customer_window_hours" type="number" value="<?= htmlspecialchars((string) ($ccWhatsapp['customer_window_hours'] ?? 24)) ?>"></label>
                            <label>كلمات إلغاء الاشتراك<input name="unsubscribe_keywords" value="<?= htmlspecialchars(implode(', ', (array) ($ccWhatsapp['unsubscribe_keywords'] ?? ['STOP', 'UNSUBSCRIBE', 'إلغاء']))) ?>"></label>
                            <label>رسالة إلغاء الاشتراك<input name="unsubscribe_message" value="<?= htmlspecialchars((string) ($ccWhatsapp['unsubscribe_message'] ?? 'تم إلغاء اشتراكك من الرسائل التسويقية.')) ?>"></label>
                            <label class="toggle-line"><input type="checkbox" name="quality_monitoring" value="1" <?= !empty($ccWhatsapp['quality_monitoring']) ? 'checked' : '' ?>> مراقبة جودة الرقم</label>
                            <label class="toggle-line"><input type="checkbox" name="templates_required_after_window" value="1" <?= !empty($ccWhatsapp['templates_required_after_window']) ? 'checked' : '' ?>> القوالب إلزامية خارج 24 ساعة</label>
                        </div>
                        <div class="status-row">
                            <span>QR: <?= htmlspecialchars($labelText($ccWhatsapp['qr_status'] ?? 'disconnected')) ?></span>
                            <span>Templates: <?= htmlspecialchars($labelText($ccWhatsapp['templates_status'] ?? 'pending')) ?></span>
                            <span>Queue: <?= htmlspecialchars($labelText($ccWhatsapp['queue_status'] ?? 'pending')) ?></span>
                        </div>
                        <button class="primary" type="submit">حفظ إعدادات واتساب</button>
                    </form>
                </section>

                <section class="panel control-section" id="settings-campaigns" data-control-section>
                    <div class="panel-head"><div><h2>الحملات والحدود</h2><span>تحكم في الدفعات، التهدئة، التكرار، QR Safe Mode، وإيقاف الحملات الضعيفة.</span></div></div>
                    <form class="ajax-form control-form" data-endpoint="/api/settings/campaign-limits" data-method="PUT">
                        <div class="control-grid">
                            <label>الحد اليومي للحملات<input name="daily_campaigns" type="number" value="<?= htmlspecialchars((string) ($ccLimits['daily_campaigns'] ?? 20)) ?>"></label>
                            <label>الحد اليومي للرسائل<input name="daily_messages" type="number" value="<?= htmlspecialchars((string) ($ccLimits['daily_messages'] ?? 5000)) ?>"></label>
                            <label>حجم الدفعة<input name="batch_size" type="number" value="<?= htmlspecialchars((string) ($ccLimits['batch_size'] ?? 250)) ?>"></label>
                            <label>الفاصل بين الدفعات بالثواني<input name="batch_interval_seconds" type="number" value="<?= htmlspecialchars((string) ($ccLimits['batch_interval_seconds'] ?? 60)) ?>"></label>
                            <label>الإيقاف عند فشل %<input name="stop_on_failure_rate" type="number" value="<?= htmlspecialchars((string) ($ccLimits['stop_on_failure_rate'] ?? 10)) ?>"></label>
                            <label>تأخير عشوائي<input name="random_delay_seconds" value="<?= htmlspecialchars((string) ($ccLimits['random_delay_seconds'] ?? '35-90')) ?>"></label>
                            <label class="toggle-line"><input type="checkbox" name="retry_failed_messages" value="1" <?= !empty($ccLimits['retry_failed_messages']) ? 'checked' : '' ?>> إعادة محاولة الرسائل الفاشلة</label>
                            <label class="toggle-line"><input type="checkbox" name="deduplicate_recipients" value="1" <?= !empty($ccLimits['deduplicate_recipients']) ? 'checked' : '' ?>> منع التكرار</label>
                            <label class="toggle-line"><input type="checkbox" name="qr_safe_mode" value="1" <?= !empty($ccLimits['qr_safe_mode']) ? 'checked' : '' ?>> وضع QR الآمن</label>
                            <label class="toggle-line"><input type="checkbox" name="cloud_api_enabled" value="1" <?= !empty($ccLimits['cloud_api_enabled']) ? 'checked' : '' ?>> تفعيل Cloud API</label>
                        </div>
                        <button class="primary" type="submit">حفظ حدود الحملات</button>
                    </form>
                </section>

                <section class="panel control-section" id="settings-quick-replies" data-control-section>
                    <div class="panel-head"><div><h2>الردود السريعة</h2><span>ردود جاهزة حسب القسم والموقف داخل Inbox والشات بوت.</span></div></div>
                    <form class="ajax-form control-form" data-endpoint="/api/settings/quick-replies" data-method="PUT">
                        <div class="control-grid">
                            <label>رد الترحيب<textarea name="welcome_reply"><?= htmlspecialchars((string) ($ccReplies['welcome_reply'] ?? '')) ?></textarea></label>
                            <label>رد خارج الدوام<textarea name="away_reply"><?= htmlspecialchars((string) ($ccReplies['away_reply'] ?? '')) ?></textarea></label>
                            <label>رد التحويل<textarea name="handover_reply"><?= htmlspecialchars((string) ($ccReplies['handover_reply'] ?? '')) ?></textarea></label>
                            <label>رد إلغاء الاشتراك<textarea name="unsubscribe_reply"><?= htmlspecialchars((string) ($ccReplies['unsubscribe_reply'] ?? '')) ?></textarea></label>
                            <label>رد الشكاوى<textarea name="complaint_reply"><?= htmlspecialchars((string) ($ccReplies['complaint_reply'] ?? '')) ?></textarea></label>
                            <label>رد المتابعة<textarea name="followup_reply"><?= htmlspecialchars((string) ($ccReplies['followup_reply'] ?? '')) ?></textarea></label>
                        </div>
                        <button class="primary" type="submit">حفظ الردود</button>
                    </form>
                </section>

                <section class="panel control-section" id="settings-users" data-control-section>
                    <div class="panel-head"><div><h2>المستخدمون</h2><span>إضافة وتعطيل المستخدمين، متابعة آخر دخول، الجلسات النشطة والأقسام.</span></div><span class="status-pill active"><?= count((array) $ccUsers) ?> مستخدم</span></div>
                    <form class="ajax-form compact-control-form" data-endpoint="/api/settings/users" data-method="POST">
                        <input name="name" placeholder="اسم المستخدم">
                        <input name="email" type="email" placeholder="البريد الإلكتروني">
                        <input name="password" type="password" placeholder="كلمة مرور مؤقتة">
                        <select name="role"><option value="store_admin">مدير متجر</option><option value="marketing_manager">مدير تسويق</option><option value="support_agent">دعم</option><option value="sales_agent">مبيعات</option><option value="billing_agent">حسابات</option><option value="viewer">مشاهد</option></select>
                        <button class="primary" type="submit">إضافة مستخدم</button>
                    </form>
                    <div class="control-table">
                        <span>المستخدم</span><span>الدور</span><span>الحالة</span><span>آخر دخول</span>
                        <?php foreach (array_slice((array) $ccUsers, 0, 12) as $user): ?>
                            <strong><?= htmlspecialchars((string) ($user['name'] ?? $user['email'] ?? 'مستخدم')) ?><small><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></small></strong>
                            <span><?= htmlspecialchars($labelText($user['role'] ?? 'viewer')) ?></span>
                            <em><?= htmlspecialchars($labelText($user['status'] ?? 'active')) ?></em>
                            <span><?= htmlspecialchars((string) ($user['last_login_at'] ?? 'لم يسجل بعد')) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($ccUsers)): ?><strong>لا يوجد مستخدمون</strong><span>أضف أول مستخدم</span><em>فارغ</em><span>-</span><?php endif; ?>
                    </div>
                </section>

                <section class="panel control-section" id="settings-roles" data-control-section>
                    <div class="panel-head"><div><h2>الأدوار والصلاحيات</h2><span>RBAC كامل مع فصل بيانات المتاجر ومنع تعديل الصلاحيات الحساسة بدون صلاحية.</span></div></div>
                    <div class="permission-layout">
                        <div class="control-card">
                            <h3>الأدوار</h3>
                            <?php foreach ((array) $ccRoles as $role): ?>
                                <div class="setting-row"><strong><?= htmlspecialchars((string) ($role['name'] ?? $role['role_key'] ?? 'Role')) ?></strong><span><?= htmlspecialchars((string) ($role['role_key'] ?? '')) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="control-card">
                            <h3>Permission Matrix</h3>
                            <div class="permission-matrix">
                                <?php foreach ((array) $ccPermissions as $permission): ?>
                                    <span><?= htmlspecialchars((string) ($permission['group'] ?? 'عام')) ?></span>
                                    <strong><?= htmlspecialchars((string) ($permission['label'] ?? $permission['permission_key'] ?? '')) ?></strong>
                                    <code><?= htmlspecialchars((string) ($permission['permission_key'] ?? '')) ?></code>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel control-section" id="settings-companies" data-control-section>
                    <div class="panel-head"><div><h2>الشركات والمتاجر</h2><span>ملفات الشركات، المتاجر التابعة، التوثيق، الدومينات والربط.</span></div></div>
                    <div class="control-split">
                        <div class="control-card"><h3>الشركات</h3><?php foreach (array_slice((array) $ccCompanies, 0, 8) as $company): ?><div class="setting-row"><strong><?= htmlspecialchars((string) ($company['name'] ?? 'شركة')) ?></strong><span><?= htmlspecialchars($labelText($company['verification_status'] ?? 'pending')) ?></span></div><?php endforeach; ?></div>
                        <div class="control-card"><h3>المتاجر</h3><?php foreach (array_slice((array) $ccStores, 0, 8) as $store): ?><div class="setting-row"><strong><?= htmlspecialchars((string) ($store['name'] ?? 'متجر')) ?></strong><span><?= htmlspecialchars((string) ($store['slug'] ?? '')) ?></span><em><?= htmlspecialchars($labelText($store['status'] ?? 'active')) ?></em></div><?php endforeach; ?></div>
                    </div>
                </section>

                <section class="panel control-section" id="settings-departments" data-control-section>
                    <div class="panel-head"><div><h2>الفرق والأقسام</h2><span>قواعد التحويل، SLA، ساعات العمل، Round Robin والحد الأقصى للمحادثات لكل موظف.</span></div></div>
                    <form class="ajax-form compact-control-form" data-endpoint="/api/settings/departments" data-method="POST">
                        <input name="name" placeholder="اسم القسم">
                        <input name="slug" placeholder="slug">
                        <input name="color" value="#2f9b75">
                        <input name="working_hours" value="09:00-18:00">
                        <select name="priority"><option value="normal">عادي</option><option value="high">مرتفع</option><option value="urgent">عاجل</option></select>
                        <button class="primary" type="submit">إضافة قسم</button>
                    </form>
                    <div class="department-grid">
                        <?php foreach ((array) $ccDepartments as $department): ?>
                            <article class="control-card"><span class="department-dot" style="background: <?= htmlspecialchars((string) ($department['color'] ?? '#2f9b75')) ?>"></span><h3><?= htmlspecialchars((string) ($department['name'] ?? 'قسم')) ?></h3><p><?= htmlspecialchars((string) ($department['welcome_message'] ?? 'رسالة ترحيب افتراضية')) ?></p><small><?= htmlspecialchars($labelText($department['priority'] ?? 'normal')) ?> · <?= !empty($department['is_active']) ? 'نشط' : 'معطل' ?></small></article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="panel control-section" id="settings-billing" data-control-section>
                    <div class="panel-head"><div><h2>الاشتراكات والباقات</h2><span>الباقة الحالية، حدود الاستخدام، الفواتير، وسائل الدفع والترقية.</span></div><span class="status-pill active"><?= htmlspecialchars($labelText($ccSubscriptions['current']['plan_key'] ?? 'free')) ?></span></div>
                    <div class="control-metrics">
                        <article><strong><?= htmlspecialchars((string) ($ccSubscriptions['usage']['messages'] ?? 0)) ?></strong><span>رسائل مستخدمة</span></article>
                        <article><strong><?= htmlspecialchars((string) ($ccSubscriptions['usage']['ai_credits'] ?? 0)) ?></strong><span>AI Credits</span></article>
                        <article><strong><?= htmlspecialchars((string) ($ccSubscriptions['usage']['team_members'] ?? 0)) ?></strong><span>أعضاء الفريق</span></article>
                        <article><strong><?= count((array) ($ccSubscriptions['invoices'] ?? [])) ?></strong><span>فواتير</span></article>
                    </div>
                </section>

                <section class="panel control-section" id="settings-security" data-control-section>
                    <div class="panel-head"><div><h2>الأمان والجلسات</h2><span>2FA، سياسة كلمة المرور، CSRF، CSP، مفاتيح التشفير، الجلسات ومحاولات الدخول.</span></div><span class="status-pill <?= !empty($ccSecurity['jwt_secret_present']) && !empty($ccSecurity['encryption_key_present']) ? 'ok' : 'danger-state' ?>">Secrets</span></div>
                    <form class="ajax-form control-form" data-endpoint="/api/settings/security" data-method="PUT">
                        <div class="control-grid">
                            <label>أقل طول لكلمة المرور<input name="password_min_length" type="number" value="<?= htmlspecialchars((string) ($ccSecurity['password_min_length'] ?? 10)) ?>"></label>
                            <label>مهلة الجلسة بالدقائق<input name="session_timeout_minutes" type="number" value="<?= htmlspecialchars((string) ($ccSecurity['session_timeout_minutes'] ?? 120)) ?>"></label>
                            <label>Rate Limit بالدقيقة<input name="rate_limit_per_minute" type="number" value="<?= htmlspecialchars((string) ($ccSecurity['rate_limit_per_minute'] ?? 120)) ?>"></label>
                            <label>IP Whitelist<input name="ip_whitelist" value="<?= htmlspecialchars(implode(', ', (array) ($ccSecurity['ip_whitelist'] ?? []))) ?>"></label>
                            <label class="toggle-line"><input type="checkbox" name="two_factor_required" value="1" <?= !empty($ccSecurity['two_factor_required']) ? 'checked' : '' ?>> تفعيل 2FA إجباري</label>
                            <label class="toggle-line"><input type="checkbox" name="csrf_enforced" value="1" <?= !empty($ccSecurity['csrf_enforced']) ? 'checked' : '' ?>> CSRF مفعل</label>
                            <label class="toggle-line"><input type="checkbox" name="secure_cookies" value="1" <?= !empty($ccSecurity['secure_cookies']) ? 'checked' : '' ?>> Secure Cookies</label>
                            <label class="toggle-line"><input type="checkbox" name="csp_enabled" value="1" <?= !empty($ccSecurity['csp_enabled']) ? 'checked' : '' ?>> CSP مفعل</label>
                        </div>
                        <button class="primary" type="submit">حفظ إعدادات الأمان</button>
                    </form>
                </section>

                <section class="panel control-section" id="settings-developer" data-control-section>
                    <div class="panel-head"><div><h2>Webhooks & API</h2><span>API Keys، Webhook URLs، Verify Tokens، Callback URLs، وسجلات آخر Payload.</span></div></div>
                    <div class="control-split">
                        <form class="ajax-form control-card" data-endpoint="/api/settings/api-keys" data-method="POST"><h3>إنشاء API Key</h3><label>الاسم<input name="name" value="Production API Key"></label><label>Scopes<input name="scopes" value="read:contacts, write:webhooks"></label><button class="primary" type="submit">إنشاء مفتاح</button></form>
                        <form class="ajax-form control-card" data-endpoint="/api/settings/webhooks/test" data-method="POST"><h3>اختبار Webhook</h3><label>الرابط<input name="url" value="<?= htmlspecialchars((string) ($ccWhatsapp['webhook_url'] ?? ($appUrl . '/api/webhooks/whatsapp'))) ?>"></label><button class="primary" type="submit">اختبار Webhook</button></form>
                    </div>
                    <div class="control-table compact">
                        <span>النوع</span><span>الاسم / الرابط</span><span>الحالة</span>
                        <?php foreach (array_slice((array) $ccApiKeys, 0, 5) as $key): ?><strong>API Key</strong><span><?= htmlspecialchars((string) ($key['name'] ?? 'Key')) ?></span><em><?= htmlspecialchars($labelText($key['status'] ?? 'active')) ?></em><?php endforeach; ?>
                        <?php foreach (array_slice((array) $ccWebhooks, 0, 5) as $webhook): ?><strong>Webhook</strong><span><?= htmlspecialchars((string) ($webhook['url'] ?? '')) ?></span><em><?= htmlspecialchars($labelText($webhook['status'] ?? 'active')) ?></em><?php endforeach; ?>
                    </div>
                </section>

                <section class="panel control-section" id="settings-documents" data-control-section>
                    <div class="panel-head"><div><h2>الملفات والمستندات</h2><span>مستندات الشركات، التوثيق، الخصوصية، الشروط، اللوجوهات والمرفقات.</span></div><span class="status-pill active"><?= count((array) $ccDocuments) ?> ملف</span></div>
                    <form class="control-upload-form" action="<?= htmlspecialchars($appUrl) ?>/api/settings/documents/upload" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\MarketingCenter\Support\Security::csrfToken()) ?>">
                        <select name="document_type"><option value="company_registration">السجل التجاري</option><option value="tax_card">البطاقة الضريبية</option><option value="privacy_policy">Privacy Policy</option><option value="terms">Terms & Conditions</option><option value="logo">Logo</option></select>
                        <input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.docx">
                        <button class="primary" type="submit">رفع ملف</button>
                    </form>
                    <div class="control-table compact">
                        <span>الملف</span><span>النوع</span><span>الحالة</span>
                        <?php foreach (array_slice((array) $ccDocuments, 0, 8) as $document): ?><strong><?= htmlspecialchars((string) ($document['file_name'] ?? 'ملف')) ?></strong><span><?= htmlspecialchars((string) ($document['document_type'] ?? 'عام')) ?></span><em><?= htmlspecialchars($labelText($document['reviewed_status'] ?? $document['status'] ?? 'pending')) ?></em><?php endforeach; ?>
                    </div>
                </section>

                <section class="panel control-section" id="settings-notifications" data-control-section>
                    <div class="panel-head"><div><h2>التنبيهات والإشعارات</h2><span>Webhook failures، انخفاض الجودة، فشل الحملات، انتهاء الاشتراك، دخول جديد وأخطاء Queue.</span></div></div>
                    <div class="control-metrics">
                        <?php foreach (($ccNotifications['settings'] ?? []) as $key => $enabled): ?>
                            <article><strong><?= !empty($enabled) ? 'مفعل' : 'معطل' ?></strong><span><?= htmlspecialchars(str_replace('_', ' ', (string) $key)) ?></span></article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="panel control-section" id="settings-logs" data-control-section>
                    <div class="panel-head"><div><h2>السجلات والمراقبة</h2><span>Audit Logs، Login Logs، Campaign Logs، Webhook Logs، Queue Logs، Error Logs وAPI Logs.</span></div></div>
                    <div class="control-split">
                        <div class="control-card"><h3>Audit Logs</h3><?php foreach (array_slice((array) ($ccLogs['audit'] ?? []), 0, 6) as $log): ?><div class="setting-row"><strong><?= htmlspecialchars((string) ($log['action'] ?? 'حدث')) ?></strong><span><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></span></div><?php endforeach; ?></div>
                        <div class="control-card"><h3>Webhook Logs</h3><?php foreach (array_slice((array) ($ccLogs['webhook'] ?? []), 0, 6) as $log): ?><div class="setting-row"><strong><?= htmlspecialchars((string) ($log['event_type'] ?? 'webhook')) ?></strong><span><?= htmlspecialchars((string) ($log['received_at'] ?? '')) ?></span></div><?php endforeach; ?></div>
                    </div>
                </section>

                <section class="panel control-section" id="settings-branding" data-control-section>
                    <div class="panel-head"><div><h2>الهوية والـ White Label</h2><span>الشعار، الألوان، الخطوط، صفحة الدخول، الدومين المخصص، البريد وPWA Theme.</span></div></div>
                    <div class="control-grid readonly-grid">
                        <label>اسم المنتج<input readonly value="<?= htmlspecialchars((string) ($ccBranding['product_name'] ?? 'Marketing Center')) ?>"></label>
                        <label>الشعار<input readonly value="<?= htmlspecialchars((string) ($ccBranding['logo_url'] ?? '')) ?>"></label>
                        <label>اللون الأساسي<input readonly value="<?= htmlspecialchars((string) ($ccBranding['primary_color'] ?? '#334a91')) ?>"></label>
                        <label>الدومين المخصص<input readonly value="<?= htmlspecialchars((string) ($ccBranding['custom_domain'] ?? '')) ?>"></label>
                    </div>
                    <a class="primary" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/saas">فتح إعدادات White Label</a>
                </section>

                <section class="panel control-section" id="settings-ai" data-control-section>
                    <div class="panel-head"><div><h2>إعدادات الذكاء الاصطناعي</h2><span>المزود، الموديل، اللغة، اللهجة، حدود الاستخدام، قواعد السلامة وKnowledge Base.</span></div><span class="status-pill <?= !empty($ccAi['enabled']) ? 'ok' : 'muted' ?>"><?= !empty($ccAi['enabled']) ? 'مفعل' : 'معطل' ?></span></div>
                    <div class="control-grid readonly-grid">
                        <label>Provider<input readonly value="<?= htmlspecialchars((string) ($ccAi['provider'] ?? 'openai')) ?>"></label>
                        <label>Model<input readonly value="<?= htmlspecialchars((string) ($ccAi['model'] ?? 'gpt-4o-mini')) ?>"></label>
                        <label>لغة الرد<input readonly value="<?= htmlspecialchars((string) ($ccAi['language'] ?? 'ar')) ?>"></label>
                        <label>لهجة الرد<input readonly value="<?= htmlspecialchars((string) ($ccAi['tone'] ?? 'professional')) ?>"></label>
                    </div>
                    <a class="primary" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/chatbot-builder#chatbotAiAssistant">فتح إعدادات AI</a>
                </section>

                <section class="panel control-section" id="settings-backup" data-control-section>
                    <div class="panel-head"><div><h2>النسخ الاحتياطي والاستعادة</h2><span>Backup Database، Backup Files، Restore Point، Retention Policy، Export وImport.</span></div></div>
                    <div class="control-metrics">
                        <article><strong><?= count((array) ($ccBackup['jobs'] ?? [])) ?></strong><span>عمليات نسخ</span></article>
                        <article><strong><?= !empty($ccBackup['schedule']['enabled']) ? 'مفعل' : 'معطل' ?></strong><span>الجدولة</span></article>
                        <article><strong><?= htmlspecialchars((string) ($ccBackup['schedule']['retention_days'] ?? 30)) ?></strong><span>أيام الاحتفاظ</span></article>
                    </div>
                </section>

                <section class="panel control-section" id="settings-launch" data-control-section>
                    <div class="panel-head"><div><h2>إعدادات الإطلاق</h2><span>Environment، Security، Database، Queue، Webhook، Build، Production Mode.</span></div><span class="status-pill <?= (int) ($ccLaunch['score'] ?? 0) >= 80 ? 'ok' : 'pending' ?>"><?= (int) ($ccLaunch['score'] ?? 0) ?>%</span></div>
                    <div class="launch-score-card"><strong><?= htmlspecialchars((string) ($ccLaunch['status'] ?? 'غير جاهز')) ?></strong><div class="progress"><span style="width: <?= max(0, min(100, (int) ($ccLaunch['score'] ?? 0))) ?>%"></span></div></div>
                    <a class="primary" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/setup-checklist">فتح جاهزية الإطلاق</a>
                </section>
            </div>
        </section>
        <div class="settings-save-bar" aria-live="polite">
            <span>لديك تغييرات غير محفوظة داخل مركز التحكم.</span>
            <button class="primary settings-save-trigger" type="button">حفظ الآن</button>
            <button class="ghost-btn settings-discard" type="button">تجاهل</button>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<nav class="bottom-nav" aria-label="تنقل الجوال">
    <?php
    $mobileNav = [
        'overview' => ['label' => 'الرئيسية', 'icon' => '⌂'],
        'inbox' => ['label' => 'المحادثات', 'icon' => 'IN', 'badge' => '3'],
        'chatbot-builder' => ['label' => 'البوت', 'icon' => 'BOT'],
        'campaign-builder' => ['label' => 'الحملات', 'icon' => 'CA'],
        'contacts' => ['label' => 'العملاء', 'icon' => 'CRM'],
    ];
    ?>
    <?php foreach ($mobileNav as $key => $item): ?>
        <a class="<?= $page === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($appUrl) ?>/marketing-center/<?= $key ?>" <?= isset($item['badge']) ? 'data-badge="' . htmlspecialchars($item['badge']) . '"' : '' ?>><span><?= htmlspecialchars($item['icon']) ?></span><?= htmlspecialchars($item['label']) ?></a>
    <?php endforeach; ?>
    <button class="mobile-more" id="mobileMore" type="button"><span>•••</span>المزيد</button>
</nav>

<button class="mobile-install-app" data-install-pwa type="button">تثبيت التطبيق</button>
<button class="fab" id="aiFab" type="button">AI</button>
<section class="ai-widget" id="aiWidget">
    <div class="panel-head"><div><h2>المساعد الذكي</h2><span>مساعد التسويق الذكي</span></div><button class="ghost-btn" id="closeAi" type="button">إغلاق</button></div>
    <p>أستطيع اقتراح قالب، تحليل حملة، أو تجهيز رد سريع حسب بيانات العملاء والحملات.</p>
    <div class="quick-prompts"><button>اقترح حملة</button><button>حلل الأداء</button><button>اكتب رد</button></div>
</section>

<section class="command-palette" id="commandPalette" aria-hidden="true">
    <div class="command-box">
        <input id="commandSearch" placeholder="ابحث عن صفحة أو اكتب أمراً...">
        <div class="command-results">
            <?php foreach ($nav as $key => $item): ?>
                <a href="<?= htmlspecialchars($appUrl) ?>/marketing-center/<?= $key ?>"><span><?= htmlspecialchars($item['icon']) ?></span><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div id="toast"></div>
<script>
window.MC_APP_URL = <?= json_encode($appUrl) ?>;
window.MC_CSRF_TOKEN = <?= json_encode(\MarketingCenter\Support\Security::csrfToken()) ?>;
</script>
<script src="<?= htmlspecialchars($appUrl) ?>/assets/app.js"></script>
</body>
</html>
