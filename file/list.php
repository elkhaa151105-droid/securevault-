<?php
require_once '../config/db.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

try {
    $db = getDB();

    // File milik sendiri
    $stmt = $db->prepare('
        SELECT
            f.id,
            f.original_name,
            f.file_size,
            f.file_hash,
            f.uploaded_at,
            "own" AS access_type,
            (
                SELECT GROUP_CONCAT(u2.username ORDER BY fs2.shared_at SEPARATOR ", ")
                FROM file_shares fs2
                JOIN users u2 ON u2.id = fs2.shared_to
                WHERE fs2.file_id = f.id
            ) AS shared_with
        FROM files f
        WHERE f.owner_id = ?

        UNION ALL

        SELECT
            f.id,
            f.original_name,
            f.file_size,
            f.file_hash,
            fs.shared_at AS uploaded_at,
            "shared" AS access_type,
            u_owner.username AS shared_with
        FROM file_shares fs
        JOIN files f ON f.id = fs.file_id
        JOIN users u_owner ON u_owner.id = f.owner_id
        WHERE fs.shared_to = ?

        ORDER BY uploaded_at DESC
    ');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $files = $stmt->fetchAll();

    foreach ($files as &$f) {
        $bytes = (int)$f['file_size'];
        if ($bytes >= 1048576) {
            $f['size_display'] = round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $f['size_display'] = round($bytes / 1024, 1) . ' KB';
        } else {
            $f['size_display'] = $bytes . ' B';
        }
    }

    echo json_encode(['success' => true, 'files' => $files]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}