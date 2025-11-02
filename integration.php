<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

$page_title = 'شرح الربط مع النظام';
$siteTitle = $config->site_title ?? 'عزم الإنجاز';
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/partials/header.php';
?>

<div class="container py-5">
  <div class="row g-4 align-items-stretch">
    <div class="col-lg-12">
      <div id="authCard" class="card card-auth fade-in">
        <div class="card-body p-4">
          <h4 class="card-title mb-4">شرح الربط مع النظام</h4>
          <p class="lead text-muted mb-4">مرحباً بالمطورين! هذه الصفحة توفر لكم كل ما تحتاجونه للربط السلس مع نظامنا. ستجدون هنا إرشادات مفصلة حول كيفية سحب النماذج والملفات، بالإضافة إلى أمثلة عملية.</p>

          <h5 class="mb-3"><i class="fas fa-cogs text-primary me-2"></i> 1. نظرة عامة على الربط</h5>
          <p>نظامنا يوفر واجهات برمجة تطبيقات (APIs) RESTful قوية وموثوقة لتمكين التكامل العميق مع تطبيقاتكم. يمكنكم الوصول إلى بيانات النماذج، تفاصيل الطلبات، والملفات المرفقة بكل سهولة وأمان باستخدام مفاتيح API المخصصة.</p>

          <h5 class="mb-3"><i class="fas fa-shield-alt text-success me-2"></i> 2. المصادقة (Authentication)</h5>
          <p>لضمان أمان بياناتكم، تتطلب جميع طلبات API مفتاح API صالحًا. يمكنكم الحصول على مفتاح API الخاص بكم من لوحة تحكم المسؤول. يرجى تضمين هذا المفتاح في رأس الطلب (Header) كـ <code>X-API-Key</code>.</p>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code1"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code1" class="language-bash">curl -H "X-API-Key: YOUR_API_KEY" \
     https://yourdomain.com/crosing/api.php/forms</code></pre>
          </div>

          <h5 class="mb-3"><i class="fas fa-file-alt text-info me-2"></i> 3. سحب النماذج (Fetching Forms)</h5>
          <p>يمكنكم سحب قائمة بجميع النماذج المتاحة أو تفاصيل نموذج معين باستخدام نقاط النهاية التالية:</p>

          <h6><i class="fas fa-list me-2"></i> سحب جميع النماذج:</h6>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code2"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code2" class="language-bash">GET /crosing/api.php/forms</code></pre>
          </div>
          <p><strong>الاستجابة المتوقعة (JSON):</strong></p>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code3"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code3" class="language-json">[
  {
    "id": 1,
    "name": "نموذج طلب سداد",
    "slug": "payment-request",
    "fields": "الاسم:name:text\nالبريد:email:email"
  },
  // ... المزيد من النماذج
]</code></pre>
          </div>

          <h6><i class="fas fa-search me-2"></i> سحب نموذج محدد بواسطة المعرّف (ID) أو الرابط (Slug):</h6>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code4"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code4" class="language-bash">GET /crosing/api.php/forms/{id_or_slug}</code></pre>
          </div>
          <p><strong>أمثلة:</strong></p>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code5"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code5" class="language-bash">GET /crosing/api.php/forms/1
GET /crosing/api.php/forms/payment-request</code></pre>
          </div>

          <h5 class="mb-3"><i class="fas fa-paperclip text-warning me-2"></i> 4. سحب الملفات (Fetching Files)</h5>
          <p>لسحب الملفات المرفقة بالنماذج، ستحتاجون إلى معرف الملف (File ID) أو مساره. نقطة النهاية التالية تسمح لكم بسحب الملفات مباشرةً:</p>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code6"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code6" class="language-bash">GET /crosing/api.php/files/{file_id}</code></pre>
          </div>
          <p><strong>مثال:</strong></p>
          <div class="code-block bg-light p-3 rounded mb-3">
            <button class="btn btn-sm btn-outline-secondary float-end copy-btn" data-clipboard-target="#code7"><i class="far fa-copy"></i> نسخ</button>
            <pre class="mb-0"><code id="code7" class="language-bash">GET /crosing/api.php/files/019574656667.pdf</code></pre>
          </div>
          <p>سيتم إرجاع الملف مباشرةً. تأكدوا من التعامل مع رؤوس الاستجابة (Response Headers) بشكل صحيح لتحديد نوع الملف (<code>Content-Type</code>) وتنزيله.</p>

          <h5 class="mb-3"><i class="fas fa-question-circle text-danger me-2"></i> 5. دعم فني</h5>
          <p>إذا واجهتكم أي مشاكل أو كانت لديكم استفسارات أثناء عملية الربط، لا تترددوا في التواصل مع فريق الدعم الفني لدينا. نحن هنا لمساعدتكم!</p>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.clipboardTarget;
            const codeBlock = document.querySelector(targetId);
            if (codeBlock) {
                const textToCopy = codeBlock.textContent;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    this.textContent = 'تم النسخ!';
                    setTimeout(() => this.innerHTML = '<i class="far fa-copy"></i> نسخ', 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                    this.textContent = 'فشل النسخ';
                    setTimeout(() => this.innerHTML = '<i class="far fa-copy"></i> نسخ', 2000);
                });
            }
        });
    });
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
