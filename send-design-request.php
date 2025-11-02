<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_href('interior-design-request.php'));
    exit;
}
if (empty($_SESSION['user']['id'] ?? null)) {
    header('Location: ' . app_href('interior-design-request.php?err=' . urlencode('يجب تسجيل الدخول أولاً')));
    exit;
}
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: ' . app_href('interior-design-request.php?err=' . urlencode('خطأ في التحقق من الأمان')));
    exit;
}

$form_id = 5;
$redirect_url = app_href('interior-design-request.php');

try {
    $design_style = trim($_POST['design_style'] ?? '');
    $area_sqm = filter_var($_POST['area_sqm'] ?? 0, FILTER_VALIDATE_FLOAT);
    $notes = trim($_POST['notes'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $site_visit = isset($_POST['site_visit']);

    if (empty($design_style) || $area_sqm <= 0 || empty($name) || empty($phone)) throw new RuntimeException('الرجاء تعبئة جميع الحقول المطلوبة (*).');
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('صيغة البريد الإلكتروني غير صحيحة.');
    if (!ksa_local($phone)) throw new RuntimeException('صيغة رقم الجوال غير صحيحة.');
    if (empty($_FILES['floor_plan']['name'])) throw new RuntimeException('الرجاء رفع مخطط أو صورة للمساحة.');
    
    $saved_file_name = handle_upload_limit($_FILES['floor_plan'], 10 * 1024 * 1024, ['pdf','jpg','jpeg','png']);
    $original_file_name = basename((string)($_FILES['floor_plan']['name'] ?? ''));

    define('PRICE_PER_METER', 5);
    define('SITE_VISIT_COST', 100);
    $calculated_design_cost = $area_sqm * PRICE_PER_METER;
    $calculated_visit_cost = $site_visit ? SITE_VISIT_COST : 0;
    $calculated_total_cost = $calculated_design_cost + $calculated_visit_cost;

    ensure_requests_schema();
    $dbr = pdo_open('requests');

    $payload = [
        'fields' => ['design_style' => $design_style, 'area_sqm' => $area_sqm, 'notes' => $notes, 'name' => $name, 'phone' => $phone, 'email' => $email, 'site_visit' => $site_visit ? 'نعم' : 'لا'],
        'files' => ['floor_plan' => ['saved' => $saved_file_name, 'orig' => $original_file_name]],
        'costs' => ['design_cost' => $calculated_design_cost, 'visit_cost' => $calculated_visit_cost, 'total_cost' => $calculated_total_cost]
    ];

    $track_code = gen_tracking_code();
    $now_db = (cfg()->db_driver === 'mysql') ? 'NOW()' : 'datetime("now")';

    $st = $dbr->prepare("INSERT INTO requests (form_id, name, email, data_json, tracking_code, created_at) VALUES (?, ?, ?, ?, ?, {$now_db})");
    $st->execute([$form_id, $name, $email, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $track_code]);
    $new_request_id = $dbr->lastInsertId();

    if (function_exists('authentica_send_sms') && !empty($phone)) {
        try {
            $sms_message = "عميلنا العزيز، تم استلام طلب التصميم الخاص بك بنجاح. التكلفة الإجمالية المقدرة: " . number_format($calculated_total_cost, 2) . " ريال. رقم التتبع هو: {$track_code}";
            $sms_result = authentica_send_sms($phone, $sms_message);
            if (!($sms_result['success'] ?? false)) {
                error_log("SMS sending failed for design request #{$new_request_id}. Error: " . ($sms_result['message'] ?? 'Unknown error'));
            }
        } catch (Throwable $e) {
            error_log("SMS sending failed for design request #{$new_request_id} (exception caught): " . $e->getMessage());
        }
    }

    header('Location: ' . $redirect_url . '?ok=1');
    exit;

} catch (Throwable $e) {
    $error_message = urlencode($e->getMessage());
    header('Location: ' . $redirect_url . '?err=' . $error_message);
    exit;
}