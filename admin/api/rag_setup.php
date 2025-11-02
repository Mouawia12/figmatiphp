<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../../inc/rag.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
$collection = trim((string)($data['collection'] ?? 'crosing_ar'));
$dim = (int)($data['dim'] ?? 1536); // text-embedding-3-small
try {
  $res = qdrant_setup_collection($collection, $dim);
  api_ok(['ok' => true, 'collection' => $collection, 'qdrant' => $res]);
} catch (Throwable $e) {
  api_fail('setup_failed', 500, ['detail' => $e->getMessage()]);
}
