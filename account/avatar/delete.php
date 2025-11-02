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

try {
    // التأكد من وجود جدول users أولاً
    if (function_exists('ensure_users_table_exists')) {
        ensure_users_table_exists();
    }

    $db = pdo_open('users');

    // Ensure avatar_path column exists (MySQL/SQLite)
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

    $uid = (int)($_SESSION['user']['id'] ?? 0);
    
    if ($uid <= 0) {
        throw new RuntimeException('معرف المستخدم غير صالح');
    }

    $st = $db->prepare('UPDATE users SET avatar_path = NULL WHERE id = ?');
    $st->execute([$uid]);

    // Also clear from session for immediate effect
    unset($_SESSION['avatar_path']);

    header('Location: ' . $back . '?avatar_deleted=1#account');
    exit;
} catch (Throwable $e) {
    error_log("ERROR: account/avatar/delete.php - " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    $error_msg = 'حدث خطأ أثناء حذف الصورة. يرجى المحاولة مرة أخرى.';
    header('Location: ' . $back . '?avatar_error=' . urlencode($error_msg) . '#account');
    exit;
}
