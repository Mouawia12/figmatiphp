<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// محاولة تحميل دوال مساعدة إن وُجدت
@require_once __DIR__ . "/inc/functions.php";
if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
}
session_start();

$siteTitle = "شركة عزم الإنجاز";
$modelName = "نموذج طلب السداد لاحقًا";
$siteDesc = "تعتز شركة عزم الإنجاز بعملائها وتسعى دائمًا لتوفير حلول مرنة ومبتكرة تسهّل تجربة الشراء. من خلال موقع \"اشترِ الآن وسدّد لاحقًا\" نتيح لك إمكانية الحصول على احتياجاتك فورًا مع خيارات دفع مريحة وآمنة. نحرص على تيسير الخدمات المالية لعملائنا الأفراد والشركات، مع التزامنا بأعلى معايير الموثوقية، الخصوصية، وخدمة العملاء المتميزة.";
// حالة المصادقة
$isAuth = !empty($_SESSION['user']['id']);

// تضمين SEO قبل header
include __DIR__ . "/partials/seo.php";
require __DIR__ . '/partials/header.php';
?>

<main>
  <!-- Hero -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-12 col-lg-7">
          <span class="badge rounded-pill text-bg-light soft-badge"></span>
          <h1 class="display-5 fw-bold mb-3"><?= e($modelName) ?></h1>
          <p class="lead text-muted mb-4"><?= e($siteDesc) ?></p>
          <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(app_href('form.php')) ?>" class="btn btn-primary btn-lg px-4">قدّم الآن</a>
            <a href="<?= e(app_href('track.php')) ?>" class="btn btn-outline-secondary btn-lg px-4">تتبّع طلبك</a>
          </div>
          <div class="mt-3 small">
            <?php if(!$isAuth): ?>
              <span>للمسؤولين:</span>
              <a href="<?= e(app_href('login.php')) ?>" class="link-body-emphasis me-2">تسجيل الدخول</a>
              <a href="<?= e(app_href('register.php')) ?>" class="link-body-emphasis">إنشاء حساب</a>
            <?php else: ?>
              
            <?php endif; ?>
          </div>
        </div>
        <div class="col-12 col-lg-5">
          <div class="hero-card card border-0 shadow-sm">
            <div class="card-body">
              <h2 class="h5 mb-3">لماذا "اشترِ الآن وسدّد لاحقًا"؟</h2>
              <ul class="list-unstyled mb-0 small">
                <li class="mb-2">دفعات ميسّرة بآجال مرنة</li>
                <li class="mb-2">إجراءات بسيطة وسريعة</li>
                <li class="mb-2">حلول للشركات والمنشآت الصغيرة والمتوسطة</li>
                <li class="mb-0">خصوصية وأمان على أعلى مستوى</li>
              </ul>
            </div>
          </div>
        </div>
      </div><!-- row -->
    </div><!-- container -->
  </section>

  <!-- FAQ -->
  <section id="faq" class="section-pad bg-soft">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <h3 class="mb-4 text-center">الأسئلة الشائعة</h3>

          <div class="accordion" id="faqAccordion">
            <!-- 1 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q1">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="false">
                  ١- ما هو الدفع الآجل (BNPL)؟
                </button>
              </h2>
              <div id="a1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  الدفع الآجل هو خدمة تمويل تتيح لك شراء المنتجات أو الخدمات الآن وتسديد قيمتها على دفعات ميسرة خلال فترة محددة، مما يساعدك على إدارة تدفقاتك النقدية بمرونة.
                </div>
              </div>
            </div>
            <!-- 2 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false">
                  ٢- من يمكنه الاستفادة من خدمة الدفع الآجل؟
                </button>
              </h2>
              <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  تتوفر الخدمة لجميع الشركات المسجلة رسميًا في المملكة العربية السعودية، بما في ذلك المؤسسات الصغيرة والمتوسطة، التي تستوفي شروط الأهلية المحددة من قبلنا.
                </div>
              </div>
            </div>
            <!-- 3 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3" aria-expanded="false">
                  ٣- كيف يتم تحديد مبلغ الدفعات وفترتها؟
                </button>
              </h2>
              <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  يتم تحديد مبلغ الدفعات وفترتها بناءً على حجم الطلب، تاريخ الشركة، وسجلها الائتماني.
                </div>
              </div>
            </div>
            <!-- 4 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q4">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4" aria-expanded="false">
                  ٤- كيف يمكنني التقديم للحصول على خدمة الدفع الآجل؟
                </button>
              </h2>
              <div id="a4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  يمكنك التقديم من خلال ملء النموذج الإلكتروني المتاح على موقعنا، أو عبر التواصل المباشر معنا عبر الواتساب أو البريد الإلكتروني.
                </div>
              </div>
            </div>
            <!-- 5 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q5">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a5" aria-expanded="false">
                  ٥- هل تؤثر خدمة الدفع الآجل على سجلي الائتماني؟
                </button>
              </h2>
              <div id="a5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  نعم، قد يتم الإبلاغ عن معاملات الدفع الآجل إلى الجهات المعنية، مما قد يؤثر على سجلك الائتماني. الالتزام بمواعيد السداد يساعد في تحسين تاريخك الائتماني.
                </div>
              </div>
            </div>
            <!-- 6 -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="q6">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a6" aria-expanded="false">
                  ٦- هل يمكنني استخدام خدمة الدفع الآجل لشراء أي منتج أو خدمة؟
                </button>
              </h2>
              <div id="a6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  نعم، يمكنك استخدام الخدمة لشراء المنتجات أو الخدمات المتاحة لدينا، بشرط أن تتوافق مع شروط الاستخدام المحددة.
                </div>
              </div>
            </div>
          </div><!-- accordion -->
        </div>
      </div>
    </div>
  </section>

  <!-- روابط مهمة -->
  <section id="links" class="section-pad">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <h3 class="mb-4 text-center">روابط مهمة</h3>
          <div class="row g-3">
            <div class="col-md-4">
              <a class="card soft-link h-100 text-decoration-none" href="<?= e(app_href('form.php')) ?>">
                <div class="card-body">
                  <h5 class="mb-1">تعبئة النموذج</h5>
                  <p class="text-muted small mb-0">قدّم طلب السداد لاحقًا عبر النموذج الإلكتروني.</p>
                </div>
              </a>
            </div>
            <div class="col-md-4">
              <a class="card soft-link h-100 text-decoration-none" href="<?= e(app_href('track.php')) ?>">
                <div class="card-body">
                  <h5 class="mb-1">تتبّع الطلب</h5>
                  <p class="text-muted small mb-0">تحقّق من حالة طلبك بسهولة.</p>
                </div>
              </a>
            </div>
            <div class="col-md-4">
              <a class="card soft-link h-100 text-decoration-none" href="<?= e(app_href('#')) ?>">
                <div class="card-body">
                  <h5 class="mb-1">واجهة برمجية (API)</h5>
                  <p class="text-muted small mb-0">وثائق الربط والاندماج التقني.</p>
                </div>
              </a>
            </div>
          </div><!-- row -->
        </div>
      </div>
    </div>
  </section>
