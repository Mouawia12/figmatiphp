<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('login.php')); exit; }

$input = trim((string)($_POST['code'] ?? ''));
$_SESSION['verify'] = $_SESSION['verify'] ?? [];
if (!empty($_SESSION['verify']['email_code']) && hash_equals($_SESSION['verify']['email_code'], $input)) {
  $_SESSION['verify']['email_verified'] = true;
}
if (!empty($_SESSION['verify']['phone_code']) && hash_equals($_SESSION['verify']['phone_code'], $input)) {
  $_SESSION['verify']['phone_verified'] = true;
}
header('Location: ' . app_href('dashboard.php#security'));
exit;

