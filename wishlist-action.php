<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SESSION['user']['role'] ?? null) !== 'guest') {
    http_response_code(403);
    exit;
}
verify_csrf();
$userId = (int) $_SESSION['user']['id'];
$stayId = (int) input($_POST, 'stay_id');
$save = input($_POST, 'save') === '1';
$destination = input($_POST, 'return');
if (!preg_match('~^(?:index|search|staydetails|wishlist)\.php(?:\?[^\r\n]*)?$~D', $destination)) {
    $destination = 'wishlist.php';
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? ORDER BY id LIMIT 1");
        $stmt->execute([$userId]);
        $list = $stmt->fetchColumn();
        if ($save && !$list) {
            $pdo->prepare("INSERT INTO wishlists (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())")->execute([$userId]);
            $list = $pdo->lastInsertId();
        }
        if ($save) {
            $stmt = $pdo->prepare("INSERT INTO wishlist_stay (wishlist_id, stay_id, created_at, updated_at)
                SELECT ?, s.id, NOW(), NOW() FROM stays s WHERE s.id = ? AND s.available = 1
                AND NOT EXISTS (SELECT 1 FROM wishlist_stay WHERE wishlist_id = ? AND stay_id = s.id)");
            $stmt->execute([$list, $stayId, $list]);
        } else {
            $pdo->prepare("DELETE ws FROM wishlist_stay ws JOIN wishlists w ON w.id = ws.wishlist_id WHERE w.user_id = ? AND ws.stay_id = ?")
                ->execute([$userId, $stayId]);
        }
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
redirect($destination);
