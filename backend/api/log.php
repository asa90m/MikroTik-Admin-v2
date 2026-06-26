<?php
require_once __DIR__ . '/../config.php';

try {
    $api  = getRouterConnection();
    $rows = $api->comm('/log/print');
    $api->disconnect();
    jsonResponse(['log' => array_slice(array_reverse($rows), 0, 100)]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
