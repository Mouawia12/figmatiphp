<?php
/**
 * /crosing/download.php
 * Secure file download guarded by api_keys (Bearer or ?token= for testing)
 */
require __DIR__ . '/inc/functions.php';
$config = cfg();

/* ---------- helpers ---------- */
function read_bearer_token(): string {
  if (!empty($_GET['token'])) return (string)$_GET['token']; // for testing only
  $keys = ['HTTP_AUTHORIZATION','REDIRECT_HTTP_AUTHORIZATION','Authorization'];
  foreach ($keys as $k) {
    if (!empty($_SERVER[$k]) && preg_match('/Bearer\s+(.+)/i', $_SERVER[$k], $m)) {
      return trim($m[1]);
    }
  }
  return '';
}
function http_json($code, $arr){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
function deny($code=403,$msg='unauthorized'){
  http_json($code,['ok'=>0,'error'=>$msg]);
}
/* تنظيف اسم الملف ومنع أي محاولات هروب/مسار */
function safe_filename(string $name): string {
  $name = basename($name);                           // يمنع المسارات
  $name = preg_replace('/[^\P{C}]+/u', '', $name);   // يحذف المحارف التحكمية
  $name = str_replace(["\r","\n"], '', $name);       // يمنع كسر الرؤوس
  return $name;
}

/* ---------- auth: allow either logged-in session OR api token ---------- */
session_start();
// Accept either legacy user_id or new structured session user['id']
$hasSession = !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);
$keyId = 0;
if (!$hasSession) {
  $token = read_bearer_token();
  if ($token === '') deny(401,'token_required');
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
  if ($keyId <= 0) deny(403,'invalid_token');
}

/* ---------- validate file param ---------- */
$fname = $_GET['file'] ?? '';
if ($fname === '') deny(400,'file_required');

/* منع المسارات المشبوهة + تنظيف الاسم */
$fname = safe_filename($fname);

/* paths */
$dir = rtrim($config->upload_dir, '/\\');
$file = $dir . DIRECTORY_SEPARATOR . $fname;

/* تحقق أن الملف داخل مجلد الرفع فعلاً */
$realDir  = realpath($dir);
$realFile = realpath($file);
if ($realDir === false || $realFile === false || strpos($realFile, $realDir) !== 0) {
  deny(404,'not_found');
}
if (!is_file($realFile) || !is_readable($realFile)) {
  deny(404,'not_found');
}

/* ---------- stream file ---------- */
@ini_set('zlib.output_compression', '0'); // لا تضغط أثناء التحميل
while (ob_get_level()) { ob_end_clean(); }
header('Content-Description: File Transfer');

$mime = function_exists('mime_content_type') ? @mime_content_type($realFile) : 'application/octet-stream';
if (!$mime) $mime = 'application/octet-stream';
$size = filesize($realFile);
if ($size !== false) header('Content-Length: ' . $size);

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

/* الاسم المرسل للتحميل (مع دعم UTF-8) */
$as = isset($_GET['as']) ? trim((string)$_GET['as']) : '';
if ($as !== '') {
  // تنظيف الاسم الظاهر دون التأثير على المسار الفعلي
  $as = preg_replace("/[\\\r\\\n]/", '', $as);
  $downloadName = $as;
} else {
  $downloadName = $fname;
}
$encoded = rawurlencode($downloadName);
$inline = isset($_GET['inline']) && (int)$_GET['inline'] === 1;
$disp = $inline ? 'inline' : 'attachment';
header('Content-Disposition: ' . $disp . '; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . $encoded);

/* تحديث آخر استخدام للمفتاح (إن وُجد) */
if (!$hasSession && $keyId > 0) {
  $dbu->prepare("UPDATE api_keys SET last_used_at = datetime('now') WHERE id=?")->execute([$keyId]);
}

/* إرسال الملف */
$fp = fopen($realFile, 'rb');
if ($fp === false) deny(500,'server_error');
set_time_limit(0);
fpassthru($fp);
fclose($fp);
exit;
