<?php
require __DIR__ . '/../../../inc/functions.php'; // Adjust path to functions.php
// Redirect duplicate path to canonical
$qs = $_SERVER['QUERY_STRING'] ?? '';
$dest = app_href('support/ticket/view' . ($qs ? ('?' . $qs) : ''));
header('Location: ' . $dest, true, 302);
exit;

