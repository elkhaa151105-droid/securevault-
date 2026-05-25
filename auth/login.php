<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';    // ← tambahkan baris ini
require_once '../includes/session.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                setSession($user);
                $_SESSION['user_password'] = $password;
                writeLog($user['id'], 'login');    // ← sudah ada, tinggal pastikan crypto.php ter-include
                header('Location: /securevault/index.php');
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SecureVault</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 2.5rem;
            width: 100%; max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 1.75rem; }
        .logo .icon { font-size: 2.5rem; }
        .logo h1 { font-size: 1.5rem; color: #1a1a2e; margin-top: .4rem; }
        .logo p  { font-size: .85rem; color: #888; margin-top: .25rem; }

        label { display: block; font-size: .85rem; font-weight: 600;
                color: #444; margin-bottom: .35rem; }
        input[type=email], input[type=password] {
            width: 100%; padding: .7rem 1rem; border: 1.5px solid #ddd;
            border-radius: 9px; font-size: .95rem; margin-bottom: 1rem;
            transition: border .2s; background: #fafafa;
        }
        input:focus { outline: none; border-color: #4361ee; background: #fff; }

        .btn {
            width: 100%; padding: .8rem; background: #4361ee; color: #fff;
            border: none; border-radius: 9px; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: background .2s; margin-top: .5rem;
        }
        .btn:hover { background: #3451d1; }

        .alert-error {
            background: #fff0f0; color: #c00; border: 1px solid #fcc;
            padding: .75rem 1rem; border-radius: 9px; margin-bottom: 1.25rem;
            font-size: .88rem;
        }
        .divider { text-align: center; margin: 1.25rem 0; font-size: .85rem; color: #aaa; }
        .divider span { background: #fff; padding: 0 .75rem; }
        .divider::before { content:''; display:block; border-top: 1px solid #eee;
                           margin-bottom: -0.6rem; }
        .link-btn {
            display: block; text-align: center; padding: .7rem;
            border: 1.5px solid #ddd; border-radius: 9px; color: #4361ee;
            text-decoration: none; font-size: .9rem; font-weight: 500;
            transition: border-color .2s;
        }
        .link-btn:hover { border-color: #4361ee; }

        .security-note {
            margin-top: 1.5rem; padding: .75rem; background: #f8f9ff;
            border-radius: 8px; border: 1px solid #e0e4ff;
            font-size: .78rem; color: #666; text-align: center;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="icon">🔐</div>
        <h1>SecureVault</h1>
        <p>Masuk untuk mengakses file terenkripsimu</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required autofocus
               autocomplete="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="kamu@email.com">

        <label>Password</label>
        <input type="password" name="password" required
               autocomplete="current-password"
               placeholder="Password kamu">

        <button type="submit" class="btn">Masuk</button>
    </form>

    <div class="divider"><span>atau</span></div>
    <a href="register.php" class="link-btn">Belum punya akun? Daftar</a>

    <div class="security-note">
        🛡️ Password kamu digunakan untuk mendekripsi kunci enkripsi secara lokal.<br>
        Server tidak pernah menyimpan password dalam bentuk aslinya.
    </div>
</div>
</body>
</html>