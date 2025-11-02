<?php
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
$page_title = 'العملاء';

$db = pdo_open('users');
ensure_customer_preferences_table_exists();
ensure_users_table_exists();
if (($method = $_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) die('CSRF');
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email= trim($_POST['email'] ?? '');
        $phone= trim($_POST['phone'] ?? '');
        $lang = trim($_POST['language'] ?? 'ar');

        if ($name === '' || $email === '' || $phone === '') {
            $err = 'الاسم والبريد والجوال مطلوبة';
        } else {

            if ($action === 'create') {
                $st = $db->prepare('INSERT INTO users(full_name,email,phone,role,created_at,updated_at) VALUES(?,?,?,"user",datetime("now"),datetime("now"))');
                $st->execute([$name,$email,$phone]);
                $id = (int)$db->lastInsertId();
            } else {
                $st = $db->prepare('UPDATE users SET full_name=?, email=?, phone=?, updated_at=datetime("now") WHERE id=?');
                $st->execute([$name,$email,$phone,$id]);
            }
            // preferences upsert
            $stp = $db->prepare('INSERT INTO customer_preferences(customer_id,language) VALUES(?,?)
                                 ON CONFLICT(customer_id) DO UPDATE SET language=excluded.language');
            try { $stp->execute([$id,$lang]); } catch (Throwable $e) { /* mysql fallback */
                try {
                    $chk = $db->prepare('SELECT customer_id FROM customer_preferences WHERE customer_id=?');
                    $chk->execute([$id]);
                    if ($chk->fetch()) {
                        $upd = $db->prepare('UPDATE customer_preferences SET language=? WHERE customer_id=?');
                        $upd->execute([$lang,$id]);
                    } else {
                        $ins = $db->prepare('INSERT INTO customer_preferences(customer_id,language) VALUES(?,?)');
                        $ins->execute([$id,$lang]);
                    }
                } catch (Throwable $e2) {}
            }
            header('Location: customers.php?saved=1'); exit;
        }
    }
}

$q = trim($_GET['q'] ?? '');
$where = '';$params = [];
if ($q !== '') { $where = 'WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?'; $params = ["%$q%","%$q%","%$q%"]; }
$rows = $db->prepare('SELECT u.*, p.language, p.timezone, p.theme FROM users u LEFT JOIN customer_preferences p ON u.id = p.customer_id ' . $where . ' ORDER BY u.id DESC LIMIT 200');
$rows->execute($params);
$rows = $rows->fetchAll();

$content = function() use ($rows, $q) {
  $csrf = csrf_token();
?>
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="mb-0">قائمة العملاء</h6>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#custModal">عميل جديد</button>
        </div>
        <div class="card-body pt-2">
          <form class="row g-2 mb-3" method="get">
            <div class="col-sm-6 col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="بحث بالاسم/البريد/الجوال"></div>
            <div class="col-auto"><button class="btn btn-secondary">بحث</button></div>
          </form>
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead><tr>
                <th>الاسم</th><th>البريد</th><th>الجوال</th><th>الدور</th><th></th>
              </tr></thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['full_name'] ?? '') ?></td>
                  <td><?= e($r['email'] ?? '') ?></td>
                  <td><?= e($r['phone'] ?? '') ?></td>
                  <td><span class="badge bg-light text-dark"><?= e($r['role'] ?? 'user') ?></span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" onclick='fillEdit(<?= json_encode($r, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>)'>تعديل</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="custModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" id="f_id">
          <input type="hidden" name="action" id="f_action" value="create">
          <div class="modal-header"><h6 class="modal-title">عميل</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">الاسم الكامل</label><input name="full_name" id="f_full_name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">البريد</label><input name="email" id="f_email" type="email" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الجوال</label><input name="phone" id="f_phone" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">اللغة</label><select name="language" id="f_language" class="form-select"><option value="ar">العربية</option><option value="en">English</option></select></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function fillEdit(r){
      document.getElementById('f_action').value='update';
      document.getElementById('f_id').value=r.id||'';
      document.getElementById('f_full_name').value=r.full_name||'';
      document.getElementById('f_email').value=r.email||'';
      document.getElementById('f_phone').value=r.phone||'';
      document.getElementById('f_language').value=r.language||'ar';
      var m = new bootstrap.Modal(document.getElementById('custModal')); m.show();
    }
  </script>
<?php };

require __DIR__ . '/_layout.php';

