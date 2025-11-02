<?php
declare(strict_types=1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../inc/functions.php';

session_start();
if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('../login.php'));
    exit;
}

ensure_support_tables_exist();

$user_id = (int)$_SESSION['user']['id'];
$errors = [];
$form_data = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form_data = $_POST;

    $subject = trim((string)($_POST['subject'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $priority = trim((string)($_POST['priority'] ?? 'medium'));
    $message = trim((string)($_POST['message'] ?? ''));

    // Validation
    if (empty($subject)) $errors['subject'] = 'الموضوع مطلوب.';
    if (empty($category)) $errors['category'] = 'الفئة مطلوبة.';
    if (empty($message)) $errors['message'] = 'الرسالة مطلوبة.';

    if (empty($errors)) {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            $errors['general'] = 'خطأ في التحقق من CSRF. يرجى المحاولة مرة أخرى.';
        } else {
            try {
                $db = pdo_open('users');

                // Insert the ticket
                $stmt = $db->prepare('INSERT INTO support_tickets (user_id, subject, category, priority) VALUES (?, ?, ?, ?)');
                $stmt->execute([$user_id, $subject, $category, $priority]);
                $ticket_id = (int)$db->lastInsertId();

                // Insert the message
                $stmt = $db->prepare('INSERT INTO support_ticket_messages (ticket_id, author_type, author_id, message) VALUES (?, ?, ?, ?)');
                $stmt->execute([$ticket_id, 'user', $user_id, $message]);

                header('Location: ./ticket/view.php?id=' . $ticket_id . '&ticket_created=1');
                exit;
            } catch (Throwable $e) {
                $errors['general'] = 'حدث خطأ أثناء إنشاء التذكرة: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'فتح تذكرة دعم جديدة';
require __DIR__ . '/../partials/header.php';
?>
<div class="container py-5">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-12">
            <div class="card card-auth fade-in">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">فتح تذكرة دعم جديدة</h4>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="subject" class="form-label">الموضوع</label>
                        <input type="text" id="subject" name="subject" class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" value="<?= e($form_data['subject'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= e($errors['subject'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">الفئة</label>
                        <select id="category" name="category" class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>">
                            <option value="">-- اختر فئة --</option>
                            <option value="technical" <?= (($form_data['category'] ?? '') === 'technical') ? 'selected' : '' ?>>مشكلة تقنية</option>
                            <option value="billing" <?= (($form_data['category'] ?? '') === 'billing') ? 'selected' : '' ?>>المدفوعات والفواتير</option>
                            <option value="general" <?= (($form_data['category'] ?? '') === 'general') ? 'selected' : '' ?>>استفسار عام</option>
                        </select>
                        <div class="invalid-feedback"><?= e($errors['category'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="priority" class="form-label">الأولوية</label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="low" <?= (($form_data['priority'] ?? '') === 'low') ? 'selected' : '' ?>>منخفضة</option>
                            <option value="medium" <?= (($form_data['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>متوسطة</option>
                            <option value="high" <?= (($form_data['priority'] ?? '') === 'high') ? 'selected' : '' ?>>عالية</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="message" class="form-label">الرسالة</label>
                        <textarea id="message" name="message" class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" rows="5" required><?= e($form_data['message'] ?? '') ?></textarea>
                        <div class="invalid-feedback"><?= e($errors['message'] ?? '') ?></div>
                    </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(app_href('../dashboard.php#tickets')) ?>" class="btn btn-light">إلغاء</a>
                    <button type="submit" class="btn btn-primary">إرسال التذكرة</button>
                </div>
            </form>
<?php require __DIR__ . '/../partials/footer.php'; ?>