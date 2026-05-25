<?php

ob_start(); 

require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireLogin();

$fileId = (int)($_GET['id'] ?? 0);
if (!$fileId) {
    ob_end_clean();
    http_response_code(400);
    die('ID file tidak valid.');
}

try {
    $db = getDB();

    // ── Cek akses milik sendiri ───────────────────────────
    $stmt = $db->prepare('
        SELECT f.*, NULL AS share_encrypted_key
        FROM files f
        WHERE f.id = ? AND f.owner_id = ?
    ');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch();

    // ── Cek akses file dibagikan ──────────────────────────
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
        ob_end_clean();
        http_response_code(403);
        die('File tidak ditemukan atau akses ditolak.');
    }

    // ── Ambil private key user ────────────────────────────
    $stmt = $db->prepare('SELECT private_key_enc FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userRow = $stmt->fetch();

    if (empty($_SESSION['user_password'])) {
        ob_end_clean();
        http_response_code(401);
        die('Sesi tidak valid. Silakan login ulang.');
    }

    // ── Dekripsi private key ──────────────────────────────
    $privateKeyPem = decryptPrivateKey(
        $userRow['private_key_enc'],
        $_SESSION['user_password']
    );

    // ── Pilih encrypted key yang sesuai ──────────────────
    $encryptedKeyToUse = !empty($file['share_encrypted_key'])
        ? $file['share_encrypted_key']
        : $file['encrypted_key'];

    // ── Dekripsi session key ──────────────────────────────
    $sessionKey = decryptSessionKey($encryptedKeyToUse, $privateKeyPem);

    // ── Baca file terenkripsi ─────────────────────────────
    $filePath = dirname(__DIR__) . '/uploads/' . $file['stored_name'];
    if (!file_exists($filePath)) {
        ob_end_clean();
        http_response_code(404);
        die('File fisik tidak ditemukan di server.');
    }

    $ciphertext = file_get_contents($filePath);
    if ($ciphertext === false) {
        throw new Exception('Gagal membaca file terenkripsi.');
    }

    // ── Dekripsi file ─────────────────────────────────────
    $plaintext = decryptFile(
        $ciphertext,
        $sessionKey,
        $file['iv'],
        $file['auth_tag']
    );

    // ── Verifikasi SHA-256 ────────────────────────────────
    $actualHash = hash('sha256', $plaintext);
    if (!hash_equals($file['file_hash'], $actualHash)) {
        ob_end_clean();
        http_response_code(500);
        die('PERINGATAN: Integritas file gagal! File mungkin telah dimodifikasi.');
    }

    // ── Deteksi MIME type ─────────────────────────────────
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($plaintext) ?: 'application/octet-stream';

    // ── Bersihkan buffer SEBELUM kirim header ─────────────
    ob_end_clean(); // ← buang semua output yang tertangkap

    // ── Audit log ─────────────────────────────────────────
    writeLog(
        $_SESSION['user_id'],
        'download',
        (int)$file['id'],
        $file['original_name']
    );

    // ── Kirim file ke browser ─────────────────────────────
    $fileSize = strlen($plaintext);
    $fileName = $file['original_name'];

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
    header('Content-Length: ' . $fileSize);
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Flush output buffer PHP sepenuhnya
    if (ob_get_level()) ob_end_clean();
    flush();

    echo $plaintext;
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    die('Error: ' . htmlspecialchars($e->getMessage()));
}