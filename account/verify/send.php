<?php
declare(strict_types=1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('login.php'));
    exit;
}

try {
    $channel = $_POST['channel'] ?? '';
    if (empty($channel)) {
        throw new RuntimeException('القناة مطلوبة');
    }

    // Simple rate limit: 60 seconds between sends per channel
    $_SESSION['verify'] = $_SESSION['verify'] ?? [];
    $now = time();
    $keyLast = $channel === 'email' ? 'email_sent_at' : 'phone_sent_at';
    if (!empty($_SESSION['verify'][$keyLast]) && ($now - (int)$_SESSION['verify'][$keyLast]) < 60) {
        header('Location: ' . app_href('dashboard.php?error=rate_limited#security'));
        exit;
    }

    $code = (string)random_int(100000, 999999);

    if ($channel === 'email') {
        // إرسال رمز التحقق عبر البريد الإلكتروني
        require_once __DIR__ . '/../../inc/email.php';
        $db = pdo_open('users');
        $st = $db->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1');
        $st->execute([(int)$_SESSION['user']['id']]);
        $u = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $user_email = $u['email'] ?? '';
        $user_name = $u['name'] ?? 'المستخدم';
        
        if (!$user_email || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('لا يمكن إرسال الرمز: البريد الإلكتروني غير مسجّل أو غير صحيح.');
        }
        
        $email_subject = 'رمز التحقق - شركة عزم الإنجاز';
        $email_body = "
            <p>مرحباً {$user_name}</p>
            <p>رمز التحقق الخاص بك هو: <strong style='font-size:24px;color:#667eea;'>{$code}</strong></p>
            <p>صالح لمدة <strong>10 دقائق</strong>.</p>
            <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.</p>
            <hr>
            <p style='color:#666;font-size:12px;'>شركة عزم الإنجاز</p>
        ";
        
        $email_sent = false;
        if (function_exists('send_email')) {
            $email_sent = send_email(
                $user_email,
                $email_subject,
                $email_body,
                'شركة عزم الإنجاز',
                null
            );
        } else {
            // استخدام mail() كبديل
            $plain_msg = "مرحباً {$user_name}\n\nرمز التحقق الخاص بك هو: {$code}\nصالح لمدة 10 دقائق.\n\nشركة عزم الإنجاز";
            $email_sent = @mail(
                $user_email,
                $email_subject,
                $plain_msg,
                "From: noreply@azmalenjaz.com\r\nContent-Type: text/html; charset=utf-8"
            );
        }
        
        if (!$email_sent) {
            throw new RuntimeException('فشل إرسال البريد الإلكتروني. يرجى المحاولة مرة أخرى أو استخدام رقم الجوال.');
        }
        
        $_SESSION['verify']['email_code'] = $code;
        $_SESSION['verify']['email_sent_at'] = $now;
    } elseif ($channel === 'phone') {
        // Fetch user phone from DB and send SMS via Authentica
        $db = pdo_open('users');
        $st = $db->prepare('SELECT phone_e164, phone_local FROM users WHERE id = ? LIMIT 1');
        $st->execute([ (int)$_SESSION['user']['id'] ]);
        $u = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $phone = $u['phone_e164'] ?? '';
        if (!$phone) {
            $local = $u['phone_local'] ?? '';
            if (!ksa_local($local)) {
                throw new RuntimeException('لا يمكن إرسال الرمز: رقم الجوال غير مسجّل أو بصيغة غير صحيحة.');
            }
            $phone = to_e164_sa($local);
        }

        // If SKIP_OTP=true, simulate success without hitting API
        $skip = getenv('SKIP_OTP') === 'true';
        if (!$skip) {
          $msg = "رمز التحقق الخاص بك هو: {$code}. صالح لمدة 10 دقائق. شركة عزم الإنجاز";
          $resp = authentica_send_sms(
              $phone, 
              $msg,
              'verification_code',
              [
                  'user_id' => (int)$_SESSION['user']['id'],
                  'channel' => 'phone',
                  'verification_type' => 'account_verification',
              ]
          );
          if (!($resp['success'] ?? false)) {
              $err = $resp['message'] ?? 'فشل إرسال الرسالة النصية';
              $_SESSION['verify']['last_error'] = $err;
              header('Location: ' . app_href('dashboard.php?error=sms_failed#security'));
              exit;
          }
        }

        $_SESSION['verify']['phone_code'] = $code;
        $_SESSION['verify']['phone_sent_at'] = $now;
    } else {
        throw new RuntimeException('قناة غير مدعومة');
    }

    header('Location: ' . app_href('dashboard.php?code_sent=1#security'));
    exit;

} catch (Throwable $e) {
    // Log the error
    error_log($e->getMessage());
    // Redirect back with a generic error message
    $_SESSION['verify']['last_error'] = $e->getMessage();
    header('Location: ' . app_href('dashboard.php?error=1#security'));
    exit;
}