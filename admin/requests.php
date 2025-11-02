<?php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me = require_admin();

if (function_exists('ensure_requests_schema')) ensure_requests_schema();

$dbr = pdo_open($config->db_requests);
$dbf = pdo_open($config->db_forms);

// الفلاتر والبحث
$limit = isset($_GET['limit']) ? max(5, min(200, (int)$_GET['limit'])) : 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$form_id = 1; // هذا القسم مخصص لطلبات نموذج #1 فقط
$q = trim($_GET['q'] ?? '');

// معالجة الإجراءات
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) { 
        http_response_code(400); 
        die('CSRF'); 
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        try {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid <= 0) throw new RuntimeException('معرّف طلب غير صالح');

            $st = $dbr->prepare("SELECT id FROM requests WHERE id=?");
            $st->execute([$rid]);
            $req = $st->fetch();
            if (!$req) throw new RuntimeException('الطلب غير موجود');

            $del = $dbr->prepare("DELETE FROM requests WHERE id=?");
            $del->execute([$rid]);

            $notice = "✅ تم حذف الطلب #{$rid} بنجاح";
        } catch (Throwable $e) {
            $error = "❌ " . $e->getMessage();
        }
    }
    
    if ($action === 'send_api') {
        try {
            $rid = (int)($_POST['id'] ?? 0);
            $api_endpoint = trim($_POST['api_endpoint'] ?? '');
            
            if ($rid <= 0) throw new RuntimeException('معرّف طلب غير صالح');
            if (empty($api_endpoint)) throw new RuntimeException('رابط API مطلوب');

            $st = $dbr->prepare("SELECT * FROM requests WHERE id=?");
            $st->execute([$rid]);
            $req = $st->fetch();
            if (!$req) throw new RuntimeException('الطلب غير موجود');

            // إرسال الطلب عبر API
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $api_endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'id' => $req['id'],
                    'name' => $req['name'],
                    'email' => $req['email'],
                    'message' => $req['message'],
                    'created_at' => $req['created_at']
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                $notice = "✅ تم إرسال الطلب #{$rid} بنجاح إلى المنصة";
            } else {
                throw new RuntimeException("فشل الإرسال - الرمز: $http_code");
            }
        } catch (Throwable $e) {
            $error = "❌ " . $e->getMessage();
        }
    }
}

// جلب النماذج
$dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
$forms = $dbf->query("SELECT id, title FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
if (!$forms) $forms = [];

// بناء الاستعلام
$where = [];
$args = [];

// حصر النتائج على نموذج #1 فقط
$where[] = 'form_id = ?';
$args[] = $form_id;
if ($q !== '') { 
    $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)'; 
    array_push($args, "%$q%", "%$q%", "%$q%"); 
}

$sqlBase = " FROM requests";
if ($where) $sqlBase .= " WHERE " . implode(' AND ', $where);

// العدّ
$stc = $dbr->prepare("SELECT COUNT(*)" . $sqlBase);
$stc->execute($args);
$total = (int)$stc->fetchColumn();

$pages = max(1, (int)ceil($total / $limit));
$page = min($page, $pages);
$offset = ($page - 1) * $limit;

// الصفوف
$sql = "SELECT id, form_id, name, email, SUBSTRING(message, 1, 100) AS message, file, status, created_at"
     . $sqlBase . " ORDER BY id DESC LIMIT ? OFFSET ?";
$st = $dbr->prepare($sql);
$pos = 1;
foreach ($args as $v) { $st->bindValue($pos++, $v); }
$st->bindValue($pos++, $limit, PDO::PARAM_INT);
$st->bindValue($pos++, $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) $rows = [];

// جلب قائمة المنصات/الشركات
$platforms = [];
try {
    $platforms = $dbf->query("SELECT id, title FROM forms ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // تجاهل الخطأ
}

$page_title = 'الطلبات';
$content = function() use ($forms, $rows, $pages, $page, $limit, $form_id, $q, $total, $notice, $error, $platforms) { ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <div class="row align-items-center">
            <div class="col">
              <h6>الطلبات</h6>
            </div>
            <div class="col text-end">
              <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-white">
                <i class="fas fa-download"></i>&nbsp;&nbsp;تصدير
              </a>
            </div>
          </div>
        </div>

        <?php if($notice): ?>
          <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
            <span class="alert-text text-white"><?= e($notice) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if($error): ?>
          <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
            <span class="alert-text text-white"><?= e($error) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <!-- شريط الفلاتر -->
            <form method="get" class="px-4 py-3">
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <select class="form-control" name="form_id">
                    <option value="">كل النماذج</option>
                    <?php foreach($forms as $fid => $title): ?>
                      <option value="<?= (int)$fid ?>" <?= (!is_null($form_id) && $form_id == $fid) ? 'selected' : '' ?>>
                        <?= e($title) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="q" placeholder="ابحث بالاسم أو البريد..." value="<?= e($q) ?>">
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
              </div>
            </form>

            <!-- الجدول -->
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الاسم</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">البريد</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">الرسالة</th>
                  <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">التاريخ</th>
                  <th class="text-secondary opacity-7"></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                      لا توجد طلبات
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td>
                        <div class="d-flex px-2 py-1">
                          <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm"><?= e($row['name']) ?></h6>
                            <p class="text-xs text-secondary mb-0">#<?= (int)$row['id'] ?></p>
                          </div>
                        </div>
                      </td>
                      <td>
                        <p class="text-xs font-weight-bold mb-0"><?= e($row['email']) ?></p>
                        <p class="text-xs text-secondary mb-0">النموذج #<?= (int)$row['form_id'] ?></p>
                      </td>
                      <td>
                        <p class="text-xs text-secondary mb-0"><?= e(mb_substr($row['message'], 0, 50, 'UTF-8')) ?>...</p>
                      </td>
                      <td class="align-middle text-center">
                        <span class="text-secondary text-xs font-weight-bold"><?= date('d/m/Y', strtotime($row['created_at'])) ?></span>
                      </td>
                      <td class="align-middle">
                        <a href="request_view.php?id=<?= (int)$row['id'] ?>" class="text-secondary font-weight-bold text-xs" title="عرض">
                          عرض
                        </a>
                        <button class="text-info font-weight-bold text-xs ms-2" style="background:none;border:none;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#apiModal<?= (int)$row['id'] ?>" title="إرسال API">
                          📤 API
                        </button>
                        <form method="post" style="display:inline" onsubmit="return confirm('هل تريد حذف هذا الطلب؟')">
                          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                          <button type="submit" class="text-danger font-weight-bold text-xs ms-2" style="background:none;border:none;cursor:pointer;">
                            حذف
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Modals لإرسال API -->
        <?php foreach ($rows as $row): ?>
          <div class="modal fade" id="apiModal<?= (int)$row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">إرسال الطلب #<?= (int)$row['id'] ?> عبر API</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                  <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="send_api">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    
                    <div class="mb-3">
                      <label class="form-label">اختر المنصة/الشركة</label>
                      <select class="form-control" name="api_endpoint" required>
                        <option value="">-- اختر منصة --</option>
                        <?php foreach($platforms as $platform): ?>
                          <option value="<?= e($platform['title']) ?>">
                            <?= e($platform['title']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="text-muted">اختر المنصة المراد إرسال الطلب إليها</small>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">📤 إرسال</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($pages > 1): ?>
          <div class="card-footer">
            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">الأولى</a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">السابقة</a>
                  </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                  <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                      <?= $i ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                  <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">التالية</a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pages])) ?>">الأخيرة</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php }; require __DIR__ . '/_layout.php'; ?>



