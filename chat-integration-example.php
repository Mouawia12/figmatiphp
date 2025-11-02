<?php
/**
 * chat-integration-example.php
 * مثال على دمج نظام الدردشة والتتبع في الموقع
 * 
 * هذا الملف يوضح كيفية:
 * 1. إضافة الدردشة الحية للعملاء
 * 2. تتبع خطوات العميل
 * 3. إرسال التنبيهات للمدير
 */

require __DIR__ . '/inc/functions.php';
$config = cfg();
$siteTitle = $config->site_title ?? 'عزم الإنجاز';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>مثال على دمج الدردشة - <?= e($siteTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="app-bg">

<header class="shadow-sm bg-white sticky-top">
    <nav class="navbar container navbar-expand-lg py-3">
        <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('')) ?>">
            <?= e($siteTitle) ?> – مثال الدردشة
        </a>
    </nav>
</header>

<main class="section-pad">
    <div class="container" style="max-width: 900px;">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="mb-4">🚀 دمج نظام الدردشة والتتبع</h2>
                        
                        <h4 class="mt-4">الخطوة 1: إضافة الدردشة للعملاء</h4>
                        <p>أضف هذا الكود قبل إغلاق الـ body tag:</p>
                        <pre><code>&lt;!-- نظام الدردشة الذكي --&gt;
&lt;script src="<?= e(asset_href('assets/chatbot.js')) ?>"&gt;&lt;/script&gt;</code></pre>
                        
                        <h4 class="mt-4">الخطوة 2: تتبع خطوات العميل</h4>
                        <p>أضف هذا الكود في صفحات العملية:</p>
                        <pre><code>&lt;!-- مكتبة تتبع الخطوات --&gt;
&lt;script src="<?= e(asset_href('assets/step-tracker.js')) ?>"&gt;&lt;/script&gt;

&lt;script&gt;
// إنشاء متتبع الخطوات
const tracker = new StepTracker({
    customerId: 'customer_123',
    conversationId: 1,
    apiUrl: '<?= e(app_href('api_chat.php')) ?>'
});

// تسجيل الخطوات
tracker.trackStep('تعبئة البيانات الشخصية', 1, 'in_progress');

// عند إكمال الخطوة
tracker.completeStep('تعبئة البيانات الشخصية', 1);

// إذا توقف العميل
tracker.abandonStep('اختيار الخطة', 2);

// مراقبة عدم النشاط (5 دقائق)
tracker.monitorInactivity(300);

// مراقبة الخروج من الصفحة
tracker.monitorPageExit();
&lt;/script&gt;</code></pre>
                        
                        <h4 class="mt-4">الخطوة 3: الوصول لوحة الدردشة</h4>
                        <p>المديرون يمكنهم الوصول إلى لوحة الدردشة من:</p>
                        <a href="<?= e(app_href('admin/chat.php')) ?>" class="btn btn-primary">
                            👉 لوحة الدردشة الحية
                        </a>
                    </div>
                </div>
                
                <!-- مثال تفاعلي -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h4 class="mb-3">📝 مثال تفاعلي</h4>
                        <p class="text-muted mb-3">جرّب النظام بملء النموذج أدناه:</p>
                        
                        <form id="demo-form" class="vstack gap-3">
                            <div>
                                <label class="form-label">اسمك</label>
                                <input type="text" class="form-control" id="demo-name" value="أحمد محمد">
                            </div>
                            
                            <div>
                                <label class="form-label">بريدك الإلكتروني</label>
                                <input type="email" class="form-control" id="demo-email" value="ahmed@example.com">
                            </div>
                            
                            <div>
                                <label class="form-label">الخطوة الحالية</label>
                                <select class="form-select" id="demo-step">
                                    <option value="1">الخطوة 1: تعبئة البيانات الشخصية</option>
                                    <option value="2">الخطوة 2: اختيار الخطة</option>
                                    <option value="3">الخطوة 3: تأكيد الدفع</option>
                                    <option value="4">الخطوة 4: إكمال الطلب</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="form-label">الحالة</label>
                                <select class="form-select" id="demo-status">
                                    <option value="in_progress">جاري</option>
                                    <option value="completed">مكتمل</option>
                                    <option value="abandoned">متوقف</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">تسجيل الخطوة</button>
                        </form>
                        
                        <div id="demo-result" class="alert alert-info mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- الشريط الجانبي -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">📚 الميزات الرئيسية</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">✅ دردشة حية 24/7</li>
                            <li class="mb-2">✅ بوت ذكي متعدد المهام</li>
                            <li class="mb-2">✅ تتبع خطوات العميل</li>
                            <li class="mb-2">✅ تنبيهات فورية للمدير</li>
                            <li class="mb-2">✅ تحليل سلوك العميل</li>
                            <li class="mb-2">✅ نصائح مبيعات ذكية</li>
                            <li class="mb-2">✅ دعم فني متخصص</li>
                            <li class="mb-2">✅ تحليل مالي</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="mb-3">🔧 المتطلبات</h5>
                        <ul class="list-unstyled small">
                            <li class="mb-2">✓ PHP 7.4+</li>
                            <li class="mb-2">✓ SQLite أو MySQL</li>
                            <li class="mb-2">✓ JavaScript مفعل</li>
                            <li class="mb-2">✓ HTTPS موصى به</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="mb-3">📞 الدعم</h5>
                        <p class="small text-muted mb-0">
                            للمساعدة والدعم الفني، يرجى التواصل مع فريق الدعم عبر الدردشة الحية.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- نظام الدردشة -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
<script src="<?= e(asset_href('assets/step-tracker.js')) ?>"></script>

<script>
    // معالج النموذج التجريبي
    document.getElementById('demo-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = document.getElementById('demo-name').value;
        const email = document.getElementById('demo-email').value;
        const step = document.getElementById('demo-step').value;
        const status = document.getElementById('demo-status').value;
        
        try {
            // أولاً: بدء محادثة
            let response = await fetch('<?= e(app_href('api_chat.php')) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'start_conversation',
                    customer_name: name,
                    customer_email: email
                })
            });
            
            let data = await response.json();
            if (!data.success) throw new Error('فشل بدء المحادثة');
            
            const convId = data.data.conversation_id;
            const customerId = data.data.customer_id;
            
            // ثانياً: تسجيل الخطوة
            response = await fetch('<?= e(app_href('api_chat.php')) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'track_step',
                    conversation_id: convId,
                    customer_id: customerId,
                    step_name: `الخطوة ${step}`,
                    step_number: step,
                    status: status
                })
            });
            
            data = await response.json();
            if (data.success) {
                const result = document.getElementById('demo-result');
                result.innerHTML = `
                    ✅ تم تسجيل الخطوة بنجاح!<br>
                    <small>
                        معرف المحادثة: ${convId}<br>
                        معرف العميل: ${customerId}<br>
                        الخطوة: ${step} - ${status}
                    </small>
                `;
                result.style.display = 'block';
            }
        } catch (error) {
            const result = document.getElementById('demo-result');
            result.className = 'alert alert-danger mt-3';
            result.innerHTML = `❌ خطأ: ${error.message}`;
            result.style.display = 'block';
        }
    });
</script>

<footer class="footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
            <span>© <?= date('Y') ?> <?= e($siteTitle) ?>. جميع الحقوق محفوظة</span>
            <a class="link-secondary" href="<?= e(app_href('')) ?>">العودة للصفحة الرئيسية</a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
