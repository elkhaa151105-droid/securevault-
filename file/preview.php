<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireLogin();

$fileId = (int)($_GET['id'] ?? 0);
if (!$fileId) {
    http_response_code(400);
    die(json_encode(['error' => 'ID tidak valid.']));
}

try {
    $db = getDB();

    // Cek akses — milik sendiri
    $stmt = $db->prepare('
        SELECT f.*, NULL AS share_encrypted_key
        FROM files f
        WHERE f.id = ? AND f.owner_id = ?
    ');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch();

    // Cek akses — file dibagikan
    if (!$file) {
        $stmt = $db->prepare('
            SELECT f.*, fs.encrypted_key AS share_encrypted_key
            FROM files f
            JOIN file_shares fs ON fs.file_id = f.id
            WHERE f.id = ? AND fs.shared_to = ?
        ');
        $stmt->execute([$fileId, $_SESSION['user_id']]);
        $file = $stmt->fetch();
    }

    if (!$file) {
        http_response_code(403);
        die(json_encode(['error' => 'Akses ditolak.']));
    }

    // Dekripsi private key
    $stmt = $db->prepare('SELECT private_key_enc FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userRow = $stmt->fetch();

    if (empty($_SESSION['user_password'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Sesi tidak valid.']));
    }

    $privateKeyPem = decryptPrivateKey(
        $userRow['private_key_enc'],
        $_SESSION['user_password']
    );

    $encryptedKeyToUse = !empty($file['share_encrypted_key'])
        ? $file['share_encrypted_key']
        : $file['encrypted_key'];

    $sessionKey = decryptSessionKey($encryptedKeyToUse, $privateKeyPem);

    $filePath = dirname(__DIR__) . '/uploads/' . $file['stored_name'];
    if (!file_exists($filePath)) {
        http_response_code(404);
        die(json_encode(['error' => 'File tidak ditemukan.']));
    }

    $ciphertext = file_get_contents($filePath);
    $plaintext  = decryptFile($ciphertext, $sessionKey, $file['iv'], $file['auth_tag']);

    // Deteksi tipe file
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($plaintext) ?: 'application/octet-stream';

    // Hanya izinkan preview untuk tipe yang aman
    $previewable = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'text/plain', 'application/pdf'
    ];

    if (!in_array($mimeType, $previewable)) {
        http_response_code(415);
        die(json_encode([
            'error'     => 'Tipe file tidak mendukung preview.',
            'mime_type' => $mimeType,
        ]));
    }

    // Kirim sebagai data URL base64 untuk ditampilkan di browser
    $base64   = base64_encode($plaintext);
    $dataUrl  = 'data:' . $mimeType . ';base64,' . $base64;

    header('Content-Type: application/json');
    echo json_encode([
        'success'       => true,
        'data_url'      => $dataUrl,
        'mime_type'     => $mimeType,
        'original_name' => $file['original_name'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}