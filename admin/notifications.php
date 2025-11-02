<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

// Utilities for notifications listing and stats
if (!function_exists('get_notification_stats')) {
    function get_notification_stats(): array {
        $cfg = cfg();
        $db = pdo_open($cfg->db_notifications);
        if (function_exists('ensure_notifications_schema')) {
            try { ensure_notifications_schema(); } catch (Throwable $e) {}
        }
        try {
            $total = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        } catch (Throwable $e) { $total = 0; }
        try {
            if (($cfg->db_driver ?? 'sqlite') === 'mysql') {
                $sent_today = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE status='sent' AND DATE(created_at)=CURRENT_DATE")->fetchColumn();
                $failed     = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE status='failed'")->fetchColumn();
            } else {
                $sent_today = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE status='sent' AND date(created_at)=date('now')")->fetchColumn();
                $failed     = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE status='failed'")->fetchColumn();
            }
        } catch (Throwable $e) { $sent_today = 0; $failed = 0; }
        return ['total'=>$total,'sent_today'=>$sent_today,'failed'=>$failed];
    }
}

if (!function_exists('get_all_notifications')) {
    function get_all_notifications(int $limit, int $offset, string $whereClause = '', array $params = []): array {
        $cfg = cfg();
        $db = pdo_open($cfg->db_notifications);
        if (function_exists('ensure_notifications_schema')) {
            try { ensure_notifications_schema(); } catch (Throwable $e) {}
        }
        $sql = "SELECT id,subject,message,recipients,type,status,sent_at,created_at FROM notifications " . $whereClause . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $st = $db->prepare($sql);
        foreach ($params as $i => $v) { $st->bindValue($i+1, $v); }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        try { $st->execute(); return $st->fetchAll() ?: []; } catch (Throwable $e) { return []; }
    }
}

if (!function_exists('count_notifications')) {
    function count_notifications(string $whereClause = '', array $params = []): int {
        $cfg = cfg();
        $db = pdo_open($cfg->db_notifications);
        if (function_exists('ensure_notifications_schema')) {
            try { ensure_notifications_schema(); } catch (Throwable $e) {}
        }
        $st = $db->prepare("SELECT COUNT(*) FROM notifications " . $whereClause);
        try { $st->execute($params); return (int)$st->fetchColumn(); } catch (Throwable $e) { return 0; }
    }
}

