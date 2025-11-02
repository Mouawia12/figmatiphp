<?php
require __DIR__ . '/../../inc/functions.php';
// Compatibility route for /order/track -> /track.php
$qs = $_SERVER['QUERY_STRING'] ?? '';
$dest = app_href('track.php' . ($qs ? ('?' . $qs) : ''));
header('Location: ' . $dest, true, 302);
exit;

