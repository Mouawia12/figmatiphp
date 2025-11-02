<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if(!verify_csrf($_POST['csrf_token'] ?? '')) { die('CSRF'); }
$title = trim($_POST['title'] ?? '');
$fields = trim($_POST['fields'] ?? '');
if($title === '' || $fields === '') { header('Location: index.php'); exit; }
$db = pdo_open($config->db_forms);
if (($config->db_driver ?? 'sqlite') === 'mysql') {
  $charset = $config->db_charset ?? 'utf8mb4';
  $db->exec("CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255), fields LONGTEXT, created_at DATETIME
  ) ENGINE=InnoDB DEFAULT CHARSET={$charset}");
  $stmt = $db->prepare("INSERT INTO forms (title,fields,created_at) VALUES (?,?,NOW())");
  $stmt->execute([$title,$fields]);
} else {
  $db->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, fields TEXT, created_at TEXT)");
  $stmt = $db->prepare("INSERT INTO forms (title,fields,created_at) VALUES (?,?,datetime('now'))");
  $stmt->execute([$title,$fields]);
}
header('Location: index.php');
exit;
