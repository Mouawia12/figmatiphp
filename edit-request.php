<?php
declare(strict_types=1);

require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

header('Content-Type: text/html; charset=utf-8');

$code = trim((string)($_GET['code'] ?? $_POST['code'] ?? ''));
$exp  = (int)($_GET['exp'] ?? $_POST['exp'] ?? 0);
$sig  = trim((string)($_GET['sig'] ?? $_POST['sig'] ?? ''));

$error = '';
$notice = '';

try {
    if ($code === '' || $exp <= 0 || $sig === '') {
        throw new RuntimeException('معلمات الرابط غير مكتملة.');
    }
    if (!verify_edit_token($code, $exp, $sig)) {
        throw new RuntimeException('الرابط منتهي الصلاحية أو غير صالح.');
    }

    // Load request by tracking_code
    if (function_exists('ensure_requests_schema')) ensure_requests_schema();
    $dbr = pdo_open('requests');
    $q = $dbr->prepare('SELECT * FROM requests WHERE tracking_code = ? LIMIT 1');
    $q->execute([$code]);
    $req = $q->fetch(PDO::FETCH_ASSOC);
    if (!$req) throw new RuntimeException('لم يتم العثور على الطلب.');

    // Only allow edit if status is needs_revision or customer_editing
    $status = (string)($req['status'] ?? 'pending');
    if (!in_array($status, ['needs_revision', 'customer_editing', 'pending'], true)) {
        // allow pending optionally for early fix
        throw new RuntimeException('لا يمكن تعديل الطلب في حالته الحالية.');
    }

    // Parse payload fields
    $payload = [];
    if (isset($req['data_json']) && $req['data_json'] !== null && $req['data_json'] !== '') {
        $payload = json_decode((string)$req['data_json'], true) ?: [];
    }
    $fields = $payload['fields'] ?? [];
    $files  = $payload['files']  ?? [];

    // If POST, process updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do']) && $_POST['do'] === 'save') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('رمز الأمان غير صالح.');
        }

        $update = $fields; // start with existing
        // Limit to common editable fields for نموذج 1 (طلب السداد لاحقاً)
        $editable = ['name','email','phone','purpose','notes'];
        foreach ($editable as $k) {
            if (array_key_exists($k, $_POST)) {
                $update[$k] = trim((string)$_POST[$k]);
            }
        }

        // Optional file re-upload (single main file key: attachment or file)
        $mainFileKey = 'attachment';
        if (!isset($files[$mainFileKey]) && isset($req['file']) && $req['file']) {
            // If original system used top-level `file`
            $files[$mainFileKey] = ['saved' => (string)$req['file'], 'orig' => basename((string)$req['file'])];
        }

        if (!empty($_FILES[$mainFileKey]['name'] ?? '')) {
            try {
                $saved = handle_upload_limit($_FILES[$mainFileKey], 500 * 1024 * 1024, ['pdf','jpg','jpeg','png']);
                $orig  = basename((string)($_FILES[$mainFileKey]['name'] ?? ''));
                $files[$mainFileKey] = ['saved'=>$saved,'orig'=>$orig];
            } catch (Throwable $e) {
                throw new RuntimeException('فشل رفع الملف: ' . $e->getMessage());
            }
        }

        $payload['fields'] = $update;
        $payload['files']  = $files;

        // Move status to reviewing and stamp update time
        $newStatus = 'reviewing';
        $now = date('Y-m-d H:i:s');

        // Update DB (MySQL vs SQLite timestamp syntax handled elsewhere when inserting; here we set values)
        $st = $dbr->prepare('UPDATE requests SET data_json = ?, status = ?, status_updated_at = ? WHERE id = ?');
        $st->execute([
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $newStatus,
            $now,
            (int)$req['id']
        ]);

        $notice = 'تم حفظ التعديلات، وسيتم مراجعتها قريباً.';
        // reload request
        $q->execute([$code]);
        $req = $q->fetch(PDO::FETCH_ASSOC);
        $fields = $payload['fields'];
        $files  = $payload['files'];
        $status = $newStatus;
    } else {
        // Switch to customer_editing to reflect that the customer opened edit link
        if ($status !== 'customer_editing') {
            try {
                $st = $dbr->prepare('UPDATE requests SET status = ?, status_updated_at = ? WHERE id = ?');
                $st->execute(['customer_editing', date('Y-m-d H:i:s'), (int)$req['id']]);
                $status = 'customer_editing';
            } catch (Throwable $e) { /* ignore */ }
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$siteTitle = $config->site_title ?? 'النظام';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($siteTitle) ?> - تعديل الطلب</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
  <style>
    .soft-card{border:0;box-shadow:0 10px 30px rgba(0,0,0,.06);border-radius:18px}
  </style>
<?php include __DIR__ . '/partials/seo.php'; ?>
</head>
<body class="app-bg">
<main class="container py-5" style="max-width:900px">
  <div class="card soft-card">
    <div class="card-body p-4">
      <h1 class="h5 mb-3">تعديل الطلب</h1>
      <?php if($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php else: ?>
        <?php if(!empty($notice)): ?><div class="alert alert-success"><?= e($notice) ?></div><?php endif; ?>

        <div class="mb-3 small text-muted">
          <span>رقم التتبّع:</span>
          <code><?= e($code) ?></code>
          <span class="ms-3">حالة الطلب الحالية:</span>
          <span class="badge text-bg-light"><?= e($status) ?></span>
        </div>

        <?php if (!empty($req['status_note'])): ?>
          <div class="alert alert-warning">
            <strong>ملاحظة من الموظف:</strong>
            <div class="mt-1"><?= nl2br(e((string)$req['status_note'])) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="code" value="<?= e($code) ?>">
          <input type="hidden" name="exp" value="<?= e((string)$exp) ?>">
          <input type="hidden" name="sig" value="<?= e($sig) ?>">
          <input type="hidden" name="do" value="save">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">الاسم</label>
              <input class="form-control" name="name" value="<?= e((string)($fields['name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">البريد الإلكتروني</label>
              <input class="form-control" type="email" name="email" value="<?= e((string)($fields['email'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">رقم الجوال</label>
              <input class="form-control" name="phone" placeholder="05XXXXXXXX" value="<?= e((string)($fields['phone'] ?? '')) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">الغرض/الملاحظات</label>
              <textarea class="form-control" rows="3" name="purpose"><?= e((string)($fields['purpose'] ?? ($fields['notes'] ?? ''))) ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">المرفق المطلوب (PDF/صور)</label>
              <input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
              <?php if (!empty($files['attachment']['orig'] ?? '')): ?>
                <div class="form-text">الموجود: <?= e((string)$files['attachment']['orig']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">حفظ التعديلات</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_href('track.php?code=' . urlencode($code))) ?>">رجوع للتتبّع</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>

