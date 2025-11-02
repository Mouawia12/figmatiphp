<?php
/**
 * إرسال رسالة SMS باستخدام خدمة Authentica
 */
function send_customer_sms($phone, $message) {
    try {
        $api_key = getenv('AUTHENTICA_API_KEY');
        $sender = getenv('SMS_SENDER') ?: 'CROSING';
        $phone = to_e164_sa($phone);
        
        $result = authentica_send_sms($phone, $message);
        
        // تسجيل محاولة الإرسال في سجلات النظام
        log_sms($phone, $message, $result);
        
        return $result['success'] ?? false;
    } catch (Exception $e) {
        error_log("فشل إرسال SMS: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل تفاصيل إرسال الرسائل النصية
 */
function log_sms($to, $message, $response) {
    try {
        $pdo = pdo_open();
        $stmt = $pdo->prepare(
            "INSERT INTO sms_logs (phone, message, status, response, created_at) " .
            "VALUES (?, ?, ?, ?, datetime('now'))"
        );
        
        $status = ($response['success'] ?? false) ? 'sent' : 'failed';
        $response_json = json_encode($response);
        
        $stmt->execute([$to, $message, $status, $response_json]);
    } catch (Exception $e) {
        error_log("فشل تسجيل سجل SMS: " . $e->getMessage());
    }
}

/**
 * إرسال إشعار بإنشاء طلب جديد
 */
function send_new_request_notification($request_id, $customer_phone) {
    $message = "عزيزنا العميل، تم استلام طلبك رقم #$request_id بنجاح. سنقوم بالتواصل معك قريباً. شكراً لثقتك بنا.";
    return send_customer_sms($customer_phone, $message);
}

/**
 * إرسال ملاحظة للعميل
 */
function send_customer_note($request_id, $customer_phone, $note) {
    $message = "عزيزنا العميل، تمت إضافة ملاحظة جديدة على طلبك #$request_id: \n$note";
    return send_customer_sms($customer_phone, $message);
}
