<?php
require_once '../config/db.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true);
$fileId    = (int)($input['file_id'] ?? 0);
$revokeFor = trim($input['username'] ?? '');   // username penerima yang dicabut

if (!$fileId || empty($revokeFor)) {
    echo json_encode(['error' => 'Parameter tidak lengkap.']);
    exit;
}

try {
    $db = getDB();

    // Pastikan file milik pengirim
    $stmt = $db->prepare('SELECT id FROM files WHERE id = ? AND owner_id = ?');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Akses ditolak.');
    }

    // Cari user yang akan dicabut aksesnya
    $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute([$revokeFor]);
    $target = $stmt->fetch();

    if (!$target) {
        throw new Exception("User \"{$revokeFor}\" tidak ditemukan.");
    }

    // Hapus record share
    $stmt = $db->prepare('
        DELETE FROM file_shares
        WHERE file_id = ? AND shared_to = ?
    ');
    $stmt->execute([$fileId, $target['id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("User \"{$revokeFor}\" tidak memiliki akses ke file ini.");
    }

    echo json_encode([
        'success' => true,
        'message' => "Akses \"{$revokeFor}\" berhasil dicabut.",
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}