<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));

$dbu = pdo_open('users');
$dbr = pdo_open('requests');

// Collect
$prefs = $dbu->query("SELECT * FROM customer_preferences_email WHERE email=" . $dbu->quote($email))->fetch(PDO::FETCH_ASSOC) ?: [];
$addrs = $dbu->query("SELECT * FROM customer_addresses WHERE email=" . $dbu->quote($email) . " ORDER BY is_default DESC, id DESC")->fetchAll() ?: [];
$tickets = $dbr->query("SELECT id,subject,category,priority,status,created_at FROM support_tickets WHERE email=" . $dbr->quote($email) . " ORDER BY id DESC")->fetchAll() ?: [];

$out = [
  'email' => $email,
  'preferences' => $prefs,
  'addresses' => $addrs,
  'tickets' => $tickets,
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="export.json"');
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

