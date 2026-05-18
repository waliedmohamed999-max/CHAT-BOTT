const root = document.documentElement;
const body = document.body;
const toast = document.getElementById('toast');
const commandPalette = document.getElementById('commandPalette');
const commandSearch = document.getElementById('commandSearch');
const aiWidget = document.getElementById('aiWidget');
const sidebar = document.getElementById('sidebar');
const drawerBackdrop = document.getElementById('mobileDrawerBackdrop');
const nativeFetch = window.fetch.bind(window);

function syncViewportHeight() {
    root.style.setProperty('--app-vh', `${window.innerHeight * 0.01}px`);
}

syncViewportHeight();
window.addEventListener('resize', syncViewportHeight, {passive: true});

function currentCsrfToken() {
    return window.MC_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

window.fetch = (input, init = {}) => {
    const method = String(init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
        if (!headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', currentCsrfToken());
        }
        init = {...init, headers};
    }

    return nativeFetch(input, init);
};

function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
}

function arabicSessionStatus(status) {
    return ({
        waiting_for_scan: 'بانتظار المسح',
        qr_scanned: 'تم مسح الباركود',
        authenticating: 'جاري التحقق',
        connected: 'متصل',
        disconnected: 'غير متصل',
        expired: 'منتهي',
        pending: 'قيد الانتظار',
    })[status] || 'غير متصل';
}

function openCommandPalette() {
    commandPalette?.classList.add('open');
    commandPalette?.setAttribute('aria-hidden', 'false');
    setTimeout(() => commandSearch?.focus(), 30);
}

function closeCommandPalette() {
    commandPalette?.classList.remove('open');
    commandPalette?.setAttribute('aria-hidden', 'true');
}

function openMobileDrawer() {
    sidebar?.classList.add('open');
    body.classList.add('mobile-drawer-open');
}

function closeMobileDrawer() {
    sidebar?.classList.remove('open');
    body.classList.remove('mobile-drawer-open');
}

document.getElementById('themeToggle')?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('marketing_center_theme', root.dataset.theme);
});

root.dataset.theme = localStorage.getItem('marketing_center_theme') || 'light';

document.getElementById('openCommand')?.addEventListener('click', openCommandPalette);
commandPalette?.addEventListener('click', (event) => {
    if (event.target === commandPalette) closeCommandPalette();
});

document.addEventListener('keydown', (event) => {
    const isCommand = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';
    if (isCommand) {
        event.preventDefault();
        openCommandPalette();
    }
    if (event.key === 'Escape') {
        closeCommandPalette();
        closeMobileDrawer();
        aiWidget?.classList.remove('open');
    }
});

commandSearch?.addEventListener('input', () => {
    const query = commandSearch.value.trim().toLowerCase();
    document.querySelectorAll('.command-results a').forEach((item) => {
        item.style.display = item.textContent.toLowerCase().includes(query) ? 'flex' : 'none';
    });
});

document.getElementById('collapseSidebar')?.addEventListener('click', () => {
    sidebar?.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('marketing_center_sidebar', sidebar?.classList.contains('collapsed') ? 'collapsed' : 'open');
});

document.querySelectorAll('[data-mobile-menu], #mobileMenu').forEach((button) => {
    button.addEventListener('click', () => {
        if (sidebar?.classList.contains('open')) {
            closeMobileDrawer();
            return;
        }
        openMobileDrawer();
    });
});

document.querySelectorAll('.side-nav a').forEach((link) => {
    link.addEventListener('click', closeMobileDrawer);
});

drawerBackdrop?.addEventListener('click', closeMobileDrawer);
document.getElementById('mobileSearch')?.addEventListener('click', openCommandPalette);
document.getElementById('mobileMore')?.addEventListener('click', openCommandPalette);

let touchStartX = 0;
let touchStartY = 0;
let edgeSwipeStarted = false;

window.addEventListener('touchstart', (event) => {
    const touch = event.touches[0];
    if (!touch || window.innerWidth > 760) return;
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    edgeSwipeStarted = touchStartX > window.innerWidth - 28;
}, {passive: true});

window.addEventListener('touchend', (event) => {
    if (window.innerWidth > 760) return;
    const touch = event.changedTouches[0];
    if (!touch) return;
    const deltaX = touch.clientX - touchStartX;
    const deltaY = Math.abs(touch.clientY - touchStartY);

    if (edgeSwipeStarted && deltaX < -48 && deltaY < 70) {
        openMobileDrawer();
    }

    if (body.classList.contains('mobile-drawer-open') && deltaX > 56 && deltaY < 70) {
        closeMobileDrawer();
    }

    edgeSwipeStarted = false;
}, {passive: true});

if (localStorage.getItem('marketing_center_sidebar') === 'collapsed') {
    sidebar?.classList.add('collapsed');
    body.classList.add('sidebar-collapsed');
}

document.getElementById('aiFab')?.addEventListener('click', () => aiWidget?.classList.toggle('open'));
document.getElementById('closeAi')?.addEventListener('click', () => aiWidget?.classList.remove('open'));

function addMobileTableLabels() {
    document.querySelectorAll('.data-table, .table-like').forEach((table) => {
        const headers = Array.from(table.querySelectorAll(':scope > b')).map((header) => header.textContent.trim());
        if (!headers.length) return;

        Array.from(table.children).forEach((cell) => {
            if (cell.tagName === 'B') return;
            const cells = Array.from(table.children).filter((item) => item.tagName !== 'B');
            const index = cells.indexOf(cell);
            const header = headers[index % headers.length] || '';
            cell.dataset.label = header;
            cell.classList.toggle('mobile-row-start', index % headers.length === 0);
            cell.classList.toggle('mobile-row-end', index % headers.length === headers.length - 1);
        });
    });
}

addMobileTableLabels();

let installPromptEvent = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPromptEvent = event;
    body.classList.add('pwa-install-ready');
});

document.addEventListener('click', async (event) => {
    const installButton = event.target.closest('[data-install-pwa]');
    if (!installButton || !installPromptEvent) return;
    installPromptEvent.prompt();
    await installPromptEvent.userChoice.catch(() => null);
    installPromptEvent = null;
    body.classList.remove('pwa-install-ready');
});

window.addEventListener('appinstalled', () => {
    installPromptEvent = null;
    body.classList.remove('pwa-install-ready');
    showToast('تم تثبيت التطبيق بنجاح');
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(`${window.MC_APP_URL || ''}/sw.js`).catch(() => {});
    });
}

document.querySelectorAll('.counter').forEach((counter) => {
    const target = Number(counter.dataset.count || counter.textContent.replace(/[^\d]/g, '') || 0);
    const duration = 700;
    const start = performance.now();
    const formatter = new Intl.NumberFormat('ar');

    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const value = Math.round(target * (1 - Math.pow(1 - progress, 3)));
        counter.textContent = formatter.format(value);
        if (progress < 1) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
});

document.querySelectorAll('.ajax-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const endpoint = `${window.MC_APP_URL || ''}${form.dataset.endpoint}`;
        const bodyData = Object.fromEntries(new FormData(form).entries());
        form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            bodyData[checkbox.name] = checkbox.checked ? '1' : '0';
        });
        const submitter = form.querySelector('button[type="submit"], button.primary');
        submitter?.classList.add('loading');

        try {
            const response = await fetch(endpoint, {
                method: form.dataset.method || 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': currentCsrfToken()},
                body: JSON.stringify(bodyData),
            });
            const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? 'تم الحفظ بنجاح' : (payload.message || payload.detail || payload.error || 'فشل تنفيذ الطلب'));
            if (response.ok) {
                document.querySelector('.settings-save-bar')?.classList.remove('show');
            }
        } catch {
            showToast('تعذر الاتصال بالخادم');
        } finally {
            submitter?.classList.remove('loading');
        }
    });
});

document.querySelectorAll('.api-post').forEach((button) => {
    button.addEventListener('click', async () => {
        button.classList.add('loading');
        try {
            const response = await fetch(`${window.MC_APP_URL || ''}${button.dataset.api}`, {method: 'POST', headers: {'X-CSRF-Token': currentCsrfToken()}});
            const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? 'تم تنفيذ العملية' : (payload.message || payload.detail || payload.error || 'فشلت العملية'));
        } catch {
            showToast('تعذر الاتصال بالخادم');
        } finally {
            button.classList.remove('loading');
        }
    });
});

