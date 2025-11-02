<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();

// كشف الروابط تلقائيًا
$apiPath = ltrim(app_href('api.php'), '/');
$dlPath = ltrim(app_href('download.php'), '/');
$apiUrl = public_url($apiPath);
$dlUrl  = public_url($dlPath);

// اسم الموقع من الإعدادات (اختياري)
$siteName = 'واجهة API';
try {
  $dbs = pdo_open($config->db_forms);
  $dbs->exec("CREATE TABLE IF NOT EXISTS app_settings (k TEXT PRIMARY KEY, v TEXT)");
  $row = $dbs->prepare("SELECT v FROM app_settings WHERE k='site_name'");
  $row->execute();
  if ($v = $row->fetchColumn()) $siteName = $v . ' — واجهة API';
} catch (Throwable $e) { /* اختياري */ }

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($siteName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href('favicon-16x16.png')) ?>">

<!-- Bootstrap RTL + التصميم الأساسي المفصول -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">

<script>
// نسخ موثوق: يستخدم Clipboard API إن توفر وفي سياق آمن، وإلا يسقط على آلية textarea + execCommand
function copyText(id){
  try{
    const el = document.getElementById(id);
    if (!el) return alert("️ لم يتم العثور على المقطع.");
    const txt = (el.innerText || el.textContent || "").trim();

    const notifyOk = () => alert(" تم النسخ");
    const notifyFail = () => alert("️ تعذّر النسخ — انسخ يدويًا.");

    // Clipboard API في سياق آمن (HTTPS أو localhost)
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(txt).then(notifyOk).catch(()=>{
        fallbackCopy(txt, notifyOk, notifyFail);
      });
    } else {
      // سياق غير آمن (HTTP) أو Clipboard API غير متاح
      fallbackCopy(txt, notifyOk, notifyFail);
    }
  } catch(e){
    alert("️ حدث خطأ غير متوقع أثناء النسخ.");
  }
}

function fallbackCopy(text, onSuccess, onFail){
  const ta = document.createElement("textarea");
  ta.value = text;
  ta.setAttribute("readonly", "");
  ta.style.position = "fixed";
  ta.style.top = "-9999px";
  ta.style.opacity = "0";
  document.body.appendChild(ta);
  ta.select();
  try{
    const ok = document.execCommand("copy");
    ok ? onSuccess() : onFail();
  } catch(e){
    onFail();
  } finally{
    document.body.removeChild(ta);
  }
}
</script>
</head>
<body class="app-bg">

<!-- الهيدر الموحّد -->
<header class="shadow-sm bg-white sticky-top">
  <nav class="navbar container navbar-expand-lg py-3">
    <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('')) ?>"><?= e($siteName) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMenu" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#about')) ?>">عن الخدمة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('index.php#faq')) ?>">الأسئلة الشائعة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(app_href('api_docs.php')) ?>">API</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-lg-3 mt-2 mt-lg-0" href="<?= e(app_href('form.php')) ?>">تعبئة النموذج</a></li>
      </ul>
    </div>
  </nav>
</header>

<!-- Hero -->
<section class="hero-section">
  <div class="container">
    <h1 class="h3 mb-1">دليل التكامل — <?= e($siteName) ?></h1>
    <p class="text-muted mb-0">كل ما تحتاجه لسحب الطلبات والمرفقات بطريقة بسيطة وآمنة.</p>
  </div>
</section>

<main class="container pb-5">
  <!-- فهرس قصير -->
  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card p-3 border-0 shadow-sm">
        <h5 class="mb-2">فهرس سريع</h5>
        <ul class="list-unstyled text-muted mb-0">
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#start">قبل أن تبدأ</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#auth">المصادقة</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#endpoints">المسارات</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#incremental">سحب الجديد فقط</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#examples">أمثلة جاهزة</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#errors">أخطاء شائعة</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#attachments">المرفقات</a></li>
          <li class="mb-1"><a class="text-reset text-decoration-none" href="#best">أفضل الممارسات</a></li>
        </ul>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card p-3 border-0 shadow-sm" id="start">
        <div class="alert alert-info mb-3" role="alert">
          <strong> قبل أن تبدأ:</strong>
          <ul class="mb-0">
            <li>احصل على <strong>مفتاح API</strong> من مسؤول النظام لدينا.</li>
            <li>نقطة الدخول الثابتة: <code><?= e($apiUrl) ?></code></li>
            <li>الاستجابات دائمًا بصيغة JSON وبهيكل موحّد: <code>{ ok, data, meta }</code></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- المصادقة -->
  <section class="section mb-4" id="auth">
    <div class="card p-3 border-0 shadow-sm">
      <h3>المصادقة</h3>
      <p class="text-muted mb-2">
        أرسلوا التوكن في ترويسة <code>Authorization: Bearer &lt;TOKEN&gt;</code>. (يمكن تمريره كـ<code>?token=…</code> للاختبار فقط).
      </p>
      <div class="row g-3">
        <div class="col-md-6">
          <h5 class="mb-2">cURL</h5>
