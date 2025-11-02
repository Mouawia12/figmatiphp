<?php
declare(strict_types=1);

/**
 * /crosing/api_chat.php
 * API موحّد للدردشة
 */

// منع عرض الأخطاء كـ HTML - فقط JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/init_chat_db.php';
session_start();

// التأكد من عدم وجود أي output قبل JSON
ob_start();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// معالجة OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Bind each browser to its own chat session key cookie to prevent cross-customer access
function ensure_chat_cookie(): string {
    $val = isset($_COOKIE['azm_chat_sid']) ? preg_replace('/[^a-zA-Z0-9]/','', (string)$_COOKIE['azm_chat_sid']) : '';
    if ($val === '' || mb_strlen($val) < 8) {
        $val = bin2hex(random_bytes(16));
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if (PHP_VERSION_ID >= 70300) {
            setcookie('azm_chat_sid', $val, [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie('azm_chat_sid', $val, time() + 31536000, '/');
        }
        $_COOKIE['azm_chat_sid'] = $val;
    }
    return $val;
}

// Ensure cookie is present for all requests
$__CHAT_COOKIE = ensure_chat_cookie();

try {
    $config = cfg();
    $chatDbPath = __DIR__ . '/data/chat.db';
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }
    $dsn = 'sqlite:' . $chatDbPath;
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo = init_chat_database($pdo);
} catch (Exception $e) {
    ob_end_clean();
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database connection failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cs = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token'])) {
        if (empty($cs) || !hash_equals($_SESSION['csrf_token'], $cs)) {
            if (in_array($action, ['delete_conversation', 'clear_conversation'])) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'CSRF token mismatch'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
}

function ok($data=[], $meta=[]) { 
    ob_end_clean();
    echo json_encode(['success'=>true,'data'=>$data,'meta'=>$meta], JSON_UNESCAPED_UNICODE); 
    exit; 
}
function bad($msg, $code=400) { 
    ob_end_clean();
    http_response_code($code); 
    echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); 
    exit; 
}
function norm_sender_type(string $t, string $sid=''): string {
    $t = strtolower($t);
    $sid = strtolower($sid);
    if ($t === 'admin' && in_array($sid, ['bot','assistant','ai'])) return 'bot';
    if (in_array($t, ['assistant','ai','system'])) return 'bot';
    if (in_array($t, ['user','customer'])) return 'customer';
    if (!in_array($t, ['admin','customer','bot'])) return 'customer';
    return $t;
}

if ($action === 'identify') {
    $staff = [
        'id'   => (string)($_SESSION['user']['id']   ?? ''),
        'name' => (string)($_SESSION['user']['name'] ?? 'موظف'),
        'role' => (string)($_SESSION['user']['role'] ?? 'admin'),
        'email'=> (string)($_SESSION['user']['email'] ?? '')
    ];
    ok($staff);
}

if ($action === 'start_conversation') {
    $name  = trim($_POST['customer_name']  ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $cid   = trim($_POST['customer_id']    ?? '') ?: null;
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        bad('Invalid email format');
    }
    $name = $name ?: 'زائر';
    $email = $email ?: null;
    try {
        $st = $pdo->prepare("INSERT INTO chat_conversations (customer_id, customer_name, customer_email, session_key, last_message) VALUES (?,?,?,?,?)");
        $st->execute([$cid, $name, $email, (string)($_COOKIE['azm_chat_sid'] ?? ''), null]);
        $convId = (int)$pdo->lastInsertId();
        // أول رسالة ترحيب محفوظة في قاعدة البيانات
        try {
            $greet = 'معاك عزم مساعدك الذكي ارحب امرني';
            $stm = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message, meta) VALUES (?,?,?,?,?)");
            $stm->execute([$convId, 'bot', 'azm', $greet, null]);
            $pdo->prepare("UPDATE chat_conversations SET last_message=? WHERE id=?")->execute([$greet, $convId]);
        } catch (Throwable $e) { error_log('Failed to insert greet: '.$e->getMessage()); }
        ok(['conversation_id'=>$convId, 'customer_id'=>$cid, 'session_id'=>bin2hex(random_bytes(16))]);
    } catch (Exception $e) {
        error_log("Error in start_conversation: " . $e->getMessage());
        bad('Failed to start conversation', 500);
    }
}

