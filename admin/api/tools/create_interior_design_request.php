<?php
declare(strict_types=1);
require_once __DIR__ . '/../_common.php';
api_require_admin();
api_require_method('POST');
$data = api_json_input();
api_verify_csrf_for_json($data);
// TODO: persist interior design request
$request_id = random_int(10000, 99999);
api_ok(['request_id' => $request_id]);
