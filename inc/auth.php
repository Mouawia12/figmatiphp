<?php
declare(strict_types=1);

/**
 * crosing/inc/auth.php
 * دوال المصادقة والمساعدة:
 * - التعامل مع المستخدمين (قراءة/إنشاء/تعيين كلمة مرور)
 * - تنسيق أرقام الجوال السعودية (محلي/E.164) + إخفاء آخر 4 أرقام
 * - استدعاء Authentica (إرسال/تحقق OTP)
 * - Turnstile تحقق بشري لمرة واحدة
 */

require_once __DIR__ . '/db.php'; // يوفر الدالة db(): PDO

/* ===========================
   أدوات عامة
   =========================== */

if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

// Functions ksa_local() and to_e164_sa() are now defined in functions.php

/**
 * إرجاع رقم E.164 موثوق للمستخدم حتى لو كانت القيمة المخزنة ناقصة.
 * - لو phone_e164 صالح نستخدمه
 * - وإلا نبنيه من phone_local إن كان بصيغة 05XXXXXXXX
 * - وإلا نرجّع قيمة افتراضية آمنة
 */
function ensure_e164(array $u): string {
  $e164   = isset($u['phone_e164']) ? (string)$u['phone_e164'] : '';
  $digits = preg_replace('/\D+/', '', $e164); // 9665XXXXXXXX
  if (strlen($digits) >= 12 && substr($digits, 0, 3) === '966') {
    return '+' . $digits;
  }
  $local = isset($u['phone_local']) ? (string)$u['phone_local'] : '';
  if ($local !== '' && ksa_local($local)) {
    return to_e164_sa($local);
  }
  return '+9665********';
}

/** إخفاء الرقم مع إظهار آخر 4 أرقام الحقيقية */
function mask_phone_last4_from_user(array $u): string {
  $e164   = ensure_e164($u);
  $digits = preg_replace('/\D+/', '', $e164);
  $last4  = substr($digits, -4);
  return '+9665******' . $last4;
}

/* ===========================
   المستخدمون (قاعدة البيانات)
   =========================== */

/** جلب مستخدم عبر البريد (يرجع مصفوفة أو null) */
function user_find_by_email(string $email): ?array {
  $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $st->execute([strtolower($email)]);
  $u = $st->fetch();
  return $u ?: null;
}

/** جلب مستخدم عبر ID (يرجع مصفوفة أو null) */
function user_find_by_id(int $id): ?array {
  $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
  $st->execute([$id]);
  $u = $st->fetch();
  return $u ?: null;
}

/** جلب مستخدم عبر رقم الجوال المحلي (يرجع مصفوفة أو null) */
function user_find_by_phone(string $phone): ?array {
  $st = db()->prepare('SELECT * FROM users WHERE phone_local = ? OR phone_e164 = ? LIMIT 1');
  $st->execute([$phone, to_e164_sa($phone)]);
  $u = $st->fetch();
  return $u ?: null;
}

/**
 * إنشاء مستخدم جديد
 * - name, email, phone_local (05XXXXXXXX)
 * - يُخزّن phone_e164 تلقائياً
 * - password اختياري (NULL لو فارغة)
 * يرجع id
 */
function user_create(string $name, string $email, string $phoneLocal, ?string $password): int {
  if (!ksa_local($phoneLocal)) {
    throw new InvalidArgumentException('رقم محلي غير صالح (يتوقع 05XXXXXXXX).');
  }
  $hash = ($password !== null && $password !== '') ? password_hash($password, PASSWORD_DEFAULT) : null;
  $pdo = db();
  $st = $pdo->prepare(
    'INSERT INTO users (name, email, phone_local, phone_e164, password_hash)
     VALUES (?, ?, ?, ?, ?)'
  );
  $st->execute([$name, strtolower($email), $phoneLocal, to_e164_sa($phoneLocal), $hash]);
  $newId = (int)$pdo->lastInsertId();

  // Ensure avatar_path exists and set default avatar
  try {
    // Try adding the column if it doesn't exist (works for MySQL/SQLite, ignore errors)
    $cfg = cfg();
    if (($cfg->db_driver ?? 'sqlite') === 'mysql') {
      $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL");
    } else {
      $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path TEXT NULL");
    }
  } catch (Throwable $e) { /* column may already exist */ }

  try {
    $defaultAvatar = 'assets/img/profile.png';
    $upd = $pdo->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
    $upd->execute([$defaultAvatar, $newId]);
  } catch (Throwable $e) { /* ignore if table lacks column unexpectedly */ }

  return $newId;
}

