<?php
/**
 * /crosing/api.php
 * JSON API (forms / request / requests / files)
 * - Auth via admin-generated tokens in db_users.api_keys (is_active=1)
 * - Returns unified JSON: { ok:1, data:[...], meta:{...} } OR { ok:0, error:"...", detail:null }
 */

require __DIR__ . '/inc/functions.php';
$config = cfg();

/* -----------------------------------------------------------
 * Helpers
 * --------------------------------------------------------- */

/** Read bearer token from GET or Authorization header (Cloudflare-safe) */
function api_read_token(): string {
  $t = $_GET['token'] ?? '';
  if ($t !== '') return $t;

  $hdrs = [
    'HTTP_AUTHORIZATION',
    'REDIRECT_HTTP_AUTHORIZATION',
    'Authorization',          // some SAPIs
  ];
  foreach ($hdrs as $h) {
    if (!empty($_SERVER[$h]) && preg_match('/Bearer\s+(.+)/i', $_SERVER[$h], $m)) {
      return trim($m[1]);
    }
  }
  return '';
}

/** Simple JSON out helper */
function api_out(array $arr, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/** Clamp helper */
function clamp_int($v, int $min, int $max, int $default): int {
  if ($v === null || $v === '') return $default;
  $i = (int)$v;
  if ($i < $min) return $min;
  if ($i > $max) return $max;
  return $i;
}

/** Ensure minimal requests schema if functions.php didn't define it */
function api_ensure_requests_schema(PDO $db): void {
  $db->exec("
    CREATE TABLE IF NOT EXISTS requests (
      id INTEGER PRIMARY KEY,
      form_id INTEGER,
      name TEXT,
      email TEXT,
      message TEXT,
      file TEXT,
      status TEXT DEFAULT 'pending',
      tracking_code TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );
    CREATE INDEX IF NOT EXISTS idx_requests_id       ON requests(id);
    CREATE INDEX IF NOT EXISTS idx_requests_created  ON requests(created_at);
  ");
}

/* -----------------------------------------------------------
 * Auth via api_keys
 * --------------------------------------------------------- */

$token = api_read_token();
if ($token === '') {
  api_out(['ok'=>0,'error'=>'token_required','detail'=>null], 401);
}

$dbu = pdo_open($config->db_users);
$dbu->exec("
  CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY,
    label TEXT NOT NULL,
    token TEXT NOT NULL UNIQUE,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    last_used_at TEXT
  );
  CREATE INDEX IF NOT EXISTS idx_api_keys_token ON api_keys(token);
");

$st = $dbu->prepare("SELECT id FROM api_keys WHERE token=? AND is_active=1");
$st->execute([$token]);
$keyId = (int)$st->fetchColumn();
if (!$keyId) {
  api_out(['ok'=>0,'error'=>'invalid_token','detail'=>null], 403);
}

/* لاحقًا سنحدث آخر استخدام فقط إذا مرّ التنفيذ بنجاح. */

/* -----------------------------------------------------------
 * Common params
 * --------------------------------------------------------- */

$what   = $_GET['what']   ?? 'requests';
$full   = (int)($_GET['full'] ?? 0);
$limit  = clamp_int($_GET['limit']  ?? null, 1, 200, 100);
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : null;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
if ($page !== null) $offset = ($page - 1) * $limit;

/* لروابط التحميل */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$downloadBase = "{$scheme}://{$host}{$base}/download.php";

/* -----------------------------------------------------------
 * Routes
 * --------------------------------------------------------- */

try {

  /* ---------- forms: قائمة النماذج ---------- */
  if ($what === 'forms') {
    $dbf = pdo_open($config->db_forms);
    $dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
    $q = $dbf->prepare("SELECT id, title, created_at FROM forms ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $q->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $q->bindValue(':offset', $offset, PDO::PARAM_INT);
    $q->execute();
    $data = $q->fetchAll(PDO::FETCH_ASSOC);

    $meta = [
      'limit'  => $limit,
      'offset' => $offset,
      'page'   => ($page ?? null),
      'count'  => count($data),
      'next_offset' => $offset + count($data),
    ];

    // تحديث آخر استخدام للمفتاح
    $dbu->prepare("UPDATE api_keys SET last_used_at = datetime('now') WHERE id=?")->execute([$keyId]);

    api_out(['ok'=>1,'data'=>$data,'meta'=>$meta], 200);
  }

  /* ---------- request: طلب واحد بالمعرّف ---------- */
  if ($what === 'request') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) api_out(['ok'=>0,'error'=>'id_required','detail'=>null], 400);

    $dbr = pdo_open($config->db_requests);
    if (function_exists('ensure_requests_schema')) ensure_requests_schema();
    else api_ensure_requests_schema($dbr);

    $sel = $full ? "SELECT id, form_id, name, email, message, file, status, tracking_code, created_at, data_json FROM requests WHERE id=?"
                 : "SELECT id, form_id, name, email, message, file, status, tracking_code, created_at FROM requests WHERE id=?";
    $q = $dbr->prepare($sel);
    $q->execute([$id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) api_out(['ok'=>0,'error'=>'not_found','detail'=>null], 404);

    if ($full && isset($row['data_json'])) {
      $payload = json_decode((string)$row['data_json'], true) ?: [];
      // enrich attachments with signed URLs (token-based)
      $files = $payload['files'] ?? [];
      $filesOut = [];
      foreach ($files as $k=>$v) {
        $saved = is_array($v) ? ($v['saved'] ?? '') : (string)$v;
        $orig  = is_array($v) ? ($v['orig']  ?? $saved) : $saved;
        if ($saved==='') continue;
        $url = $downloadBase . '?file=' . rawurlencode($saved) . '&token=' . rawurlencode($token) . '&as=' . rawurlencode($orig);
        $preview = $url . '&inline=1';
        $filesOut[$k] = ['saved'=>$saved,'orig'=>$orig,'url'=>$url,'preview_url'=>$preview];
      }
      $payload['files'] = $filesOut;
      $row['payload'] = $payload;
      unset($row['data_json']);
    }

    $dbu->prepare("UPDATE api_keys SET last_used_at = datetime('now') WHERE id=?")->execute([$keyId]);

    api_out(['ok'=>1,'data'=>$row,'meta'=>['id'=>$id]], 200);
  }

  /* ---------- requests: قائمة الطلبات (تدعم تزايدي) ---------- */
  if ($what === 'requests') {
    $form_id        = isset($_GET['form_id']) ? (int)$_GET['form_id'] : null;
    $qtext          = trim($_GET['q'] ?? '');
    $since_id       = isset($_GET['since_id']) ? max(0, (int)$_GET['since_id']) : null;
    $since_ts       = trim($_GET['since_ts'] ?? '');  // "YYYY-MM-DD" أو "YYYY-MM-DD HH:MM:SS"
    $include_updates= (int)($_GET['include_updates'] ?? 0); // احتياطي للمستقبل

    $dbr = pdo_open($config->db_requests);
    if (function_exists('ensure_requests_schema')) ensure_requests_schema();
    else api_ensure_requests_schema($dbr);

    $where = [];
    $args  = [];

    if (!is_null($form_id)) {
      $where[] = 'form_id = ?';
      $args[]  = $form_id;
    }

    if ($qtext !== '') {
      $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
      $like = '%' . $qtext . '%';
      $args[] = $like; $args[] = $like; $args[] = $like;
    }

    if (!is_null($since_id)) {
      $where[] = 'id > ?';
      $args[]  = $since_id;
    }

    if ($since_ts !== '') {
      // بما أن جدولنا لا يملك updated_at رسميًا، نستخدم created_at
      $where[] = 'datetime(created_at) >= datetime(?)';
      $args[]  = $since_ts;
    }

    $sql  = $full
      ? "SELECT id, form_id, name, email, message, file, status, tracking_code, created_at, data_json FROM requests"
      : "SELECT id, form_id, name, email, message, file, status, tracking_code, created_at FROM requests";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);

    // عند التزايدي (since_id/ts) نرجّع تصاعدي لتسهيل التتبع
    $order = (!is_null($since_id) || $since_ts !== '') ? "ORDER BY id ASC" : "ORDER BY id DESC";
    $sql  .= " $order LIMIT :limit OFFSET :offset";

    $q = $dbr->prepare($sql);
    $i = 1;
    foreach ($args as $v) {
      $q->bindValue($i++, $v);
    }
    $q->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $q->bindValue(':offset', $offset, PDO::PARAM_INT);
    $q->execute();

    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    if ($full && $rows) {
      foreach ($rows as &$row) {
        $payload = isset($row['data_json']) ? (json_decode((string)$row['data_json'], true) ?: []) : [];
        $files = $payload['files'] ?? [];
        $filesOut = [];
        foreach ($files as $k=>$v) {
          $saved = is_array($v) ? ($v['saved'] ?? '') : (string)$v;
          $orig  = is_array($v) ? ($v['orig']  ?? $saved) : $saved;
          if ($saved==='') continue;
          $url = $downloadBase . '?file=' . rawurlencode($saved) . '&token=' . rawurlencode($token) . '&as=' . rawurlencode($orig);
          $preview = $url . '&inline=1';
          $filesOut[$k] = ['saved'=>$saved,'orig'=>$orig,'url'=>$url,'preview_url'=>$preview];
        }
        $payload['files'] = $filesOut;
        $row['payload'] = $payload;
        unset($row['data_json']);
      }
      unset($row); // break reference
    }

    // meta.next_since_id: آخر id في النتيجة (يفيد السحب التزايدي)
    $next_since_id = null;
    if ($rows) {
      $last = end($rows);
      $next_since_id = (int)$last['id'];
    }

    $meta = [
      'limit'         => $limit,
      'offset'        => $offset,
      'page'          => ($page ?? null),
      'count'         => count($rows),
      'next_offset'   => $offset + count($rows),
      'next_since_id' => $next_since_id,
      'ordered'       => (!is_null($since_id) || $since_ts !== '') ? 'ASC' : 'DESC',
    ];

    $dbu->prepare("UPDATE api_keys SET last_used_at = datetime('now') WHERE id=?")->execute([$keyId]);

    api_out(['ok'=>1,'data'=>$rows,'meta'=>$meta], 200);
  }

  /* ---------- files: قائمة المرفقات وروابط تنزيلها ---------- */
  if ($what === 'files') {
    $dir = $config->upload_dir;
    $out = [];
    if (is_dir($dir)) {
      foreach (array_values(array_filter(scandir($dir), fn($x)=> !in_array($x,['.','..']))) as $f) {
        $out[] = [
          'file' => $f,
          'url'  => $downloadBase . '?file=' . rawurlencode($f) . '&token=' . rawurlencode($token),
        ];
      }
    }

    $dbu->prepare("UPDATE api_keys SET last_used_at = datetime('now') WHERE id=?")->execute([$keyId]);

    api_out(['ok'=>1,'data'=>$out,'meta'=>['count'=>count($out)]], 200);
  }

  /* ---------- unknown ---------- */
  api_out(['ok'=>0,'error'=>'unknown_endpoint','detail'=>null], 404);

} catch (Throwable $e) {
  api_out(['ok'=>0,'error'=>'server_error','detail'=>$e->getMessage()], 500);
}
