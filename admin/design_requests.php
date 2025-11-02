<?php
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();

$form_id = 5; // ID نموذج طلب التصميم الداخلي

$dbr = pdo_open(cfg()->db_requests);

$st = $dbr->prepare("SELECT * FROM requests WHERE form_id = ? ORDER BY id DESC");
$st->execute([$form_id]);
$requests = $st->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'طلبات التصميم الداخلي';
$content = function() use ($requests) { ?>
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0"><h6><?= e($GLOBALS['page_title']) ?> (<?= count($requests) ?>)</h6></div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">العميل</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">تفاصيل</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المبلغ</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">التاريخ</th>
                <th class="text-secondary opacity-7">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($requests)): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted"><h6 class="mb-0">لا توجد طلبات حالياً.</h6></td></tr>
              <?php else: foreach ($requests as $req): ?>
                <?php
                  $payload = json_decode((string)($req['data_json'] ?? ''), true) ?: [];
                  $fields  = $payload['fields'] ?? [];
                  $costs   = $payload['costs'] ?? [];
                  $files   = $payload['files'] ?? [];
                  $file_info = $files['floor_plan'] ?? ($files['attachment'] ?? null);
                  $download_url = null; $preview_url = null; $orig = null;
                  if ($file_info) {
                    $saved = is_array($file_info) ? ($file_info['saved'] ?? '') : (string)$file_info;
                    $orig  = is_array($file_info) ? ($file_info['orig']  ?? $saved) : $saved;
                    if ($saved !== '') {
                      $saved_q = rawurlencode($saved);
                      $orig_q  = rawurlencode($orig);
                      $download_url = app_href("../download.php?file={$saved_q}&as={$orig_q}");
                      $preview_url  = $download_url . '&inline=1';
                    }
                  }
                  $view_url = 'request_view.php?id=' . (int)$req['id'];
                ?>
                <tr>
                  <td>
                    <div class="d-flex px-2 py-1">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="mb-0 text-sm"><?= e($fields['name'] ?? $req['name'] ?? '-') ?></h6>
                        <p class="text-xs text-secondary mb-0"><?= e($fields['email'] ?? $req['email'] ?? '-') ?></p>
                        <p class="text-xs text-secondary mb-0"><?= e($fields['phone'] ?? '-') ?></p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <p class="text-xs font-weight-bold mb-0">نمط: <span class="font-weight-normal"><?= e($fields['design_style'] ?? '-') ?></span></p>
                    <p class="text-xs font-weight-bold mb-0">مساحة: <span class="font-weight-normal"><?= e($fields['area_sqm'] ?? '-') ?> م²</span></p>
                  </td>
                  <td class="align-middle text-center text-sm">
                    <span class="badge badge-sm bg-gradient-success"><?= e(number_format((float)($costs['total_cost'] ?? 0), 2)) ?> ريال</span>
                  </td>
                  <td class="align-middle text-center"><span class="text-secondary text-xs font-weight-bold"><?= e($req['created_at'] ?? '-') ?></span></td>
                  <td class="align-middle">
                    <a href="<?= e($view_url) ?>" class="text-secondary font-weight-bold text-xs" title="عرض">عرض</a>
                    <?php if ($download_url): ?>
                      <button type="button" class="text-info font-weight-bold text-xs ms-3" style="background:none;border:none;cursor:pointer;" title="معاينة" onclick="previewFile('<?= e($preview_url) ?>','<?= e($orig ?? 'attachment') ?>')">معاينة</button>
                      <a href="<?= e($download_url) ?>" class="text-primary font-weight-bold text-xs ms-3" title="تنزيل">تنزيل</a>
                    <?php endif; ?>
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
<?php }; include __DIR__ . '/_layout.php';

