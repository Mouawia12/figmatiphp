<?php
// Redirect to the new ticket page
require __DIR__ . '/../../../inc/functions.php';
header('Location: ' . app_href('support/new_ticket.php'), true, 302);
exit;