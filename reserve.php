<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$token = $_POST['token'] ?? '';
$giftId = (int)($_POST['gift_id'] ?? 0);
$guestName = trim($_POST['guest_name'] ?? '');

$settings = getSettings();
if (!$token || $token !== $settings['guest_access_token']) {
    echo json_encode(['success' => false, 'message' => 'Ungültiger Zugang.']);
    exit;
}

if (!$giftId || !$guestName) {
    echo json_encode(['success' => false, 'message' => 'Bitte wähle ein Geschenk und gib deinen Namen an.']);
    exit;
}

$pdo = getPDO();

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT is_reserved FROM gifts WHERE id = ? FOR UPDATE');
    $stmt->execute([$giftId]);
    $gift = $stmt->fetch();

    if (!$gift) {
        throw new RuntimeException('Geschenk nicht gefunden.');
    }

    if ($gift['is_reserved']) {
        throw new RuntimeException('Dieses Geschenk ist bereits reserviert.');
    }

    $update = $pdo->prepare('UPDATE gifts SET is_reserved = 1, reserved_by_name = ? WHERE id = ?');
    $update->execute([$guestName, $giftId]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
