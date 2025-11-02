<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

if (defined('CROSING_FUNCS_LOADED')) { return; }

function ensure_crawl_schema(): void {
    $c  = cfg();
    $db = pdo_open('notifications');
    if (($c->db_driver ?? 'sqlite') === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS crawl_runs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mode VARCHAR(16) NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'queued',
            pages INT NOT NULL DEFAULT 0,
            notes TEXT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS crawl_runs (
            id INTEGER PRIMARY KEY,
            mode TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'queued',
            pages INTEGER NOT NULL DEFAULT 0,
            notes TEXT,
            started_at TEXT NOT NULL DEFAULT (datetime('now')),
            finished_at TEXT
        )");
    }
}

function ensure_training_queue_schema(): void {
    $c  = cfg();
    $db = pdo_open('notifications');
    if (($c->db_driver ?? 'sqlite') === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS training_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(64) NOT NULL,
            ref_id VARCHAR(64) NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            note TEXT NULL,
            payload_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS training_queue (
            id INTEGER PRIMARY KEY,
            type TEXT NOT NULL,
            ref_id TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            note TEXT,
            payload_json TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    }
}

// ---- Chat analytics (most asked questions and unanswered tracking) ----
function ensure_chat_analytics_schema(): void {
    $c  = cfg();
    $db = pdo_open('notifications'); // reuse notifications db file/conn
    if (($c->db_driver ?? 'sqlite') === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS chat_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_key VARCHAR(255) UNIQUE,
            question_text TEXT,
            asked_count INT NOT NULL DEFAULT 0,
            unanswered_count INT NOT NULL DEFAULT 0,
            last_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS chat_questions (
            id INTEGER PRIMARY KEY,
            question_key TEXT UNIQUE,
            question_text TEXT,
            asked_count INTEGER NOT NULL DEFAULT 0,
            unanswered_count INTEGER NOT NULL DEFAULT 0,
            last_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    }
}

function _normalize_question_key(string $q): string {
    $q = mb_strtolower(trim($q), 'UTF-8');
    // remove punctuation and extra spaces
    $q = preg_replace('/[\p{P}\p{S}]+/u', ' ', $q);
    $q = preg_replace('/\s+/u', ' ', $q);
    // limit length
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
    return (string)$q;
}

function track_chat_question(string $userMessage, bool $hasSources): void {
    try {
        ensure_chat_analytics_schema();
        $db = pdo_open('notifications');
        $key = _normalize_question_key($userMessage);
        if ($key === '') return;
        $isMysql = (cfg()->db_driver ?? 'sqlite') === 'mysql';
        if ($isMysql) {
            $sql = "INSERT INTO chat_questions(question_key, question_text, asked_count, unanswered_count, last_at)
                    VALUES(?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                      question_text = VALUES(question_text),
                      asked_count = asked_count + 1,
                      unanswered_count = unanswered_count + VALUES(unanswered_count),
                      last_at = NOW()";
        } else {
            // Upsert for SQLite
            $sql = "INSERT INTO chat_questions(question_key, question_text, asked_count, unanswered_count, last_at)
                    VALUES(?, ?, 1, ?, datetime('now'))
                    ON CONFLICT(question_key) DO UPDATE SET
                      question_text=excluded.question_text,
                      asked_count=chat_questions.asked_count+1,
                      unanswered_count=chat_questions.unanswered_count+excluded.unanswered_count,
                      last_at=datetime('now')";
        }
        $st = $db->prepare($sql);
        $st->execute([$key, $userMessage, $hasSources ? 0 : 1]);
    } catch (Throwable $e) {
        error_log('track_chat_question failed: ' . $e->getMessage());
    }
}
// Convert any input to local Saudi format 05XXXXXXXX when possible
function to_local_sa(string $phone): string {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (substr($digits, 0, 4) === '9665' && strlen($digits) >= 12) {
        return '0' . substr($digits, 3); // 9665XXXXXXXX -> 05XXXXXXXX
    }
    if (substr($digits, 0, 1) === '5' && strlen($digits) === 9) {
        return '0' . $digits; // 5XXXXXXXX -> 05XXXXXXXX
    }
    if (substr($digits, 0, 2) === '05' && strlen($digits) === 10) {
        return $digits;
    }
    // Fallback: try to_e164 then re-localize
    $e = preg_replace('/[^0-9]/', '', to_e164_sa($phone)); // 9665XXXXXXXX
    if (substr($e, 0, 4) === '9665') return '0' . substr($e, 3);
    return $digits ?: $phone;
}
define('CROSING_FUNCS_LOADED', true);

function load_dotenv(string $path): void {
    if (!is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (substr($trimmed, 0, 1) === '#' || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (strlen($value) > 1 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'" ))) {
            $value = substr($value, 1, -1);
        }
        if (!empty($name)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

if (!function_exists('cfg')) {
    function cfg() {
        static $c = null;
        if ($c === null) {
            load_dotenv(__DIR__ . '/../.env');
            $c = require __DIR__ . '/../config.php';
            if (is_array($c)) $c = (object)$c;
            $c->db_driver = $c->db_driver ?? 'sqlite';
            if ($c->db_driver === 'sqlite') {
                $c->db_users = $c->db_users ?? (__DIR__ . '/../data/users.db');
                $c->db_requests = $c->db_requests ?? (__DIR__ . '/../data/requests.db');
                $c->db_forms = $c->db_forms ?? (__DIR__ . '/../data/forms.db');
                $c->db_notifications = $c->db_notifications ?? (__DIR__ . '/../data/notifications.db');
            } else {
                $c->db_host    = $c->db_host   ?? env('DB_HOST', '127.0.0.1');
                $c->db_name    = $c->db_name   ?? env('DB_DATABASE', env('DB_NAME', 'crosing'));
                $c->db_user    = $c->db_user   ?? env('DB_USERNAME', env('DB_USER', 'root'));
                $c->db_pass    = $c->db_pass   ?? env('DB_PASSWORD', env('DB_PASS', ''));
                $c->db_charset = $c->db_charset?? env('DB_CHARSET', 'utf8mb4');
                $c->db_users        = $c->db_users        ?? 'users';
                $c->db_requests     = $c->db_requests     ?? 'requests';
                $c->db_forms        = $c->db_forms        ?? 'forms';
                $c->db_notifications= $c->db_notifications?? 'notifications';
            }
        }
        return $c;
    }
}

function pdo_open(string $db_name = 'default'): PDO {
    $c = cfg();
    static $pdo_instances = [];
    if (isset($pdo_instances[$db_name])) return $pdo_instances[$db_name];

    if ($c->db_driver === 'mysql') {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c->db_host, $c->db_name, $c->db_charset);
        try {
            $pdo = new PDO($dsn, $c->db_user, $c->db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('SET NAMES utf8mb4');
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Unknown database') !== false || $e->getCode() == 1049) {
                $dsnNoDb = sprintf('mysql:host=%s;charset=%s', $c->db_host, $c->db_charset);
                $pdoTmp = new PDO($dsnNoDb, $c->db_user, $c->db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $dbNameQuoted = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$c->db_name);
                $charset = $c->db_charset ?: 'utf8mb4';
                $pdoTmp->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameQuoted}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
                $pdo = new PDO($dsn, $c->db_user, $c->db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('SET NAMES utf8mb4');
            } else {
                throw $e;
            }
        }
    } else {
        $path = match($db_name) {
            'users' => $c->db_users,
            'requests' => $c->db_requests,
            'forms' => $c->db_forms,
            'notifications' => $c->db_notifications,
            default => $c->db_requests,
        };
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    $pdo_instances[$db_name] = $pdo;
    return $pdo;
}

if (!function_exists('e')) {
    function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
function csrf_token(): string { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); } return $_SESSION['csrf_token']; }
function verify_csrf(string $token): bool { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); }
/**
 * إنشاء رابط نسبي للتطبيق متكيف مع أي مجلد تثبيت
 * 
 * هذه الدالة تحسب المسار الأساسي للتطبيق بناءً على موقع الملف الحالي
 * وتعمل بشكل صحيح حتى لو كان التطبيق مثبت في مجلد فرعي
 * 
 * @param string $path المسار النسبي من جذر التطبيق (مثلاً: 'login.php', 'admin/index.php')
 * @return string رابط كامل نسبي للمتصفح
 */
function app_href(string $path = ''): string {
    // الحصول على مسار السكريبت الحالي
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/index.php';
    
    // تحويل المسار إلى صيغة Unix
    $scriptPath = str_replace('\\', '/', $scriptPath);
    
    // إزالة اسم الملف الحالي للحصول على المسار الأساسي
    $base = dirname($scriptPath);
    
    // تنظيف المسار: إزالة '/' الزائدة في البداية والنهاية
    $base = rtrim($base, '/');
    
    // إذا كان المسار '/' فقط، استخدم مسار فارغ
    if ($base === '/' || $base === '\\' || $base === '.') {
        $base = '';
    }
    
    // تنظيف المسار المطلوب
    $path = ltrim(str_replace('\\', '/', $path), '/');
    
    // دمج المسار الأساسي مع المسار المطلوب
    if ($base === '') {
        return '/' . $path;
    }
    
    return $base . '/' . $path;
}

function handle_upload_limit(array $file, int $max_bytes, array $allowed_ext): string {
    if (empty($file['name'])) throw new RuntimeException('No file uploaded.');
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('File upload error.');
    if ($file['size'] > $max_bytes) throw new RuntimeException('File size exceeds limit.');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) throw new RuntimeException('Unsupported file extension.');
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new RuntimeException('Failed to create uploads directory.');
        }
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('The uploads directory is not writable.');
    }
    do { $name = bin2hex(random_bytes(12)) . '.' . $ext; } while (file_exists($dir . '/' . $name));
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Failed to save uploaded file.');
    return $name;
}

// Backward-compatible wrapper used by upload.php
if (!function_exists('handle_upload')) {
    function handle_upload(array $file): string {
        // Default: 10MB, common image/docs
        $max = 10 * 1024 * 1024;
        $exts = ['jpg','jpeg','png','gif','webp','bmp','svg','pdf','doc','docx','xls','xlsx','ppt','pptx','txt'];
        return handle_upload_limit($file, $max, $exts);
    }
}

function require_admin(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('login.php')); exit; }
    $db = pdo_open('users');
    $st = $db->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $me = $st->fetch();
    if (!$me || ($me['role'] ?? '') !== 'admin') die('Forbidden');
    return $me;
}

