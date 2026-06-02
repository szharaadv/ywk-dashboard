<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/ywk-dashboard/config/db.php';

$db = getDB();

// Handle delete single
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM kaizen_sheet WHERE id = ?")->execute([(int)$_POST['delete_id']]);
    header('Location: index.php?deleted=1');
    exit;
}

// Handle delete all
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    $db->exec("DELETE FROM kaizen_sheet");
    header('Location: index.php?cleared=1');
    exit;
}

$kaizens = $db->query("
    SELECT * FROM kaizen_sheet ORDER BY score DESC, created_at DESC
")->fetchAll();

$total = count($kaizens);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kaizen Sheet — Admin YWK</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display:flex; align-items:center; gap:12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }
        .topbar-actions { display:flex; gap:8px; }

        .btn-white {
            background:#fff; color:#D0021B; border:none;
            border-radius:8px; padding:7px 14px;
            font-size:12px; font-weight:700; cursor:pointer;
            text-decoration:none; display:inline-flex; align-items:center; gap:5px;
        }
        .btn-white:hover { background:#FDECEA; }

        .content { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; }

        /* Upload Card */
        .upload-card {
            background:#fff; border:1px solid #e5e7eb;
            border-radius:12px; padding:1.25rem;
        }

        .upload-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .step {
            background: #f4f5f7;
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .step-num {
            width: 24px; height: 24px;
            background: #D0021B; color: #fff;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            flex-shrink: 0;
        }

        .step-title {
            font-size: 12px; font-weight: 700; color: #1a1a1a;
        }

        .step-desc {
            font-size: 11px; color: #6b7280; line-height: 1.4;
        }

        .btn-download {
            background: #185FA5; color: #fff; border: none;
            border-radius: 8px; padding: 8px 16px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-block;
            margin-top: 4px; width: fit-content;
        }
        .btn-download:hover { background: #0c4480; }

        .upload-form {
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: border-color 0.15s;
            cursor: pointer;
        }
        .upload-form:hover { border-color: #D0021B; }
        .upload-form.dragover { border-color: #D0021B; background: #FDECEA20; }

        .upload-icon { font-size: 28px; margin-bottom: 8px; }
        .upload-title { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .upload-sub { font-size: 11px; color: #6b7280; }

        .mode-toggle {
            display: flex; gap: 8px; justify-content: center;
            margin-top: 12px; flex-wrap: wrap;
        }

        .mode-btn {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px; font-weight: 600;
            cursor: pointer; border: 2px solid #e5e7eb;
            background: #fff; color: #6b7280;
            transition: all 0.15s;
        }

        .mode-btn.active.append  { background: #185FA5; border-color: #185FA5; color: #fff; }
        .mode-btn.active.replace { background: #D0021B; border-color: #D0021B; color: #fff; }

        .btn-upload {
            margin-top: 12px;
            background: #D0021B; color: #fff; border: none;
            border-radius: 8px; padding: 9px 24px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            display: none;
        }
        .btn-upload:hover { background: #7B0000; }

        #file-name {
            font-size: 11px; color: #374151; margin-top: 8px;
            font-weight: 600; display: none;
        }

        /* Alert */
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; }
        .alert-success { background: #EAF3DE; color: #3B6D11; }
        .alert-danger  { background: #FDECEA; color: #7B0000; }
        .alert-info    { background: #E6F1FB; color: #185FA5; }

        /* Table card */
        .table-card {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 12px; overflow: hidden;
        }

        .table-header {
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-title {
            font-size: 11px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.05em;
        }

        .btn-danger {
            background: #fff; color: #D0021B;
            border: 1px solid #D0021B; border-radius: 6px;
            font-size: 11px; padding: 4px 12px; cursor: pointer;
        }
        .btn-danger:hover { background: #FDECEA; }

        .btn-del {
            background: #fff; color: #D0021B;
            border: 1px solid #D0021B; border-radius: 6px;
            font-size: 10px; padding: 2px 8px; cursor: pointer;
        }
        .btn-del:hover { background: #FDECEA; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th {
            padding: 8px 12px; text-align: left;
            font-size: 10px; font-weight: 700; color: #6b7280;
            background: #f4f5f7; text-transform: uppercase; letter-spacing: 0.04em;
        }
        td { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .score-badge {
            font-size: 11px; font-weight: 700;
            padding: 2px 8px; border-radius: 20px;
            background: #EAF3DE; color: #3B6D11;
        }

        .status-badge {
            font-size: 10px; font-weight: 600;
            padding: 2px 8px; border-radius: 20px;
        }
        .status-implemented { background: #EAF3DE; color: #3B6D11; }
        .status-approved    { background: #E6F1FB; color: #185FA5; }
        .status-open        { background: #FAEEDA; color: #854F0B; }

        .empty-state {
            text-align: center; color: #9ca3af;
            padding: 3rem; font-size: 13px;
        }

        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 999;
            align-items: center; justify-content: center;
        }
        .loading-overlay.show { display: flex; }
        .loading-box {
            background: #fff; border-radius: 12px;
            padding: 2rem 2.5rem; text-align: center;
        }
        .loading-spinner {
            width: 36px; height: 36px;
            border: 3px solid #f0f0f0;
            border-top-color: #D0021B;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="../index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Kaizen Sheet Management</div>
    </div>
    <div class="topbar-actions">
        <a href="template.php" class="btn-white">⬇ Download Template</a>
    </div>
</div>

<div class="content">

    <!-- Alert dari hasil upload -->
    <div id="upload-alert" style="display:none;"></div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">✓ Data kaizen berhasil dihapus.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cleared'])): ?>
        <div class="alert alert-success">✓ Semua data kaizen berhasil dihapus.</div>
    <?php endif; ?>

    <!-- Upload Card -->
    <div class="upload-card">
        <div style="font-size:13px; font-weight:700; color:#1a1a1a; margin-bottom:1rem;">
            Upload Data Kaizen via Excel
        </div>

        <!-- Steps -->
        <div class="upload-steps">
            <div class="step">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <div class="step-num">1</div>
                    <div class="step-title">Download Template</div>
                </div>
                <div class="step-desc">
                    Download template Excel yang sudah ada header kolom yang benar.
                    Jangan ubah nama kolom header.
                </div>
                <a href="template.php" class="btn-download">⬇ Download Template Excel</a>
            </div>

            <div class="step">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <div class="step-num">2</div>
                    <div class="step-title">Isi Data di Excel</div>
                </div>
                <div class="step-desc">
                    Isi data kaizen mulai dari baris ke-2. Kolom yang wajib diisi:
                    <strong>Kaizen Theme, Section, Score, Status</strong>.
                </div>
                <div style="margin-top:6px; font-size:10px; color:#6b7280;">
                    Format Status: <strong>Implemented</strong>, <strong>Approved</strong>, atau <strong>Open</strong>
                </div>
            </div>

            <div class="step">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <div class="step-num">3</div>
                    <div class="step-title">Upload File</div>
                </div>
                <div class="step-desc">
                    Upload file Excel (.xlsx, .xls) atau CSV. Pilih mode upload:
                    <strong>Append</strong> untuk menambah atau
                    <strong>Replace</strong> untuk mengganti semua data lama.
                </div>
            </div>
        </div>

        <!-- Mode toggle -->
        <div style="margin-bottom:10px;">
            <div style="font-size:11px; font-weight:700; color:#6b7280;
                        text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">
                Mode Upload:
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="mode-btn active append" data-mode="append"
                        onclick="setMode('append')">
                    ➕ Append — Tambah ke data yang ada
                </button>
                <button type="button" class="mode-btn replace" data-mode="replace"
                        onclick="setMode('replace')">
                    🔄 Replace — Hapus semua & ganti baru
                </button>
            </div>
        </div>

        <!-- Upload zone -->
        <div class="upload-form" id="dropZone"
             onclick="document.getElementById('excelFile').click()">
            <div class="upload-icon">📊</div>
            <div class="upload-title">Klik atau drag & drop file CSV di sini</div>
            <div class="upload-sub">Format: .csv — Simpan Excel as CSV dulu — Maks 10MB</div>
            <input type="file" id="excelFile" accept=".csv"
                   style="display:none" onchange="handleFile(this)">
            <div id="file-name"></div>
        </div>

        <div style="text-align:center; margin-top:10px;">
            <button type="button" class="btn-upload" id="btnUpload"
                    onclick="doUpload()">
                ⬆ Upload & Simpan Data
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                Data Kaizen — <?= $total ?> kaizen
            </div>
            <?php if ($total > 0): ?>
            <form method="POST" onsubmit="return confirm('Hapus SEMUA data kaizen?')">
                <input type="hidden" name="delete_all" value="1">
                <button type="submit" class="btn-danger">🗑 Hapus Semua</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($kaizens)): ?>
            <div class="empty-state">
                📭 Belum ada data kaizen. Upload file Excel di atas untuk mulai.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kaizen Theme</th>
                    <th>Category</th>
                    <th>Section</th>
                    <th>Purposed</th>
                    <th>No. IP</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kaizens as $i => $k): ?>
                <tr>
                    <td style="color:#9ca3af;"><?= $i + 1 ?></td>
                    <td style="font-weight:500; color:#1a1a1a; max-width:300px;">
                        <?= htmlspecialchars($k['judul']) ?>
                    </td>
                    <td><?= htmlspecialchars($k['category'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($k['section']  ?? '—') ?></td>
                    <td><?= htmlspecialchars($k['purposed'] ?? '—') ?></td>
                    <td style="font-size:11px; color:#6b7280;">
                        <?= htmlspecialchars($k['no_ip'] ?? '—') ?>
                    </td>
                    <td>
                        <span class="score-badge"><?= $k['score'] ?? '—' ?></span>
                    </td>
                    <td>
                        <?php
                        $sc  = strtolower($k['status'] ?? '');
                        $cls = str_contains($sc,'implement') ? 'status-implemented'
                             : (str_contains($sc,'approv') ? 'status-approved' : 'status-open');
                        ?>
                        <span class="status-badge <?= $cls ?>">
                            <?= htmlspecialchars($k['status'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus data ini?')">
                            <input type="hidden" name="delete_id" value="<?= $k['id'] ?>">
                            <button type="submit" class="btn-del">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <div class="loading-spinner"></div>
        <div style="font-size:13px; font-weight:600; color:#1a1a1a;">
            Sedang memproses data...
        </div>
        <div style="font-size:11px; color:#6b7280; margin-top:4px;">
            Mohon tunggu sebentar
        </div>
    </div>
</div>

<script>
let uploadMode = 'append';
let selectedFile = null;

function setMode(mode) {
    uploadMode = mode;
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.mode-btn.${mode}`).classList.add('active');
}

function handleFile(input) {
    if (input.files && input.files[0]) {
        selectedFile = input.files[0];
        const nameEl = document.getElementById('file-name');
        nameEl.textContent = '📎 ' + selectedFile.name
            + ' (' + (selectedFile.size / 1024).toFixed(1) + ' KB)';
        nameEl.style.display = 'block';
        document.getElementById('btnUpload').style.display = 'inline-block';
    }
}

// Drag & drop
const zone = document.getElementById('dropZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        selectedFile = file;
        const nameEl = document.getElementById('file-name');
        nameEl.textContent = '📎 ' + file.name
            + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        nameEl.style.display = 'block';
        document.getElementById('btnUpload').style.display = 'inline-block';
    }
});

function doUpload() {
    if (!selectedFile) {
        alert('Pilih file Excel dulu!');
        return;
    }

    if (uploadMode === 'replace') {
        if (!confirm('Mode REPLACE akan menghapus semua data kaizen yang ada dan menggantinya dengan data baru. Lanjutkan?')) {
            return;
        }
    }

    const formData = new FormData();
    formData.append('excel', selectedFile);
    formData.append('mode', uploadMode);

    document.getElementById('loadingOverlay').classList.add('show');

    fetch('upload.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            document.getElementById('loadingOverlay').classList.remove('show');

            const alertEl = document.getElementById('upload-alert');
            if (res.success) {
                alertEl.className = 'alert alert-success';
                alertEl.innerHTML = `✓ ${res.message}`;
                if (res.errors && res.errors.length > 0) {
                    alertEl.innerHTML += `<br><small style="color:#854F0B;">
                        ⚠ ${res.errors.length} baris gagal: ${res.errors.join(', ')}
                    </small>`;
                }
                alertEl.style.display = 'block';
                // Reload halaman setelah 1.5 detik
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alertEl.className = 'alert alert-danger';
                alertEl.innerHTML = `✗ ${res.message}`;
                alertEl.style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            const alertEl = document.getElementById('upload-alert');
            alertEl.className = 'alert alert-danger';
            alertEl.innerHTML = '✗ Terjadi kesalahan saat upload. Coba lagi.';
            alertEl.style.display = 'block';
        });
}
</script>
</body>
</html>