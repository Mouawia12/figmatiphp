<?php
// send.php
require __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/email.php'; // تحميل دالة send_email()
$config = cfg();
session_start();

// ---------- تحميل تعريف النموذج ----------
// عند الإرسال نقرأ form_id من POST، وإلا نسمح بالمعاينة عبر GET
$form_id = (int)(
    $_SERVER['REQUEST_METHOD'] === 'POST'
        ? ($_POST['form_id'] ?? 0)
        : ($_GET['form_id']  ?? 0)
);
$dbf = pdo_open($config->db_forms);
if (($config->db_driver ?? 'sqlite') === 'mysql') {
    $charset = $config->db_charset ?? 'utf8mb4';
    $dbf->exec("CREATE TABLE IF NOT EXISTS forms (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255),
      fields LONGTEXT,
      created_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset}");
} else {
    $dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
}

$form  = null;
$parts = []; // [{label,name,type,opts,required}]
if ($form_id > 0) {
    $st = $dbf->prepare("SELECT * FROM forms WHERE id=?");
    $st->execute([$form_id]);
    $form = $st->fetch();
    if ($form) {
        $lines = array_filter(array_map('trim', explode("\n", (string)$form['fields'])));
        foreach ($lines as $ln) {
            // الصيغة: label:name:type[:options]
            $a = array_map('trim', explode(':', $ln, 4));
            $label = $a[0] ?? 'حقل';
            $name  = $a[1] ?? 'field';
            $type  = strtolower($a[2] ?? 'text');
            $opts  = $a[3] ?? '';
            $required = str_contains($label, '*');
            $parts[] = compact('label','name','type','opts','required');
        }
    }
}

