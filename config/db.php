<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // sesuaikan dengan user XAMPP kamu
define('DB_PASS', '');           // kosong jika default XAMPP
define('DB_NAME', 'securevault');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Koneksi database gagal']));
        }
    }
    return $pdo;
}