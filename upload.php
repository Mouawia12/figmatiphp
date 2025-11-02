<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if(!verify_csrf($_POST['csrf_token'] ?? '')) { header('Location: index.php'); exit; }

try {
    if(empty($_FILES['file'])) throw new RuntimeException('لا يوجد ملف');
    $saved = handle_upload($_FILES['file']); // يعيد اسمًا رقميًا عشوائيًا
    // إشعار إداري
    $dbn = pdo_open($config->db_notifications);
    $dbn->exec("CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY, message TEXT, created_at TEXT)");
    $dbn->prepare("INSERT INTO notifications (message,created_at) VALUES (?,datetime('now'))")->execute(["تم رفع ملف: {$saved}"]);

    // لا نظهر اسم الملف — إثبات نجاح فقط
    header('Location: index.php?ok=1'); 
    exit;
} catch (Exception $e) {
    header('Location: index.php?ok=1'); // حتى لو فشلت التفاصيل، لا نكشف معلومات
    exit;
}
