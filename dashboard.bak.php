<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('login.php')); exit; }
$user = e($_SESSION['user']['name'] ?? $_SESSION['user']['email']);
// جلب دور المستخدم لتحديد التحويل أو إخفاء أقسام
$role = 'user';
try {
  $dbu = pdo_open($config->db_users);
  $st  = $dbu->prepare('SELECT role FROM users WHERE id=?');
  $st->execute([ (int)$_SESSION['user']['id'] ]);
  $r   = $st->fetch(PDO::FETCH_ASSOC);
  if ($r && !empty($r['role'])) $role = (string)$r['role'];
} catch (Throwable $e) { /* تجاهل بهدوء */ }
// إن كان مديرًا، حوله مباشرة للوحة الإدارة
if ($role === 'admin') { header('Location: ' . app_href('admin/index.php')); exit; }
$siteTitle = $config->site_title ?? 'عزم الإنجاز';

/* قراءة الملفات من مجلد الرفع وفرزها بالأحدث */
$files = [];
$dir = $config->upload_dir ?? '';
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $f;
        if (is_file($path) && is_readable($path)) {
            $files[] = [
                'name' => $f,
                'size' => filesize($path) ?: 0,
                'mtime'=> filemtime($path) ?: 0,
            ];
        }
    }
    usort($files, fn($a,$b) => $b['mtime'] <=> $a['mtime']); // الأحدث أولًا
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($siteTitle) ?> – لوحة المستخدم</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href('favicon-16x16.png')) ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251007-4')) ?>">
</head>
<body class="app-bg">

<!-- الملفات المرفوعة -->
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content_between align-items-center mb-2">
              <h6 class="mb-0">الملفات المرفوعة</h6>
              <span class="badge text-bg-light"><?= count($files) ?></span>
            </div>
            <?php if (empty($files)): ?>
              <p class="text-muted mb-0">لا توجد ملفات بعد.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr><th>الاسم</th><th class="text-nowrap">الحجم</th><th class="text-nowrap">آخر تعديل</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($files as $f): ?>
                      <tr>
                        <td class="small"><?= e($f['name']) ?></td>
                        <td class="small text-nowrap">
                          <?php
                            $sz = (int)$f['size'];
                            echo $sz >= 1048576 ? number_format($sz/1048576,2) . ' م.ب' :
                                 ($sz >= 1024 ? number_format($sz/1024,1) . ' ك.ب' : $sz . ' ب');
                          ?>
                        </td>
                        <td class="small text-nowrap"><?= date('Y-m-d H:i', (int)$f['mtime']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div><!-- /col -->
    </div><!-- /row -->
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

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/ai-decorator-module.js')) ?>"></script>
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
</body>
</html>





