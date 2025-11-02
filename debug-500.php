<?php
/**
 * أداة تصحيح شاملة لخطأ HTTP 500
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تصحيح خطأ HTTP 500</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; }
        .section { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; }
        .section h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 تصحيح خطأ HTTP 500 في الشات بوت</h1>
        
        <div class="section">
            <h2>1️⃣ فحص الملفات الأساسية</h2>
            <?php
            $files = [
                '.env' => 'ملف الإعدادات',
                'config.php' => 'ملف الإعدادات الرئيسي',
                'api_chat.php' => 'API الشات',
                'inc/functions.php' => 'الدوال المساعدة',
                'inc/init_chat_db.php' => 'إنشاء جداول الشات'
            ];
            
            foreach ($files as $file => $name) {
                if (file_exists($file)) {
                    echo "<span class='success'>✅</span> $name ($file)<br>";
                } else {
                    echo "<span class='error'>❌</span> $name ($file) - <strong>غير موجود!</strong><br>";
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>2️⃣ فحص الإعدادات</h2>
            <?php
            try {
                require_once 'config.php';
                require_once 'inc/functions.php';
                
                $config = cfg();
                
                echo "<strong>إعدادات قاعدة البيانات:</strong><br>";
                echo "Driver: " . htmlspecialchars($config->db_driver ?? 'غير معرّف') . "<br>";
                echo "Host: " . htmlspecialchars($config->db_host ?? 'غير معرّف') . "<br>";
                echo "Database: " . htmlspecialchars($config->db_name ?? 'غير معرّف') . "<br>";
                echo "User: " . htmlspecialchars($config->db_user ?? 'غير معرّف') . "<br>";
                echo "Charset: " . htmlspecialchars($config->db_charset ?? 'utf8mb4') . "<br>";
                
                if (empty($config->db_host) || empty($config->db_name)) {
                    echo "<br><span class='error'>❌ الإعدادات ناقصة! تأكد من ملف .env</span>";
                } else {
                    echo "<br><span class='success'>✅ الإعدادات موجودة</span>";
                }
            } catch (Exception $e) {
                echo "<span class='error'>❌ خطأ في تحميل الإعدادات: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>3️⃣ فحص الاتصال بقاعدة البيانات</h2>
            <?php
            try {
                $config = cfg();
                
                $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', 
                    $config->db_host, 
                    $config->db_name, 
                    $config->db_charset ?? 'utf8mb4'
                );
                
                echo "DSN: <span class='code'>" . htmlspecialchars($dsn) . "</span><br><br>";
                
                $pdo = new PDO($dsn, $config->db_user ?? 'root', $config->db_pass ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                
                echo "<span class='success'>✅ الاتصال بقاعدة البيانات نجح!</span>";
                
                // فحص الجداول
                echo "<br><br><strong>فحص الجداول:</strong><br>";
                $tables = ['chat_conversations', 'chat_messages', 'admin_alerts'];
                
                foreach ($tables as $table) {
                    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    if ($stmt->rowCount() > 0) {
                        echo "<span class='success'>✅</span> $table<br>";
                    } else {
                        echo "<span class='warning'>⚠️</span> $table - غير موجود (سيتم إنشاؤه تلقائياً)<br>";
                    }
                }
                
            } catch (PDOException $e) {
                echo "<span class='error'>❌ خطأ الاتصال:</span><br>";
                echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                echo "<br><strong>الحل:</strong><br>";
                echo "1. تأكد من أن MySQL يعمل<br>";
                echo "2. تأكد من أن قاعدة البيانات موجودة<br>";
                echo "3. تأكد من بيانات الاتصال في .env<br>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>4️⃣ اختبار API الشات</h2>
            <?php
            echo "<p>لاختبار API الشات مباشرة، استخدم الأمر التالي:</p>";
            echo "<div class='code'>";
            echo "curl -X POST http://localhost/crosing/api_chat.php \<br>";
            echo "&nbsp;&nbsp;-d 'action=start_conversation' \<br>";
            echo "&nbsp;&nbsp;-d 'customer_name=اختبار' \<br>";
            echo "&nbsp;&nbsp;-d 'customer_email=test@example.com'<br>";
            echo "</div>";
            ?>
        </div>
        
        <div class="section">
            <h2>5️⃣ ملخص الحلول</h2>
            <strong>إذا رأيت خطأ "Database connection failed":</strong>
            <ol>
                <li>افتح ملف <code>.env</code></li>
                <li>تأكد من أن <code>DB_HOST</code> صحيح (عادة: localhost)</li>
                <li>تأكد من أن <code>DB_NAME</code> صحيح (عادة: azzm_sin)</li>
                <li>تأكد من أن <code>DB_USER</code> صحيح (عادة: root)</li>
                <li>تأكد من أن MySQL يعمل</li>
                <li>تأكد من أن قاعدة البيانات موجودة</li>
            </ol>
            
            <strong>إذا رأيت خطأ "Database configuration is missing":</strong>
            <ol>
                <li>تأكد من وجود ملف <code>.env</code></li>
                <li>تأكد من أن الملف يحتوي على <code>DB_HOST</code> و <code>DB_NAME</code></li>
                <li>أعد تحميل الصفحة</li>
            </ol>
        </div>
        
        <div class="section">
            <h2>6️⃣ الخطوة التالية</h2>
            <p>بعد التأكد من أن جميع الاختبارات نجحت:</p>
            <ol>
                <li>افتح الصفحة الرئيسية: <a href="index.php" target="_blank">http://localhost/crosing/index.php</a></li>
                <li>افتح نافذة الشات</li>
                <li>أرسل رسالة بسيطة</li>
                <li>تحقق من أن الرد يظهر بدون أخطاء</li>
            </ol>
        </div>
    </div>
</body>
</html>
