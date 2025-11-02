<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';

session_start();
if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('../../login.php'));
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$errors = [];
$form_data = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form_data = $_POST;

    $label = trim((string)($_POST['label'] ?? ''));
    $country = trim((string)($_POST['country'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $street = trim((string)($_POST['street'] ?? ''));
    $zip = trim((string)($_POST['zip'] ?? ''));
    $type = trim((string)($_POST['type'] ?? 'home'));
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Validation
    if (empty($label)) $errors['label'] = 'الاسم الوصفي مطلوب.';
    if (empty($country)) $errors['country'] = 'الدولة مطلوبة.';
    if (empty($city)) $errors['city'] = 'المدينة مطلوبة.';
    if (empty($street)) $errors['street'] = 'الشارع مطلوب.';

    if (empty($errors)) {
        try {
            $db = pdo_open('users');

            // If setting this as default, unset other defaults first
            if ($is_default) {
                $stmt = $db->prepare('UPDATE customer_addresses SET is_default = 0 WHERE user_id = ?');
                $stmt->execute([$user_id]);
            }

            $sql = 'INSERT INTO customer_addresses (user_id, label, country, city, street, zip, type, is_default, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id, $label, $country, $city, $street, $zip, $type, $is_default]);

            header('Location: ' . app_href('../../dashboard.php#addresses&address_saved=1'));
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'حدث خطأ أثناء حفظ العنوان. الرجاء المحاولة مرة أخرى.';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة عنوان جديد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="app-bg">
<div class="container py-5" style="max-width: 800px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="card-title mb-4">إضافة عنوان جديد</h4>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="label" class="form-label">الاسم الوصفي (e.g., المنزل, العمل)</label>
                        <input type="text" id="label" name="label" class="form-control <?= isset($errors['label']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['label'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= e($errors['label'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label">النوع</label>
                        <select id="type" name="type" class="form-select">
                            <option value="home" <?= (($form_data['type'] ?? '') === 'home') ? 'selected' : '' ?>>منزل</option>
                            <option value="work" <?= (($form_data['type'] ?? '') === 'work') ? 'selected' : '' ?>>عمل</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="country" class="form-label">الدولة</label>
                        <input type="text" id="country" name="country" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['country'] ?? 'المملكة العربية السعودية') ?>" required>
                        <div class="invalid-feedback"><?= e($errors['country'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="city" class="form-label">المدينة</label>
                        <input type="text" id="city" name="city" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['city'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= e($errors['city'] ?? '') ?></div>
                    </div>
                    <div class="col-md-8">
                        <label for="street" class="form-label">الشارع</label>
                        <input type="text" id="street" name="street" class="form-control <?= isset($errors['street']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['street'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= e($errors['street'] ?? '') ?></div>
                    </div>
                    <div class="col-md-4">
                        <label for="zip" class="form-label">الرمز البريدي</label>
                        <input type="text" id="zip" name="zip" class="form-control" value="<?= e($form_data['zip'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" <?= isset($form_data['is_default']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">تعيين كعنوان افتراضي</label>
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(app_href('../../dashboard.php#addresses')) ?>" class="btn btn-light">إلغاء</a>
                    <button type="submit" class="btn btn-primary">حفظ العنوان</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

