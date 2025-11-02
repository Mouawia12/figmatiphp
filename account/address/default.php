<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
try {
  $db = pdo_open('users');
  $db->prepare('UPDATE customer_addresses SET is_default=0 WHERE email=?')->execute([$email]);
  if ($id > 0) $db->prepare('UPDATE customer_addresses SET is_default=1 WHERE id=? AND email=?')->execute([$id,$email]);
} catch (Throwable $e) {}
header('Location: ' . app_href('../../dashboard.php#addresses'));
exit;

