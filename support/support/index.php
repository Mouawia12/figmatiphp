<?php
require __DIR__ . '/../../inc/functions.php';
// Normalize accidental duplicate path /support/support/* to /support/*
$qs = $_SERVER['QUERY_STRING'] ?? '';
$dest = app_href('support/' . ($qs ? ('?' . $qs) : ''));
header('Location: ' . $dest, true, 302);
exit;

