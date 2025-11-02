<?php
/**
 * نظام الإشعارات المركزي - عزم الإنجاز
 * 
 * هذا الملف يحتوي على دوال مركزية لإرسال SMS وEmail
 * عند إنشاء طلب جديد أو تحديث حالة الطلب
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email.php';

/**
 * استخراج معلومات العميل من بيانات الطلب
 */
function extract_customer_info(array $request): array {
    $info = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'phone_e164' => ''
    ];

    // الحصول على البيانات الأساسية
    $info['name'] = $request['name'] ?? '';
    $info['email'] = $request['email'] ?? '';

    // محاولة استخراج رقم الهاتف من data_json
    $payload = isset($request['data_json']) ? json_decode((string)$request['data_json'], true) : [];
    $fields = $payload['fields'] ?? [];

    // البحث عن رقم الهاتف في الحقول الشائعة
    $phone_keys = ['phone', 'tel', 'mobile', 'jawwal', 'phone_local', 'phone_reset'];
    foreach ($phone_keys as $key) {
        if (!empty($fields[$key])) {
            $info['phone'] = (string)$fields[$key];
            break;
        }
    }

    // إذا لم نجد، نحاول البحث في جميع الحقول
    if (!$info['phone'] && function_exists('ksa_local')) {
        foreach ($fields as $value) {
            if (is_string($value) && ksa_local($value)) {
                $info['phone'] = $value;
                break;
            }
        }
    }

    // إذا كان الطلب مربوط بمستخدم، نجرب جلب معلوماته
    if (!$info['phone'] && !empty($request['user_id'])) {
        try {
            $db = pdo_open('users');
            $stmt = $db->prepare('SELECT phone_local, phone_e164, email, name FROM users WHERE id = ?');
            $stmt->execute([(int)$request['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                if (!$info['phone']) {
                    $info['phone'] = $user['phone_local'] ?? '';
                    $info['phone_e164'] = $user['phone_e164'] ?? '';
                }
                if (!$info['email']) {
                    $info['email'] = $user['email'] ?? '';
                }
                if (!$info['name']) {
                    $info['name'] = $user['name'] ?? '';
                }
            }
        } catch (Throwable $e) {
            error_log('Failed to fetch user info for notifications: ' . $e->getMessage());
        }
    }

    // تحويل رقم الهاتف إلى E.164 إذا لم يكن موجوداً
    if ($info['phone'] && !$info['phone_e164'] && function_exists('to_e164_sa')) {
        $info['phone_e164'] = to_e164_sa($info['phone']);
    }

    return $info;
}

/**
 * إنشاء رابط التتبع
 */
function build_tracking_link(string $tracking_code): string {
    $trackPath = ltrim(app_href('track.php'), '/');
    return public_url($trackPath) . '?code=' . urlencode($tracking_code);
}

/**
 * إرسال إشعار عند إنشاء طلب جديد
 */