const settingsSaveBar = document.querySelector('.settings-save-bar');
document.querySelectorAll('.control-center-layout input, .control-center-layout select, .control-center-layout textarea').forEach((field) => {
    field.addEventListener('input', () => settingsSaveBar?.classList.add('show'));
    field.addEventListener('change', () => settingsSaveBar?.classList.add('show'));
});
document.querySelectorAll('.settings-save-trigger').forEach((button) => {
    button.addEventListener('click', () => {
        const visibleForm = Array.from(document.querySelectorAll('.control-section .ajax-form')).find((form) => {
            const section = form.closest('.control-section');
            return section && section.getBoundingClientRect().top >= -40;
        });
        visibleForm?.requestSubmit();
        if (!visibleForm) showToast('اختر قسماً يحتوي على نموذج حفظ');
    });
});
document.querySelector('.settings-discard')?.addEventListener('click', () => {
    settingsSaveBar?.classList.remove('show');
    showToast('تم تجاهل مؤشر التغييرات');
});
document.querySelector('.control-search')?.addEventListener('input', (event) => {
    const term = event.target.value.trim().toLowerCase();
    document.querySelectorAll('[data-control-section]').forEach((section) => {
        section.style.display = !term || section.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
document.querySelectorAll('[data-control-link]').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelectorAll('[data-control-link]').forEach((item) => item.classList.remove('active'));
        link.classList.add('active');
    });
});
document.querySelector('.control-test-settings')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.classList.add('loading');
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}${button.dataset.api || '/api/settings/health'}`);
        const payload = await response.json().catch(() => ({}));
        showToast(response.ok ? 'تم اختبار الإعدادات بنجاح' : (payload.message || payload.error || 'فشل اختبار الإعدادات'));
    } catch {
        showToast('تعذر اختبار الإعدادات');
    } finally {
        button.classList.remove('loading');
    }
});
document.querySelector('.control-export-report')?.addEventListener('click', async () => {
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}/api/settings/overview`);
        const payload = await response.json();
        const blob = new Blob([JSON.stringify(payload.data || payload, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `platform-control-center-${Date.now()}.json`;
        link.click();
        URL.revokeObjectURL(url);
        showToast('تم تجهيز تقرير الإعدادات');
    } catch {
        showToast('تعذر تصدير التقرير');
    }
});
document.querySelector('.control-safe-mode')?.addEventListener('click', () => {
    document.body.classList.toggle('safe-mode');
    showToast('تم تبديل الوضع الآمن للواجهة');
});
document.querySelectorAll('.control-upload-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitter = form.querySelector('button[type="submit"]');
        submitter?.classList.add('loading');
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form)});
            const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? 'تم رفع الملف بنجاح' : (payload.message || payload.error || 'تعذر رفع الملف'));
        } catch {
            showToast('تعذر رفع الملف');
        } finally {
            submitter?.classList.remove('loading');
        }
    });
});

async function runDevelopmentExecution(endpoint, body, button) {
    button?.classList.add('loading');
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}${endpoint}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body || {}),
        });
        const payload = await response.json().catch(() => ({}));
        const message = response.ok
            ? (payload.message || payload.data?.message || 'تم تنفيذ مهمة التطوير بنجاح')
            : (payload.message || payload.detail || payload.error || payload.data?.message || 'تعذر تنفيذ مهمة التطوير');
        showToast(message);
        if (response.ok) {
            window.setTimeout(() => window.location.reload(), 650);
        }
    } catch {
        showToast('تعذر الاتصال بمحرك التطوير');
    } finally {
        button?.classList.remove('loading');
    }
}

document.querySelectorAll('.dev-exec-run').forEach((button) => {
    button.addEventListener('click', () => {
        const taskKey = button.dataset.taskKey;
        if (!taskKey) {
            showToast('لم يتم تحديد مهمة صالحة');
            return;
        }
        runDevelopmentExecution('/api/development-execution/tasks/run', {task_key: taskKey}, button);
    });
});

document.querySelector('.dev-exec-run-all')?.addEventListener('click', (event) => {
    runDevelopmentExecution('/api/development-execution/tasks/run-all', {}, event.currentTarget);
});

document.querySelectorAll('[data-scroll-target]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector(button.dataset.scrollTarget || '')?.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
});

document.getElementById('refreshKnowledgeBase')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const container = document.getElementById('knowledgeCards');
    button.classList.add('loading');
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/knowledge-base`);
        const payload = await response.json().catch(() => ({}));
        const rows = Array.isArray(payload.data) ? payload.data : [];
        if (container && rows.length) {
            container.innerHTML = rows.slice(0, 6).map((item) => `<article><b>${escapeHtml(item.title || item.question || 'معلومة')}</b><span>${escapeHtml(item.category || 'knowledge')}</span></article>`).join('');
        }
        if (container && !rows.length) {
            container.innerHTML = '<article><b>لا توجد عناصر معرفة بعد</b><span>أضف سؤالاً أو ارفع ملف معرفة ليستخدمه AI في الردود.</span></article>';
        }
        showToast(response.ok ? 'تم تحديث قاعدة المعرفة' : (payload.message || payload.error || 'تعذر تحديث قاعدة المعرفة'));
    } catch {
        showToast('تعذر الاتصال بالخادم');
    } finally {
        button.classList.remove('loading');
    }
});

document.querySelector('.run-chatbot-diagnostics')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const score = document.getElementById('chatbotDiagnosticsScore');
    const results = document.getElementById('chatbotDiagnosticsResults');
    button.classList.add('loading');
    if (score) {
        score.className = 'status-pill pending';
        score.textContent = 'جاري الفحص...';
    }
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/diagnostics/routes`);
        const payload = await response.json().catch(() => ({}));
        const data = payload.data || {};
        if (score) {
            score.className = `status-pill ${data.ready ? 'ok' : 'pending'}`;
            score.textContent = `${data.score || 0}%`;
        }
        const rows = [...(data.routes || []), ...(data.intents || [])];
        if (results) {
            results.innerHTML = rows.map((item) => {
                const title = item.label || item.message || 'اختبار';
                const meta = item.label
                    ? `${item.action || 'غير معروف'} / ${item.department || 'بدون قسم'}`
                    : `${item.intent || 'غير معروف'} / ${item.department || 'بدون قسم'}${item.needs_human ? ' / تحويل لموظف' : ''}`;
                return `<article class="${item.passed ? 'passed' : 'failed'}"><b>${escapeHtml(item.passed ? '✓ ' : '! ')}${escapeHtml(title)}</b><span>${escapeHtml(meta)}</span></article>`;
            }).join('');
        }
        showToast(data.ready ? 'تشخيص البوت مكتمل وجاهز' : 'تشخيص البوت يحتاج مراجعة');
    } catch {
        if (score) {
            score.className = 'status-pill pending';
            score.textContent = 'فشل الفحص';
        }
        showToast('تعذر تشغيل تشخيص البوت');
    } finally {
        button.classList.remove('loading');
    }
});

async function refreshQrSession() {
    const qrBox = document.getElementById('qrBox');
    const statusPill = document.getElementById('qrStatusPill');
    const statusText = document.getElementById('qrStatusText');
    if (!qrBox && !statusPill && !statusText) return;

    try {
        const response = await fetch(`${window.MC_APP_URL || ''}/api/whatsapp-qr/session/status`);
        const payload = await response.json();
        const data = payload.data || {};
        if (statusPill) {
            statusPill.textContent = arabicSessionStatus(data.session_status || 'disconnected');
            statusPill.classList.toggle('ok', data.session_status === 'connected');
            statusPill.classList.toggle('pending', data.session_status !== 'connected');
        }
        if (statusText) statusText.textContent = arabicSessionStatus(data.session_status || 'disconnected');
        if (qrBox && data.last_qr_code && !qrBox.querySelector('img')) {
            qrBox.innerHTML = `<img src="${data.last_qr_code}" alt="WhatsApp QR Code">`;
        }
    } catch {
        // QR bridge can be offline during setup; keep UI stable.
    }
}

if (document.getElementById('qrBox')) {
    refreshQrSession();
    setInterval(refreshQrSession, 5000);
}

document.querySelectorAll('[data-wizard-tabs] button').forEach((button) => {
    button.addEventListener('click', () => {
        const step = button.dataset.step;
        document.querySelectorAll('[data-wizard-tabs] button').forEach((item) => item.classList.toggle('active', item === button));
        document.querySelectorAll('[data-step-panel]').forEach((panel) => panel.classList.toggle('active', panel.dataset.stepPanel === step));
    });
});

document.querySelectorAll('.setup-method').forEach((button) => {
    button.addEventListener('click', async () => {
        button.classList.add('loading');
        try {
            const response = await fetch(`${window.MC_APP_URL || ''}/api/whatsapp-setup/method/select`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({method: button.dataset.method}),
            });
            const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? 'تم اختيار طريقة الربط' : (payload.message || 'فشل اختيار طريقة الربط'));
        } finally {
            button.classList.remove('loading');
        }
    });
});

document.querySelector('.setup-profile-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        data[checkbox.name] = checkbox.checked ? 1 : 0;
    });
    const response = await fetch(`${window.MC_APP_URL || ''}/api/whatsapp-setup/profile`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ بيانات النشاط' : (payload.message || 'تعذر حفظ البيانات'));
});

