<?php
require __DIR__ . '/inc/functions.php';

session_start();
session_unset();
session_destroy();
header('Location: ' . app_href('index.php'));
exit;
