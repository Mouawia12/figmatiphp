<?php

/**
 * إرسال بريد إلكتروني باستخدام إعدادات SMTP
 * 
 * @param string $to عنوان البريد الإلكتروني للمستلم
 * @param string $subject موضوع الرسالة
 * @param string $message نص الرسالة (يدعم HTML)
 * @param string $from_name اسم المرسل (اختياري)
 * @param string $from_email بريد المرسل (اختياري)
 * @param array $attachments مصفوفة بالمرفقات (اختياري)
 * @return bool true إذا تم الإرسال بنجاح، false إذا فشل
 */
function send_email($to, $subject, $message, $from_name = null, $from_email = null, $attachments = []) {
    // تحميل إعدادات البريد الإلكتروني من ملف .env
    $from_name = $from_name ?: env('MAIL_FROM_NAME', 'Crosing System');
    $from_email = $from_email ?: env('MAIL_FROM_ADDRESS', 'noreply@azmalenjaz.com');
    
    // إذا لم يكن PHPMailer متوفراً، استخدم mail() كبديل
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback إلى mail() function
        $plain_message = strip_tags(str_replace(['<br>', '<br/>', '<br />', '<p>', '</p>'], "\n", $message));
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . mb_encode_mimeheader($from_name, 'UTF-8', 'Q') . " <{$from_email}>\r\n";
        $headers .= "Reply-To: {$from_email}\r\n";
        
        $error = null;
        $result = @mail($to, $subject, $message, $headers);
        
        // الحصول على آخر خطأ إن وجد
        if (!$result) {
            $lastError = error_get_last();
            $error = $lastError ? $lastError['message'] : 'mail() function returned false';
        }
        
        // تسجيل في قاعدة البيانات
        log_email($to, $subject, $result ? 'sent (mail)' : 'failed (mail)');
        
        // تسجيل تفصيلي في mail_errors.log
        log_email_attempt($to, $subject, $result, 'mail_fallback', $error, [
            'from_name' => $from_name,
            'from_email' => $from_email,
        ]);
        
        return $result;
    }
    
    $smtp_host = env('MAIL_HOST', 'localhost');
    $smtp_port = (int)env('MAIL_PORT', 25);
    $smtp_username = env('MAIL_USERNAME', '');
    $smtp_password = env('MAIL_PASSWORD', '');
    $smtp_encryption = env('MAIL_ENCRYPTION', 'tls');

    // إنشاء رسالة البريد الإلكتروني
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // إعدادات الخادم
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->Port = $smtp_port;
        $mail->SMTPAuth = !empty($smtp_username) && !empty($smtp_password);
        
        if ($mail->SMTPAuth) {
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
        }
        
        if (!empty($smtp_encryption) && in_array(strtolower($smtp_encryption), ['tls', 'ssl'])) {
            $mail->SMTPSecure = strtolower($smtp_encryption);
        }
        
        $mail->CharSet = 'UTF-8';
        $mail->setLanguage('ar');
        
        // إعداد المرسل والمستلم
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        
        // إضافة المرفقات إذا وجدت
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_file($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // محتوى الرسالة
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // إضافة نسخة نصية من الرسالة
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        
        // إرسال الرسالة
        $result = $mail->send();
        
        // تسجيل عملية الإرسال في السجلات
        $status = $result ? 'sent' : 'failed';
        log_email($to, $subject, $status);
        
        // تسجيل تفصيلي في mail_errors.log
        log_email_attempt($to, $subject, $result, 'smtp', $result ? null : 'PHPMailer send() returned false', [
            'from_name' => $from_name,
            'from_email' => $from_email,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_auth' => $mail->SMTPAuth,
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        error_log("Email sending failed: " . $errorMessage);
        log_email($to, $subject, 'error: ' . $errorMessage);
        
        // تسجيل تفصيلي في mail_errors.log
        log_email_attempt($to, $subject, false, 'smtp_exception', $errorMessage, [
            'from_name' => $from_name,
            'from_email' => $from_email,
            'smtp_host' => $smtp_host ?? 'unknown',
            'smtp_port' => $smtp_port ?? 'unknown',
            'exception_class' => get_class($e),
        ]);
        
        return false;
    }
}

/**
 * تسجيل عملية إرسال البريد الإلكتروني في قاعدة البيانات
 */
function log_email($to, $subject, $status) {
    try {
        $db = pdo_open();
        $stmt = $db->prepare(
            'INSERT INTO email_logs (recipient, subject, status, created_at) ' .
            'VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $to,
            $subject,
            $status,
            date('Y-m-d H:i:s')
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log email: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل تفاصيل محاولات إرسال البريد الإلكتروني في ملف mail_errors.log
 * 
 * @param string $to عنوان البريد الإلكتروني
 * @param string $subject موضوع الرسالة
 * @param bool $success نجاح/فشل الإرسال
 * @param string $context سياق الإرسال (مثل: 'new_request', 'status_update', 'customer_notification')
 * @param string|null $error_message رسالة الخطأ (إن وجدت)
 * @param array $extra معلومات إضافية
 */
function log_email_attempt(string $to, string $subject, bool $success, string $context = 'general', ?string $error_message = null, array $extra = []): void {
    $logFile = __DIR__ . '/../mail_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    
    $logLine = sprintf(
        "[%s] %s | Context: %s | To: %s | Subject: %s",
        $timestamp,
        $status,
        $context,
        $to,
        mb_substr($subject, 0, 100)
    );
    
    // إضافة رسالة الخطأ إن وجدت
    if (!$success && $error_message) {
        $logLine .= " | Error: " . mb_substr($error_message, 0, 200);
    }
    
    // إضافة معلومات إضافية
    if (!empty($extra)) {
        $logLine .= " | Extra: " . json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    $logLine .= "\n";
    
    // تسجيل في الملف
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    
    // إذا فشل الإرسال، أضف تفاصيل إضافية
    if (!$success && $error_message) {
        $errorDetails = sprintf(
            "  └─ Error Details: %s | Recipient: %s | Context: %s\n",
            $error_message,
            $to,
            $context
        );
        @file_put_contents($logFile, $errorDetails, FILE_APPEND | LOCK_EX);
    }
}

/**
 * إرسال إشعار مخصص
 */
function send_custom_notification($subject, $message, $recipients, $from_name = null, $from_email = null) {
    if (!is_array($recipients)) {
        $recipients = [$recipients];
    }
    
    $results = [];
    foreach ($recipients as $recipient) {
        $results[$recipient] = send_email(
            $recipient,
            $subject,
            $message,
            $from_name,
            $from_email
        );
    }
    
    return $results;
}

// التأكد من تحميل PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // محاولة تحميل من vendor في الجذر (ليس من inc/vendor)
    $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
    }
}

// إنشاء جدول سجلات البريد الإلكتروني إذا لم يكن موجوداً
function ensure_email_logs_table() {
    $db = pdo_open();
    $c = cfg(); // Get config to check db_driver

    if (($c->db_driver ?? 'sqlite') === 'mysql') {
        // MySQL/MariaDB syntax
        $db->exec('CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    } else {
        // SQLite syntax
        $db->exec('CREATE TABLE IF NOT EXISTS email_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }
}

// تنفيذ تهيئة الجدول عند تحميل الملف
ensure_email_logs_table();