/** تعيين/تحديث كلمة مرور (لا يلمس updated_at لتفادي أخطاء الجداول القديمة) */
function user_set_password(int $id, string $password): void {
  $st = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
  $st->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}

/** جلب كل المستخدمين مع بياناتهم الأساسية */
function get_all_users(): array {
  $st = db()->query('SELECT id, name, email, phone_local, role, created_at FROM users ORDER BY id DESC');
  return $st->fetchAll() ?: [];
}

/* ===========================
   Authentica (OTP)
   =========================== */

/** طلب HTTP JSON بسيط مع cURL */
function http_json(string $method, string $url, array $headers, ?array $json = null): array {
  if (!function_exists('curl_init')) {
    throw new RuntimeException('cURL غير مفعّل على الخادم.');
  }
  $ch = curl_init($url);
  if ($json) $headers[] = 'Content-Type: application/json';
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => strtoupper($method),
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 5,
  ]);
  if ($json) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE));
  }
  $resp = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  if (curl_errno($ch)) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new RuntimeException("cURL: $err");
  }
  curl_close($ch);
  $dec = $resp ? json_decode($resp, true) : null;

  if ($code >= 200 && $code < 300) {
    return is_array($dec) ? $dec : ['status' => $code, 'raw' => $resp];
  }
  $msg = is_array($dec) && isset($dec['message']) ? (string)$dec['message'] : "HTTP $code";
  throw new RuntimeException("Authentica API error: $msg");
}

/**
 * إرسال OTP عبر Authentica API (مطابق للمواصفات)
 * 
 * @param string|null $phone رقم الهاتف بصيغة E.164 (مطلوب إذا كان method = sms أو whatsapp)
 * @param string|null $email البريد الإلكتروني (مطلوب إذا كان method = email)
 * @param string $method طريقة الإرسال: 'sms', 'whatsapp', 'email' (افتراضي: 'sms')
 * @param int|null $template_id معرف القالب (اختياري، افتراضي من إعدادات التطبيق)
 * @param string|null $fallback_phone رقم هاتف احتياطي للـ fallback
 * @param string|null $fallback_email بريد احتياطي للـ fallback
 * @param string|null $custom_otp OTP مخصص (أرقام فقط، اختياري)
 * @return array النتيجة مع success و message و data
 */
