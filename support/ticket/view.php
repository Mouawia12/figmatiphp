<?php declare(strict_types=1);

ob_start(); // بدء تخزين المخرجات مؤقتًا

// 1. إعداد ملف سجل أخطاء مخصص
$errorLogFile = __DIR__ . '/../../php_error_log.log';
ini_set('error_log', $errorLogFile);
ini_set('log_errors', '1'); // تأكد من تفعيل تسجيل الأخطاء

// 2. إعداد عرض الأخطاء (للتطوير فقط)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// 3. معالج أخطاء مخصص
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logMessage = "PHP Error: [$errno] $errstr in $errfile on line $errline";
    error_log($logMessage);
}
set_error_handler("customErrorHandler");

// 4. معالج استثناءات مخصص
function customExceptionHandler($exception) {
    $logMessage = "PHP Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($logMessage);
}
set_exception_handler("customExceptionHandler");

error_log("DEBUG: support/ticket/view.php - Start");

require_once __DIR__ . '/../../inc/functions.php';
error_log("DEBUG: support/ticket/view.php - functions.php loaded");

session_start();
error_log("DEBUG: support/ticket/view.php - Session started");

if (empty($_SESSION['user']['id'])) {
    header('Location: ' . app_href('../../login.php'));
    exit;
}
error_log("DEBUG: support/ticket/view.php - User logged in");

try {
    ensure_support_tables_exist();
    error_log("DEBUG: support/ticket/view.php - ensure_support_tables_exist() executed");
} catch (Throwable $e) {
    error_log("Error in ensure_support_tables_exist(): " . $e->getMessage());
    die("Database initialization error. Please check logs.");
}

$user_id = (int)$_SESSION['user']['id'];
error_log("DEBUG: support/ticket/view.php - user_id: " . $user_id);
$page_title = 'عرض تذكرة الدعم';

$ticket_id = (int)($_GET['id'] ?? 0);
error_log("DEBUG: support/ticket/view.php - ticket_id: " . $ticket_id);
if ($ticket_id <= 0) {
    header('Location: ' . app_href('../../dashboard.php#tickets'));
    exit;
}

try {
    $db = pdo_open('users');
    error_log("DEBUG: support/ticket/view.php - Database opened");
} catch (Throwable $e) {
    error_log("Error opening database: " . $e->getMessage());
    die("Database connection error. Please check logs.");
}

// -------- fetch ticket --------
$sqlTicket = "
    SELECT 
        t.*,
        u.name AS user_name
    FROM `support_tickets` t
    JOIN `users` u ON u.id = t.user_id
    WHERE t.id = ? AND t.user_id = ?
    LIMIT 1
";
try {
    $stmt = $db->prepare($sqlTicket);
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: support/ticket/view.php - Ticket fetched");
} catch (Throwable $e) {
    error_log("Error fetching ticket: " . $e->getMessage());
    die("Error retrieving ticket details. Please check logs.");
}

if (!$ticket) {
    header('Location: ' . app_href('../../dashboard.php#tickets'));
    exit;
}

// Mark admin messages as read for the user
$stmt = $db->prepare("UPDATE support_ticket_messages SET is_read = 1 WHERE ticket_id = ? AND author_type = 'admin' AND is_read = 0");
$stmt->execute([$ticket_id]);
error_log("DEBUG: support/ticket/view.php - Ticket exists");

// -------- handle reply (POST) --------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['reply_message'])) {
    error_log("DEBUG: support/ticket/view.php - Inside POST handler");
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        error_log("DEBUG: support/ticket/view.php - CSRF check failed");
        header('Location: ' . app_href('view.php?id=' . $ticket_id . '&csrf_error=1'));
        exit;
    }
    error_log("DEBUG: support/ticket/view.php - CSRF check passed");
    $msg = trim((string)$_POST['reply_message']);
    if ($msg !== '') {
        error_log("DEBUG: support/ticket/view.php - Message is not empty");
        $c = cfg();
        $now_func = (($c->db_driver ?? 'sqlite') === 'mysql') ? 'NOW()' : "datetime('now')";

        try {
            error_log("DEBUG: support/ticket/view.php - Before INSERT/UPDATE queries");
            // created_at has defaults, but set explicitly for consistency across drivers
            $stmt = $db->prepare(
                "INSERT INTO support_ticket_messages (ticket_id, author_type, author_id, message, created_at)
                 VALUES (?, 'user', ?, ?, {$now_func})"
            );
            $stmt->execute([$ticket_id, $user_id, $msg]);
            error_log("DEBUG: support/ticket/view.php - Message inserted");

            // Update ticket timestamp and status
            $stmt = $db->prepare("UPDATE support_tickets SET updated_at = {$now_func}, status = 'pending' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            error_log("DEBUG: support/ticket/view.php - Ticket updated");
        } catch (Throwable $e) {
            error_log('Error adding reply or updating ticket: ' . $e->getMessage());
            header('Location: ' . app_href('view.php?id=' . $ticket_id . '&db_error=1'));
            exit;
        }
    }
    error_log("DEBUG: support/ticket/view.php - After message processing");
    header('Location: ' . app_href('view.php?id=' . $ticket_id));
    exit;
} // Closing brace for the main POST handler if statement
error_log("DEBUG: support/ticket/view.php - After POST handler block");