if ($action === 'get_conversations') {
    $stmt = $pdo->query("SELECT id, customer_name, customer_email, staff_id, last_message, created_at, updated_at FROM chat_conversations WHERE is_deleted=0 AND is_archived=0 ORDER BY updated_at DESC LIMIT 200");
    $rows = $stmt->fetchAll();
    $meta = ['count'=>count($rows)];
    ok($rows, $meta);
}

if ($action === 'get_messages') {
    $conv = (int)($_GET['conversation_id'] ?? 0);
    if (!$conv) bad('conversation_id required');
    // claim or enforce session ownership
    try {
        $ownq = $pdo->prepare('SELECT session_key FROM chat_conversations WHERE id=?');
        $ownq->execute([$conv]);
        $own = $ownq->fetch();
        $current = isset($_COOKIE['azm_chat_sid']) ? preg_replace('/[^a-zA-Z0-9]/','', (string)$_COOKIE['azm_chat_sid']) : '';
        if ($own) {
            $sk = (string)($own['session_key'] ?? '');
            if ($sk === '' && $current !== '') {
                $pdo->prepare('UPDATE chat_conversations SET session_key=? WHERE id=?')->execute([$current, $conv]);
            } elseif ($sk === '' && $current === '') {
                // No cookie yet: create a fresh conversation and bind it
                $current = ensure_chat_cookie();
                $st = $pdo->prepare("INSERT INTO chat_conversations (customer_id, customer_name, customer_email, session_key, last_message) VALUES (?,?,?,?,?)");
                $st->execute([null, null, null, $current, null]);
                $conv = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?,?,?,?)")
                    ->execute([$conv, 'bot', 'azm', 'ꩥ��� ��! ��� �����?']);
            } elseif ($sk !== '' && !hash_equals($sk, $current)) {
                // Start a fresh conversation for this browser instead of exposing others' chats
                $st = $pdo->prepare("INSERT INTO chat_conversations (customer_id, customer_name, customer_email, session_key, last_message) VALUES (?,?,?,?,?)");
                $st->execute([null, null, null, $current, null]);
                $conv = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?,?,?,?)")
                    ->execute([$conv, 'bot', 'azm', 'مرحباً بك! كيف أساعدك؟']);
            }
        }
    } catch (Throwable $e) { /* ignore */ }
    $stmt = $pdo->prepare("SELECT id, conversation_id, sender_type, sender_id, message, meta, created_at FROM chat_messages WHERE conversation_id=? ORDER BY id ASC LIMIT 2000");
    $stmt->execute([$conv]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['sender_type'] = norm_sender_type($r['sender_type'] ?? 'customer', $r['sender_id'] ?? '');
    }
    ok($rows);
}

