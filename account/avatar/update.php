<?php
declare(strict_types=1);

// منع عرض الأخطاء للمستخدم
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

require __DIR__ . '/../../inc/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('login.php'));
    exit;
}

$back = app_href('dashboard.php');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $back);
    exit;
}

try {
    if (empty($_FILES['avatar']) || empty($_FILES['avatar']['name'])) {
        throw new RuntimeException('لم يتم اختيار ملف');
    }

    // 2MB image limit
    $name = handle_upload_limit($_FILES['avatar'], 2 * 1024 * 1024, ['jpg', 'jpeg', 'png', 'webp']);
    $path = 'uploads/' . $name;

    $user_id = (int)$_SESSION['user']['id'];

    // التأكد من وجود جدول users أولاً
    if (function_exists('ensure_users_table_exists')) {
        ensure_users_table_exists();
    }

    $db = pdo_open('users');

    // Ensure avatar_path column exists (SQLite/MySQL)
    try {
        $cfg = cfg();
        if (($cfg->db_driver ?? 'sqlite') === 'mysql') {
            $db->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL");
        } else {
            $db->exec("ALTER TABLE users ADD COLUMN avatar_path TEXT NULL");
        }
    } catch (Throwable $e) {
        // تجاهل الخطأ إذا كانت العمود موجوداً بالفعل
    }

    $stmt = $db->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
    $stmt->execute([$path, $user_id]);

    // Put in session for immediate use
    $_SESSION['avatar_path'] = $path;

    header('Location: ' . $back . '?avatar_saved=1#account');
    exit;

} catch (Throwable $e) {
    error_log("ERROR: account/avatar/update.php - " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    $error_msg = 'حدث خطأ أثناء رفع الصورة. يرجى المحاولة مرة أخرى.';
    if (strpos($e->getMessage(), 'size') !== false) {
        $error_msg = 'حجم الصورة كبير جداً. الحد الأقصى 2MB.';
    } elseif (strpos($e->getMessage(), 'extension') !== false) {
        $error_msg = 'نوع الملف غير مدعوم. يرجى استخدام JPG, PNG, أو WEBP.';
    }
    header('Location: ' . $back . '?avatar_error=' . urlencode($error_msg) . '#account');
    exit;
}