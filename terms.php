<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();
$siteTitle = $config->site_title ?? 'المؤسسة';
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($siteTitle) ?> | الشروط والأحكام</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . "/partials/seo.php"; ?>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href('favicon-16x16.png')) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251007-4')) ?>">
</head>
<body class="app-bg">
<header class="shadow-sm bg-white sticky-top">
  <nav class="navbar container navbar-expand-lg py-3">
    <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('')) ?>"><?= e($siteTitle) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMenu" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#links')) ?>">روابط مهمة</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-lg-3 mt-2 mt-lg-0" href="<?= e(app_href('register.php')) ?>">إنشاء حساب</a></li>
      </ul>
    </div>
  </nav>
</header>
<section class="hero-section">
  <div class="container">
    <h1 class="display-6 fw-bold mb-1">الشروط والأحكام</h1>
    <p class="lead text-muted mb-0">يرجى مراجعة الشروط بعناية قبل استخدام الخدمات.</p>
  </div>
</section>
<main class="section-pad">
  <div class="container" style="max-width:900px">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h5 class="mb-3">مقدمة</h5>
        <ul class="small text-muted">
          <li>باستخدامك للخدمة فإنك توافق على هذه الشروط.</li>
          <li>قد نقوم بتحديث الشروط من وقت لآخر.</li>
          <li>تأكد من مراجعة السياسات الخاصة بالخصوصية والرسوم حيث تنطبق.</li>
          <li>في حال وجود تعارض، تسود الشروط العربية.</li>
          <li>استخدامك للموقع يعني قبولك الكامل.</li>
        </ul>
        <h5 class="mt-4 mb-2">مسؤوليات المستخدم</h5>
        <p class="small text-muted mb-0">يرجى إدخال بيانات صحيحة والتواصل معنا لأي استفسار قبل تقديم الطلبات.</p>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <a href="<?= e(app_href('register.php')) ?>" class="btn btn-primary">إنشاء حساب جديد</a>
      <a href="<?= e(app_href('')) ?>" class="btn btn-light">العودة للرئيسية</a>
    </div>
  </div>
</main>
<footer class="footer mt-auto pt-5 pb-4">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
      <span>© <?= date('Y') ?> <?= e($siteTitle) ?>. جميع الحقوق محفوظة</span>
      <a class="link-secondary" href="#">سياسة الخصوصية</a>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- مكوّن المساعد الذكي (اختياري) -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
</body>
</html>