if ($action === 'send_message') {
    $conv   = (int)($_POST['conversation_id'] ?? 0);
    $text   = trim((string)($_POST['message'] ?? ''));
    $stype  = (string)($_POST['sender_type'] ?? 'customer');
    $sid    = (string)($_POST['sender_id']   ?? null);
    if (!$conv) bad('conversation_id required');
    if ($text === '') bad('message cannot be empty');
    if (strlen($text) > 5000) bad('message too long (max 5000 characters)');
    $stype = norm_sender_type($stype, $sid);
    try {
        // claim or enforce session ownership
        try {
            $ownq = $pdo->prepare('SELECT session_key FROM chat_conversations WHERE id=?');
            $ownq->execute([$conv]);
            $own = $ownq->fetch();
            $current = isset($_COOKIE['azm_chat_sid']) ? preg_replace('/[^a-zA-Z0-9]/','', (string)$_COOKIE['azm_chat_sid']) : '';
            if ($own) {
                $sk = (string)($own['session_key'] ?? '');
                if ($sk === '' && $current !== '') {
                    $pdo->prepare('UPDATE chat_conversations SET session_key=? WHERE id=?')->execute([$current, $conv]);
                } elseif ($sk !== '' && !hash_equals($sk, $current)) {
                    bad('forbidden', 403);
                }
            }
        } catch (Throwable $e) { /* ignore */ }
        $st = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?,?,?,?)");
        $st->execute([$conv, $stype, $sid, $text]);
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $upd = $pdo->prepare("UPDATE chat_conversations SET last_message=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $upd->execute([$text, $conv]);
            if ($stype === 'admin') {
                $upd2 = $pdo->prepare("UPDATE chat_conversations SET staff_id=? WHERE id=? AND staff_id IS NULL");
                $upd2->execute([$sid, $conv]);
            }
        } else {
            $upd = $pdo->prepare("UPDATE chat_conversations SET last_message=?, updated_at=NOW(), staff_id=IF(staff_id IS NULL AND ?='admin', ?, staff_id) WHERE id=?");
            $upd->execute([$text, $stype, $sid, $conv]);
        }
        $msgId = (int)$pdo->lastInsertId();
        if (in_array($stype, ['customer', 'admin'])) {
            $botReply = generate_bot_response($text, $conv, $pdo);
            $st2 = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?,?,?,?)");
            $st2->execute([$conv, 'bot', 'ai_bot', $botReply]);
        }
        ok(['id'=>$msgId]);
    } catch (Exception $e) {
        error_log("Error in send_message: " . $e->getMessage());
        bad('Failed to send message', 500);
    }
}

// تنظيف أي output غير متوقع
ob_end_clean();
bad('Unknown action', 404);

function generate_bot_response(string $message, int $conversation_id, PDO $pdo): string {
    $message = trim($message);
    if (empty($message)) {
        return "عذراً، لم أستقبل رسالتك. الرجاء إعادة المحاولة.";
    }
    $is_arabic = (bool)preg_match('/[\x{0600}-\x{06FF}]/u', $message);
    $api_key = getenv('OPENAI_API_KEY');
    if ($api_key && $api_key !== 'YOUR_OPENAI_API_KEY' && strlen($api_key) > 20) {
        $reply = generate_openai_response_sa($message, $api_key, $is_arabic);
        if ($reply) return $reply;
    }
    return sa_default_response($message, $conversation_id, $pdo);
}

// نسخة مخصصة بأسلوب سعودي وشخصية "عزم"
function generate_openai_response_sa(string $message, string $api_key, bool $is_arabic = true): ?string {
    try {
        $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
        $message = mb_substr($message, 0, 2000, 'UTF-8');

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        $system_prompt = $is_arabic
            ? "أنت عزم: مساعد ذكي سعودي. تحدّث دائماً بالعربية بأسلوب سعودي بسيط وواضح، وتجنّب أي لهجات أو صيغ من دول أخرى. عند أول رد فقط عرّف نفسك بعبارة: 'معاك عزم مساعدك الذكي ارحب امرني'. بعد ذلك، أجب بإيجاز وبشكل عملي ومباشر، واطلب توضيحاً عند الحاجة. لا تذكر أنك نموذج أو ذكاء اصطناعي، ولا تستخدم تعبيرات غير سعودية."
            : "You are Azm, a helpful assistant for a Saudi audience. Use concise, direct responses. Avoid mentioning you are an AI.";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => 0.6,
            'max_tokens' => 800,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if ($curl_error) {
            error_log('OpenAI cURL Error: ' . $curl_error);
            return 'تعذّر الاتصال بالخدمة: ' . $curl_error;
        }
        if ($http_code === 200) {
            $data = json_decode($response, true);
            return $data['choices'][0]['message']['content'] ?? null;
        }
        $data = json_decode($response, true);
        $api_error_message = $data['error']['message'] ?? $response;
        error_log('OpenAI API Error (HTTP ' . $http_code . '): ' . $api_error_message);
        return 'خطأ من خدمة الذكاء الاصطناعي: ' . $api_error_message;
    } catch (Exception $e) {
        error_log('OpenAI Exception: ' . $e->getMessage());
        return 'حصل خطأ غير متوقع أثناء المعالجة.';
    }
}