function gen_tracking_code(int $len = 10): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) { $out .= $alphabet[random_int(0, strlen($alphabet) - 1)]; }
    return $out;
}

function status_label(string $s): string {
    return match ($s) {
        'pending'          => 'قيد الانتظار',
        'reviewing'        => 'قيد المراجعة',
        'approved'         => 'تم الاعتماد',
        'rejected'         => 'مرفوض',
        'needs_revision'   => 'بانتظار استكمال العميل',
        'customer_editing' => 'العميل يعدّل الطلب',
        default            => 'غير معروف',
    };
}

// --- Secure temporary edit links for customer request updates ---
if (!function_exists('edit_link_secret')) {
    function edit_link_secret(): string {
        $secret = (string)env('REQUEST_EDIT_SECRET', 'dev-insecure-secret');
        if ($secret === '' || $secret === 'change-me-please') {
            static $rand = null; if ($rand === null) { $rand = bin2hex(random_bytes(16)); } 
            return $rand;
        }
        return $secret;
    }
}
if (!function_exists('make_edit_token')) {
    function make_edit_token(string $trackingCode, int $expiresTs): string {
        $data = $trackingCode . '|' . $expiresTs;
        return hash_hmac('sha256', $data, edit_link_secret());
    }
}
if (!function_exists('verify_edit_token')) {
    function verify_edit_token(string $trackingCode, int $expiresTs, string $sig): bool {
        if ($expiresTs < time()) return false;
        $expected = make_edit_token($trackingCode, $expiresTs);
        return hash_equals($expected, $sig);
    }
}
if (!function_exists('edit_link_for_request')) {
    function edit_link_for_request(string $trackingCode, int $hours = 48): string {
        $exp = time() + max(1, $hours) * 3600;
        $sig = make_edit_token($trackingCode, $exp);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/');
        $path   = ($base === '' || $base === '/') ? '/edit-request.php' : ($base . '/edit-request.php');
        $qs     = http_build_query(['code'=>$trackingCode,'exp'=>$exp,'sig'=>$sig]);
        return $scheme . '://' . $host . $path . '?' . $qs;
    }
}

