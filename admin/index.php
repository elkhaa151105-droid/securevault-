<?php
require_once '../config/db.php';
require_once '../includes/crypto.php';
require_once '../includes/session.php';

requireAdmin();

$db = getDB();

// ── Statistik utama ───────────────────────────────────────────
$stats = $db->query('
    SELECT
        (SELECT COUNT(*) FROM users)                        AS total_users,
        (SELECT COUNT(*) FROM users WHERE is_admin = 1)    AS total_admins,
        (SELECT COUNT(*) FROM files)                        AS total_files,
        (SELECT COALESCE(SUM(file_size), 0) FROM files)    AS total_size,
        (SELECT COUNT(*) FROM file_shares)                  AS total_shares,
        (SELECT COUNT(*) FROM audit_logs)                   AS total_logs
')->fetch();

// Format ukuran
function formatSize(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes/1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes/1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes/1024, 1)       . ' KB';
    return $bytes . ' B';
}

// ── Data users ────────────────────────────────────────────────
$users = $db->query('
    SELECT
        u.id, u.username, u.email, u.is_admin, u.created_at,
        COUNT(DISTINCT f.id)  AS file_count,
        COALESCE(SUM(f.file_size), 0) AS storage_used
    FROM users u
    LEFT JOIN files f ON f.owner_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
')->fetchAll();

// ── Audit log terbaru ─────────────────────────────────────────
$logs = $db->query('
    SELECT
        al.id, al.action, al.file_name, al.ip_address, al.created_at,
        u.username
    FROM audit_logs al
    JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 50
')->fetchAll();

// ── Top uploader ──────────────────────────────────────────────
$topUploaders = $db->query('
    SELECT u.username, COUNT(f.id) AS file_count,
           COALESCE(SUM(f.file_size), 0) AS total_size
    FROM users u
    LEFT JOIN files f ON f.owner_id = u.id
    GROUP BY u.id
    ORDER BY file_count DESC
    LIMIT 5
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — SecureVault</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; }

        nav { background: #1a1a2e; color: #fff; padding: 1rem 2rem;
              display: flex; justify-content: space-between; align-items: center; }
        nav h1 { font-size: 1.1rem; letter-spacing: 1px; }
        nav .nav-right { display: flex; align-items: center; gap: 1.5rem; font-size: .85rem; }
        nav a { color: #a0c4ff; text-decoration: none; }
        nav a:hover { color: #fff; }
        .admin-badge { background: #e63946; color: #fff; padding: .2rem .6rem;
                       border-radius: 20px; font-size: .75rem; font-weight: 600; }

        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }

        /* Tab navigasi */
        .tabs { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .tab-btn { padding: .55rem 1.2rem; border-radius: 8px; border: 1.5px solid #ddd;
                   background: #fff; cursor: pointer; font-size: .9rem; font-weight: 500;
                   color: #555; transition: all .2s; }
        .tab-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
        .tab-btn:hover:not(.active) { border-color: #aaa; }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Card */
        .card { background: #fff; border-radius: 12px; padding: 1.5rem;
                box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: 1.5rem; }
        .card h2 { font-size: .95rem; color: #1a1a2e; margin-bottom: 1.25rem;
                   padding-bottom: .75rem; border-bottom: 1px solid #f0f0f0; }

        /* Stat cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                      gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border-radius: 12px; padding: 1.25rem;
                     box-shadow: 0 2px 8px rgba(0,0,0,.07); text-align: center; }
        .stat-card .value { font-size: 1.8rem; font-weight: 600; color: #1a1a2e; }
        .stat-card .label { font-size: .8rem; color: #888; margin-top: .3rem; }
        .stat-card.accent { background: #1a1a2e; }
        .stat-card.accent .value { color: #a0c4ff; }
        .stat-card.accent .label { color: #8899aa; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th { text-align: left; padding: .6rem .75rem; border-bottom: 2px solid #eee;
             color: #666; font-weight: 600; font-size: .8rem; text-transform: uppercase;
             letter-spacing: .04em; }
        td { padding: .6rem .75rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* Badge */
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 20px;
                 font-size: .75rem; font-weight: 600; }
        .badge-admin  { background: #fff0f3; color: #c9184a; }
        .badge-user   { background: #e8f4fd; color: #1a6ea8; }
        .badge-upload   { background: #d4edda; color: #155724; }
        .badge-download { background: #cce5ff; color: #004085; }
        .badge-delete   { background: #f8d7da; color: #721c24; }
        .badge-share    { background: #e8d5f5; color: #6f2da8; }
        .badge-login    { background: #fff3cd; color: #856404; }

        /* Button */
        .btn-sm { padding: .3rem .75rem; border-radius: 6px; border: none; cursor: pointer;
                  font-size: .8rem; font-weight: 500; transition: background .2s; }
        .btn-danger  { background: #e63946; color: #fff; }
        .btn-danger:hover  { background: #c1121f; }
        .btn-primary { background: #4361ee; color: #fff; }
        .btn-primary:hover { background: #3451d1; }

        /* Alert */
        #alert { padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
                 font-size: .9rem; display: none; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Search */
        .search-wrap { margin-bottom: 1rem; }
        .search-wrap input { padding: .55rem 1rem; border: 1.5px solid #ddd;
                             border-radius: 8px; font-size: .9rem; width: 280px; }
        .search-wrap input:focus { outline: none; border-color: #4361ee; }

        /* Progress bar storage */
        .prog-wrap { background: #eee; border-radius: 4px; height: 6px;
                     overflow: hidden; min-width: 80px; }
        .prog-fill { height: 100%; background: #4361ee; border-radius: 4px; }

        .empty { text-align: center; padding: 2rem; color: #aaa; font-size: .9rem; }
        .text-muted { color: #888; font-size: .8rem; }
        .mono { font-family: monospace; font-size: .8rem; }
    </style>
</head>
<body>

<nav>
    <h1>🔐 SecureVault <span class="admin-badge">ADMIN</span></h1>
    <div class="nav-right">
        <span>Login sebagai <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        <a href="/securevault/index.php">Dashboard User</a>
        <a href="/securevault/auth/logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div id="alert"></div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card accent">
            <div class="value"><?= number_format($stats['total_users']) ?></div>
            <div class="label">Total User</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= number_format($stats['total_files']) ?></div>
            <div class="label">Total File</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= formatSize((int)$stats['total_size']) ?></div>
            <div class="label">Total Penyimpanan</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= number_format($stats['total_shares']) ?></div>
            <div class="label">File Dibagikan</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= number_format($stats['total_logs']) ?></div>
            <div class="label">Total Aktivitas</div>
        </div>
    </div>

    <!-- Tab Navigasi -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('users', this)">
            👥 Manajemen User
        </button>
        <button class="tab-btn" onclick="switchTab('logs', this)">
            📋 Audit Log
        </button>
        <button class="tab-btn" onclick="switchTab('storage', this)">
            💾 Statistik Storage
        </button>
    </div>

    <!-- TAB: Manajemen User -->
    <div class="tab-panel active" id="tab-users">
        <div class="card">
            <h2>Daftar User</h2>
            <div class="search-wrap">
                <input type="text" id="search-user" placeholder="Cari username atau email..."
                       oninput="filterTable('user-tbody', this.value, [0,1])">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>File</th>
                        <th>Storage</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="user-tbody">
                <?php foreach ($users as $u):
                    $maxStorage = 100 * 1024 * 1024;
                    $pct = min(100, round(($u['storage_used'] / $maxStorage) * 100));
                ?>
                <tr data-id="<?= $u['id'] ?>">
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['is_admin'] ? 'badge-admin' : 'badge-user' ?>">
                            <?= $u['is_admin'] ? 'Admin' : 'User' ?>
                        </span>
                    </td>
                    <td><?= $u['file_count'] ?> file</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <div class="prog-wrap">
                                <div class="prog-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-muted"><?= formatSize((int)$u['storage_used']) ?></span>
                        </div>
                    </td>
                    <td class="text-muted">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td>
                        <?php if (!$u['is_admin']): ?>
                        <button class="btn-sm btn-danger"
                            onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                            Hapus
                        </button>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($users)): ?>
            <div class="empty">Belum ada user terdaftar.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: Audit Log -->
    <div class="tab-panel" id="tab-logs">
        <div class="card">
            <h2>Audit Log — 50 Aktivitas Terbaru</h2>
            <div class="search-wrap">
                <input type="text" id="search-log" placeholder="Cari username atau aksi..."
                       oninput="filterTable('log-tbody', this.value, [0,1])">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>File</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="log-tbody">
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="mono"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= htmlspecialchars($log['action']) ?>">
                            <?= strtoupper(htmlspecialchars($log['action'])) ?>
                        </span>
                    </td>
                    <td class="text-muted">
                        <?= $log['file_name'] ? htmlspecialchars($log['file_name']) : '—' ?>
                    </td>
                    <td class="mono text-muted"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($logs)): ?>
            <div class="empty">Belum ada aktivitas tercatat.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: Statistik Storage -->
    <div class="tab-panel" id="tab-storage">
        <div class="card">
            <h2>Top Pengguna Storage</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Username</th>
                        <th>Jumlah File</th>
                        <th>Storage Digunakan</th>
                        <th>Proporsi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $totalStorage = (int)$stats['total_size'];
                foreach ($topUploaders as $i => $u):
                    $pct = $totalStorage > 0
                        ? round(($u['total_size'] / $totalStorage) * 100)
                        : 0;
                ?>
                <tr>
                    <td><strong>#<?= $i + 1 ?></strong></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= $u['file_count'] ?> file</td>
                    <td><?= formatSize((int)$u['total_size']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="prog-wrap" style="width:120px">
                                <div class="prog-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-muted"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($topUploaders)): ?>
            <div class="empty">Belum ada data storage.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Ringkasan Sistem</h2>
            <table>
                <thead>
                    <tr><th>Metrik</th><th>Nilai</th></tr>
                </thead>
                <tbody>
                    <tr><td>Total user terdaftar</td><td><strong><?= $stats['total_users'] ?></strong></td></tr>
                    <tr><td>Total file terenkripsi</td><td><strong><?= $stats['total_files'] ?></strong></td></tr>
                    <tr><td>Total penyimpanan digunakan</td><td><strong><?= formatSize((int)$stats['total_size']) ?></strong></td></tr>
                    <tr><td>Rata-rata file per user</td>
                        <td><strong><?= $stats['total_users'] > 0 ? round($stats['total_files'] / $stats['total_users'], 1) : 0 ?></strong></td></tr>
                    <tr><td>Total sharing dilakukan</td><td><strong><?= $stats['total_shares'] ?></strong></td></tr>
                    <tr><td>Total aktivitas tercatat</td><td><strong><?= $stats['total_logs'] ?></strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// ── Tab switching ─────────────────────────────────────────────
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

// ── Filter tabel ──────────────────────────────────────────────
function filterTable(tbodyId, query, cols) {
    const q = query.toLowerCase();
    document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
        const text = cols.map(c => row.cells[c]?.textContent.toLowerCase() || '').join(' ');
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

// ── Hapus user ────────────────────────────────────────────────
function deleteUser(userId, username) {
    if (!confirm(`Hapus user "${username}"?\n\nSemua file milik user ini juga akan dihapus. Tindakan ini tidak bisa dibatalkan.`)) return;

    fetch('/securevault/admin/delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert('User "' + username + '" berhasil dihapus.', 'success');
            document.querySelector('#user-tbody tr[data-id="' + userId + '"]').remove();
        } else {
            showAlert('Gagal: ' + res.error, 'error');
        }
    })
    .catch(() => showAlert('Koneksi gagal.', 'error'));
}

// ── Alert ─────────────────────────────────────────────────────
function showAlert(msg, type) {
    const el = document.getElementById('alert');
    el.textContent   = msg;
    el.className     = 'alert-' + type;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 4000);
}
</script>
</body>
</html>