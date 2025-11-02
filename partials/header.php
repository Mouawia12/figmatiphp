<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
/** الهيدر الرسمي الموحد - مطابق لـ index.php */

// تحميل الملفات الأساسية
if (!isset($APP)) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/../inc/functions.php';
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

// التأكد من بدء الجلسة قبل الوصول إلى $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من وجود المستخدم في الجلسة قبل الاستخدام
$unread_count = 0;
if (!empty($_SESSION['user']) && function_exists('ensure_support_tables_exist')) {
    try {
        ensure_support_tables_exist();
    } catch (Throwable $e) {
        error_log("Error in ensure_support_tables_exist: " . $e->getMessage());
    }
}

if (!empty($_SESSION['user']) && function_exists('get_unread_support_messages_count')) {
    try {
        $unread_count = get_unread_support_messages_count();
    } catch (Throwable $e) {
        error_log("Error in get_unread_support_messages_count: " . $e->getMessage());
    }
}

$siteTitle = $siteTitle ?? ($APP->site_title ?? 'شركة عزم الإنجاز');
$modelName = $modelName ?? '';
if (!isset($siteDesc) || !is_string($siteDesc)) { $siteDesc = ''; }
$isAuth = $isAuth ?? (!empty($_SESSION['user']['id']));
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<script>window.APP_BASE_URL = '<?= e(rtrim(app_href(''), '/')) ?>';</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($siteTitle) ?><?= $modelName ? ' – ' . e($modelName) : '' ?></title>
<?php if($siteDesc): ?><meta name="description" content="<?= e($siteDesc) ?>"><?php endif; ?>
<?php 
// Output SEO meta tags if available (from partials/seo.php)
if (isset($meta_tags_output) && !empty($meta_tags_output)) {
    echo $meta_tags_output;
}
if (isset($json_ld_output) && !empty($json_ld_output)) {
    echo $json_ld_output;
}
?>

<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href('favicon-16x16.png')) ?>">

<!-- Bootstrap RTL -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">

<!-- Font Awesome للأيقونات -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2Pkf6CG5s8zUqDbgHgtE7SNY7VZqC2T9gFSkKf+DQ5BqZbP6Vx1E06R9uA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- النمط المخصص -->
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="app-bg">

<!-- Avatar Dropdown (إذا كان المستخدم مسجل دخول) -->
<?php if ($isAuth): ?>
<div style="position: fixed; top: 1rem; left: 1rem; z-index: 1050;">
  <div class="dropdown">
    <a class="nav-link dropdown-toggle position-relative" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?php 
        $avatarUrl = null;
        if (!empty($_SESSION['avatar_path'])) {
            $avatarUrl = $_SESSION['avatar_path'];
        } elseif (!empty($_SESSION['user']['id']) && function_exists('pdo_open')) {
            try {
                $db_avatar = pdo_open('users');
                $st_avatar = $db_avatar->prepare('SELECT avatar_path FROM users WHERE id = ?');
                $st_avatar->execute([(int)$_SESSION['user']['id']]);
                $user_avatar = $st_avatar->fetch(PDO::FETCH_ASSOC);
                if ($user_avatar && !empty($user_avatar['avatar_path'])) {
                    $avatarUrl = $user_avatar['avatar_path'];
                    $_SESSION['avatar_path'] = $avatarUrl;
                }
            } catch (Throwable $e) {
                error_log("Error fetching avatar: " . $e->getMessage());
            }
        }
        if ($avatarUrl && strpos($avatarUrl, 'http://') !== 0 && strpos($avatarUrl, 'https://') !== 0) {
            $avatarUrl = asset_href($avatarUrl);
        }
        ?>
        <img src="<?= e($avatarUrl ?: asset_href('assets/img/avatar-placeholder.png')) ?>" alt="avatar" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
        <?php if ($unread_count > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
            <span class="visually-hidden">New alerts</span>
        </span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item" href="<?= e(app_href('dashboard.php')) ?>"><i class="fas fa-user me-2 text-muted"></i>ملفي الشخصي</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= e(app_href('logout.php')) ?>"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
    </ul>
  </div>
</div>
<?php endif; ?>

<header class="shadow-sm bg-white sticky-top" id="top">
  <nav class="navbar container navbar-expand-lg py-3">
    <a class="navbar-brand d-flex align-items-center" href="<?= e(app_href('')) ?>" aria-label="<?= e($siteTitle) ?>">
      <img src="<?= e(asset_href('assets/img/logo.svg')) ?>" alt="شركة عزم الإنجاز" class="brand-logo" decoding="async" fetchpriority="high" width="250" height="70">
      <span class="visually-hidden"><?= e($siteTitle) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMenu" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php')) ?>">الرئيسية</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#about')) ?>">عن الخدمة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#faq')) ?>">الأسئلة الشائعة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#links')) ?>">روابط مهمة</a></li>
        <li class="nav-item me-lg-2"></li>
        <?php if(!$isAuth): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('login.php')) ?>">تسجيل الدخول</a></li>
          <li class="nav-item"><a class="btn btn-outline-secondary ms-lg-2 mt-2 mt-lg-0" href="<?= e(app_href('register.php')) ?>">إنشاء حساب</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('dashboard.php')) ?>">لوحة التحكم</a></li>
          <li class="nav-item"><a class="btn btn-outline-danger ms-lg-2 mt-2 mt-lg-0" href="<?= e(app_href('logout.php')) ?>">تسجيل الخروج</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>
