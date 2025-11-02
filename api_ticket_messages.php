<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'messages' => [], 'error' => 'An unknown error occurred.'];

if (empty($_SESSION['user']['id'])) {
    $response['error'] = 'Authentication required.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$ticket_id = (int)($_GET['ticket_id'] ?? 0);
$last_message_id = (int)($_GET['last_message_id'] ?? 0);

if ($ticket_id <= 0) {
    $response['error'] = 'Invalid ticket ID.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    $db = pdo_open('users');

    // Verify user has access to this ticket (for customer view)
    $stmt = $db->prepare("SELECT user_id FROM support_tickets WHERE id = ? LIMIT 1");
    $stmt->execute([$ticket_id]);
    $ticket_owner_id = (int)$stmt->fetchColumn();

    $isAdmin = false;
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        $isAdmin = true;
    }

    if (!$isAdmin && $ticket_owner_id !== $user_id) {
        $response['error'] = 'Access denied.';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // Fetch new messages
    $sqlMsgs = "
        SELECT 
            m.*,
            CASE 
                WHEN m.author_type = 'admin' THEN 'Admin'
                ELSE u.name
            END AS author_name
        FROM `support_ticket_messages` m
        LEFT JOIN `users` u ON (m.author_type = 'user' AND u.id = m.author_id)
        WHERE m.ticket_id = ? AND m.id > ?
        ORDER BY m.id ASC
    ";
    $stmt = $db->prepare($sqlMsgs);
    $stmt->execute([$ticket_id, $last_message_id]);
    $new_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['messages'] = $new_messages;
    $response['error'] = '';

} catch (Throwable $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit;

?>