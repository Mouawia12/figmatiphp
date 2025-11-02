<?php

$pdo = pdo_open();

// إنشاء جدول سجلات الرسائل النصية
$pdo->exec("
    CREATE TABLE IF NOT EXISTS sms_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone TEXT NOT NULL,
        message TEXT NOT NULL,
        status TEXT NOT NULL,
        response TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME
    )
");

// إنشاء فهارس للبحث السريع
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sms_logs_phone ON sms_logs(phone)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sms_logs_created_at ON sms_logs(created_at)");

echo "تم إنشاء جدول سجلات الرسائل النصية بنجاح\n";
