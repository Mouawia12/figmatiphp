<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
require_once __DIR__ . '/../../inc/functions.php';

$id = (int)($data['id'] ?? 0);
$status = trim((string)($data['status'] ?? ''));
if ($id <= 0 || $status === '') api_fail('missing fields');

try {
  ensure_training_queue_schema();
  $db = pdo_open('notifications');
  $st = $db->prepare("UPDATE training_queue SET status=? WHERE id=?");
  $st->execute([$status, $id]);
  api_ok(['ok' => true]);
} catch (Throwable $e) {
  api_fail('queue_update_failed', 500, ['detail' => $e->getMessage()]);
}
