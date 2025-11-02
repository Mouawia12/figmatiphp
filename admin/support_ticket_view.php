<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$me = require_admin();
$page_title = 'عرض تذكرة الدعم';

$ticket_id = (int)($_GET['id'] ?? 0);
if ($ticket_id <= 0) {
    header('Location: support_tickets.php');
    exit;
}

$db = pdo_open('users');

/** Check if a table exists in current database */
function table_exists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

/** Pick best display name expression for users.* */
function pick_user_name_expr(PDO $db, string $alias = 'u'): string {
    $cols = $db->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    $have = array_flip(array_map('strval', $cols));
    if (isset($have['first_name']) && isset($have['last_name'])) {
        return "TRIM(CONCAT(IFNULL(`{$alias}`.`first_name`, ''), ' ', IFNULL(`{$alias}`.`last_name`, '')))";
    }
    foreach (['full_name','name','username','email'] as $c) {
        if (isset($have[$c])) return "`{$alias}`.`{$c}`";
    }
    return "CAST(`{$alias}`.`id` AS CHAR)";
}

/** Pick best display name expression for admins.* if table exists, else fallback */
$admins_exists = table_exists($db, 'admins');
$adminNameExpr = $admins_exists
    ? (function(PDO $db) {
        $cols = $db->query("SHOW COLUMNS FROM `admins`")->fetchAll(PDO::FETCH_COLUMN);
        $have = array_flip(array_map('strval', $cols));
        // Ordered preference
        foreach (['display_name','name','username','email'] as $c) {
            if (isset($have[$c])) return "`a`.`{$c}`";
        }
        return "CAST(`a`.`id` AS CHAR)";
    })($db)
    : "CONCAT('مسؤول #', CAST(m.author_id AS CHAR))";

// -------- fetch ticket --------
$nameExpr = pick_user_name_expr($db, 'u');
$sqlTicket = "
    SELECT 
        t.*,
        {$nameExpr} AS user_name
    FROM `support_tickets` t
    JOIN `users` u ON u.id = t.user_id
    WHERE t.id = ?
    LIMIT 1
";
$stmt = $db->prepare($sqlTicket);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    header('Location: support_tickets.php');
    exit;
}

// Mark user messages as read for the admin
$stmt = $db->prepare("UPDATE support_ticket_messages SET is_read = 1 WHERE ticket_id = ? AND author_type = 'user' AND is_read = 0");
$stmt->execute([$ticket_id]);

// -------- handle reply (POST) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        // Handle CSRF error, e.g., redirect or display an error message
        // For now, we'll just exit to prevent further processing
        header('Location: support_ticket_view.php?id=' . $ticket_id . '&csrf_error=1');
        exit;
    }
    $msg = trim((string)$_POST['reply_message']);
    if ($msg !== '') {
        $c = cfg(); // Get config
        $now_func = ($c->db_driver === 'mysql') ? 'NOW()' : 'datetime(\'now\')';

        $stmt = $db->prepare("
            INSERT INTO `support_ticket_messages` (ticket_id, author_type, author_id, message, created_at)
            VALUES (?, 'admin', ?, ?, {$now_func})
        ");
        $stmt->execute([$ticket_id, (int)$me['id'], $msg]);

        // Update ticket updated_at timestamp and status
        $stmt = $db->prepare("UPDATE support_tickets SET updated_at = {$now_func}, status = 'answered' WHERE id = ?");
        $stmt->execute([$ticket_id]);
    }
    header('Location: support_ticket_view.php?id=' . $ticket_id);
    exit;
}

// -------- fetch messages --------
$joinAdmins = $admins_exists ? "LEFT JOIN `admins` a  ON (m.author_type = 'admin' AND a.id = m.author_id)" : "";
$sqlMsgs = "
    SELECT 
        m.*,
        CASE 
            WHEN m.author_type = 'admin' THEN {$adminNameExpr}
            ELSE {$nameExpr}
        END AS author_name
    FROM `support_ticket_messages` m
    LEFT JOIN `users` u   ON (m.author_type = 'user'  AND u.id = m.author_id)
    {$joinAdmins}
    WHERE m.ticket_id = ?
    ORDER BY m.id ASC
";
$stmt = $db->prepare($sqlMsgs);
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------- page content (keeps your existing layout system) --------
$content = function() use ($ticket, $messages) {
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>تذكرة #<?= e($ticket['id']) ?>: <?= e($ticket['subject'] ?? '') ?></h6>
                    <p class="text-sm mb-0">
                        العميل: <?= e($ticket['user_name'] ?? '') ?> |
                        الحالة: <span class="badge bg-gradient-info"><?= e($ticket['status'] ?? '') ?></span> |
                        الأولوية: <?= e($ticket['priority'] ?? '') ?>
                    </p>
                </div>
                <div class="card-body px-4 pt-4 pb-2">
                    <?php if (!$messages): ?>
                        <div class="alert alert-secondary">لا توجد رسائل بعد.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                            <?php
                                $isAdmin = ($m['author_type']==='admin');
                                $authorName = e($m['author_name'] ?? ($isAdmin ? 'مسؤول' : 'عميل'));
                                $messageClass = $isAdmin ? 'bg-light ms-auto' : 'bg-white me-auto';
                                $avatarText = mb_substr($authorName, 0, 1);
                            ?>
                            <div class="d-flex mb-3 <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?>">
                                <?php if (!$isAdmin): // User avatar on left ?>
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><?= $avatarText ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="message-bubble p-3 rounded-3 shadow-sm <?= $messageClass ?>" style="max-width: 75%;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="small"><?= $authorName ?></strong>
                                        <small class="text-muted" style="font-size: 0.75em;"><?= e($m['created_at'] ?? '') ?></small>
                                    </div>
                                    <div><?= nl2br(e($m['message'] ?? '')) ?></div>
                                </div>
                                <?php if ($isAdmin): // Admin avatar on right ?>
                                    <div class="flex-shrink-0 ms-3">
                                        <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><?= $avatarText ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
<?php
};

require __DIR__ . '/_layout.php';
?>
<script>
    const ticketId = <?= (int)$ticket_id ?>;
    let lastMessageId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
    const messagesContainer = document.querySelector('.card-body');

    function fetchNewMessages() {
        fetch(`${window.APP_BASE_URL}/api_ticket_messages.php?ticket_id=${ticketId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        const isAdmin = (message.author_type === 'admin');
                        const authorName = message.author_name || (isAdmin ? 'مسؤول' : 'عميل');
                        const messageClass = isAdmin ? 'bg-light ms-auto' : 'bg-white me-auto';
                        const avatarText = authorName.substring(0, 1);

                        const messageHtml = `
                            <div class="d-flex mb-3 ${isAdmin ? 'justify-content-end' : 'justify-content-start'}">
                                ${!isAdmin ? `
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">${avatarText}</div>
                                    </div>
                                ` : ''}
                                <div class="message-bubble p-3 rounded-3 shadow-sm ${messageClass}" style="max-width: 75%;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="small">${authorName}</strong>
                                        <small class="text-muted" style="font-size: 0.75em;">${message.created_at}</small>
                                    </div>
                                    <div>${message.message.replace(/\n/g, '<br>')}</div>
                                </div>
                                ${isAdmin ? `
                                    <div class="flex-shrink-0 ms-3">
                                        <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">${avatarText}</div>
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
