<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function openai_embed(array $texts): array {
  $apiKey = (string)env('OPENAI_API_KEY', '');
  if ($apiKey === '') throw new RuntimeException('OPENAI_API_KEY missing');
  $model = (string)env('OPENAI_EMBED_MODEL', 'text-embedding-3-small');
  $payload = ['model' => $model, 'input' => array_values($texts)];
  $ch = curl_init('https://api.openai.com/v1/embeddings');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $apiKey,
      'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 30,
  ]);
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('OpenAI error: ' . $err); }
  curl_close($ch);
  $j = json_decode((string)$res, true);
  if ($code >= 400) throw new RuntimeException('OpenAI HTTP ' . $code . ': ' . (($j['error']['message'] ?? '') . ' ' . json_encode($j)));
  $vecs = [];
  foreach (($j['data'] ?? []) as $item) { $vecs[] = $item['embedding'] ?? []; }
  return $vecs;
}

function qdrant_base(): string {
  $host = rtrim((string)env('QDRANT_HOST', 'http://localhost:6333'), '/');
  return $host;
}

function qdrant_http(string $method, string $path, array $body = null): array {
  $url = qdrant_base() . '/' . ltrim($path, '/');
  $ch = curl_init($url);
  $headers = ['Content-Type: application/json'];
  $apiKey = (string)env('QDRANT_API_KEY', '');
  if ($apiKey !== '') { $headers[] = 'api-key: ' . $apiKey; }
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $timeout = (int)env('QDRANT_TIMEOUT', '30');
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout > 0 ? $timeout : 30);
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('Qdrant error: ' . $err); }
  curl_close($ch);
  $j = json_decode((string)$res, true);
  if ($code >= 400) throw new RuntimeException('Qdrant HTTP ' . $code . ': ' . json_encode($j));
  return $j ?? [];
}

function qdrant_setup_collection(string $name, int $dim): array {
  // create if not exists idempotent
  try {
    qdrant_http('PUT', "/collections/{$name}", [
      'vectors' => ['size' => $dim, 'distance' => 'Cosine']
    ]);
  } catch (Throwable $e) {
    // ignore if already exists
  }
  return qdrant_http('GET', "/collections/{$name}");
}

function qdrant_upsert(string $name, array $points): array {
  return qdrant_http('PUT', "/collections/{$name}/points?wait=true", [ 'points' => $points ]);
}

function qdrant_search(string $name, array $vector, int $topK = 8, array $filter = null): array {
  $body = ['vector' => $vector, 'limit' => $topK];
  if ($filter) $body['filter'] = $filter;
  return qdrant_http('POST', "/collections/{$name}/points/search", $body);
}

function rag_upsert_chunks(string $collection, array $chunks, array $metas): array {
  // $chunks: list of strings. $metas: list of arrays meta for each
  if (count($chunks) === 0) return ['ok' => true, 'count' => 0];
  $vecs = openai_embed($chunks);
  $points = [];
  foreach ($vecs as $i => $v) {
    $payload = $metas[$i] ?? [];
    $payload['content'] = $chunks[$i];
    $points[] = [
      'id' => hexdec(substr(hash('xxh128', $chunks[$i] . json_encode($payload)), 0, 12)) % 1000000000,
      'vector' => $v,
      'payload' => $payload,
    ];
  }
  return qdrant_upsert($collection, $points);
}

function rag_search(string $collection, string $query, int $topK = 8, array $filter = null): array {
  $vec = openai_embed([$query])[0] ?? [];
  $res = qdrant_search($collection, $vec, $topK, $filter);
  $out = [];
  foreach (($res['result'] ?? []) as $r) {
    $out[] = [
      'score' => $r['score'] ?? 0,
      'content' => $r['payload']['content'] ?? '',
      'meta' => $r['payload'] ?? [],
    ];
  }
  return $out;
}
