<?php
// /crosing/admin/forms_view.php
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();
$dbf = pdo_open(cfg()->db_forms);
$dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
$id = (int)($_GET['id'] ?? 0);
header('Content-Type: application/json; charset=utf-8');
if ($id <= 0) { echo json_encode(['ok'=>0,'error'=>'id_required']); exit; }
$st = $dbf->prepare("SELECT id,title,fields,created_at FROM forms WHERE id=?");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo json_encode(['ok'=>0,'error'=>'not_found']); exit; }
echo json_encode(['ok'=>1,'data'=>$row], JSON_UNESCAPED_UNICODE);
