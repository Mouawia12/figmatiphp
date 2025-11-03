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

$serviceCards = [
    [
        'icon' => 'design/ايقونة2 2-1.svg',
        'icon_alt' => 'أيقونة طلب عرض السعر',
        'title' => 'طلبات عرض السعر',
        'description' => 'اطلب عرض سعر دقيق يلائم احتياجات مشروعك، مع توصيات المتخصصين وخيارات التوريد المناسبة.',
        'cta' => 'اطلب عرض سعر',
        'href' => app_href('request-for-quote.php'),
    ],
    [
        'icon' => 'design/ايقونة2 2.svg',
        'icon_alt' => 'أيقونة البيع بالأجل',
        'title' => 'البيع بالأجل',
        'description' => 'حلول تمويل مرنة للشركات والمنشآت الصغيرة والمتوسطة بأسعار شفافة وخطط سداد ميسرة.',
        'cta' => 'ابدأ خدمة البيع بالأجل',
        'href' => app_href('form.php'),
    ],
    [
        'icon' => 'design/ايقونة2 2-2.svg',
        'icon_alt' => 'أيقونة التصميم الداخلي',
        'title' => 'التصميم الداخلي',
        'description' => 'صمّم مساحاتك مع مهندسين محترفين، واحصل على رؤية متكاملة للتوريد والتنفيذ قبل البدء.',
        'cta' => 'اطلب جلسة تصميم',
        'href' => app_href('interior-design-request.php'),
    ],
    [
        'icon' => 'design/ايقونة2 2-3.svg',
        'icon_alt' => 'أيقونة المتجر الإلكتروني',
        'title' => 'المتجر الإلكتروني',
        'description' => 'تسوق منتجات البناء والتشطيب من منصة موثوقة مع خيارات شحن سريعة ودعم متخصص.',
        'cta' => 'تصفح المنتجات',
        'href' => 'https://azmalenjaz.com/',
    ],
];

$aboutFeatures = [
    [
        'icon' => 'design/ايقونة2 2-1.svg',
        'icon_alt' => 'توريد موثوق',
        'title' => 'توريد موثوق',
        'description' => 'شبكة توريد متكاملة تغطي مواد البناء والتشطيب مع التزام صارم بالمواعيد وجودة التنفيذ.',
    ],
    [
        'icon' => 'design/ايقونة2 2.svg',
        'icon_alt' => 'تمويل مرن',
        'title' => 'تمويل مرن',
        'description' => 'خيارات بيع بالأجل مصممة لتمنح شركتك حرية الحركة المالية وتدعم خطط التوسع بثقة.',
    ],
    [
        'icon' => 'design/ايقونة2 2-2.svg',
        'icon_alt' => 'تصميم هندسي',
        'title' => 'تصميم هندسي متخصص',
        'description' => 'فريق هندسي يطوّر تصاميم عملية وجذابة، مع مواءمة كاملة لحلول التوريد والتنفيذ.',
    ],
    [
        'icon' => 'design/ايقونة2 2-3.svg',
        'icon_alt' => 'دعم متكامل',
        'title' => 'دعم متكامل',
        'description' => 'مستشارون يتابعون مشروعك خطوة بخطوة لضمان تجربة سلسة من الطلب وحتى التسليم.',
    ],
];

$whyReasons = [
    ['title' => 'خبرة توريد عميقة', 'description' => 'خبرة تراكمية في توفير مواد البناء والتشطيب لمشاريع كبرى ومتوسطة.'],
    ['title' => 'تكامل كامل للخدمات', 'description' => 'من التصور الأولي وحتى التسليم، نوفر التصميم، التوريد، والتمويل في منصة واحدة.'],
    ['title' => 'تسهيلات سداد مرنة', 'description' => 'خطط دفع بالأجل تعطي منظمتك مساحة للتحرك والنمو بدون ضغوط مالية.'],
    ['title' => 'دعم ذكي ومتواصل', 'description' => 'شات بوت ذكي وفريق دعم بشري لمتابعة طلباتك والاستجابة الفورية لاستفساراتك.'],
    ['title' => 'سرعة في التسليم', 'description' => 'شبكة لوجستية تضمن وصول الموارد بدقة وفي الوقت المتفق عليه.'],
];

