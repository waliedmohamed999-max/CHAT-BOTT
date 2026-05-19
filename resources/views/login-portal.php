<?php
$scriptBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (isset($_GET['__mc_path'])) {
    $scriptBase = '';
}
$appUrl = rtrim(\MarketingCenter\Support\Env::get('APP_URL', $scriptBase), '/');
$assetVersion = '20260519-premium-brand-v47';
$csrfToken = \MarketingCenter\Support\Security::csrfToken();
$config = $config ?? [];
$portal = (string) ($config['portal'] ?? 'store');
$accent = (string) ($config['accent'] ?? '#334a91');
$secondary = (string) ($config['secondary'] ?? '#5aa9b8');
?>
<!doctype html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title><?= htmlspecialchars((string) ($config['title'] ?? 'تسجيل الدخول')) ?> - Marketing Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/app.css?v=<?= htmlspecialchars($assetVersion) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/brand.css?v=<?= htmlspecialchars($assetVersion) ?>">
    <style>
        :root { --portal-accent: <?= htmlspecialchars($accent) ?>; --portal-secondary: <?= htmlspecialchars($secondary) ?>; }
        <?php if (!empty($config['background_url'])): ?>
        .portal-auth-body::before { background-image: linear-gradient(120deg, rgba(15,23,42,.62), rgba(15,23,42,.28)), url("<?= htmlspecialchars((string) $config['background_url']) ?>"); }
        <?php endif; ?>
    </style>
