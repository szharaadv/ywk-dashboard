<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';

$db    = getDB();
$error = '';
$id    = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM ywk_event WHERE id = ?");
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun       = (int)  ($_POST['tahun']       ?? date('Y'));
    $jenis_event = trim(  $_POST['jenis_event']  ?? 'YWKS');
    $judul       = trim(  $_POST['judul_materi'] ?? '');
    $peserta     = trim(  $_POST['peserta']      ?? '');
    $departemen  = trim(  $_POST['departemen']   ?? '');
    $peringkat   = trim(  $_POST['peringkat']    ?? '');
    $deskripsi   = trim(  $_POST['deskripsi']    ?? '');
    $foto_name   = $ev['foto']; // default foto lama

    if (!$judul)   $error = 'Judul materi wajib diisi.';
    if (!$peserta) $error = 'Nama peserta wajib diisi.';

    // Upload foto baru kalau ada
    if (!$error && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format foto harus JPG atau PNG.';
        } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 5MB.';
        } else {
            $new_foto = 'event_' . date('Ymd') . '_' . rand(1000,9999) . '.' . $ext;
            $dest     = __DIR__ . '/../../assets/img/' . $new_foto;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                // Hapus foto lama
                if ($ev['foto']) {
                    $old = __DIR__ . '/../../assets/img/' . $ev['foto'];
                    if (file_exists($old)) unlink($old);
                }
                $foto_name = $new_foto;
            } else {
                $error = 'Gagal menyimpan foto.';
            }
        }
    }

    if (!$error) {
        $stmt = $db->prepare("
            UPDATE ywk_event
            SET tahun=?, jenis_event=?, judul_materi=?, peserta=?,
                departemen=?, peringkat=?, foto=?, deskripsi=?
            WHERE id=?
        ");
        $stmt->execute([$tahun, $jenis_event, $judul, $peserta,
                        $departemen, $peringkat, $foto_name, $deskripsi, $id]);
        header('Location: index.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Event — Admin YWK</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .topbar {
            background:linear-gradient(135deg,#7B0000,#D0021B);
            padding:12px 1.5rem;
            display:flex; align-items:center; gap:12px;
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
        .required { color:#D0021B; }
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
            margin-top:10px; object-fit:cover;
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
    <div class="topbar-title">Edit Event</div>
</div>
<div class="content">
    <?php if ($error): ?>
        <div class="alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <div class="section-title">Event Info</div>
            <div class="grid-2">
                <div>
                    <label class="form-label">Year <span class="required">*</span></label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= $ev['tahun'] ?>" min="2020" max="2035" required>
                </div>
                <div>
                    <label class="form-label">Event Type <span class="required">*</span></label>
                    <select name="jenis_event" class="form-control" required>
                        <option value="YWKS" <?= $ev['jenis_event']==='YWKS'?'selected':'' ?>>YWKS</option>
                        <option value="YGP"  <?= $ev['jenis_event']==='YGP' ?'selected':'' ?>>YGP Internal</option>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div>
                    <label class="form-label">Rank <span class="required">*</span></label>
                    <input type="text" name="peringkat" class="form-control"
                           value="<?= htmlspecialchars($ev['peringkat']) ?>" required>
                </div>
                <div></div>
            </div>
            <label class="form-label">Material Title <span class="required">*</span></label>
            <input type="text" name="judul_materi" class="form-control"
                   value="<?= htmlspecialchars($ev['judul_materi'] ?? '') ?>" required>
            <div class="grid-2">
                <div>
                    <label class="form-label">Participant <span class="required">*</span></label>
                    <input type="text" name="peserta" class="form-control"
                           value="<?= htmlspecialchars($ev['peserta'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" name="departemen" class="form-control"
                           value="<?= htmlspecialchars($ev['departemen'] ?? '') ?>">
                </div>
            </div>
            <label class="form-label">Description</label>
            <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($ev['deskripsi'] ?? '') ?></textarea>

            <div class="section-title">Photo</div>
            <?php if ($ev['foto']): ?>
                <div style="margin-bottom:10px;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">Foto saat ini:</div>
                    <img src="../../assets/img/<?= htmlspecialchars($ev['foto']) ?>"
                         class="preview-img" id="fotoPreview">
                </div>
            <?php else: ?>
                <img id="fotoPreview" class="preview-img" style="display:none;">
            <?php endif; ?>
            <div class="upload-zone" onclick="document.getElementById('fotoInput').click()">
                <div style="font-size:28px;margin-bottom:6px;">📷</div>
                <div style="font-size:13px;font-weight:600;color:#374151;">Ganti Foto (opsional)</div>
                <div style="font-size:11px;color:#6b7280;margin-top:4px;">JPG, PNG — max 5MB</div>
                <input type="file" id="fotoInput" name="foto"
                       accept=".jpg,.jpeg,.png" style="display:none"
                       onchange="previewFoto(this)">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <a href="index.php" class="btn-cancel">Batal</a>
                <button type="submit" class="btn">Simpan Perubahan</button>
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