function to_e164_sa(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 3) === '966') return '+' . $phone;
    if (substr($phone, 0, 2) === '05') return '+966' . substr($phone, 1);
    if (substr($phone, 0, 1) === '5') return '+966' . $phone;
    return '+' . ltrim($phone, '+');
}
function ksa_local(string $phone): bool {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^05[0-9]{8}$/', $phone) === 1;
}

/**
 * طلب عام إلى Authentica API (مطابق للمواصفات)
 * 
 * @param string $endpoint مسار الـ endpoint (مثل: '/send-sms', '/send-otp', '/balance')
 * @param array $data البيانات للإرسال (فارغة للـ GET requests)
 * @param string $method طريقة HTTP: 'GET', 'POST' (افتراضي: 'POST')
 * @return array النتيجة مع success و message و data
 */
function authentica_api_request(string $endpoint, array $data = [], string $method = 'POST'): array {
    $baseUrl = rtrim(env('AUTHENTICA_BASE_URL', 'https://api.authentica.sa/api/v2'), '/');
    $apiKey = env('AUTHENTICA_API_KEY');
    if (empty($apiKey)) {
        $errorMsg = 'Authentica API key is not configured';
        @file_put_contents(__DIR__ . '/../sms_errors.log', 
            "[" . date('Y-m-d H:i:s') . "] CONFIGURATION ERROR: {$errorMsg}\n", 
            FILE_APPEND | LOCK_EX
        );
        return ['success' => false, 'message' => $errorMsg];
    }
    
    $verifySsl = filter_var(env('AUTHENTICA_SSL_VERIFY', '1'), FILTER_VALIDATE_BOOLEAN);
    $fullUrl = $baseUrl . $endpoint;
    $ch = curl_init($fullUrl);
    
    $headers = [
        'X-Authorization: ' . $apiKey,
        'Accept: application/json'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ]);
    
    if (!empty($data) && $method !== 'GET') {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    if ($curlError || $curlErrno) {
        $errorMsg = $curlError ?: "cURL Error #{$curlErrno}";
        $result = ['success' => false, 'message' => $errorMsg, 'http_code' => $httpCode ?: 0, 'curl_errno' => $curlErrno];
        @file_put_contents(__DIR__ . '/../sms_errors.log', 
            "[" . date('Y-m-d H:i:s') . "] CURL ERROR: {$errorMsg} | Endpoint: {$endpoint} | Duration: {$duration}ms | HTTP Code: " . ($httpCode ?: 'N/A') . "\n", 
            FILE_APPEND | LOCK_EX
        );
        return $result;
    }
    
    $result = json_decode($response, true) ?: [];
    if ($httpCode >= 400) {
        $result['success'] = false;
        $errorMessage = $result['errors'][0]['message'] ?? $result['message'] ?? 'Unknown API error';
        $result['message'] = $errorMessage;
        $result['http_code'] = $httpCode;
        $result['raw_response'] = $response;
        
        // تسجيل خطأ API
        @file_put_contents(__DIR__ . '/../sms_errors.log', 
            "[" . date('Y-m-d H:i:s') . "] API ERROR: {$errorMessage} | Endpoint: {$endpoint} | HTTP Code: {$httpCode} | Duration: {$duration}ms\n", 
            FILE_APPEND | LOCK_EX
        );
    } else {
        $result['success'] = $result['success'] ?? true;
        $result['http_code'] = $httpCode;
    }
    
    return $result;
}

