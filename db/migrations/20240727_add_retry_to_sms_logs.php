<?php
require_once __DIR__ . '/../../inc/functions.php';

$pdo = pdo_open();

// Create sms_logs table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `sms_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `phone` VARCHAR(50) NOT NULL,
        `message` TEXT NOT NULL,
        `status` VARCHAR(20) NOT NULL,
        `response` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `sent_at` DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Add retry_count column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE `sms_logs` ADD COLUMN `retry_count` INT DEFAULT 0");
} catch (PDOException $e) {
    if ($e->errorInfo[1] != 1060) { // 1060 is for 'Duplicate column name'
        throw $e;
    }
}

// Add last_retry_at column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE `sms_logs` ADD COLUMN `last_retry_at` DATETIME");
} catch (PDOException $e) {
    if ($e->errorInfo[1] != 1060) { // 1060 is for 'Duplicate column name'
        throw $e;
    }
}

echo "تم فحص وتحديث جدول سجلات الرسائل النصية بنجاح.\n";