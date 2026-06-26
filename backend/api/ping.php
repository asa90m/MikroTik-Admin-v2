<?php
require_once __DIR__ . '/../config.php';

$host = $_GET['host'] ?? '8.8.8.8';
if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.\-]+$/', $host)) {
    jsonResponse(['error' => 'عنوان غير صالح'], 400);
}

try {
    $api = getRouterConnection();
    // أمر /ping على RouterOS يعيد عدة جمل !re، نطلب 4 حزم فقط
    $replies = $api->comm('/ping', ['address' => $host, 'count' => '4']);
    $api->disconnect();
    jsonResponse(['replies' => $replies]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
