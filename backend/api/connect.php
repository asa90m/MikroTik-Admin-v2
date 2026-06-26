<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/RouterosAPI.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$host  = trim($input['host'] ?? '');
$user  = trim($input['user'] ?? '');
$pass  = (string) ($input['pass'] ?? '');
$ssl   = !empty($input['ssl']);
$port  = (int) ($input['port'] ?? ($ssl ? 8729 : 8728));

if ($host === '' || $user === '') {
    jsonResponse(['error' => 'أدخل عنوان الراوتر واسم المستخدم'], 400);
}

// ملاحظة: هذا العميل يتصل بمنفذ API الخام (8728). الاتصال المشفّر (8729/SSL)
// يحتاج فتح المقبس عبر "ssl://" بدل fsockopen العادي — أضِفه في RouterosAPI::connect
// إن كان راوترك يفرض API-SSL فقط (IP > Services > api-ssl).
$api = new RouterosAPI();
$ok  = $api->connect($host, $user, $pass, $port);

if (!$ok) {
    jsonResponse(['error' => $api->errorStr], 401);
}

$identity = $api->comm('/system/identity/print')[0] ?? [];
$resource = $api->comm('/system/resource/print')[0] ?? [];
$board    = $api->comm('/system/routerboard/print')[0] ?? [];
$api->disconnect();

// حفظ بيانات الاتصال في جلسة PHP — تماماً كما يبقيك Winbox متصلاً بعد إدخال البيانات مرة واحدة
$_SESSION['router'] = compact('host', 'port', 'user', 'pass');

jsonResponse([
    'connected' => true,
    'identity'  => $identity['name'] ?? $host,
    'version'   => $resource['version'] ?? '',
    'board'     => $board['model'] ?? '',
    'uptime'    => $resource['uptime'] ?? '',
]);
