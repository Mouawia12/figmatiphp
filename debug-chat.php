<?php
/**
 * debug-chat.php
 * أداة فحص نظام الدردشة الكاملة
 */

declare(strict_types=1);
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/init_chat_db.php';

$pdo = init_chat_database();

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='utf-8'>
    <title>🔍 فحص نظام الدردشة</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #667eea; margin-bottom: 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .test { padding: 10px; margin: 10px 0; border-left: 4px solid #ddd; background: #f9f9f9; }
        .test.pass { border-left-color: #10b981; background: #f0fdf4; }
        .test.fail { border-left-color: #ef4444; background: #fef2f2; }
        .test.warn { border-left-color: #f59e0b; background: #fffbeb; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge.ok { background: #10b981; color: white; }
        .badge.error { background: #ef4444; color: white; }
        .badge.warn { background: #f59e0b; color: white; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 أداة فحص نظام الدردشة</h1>";

// 1. فحص قاعدة البيانات
echo "<div class='section'>
    <h2>1️⃣ فحص قاعدة البيانات</h2>";

try {
    // فحص الجداول
    $tables = ['chat_conversations', 'chat_messages', 'admin_alerts'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $class = $exists ? 'pass' : 'fail';
        $status = $exists ? '✅ موجود' : '❌ مفقود';
        echo "<div class='test $class'>جدول <code>$table</code>: $status</div>";
    }
    
    // عدد المحادثات
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM chat_conversations");
    $count = $stmt->fetch()['cnt'];
    echo "<div class='test pass'>عدد المحادثات: <strong>$count</strong></div>";
    
    // عدد الرسائل
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM chat_messages");
    $count = $stmt->fetch()['cnt'];
    echo "<div class='test pass'>عدد الرسائل: <strong>$count</strong></div>";
    
} catch (Exception $e) {
    echo "<div class='test fail'>❌ خطأ: " . $e->getMessage() . "</div>";
}

echo "</div>";

// 2. فحص متغيرات البيئة
echo "<div class='section'>
    <h2>2️⃣ فحص متغيرات البيئة (OpenAI)</h2>";

$api_key = getenv('OPENAI_API_KEY');
$model = getenv('OPENAI_MODEL');

if ($api_key && $api_key !== 'YOUR_OPENAI_API_KEY') {
    $masked = substr($api_key, 0, 10) . '...' . substr($api_key, -10);
    echo "<div class='test pass'>🔑 OPENAI_API_KEY: <code>$masked</code> <span class='badge ok'>صحيح</span></div>";
} else {
    echo "<div class='test fail'>❌ OPENAI_API_KEY: غير موجود أو غير صحيح</div>";
}

if ($model) {
    echo "<div class='test pass'>🤖 OPENAI_MODEL: <code>$model</code></div>";
} else {
    echo "<div class='test warn'>⚠️ OPENAI_MODEL: سيتم استخدام gpt-4o-mini افتراضياً</div>";
}

echo "</div>";

// 3. فحص آخر محادثة
echo "<div class='section'>
    <h2>3️⃣ فحص آخر محادثة</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM chat_conversations ORDER BY id DESC LIMIT 1");
    $conv = $stmt->fetch();
    
    if ($conv) {
        echo "<table>
            <tr><th>المعلومة</th><th>القيمة</th></tr>
            <tr><td>معرف المحادثة</td><td><code>" . $conv['id'] . "</code></td></tr>
            <tr><td>اسم العميل</td><td>" . ($conv['customer_name'] ?? 'بدون') . "</td></tr>
            <tr><td>بريد العميل</td><td>" . ($conv['customer_email'] ?? 'بدون') . "</td></tr>
    echo "<tr><td>آخر رسالة</td><td>\" . (substr($conv['last_message'] ?? '', 0, 50) . '...') . "</td></tr>
            <tr><td>تاريخ الإنشاء</td><td>" . $conv['created_at'] . "</td></tr>
            <tr><td>آخر تحديث</td><td>" . $conv['updated_at'] . "</td></tr>
        </table>";
        
        // رسائل هذه المحادثة
        $conv_id = $conv['id'];
        $stmt = $pdo->query("SELECT * FROM chat_messages WHERE conversation_id = $conv_id ORDER BY id DESC LIMIT 10");
        $messages = $stmt->fetchAll();
        
        echo "<h3 style='margin-top: 20px; color: #333;'>رسائل المحادثة (آخر 10):</h3>";
        echo "<table>
            <tr><th>المرسل</th><th>نوع المرسل</th><th>الرسالة</th><th>الوقت</th></tr>";
        
        foreach (array_reverse($messages) as $msg) {
            $sender = $msg['sender_id'] ?? 'بدون';
            $type = $msg['sender_type'] ?? 'customer';
            $text = substr($msg['message'] ?? '', 0, 50);
            $time = $msg['created_at'] ?? '';
            echo "<tr>
                <td><code>$sender</code></td>
                <td><span class='badge " . ($type === 'bot' ? 'ok' : 'warn') . "'>$type</span></td>
                <td>$text...</td>
                <td>$time</td>
            </tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div class='test warn'>⚠️ لا توجد محادثات بعد</div>";
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ خطأ: " . $e->getMessage() . "</div>";
}

echo "</div>";

// 4. اختبار API
echo "<div class='section'>
    <h2>4️⃣ اختبار API</h2>";

echo "<div class='test warn'>📝 لاختبار API، استخدم أحد الطلبات التالية:</div>";

echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto;'>
<strong>GET:</strong> /crosing/api_chat.php?action=get_conversations

<strong>POST (إرسال رسالة):</strong>
POST /crosing/api_chat.php
Content-Type: application/x-www-form-urlencoded

action=send_message&conversation_id=1&message=مرحبا&sender_type=customer

<strong>GET (جلب الرسائل):</strong>
/crosing/api_chat.php?action=get_messages&conversation_id=1
</pre>";

echo "</div>";

// 5. فحص الملفات
echo "<div class='section'>
    <h2>5️⃣ فحص الملفات الأساسية</h2>";

$files = [
    '/crosing/api_chat.php' => 'API الرئيسي',
    '/crosing/admin/chat.php' => 'لوحة تحكم الدردشة',
    '/crosing/assets/chatbot.js' => 'واجهة الدردشة للعملاء',
    '/crosing/.env' => 'ملف البيئة',
];

foreach ($files as $file => $desc) {
    $full_path = __DIR__ . $file;
    $exists = file_exists($full_path);
    $class = $exists ? 'pass' : 'fail';
    $status = $exists ? '✅ موجود' : '❌ مفقود';
    echo "<div class='test $class'>$desc <code>$file</code>: $status</div>";
}

echo "</div>";

// 6. فحص الأخطاء
echo "<div class='section'>
    <h2>6️⃣ سجل الأخطاء الأخيرة</h2>";

$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = array_slice(file($error_log), -20);
    echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto;'>";
    foreach ($lines as $line) {
        if (strpos($line, 'OpenAI') !== false || strpos($line, 'Chat') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<div class='test warn'>⚠️ لم يتم العثور على ملف السجل</div>";
}

echo "</div>";

// 7. نصائح الحل
echo "<div class='section'>
    <h2>7️⃣ نصائح الحل</h2>";

echo "<div style='background: #fffbeb; padding: 15px; border-radius: 5px; border-right: 4px solid #f59e0b;'>
    <h3 style='color: #f59e0b; margin-bottom: 10px;'>إذا لم تظهر الردود:</h3>
    <ol style='margin-right: 20px;'>
        <li>✅ تأكد من وجود <code>OPENAI_API_KEY</code> في ملف <code>.env</code></li>
        <li>✅ تحقق من أن المفتاح صحيح وليس مجرد نص افتراضي</li>
        <li>✅ تأكد من أن الاتصال بالإنترنت يعمل</li>
        <li>✅ افتح Console في المتصفح (F12) وابحث عن الأخطاء</li>
        <li>✅ تحقق من سجل الأخطاء في الخادم</li>
        <li>✅ جرب إرسال رسالة واحدة وانتظر 5 ثواني</li>
    </ol>
</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