document.querySelectorAll('.setup-upload-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button');
        button?.classList.add('loading');
        try {
            const response = await fetch(`${window.MC_APP_URL || ''}/api/whatsapp-setup/documents/upload`, {
                method: 'POST',
                body: new FormData(form),
            });
            const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? 'تم رفع الملف' : (payload.message || payload.error || 'فشل رفع الملف'));
        } finally {
            button?.classList.remove('loading');
        }
    });
});

document.querySelector('.setup-test-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/whatsapp-setup/test/send-message`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم تسجيل اختبار الإرسال' : (payload.message || 'فشل الاختبار'));
});

document.querySelector('.automation-trigger-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/automation-revenue/trigger`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
            showToast(response.ok ? `تم تشغيل الأتمتة: ${payload.queued || 0} مسار` : (payload.message || payload.error || 'تعذر تشغيل الأتمتة'));
});

document.querySelector('.workspace-switch-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/saas/switch-workspace`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم تبديل مساحة العمل' : (payload.message || payload.error || 'تعذر تبديل مساحة العمل'));
    if (response.ok) setTimeout(() => window.location.reload(), 500);
});

document.querySelector('.team-invite-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/saas/team/invite`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم إرسال دعوة العضو' : (payload.message || payload.error || 'تعذر إرسال الدعوة'));
});

document.querySelector('.payment-gateway-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    data.test_mode = form.querySelector('[name="test_mode"]')?.checked ? 1 : 0;
    const response = await fetch(`${window.MC_APP_URL || ''}/api/saas/payment-gateways`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ بوابة الدفع' : (payload.message || payload.error || 'تعذر حفظ بوابة الدفع'));
});

document.querySelector('.white-label-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/saas/white-label`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ الهوية البيضاء' : (payload.message || payload.error || 'تعذر حفظ الهوية'));
});

document.querySelector('.developer-api-key-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/developer/api-keys`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? `تم إنشاء المفتاح: ${payload.data?.api_key || 'راجع الاستجابة'}` : (payload.message || payload.error || 'تعذر إنشاء API Key'));
});

document.querySelector('.developer-webhook-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(event.currentTarget).entries());
    formData.events = String(formData.events || '').split(',').map((item) => item.trim()).filter(Boolean);
    const response = await fetch(`${window.MC_APP_URL || ''}/api/developer/webhooks`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم تسجيل Webhook' : (payload.message || payload.error || 'تعذر تسجيل Webhook'));
});

document.querySelector('.marketplace-api-key')?.addEventListener('click', () => {
    document.querySelector('.developer-api-key-form input[name="name"]')?.focus();
});

document.querySelector('.marketplace-oauth-app')?.addEventListener('click', async () => {
    const response = await fetch(`${window.MC_APP_URL || ''}/api/developer/oauth-apps`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({name: 'تطبيق OAuth للمتجر', scopes: ['read:profile', 'read:contacts'], redirect_uris: []}),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? `OAuth App: ${payload.data?.client_id || 'تم الإنشاء'}` : (payload.message || payload.error || 'تعذر إنشاء OAuth App'));
});

document.querySelector('.enterprise-security-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    ['sso_enabled', 'saml_enabled', 'oauth_enterprise_enabled', 'soc2_ready', 'gdpr_enabled'].forEach((key) => {
        data[key] = form.querySelector(`[name="${key}"]`)?.checked ? 1 : 0;
    });
    const response = await fetch(`${window.MC_APP_URL || ''}/api/enterprise/security-policy`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ سياسة Enterprise Security' : (payload.message || payload.error || 'تعذر حفظ سياسة الأمان'));
});

document.querySelector('.enterprise-provider-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    data.failover_enabled = form.querySelector('[name="failover_enabled"]')?.checked ? 1 : 0;
    const response = await fetch(`${window.MC_APP_URL || ''}/api/enterprise/messaging-providers`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ مزود الرسائل' : (payload.message || payload.error || 'تعذر حفظ مزود الرسائل'));
});

document.querySelector('.ai-commerce-generate-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/ai-commerce-os/generate`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? `تم توليد: ${payload.data?.experience?.title || 'تجربة AI'}` : (payload.message || payload.error || 'تعذر توليد التجربة'));
});

document.querySelector('.commerce-generate-btn')?.addEventListener('click', async () => {
    const response = await fetch(`${window.MC_APP_URL || ''}/api/ai-commerce-os/generate`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({type: 'campaign', prompt: 'حملة مبيعات ذكية للعملاء الجاهزين للشراء'}),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? `تم توليد: ${payload.data?.experience?.title || 'حملة AI'}` : (payload.message || payload.error || 'تعذر توليد حملة AI'));
});

document.querySelectorAll('[data-chatbot-tab]').forEach((button) => {
    button.addEventListener('click', () => {
        const tab = button.dataset.chatbotTab;
        document.querySelectorAll('[data-chatbot-tab]').forEach((item) => item.classList.toggle('active', item === button));
        document.querySelectorAll('[data-chatbot-panel]').forEach((panel) => panel.classList.toggle('active', panel.dataset.chatbotPanel === tab));
    });
});

document.querySelector('.chatbot-create-flow')?.addEventListener('click', async () => {
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/flows`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            name: 'مسار واتساب الافتراضي',
            connection_source: 'all_channels',
            status: 'draft',
            trigger_type: 'keyword',
            trigger_value: 'مرحبا'
        }),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ المسار' : (payload.message || payload.error || 'تعذر حفظ المسار'));
});

document.querySelector('.chatbot-auto-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/auto-replies`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ الرد التلقائي' : (payload.message || payload.error || 'تعذر حفظ الرد'));
});

document.querySelector('.chatbot-keyword-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/keywords`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ الكلمة المفتاحية' : (payload.message || payload.error || 'تعذر حفظ الكلمة المفتاحية'));
});

document.getElementById('testAiReply')?.addEventListener('click', async () => {
    const message = document.getElementById('aiPrompt')?.value || '';
    const output = document.getElementById('aiOutput');
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/ai/reply`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message, connection_source: 'meta_cloud_api'}),
    });
    const payload = await response.json().catch(() => ({}));
    if (output) output.textContent = payload.data?.reply || payload.message || 'تعذر توليد رد';
});

document.querySelector('.ai-settings-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    data.enabled = form.querySelector('[name="enabled"]')?.checked ? 1 : 0;
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/ai/settings`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ إعدادات AI' : (payload.message || payload.error || 'تعذر حفظ إعدادات AI'));
});

document.querySelector('.knowledge-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/knowledge-base`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget).entries())),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم حفظ المعلومة في قاعدة المعرفة' : (payload.message || payload.error || 'تعذر حفظ المعلومة'));
});

document.querySelector('.knowledge-upload-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/knowledge-base/upload`, {
        method: 'POST',
        body: new FormData(event.currentTarget),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? 'تم رفع ملف المعرفة' : (payload.message || payload.error || 'تعذر رفع الملف'));
});

document.querySelector('.ai-analyze-conversation')?.addEventListener('click', async (event) => {
    const conversationId = event.currentTarget.dataset.conversationId || '1';
    const response = await fetch(`${window.MC_APP_URL || ''}/api/chatbot/conversations/${conversationId}/ai/analyze`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message: 'أحتاج حالة طلبي'}),
    });
    const payload = await response.json().catch(() => ({}));
    showToast(response.ok ? `AI: ${payload.data?.intent || 'تم التحليل'} - ${payload.data?.priority || ''}` : (payload.message || payload.error || 'تعذر تحليل المحادثة'));
});

document.querySelector('.omni-connect-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button');
    button?.classList.add('loading');
    try {
        const response = await fetch(`${window.MC_APP_URL || ''}/api/omnichannel/channels/connect`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(Object.fromEntries(new FormData(form).entries())),
        });
        const payload = await response.json().catch(() => ({}));
        showToast(response.ok ? 'تم حفظ إعداد القناة' : (payload.message || payload.error || 'تعذر حفظ القناة'));
    } catch {
        showToast('تعذر الاتصال بالخادم');
    } finally {
        button?.classList.remove('loading');
    }
});

const chatbotUiState = {
    zoom: 1,
    latestFlowId: null,
    customNodeIndex: 1,
};

