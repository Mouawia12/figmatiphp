<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';

session_start();
if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('../../login.php'));
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$address_id = (int)($_REQUEST['id'] ?? 0);
$back_url = app_href('../../dashboard.php#addresses');

if ($address_id <= 0) {
    header('Location: ' . $back_url);
    exit;
}

try {
    $db = pdo_open('users');
    // Fetch the address to make sure it belongs to the user
    $stmt = $db->prepare('SELECT * FROM customer_addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$address_id, $user_id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$address) {
        // Address not found or doesn't belong to the user
        header('Location: ' . $back_url . '&error=not_found');
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        // CSRF token validation (simple implementation)
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed.');
        }

        // Delete the address
        $stmt = $db->prepare('DELETE FROM customer_addresses WHERE id = ? AND user_id = ?');
        $stmt->execute([$address_id, $user_id]);

        header('Location: ' . $back_url . '&address_deleted=1');
        exit;
    }
} catch (Throwable $e) {
    header('Location: ' . $back_url . '&error=delete_failed');
    exit;
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد حذف العنوان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css')) ?>">
</head>
<body class="app-bg">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="card-title mb-4">تأكيد الحذف</h4>
            <p>هل أنت متأكد من أنك تريد حذف هذا العنوان؟</p>
            <div class="alert alert-warning">
                <strong><?= e($address['label']) ?>:</strong><br>
                <?= e($address['street']) ?>, <?= e($address['city']) ?>, <?= e($address['country']) ?>
            </div>
            <form method="post">
                <input type="hidden" name="id" value="<?= e($address_id) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= e($back_url) ?>" class="btn btn-light">إلغاء</a>
                    <button type="submit" class="btn btn-danger">نعم، قم بالحذف</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>