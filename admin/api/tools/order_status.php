<?php
declare(strict_types=1);
require_once __DIR__ . '/../_common.php';
api_require_admin();
api_require_method('GET');
$order_id = trim((string)($_GET['order_id'] ?? ''));
if ($order_id === '') api_fail('order_id required');
// TODO: lookup real order status
api_ok(['order_id' => $order_id, 'status' => 'pending']);
