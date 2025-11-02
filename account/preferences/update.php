<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { header('Location: ' . app_href('../../dashboard.php#prefs')); exit; }

$lang = $_POST['lang'] ?? 'ar';
$tz   = $_POST['tz']   ?? 'Asia/Riyadh';
$theme= $_POST['theme']?? 'light';
$email= strtolower((string)($_SESSION['user']['email'] ?? ''));

try {
  $db = pdo_open('users');
  // Table keyed by email for simplicity and portability
  if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
    $db->exec("CREATE TABLE IF NOT EXISTS customer_preferences_email (
      email VARCHAR(190) PRIMARY KEY,
      language VARCHAR(10), timezone VARCHAR(64), theme VARCHAR(10),
      updated_at DATETIME
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $st = $db->prepare("INSERT INTO customer_preferences_email(email,language,timezone,theme,updated_at) VALUES(?,?,?,?,NOW())
      ON DUPLICATE KEY UPDATE language=VALUES(language), timezone=VALUES(timezone), theme=VALUES(theme), updated_at=VALUES(updated_at)");
    $st->execute([$email,$lang,$tz,$theme]);
  } else {
    $db->exec("CREATE TABLE IF NOT EXISTS customer_preferences_email (
      email TEXT PRIMARY KEY,
      language TEXT, timezone TEXT, theme TEXT, updated_at TEXT
    )");
    $st = $db->prepare("INSERT INTO customer_preferences_email(email,language,timezone,theme,updated_at) VALUES(?,?,?,?,datetime('now'))
      ON CONFLICT(email) DO UPDATE SET language=excluded.language, timezone=excluded.timezone, theme=excluded.theme, updated_at=excluded.updated_at");
    $st->execute([$email,$lang,$tz,$theme]);
  }
} catch (Throwable $e) {
  // swallow and continue; preferences are non-critical
}

// Apply theme immediately via session flag if desired
$_SESSION['prefs'] = ['lang'=>$lang,'tz'=>$tz,'theme'=>$theme];
header('Location: ' . app_href('../../dashboard.php#prefs'));
exit;

