<?php declare(strict_types=1);
$APP = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/auth.php';   // فيه db() + user_* + authentica + turnstile helpers
require_once __DIR__ . '/inc/functions.php'; // Added to provide ksa_local() and other helpers

session_start();

// معالجة تسجيل الخروج
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- إعدادات الحماية من هجمات التخمين ---
define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_LOCKOUT_PERIOD', 15 * 60);
define('LOGIN_ATTEMPT_FILE', __DIR__ . '/inc/login_attempts.json');


/* إعدادات عامة */
$TURNSTILE_SITEKEY = getenv('TURNSTILE_SITEKEY') ?: '';
$TURNSTILE_SECRET  = getenv('TURNSTILE_SECRET') ?: '';

$siteTitle = "شركة عزم الإنجاز";
$modelName = "الحساب";

/* الوضع الحالي للواجهة (نفس الصفحة) */
$mode = isset($_GET['mode']) && $_GET['mode']==='register' ? 'register' : 'login';

/* حقول POST مشتركة */
$action   = $_POST['action']   ?? '';          // login_email | login_password | login_otp | register_submit
$email    = trim((string)($_POST['email']    ?? ''));
$password = (string)($_POST['password'] ?? '');
$otp      = trim((string)($_POST['otp']      ?? ''));
$phone_reset = trim((string)($_POST['phone_reset'] ?? ''));
$new_password = (string)($_POST['new_password'] ?? '');

/* حقول التسجيل */
$nameReg  = trim((string)($_POST['name']  ?? ''));
$phoneReg = trim((string)($_POST['phone'] ?? ''));

$flow = $_POST['flow'] ?? 'email'; // login flow (email -> password -> otp)
$err  = ''; $info = '';

