<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../../inc/rag.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
$collection = trim((string)($data['collection'] ?? env('RAG_COLLECTION', 'crosing_ar')));
$query = trim((string)($data['query'] ?? ''));
$topk = (int)($data['top_k'] ?? 8);
$filter = isset($data['filter']) && is_array($data['filter']) ? $data['filter'] : null;
if ($query === '') api_fail('query required');
try {
  $res = rag_search($collection, $query, $topk, $filter);
  api_ok(['results' => $res]);
} catch (Throwable $e) {
  api_fail('search_failed', 500, ['detail' => $e->getMessage()]);
}
