<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan.']);
    exit;
}

// Batas ukuran file: 20 MB
define('MAX_FILE_SIZE', 20 * 1024 * 1024);

try {
    // Validasi file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas server).',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (batas form).',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file.',
        ];
        $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($uploadErrors[$errCode] ?? 'Upload gagal.');
    }

    $file         = $_FILES['file'];
    $originalName = basename($file['name']);
    $fileSize     = $file['size'];

    // Validasi ukuran
    if ($fileSize > MAX_FILE_SIZE) {
        throw new Exception('Ukuran file melebihi batas 20 MB.');
    }

    // Validasi nama file
    if (empty($originalName) || $originalName === '.') {
        throw new Exception('Nama file tidak valid.');
    }

    // Baca isi file asli
    $plaintext = file_get_contents($file['tmp_name']);
    if ($plaintext === false) {
        throw new Exception('Gagal membaca file yang diupload.');
    }

    // ── ENKRIPSI FILE (AES-256-GCM) ──────────────────────────────
    $encrypted = encryptFile($plaintext);
    // $encrypted berisi: session_key, iv, auth_tag, ciphertext, file_hash

    // ── WRAP SESSION KEY (RSA-OAEP) ──────────────────────────────
    $db   = getDB();
    $stmt = $db->prepare('SELECT public_key FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User tidak ditemukan.');
    }

    $encryptedKey = encryptSessionKey($encrypted['session_key'], $user['public_key']);

    // ── SIMPAN FILE TERENKRIPSI ───────────────────────────────────
    // Nama file di server: random agar tidak bisa ditebak
    $storedName = bin2hex(random_bytes(16)) . '.enc';
    $uploadPath = dirname(__DIR__) . '/uploads/' . $storedName;

    if (file_put_contents($uploadPath, $encrypted['ciphertext']) === false) {
        throw new Exception('Gagal menyimpan file terenkripsi.');
    }

    // ── SIMPAN METADATA KE DATABASE ───────────────────────────────
    $stmt = $db->prepare('
        INSERT INTO files
            (owner_id, original_name, stored_name, file_size, encrypted_key, iv, auth_tag, file_hash)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $_SESSION['user_id'],
        $originalName,
        $storedName,
        $fileSize,
        $encryptedKey,
        $encrypted['iv'],
        $encrypted['auth_tag'],
        $encrypted['file_hash'],
    ]);

    $fileId = $db->lastInsertId();
    writeLog($_SESSION['user_id'], 'upload', (int)$fileId, $originalName);

    // Hapus file sementara PHP (sudah terenkripsi, tidak perlu simpan plaintext)
    @unlink($file['tmp_name']);

    echo json_encode([
        'success' => true,
        'file_id' => $fileId,
        'message' => "File '{$originalName}' berhasil dienkripsi dan disimpan.",
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}