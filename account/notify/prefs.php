<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));

$notify_email = isset($_POST['notify_email']) ? 1 : 0;
$notify_sms   = isset($_POST['notify_sms']) ? 1 : 0;

try {
  $db = pdo_open('users');
  if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
    $db->exec("CREATE TABLE IF NOT EXISTS notify_prefs (
      email VARCHAR(190) PRIMARY KEY, pref_email TINYINT(1), pref_sms TINYINT(1), updated_at DATETIME
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $st = $db->prepare('INSERT INTO notify_prefs(email, pref_email, pref_sms, updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE pref_email=VALUES(pref_email), pref_sms=VALUES(pref_sms), updated_at=VALUES(updated_at)');
  } else {
    $db->exec("CREATE TABLE IF NOT EXISTS notify_prefs (
      email TEXT PRIMARY KEY, pref_email INTEGER, pref_sms INTEGER, updated_at TEXT
    )");
    $st = $db->prepare("INSERT INTO notify_prefs(email,pref_email,pref_sms,updated_at) VALUES(?,?,?,datetime('now')) ON CONFLICT(email) DO UPDATE SET pref_email=excluded.pref_email, pref_sms=excluded.pref_sms, updated_at=excluded.updated_at");
  }
  $st->execute([$email,$notify_email,$notify_sms]);
} catch (Throwable $e) {}

header('Location: ' . app_href('../../dashboard.php#notifications'));
exit;

