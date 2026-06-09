<?php
session_start();
require_once '../config.php';
requireAdminLogin();

$slideDir = __DIR__ . '/../../assets/ppm-slides/';
$uploadMsg = '';
$uploadErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slides'])) {
    $files    = $_FILES['slides'];
    $count    = count($files['name']);
    $uploaded = 0;
    $errors   = [];

    // Hapus semua slide lama (jpg, jpeg, png)
foreach (glob($slideDir . 'slide-*') as $old) {
    $ext = strtolower(pathinfo($old, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        unlink($old);
    }
}
SKIP_UPLOAD:

    // Kumpulkan & sort berdasarkan nama file
    $fileList = [];
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileList[] = [
                'tmp'  => $files['tmp_name'][$i],
                'name' => $files['name'][$i],
            ];
        }
    }
    usort($fileList, fn($a, $b) => strnatcmp($a['name'], $b['name']));

    foreach ($fileList as $idx => $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors[] = $file['name'] . ' — bukan JPG/PNG';
            continue;
        }
        $newName = sprintf('slide-%02d.jpg', $idx + 1);
        $dest    = $slideDir . $newName;
        if (move_uploaded_file($file['tmp'], $dest)) {
            $uploaded++;
        } else {
            $errors[] = $file['name'] . ' — gagal disimpan';
        }
    }

    if ($uploaded > 0) {
        $uploadMsg = "$uploaded slide berhasil diupload. Slide lama sudah dihapus.";
    }
    if ($errors) {
        $uploadErr = implode('<br>', $errors);
    }
}

