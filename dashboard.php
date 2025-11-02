<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('login.php')); exit; }
$user = e($_SESSION['user']['name'] ?? $_SESSION['user']['email']);
// derive verification flags from session if available
$emailVerified = !empty($_SESSION['verify']['email_verified']);
$phoneVerified = !empty($_SESSION['verify']['phone_verified']);

// If admin, redirect to admin dashboard
$role = 'user';
try {
  $dbu = pdo_open($config->db_users);
  $st  = $dbu->prepare('SELECT role FROM users WHERE id=?');
  $st->execute([ (int)$_SESSION['user']['id'] ]);
  $r   = $st->fetch(PDO::FETCH_ASSOC);
  if ($r && !empty($r['role'])) $role = (string)$r['role'];
} catch (Throwable $e) { /* ignore */ }
if ($role === 'admin') { header('Location: ' . app_href('admin/index.php')); exit; }

// Fetch user's requests
$orders = [];
try {
    ensure_requests_schema(); // Ensure tables exist before querying
    $db = pdo_open('requests');
    $current_user_id = (int)$_SESSION['user']['id'];
    $stmt = $db->prepare("SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$current_user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("Error fetching user requests: " . $e->getMessage());
}

// Fetch user's support tickets
$tickets = [];
try {
    ensure_support_tables_exist(); // Ensure tables exist before querying
    $db = pdo_open('users');
    $stmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC");
    $stmt->execute([(int)$_SESSION['user']['id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Log error, but don't stop page rendering
    error_log("Error fetching user tickets: " . $e->getMessage());
}

// Header (same shell as login.php)
$siteTitle = $config->site_title ?? 'موقعي';
require __DIR__ . '/partials/header.php';
?>

<style>
:root {
    --bs-primary-rgb: 23, 193, 204;
    --bs-primary: #17c1cc;
}
body {
    background-color: #f8f9fa;
}
.dashboard-layout {
    display: flex;
    min-height: 100vh;
}
.sidebar {
    width: 250px;
    background-color: #fff;
    border-left: 1px solid #dee2e6;
    padding: 1.5rem;
    flex-shrink: 0;
}
.sidebar .nav-link {
    display: flex;
    align-items: center;
    padding: .75rem 1rem;
    margin-bottom: .5rem;
    border-radius: .5rem;
    color: #495057;
    transition: all .2s ease-out;
}
.sidebar .nav-link i {
    margin-left: .75rem;
    width: 20px;
    text-align: center;
}
.sidebar .nav-link.active,
.sidebar .nav-link:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    font-weight: 500;
}
.main-content {
    flex-grow: 1;
    padding: 2rem;
    overflow-y: auto;
}
.main-content section {
    display: none;
}
.main-content section.active {
    display: block;
}
.card-dashboard {
    border: 0;
    box-shadow: 0 8px 24px rgba(0,0,0,.05);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.input-soft{background:#f8f9fa;border-color:#dee2e6}
.btn-main{background:#17c1cc;color:#fff;border:0}
.btn-main:hover{background:#13aab4;color:#fff}
.avatar{font-size:1.1rem;font-weight:bold;}
.message-bubble{word-wrap:break-word;}
.message-bubble.bg-light{border:1px solid #e9ecef;}
.message-bubble.bg-white{border:1px solid #dee2e6;}
</style>

<div class="dashboard-layout">
    <aside class="sidebar">
        <h4 class="mb-4"><?= e($siteTitle) ?></h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#overview"><i class="fas fa-tachometer-alt"></i> النظرة العامة</a>
            <a class="nav-link" href="#profile"><i class="fas fa-user-edit"></i> بياناتي</a>
            <a class="nav-link" href="#prefs"><i class="fas fa-sliders-h"></i> التفضيلات</a>
            <a class="nav-link" href="#orders"><i class="fas fa-box"></i> طلباتي</a>
            <a class="nav-link" href="#tickets"><i class="fas fa-life-ring"></i> تذاكري</a>
            <a class="nav-link" href="#security"><i class="fas fa-shield-alt"></i> الأمان</a>
            <a class="nav-link" href="#notifications"><i class="fas fa-bell"></i> الإشعارات</a>
            <a class="nav-link" href="#account"><i class="fas fa-user-circle"></i> الحساب والصورة</a>
        </nav>
        <hr>
        <div class="d-grid">
            <a href="<?= e(app_href('logout.php')) ?>" class="btn btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
    </aside>

    <main class="main-content">
        <!-- Overview -->
        <section id="overview" class="active">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">نظرة عامة</h5>
                    <p class="mb-2">مرحباً، <strong><?= $user ?></strong></p>
                    <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                        <span>توثيق البريد: <strong class="text-<?= !empty($emailVerified) ? 'success' : 'danger' ?>"><?= !empty($emailVerified) ? 'موثّق' : 'غير موثّق' ?></strong></span>
                        <span>توثيق الجوال: <strong class="text-<?= !empty($phoneVerified) ? 'success' : 'danger' ?>"><?= !empty($phoneVerified) ? 'موثّق' : 'غير موثّق' ?></strong></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اكتمال الملف</label>
                        <?php $completion = isset($profileCompletion) ? (int)$profileCompletion : 80; ?>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $completion ?>%" aria-valuenow="<?= $completion ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= e(app_href('order/new')) ?>" class="btn btn-main btn-sm">طلب جديد</a>
                        <a href="<?= e(app_href('track.php')) ?>" class="btn btn-outline-secondary btn-sm">تتبع الطلب</a>
                        <a href="<?= e(app_href('faq')) ?>" class="btn btn-light btn-sm">الأسئلة الشائعة</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile -->
        <section id="profile">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">بياناتي</h5>
                    <?php
                    $form_data = $_SESSION['form_data'] ?? [];
                    $form_errors = $_SESSION['form_errors'] ?? [];
                    unset($_SESSION['form_data'], $_SESSION['form_errors']);
                    ?>
                    <?php if (isset($_GET['profile_saved'])): ?>
                        <div class="alert alert-success py-2 mb-3">تم حفظ بياناتك بنجاح</div>
                    <?php elseif (!empty($form_errors['general'])): ?>
                        <div class="alert alert-danger py-2 mb-3"><?= e($form_errors['general']) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= e(app_href('account/profile/update.php')) ?>" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" name="full_name" class="form-control input-soft <?= isset($form_errors['full_name']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['full_name'] ?? $profile['full_name'] ?? '') ?>" />
                                <?php if (isset($form_errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= e($form_errors['full_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control input-soft <?= isset($form_errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['email'] ?? $profile['email'] ?? '') ?>" />
                                <?php if (isset($form_errors['email'])): ?>
                                    <div class="invalid-feedback"><?= e($form_errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الجوال</label>
                                <input type="tel" name="phone" class="form-control input-soft <?= isset($form_errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['phone'] ?? $profile['phone'] ?? '') ?>" />
                                <?php if (isset($form_errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= e($form_errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-main" type="submit">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Preferences -->
        <section id="prefs">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">التفضيلات</h5>
                    <form method="post" action="<?= e(app_href('account/preferences/update')) ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">اللغة</label>
                                <select name="lang" class="form-select input-soft" onchange="this.form.submit()">
                                    <option value="ar" <?= (($prefs['lang'] ?? 'ar')==='ar')?'selected':''; ?>>العربية</option>
                                    <option value="en" <?= (($prefs['lang'] ?? '')==='en')?'selected':''; ?>>English</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">المنطقة الزمنية</label>
                                <select name="tz" class="form-select input-soft" onchange="this.form.submit()">
                                    <option value="Asia/Riyadh" <?= (($prefs['tz'] ?? '')==='Asia/Riyadh')?'selected':''; ?>>Asia/Riyadh</option>
                                    <option value="UTC" <?= (($prefs['tz'] ?? '')==='UTC')?'selected':''; ?>>UTC</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">المظهر</label>
                                <select name="theme" class="form-select input-soft" onchange="this.form.submit()">
                                    <option value="light" <?= (($prefs['theme'] ?? 'light')==='light')?'selected':''; ?>>فاتح</option>
                                    <option value="dark" <?= (($prefs['theme'] ?? '')==='dark')?'selected':''; ?>>داكن</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mt-2">يحفظ فوراً وتُطبّق الواجهة عند التغيير</div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Orders -->
        <section id="orders">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">طلباتي</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th>الإجمالي</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($orders ?? []) as $o): ?>
                                    <tr>
                                        <td>#<?= e($o['id'] ?? '-') ?></td>
                                        <td><?= e($o['date'] ?? '-') ?></td>
                                        <td><span class="badge bg-light text-dark"><?= e($o['status'] ?? '-') ?></span></td>
                                        <td><?= e($o['total_short'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <?php $trk = !empty($o['tracking_code']) ? ('?code=' . urlencode($o['tracking_code'])) : ''; ?>
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_href('track.php' . $trk)) ?>">تتبع</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">لا توجد طلبات بعد</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tickets -->
        <section id="tickets">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title m-0">تذاكري (الدعم)</h5>
                        <a href="<?= e(app_href('support/new_ticket.php')) ?>" class="btn btn-sm btn-main">تذكرة جديدة</a>
                    </div>
                    <div class="row g-3">
                        <?php foreach (($tickets ?? []) as $t): ?>
                            <div class="col-12">
                                <div class="card card-dashboard card-ticket">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1"><a href="<?= e(app_href('support/ticket/view?id=' . urlencode($t['id'] ?? ''))) ?>" class="text-decoration-none"><?= e($t['subject'] ?? '-') ?></a></h6>
                                            <?php
                                                $statusClass = 'bg-secondary';
                                                switch ($t['status'] ?? '') {
                                                    case 'open': $statusClass = 'bg-success'; break;
                                                    case 'pending': $statusClass = 'bg-warning text-dark'; break;
                                                    case 'closed': $statusClass = 'bg-danger'; break;
                                                }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= e($t['status'] ?? '-') ?></span>
                                        </div>
                                        <small class="text-muted">آخر تحديث: <?= e($t['updated_at'] ?? '-') ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($tickets)): ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center py-4 mb-0">لا توجد تذاكر دعم فني حالياً.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security -->
        <section id="security">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">الأمان</h5>
                    <div class="border rounded p-3">
                        <div class="fw-bold mb-2">توثيق رقم الجوال</div>
                        <p class="text-muted small">الحالة الحالية: <?= !empty($phoneVerified)?'<span class="text-success">موثّق</span>':'<span class="text-danger">غير موثّق</span>' ?></p>
                        <form method="post" action="<?= e(app_href('account/verify/send')) ?>" class="mb-3">
                            <button class="btn btn-main btn-sm" name="channel" value="phone">إرسال رمز التحقق إلى جوالك</button>
                        </form>
                        <form method="post" action="<?= e(app_href('account/verify/check')) ?>">
                            <div class="input-group input-group-sm" style="max-width:320px;">
                                <input type="text" name="code" class="form-control input-soft" placeholder="رمز التحقق" required />
                                <button class="btn btn-main">تحقق من الرمز</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Notifications -->
        <section id="notifications">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">الإشعارات</h5>
                    <div class="list-group mb-3">
                        <?php foreach (($notifications ?? []) as $n): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= e($n['title'] ?? '-') ?></h6>
                                    <small class="text-muted"><?= e($n['time'] ?? '') ?></small>
                                </div>
                                <p class="mb-1 small text-muted"><?= e($n['message'] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($notifications)): ?>
                            <div class="list-group-item text-muted">لا توجد إشعارات جديدة</div>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(app_href('account/notify/prefs')) ?>" class="d-flex flex-wrap gap-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="notify_email" id="notify_email" <?= !empty($notifyPrefs['email'])?'checked':''; ?> />
                            <label class="form-check-label" for="notify_email">تلقي الإشعارات عبر البريد الإلكتروني</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="notify_sms" id="notify_sms" <?= !empty($notifyPrefs['sms'])?'checked':''; ?> />
                            <label class="form-check-label" for="notify_sms">تلقي الإشعTشعارات عبر الرسائل النصية</label>
                        </div>
                        <button class="btn btn-main btn-sm ms-auto" type="submit">حفظ التفضيلات</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Account & Avatar -->
        <section id="account">
            <div class="card card-dashboard">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">الحساب والصورة الشخصية</h5>
                    <?php if (isset($_GET['avatar_saved'])): ?>
                        <div class="alert alert-success py-2 mb-3">تم تحديث صورتك بنجاح</div>
                    <?php elseif (isset($_GET['avatar_error'])): ?>
                        <div class="alert alert-danger py-2 mb-3"><?= e($_GET['avatar_error']) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= e(app_href('account/avatar/update')) ?>" enctype="multipart/form-data">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <?php 
                            // جلب مسار الصورة من قاعدة البيانات أو من الـ session
                            $avatarUrl = null;
                            if (!empty($_SESSION['avatar_path'])) {
                                $avatarUrl = $_SESSION['avatar_path'];
                            } else {
                                try {
                                    $db_avatar = pdo_open('users');
                                    $st_avatar = $db_avatar->prepare('SELECT avatar_path FROM users WHERE id = ?');
                                    $st_avatar->execute([(int)$_SESSION['user']['id']]);
                                    $user_avatar = $st_avatar->fetch(PDO::FETCH_ASSOC);
                                    if ($user_avatar && !empty($user_avatar['avatar_path'])) {
                                        $avatarUrl = $user_avatar['avatar_path'];
                                        $_SESSION['avatar_path'] = $avatarUrl; // حفظ في الـ session للمرة القادمة
                                    }
                                } catch (Throwable $e) {
                                    error_log("Error fetching avatar: " . $e->getMessage());
                                }
                            }
                            // استخدام asset_href إذا كان المسار نسبي
                            if ($avatarUrl && strpos($avatarUrl, 'http://') !== 0 && strpos($avatarUrl, 'https://') !== 0) {
                                $avatarUrl = asset_href($avatarUrl);
                            }
                            ?>
                            <img src="<?= e($avatarUrl ?: asset_href('assets/img/avatar-placeholder.png')) ?>" alt="avatar" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;" />
                            <div>
                                <label for="avatar-upload" class="btn btn-sm btn-main">اختيار صورة</label>
                                <input type="file" id="avatar-upload" name="avatar" accept="image/png,image/jpeg,image/webp" class="visually-hidden" onchange="this.form.submit()" />
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(app_href('account/avatar/delete')) ?>">حذف</a>
                                <div class="form-text mt-2">الأنواع المسموحة: JPEG/PNG/WebP • الحد الأقصى: 2MB</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    const sections = document.querySelectorAll('.main-content section');

    function showSection(hash) {
        sections.forEach(section => {
            if ('#' + section.id === hash) {
                section.classList.add('active');
            } else {
                section.classList.remove('active');
            }
        });
        navLinks.forEach(link => {
            if (link.getAttribute('href') === hash) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            showSection(targetId);
            window.history.pushState(null, null, targetId);
        });
    });

    // Show section based on URL hash on page load
    let hash = window.location.hash;
    if (!hash) {
        hash = '#overview'; // Default section
    }
    showSection(hash);
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>