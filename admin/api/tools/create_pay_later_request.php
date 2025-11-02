<?php
declare(strict_types=1);
require_once __DIR__ . '/../_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
// TODO: persist request, return request id and tracking link
$request_id = random_int(10000, 99999);
$code = bin2hex(random_bytes(5));
$trackPath = ltrim(app_href('track.php'), '/');
$link = public_url($trackPath) . '?code=' . urlencode($code);
api_ok(['request_id' => $request_id, 'track_code' => $code, 'track_url' => $link]);
