<?php
require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/init_chat_db.php';
$config = cfg();
$me = require_admin();

// Metrics: requests, forms, notifications
$dbr = pdo_open($config->db_requests);
if (function_exists('ensure_requests_schema')) ensure_requests_schema();
$total_requests = (int)$dbr->query("SELECT COUNT(*) FROM requests")->fetchColumn();

$dbf = pdo_open($config->db_forms);
if (($config->db_driver ?? 'sqlite') === 'mysql') {
  $charset = $config->db_charset ?? 'utf8mb4';
  $dbf->exec("CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255), fields LONGTEXT, created_at DATETIME
  ) ENGINE=InnoDB DEFAULT CHARSET={$charset}");
} else {
  $dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
}
$total_forms = (int)$dbf->query("SELECT COUNT(*) FROM forms")->fetchColumn();

$dbn = pdo_open($config->db_notifications);
if (($config->db_driver ?? 'sqlite') === 'mysql') {
  $charset = $config->db_charset ?? 'utf8mb4';
  $dbn->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY, message TEXT, created_at DATETIME
  ) ENGINE=InnoDB DEFAULT CHARSET={$charset}");
} else {
  $dbn->exec("CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY, message TEXT, created_at TEXT)");
}
$total_notes = (int)$dbn->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

// Recent requests
if (($config->db_driver ?? 'sqlite') === 'mysql') {
  $recent = $dbr->query("SELECT id, form_id, name, email, SUBSTRING(message,1,100) AS msg, file, created_at FROM requests ORDER BY id DESC LIMIT 8")->fetchAll();
} else {
  $recent = $dbr->query("SELECT id, form_id, name, email, substr(message,1,100) AS msg, file, created_at FROM requests ORDER BY id DESC LIMIT 8")->fetchAll();
}

$page_title = 'لوحة التحكم';

// Chat analytics (best-effort)
$chat_stats = ['conversations' => 0, 'messages' => 0, 'avg_satisfaction' => 0];
try {
    $stmt = $dbr->prepare(
        "SELECT 
            COUNT(DISTINCT conversation_id) as conversations,
            SUM(total_messages) as messages,
            AVG(satisfaction_score) as avg_satisfaction
         FROM chat_analytics
         WHERE created_at >= datetime('now', '-30 days')"
    );
    $stmt->execute();
    $result = $stmt->fetch();
    if ($result) {
        $chat_stats = [
            'conversations' => (int)($result['conversations'] ?? 0),
            'messages' => (int)($result['messages'] ?? 0),
            'avg_satisfaction' => round((float)($result['avg_satisfaction'] ?? 0), 1)
        ];
    }
} catch (Throwable $e) { /* ignore if table missing */ }

// Live chat stats from chat.db (fallback and for recent list)
$recent_conversations = [];
try {
    $chatDbPath = __DIR__ . '/../data/chat.db';
    $pdoChat = new PDO('sqlite:' . $chatDbPath);
    $pdoChat->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdoChat->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Ensure chat schema exists without altering MySQL
    init_chat_database($pdoChat);

    $liveConvs = (int)$pdoChat->query("SELECT COUNT(*) FROM chat_conversations WHERE is_deleted=0 AND is_archived=0")->fetchColumn();
    $liveMsgs  = (int)$pdoChat->query("SELECT COUNT(*) FROM chat_messages")->fetchColumn();
    if ($liveConvs >= 0) $chat_stats['conversations'] = $liveConvs; // prefer live
    if ($liveMsgs  >= 0) $chat_stats['messages']      = $liveMsgs;

    $stRecent = $pdoChat->query("SELECT id, customer_name, customer_email, last_message, updated_at FROM chat_conversations WHERE is_deleted=0 ORDER BY updated_at DESC LIMIT 8");
    $recent_conversations = $stRecent ? $stRecent->fetchAll() : [];
} catch (Throwable $e) { /* ignore if chat.db missing */ }

