<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$fileId = (int)($input['file_id'] ?? 0);

if (!$fileId) {
    echo json_encode(['error' => 'ID file tidak valid.']);
    exit;
}

try {
    $db = getDB();

    // Pastikan hanya pemilik yang bisa hapus
    // ✅ FIX: tambah original_name di SELECT agar writeLog bisa pakai nama asli
    $stmt = $db->prepare('SELECT stored_name, owner_id, original_name FROM files WHERE id = ?');
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        throw new Exception('File tidak ditemukan.');
    }
    if ((int)$file['owner_id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('Akses ditolak. Kamu bukan pemilik file ini.');
    }

    // Hapus file fisik dari server
    $filePath = dirname(__DIR__) . '/uploads/' . $file['stored_name'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Hapus dari database (cascade ke file_shares)
    $stmt = $db->prepare('DELETE FROM files WHERE id = ?');
    $stmt->execute([$fileId]);

    // ✅ FIX: writeLog dipindah SEBELUM response, dan pakai original_name bukan stored_name
    writeLog($_SESSION['user_id'], 'delete', $fileId, $file['original_name']);

    echo json_encode(['success' => true, 'message' => 'File berhasil dihapus.']);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['error' => $e->getMessage()]);
}