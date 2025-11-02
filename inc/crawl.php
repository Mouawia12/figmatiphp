<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/rag.php';

function http_fetch(string $url, int $timeout = 20): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => 'AzmCrawler/1.0 (+bot)'
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
  $err  = curl_error($ch);
  curl_close($ch);
  return ['ok' => ($body !== false && $code < 400), 'code' => $code, 'type' => $ct, 'body' => (string)$body, 'error' => $err];
}

function same_domain(string $base, string $url): bool {
  $b = parse_url($base);
  $u = parse_url($url);
  return isset($b['host'], $u['host']) && strtolower($b['host']) === strtolower($u['host']);
}

function absolutize(string $base, string $href): string {
  if (preg_match('~^https?://~i', $href)) return $href;
  if (str_starts_with($href, '//')) {
    $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'http';
    return $scheme . ':' . $href;
  }
  $baseParts = parse_url($base);
  $scheme = $baseParts['scheme'] ?? 'http';
  $host   = $baseParts['host'] ?? '';
  $port   = isset($baseParts['port']) ? (':' . $baseParts['port']) : '';
  $path   = $baseParts['path'] ?? '/';
  if ($href === '') return $base;
  if ($href[0] === '/') return $scheme . '://' . $host . $port . $href;
  $dir = rtrim(substr($path, 0, strrpos($path, '/') + 1), '/') . '/';
  return $scheme . '://' . $host . $port . $dir . $href;
}

function extract_text_and_links(string $html, string $baseUrl): array {
  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA | LIBXML_COMPACT);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);
  // remove scripts/styles/nav/footer/header
  foreach (['script','style','nav','footer'] as $tag) {
    foreach ($dom->getElementsByTagName($tag) as $node) { $node->parentNode?->removeChild($node); }
  }
  $title = '';
  $t = $dom->getElementsByTagName('title'); if ($t->length) $title = trim($t->item(0)->textContent ?? '');
  $bodyNode = $dom->getElementsByTagName('body')->item(0);
  $text = trim($bodyNode ? preg_replace('/\s+/u', ' ', $bodyNode->textContent ?? '') : '');
  // links
  $links = [];
  foreach ($dom->getElementsByTagName('a') as $a) {
    $href = trim((string)$a->getAttribute('href'));
    if ($href === '' || str_starts_with($href, '#')) continue;
    $abs = absolutize($baseUrl, $href);
    $links[] = $abs;
  }
  // de-dup
  $links = array_values(array_unique($links));
  return ['title' => $title, 'text' => $text, 'links' => $links];
}

function chunk_text(string $text, int $targetWords = 700, int $overlap = 80): array {
  $words = preg_split('/\s+/u', trim($text));
  $chunks = [];
  $i = 0; $n = count($words);
  while ($i < $n) {
    $end = min($n, $i + $targetWords);
    $chunk = implode(' ', array_slice($words, $i, $end - $i));
    if (mb_strlen(trim($chunk)) > 0) $chunks[] = $chunk;
    if ($end >= $n) break;
    $i = $end - min($overlap, $end); // overlap
    if ($i < 0) $i = 0;
  }
  return $chunks;
}

function crawl_and_upsert(array $opts): array {
  $startUrl  = rtrim((string)($opts['start_url'] ?? ''), '/');
  $depthMax  = max(0, (int)($opts['depth'] ?? 3));
  $maxPages  = max(1, (int)($opts['max_pages'] ?? 200));
  $excludeRe = (string)($opts['exclude'] ?? '');
  $allowHost = parse_url($startUrl, PHP_URL_HOST) ?: '';
  if ($startUrl === '' || $allowHost === '') return ['ok'=>false,'message'=>'invalid start_url'];

  $visited = [];
  $queue = new SplQueue();
  $queue->enqueue([$startUrl, 0]);
  $count = 0;
  $collection = (string)env('RAG_COLLECTION', 'crosing_ar');

  while (!$queue->isEmpty()) {
    [$url, $depth] = $queue->dequeue();
    if (isset($visited[$url])) continue;
    $visited[$url] = true;
    if ($excludeRe !== '' && @preg_match($excludeRe, $url)) continue;
    if (!same_domain($startUrl, $url)) continue;
    $resp = http_fetch($url);
    if (!$resp['ok'] || stripos($resp['type'], 'text/html') === false) continue;
    $parsed = extract_text_and_links($resp['body'], $url);
    if ($parsed['text'] !== '') {
      $chunks = chunk_text($parsed['text']);
      $metas  = array_map(function($c) use ($parsed,$url){ return ['lang'=>'ar','category'=>'site','title'=>($parsed['title']?:'صفحة'),'path_or_url'=>$url]; }, $chunks);
      try { rag_upsert_chunks($collection, $chunks, $metas); } catch (Throwable $e) { /* ignore */ }
      $count++;
    }
    if ($count >= $maxPages) break;
    if ($depth < $depthMax) {
      foreach ($parsed['links'] as $lnk) {
        if (!isset($visited[$lnk]) && same_domain($startUrl, $lnk)) $queue->enqueue([$lnk, $depth+1]);
      }
    }
    // throttle
    usleep(250000); // 0.25s
  }
  return ['ok'=>true,'pages'=>$count];
}
