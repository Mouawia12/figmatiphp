<?php
declare(strict_types=1);
$APP = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/auth.php';
error_reporting(E_ALL); ini_set('display_errors','1');
session_start();

$TURNSTILE_SITEKEY = getenv('TURNSTILE_SITEKEY') ?: '';
$TURNSTILE_SECRET  = getenv('TURNSTILE_SECRET') ?: '';

$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pass  = (string)($_POST['password'] ?? '');
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

    if (user_find_by_email(strtolower($email))) throw new RuntimeException('هذا البريد مسجّل مسبقًا.');

    $uid = user_create($name, strtolower($email), $phone, $pass ?: null);
    $_SESSION['user'] = ['id'=>$uid,'email'=>strtolower($email),'name'=>$name];
    header('Location: ' . app_href('index.php')); exit;
  }
} catch (Throwable $e) { $err = $e->getMessage(); }

$siteTitle = "شركة عزم الإنجاز"; $modelName="إنشاء حساب";
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
          <h1 class="h4 mb-3 text-center">إنشاء حساب</h1>
          <?php if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
          <?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

          <form method="post" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">الاسم الكامل</label>
                <input class="form-control" name="name" required value="<?= e($name) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">البريد الإلكتروني</label>
                <input class="form-control" type="email" name="email" required value="<?= e($email) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">رقم الجوال (محلي سعودي)</label>
                <input class="form-control" name="phone" placeholder="05XXXXXXXX" pattern="^05\d{8}$" required value="<?= e($phone) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">كلمة المرور (اختياري)</label>
                <div class="input-group">
                  <input class="form-control" type="password" name="password" placeholder="اتركه فارغًا إن أردت OTP فقط">
                  <button class="btn btn-outline-secondary" type="button" onclick="this.previousElementSibling.type=this.previousElementSibling.type==='password'?'text':'password'">إظهار</button>
                </div>
              </div>
            </div>

            <?php if ($TURNSTILE_SITEKEY && $TURNSTILE_SECRET && need_turnstile()): ?>
              <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
              <div class="cf-turnstile my-3" data-sitekey="<?= e($TURNSTILE_SITEKEY) ?>" data-theme="auto"></div>
            <?php endif; ?>

            <div class="d-grid mt-3">
              <button class="btn btn-success" type="submit">تسجيل</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>

<?php require __DIR__ . '/partials/footer.php'; ?>
