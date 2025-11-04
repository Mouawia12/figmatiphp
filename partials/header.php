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
$avatarUrl = null;
if ($isAuth) {
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
}
// Build configurable body classes while keeping the defaults.
$bodyClass = trim((string)($bodyClass ?? ''));
$bodyClassParts = array_filter(array_map('trim', preg_split('/\s+/', $bodyClass) ?: []));
$bodyClassParts = array_merge(['app-bg', 'has-floating-nav'], $bodyClassParts);
$bodyClassParts = array_values(array_unique(array_filter($bodyClassParts, static fn($c) => $c !== '')));
$bodyClassAttr = implode(' ', $bodyClassParts);
$isHomePage = in_array('home-page', $bodyClassParts, true);
$navClass = 'navbar navbar-expand-lg floating-nav' . ($isHomePage ? '' : ' scrolled') . ' navbar-light';

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<script>window.APP_BASE_URL = '<?= e(rtrim(app_href(''), '/')) ?>';</script>
<script>(function(){document.documentElement.classList.add('js');})();</script>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- خطوط Google -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- النمط المخصص -->
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="<?= e($bodyClassAttr) ?>">

<header id="top">
  <nav class="<?= e($navClass) ?>">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(app_href('')) ?>" aria-label="<?= e($siteTitle) ?>">
        <img src="<?= e(asset_href('design/Rectangle.png')) ?>" alt="شركة عزم الإنجاز" class="brand-logo" decoding="async" fetchpriority="high" width="220" height="80">
        <span class="visually-hidden"><?= e($siteTitle) ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="تبديل القائمة">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="navMenu" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-2 w-100 w-lg-auto nav-links">
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#top')) ?>">الرئيسية</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#services')) ?>">خدماتنا</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#about')) ?>">عن الشركة</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#contact')) ?>">تواصل معنا</a></li>
          <?php if ($isAuth): ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(app_href('dashboard.php')) ?>">لوحة التحكم</a></li>
          <?php endif; ?>
        </ul>
        <div class="nav-actions d-flex flex-column flex-lg-row flex-lg-row-reverse align-items-lg-center gap-2 w-100 w-lg-auto mt-3 mt-lg-0 ms-lg-3">
          <?php if(!$isAuth): ?>
            <a class="btn nav-action-primary d-inline-flex align-items-center gap-2" href="<?= e(app_href('form.php')) ?>">
              <i class="fas fa-file-signature small opacity-75"></i>
              <span>اطلب عرض سعر</span>
            </a>
          <?php else: ?>
            <div class="dropdown nav-account">
              <a class="nav-action-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="navAccountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?= e($avatarUrl ?: asset_href('assets/img/avatar-placeholder.png')) ?>" alt="صورة الحساب" class="nav-avatar">
                <span>حسابي</span>
                <?php if ($unread_count > 0): ?>
                  <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis small"><?= (int)$unread_count ?></span>
                <?php endif; ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-start text-end shadow" aria-labelledby="navAccountDropdown">
                <li><a class="dropdown-item" href="<?= e(app_href('dashboard.php')) ?>"><i class="fas fa-user ms-2 text-muted"></i>ملفي الشخصي</a></li>
                <li><a class="dropdown-item" href="<?= e(app_href('support/index.php')) ?>"><i class="fas fa-headset ms-2 text-muted"></i>دعم العملاء</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= e(app_href('logout.php')) ?>"><i class="fas fa-sign-out-alt ms-2"></i>تسجيل الخروج</a></li>
              </ul>
            </div>
            <a class="btn nav-action-primary d-inline-flex align-items-center gap-2" href="<?= e(app_href('form.php')) ?>">
              <i class="fas fa-file-signature small opacity-75"></i>
              <span>اطلب عرض سعر</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>
<div class="floating-nav-spacer" aria-hidden="true"></div>
