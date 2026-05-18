# مركز التسويق Marketing Center

وحدة SaaS جاهزة للإنتاج لربط Meta Business Login وWhatsApp Embedded Signup وWhatsApp Cloud API، مع إدارة الحملات، القوالب، العملاء، صندوق الرسائل، الأتمتة، التحليلات، وسجلات التدقيق.

## التشغيل المحلي

1. انسخ `.env.example` إلى `.env`.
2. أنشئ قاعدة بيانات MySQL واستورد `database/schema.sql`.
3. ضع بيانات تطبيق Meta داخل `.env`.
4. وجّه Apache إلى مجلد `public/` أو افتح:

```text
http://localhost/sHAT%20POT/public/marketing-center
```

## نقاط الربط الرسمية

- ربط Meta Business وEmbedded Signup يتم عبر OAuth الرسمي من Meta.
- طلبات WhatsApp Cloud API تستخدم `WHATSAPP_API_VERSION`.
- Webhooks تدعم التحقق من `verify token`، توقيع `X-Hub-Signature-256`، رسائل العملاء، حالات الرسائل، تحديثات القوالب، وحفظ كل Payload.
- إطلاق الحملات يمنع الإرسال لأي عميل غير موافق Opt-in ويمنع استخدام أي قالب غير معتمد.
- إرسال الحملات يتم عبر Queue من قاعدة البيانات، مع إمكانية إضافة Redis لاحقاً عبر `QUEUE_REDIS_URL`.

## متغيرات البيئة المطلوبة

```text
META_APP_ID=
META_APP_SECRET=
META_CONFIG_ID=
META_REDIRECT_URI=https://your-domain.com/api/meta/callback
META_VERIFY_TOKEN=
META_WEBHOOK_SECRET=
WHATSAPP_API_VERSION=v23.0
DATABASE_URL=mysql://user:password@host:3306/marketing_center
ENCRYPTION_KEY=
QUEUE_REDIS_URL=redis://127.0.0.1:6379/0
INSTAGRAM_APP_SECRET=
FACEBOOK_PAGE_ACCESS_TOKEN=
TELEGRAM_BOT_TOKEN=
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASSWORD=
IMAP_HOST=
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=
LIVE_CHAT_WIDGET_SECRET=
```

## خطوات الربط الإنتاجي

1. اضبط ملف `.env`.
2. في لوحة Meta Developers، ضع رابط Webhook:

```text
https://your-domain.com/api/webhooks/whatsapp
```

المسار القديم ما زال مدعوماً للتوافق:

```text
https://your-domain.com/api/whatsapp/webhook
```

3. افتح `مركز التسويق > ربط Meta` واضغط `ربط Meta`.
4. من صفحة `قائمة الإعداد` نفذ:
   - مزامنة أصول Meta.
   - مزامنة قوالب واتساب.
   - إرسال رسالة اختبار.
   - تشغيل قائمة الإرسال.

## تشغيل Queue في الإنتاج

شغّل معالج الحملات كل دقيقة عبر Cron أو Windows Task Scheduler:

```bash
php bin/process-campaign-queue.php
```

لتشغيل حملة محددة:

```bash
php bin/process-campaign-queue.php 123
```

## تشغيل WhatsApp QR Connect

خدمة QR تعمل كـ Bridge منفصل باستخدام Baileys حتى تبقى منفصلة عن الربط الرسمي Meta Cloud API.

```bash
cd bridge
npm install
copy .env.example .env
npm start
```

اضبط نفس التوكن في ملفي البيئة:

```text
WHATSAPP_QR_BRIDGE_URL=http://127.0.0.1:3020
WHATSAPP_QR_BRIDGE_TOKEN=change-me-bridge-token
```

وفي `bridge/.env`:

```text
BRIDGE_TOKEN=change-me-bridge-token
PHP_WEBHOOK_URL=https://your-domain.com/api/whatsapp-qr/bridge-webhook
```

ملاحظات مهمة:

- QR Connect لا يلغي الربط الرسمي Cloud API.
- QR Connect لا يستخدم قوالب Meta الرسمية.
- استخدم QR للمحادثات والردود والتشغيل الخفيف فقط.
- الحملات عبر QR تعمل بحدود آمنة وDelay عشوائي.

