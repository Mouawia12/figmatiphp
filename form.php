<?php
// /crosing/form.php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

$dbf = pdo_open($config->db_forms);

// --- تحديد النموذج المطلوب عرضه ---
$form = null;
$slug = trim($_GET['slug'] ?? '');
$form_id = (int)($_GET['form_id'] ?? 0);

if ($slug !== '') {
    $st = $dbf->prepare("SELECT * FROM forms WHERE slug = ?");
    $st->execute([$slug]);
    $form = $st->fetch(PDO::FETCH_ASSOC);
} elseif ($form_id > 0) {
    $st = $dbf->prepare("SELECT * FROM forms WHERE id = ?");
    $st->execute([$form_id]);
    $form = $st->fetch(PDO::FETCH_ASSOC);
} else {
    // إذا لم يتم تحديد رابط أو معرّف، اعرض النموذج النشط
    $active_id = (int)($dbf->query("SELECT v FROM app_settings WHERE k='active_form_id'")->fetchColumn() ?: 0);
    if ($active_id > 0) {
        $st = $dbf->prepare("SELECT * FROM forms WHERE id=?");
        $st->execute([$active_id]);
        $form = $st->fetch(PDO::FETCH_ASSOC);
    }
}

// --- تحليل حقول النموذج ---
$parts = [];
if ($form) {
    $raw = (string)$form['fields'];
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
    $parts = $lines;
    // Set active_id for the form submission
    $active_id = (int)$form['id']; 
} else {
    // إذا لم يتم العثور على أي نموذج، اعرض خطأ
    http_response_code(404);
    // يمكنك عرض صفحة خطأ 404 مخصصة هنا
}

/* صفحة */
$title = $form ? $form['title'] : 'نموذج طلب السداد لاحقًا';
$siteTitle = $config->site_title ?? 'عزم الإنجاز';
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/partials/header.php';
?>

<div class="container py-5">
  <div class="row g-4 align-items-stretch">
    <div class="col-lg-12">
      <div id="authCard" class="card card-auth fade-in">
        <div class="card-body p-4">
          <div class="text-center mb-4">
            <h2 class="h3 mb-2"><?= e($title) ?></h2>
          </div>
          <?php if (!$form): ?>
            <div class="alert alert-warning border-0 shadow-sm" role="alert">
              لا يوجد نموذج نشط حالياً. يرجى تعيين نموذج من لوحة الإدارة &raquo; النماذج.
            </div>
          <?php elseif (empty($_SESSION['user']['id'] ?? null)): ?>
            <div class="alert alert-info border-0 shadow-sm" role="alert">
              <div class="d-flex align-items-center gap-2">
                <span>🔐</span>
                <div>
                  <strong>يجب تسجيل الدخول أولاً</strong>
                  <p class="mb-0 small mt-1">لرفع طلب جديد، يرجى <a href="<?= e(app_href('login.php')) ?>" class="alert-link">تسجيل الدخول</a> أو <a href="<?= e(app_href('register.php')) ?>" class="alert-link">إنشاء حساب جديد</a></p>
                </div>
              </div>
            </div>
          <?php else: ?>
            <?php if (!empty($_GET['ok'])): ?>
              <?php $trk = $_SESSION['track_code'] ?? ''; unset($_SESSION['track_code']); ?>
              <div class="alert alert-success border-0 shadow-sm" role="alert">
                <div>✅ تم استلام طلبك بنجاح.</div>
                <?php if($trk !== ''): ?>
                  <div class="mt-1">كود التتبّع: <code><?= e($trk) ?></code></div>
                  <div class="mt-1"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_href('track.php')) ?>?code=<?= urlencode($trk) ?>">فتح صفحة التتبّع</a></div>
                <?php else: ?>
                  <div class="mt-1">يمكنك تتبّع الطلب من صفحة التتبّع.</div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <form action="<?= e(app_href('send.php')) ?>" method="post" enctype="multipart/form-data" class="row g-3">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_id" value="<?= (int)$active_id 
