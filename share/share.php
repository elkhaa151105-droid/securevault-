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

$input  = json_decode(file_get_contents('php://input'), true);
$fileId = (int)($input['file_id'] ?? 0);
$target = trim($input['target'] ?? '');   // username atau email penerima

if (!$fileId || empty($target)) {
    echo json_encode(['error' => 'File ID dan target wajib diisi.']);
    exit;
}

try {
    $db = getDB();

    // ── 1. Pastikan file milik pengirim ───────────────────────
    $stmt = $db->prepare('
        SELECT id, encrypted_key, original_name
        FROM files
        WHERE id = ? AND owner_id = ?
    ');
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch();

    if (!$file) {
        throw new Exception('File tidak ditemukan atau kamu bukan pemiliknya.');
    }

    // ── 2. Cari user penerima berdasarkan username atau email ─
    $stmt = $db->prepare('
        SELECT id, username, public_key
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1
    ');
    $stmt->execute([$target, $target]);
    $recipient = $stmt->fetch();

    if (!$recipient) {
        throw new Exception("User \"{$target}\" tidak ditemukan.");
    }

    // Tidak boleh share ke diri sendiri
    if ((int)$recipient['id'] === (int)$_SESSION['user_id']) {
        throw new Exception('Tidak bisa membagikan file ke diri sendiri.');
    }

    // Cek apakah sudah pernah dibagikan ke user ini
    $stmt = $db->prepare('
        SELECT id FROM file_shares
        WHERE file_id = ? AND shared_to = ?
    ');
    $stmt->execute([$fileId, $recipient['id']]);
    if ($stmt->fetch()) {
        throw new Exception("File sudah pernah dibagikan ke \"{$recipient['username']}\".");
    }

    // ── 3. Ambil private key pengirim dari DB ─────────────────
    $stmt = $db->prepare('SELECT private_key_enc FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $senderRow = $stmt->fetch();

    if (empty($_SESSION['user_password'])) {
        throw new Exception('Sesi tidak valid. Silakan login ulang.');
    }

    // ── 4. Dekripsi private key pengirim ─────────────────────
    $privateKeyPem = decryptPrivateKey(
        $senderRow['private_key_enc'],
        $_SESSION['user_password']
    );

    // ── 5. Dekripsi session key dari encrypted_key milik pengirim
    $sessionKey = decryptSessionKey($file['encrypted_key'], $privateKeyPem);

    // ── 6. Re-enkripsi session key dengan public key penerima ─
    $newEncryptedKey = encryptSessionKey($sessionKey, $recipient['public_key']);

    // ── 7. Simpan ke tabel file_shares ───────────────────────
    $stmt = $db->prepare('
        INSERT INTO file_shares (file_id, shared_by, shared_to, encrypted_key)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([
        $fileId,
        $_SESSION['user_id'],
        $recipient['id'],
        $newEncryptedKey,
    ]);

    writeLog($_SESSION['user_id'], 'share', (int)$fileId, $file['original_name']);

    echo json_encode([
        'success'   => true,
        'shared_to' => $recipient['username'],
        'message'   => "File \"{$file['original_name']}\" berhasil dibagikan ke \"{$recipient['username']}\".",
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}