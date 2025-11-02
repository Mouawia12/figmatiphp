<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/../../inc/crawl.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);

$mode = (string)($data['mode'] ?? 'full');
$start_url = trim((string)($data['start_url'] ?? env('PUBLIC_BASE_URL', '')));
$depth = (int)($data['depth'] ?? 3);
$max_pages = (int)($data['max_pages'] ?? 200);
$exclude = (string)($data['exclude'] ?? '/admin|/login|/assets|/api');

if ($start_url === '') api_fail('missing start_url');

try {
  ensure_crawl_schema();
  $db = pdo_open('notifications');
  // create run
  $isMysql = (cfg()->db_driver ?? 'sqlite') === 'mysql';
  if ($isMysql) {
    $db->exec("CREATE TABLE IF NOT EXISTS crawl_runs (id INT AUTO_INCREMENT PRIMARY KEY, mode VARCHAR(16), status VARCHAR(16), pages INT, notes TEXT, started_at DATETIME DEFAULT CURRENT_TIMESTAMP, finished_at DATETIME NULL)");
  }
  $st = $db->prepare($isMysql ? "INSERT INTO crawl_runs(mode,status,pages,notes,started_at) VALUES(?, 'running', 0, ?, NOW())" : "INSERT INTO crawl_runs(mode,status,pages,notes,started_at) VALUES(?, 'running', 0, ?, datetime('now'))");
  $st->execute([$mode, json_encode(['start_url'=>$start_url,'depth'=>$depth,'max_pages'=>$max_pages,'exclude'=>$exclude], JSON_UNESCAPED_UNICODE)]);
  $run_id = (int)$db->lastInsertId();

  // execute crawl synchronously (MVP)
  $opts = ['start_url'=>$start_url,'depth'=>$depth,'max_pages'=>$max_pages,'exclude'=>$exclude];
  $res = crawl_and_upsert($opts);
  $pages = (int)($res['pages'] ?? 0);
  $status = ($res['ok'] ?? false) ? 'finished' : 'failed';
  $notes = ($res['ok'] ?? false) ? null : (string)($res['message'] ?? 'failed');

  $upd = $db->prepare($isMysql ? "UPDATE crawl_runs SET status=?, pages=?, notes=?, finished_at=NOW() WHERE id=?" : "UPDATE crawl_runs SET status=?, pages=?, notes=?, finished_at=datetime('now') WHERE id=?");
  $upd->execute([$status, $pages, $notes, $run_id]);

  api_ok(['ok'=>($res['ok'] ?? false), 'run_id'=>$run_id, 'pages'=>$pages]);
} catch (Throwable $e) {
  api_fail('crawl_failed', 500, ['detail'=>$e->getMessage()]);
}
