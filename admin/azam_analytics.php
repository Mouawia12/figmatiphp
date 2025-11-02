<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
ensure_chat_analytics_schema();

$db = pdo_open('notifications');
$top = [];
$needs = [];
try {
  $top = $db->query("SELECT question_key, question_text, asked_count, unanswered_count, last_at FROM chat_questions ORDER BY asked_count DESC, last_at DESC LIMIT 50")->fetchAll();
  $needs = $db->query("SELECT question_key, question_text, asked_count, unanswered_count, last_at FROM chat_questions WHERE unanswered_count > 0 ORDER BY unanswered_count DESC, last_at DESC LIMIT 50")->fetchAll();
} catch (Throwable $e) {
  $top = $needs = [];
}

$page_title = 'إحصاءات عزم';
$content = function() use ($top, $needs) {
  $csrf = csrf_token();
?>
<div class="container-fluid py-4">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">أكثر الأسئلة طرحًا</h6>
          <small class="text-muted">Top 50</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>السؤال</th>
                  <th class="text-center" style="width:120px">عدد المرات</th>
                  <th class="text-center" style="width:120px">غير مُجاب</th>
                  <th class="text-center" style="width:120px">إجراء</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$top): ?>
                  <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات بعد.</td></tr>
                <?php else: foreach ($top as $r): ?>
                  <tr>
                    <td><?= e($r['question_text'] ?? '') ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= (int)($r['asked_count'] ?? 0) ?></span></td>
                    <td class="text-center"><span class="badge bg-warning"><?= (int)($r['unanswered_count'] ?? 0) ?></span></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary" onclick="queueQuestion('<?= e($r['question_key'] ?? '') ?>','<?= e($r['question_text'] ?? '') ?>')">أرسل للتعليم</button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">بحاجة لتعليم (لم تُجَب بثقة)</h6>
          <small class="text-muted">Top 50</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:520px;overflow:auto">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>السؤال</th>
                  <th class="text-center" style="width:120px">غير مُجاب</th>
                  <th class="text-center" style="width:120px">إجراء</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$needs): ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">لا توجد أسئلة بحاجة لتعليم.</td></tr>
                <?php else: foreach ($needs as $r): ?>
                  <tr>
                    <td><?= e($r['question_text'] ?? '') ?></td>
                    <td class="text-center"><span class="badge bg-warning"><?= (int)($r['unanswered_count'] ?? 0) ?></span></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary" onclick="queueQuestion('<?= e($r['question_key'] ?? '') ?>','<?= e($r['question_text'] ?? '') ?>')">أرسل للتعليم</button>
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
</div>
<script>
async function queueQuestion(key, text){
  try {
    const res = await fetch('api/queue_from_question.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_token: '<?= e($csrf) ?>', question_key: key, question_text: text })
    });
    const data = await res.json();
    if (data.ok) {
      alert('تم إرسال السؤال لقائمة الانتظار للتعليم');
    } else {
      alert('تعذر الإرسال: ' + (data.error || 'خطأ'));
    }
  } catch (e) { alert('خطأ في الاتصال'); }
}
</script>
<?php };
require __DIR__ . '/_layout.php';