</main>

<!-- نظام الدردشة الذكي - عزم -->
<script src="<?= e(asset_href('assets/ai-decorator-module.js')) ?>"></script>
<script src="<?= e(asset_href('assets/chatbot.js')) ?>"></script>
<script>
  // تخصيص اسم الذكاء الاصطناعي والترحيب التلقائي عند فتح الصفحة
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
      if (window.chatBot) {
        window.chatBot.botName = 'عزم';
        try {
          var t = document.querySelector('#chatbot-window .chatbot-title h3');
          if (t) t.textContent = 'عزم - مساعدك الذكي';
          var l = document.querySelector('#chatbot-widget .chatbot-label');
          if (l) l.textContent = 'تحدث مع عزم';
        } catch (e) {}
        // لا نستبدل الرسائل إذا كانت موجودة بالفعل
        var box = document.getElementById('chatbot-messages');
        var hasMsgs = box && box.querySelector('.message');
        if (!hasMsgs) {
          if (box) {
            box.innerHTML = '<div class="chatbot-welcome"><div class="welcome-icon">🤖</div><h4>أهلاً بك!</h4><p>معاك عزم حياك الله — اكتب الى تحتاج ونا بخدمتك.</p></div>';
          }
          // افتح نافذة الدردشة مرة واحدة
          try { localStorage.setItem('chatbot_greeted', '1'); } catch (e) {}
          if (!window.chatBot.isOpen && typeof window.chatBot.toggleWindow === 'function') {
            window.chatBot.toggleWindow();
          }
        }
      }
    }, 400);
  });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