</head>
<body class="portal-auth-body portal-<?= htmlspecialchars($portal) ?>">
    <main class="portal-auth-shell">
        <section class="portal-auth-visual">
            <div class="portal-brand-orb">
                <?php if (!empty($config['logo_url'])): ?>
                    <img src="<?= htmlspecialchars((string) $config['logo_url']) ?>" alt="">
                <?php else: ?>
                    <span><?= htmlspecialchars((string) ($config['logo'] ?? 'MC')) ?></span>
                <?php endif; ?>
            </div>
            <span class="premium-pill"><?= htmlspecialchars((string) ($config['badge'] ?? 'Secure Login')) ?></span>
            <h1><?= htmlspecialchars((string) ($config['title'] ?? 'تسجيل الدخول')) ?></h1>
            <p><?= htmlspecialchars((string) ($config['subtitle'] ?? 'بوابة دخول آمنة متعددة المستأجرين.')) ?></p>
            <div class="portal-security-grid">
                <article><b>عزل المستأجر</b><span>كل جلسة مرتبطة بالمتجر والدور.</span></article>
                <article><b>تتبع الدخول</b><span>IP، الجهاز، ومحاولات الدخول.</span></article>
                <article><b>صلاحيات دقيقة</b><span>منع الخلط بين المنصة والمتاجر والموظفين.</span></article>
            </div>
        </section>

        <section class="portal-auth-card">
            <div class="brand auth-brand">
                <span class="brand-mark"><?= htmlspecialchars((string) ($config['logo'] ?? 'MC')) ?></span>
                <div class="brand-copy">
                    <strong><?= htmlspecialchars((string) ($config['store_name'] ?? 'Marketing Center')) ?></strong>
                    <small><?= htmlspecialchars((string) ($config['title'] ?? 'تسجيل الدخول')) ?></small>
                </div>
            </div>

            <form class="portal-login-form" id="portalLoginForm" data-endpoint="<?= htmlspecialchars($appUrl . (string) ($config['endpoint'] ?? '/api/auth/store/login')) ?>">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <?php if (!empty($config['slug'])): ?>
                    <input type="hidden" name="tenant_slug" value="<?= htmlspecialchars((string) $config['slug']) ?>">
                <?php endif; ?>
                <label>البريد الإلكتروني
                    <input name="email" type="email" autocomplete="email" required placeholder="user@example.com">
                </label>
                <label>كلمة المرور
                    <input name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
                </label>
                <?php if (!empty($config['show_store_code'])): ?>
                    <label>كود المتجر أو الرابط
                        <input name="store_code" autocomplete="organization" placeholder="main-store">
                    </label>
                    <div class="agent-duty-state">
                        <span>حالة الدوام</span>
                        <strong>جاهز لاستقبال المحادثات</strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($config['show_store_select'])): ?>
                    <label>اختيار المتجر
                        <input name="store_code" autocomplete="organization" placeholder="اتركه فارغاً لاختيار آخر متجر مصرح">
                    </label>
                <?php endif; ?>
                <div class="auth-inline-row">
                    <label><input name="remember" type="checkbox" value="1"> تذكرني</label>
                    <button class="link-button forgot-password-trigger" type="button">نسيت كلمة المرور؟</button>
                </div>
                <div class="twofa-box" hidden>
                    <label>رمز التحقق الثنائي
                        <input name="two_factor_code" inputmode="numeric" placeholder="000000">
                    </label>
                </div>
                <button class="primary portal-submit" type="submit">تسجيل الدخول</button>
                <p class="auth-error" id="portalLoginError" role="alert"></p>
                <p class="auth-success" id="portalLoginSuccess"></p>
                <?php if (!empty($config['allow_register'])): ?>
                    <a class="ghost-btn portal-register-link" href="<?= htmlspecialchars($appUrl) ?>/store/register">إنشاء حساب متجر جديد</a>
                <?php endif; ?>
            </form>

            <div class="auth-security-note">
                <b>تنبيه أمني</b>
                <span><?= htmlspecialchars((string) ($config['security_note'] ?? 'يتم تسجيل كل محاولة دخول ومراجعة الصلاحيات قبل إنشاء الجلسة.')) ?></span>
            </div>

            <?php if ($portal === 'tenant'): ?>
                <div class="tenant-login-links">
                    <a href="<?= htmlspecialchars((string) ($config['support_url'] ?? '#')) ?>">الدعم</a>
                    <a href="<?= htmlspecialchars((string) ($config['privacy_url'] ?? '#')) ?>">الخصوصية</a>
                    <a href="<?= htmlspecialchars((string) ($config['terms_url'] ?? '#')) ?>">الشروط</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div id="toast"></div>
    <script>
    const form = document.getElementById('portalLoginForm');
    const errorBox = document.getElementById('portalLoginError');
    const successBox = document.getElementById('portalLoginSuccess');
    const submit = form.querySelector('.portal-submit');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.textContent = '';
        successBox.textContent = '';
        submit.classList.add('loading');
        const formData = new FormData(form);
        const response = await fetch(form.dataset.endpoint, {
            method: 'POST',
            headers: {'X-CSRF-Token': '<?= htmlspecialchars($csrfToken) ?>'},
            body: formData
        });
        const payload = await response.json().catch(() => ({}));
        submit.classList.remove('loading');
        if (!response.ok) {
            const messages = {
                invalid_credentials: 'بيانات الدخول غير صحيحة.',
                portal_forbidden: 'هذا الحساب غير مسموح له باستخدام هذه البوابة.',
                account_disabled: 'هذا الحساب معطل. تواصل مع الإدارة.',
                account_temporarily_locked: 'تم قفل المحاولة مؤقتاً بسبب محاولات فاشلة متكررة.'
            };
            errorBox.textContent = messages[payload.error] || payload.message || 'تعذر تسجيل الدخول.';
            return;
        }
        if (payload.data?.requires_2fa) {
            document.querySelector('.twofa-box').hidden = false;
            successBox.textContent = 'أدخل رمز التحقق الثنائي لإكمال الدخول.';
            return;
        }
        successBox.textContent = 'تم تسجيل الدخول بنجاح. جاري التحويل...';
        window.location.href = '<?= htmlspecialchars($appUrl) ?>' + (payload.data?.redirect || '/marketing-center');
    });

    document.querySelector('.forgot-password-trigger')?.addEventListener('click', async () => {
        errorBox.textContent = '';
        const email = form.email.value.trim();
        if (!email) {
            errorBox.textContent = 'اكتب البريد الإلكتروني أولاً.';
            return;
        }
        await fetch('<?= htmlspecialchars($appUrl) ?>/api/auth/forgot-password', {
            method: 'POST',
            headers: {'X-CSRF-Token': '<?= htmlspecialchars($csrfToken) ?>'},
            body: new URLSearchParams({email, portal: '<?= htmlspecialchars($portal) ?>'})
        });
        successBox.textContent = 'إذا كان البريد مسجلاً سيتم إرسال رابط إعادة التعيين.';
    });
    </script>
</body>
</html>
