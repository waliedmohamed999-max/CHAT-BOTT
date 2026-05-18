INSERT INTO stores (id, name, slug, plan, status, created_at, updated_at)
VALUES (1, 'المتجر الرئيسي', 'main-store', 'professional', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug), plan = VALUES(plan), status = VALUES(status), updated_at = NOW();

INSERT INTO users (id, store_id, name, email, password_hash, role, created_at, updated_at)
VALUES (1, 1, 'مدير المنصة', 'admin@marketing-center.local', '$2y$10$VKE7iv2aGEGityvnfJE.P./feZ/sWjIBvgeU4HNVWYWB5iWWWAKdi', 'owner', NOW(), NOW())
ON DUPLICATE KEY UPDATE store_id = VALUES(store_id), name = VALUES(name), role = VALUES(role), updated_at = NOW();

INSERT INTO platform_users (name, email, password_hash, role, status, created_at, updated_at)
VALUES ('مدير المنصة', 'platform@marketing-center.local', '$2y$10$VKE7iv2aGEGityvnfJE.P./feZ/sWjIBvgeU4HNVWYWB5iWWWAKdi', 'super_admin', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role), status = VALUES(status), updated_at = NOW();

INSERT INTO store_users (store_id, name, email, password_hash, role, status, created_at, updated_at) VALUES
(1, 'مالك المتجر', 'owner@main-store.local', '$2y$10$VKE7iv2aGEGityvnfJE.P./feZ/sWjIBvgeU4HNVWYWB5iWWWAKdi', 'owner', 'active', NOW(), NOW()),
(1, 'موظف الدعم', 'agent@main-store.local', '$2y$10$VKE7iv2aGEGityvnfJE.P./feZ/sWjIBvgeU4HNVWYWB5iWWWAKdi', 'support_agent', 'active', NOW(), NOW()),
(1, 'مدير بوابة المتجر', 'tenant@main-store.local', '$2y$10$VKE7iv2aGEGityvnfJE.P./feZ/sWjIBvgeU4HNVWYWB5iWWWAKdi', 'admin', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role), status = VALUES(status), updated_at = NOW();

INSERT INTO workspaces (id, store_id, name, slug, status, created_at, updated_at)
VALUES (1, 1, 'مساحة العمل الرئيسية', 'main-workspace', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), updated_at = NOW();

INSERT INTO workspace_members (store_id, workspace_id, user_id, role, status, created_at, updated_at)
VALUES (1, 1, 1, 'owner', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status), updated_at = NOW();

INSERT INTO role_permissions (role_key, permission_key, description, created_at, updated_at) VALUES
('owner', '*', 'كل الصلاحيات', NOW(), NOW()),
('admin', 'meta.connect', 'ربط Meta وواتساب', NOW(), NOW()),
('admin', 'campaign.launch', 'إطلاق الحملات', NOW(), NOW()),
('admin', 'billing.manage', 'إدارة الفواتير', NOW(), NOW()),
('marketing_manager', 'campaign.create', 'إنشاء الحملات', NOW(), NOW()),
('marketing_manager', 'templates.manage', 'إدارة القوالب', NOW(), NOW()),
('support_agent', 'inbox.reply', 'الرد على المحادثات', NOW(), NOW()),
('viewer', 'analytics.view', 'عرض التقارير فقط', NOW(), NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = NOW();

INSERT INTO departments (store_id, name, slug, color, welcome_message, away_message, working_hours, priority, is_active, auto_tag, created_at, updated_at) VALUES
(1, 'المبيعات', 'sales', '#2f80ed', 'يسعدنا مساعدتك في المبيعات.', 'فريق المبيعات خارج ساعات العمل حالياً.', '09:00-18:00', 'high', 1, 'sales', NOW(), NOW()),
(1, 'الدعم الفني', 'support', '#25d366', 'من فضلك اختر نوع المشكلة التي تواجهك.', 'فريق الدعم خارج ساعات العمل حالياً.', '09:00-18:00', 'normal', 1, 'support', NOW(), NOW()),
(1, 'الطلبات والشحن', 'orders', '#f59e0b', 'يمكنك متابعة طلبك من هنا. أرسل رقم الطلب.', 'فريق الطلبات خارج ساعات العمل حالياً.', '09:00-18:00', 'normal', 1, 'orders', NOW(), NOW()),
(1, 'الحسابات والفواتير', 'billing', '#7c3aed', 'اختر الخدمة المطلوبة للحسابات أو الفواتير.', 'فريق الحسابات خارج ساعات العمل حالياً.', '09:00-18:00', 'normal', 1, 'billing', NOW(), NOW()),
(1, 'الشكاوى', 'complaints', '#ef4444', 'نأسف لسماع ذلك. سيتم تحويلك للقسم المختص.', 'سيتم متابعة الشكوى في أقرب وقت.', '09:00-18:00', 'urgent', 1, 'complaint', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), welcome_message = VALUES(welcome_message), away_message = VALUES(away_message), updated_at = NOW();

INSERT INTO subscriptions (store_id, plan_key, status, billing_cycle, trial_ends_at, current_period_starts_at, current_period_ends_at, auto_renew, created_at, updated_at)
VALUES (1, 'professional', 'trialing', 'monthly', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE plan_key = VALUES(plan_key), status = VALUES(status), current_period_ends_at = VALUES(current_period_ends_at), updated_at = NOW();

INSERT INTO whatsapp_templates (store_id, name, category, language, status, header, body, footer, buttons_json, components_json, created_at, updated_at) VALUES
(1, 'welcome_offer_ar', 'MARKETING', 'ar', 'pending', 'عرض خاص', 'مرحباً {{1}}، عرضك جاهز للاستخدام. اكتب إلغاء لإيقاف الرسائل.', 'Marketing Center', JSON_ARRAY(), JSON_ARRAY(), NOW(), NOW()),
(1, 'order_followup_ar', 'UTILITY', 'ar', 'pending', 'متابعة الطلب', 'مرحباً {{1}}، رقم طلبك {{2}} قيد المتابعة وسنرسل لك أي تحديث.', 'خدمة العملاء', JSON_ARRAY(), JSON_ARRAY(), NOW(), NOW()),
(1, 'abandoned_cart_ar', 'MARKETING', 'ar', 'pending', 'سلتك بانتظارك', 'مرحباً {{1}}، تركت منتجات في السلة. استخدم الكود {{2}} لإكمال الشراء.', 'اكتب إلغاء لإيقاف الرسائل', JSON_ARRAY(), JSON_ARRAY(), NOW(), NOW())
ON DUPLICATE KEY UPDATE body = VALUES(body), status = VALUES(status), updated_at = NOW();