// ---------- استقبال الإرسال ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من تسجيل الدخول
    if (empty($_SESSION['user']['id'] ?? null)) {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'message' => 'يجب تسجيل الدخول أولاً لرفع طلب',
            'redirect' => app_href('login.php')
        ]));
    }
    
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die('CSRF token invalid');
    }

    if (function_exists('ensure_requests_schema')) ensure_requests_schema();
    $dbr = pdo_open($config->db_requests);

    $data  = [];
    $files = [];
    $errors = [];

    foreach ($parts as $p) {
        $name = $p['name'];
        $type = $p['type'];
        $label= $p['label'];
        $required = $p['required'];

        if ($type === 'file') {
            $has = isset($_FILES[$name]) && !empty($_FILES[$name]['name']);
            if ($required && !$has) {
                $errors[] = "الرجاء رفع: " . str_replace('*','',$label);
                continue;
            }
            if ($has) {
                try {
                    // حدّ أقصى 500MB للملفات
                    $saved = handle_upload_limit($_FILES[$name], 500 * 1024 * 1024, ['pdf','jpg','jpeg','png']);
                    $orig = basename((string)($_FILES[$name]['name'] ?? ''));
                    $files[$name] = ['saved'=>$saved,'orig'=>$orig];
                } catch (Throwable $e) {
                    $errors[] = str_replace('*','',$label) . ': ' . $e->getMessage();
                }
            }
        } else {
            $val = trim($_POST[$name] ?? '');
            if ($required && $val === '') {
                $errors[] = "الرجاء تعبئة: " . str_replace('*','',$label);
            }
            $data[$name] = $val;
        }
    }

    // تحقق من الموافقة على إرسال البيانات لمنصات تمويل
    $consent = isset($_POST['consent_finance']) ? (string)$_POST['consent_finance'] : '';
    if ($consent !== '1') {
        $errors[] = 'يجب الموافقة على إرسال بيانات النموذج لمنصات التمويل.';
    } else {
        $data['consent_finance'] = 'yes';
    }

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old']    = $_POST;
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit;
    }

    // قيم منطقية لعرض سريع في اللوحة
    $logical_name    = $data['company_name'] ?? ($data['rep_name'] ?? ($data['name'] ?? ''));
    $logical_email   = $data['rep_email'] ?? ($data['email'] ?? '');
    $logical_message = $data['purpose'] ?? ($data['message'] ?? '');

    // أول مرفق (اختياري للعرض السريع)
    $first_file = null;
    if ($files) {
        $first = reset($files);
        $first_file = is_array($first) ? ($first['saved'] ?? null) : $first;
    }

    // كود تتبع
    $track = gen_tracking_code();

    $user_id = (int)$_SESSION['user']['id'];

    // حفظ
    $payload = ['fields'=>$data, 'files'=>$files];
    if (($config->db_driver ?? 'sqlite') === 'mysql') {
        $st = $dbr->prepare('INSERT INTO requests
            (user_id,form_id,name,email,message,file,data_json,status,status_updated_at,tracking_code,created_at)
            VALUES (?,?,?,?,?,?,?, "pending", NOW(), ?, NOW())');
    } else {
        $st = $dbr->prepare('INSERT INTO requests
            (user_id,form_id,name,email,message,file,data_json,status,status_updated_at,tracking_code,created_at)
            VALUES (?,?,?,?,?,?,?, "pending", datetime("now"), ?, datetime("now"))');
    }
    $st->execute([
        $user_id,
        $form_id,
        $logical_name,
        $logical_email,
        $logical_message,
        $first_file,
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        $track
    ]);

    // بريد إشعار لصاحب الموقع (اختياري)
    try {
        $admin_email_sent = false;
        if (function_exists('send_email')) {
            // استخدام PHPMailer إذا كان متوفراً
            $admin_email_sent = send_email(
                $config->mail_to,
                "طلب جديد من {$logical_name}",
                "<p>تم استلام طلب جديد من <strong>{$logical_name}</strong></p><p>{$logical_message}</p>",
                'نظام عزم',
                $logical_email ?: $config->mail_to
            );
        } else {
            // استخدام mail() كبديل
            $admin_email_sent = @mail(
                $config->mail_to,
                "طلب جديد من {$logical_name}",
                "تم استلام طلب جديد من {$logical_name}\n\n{$logical_message}",
                safe_mail_headers($logical_email ?: $config->mail_to)
            );
        }
        if (!$admin_email_sent) {
            // تسجيل تفصيلي في mail_errors.log
            if (function_exists('log_email_attempt')) {
                log_email_attempt(
                    $config->mail_to,
                    "طلب جديد من {$logical_name}",
                    false,
                    'new_request_admin',
                    'send_email() returned false',
                    ['request_name' => $logical_name, 'tracking_code' => $track]
                );
            } else {
                error_log("[" . date("Y-m-d H:i:s") . "] Failed to send admin email notification for request from {$logical_name}\n", 3, __DIR__ . '/mail_errors.log');
            }
        }
    } catch (Throwable $e) {
        // تسجيل تفصيلي في mail_errors.log
        if (function_exists('log_email_attempt')) {
            log_email_attempt(
                $config->mail_to,
                "طلب جديد من {$logical_name}",
                false,
                'new_request_admin_exception',
                $e->getMessage(),
                ['request_name' => $logical_name, 'tracking_code' => $track, 'exception_class' => get_class($e)]
            );
        } else {
            error_log("[" . date("Y-m-d H:i:s") . "] Email exception for admin notification: " . $e->getMessage() . "\n", 3, __DIR__ . '/mail_errors.log');
        }
    }

    // رابط التتبّع يبنى مرة واحدة
    // استخدام app_href() بدلاً من asset_href() لصفحات التطبيق
    $trackPath = ltrim(app_href('track.php'), '/');
    $link = public_url($trackPath) . '?code=' . urlencode($track);

    // بريد تأكيد للعميل مع رابط التتبّع (اختياري إذا كان البريد موجود)
    if ($logical_email !== '') {
        try {
            $email_subject = 'تأكيد استلام الطلب';
            $email_body = "
                <p>مرحبًا {$logical_name}</p>
                <p>تم استلام طلبك وهو الآن: <strong>قيد الانتظار</strong>.</p>
                <p>يمكنك متابعة الحالة عبر الرابط التالي:</p>
                <p><a href=\"{$link}\">{$link}</a></p>
                <p>شكرًا لك.</p>
            ";
            $plain_msg = "مرحبًا {$logical_name}\n\n"
                 . "تم استلام طلبك وهو الآن: قيد الانتظار.\n"
                 . "يمكنك متابعة الحالة عبر الرابط:\n{$link}\n\n"
                 . "شكرًا لك.";
            
            $email_sent = false;
            if (function_exists('send_email')) {
                // استخدام PHPMailer إذا كان متوفراً
                $email_sent = send_email(
                    $logical_email,
                    $email_subject,
                    $email_body,
                    'شركة عزم الإنجاز',
                    $config->mail_to
                );
            } else {
                // استخدام mail() كبديل
                $email_sent = @mail(
                    $logical_email,
                    $email_subject,
                    $plain_msg,
                    safe_mail_headers($config->mail_to)
                );
            }
            
            if (!$email_sent) {
                // تسجيل تفصيلي في mail_errors.log
                if (function_exists('log_email_attempt')) {
                    log_email_attempt(
                        $logical_email,
                        $email_subject,
                        false,
                        'new_request_customer',
                        'send_email() or mail() returned false',
                        ['customer_name' => $logical_name, 'tracking_code' => $track]
                    );
                } else {
                    error_log("[" . date("Y-m-d H:i:s") . "] Failed to send confirmation email to {$logical_email} for tracking code {$track}\n", 3, __DIR__ . '/mail_errors.log');
                }
            }
        } catch (Throwable $e) {
            // تسجيل تفصيلي في mail_errors.log
            if (function_exists('log_email_attempt')) {
                log_email_attempt(
                    $logical_email,
                    $email_subject ?? 'تأكيد استلام الطلب',
                    false,
                    'new_request_customer_exception',
                    $e->getMessage(),
                    ['customer_name' => $logical_name, 'tracking_code' => $track, 'exception_class' => get_class($e)]
                );
            } else {
                error_log("[" . date("Y-m-d H:i:s") . "] Email exception for confirmation: " . $e->getMessage() . "\n", 3, __DIR__ . '/mail_errors.log');
            }
        }
    }

    // إرسال رسالة SMS دائمًا إن وُجد رقم هاتف في البيانات
    $phone = $data['phone'] ?? $data['mobile'] ?? $data['tel'] ?? null;
    if (!$phone && function_exists('ksa_local')) {
        // محاولة استخراج أي قيمة تبدو كرقم سعودي من الحقول
        foreach ($data as $v) { if (is_string($v) && ksa_local($v)) { $phone = $v; break; } }
    }
    if ($phone) {
        try {
            $sms_message  = "تم استلام طلبك. رقم التتبع: {$track}.\n";
            $sms_message .= "تتبع الطلب: {$link}\n";
            $sms_message .= "الحالة الحالية: قيد الانتظار.";
            if (function_exists('authentica_send_sms')) {
                $sms_result = authentica_send_sms(
                    $phone, 
                    $sms_message,
                    'form_submission',
                    [
                        'form_id' => $form_id,
                        'tracking_code' => $track,
                        'user_id' => $user_id,
                        'logical_name' => $logical_name,
                        'logical_email' => $logical_email,
                        'link' => $link,
                    ]
                );
                if (!($sms_result['success'] ?? false)) {
                    error_log("فشل إرسال رسالة SMS للطلب #$track: " . json_encode($sms_result, JSON_UNESCAPED_UNICODE));
                }
            }
        } catch (Throwable $e) {
            error_log("خطأ في إرسال رسالة SMS للطلب #$track: " . $e->getMessage());
            // تسجيل في ملف sms_errors.log أيضاً
            @file_put_contents(__DIR__ . '/sms_errors.log', 
                "[" . date('Y-m-d H:i:s') . "] EXCEPTION in send.php: " . $e->getMessage() . " | Tracking Code: {$track} | Phone: {$phone}\n", 
                FILE_APPEND | LOCK_EX
            );
        }
    } else {
        // تسجيل عدم وجود رقم هاتف
        @file_put_contents(__DIR__ . '/sms_errors.log', 
            "[" . date('Y-m-d H:i:s') . "] WARNING: No phone number found in form submission | Form ID: {$form_id} | Tracking Code: {$track} | User: {$logical_name}\n", 
            FILE_APPEND | LOCK_EX
        );
    }

    // إثبات نجاح + تمرير كود التتبّع لعرضه في الصفحة
    $_SESSION['form_success'] = true;
    $_SESSION['track_code']   = $track;
    // العودة إلى صفحة النموذج العامة لعرض رسالة النجاح (مع تمرير form_id) بتوافق مع مجلد التثبيت
    $redir = app_href('form.php') . '?ok=1&form_id=' . (int)$form_id;
    header('Location: ' . $redir);
    exit;
}

// قيم قديمة عند أخطاء
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$errors_list = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// ---------- واجهة ----------
$siteTitle = $config->site_title ?? 'عزم الإنجاز';
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($siteTitle) ?> – <?= e($form['title'] ?? 'نموذج') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href('favicon-16x16.png')) ?>">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251007-4')) ?>">
<style>.required-star{color:#dc2626;margin-inline-start:4px}</style>
</head>
<body class="app-bg">

<!-- الهيدر الموحّد (مطابق index.php) -->
<header class="shadow-sm bg-white sticky-top">
  <nav class="navbar container navbar-expand-lg py-3">
    <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('')) ?>"><?= e($siteTitle) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMenu" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#about')) ?>">عن الخدمة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#faq')) ?>">الأسئلة الشائعة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#links')) ?>">روابط مهمة</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-lg-3 mt-2 mt-lg-0" href="<?= e(app_href('track.php')) ?>">تتبّع الطلب</a></li>
      </ul>
    </div>
  </nav>