/**
 * الحصول على الرصيد الحالي من Authentica API (مطابق للمواصفات)
 * 
 * @return array النتيجة مع success و data.balance و message
 */
function authentica_get_balance(): array {
    $result = authentica_api_request('/balance', [], 'GET');
    return $result;
}

if (!function_exists('asset_href')) {
    /**
     * إنشاء رابط للأصول الثابتة (CSS, JS, الصور) متكيف مع أي مجلد تثبيت
     * 
     * هذه الدالة تحسب المسار الصحيح للأصول الثابتة حتى لو كان التطبيق مثبت في مجلد فرعي
     * وتتعامل تلقائياً مع الملفات الموجودة في مجلد admin
     * 
     * @param string $path المسار النسبي للأصل من مجلد assets (مثلاً: 'assets/styles.css', 'assets/img/logo.png')
     * @return string رابط كامل نسبي للمتصفح
     */
    function asset_href(string $path = ''): string {
        // الحصول على مسار السكريبت الحالي
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/index.php';
        
        // تحويل المسار إلى صيغة Unix
        $scriptPath = str_replace('\\', '/', $scriptPath);
        
        // إزالة اسم الملف الحالي للحصول على المسار الأساسي
        $base = dirname($scriptPath);
        
        // تنظيف المسار: إزالة '/' الزائدة في البداية والنهاية
        $base = rtrim($base, '/');
        
        // إذا كان المسار '/' فقط، استخدم مسار فارغ
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        
        // إذا كنا في مجلد admin، نعود لمجلد الجذر
        if (substr($base, -6) === '/admin') {
            $base = substr($base, 0, -6);
        }
        
        // تنظيف المسار المطلوب
        $path = ltrim(str_replace('\\', '/', $path), '/');
        
        // دمج المسار الأساسي مع المسار المطلوب
        if ($base === '') {
            return '/' . $path;
        }
        
        return $base . '/' . $path;
    }
}