$currentSlides = glob($slideDir . 'slide-*.jpg') ?: [];
natsort($currentSlides);
$currentSlides = array_values($currentSlides);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PPM Slides — Admin YWK</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }

        .content { padding:1.5rem; display:flex; flex-direction:column; gap:1rem; max-width:1100px; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem;
        }

        .alert-success { background:#EAF3DE; color:#3B6D11; padding:10px 14px; border-radius:8px; font-size:13px; }
        .alert-danger  { background:#FDECEA; color:#7B0000; padding:10px 14px; border-radius:8px; font-size:13px; }
        .alert-warn    { background:#FAEEDA; color:#854F0B; padding:10px 14px; border-radius:8px; font-size:12px; margin-bottom:1rem; }

        .upload-zone {
            border:2px dashed #e5e7eb; border-radius:10px;
            padding:2.5rem; text-align:center; cursor:pointer;
            transition:all 0.15s; margin-bottom:1rem;
        }
        .upload-zone:hover  { border-color:#D0021B; background:#FDECEA10; }
        .upload-zone.active { border-color:#D0021B; background:#FDECEA20; }

        .upload-icon  { font-size:32px; margin-bottom:8px; }
        .upload-title { font-size:14px; font-weight:600; color:#374151; margin-bottom:4px; }
        .upload-sub   { font-size:12px; color:#6b7280; }

        .btn {
            background:#D0021B; color:#fff; border:none;
            border-radius:8px; padding:9px 20px;
            font-size:13px; font-weight:600; cursor:pointer;
        }
        .btn:hover { background:#7B0000; }
        .btn:disabled { background:#e5e7eb; color:#9ca3af; cursor:not-allowed; }

        .btn-outline {
            background:#fff; color:#6b7280;
            border:1px solid #e5e7eb; font-size:13px;
            padding:9px 16px; border-radius:8px; cursor:pointer;
        }
        .btn-outline:hover { background:#f4f5f7; }

        .file-list {
            margin-top:10px; font-size:12px;
            color:#374151; max-height:120px;
            overflow-y:auto; border:1px solid #f0f0f0;
            border-radius:6px; padding:8px;
            display:none;
        }
        .file-list div { padding:2px 0; border-bottom:1px solid #f9f9f9; }
        .file-list div:last-child { border-bottom:none; }

        #fileCount {
            font-size:13px; font-weight:700;
            color:#D0021B; margin-top:8px;
        }

        .slide-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));
            gap:8px;
        }
        .slide-item {
            border:1px solid #e5e7eb; border-radius:8px;
            overflow:hidden; position:relative;
        }
        .slide-item img {
            width:100%; height:85px;
            object-fit:cover; display:block;
        }
        .slide-num {
            font-size:10px; font-weight:700;
            color:#6b7280; padding:4px 6px;
            text-align:center; background:#f4f5f7;
        }

        .empty-state {
            text-align:center; color:#9ca3af;
            padding:2rem; font-size:13px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="../index.php" class="back-btn">← Back</a>
        <div class="topbar-title">PPM Slides Management</div>
    </div>
    <span style="font-size:12px; color:rgba(255,255,255,0.7);">
        <?= count($currentSlides) ?> slide aktif
    </span>
</div>

<div class="content">

    <?php if ($uploadMsg): ?>
        <div class="alert-success">✓ <?= htmlspecialchars($uploadMsg) ?></div>
    <?php endif; ?>
    <?php if ($uploadErr): ?>
        <div class="alert-danger">⚠ <?= $uploadErr ?></div>
    <?php endif; ?>

    <!-- $MAX_SLIDES = 50;

if (count($_FILES['slides']['name']) > $MAX_SLIDES) {
    $uploadErr = "Maksimal $MAX_SLIDES slide per upload.";
    goto SKIP_UPLOAD;
} -->

    <!-- Upload Form -->
    <div class="card">
        <div class="card-title">Upload Slide Baru</div>
        <div class="alert-warn">
            ⚠ Upload baru akan <strong>menghapus semua slide lama</strong>
            dan menggantinya dengan file yang diupload.
            File diurutkan otomatis berdasarkan nama file
            (contoh: slide-01.jpg, slide-02.jpg, ...).
        </div>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-zone" id="dropZone"
                 onclick="document.getElementById('slideFiles').click()">
                <div class="upload-icon">🖼️</div>
                <div class="upload-title">Klik atau drag & drop file JPG di sini</div>
                <div class="upload-sub">
                    Upload semua slide sekaligus — JPG, PNG — urutan sesuai nama file
                </div>
                <input type="file" id="slideFiles" name="slides[]"
                       multiple accept=".jpg,.jpeg,.png"
                       style="display:none"
                       onchange="handleFiles(this.files)">
                <div id="fileCount"></div>
            </div>

            <div class="file-list" id="fileList"></div>

            <div style="display:flex; gap:8px; margin-top:12px;">
                <button type="submit" class="btn" id="uploadBtn" disabled>
                    ↑ Upload & Replace Semua Slide
                </button>
                <button type="button" class="btn-outline" onclick="resetForm()">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Current Slides -->
    <div class="card">
        <div class="card-title">
            Slide Aktif Saat Ini — <?= count($currentSlides) ?> slide
        </div>
        <?php if (empty($currentSlides)): ?>
            <div class="empty-state">
                📭 Belum ada slide. Upload slide baru di atas.
            </div>
        <?php else: ?>
            <div class="slide-grid">
                <?php foreach ($currentSlides as $i => $slide): ?>
                <div class="slide-item">
                    <img src="/assets/ppm-slides/<?= basename($slide) ?>"
                         alt="Slide <?= $i+1 ?>"
                         loading="lazy">
                    <div class="slide-num">Slide <?= $i+1 ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function handleFiles(files) {
    const count = files.length;
    document.getElementById('fileCount').textContent =
        count + ' file dipilih';
    document.getElementById('uploadBtn').disabled = count === 0;

    const listEl = document.getElementById('fileList');
    const sorted = Array.from(files)
        .sort((a, b) => a.name.localeCompare(b.name, undefined, {numeric: true}));

    listEl.innerHTML = sorted
        .map((f, i) => `<div>${String(i+1).padStart(2,'0')}. ${f.name}</div>`)
        .join('');
    listEl.style.display = 'block';
}

function resetForm() {
    document.getElementById('slideFiles').value   = '';
    document.getElementById('fileCount').textContent = '';
    document.getElementById('fileList').innerHTML = '';
    document.getElementById('fileList').style.display = 'none';
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('dropZone').classList.remove('active');
}

// Drag & Drop
const zone = document.getElementById('dropZone');
zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('active');
});
zone.addEventListener('dragleave', () => zone.classList.remove('active'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('active');
    const input = document.getElementById('slideFiles');
    input.files = e.dataTransfer.files;
    handleFiles(e.dataTransfer.files);
});
</script>
</body>
</html>