<?php
require __DIR__ . '/../inc/functions.php';
// Redirect /faq -> homepage FAQ section
$qs = $_SERVER['QUERY_STRING'] ?? '';
$dest = app_href('index.php#faq');
header('Location: ' . $dest, true, 302);
exit;

