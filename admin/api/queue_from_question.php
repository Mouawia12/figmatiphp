<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
require_once __DIR__ . '/../../inc/functions.php';

try {
  ensure_training_queue_schema();
  $qkey = trim((string)($data['question_key'] ?? ''));
  $qtext= trim((string)($data['question_text'] ?? ''));
  if ($qkey === '' || $qtext === '') api_fail('missing question');
  $db = pdo_open('notifications');
  $payload = json_encode(['question_key'=>$qkey,'question_text'=>$qtext], JSON_UNESCAPED_UNICODE);
  $stmt = $db->prepare("INSERT INTO training_queue(type, ref_id, status, note, payload_json, created_at) VALUES(?,?,?,?,?, %s)" . (((cfg()->db_driver ?? 'sqlite')==='mysql')? '':'') );
} catch (Throwable $e) {
  // Fallback portable insert with NOW()/datetime('now')
}

try {
  $db = pdo_open('notifications');
  $isMysql = (cfg()->db_driver ?? 'sqlite') === 'mysql';
  if ($isMysql) {
    $st = $db->prepare("INSERT INTO training_queue(type, ref_id, status, note, payload_json, created_at) VALUES(?,?,?,?,?, NOW())");
  } else {
    $st = $db->prepare("INSERT INTO training_queue(type, ref_id, status, note, payload_json, created_at) VALUES(?,?,?,?,?, datetime('now'))");
  }
  $st->execute(['qa_suggestion', $qkey, 'pending', 'from_chat_analytics', $payload]);
  api_ok(['ok'=>true]);
} catch (Throwable $e) {
  api_fail('queue_failed', 500, ['detail'=>$e->getMessage()]);
}
