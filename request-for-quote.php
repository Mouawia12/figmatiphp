<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';

session_start();

$form_id = 4; // The ID of the 'Request for Quotation' form
$siteTitle = cfg()->site_title ?? 'عزم الإنجاز';
$page_title = 'طلب عرض سعر';

require __DIR__ . '/partials/header.php';
?>
<style>
/* Using styles from login.php for consistency */
.card-auth{border:0;box-shadow:0 10px 30px rgba(0,0,0,.06);border-radius:24px}
.fade-in{animation:fadeIn .35s ease-out both}
@keyframes fadeIn{from{opacity:0;transform:scale(.98)}to{opacity:1;transform:none}}
</style>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card card-auth fade-in">
        <div class="card-body p-4 p-md-5">

          <div class="text-center mb-4">
            <h2 class="h3 mb-2"><?= e($page_title) ?></h2>
            <p class="text-muted">الرجاء رفع ملف الإكسيل لطلب عرض السعر.</p>
          </div>

          <?php if (empty($_SESSION['user']['id'] ?? null)): ?>
            <div class="alert alert-warning text-center"><strong>يجب تسجيل الدخول أولاً.</strong><br><a href="<?= e(app_href('login.php')) ?>" class="alert-link">اضغط هنا لتسجيل الدخول</a>.</div>
          <?php else: ?>
            <?php if (!empty($_GET['ok'])): ?>
              <div class="alert alert-success">✅ تم استلام طلب عرض السعر بنجاح. سيتم مراجعته من قبل فريقنا.</div>
            <?php elseif (!empty($_GET['err'])): ?>
              <div class="alert alert-danger">❌ حدث خطأ: <?= e(urldecode($_GET['err'])) ?></div>
            <?php endif; ?>

            <form action="<?= e(app_href('send-quote-request.php')) ?>" method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_id" value="<?= (int)$form_id ?>">

              <div class="mb-3">
                <label for="quote_file" class="form-label">ملف عرض السعر (Excel)*</label>
                <input type="file" id="quote_file" name="quote_file" class="form-control form-control-lg" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <div class="form-text mt-2">الملفات المسموحة: XLS, XLSX. الحجم الأقصى: 10MB.</div>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">إرسال الطلب</button>
              </div>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>