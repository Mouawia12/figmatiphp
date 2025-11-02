<?php
/**
 * أداة تشخيص لخطأ 500 في login.php
 * استخدم هذا الملف لفهم سبب الخطأ
 */

// إرسال headers أولاً قبل أي شيء
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');

// تفعيل عرض الأخطاء
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// إعداد سجل الأخطاء
$errorLogFile = __DIR__ . '/php_errors.log';
ini_set('error_log', $errorLogFile);
ini_set('log_errors', '1');

// بدء output buffering لتجنب مشاكل headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تشخيص login.php</title>
    <style>
        body { font-family: Arial, Tahoma; margin: 20px; background: #f5f5f5; direction: rtl; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 تشخيص خطأ 500 في login.php</h1>

        <div class="section">
            <h2>1️⃣ فحص الملفات الأساسية</h2>
            <?php
            $files = [
                '.env' => 'ملف الإعدادات',
                'config.php' => 'ملف الإعدادات الرئيسي',
                'login.php' => 'صفحة تسجيل الدخول',
                'inc/auth.php' => 'ملف المصادقة',
                'inc/db.php' => 'ملف قاعدة البيانات',
                'inc/functions.php' => 'الدوال المساعدة',
                'partials/header.php' => 'رأس الصفحة',
                'partials/footer.php' => 'تذييل الصفحة'
            ];
            
            foreach ($files as $file => $name) {
                $fullPath = __DIR__ . '/' . $file;
                if (file_exists($fullPath)) {
                    $readable = is_readable($fullPath) ? '<span class="success">قابل للقراءة</span>' : '<span class="error">غير قابل للقراءة!</span>';
                    echo "<span class='success'>✅</span> $name ($file) - $readable<br>";
                } else {
                    echo "<span class='error'>❌</span> $name ($file) - <strong>غير موجود!</strong><br>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>2️⃣ فحص ملف .env</h2>
            <?php
            $envFile = __DIR__ . '/.env';
            if (file_exists($envFile)) {
                echo "<span class='success'>✅</span> ملف .env موجود<br>";
                if (is_readable($envFile)) {
                    echo "<span class='success'>✅</span> ملف .env قابل للقراءة<br>";
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    echo "<span class='info'>📄 عدد السطور: " . count($lines) . "</span><br><br>";
                    
                    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'AUTHENTICA_API_KEY'];
                    echo "<strong>المتغيرات المطلوبة:</strong><br>";
                    foreach ($required as $var) {
                        $value = getenv($var);
                        if ($value !== false && $value !== '') {
                            $masked = in_array($var, ['DB_PASS', 'AUTHENTICA_API_KEY']) ? '***' : $value;
                            echo "<span class='success'>✅</span> $var = $masked<br>";
                        } else {
                            echo "<span class='error'>❌</span> $var - غير محدد!<br>";
                        }
                    }
                } else {
                    echo "<span class='error'>❌</span> ملف .env غير قابل للقراءة! تحقق من التصريحات (chmod 644)<br>";
                }
            } else {
                echo "<span class='error'>❌</span> ملف .env غير موجود!<br>";
                echo "<span class='warning'>⚠️</span> هذا قد يكون سبب المشكلة<br>";
            }
            ?>
        </div>

        <div class="section">
            <h2>3️⃣ اختبار تحميل config.php</h2>
            <?php
            try {
                $APP = require_once __DIR__ . '/config.php';
                echo "<span class='success'>✅</span> تم تحميل config.php بنجاح<br>";
                echo "<pre>";
                echo "DB Driver: " . ($APP->db_driver ?? 'غير محدد') . "\n";
                echo "DB Host: " . ($APP->db_host ?? 'غير محدد') . "\n";
                echo "DB Name: " . ($APP->db_name ?? 'غير محدد') . "\n";
                echo "DB User: " . ($APP->db_user ?? 'غير محدد') . "\n";
                echo "DB Pass: " . (!empty($APP->db_pass) ? '***' : 'غير محدد') . "\n";
                echo "</pre>";
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل تحميل config.php:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>4️⃣ اختبار تحميل inc/functions.php</h2>
            <?php
            try {
                require_once __DIR__ . '/inc/functions.php';
                echo "<span class='success'>✅</span> تم تحميل inc/functions.php بنجاح<br>";
                
                if (function_exists('app_href')) {
                    echo "<span class='success'>✅</span> دالة app_href() موجودة<br>";
                    echo "<span class='info'>📝 app_href('test'): " . app_href('test') . "</span><br>";
                } else {
                    echo "<span class='error'>❌</span> دالة app_href() غير موجودة!<br>";
                }
                
                if (function_exists('asset_href')) {
                    echo "<span class='success'>✅</span> دالة asset_href() موجودة<br>";
                    echo "<span class='info'>📝 asset_href('assets/styles.css'): " . asset_href('assets/styles.css') . "</span><br>";
                } else {
                    echo "<span class='error'>❌</span> دالة asset_href() غير موجودة!<br>";
                }
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل تحميل inc/functions.php:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>5️⃣ اختبار تحميل inc/auth.php</h2>
            <?php
            try {
                require_once __DIR__ . '/inc/auth.php';
                echo "<span class='success'>✅</span> تم تحميل inc/auth.php بنجاح<br>";
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل تحميل inc/auth.php:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>6️⃣ اختبار الاتصال بقاعدة البيانات</h2>
            <?php
            try {
                if (!isset($APP)) {
                    $APP = require_once __DIR__ . '/config.php';
                }
                require_once __DIR__ . '/inc/db.php';
                
                $db = db();
                echo "<span class='success'>✅</span> تم الاتصال بقاعدة البيانات بنجاح<br>";
                echo "<span class='info'>📝 PDO Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "</span><br>";
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل الاتصال بقاعدة البيانات:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>7️⃣ اختبار تحميل partials/header.php</h2>
            <?php
            try {
                // محاكاة المتغيرات المطلوبة
                $siteTitle = $siteTitle ?? ($APP->site_title ?? 'شركة عزم الإنجاز');
                $modelName = $modelName ?? '';
                $siteDesc = $siteDesc ?? '';
                $isAuth = $isAuth ?? false;
                session_start();
                
                ob_start();
                $headerLoaded = @include __DIR__ . '/partials/header.php';
                $headerOutput = ob_get_clean();
                
                if ($headerLoaded !== false) {
                    echo "<span class='success'>✅</span> تم تحميل partials/header.php بنجاح<br>";
                    echo "<span class='info'>📝 حجم المخرجات: " . strlen($headerOutput) . " بايت</span><br>";
                } else {
                    echo "<span class='error'>❌</span> فشل تحميل partials/header.php<br>";
                    if (!empty($headerOutput)) {
                        echo "<pre class='error'>" . htmlspecialchars(substr($headerOutput, 0, 500)) . "</pre>";
                    }
                }
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل تحميل partials/header.php:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>8️⃣ اختبار تحميل login.php</h2>
            <?php
            try {
                // إعادة تعيين المتغيرات
                unset($APP);
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_POST = [];
                $_GET = [];
                
                ob_start();
                $loginLoaded = @include __DIR__ . '/login.php';
                $loginOutput = ob_get_clean();
                
                if ($loginLoaded !== false) {
                    echo "<span class='success'>✅</span> تم تحميل login.php بدون أخطاء واضحة<br>";
                    echo "<span class='info'>📝 حجم المخرجات: " . strlen($loginOutput) . " بايت</span><br>";
                } else {
                    echo "<span class='error'>❌</span> فشل تحميل login.php<br>";
                    if (!empty($loginOutput)) {
                        $preview = substr($loginOutput, 0, 1000);
                        echo "<pre class='error'>" . htmlspecialchars($preview) . (strlen($loginOutput) > 1000 ? "\n...(مقطوع)" : "") . "</pre>";
                    }
                }
            } catch (Throwable $e) {
                echo "<span class='error'>❌</span> فشل تحميل login.php:<br>";
                echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            ?>
        </div>

        <div class="section">
            <h2>9️⃣ معلومات PHP والسيرفر</h2>
            <pre>
PHP Version: <?= PHP_VERSION ?>

Server Software: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد' ?>

Document Root: <?= $_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد' ?>

Script Name: <?= $_SERVER['SCRIPT_NAME'] ?? 'غير محدد' ?>

Current Directory: <?= __DIR__ ?>

Error Log: <?= ini_get('error_log') ?>

Display Errors: <?= ini_get('display_errors') ?>

Error Reporting: <?= error_reporting() ?>
            </pre>
        </div>

        <div class="section">
            <h2>🔟 التصريحات (Permissions)</h2>
            <?php
            $dirs = [
                '.' => 'المجلد الجذر',
                'uploads' => 'مجلد الرفع',
                'inc' => 'مجلد inc',
                'partials' => 'مجلد partials',
                'data' => 'مجلد data (إذا موجود)'
            ];
            
            foreach ($dirs as $dir => $name) {
                $fullPath = __DIR__ . '/' . $dir;
                if (is_dir($fullPath)) {
                    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                    $readable = is_readable($fullPath) ? '✅' : '❌';
                    $writable = is_writable($fullPath) ? '✅' : '❌';
                    echo "<span class='info'>📁</span> $name ($dir): $perms - قراءة: $readable | كتابة: $writable<br>";
                }
            }
            ?>
        </div>

    </div>
</body>
</html>
<?php
ob_end_flush();
?>