function authentica_send_otp(?string $phone = null, ?string $email = null, string $method = 'sms', ?int $template_id = null, ?string $fallback_phone = null, ?string $fallback_email = null, ?string $custom_otp = null): array {
  // إذا تم تعطيل OTP، أرجع رد وهمي
  if (getenv('SKIP_OTP') === 'true') {
    return ['success' => true, 'data' => null, 'message' => 'OTP send successfully (DEMO)', 'reference' => 'DEMO_' . bin2hex(random_bytes(8))];
  }
  
  $base = rtrim(getenv('AUTHENTICA_BASE_URL') ?: 'https://api.authentica.sa/api/v2', '/');
  $key  = getenv('AUTHENTICA_API_KEY') ?: '';
  if ($key === '') {
    throw new RuntimeException('AUTHENTICA_API_KEY غير مضبوط.');
  }
  
  // بناء payload حسب المواصفات
  $payload = [];
  
  // method (مطلوب إذا لم يكن هناك default في إعدادات التطبيق)
  $defaultMethod = getenv('AUTHENTICA_DEFAULT_METHOD');
  if ($method || !$defaultMethod) {
    $payload['method'] = $method ?: 'sms';
  }
  
  // phone (مطلوب إذا كان method = sms أو whatsapp)
  if (in_array($method, ['sms', 'whatsapp']) && $phone) {
    $payload['phone'] = $phone;
  } elseif (in_array($method, ['sms', 'whatsapp']) && !$phone) {
    throw new InvalidArgumentException('phone مطلوب عندما method = ' . $method);
  }
  
  // email (مطلوب إذا كان method = email)
  if ($method === 'email' && $email) {
    $payload['email'] = $email;
  } elseif ($method === 'email' && !$email) {
    throw new InvalidArgumentException('email مطلوب عندما method = email');
  }
  
  // template_id (اختياري)
  if ($template_id !== null) {
    $payload['template_id'] = $template_id;
  }
  
  // fallback_phone (مطلوب إذا كان fallback channel = sms أو whatsapp)
  if ($fallback_phone) {
    $payload['fallback_phone'] = $fallback_phone;
  }
  
  // fallback_email (مطلوب إذا كان fallback channel = email)
  if ($fallback_email) {
    $payload['fallback_email'] = $fallback_email;
  }
  
  // custom OTP (اختياري، أرقام فقط)
  if ($custom_otp !== null && preg_match('/^\d+$/', $custom_otp)) {
    $payload['otp'] = $custom_otp;
  }
  
  return http_json(
    'POST',
    $base . '/send-otp',
    ['Accept: application/json', 'X-Authorization: ' . $key, 'Content-Type: application/json'],
    $payload
  );
}

/**
 * التحقق من OTP (مطابق للمواصفات)
 * 
 * @param string $otp رمز OTP للتحقق منه (مطلوب)
 * @param string|null $phone رقم الهاتف (مطلوب إذا استخدم sms أو whatsapp كقناة أساسية أو احتياطية)
 * @param string|null $email البريد الإلكتروني (مطلوب إذا استخدم email كقناة أساسية أو احتياطية)
 * @return array النتيجة مع status و message
 */
function authentica_verify_otp(string $otp, ?string $phone = null, ?string $email = null): array {
  // إذا تم تعطيل OTP، قبل أي رمز
  if (getenv('SKIP_OTP') === 'true') {
    return ['status' => true, 'message' => 'OTP verified successfully (DEMO)'];
  }
  
  $base = rtrim(getenv('AUTHENTICA_BASE_URL') ?: 'https://api.authentica.sa/api/v2', '/');
  $key  = getenv('AUTHENTICA_API_KEY') ?: '';
  if ($key === '') {
    throw new RuntimeException('AUTHENTICA_API_KEY غير مضبوط.');
  }
  
  // بناء payload حسب المواصفات
  $payload = ['otp' => $otp];
  
  // phone (مطلوب إذا استخدم sms أو whatsapp)
  if ($phone) {
    $payload['phone'] = $phone;
  }
  
  // email (مطلوب إذا استخدم email)
  if ($email) {
    $payload['email'] = $email;
  }
  
  // يجب أن يكون هناك على الأقل phone أو email
  if (empty($payload['phone']) && empty($payload['email'])) {
    throw new InvalidArgumentException('يجب توفير phone أو email للتحقق من OTP');
  }

  return http_json(
    'POST',
    $base . '/verify-otp',
    ['Accept: application/json', 'X-Authorization: ' . $key, 'Content-Type: application/json'],
    $payload
  );
}

// دالة authentica_send_sms موجودة الآن في ملف functions.php مع ميزات إضافية مثل تسجيل الإشعارات

/* ===========================
   Cloudflare Turnstile (مرة واحدة)
   =========================== */

/** هل نحتاج Turnstile لهذه الجلسة؟ (لم يتم وسم الجهاز بعد) */
function need_turnstile(): bool {
  return empty($_COOKIE['cf_human']);
}

/** تحقق من رمز Turnstile */
function verify_turnstile_once(string $secret, string $token): bool {
  if ($secret === '' || $token === '') return false;
  try {
    $r = http_json(
      'POST',
      'https://challenges.cloudflare.com/turnstile/v0/siteverify',
      [],
      ['secret' => $secret, 'response' => $token]
    );
    return !empty($r['success']);
  } catch (Throwable $e) {
    return false;
  }
}