const premiumDepartmentReplies = {
    sales: {title: 'المبيعات', reply: 'يسعدنا مساعدتك في المبيعات. هل تريد معرفة الأسعار أم العروض أم التحدث مع مستشار مبيعات؟', buttons: ['الأسعار', 'العروض', 'مستشار مبيعات'], queue: 'طابور المبيعات'},
    support: {title: 'الدعم الفني', reply: 'من فضلك اختر نوع المشكلة التي تواجهك.', buttons: ['مشكلة في الطلب', 'مشكلة في الدفع', 'مشكلة في الحساب', 'تحويل للدعم الفني'], queue: 'طابور الدعم الفني'},
    orders: {title: 'الطلبات والشحن', reply: 'يمكنك متابعة طلبك من هنا. من فضلك أرسل رقم الطلب.', buttons: [], queue: 'طابور الطلبات'},
    billing: {title: 'الحسابات والفواتير', reply: 'اختر الخدمة المطلوبة.', buttons: ['طلب فاتورة', 'مشكلة دفع', 'مراجعة حساب'], queue: 'طابور الحسابات'},
    complaints: {title: 'الشكاوى', reply: 'نأسف لسماع ذلك. من فضلك اكتب تفاصيل الشكوى وسيتم تحويلها للقسم المختص.', buttons: [], queue: 'طابور الشكاوى'},
    handover: {title: 'التحدث مع موظف', reply: 'سيتم تحويلك الآن إلى أحد ممثلي خدمة العملاء.', buttons: [], queue: 'طابور الدعم الفني'},
};

const nodeTypeLabels = {
    message: 'رسالة نصية',
    question: 'سؤال',
    condition: 'شروط',
    delay: 'تأخير',
    ai_reply: 'رد ذكي',
    human_handover: 'تحويل لموظف',
    api_request: 'طلب API',
    tag: 'وسم',
    campaign: 'حملة',
    end: 'نهاية',
};

function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function chatbotApiPath(path) {
    return `${window.MC_APP_URL || ''}${path}`;
}

async function chatbotRequest(path, options = {}) {
    const response = await fetch(chatbotApiPath(path), {
        headers: {'Content-Type': 'application/json', ...(options.headers || {})},
        ...options,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || payload.detail || payload.error || 'تعذر تنفيذ العملية');
    return payload;
}

function selectedChatbotSource() {
    return document.getElementById('chatbotConnectionSource')?.value || 'all_channels';
}

function normalizedNodeType(type) {
    return ({
        start: 'message',
        list: 'message',
        buttons: 'message',
        button: 'message',
        text: 'message',
        handover: 'human_handover',
        api: 'api_request',
        ai: 'ai_reply',
        department_reply: 'message',
    })[type] || type || 'message';
}

function selectedNode() {
    return document.querySelector('#departmentFlowMap .dept-node.selected');
}

function departmentKeyByTitle(value) {
    const clean = String(value || '').trim();
    const direct = Object.entries(premiumDepartmentReplies).find(([, item]) => item.title === clean);
    if (direct) return direct[0];
    const normalized = clean.toLowerCase();
    if (/(سعر|أسعار|الاسعار|العروض|مبيعات|مستشار)/u.test(normalized)) return 'sales';
    if (/(دعم|مشكلة|عطل|حساب)/u.test(normalized)) return 'support';
    if (/(طلب|طلبات|شحن|تتبع)/u.test(normalized)) return 'orders';
    if (/(فاتورة|فواتير|دفع|حسابات)/u.test(normalized)) return 'billing';
    if (/(شكوى|شكاوى|استرجاع|إرجاع)/u.test(normalized)) return 'complaints';
    if (/(موظف|ممثل|إنسان|انسان)/u.test(normalized)) return 'handover';
    return null;
}

function nodeKey(node) {
    if (node.dataset.nodeKey) return node.dataset.nodeKey;
    if (node.classList.contains('start')) return 'start';
    if (node.classList.contains('message')) return 'welcome';
    if (node.classList.contains('list')) return 'departments';
    for (const key of ['sales', 'support', 'orders', 'billing', 'complaints', 'handover']) {
        if (node.classList.contains(key)) return key;
    }
    const title = (node.dataset.nodeTitle || node.querySelector('b')?.textContent || 'node').trim();
    return title.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '_').replace(/^_+|_+$/g, '') || `node_${Date.now()}`;
}

function nodeType(node) {
    if (node.dataset.nodeType) return normalizedNodeType(node.dataset.nodeType);
    if (node.classList.contains('orders')) return 'question';
    if (node.classList.contains('complaints') || node.classList.contains('handover')) return 'human_handover';
    if (node.classList.contains('condition')) return 'condition';
    if (node.classList.contains('delay')) return 'delay';
    if (node.classList.contains('api_request')) return 'api_request';
    if (node.classList.contains('end')) return 'end';
    return 'message';
}

function defaultNodeOptions(key) {
    if (key === 'departments') return Object.values(premiumDepartmentReplies).map((item) => item.title);
    return premiumDepartmentReplies[key]?.buttons || [];
}

function parseButtons(value) {
    return String(value || '').split(/\r?\n|،|,/).map((item) => item.trim()).filter(Boolean);
}

function settingsControls() {
    const panel = document.querySelector('.bot-node-settings');
    const textareas = Array.from(panel?.querySelectorAll('textarea') || []);
    const selects = Array.from(panel?.querySelectorAll('select') || []);
    const inputs = Array.from(panel?.querySelectorAll('input') || []);
    return {
        title: document.getElementById('nodeTitleInput'),
        type: document.getElementById('nodeTypeInput') || selects[0],
        message: document.getElementById('nodeMessageInput'),
        buttons: document.getElementById('nodeButtonsInput') || textareas.find((item) => item.id !== 'nodeMessageInput'),
        department: document.getElementById('nodeDepartmentInput') || selects[1],
        tag: document.getElementById('nodeTagInput') || inputs.find((item) => item.id !== 'nodeTitleInput' && item.type !== 'checkbox'),
        next: document.getElementById('nodeNextInput') || selects[2],
        autosave: document.querySelector('.autosave-state'),
    };
}

function setAutosaveState(state, text) {
    const autosave = settingsControls().autosave;
    if (!autosave) return;
    autosave.classList.remove('saving', 'saved', 'error');
    autosave.classList.add(state);
    autosave.textContent = text;
}

function syncFlowStepList(activeKey = null) {
    const keys = new Set(Array.from(document.querySelectorAll('#departmentFlowMap .dept-node')).map((node) => nodeKey(node)));
    document.querySelectorAll('.flow-step-list article').forEach((item) => {
        const key = item.dataset.stepKey;
        if (key) {
            item.classList.toggle('active', key === activeKey);
            item.classList.toggle('is-hidden-step', !keys.has(key) && !['end'].includes(key));
        }
    });
}

function isDefaultFlowStep(key) {
    return ['welcome', 'departments', 'sales', 'support', 'orders', 'billing', 'complaints', 'handover', 'end'].includes(key);
}

function selectFlowNode(node, append = true) {
    if (!node) return;
    document.querySelectorAll('#departmentFlowMap .dept-node').forEach((item) => item.classList.toggle('selected', item === node));
    const controls = settingsControls();
    const key = nodeKey(node);
    const title = node.dataset.nodeTitle || node.querySelector('b')?.textContent || '';
    const message = node.dataset.nodeMessage || '';
    const options = node.dataset.nodeOptions ? parseButtons(node.dataset.nodeOptions) : defaultNodeOptions(key);
    if (controls.title) controls.title.value = title;
    if (controls.type) controls.type.value = nodeType(node);
    if (controls.message) controls.message.value = message;
    if (controls.buttons) controls.buttons.value = options.join('\n');
    if (controls.department && premiumDepartmentReplies[key]) controls.department.value = premiumDepartmentReplies[key].title;
    if (controls.tag) controls.tag.value = node.dataset.nodeTag || `department_${key}`;
    syncFlowStepList(key);
    setAutosaveState('saved', 'الحفظ التلقائي جاهز');
    if (append) appendPreviewMessage('bot', message || title, options);
}

function collectChatbotNodes() {
    return Array.from(document.querySelectorAll('#departmentFlowMap .dept-node')).map((node) => {
        const key = nodeKey(node);
        const message = node.dataset.nodeMessage || node.querySelector('span')?.textContent || '';
        const options = node.dataset.nodeOptions ? parseButtons(node.dataset.nodeOptions) : defaultNodeOptions(key);
        return {
            node_key: key,
            node_type: nodeType(node),
            title: node.dataset.nodeTitle || node.querySelector('b')?.textContent || key,
            message,
            options,
            position_x: parseInt(node.style.getPropertyValue('--x'), 10) || 0,
            position_y: parseInt(node.style.getPropertyValue('--y'), 10) || 0,
            config: {
                text: message,
                options,
                department: premiumDepartmentReplies[key]?.queue || null,
                tag: node.dataset.nodeTag || `department_${key}`,
                next: node.dataset.nextNode || null,
                wait_for_customer: ['question', 'human_handover'].includes(nodeType(node)),
            },
            settings: {enabled: !node.classList.contains('disabled'), source: selectedChatbotSource()},
        };
    });
}

