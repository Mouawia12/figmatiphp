<?php
// /crosing/admin/api_keys.php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me = require_admin();

$db = pdo_open($config->db_users);

$db->exec("
  CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    INDEX idx_api_keys_token (token)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $flash = ['type' => 'danger', 'msg' => 'خطأ في التحقق من الأمان.'];
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $label = trim($_POST['label'] ?? '');
            if ($label === '') {
                $flash = ['type' => 'danger', 'msg' => 'الرجاء كتابة اسم للمفتاح.'];
            } else {
                $token = 'sk-' . bin2hex(random_bytes(20));
                $db->prepare("INSERT INTO api_keys (label, token, is_active) VALUES (?, ?, 1)")->execute([$label, $token]);
                $flash = ['type' => 'success', 'msg' => 'تم إنشاء مفتاح API جديد بنجاح.'];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare("DELETE FROM api_keys WHERE id = ?")->execute([$id]);
                $flash = ['type' => 'success', 'msg' => 'تم حذف المفتاح.'];
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare("UPDATE api_keys SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
                $flash = ['type' => 'success', 'msg' => 'تم تغيير حالة المفتاح.'];
            }
        }
    }
}

$rows = $db->query("SELECT id, label, token, is_active, created_at, IFNULL(last_used_at, '—') AS last_used_at FROM api_keys ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'مفاتيح API';
$content = function() use ($rows, $flash) { ?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> text-white font-weight-bold" role="alert">
    <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Create New API Key Card -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">إنشاء مفتاح API جديد</h6>
            </div>
            <div class="card-body">
                <p class="text-sm">أنشئ مفتاحًا جديدًا لكل منصّة أو خدمة خارجية تحتاج للوصول إلى API الخاص بك. تعامل مع هذه المفاتيح ككلمات مرور.</p>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="label" class="form-label">اسم المنصّة / الجهة</label>
                                <input type="text" name="label" id="label" class="form-control" placeholder="مثال: تطبيق الجوال أو شريك تجاري" required>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="mb-3">
                                <button type="submit" class="btn bg-gradient-primary mb-0">إنشاء مفتاح جديد</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Current Keys Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">المفاتيح الحالية</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المنصّة</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">المفتاح</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">أُنشئ في</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">لا توجد مفاتيح API بعد.</td></tr>
                            <?php else: foreach ($rows as $r): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm"><?= e($r['label']) ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm" value="<?= e(substr($r['token'], 0, 8)) . '...' . e(substr($r['token'], -4)) ?>" readonly>
                                            <button class="btn btn-sm btn-outline-secondary mb-0 js-copy-btn" type="button" data-copy="<?= e($r['token']) ?>">نسخ</button>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-<?= (int)$r['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                            <?= (int)$r['is_active'] === 1 ? 'مفعّل' : 'موقوف' ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?= date('Y-m-d', strtotime($r['created_at'])) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex justify-content-end">
                                            <form method="post" class="d-inline me-2">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button type="submit" class="btn btn-link text-secondary font-weight-bold text-xs p-0" data-bs-toggle="tooltip" data-bs-title="<?= (int)$r['is_active'] === 1 ? 'إيقاف' : 'تفعيل' ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المفتاح نهائيًا؟');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger font-weight-bold text-xs p-0" data-bs-toggle="tooltip" data-bs-title="حذف">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle copy buttons
    document.querySelectorAll('.js-copy-btn').forEach(function(btn) {
        const originalText = btn.textContent;
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(btn.dataset.copy).then(function() {
                btn.textContent = '✅ نُسخ';
                setTimeout(function() {
                    btn.textContent = originalText;
                }, 2000);
            });
        });
    });
});
</script>

<?php };
include __DIR__ . '/_layout.php';