$faqItems = [
    [
        'question' => 'ماهو الدفع الآجل (BNPL)؟',
        'answer' => 'الدفع الآجل هو خدمة تمويل تتيح لك شراء المنتجات أو الخدمات الآن وتسديد قيمتها على دفعات ميسرة خلال فترة محددة، مما يساعدك على إدارة تدفقاتك النقدية بمرونة.',
    ],
    [
        'question' => 'من يمكنه الاستفادة من خدمة الدفع الآجل؟',
        'answer' => 'تتوفر الخدمة لجميع الشركات المسجلة رسميًا في المملكة العربية السعودية، بما في ذلك المؤسسات الصغيرة والمتوسطة، التي تستوفي شروط الأهلية المحددة.',
    ],
    [
        'question' => 'ما هي المنتجات التي تقدمها شركة عزم الإنجاز؟',
        'answer' => 'نقدم مجموعة واسعة من مواد البناء والتشطيب والخدمات اللوجستية المرتبطة بها، بالإضافة إلى حلول التصميم الداخلي وخيارات التمويل بالأجل.',
    ],
    [
        'question' => 'ما هي خدمات التصميم الداخلي التي تقدمها الشركة؟',
        'answer' => 'نوفر خدمات التصميم الداخلي الشاملة من التخطيط المفاهيمي وحتى التوريد والتنفيذ، مع مراعاة هوية المشروع وميزانيته.',
    ],
    [
        'question' => 'هل توفرون توصيل للمنتجات؟',
        'answer' => 'نعم، نغطي التوصيل إلى مختلف مناطق المملكة عبر شبكة لوجستية موثوقة، مع إمكانية تتبع الطلب لحظة بلحظة.',
    ],
    [
        'question' => 'هل يمكنني الحصول على استشارة قبل الشراء؟',
        'answer' => 'بالطبع، يوفر فريقنا الاستشاري جلسات مجانية لمناقشة الحلول الأنسب لمشروعك واختيار التوريد أو الخدمة المثلى.',
    ],
];

$quickLinks = [
    [
        'title' => 'قدّم طلب البيع بالأجل',
        'description' => 'ابدأ نموذج الطلب الإلكتروني ووفّر علينا البيانات الأساسية لمتابعة فريقنا معك.',
        'href' => app_href('form.php'),
        'icon' => 'fa-file-pen',
    ],
    [
        'title' => 'تتبّع حالة طلبك',
        'description' => 'أدخل رقم الطلب لمعرفة آخر التحديثات وخطوات المعالجة الحالية.',
        'href' => app_href('track.php'),
        'icon' => 'fa-location-dot',
    ],
    [
        'title' => 'استعرض وثائق الـAPI',
        'description' => 'تكامل برمجي سلس مع منصاتكم عبر واجهات موثقة وواضحة.',
        'href' => app_href('api_docs.php'),
        'icon' => 'fa-code',
    ],
];

// تضمين SEO قبل header
include __DIR__ . "/partials/seo.php";
require __DIR__ . '/partials/header.php';
?>


