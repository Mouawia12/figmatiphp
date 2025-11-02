<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
$page_title = 'إعدادات عزم';

$openai_key = (string)env('OPENAI_API_KEY', '');
$openai_model = (string)env('OPENAI_MODEL', 'gpt-4o-mini');
$embed_model = (string)env('OPENAI_EMBED_MODEL', 'text-embedding-3-small');
$qdrant_host = (string)env('QDRANT_HOST', 'http://localhost:6333');
$rag_collection = (string)env('RAG_COLLECTION', 'crosing_ar');

$content = function() use ($openai_key, $openai_model, $embed_model, $qdrant_host, $rag_collection) {
  $csrf = csrf_token();
?>
<div class="container-fluid py-4">
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">إعدادات OpenAI</h6>
          <button class="btn btn-sm btn-outline-primary" onclick="testOpenAI()">اختبار الاتصال</button>
        </div>
        <div class="card-body">
          <div class="mb-2"><strong>OPENAI_MODEL:</strong> <code><?= e($openai_model) ?></code></div>
          <div class="mb-2"><strong>OPENAI_EMBED_MODEL:</strong> <code><?= e($embed_model) ?></code></div>
          <div class="mb-2"><strong>OPENAI_API_KEY:</strong> <code><?= $openai_key ? '******' : 'غير مضبوط' ?></code></div>
          <div id="openaiResult" class="small text-muted"></div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">إعدادات Qdrant / RAG</h6>
          <button class="btn btn-sm btn-outline-primary" onclick="testQdrant()">اختبار الاتصال</button>
        </div>
        <div class="card-body">
          <div class="mb-2"><strong>QDRANT_HOST:</strong> <code><?= e($qdrant_host) ?></code></div>
          <div class="mb-2"><strong>RAG_COLLECTION:</strong> <code><?= e($rag_collection) ?></code></div>
          <div id="qdrantResult" class="small text-muted"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">فهرسة الموقع (زحف)</h6>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="runCrawl('full')">زحف كامل</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="runCrawl('quick')">زحف سريع (sitemap)</button>
          </div>
        </div>
        <div class="card-body">
          <form onsubmit="return false;" id="crawlForm">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Start URL</label>
                <input id="start_url" class="form-control" value="<?= e(env('PUBLIC_BASE_URL', '')) ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label">العمق (depth)</label>
                <input id="depth" class="form-control" type="number" min="0" value="3">
              </div>
              <div class="col-md-2">
                <label class="form-label">الحد الأقصى (صفحات)</label>
                <input id="max_pages" class="form-control" type="number" min="1" value="200">
              </div>
              <div class="col-md-4">
                <label class="form-label">استبعاد (Regex | مسارات)</label>
                <input id="exclude" class="form-control" placeholder="/admin|/login|/assets|/api">
              </div>
            </div>
          </form>
          <div class="mt-3 small" id="crawlResult"></div>
          <hr>
          <h6 class="mb-2">آخر التشغيلات</h6>
          <div id="crawlRuns" class="small text-muted"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
async function testOpenAI(){
  const box = document.getElementById('openaiResult'); box.textContent = 'جاري الاختبار...';
  try{
    const res = await fetch('api/test_openai.php');
    const data = await res.json();
    if(data.ok){ box.textContent = '✅ الاتصال ناجح: ' + (data.model||'') + ' | latency: ' + (data.ms||'-') + 'ms'; }
    else{ box.textContent = '❌ فشل الاتصال: ' + (data.error||''); }
  }catch(e){ box.textContent = '❌ خطأ في الاتصال'; }
}
async function testQdrant(){
  const box = document.getElementById('qdrantResult'); box.textContent = 'جاري الاختبار...';
  try{
    const res = await fetch('api/test_qdrant.php');
    const data = await res.json();
    if(data.ok){ box.textContent = '✅ الاتصال ناجح: ' + (data.version||'') + ' | ready=' + (data.ready?'yes':'no'); }
    else{ box.textContent = '❌ فشل الاتصال: ' + (data.error||''); }
  }catch(e){ box.textContent = '❌ خطأ في الاتصال'; }
}
async function refreshCrawlRuns(){
  const box = document.getElementById('crawlRuns'); box.textContent = 'جاري التحميل...';
  try{
    const res = await fetch('api/crawl_status.php');
    const data = await res.json();
    if(data.items){
      const rows = data.items.map(r => `#${r.id} · ${r.mode} · ${r.status} · pages=${r.pages} · ${r.started_at}${r.finished_at?(' → '+r.finished_at):''}`).join('\n');
      box.textContent = rows || 'لا توجد بيانات';
    } else { box.textContent = 'لا توجد بيانات'; }
  }catch(e){ box.textContent = 'تعذر التحميل'; }
}
async function runCrawl(mode){
  const out = document.getElementById('crawlResult'); out.textContent = 'جارٍ بدء الزحف...';
  const payload = {
    csrf_token: '<?= e($csrf) ?>',
    mode,
    start_url: document.getElementById('start_url').value.trim(),
    depth: parseInt(document.getElementById('depth').value||'3'),
    max_pages: parseInt(document.getElementById('max_pages').value||'200'),
    exclude: document.getElementById('exclude').value.trim(),
  };
  try{
    const res = await fetch('api/crawl_run.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await res.json();
    if(data.ok){ out.textContent = '✅ تم بدء الزحف. رقم التشغيل: #'+data.run_id; refreshCrawlRuns(); }
    else { out.textContent = '❌ فشل البدء: ' + (data.error||''); }
  }catch(e){ out.textContent = '❌ خطأ في الاتصال'; }
}
document.addEventListener('DOMContentLoaded', refreshCrawlRuns);
</script>
<?php };
require __DIR__ . '/_layout.php';
