<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/functions.php';

function api_require_admin(): array {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $uid = (int)($_SESSION['user']['id'] ?? 0);
  if ($uid <= 0) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'unauthorized']);
    exit;
  }
  // fetch user and ensure role=admin
  $db = pdo_open('users');
  $st = $db->prepare('SELECT * FROM users WHERE id = ?');
  $st->execute([$uid]);
  $me = $st->fetch();
  if (!$me || ($me['role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'forbidden']);
    exit;
  }
  return $me;
}

function api_require_method(string $method): void {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
  }
}

function api_verify_csrf_for_form(): void {
  if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
  }
}

function api_json_input(): array {
  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function api_verify_csrf_for_json(array $data): void {
  if (!verify_csrf((string)($data['csrf_token'] ?? ''))) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
  }
}

function api_ok(array $payload = []): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function api_fail(string $msg, int $code = 400, array $extra = []): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  $out = array_merge(['error' => $msg], $extra);
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}
