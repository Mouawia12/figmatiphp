<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('GET');
require_once __DIR__ . '/../../inc/functions.php';

ensure_chat_analytics_schema();
$db = pdo_open('notifications');
try {
  $rows = $db->query("SELECT question_key, question_text, asked_count, unanswered_count, last_at FROM chat_questions ORDER BY asked_count DESC, last_at DESC")->fetchAll();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'export_failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
