<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));

$dbu = pdo_open('users');
$dbr = pdo_open('requests');

$prefs = $dbu->query("SELECT * FROM customer_preferences_email WHERE email=" . $dbu->quote($email))->fetch(PDO::FETCH_ASSOC) ?: [];
$addrs = $dbu->query("SELECT * FROM customer_addresses WHERE email=" . $dbu->quote($email) . " ORDER BY is_default DESC, id DESC")->fetchAll() ?: [];
$tickets = $dbr->query("SELECT id,subject,category,priority,status,created_at FROM support_tickets WHERE email=" . $dbr->quote($email) . " ORDER BY id DESC")->fetchAll() ?: [];

$files = [
  'profile.json'   => json_encode(['email'=>$email], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
  'preferences.json'=> json_encode($prefs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
  'addresses.json' => json_encode($addrs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
  'tickets.json'   => json_encode($tickets, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
];

if (class_exists('ZipArchive')) {
  $zip = new ZipArchive();
  $tmp = tempnam(sys_get_temp_dir(), 'exp');
  $zip->open($tmp, ZipArchive::OVERWRITE);
  foreach ($files as $name=>$content) { $zip->addFromString($name, $content ?: '{}'); }
  $zip->close();
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="export.zip"');
  header('Content-Length: ' . filesize($tmp));
  readfile($tmp);
  @unlink($tmp);
  exit;
}

// Fallback: send JSON bundle
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="export.json"');
echo json_encode($files, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

