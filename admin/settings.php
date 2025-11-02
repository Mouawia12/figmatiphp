<?php
// /crosing/admin/settings.php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me     = require_admin();

$dbs = pdo_open($config->db_forms);

// جدول الإعدادات
$dbs->exec("CREATE TABLE IF NOT EXISTS app_settings (k TEXT PRIMARY KEY, v TEXT)");

function get_all_settings(PDO $db): array {
    $rows = $db->query("SELECT k,v FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows ?: [];
}

function save_settings(PDO $db, array $assoc): void {
    $st = $db->prepare("REPLACE INTO app_settings (k,v) VALUES (?,?)");
    foreach ($assoc as $k=>$v) { $st->execute([$k, (string)$v]); }
}

$defaults = [
  'site_name'          => 'شركة عزم الإنجاز',
  'site_tagline'       => 'نموذج طلب السداد لاحقًا',
  'company_description'=> 'تعتز شركة عزم الإنجاز بعملائها وتسعى دائمًا لتوفير حلول مرنة ومبتكرة تسهّل تجربة الشراء...',
  'contact_phone'      => '0115186956',
  'contact_email'      => $config->mail_to ?? 'info@example.com',
  'address'            => 'برج سنام، ‏طريق الملك سعود المعذر، ‏الرياض 12624',
  'instagram'          => 'Azemalenjaz_sa',
  'tiktok'             => 'Azemalenjaz_sa',
  'snapchat'           => 'Azemalenjaz_sa',
  'x_handle'           => 'Azemalenjaz_sa',
  'linkedin'           => 'Azem Alenjaz company',
  'footer_copyright'   => '© 2025 عزم الإنجاز. جميع الحقوق محفوظة.',
  'theme_default'      => 'light', 
  'max_upload_mb'      => '512',
  'allowed_ext'        => 'pdf,jpg,jpeg,png',
  'seo_description'    => 'وصف افتراضي للموقع يظهر في محركات البحث.',
  'seo_keywords'       => 'كلمات, مفتاحية, افتراضية',
  'twitter_handle'     => 'yourhandle',
];

$settings = array_merge($defaults, get_all_settings($dbs));
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) { http_response_code(400); die('CSRF'); }

    $allowed_keys = array_keys($defaults);
    $payload = [];
    foreach ($allowed_keys as $key) {
        $val = trim((string)($_POST[$key] ?? ''));
        if ($key === 'max_upload_mb') $val = (string)max(1, (int)$val);
        if ($key === 'theme_default' && !in_array($val, ['light','dark'], true)) $val = 'light';
        $payload[$key] = $val;
    }

    save_settings($dbs, $payload);
    $settings = array_merge($settings, $payload);
    $notice = '✅ تم حفظ الإعدادات بنجاح.';
}

$page_title = 'إعدادات الموقع';
$content = function() use ($settings, $notice){ ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <?php if($notice): ?>
    <div class="alert alert-success text-white font-weight-bold" role="alert">
        <?= e($notice) ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Identity & Content Card -->
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">الهوية والمحتوى</h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">اسم الموقع/الشركة</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?= e($settings['site_name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_tagline" class="form-label">وصف قصير (Tagline)</label>
                                <input type="text" class="form-control" id="site_tagline" name="site_tagline" value="<?= e($settings['site_tagline']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="company_description" class="form-label">وصف الشركة</label>
                        <textarea class="form-control" id="company_description" name="company_description" rows="4"><?= e($settings['company_description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="footer_copyright" class="form-label">حقوق الفوتر</label>
                        <input type="text" class="form-control" id="footer_copyright" name="footer_copyright" value="<?= e($settings['footer_copyright']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info Card -->
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">بيانات التواصل</h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">الهاتف</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= e($settings['contact_phone']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">بريد الإشعارات</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= e($settings['contact_email']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">العنوان</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?= e($settings['address']) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Social Media Card -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6 class="mb-0">حسابات التواصل الاجتماعي</h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="text" class="form-control" name="instagram" value="<?= e($settings['instagram']) ?>" placeholder="Instagram">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fab fa-tiktok"></i></span>
                                <input type="text" class="form-control" name="tiktok" value="<?= e($settings['tiktok']) ?>" placeholder="TikTok">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fab fa-snapchat"></i></span>
                                <input type="text" class="form-control" name="snapchat" value="<?= e($settings['snapchat']) ?>" placeholder="Snapchat">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                <input type="text" class="form-control" name="x_handle" value="<?= e($settings['x_handle']) ?>" placeholder="X (Twitter)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                <input type="text" class="form-control" name="linkedin" value="<?= e($settings['linkedin']) ?>" placeholder="LinkedIn">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Settings Card -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6 class="mb-0">إعدادات SEO</h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="seo_description" class="form-label">الوصف الافتراضي للموقع (Meta Description)</label>
                                <textarea class="form-control" id="seo_description" name="seo_description" rows="3"><?= e($settings['seo_description']) ?></textarea>
                                <p class="text-sm text-muted mt-2">وصف قصير (حوالي 160 حرف) يظهر في نتائج البحث.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="seo_keywords" class="form-label">الكلمات المفتاحية (Meta Keywords)</label>
                                <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" value="<?= e($settings['seo_keywords']) ?>">
                                <p class="text-sm text-muted mt-2">افصل بينها بفاصلة, مثل: <code>كلمة1, كلمة2</code></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter_handle" class="form-label">حساب تويتر</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="twitter_handle" name="twitter_handle" value="<?= e($settings['twitter_handle']) ?>" placeholder="yourhandle">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Advanced Settings Card -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6 class="mb-0">إعدادات متقدمة</h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="theme_default" class="form-label">الوضع الافتراضي للمظهر</label>
                                <select class="form-select" id="theme_default" name="theme_default">
                                    <option value="light" <?= $settings['theme_default']==='light'?'selected':'' ?>>نهاري (Light)</option>
                                    <option value="dark"  <?= $settings['theme_default']==='dark'?'selected':''  ?>>ليلي (Dark)</option>
                                </select>
                                <p class="text-sm text-muted mt-2">هذا هو المظهر الذي يراه الزائر الجديد. يمكنه تغييره لاحقًا.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_upload_mb" class="form-label">الحد الأقصى للرفع (MB)</label>
                                <input type="number" class="form-control" id="max_upload_mb" min="1" name="max_upload_mb" value="<?= e($settings['max_upload_mb']) ?>">
                                <p class="text-sm text-muted mt-2">يجب ضبط <code>upload_max_filesize</code> في <code>.user.ini</code> لتطبيقه.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="allowed_ext" class="form-label">الامتدادات المسموحة</label>
                                <input type="text" class="form-control" id="allowed_ext" name="allowed_ext" value="<?= e($settings['allowed_ext']) ?>">
                                <p class="text-sm text-muted mt-2">افصل بينها بفاصلة, مثل: <code>pdf,jpg,png</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="row">
        <div class="col-12 d-flex justify-content-end">
            <a href="index.php" class="btn btn-outline-secondary me-3">إلغاء</a>
            <button type="submit" class="btn bg-gradient-primary mb-0">حفظ الإعدادات</button>
        </div>
    </div>

</form>

<?php };

include __DIR__ . '/_layout.php';