<?php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me = require_admin();
ensure_requests_schema();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); die('Not found'); }

$dbr = pdo_open('requests');
$dbf = pdo_open('forms');

// Fetch request
$st = $dbr->prepare("SELECT * FROM requests WHERE id=?");
$st->execute([$id]);
$req = $st->fetch();
if (!$req) { http_response_code(404); die('Not found'); }

function _parse_form_fields(string $fields): array {
    $parsed = [];
    $lines = explode("\n", trim($fields));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode(':', $line);
        if (count($parts) >= 3) {
            $field = [
                'label' => trim($parts[0]),
                'name' => trim($parts[1]),
                'type' => trim($parts[2]),
                'options' => []
            ];
            if ($field['type'] === 'select' && isset($parts[3])) {
                $options = explode('|', $parts[3]);
                foreach ($options as $option) {
                    $optionParts = explode('=', $option, 2);
                    if (count($optionParts) === 2) {
                        $field['options'][trim($optionParts[0])] = trim($optionParts[1]);
                    }
                }
            }
            $parsed[$field['name']] = $field;
        }
    }
    return $parsed;
}

// POST Handling
$notice = '';
$send_notice = '';
$send_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'ask_revision') {
        try {
            $note  = trim((string)($_POST['note'] ?? ''));
            $track = (string)($req['tracking_code'] ?? '');
            if ($track === '') { $track = gen_tracking_code(); $dbr->prepare("UPDATE requests SET tracking_code=? WHERE id=?")->execute([$track, $id]); }
            $link = edit_link_for_request($track, 48);
            $now_db = ($config->db_driver === 'mysql') ? 'NOW()' : "datetime('now')";
            $dbr->prepare("UPDATE requests SET status=?, status_note=?, status_updated_at={$now_db} WHERE id=?")
                ->execute(['needs_revision', $note, $id]);

            $payload = isset($req['data_json']) ? (json_decode((string)$req['data_json'], true) ?: []) : [];
            $fields = $payload['fields'] ?? [];
            $logical_email = (string)($req['email'] ?? ($fields['email'] ?? ''));
            $logical_phone = (string)($fields['phone'] ?? '');

            $trackCode = (string)$track;
            $sms = "عميلنا الكريم، نحتاج إكمال بيانات طلبك رقم {$trackCode}. مدة الرابط 48 ساعة:\n{$link}\nشكرًا لتعاونك.";
            if ($logical_phone !== '' && function_exists('authentica_send_sms')) {
                try { 
                    authentica_send_sms(
                        $logical_phone, 
                        $sms,
                        'admin_note',
                        [
                            'request_id' => $id,
                            'tracking_code' => $track_code,
                            'admin_action' => 'add_note',
                        ]
                    ); 
                } catch (Throwable $e) { 
                    error_log("Failed to send SMS in admin note: " . $e->getMessage());
                }
            }
            if ($logical_email !== '') {
                @mail($logical_email,
                      'طلب إكمال بيانات الطلب رقم ' . $trackCode,
                      "السلام عليكم، نحتاج بعض المعلومات/الملفات لإكمال طلبك رقم {$trackCode}.\nالرجاء فتح الرابط خلال 48 ساعة وإتمام المطلوب:\n{$link}\n\nشكرًا لك.",
                      safe_mail_headers($config->mail_to));
            }
            $send_notice = 'تم إرسال طلب التعديل للعميل.';

            // refresh request record
            $st->execute([$id]);
            $req = $st->fetch();
        } catch (Throwable $e) {
            $send_error = $e->getMessage();
        }
    } else {
    $new_status = $_POST['status'] ?? 'pending';
    $note = trim($_POST['status_note'] ?? '');

    $now_db = ($config->db_driver === 'mysql') ? 'NOW()' : "datetime('now')";
    $dbr->prepare("UPDATE requests SET status=?, status_note=?, status_updated_at={$now_db} WHERE id=?")
        ->execute([$new_status, $note, $id]);

    $notice = 'تم تحديث حالة الطلب.';

    // Optional SMS send if note provided and phone detected
    if ($note !== '') {
        $customer_phone = null;
        $json_payload = isset($req['data_json']) ? json_decode((string)$req['data_json'], true) : [];
        $common_keys = ['phone', 'tel', 'mobile', 'jawwal'];
        if (isset($json_payload['fields']) && is_array($json_payload['fields'])) {
            foreach ($common_keys as $key) {
                if (!empty($json_payload['fields'][$key])) { $customer_phone = $json_payload['fields'][$key]; break; }
            }
        }
        if (!$customer_phone && isset($json_payload['fields']) && is_array($json_payload['fields']) && function_exists('ksa_local')) {
            foreach ($json_payload['fields'] as $value) {
                if (is_string($value) && ksa_local($value)) { $customer_phone = $value; break; }
            }
        }
        if ($customer_phone && function_exists('authentica_send_sms')) {
            $status_label = status_label($new_status);
            $sms_message = "تحديث: حالة طلبك رقم #{$id} أصبحت: {$status_label}. ملاحظة: {$note}";
            // Override message to include tracking link and note
            $track_code = $req['tracking_code'] ?? '';
            if ($track_code === '' && function_exists('gen_tracking_code')) {
                $track_code = gen_tracking_code();
                $dbr->prepare("UPDATE requests SET tracking_code=? WHERE id=?")->execute([$track_code, $id]);
            }
            $trackPath = ltrim(app_href('track.php'), '/');
            $link = public_url($trackPath) . '?code=' . urlencode($track_code);
            $sms_message  = "تحديث طلبك رقم #{$id}: الحالة الآن: " . status_label($new_status) . ".";
            $sms_message .= "\nتتبع الطلب: {$link}";
            if ($note !== '') { $sms_message .= "\nملاحظة: {$note}"; }
            $sms_result = authentica_send_sms(
                $customer_phone, 
                $sms_message,
                'admin_status_update',
                [
                    'request_id' => $id,
                    'tracking_code' => $track_code,
                    'new_status' => $new_status,
                    'admin_action' => 'status_change',
                ]
            );
            if (($sms_result['success'] ?? false)) {
                $notice .= ' تم إرسال رسالة SMS للعميل.';
            }
        }
    }

    // Refresh
    $st->execute([$id]);
    $req = $st->fetch();

    // If note is empty, still send status update with link
    if ($note === '') {
        $customer_phone = null;
        $json_payload = isset($req['data_json']) ? json_decode((string)$req['data_json'], true) : [];
        $common_keys = ['phone', 'tel', 'mobile', 'jawwal'];
        if (isset($json_payload['fields']) && is_array($json_payload['fields'])) {
            foreach ($common_keys as $key) {
                if (!empty($json_payload['fields'][$key])) { $customer_phone = $json_payload['fields'][$key]; break; }
            }
        }
        if (!$customer_phone && isset($json_payload['fields']) && is_array($json_payload['fields']) && function_exists('ksa_local')) {
            foreach ($json_payload['fields'] as $value) {
                if (is_string($value) && ksa_local($value)) { $customer_phone = $value; break; }
            }
        }
        if ($customer_phone && function_exists('authentica_send_sms')) {
            $track_code = $req['tracking_code'] ?? '';
            if ($track_code === '' && function_exists('gen_tracking_code')) {
                $track_code = gen_tracking_code();
                $dbr->prepare("UPDATE requests SET tracking_code=? WHERE id=?")->execute([$track_code, $id]);
            }
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? '';
            $path   = app_href('../track.php');
            $link   = $scheme . '://' . $host . $path . '?code=' . urlencode($track_code);
            $sms_message  = "تحديث طلبك رقم #{$id}: الحالة الآن: " . status_label($new_status) . ".\nتتبع الطلب: {$link}";
            authentica_send_sms($customer_phone, $sms_message);
        }
    }
    }
}

