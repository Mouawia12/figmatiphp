<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/send_sms.php';

function process_sms_retry_queue() {
    $pdo = pdo_open();
    
    // Get failed SMS messages with less than 3 retries
    $stmt = $pdo->prepare(
        "SELECT * FROM sms_logs 
        WHERE status = 'failed' AND retry_count < 3"
    );
    $stmt->execute();
    $failed_messages = $stmt->fetchAll();

    echo "Processing " . count($failed_messages) . " failed SMS messages.\n";

    foreach ($failed_messages as $message) {
        echo "Retrying SMS to ". htmlspecialchars($message['phone'], ENT_QUOTES, 'UTF-8') . ".\n";
        // Retry sending the SMS
        $result = authentica_send_sms($message['phone'], $message['message']);
        
        if ($result['success'] ?? false) {
            // Update status to 'sent'
            $update_stmt = $pdo->prepare(
                "UPDATE sms_logs 
                SET status = 'sent', response = ?
                WHERE id = ?"
            );
            $update_stmt->execute([json_encode($result), $message['id']]);
            echo "SMS to ". htmlspecialchars($message['phone'], ENT_QUOTES, 'UTF-8') . " sent successfully.\n";
        } else {
            // Increment retry_count and update last_retry_at
            $update_stmt = $pdo->prepare(
                "UPDATE sms_logs 
                SET retry_count = retry_count + 1, last_retry_at = datetime('now'), response = ?
                WHERE id = ?"
            );
            $update_stmt->execute([json_encode($result), $message['id']]);
            echo "Failed to send SMS to ". htmlspecialchars($message['phone'], ENT_QUOTES, 'UTF-8') . ". Retry count: " . ($message['retry_count'] + 1) . "\n";

            if (($message['retry_count'] + 1) >= 3) {
                // Send notification to admin
                $admin_phone = env('ADMIN_PHONE');
                if ($admin_phone) {
                    $admin_message = "Failed to send SMS to ". htmlspecialchars($message['phone'], ENT_QUOTES, 'UTF-8') . " after 3 retries.";
                    authentica_send_sms($admin_phone, $admin_message);
                    echo "Sent admin notification about failed SMS to ". htmlspecialchars($message['phone'], ENT_QUOTES, 'UTF-8') . ".\n";
                }
            }
        }
    }
}

process_sms_retry_queue();