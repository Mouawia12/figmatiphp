<?php
declare(strict_types=1);

// Ensure config is loaded and get the global APP object
if (!isset($GLOBALS['__CROSING_APP__'])) {
    require_once __DIR__ . '/../config.php';
}
$APP = $GLOBALS['__CROSING_APP__'];

function db(): PDO {
  global $APP; // Make sure $APP is available in the function scope
  static $pdo;
  if ($pdo) return $pdo;
  
  $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s',
    $APP->db_driver ?? 'mysql',
    $APP->db_host   ?? 'localhost',
    $APP->db_name   ?? '',
    $APP->db_charset?? 'utf8mb4',
  );
  
  $pdo = new PDO($dsn, $APP->db_user, $APP->db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
