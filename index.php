<?php
require_once 'config/db.php';
require_once 'includes/session.php';
requireLogin();
$user = getCurrentUser();

// Cek admin di PHP (bukan di tengah HTML)
$stmtAdmin = getDB()->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmtAdmin->execute([$_SESSION['user_id']]);
$isAdmin = (bool)$stmtAdmin->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f0f2f5; color: #333; }

        nav { background: #1a1a2e; color: #fff; padding: 1rem 2rem;
              display: flex; justify-content: space-between; align-items: center; }
        nav h1 { font-size: 1.2rem; letter-spacing: 1px; }
        nav span { font-size: .9rem; opacity: .8; }
        nav a { color: #a0c4ff; text-decoration: none; margin-left: 1.5rem; font-size: .9rem; }
        nav a:hover { color: #fff; }

        .container { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }

        .card { background: #fff; border-radius: 12px; padding: 1.5rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1rem; margin-bottom: 1rem; color: #1a1a2e; }

        .dropzone { border: 2px dashed #4361ee; border-radius: 10px; padding: 2rem;
                    text-align: center; cursor: pointer; transition: background .2s; }
        .dropzone:hover, .dropzone.drag-over { background: #eef0ff; }
        .dropzone p { color: #666; margin-bottom: .75rem; }
        .dropzone input[type=file] { display: none; }

        .btn { display: inline-block; padding: .4rem .85rem; border-radius: 7px;
               border: none; cursor: pointer; font-size: .8rem;
               font-weight: 500; transition: background .2s; }
        .btn-primary  { background: #4361ee; color: #fff; font-size: .95rem;
                        padding: .6rem 1.4rem; }
        .btn-primary:hover  { background: #3451d1; }
        .btn-success  { background: #2a9d8f; color: #fff; }
        .btn-success:hover  { background: #21867a; }
        .btn-danger   { background: #e63946; color: #fff; }
        .btn-danger:hover   { background: #c1121f; }
        .btn-verify   { background: #6c757d; color: #fff; }
        .btn-verify:hover   { background: #545b62; }
        .btn-share    { background: #7209b7; color: #fff; }
        .btn-share:hover    { background: #560bad; }
        .btn-revoke   { background: #fd7e14; color: #fff; }
        .btn-revoke:hover   { background: #e8690a; }

        .btn-group { display: flex; gap: .35rem; flex-wrap: wrap; }

        #progress-wrap { display: none; margin-top: 1rem; }
        #progress-bar  { height: 8px; background: #4361ee; border-radius: 4px;
                         width: 0%; transition: width .3s; }
        #progress-bg   { background: #e0e0e0; border-radius: 4px; overflow: hidden; }
        #progress-text { font-size: .85rem; color: #555; margin-top: .4rem; }

        .alert { padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
                 font-size: .9rem; display: none; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th { text-align: left; padding: .65rem .75rem; border-bottom: 2px solid #eee;
             color: #555; font-weight: 600; }
        td { padding: .65rem .75rem; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 20px;
                 font-size: .75rem; font-weight: 600; }
        .badge-own    { background: #d4edda; color: #155724; }
        .badge-shared { background: #cce5ff; color: #004085; }

        .file-name { font-weight: 500; max-width: 200px;
                     overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hash-preview { font-family: monospace; font-size: .75rem; color: #888;
                        cursor: help; }
        .empty-state { text-align: center; padding: 2rem; color: #aaa; }
        #loading-files { text-align: center; padding: 1.5rem; color: #888; font-size: .9rem; }

        /* Tag penerima dengan tombol revoke */
        .shared-tag {
            display: inline-flex; align-items: center; gap: .3rem;
            background: #f3e8ff; border: 1px solid #d8b4fe;
            border-radius: 20px; padding: .15rem .5rem .15rem .55rem;
            margin: .1rem; font-size: .78rem; color: #6b21a8;
        }
        .shared-tag .revoke-btn {
            background: none; border: none; cursor: pointer;
            color: #e63946; font-size: .85rem; line-height: 1;
            padding: 0; font-weight: 700;
        }
        .shared-tag .revoke-btn:hover { color: #c1121f; }

        .modal-overlay { display: none; position: fixed; inset: 0;
                         background: rgba(0,0,0,0.5); z-index: 100;
                         justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 12px; padding: 1.75rem;
                 width: 100%; max-width: 420px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .modal h3 { margin-bottom: 1rem; color: #1a1a2e; font-size: 1rem; }
        .modal label { display: block; font-size: .85rem; color: #555; margin-bottom: .4rem; }
        .modal input { width: 100%; padding: .6rem .85rem; border: 1px solid #ccc;
                       border-radius: 8px; font-size: .95rem; margin-bottom: 1rem; }
        .modal input:focus { outline: none; border-color: #7209b7; }
        .modal-actions { display: flex; gap: .75rem; justify-content: flex-end; }
        .modal-actions .btn { font-size: .9rem; padding: .55rem 1.2rem; }
        #share-result { font-size: .85rem; margin-bottom: .75rem;
                        padding: .5rem .75rem; border-radius: 6px; display: none; }
        #share-result.ok  { background: #d4edda; color: #155724; }
        #share-result.err { background: #f8d7da; color: #721c24; }

        #preview-body img {
            max-width: 100%; max-height: 60vh;
            border-radius: 8px; object-fit: contain;
        }
        #preview-body iframe {
            width: 100%; height: 60vh;
            border: none; border-radius: 8px;
        }
        #preview-body pre {
            text-align: left; background: #f8f9fa;
            padding: 1rem; border-radius: 8px; font-size: .8rem;
            max-height: 60vh; overflow: auto;
            white-space: pre-wrap; word-break: break-all; width: 100%;
        }
    </style>
</head>
<body>

<nav>
    <h1>🔐 SecureVault</h1>
    <div>
        <span>Halo, <strong><?= htmlspecialchars($user['username']) ?></strong></span>
        <?php if ($isAdmin): ?>
            <a href="/securevault/admin/index.php" style="color:#ffd166">⚙ Admin</a>
        <?php endif; ?>
        <a href="auth/logout.php">Logout</a>
    </div>
</nav>

<!-- Modal Share -->
<div class="modal-overlay" id="modal-share">
    <div class="modal">
        <h3>📤 Bagikan File</h3>
        <p style="font-size:.85rem;color:#666;margin-bottom:1rem">
            File: <strong id="share-file-name"></strong>
        </p>
        <div id="share-result"></div>
        <label>Username atau Email penerima</label>
        <input type="text" id="share-target" placeholder="contoh: tamkha atau user@email.com">
        <div class="modal-actions">
            <button class="btn btn-verify" onclick="closeShareModal()">Batal</button>
            <button class="btn btn-share" onclick="submitShare()">Bagikan</button>
        </div>
    </div>
</div>

<!-- Modal Preview -->
<div class="modal-overlay" id="modal-preview">
    <div class="modal" style="max-width:700px;width:95%">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 id="preview-title" style="font-size:.95rem;color:#1a1a2e;margin:0">Preview File</h3>
            <button onclick="closePreview()"
                style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#888">✕</button>
        </div>
        <div id="preview-body" style="text-align:center;min-height:200px;
             display:flex;align-items:center;justify-content:center">
            <span style="color:#aaa">Memuat preview...</span>
        </div>
        <div style="margin-top:1rem;text-align:right">
            <button class="btn btn-verify" onclick="closePreview()">Tutup</button>
        </div>
    </div>
</div>

<div class="container">

    <div id="alert" class="alert"></div>

    <!-- Upload Card -->
    <div class="card">
        <h2>Upload File Terenkripsi</h2>
        <div class="dropzone" id="dropzone"
             onclick="document.getElementById('fileInput').click()">
            <p>Seret file ke sini atau klik untuk memilih</p>
            <p style="font-size:.8rem;color:#999">Maks. 20 MB per file</p>
            <button class="btn btn-primary" type="button">Pilih File</button>
            <input type="file" id="fileInput" multiple>
        </div>
        <div id="progress-wrap">
            <div id="progress-bg"><div id="progress-bar"></div></div>
            <div id="progress-text">Mengenkripsi &amp; mengupload...</div>
        </div>
    </div>

    <!-- Daftar File -->
    <div class="card">
        <h2>File Saya</h2>
        <div id="loading-files">Memuat daftar file...</div>
        <div id="file-list" style="display:none">
            <table>
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Tanggal</th>
                        <th>SHA-256</th>
                        <th>Akses</th>
                        <th>Dibagikan ke</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="file-tbody"></tbody>
            </table>
            <div id="empty-state" class="empty-state" style="display:none">
                Belum ada file. Upload file pertamamu!
            </div>
        </div>
    </div>
</div>

<script>
let shareFileId   = null;
let shareFileName = '';

const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');

dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('drag-over');
});
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    uploadFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => uploadFiles(fileInput.files));

function uploadFiles(files) {
    if (!files || files.length === 0) return;
    Array.from(files).forEach(uploadSingleFile);
}

function uploadSingleFile(file) {
    const formData    = new FormData();
    formData.append('file', file);

    const progressWrap = document.getElementById('progress-wrap');
    const progressBar  = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');

    progressWrap.style.display = 'block';
    progressBar.style.width    = '10%';
    progressText.textContent   = `Mengenkripsi "${file.name}"...`;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'file/upload.php');

    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            progressBar.style.width = (Math.round((e.loaded / e.total) * 80) + 10) + '%';
        }
    };

    xhr.onload = () => {
        progressBar.style.width = '100%';
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                showAlert(res.message, 'success');
                loadFiles();
            } else {
                showAlert('Gagal: ' + res.error, 'error');
            }
        } catch {
            showAlert('Respons server tidak valid.', 'error');
        }
        setTimeout(() => {
            progressWrap.style.display = 'none';
            progressBar.style.width    = '0%';
        }, 1500);
        fileInput.value = '';
    };

    xhr.onerror = () => {
        showAlert('Koneksi gagal. Coba lagi.', 'error');
        progressWrap.style.display = 'none';
    };

    xhr.send(formData);
}

function loadFiles() {
    fetch('file/list.php')
        .then(r => r.json())
        .then(data => {
            document.getElementById('loading-files').style.display = 'none';
            document.getElementById('file-list').style.display     = 'block';

            const tbody = document.getElementById('file-tbody');
            tbody.innerHTML = '';

            if (!data.files || data.files.length === 0) {
                document.getElementById('empty-state').style.display = 'block';
                return;
            }
            document.getElementById('empty-state').style.display = 'none';

            data.files.forEach(f => {
                const date       = new Date(f.uploaded_at).toLocaleDateString('id-ID',
                                   { day:'2-digit', month:'short', year:'numeric' });
                const badgeClass = f.access_type === 'own' ? 'badge-own' : 'badge-shared';
                const badgeLabel = f.access_type === 'own' ? 'Milik saya' : 'Dibagikan';
                const hashPrev   = f.file_hash ? f.file_hash.substring(0, 10) + '…' : '-';
                const isOwn      = f.access_type === 'own';

                // ── Kolom "Dibagikan ke" ─────────────────────────────
                // Jika pemilik: tampilkan tag per-user dengan tombol ✕ revoke
                // Jika penerima: tampilkan nama pemilik saja
                let sharedWith = '—';
                if (isOwn && f.shared_with) {
                    const users = f.shared_with.split(', ');
                    sharedWith = users.map(u =>
                        `<span class="shared-tag">
                            👤 ${escHtml(u)}
                            <button class="revoke-btn"
                                title="Cabut akses ${escHtml(u)}"
                                onclick="revokeAccess(${f.id}, '${escHtml(u)}')">✕</button>
                        </span>`
                    ).join('');
                } else if (!isOwn && f.shared_with) {
                    sharedWith = `<span style="color:#1a6ea8;font-size:.8rem">dari: ${escHtml(f.shared_with)}</span>`;
                }

                tbody.innerHTML += `
                <tr>
                    <td><div class="file-name" title="${escHtml(f.original_name)}">${escHtml(f.original_name)}</div></td>
                    <td>${escHtml(f.size_display)}</td>
                    <td>${date}</td>
                    <td><span class="hash-preview" title="${escHtml(f.file_hash)}">${hashPrev}</span></td>
                    <td><span class="badge ${badgeClass}">${badgeLabel}</span></td>
                    <td>${sharedWith}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-success"
                                onclick="downloadFile(${f.id})">⬇ Unduh</button>
                            <button class="btn btn-verify"
                                onclick="verifyFile(${f.id}, '${escHtml(f.original_name)}')">✓ Verifikasi</button>
                            <button class="btn" style="background:#0f3460;color:#fff"
                                onclick="previewFile(${f.id}, '${escHtml(f.original_name)}')">👁 Preview</button>
                            ${isOwn ? `
                            <button class="btn btn-share"
                                onclick="openShareModal(${f.id}, '${escHtml(f.original_name)}')">⇗ Bagikan</button>
                            <button class="btn btn-danger"
                                onclick="deleteFile(${f.id}, '${escHtml(f.original_name)}')">✕ Hapus</button>
                            ` : ''}
                        </div>
                    </td>
                </tr>`;
            });
        })
        .catch(() => {
            document.getElementById('loading-files').textContent = 'Gagal memuat daftar file.';
        });
}

// ── Revoke akses ─────────────────────────────────────────────
function revokeAccess(fileId, username) {
    if (!confirm(`Cabut akses "${username}" dari file ini?`)) return;
    fetch('share/revoke.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: fileId, username: username })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert(res.message, 'success');
            loadFiles();
        } else {
            showAlert('Gagal: ' + res.error, 'error');
        }
    })
    .catch(() => showAlert('Koneksi gagal saat revoke.', 'error'));
}

function downloadFile(fileId) {
    window.location.href = `file/download.php?id=${fileId}`;
}

function verifyFile(fileId, fileName) {
    showAlert(`Memverifikasi "${fileName}"...`, 'success');
    fetch(`file/verify.php?id=${fileId}`)
        .then(r => r.json())
        .then(res => {
            if (res.error) { showAlert('Error: ' + res.error, 'error'); return; }
            if (res.is_valid) {
                showAlert(`✅ "${fileName}" — Integritas OK. SHA-256 cocok.`, 'success');
            } else {
                showAlert(`⚠️ PERINGATAN! "${fileName}" — Hash tidak cocok! File mungkin dimodifikasi.`, 'error');
            }
        })
        .catch(() => showAlert('Koneksi gagal saat verifikasi.', 'error'));
}

function deleteFile(fileId, fileName) {
    if (!confirm(`Hapus "${fileName}"? Tindakan ini tidak bisa dibatalkan.`)) return;
    fetch('file/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: fileId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showAlert('File berhasil dihapus.', 'success'); loadFiles(); }
        else showAlert('Gagal menghapus: ' + res.error, 'error');
    });
}

function openShareModal(fileId, fileName) {
    shareFileId   = fileId;
    shareFileName = fileName;
    document.getElementById('share-file-name').textContent = fileName;
    document.getElementById('share-target').value          = '';
    document.getElementById('share-result').style.display  = 'none';
    document.getElementById('modal-share').classList.add('active');
    setTimeout(() => document.getElementById('share-target').focus(), 100);
}

function closeShareModal() {
    document.getElementById('modal-share').classList.remove('active');
    shareFileId = null;
}

document.getElementById('modal-share').addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});

function submitShare() {
    const target   = document.getElementById('share-target').value.trim();
    const resultEl = document.getElementById('share-result');

    if (!target) {
        resultEl.textContent   = 'Masukkan username atau email penerima.';
        resultEl.className     = 'err';
        resultEl.style.display = 'block';
        return;
    }

    resultEl.textContent   = 'Memproses...';
    resultEl.className     = '';
    resultEl.style.display = 'block';

    fetch('share/share.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: shareFileId, target: target })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            resultEl.textContent   = `✅ File berhasil dibagikan ke "${res.shared_to}".`;
            resultEl.className     = 'ok';
            resultEl.style.display = 'block';
            setTimeout(() => { closeShareModal(); loadFiles(); }, 2000);
        } else {
            resultEl.textContent = '❌ ' + res.error;
            resultEl.className   = 'err';
        }
    })
    .catch(() => {
        resultEl.textContent = 'Koneksi gagal.';
        resultEl.className   = 'err';
    });
}

function showAlert(msg, type) {
    const el = document.getElementById('alert');
    el.textContent   = msg;
    el.className     = `alert alert-${type}`;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 5000);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Preview File ─────────────────────────────────────────────
function previewFile(fileId, fileName) {
    document.getElementById('preview-title').textContent = fileName;
    document.getElementById('preview-body').innerHTML =
        '<span style="color:#aaa">Memuat preview...</span>';
    document.getElementById('modal-preview').classList.add('active');

    fetch(`file/preview.php?id=${fileId}`)
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                document.getElementById('preview-body').innerHTML =
                    `<div style="color:#e63946;padding:1rem">
                        ❌ ${escHtml(res.error)}<br>
                        <small style="color:#888">${res.mime_type ? 'Tipe: ' + res.mime_type : ''}</small>
                     </div>`;
                return;
            }

            const mime = res.mime_type;
            let content = '';

            if (mime.startsWith('image/')) {
                content = `<img src="${res.data_url}" alt="${escHtml(fileName)}">`;
            } else if (mime === 'application/pdf') {
                content = `<iframe src="${res.data_url}" title="${escHtml(fileName)}"></iframe>`;
            } else if (mime.startsWith('text/')) {
                const base64 = res.data_url.split(',')[1];
                const text   = decodeURIComponent(escape(atob(base64)));
                content = `<pre>${escHtml(text)}</pre>`;
            }

            document.getElementById('preview-body').innerHTML = content;
        })
        .catch(() => {
            document.getElementById('preview-body').innerHTML =
                '<div style="color:#e63946">Koneksi gagal saat memuat preview.</div>';
        });
}

function closePreview() {
    document.getElementById('modal-preview').classList.remove('active');
    setTimeout(() => {
        document.getElementById('preview-body').innerHTML =
            '<span style="color:#aaa">Memuat preview...</span>';
    }, 300);
}

document.getElementById('modal-preview').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});

loadFiles();
</script>
</body>
</html>