function collectChatbotEdges() {
    const keys = new Set(collectChatbotNodes().map((node) => node.node_key));
    const staticEdges = [['start', 'welcome'], ['welcome', 'departments'], ['departments', 'sales'], ['departments', 'support'], ['departments', 'orders'], ['departments', 'billing'], ['departments', 'complaints'], ['departments', 'handover']];
    return staticEdges.filter(([source, target]) => keys.has(source) && keys.has(target)).map(([source, target]) => ({source, target, condition: {option: premiumDepartmentReplies[target]?.title || target}}));
}

function chatbotFlowPayload(status = 'draft') {
    return {
        name: 'مسار واتساب الرئيسي للأقسام',
        description: 'مسار رد آلي موحد لواتساب Cloud API و QR Session مع تحويل ذكي للأقسام.',
        connection_source: selectedChatbotSource(),
        status,
        trigger_type: 'first_message',
        trigger_value: 'welcome',
        nodes: collectChatbotNodes(),
        edges: collectChatbotEdges(),
    };
}

async function latestChatbotFlow() {
    if (chatbotUiState.latestFlowId) return chatbotUiState.latestFlowId;
    const payload = await chatbotRequest('/api/chatbot/flows/current');
    const flow = payload.data || null;
    chatbotUiState.latestFlowId = flow?.id || null;
    return chatbotUiState.latestFlowId;
}

function statusLabel(status) {
    return ({active: 'منشط', paused: 'متوقف', draft: 'مسودة'})[status] || 'غير محدد';
}

function updateFlowStatusUi(flow) {
    const status = flow?.status || 'draft';
    const statusToggle = document.querySelector('.bot-status-toggle');
    if (statusToggle) {
        statusToggle.classList.toggle('paused', status === 'paused');
        statusToggle.classList.toggle('draft', status === 'draft');
        statusToggle.innerHTML = `<i></i> الحالة: ${escapeHtml(statusLabel(status))}`;
    }
    const name = document.getElementById('currentFlowName');
    const statusNode = document.getElementById('currentFlowStatus');
    const updatedAt = document.getElementById('currentFlowUpdatedAt');
    const nodeCount = document.getElementById('flowNodeCount');
    const activeCount = document.getElementById('activeFlowCount');
    const pausedCount = document.getElementById('pausedFlowCount');
    const draftCount = document.getElementById('draftFlowCount');
    if (name) name.textContent = flow?.name || 'التدفق الرئيسي';
    if (statusNode) statusNode.textContent = statusLabel(status);
    if (updatedAt) updatedAt.textContent = flow?.updated_at || flow?.created_at || 'غير متاح';
    if (nodeCount) nodeCount.textContent = Array.isArray(flow?.nodes) ? String(flow.nodes.length) : String(collectChatbotNodes().length);
    if (activeCount) activeCount.textContent = status === 'active' ? '1' : '0';
    if (pausedCount) pausedCount.textContent = status === 'paused' ? '1' : '0';
    if (draftCount) draftCount.textContent = status === 'draft' ? '1' : '0';
}

function updateFlowCounters(flows = []) {
    const activeCount = document.getElementById('activeFlowCount');
    const pausedCount = document.getElementById('pausedFlowCount');
    const draftCount = document.getElementById('draftFlowCount');
    if (activeCount) activeCount.textContent = String(flows.filter((flow) => flow.status === 'active').length);
    if (pausedCount) pausedCount.textContent = String(flows.filter((flow) => flow.status === 'paused').length);
    if (draftCount) draftCount.textContent = String(flows.filter((flow) => !flow.status || flow.status === 'draft').length);
}

function flowStatusClass(status) {
    if (status === 'active') return 'ok';
    if (status === 'paused') return 'danger-state';
    return 'pending';
}

function renderFlowLibrary(flows = []) {
    const list = document.getElementById('chatbotFlowLibraryList');
    if (!list) return;
    updateFlowCounters(flows);
    if (!flows.length) {
        list.innerHTML = '<article class="empty-flow-library"><b>لا توجد مسارات محفوظة بعد</b><span>اضغط حفظ المسار لإنشاء أول نسخة قابلة للتحميل والنشر.</span></article>';
        return;
    }
    list.innerHTML = flows.map((flow) => {
        const status = flow.status || 'draft';
        const updated = flow.updated_at || flow.created_at || 'غير متاح';
        const source = flow.connection_source || 'both';
        const version = flow.version || 1;
        return `
            <article class="flow-library-item ${escapeHtml(status)} ${Number(flow.id) === Number(chatbotUiState.latestFlowId) ? 'selected' : ''}" data-flow-id="${escapeHtml(flow.id)}">
                <div>
                    <strong>${escapeHtml(flow.name || 'مسار بدون اسم')}</strong>
                    <span>${escapeHtml(source)} · الإصدار ${escapeHtml(version)}</span>
                </div>
                <span class="status-pill ${flowStatusClass(status)}">${escapeHtml(statusLabel(status))}</span>
                <small>${escapeHtml(updated)}</small>
                <div class="button-row">
                    <button class="secondary load-flow-by-id" type="button" data-flow-id="${escapeHtml(flow.id)}">تحميل</button>
                    <button class="secondary duplicate-flow-by-id" type="button" data-flow-id="${escapeHtml(flow.id)}">نسخ</button>
                    <button class="danger delete-flow-by-id" type="button" data-flow-id="${escapeHtml(flow.id)}">حذف</button>
                </div>
            </article>
        `;
    }).join('');
}

async function refreshFlowLibrary(silent = false) {
    const list = document.getElementById('chatbotFlowLibraryList');
    if (!list) return [];
    try {
        const payload = await chatbotRequest('/api/chatbot/flows');
        const flows = Array.isArray(payload.data) ? payload.data : [];
        renderFlowLibrary(flows);
        if (!silent) showToast('تم تحديث مكتبة المسارات');
        return flows;
    } catch (error) {
        if (!silent) showToast(error.message);
        return [];
    }
}

async function loadFlowById(flowId, button = null) {
    if (!flowId) return;
    button?.classList.add('loading');
    try {
        const payload = await chatbotRequest(`/api/chatbot/flows/${flowId}`);
        if (!payload.data) throw new Error('المسار المطلوب غير موجود');
        renderPersistedChatbotFlow(payload.data);
        renderFlowLibrary(await refreshFlowLibrary(true));
        showToast('تم تحميل المسار المحدد');
    } catch (error) {
        showToast(error.message);
    } finally {
        button?.classList.remove('loading');
    }
}

async function duplicateFlow(flowId = null, button = null) {
    button?.classList.add('loading');
    try {
        const sourceFlow = flowId
            ? (await chatbotRequest(`/api/chatbot/flows/${flowId}`)).data
            : {
                name: document.getElementById('currentFlowName')?.textContent || 'مسار شات بوت',
                description: 'نسخة مسودة من المسار الحالي',
                connection_source: selectedChatbotSource(),
                nodes: collectChatbotNodes(),
                edges: collectChatbotEdges(),
            };
        if (!sourceFlow) throw new Error('لا يمكن نسخ مسار غير موجود');
        const created = await chatbotRequest('/api/chatbot/flows', {
            method: 'POST',
            body: JSON.stringify({
                name: `${sourceFlow.name || 'مسار شات بوت'} - نسخة`,
                description: sourceFlow.description || 'نسخة مسودة قابلة للتعديل',
                connection_source: sourceFlow.connection_source || selectedChatbotSource(),
                status: 'draft',
                trigger_type: sourceFlow.trigger_type || 'first_message',
                trigger_value: sourceFlow.trigger_value || null,
                nodes: Array.isArray(sourceFlow.nodes) ? sourceFlow.nodes : collectChatbotNodes(),
                edges: Array.isArray(sourceFlow.edges) ? sourceFlow.edges : collectChatbotEdges(),
            }),
        });
        chatbotUiState.latestFlowId = created.id || null;
        await loadFlowById(chatbotUiState.latestFlowId, null);
        showToast('تم إنشاء نسخة مسودة من المسار');
    } catch (error) {
        showToast(error.message);
    } finally {
        button?.classList.remove('loading');
    }
}

async function deleteFlowById(flowId, button = null) {
    if (!flowId) return;
    if (!window.confirm('هل تريد حذف هذا المسار؟ لا يمكن التراجع عن الحذف.')) return;
    button?.classList.add('loading');
    try {
        await chatbotRequest(`/api/chatbot/flows/${flowId}`, {method: 'DELETE'});
        if (Number(chatbotUiState.latestFlowId) === Number(flowId)) {
            chatbotUiState.latestFlowId = null;
            await loadPersistedChatbotFlow(null, true);
        }
        await refreshFlowLibrary(true);
        showToast('تم حذف المسار');
    } catch (error) {
        showToast(error.message);
    } finally {
        button?.classList.remove('loading');
    }
}

