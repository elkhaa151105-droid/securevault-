<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireLogin();
header('Content-Type: application/json');

$fileId = (int)($_GET['id'] ?? 0);
if (!$fileId) {
    echo json_encode(['error' => 'ID tidak valid.']);
    exit;
}

try {
    $db = getDB();

    // ── Cek apakah file milik sendiri ────────────────────────
    $stmt = $db->prepare('
        SELECT f.stored_name, f.file_hash, f.original_name,
               f.encrypted_key, f.iv, f.auth_tag,
               "own" AS access_type,
               NULL AS share_encrypted_key
        FROM files f
        WHERE f.id = ? AND f.owner_id = ?
    ');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch();

    // ── Kalau bukan milik sendiri, cek di file_shares ────────
    if (!$file) {
        $stmt = $db->prepare('
            SELECT f.stored_name, f.file_hash, f.original_name,
                   f.encrypted_key, f.iv, f.auth_tag,
                   "shared" AS access_type,
                   fs.encrypted_key AS share_encrypted_key
            FROM files f
            JOIN file_shares fs ON fs.file_id = f.id
            WHERE f.id = ? AND fs.shared_to = ?
        ');
        $stmt->execute([$fileId, $_SESSION['user_id']]);
        $file = $stmt->fetch();
    }

    if (!$file) {
        throw new Exception('File tidak ditemukan atau akses ditolak.');
    }

    // ── Ambil & dekripsi private key user yang login ─────────
    $stmt = $db->prepare('SELECT private_key_enc FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userRow = $stmt->fetch();

    if (empty($_SESSION['user_password'])) {
        throw new Exception('Sesi tidak valid. Silakan login ulang.');
    }

    $privateKeyPem = decryptPrivateKey(
        $userRow['private_key_enc'],
        $_SESSION['user_password']
    );

    // ── Pilih encrypted_key yang sesuai ──────────────────────
    // Jika file dibagikan → pakai share_encrypted_key
    // Jika file milik sendiri → pakai encrypted_key
    $encryptedKeyToUse = !empty($file['share_encrypted_key'])
        ? $file['share_encrypted_key']
        : $file['encrypted_key'];

    // ── Dekripsi session key ──────────────────────────────────
    $sessionKey = decryptSessionKey($encryptedKeyToUse, $privateKeyPem);

    // ── Baca & dekripsi file ──────────────────────────────────
    $filePath   = dirname(__DIR__) . '/uploads/' . $file['stored_name'];
    if (!file_exists($filePath)) {
        throw new Exception('File fisik tidak ditemukan di server.');
    }

    $ciphertext = file_get_contents($filePath);
    $plaintext  = decryptFile($ciphertext, $sessionKey, $file['iv'], $file['auth_tag']);

    // ── Bandingkan hash SHA-256 ───────────────────────────────
    $actualHash   = hash('sha256', $plaintext);
    $expectedHash = $file['file_hash'];
    $isValid      = hash_equals($expectedHash, $actualHash);

    echo json_encode([
        'success'       => true,
        'file_name'     => $file['original_name'],
        'is_valid'      => $isValid,
        'status'        => $isValid ? 'INTEGRITAS OK ✓' : 'INTEGRITAS GAGAL ✗',
        'expected_hash' => $expectedHash,
        'actual_hash'   => $actualHash,
        'access_type'   => $file['access_type'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}