// Build absolute public URL for use in outbound messages (SMS/Email)
function public_base_url(): string {
    $envBase = trim((string)env('PUBLIC_BASE_URL', ''));
    if ($envBase !== '') { return rtrim($envBase, '/'); }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}
function public_url(string $path = ''): string {
    return public_base_url() . '/' . ltrim($path, '/');
}

function public_is_localhost(): bool {
    $envBase = trim((string)env('PUBLIC_BASE_URL', ''));
    if ($envBase !== '') {
        $h = parse_url($envBase, PHP_URL_HOST) ?: '';
    } else {
        $h = $_SERVER['HTTP_HOST'] ?? '';
    }
    $h = strtolower($h);
    return $h === 'localhost' || $h === '127.0.0.1' || substr($h, 0, 10) === 'localhost:' || substr($h, 0, 9) === '127.0.0.1:';
}

function safe_mail_headers(string $fromEmail, string $replyTo = ''): string {
    $fromName = env('MAIL_FROM_NAME', 'عزم الإنجاز');
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: " . mb_encode_mimeheader($fromName, 'UTF-8', 'Q') . " <" . filter_var($fromEmail, FILTER_SANITIZE_EMAIL) . ">\r\n";
    if ($replyTo !== '') {
        $headers .= "Reply-To: " . filter_var($replyTo, FILTER_SANITIZE_EMAIL) . "\r\n";
    }
    $headers .= "X-Mailer: PHP/" . phpversion();
    return $headers;
}

/**
 * تسجيل تفاصيل محاولات إرسال SMS في ملف sms_errors.log
 * 
 * @param string $phone رقم الهاتف
 * @param string $message نص الرسالة
 * @param array $result نتيجة محاولة الإرسال
 * @param string $context سياق الإرسال (مثل: 'new_request', 'status_update', 'customer_note')
 * @param array $extra معلومات إضافية (مثل: request_id, tracking_code)
 */
function log_sms_attempt(string $phone, string $message, array $result, string $context = 'general', array $extra = []): void {
    $logFile = __DIR__ . '/../sms_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $success = $result['success'] ?? false;
    $status = $success ? 'SUCCESS' : 'FAILED';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'status' => $status,
        'context' => $context,
        'phone' => $phone,
        'phone_formatted' => $extra['phone_formatted'] ?? $phone,
        'message_length' => mb_strlen($message),
        'message_preview' => mb_substr($message, 0, 100) . (mb_strlen($message) > 100 ? '...' : ''),
        'api_response' => [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'No message',
            'http_code' => $result['http_code'] ?? 0,
            'raw_response' => $result['raw'] ?? null,
        ],
        'extra' => $extra,
    ];
    
    // إضافة تفاصيل الخطأ إذا فشل الإرسال
    if (!$success) {
        $logEntry['error_details'] = [
            'error_message' => $result['message'] ?? 'Unknown error',
            'http_code' => $result['http_code'] ?? 0,
            'api_error' => $result['errors'][0]['message'] ?? null,
        ];
    }
    
    $logLine = sprintf(
        "[%s] %s | Context: %s | Phone: %s | Status: %s | Message: %s | Response: %s\n",
        $timestamp,
        $status,
        $context,
        $phone,
        $status,
        mb_substr($message, 0, 50),
        json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    
    // تسجيل في الملف
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    
    // إذا فشل الإرسال، أضف تفاصيل إضافية
    if (!$success) {
        $errorDetails = sprintf(
            "  └─ Error Details: %s | HTTP Code: %d | Phone Format: %s\n",
            $result['message'] ?? 'Unknown',
            $result['http_code'] ?? 0,
            $extra['phone_formatted'] ?? 'N/A'
        );
        @file_put_contents($logFile, $errorDetails, FILE_APPEND | LOCK_EX);
    }
}

