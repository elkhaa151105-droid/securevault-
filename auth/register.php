<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username atau email sudah digunakan.';
            } else {
                $keys         = generateKeyPair($password);
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare('
                    INSERT INTO users (username, email, password_hash, public_key, private_key_enc)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $username, $email, $passwordHash,
                    $keys['public_key'], $keys['private_key_enc'],
                ]);
                $success = 'Akun berhasil dibuat! Silakan login.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SecureVault</title>
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
            width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 1.75rem; }
        .logo .icon { font-size: 2.5rem; }
        .logo h1 { font-size: 1.5rem; color: #1a1a2e; margin-top: .4rem; }
        .logo p  { font-size: .85rem; color: #888; margin-top: .25rem; }

        label { display: block; font-size: .85rem; font-weight: 600;
                color: #444; margin-bottom: .35rem; }
        .input-wrap { position: relative; margin-bottom: 1rem; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: .7rem 1rem; border: 1.5px solid #ddd;
            border-radius: 9px; font-size: .95rem; transition: border .2s;
            background: #fafafa;
        }
        input:focus { outline: none; border-color: #4361ee; background: #fff; }

        .strength-bar { height: 4px; border-radius: 2px; margin-top: .3rem;
                        background: #eee; overflow: hidden; }
        .strength-fill { height: 100%; width: 0%; border-radius: 2px;
                         transition: width .3s, background .3s; }

        .btn {
            width: 100%; padding: .8rem; background: #4361ee; color: #fff;
            border: none; border-radius: 9px; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: background .2s;
            margin-top: .5rem;
        }
        .btn:hover { background: #3451d1; }
        .btn:disabled { background: #a0aec0; cursor: not-allowed; }

        .alert { padding: .75rem 1rem; border-radius: 9px; margin-bottom: 1.25rem;
                 font-size: .88rem; }
        .alert-error   { background: #fff0f0; color: #c00; border: 1px solid #fcc; }
        .alert-success { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }

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

        .hint { font-size: .78rem; color: #aaa; margin-top: -.7rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="icon">🔐</div>
        <h1>SecureVault</h1>
        <p>Buat akun baru — data kamu aman secara kriptografi</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?>
            <br><a href="login.php" style="color:#276749;font-weight:600">→ Login sekarang</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" id="reg-form">
        <label>Username</label>
        <div class="input-wrap">
            <input type="text" name="username" required autocomplete="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="contoh: tamkha">
        </div>

        <label>Email</label>
        <div class="input-wrap">
            <input type="email" name="email" required autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="contoh: kamu@email.com">
        </div>

        <label>Password</label>
        <div class="input-wrap">
            <input type="password" name="password" id="pw" required
                   autocomplete="new-password" placeholder="Min. 8 karakter"
                   oninput="checkStrength(this.value)">
            <div class="strength-bar"><div class="strength-fill" id="sf"></div></div>
        </div>
        <p class="hint" id="strength-label">Masukkan password</p>

        <label>Konfirmasi Password</label>
        <div class="input-wrap">
            <input type="password" name="confirm_password" required
                   autocomplete="new-password" placeholder="Ulangi password">
        </div>

        <button type="submit" class="btn">Buat Akun</button>
    </form>

    <div class="divider"><span>atau</span></div>
    <a href="login.php" class="link-btn">Sudah punya akun? Login</a>
    <?php endif; ?>
</div>
<script>
function checkStrength(pw) {
    const sf    = document.getElementById('sf');
    const label = document.getElementById('strength-label');
    let score   = 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const map = [
        { w:'0%',   bg:'#eee',    t:'Masukkan password' },
        { w:'25%',  bg:'#e63946', t:'Lemah' },
        { w:'50%',  bg:'#f4a261', t:'Cukup' },
        { w:'75%',  bg:'#2a9d8f', t:'Kuat' },
        { w:'100%', bg:'#4361ee', t:'Sangat kuat ✓' },
    ];
    const lvl = Math.min(score, 4);
    sf.style.width      = map[lvl].w;
    sf.style.background = map[lvl].bg;
    label.textContent   = map[lvl].t;
}
</script>
</body>
</html>