<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = (int) ($input['id'] ?? 0);

try {
    $db   = getDb();
    $stmt = $db->prepare('UPDATE cards_archive SET is_printed = 1, printed_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $id]);
    jsonResponse(['ok' => true]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
