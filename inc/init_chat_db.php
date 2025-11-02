<?php
declare(strict_types=1);

/**
 * inc/init_chat_db.php
 * إنشاء/ترقية جداول الدردشة عند اللزوم.
 *
 * توفر دالة: init_chat_database(PDO $pdo = null): PDO
 */

function db(): PDO {
    // لو عندك دالة db() في inc/auth.php استخدمها بدلاً من التالي
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn  = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=azzm_sin;charset=utf8mb4';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function init_chat_database(PDO $pdo = null): PDO {
    $pdo = $pdo ?: db();
    
    // تحديد نوع قاعدة البيانات
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSQLite = ($driver === 'sqlite');

    // chat_conversations
    if ($isSQLite) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id TEXT DEFAULT NULL,
                customer_name TEXT DEFAULT NULL,
                customer_email TEXT DEFAULT NULL,
                session_key TEXT DEFAULT NULL,
                staff_id TEXT DEFAULT NULL,
                last_message TEXT NULL,
                is_archived INTEGER NOT NULL DEFAULT 0,
                is_deleted INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_chat_conversations_updated_at ON chat_conversations(updated_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_chat_conversations_is_archived ON chat_conversations(is_archived)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_chat_conversations_is_deleted ON chat_conversations(is_deleted)");
        // Add missing columns (migration)
        try { $pdo->exec("ALTER TABLE chat_conversations ADD COLUMN session_key TEXT"); } catch (Throwable $e) {}
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `chat_conversations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `customer_id` VARCHAR(190) DEFAULT NULL,
                `customer_name` VARCHAR(190) DEFAULT NULL,
                `customer_email` VARCHAR(190) DEFAULT NULL,
                `session_key` VARCHAR(64) DEFAULT NULL,
                `staff_id` VARCHAR(190) DEFAULT NULL,
                `last_message` TEXT NULL,
                `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
                `is_deleted`  TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`updated_at`),
                INDEX (`is_archived`),
                INDEX (`is_deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Migration: add session_key if missing
        try { $pdo->exec("ALTER TABLE chat_conversations ADD COLUMN session_key VARCHAR(64) NULL"); } catch (Throwable $e) {}
    }

    // chat_messages
    if ($isSQLite) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                sender_type TEXT NOT NULL,
                sender_id TEXT DEFAULT NULL,
                message TEXT NOT NULL,
                meta TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation_id ON chat_messages(conversation_id)");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `chat_messages` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `conversation_id` BIGINT UNSIGNED NOT NULL,
                `sender_type` VARCHAR(32) NOT NULL,
                `sender_id`   VARCHAR(190) DEFAULT NULL,
                `message`     LONGTEXT NOT NULL,
                `meta`        JSON NULL,
                `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `chat_messages_ibfk_1`
                    FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // admin_alerts
    if ($isSQLite) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_alerts` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    return $pdo;
}
