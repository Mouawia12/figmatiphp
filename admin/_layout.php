<?php
// التأكد من تحميل functions.php (يجب أن يكون محملاً من الملفات التي تستدعي _layout.php)
if (!function_exists('asset_href')) {
    require_once __DIR__ . '/../inc/functions.php';
}

if (function_exists('ensure_support_tables_exist')) {
    ensure_support_tables_exist();
}
$unread_count = get_unread_support_messages_count();
$siteTitle = $config->site_title ?? 'لوحة التحكم';
$page_title = $page_title ?? $siteTitle;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?php if (!headers_sent()) { header('Content-Type: text/html; charset=utf-8'); } ?>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= e($page_title) ?> | <?= e($siteTitle) ?></title>
  <?php
    // استخدام دالة asset_href() الموجودة في functions.php
    // هذه الدالة تحسب المسار بشكل صحيح حتى لو كنا في مجلد admin
    $assets_base = asset_href('admin/assets');
    $base_assets = asset_href('assets');
  ?>
  <link rel="apple-touch-icon" sizes="76x76" href="<?= e($base_assets . '/img/apple-icon.png') ?>">
  <link rel="icon" type="image/png" href="<?= e($base_assets . '/img/favicon.png') ?>">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2Pkf6CG5s8zUqDbgHgtE7SNY7VZqC2T9gFSkKf+DQ5BqZbP6Vx1E06R9uA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="<?= e($assets_base . '/css/nucleo-icons.css') ?>" rel="stylesheet" />
  <link href="<?= e($assets_base . '/css/nucleo-svg.css') ?>" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link id="pagestyle" href="<?= e($assets_base . '/css/argon-dashboard.min.css') ?>" rel="stylesheet" />
  <style>
    .avatar{font-size:1.1rem;font-weight:bold;}
    .message-bubble{word-wrap:break-word;}
    .message-bubble.bg-light{border:1px solid #e9ecef;}
    .message-bubble.bg-white{border:1px solid #dee2e6;}
    
    /* توحيد الأيقونات باللون الرمادي/الرصاصي */
    .sidenav .nav-link i,
    .sidenav .navbar-nav .nav-link i {
      color: #6c757d !important;
      opacity: 0.8;
      transition: all 0.3s ease;
    }
    
    .sidenav .nav-link:hover i,
    .sidenav .navbar-nav .nav-link:hover i,
    .sidenav .nav-link.active i,
    .sidenav .navbar-nav .nav-link.active i {
      color: #495057 !important;
      opacity: 1;
    }
    
    /* إزالة أي ألوان نصية أخرى من الأيقونات */
    .sidenav .nav-link i.text-primary,
    .sidenav .nav-link i.text-info,
    .sidenav .nav-link i.text-success,
    .sidenav .nav-link i.text-warning,
    .sidenav .nav-link i.text-danger,
    .sidenav .nav-link i.text-secondary,
    .sidenav .nav-link i.text-dark {
      color: #6c757d !important;
    }
  </style>
  <script>window.APP_BASE_URL = '<?= e(rtrim(app_href(''), '/')) ?>';</script>
</head>
<body class="g-sidenav-show rtl bg-gray-100">
<div style="position: fixed; top: 1rem; left: 1rem; z-index: 1050;">
  <div class="dropdown">
    <a href="#" class="nav-link text-white p-0 position-relative" id="navbarUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="avatar avatar-sm bg-gradient-secondary rounded-circle d-inline-flex align-items-center justify-content-center">
        <i class="ni ni-single-02 text-white"></i>
      </span>
      <?php if ($unread_count > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
            <span class="visually-hidden">New alerts</span>
        </span>
      <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-start px-2 py-3" aria-labelledby="navbarUserMenu">
<?php $role = $_SESSION['user']['role'] ?? 'user'; if ($role === 'admin' || $role === 'employee'): ?>


        <li><a class="dropdown-item border-radius-md" href="index.php">عودة للوحة</a></li>
        <li><a class="dropdown-item border-radius-md" href="profile.php">الملف الشخصي</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item border-radius-md text-danger" href="<?= e(app_href('logout.php')) ?>">تسجيل الخروج</a></li>
      <?php else: ?>
        <li><a class="dropdown-item border-radius-md" href="<?= e(app_href('admin/')) ?>">لوحة التحكم</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item border-radius-md text-danger" href="<?= e(app_href('logout.php')) ?>">تسجيل الخروج</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>
  <div class="min-height-300 bg-dark position-absolute w-100"></div>
  <aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-end me-4 rotate-caret" id="sidenav-main">
    <div class="sidenav-header">
      <a class="navbar-brand m-0" href="<?= e(app_href('admin/')) ?>">
        <span class="me-1 font-weight-bold"><?= e($siteTitle) ?></span>
      </a>
      <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute start-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse px-0 w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt fa-lg ms-2"></i><span class="nav-link-text me-1">لوحة التحكم</span></a></li>
        <li class="nav-item"><a class="nav-link" href="requests.php"><i class="fas fa-list-alt fa-lg ms-2"></i><span class="nav-link-text me-1">الطلبات</span></a></li>
        <li class="nav-item"><a class="nav-link" href="quote_requests.php"><i class="fas fa-tags fa-lg ms-2"></i><span class="nav-link-text me-1">عروض الأسعار</span></a></li>
        <li class="nav-item"><a class="nav-link" href="design_requests.php"><i class="fas fa-palette fa-lg ms-2"></i><span class="nav-link-text me-1">طلبات التصميم</span></a></li>
        <li class="nav-item"><a class="nav-link" href="forms.php"><i class="fas fa-file-alt fa-lg ms-2"></i><span class="nav-link-text me-1">النماذج</span></a></li>
        
        <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users fa-lg ms-2"></i><span class="nav-link-text me-1">العملاء</span></a></li>
        <li class="nav-item"><a class="nav-link" href="support_tickets.php"><i class="fas fa-life-ring fa-lg ms-2"></i><span class="nav-link-text me-1">تذاكر الدعم</span> <?php if ($unread_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?= $unread_count ?></span><?php endif; ?></a></li>
        <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell fa-lg ms-2"></i><span class="nav-link-text me-1">الإشعارات</span></a></li>
        <li class="nav-item"><a class="nav-link" href="api_keys.php"><i class="fas fa-key fa-lg ms-2"></i><span class="nav-link-text me-1">مفاتيح API</span></a></li>
        <li class="nav-item mt-3 px-3 text-xs text-uppercase text-secondary">عزم – الذكاء</li>
        <li class="nav-item"><a class="nav-link" href="chat.php"><i class="fas fa-robot fa-lg ms-2"></i><span class="nav-link-text me-1">عزم – المساعد</span></a></li>
        <li class="nav-item"><a class="nav-link" href="azam_knowledge.php"><i class="fas fa-database fa-lg ms-2"></i><span class="nav-link-text me-1">مصادر المعرفة</span></a></li>
        <li class="nav-item"><a class="nav-link" href="azam_queue.php"><i class="fas fa-inbox fa-lg ms-2"></i><span class="nav-link-text me-1">قائمة الانتظار</span></a></li>
        <li class="nav-item"><a class="nav-link" href="azam_import_export.php"><i class="fas fa-exchange-alt fa-lg ms-2"></i><span class="nav-link-text me-1">تصدير/استيراد</span></a></li>
        <li class="nav-item"><a class="nav-link" href="azam_settings.php"><i class="fas fa-sliders-h fa-lg ms-2"></i><span class="nav-link-text me-1">إعدادات عزم</span></a></li>
        <li class="nav-item"><a class="nav-link" href="azam_analytics.php"><i class="fas fa-chart-bar fa-lg ms-2"></i><span class="nav-link-text me-1">إحصاءات عزم</span></a></li>
        <li class="nav-item mt-3"><a class="nav-link" href="settings.php"><i class="fas fa-cog fa-lg ms-2"></i><span class="nav-link-text me-1">الإعدادات</span></a></li>
      </ul>
    </div>
  </aside>

  <main class="main-content position-relative border-radius-lg overflow-hidden">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-1 px-3 position-relative">

        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0">
            <li class="breadcrumb-item text-sm ps-2"><a class="opacity-5 text-white" href="<?= e(app_href('admin/')) ?>">لوحة التحكم</a></li>
            <li class="breadcrumb-item text-sm text-white active" aria-current="page"><?= e($page_title) ?></li>
          </ol>
          <h6 class="font-weight-bolder text-white mb-0"><?= e($page_title) ?></h6>
        </nav>
        <div class="collapse navbar-collapse me-md-0 me-sm-4" id="navbar">
          <ul class="navbar-nav ms-md-auto justify-content-end">
            <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users text-white text-sm opacity-10 ms-2"></i><span class="nav-link-text me-1">العملاء</span></a></li>
          </ul>


        </div>
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

  <script src="<?= e($assets_base . '/js/core/popper.min.js') ?>"></script>
  <script src="<?= e($assets_base . '/js/core/bootstrap.min.js') ?>"></script>
  <script src="<?= e($assets_base . '/js/plugins/perfect-scrollbar.min.js') ?>"></script>
  <script src="<?= e($assets_base . '/js/plugins/smooth-scrollbar.min.js') ?>"></script>
  <script src="<?= e($assets_base . '/js/argon-dashboard.min.js?v=2.1.0') ?>"></script>
  <script>
    // Normalize sidebar labels in case of encoding issues
    (function(){
      var map = {
        'index.php':'لوحة التحكم',
        'requests.php':'الطلبات',
        'quote_requests.php':'عروض الأسعار',
        'design_requests.php':'طلبات التصميم',
        'forms.php':'النماذج',
        'users.php':'المستخدمون',
        'notifications.php':'الإشعارات',
        'api_keys.php':'مفاتيح API',
        'export.php':'تصدير',
        'settings.php':'الإعدادات',
        'customers.php':'العملاء',
        'ai-training.php':'تدريب الذكاء'
      };
      var root = document.getElementById('sidenav-main');
      if(!root) return;
      root.querySelectorAll('a.nav-link').forEach(function(a){
        try{
          var href = a.getAttribute('href')||'';
          var file = href.split('/').pop().split('?')[0];
          var label = map[file];
          var span = a.querySelector('.nav-link-text');
          if (label && span) span.textContent = label;
        }catch(e){}
      });
    })();
  </script>
  <script>
    // Preview Modal utility
    (function(){
      window.previewFile = function(url, name){
        try{
          var modal = document.getElementById('previewModal');
          if(!modal){ console.warn('preview modal missing'); return; }
          var titleEl = modal.querySelector('.modal-title');
          if(titleEl) titleEl.textContent = name || 'معاينة الملف';
          var iframe = modal.querySelector('#previewFrame');
          var img    = modal.querySelector('#previewImage');
          var ext = (name||'').split('.').pop().toLowerCase();
          var isImg = ['jpg','jpeg','png','gif','webp','bmp','svg'].indexOf(ext) !== -1;
          if(isImg){
            if(img){ img.src = url; img.style.display='block'; }
            if(iframe){ iframe.src = ''; iframe.style.display='none'; }
          }else{
            if(iframe){ iframe.src = url; iframe.style.display='block'; }
            if(img){ img.src = ''; img.style.display='none'; }
          }
          var bsModal = new bootstrap.Modal(modal);
          bsModal.show();
        }catch(e){ console.error('previewFile failed', e); }
      };
    })();
  </script>

  <!-- Global Preview Modal -->
  <div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">معاينة الملف</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="min-height:70vh">
          <img id="previewImage" alt="preview" style="max-width:100%;height:auto;display:none"/>
          <iframe id="previewFrame" style="width:100%;height:70vh;border:0;display:none" allowfullscreen></iframe>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