async function persistChatbotFlow(status, button) {
    button?.classList.add('loading');
    setAutosaveState('saving', 'جاري حفظ المسار...');
    try {
        if (status === 'active') {
            const diagnostics = await chatbotRequest('/api/chatbot/diagnostics/routes');
            if (!diagnostics.data?.ready) {
                setAutosaveState('error', 'لا يمكن نشر البوت قبل نجاح التشخيص');
                showToast('راجع تشخيص مسارات البوت قبل النشر');
                document.getElementById('chatbotDiagnosticsPanel')?.scrollIntoView({behavior: 'smooth', block: 'start'});
                return;
            }
        }
        const payload = chatbotFlowPayload(status);
        const existingId = await latestChatbotFlow();
        if (existingId) {
            await chatbotRequest(`/api/chatbot/flows/${existingId}`, {method: 'PUT', body: JSON.stringify(payload)});
            if (status === 'active') await chatbotRequest(`/api/chatbot/flows/${existingId}/activate`, {method: 'POST', body: JSON.stringify({})});
        } else {
            const created = await chatbotRequest('/api/chatbot/flows', {method: 'POST', body: JSON.stringify(payload)});
            chatbotUiState.latestFlowId = created.id || null;
            if (status === 'active' && chatbotUiState.latestFlowId) await chatbotRequest(`/api/chatbot/flows/${chatbotUiState.latestFlowId}/activate`, {method: 'POST', body: JSON.stringify({})});
        }
        setAutosaveState('saved', status === 'active' ? 'تم نشر المسار بنجاح' : status === 'paused' ? 'تم إيقاف المسار' : 'تم حفظ المسار');
        updateFlowStatusUi({...payload, id: chatbotUiState.latestFlowId, status, nodes: collectChatbotNodes(), updated_at: new Date().toISOString().slice(0, 19).replace('T', ' ')});
        await refreshFlowLibrary(true);
        showToast(status === 'active' ? 'تم نشر البوت وربطه بمصدر الواتساب' : status === 'paused' ? 'تم إيقاف البوت مؤقتاً' : 'تم حفظ المسار');
    } catch (error) {
        setAutosaveState('error', error.message);
        showToast(error.message);
    } finally {
        button?.classList.remove('loading');
    }
}

function resetBotPreview() {
    const chat = document.getElementById('botPreviewChat');
    if (!chat) return;
    chat.innerHTML = '<div class="wa-day">اليوم</div>';
    appendPreviewMessage('bot', 'أهلاً بك 👋\nكيف يمكننا مساعدتك اليوم؟', Object.values(premiumDepartmentReplies).map((item) => item.title));
}

function appendPreviewMessage(direction, body, buttons = []) {
    const chat = document.getElementById('botPreviewChat');
    if (!chat) return;
    chat.querySelector('.typing-indicator')?.remove();
    const message = document.createElement('div');
    message.className = `wa-msg ${direction}`;
    message.innerHTML = `<p>${escapeHtml(body).replace(/\n/g, '<br>')}</p><time>09:42 ${direction === 'bot' ? '✓✓' : ''}</time>`;
    chat.appendChild(message);
    if (buttons.length) {
        const replies = document.createElement('div');
        replies.className = 'wa-quick-replies';
        replies.innerHTML = buttons.map((button) => `<button type="button" data-preview-choice="${escapeHtml(button)}">${escapeHtml(button)}</button>`).join('');
        chat.appendChild(replies);
    }
    const typing = document.createElement('div');
    typing.className = 'typing-indicator';
    typing.innerHTML = '<i></i><i></i><i></i>';
    chat.appendChild(typing);
    chat.scrollTop = chat.scrollHeight;
}

function findDepartmentReply(value) {
    const clean = String(value || '').trim();
    const key = departmentKeyByTitle(clean);
    return key ? premiumDepartmentReplies[key] : null;
}

function selectNodeForRuntimeResult(input, data = {}) {
    let key = data.department || departmentKeyByTitle(input);
    if (key === 'human') key = 'handover';
    const node = key ? findFlowNodeByKey(key) : null;
    if (!node) return;
    selectFlowNode(node, false);
    node.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'center'});
}

function appendFlowStepForNode(node) {
    const list = document.querySelector('.flow-step-list');
    const key = node ? nodeKey(node) : null;
    if (!list || !key || Array.from(list.querySelectorAll('[data-step-key]')).some((item) => item.dataset.stepKey === key)) return;
    const article = document.createElement('article');
    article.dataset.stepKey = key;
    if (!isDefaultFlowStep(key)) article.classList.add('custom-flow-step');
    article.innerHTML = `<span>▣</span><div><b>${escapeHtml(node.dataset.nodeTitle || node.querySelector('b')?.textContent || 'خطوة جديدة')}</b><small>${escapeHtml(nodeTypeLabels[nodeType(node)] || 'عقدة')}</small></div><em class="blue"></em>`;
    list.insertBefore(article, list.lastElementChild || null);
}

function updateFlowStepForNode(node) {
    const key = node ? nodeKey(node) : null;
    if (!key) return;
    const step = Array.from(document.querySelectorAll('.flow-step-list [data-step-key]')).find((item) => item.dataset.stepKey === key);
    if (!step) return appendFlowStepForNode(node);
    const title = step.querySelector('b');
    const subtitle = step.querySelector('small');
    if (title && !isDefaultFlowStep(key)) title.textContent = node.dataset.nodeTitle || node.querySelector('b')?.textContent || 'خطوة جديدة';
    if (subtitle && !isDefaultFlowStep(key)) subtitle.textContent = nodeTypeLabels[nodeType(node)] || 'عقدة';
}

function addFlowNode(type = 'message', title = null, options = {}) {
    const map = document.getElementById('departmentFlowMap');
    if (!map) return null;
    const normalizedType = normalizedNodeType(type);
    const index = chatbotUiState.customNodeIndex++;
    const key = `custom_${normalizedType}_${Date.now()}_${index}`;
    const node = document.createElement('article');
    node.className = `dept-node custom ${normalizedType}`;
    node.dataset.nodeKey = key;
    node.dataset.nodeType = normalizedType;
    node.dataset.nodeTitle = title || (nodeTypeLabels[normalizedType] || 'عقدة جديدة');
    node.dataset.nodeMessage = normalizedType === 'human_handover' ? 'سيتم تحويلك الآن إلى موظف مختص.' : 'اكتب نص الرسالة هنا.';
    node.dataset.nodeOptions = '';
    node.style.setProperty('--x', `${Math.max(40, 260 - index * 20)}px`);
    node.style.setProperty('--y', `${Math.max(40, 220 + index * 86)}px`);
    node.innerHTML = `<b>${escapeHtml(node.dataset.nodeTitle)}</b><span>${escapeHtml(nodeTypeLabels[normalizedType] || normalizedType)}</span>`;
    map.appendChild(node);
    appendFlowStepForNode(node);
    syncFlowStepList(key);
    if (!options.silent) {
        selectFlowNode(node, true);
        showToast('تمت إضافة مكون جديد إلى المخطط');
    }
    return node;
}

function findFlowNodeByKey(key) {
    return Array.from(document.querySelectorAll('#departmentFlowMap .dept-node')).find((node) => node.dataset.nodeKey === key || nodeKey(node) === key) || null;
}

function renderSavedNode(savedNode) {
    const key = savedNode.node_key || `saved_${Date.now()}`;
    const type = normalizedNodeType(savedNode.node_type || savedNode.type || 'message');
    const options = Array.isArray(savedNode.options) ? savedNode.options : (savedNode.config?.options || []);
    let node = findFlowNodeByKey(key);
    if (!node) {
        node = addFlowNode(type, savedNode.title || key, {silent: true});
        if (!node) return null;
        node.dataset.nodeKey = key;
        node.classList.add('custom');
    }

    node.dataset.nodeKey = key;
    node.dataset.nodeType = type;
    node.dataset.nodeTitle = savedNode.title || key;
    node.dataset.nodeMessage = savedNode.message || savedNode.config?.text || '';
    node.dataset.nodeOptions = options.join('\n');
    node.dataset.nodeTag = savedNode.config?.tag || '';
    node.dataset.nextNode = savedNode.config?.next || '';
    node.style.setProperty('--x', `${parseInt(savedNode.position_x, 10) || parseInt(savedNode.x, 10) || 0}px`);
    node.style.setProperty('--y', `${parseInt(savedNode.position_y, 10) || parseInt(savedNode.y, 10) || 0}px`);
    node.classList.remove('message', 'question', 'condition', 'delay', 'human_handover', 'api_request', 'end');
    node.classList.add(type);

    const title = node.querySelector('b');
    const meta = node.querySelector('span');
    if (title) title.textContent = savedNode.title || key;
    if (meta) meta.textContent = savedNode.config?.department || nodeTypeLabels[type] || type;
    appendFlowStepForNode(node);
    return node;
}

