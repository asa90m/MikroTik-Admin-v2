<?php
require_once __DIR__ . '/../config.php';

try {
    $api = getRouterConnection();
    $pools = $api->comm('/ip/pool/print');
    $used  = $api->comm('/ip/pool/used/print');
    $api->disconnect();
    jsonResponse(['pools' => $pools, 'used' => $used]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
