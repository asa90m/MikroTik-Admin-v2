<?php
require_once __DIR__ . '/../config.php';

try {
    $db = getDb();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt  = $db->prepare(
            'UPDATE routers_config
             SET router_name = :name, api_host = :host, api_port = :port, api_user = :user, api_pass = :pass
             WHERE id = 1'
        );
        $stmt->execute([
            'name' => $input['router_name'] ?? '',
            'host' => $input['api_host'] ?? '',
            'port' => (int) ($input['api_port'] ?? 8728),
            'user' => $input['api_user'] ?? '',
            'pass' => $input['api_pass'] ?? '', // شفّرها قبل الحفظ في بيئة الإنتاج
        ]);
        jsonResponse(['ok' => true]);
    } else {
        $row = $db->query(
            'SELECT id, router_name, api_host, api_port, api_user FROM routers_config LIMIT 1'
        )->fetch();
        jsonResponse($row ?: []);
    }
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