// Build page data
$payload = isset($req['data_json']) ? (json_decode((string)$req['data_json'], true) ?: []) : [];
$fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
$files  = is_array($payload['files'] ?? null) ? $payload['files'] : [];
$costs  = is_array($payload['costs'] ?? null) ? $payload['costs'] : [];

$form = null; $field_defs = [];
if (!empty($req['form_id'])) {
    try {
        $stf = $dbf->prepare('SELECT id, title, fields FROM forms WHERE id=?');
        $stf->execute([(int)$req['form_id']]);
        $form = $stf->fetch();
        if ($form && !empty($form['fields'])) $field_defs = _parse_form_fields((string)$form['fields']);
    } catch (Throwable $e) {}
}

function _human_value($key, $value, $defs) {
    if (is_array($value)) return implode(', ', array_map('strval', $value));
    if (isset($defs[$key]['type']) && $defs[$key]['type'] === 'select') {
        $opts = $defs[$key]['options'] ?? [];
        if (isset($opts[$value])) return $opts[$value];
    }
    return (string)$value;
}

$page_title = 'عرض الطلب #' . $id;
$content = function() use ($req, $fields, $files, $costs, $form, $field_defs, $id, $notice) {
?>
  <?php if (!empty($notice)): ?>
    <div class="alert alert-success" role="alert"><?= e($notice) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <h6 class="mb-0">تفاصيل الطلب #<?= (int)$id ?></h6>
        </div>
        <div class="card-body">
          <div class="row mb-2">
            <div class="col-md-6"><strong>الحالة:</strong> <?= e(status_label($req['status'] ?? 'pending')) ?></div>
            <div class="col-md-6"><strong>تاريخ الإنشاء:</strong> <?= e($req['created_at'] ?? '-') ?></div>
          </div>
          <?php if (!empty($req['status_updated_at'])): ?>
            <div class="mb-2"><strong>آخر تحديث للحالة:</strong> <?= e($req['status_updated_at']) ?></div>
          <?php endif; ?>
          <?php if (!empty($req['tracking_code'])): ?>
            <div class="mb-2"><strong>رقم التتبع:</strong> <code><?= e($req['tracking_code']) ?></code></div>
          <?php endif; ?>
          <?php if ($form): ?>
            <div class="mb-2"><strong>النموذج:</strong> <?= e($form['title'] ?? ('#' . (int)($form['id'] ?? 0))) ?></div>
          <?php endif; ?>
          <?php if (!empty($req['status_note'])): ?>
            <div class="mt-3"><strong>ملاحظة الحالة:</strong><br><?= nl2br(e($req['status_note'])) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">الحقول</h6></div>
        <div class="card-body">
          <?php if (empty($fields)): ?>
            <div class="text-muted">لا توجد حقول إضافية.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>الحقل</th><th>القيمة</th></tr></thead>
                <tbody>
                  <?php foreach ($fields as $k => $v): $label = $field_defs[$k]['label'] ?? $k; ?>
                    <tr>
                      <td class="text-muted small"><?= e($label) ?></td>
                      <td><?= e(_human_value($k, $v, $field_defs)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">الملفات</h6></div>
        <div class="card-body">
          <?php 
            $dl = function(string $saved, string $orig) {
              $saved_q = rawurlencode($saved); $orig_q = rawurlencode($orig);
              return app_href("../download.php?file={$saved_q}&as={$orig_q}");
            };
          ?>
          <?php if (!empty($files) && is_array($files)): ?>
            <ul class="list-unstyled mb-0">
              <?php foreach ($files as $name => $fi): if (!is_array($fi)) continue; $orig = (string)($fi['orig'] ?? $name); $saved = (string)($fi['saved'] ?? ''); if ($saved==='') continue; $href = $dl($saved, $orig); $pv = $href . '&inline=1'; ?>
                <li class="mb-2 d-flex align-items-center gap-2">
                  <a class="text-primary" href="<?= e($href) ?>">تحميل: <?= e($orig) ?></a>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewFile('<?= e($pv) ?>','<?= e($orig) ?>')">معاينة</button>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php elseif (!empty($req['file'])): ?>
            <?php $file_q = rawurlencode((string)$req['file']); $href = app_href('../download.php?file=' . $file_q); $pv = $href . '&inline=1'; ?>
            <div class="d-flex align-items-center gap-2">
              <a class="text-primary" href="<?= e($href) ?>">تحميل الملف</a>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewFile('<?= e($pv) ?>','<?= e((string)$req['file']) ?>')">معاينة</button>
            </div>
          <?php else: ?>
            <div class="text-muted">لا توجد ملفات.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header pb-0"><h6 class="mb-0">تحديث الحالة</h6></div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">الحالة</label>
              <?php $current = $req['status'] ?? 'pending'; ?>
              <select class="form-control" name="status">
                <option value="pending"   <?= $current==='pending'   ? 'selected' : '' ?>>قيد الانتظار</option>
                <option value="reviewing" <?= $current==='reviewing' ? 'selected' : '' ?>>قيد المراجعة</option>
                <option value="approved"  <?= $current==='approved'  ? 'selected' : '' ?>>تمت الموافقة</option>
                <option value="rejected"  <?= $current==='rejected'  ? 'selected' : '' ?>>مرفوض</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظة</label>
              <textarea class="form-control" name="status_note" rows="4" placeholder="ملاحظة للعميل (اختياري)"><?= e($req['status_note'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">حفظ</button>
              <a href="<?= e(app_href('admin/requests.php')) ?>" class="btn btn-outline-secondary">عودة</a>
            </div>
          </form>
        
      <div class="card mt-3">
        <div class="card-header pb-0"><h6 class="mb-0">طلب تعديل من العميل</h6></div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="ask_revision">
            <div class="mb-3">
              <label class="form-label">ملاحظة للعميل</label>
              <textarea class="form-control" name="note" rows="3" placeholder="مثال: نحتاج صورة أوضح للوثيقة أو رقم الحساب الصحيح."></textarea>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-warning">إرسال طلب تعديل</button>
            </div>
          </form>
        </div>
      </div>
      </div>
    </div>
  </div>
<?php };

include __DIR__ . '/_layout.php';



