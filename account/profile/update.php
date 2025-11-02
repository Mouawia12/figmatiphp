<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';

session_start();
if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('login.php'));
    exit;
}

$back = app_href('dashboard.php');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $back . '#profile');
    exit;
}

// Store submitted data in session to re-populate form
$_SESSION['form_data'] = $_POST;

$full_name = trim((string)($_POST['full_name'] ?? ''));
$email     = strtolower(trim((string)($_POST['email'] ?? '')));
$phone     = trim((string)($_POST['phone'] ?? ''));

// --- Validation ---
$errors = [];
if (empty($full_name)) {
    $errors['full_name'] = 'الاسم الكامل مطلوب.';
}

if (empty($email)) {
    $errors['email'] = 'البريد الإلكتروني مطلوب.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'صيغة البريد الإلكتروني غير صالحة.';
}

if (empty($phone)) {
    $errors['phone'] = 'رقم الجوال مطلوب.';
} elseif (!ksa_local($phone)) {
    $errors['phone'] = 'أدخل رقم سعودي بصيغة 05XXXXXXXX.';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: ' . $back);
    exit;
}

// --- Database Update Logic ---
try {
    $db = pdo_open('users');
    $currentId = (int)($_SESSION['user']['id'] ?? 0);

    if (!$currentId) {
        throw new RuntimeException('تعذر تحديد الحساب الحالي.');
    }

    // Check for uniqueness (email and phone_local)
    $stmt = $db->prepare('SELECT id FROM users WHERE (email = ? OR phone_local = ?) AND id != ?');
    $stmt->execute([$email, $phone, $currentId]);
    if ($stmt->fetch()) {
        // This is a simplified check. A more robust implementation would check fields separately.
        throw new RuntimeException('البريد الإلكتروني أو رقم الجوال مستخدم بالفعل من قبل حساب آخر.');
    }

    // Prepare values according to actual schema: name, email, phone_local, phone_e164
    $phone_e164 = to_e164_sa($phone);

    $sql = 'UPDATE users SET name = ?, email = ?, phone_local = ?, phone_e164 = ? WHERE id = ?';
    $stmt = $db->prepare($sql);
    $stmt->execute([$full_name, $email, $phone, $phone_e164, $currentId]);

    // --- Also update the session ---
    $_SESSION['user']['name']  = $full_name;
    $_SESSION['user']['email'] = $email;

    // Clear form data on success
    unset($_SESSION['form_data']);
    unset($_SESSION['form_errors']);

    header('Location: ' . $back . '?profile_saved=1#profile');
    exit;

} catch (Throwable $e) {
    $_SESSION['form_errors'] = ['general' => $e->getMessage()];
    header('Location: ' . $back . '#profile');
    exit;
}
