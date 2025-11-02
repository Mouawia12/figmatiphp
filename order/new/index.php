<?php
require __DIR__ . '/../../inc/functions.php';
// Compatibility route for /order/new -> /form.php
$qs = $_SERVER['QUERY_STRING'] ?? '';
$dest = app_href('form.php' . ($qs ? ('?' . $qs) : ''));
header('Location: ' . $dest, true, 302);
exit;

