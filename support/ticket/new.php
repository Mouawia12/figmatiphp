<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/functions.php';
session_start();
if (empty($_SESSION['user']['id'])) { header('Location: ' . app_href('../../login.php')); exit; }
$email = strtolower((string)($_SESSION['user']['email'] ?? ''));

$db = pdo_open('requests');
if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
  $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190), subject VARCHAR(255), category VARCHAR(100), priority VARCHAR(50), status VARCHAR(50) DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  $db->exec("CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT, email VARCHAR(190), message TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
  ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
  $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY, email TEXT, subject TEXT, category TEXT, priority TEXT, status TEXT DEFAULT 'open',
    created_at TEXT DEFAULT (datetime('now'))
  )");
  $db->exec("CREATE TABLE IF NOT EXISTS support_messages (
    id INTEGER PRIMARY KEY, ticket_id INTEGER, email TEXT, message TEXT, created_at TEXT DEFAULT (datetime('now'))
  )");
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $subject = trim((string)($_POST['subject'] ?? ''));
  $category= trim((string)($_POST['category'] ?? ''));
  $priority= trim((string)($_POST['priority'] ?? 'normal'));
  $message = trim((string)($_POST['message'] ?? ''));
  if ($subject !== '') {
    $st = $db->prepare('INSERT INTO support_tickets(email,subject,category,priority,status) VALUES(?,?,?,?,\'open\')');
    $st->execute([$email,$subject,$category,$priority]);
    $tid = (int)$db->lastInsertId();
    if ($message !== '') {
      $db->prepare('INSERT INTO support_messages(ticket_id,email,message) VALUES(?,?,?)')->execute([$tid,$email,$message]);
    }
    header('Location: ' . app_href('support/ticket/view.php?id=' . $tid));
    exit;
  }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تذكرة جديدة</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="app-bg">
  <div class="container py-4" style="max-width:720px">
    <div class="card"><div class="card-body">
      <h5 class="mb-3">تذكرة دعم جديدة</h5>
      <form method="post">
        <div class="mb-3"><label class="form-label">الموضوع</label><input name="subject" class="form-control" required></div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">الفئة</label><input name="category" class="form-control"></div>
          <div class="col-md-6"><label class="form-label">الأولوية</label><select name="priority" class="form-select"><option value="low">منخفضة</option><option value="normal" selected>عادية</option><option value="high">مرتفعة</option></select></div>
        </div>
        <div class="mb-3 mt-3"><label class="form-label">الوصف</label><textarea name="message" class="form-control" rows="4"></textarea></div>
        <div class="d-flex gap-2 mt-2"><button class="btn btn-primary">إنشاء</button><a class="btn btn-light" href="<?= e(app_href('../../dashboard.php#tickets')) ?>">إلغاء</a></div>
      </form>
    </div></div>
  </div>
</body>
</html>
