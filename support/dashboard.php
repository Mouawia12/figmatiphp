<?php
require __DIR__ . '/../inc/functions.php';
// Redirect helper: /support/dashboard.php -> /dashboard.php#tickets
header('Location: ' . app_href('dashboard.php#tickets'), true, 302);
exit;

