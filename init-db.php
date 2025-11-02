<?php
/**
 * init-db.php
 * صفحة تهيئة قاعدة البيانات
 */

require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/init_chat_db.php';

$config = cfg();
$siteTitle = $config->site_title ?? 'عزم الإنجاز';

// تهيئة قاعدة البيانات
try {
    init_chat_database();
    $success = true;
    $message = '✅ تم تهيئة قاعدة البيانات بنجاح!';
} catch (Exception $e) {
    $success = false;
    $message = '❌ خطأ: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title><?= e($siteTitle) ?> – تهيئة قاعدة البيانات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-5 text-center">
                    <h2 class="mb-4">🗄️ تهيئة قاعدة البيانات</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success success border">
                            <h4>✅ نجح!</h4>
                            <p><?= $message ?></p>
                        </div>
                        
                        <div class="mt-4">
                            <p>تم إنشاء جميع الجداول المطلوبة:</p>
                            <ul class="text-start small">
                                <li>✓ chat_conversations</li>
                                <li>✓ chat_messages</li>
                                <li>✓ customer_steps</li>
                                <li>✓ admin_alerts</li>
                                <li>✓ customer_analytics</li>
                                <li>✓ daily_stats</li>
                                <li>✓ frequent_questions</li>
                                <li>✓ chat_analytics</li>
                                <li>✓ question_log</li>
                                <li>✓ ai_training_data</li>
                                <li>✓ performance_metrics</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= e(app_href('')) ?>" class="btn btn-primary btn-lg">
                                🏠 الرجوع للرئيسية
                            </a>
                            <a href="<?= e(app_href('admin/chat.php')) ?>" class="btn btn-success btn-lg ms-2">
                                💬 الذهاب للدردشة
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger error border">
                            <h4>❌ خطأ!</h4>
                            <p><?= $message ?></p>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-muted">تأكد من:</p>
                            <ul class="text-start small">
                                <li>✓ قاعدة البيانات موجودة</li>
                                <li>✓ صلاحيات الكتابة متاحة</li>
                                <li>✓ الاتصال بقاعدة البيانات يعمل</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= e(app_href('init-db.php')) ?>" class="btn btn-warning">
                                🔄 حاول مرة أخرى
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
