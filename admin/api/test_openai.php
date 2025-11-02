<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('GET');
require_once __DIR__ . '/../../inc/functions.php';

$apiKey = (string)env('OPENAI_API_KEY', '');
$model  = (string)env('OPENAI_MODEL', 'gpt-4o-mini');
if ($apiKey === '') { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error'=>'missing_openai_key']); exit; }

$start = microtime(true);
$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [ 'Authorization: Bearer ' . $apiKey, 'Accept: application/json' ],
  CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);
$ms = (int)round((microtime(true) - $start) * 1000);
header('Content-Type: application/json; charset=utf-8');
if ($res === false || $code >= 400) { echo json_encode(['error'=>'openai_unreachable','detail'=>$err ?: $res, 'ms'=>$ms]); exit; }

echo json_encode(['ok'=>true, 'model'=>$model, 'ms'=>$ms]);
