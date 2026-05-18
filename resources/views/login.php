<?php
$scriptBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (isset($_GET['__mc_path'])) {
    $scriptBase = '';
}
$appUrl = rtrim(\MarketingCenter\Support\Env::get('APP_URL', $scriptBase), '/');
$assetVersion = '20260519-premium-brand-v25';
$csrfToken = \MarketingCenter\Support\Security::csrfToken();
?>
<!doctype html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>تسجيل الدخول - مركز التسويق</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/app.css?v=<?= htmlspecialchars($assetVersion) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/brand.css?v=<?= htmlspecialchars($assetVersion) ?>">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand auth-brand">
                <span class="brand-mark">MC</span>
                <div class="brand-copy">
                    <strong>مركز التسويق</strong>
                    <small>تسجيل دخول آمن للوحة التحكم</small>
                </div>
            </div>
            <div class="auth-copy">
                <span class="premium-pill">Core System Foundation</span>
                <h1>ادخل إلى مساحة العمل</h1>
                <p>يتم تسجيل كل محاولة دخول، وتطبيق الصلاحيات حسب الدور والمتجر النشط.</p>
            </div>
            <form class="auth-form" id="loginForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label>البريد الإلكتروني
                    <input name="email" type="email" autocomplete="email" required placeholder="admin@example.com">
                </label>
                <label>كلمة المرور
                    <input name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
                </label>
                <button class="primary" type="submit">تسجيل الدخول</button>
                <p class="auth-error" id="loginError" role="alert"></p>
            </form>
            <div class="auth-security-note">
                <b>حماية الجلسة</b>
                <span>Session Cookie آمن، CSRF Token، Rate Limit، وسجل تدقيق لكل عملية دخول.</span>
            </div>
        </section>
    </main>
    <script>
    const form = document.getElementById('loginForm');
    const errorBox = document.getElementById('loginError');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.textContent = '';
        const formData = new FormData(form);
        const response = await fetch('<?= htmlspecialchars($appUrl) ?>/api/auth/login', {
            method: 'POST',
            headers: {'X-CSRF-Token': '<?= htmlspecialchars($csrfToken) ?>'},
            body: formData
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = {
                invalid_credentials: 'بيانات الدخول غير صحيحة.',
                auth_database_unavailable: 'قاعدة البيانات غير متاحة حالياً. فعّل DATABASE_URL أو وضع الدخول التجريبي.',
            };
            errorBox.textContent = messages[payload.error] || payload.message || 'تعذر تسجيل الدخول. تأكد من البيانات.';
            return;
        }
        window.location.href = '<?= htmlspecialchars($appUrl) ?>/marketing-center';
    });
    </script>
</body>
</html>