try {

  /* ---------- نسيت كلمة المرور: الخطوة 1 (طلب) ---------- */
  if ($action === 'forgot_password_request') {
    $mode = 'login';
    if ($phone_reset === '') throw new InvalidArgumentException('أدخل رقم جوالك.');
    if (!ksa_local($phone_reset)) throw new InvalidArgumentException('أدخل رقم سعودي بصيغة 05XXXXXXXX');

    $u = user_find_by_phone($phone_reset);
    if (!$u) throw new RuntimeException('رقم الجوال غير مسجل.');

    // إرسال OTP
    $phoneE164 = ensure_e164($u);
    $resp = authentica_send_otp(phone: $phoneE164, method: 'sms');
    $_SESSION['forgot_password_flow'] = [
        'user_id' => (int)$u['id'],
        'phone_e164' => $phoneE164,
        'otp_ref' => $resp['reference'] ?? null,
        'otp_start' => time()
    ];
    $flow = 'forgot_password_otp';
    $info = 'تم إرسال رمز التحقق إلى جوالك.';
  }

  /* ---------- نسيت كلمة المرور: الخطوة 2 (تأكيد) ---------- */
  if ($action === 'forgot_password_reset') {
    $mode = 'login';
    if (empty($_SESSION['forgot_password_flow'])) throw new RuntimeException('انتهت الجلسة، أعد المحاولة.');
    if ($otp === '' || !preg_match('/^\d{4,8}$/', $otp)) throw new InvalidArgumentException('الرمز غير صحيح.');
    if (mb_strlen($new_password) < 6) throw new InvalidArgumentException('كلمة المرور يجب ألا تقل عن 6 أحرف.');
    
    $flow_data = $_SESSION['forgot_password_flow'];

    authentica_verify_otp(otp: $otp, phone: $flow_data['phone_e164']);

    user_set_password((int)$flow_data['user_id'], $new_password);

    unset($_SESSION['forgot_password_flow']);
    
    // Log the user in
    session_regenerate_id(true);
    $u = user_find_by_id((int)$flow_data['user_id']);
    $_SESSION['user'] = ['id' => $u['id'], 'email' => $u['email'], 'login_at' => time()];
    header('Location: ' . app_href('index.php'));
    exit;
  }

  /* ---------- تسجيل المستخدم الجديد ---------- */
  if ($action === 'register_submit') {
    $mode = 'register'; // ابق في وضع التسجيل لو صار خطأ

    // Turnstile مرة واحدة (اختياري - يمكن تعطيله في البيئة المحلية)
    $skip_turnstile = getenv('SKIP_TURNSTILE') === 'true';
    if (!$skip_turnstile && $TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()){
      $tk = $_POST['cf-turnstile-response'] ?? '';
      if (!verify_turnstile_once($TURNSTILE_SECRET, $tk)) throw new RuntimeException('فشل التحقق البشري، حاول مجددًا.');
      setcookie('cf_human','1',['expires'=>time()+60*60*24*30,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
    }

    if ($nameReg==='' || $email==='' || $phoneReg==='') throw new InvalidArgumentException('الاسم والبريد والجوال إلزامية.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('بريد إلكتروني غير صحيح.');
    if (!ksa_local($phoneReg)) throw new InvalidArgumentException('أدخل رقم سعودي بصيغة 05XXXXXXXX');
    if (user_find_by_email(strtolower($email))) throw new RuntimeException('هذا البريد مسجّل مسبقًا.');

    $uid = user_create($nameReg, strtolower($email), $phoneReg, $password ?: null);
    session_regenerate_id(true); // <-- منع هجمات تثبيت الجلسة
    $_SESSION['user'] = ['id'=>$uid,'email'=>strtolower($email),'name'=>$nameReg,'login_at'=>time()];
    header('Location: ' . app_href('index.php')); exit;
  }

  /* ---------- تسجيل الدخول: الخطوة 1 (إيميل) ---------- */
  if ($action === 'login_email') {
    $mode = 'login';
    if ($email==='') throw new InvalidArgumentException('أدخل بريدك الإلكتروني.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('البريد غير صحيح.');

    // Turnstile مرة واحدة (اختياري - يمكن تعطيله في البيئة المحلية)
    $skip_turnstile = getenv('SKIP_TURNSTILE') === 'true';
    if (!$skip_turnstile && $TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()){
      $tk = $_POST['cf-turnstile-response'] ?? '';
      if (!verify_turnstile_once($TURNSTILE_SECRET, $tk)) throw new RuntimeException('فشل التحقق البشري.');
      setcookie('cf_human','1',['expires'=>time()+60*60*24*30,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
    }

    $u = user_find_by_email(strtolower($email));
    if (!$u) throw new RuntimeException('بيانات غير صحيحة.');

    $_SESSION['login_flow'] = [
      'user_id'=>(int)$u['id'],
      'email'=>strtolower($u['email']),
      'has_password'=>!empty($u['password_hash']),
      'phone_e164'=>$u['phone_e164'],
      'phone_mask'=>mask_phone_last4_from_user($u)
    ];
    $flow = 'password'; // دائماً نطلب كلمة المرور أولاً
  }

  /* ---------- تسجيل الدخول: الخطوة 2 (كلمة المرور) ---------- */
  if ($action === 'login_password') {
    $mode = 'login';
    if (empty($_SESSION['login_flow'])) throw new RuntimeException('ابدأ بإدخال البريد أولًا.');
    $u   = $_SESSION['login_flow'];
    $row = user_find_by_email($u['email']);
    if (!$row) throw new RuntimeException('الحساب غير موجود.');
    if ($password==='') throw new InvalidArgumentException('أدخل كلمة المرور.');

    // --- التحقق من الحماية ضد التخمين ---
    $attempts_data = file_exists(LOGIN_ATTEMPT_FILE) ? json_decode(file_get_contents(LOGIN_ATTEMPT_FILE), true) : [];
    $user_email_key = str_replace(['.', '@'], '_', $u['email']);

    if (isset($attempts_data[$user_email_key])) {
        $last_attempt_time = $attempts_data[$user_email_key]['time'];
        $attempt_count = $attempts_data[$user_email_key]['count'];

        if ($attempt_count >= LOGIN_ATTEMPT_LIMIT && (time() - $last_attempt_time) < LOGIN_LOCKOUT_PERIOD) {
            $wait_time = ceil((LOGIN_LOCKOUT_PERIOD - (time() - $last_attempt_time)) / 60);
            throw new RuntimeException("تم حظر الحساب مؤقتًا لكثرة المحاولات الفاشلة. يرجى المحاولة مرة أخرى بعد {$wait_time} دقيقة.");
        }
    }

    if (empty($row['password_hash'])) {
      // تعيين كلمة مرور لأول مرة
      if (mb_strlen($password) < 6) throw new InvalidArgumentException('كلمة المرور يجب ألا تقل عن 6 أحرف.');
      user_set_password((int)$row['id'], $password);
    } else {
      if (!password_verify($password, $row['password_hash'])) {
        // --- تسجيل محاولة فاشلة ---
        $attempts_data = file_exists(LOGIN_ATTEMPT_FILE) ? json_decode(file_get_contents(LOGIN_ATTEMPT_FILE), true) : [];
        $user_email_key = str_replace(['.', '@'], '_', $u['email']);

        if (!isset($attempts_data[$user_email_key]) || (time() - $attempts_data[$user_email_key]['time']) > LOGIN_LOCKOUT_PERIOD) {
            $attempts_data[$user_email_key] = ['count' => 1, 'time' => time()];
        } else {
            $attempts_data[$user_email_key]['count']++;
        }
        file_put_contents(LOGIN_ATTEMPT_FILE, json_encode($attempts_data, JSON_PRETTY_PRINT));

        throw new RuntimeException('بيانات غير صحيحة.');
      }
    }

    // إرسال OTP
    $resp = authentica_send_otp(phone: $u['phone_e164'], method: 'sms');
    $_SESSION['login_flow']['otp_ref']   = $resp['reference'] ?? null;
    $_SESSION['login_flow']['otp_start'] = time();
    $flow = 'otp';
    $info = 'تم إرسال رمز التحقق.';
  }

  /* ---------- تسجيل الدخول: الخطوة 3 (OTP) ---------- */
  if ($action === 'login_otp') {
    $mode = 'login';
    if (empty($_SESSION['login_flow'])) throw new RuntimeException('انتهت الجلسة، أعد المحاولة.');
    if ($otp==='' || !preg_match('/^\d{4,8}$/', $otp)) throw new InvalidArgumentException('الرمز غير صحيح.');
    $u = $_SESSION['login_flow'];

    authentica_verify_otp(otp: $otp, phone: $u['phone_e164']);

    // --- مسح سجل المحاولات الفاشلة عند النجاح ---
    $attempts_data = file_exists(LOGIN_ATTEMPT_FILE) ? json_decode(file_get_contents(LOGIN_ATTEMPT_FILE), true) : [];
    $user_email_key = str_replace(['.', '@'], '_', $u['email']);
    if (isset($attempts_data[$user_email_key])) {
        unset($attempts_data[$user_email_key]);
        file_put_contents(LOGIN_ATTEMPT_FILE, json_encode($attempts_data, JSON_PRETTY_PRINT));
    }

    session_regenerate_id(true); // <-- منع هجمات تثبيت الجلسة
    $_SESSION['user'] = ['id'=>$u['user_id'], 'email'=>$u['email'], 'login_at'=>time()];
    unset($_SESSION['login_flow']);
    header('Location: ' . app_href('index.php')); exit;
  }

} catch (Throwable $e) {
  $err = $e->getMessage();
  if ($mode==='login' && ($action==='login_password' || $action==='login_otp')) {
    // ارجع لخطوة الإيميل عند الفشل الشديد
    if ($action==='login_password') $flow='email';
    if ($action==='login_otp')      $flow='password';
  }
}

require __DIR__ . '/partials/header.php';
?>

<main class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header text-center text-lg-start">
      <h1 class="fw-bold mb-2">مرحبًا بك في عزم الإنجاز</h1>
      <p class="lead mb-0">إدارة الطلبات، التوريد، وخدمات البيع بالأجل تبدأ من هنا.</p>
    </div>
    <div class="auth-body">
      <ul class="nav nav-tabs justify-content-center mb-4">
        <li class="nav-item"><a class="nav-link <?= $mode==='login' ? 'active' : '' ?>" href="?mode=login">تسجيل الدخول</a></li>
        <li class="nav-item"><a class="nav-link <?= $mode==='register' ? 'active' : '' ?>" href="?mode=register">إنشاء حساب</a></li>
      </ul>

      <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
      <?php if ($info): ?><div class="alert alert-info"><?= e($info) ?></div><?php endif; ?>

      <?php if ($mode==='login'): ?>
        <?php
          $hasFlow  = isset($_SESSION['login_flow']);
          $hasForgotFlow = isset($_SESSION['forgot_password_flow']);

          $flowStep = $_GET['flow'] ?? $flow;

          if (!$hasFlow && !$hasForgotFlow) {
              if ($flowStep !== 'forgot_password') {
                  $flowStep = 'email';
              }
          }

          if ($action==='login_email' && empty($err))    $flowStep = 'password';
          if ($action==='login_password' && empty($err)) $flowStep = 'otp';
          if ($action==='forgot_password_request' && empty($err)) $flowStep = 'forgot_password_otp';
        ?>

        <?php if ($flowStep==='email' || (!$hasFlow && !$hasForgotFlow && $flowStep !== 'forgot_password')): ?>
          <div class="mb-4">
            <h2 class="h4 fw-bold mb-2">أدخل بريدك الإلكتروني</h2>
            <p class="text-muted-soft mb-0">سنرسل لك رابط أو رمز للتحقق من حسابك.</p>
          </div>
          <form method="post" class="animate-fade" novalidate>
            <input type="hidden" name="action" value="login_email">
            <div class="mb-3">
              <label class="form-label">البريد الإلكتروني</label>
              <input class="form-control" type="email" name="email" placeholder="name@example.com" required value="<?= e($email) ?>">
            </div>
            <?php if ($TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()): ?>
              <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
              <div class="cf-turnstile mb-3" data-sitekey="<?= e($TURNSTILE_SITEKEY) ?>" data-theme="auto"></div>
            <?php endif; ?>
            <div class="d-grid"><button class="btn btn-primary py-2">استمرار</button></div>
          </form>

        <?php elseif ($flowStep==='password'): ?>
          <div class="mb-4">
            <h2 class="h4 fw-bold mb-2">مرحبًا من جديد</h2>
            <p class="text-muted-soft mb-0">أدخل كلمة المرور المتصلة بالحساب.</p>
          </div>
          <form method="post" class="animate-fade">
            <input type="hidden" name="action" value="login_password">
            <div class="mb-3">
              <label class="form-label">البريد الإلكتروني</label>
              <input class="form-control" type="email" name="email" required value="<?= e($email ?: ($_SESSION['login_flow']['email'] ?? '')) ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">كلمة المرور</label>
              <div class="input-group">
                <input id="pwd" class="form-control" type="password" name="password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="const p=document.getElementById('pwd');p.type=p.type==='password'?'text':'password'">👁</button>
              </div>
            </div>
            <div class="d-flex justify-content-between small mb-3">
              <a href="?mode=login&flow=forgot_password" class="text-decoration-none">نسيت كلمة المرور؟</a>
            </div>
            <div class="d-grid"><button class="btn btn-primary py-2">دخول</button></div>
          </form>

        <?php elseif ($flowStep==='otp'): ?>
          <?php $mask = $_SESSION['login_flow']['phone_mask'] ?? '+9665******'; $start = $_SESSION['login_flow']['otp_start'] ?? time(); $left = max(0, 60 - (time()-$start)); ?>
          <div class="text-center mb-3 animate-fade">
            <h2 class="h4 mb-2">أدخل رمز التحقق</h2>
            <div class="text-muted-soft">تم إرسال الرمز إلى <strong><?= e($mask) ?></strong></div>
          </div>
          <form method="post" id="otpForm" class="animate-fade">
            <input type="hidden" name="action" value="login_otp">
            <input type="hidden" name="otp" id="otpVal">
            <div class="d-flex justify-content-center gap-2 my-3" id="otp-container">
              <?php for($i=0;$i<4;$i++): ?>
                <input class="form-control otp-input" maxlength="1" inputmode="numeric" pattern="\d" <?= $i === 0 ? 'autocomplete="one-time-code"' : '' ?>>
              <?php endfor; ?>
            </div>
            <div class="text-center mb-2 small">
              <a href="?logout=1" class="text-danger text-decoration-none fw-semibold">تسجيل الخروج</a>
              <span id="logoutTimer" class="text-danger"> ⏱ <?= sprintf('00:%02d',$left) ?></span>
            </div>
            <div class="d-grid"><button class="btn btn-primary py-2">تأكيد</button></div>
          </form>
          <script>
            const otpContainer = document.getElementById('otp-container');
            const boxes = [...otpContainer.querySelectorAll('.otp-input')];
            const form = document.getElementById('otpForm');
            const otpValInput = document.getElementById('otpVal');

            boxes.forEach((box, index) => {
                box.addEventListener('input', (e) => {
                    box.value = box.value.replace(/\D/g, '');
                    if (box.value.length === 1 && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }
                });

                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !box.value && index > 0) {
                        boxes[index - 1].focus();
                    }
                });
            });

            otpContainer.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!pastedData) return;
                for (let i = 0; i < pastedData.length && i < boxes.length; i++) {
                    boxes[i].value = pastedData[i];
                }
                const focusIndex = Math.min(pastedData.length, boxes.length - 1);
                boxes[focusIndex].focus();
            });

            form.addEventListener('submit', e => {
                otpValInput.value = boxes.map(b => b.value).join('');
            });

            let sec = <?= (int)$left ?>;
            const t = document.getElementById('logoutTimer');
            if (t) {
                const timer = setInterval(() => {
                    if (sec > 0) {
                        sec--;
                        t.textContent = ' ⏱ ' + '00:' + String(sec).padStart(2, '0');
                    } else {
                        clearInterval(timer);
                    }
                }, 1000);
            }
          </script>

        <?php elseif ($flowStep==='forgot_password'): ?>
          <div class="text-center mb-3 animate-fade">
            <h2 class="h4 mb-2">إعادة تعيين كلمة المرور</h2>
            <div class="text-muted-soft">أدخل رقم جوالك المسجل لإرسال رمز التحقق.</div>
          </div>
          <form method="post" class="animate-fade">
            <input type="hidden" name="action" value="forgot_password_request">
            <div class="mb-3">
              <label class="form-label">رقم الجوال</label>
              <input class="form-control" type="text" name="phone_reset" placeholder="05XXXXXXXX" required pattern="^05\d{8}$">
            </div>
            <div class="d-grid"><button class="btn btn-primary py-2">إرسال الرمز</button></div>
            <div class="text-center mt-3">
              <a href="?mode=login" class="text-decoration-none">العودة لتسجيل الدخول</a>
            </div>
          </form>

        <?php elseif ($flowStep==='forgot_password_otp'): ?>
          <?php $start = $_SESSION['forgot_password_flow']['otp_start'] ?? time(); $left = max(0, 60 - (time()-$start)); ?>
          <div class="text-center mb-3 animate-fade">
            <h2 class="h4 mb-2">إعادة تعيين كلمة المرور</h2>
            <div class="text-muted-soft">تم إرسال رمز التحقق إلى جوالك.</div>
          </div>
          <form method="post" id="otpForm" class="animate-fade">
            <input type="hidden" name="action" value="forgot_password_reset">
            <input type="hidden" name="otp" id="otpVal">
            <div class="d-flex justify-content-center gap-2 my-3" id="otp-container">
              <?php for($i=0;$i<4;$i++): ?>
                <input class="form-control otp-input" maxlength="1" inputmode="numeric" pattern="\d" <?= $i === 0 ? 'autocomplete="one-time-code"' : '' ?>>
              <?php endfor; ?>
            </div>
            <div class="mb-3">
              <label class="form-label">كلمة المرور الجديدة</label>
              <input class="form-control" type="password" name="new_password" required>
            </div>
            <div class="text-center mb-2 small">
              <span id="logoutTimer" class="text-danger"> ⏱ <?= sprintf('00:%02d',$left) ?></span>
            </div>
            <div class="d-grid"><button class="btn btn-primary py-2">تأكيد</button></div>
          </form>
          <script>
            const otpContainer2 = document.getElementById('otp-container');
            const boxes2 = [...otpContainer2.querySelectorAll('.otp-input')];
            const form2 = document.getElementById('otpForm');
            const otpValInput2 = document.getElementById('otpVal');

            boxes2.forEach((box, index) => {
                box.addEventListener('input', (e) => {
                    box.value = box.value.replace(/\D/g, '');
                    if (box.value.length === 1 && index < boxes2.length - 1) {
                        boxes2[index + 1].focus();
                    }
                });
            });

            otpContainer2.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!pastedData) return;
                for (let i = 0; i < pastedData.length && i < boxes2.length; i++) {
                    boxes2[i].value = pastedData[i];
                }
                const focusIndex = Math.min(pastedData.length, boxes2.length - 1);
                boxes2[focusIndex].focus();
            });

            form2.addEventListener('submit', e => {
                otpValInput2.value = boxes2.map(b => b.value).join('');
            });

            let sec2 = <?= (int)$left ?>;
            const t2 = document.getElementById('logoutTimer');
            if (t2) {
                const timer = setInterval(() => {
                    if (sec2 > 0) {
                        sec2--;
                        t2.textContent = ' ⏱ ' + '00:' + String(sec2).padStart(2, '0');
                    } else {
                        clearInterval(timer);
                    }
                }, 1000);
            }
          </script>
        <?php endif; ?>
      <?php else: ?>
        <div class="mb-4">
          <h2 class="h4 fw-bold mb-2">أنشئ حساب شركتك</h2>
          <p class="text-muted-soft mb-0">املأ البيانات التالية وسيتواصل معك فريقنا لتفعيل الخدمة.</p>
        </div>
        <form method="post" class="animate-fade" novalidate>
          <input type="hidden" name="action" value="register_submit">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">اسم الشركة</label>
              <input class="form-control" name="name" required value="<?= e($nameReg) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">البريد الإلكتروني</label>
              <input class="form-control" type="email" name="email" required value="<?= e($email) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">رقم الجوال (05XXXXXXXX)</label>
              <input class="form-control" name="phone" placeholder="05XXXXXXXX" pattern="^05\d{8}$" required value="<?= e($phoneReg) ?>">
            </div>
          </div>
          <?php if ($TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()): ?>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="cf-turnstile my-3" data-sitekey="<?= e($TURNSTILE_SITEKEY) ?>" data-theme="auto"></div>
          <?php endif; ?>
          <div class="d-grid mt-3"><button class="btn btn-primary py-2">تسجيل</button></div>
        </form>
      <?php endif; ?>

      <div class="border-top pt-3 mt-4 text-muted-soft small text-center">
        بحاجة للمساعدة؟ <a href="<?= e(app_href('support/index.php')) ?>" class="text-decoration-none">تواصل مع فريق الدعم</a>
      </div>
    </div>
  </div>
</main>

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>

<?php require __DIR__ . '/partials/footer.php'; ?>