function send_new_request_notification(array $request): array {
    $results = [
        'sms' => ['sent' => false, 'error' => null],
        'email' => ['sent' => false, 'error' => null]
    ];

    try {
        $customer = extract_customer_info($request);
        $tracking_code = $request['tracking_code'] ?? '';
        
        if ($tracking_code === '') {
            $tracking_code = gen_tracking_code();
            // تحديث tracking_code في قاعدة البيانات
            try {
                $db = pdo_open('requests');
                $db->prepare('UPDATE requests SET tracking_code = ? WHERE id = ?')
                   ->execute([$tracking_code, (int)$request['id']]);
            } catch (Throwable $e) {
                error_log('Failed to update tracking code: ' . $e->getMessage());
            }
        }

        $tracking_link = build_tracking_link($tracking_code);
        $customer_name = $customer['name'] ?: 'عميلنا الكريم';

        // إعداد رسالة SMS
        $sms_message = "عميلنا العزيز {$customer_name}،\n";
        $sms_message .= "تم استلام طلبك بنجاح.\n";
        $sms_message .= "رقم التتبع: {$tracking_code}\n";
        $sms_message .= "تتبع الطلب: {$tracking_link}\n";
        $sms_message .= "الحالة الحالية: قيد الانتظار.\n";
        $sms_message .= "شكراً لثقتك بنا.";

        // إرسال SMS
        if ($customer['phone'] && function_exists('authentica_send_sms')) {
            try {
                $sms_result = authentica_send_sms(
                    $customer['phone'], 
                    $sms_message,
                    'new_request',
                    [
                        'request_id' => $request['id'] ?? null,
                        'tracking_code' => $tracking_code,
                        'customer_name' => $customer_name,
                        'link' => $tracking_link,
                    ]
                );
                $results['sms']['sent'] = ($sms_result['success'] ?? false);
                if (!$results['sms']['sent']) {
                    $results['sms']['error'] = $sms_result['message'] ?? 'Unknown error';
                    error_log("SMS sending failed for request #{$request['id']}: " . json_encode($sms_result));
                }
            } catch (Throwable $e) {
                $results['sms']['error'] = $e->getMessage();
                error_log("SMS sending exception for request #{$request['id']}: " . $e->getMessage());
                // تسجيل الخطأ في ملف السجل
                @file_put_contents(__DIR__ . '/../sms_errors.log', 
                    "[" . date('Y-m-d H:i:s') . "] EXCEPTION in send_new_request_notification: " . $e->getMessage() . " | Request ID: " . ($request['id'] ?? 'N/A') . "\n", 
                    FILE_APPEND | LOCK_EX
                );
            }
        } else {
            $results['sms']['error'] = 'No phone number found or SMS function not available';
            // تسجيل عدم وجود رقم هاتف
            @file_put_contents(__DIR__ . '/../sms_errors.log', 
                "[" . date('Y-m-d H:i:s') . "] WARNING: No phone number found for request #" . ($request['id'] ?? 'N/A') . " | Customer: {$customer_name}\n", 
                FILE_APPEND | LOCK_EX
            );
        }

        // إعداد رسالة Email
        $email_subject = "تأكيد استلام الطلب - رقم التتبع: {$tracking_code}";
        $email_body = "
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #17c1cc; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                .tracking-code { background-color: #fff; padding: 15px; margin: 15px 0; border-radius: 5px; text-align: center; font-size: 18px; font-weight: bold; color: #17c1cc; }
                .button { display: inline-block; background-color: #17c1cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>شركة عزم الإنجاز</h2>
                </div>
                <div class='content'>
                    <p>مرحباً {$customer_name},</p>
                    <p>نشكرك على استخدام خدماتنا. تم استلام طلبك وهو الآن قيد المراجعة.</p>
                    
                    <div class='tracking-code'>
                        رقم التتبع: {$tracking_code}
                    </div>
                    
                    <p>يمكنك متابعة حالة طلبك عبر الرابط التالي:</p>
                    <p style='text-align: center;'>
                        <a href='{$tracking_link}' class='button'>تتبع الطلب</a>
                    </p>
                    
                    <p>أو انسخ هذا الرابط في المتصفح:</p>
                    <p style='background-color: #fff; padding: 10px; border-radius: 5px; word-break: break-all;'>{$tracking_link}</p>
                    
                    <p>شكراً لثقتك بنا.</p>
                    <p>فريق عزم الإنجاز</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " شركة عزم الإنجاز. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";

        // إرسال Email
        if ($customer['email']) {
            try {
                $config = cfg();
                $email_sent = false;
                
                // محاولة استخدام دالة send_email إذا كانت موجودة
                if (function_exists('send_email')) {
                    $email_sent = send_email(
                        $customer['email'],
                        $email_subject,
                        $email_body,
                        'شركة عزم الإنجاز',
                        $config->mail_to ?? 'noreply@azmalenjaz.com'
                    );
                } else {
                    // استخدام mail() الافتراضية كبديل
                    $email_sent = @mail(
                        $customer['email'],
                        $email_subject,
                        strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body)),
                        safe_mail_headers($config->mail_to ?? 'noreply@azmalenjaz.com')
                    );
                }
                
                $results['email']['sent'] = $email_sent;
                if (!$email_sent) {
                    $results['email']['error'] = 'Failed to send email';
                    error_log("Email sending failed for request #{$request['id']} to {$customer['email']}");
                }
            } catch (Throwable $e) {
                $results['email']['error'] = $e->getMessage();
                error_log("Email sending exception for request #{$request['id']}: " . $e->getMessage());
            }
        } else {
            $results['email']['error'] = 'No email address found';
        }

    } catch (Throwable $e) {
        error_log("Failed to send new request notification: " . $e->getMessage());
    }

    return $results;
}

/**
 * إرسال إشعار عند تحديث حالة الطلب
 */