</header>

<!-- Hero -->
<section class="hero-section">
  <div class="container">
    <h1 class="display-6 fw-bold mb-1"><?= e($form['title'] ?? 'نموذج') ?></h1>
    <p class="lead text-muted mb-0">املأ الحقول التالية بدقّة ثم اضغط إرسال.</p>
  </div>
</section>

<main class="section-pad">
  <div class="container" style="max-width:960px">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <?php if ($errors_list): ?>
          <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0"><?php foreach($errors_list as $er) echo '<li>'.e($er).'</li>'; ?></ul>
          </div>
        <?php endif; ?>

        <?php if (!$parts): ?>
          <div class="alert alert-warning border-0 shadow-sm">لا توجد حقول معرفة لهذا النموذج.</div>
        <?php else: ?>
          <form method="post" enctype="multipart/form-data" novalidate class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <?php foreach ($parts as $p):
              $labelTxt = trim(str_replace('*','',$p['label']));
              $name = $p['name'];
              $type = $p['type'];
              $req  = $p['required'];
              $val  = $old[$name] ?? '';
            ?>
              <div class="col-12">
                <label class="form-label">
                  <?= e($labelTxt) ?> <?php if($req): ?><span class="required-star">*</span><?php endif; ?>
                </label>

                <?php if ($type === 'textarea'): ?>
                  <textarea name="<?= e($name) ?>" class="form-control" rows="4" <?= $req?'required':'' ?>><?= e($val) ?></textarea>

                <?php elseif ($type === 'select'):
                  // opts = "12=١٢ شهر|24=سنتين|..."
                  $opts = [];
                  foreach (explode('|', $p['opts']) as $opt) {
                    $opt = trim($opt); if ($opt==='') continue;
                    $kv = explode('=', $opt, 2);
                    $oval = trim($kv[0] ?? '');
                    $otxt = trim($kv[1] ?? $oval);
                    if ($oval!=='') $opts[] = [$oval,$otxt];
                  }
                ?>
                  <select name="<?= e($name) ?>" class="form-select" <?= $req?'required':'' ?>>
                    <option value="">— اختر —</option>
                    <?php foreach($opts as [$oval,$otxt]): ?>
                      <option value="<?= e($oval) ?>" <?= ($val !== '' && $val==$oval)?'selected':'' ?>><?= e($otxt) ?></option>
                    <?php endforeach; ?>
                  </select>

                <?php elseif ($type === 'file'): ?>
                  <input type="file" name="<?= e($name) ?>" class="form-control" accept=".pdf,.jpg,.jpeg,.png" <?= $req?'required':'' ?>>

                <?php else: // text/email/number/date/tel... ?>
                  <input type="<?= e($type) ?>" name="<?= e($name) ?>" class="form-control"
                        value="<?= e($val) ?>" <?= $req?'required':'' ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <a href="<?= e(app_href('')) ?>" class="btn btn-outline-secondary">إلغاء</a>
              <button class="btn btn-primary">إرسال</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <a href="<?= e(app_href('')) ?>" class="btn btn-light">العودة للصفحة الرئيسية</a>
      <a href="<?= e(app_href('form.php')) ?>" class="btn btn-outline-secondary">عرض النموذج</a>
    </div>
  </div>
</main>

<!-- الفوتر الموحّد -->
<footer class="footer mt-auto pt-5 pb-4">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
      <span>© <?= date('Y') ?> <?= e($siteTitle) ?>. جميع الحقوق محفوظة</span>
      <a class="link-secondary" href="#">الرجوع للأعلى</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_href('assets/dnd-upload.js?v=2')) ?>"></script>
</body>
</html>

