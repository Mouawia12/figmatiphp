<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
// TODO: redact PII, validate items, enqueue to training_queue
$queued = is_array($data['items'] ?? null) ? count($data['items']) : 0;
api_ok(['queued' => $queued]);