?>">

              <?php
              // مولّد الحقول
              // الصيغة: "التسمية:الاسم:type[:خيارات]"
              // type يدعم: text, email, tel, number, date, select|val1=عرض1|val2=عرض2, textarea, file
              foreach ($parts as $line):
                $seg = array_map('trim', explode(':', $line, 4));
                $label = e($seg[0] ?? 'حقل');
                $name  = preg_replace('/[^a-z0-9_]/i','_', $seg[1] ?? 'field');
                $type  = strtolower($seg[2] ?? 'text');
                $opts  = $seg[3] ?? ''; // للـ select مثلاً "12=١٢ شهر|24=٢٤ شهر|36=٣٦ شهر"
              ?>
              <div class="col-12">
                <label class="form-label">
                  <?= $label ?><?= str_contains($label,'*') ? ' <span class="text-danger">*</span>' : '' ?>
                </label>

                <?php if ($type === 'textarea'): ?>
                  <textarea name="<?= e($name) ?>" class="form-control" rows="4"></textarea>

                <?php elseif ($type === 'select' || str_starts_with($type,'select')): 
                  // إن كانت select أو select|خيارات ضمن type نفسه
                  $options = [];
                  if ($opts === '' && str_contains($type,'|')) {
                    [, $opts] = explode('|', $type, 2);
                    $type = 'select';
                  }
                  if ($opts !== '') {
                    foreach (explode('|', $opts) as $opt) {
                      $kv = array_map('trim', explode('=', $opt, 2));
                      $val = $kv[0] ?? '';
                      $txt = $kv[1] ?? $val;
                      if ($val !== '') $options[] = [$val, $txt];
                    }
                  }
                ?>
                  <select name="<?= e($name) ?>" class="form-select">
                    <?php foreach ($options as [$val,$txt]): ?>
                      <option value="<?= e($val) ?>"><?= e($txt) ?></option>
                    <?php endforeach; ?>
                  </select>

                <?php elseif ($type === 'file'): ?>
                  <input type="file" name="<?= e($name) ?>" class="form-control" accept=".pdf,.jpg,.jpeg,.png">

                <?php else: 
                  // text, email, tel, number, date ...
                  $htmlType = in_array($type, ['text','email','tel','number','date']) ? $type : 'text';
                  $maxDateAttr = ($htmlType === 'date') ? ' max="' . date('Y-m-d') . '"' : '';
                ?>
                  <?php if ($htmlType === 'number'): ?>
                    <input type="text" name="<?= e($name) ?>" class="form-control js-numfmt" inputmode="numeric" autocomplete="off">
                  <?php else: ?>
                    <input type="<?= e($htmlType) ?>" name="<?= e($name) ?>" class="form-control"<?= $maxDateAttr ?>>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>

              <div class="col-12">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="consent_finance" value="1" required>
                  <span class="form-check-label">أوافق على إرسال بيانات النموذج كاملةً إلى منصات التمويل ذات الصلة لغرض معالبتي.</span>
                </label>
              </div>

              <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="<?= e(app_href('')) ?>" class="btn btn-outline-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary">إرسال الطلب</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const els=document.querySelectorAll('.reveal');
  const io=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('is-visible'); io.unobserve(e.target);}})},{threshold:.12});
  els.forEach(el=>io.observe(el));
</script>


<script>
  function toLatinDigits(s){
    const map = {'٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
    return s.replace(/[٠-٩]/g, d=>map[d]||d);
  }
  function formatWithCommas(val){
    let x = toLatinDigits(val).replace(/[^0-9.]/g,'');
    const parts = x.split('.');
    let int = parts[0];
    let dec = parts[1] ? (parts[1].slice(0,6)) : '';
    int = int.replace(/^0+(?=\d)/,'');
    int = int.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return dec ? int+'.'+dec : int;
  }
  document.addEventListener('input', function(e){
    const el = e.target;
    if(el.classList && el.classList.contains('js-numfmt')){
      const pos = el.selectionStart;
      const before = el.value;
      el.value = formatWithCommas(el.value);
      const diff = el.value.length - before.length;
      if(typeof pos==='number') el.setSelectionRange(Math.max(0,pos+diff), Math.max(0,pos+diff));
    }
  });
  document.addEventListener('submit', function(e){
    const form = e.target.closest('form');
    if(!form) return;
    form.querySelectorAll('.js-numfmt').forEach(function(el){
      el.value = toLatinDigits(el.value).replace(/,/g,'');
    });
  }, true);
  document.querySelectorAll('.js-numfmt').forEach(el=>{ if(el.value) el.value = formatWithCommas(el.value); });
  </script>
<script src="<?= e(asset_href('assets/dnd-upload.js?v=3')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= e(asset_href('assets/ai-decorator-module.js')) ?>"></script>
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
<script src="<?= e(asset_href('assets/step-tracker.js')) ?>"></script>
<script>
    const tracker = new StepTracker({
        customerId: 'form_user_' + Date.now(),
        apiUrl: '/crosing/api_chat.php'
    });
    
    tracker.trackStep('ملء النموذج', 1, 'in_progress');
    
    tracker.monitorInactivity(600);
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>