function authentica_send_sms(string $phone, string $message, string $context = 'general', array $extra = []): array {
    // Decide output format based on env
    $format = strtolower((string)env('AUTHENTICA_PHONE_FORMAT', 'e164')); // e164|digits|local
    $originalPhone = $phone;
    $candidate = to_e164_sa($phone); // +9665XXXXXXXX by default
    
    if ($format === 'digits') {
        // Send as digits only (e.g., 9665XXXXXXXX)
        $candidate = preg_replace('/[^0-9]/', '', $candidate ?: $phone);
    } elseif ($format === 'local') {
        // Force local 05XXXXXXXX when possible
        $candidate = to_local_sa($phone);
    } else { // e164
        // Ensure leading +
        $candidate = to_e164_sa($phone);
    }

    // تسجيل محاولة الإرسال قبل التنفيذ
    $senderName = env('SMS_SENDER', 'CROSING');
    $logExtra = array_merge($extra, [
        'phone_original' => $originalPhone,
        'phone_formatted' => $candidate,
        'format_type' => $format,
        'sender_name' => $senderName,
    ]);

    $payload = [
        'phone' => $candidate, 
        'message' => $message,
        'sender_name' => $senderName
    ];
    $endpoint = env('AUTHENTICA_SMS_ENDPOINT', '/send-sms');
    
    // تسجيل بداية المحاولة
    $startTime = microtime(true);
    $result = authentica_api_request($endpoint, $payload);
    $duration = round((microtime(true) - $startTime) * 1000, 2); // بالمللي ثانية
    
    // إضافة مدة التنفيذ للنتيجة
    $result['duration_ms'] = $duration;
    
    // تسجيل النتيجة في sms_errors.log
    log_sms_attempt($phone, $message, $result, $context, array_merge($logExtra, ['duration_ms' => $duration]));
    
    // تسجيل في قاعدة البيانات (إن أمكن)
    try {
        ensure_notifications_schema();
        $pdo = pdo_open('notifications');
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO notifications (type, subject, message, recipients, status, created_at, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $status = ($result['success'] ?? false) ? 'sent' : 'failed';
        $storedMsg = $message;
        if ($status === 'failed') {
            $apiErr = ($result['message'] ?? '') . ' | format=' . $format . ' phone_sent=' . $candidate . ' http=' . ($result['http_code'] ?? 0);
            if ($apiErr !== '') { $storedMsg .= ' | ERROR: ' . $apiErr; }
        }
        $stmt->execute(['sms', 'SMS Notification', $storedMsg, $candidate, $status, $now, $now]);
    } catch (Exception $e) {
        error_log('Failed to log SMS notification to database: ' . $e->getMessage());
        // تسجيل خطأ قاعدة البيانات في ملف السجل
        @file_put_contents(__DIR__ . '/../sms_errors.log', 
            "[" . date('Y-m-d H:i:s') . "] WARNING: Failed to log SMS to database: " . $e->getMessage() . "\n", 
            FILE_APPEND | LOCK_EX
        );
    }
    
    return $result;
}

function ensure_notifications_schema(): void {
    $c  = cfg();
    $db = pdo_open('notifications');
    if ($c->db_driver === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL, recipients TEXT NOT NULL, type VARCHAR(32) NOT NULL, status VARCHAR(32) DEFAULT 'pending', sent_at DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $maybe = [
            'subject VARCHAR(255)',
            'message TEXT',
            'recipients TEXT',
            'type VARCHAR(32)',
            "status VARCHAR(32) DEFAULT 'pending'",
            'sent_at DATETIME',
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP'
        ];
        foreach ($maybe as $col) { try { $db->exec("ALTER TABLE notifications ADD COLUMN $col"); } catch (Throwable $e) {} }
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY, subject TEXT NOT NULL, message TEXT NOT NULL, recipients TEXT NOT NULL, type TEXT NOT NULL, status TEXT DEFAULT 'pending', sent_at TEXT, created_at TEXT DEFAULT (datetime('now')))");
        $maybe = [
            'subject TEXT', 'message TEXT', 'recipients TEXT', 'type TEXT', "status TEXT DEFAULT 'pending'", 'sent_at TEXT', "created_at TEXT DEFAULT (datetime('now'))"
        ];
        foreach ($maybe as $col) { try { $db->exec("ALTER TABLE notifications ADD COLUMN $col"); } catch (Throwable $e) {} }
    }
}

