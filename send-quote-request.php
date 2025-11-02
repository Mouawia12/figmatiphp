<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_href('request-for-quote.php'));
    exit;
}

if (empty($_SESSION['user']['id'] ?? null)) {
    header('Location: ' . app_href('request-for-quote.php?err=' . urlencode('يجب تسجيل الدخول أولاً')));
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: ' . app_href('request-for-quote.php?err=' . urlencode('خطأ في التحقق من الأمان')));
    exit;
}

$form_id = (int)($_POST['form_id'] ?? 0);
$redirect_url = app_href('request-for-quote.php');

try {
    if (empty($_FILES['quote_file']['name'])) {
        throw new RuntimeException('الرجاء اختيار ملف إكسيل لرفعه.');
    }

    $max_size = 10 * 1024 * 1024; // 10 MB
    $allowed_exts = ['xls', 'xlsx'];
    $saved_file_name = handle_upload_limit($_FILES['quote_file'], $max_size, $allowed_exts);
    $original_file_name = basename((string)($_FILES['quote_file']['name'] ?? ''));

    ensure_requests_schema();
    $dbr = pdo_open('requests');

    $user_name = $_SESSION['user']['name'] ?? 'مستخدم غير معروف';
    $user_email = $_SESSION['user']['email'] ?? '';

    $payload = [
        'fields' => ['submitter_name' => $user_name, 'submitter_email' => $user_email],
        'files' => ['quote_file' => ['saved' => $saved_file_name, 'orig' => $original_file_name]]
    ];

    $track_code = gen_tracking_code();
    $now_db = (cfg()->db_driver === 'mysql') ? 'NOW()' : 'datetime("now")';

    $st = $dbr->prepare("INSERT INTO requests (form_id, name, email, data_json, tracking_code, created_at) VALUES (?, ?, ?, ?, ?, {$now_db})");
    $st->execute([
        $form_id,
        $user_name,
        $user_email,
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        $track_code
    ]);
    $new_request_id = $dbr->lastInsertId();

    if (function_exists('authentica_send_sms')) {
        try {
            $dbu = pdo_open('users');
            $st_user = $dbu->prepare("SELECT phone_local FROM users WHERE id = ?");
            $st_user->execute([$_SESSION['user']['id']]);
            $user_phone = $st_user->fetchColumn();

            if ($user_phone) {
                $sms_message = "عميلنا العزيز، تم استلام طلب عرض السعر الخاص بك بنجاح. رقم التتبع هو: {$track_code}";
                $sms_result = authentica_send_sms($user_phone, $sms_message);
                if (!($sms_result['success'] ?? false)) {
                    error_log("SMS sending failed for quote request #{$new_request_id}. Error: " . ($sms_result['message'] ?? 'Unknown error'));
                }
            } else {
                error_log("SMS not sent for quote request #{$new_request_id}: User phone not found.");
            }
        } catch (Throwable $e) {
            error_log("SMS sending failed for quote request #{$new_request_id} (exception caught): " . $e->getMessage());
        }
    }

    header('Location: ' . $redirect_url . '?ok=1');
    exit;

} catch (Throwable $e) {
    $error_message = urlencode($e->getMessage());
    header('Location: ' . $redirect_url . '?err=' . $error_message);
    exit;
}