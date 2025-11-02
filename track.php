<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
ensure_requests_schema();

$code = preg_replace('/[^A-Za-z0-9]/','', $_GET['code'] ?? '');
$dbr  = pdo_open($config->db_requests);

$row = null;
if ($code !== '') {
  $st = $dbr->prepare("SELECT id,name,created_at,status,status_note FROM requests WHERE tracking_code=?");
  $st->execute([$code]);
  $row = $st->fetch();
}

$siteTitle = $config->site_title ?? 'عزم الإنجاز';
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/partials/header.php';
?>

<div class="container py-5">
  <div class="row g-4 align-items-stretch">
    <div class="col-lg-12">
      <div id="authCard" class="card card-auth fade-in">
        <div class="card-body p-4">
          <h4 class="card-title mb-4">تتبّع الطلب</h4>
          <p class="lead text-muted mb-0">أدخل كود التتبّع الذي وصلك في رسالة التأكيد.</p>

          <!-- نموذج البحث -->
          <form class="card border-0 shadow-sm p-3 mb-4" method="get" action="">
            <label class="form-label">أدخل كود التتبّع</label>
            <div class="input-group">
              <input class="form-control" name="code" value="<?= e($code) ?>" placeholder="مثال: 9F2X7K3BHQ" autocomplete="off">
              <button class="btn btn-primary" type="submit">استعلام</button>
            </div>
            <?php if ($code !== ''): ?>
              <div class="d-flex align-items-center gap-2 mt-2">
                <code id="trk" class="px-2 py-1 rounded bg-soft d-inline-block"><?= e($code) ?></code>
                <button class="btn btn-sm btn-link p-0" type="button" onclick="copyCode()">📋 نسخ الكود</button>
              </div>
            <?php endif; ?>
          </form>

          <!-- النتائج / التنبيهات -->
          <?php if($code==='' ): ?>
            <div class="alert alert-info border-0 shadow-sm">أدخل كود التتبّع الذي وصلك في رسالة التأكيد.</div>
          <?php elseif(!$row): ?>
            <div class="alert alert-danger border-0 shadow-sm">لم يتم العثور على طلب بهذا الكود.</div>
          <?php else: ?>
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                  <div class="mb-2 mb-md-0">
                    <div class="small text-muted mb-1">الحالة الحالية</div>
                    <div class="h5 mb-0">
                      <span class="badge rounded-pill text-bg-light"><?= e(status_label($row['status'])) ?></span>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="small text-muted">تاريخ الطلب</div>
                    <div class="fw-semibold"><?= e($row['created_at'] ?? '') ?></div>
                  </div>
                </div>

                <?php if(!empty($row['status_note'])): ?>
                  <hr class="my-3">
                  <div><strong>ملاحظة:</strong> <?= e($row['status_note']) ?></div>
                <?php endif; ?>

                <hr class="my-3">
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <div class="small text-muted">الاسم</div>
                    <div class="fw-semibold"><?= e($row['name'] ?? '') ?></div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="small text-muted">كود التتبّع</div>
                    <div class="d-flex align-items-center gap-2">
                      <code id="trk2" class="px-2 py-1 rounded bg-soft d-inline-block"><?= e($code) ?></code>
                      <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyCode('#trk2')">نسخ</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- روابط مساعدة -->
          <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
            <a href="<?= e(app_href('form.php')) ?>" class="btn btn-outline-secondary">تعبئة نموذج جديد</a>
            <a href="<?= e(app_href('')) ?>" class="btn btn-light">العودة للصفحة الرئيسية</a>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- نسخ الكود: HTTPS Clipboard API + بديل آمن لـ HTTP -->
<script>
function copyCode(selector = '#trk'){
  const el = document.querySelector(selector);
  if(!el) return alert('⚠️ لم يتم العثور على الكود.');
  const txt = (el.innerText || el.textContent || '').trim();
  const ok = () => alert('✅ تم نسخ الكود');
  const fail = () => alert('⚠️ تعذّر النسخ — انسخ يدويًا');

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(txt).then(ok).catch(()=>fallback(txt, ok, fail));
  } else {
    fallback(txt, ok, fail);
  }
}
function fallback(text, onOk, onFail){
  try{
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly','');
    ta.style.position='fixed';
    ta.style.top='-9999px';
    document.body.appendChild(ta);
    ta.select();
    const done = document.execCommand('copy');
    document.body.removeChild(ta);
    done ? onOk() : onFail();
  }catch(e){ onFail(); }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>