function send_status_update_notification(array $request, string $new_status, ?string $note = null): array {
    $results = [
        'sms' => ['sent' => false, 'error' => null],
        'email' => ['sent' => false, 'error' => null]
    ];

    try {
        $customer = extract_customer_info($request);
        $tracking_code = $request['tracking_code'] ?? '';
        
        if ($tracking_code === '') {
            $tracking_code = gen_tracking_code();
            try {
                $db = pdo_open('requests');
                $db->prepare('UPDATE requests SET tracking_code = ? WHERE id = ?')
                   ->execute([$tracking_code, (int)$request['id']]);
            } catch (Throwable $e) {
                error_log('Failed to update tracking code: ' . $e->getMessage());
            }
        }

        $tracking_link = build_tracking_link($tracking_code);
        $customer_name = $customer['name'] ?: 'عميلنا الكريم';
        $status_label = status_label($new_status);

        // إعداد رسالة SMS
        $sms_message = "عميلنا العزيز {$customer_name}،\n";
        $sms_message .= "تحديث: حالة طلبك رقم #{$request['id']} أصبحت: {$status_label}.\n";
        $sms_message .= "تتبع الطلب: {$tracking_link}";
        if ($note && trim($note) !== '') {
            $sms_message .= "\nملاحظة: " . trim($note);
        }
        $sms_message .= "\nشكراً لثقتك بنا.";

        // إرسال SMS
        if ($customer['phone'] && function_exists('authentica_send_sms')) {
            try {
                $sms_result = authentica_send_sms(
                    $customer['phone'], 
                    $sms_message,
                    'status_update',
                    [
                        'request_id' => $request['id'] ?? null,
                        'tracking_code' => $tracking_code,
                        'customer_name' => $customer_name,
                        'new_status' => $new_status,
                        'status_label' => $status_label,
                        'note' => $note,
                        'link' => $tracking_link,
                    ]
                );
                $results['sms']['sent'] = ($sms_result['success'] ?? false);
                if (!$results['sms']['sent']) {
                    $results['sms']['error'] = $sms_result['message'] ?? 'Unknown error';
                    error_log("SMS sending failed for status update request #{$request['id']}: " . json_encode($sms_result));
                }
            } catch (Throwable $e) {
                $results['sms']['error'] = $e->getMessage();
                error_log("SMS sending exception for status update request #{$request['id']}: " . $e->getMessage());
                // تسجيل الخطأ في ملف السجل
                @file_put_contents(__DIR__ . '/../sms_errors.log', 
                    "[" . date('Y-m-d H:i:s') . "] EXCEPTION in send_status_update_notification: " . $e->getMessage() . " | Request ID: " . ($request['id'] ?? 'N/A') . "\n", 
                    FILE_APPEND | LOCK_EX
                );
            }
        } else {
            $results['sms']['error'] = 'No phone number found or SMS function not available';
            // تسجيل عدم وجود رقم هاتف
            @file_put_contents(__DIR__ . '/../sms_errors.log', 
                "[" . date('Y-m-d H:i:s') . "] WARNING: No phone number found for status update | Request ID: " . ($request['id'] ?? 'N/A') . "\n", 
                FILE_APPEND | LOCK_EX
            );
        }

        // إعداد رسالة Email
        $email_subject = "تحديث حالة الطلب - رقم التتبع: {$tracking_code}";
        $note_html = $note && trim($note) !== '' ? "<div style='background-color: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-right: 4px solid #ffc107;'><strong>ملاحظة:</strong><br>" . nl2br(e(trim($note))) . "</div>" : '';
        
        $email_body = "
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #17c1cc; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                .status-badge { display: inline-block; padding: 8px 16px; background-color: #17c1cc; color: white; border-radius: 5px; font-weight: bold; margin: 10px 0; }
                .button { display: inline-block; background-color: #17c1cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>شركة عزم الإنجاز</h2>
                </div>
                <div class='content'>
                    <p>مرحباً {$customer_name},</p>
                    <p>تم تحديث حالة طلبك رقم #{$request['id']}</p>
                    
                    <div style='text-align: center;'>
                        <span class='status-badge'>الحالة الجديدة: {$status_label}</span>
                    </div>
                    
                    {$note_html}
                    
                    <p>يمكنك متابعة حالة طلبك عبر الرابط التالي:</p>
                    <p style='text-align: center;'>
                        <a href='{$tracking_link}' class='button'>تتبع الطلب</a>
                    </p>
                    
                    <p>أو انسخ هذا الرابط في المتصفح:</p>
                    <p style='background-color: #fff; padding: 10px; border-radius: 5px; word-break: break-all;'>{$tracking_link}</p>
                    
                    <p>شكراً لثقتك بنا.</p>
                    <p>فريق عزم الإنجاز</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " شركة عزم الإنجاز. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";

        // إرسال Email
        if ($customer['email']) {
            try {
                $config = cfg();
                $email_sent = false;
                
                if (function_exists('send_email')) {
                    $email_sent = send_email(
                        $customer['email'],
                        $email_subject,
                        $email_body,
                        'شركة عزم الإنجاز',
                        $config->mail_to ?? 'noreply@azmalenjaz.com'
                    );
                } else {
                    $email_sent = @mail(
                        $customer['email'],
                        $email_subject,
                        strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body)),
                        safe_mail_headers($config->mail_to ?? 'noreply@azmalenjaz.com')
                    );
                }
                
                $results['email']['sent'] = $email_sent;
                if (!$email_sent) {
                    $results['email']['error'] = 'Failed to send email';
                    error_log("Email sending failed for status update request #{$request['id']} to {$customer['email']}");
                }
            } catch (Throwable $e) {
                $results['email']['error'] = $e->getMessage();
                error_log("Email sending exception for status update request #{$request['id']}: " . $e->getMessage());
            }
        } else {
            $results['email']['error'] = 'No email address found';
        }

    } catch (Throwable $e) {
        error_log("Failed to send status update notification: " . $e->getMessage());
    }

    return $results;
}

