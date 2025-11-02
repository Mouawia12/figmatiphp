<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
$page_title = 'تصدير/استيراد – عزم';
$content = function(){ $csrf = csrf_token(); ?>
<div class="container-fluid py-4">
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">تصدير الأسئلة الأكثر تكرارًا</h6>
          <button class="btn btn-sm btn-outline-secondary" onclick="exportQuestions()">تنزيل الملف</button>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-2">بنقرة واحدة تحصل على ملف JSON فيه الأسئلة وإحصاءاتها.</p>
          <pre id="exportPreview" class="bg-light p-2 rounded small" style="max-height:260px;overflow:auto"></pre>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header pb-0"><h6 class="mb-0">استيراد معرفة جاهزة (JSON)</h6></div>
        <div class="card-body">
          <p class="text-muted small mb-2">انسخ ملف JSON بالمقاطع هنا ثم اضغط استيراد. مثال بسيط:</p>
          <pre class="bg-light p-2 rounded small">{
  "chunks": ["نص مقطع 1", "نص مقطع 2"],
  "metas":  [{"lang":"ar","category":"policy","title":"سياسة"}, {"lang":"ar","category":"payment","title":"الدفع لاحقًا"}]
}</pre>
          <textarea id="importJson" class="form-control" rows="10" placeholder='الصق JSON هنا'></textarea>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" onclick="importChunks()">استيراد الآن</button>
            <a href="azam_knowledge.php" class="btn btn-outline-secondary">إدخال سريع (نصوص)</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
async function exportQuestions(){
  try{
    const res = await fetch('api/export_questions.php');
    const data = await res.json();
    document.getElementById('exportPreview').textContent = JSON.stringify(data, null, 2);
    const blob = new Blob([JSON.stringify(data, null, 2)], {type:'application/json'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'chat_questions.json'; a.click();
    URL.revokeObjectURL(url);
  }catch(e){ alert('تعذر التصدير'); }
}
async function importChunks(){
  const txt = document.getElementById('importJson').value.trim();
  if(!txt){ alert('ألصق JSON صالح'); return; }
  let payload; try{ payload = JSON.parse(txt); }catch(e){ alert('JSON غير صالح'); return; }
  payload.csrf_token = '<?= e($csrf) ?>';
  try{
    const res = await fetch('api/import_chunks.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await res.json();
    if(data.ok){ alert('تم إدخال '+(data.count||0)+' مقطع'); }
    else{ alert('فشل الاستيراد: '+(data.error||'خطأ')); }
  }catch(e){ alert('خطأ في الاتصال'); }
}
</script>
<?php };
require __DIR__ . '/_layout.php';