// New default responder that avoids التكرار ويستخدم بيانات التدريب
function sa_default_response(string $message, int $conversation_id, PDO $pdo): string {
    // Optional file-based knowledge
    foreach (['/data/ai_knowledge.json','/data/knowledge.json'] as $rel) {
        $path = __DIR__ . $rel;
        if (is_readable($path)) {
            try {
                $json = json_decode((string)file_get_contents($path), true);
                if (is_array($json)) {
                    foreach ($json as $row) {
                        $q = (string)($row['q'] ?? ($row['question'] ?? ''));
                        $a = (string)($row['a'] ?? ($row['answer'] ?? ''));
                        $kw= (string)($row['keywords'] ?? '');
                        $kwl = preg_split('/[,|،]/u', $kw) ?: [];
                        $hit = false;
                        foreach ($kwl as $k) { $k = trim($k); if ($k !== '' && mb_stripos($message, $k) !== false) { $hit = true; break; } }
                        if (!$hit && $q !== '' && mb_stripos($message, $q) !== false) $hit = true;
                        if ($hit && $a !== '') return $a;
                    }
                }
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    try {
        $rows = $pdo->query("SELECT question,answer,keywords,confidence_score,id FROM ai_training_data ORDER BY confidence_score DESC, id DESC LIMIT 200")->fetchAll();
        foreach ($rows ?: [] as $r) {
            $kw = preg_split('/[,|،]/u', (string)($r['keywords'] ?? '')) ?: [];
            $hit = false;
            foreach ($kw as $k) { $k = trim((string)$k); if ($k !== '' && mb_stripos($message, $k) !== false) { $hit = true; break; } }
            if (!$hit && mb_stripos($message, (string)($r['question'] ?? '')) !== false) $hit = true;
            if ($hit) return (string)$r['answer'];
        }
    } catch (Throwable $e) { /* ignore */ }

    $rules = [
        ['kw'=>['سعر','التكلفة','التسعير','عرض السعر'], 'res'=>'للتسعير: أرسل تفاصيل الطلب عبر نموذج الطلب وسنزوّدك بعرض سعر خلال 24 ساعة.'],
        ['kw'=>['موعد','مدة','كم يأخذ','كم يستغرق'], 'res'=>'المدة تعتمد على نوع الخدمة وحجم العمل. بعد استلام التفاصيل نشاركك المدة المتوقعة وخطوات التنفيذ.'],
        ['kw'=>['دفع','الدفع','فاتورة','تحويل'], 'res'=>'طرق الدفع: تحويل بنكي وفاتورة إلكترونية موثقة. تصلك بعد اعتماد العرض.'],
        ['kw'=>['دعم','مشكلة','مساعدة','تواصل'], 'res'=>'صف مشكلتك أو رقم الطلب وسنساعدك خطوة بخطوة. يمكنك أيضاً فتح تذكرة من لوحة العميل.'],
        ['kw'=>['طلب','جديد','خدمة'], 'res'=>'لبدء طلب جديد استخدم صفحة الطلب وحدد النوع والمرفقات. سنراجع الطلب ونتواصل معك بالتفاصيل.'],
    ];
    foreach ($rules as $r) { foreach ($r['kw'] as $k) { if (mb_stripos($message, $k) !== false) return $r['res']; } }
    return 'أوضح سؤالك وسأجيبك مباشرة بخطوات عملية.';
}