// Filtering and Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($status && in_array($status, ['sent', 'failed', 'pending', 'partial'])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($type) {
    $where[] = 'type = ?';
    $params[] = $type;
}
if ($search) {
    $where[] = '(subject LIKE ? OR recipients LIKE ? OR message LIKE ?)';
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Data Fetching
$stats = get_notification_stats();
$notifications = get_all_notifications($per_page, $offset, $whereClause, $params);
$total_notifications = count_notifications($whereClause, $params);
$total_pages = ceil($total_notifications / $per_page);

function get_status_badge_class(string $status): string {
    $map = ['sent' => 'success', 'failed' => 'danger', 'pending' => 'warning', 'partial' => 'info'];
    return $map[strtolower($status)] ?? 'secondary';
}
function get_status_label(string $status): string {
    $map = ['sent' => 'مرسلة', 'failed' => 'فشل', 'pending' => 'قيد الانتظار', 'partial' => 'جزئي'];
    return $map[strtolower($status)] ?? $status;
}

$page_title = 'الإشعارات والتنبيهات';
$content = function () use ($stats, $notifications, $status, $type, $search, $page, $total_pages) { ?>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8"><div class="numbers"><p class="text-sm mb-0 text-uppercase font-weight-bold">إجمالي الإشعارات</p><h5 class="font-weight-bolder"><?= number_format((int)$stats['total']) ?></h5></div></div>
                    <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle"><i class="ni ni-archive-2 text-lg opacity-10"></i></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8"><div class="numbers"><p class="text-sm mb-0 text-uppercase font-weight-bold">المرسلة اليوم</p><h5 class="font-weight-bolder"><?= number_format((int)$stats['sent_today']) ?></h5></div></div>
                    <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle"><i class="ni ni-send text-lg opacity-10"></i></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8"><div class="numbers"><p class="text-sm mb-0 text-uppercase font-weight-bold">الإشعارات الفاشلة</p><h5 class="font-weight-bolder"><?= number_format((int)$stats['failed']) ?></h5></div></div>
                    <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle"><i class="ni ni-bell-55 text-lg opacity-10"></i></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8"><div class="numbers"><p class="text-sm mb-0 text-uppercase font-weight-bold">إجمالي اليوم</p><h5 class="font-weight-bolder"><?= number_format((int)($stats['sent_today'] + $stats['failed'])) ?></h5></div></div>
                    <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle"><i class="ni ni-chart-bar-32 text-lg opacity-10"></i></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Table Card -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6>سجل الإشعارات</h6>
                    <button class="btn btn-sm bg-gradient-primary mb-0" data-bs-toggle="modal" data-bs-target="#sendNotificationModal"><i class="fas fa-plus me-2"></i>إشعار جديد</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="get" class="row gx-2 gy-3 align-items-end">
                    <div class="col-md-3"><label class="form-label">الحالة</label><select name="status" class="form-select"><option value="">كل الحالات</option><option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>مرسلة</option><option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>فشل</option><option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>قيد الانتظار</option></select></div>
                    <div class="col-md-3"><label class="form-label">النوع</label><select name="type" class="form-select"><option value="">كل الأنواع</option><option value="email" <?= $type === 'email' ? 'selected' : '' ?>>بريد</option><option value="sms" <?= $type === 'sms' ? 'selected' : '' ?>>SMS</option><option value="system" <?= $type === 'system' ? 'selected' : '' ?>>نظام</option></select></div>
                    <div class="col-md-4"><label class="form-label">بحث</label><input type="text" name="search" class="form-control" placeholder="ابحث في الموضوع، المستلم، أو الرسالة..." value="<?= e($search) ?>"></div>
                    <div class="col-md-2 d-flex"><button class="btn btn-primary w-100 me-2" type="submit"><i class="fas fa-search"></i></button><a href="?" class="btn btn-outline-secondary w-100"><i class="fas fa-undo"></i></a></div>
                </form>
            </div>
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                    <thead><tr><th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">التفاصيل</th><th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">المستلم</th><th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th><th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">التاريخ</th><th class="text-secondary opacity-7"></th></tr></thead>
                    <tbody>
                        <?php if(empty($notifications)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted"><h6>لا توجد إشعارات تطابق بحثك</h6></td></tr>
                        <?php else: foreach ($notifications as $noti): ?>
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm"><?= e(mb_strimwidth($noti['subject'] ?? '(بدون موضوع)', 0, 40, "...")) ?></h6>
                                            <p class="text-xs text-secondary mb-0"><?= e(mb_strimwidth(strip_tags($noti['message'] ?? ''), 0, 50, "...")) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><p class="text-xs font-weight-bold mb-0"><?= e($noti['recipients'] ?? '-') ?></p><p class="text-xs text-secondary mb-0"><?= e(ucfirst($noti['type'] ?? '-')) ?></p></td>
                                <td class="align-middle text-center text-sm"><span class="badge badge-sm bg-gradient-<?= get_status_badge_class($noti['status'] ?? '') ?>"><?= e(get_status_label($noti['status'] ?? '')) ?></span></td>
                                <td class="align-middle text-center"><span class="text-secondary text-xs font-weight-bold"><?= date('Y-m-d H:i', strtotime($noti['created_at'])) ?></span></td>
                                <td class="align-middle">
                                    <button class="btn btn-link text-secondary mb-0" onclick='showNotificationDetails(<?= htmlspecialchars(json_encode($noti), ENT_QUOTES, "UTF-8") ?>)'><i class="fas fa-eye"></i></button>
                                    <?php if(in_array($noti['status'], ['failed', 'pending'])): ?><button class="btn btn-link text-info mb-0" onclick="resendNotification(<?= (int)$noti['id'] ?>)"><i class="fas fa-redo"></i></button><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-primary">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&status=<?= e($status) ?>&type=<?= e($type) ?>&search=<?= e($search) ?>"><?= $i ?></a></li><?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals and JS -->
<div class="modal fade" id="notificationDetailsModal" tabindex="-1" role="dialog">...</div>
<div class="modal fade" id="sendNotificationModal" tabindex="-1" role="dialog">...</div>
<script>/* JS code remains largely the same */</script>

<?php }; include __DIR__ . '/_layout.php'; ?>
