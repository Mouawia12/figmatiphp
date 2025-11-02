<?php
/**
 * admin/setup-chat.php
 * صفحة إعداد نظام الدردشة
 */

require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/init_chat_db.php';

session_start();
if (empty($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . app_href('login.php'));
    exit;
}

$config = cfg();
$siteTitle = $config->site_title ?? 'عزم الإنجاز';

// محاولة تهيئة قاعدة البيانات
$db_initialized = false;
$db_error = '';

try {
    init_chat_database();
    $db_initialized = true;
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إعداد الدردشة - <?= e($siteTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="app-bg">

<header class="shadow-sm bg-white sticky-top">
    <nav class="navbar container-fluid navbar-expand-lg py-3">
        <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('admin/')) ?>">
            <?= e($siteTitle) ?> – إعداد الدردشة
        </a>
    </nav>
</header>

<main class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- الخطوة 1: قاعدة البيانات -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number" style="
                            width: 40px;
                            height: 40px;
                            background: <?= $db_initialized ? '#28a745' : '#dc3545' ?>;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            margin-left: 15px;
                        ">
                            <?= $db_initialized ? '✓' : '✕' ?>
                        </div>
                        <h4 class="mb-0">الخطوة 1: تهيئة قاعدة البيانات</h4>
                    </div>
                    
                    <?php if ($db_initialized): ?>
                        <div class="alert alert-success border-0">
                            ✅ تم تهيئة قاعدة البيانات بنجاح!
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger border-0">
                            ❌ خطأ: <?= e($db_error) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الخطوة 2: إضافة الدردشة للموقع -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number" style="
                            width: 40px;
                            height: 40px;
                            background: #667eea;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            margin-left: 15px;
                        ">
                            2
                        </div>
                        <h4 class="mb-0">الخطوة 2: إضافة الدردشة للموقع</h4>
                    </div>
                    
                    <p class="text-muted mb-3">أضف هذا الكود قبل إغلاق <code>&lt;/body&gt;</code> في صفحاتك:</p>
                    
                    <div class="bg-dark text-light p-3 rounded mb-3" style="overflow-x: auto;">
                        <code style="font-size: 12px;">
&lt;!-- نظام الدردشة الذكي --&gt;<br>
&lt;script src="/crosing/assets/chatbot.js"&gt;&lt;/script&gt;
                        </code>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-primary" onclick="copyCode()">
                        📋 نسخ الكود
                    </button>
                </div>
            </div>
            
            <!-- الخطوة 3: تتبع الخطوات (اختياري) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number" style="
                            width: 40px;
                            height: 40px;
                            background: #667eea;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            margin-left: 15px;
                        ">
                            3
                        </div>
                        <h4 class="mb-0">الخطوة 3: تتبع خطوات العميل (اختياري)</h4>
                    </div>
                    
                    <p class="text-muted mb-3">لتتبع خطوات العميل وإرسال التنبيهات، أضف هذا الكود:</p>
                    
                    <div class="bg-dark text-light p-3 rounded mb-3" style="overflow-x: auto;">
                        <code style="font-size: 11px;">
&lt;script src="/crosing/assets/step-tracker.js"&gt;&lt;/script&gt;<br>
&lt;script&gt;<br>
&nbsp;&nbsp;const tracker = new StepTracker({<br>
&nbsp;&nbsp;&nbsp;&nbsp;customerId: 'customer_123',<br>
&nbsp;&nbsp;&nbsp;&nbsp;apiUrl: '/crosing/api_chat.php'<br>
&nbsp;&nbsp;});<br>
&nbsp;&nbsp;<br>
&nbsp;&nbsp;// تسجيل خطوة<br>
&nbsp;&nbsp;tracker.trackStep('اسم الخطوة', 1, 'in_progress');<br>
&lt;/script&gt;
                        </code>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-primary" onclick="copyStepCode()">
                        📋 نسخ الكود
                    </button>
                </div>
            </div>
            
            <!-- الخطوة 4: الوصول لوحة الدردشة -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number" style="
                            width: 40px;
                            height: 40px;
                            background: #667eea;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            margin-left: 15px;
                        ">
                            4
                        </div>
                        <h4 class="mb-0">الخطوة 4: لوحة التحكم</h4>
                    </div>
                    
                    <p class="text-muted mb-3">الآن يمكنك الوصول إلى لوحة الدردشة الحية:</p>
                    
                    <a href="<?= e(app_href('admin/chat.php')) ?>" class="btn btn-primary">
                        💬 فتح لوحة الدردشة الحية
                    </a>
                </div>
            </div>
            
            <!-- الموارد الإضافية -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">📚 موارد إضافية</h5>
                    
                    <div class="list-group list-group-flush">
                        <a href="<?= e(app_href('chat-integration-example.php')) ?>" class="list-group-item list-group-item-action">
                            📖 مثال تفاعلي كامل
                        </a>
                        <a href="<?= e(app_href('CHATBOT_SETUP.md')) ?>" class="list-group-item list-group-item-action">
                            📝 دليل التثبيت الكامل
                        </a>
                        <a href="<?= e(app_href('admin/')) ?>" class="list-group-item list-group-item-action">
                            🏠 العودة للوحة التحكم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function copyCode() {
        const code = `<!-- نظام الدردشة الذكي -->
<script src="/crosing/assets/chatbot.js"><\/script>`;
        
        navigator.clipboard.writeText(code).then(() => {
            alert('✅ تم نسخ الكود');
        }).catch(() => {
            alert('⚠️ تعذر النسخ - انسخ يدويًا');
        });
    }
    
    function copyStepCode() {
        const code = `<script src="/crosing/assets/step-tracker.js"><\/script>
<script>
    const tracker = new StepTracker({
        customerId: 'customer_123',
        apiUrl: '/crosing/api_chat.php'
    });
    
    // تسجيل خطوة
    tracker.trackStep('اسم الخطوة', 1, 'in_progress');
<\/script>`;
        
        navigator.clipboard.writeText(code).then(() => {
            alert('✅ تم نسخ الكود');
        }).catch(() => {
            alert('⚠️ تعذر النسخ - انسخ يدويًا');
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
