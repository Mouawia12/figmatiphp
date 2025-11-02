<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$me = require_admin();
$page_title = 'تذاكر الدعم';

if (function_exists('ensure_support_tables_exist')) {
    ensure_support_tables_exist();
}

$db = pdo_open('users');
$stmt = $db->query("SELECT st.*, u.name AS user_name FROM support_tickets st JOIN users u ON st.user_id = u.id ORDER BY st.updated_at DESC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content = function() use ($tickets) {
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>تذاكر الدعم</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="row g-3 p-3">
                        <?php if (empty($tickets)): ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center py-4 mb-0">لا توجد تذاكر دعم حالياً.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><a href="support_ticket_view.php?id=<?= (int)$ticket['id'] ?>" class="text-dark text-decoration-none"><?= e($ticket['subject']) ?></a></h6>
                                                <?php
                                                    $statusClass = 'bg-secondary';
                                                    switch ($ticket['status'] ?? '') {
                                                        case 'open': $statusClass = 'bg-success'; break;
                                                        case 'pending': $statusClass = 'bg-warning text-dark'; break;
                                                        case 'closed': $statusClass = 'bg-danger'; break;
                                                    }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= e($ticket['status']) ?></span>
                                            </div>
                                            <p class="text-muted small mb-1">العميل: <?= e($ticket['user_name']) ?></p>
                                            <p class="text-muted small mb-3">آخر تحديث: <?= e($ticket['updated_at']) ?></p>
                                            <div class="mt-auto">
                                                <a href="support_ticket_view.php?id=<?= (int)$ticket['id'] ?>" class="btn btn-sm btn-outline-primary">عرض التذكرة</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
};

require __DIR__ . '/_layout.php';