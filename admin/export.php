<?php
require_once __DIR__ . '/../inc/functions.php';
$config = cfg();
$me = require_admin();
if(function_exists('ensure_requests_schema')) ensure_requests_schema();

$form_id = isset($_GET['form_id']) && $_GET['form_id']!=='' ? (int)$_GET['form_id'] : null;
$since   = trim($_GET['since'] ?? '');
$until   = trim($_GET['until'] ?? '');
$q       = trim($_GET['q'] ?? '');

$dbr = pdo_open($config->db_requests);
$dbr->exec("CREATE TABLE IF NOT EXISTS requests (id INTEGER PRIMARY KEY, form_id INTEGER, name TEXT, email TEXT, message TEXT, file TEXT, created_at TEXT)");

$where=[];$args=[];
if(!is_null($form_id)){ $where[]='form_id = ?'; $args[]=$form_id; }
if($since!==''){ $where[]='date(created_at) >= date(?)'; $args[]=$since; }
if($until!==''){ $where[]='date(created_at) <= date(?)'; $args[]=$until; }
if($q!==''){ $where[]='(name LIKE ? OR email LIKE ? OR message LIKE ?)'; $args[]="%$q%"; $args[]="%$q%"; $args[]="%$q%"; }

$sql="SELECT id, form_id, name, email, message, file, created_at FROM requests";
if($where) $sql.=" WHERE ".implode(' AND ',$where);
$sql.=" ORDER BY id DESC";

$st=$dbr->prepare($sql);
$st->execute($args);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="requests_export.csv"');

$out=fopen('php://output','w');
fputcsv($out, ['id','form_id','name','email','message','file','created_at']);
foreach($rows as $r){ fputcsv($out, $r); }
fclose($out);
exit;
