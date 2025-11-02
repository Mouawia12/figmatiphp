<?php
declare(strict_types=1);
/** الفوتر الرسمي الموحد - مطابق لـ index.php مع أيقونات Font Awesome */

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$siteTitle = $siteTitle ?? ($APP->site_title ?? 'شركة عزم الإنجاز');
$year = date('Y');
?>
<footer class="footer mt-auto pt-5 pb-4 bg-white border-top">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-6">
        <h5 class="mb-3"><?= e($siteTitle) ?></h5>
        <ul class="list-unstyled small text-muted mb-0">
          <li><i class="fas fa-building me-2 text-muted"></i>اسم الشركة: شركة عزم الإنجاز للأدوات الصحية (مساهمة مبسطة)</li>
          <li><i class="fas fa-id-card me-2 text-muted"></i>الرقم الموحد: 7015661239</li>
          <li><i class="fas fa-phone me-2 text-muted"></i>رقم الهاتف / واتساب: <a href="tel:0115186956" class="link-body-emphasis">0115186956</a></li>
          <li><i class="fas fa-map-marker-alt me-2 text-muted"></i>العنوان: برج سنام، ‏طريق الملك سعود المعذر، ‏الرياض 12624</li>
        </ul>
      </div>
      
      <!-- حسابات التواصل الاجتماعي -->
      <div class="col-md-3">
        <h6 class="mb-3">حسابات التواصل</h6>
        <ul class="list-unstyled small mb-0">
          <li class="mb-2">
            <a href="https://instagram.com/Azemalenjaz_sa" target="_blank" rel="noopener" class="text-decoration-none text-muted d-inline-flex align-items-center">
              <i class="fab fa-instagram me-2 text-muted" style="width: 20px; text-align: center;"></i>
              <span>إنستغرام</span>
            </a>
          </li>
          <li class="mb-2">
            <a href="https://www.tiktok.com/@Azemalenjaz_sa" target="_blank" rel="noopener" class="text-decoration-none text-muted d-inline-flex align-items-center">
              <i class="fab fa-tiktok me-2 text-muted" style="width: 20px; text-align: center;"></i>
              <span>تيك توك</span>
            </a>
          </li>
          <li class="mb-2">
            <a href="https://www.snapchat.com/add/Azemalenjaz_sa" target="_blank" rel="noopener" class="text-decoration-none text-muted d-inline-flex align-items-center">
              <i class="fab fa-snapchat me-2 text-muted" style="width: 20px; text-align: center;"></i>
              <span>سناب شات</span>
            </a>
          </li>
          <li class="mb-2">
            <a href="https://x.com/Azemalenjaz_sa" target="_blank" rel="noopener" class="text-decoration-none text-muted d-inline-flex align-items-center">
              <i class="fab fa-x-twitter me-2 text-muted" style="width: 20px; text-align: center;"></i>
              <span>إكس (تويتر)</span>
            </a>
          </li>
          <li class="mb-2">
            <a href="https://www.linkedin.com/company/azem-alenjaz-company/" target="_blank" rel="noopener" class="text-decoration-none text-muted d-inline-flex align-items-center">
              <i class="fab fa-linkedin me-2 text-muted" style="width: 20px; text-align: center;"></i>
              <span>لنكد إن</span>
            </a>
          </li>
        </ul>
      </div>
      
      <!-- روابط مهمة -->
      <div class="col-md-3">
        <h6 class="mb-3">روابط</h6>
        <ul class="list-unstyled small mb-0">
          <li class="mb-2"><a class="link-body-emphasis text-decoration-none" href="<?= e(app_href('login.php')) ?>"><i class="fas fa-sign-in-alt me-2 text-muted"></i>تسجيل الدخول</a></li>
          <li class="mb-2"><a class="link-body-emphasis text-decoration-none" href="<?= e(app_href('register.php')) ?>"><i class="fas fa-user-plus me-2 text-muted"></i>إنشاء حساب</a></li>
          <li class="mb-2"><a class="link-body-emphasis text-decoration-none" href="<?= e(app_href('api_docs.php')) ?>"><i class="fas fa-code me-2 text-muted"></i>وثائق الـ API</a></li>
          <li class="mb-2"><a class="link-body-emphasis text-decoration-none" href="<?= e(app_href('dashboard.php')) ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i>لوحة التحكم</a></li>
        </ul>
      </div>
    </div>
    <hr class="my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
      <span>© <?= e($year) ?> عزم الإنجاز. جميع الحقوق محفوظة</span>
      <a class="link-secondary text-decoration-none" href="#top"><i class="fas fa-arrow-up me-1"></i>الرجوع للأعلى</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
