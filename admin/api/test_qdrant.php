<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('GET');
require_once __DIR__ . '/../../inc/rag.php';

header('Content-Type: application/json; charset=utf-8');
try {
  $info = qdrant_http('GET', '');
  $version = (string)($info['version'] ?? '');
  $ready   = (bool)($info['status'] ?? true);
  echo json_encode(['ok'=>true, 'version'=>$version, 'ready'=>$ready]);
} catch (Throwable $e) {
  echo json_encode(['error'=>'qdrant_unreachable', 'detail'=>$e->getMessage()]);
}