$content = function() use ($config,$total_requests,$total_forms,$total_notes,$recent,$chat_stats,$recent_conversations){ ?>
  <div class="row">
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">طلبات</p>
                <h5 class="font-weight-bolder mb-0"><?= (int)$total_requests ?></h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-primary shadow text-center rounded-circle"><i class="ni ni-bullet-list-67 text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">نماذج</p>
                <h5 class="font-weight-bolder mb-0"><?= (int)$total_forms ?></h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-success shadow text-center rounded-circle"><i class="ni ni-folder-17 text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">إشعارات</p>
                <h5 class="font-weight-bolder mb-0"><?= (int)$total_notes ?></h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-warning shadow text-center rounded-circle"><i class="ni ni-bell-55 text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  

  <?php
    // Chat Questions Analytics for dashboard
    $top_questions = [];
    $unanswered_total = 0;
    try {
      ensure_chat_analytics_schema();
      $dbn_dash = pdo_open($config->db_notifications);
      $top_questions = $dbn_dash->query("SELECT question_text, asked_count, unanswered_count FROM chat_questions ORDER BY asked_count DESC, last_at DESC LIMIT 8")->fetchAll();
      $unanswered_total = (int)$dbn_dash->query("SELECT SUM(unanswered_count) FROM chat_questions")->fetchColumn();
    } catch (Throwable $e) { $top_questions = []; $unanswered_total = 0; }
  ?>
  <div class="card my-4">
    <div class="card-header pb-0 d-flex align-items-center justify-content-between">
      <h6 class="mb-0">إحصاءات عزم – أكثر الأسئلة</h6>
      <a href="<?= e(app_href('admin/azam_analytics.php')) ?>" class="text-sm text-primary">عرض الإحصاءات</a>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
      <div class="table-responsive p-0">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">السؤال</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7 text-center" style="width:120px">عدد المرات</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7 text-center" style="width:140px">غير مُجاب بثقة</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$top_questions): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">لا توجد بيانات بعد.</td></tr>
            <?php else: foreach($top_questions as $q): ?>
              <tr>
                <td><?= e($q['question_text'] ?? '') ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?= (int)($q['asked_count'] ?? 0) ?></span></td>
                <td class="text-center"><span class="badge bg-warning"><?= (int)($q['unanswered_count'] ?? 0) ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="px-3 pt-3 text-sm text-muted">إجمالي الأسئلة غير المُجابة بثقة: <strong class="text-warning"><?= (int)$unanswered_total ?></strong></div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">محادثات هذا الشهر</p>
                <h5 class="font-weight-bolder mb-0"><?= (int)$chat_stats['conversations'] ?></h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-info shadow text-center rounded-circle"><i class="ni ni-chat-left text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">عدد الرسائل</p>
                <h5 class="font-weight-bolder mb-0"><?= (int)$chat_stats['messages'] ?></h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-success shadow text-center rounded-circle"><i class="ni ni-send text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <div class="row">
            <div class="col-8">
              <div class="numbers">
                <p class="text-sm mb-0 text-capitalize font-weight-bold">رضا العملاء</p>
                <h5 class="font-weight-bolder mb-0"><?= e($chat_stats['avg_satisfaction']) ?>/5</h5>
              </div>
            </div>
            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-warning shadow text-center rounded-circle"><i class="ni ni-satisfied text-lg opacity-10" aria-hidden="true"></i></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card my-4">
    <div class="card-header pb-0">
      <h6>أحدث الطلبات</h6>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
      <div class="table-responsive p-0">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">#</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">النموذج</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">الاسم</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">البريد</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">ملخص</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">ملف</th>
              <th class="text-secondary text-xxs font-weight-bolder opacity-7">التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">لا توجد بيانات</td></tr>
            <?php else: foreach($recent as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><span class="badge bg-gradient-info">#<?= (int)$r['form_id'] ?></span></td>
                <td><?= e($r['name'] ?? '') ?></td>
                <td class="text-muted"><?= e($r['email'] ?? '') ?></td>
                <td class="text-muted"><?= e($r['msg'] ?? '') ?></td>
                <td><?= !empty($r['file']) ? 'نعم' : 'لا' ?></td>
                <td><small><?= e($r['created_at'] ?? '') ?></small></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php };
include __DIR__ . '/_layout.php';
