<?php
require_once '../config/db.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

$fileId = (int)($_GET['file_id'] ?? 0);
if (!$fileId) {
    echo json_encode(['error' => 'File ID tidak valid.']);
    exit;
}

try {
    $db = getDB();

    // Pastikan file milik user ini
    $stmt = $db->prepare('SELECT id FROM files WHERE id = ? AND owner_id = ?');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Akses ditolak.');
    }

    // Ambil daftar penerima
    $stmt = $db->prepare('
        SELECT
            u.username,
            u.email,
            fs.shared_at
        FROM file_shares fs
        JOIN users u ON u.id = fs.shared_to
        WHERE fs.file_id = ?
        ORDER BY fs.shared_at DESC
    ');
    $stmt->execute([$fileId]);
    $shares = $stmt->fetchAll();

    echo json_encode(['success' => true, 'shares' => $shares]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['error' => $e->getMessage()]);
}