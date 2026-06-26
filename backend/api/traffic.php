<?php
require_once __DIR__ . '/../config.php';

$iface = $_GET['iface'] ?? 'ether1';

try {
    $api  = getRouterConnection();
    $rows = $api->comm('/interface/monitor-traffic', ['interface' => $iface, 'once' => '']);
    $api->disconnect();
    $row = $rows[0] ?? [];
    jsonResponse([
        'rx_bps' => $row['rx-bits-per-second'] ?? 0,
        'tx_bps' => $row['tx-bits-per-second'] ?? 0,
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
