<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();

$page_title = 'عزم – مساعدك الذكي';
$content = function() {
  $csrf = csrf_token();
?>
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card card-auth">
        <div class="card-header pb-0 d-flex align-items-center justify-content-between">
          <h6 class="mb-0">عزم – مساعدك الذكي</h6>
          <button id="btn-clear" class="btn btn-sm btn-outline-secondary">بدء محادثة جديدة</button>
        </div>
        <div class="card-body">
          <div id="chat-window" class="border rounded p-3 bg-light" style="height: 460px; overflow-y: auto;">
            <div class="text-muted small">ابدأ بكتابة سؤالك في الأسفل.</div>
          </div>
        </div>
        <div class="card-footer pt-0">
          <form id="chat-form" class="d-flex gap-2" method="post" onsubmit="return false;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" id="conversation_id" name="conversation_id" value="">
            <input type="text" id="message" name="message" class="form-control" placeholder="اكتب رسالتك..." autocomplete="off" required>
            <button id="btn-send" class="btn btn-primary">إرسال</button>
          </form>
          <div id="sources" class="mt-2 small"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
const wnd = document.getElementById('chat-window');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const btn = document.getElementById('btn-send');
const btnClear = document.getElementById('btn-clear');
const conv = document.getElementById('conversation_id');
const sourcesBox = document.getElementById('sources');

function appendMsg(role, text) {
  const wrap = document.createElement('div');
  wrap.className = 'mb-3';
  const bubble = document.createElement('div');
  bubble.className = role === 'user' ? 'p-2 border rounded bg-white' : 'p-2 border rounded bg-info bg-opacity-10';
  bubble.style.whiteSpace = 'pre-wrap';
  bubble.textContent = text;
  wrap.appendChild(bubble);
  wnd.appendChild(wrap);
  wnd.scrollTop = wnd.scrollHeight;
}

form.addEventListener('submit', async () => {
  const message = input.value.trim();
  if (!message) return;
  appendMsg('user', message);
  input.value = '';
  btn.disabled = true;
  sourcesBox.textContent = '';
  try {
    const fd = new FormData(form);
    const res = await fetch('api/chat.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.reply) appendMsg('assistant', data.reply);
    if (data.conversation_id) conv.value = data.conversation_id;
    if (Array.isArray(data.sources) && data.sources.length) {
      sourcesBox.textContent = 'المصادر: ' + data.sources.map(s => s.title || s.url || '').filter(Boolean).join('، ');
    }
  } catch (e) {
    appendMsg('assistant', 'حصل خطأ أثناء الإرسال.');
  } finally {
    btn.disabled = false;
    input.focus();
  }
});

btnClear.addEventListener('click', () => {
  wnd.innerHTML = '<div class="text-muted small">تم بدء محادثة جديدة.</div>';
  conv.value = '';
  sourcesBox.textContent = '';
  input.focus();
});
</script>
<?php };
require __DIR__ . '/_layout.php';