/**
 * إرسال إشعار عند طلب تعديل من العميل
 */
function send_revision_request_notification(array $request, string $note, string $edit_link): array {
    $results = [
        'sms' => ['sent' => false, 'error' => null],
        'email' => ['sent' => false, 'error' => null]
    ];

    try {
        $customer = extract_customer_info($request);
        $tracking_code = $request['tracking_code'] ?? '';
        $customer_name = $customer['name'] ?: 'عميلنا الكريم';

        // إعداد رسالة SMS
        $sms_message = "عميلنا الكريم {$customer_name}،\n";
        $sms_message .= "نحتاج إكمال بيانات طلبك رقم {$tracking_code}.\n";
        $sms_message .= "الرجاء فتح الرابط خلال 48 ساعة:\n{$edit_link}\n";
        $sms_message .= "شكرًا لتعاونك.";

        // إرسال SMS
        if ($customer['phone'] && function_exists('authentica_send_sms')) {
            try {
                $sms_result = authentica_send_sms(
                    $customer['phone'], 
                    $sms_message,
                    'revision_request',
                    [
                        'request_id' => $request['id'] ?? null,
                        'tracking_code' => $tracking_code,
                        'customer_name' => $customer_name,
                        'note' => $note,
                        'edit_link' => $edit_link,
                    ]
                );
                $results['sms']['sent'] = ($sms_result['success'] ?? false);
                if (!$results['sms']['sent']) {
                    $results['sms']['error'] = $sms_result['message'] ?? 'Unknown error';
                }
            } catch (Throwable $e) {
                // تسجيل الخطأ في ملف السجل
                @file_put_contents(__DIR__ . '/../sms_errors.log', 
                    "[" . date('Y-m-d H:i:s') . "] EXCEPTION in send_revision_request_notification: " . $e->getMessage() . " | Request ID: " . ($request['id'] ?? 'N/A') . "\n", 
                    FILE_APPEND | LOCK_EX
                );
                $results['sms']['error'] = $e->getMessage();
            }
        }

        // إعداد رسالة Email
        $email_subject = "طلب إكمال بيانات الطلب رقم {$tracking_code}";
        $email_body = "
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #17c1cc; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                .alert { background-color: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-right: 4px solid #ffc107; }
                .button { display: inline-block; background-color: #17c1cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>شركة عزم الإنجاز</h2>
                </div>
                <div class='content'>
                    <p>السلام عليكم {$customer_name},</p>
                    <p>نحتاج بعض المعلومات/الملفات لإكمال طلبك رقم {$tracking_code}.</p>
                    
                    <div class='alert'>
                        <strong>ملاحظة:</strong><br>" . nl2br(e(trim($note))) . "
                    </div>
                    
                    <p>الرجاء فتح الرابط التالي خلال 48 ساعة وإتمام المطلوب:</p>
                    <p style='text-align: center;'>
                        <a href='{$edit_link}' class='button'>تعديل الطلب</a>
                    </p>
                    
                    <p style='background-color: #fff; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 12px;'>{$edit_link}</p>
                    
                    <p><strong>ملاحظة:</strong> الرابط صالح لمدة 48 ساعة فقط.</p>
                    
                    <p>شكرًا لك.</p>
                    <p>فريق عزم الإنجاز</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " شركة عزم الإنجاز. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";

        // إرسال Email
        if ($customer['email']) {
            try {
                $config = cfg();
                $email_sent = false;
                
                if (function_exists('send_email')) {
                    $email_sent = send_email(
                        $customer['email'],
                        $email_subject,
                        $email_body,
                        'شركة عزم الإنجاز',
                        $config->mail_to ?? 'noreply@azmalenjaz.com'
                    );
                } else {
                    $email_sent = @mail(
                        $customer['email'],
                        $email_subject,
                        strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $email_body)),
                        safe_mail_headers($config->mail_to ?? 'noreply@azmalenjaz.com')
                    );
                }
                
                $results['email']['sent'] = $email_sent;
            } catch (Throwable $e) {
                $results['email']['error'] = $e->getMessage();
            }
        }

    } catch (Throwable $e) {
        error_log("Failed to send revision request notification: " . $e->getMessage());
    }

    return $results;
}

