<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
$page_title = 'مصادر المعرفة – إدخال سريع';
$content = function() {
  $csrf = csrf_token();
?>
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header pb-0">
          <h6 class="mb-0">إدخال نصوص مباشرة (MVP)</h6>
          <small class="text-muted">قسّم المحتوى إلى مقاطع قصيرة واضحة (سطر لكل مقطع أو افصل بين المقاطع بسطر فارغ)</small>
        </div>
        <div class="card-body">
          <form id="upsertForm" onsubmit="return false;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="mb-3">
              <label class="form-label">المقاطع (Chunks)</label>
              <textarea id="chunks" class="form-control" rows="10" placeholder="اكتب كل مقطع في سطر منفصل"></textarea>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">اللغة</label>
                <input id="lang" class="form-control" value="ar">
              </div>
              <div class="col-md-4">
                <label class="form-label">التصنيف</label>
                <input id="category" class="form-control" placeholder="policy/payment/faq...">
              </div>
              <div class="col-md-4">
                <label class="form-label">عنوان عام</label>
                <input id="title" class="form-control" placeholder="عنوان المصدر">
              </div>
            </div>
            <div class="mt-3 d-flex gap-2">
              <button id="btnUpsert" class="btn btn-primary">إدخال إلى RAG</button>
              <a href="azam_queue.php" class="btn btn-outline-secondary">الذهاب إلى قائمة الانتظار</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header pb-0"><h6 class="mb-0">ملاحظات بسيطة</h6></div>
        <div class="card-body">
          <ul class="mb-0">
            <li>هذه طريقة سريعة لتغذية "عزم" بالمعلومة وتجربتها فورًا.</li>
            <li>قسّم السياسة أو الصفحة إلى مقاطع قصيرة وواضحة ليسهل استرجاعها.</li>
            <li>بعد الإدخال، جرّب سؤالًا في صفحة "عزم – المساعد" وتأكد أن "المصدر" يظهر أسفل الرد.</li>
            <li>للعمل الفعلي مع الفريق، الأفضل تمرير المواد عبر "قائمة الانتظار" للمراجعة والاعتماد.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
async function upsert(){
  const csrf = '<?= e($csrf) ?>';
  const raw = document.getElementById('chunks').value.trim();
  if (!raw){ alert('أدخل مقاطع نصية'); return; }
  const lang = document.getElementById('lang').value.trim()||'ar';
  const category = document.getElementById('category').value.trim()||'misc';
  const title = document.getElementById('title').value.trim()||'مصدر';
  // split by lines and remove empties
  const chunks = raw.split(/\n+/).map(s=>s.trim()).filter(Boolean);
  const metas = chunks.map(()=>({lang, category, title}));
  try{
    const res = await fetch('api/rag_upsert.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ csrf_token: csrf, chunks, metas })
    });
    const data = await res.json();
    if (data.ok){ alert('تم إدخال '+(data.count||chunks.length)+' مقطع'); }
    else { alert('فشل الإدخال: ' + (data.error||'خطأ')); }
  }catch(e){ alert('خطأ في الاتصال'); }
}

document.getElementById('btnUpsert').addEventListener('click', upsert);
</script>
<?php };
require __DIR__ . '/_layout.php';