// -------- fetch messages --------
error_log("DEBUG: support/ticket/view.php - Before fetching messages");
$sqlMsgs = "
    SELECT 
        m.*,
        CASE 
            WHEN m.author_type = 'admin' THEN 'Admin'
            ELSE u.name
        END AS author_name
    FROM `support_ticket_messages` m
    LEFT JOIN `users` u   ON (m.author_type = 'user'  AND u.id = m.author_id)
    WHERE m.ticket_id = ?
    ORDER BY m.id ASC
";
try {
    $stmt = $db->prepare($sqlMsgs);
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("DEBUG: support/ticket/view.php - Messages fetched");
} catch (Throwable $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    die("Error retrieving messages. Please check logs.");
}
error_log("DEBUG: support/ticket/view.php - After fetching messages");

require __DIR__ . '/../../partials/header.php';
error_log("DEBUG: support/ticket/view.php - Header included");

error_log("DEBUG: support/ticket/view.php - Before HTML output");
?>
<div class="container py-5">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-12">
            <div class="card card-auth fade-in">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">تذكرة #<?= e($ticket['id']) ?>: <?= e($ticket['subject'] ?? '') ?></h4>
                    <p class="text-sm mb-0">
                        الحالة: <span class="badge bg-gradient-info"><?= e($ticket['status'] ?? '') ?></span> |
                        الأولوية: <?= e($ticket['priority'] ?? '') ?>
                    </p>
                    <hr>
                    <?php if (!$messages): ?>
                        <div class="alert alert-secondary">لا توجد رسائل بعد.</div>
                    <?php else: ?>
                                        <?php foreach ($messages as $m): ?>
                                            <?php
                                                $isUser = ($m['author_type']==='user');
                                                $authorName = e($m['author_name'] ?? ($isUser ? 'أنت' : 'مسؤول'));
                                                $messageClass = $isUser ? 'bg-light ms-auto' : 'bg-white me-auto';
                                                $avatarText = mb_substr($authorName, 0, 1);
                                            ?>
                                            <div class="d-flex mb-3 <?= $isUser ? 'justify-content-end' : 'justify-content-start' ?>">
                                                <?php if (!$isUser): // Admin avatar on left ?>
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><?= $avatarText ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="message-bubble p-3 rounded-3 shadow-sm <?= $messageClass ?>" style="max-width: 75%;">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="small"><?= $authorName ?></strong>
                                                        <small class="text-muted" style="font-size: 0.75em;"><?= e($m['created_at'] ?? '') ?></small>
                                                    </div>
                                                    <div><?= nl2br(e($m['message'] ?? '')) ?></div>
                                                </div>
                                                <?php if ($isUser): // User avatar on right ?>
                                                    <div class="flex-shrink-0 ms-3">
                                                        <div class="avatar rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><?= $avatarText ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>                    <?php endif; ?>
                </div>
                <div class="card-footer pt-0 px-4 pb-4">
                    <form method="post" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="mb-3">
                            <label for="reply_message" class="form-label">إضافة رد</label>
                            <textarea class="form-control" id="reply_message" name="reply_message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">إرسال الرد</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
<script>
    const ticketId = <?= (int)$ticket_id ?>;
    let lastMessageId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
    const messagesContainer = document.querySelector('.card-body .alert.alert-secondary') || document.querySelector('.card-body');

    function fetchNewMessages() {
        fetch(`${window.APP_BASE_URL}/api_ticket_messages.php?ticket_id=${ticketId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        const isUser = (message.author_type === 'user');
                        const authorName = message.author_name || (isUser ? 'أنت' : 'مسؤول');
                        const messageClass = isUser ? 'bg-light ms-auto' : 'bg-white me-auto';
                        const avatarText = authorName.substring(0, 1);

                        const messageHtml = `
                            <div class="d-flex mb-3 ${isUser ? 'justify-content-end' : 'justify-content-start'}">
                                ${!isUser ? `
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">${avatarText}</div>
                                    </div>
                                ` : ''}
                                <div class="message-bubble p-3 rounded-3 shadow-sm ${messageClass}" style="max-width: 75%;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="small">${authorName}</strong>
                                        <small class="text-muted" style="font-size: 0.75em;">${message.created_at}</small>
                                    </div>
                                    <div>${message.message.replace(/\n/g, '<br>')}</div>
                                </div>
                                ${isUser ? `
                                    <div class="flex-shrink-0 ms-3">
                                        <div class="avatar rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">${avatarText}</div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                        messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                        lastMessageId = message.id;
                    });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to bottom
                }
            })
            .catch(error => console.error('Error fetching new messages:', error));
    }

    // Initial scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    // Poll for new messages every 3 seconds
    setInterval(fetchNewMessages, 3000);
</script>
<?php error_log("DEBUG: support/ticket/view.php - Footer included and script finished."); ?>
<?php ob_end_flush(); ?>