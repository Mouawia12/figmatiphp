<?php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me = require_admin();
$page_title = $page_title ?? ($config->site_title ?? 'لوحة التحكم');
$siteTitle = ($config->site_title ?? 'عزم الإنجاز');
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> – <?= e($siteTitle) ?></title>
  <link rel="apple-touch-icon" sizes="76x76" href="<?= e(asset_href('argon/assets/img/apple-icon.png')) ?>">
  <link rel="icon" type="image/png" href="<?= e(asset_href('argon/assets/img/favicon.png')) ?>">
  <link href="<?= e(asset_href('argon/assets/css/nucleo-icons.css')) ?>" rel="stylesheet" />
  <link href="<?= e(asset_href('argon/assets/css/nucleo-svg.css')) ?>" rel="stylesheet" />
  <link href="<?= e(asset_href('argon/assets/css/argon-dashboard.min.css')) ?>" rel="stylesheet" />
  <style>html[dir=rtl] body{direction:rtl;text-align:right}</style>
</head>
<body class="g-sidenav-show bg-gray-100">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 fixed-start bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <a class="navbar-brand m-0" href="<?= e(app_href('admin/')) ?>">
        <span class="ms-1 font-weight-bold text-white"><?= e($siteTitle) ?></span>
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link text-white" href="index.php"><span class="nav-link-text ms-1">الرئيسية</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="requests.php"><span class="nav-link-text ms-1">الطلبات</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="forms.php"><span class="nav-link-text ms-1">النماذج</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="users.php"><span class="nav-link-text ms-1">المستخدمون</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="notifications.php"><span class="nav-link-text ms-1">الإشعارات</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="api_keys.php"><span class="nav-link-text ms-1">مفاتيح API</span></a></li>
        <li class="nav-item"><a class="nav-link text-white" href="export.php"><span class="nav-link-text ms-1">تصدير</span></a></li>
        <li class="nav-item mt-3"><a class="nav-link text-white" href="settings.php"><span class="nav-link-text ms-1">الإعدادات</span></a></li>
      </ul>
    </div>
  </aside>

  <main class="main-content position-relative border-radius-lg">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur">
      <div class="container-fluid py-1 px-3">
        <h6 class="font-weight-bolder mb-0"><?= e($page_title) ?></h6>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item d-flex align-items-center">
            <span class="me-2"><?= e($_SESSION['user']['name'] ?? $_SESSION['user']['email'] ?? '') ?></span>
            <a href="<?= e(app_href('logout.php')) ?>" class="btn btn-sm btn-outline-secondary">خروج</a>
          </li>
        </ul>
      </div>
    </nav>

    <div class="container-fluid py-4">
      <?php if (isset($content) && is_callable($content)) { $content(); } ?>
      <footer class="footer pt-3">
        <div class="container-fluid">
          <div class="text-muted text-center">© <?= date('Y') ?> <?= e($siteTitle) ?></div>
        </div>
      </footer>
    </div>
  </main>

  <script src="<?= e(asset_href('argon/assets/js/core/popper.min.js')) ?>"></script>
  <script src="<?= e(asset_href('argon/assets/js/core/bootstrap.min.js')) ?>"></script>
  <script src="<?= e(asset_href('argon/assets/js/plugins/perfect-scrollbar.min.js')) ?>"></script>
  <script src="<?= e(asset_href('argon/assets/js/plugins/smooth-scrollbar.min.js')) ?>"></script>
  <script src="<?= e(asset_href('argon/assets/js/plugins/chartjs.min.js')) ?>"></script>
  <script src="<?= e(asset_href('argon/assets/js/argon-dashboard.min.js')) ?>"></script>
</body>
</html>

