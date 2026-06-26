<?php
require_once __DIR__ . '/../config.php';

try {
    $api = getRouterConnection();
    $rows = $api->comm('/ip/neighbor/print');
    $api->disconnect();
    jsonResponse(['neighbors' => $rows]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