<main class="overflow-hidden">
  <section class="hero-section" id="hero">
    <div class="container">
      <div class="row align-items-center gy-5 hero-row">
        <div class="col-lg-7 col-xl-6">
          <div class="hero-copy animate-fade">
            <h1 class="hero-title animate-fade delay-1">
              <span>عزمنا في التوريد</span>
              <span class="accent">أساس كل إنجاز</span>
            </h1>
            <p class="hero-subtitle animate-fade delay-2">
              من خلال منصة عزم الإنجاز نوفر لك مسارًا واحدًا يضم التصميم، التوريد، والبيع بالأجل لتنجز مشروعك بثقة وسرعة وبجودة تتجاوز التوقعات.
            </p>
            <div class="hero-actions animate-fade delay-3">
              <a href="<?= e(app_href('form.php')) ?>" class="btn btn-primary hero-primary d-inline-flex align-items-center gap-2">
                <i class="fas fa-credit-card"></i>
                <span>ابدأ خدمة البيع بالأجل</span>
              </a>
              <a href="https://azmalenjaz.com/" class="btn btn-outline-secondary hero-secondary d-inline-flex align-items-center gap-2" target="_blank" rel="noopener">
                <i class="fas fa-store"></i>
                <span>تصفح المتجر</span>
              </a>
            </div>
          </div>
        </div>
        <div class="col-lg-5 col-xl-6 hero-visual-col">
          <div class="hero-visual" aria-hidden="true">
            <div class="hero-visual-layer layer-primary"></div>
            <div class="hero-visual-layer layer-secondary"></div>
            <div class="hero-visual-layer layer-tertiary"></div>

            <div class="hero-visual-card hero-visual-card-main">
              <div class="hero-card-icon">
                <i class="fas fa-truck-fast"></i>
              </div>
              <div class="hero-card-copy">
                <strong>توريد سريع</strong>
                <span>شبكة لوجستية تغطي المملكة</span>
              </div>
            </div>

            <div class="hero-visual-card hero-visual-card-secondary">
              <div class="hero-card-icon">
                <i class="fas fa-helmet-safety"></i>
              </div>
              <div class="hero-card-copy">
                <strong>إشراف هندسي</strong>
                <span>فريق متخصص يتابع مشروعك</span>
              </div>
            </div>

            <div class="hero-visual-metric metric-one">
              <span class="metric-label">طلبات منجزة</span>
              <span class="metric-value" data-count="2500" data-prefix="+">0</span>
            </div>

            <div class="hero-visual-metric metric-two">
              <span class="metric-label">شركاء موثقون</span>
              <span class="metric-value" data-count="120" data-suffix="+">0</span>
            </div>

            <span class="hero-visual-spark spark-one"></span>
            <span class="hero-visual-spark spark-two"></span>
            <span class="hero-visual-spark spark-three"></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="about-section" id="about">
    <div class="container">
      <div class="about-highlight-card">
        <div class="row align-items-center g-4 g-lg-5">
          <div class="col-lg-4 order-lg-1">
            <div class="about-logo-panel animate-fade delay-2">
              <div class="about-logo-circle">
                <img src="<?= e(asset_href('assets/img/logo.svg')) ?>" alt="شعار شركة عزم الإنجاز" loading="lazy">
              </div>
            </div>
          </div>
          <div class="col-lg-8 order-lg-2">
            <div class="about-copy">
              <span class="section-eyebrow about-eyebrow animate-fade">
                <span class="about-eyebrow-icon">
                  <i class="fas fa-person-digging"></i>
                </span>
                <span>من نحن</span>
              </span>
            
                <p class="about-quote-text">
                  <span class="quote-mark quote-mark-open" aria-hidden="true">
                    <i class="fas fa-quote-right"></i>
                  </span>
                  <span class="quote-body">
                    عزم الإنجاز شركة سعودية متخصصة في بيع وتوريد مواد البناء بجودة مضمونة وأسعار منافسة، وتقدم حلولًا متكاملة تشمل البيع بالأجل، التصميم الداخلي، والمتجر الإلكتروني.<br>
                    نؤمن أن البناء الحقيقي يبدأ من التوريد الصحيح، ولهذا كان شعارنا: عزمنا في التوريد ... أساس كل إنجاز
                  </span>
                  <span class="quote-mark quote-mark-close" aria-hidden="true">
                    <i class="fas fa-quote-left"></i>
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="services-section" id="services">
    <div class="container">
      <div class="services-header">
        <span class="section-eyebrow animate-fade">
          <i class="fas fa-layer-group"></i>
          خدماتنا
        </span>
        <h2 class="section-title animate-fade delay-1">خدمات البناء في مكان واحد</h2>
        <p class="section-subtitle mx-auto animate-fade delay-2">
          اختر الخدمة المناسبة وابدأ رحلتك بثقة مع فريق يتابع كل تفاصيل مشروعك ويضمن وصول الموارد في الوقت المناسب.
        </p>
      </div>
      <div class="services-grid">
        <?php foreach ($serviceCards as $index => $card): ?>
          <?php
            $delayClass = 'delay-' . min(4, $index + 1);
            $isExternal = strpos($card['href'], 'http') === 0;
          ?>
          <article class="service-card animate-fade <?= $delayClass ?>">
            <div class="service-icon">
              <img src="<?= e(asset_href($card['icon'])) ?>" alt="<?= e($card['icon_alt'] ?? $card['title']) ?>" loading="lazy">
            </div>
            <h3><?= e($card['title']) ?></h3>
            <p><?= e($card['description']) ?></p>
            <a class="btn service-link d-inline-flex align-items-center gap-2" href="<?= e($card['href']) ?>"<?= $isExternal ? ' target="_blank" rel="noopener"' : '' ?>>
              <span><?= e($card['cta']) ?></span>
              <i class="fas fa-arrow-left"></i>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  

  <section class="why-section" id="why">
    <div class="container">
      <div class="row gy-4 align-items-start">
        <div class="col-lg-5">
          <span class="section-eyebrow animate-fade">لماذا عزم؟</span>
          <h2 class="section-title animate-fade delay-1">
            لماذا تختار <span class="accent">عزم الإنجاز</span> لإدارة مشروعك؟
          </h2>
          <p class="section-subtitle animate-fade delay-2">
            لأننا نؤمن بأن النجاح الحقيقي يتحقق عندما نجمع بين السرعة، الجودة، والالتزام الكامل بالوعد.
          </p>
        </div>
        <div class="col-lg-7">
          <div class="why-grid">
            <?php foreach ($whyReasons as $index => $reason): ?>
              <?php $delayClass = 'delay-' . min(4, $index + 1); ?>
              <div class="why-card animate-fade <?= $delayClass ?>">
                <div class="badge"><?= sprintf('%02d', $index + 1) ?></div>
                <div>
                  <strong><?= e($reason['title']) ?></strong>
                  <p><?= e($reason['description']) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="faq-section" id="faq">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <div class="text-center mb-5">
            <span class="section-eyebrow animate-fade">الأسئلة الشائعة</span>
            <h2 class="section-title animate-fade delay-1">إجابات سريعة على أهم استفساراتكم</h2>
            <p class="section-subtitle mx-auto animate-fade delay-2">جمعنا أبرز الأسئلة التي تصلنا من شركائنا لنساعدكم على اتخاذ القرار الصحيح بأسرع وقت.</p>
          </div>
          <div class="faq-wrapper animate-fade delay-3">
            <div class="accordion" id="faqAccordion">
              <?php foreach ($faqItems as $index => $faq): ?>
                <?php
                  $headingId = 'faqHeading' . $index;
                  $collapseId = 'faqCollapse' . $index;
                  $isFirst = $index === 0;
                ?>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="<?= e($headingId) ?>">
                    <button class="accordion-button<?= $isFirst ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapseId) ?>" aria-expanded="<?= $isFirst ? 'true' : 'false' ?>" aria-controls="<?= e($collapseId) ?>">
                      <?= e(($index + 1) . '. ' . $faq['question']) ?>
                    </button>
                  </h2>
                  <div id="<?= e($collapseId) ?>" class="accordion-collapse collapse<?= $isFirst ? ' show' : '' ?>" aria-labelledby="<?= e($headingId) ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <?= e($faq['answer']) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="links-section" id="links">
    <div class="container">
      <div class="row gy-4 align-items-center">
        <div class="col-lg-4">
          <span class="section-eyebrow animate-fade">روابط مهمة</span>
          <h2 class="section-title animate-fade delay-1">ابدأ خطوتك التالية الآن</h2>
          <p class="section-subtitle animate-fade delay-2">
            سواء كنت ترغب في تقديم طلب جديد أو متابعة حالة طلب سابق، هذه الروابط تختصر عليك الوقت.
          </p>
        </div>
        <div class="col-lg-8">
          <div class="links-grid">
            <?php foreach ($quickLinks as $index => $link): ?>
              <?php $delayClass = 'delay-' . min(4, $index + 1); ?>
              <a class="link-card d-block animate-fade <?= $delayClass ?>" href="<?= e($link['href']) ?>">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0"><?= e($link['title']) ?></h4>
                  <span class="badge-soft"><i class="fas <?= e($link['icon']) ?>"></i></span>
                </div>
                <p class="mb-0 text-muted-soft"><?= e($link['description']) ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-section text-center text-white">
    <div class="container">
      <h2 class="fw-bold mb-3">جاهزون لتجهيز مشروعك؟</h2>
      <p class="lead mb-4 text-white-50">تواصل معنا الآن لحلول مخصصة لمشروعك، وسنكون شريكك في كل خطوة من التخطيط حتى التسليم.</p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="<?= e(app_href('support/index.php')) ?>" class="btn btn-outline-light d-inline-flex align-items-center gap-2">
          <i class="fas fa-comments"></i>
          <span>تواصل مع فريق الدعم</span>
        </a>
        <a href="<?= e(app_href('form.php')) ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
          <i class="fas fa-calendar-check"></i>
          <span>احجز مكالمة استشارية</span>
        </a>
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