<pre id="c1"><code>curl -s -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  "<?= e($apiUrl) ?>?what=requests&amp;limit=5"</code></pre>
          <button class="btn btn-link p-0 mt-1" onclick="copyText('c1')" type="button">📋 نسخ المثال</button>
        </div>
        <div class="col-md-6">
          <h5 class="mb-2">PowerShell</h5>
<pre id="c2"><code>$token   = "YOUR_TOKEN_HERE"
$headers = @{ Authorization = "Bearer $token"; "User-Agent" = "Mozilla/5.0" }
Invoke-RestMethod -Uri "<?= e($apiUrl) ?>?what=requests&amp;limit=5" -Headers $headers</code></pre>
          <button class="btn btn-link p-0 mt-1" onclick="copyText('c2')" type="button">📋 نسخ المثال</button>
        </div>
      </div>
      <div class="alert alert-warning mt-3" role="alert">
        <strong>ملاحظة:</strong> خلف Cloudflare تأكدوا من تمرير الترويسة إلى PHP لدينا (تم تفعيل ذلك في الخادم).
      </div>
    </div>
  </section>

  <!-- المسارات -->
  <section class="section mb-4" id="endpoints">
    <div class="card p-3 border-0 shadow-sm">
      <h3>🔗 المسارات (Endpoints)</h3>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>what=</th><th>الوصف</th><th>أهم الباراميترات</th></tr></thead>
          <tbody>
            <tr>
              <td><span class="badge rounded-pill text-bg-light">requests</span></td>
              <td>قائمة الطلبات (تدعم التزايدي)</td>
              <td><code>limit</code>, <code>page</code>/<code>offset</code>, <code>form_id</code>, <code>q</code>, <code>since_id</code>, <code>since_ts</code>, <code>include_updates=1</code></td>
            </tr>
            <tr>
              <td><span class="badge rounded-pill text-bg-light">request</span></td>
              <td>تفاصيل طلب واحد</td>
              <td><code>id</code></td>
            </tr>
            <tr>
              <td><span class="badge rounded-pill text-bg-light">forms</span></td>
              <td>قائمة النماذج</td>
              <td><code>limit</code>, <code>page</code></td>
            </tr>
            <tr>
              <td><span class="badge rounded-pill text-bg-light">files</span></td>
              <td>قائمة المرفقات وروابط تنزيلها</td>
              <td>—</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-muted mb-0">ترتيب النتائج يكون <strong>تصاعديًا</strong> عند استخدام <code>since_id</code>/<code>since_ts</code> لتسهيل الالتقاط.</p>
    </div>
  </section>

  <!-- التزايدي -->
  <section class="section mb-4" id="incremental">
    <div class="card p-3 border-0 shadow-sm">
      <div class="alert alert-success mb-3" role="alert">
        <h3 class="h5 mb-2">سحب “الجديد فقط” (Incremental)</h3>
        <p class="mb-2">أفضل طريقة للسحب الدوري بدون تكرار:</p>
        <ol class="mb-2">
          <li>أول مرة: نادوا <code>what=requests&amp;limit=200</code> وخزّنوا <code>meta.next_since_id</code>.</li>
          <li>لاحقًا: نادوا <code>what=requests&amp;since_id=&lt;آخر_next_since_id&gt;</code> فقط.</li>
        </ol>
<pre id="c3"><code># أول مزامنة
GET <?= e($apiUrl) ?>?what=requests&amp;limit=200
# خزّنوا meta.next_since_id

# لاحقًا (تزايدي)
GET <?= e($apiUrl) ?>?what=requests&amp;since_id=LAST_NEXT_SINCE_ID&amp;limit=200</code></pre>
        <button class="btn btn-link p-0 mt-1" onclick="copyText('c3')" type="button">📋 نسخ المثال</button>
        <hr class="my-3">
        <p class="mb-1"><strong>تغييرات الحالة؟</strong> استخدموا وقت المزامنة:</p>
