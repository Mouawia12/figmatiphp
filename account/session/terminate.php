<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
// Minimal stub: if asked to terminate current session, log out
$id = (string)($_GET['id'] ?? '');
// Without a session store, just regenerate ID for safety
session_regenerate_id(true);
header('Location: ' . app_href('../../dashboard.php#security'));
exit;

