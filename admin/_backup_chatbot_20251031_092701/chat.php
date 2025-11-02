<?php
declare(strict_types=1);
/**
 * admin/chat.php
 * لوحة تحكم الدردشة – تظهر ردود البوت وتدير حذف/أرشفة الدردشات السابقة
 */

require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/init_chat_db.php';

session_start();
if (empty($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ' . app_href('login.php'));
    exit;
}

// تعريف معلومات الموظّف للواجهة
$STAFF = [
    'id'   => (string)($_SESSION['user']['id'] ?? ''),
    'name' => (string)($_SESSION['user']['name'] ?? 'موظف'),
    'role' => (string)($_SESSION['user']['role'] ?? 'admin'),
];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf_token'];

$config    = cfg();
$siteTitle = $config->site_title ?? 'عزم الإنجاز';

init_chat_database();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title><?= e($siteTitle) ?> – لوحة الدردشة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($CSRF) ?>">
    <meta name="staff" content='<?= e(json_encode($STAFF, JSON_UNESCAPED_UNICODE)) ?>'>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251028-3')) ?>">
    <style>
        :root{ --brand:#667eea; --ring:#eef2ff; --bot:#10b981; --botBorder:#059669; }
        body.app-bg{background:#f3f4f6}
        .chat-container{display:flex;height:calc(100vh - 120px);gap:20px;margin-top:20px}
        .conversations-panel{width:300px;border-right:1px solid #e5e7eb;overflow-y:auto;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
        .conversation-item{padding:12px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s}
        .conversation-item:hover{background:#f8fafc}
        .conversation-item.active{background:#eef2ff;border-inline-start:4px solid var(--brand)}
        .conv-name{font-weight:600;font-size:14px;color:#111827}
        .conv-preview{font-size:12px;color:#6b7280;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .conv-time{font-size:11px;color:#9ca3af;margin-top:4px}
        .chat-main{flex:1;display:flex;flex-direction:column;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
        .chat-header{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:.5rem}
        .chat-messages{flex:1;overflow-y:auto;padding:20px;background:#f7f7f7}
        .message{margin-bottom:12px;display:flex;animation:fadeIn .2s ease}
        @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
        .message.customer{justify-content:flex-start}
        .message.admin{justify-content:flex-end}
        .message.bot{justify-content:flex-start}
        .message-content{max-width:70%;padding:10px 12px;border-radius:10px;font-size:14px;line-height:1.45;word-wrap:break-word;position:relative}
        .message.customer .message-content{background:#fff;color:#111827;border:1px solid #e5e7eb}
        .message.admin .message-content{background:var(--brand);color:#fff}
        .message.bot .message-content{background:var(--bot);color:#fff;border:1px solid var(--botBorder)}
        .stamp{display:block;font-size:11px;opacity:.75;margin-top:4px}
        .chat-input-area{padding:12px;border-top:1px solid #e5e7eb;background:#fff}
        .chat-form{display:flex;gap:10px}
        .chat-input{flex:1;border:1px solid #d1d5db;border-radius:8px;padding:10px 12px;font-size:14px}
        .chat-input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(102,126,234,.15)}
        .chat-send{background:var(--brand);color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:600}
        .chat-send[disabled]{opacity:.7;cursor:default}
        .alerts-panel{width:320px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.05);display:flex;flex-direction:column;max-height:calc(100vh - 120px)}
        .alerts-header{padding:15px;border-bottom:1px solid #e5e7eb;font-weight:600;display:flex;justify-content:space-between;align-items:center;gap:8px}
        .alert-badge{background:#ef4444;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
        .alerts-list{flex:1;overflow-y:auto}
        .alert-item{padding:12px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s}
        .alert-item:hover{background:#f8fafc}
        .alert-item.unread{background:#fff7ed;border-inline-start:3px solid #fb923c}
        .alert-title{font-weight:600;font-size:13px;color:#111827}
        .alert-message{font-size:12px;color:#6b7280;margin-top:4px}
        .alert-time{font-size:11px;color:#9ca3af;margin-top:4px}
        .empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#9ca3af;text-align:center}
        .empty-icon{font-size:44px;margin-bottom:8px}
        .kbd{border:1px solid #e5e7eb;background:#f8fafc;border-radius:6px;padding:2px 6px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
        .toolbar .btn{border:1px solid #e5e7eb}
    </style>
</head>
<body class="app-bg">

<header class="shadow-sm bg-white sticky-top">
    <nav class="navbar container-fluid navbar-expand-lg py-3">
        <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('admin/index.php')) ?>">
            <?= e($siteTitle) ?> – الدردشة الحية
        </a>
        <div class="ms-auto d-flex gap-2 toolbar">
            <button class="btn btn-sm btn-outline-secondary" onclick="createNewConversationPrompt()">➕ محادثة</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="scrollToBottom()">⬇️ آخر الرسائل</button>
            <button class="btn btn-sm btn-outline-warning"  onclick="clearCurrentHistory()">🧹 تفريغ سجل الحالية</button>
            <button class="btn btn-sm btn-outline-danger"   onclick="purgeOldConversations()">🧨 حذف الدردشات السابقة</button>
            <span class="d-none d-md-inline text-muted">إرسال: <span class="kbd">Enter</span> • سطر جديد: <span class="kbd">Shift+Enter</span></span>
        </div>
    </nav>
</header>

<main class="container-fluid p-4">
    <div class="chat-container">
        <!-- قائمة المحادثات -->
        <aside class="conversations-panel">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0">المحادثات النشطة</h6>
                    <small class="text-muted">اختر محادثة للتحدث</small>
                </div>
                <button class="btn btn-sm btn-primary ms-2" onclick="createNewConversationPrompt()">➕</button>
            </div>
            <div id="conversations-list"></div>
        </aside>

        <!-- نافذة الدردشة -->
        <section class="chat-main">
            <div id="chat-empty" class="empty-state">
                <div class="empty-icon">💬</div>
                <p class="mb-1">ابدأ بإنشاء محادثة أو اختر واحدة من اليسار</p>
                <button class="btn btn-sm btn-primary mt-2" onclick="createNewConversationPrompt()">ابدأ محادثة</button>
            </div>

            <div id="chat-window" style="display:none; flex:1; display:flex; flex-direction:column;">
                <div class="chat-header">
                    <div>
                        <h4 id="chat-customer-name" class="mb-0">العميل</h4>
                        <div class="customer-info">
                            <span id="chat-customer-email" class="text-muted small"></span>
                            <span id="chat-customer-steps" class="text-muted small ms-2"></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="showCustomerAnalysis()">📊 تحليل</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="closeConversation()">إغلاق</button>
                    </div>
                </div>

                <div id="chat-messages" class="chat-messages"></div>

                <div class="chat-input-area">
                    <form id="chat-form" class="chat-form" onsubmit="sendAdminMessage(event)" novalidate>
                        <input type="text" id="admin-message-input" class="chat-input" placeholder="اكتب رسالتك..." autocomplete="off" spellcheck="false">
                        <button type="submit" class="chat-send" id="send-btn">إرسال</button>
                    </form>
                    <div id="send-hint" class="small text-muted mt-1" style="display:none">يتم الإرسال…</div>
                </div>
            </div>
        </section>

        <!-- لوحة التنبيهات -->
        <aside class="alerts-panel">
            <div class="alerts-header">
                <span>التنبيهات والإحصائيات</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="alert-badge" id="unread-count">0</span>
                </div>
            </div>
            <div class="p-3 border-bottom">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-center p-2 border rounded" style="background:#eef2ff">
                            <div class="fw-bold" id="stat-conversations">0</div>
                            <div class="small">محادثات</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 border rounded" style="background:#eef2ff">
                            <div class="fw-bold" id="stat-messages">0</div>
                            <div class="small">رسائل</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-3 py-2 fw-semibold border-bottom">التنبيهات الحديثة</div>
            <div id="alerts-list" class="alerts-list"></div>
        </aside>
    </div>
</main>

<!-- Modal للتحليل -->
<div class="modal fade" id="analysisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحليل سلوك العميل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="analysis-content">
                <div class="text-muted">(تخصّص لاحقًا من API)</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* === إعدادات عامة === */
const API_URL = '<?= e(app_href('../api_chat.php')) ?>';
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const STAFF   = JSON.parse(document.querySelector('meta[name="staff"]')?.getAttribute('content')||'{}');

console.log('🔧 إعدادات API:');
console.log('  API_URL:', API_URL);
console.log('  CSRF:', CSRF ? '✅ موجود' : '❌ غير موجود');
console.log('  STAFF:', STAFF);

let currentConversationId = null;
let pollConvInterval = null;
let pollMsgInterval  = null;
let lastMessageId    = null; // لتعقّب ظهور رسائل جديدة (خاصة ردّ البوت)

/* === أدوات مساعدة === */
const escapeHTML = (s='') => String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');

function pickText(msg){
    return (msg.message ?? msg.content ?? msg.text ?? '');
}
function pickTime(msg){
    return (msg.created_at ?? msg.createdAt ?? msg.time ?? null);
}

function formatTime(dateString) {
    const d = new Date(dateString);
    if (isNaN(d)) return '';
    return d.toLocaleDateString('ar-SA') + ' ' + d.toLocaleTimeString('ar-SA', {hour:'2-digit', minute:'2-digit'});
}

function scrollToBottom() {
    const c = document.getElementById('chat-messages');
    if (c) c.scrollTop = c.scrollHeight;
}

async function apiGet(qs) {
    const url = `${API_URL}?${qs}&_=${Date.now()}`;
    const res = await fetch(url, {cache:'no-store', credentials:'same-origin'});
    if (!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
}
async function apiPost(bodyObj) {
    const body = new URLSearchParams(bodyObj);
    body.append('csrf_token', CSRF);
    const res = await fetch(API_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        credentials:'same-origin',
        body: body.toString()
    });
    
    if (!res.ok) {
        const text = await res.text();
        console.error('❌ HTTP Error:', res.status, text);
        throw new Error('HTTP '+res.status);
    }
    
    const text = await res.text();
    if (!text) {
        console.error('❌ الخادم لم يرجع أي بيانات');
        throw new Error('الخادم لم يرجع أي بيانات');
    }
    
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error('❌ خطأ في parsing JSON:', e.message);
        console.error('📝 الرد من الخادم:', text.substring(0, 200));
        throw new Error('خطأ في الاستجابة: ' + text.substring(0, 100));
    }
}

/* === المحادثات === */
async function loadConversations() {
    try {
        const data = await apiGet('action=get_conversations');
        if (data?.success) {
            displayConversations(Array.isArray(data.data) ? data.data : []);
            document.getElementById('stat-conversations').textContent = data.meta?.count ?? data.data.length ?? 0;
            document.getElementById('stat-messages').textContent      = data.meta?.messages ?? '—';
        }
    } catch (e) { console.error('خطأ في جلب المحادثات:', e); }
}

function displayConversations(conversations) {
    const list = document.getElementById('conversations-list');
    if (!conversations.length) {
        list.innerHTML = '<div class="p-4 text-center text-muted">لا توجد محادثات</div>';
        return;
    }
    list.innerHTML = conversations.map(conv => {
        const active = (Number(conv.id) === Number(currentConversationId)) ? 'active' : '';
        return `
        <div class="conversation-item ${active}"
             data-id="${conv.id}"
             onclick="selectConversation(${Number(conv.id)}, '${escapeHTML(conv.customer_name||'عميل')}', '${escapeHTML(conv.customer_email||'')}')">
            <div class="conv-name">${escapeHTML(conv.customer_name || 'عميل')}</div>
            <div class="conv-preview">${escapeHTML(conv.last_message || 'لا توجد رسائل')}</div>
            <div class="conv-time">${formatTime(conv.updated_at || conv.created_at || new Date().toISOString())}</div>
        </div>`;
    }).join('');
}

function highlightActiveConversation() {
    document.querySelectorAll('.conversation-item').forEach(el=>{
        el.classList.toggle('active', Number(el.dataset.id) === Number(currentConversationId));
    });
}

/* إنشاء محادثة (Prompt) */
async function createNewConversationPrompt() {
    const name  = prompt('اسم العميل:');
    if (!name) return;
    const email = prompt('بريد العميل (اختياري):') || '';
    await createNewConversation({name, email, customer_id: 'admin_'+Date.now()});
}

/* إنشاء محادثة عبر API (بدون Prompt) */
async function createNewConversation({name='عميل جديد', email='customer@example.com', customer_id='admin_auto_'+Date.now()} = {}) {
    try {
        const data = await apiPost({
            action:'start_conversation',
            customer_name:name,
            customer_email:email,
            customer_id
        });
        if (data?.success && data.data?.conversation_id) {
            selectConversation(Number(data.data.conversation_id), name, email);
            await loadConversations();
            return Number(data.data.conversation_id);
        } else {
            throw new Error(data?.message || 'فشل إنشاء المحادثة');
        }
    } catch (e) {
        console.error('خطأ في إنشاء المحادثة:', e);
        alert('خطأ في إنشاء المحادثة: '+e.message);
        return null;
    }
}

/* تأكيد وجود محادثة قبل الإرسال */
async function ensureConversationExists() {
    if (currentConversationId) return currentConversationId;
    return await createNewConversation(); // إنشاء افتراضي
}

/* اختيار محادثة */
function selectConversation(convId, name, email) {
    currentConversationId = Number(convId);
    lastMessageId = null; // إعادة ضبط
    document.getElementById('chat-empty').style.display = 'none';
    document.getElementById('chat-window').style.display = 'flex';
    document.getElementById('chat-customer-name').textContent  = name || 'عميل';
    document.getElementById('chat-customer-email').textContent = email || 'بدون بريد';

    highlightActiveConversation();
    loadMessages();

    clearInterval(pollMsgInterval);
    pollMsgInterval = setInterval(loadMessages, 1000);

    autoGreetStaffOncePerDay();

    setTimeout(()=> document.getElementById('admin-message-input')?.focus(), 50);
}

/* إغلاق المحادثة */
async function closeConversation() {
    if (!currentConversationId) return;
    currentConversationId = null;
    document.getElementById('chat-window').style.display = 'none';
    document.getElementById('chat-empty').style.display  = 'flex';
    highlightActiveConversation();
}

/* === الرسائل === */
function normalizeSenderType(msg){
    const raw = String(msg.sender_type||'customer').toLowerCase();
    const sid = String(msg.sender_id||'').toLowerCase();
    if (raw === 'admin') return (sid==='bot' || sid==='assistant' || sid==='ai') ? 'bot' : 'admin';
    if (raw === 'customer' || raw === 'user') return 'customer';
    if (raw === 'bot' || raw === 'assistant' || raw === 'ai' || raw === 'system') return 'bot';
    return 'customer';
}

async function loadMessages() {
    if (!currentConversationId) return;
    try {
        const data = await apiGet(`action=get_messages&conversation_id=${currentConversationId}`);
        if (data?.success) {
            const messages = Array.isArray(data.data) ? data.data : [];
            displayMessages(messages);
        }
    } catch (e) { console.error('خطأ في جلب الرسائل:', e); }
}

function displayMessages(messages) {
    const c = document.getElementById('chat-messages');
    if (!Array.isArray(messages)) return;

    // تعقّب آخر ID
    const last = messages[messages.length - 1];
    if (last && (last.id ?? last.message_id ?? null) !== null) {
        lastMessageId = (last.id ?? last.message_id);
    }

    c.innerHTML = messages.map(msg => {
        const type  = normalizeSenderType(msg);
        const text  = escapeHTML(pickText(msg));
        const t     = pickTime(msg);
        const stamp = t ? `<span class="stamp">${formatTime(t)}</span>` : '';
        return `
        <div class="message ${type}">
            <div class="message-content">${text}${stamp}</div>
        </div>`;
    }).join('');
    scrollToBottom();
}

/* انتظار ظهور ردّ جديد بعد رسالتك (حتى 4 ثواني) */
async function waitForNewMessage(prevLastId, tries=6){
    if (!currentConversationId) {
        console.warn('⚠️ لا توجد محادثة نشطة');
        return false;
    }
    
    for (let i=0;i<tries;i++){
        await new Promise(r=>setTimeout(r, 600));
        try{
            const data = await apiGet(`action=get_messages&conversation_id=${currentConversationId}`);
            if (data?.success) {
                const messages = Array.isArray(data.data) ? data.data : [];
                console.log(`🔄 محاولة ${i+1}/${tries}: ${messages.length} رسالة`);
                const last = messages[messages.length - 1];
                const lastId = last ? (last.id ?? last.message_id ?? null) : null;
                if (lastId !== null && String(lastId) !== String(prevLastId)) {
                    console.log('✅ رسالة جديدة وجدت!');
                    displayMessages(messages);
                    return true;
                }
            }
        }catch(e){
            console.error('❌ خطأ في انتظار الرسالة:', e);
        }
    }
    console.warn('⚠️ لم يتم العثور على رسالة جديدة');
    return false;
}

async function sendAdminMessage(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    
    console.log('📤 محاولة إرسال رسالة...');
    
    const ensuredId = await ensureConversationExists();
    if (!ensuredId) {
        console.error('❌ فشل إنشاء/الحصول على معرف المحادثة');
        return false;
    }
    
    console.log('✅ معرف المحادثة:', ensuredId);

    const input = document.getElementById('admin-message-input');
    if (!input) {
        console.error('❌ حقل الإدخال غير موجود');
        return false;
    }

    const message = input.value.trim();
    if (!message) {
        console.warn('⚠️ الرسالة فارغة');
        return false;
    }
    
    console.log('📝 الرسالة:', message.substring(0, 50));

    const btn  = document.getElementById('send-btn');
    const hint = document.getElementById('send-hint');
    btn.disabled = true; btn.textContent = 'جاري الإرسال…';
    hint.style.display = 'block';

    // احفظ آخر رسالة قبل الإرسال
    const prevId = lastMessageId;

    // Optimistic UI
    const c = document.getElementById('chat-messages');
    const tempId = 'tmp_'+Date.now();
    c.insertAdjacentHTML('beforeend', `
        <div class="message admin" id="${tempId}">
            <div class="message-content">${escapeHTML(message)}<span class="stamp">${formatTime(new Date().toISOString())}</span></div>
        </div>
    `);
    scrollToBottom();

    try {
        console.log('📤 إرسال الطلب إلى API...');
        const data = await apiPost({
            action:'send_message',
            conversation_id: ensuredId,
            message: message,
            sender_type:'admin',
            sender_id: STAFF?.id || 'admin'
        });
        
        console.log('✅ رد من API:', data);
        
        if (data?.success) {
            console.log('✅ الرسالة أُرسلت بنجاح!');
            input.value = '';
            // انتظر ردّ البوت ثم حدّث
            console.log('⏳ في انتظار رد البوت...');
            await waitForNewMessage(prevId);
            console.log('🔄 تحديث المحادثات...');
            await loadConversations();
            console.log('✅ تم التحديث!');
        } else {
            console.error('❌ فشل الإرسال:', data?.message);
            const bubble = document.getElementById(tempId);
            if (bubble) bubble.querySelector('.message-content').innerHTML =
                `<span class="text-decoration-underline">فشل الإرسال:</span> ${escapeHTML(data?.message||'غير معروف')}`;
        }
    } catch (err) {
        console.error('❌ خطأ في إرسال الرسالة:', err);
        const bubble = document.getElementById(tempId);
        if (bubble) bubble.querySelector('.message-content').innerHTML =
            `<span class="text-decoration-underline">خطأ اتصال:</span> ${escapeHTML(err.message)}`;
        alert('خطأ في الاتصال: '+err.message);
    } finally {
        btn.disabled = false; btn.textContent = 'إرسال';
        hint.style.display = 'none';
    }
    return false;
}

/* === التنبيهات === */
async function loadAlerts() {
    try {
        const data = await apiGet('action=get_alerts&limit=10&unread_only=1');
        if (data?.success) {
            displayAlerts(Array.isArray(data.data) ? data.data : []);
            document.getElementById('unread-count').textContent = data.data.length;
        }
    } catch (e) { console.error('خطأ في جلب التنبيهات:', e); }
}
function displayAlerts(alerts) {
    const list = document.getElementById('alerts-list');
    if (!alerts.length) {
        list.innerHTML = '<div class="p-4 text-center text-muted small">لا توجد تنبيهات جديدة</div>';
        return;
    }
    list.innerHTML = alerts.map(a => `
        <div class="alert-item ${a.is_read ? '' : 'unread'}" onclick="markAlertRead(${a.id})">
            <div class="alert-title">${escapeHTML(a.title || '')}</div>
            <div class="alert-message">${escapeHTML(a.message || '')}</div>
            <div class="alert-time">${formatTime(a.created_at || a.createdAt || a.time || new Date().toISOString())}</div>
        </div>
    `).join('');
}
async function markAlertRead(alertId) {
    try {
        await apiPost({action:'mark_alert_read', alert_id: alertId});
        loadAlerts();
    } catch(e){ console.error('خطأ في تحديث التنبيه:', e); }
}

/* === إدارة الكاش/الأرشفة === */
async function clearCurrentHistory() {
    if (!currentConversationId) return;
    const yes = confirm('تفريغ عرض الرسائل الآن. إن كان API الأرشفة متوفرًا سيتم أرشفة السجل أولًا. متابعة؟');
    if (!yes) return;
    try {
        // لو عندك إجراء في الخادم:
        // await apiPost({action:'archive_conversation', conversation_id: currentConversationId});
    } catch(_) {}
    document.getElementById('chat-messages').innerHTML = '';
    scrollToBottom();
}

async function purgeOldConversations() {
    const yes = confirm('سيتم حذف/أرشفة الدردشات السابقة (لتخفيف الكاش) مع الإبقاء عليها في الأرشيف إن وُجد. متابعة؟');
    if (!yes) return;
    try {
        // إن كان لديك endpoint حقيقي للأرشفة الشاملة:
        // await apiPost({action:'archive_old_conversations'}); // مثال
    } catch(_) {}
    // تنظيف واجهة فقط
    document.getElementById('conversations-list').innerHTML = '<div class="p-4 text-center text-muted">لا توجد محادثات</div>';
    document.getElementById('chat-messages').innerHTML = '';
    currentConversationId = null;
    document.getElementById('chat-window').style.display = 'none';
    document.getElementById('chat-empty').style.display  = 'flex';
}

/* === تحية الموظف مرة يوميًا لكل محادثة === */
function autoGreetStaffOncePerDay(){
    if (!currentConversationId) return;
    const key = `greeted:${STAFF.id}:${currentConversationId}:${new Date().toISOString().slice(0,10)}`;
    if (localStorage.getItem(key)) return;
    localStorage.setItem(key, '1');
    apiPost({
        action:'send_message',
        conversation_id: currentConversationId,
        message: `👋 أهلاً، معك ${STAFF.name} (${STAFF.role}). كيف أقدر أساعدك؟`,
        sender_type:'admin',
        sender_id: STAFF.id || 'admin'
    }).then(()=> loadMessages()).catch(()=>{});
}

/* === تهيئة === */
document.addEventListener('DOMContentLoaded', () => {
    // إدخال: Enter للإرسال – Shift+Enter للسطر الجديد
    const input = document.getElementById('admin-message-input');
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendAdminMessage(e);
        }
    });

    loadConversations();
    loadAlerts();
    clearInterval(pollConvInterval); pollConvInterval = setInterval(loadConversations, 3000);
    clearInterval(pollMsgInterval);  // يبدأ عند اختيار محادثة
});
</script>
</body>
</html>