## مركز إعداد واتساب الموحد

الصفحة الجديدة داخل اللوحة:

```text
/marketing-center/whatsapp-setup-center
```

تجمع خطوات الربط في Wizard واحد:

1. اختيار طريقة الربط: Meta Cloud API أو QR Web Session.
2. بيانات النشاط التجاري والمتجر.
3. رفع المستندات المطلوبة بشكل آمن خارج `public`.
4. تنفيذ الربط الرسمي أو QR.
5. اختبار الإرسال وWebhook.
6. حساب جاهزية الإطلاق من 100.

## AI Chatbot & Auto Reply Builder

المسار الجديد داخل اللوحة:

```text
/marketing-center/chatbot-builder
```

يدعم:

- Bot Flows بدون كود.
- Auto Replies.
- Keywords Triggers.
- AI Reply وAI Classification.
- Human Handover.
- Knowledge Base.
- Analytics.
- العمل مع `Meta Cloud API` و`QR Web Session`.

## Omnichannel Communication Platform

المسار الجديد:

```text
/marketing-center/omnichannel
```

يوحد القنوات التالية داخل Inbox وCRM وChatbot واحد:

- WhatsApp Cloud API
- WhatsApp QR Sessions
- Instagram DM
- Facebook Messenger
- Telegram
- Email
- SMS
- Website Live Chat

الطبقة الجديدة مبنية كـ `Unified Messaging Layer` مع `Channel Adapter System`، بحيث تبقى كل قناة Module منفصل ويمكن ربطها أو تعطيلها بدون كسر واتساب الحالي.

ودجت الدردشة للموقع:

```html
<script src="/assets/live-chat-widget.js" data-store="1"></script>
```

## مسارات API

```text
POST /api/meta/connect
GET  /api/meta/callback
POST /api/meta/sync-assets
POST /api/meta/disconnect
GET  /api/whatsapp/accounts
GET  /api/whatsapp/phone-numbers
POST /api/whatsapp/phone-numbers/primary
POST /api/whatsapp/send-test
GET  /api/whatsapp/templates
POST /api/whatsapp/templates
POST /api/whatsapp/templates/sync
GET  /api/whatsapp/webhook
POST /api/whatsapp/webhook
GET  /api/webhooks/whatsapp
POST /api/webhooks/whatsapp
POST /api/campaigns
GET  /api/campaigns
GET  /api/campaigns/{id}
POST /api/campaigns/{id}/launch
POST /api/campaigns/{id}/pause
POST /api/campaigns/{id}/resume
POST /api/campaigns/queue/process
POST /api/campaigns/{id}/queue/process
GET  /api/campaigns/{id}/progress
POST /api/campaigns/{id}/retry-failed
GET  /api/analytics
POST /api/contacts/import
GET  /api/inbox
POST /api/inbox/reply
POST /api/inbox/{id}
GET  /api/setup-checklist
GET  /api/whatsapp-setup/profile
POST /api/whatsapp-setup/profile
PUT  /api/whatsapp-setup/profile
POST /api/whatsapp-setup/documents/upload
GET  /api/whatsapp-setup/documents
DELETE /api/whatsapp-setup/documents/{id}
POST /api/whatsapp-setup/method/select
POST /api/whatsapp-setup/meta/connect
GET  /api/whatsapp-setup/meta/callback
POST /api/whatsapp-setup/meta/disconnect
POST /api/whatsapp-setup/qr/create
GET  /api/whatsapp-setup/qr/status
GET  /api/whatsapp-setup/qr/code
POST /api/whatsapp-setup/qr/disconnect
POST /api/whatsapp-setup/test/send-message
POST /api/whatsapp-setup/test/webhook
GET  /api/whatsapp-setup/test/logs
GET  /api/whatsapp-setup/readiness
POST /api/whatsapp-qr/session/create
GET  /api/whatsapp-qr/session/status
GET  /api/whatsapp-qr/session/qr
POST /api/whatsapp-qr/session/disconnect
POST /api/whatsapp-qr/session/reconnect
GET  /api/whatsapp-qr/chats
GET  /api/whatsapp-qr/chats/{id}/messages
POST /api/whatsapp-qr/send-message
POST /api/whatsapp-qr/send-media
GET  /api/whatsapp-qr/contacts
GET  /api/whatsapp-qr/events
POST /api/whatsapp-qr/bridge-webhook
GET  /api/chatbot/overview
POST /api/chatbot/flows
GET  /api/chatbot/flows
PUT  /api/chatbot/flows/{id}
DELETE /api/chatbot/flows/{id}
POST /api/chatbot/keywords
GET  /api/chatbot/keywords
POST /api/chatbot/auto-replies
GET  /api/chatbot/auto-replies
POST /api/chatbot/ai/reply
POST /api/chatbot/ai/classify
POST /api/chatbot/handover
POST /api/chatbot/resume
POST /api/chatbot/webhook/process
GET  /api/omnichannel/overview
GET  /api/omnichannel/channels
POST /api/omnichannel/channels/connect
GET  /api/omnichannel/conversations
GET  /api/omnichannel/conversations/{id}/messages
POST /api/omnichannel/reply
GET  /api/omnichannel/customer-360
GET  /api/omnichannel/analytics
GET  /api/omnichannel/live-chat/config
POST /api/omnichannel/webhooks/{channel}
```