function renderPersistedChatbotFlow(flow) {
    if (!flow) return false;
    chatbotUiState.latestFlowId = flow.id || null;
    const sourceSelect = document.getElementById('chatbotConnectionSource');
    if (sourceSelect) sourceSelect.value = flow.connection_source || 'all_channels';
    document.querySelectorAll('#departmentFlowMap .dept-node.custom').forEach((node) => node.remove());
    document.querySelectorAll('.flow-step-list .custom-flow-step').forEach((step) => step.remove());
    const nodes = Array.isArray(flow.nodes) ? flow.nodes : [];
    nodes.forEach(renderSavedNode);
    updateFlowStatusUi(flow);
    const selected = findFlowNodeByKey('departments') || findFlowNodeByKey('start') || document.querySelector('#departmentFlowMap .dept-node');
    selectFlowNode(selected, false);
    setAutosaveState('saved', 'تم تحميل آخر مسار محفوظ');
    return true;
}

async function loadPersistedChatbotFlow(button = null, silent = false) {
    if (!document.getElementById('departmentFlowMap')) return;
    button?.classList.add('loading');
    try {
        const payload = await chatbotRequest('/api/chatbot/flows/current');
        if (!payload.data) {
            chatbotUiState.latestFlowId = null;
            document.querySelectorAll('#departmentFlowMap .dept-node.custom').forEach((node) => node.remove());
            document.querySelectorAll('.flow-step-list .custom-flow-step').forEach((step) => step.remove());
            updateFlowStatusUi({name: 'التدفق الرئيسي', status: 'draft', nodes: collectChatbotNodes()});
            selectFlowNode(findFlowNodeByKey('departments') || document.querySelector('#departmentFlowMap .dept-node'), false);
            setAutosaveState('saved', 'لا يوجد مسار محفوظ حالياً');
            if (!silent) showToast('لا يوجد مسار محفوظ بعد');
            return;
        }
        renderPersistedChatbotFlow(payload.data);
        if (!silent) showToast('تم تحميل آخر مسار محفوظ');
    } catch (error) {
        if (!silent) showToast(error.message);
    } finally {
        button?.classList.remove('loading');
    }
}

function updateSelectedNodeFromSettings() {
    const node = selectedNode();
    if (!node) return showToast('اختر عقدة من المخطط أولاً');
    const controls = settingsControls();
    const title = controls.title?.value?.trim() || 'عقدة جديدة';
    const message = controls.message?.value?.trim() || '';
    const options = parseButtons(controls.buttons?.value || '');
    const typeText = controls.type?.value || '';
    const selectedType = Object.prototype.hasOwnProperty.call(nodeTypeLabels, typeText) ? typeText : Object.entries(nodeTypeLabels).find(([, label]) => label === typeText)?.[0];
    const type = normalizedNodeType(selectedType || node.dataset.nodeType || nodeType(node));
    node.dataset.nodeTitle = title;
    node.dataset.nodeMessage = message;
    node.dataset.nodeOptions = options.join('\n');
    node.dataset.nodeType = type;
    node.dataset.nodeTag = controls.tag?.value || '';
    node.dataset.nextNode = controls.next?.value || '';
    node.classList.remove('message', 'question', 'condition', 'delay', 'human_handover', 'api_request', 'end');
    node.classList.add(type);
    const titleEl = node.querySelector('b');
    const metaEl = node.querySelector('span');
    if (titleEl) titleEl.textContent = title;
    if (metaEl) metaEl.textContent = nodeTypeLabels[type] || type;
    updateFlowStepForNode(node);
    setAutosaveState('saved', 'تم تحديث العقدة محلياً');
    appendPreviewMessage('bot', message || title, options);
}

function autoArrangeFlow() {
    const positions = {start: [820, 42], welcome: [565, 42], departments: [315, 42], sales: [50, -34], support: [50, 116], orders: [50, 266], billing: [50, 416], complaints: [50, 566], handover: [50, 696]};
    document.querySelectorAll('#departmentFlowMap .dept-node').forEach((node, index) => {
        const position = positions[nodeKey(node)] || [Math.max(20, 660 - (index % 3) * 220), 190 + Math.floor(index / 3) * 130];
        node.style.setProperty('--x', `${position[0]}px`);
        node.style.setProperty('--y', `${position[1]}px`);
    });
    showToast('تم ترتيب الشجرة تلقائياً');
}

function updateFlowZoom(nextZoom) {
    chatbotUiState.zoom = Math.min(1.3, Math.max(0.75, nextZoom));
    const map = document.getElementById('departmentFlowMap');
    if (map) map.style.transform = `scale(${chatbotUiState.zoom})`;
    document.querySelectorAll('.flow-zoom-label, .flow-canvas-toolbar .secondary').forEach((item) => {
        if (item.textContent.trim().includes('%')) item.textContent = `${Math.round(chatbotUiState.zoom * 100)}%`;
    });
}

