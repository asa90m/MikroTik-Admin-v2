<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$code  = trim($input['code'] ?? '');

if ($code === '' || $code !== ACTIVATION_CODE) {
    jsonResponse(['error' => 'كود التفعيل غير صحيح'], 400);
}

$data = file_exists(LICENSE_FILE) ? json_decode(file_get_contents(LICENSE_FILE), true) : [];
if (!is_array($data)) $data = [];
$data['activated'] = true;
$data['activated_at'] = date('c');
@mkdir(dirname(LICENSE_FILE), 0775, true);
file_put_contents(LICENSE_FILE, json_encode($data));

jsonResponse(['ok' => true]);
