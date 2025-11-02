<?php
/**
 * أداة تشخيص شاملة لنظام الشات بوت
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تشخيص نظام الشات بوت</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ddd; }
        .test.pass { border-left-color: #28a745; }
        .test.fail { border-left-color: #dc3545; }
        .test.warn { border-left-color: #ffc107; }
        .test h3 { margin: 0 0 10px 0; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status.pass { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
        .status.warn { background: #fff3cd; color: #856404; }
        .details { margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px; font-size: 12px; }
        h1 { color: #333; }
        .summary { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 تشخيص نظام الشات بوت</h1>
        
        <?php
        $tests = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        // 1. اختبر ملف .env
        echo '<div class="test ' . (file_exists('.env') ? 'pass' : 'fail') . '">';
        echo '<h3>1️⃣ ملف .env</h3>';
        if (file_exists('.env')) {
            echo '<span class="status pass">✅ موجود</span>';
            $passed++;
        } else {
            echo '<span class="status fail">❌ غير موجود</span>';
            $failed++;
        }
        echo '</div>';
        
        // 2. اختبر تحميل الإعدادات
        echo '<div class="test ' . (function_exists('cfg') ? 'pass' : 'fail') . '">';
        echo '<h3>2️⃣ تحميل الإعدادات</h3>';
        try {
            require_once 'inc/functions.php';
            $config = cfg();
            echo '<span class="status pass">✅ تم التحميل</span>';
            echo '<div class="details">';
            echo 'قاعدة البيانات: ' . htmlspecialchars($config->db_name) . '<br>';
            echo 'المضيف: ' . htmlspecialchars($config->db_host) . '<br>';
            echo 'المستخدم: ' . htmlspecialchars($config->db_user) . '<br>';
            echo '</div>';
            $passed++;
        } catch (Exception $e) {
            echo '<span class="status fail">❌ خطأ: ' . htmlspecialchars($e->getMessage()) . '</span>';
            $failed++;
        }
        echo '</div>';
        
        // 3. اختبر الاتصال بقاعدة البيانات
        echo '<div class="test">';
        echo '<h3>3️⃣ الاتصال بقاعدة البيانات</h3>';
        try {
            $config = cfg();
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', 
                $config->db_host, 
                $config->db_name, 
                $config->db_charset
            );
            $pdo = new PDO($dsn, $config->db_user, $config->db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            echo '<span class="status pass">✅ متصل</span>';
            echo '<div class="details">اتصال MySQL نجح</div>';
            $passed++;
            
            // 4. اختبر الجداول
            echo '</div><div class="test">';
            echo '<h3>4️⃣ جداول قاعدة البيانات</h3>';
            
            $tables = ['chat_conversations', 'chat_messages', 'admin_alerts'];
            $allTablesExist = true;
            
            foreach ($tables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                $exists = $stmt->rowCount() > 0;
                
                if ($exists) {
                    echo "✅ $table<br>";
                } else {
                    echo "❌ $table<br>";
                    $allTablesExist = false;
                }
            }
            
            if ($allTablesExist) {
                echo '<span class="status pass">✅ جميع الجداول موجودة</span>';
                $passed++;
            } else {
                echo '<span class="status fail">❌ بعض الجداول غير موجودة</span>';
                $failed++;
            }
            
        } catch (PDOException $e) {
            echo '<span class="status fail">❌ خطأ الاتصال: ' . htmlspecialchars($e->getMessage()) . '</span>';
            echo '<div class="details">';
            echo 'تأكد من:<br>';
            echo '- أن MySQL يعمل<br>';
            echo '- أن قاعدة البيانات موجودة<br>';
            echo '- أن بيانات الاتصال صحيحة<br>';
            echo '</div>';
            $failed++;
        }
        echo '</div>';
        
        // 5. اختبر ملفات الشات بوت
        echo '<div class="test">';
        echo '<h3>5️⃣ ملفات الشات بوت</h3>';
        
        $files = [
            'api_chat.php' => 'API الشات',
            'assets/chatbot.js' => 'واجهة الشات',
            'assets/ai-decorator-module.js' => 'وحدة الديكور'
        ];
        
        $allFilesExist = true;
        foreach ($files as $file => $name) {
            if (file_exists($file)) {
                echo "✅ $name ($file)<br>";
            } else {
                echo "❌ $name ($file)<br>";
                $allFilesExist = false;
            }
        }
        
        if ($allFilesExist) {
            echo '<span class="status pass">✅ جميع الملفات موجودة</span>';
            $passed++;
        } else {
            echo '<span class="status fail">❌ بعض الملفات غير موجودة</span>';
            $failed++;
        }
        echo '</div>';
        
        // 6. اختبر مفتاح OpenAI
        echo '<div class="test">';
        echo '<h3>6️⃣ مفتاح OpenAI</h3>';
        
        $apiKey = getenv('OPENAI_API_KEY');
        if ($apiKey && $apiKey !== 'YOUR_OPENAI_API_KEY' && strlen($apiKey) > 20) {
            echo '<span class="status pass">✅ مفتاح صحيح</span>';
            echo '<div class="details">المفتاح موجود وصحيح</div>';
            $passed++;
        } else {
            echo '<span class="status warn">⚠️ مفتاح غير صحيح</span>';
            echo '<div class="details">';
            echo 'الشات بوت سيستخدم الردود الافتراضية<br>';
            echo 'لتفعيل OpenAI، أضف مفتاح صحيح في .env<br>';
            echo '</div>';
            $warnings++;
        }
        echo '</div>';
        
        // 7. اختبر الأذونات
        echo '<div class="test">';
        echo '<h3>7️⃣ أذونات الملفات</h3>';
        
        $uploadDir = 'uploads';
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            echo '<span class="status pass">✅ مجلد الرفع قابل للكتابة</span>';
            $passed++;
        } else {
            if (!is_dir($uploadDir)) {
                echo '<span class="status fail">❌ مجلد الرفع غير موجود</span>';
                $failed++;
            } else {
                echo '<span class="status fail">❌ مجلد الرفع غير قابل للكتابة</span>';
                $failed++;
            }
        }
        echo '</div>';
        
        // الملخص
        echo '<div class="summary">';
        echo '<h2>📊 الملخص</h2>';
        echo '<p>✅ نجح: <strong>' . $passed . '</strong></p>';
        echo '<p>❌ فشل: <strong>' . $failed . '</strong></p>';
        echo '<p>⚠️ تحذيرات: <strong>' . $warnings . '</strong></p>';
        
        if ($failed === 0) {
            echo '<p style="color: green; font-size: 16px; font-weight: bold;">🎉 جميع الاختبارات نجحت! يمكنك استخدام الشات بوت.</p>';
        } else {
            echo '<p style="color: red; font-size: 16px; font-weight: bold;">⚠️ هناك مشاكل يجب حلها قبل استخدام الشات بوت.</p>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>