function ensure_requests_schema(): void {
    $c  = cfg();
    $db = pdo_open('requests');
    if (($c->db_driver ?? 'sqlite') === 'mysql') {
        $charset = $c->db_charset ?? 'utf8mb4';
        $db->exec("CREATE TABLE IF NOT EXISTS requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            form_id INT NULL,
            name VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            message TEXT NULL,
            file VARCHAR(255) NULL,
            data_json LONGTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            status_note TEXT NULL,
            status_updated_at DATETIME NULL,
            tracking_code VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}");
        try { $db->exec('CREATE INDEX idx_requests_created ON requests (created_at)'); } catch (Throwable $e) {} 
        try { $db->exec('CREATE INDEX idx_requests_tracking ON requests (tracking_code)'); } catch (Throwable $e) {} 
        $maybe = [
            'user_id INT NULL',
            'data_json LONGTEXT',
            "status VARCHAR(32) NOT NULL DEFAULT 'pending'",
            'status_note TEXT',
            'status_updated_at DATETIME',
            'tracking_code VARCHAR(64)'
        ];
        foreach ($maybe as $col) { try { error_log("DEBUG: Altering requests table (MySQL): ADD COLUMN $col"); $db->exec("ALTER TABLE requests ADD COLUMN $col"); } catch (Throwable $e) { error_log("ERROR: Altering requests table (MySQL): " . $e->getMessage()); } }
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            form_id INTEGER,
            name TEXT,
            email TEXT,
            message TEXT,
            file TEXT,
            data_json TEXT,
            status TEXT DEFAULT 'pending',
            status_note TEXT,
            status_updated_at TEXT,
            tracking_code TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_requests_created ON requests(created_at)"); } catch (Throwable $e) {} 
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_requests_tracking ON requests(tracking_code)"); } catch (Throwable $e) {} 
        $maybe = [ 'user_id INTEGER', 'data_json TEXT', "status TEXT DEFAULT 'pending'", 'status_note TEXT', 'status_updated_at TEXT', 'tracking_code TEXT' ];
        foreach ($maybe as $col) { try { error_log("DEBUG: Altering requests table (SQLite): ADD COLUMN $col"); $db->exec("ALTER TABLE requests ADD COLUMN $col"); } catch (Throwable $e) { error_log("ERROR: Altering requests table (SQLite): " . $e->getMessage()); } }
    }
}