<pre id="c4"><code>GET <?= e($apiUrl) ?>?what=requests&amp;since_ts=2025-10-07%2009:00:00&amp;include_updates=1&amp;limit=200</code></pre>
        <button class="btn btn-link p-0 mt-1" onclick="copyText('c4')" type="button">📋 نسخ المثال</button>
      </div>
    </div>
  </section>

  <!-- أمثلة -->
  <section class="section mb-4" id="examples">
    <div class="card p-3 border-0 shadow-sm">
      <h3>أمثلة سريعة</h3>
      <div class="row g-3">
        <div class="col-md-6">
          <h5 class="mb-1">JavaScript (fetch)</h5>
<pre id="c5"><code>const token = "YOUR_TOKEN_HERE";
fetch("<?= e($apiUrl) ?>?what=requests&amp;limit=20", {
  headers: { "Authorization": "Bearer " + token }
})
.then(r => r.json())
.then(({ok, data, meta}) => { if(!ok) throw new Error("API error"); console.log(data, meta); });</code></pre>
          <button class="btn btn-link p-0 mt-1" onclick="copyText('c5')" type="button"> نسخ المثال</button>
        </div>
        <div class="col-md-6">
          <h5 class="mb-1">PowerShell</h5>
<pre id="c6"><code>$token   = "YOUR_TOKEN_HERE"
$headers = @{ Authorization = "Bearer $token"; "User-Agent" = "Mozilla/5.0" }
$r = Invoke-RestMethod -Uri "<?= e($apiUrl) ?>?what=requests&amp;limit=20" -Headers $headers
$r.data | Format-Table id,form_id,name,email,status,created_at</code></pre>
          <button class="btn btn-link p-0 mt-1" onclick="copyText('c6')" type="button"> نسخ المثال</button>
        </div>
      </div>
    </div>
  </section>

  <!-- المرفقات -->
  <section class="section mb-4" id="attachments">
    <div class="card p-3 border-0 shadow-sm">
      <h3>تنزيل المرفقات</h3>
      <p class="text-muted">حمّلوا الملف بنفس ترويسة المصادقة:</p>
<pre id="c7"><code># PowerShell
$f = "972725494846.pdf"
Invoke-WebRequest -Uri "<?= e($dlUrl) ?>?file=$f" -Headers $headers -OutFile $f

# cURL
curl -OJL -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  "<?= e($dlUrl) ?>?file=972725494846.pdf"</code></pre>
      <button class="btn btn-link p-0 mt-1" onclick="copyText('c7')" type="button"> نسخ المثال</button>
    </div>
  </section>

  <!-- الأخطاء -->
  <section class="section mb-4" id="errors">
    <div class="card p-3 border-0 shadow-sm">
      <h3>أخطاء شائعة وكيف نحلّها</h3>
      <ul class="mb-2">
        <li><code>token_required</code>: تأكد من إرسال الترويسة أو استخدم <code>?token=</code> للاختبار.</li>
        <li><code>invalid_token</code>: المفتاح غير صالح — اطلبوا مفتاحًا جديدًا.</li>
        <li><code>not_found</code>: المعرّف غير موجود.</li>
        <li><code>server_error</code>: خطأ غير متوقع — جرّبوا لاحقًا أو تواصلوا معنا.</li>
      </ul>
      <p class="text-muted mb-0">الردود تأخذ دائمًا شكل <code>{ ok, data, meta }</code> عند النجاح و <code>{ ok:0, error }</code> عند الخطأ.</p>
    </div>
  </section>

  <!-- أفضل الممارسات -->
  <section class="section mb-4" id="best">
    <div class="card p-3 border-0 shadow-sm">
      <h3>أفضل الممارسات</h3>
      <ul class="mb-0">
        <li>اعتمدوا <code>since_id</code> وخزّنوا <code>meta.next_since_id</code> كل دورة.</li>
        <li>للتغييرات اللاحقة على الحالات استخدموا <code>since_ts</code> مع <code>include_updates=1</code>.</li>
        <li>حددوا <code>limit</code> معقول (100–200) وتجنبوا طلبات عملاقة.</li>
        <li>دوّروا المفاتيح دوريًا ويمكن تقييدها بـ IP عند الحاجة.</li>
      </ul>
    </div>
  </section>

  <footer class="pt-2 text-muted">
    <div>نقطة الدخول: <code><?= e($apiUrl) ?></code></div>
    <div>التحميل: <code><?= e($dlUrl) ?></code></div>
  </footer>
</main>

<!-- الفوتر الموحّد -->
<footer class="footer mt-auto pt-5 pb-4">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
      <span>© 2025 عزم الإنجاز. جميع الحقوق محفوظة</span>
      <a class="link-secondary" href="#">الرجوع للأعلى</a>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
</body>
</html>
