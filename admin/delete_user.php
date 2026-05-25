<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireAdmin();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$userId = (int)($input['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['error' => 'ID tidak valid.']);
    exit;
}

if ($userId === (int)$_SESSION['user_id']) {
    echo json_encode(['error' => 'Tidak bisa menghapus akun sendiri.']);
    exit;
}

try {
    $db = getDB();

    // Ambil file fisik milik user
    $stmt = $db->prepare('SELECT stored_name FROM files WHERE owner_id = ?');
    $stmt->execute([$userId]);
    $files = $stmt->fetchAll();

    // Hapus file fisik
    foreach ($files as $f) {
        $path = dirname(__DIR__) . '/uploads/' . $f['stored_name'];
        if (file_exists($path)) unlink($path);
    }

    // Hapus user dari DB (cascade hapus files & shares)
    $stmt = $db->prepare('DELETE FROM users WHERE id = ? AND is_admin = 0');
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('User tidak ditemukan atau adalah admin.');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}