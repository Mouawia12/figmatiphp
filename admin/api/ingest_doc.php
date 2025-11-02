<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
api_require_admin();
api_require_method('POST');
$data = $_POST;
api_verify_csrf_for_form();
$docIds = [];
api_ok(['document_ids' => $docIds]);
