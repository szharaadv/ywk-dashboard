<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';

$db    = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun      = (int)  ($_POST['tahun']       ?? date('Y'));
    $judul      = trim(   $_POST['judul_materi'] ?? '');
    $peserta    = trim(   $_POST['peserta']      ?? '');
    $departemen = trim(   $_POST['departemen']   ?? '');
    $peringkat  = trim($_POST['peringkat'] ?? 'Peserta');
    $deskripsi  = trim(   $_POST['deskripsi']    ?? '');
    $foto_name  = null;

    // Validasi
    if (!$judul)   $error = 'Judul materi wajib diisi.';
    if (!$peserta) $error = 'Nama peserta wajib diisi.';

    // Upload foto
    if (!$error && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format foto harus JPG atau PNG.';
        } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 5MB.';
        } else {
            $foto_name = 'event_' . date('Ymd') . '_' . rand(1000, 9999) . '.' . $ext;
            $dest = __DIR__ . '/../../assets/img/' . $foto_name;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $error     = 'Gagal menyimpan foto. Cek permission folder assets/img/';
                $foto_name = null;
            }
        }
    }

    if (!$error) {
        $stmt = $db->prepare("
            INSERT INTO ywk_event
                (tahun, judul_materi, peserta, departemen, peringkat, foto, deskripsi)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tahun, $judul, $peserta, $departemen, $peringkat, $foto_name, $deskripsi]);
        header('Location: index.php?created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Add Event — Admin YWK</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; gap: 12px;
        }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }

        .content { padding:1.5rem; max-width:680px; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.5rem; }

        .section-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em;
            margin-bottom:1rem; padding-bottom:6px;
            border-bottom:1px solid #f0f0f0;
        }

        .form-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; display:block; }
        .required   { color:#D0021B; }

        .form-control {
            width:100%; padding:9px 12px; border:1px solid #e5e7eb;
            border-radius:8px; font-size:13px; margin-bottom:14px;
            outline:none; font-family:inherit; background:#fff;
        }
        .form-control:focus { border-color:#D0021B; }
        textarea.form-control { min-height:80px; resize:vertical; }

        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

        .upload-zone {
            border:2px dashed #e5e7eb; border-radius:10px;
            padding:1.5rem; text-align:center; cursor:pointer;
            margin-bottom:14px; transition:border-color 0.15s;
        }
        .upload-zone:hover { border-color:#D0021B; }

        .preview-img {
            max-width:100%; max-height:180px; border-radius:8px;
            margin-top:10px; display:none; object-fit:cover;
        }

        .btn {
            background:#D0021B; color:#fff; border:none;
            border-radius:8px; padding:10px 24px;
            font-size:13px; font-weight:600; cursor:pointer;
        }
        .btn:hover { background:#7B0000; }

        .btn-cancel {
            background:#fff; color:#6b7280;
            border:1px solid #e5e7eb; border-radius:8px;
            padding:10px 20px; font-size:13px;
            text-decoration:none; display:inline-block;
        }
        .btn-cancel:hover { background:#f4f5f7; }

        .alert-danger {
            background:#FDECEA; color:#7B0000;
            padding:10px 14px; border-radius:8px;
            font-size:13px; margin-bottom:1rem;
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="index.php" class="back-btn">← Back</a>
    <div class="topbar-title">Add Event</div>
</div>

<div class="content">
    <?php if ($error): ?>
        <div class="alert-danger" style="margin-bottom:1rem;">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">

            <div class="section-title">Event Info</div>

            <div class="grid-2">
                <div>
                    <label class="form-label">
                        Year <span class="required">*</span>
                    </label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= date('Y') ?>"
                           min="2020" max="2035" required>
                </div>
                <div>
                    <label class="form-label">
                        Rank <span class="required">*</span>
                    </label>
                    <input type="text" name="peringkat" class="form-control"
                           placeholder="e.g: 1st Winner, Participant, Honorary..." required>
                </div>
            </div>

            <label class="form-label">
                Material Title <span class="required">*</span>
            </label>
            <input type="text" name="judul_materi" class="form-control"
                   placeholder="Title of the competition material" required>

            <div class="grid-2">
                <div>
                    <label class="form-label">
                        Participant <span class="required">*</span>
                    </label>
                    <input type="text" name="peserta" class="form-control"
                           placeholder="Participant / team name" required>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" name="departemen" class="form-control"
                           placeholder="Department name">
                </div>
            </div>

            <label class="form-label">Description</label>
            <textarea name="deskripsi" class="form-control"
                      placeholder="Brief description (optional)..."></textarea>

            <div class="section-title">Photo</div>

            <div class="upload-zone"
                 onclick="document.getElementById('fotoInput').click()">
                <div style="font-size:28px; margin-bottom:6px;">📷</div>
                <div style="font-size:13px; font-weight:600; color:#374151;">
                    Click for Upload Photo
                </div>
                <div style="font-size:11px; color:#6b7280; margin-top:4px;">
                    JPG, PNG — max 5MB (optional)
                </div>
                <input type="file" id="fotoInput" name="foto"
                       accept=".jpg,.jpeg,.png" style="display:none"
                       onchange="previewFoto(this)">
                <img id="fotoPreview" class="preview-img">
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                <a href="index.php" class="btn-cancel">Batal</a>
                <button type="submit" class="btn">Simpan Event</button>
            </div>

        </form>
    </div>
</div>

<script>
function previewFoto(input) {
    const preview = document.getElementById('fotoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>