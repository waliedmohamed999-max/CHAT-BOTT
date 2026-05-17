(function () {
    const script = document.currentScript;
    const storeId = script?.dataset.store || '1';
    const existing = document.getElementById('mc-live-chat-widget');
    if (existing) return;

    const root = document.createElement('section');
    root.id = 'mc-live-chat-widget';
    root.dir = 'rtl';
    root.innerHTML = `
        <button class="mc-live-chat-fab" type="button">محادثة</button>
        <div class="mc-live-chat-panel" aria-hidden="true">
            <header><strong>دردشة المتجر</strong><span>متصل</span></header>
            <div class="mc-live-chat-body">
                <p class="bot">أهلاً بك، اسألني عن المنتجات أو الطلبات أو التوصيل.</p>
                <button type="button">الأسئلة الشائعة</button>
                <button type="button">التحدث مع موظف</button>
                <button type="button">الانتقال إلى واتساب</button>
            </div>
            <form>
                <input name="message" placeholder="اكتب رسالتك...">
                <button type="submit">إرسال</button>
            </form>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        #mc-live-chat-widget{position:fixed;left:22px;bottom:22px;z-index:2147483000;font-family:Arial,Tahoma,sans-serif}
        .mc-live-chat-fab{width:76px;height:48px;border:0;border-radius:999px;background:#263b7a;color:#fff;font-weight:800;box-shadow:0 16px 34px rgba(20,33,73,.24);cursor:pointer}
        .mc-live-chat-panel{position:absolute;left:0;bottom:62px;width:min(360px,calc(100vw - 32px));display:none;overflow:hidden;border:1px solid rgba(120,130,160,.26);border-radius:18px;background:#fff;color:#172033;box-shadow:0 24px 60px rgba(20,33,73,.22)}
        .mc-live-chat-panel.open{display:block}
        .mc-live-chat-panel header{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:#f5f8fb}
        .mc-live-chat-panel header span{color:#17885f;font-size:12px}
        .mc-live-chat-body{display:grid;gap:10px;padding:14px}
        .mc-live-chat-body .bot{margin:0;padding:12px;border-radius:14px;background:#eef7f4;line-height:1.7}
        .mc-live-chat-body button{min-height:38px;border:1px solid #dbe2ef;border-radius:12px;background:#fff;color:#263b7a;cursor:pointer}
        .mc-live-chat-panel form{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px;border-top:1px solid #edf1f7}
        .mc-live-chat-panel input{min-height:40px;border:1px solid #dbe2ef;border-radius:12px;padding:0 12px}
        .mc-live-chat-panel form button{border:0;border-radius:12px;background:#263b7a;color:#fff;padding:0 14px;cursor:pointer}
        @media (prefers-color-scheme:dark){.mc-live-chat-panel{background:#111827;color:#f8fafc}.mc-live-chat-panel header,.mc-live-chat-body button{background:#172033;color:#f8fafc}.mc-live-chat-body .bot{background:#12312a}.mc-live-chat-panel input{background:#0f172a;color:#fff;border-color:#29364d}}
    `;

    document.head.appendChild(style);
    document.body.appendChild(root);

    const panel = root.querySelector('.mc-live-chat-panel');
    root.querySelector('.mc-live-chat-fab')?.addEventListener('click', () => {
        panel?.classList.toggle('open');
        panel?.setAttribute('aria-hidden', panel.classList.contains('open') ? 'false' : 'true');
    });

    root.querySelector('form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = root.querySelector('input[name="message"]');
        const body = input?.value?.trim();
        if (!body) return;
        const message = document.createElement('p');
        message.textContent = body;
        message.className = 'bot';
        root.querySelector('.mc-live-chat-body')?.appendChild(message);
        if (input) input.value = '';

        try {
            await fetch('/api/omnichannel/webhooks/live_chat', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({store_id: storeId, event_type: 'message', body})
            });
        } catch {
            // Widget remains usable if API is offline.
        }
    });
})();
