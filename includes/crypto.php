<?php

// ── Konfigurasi OpenSSL (cross-platform) ─────────────────────
// Hanya set OPENSSL_CONF di Windows (XAMPP), karena di Linux/Mac
// OpenSSL sudah mengetahui path konfigurasinya secara otomatis.
if (PHP_OS_FAMILY === 'Windows') {
    // Cari openssl.cnf di beberapa lokasi umum XAMPP
    $candidates = [
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/ssl/openssl.cnf',
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            define('OPENSSL_CNF_PATH', $path);
            putenv('OPENSSL_CONF=' . OPENSSL_CNF_PATH);
            break;
        }
    }
}

// Jika bukan Windows atau file tidak ditemukan, konstanta tidak didefinisikan.
// Fungsi generateKeyPair() menggunakan null agar OpenSSL pakai default sistem.
if (!defined('OPENSSL_CNF_PATH')) {
    define('OPENSSL_CNF_PATH', null);
}

/**
 * Generate RSA-2048 key pair untuk user baru.
 */
function generateKeyPair(string $userPassword): array {
    $config = [
        'digest_alg'       => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    
    if (OPENSSL_CNF_PATH !== null) {
        $config['config'] = OPENSSL_CNF_PATH;
    }

    $keyPair = openssl_pkey_new($config);
    if (!$keyPair) {
        throw new Exception('Gagal membuat key pair: ' . openssl_error_string());
    }

    $keyDetails = openssl_pkey_get_details($keyPair);
    $publicKey  = $keyDetails['key'];

    $exportConfig = OPENSSL_CNF_PATH !== null ? ['config' => OPENSSL_CNF_PATH] : [];
    openssl_pkey_export($keyPair, $privateKeyPem, null, $exportConfig);

    $encryptedPrivateKey = encryptPrivateKey($privateKeyPem, $userPassword);

    return [
        'public_key'      => $publicKey,
        'private_key_enc' => $encryptedPrivateKey,
    ];
}

/**
 * Enkripsi private key dengan AES-256-CBC + PBKDF2.
 */
function encryptPrivateKey(string $privateKeyPem, string $password): string {
    $salt   = random_bytes(16);
    $iv     = random_bytes(16);
    $aesKey = hash_pbkdf2('sha256', $password, $salt, 100000, 32, true);

    $encrypted = openssl_encrypt(
        $privateKeyPem, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv
    );

    return base64_encode($salt . $iv . $encrypted);
}

/**
 * Dekripsi private key dari database.
 */
function decryptPrivateKey(string $encryptedB64, string $password): string {
    $raw        = base64_decode($encryptedB64);
    $salt       = substr($raw, 0, 16);
    $iv         = substr($raw, 16, 16);
    $ciphertext = substr($raw, 32);
    $aesKey     = hash_pbkdf2('sha256', $password, $salt, 100000, 32, true);

    $decrypted = openssl_decrypt(
        $ciphertext, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv
    );

    if ($decrypted === false) {
        throw new Exception('Gagal dekripsi private key. Password salah?');
    }
    return $decrypted;
}

/**
 * Enkripsi session key dengan RSA public key (OAEP).
 */
function encryptSessionKey(string $sessionKey, string $publicKeyPem): string {
    $encrypted = '';
    if (!openssl_public_encrypt($sessionKey, $encrypted, $publicKeyPem, OPENSSL_PKCS1_OAEP_PADDING)) {
        throw new Exception('Gagal enkripsi session key: ' . openssl_error_string());
    }
    return base64_encode($encrypted);
}

/**
 * Dekripsi session key dengan RSA private key (OAEP).
 */
function decryptSessionKey(string $encryptedKeyB64, string $privateKeyPem): string {
    $encrypted = base64_decode($encryptedKeyB64);
    $decrypted = '';
    if (!openssl_private_decrypt($encrypted, $decrypted, $privateKeyPem, OPENSSL_PKCS1_OAEP_PADDING)) {
        throw new Exception('Gagal dekripsi session key: ' . openssl_error_string());
    }
    return $decrypted;
}

/**
 * Enkripsi file dengan AES-256-GCM.
 */
function encryptFile(string $plaintext): array {
    $sessionKey = random_bytes(32);
    $iv         = random_bytes(12);
    $tag        = '';

    $ciphertext = openssl_encrypt(
        $plaintext, 'aes-256-gcm', $sessionKey,
        OPENSSL_RAW_DATA, $iv, $tag, '', 16
    );

    if ($ciphertext === false) {
        throw new Exception('Gagal enkripsi file: ' . openssl_error_string());
    }

    return [
        'session_key' => $sessionKey,
        'iv'          => base64_encode($iv),
        'auth_tag'    => base64_encode($tag),
        'ciphertext'  => $ciphertext,
        'file_hash'   => hash('sha256', $plaintext),
    ];
}

/**
 * Dekripsi file dengan AES-256-GCM.
 */
function decryptFile(string $ciphertext, string $sessionKey, string $ivB64, string $tagB64): string {
    $iv  = base64_decode($ivB64);
    $tag = base64_decode($tagB64);

    $plaintext = openssl_decrypt(
        $ciphertext, 'aes-256-gcm', $sessionKey,
        OPENSSL_RAW_DATA, $iv, $tag
    );

    if ($plaintext === false) {
        throw new Exception('Dekripsi gagal. File mungkin sudah dimodifikasi.');
    }
    return $plaintext;
}

function writeLog(int $userId, string $action, ?int $fileId = null, ?string $fileName = null): void {
    try {
        $db   = getDB();
        $ip   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $db->prepare('
            INSERT INTO audit_logs (user_id, action, file_id, file_name, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $action, $fileId, $fileName, $ip]);
    } catch (Exception $e) {
        // Log gagal tidak boleh menghentikan operasi utama
    }
}