<?php
require_once __DIR__ . '/../config.php';

try {
    $db      = getDb();
    $printed = isset($_GET['printed']) ? (int) $_GET['printed'] : 0;

    $sql    = 'SELECT * FROM cards_archive WHERE is_printed = :printed';
    $params = ['printed' => $printed];

    if (!empty($_GET['profile_id'])) {
        $sql .= ' AND profile_id = :profile_id';
        $params['profile_id'] = (int) $_GET['profile_id'];
    }
    if (!empty($_GET['card_type'])) {
        $sql .= ' AND card_type = :card_type';
        $params['card_type'] = $_GET['card_type'];
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 200';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['cards' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
