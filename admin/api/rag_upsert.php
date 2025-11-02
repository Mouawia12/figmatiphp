<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../../inc/rag.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
$collection = trim((string)($data['collection'] ?? env('RAG_COLLECTION', 'crosing_ar')));
$chunks = (array)($data['chunks'] ?? []);
$metas  = (array)($data['metas']  ?? []);
try {
  $res = rag_upsert_chunks($collection, $chunks, $metas);
  api_ok(['ok' => true, 'count' => count($chunks), 'result' => $res]);
} catch (Throwable $e) {
  api_fail('upsert_failed', 500, ['detail' => $e->getMessage()]);
}
