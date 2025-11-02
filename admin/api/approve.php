<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
$ids = array_values(array_filter(array_map('intval', (array)($data['ids'] ?? []))));
if (!$ids) api_ok(['approved' => 0]);
try {
  ensure_training_queue_schema();
  $db = pdo_open('notifications');
  $place = implode(',', array_fill(0, count($ids), '?'));
  $st = $db->prepare("UPDATE training_queue SET status='approved' WHERE id IN ($place)");
  $st->execute($ids);
  api_ok(['approved' => $st->rowCount()]);
} catch (Throwable $e) {
  api_fail('approve_failed', 500, ['detail' => $e->getMessage()]);
}
