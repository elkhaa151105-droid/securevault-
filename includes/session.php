<?php
session_start();
require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /securevault/auth/login.php');
        exit;
    }
}

function setSession($user) {
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
}

function destroySession() {
    session_unset();
    session_destroy();
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
    ];
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: /securevault/auth/login.php');
        exit;
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        die('Akses ditolak. Halaman ini hanya untuk admin.');
    }
}