document.addEventListener('click', async (event) => {
    const target = event.target;
    const flowButton = target.closest('.chatbot-create-flow, .chatbot-publish-flow, .chatbot-pause-flow');
    const previewButton = target.closest('.bot-preview-run');
    const customerTest = target.closest('.bot-customer-test');
    const allDepartmentsTest = target.closest('.bot-all-depts-test');
    const loadFlowButton = target.closest('.load-current-flow');
    const handoverTest = target.closest('.test-handover-btn');
    const addNodeButton = target.closest('.add-node-btn, .add-flow-step');
    const addDepartment = target.closest('.add-department-btn');
    const paletteButton = target.closest('.component-palette button');
    const saveNode = target.closest('.save-node-settings, .bot-node-settings .button-row .primary');
    const deleteNode = target.closest('.delete-node-btn, .bot-node-settings .button-row .danger');
    const flowStep = target.closest('.flow-step-list article');
    const previewSend = target.closest('.wa-preview-send');
    const flowNode = target.closest('#departmentFlowMap .dept-node');
    const toolbarButton = target.closest('.flow-canvas-toolbar button');
    const toolbarText = toolbarButton?.textContent.trim() || '';

    if (flowButton) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        await persistChatbotFlow(flowButton.dataset.flowStatus || 'draft', flowButton);
        return;
    }
    if (previewButton) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        resetBotPreview();
        return;
    }
    if (customerTest) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        try {
            const payload = await chatbotRequest('/api/chatbot/process-message', {method: 'POST', body: JSON.stringify({body: 'المبيعات', connection_source: selectedChatbotSource()})});
            appendPreviewMessage('customer', 'المبيعات');
            appendPreviewMessage('bot', payload.data?.reply || 'تم تشغيل تجربة العميل بنجاح', payload.data?.buttons || []);
            showToast(`تم تشغيل تجربة العميل: ${payload.data?.queue || 'طابور المبيعات'}`);
        } catch (error) {
            showToast(error.message);
        }
        return;
    }
    if (allDepartmentsTest) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        allDepartmentsTest.classList.add('loading');
        resetBotPreview();
        const labels = Object.values(premiumDepartmentReplies).map((item) => item.title);
        let passed = 0;
        for (const label of labels) {
            try {
                const payload = await chatbotRequest('/api/chatbot/process-message', {method: 'POST', body: JSON.stringify({body: label, connection_source: selectedChatbotSource()})});
                appendPreviewMessage('customer', label);
                appendPreviewMessage('bot', payload.data?.reply || 'تم تنفيذ المسار', payload.data?.buttons || []);
                passed += 1;
            } catch (error) {
                appendPreviewMessage('bot', `تعذر اختبار ${label}: ${error.message}`);
            }
        }
        allDepartmentsTest.classList.remove('loading');
        showToast(`اكتمل فحص الأقسام: ${passed}/${labels.length}`);
        return;
    }
    if (loadFlowButton) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        await loadPersistedChatbotFlow(loadFlowButton, false);
        return;
    }
    const refreshLibraryButton = target.closest('.refresh-flow-library');
    if (refreshLibraryButton) {
        event.preventDefault();
        await refreshFlowLibrary(false);
        return;
    }
    const loadFlowItem = target.closest('.load-flow-by-id');
    if (loadFlowItem) {
        event.preventDefault();
        await loadFlowById(loadFlowItem.dataset.flowId, loadFlowItem);
        return;
    }
    const duplicateFlowButton = target.closest('.duplicate-flow-by-id, .duplicate-current-flow');
    if (duplicateFlowButton) {
        event.preventDefault();
        await duplicateFlow(duplicateFlowButton.dataset.flowId || null, duplicateFlowButton);
        return;
    }
    const deleteFlowButton = target.closest('.delete-flow-by-id');
    if (deleteFlowButton) {
        event.preventDefault();
        await deleteFlowById(deleteFlowButton.dataset.flowId, deleteFlowButton);
        return;
    }
    if (flowStep && flowStep.dataset.stepKey) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        let node = findFlowNodeByKey(flowStep.dataset.stepKey);
        if (!node && flowStep.dataset.stepKey === 'end') {
            node = addFlowNode('end', 'نهاية المحادثة', {silent: true});
        }
        if (!node) return showToast('هذه الخطوة غير موجودة في المخطط الحالي');
        selectFlowNode(node, true);
        node.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'center'});
        return;
    }
    if (previewSend) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        const input = document.getElementById('waPreviewInput');
        const value = input?.value?.trim() || '';
        if (!value) return showToast('اكتب رسالة اختبار أولاً');
        if (input) input.value = '';
        appendPreviewMessage('customer', value);
        try {
            const payload = await chatbotRequest('/api/chatbot/process-message', {method: 'POST', body: JSON.stringify({body: value, connection_source: selectedChatbotSource()})});
            const data = payload.data || {};
            appendPreviewMessage('bot', data.reply || 'تم استلام رسالتك وسيتم توجيهك للخطوة المناسبة.', data.buttons || []);
            selectNodeForRuntimeResult(value, data);
            showToast(`تم تشغيل الرد: ${data.queue || statusLabel(data.action) || 'مسار البوت'}`);
        } catch (error) {
            const direct = findDepartmentReply(value);
            appendPreviewMessage('bot', direct?.reply || 'تعذر تشغيل الاختبار حالياً.', direct?.buttons || []);
            selectNodeForRuntimeResult(value, {department: departmentKeyByTitle(value)});
            showToast(error.message);
        }
        return;
    }
    if (handoverTest) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        try {
            const payload = await chatbotRequest('/api/chatbot/process-message', {method: 'POST', body: JSON.stringify({body: 'التحدث مع موظف', connection_source: selectedChatbotSource()})});
            appendPreviewMessage('customer', 'التحدث مع موظف');
            appendPreviewMessage('bot', payload.data?.reply || 'سيتم تحويلك الآن إلى أحد ممثلي خدمة العملاء.', payload.data?.buttons || []);
            showToast(`تم اختبار التحويل: ${payload.data?.queue || 'طابور الدعم الفني'}`);
        } catch (error) {
            showToast(error.message);
        }
        return;
    }
    if (addNodeButton) {
        event.preventDefault();
        addFlowNode('message');
        return;
    }
    if (addDepartment) {
        event.preventDefault();
        const name = window.prompt('اسم القسم الجديد');
        if (!name) return;
        try {
            await chatbotRequest('/api/chatbot/departments', {method: 'POST', body: JSON.stringify({name, slug: name.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-'), welcome_message: `أهلاً بك في قسم ${name}. سيتم توجيهك للموظف المناسب.`, is_active: 1, priority: 'normal', auto_tag: name})});
            addFlowNode('human_handover', name);
            showToast('تم إنشاء القسم وإضافته للمخطط');
        } catch (error) {
            addFlowNode('human_handover', name);
            showToast(`تمت الإضافة محلياً، وتعذر حفظ القسم: ${error.message}`);
        }
        return;
    }
    if (paletteButton) {
        event.preventDefault();
        const types = ['message', 'message', 'question', 'condition', 'human_handover', 'delay', 'api_request', 'end'];
        const index = Array.from(document.querySelectorAll('.component-palette button')).indexOf(paletteButton);
        addFlowNode(paletteButton.dataset.componentType || types[index] || 'message');
        return;
    }
    if (saveNode) {
        event.preventDefault();
        updateSelectedNodeFromSettings();
        return;
    }
    if (deleteNode) {
        event.preventDefault();
        const node = selectedNode();
        if (!node) return showToast('اختر عقدة لحذفها');
        if (!node.classList.contains('custom')) return showToast('العقد الأساسية لا يمكن حذفها، يمكنك تعطيلها أو تعديلها');
        document.querySelector(`.flow-step-list [data-step-key="${nodeKey(node)}"]`)?.remove();
        node.remove();
        const firstNode = document.querySelector('#departmentFlowMap .dept-node');
        syncFlowStepList(firstNode ? nodeKey(firstNode) : null);
        setAutosaveState('saved', 'تم حذف العقدة محلياً');
        return;
    }
    if (target.closest('.flow-zoom-in') || toolbarText.includes('Zoom +') || toolbarText.includes('تكبير')) {
        event.preventDefault();
        updateFlowZoom(chatbotUiState.zoom + 0.1);
        return;
    }
    if (target.closest('.flow-zoom-out') || toolbarText.includes('Zoom -') || toolbarText.includes('تصغير')) {
        event.preventDefault();
        updateFlowZoom(chatbotUiState.zoom - 0.1);
        return;
    }
    if (toolbarText.includes('100%')) {
        event.preventDefault();
        updateFlowZoom(1);
        return;
    }
    if (toolbarText.includes('Auto Arrange') || toolbarText.includes('ترتيب تلقائي') || target.closest('.auto-arrange-flow')) {
        event.preventDefault();
        autoArrangeFlow();
        return;
    }
    if (flowNode) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        selectFlowNode(flowNode, true);
    }
}, true);

document.addEventListener('input', (event) => {
    if (event.target?.id !== 'nodeMessageInput') return;
    event.stopPropagation();
    event.stopImmediatePropagation();
    const lastBot = document.querySelector('#botPreviewChat .wa-msg.bot:last-of-type p');
    if (lastBot) lastBot.innerHTML = escapeHtml(event.target.value).replace(/\n/g, '<br>');
    const node = selectedNode();
    if (node) node.dataset.nodeMessage = event.target.value;
    setAutosaveState('saving', 'تعديل غير محفوظ...');
}, true);

document.querySelector('.flow-search')?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    document.querySelectorAll('.flow-step-list article').forEach((item) => {
        item.style.display = item.textContent.toLowerCase().includes(query) ? 'grid' : 'none';
    });
});

loadPersistedChatbotFlow(null, true);
refreshFlowLibrary(true);

const flowMap = document.getElementById('departmentFlowMap');
let dragNodeState = null;
flowMap?.addEventListener('pointerdown', (event) => {
    const node = event.target.closest('.dept-node');
    if (!node || event.button !== 0) return;
    const rect = flowMap.getBoundingClientRect();
    const nodeRect = node.getBoundingClientRect();
    dragNodeState = {node, startX: event.clientX, startY: event.clientY, offsetX: event.clientX - nodeRect.left, offsetY: event.clientY - nodeRect.top, mapWidth: rect.width, moved: false};
    flowMap.classList.add('is-dragging');
    node.setPointerCapture?.(event.pointerId);
});

flowMap?.addEventListener('pointermove', (event) => {
    if (!dragNodeState) return;
    const rect = flowMap.getBoundingClientRect();
    const nodeWidth = dragNodeState.node.offsetWidth || 176;
    const xFromLeft = event.clientX - rect.left - dragNodeState.offsetX + flowMap.scrollLeft;
    const y = event.clientY - rect.top - dragNodeState.offsetY + flowMap.scrollTop;
    const right = Math.max(10, Math.min(900, (flowMap.scrollWidth || dragNodeState.mapWidth) - xFromLeft - nodeWidth));
    dragNodeState.node.style.setProperty('--x', `${Math.round(right)}px`);
    dragNodeState.node.style.setProperty('--y', `${Math.round(Math.max(-60, Math.min(720, y)))}px`);
    dragNodeState.moved = Math.abs(event.clientX - dragNodeState.startX) > 4 || Math.abs(event.clientY - dragNodeState.startY) > 4;
});

flowMap?.addEventListener('pointerup', () => {
    if (dragNodeState?.moved) setAutosaveState('saving', 'تم تغيير ترتيب العقد، احفظ المسار');
    flowMap.classList.remove('is-dragging');
    dragNodeState = null;
});

document.addEventListener('click', async (event) => {
    const choice = event.target.closest('[data-preview-choice]');
    if (!choice) return;
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    const value = choice.dataset.previewChoice || choice.textContent.trim();
    appendPreviewMessage('customer', value);
    try {
        const payload = await chatbotRequest('/api/chatbot/process-message', {method: 'POST', body: JSON.stringify({body: value, connection_source: selectedChatbotSource()})});
        const data = payload.data || {};
        appendPreviewMessage('bot', data.reply || 'تم استلام اختيارك وسيتم توجيهك للخطوة التالية.', data.buttons || []);
        selectNodeForRuntimeResult(value, data);
    } catch (error) {
        const direct = findDepartmentReply(value);
        appendPreviewMessage('bot', direct?.reply || 'تم استلام اختيارك وسيتم توجيهك للخطوة التالية.', direct?.buttons || []);
        selectNodeForRuntimeResult(value, {department: departmentKeyByTitle(value)});
        showToast(error.message);
    }
}, true);

document.getElementById('waPreviewInput')?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    document.querySelector('.wa-preview-send')?.click();
});
