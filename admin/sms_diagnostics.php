<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();

// Ensure notifications table exists
ensure_notifications_schema();

$db = pdo_open('notifications');

// Fetch last failed SMS and last 10 failures
$lastFailed = null;
$recent = [];
try {
  $st = $db->prepare("SELECT * FROM notifications WHERE type='sms' AND status='failed' ORDER BY id DESC LIMIT 1");
  $st->execute();
  $lastFailed = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  $st2 = $db->prepare("SELECT * FROM notifications WHERE type='sms' AND status='failed' ORDER BY id DESC LIMIT 10");
  $st2->execute();
  $recent = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  error_log('sms_diagnostics query failed: ' . $e->getMessage());
}

$page_title = 'تشخيص رسائل SMS';
$content = function() use ($lastFailed, $recent) {
?>
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0"><h6>آخر استجابة فاشلة</h6></div>
        <div class="card-body">
          <?php if (!$lastFailed): ?>
            <div class="alert alert-success mb-0">لا توجد حالات فشل مسجلة لرسائل SMS.</div>
          <?php else: ?>
            <dl class="row mb-0">
              <dt class="col-sm-3">التاريخ</dt><dd class="col-sm-9"><?= e($lastFailed['created_at'] ?? '') ?></dd>
              <dt class="col-sm-3">المستلم</dt><dd class="col-sm-9"><code><?= e($lastFailed['recipients'] ?? '') ?></code></dd>
              <dt class="col-sm-3">النص</dt><dd class="col-sm-9"><pre class="mb-0" style="white-space:pre-wrap;word-wrap:anywhere;"><?= e($lastFailed['message'] ?? '') ?></pre></dd>
              <dt class="col-sm-3">الحالة</dt><dd class="col-sm-9"><span class="badge bg-danger">failed</span></dd>
            </dl>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header pb-0"><h6>آخر 10 حالات فشل</h6></div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>التاريخ</th>
                  <th>المستلم</th>
                  <th>الرسالة</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$recent): ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">لا توجد بيانات</td></tr>
                <?php else: ?>
                  <?php foreach ($recent as $row): ?>
                    <tr>
                      <td class="text-xs text-secondary"><?= e($row['created_at'] ?? '') ?></td>
                      <td><code><?= e($row['recipients'] ?? '') ?></code></td>
                      <td style="max-width:600px"><pre class="mb-0" style="white-space:pre-wrap;word-wrap:anywhere;"><?= e($row['message'] ?? '') ?></pre></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php };
require __DIR__ . '/_layout.php';