function ensure_support_tables_exist(): void {
    $c = cfg();
    $db = pdo_open('users');

    if ($c->db_driver === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (id INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ticket_columns = [
            'user_id'    => 'INT NOT NULL',
            'subject'    => 'VARCHAR(255) NOT NULL',
            'category'   => 'VARCHAR(100) NOT NULL',
            'priority'   => 'VARCHAR(20) NOT NULL',
            'status'     => "VARCHAR(20) NOT NULL DEFAULT 'open'",
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ];
        foreach ($ticket_columns as $column => $definition) {
            try { $db->exec("ALTER TABLE support_tickets ADD COLUMN {$column} {$definition}"); } catch (Throwable $e) { /* ignore */ }
        }

        $db->exec("CREATE TABLE IF NOT EXISTS support_ticket_messages (id INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $message_columns = [
            'ticket_id'   => 'INT NOT NULL DEFAULT 0',
            'author_type' => 'VARCHAR(20) NOT NULL DEFAULT \'user\'',
            'author_id'   => 'INT NOT NULL DEFAULT 0',
            'message'     => 'TEXT NOT NULL',
            'created_at'  => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'is_read'     => 'TINYINT(1) NOT NULL DEFAULT 0'
        ];
        foreach ($message_columns as $column => $definition) {
            try { $db->exec("ALTER TABLE support_ticket_messages ADD COLUMN {$column} {$definition}"); } catch (Throwable $e) { /* ignore */ }
        }
        try { $db->exec("ALTER TABLE support_ticket_messages ADD FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE"); } catch (Throwable $e) { /* ignore */ }

    } else { // SQLite
        $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (id INTEGER PRIMARY KEY AUTOINCREMENT)");
        $ticket_columns = [
            'user_id'    => 'INTEGER NOT NULL DEFAULT 0',
            'subject'    => 'TEXT NOT NULL DEFAULT \'\'',
            'category'   => 'TEXT NOT NULL DEFAULT \'\'',
            'priority'   => 'TEXT NOT NULL DEFAULT \'\'',
            'status'     => "TEXT NOT NULL DEFAULT 'open'",
            'created_at' => "TEXT NOT NULL DEFAULT (datetime('now'))",
            'updated_at' => "TEXT NOT NULL DEFAULT (datetime('now'))"
        ];
        foreach ($ticket_columns as $column => $definition) {
            try { $db->exec("ALTER TABLE support_tickets ADD COLUMN {$column} {$definition}"); } catch (Throwable $e) { /* ignore */ }
        }

        $db->exec("CREATE TABLE IF NOT EXISTS support_ticket_messages (id INTEGER PRIMARY KEY AUTOINCREMENT)");
        $message_columns = [
            'ticket_id'   => 'INTEGER NOT NULL DEFAULT 0',
            'author_type' => 'TEXT NOT NULL DEFAULT \'user\'',
            'author_id'   => 'INTEGER NOT NULL DEFAULT 0',
            'message'     => 'TEXT NOT NULL DEFAULT \'\'',
            'created_at'  => "TEXT NOT NULL DEFAULT (datetime('now'))",
            'is_read'     => 'INTEGER NOT NULL DEFAULT 0'
        ];
        foreach ($message_columns as $column => $definition) {
            try { $db->exec("ALTER TABLE support_ticket_messages ADD COLUMN {$column} {$definition}"); } catch (Throwable $e) { /* ignore */ }
        }
        try { $db->exec("ALTER TABLE support_ticket_messages ADD FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE"); } catch (Throwable $e) { /* ignore */ }
    }
}

function get_unread_support_messages_count(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user']['id'])) return 0;

    try {
        // Ensure tables exist first
        ensure_support_tables_exist();
        
        $user_id = (int)$_SESSION['user']['id'];
        
        $db = pdo_open('users');
        
        // Get user role
        $st = $db->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$user_id]);
        $role = $st->fetchColumn();

        if ($role === 'admin') {
            // Admin sees messages from users
            $sql = "SELECT COUNT(m.id) 
                    FROM support_ticket_messages m
                    WHERE m.is_read = 0 AND m.author_type = 'user'";
            $stmt = $db->query($sql);
            return (int)$stmt->fetchColumn();
        } else {
            // User sees messages from admins on their tickets
            $sql = "SELECT COUNT(m.id) 
                    FROM support_ticket_messages m
                    JOIN support_tickets t ON m.ticket_id = t.id
                    WHERE m.is_read = 0 
                      AND m.author_type = 'admin' 
                      AND t.user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        }
    } catch (Throwable $e) {
        // If tables don't exist or any error, return 0
        error_log("get_unread_support_messages_count error: " . $e->getMessage());
        return 0;
    }
}

function ensure_customer_preferences_table_exists(): void {
    $db = pdo_open('users');
    if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS customer_preferences (
            customer_id INT PRIMARY KEY,
            language VARCHAR(10),
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS customer_preferences (
            customer_id INTEGER PRIMARY KEY,
            language TEXT
        )");
    }
}

function ensure_users_table_exists(): void {
    $db = pdo_open('users');
    if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(190), email VARCHAR(190) UNIQUE, phone VARCHAR(50) UNIQUE,
            avatar_path VARCHAR(255) NULL,
            role VARCHAR(30) DEFAULT 'user',
            email_verified_at DATETIME NULL, phone_verified_at DATETIME NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL
        ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT, email TEXT UNIQUE, phone TEXT UNIQUE,
            avatar_path TEXT NULL,
            role TEXT DEFAULT 'user',
            email_verified_at TEXT NULL, phone_verified_at TEXT NULL,
            created_at TEXT NULL, updated_at TEXT NULL
        )");
    }
}
