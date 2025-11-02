<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));

$consent_email = isset($_POST['consent_email']) ? 1 : 0;
$consent_sms   = isset($_POST['consent_sms']) ? 1 : 0;

try {
  $db = pdo_open('users');
  if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
    $db->exec("CREATE TABLE IF NOT EXISTS marketing_consents (
      email VARCHAR(190) PRIMARY KEY, consent_email TINYINT(1), consent_sms TINYINT(1), updated_at DATETIME
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $st = $db->prepare('INSERT INTO marketing_consents(email, consent_email, consent_sms, updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE consent_email=VALUES(consent_email), consent_sms=VALUES(consent_sms), updated_at=VALUES(updated_at)');
  } else {
    $db->exec("CREATE TABLE IF NOT EXISTS marketing_consents (
      email TEXT PRIMARY KEY, consent_email INTEGER, consent_sms INTEGER, updated_at TEXT
    )");
    $st = $db->prepare("INSERT INTO marketing_consents(email,consent_email,consent_sms,updated_at) VALUES(?,?,?,datetime('now')) ON CONFLICT(email) DO UPDATE SET consent_email=excluded.consent_email, consent_sms=excluded.consent_sms, updated_at=excluded.updated_at");
  }
  $st->execute([$email,$consent_email,$consent_sms]);
} catch (Throwable $e) {}

header('Location: ' . app_href('../../dashboard.php#data'));
exit;

