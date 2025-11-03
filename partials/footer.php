<?php
declare(strict_types=1);
/** الفوتر الحديث الموحد */

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$siteTitle = $siteTitle ?? ($APP->site_title ?? 'شركة عزم الإنجاز');
$year = date('Y');
?>
<footer class="site-footer" id="contact">
  <div class="container">
    <div class="row gy-5 align-items-start">
      <div class="col-lg-5">
        <div class="logo-wrap mb-4">
          <img src="<?= e(asset_href('design/Rectangle.png')) ?>" alt="<?= e($siteTitle) ?>" width="210" height="64" loading="lazy">
        </div>
        <p class="text-muted-soft mb-4">
          شركة عزم الإنجاز تجمع حلول التوريد، والتصميم، والبيع بالأجل في منصة واحدة تساعد منشأتك على النمو بثقة وسرعة.
        </p>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-3 mb-3">
            <span class="badge-soft"><i class="fas fa-building"></i></span>
            <span>اسم الشركة: شركة عزم الإنجاز للأدوات الصحية (مساهمة مبسطة)</span>
          </li>
          <li class="d-flex align-items-start gap-3 mb-3">
            <span class="badge-soft"><i class="fas fa-id-card"></i></span>
            <span>الرقم الموحد: 7015661239</span>
          </li>
          <li class="d-flex align-items-start gap-3 mb-3">
            <span class="badge-soft"><i class="fas fa-phone"></i></span>
            <span>الهاتف / واتساب: <a href="tel:0115186956" class="fw-semibold text-decoration-none"><?= e('0115186956') ?></a></span>
          </li>
          <li class="d-flex align-items-start gap-3">
            <span class="badge-soft"><i class="fas fa-map-marker-alt"></i></span>
            <span>العنوان: برج سنام، ‏طريق الملك سعود المعذر، ‏الرياض 12624</span>
          </li>
        </ul>
      </div>

      <div class="col-md-6 col-lg-4">
        <h6 class="mb-3">روابط سريعة</h6>
        <div class="footer-links d-flex flex-column">
          <a href="<?= e(app_href('login.php')) ?>"><i class="fas fa-right-to-bracket"></i><span>تسجيل الدخول</span></a>
          <a href="<?= e(app_href('register.php')) ?>"><i class="fas fa-user-plus"></i><span>إنشاء حساب</span></a>
          <a href="<?= e(app_href('api_docs.php')) ?>"><i class="fas fa-code"></i><span>وثائق الـAPI</span></a>
          <a href="<?= e(app_href('dashboard.php')) ?>"><i class="fas fa-gauge"></i><span>لوحة التحكم</span></a>
          <a href="<?= e(app_href('support/index.php')) ?>"><i class="fas fa-headset"></i><span>مركز الدعم</span></a>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <h6 class="mb-3">حسابات التواصل</h6>
        <div class="social-links d-flex flex-column">
          <a href="https://instagram.com/Azemalenjaz_sa" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>إنستغرام</span></a>
          <a href="https://www.tiktok.com/@Azemalenjaz_sa" target="_blank" rel="noopener"><i class="fab fa-tiktok"></i><span>تيك توك</span></a>
          <a href="https://www.snapchat.com/add/Azemalenjaz_sa" target="_blank" rel="noopener"><i class="fab fa-snapchat"></i><span>سناب شات</span></a>
          <a href="https://x.com/Azemalenjaz_sa" target="_blank" rel="noopener"><i class="fab fa-x-twitter"></i><span>إكس</span></a>
          <a href="https://www.linkedin.com/company/azem-alenjaz-company/" target="_blank" rel="noopener"><i class="fab fa-linkedin"></i><span>لنكد إن</span></a>
        </div>

        <div class="mt-4">
          <h6 class="mb-2">جاهزون لخدمتكم</h6>
          <a href="<?= e(app_href('form.php')) ?>" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="fas fa-paper-plane"></i>
            <span>أرسل طلبك الآن</span>
          </a>
        </div>
      </div>
    </div>

    <div class="footer-meta d-flex flex-wrap justify-content-between align-items-center gap-3">
      <span>© <?= e($year) ?> <?= e($siteTitle) ?>. جميع الحقوق محفوظة.</span>
      <a class="back-to-top text-decoration-none text-muted-soft" href="#top">
        <i class="fas fa-arrow-up"></i>
        <span>الرجوع للأعلى</span>
      </a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