## ترقية قاعدة بيانات موجودة

إذا كنت استوردت النسخة الأولى من القاعدة، شغّل:

```sql
SOURCE database/production_integration_migration.sql;
```

## صلاحيات Meta المطلوبة

```text
business_management
whatsapp_business_management
whatsapp_business_messaging
pages_show_list
pages_manage_posts
instagram_basic
instagram_content_publish
```

## الأمان

- التوكنات تحفظ مشفرة باستخدام `ENCRYPTION_KEY`.
- لا يتم عرض Access Tokens في الواجهة.
- Webhooks محمية بتوقيع `X-Hub-Signature-256`.
- يوجد Rate Limiting وRBAC وAudit Logs.
- لا ترسل حملات واتساب إلا للعملاء الموافقين فقط.

## خطة الإطلاق التدريجي

- اليوم الأول: 50 رسالة فقط.
- بعد ثبات الجودة: 200 رسالة.
- بعد ذلك ارفع العدد تدريجياً حسب جودة الرقم وحدود Meta.
- أوقف أي حملة تزيد معدل الفشل أو البلاغات.

## المرحلة الاحترافية القادمة

- AI Replies.
- AI Campaign Generator.
- Auto Segmentation.
- Smart Analytics.
- WhatsApp Chatbot.
- Multi-Agent Inbox.
- Multi-Tenant SaaS.
- Subscription System.

## Production Launch Checklist

هذا التقرير يحدد شروط الإطلاق النهائية لمنصة Marketing Center بعد مراجعة جاهزية الإنتاج:

- صفحة الجاهزية مربوطة داخل اللوحة عبر `Marketing Center > جاهزية الإطلاق` وتعمل أيضاً من المسار `/marketing-center/launch-readiness`.
- API الجاهزية يعمل من `GET /api/launch-readiness` ويعرض `score` و`status` و`blocking` و`alerts` بدون كشف أي أسرار.
- لا يتم اعتبار المنصة `جاهز للإطلاق` إذا كان `PUBLIC_APP_URL` أو `APP_URL` لا يستخدم HTTPS أو كان يشير إلى localhost.
- لا يتم اعتبار المنصة جاهزة إذا كانت الأسرار الحرجة ناقصة أو تجريبية: `JWT_SECRET` و`ENCRYPTION_KEY` و`META_APP_ID` و`META_APP_SECRET` و`META_VERIFY_TOKEN` و`META_WEBHOOK_SECRET`.
- يجب تشغيل قاعدة البيانات واستيراد `database/schema.sql` ثم `database/production_launch_migration.sql` ثم `database/seed.sql` قبل الإطلاق.
- يجب ضبط `.env` الحقيقي من `.env.example` بقيم إنتاجية وعدم استخدام قيم `replace-with` أو `change-me`.
- يجب تفعيل `APP_ENV=production` و`APP_DEBUG=false` و`CSRF_ENFORCE=true` في الإنتاج.
- يجب ربط واتساب فعلياً عبر Meta Cloud API أو QR Session قبل اعتماد بند `واتساب جاهز`.
- يجب ربط Webhook على رابط HTTPS: `https://your-domain.com/api/webhooks/whatsapp` مع Verify Token وSignature Secret.
- يجب تشغيل Queue/Redis من `QUEUE_REDIS_URL` وتشغيل معالج الحملات `php bin/process-campaign-queue.php` بجدولة ثابتة.
- يجب التأكد من عمل المسارات العربية الأساسية: `overview` و`whatsapp-setup-center` و`whatsapp-qr` و`chatbot-builder` و`inbox` و`campaign-builder` و`analytics` و`setup-checklist`.
- فحص البناء الحالي للمشروع PHP يتم عبر `php -l` لكل ملفات `app` و`public` و`resources` و`bin`.
- Bridge الخاص بجلسات QR لا يحتوي build منفصل حالياً، وتشغيله يتم عبر `npm --prefix bridge start` بعد تثبيت الاعتمادات.
- قبل الإطلاق التجاري يجب تنفيذ اختبار حقيقي End-to-End: ربط Meta، إرسال رسالة اختبار، استقبال Webhook، تشغيل الرد الآلي، تحويل محادثة لقسم، وإنشاء حملة تجريبية لعملاء لديهم Opt-in فقط.

