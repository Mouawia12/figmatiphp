<?php
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
$me = require_admin();

$notice = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'خطأ في التحقق من الأمان (CSRF)';
    } else {
        try {
            $name     = trim((string)($_POST['name'] ?? ''));
            $email    = trim((string)($_POST['email'] ?? ''));
            $phone    = trim((string)($_POST['phone'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role     = in_array($_POST['role'] ?? 'user', ['admin', 'employee', 'user']) ? $_POST['role'] : 'user';
            $permissions = ($role === 'employee') ? json_encode($_POST['permissions'] ?? []) : null;

            if (empty($name) || empty($email) || empty($password)) throw new RuntimeException('الاسم، البريد الإلكتروني، وكلمة المرور حقول إلزامية.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('صيغة البريد الإلكتروني غير صحيحة.');
            if (!empty($phone) && !ksa_local($phone)) throw new RuntimeException('صيغة رقم الجوال غير صحيحة (يجب أن تكون 05XXXXXXXX).');
            if (user_find_by_email($email)) throw new RuntimeException('هذا البريد الإلكتروني مسجل مسبقًا.');

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $phone_e164 = !empty($phone) ? to_e164_sa($phone) : null;

            $st = db()->prepare('INSERT INTO users (name, email, password_hash, phone_local, phone_e164, role, permissions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            $st->execute([$name, $email, $hash, $phone, $phone_e164, $role, $permissions]);

            $notice = "✅ تم إنشاء المستخدم بنجاح: {$name}";

        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$users = get_all_users();

$page_title = 'المستخدمون';
$content = function () use ($users, $notice, $error, $me) { ?>

<?php if($notice): ?><div class="alert alert-success text-white font-weight-bold"><?= e($notice) ?></div><?php endif; ?>
<?php if($error):  ?><div class="alert alert-danger text-white font-weight-bold"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <!-- Create User Card -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">إنشاء مستخدم جديد</h6>
            </div>
            <div class="card-body">
                <form method="post" id="createUserForm">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" name="name" placeholder="مثال: خالد محمد" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" name="email" placeholder="example@company.com" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">رقم الجوال (اختياري)</label>
                                <input type="text" class="form-control" name="phone" placeholder="05XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">الدور</label>
                                <select class="form-select" name="role" id="roleSelector">
                                    <option value="employee" selected>موظف</option>
                                    <option value="admin">مدير</option>
                                    <option value="user">عميل</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Permissions -->
                    <div id="permissionsContainer" class="mt-3">
                        <h6 class="text-sm">صلاحيات الموظف</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="view_requests" id="perm1">
                                    <label class="form-check-label" for="perm1">عرض الطلبات</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="edit_requests" id="perm2">
                                    <label class="form-check-label" for="perm2">تعديل الطلبات</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="view_forms" id="perm3">
                                    <label class="form-check-label" for="perm3">عرض النماذج</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="view_users" id="perm4">
                                    <label class="form-check-label" for="perm4">عرض المستخدمين</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn bg-gradient-primary mb-0">إنشاء المستخدم</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Users List Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6>قائمة المستخدمين (<?= (int)count($users) ?>)</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المستخدم</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">الدور</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">تاريخ الإنشاء</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$users): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">لا يوجد مستخدمون حاليًا.</td></tr>
                            <?php else: foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div>
                                                <span class="avatar avatar-sm bg-gradient-secondary me-3 d-flex align-items-center justify-content-center">U</span>
                                            </div>
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">
                                                    <?= e($user['name'] ?? '') ?>
                                                    <?= ($user['id']==($me['id']??0)) ? '<span class="badge badge-sm bg-gradient-success ms-1">أنت</span>' : '' ?>
                                                </h6>
                                                <p class="text-xs text-secondary mb-0"><?= e($user['email'] ?? '') ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $role = $user['role'] ?? 'user';
                                            $role_text = ucfirst($role);
                                            $role_color = 'secondary';
                                            if ($role === 'admin') { $role_color = 'primary'; $role_text = 'مدير'; }
                                            if ($role === 'employee') { $role_color = 'info'; $role_text = 'موظف'; }
                                            if ($role === 'user') { $role_text = 'عميل'; }
                                        ?>
                                        <span class="badge badge-sm bg-gradient-<?= $role_color ?>"><?= e($role_text) ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?= date('Y-m-d', strtotime($user['created_at'] ?? time())) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="javascript:;" class="text-secondary font-weight-bold text-xs" data-bs-toggle="tooltip" data-bs-title="تعديل المستخدم">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:;" class="text-danger font-weight-bold text-xs ms-3" data-bs-toggle="tooltip" data-bs-title="حذف المستخدم">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelector = document.getElementById('roleSelector');
    const permissionsContainer = document.getElementById('permissionsContainer');

    function togglePermissions() {
        permissionsContainer.style.display = (roleSelector.value === 'employee') ? 'block' : 'none';
    }

    roleSelector.addEventListener('change', togglePermissions);
    togglePermissions(); // Initial check

    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
<?php };

include __DIR__ . '/_layout.php';