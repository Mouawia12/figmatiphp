<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../../inc/rag.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);

$chunks = (array)($data['chunks'] ?? []);
$metas  = (array)($data['metas']  ?? []);
if (!$chunks) api_fail('chunks required');
try {
  $collection = (string)env('RAG_COLLECTION', 'crosing_ar');
  $res = rag_upsert_chunks($collection, $chunks, $metas);
  $count = count($chunks);
  api_ok(['ok'=>true,'count'=>$count,'result'=>$res]);
} catch (Throwable $e) {
  api_fail('import_failed', 500, ['detail'=>$e->getMessage()]);
}
