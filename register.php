<?php
declare(strict_types=1);
$APP = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/auth.php';
error_reporting(E_ALL); ini_set('display_errors','1');
session_start();

$TURNSTILE_SITEKEY = getenv('TURNSTILE_SITEKEY') ?: '';
$TURNSTILE_SECRET  = getenv('TURNSTILE_SECRET') ?: '';

$name     = trim($_POST['name']  ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$username = trim($_POST['username'] ?? '');
$pass     = (string)($_POST['password'] ?? '');
$pass2    = (string)($_POST['password_confirm'] ?? '');
$msg   = ''; $err='';

try {
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()){
      $tk = $_POST['cf-turnstile-response'] ?? '';
      if (!verify_turnstile_once($TURNSTILE_SECRET, $tk)) throw new RuntimeException('فشل التحقق البشري، حاول مجددًا.');
      setcookie('cf_human','1',['expires'=>time()+60*60*24*30,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
    }
    if ($name==='' || $email==='' || $phone==='') throw new InvalidArgumentException('الاسم والإيميل والجوال إلزامية.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('بريد غير صحيح.');
    if (!ksa_local($phone)) throw new InvalidArgumentException('رقم سعودي بصيغة 05XXXXXXXX');
    if ($pass !== '' && $pass !== $pass2) throw new InvalidArgumentException('كلمتا المرور غير متطابقتين.');

    if (user_find_by_email(strtolower($email))) throw new RuntimeException('هذا البريد مسجّل مسبقًا.');

    $uid = user_create($name, strtolower($email), $phone, $pass ?: null);
    $_SESSION['user'] = ['id'=>$uid,'email'=>strtolower($email),'name'=>$name];
    header('Location: ' . app_href('index.php')); exit;
  }
} catch (Throwable $e) { $err = $e->getMessage(); }

$siteTitle = "شركة عزم الإنجاز"; $modelName="إنشاء حساب";
require __DIR__ . '/partials/header.php';
?>
<main class="register-page" id="main-content">
  <div class="register-shell container-xxl">
    <div class="register-layout">
      <div class="register-form-column">
        <div class="register-form-inner">
          <a class="register-logo" href="<?= e(app_href('')) ?>" aria-label="<?= e($siteTitle) ?>">
            <img src="<?= e(asset_href('design/Rectangle.png')) ?>" alt="<?= e($siteTitle) ?>" loading="lazy">
          </a>
          <div class="register-headline">
            <h1>إنشاء حساب جديد</h1>
            <p>ابدأ رحلتك معنا عبر إنشاء حساب للوصول إلى خدمات عزم الإنجاز الرقمية بسهولة.</p>
          </div>

          <?php if($err): ?><div class="alert alert-danger register-alert" role="alert"><?= e($err) ?></div><?php endif; ?>
          <?php if($msg): ?><div class="alert alert-success register-alert" role="alert"><?= e($msg) ?></div><?php endif; ?>

          <form class="register-form" method="post" novalidate>
            <div class="register-field">
              <label for="name" class="form-label">الاسم الكامل</label>
              <input id="name" class="form-control" name="name" required value="<?= e($name) ?>" autocomplete="name">
            </div>

            <div class="register-field">
              <label for="email" class="form-label">البريد الإلكتروني</label>
              <input id="email" class="form-control" type="email" name="email" required value="<?= e($email) ?>" autocomplete="email">
            </div>

            <div class="register-field">
              <label for="username" class="form-label">اسم المستخدم</label>
              <input id="username" class="form-control" name="username" value="<?= e($username) ?>" placeholder="اسم فريد لملفك الشخصي">
            </div>

            <div class="register-field">
              <label for="phone" class="form-label">رقم الجوال السعودي</label>
              <input id="phone" class="form-control" name="phone" placeholder="05XXXXXXXX" pattern="^05\d{8}$" required value="<?= e($phone) ?>" autocomplete="tel">
              <div class="form-text">سيُستخدم رقم الجوال للتحقق عبر OTP عند الحاجة.</div>
            </div>

            <div class="register-field password-field">
              <label for="password" class="form-label">كلمة المرور</label>
              <div class="password-input">
                <input id="password" class="form-control" type="password" name="password" placeholder="يمكنك تركه فارغًا للاعتماد على OTP" autocomplete="new-password">
                <button class="btn btn-link password-toggle" type="button" data-target="password" aria-label="إظهار كلمة المرور">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="register-field password-field">
              <label for="password_confirm" class="form-label">تأكيد كلمة المرور</label>
              <div class="password-input">
                <input id="password_confirm" class="form-control" type="password" name="password_confirm" placeholder="أعد إدخال كلمة المرور" autocomplete="new-password">
                <button class="btn btn-link password-toggle" type="button" data-target="password_confirm" aria-label="إظهار كلمة المرور">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>

            <?php if ($TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()): ?>
              <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
              <div class="cf-turnstile register-turnstile" data-sitekey="<?= e($TURNSTILE_SITEKEY) ?>" data-theme="auto"></div>
            <?php endif; ?>

            <button class="btn register-submit" type="submit">إنشاء الحساب</button>
          </form>

          <div class="register-footer">
            <p>لديك حساب بالفعل؟ <a href="<?= e(app_href('login.php')) ?>">سجّل الدخول الآن</a></p>
          </div>
        </div>
      </div>
      <div class="register-visual" aria-hidden="true">
        <div class="register-visual-pattern"></div>
        <div class="register-visual-card">
          <span class="register-badge">منصة سمارت كيوب</span>
          <h3>حلول رقمية متكاملة</h3>
          <p>إدارة مشاريعك، متابعة طلباتك، والتواصل مع فريق الدعم من مكان واحد.</p>
          <ul>
            <li><i class="fa-solid fa-circle-check"></i> لوحة تحكم فورية</li>
            <li><i class="fa-solid fa-circle-check"></i> تقارير ذكية لحظية</li>
            <li><i class="fa-solid fa-circle-check"></i> دعم فني على مدار الساعة</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
<script>
  document.querySelectorAll('.password-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = document.getElementById(btn.dataset.target);
      if (!target) return;
      var isHidden = target.type === 'password';
      target.type = isHidden ? 'text' : 'password';
      btn.querySelector('i').classList.toggle('fa-eye');
      btn.querySelector('i').classList.toggle('fa-eye-slash');
      btn.setAttribute('aria-label', isHidden ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
    });
  });

  var registerForm = document.querySelector('.register-form');
  if (registerForm) {
    var confirmInput = document.getElementById('password_confirm');
    if (confirmInput) {
      confirmInput.addEventListener('input', function(){
        confirmInput.setCustomValidity('');
      });
    }
    registerForm.addEventListener('submit', function (event) {
      var passInput = document.getElementById('password');
      var confirmInput = document.getElementById('password_confirm');
      if (!passInput || !confirmInput) {
        return;
      }
      if (passInput.value !== '' && passInput.value !== confirmInput.value) {
        event.preventDefault();
        confirmInput.setCustomValidity('كلمتا المرور غير متطابقتين');
        confirmInput.reportValidity();
        confirmInput.focus();
        confirmInput.select();
      } else {
        confirmInput.setCustomValidity('');
      }
    });
  }
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