## Platform Development Roadmap

تم تنظيم التطوير إلى 12 مرحلة مستقلة داخل النظام من صفحة `Marketing Center > خارطة التطوير`:

- المرحلة 1: Core System Foundation.
- المرحلة 2: WhatsApp Setup Center.
- المرحلة 3: Unified Inbox.
- المرحلة 4: Chatbot Builder.
- المرحلة 5: CRM System.
- المرحلة 6: Campaign Builder.
- المرحلة 7: Automation Engine.
- المرحلة 8: AI Knowledge Base.
- المرحلة 9: Analytics & BI.
- المرحلة 10: Billing & SaaS.
- المرحلة 11: Marketplace & Integrations.
- المرحلة 12: Enterprise & Scaling.

كل مرحلة تعرض الهدف، الصفحات المطلوبة، APIs، Database Models، UI/UX، فحص الأمان، اختبار المرحلة، Production Checklist، وLaunch Score. المسار البرمجي للفحص هو `GET /api/platform-roadmap`، ويوجد alias متوافق باسم `GET /api/development-roadmap`.

### Phase 1 - Core System Foundation

تم تثبيت أساس المصادقة والصلاحيات ضمن المرحلة الأولى:

- صفحة تسجيل دخول عربية من `/login`.
- APIs المصادقة:
  - `POST /api/auth/login`
  - `POST /api/auth/logout`
  - `GET /api/auth/me`
- جلسات آمنة مع `session_regenerate_id`.
- تسجيل محاولات الدخول في `login_logs`.
- ربط RBAC بجدول `role_permissions` مع fallback داخلي.
- حماية إجبارية في الإنتاج عبر `AUTH_ENFORCE=true`.
- أداة إنشاء/تحديث Super Admin:

```bash
php bin/create-super-admin.php admin@example.com StrongPassword123! "مدير المنصة"
```

فحص جاهزية المرحلة الأولى من CLI:

```bash
php bin/core-foundation-check.php
```

فحص حالة الطوابير وحفظ Snapshot تشغيلي:

```bash
php bin/queue-health-check.php
```

عند استخدام `QUEUE_CONNECTION=redis` يتم فحص قابلية الاتصال بمنفذ Redis فعلياً، وليس مجرد وجود `QUEUE_REDIS_URL`.

فحص قاعدة البيانات والبيانات الافتراضية للمرحلة الأولى:

```bash
php bin/database-foundation-check.php
```

توليد قالب بيئة إنتاج آمن مع أسرار عشوائية أولية ودومين HTTPS حقيقي:

```bash
php bin/generate-production-env.php https://your-domain.com
```

بعد التوليد يجب ملء قيم Meta الحقيقية وقاعدة البيانات والتخزين والمراقبة قبل نسخ القيم إلى `.env`.
يجب أيضاً إبقاء `APP_ENV=production` و`APP_DEBUG=false` و`AUTH_ENFORCE=true` و`CSRF_ENFORCE=true` مع `RATE_LIMIT_PER_MINUTE` أكبر من صفر.

فحص ملف البيئة قبل الانتقال لأي مرحلة جديدة:

```bash
php bin/verify-production-env.php .env
```

فحص Preflight شامل للمرحلة الأولى قبل فتح المرحلة الثانية:

```bash
php bin/production-preflight.php .env
```

لا تصبح المرحلة الأولى `Production Ready` إلا بعد ضبط HTTPS حقيقي، أسرار قوية في `.env`، وتفعيل Redis/Queue.

## Multi Login Portals

تمت إضافة بوابات دخول متعددة بجانب `/login` القديم بدون كسره:

- `/platform/login` لإدارة المنصة وفرق التشغيل.
- `/store/login` لأصحاب المتاجر ومديري التسويق.
- `/agent/login` لموظفي الدعم والمبيعات والحسابات.
- `/tenant/{slug}/login` لدخول White Label حسب المتجر.
- `/auth/callback` كنقطة استقبال OAuth.

APIs الجديدة:

- `POST /api/auth/platform/login`
- `POST /api/auth/store/login`
- `POST /api/auth/agent/login`
- `POST /api/auth/tenant/login`
- `GET /api/auth/login-branding/{slug}`
- `GET /api/auth/sessions`
- `POST /api/auth/sessions/revoke`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/verify-2fa`

الجداول الرسمية موجودة في `database/multi_login_portals_migration.sql`:

- `platform_users`
- `store_users`
- `login_sessions`
- `login_attempts`
- `password_resets`

كل بوابة تتحقق من الدور قبل إنشاء الجلسة، وتمنع خلط دخول إدارة المنصة مع مستخدمي المتاجر أو الموظفين. صفحة Super Admin تحتوي الآن على قسم `إدارة بوابات الدخول` لعرض روابط المتاجر والجلسات ومحاولات الدخول.

عند ضبط `JWT_SECRET` بقيمة قوية لا تقل عن 32 حرفاً، ترجع بوابات الدخول `access_token` بصيغة JWT موقعة بـ HS256 بجانب جلسة HttpOnly الآمنة.

## Vercel Deployment Guard

تمت إضافة إعداد نشر آمن لـ Vercel حتى لا يتم عرض ملفات PHP كنص خام:

- `api/index.php` هو نقطة دخول Serverless ويستدعي `public/index.php`.
- `vercel.json` يمرر كل المسارات إلى PHP Runtime ويترك `/assets/*` كملفات Static فقط.
- `vercel.json` يثبت التوجيه بصيغة `routes` الرسمية الموصى بها من Runtime `vercel-php`.
- `vercel.json` يضبط `outputDirectory` على `public` لتجاوز إعداد Vercel الافتراضي الذي كان يبحث عن `dist`.
- `package.json` يثبت Node على `22.x` لأن `vercel-php@0.9.0` موثق للعمل على Node 22.
- تمت إضافة `package.json` و`vite.config.js` كحل احتياطي حتى لو بقي Build Command في لوحة Vercel مضبوطاً على `vite build`.
- تمت إضافة `composer.json` بسيط حتى يتعامل PHP Runtime مع المشروع كتطبيق PHP واضح.
- `.vercelignore` يمنع رفع ملفات البيئة والتخزين والحزم المحلية.
- `api/php.ini` يضبط حدود الرفع والذاكرة ويعطل `expose_php`.

مهم: تشغيل المنصة على Vercel يحتاج ضبط Environment Variables داخل لوحة Vercel، خصوصاً:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-vercel-domain.vercel.app
PUBLIC_APP_URL=https://your-vercel-domain.vercel.app
DATABASE_URL=mysql://user:password@host:3306/database
JWT_SECRET=strong-random-secret
ENCRYPTION_KEY=strong-random-key
META_APP_ID=...
META_APP_SECRET=...
META_VERIFY_TOKEN=...
META_WEBHOOK_SECRET=...
```

استخدم قاعدة بيانات MySQL خارجية لأن Vercel Serverless لا يوفر MySQL محلياً ولا تخزين دائم للملفات. خدمة `bridge/` الخاصة بـ WhatsApp QR Session يجب تشغيلها كخدمة Node منفصلة على VPS أو Railway أو Render لأنها تحتاج جلسة طويلة واتصال WebSocket دائم.
