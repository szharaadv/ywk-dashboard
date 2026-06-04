<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';
$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id   = (int) $_POST['delete_id'];
    $stmt = $db->prepare("SELECT foto FROM ywk_event WHERE id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if ($ev && $ev['foto']) {
        $path = __DIR__ . '/../../assets/img/' . $ev['foto'];
        if (file_exists($path)) unlink($path);
    }
    $db->prepare("DELETE FROM ywk_event WHERE id = ?")->execute([$id]);
    header('Location: index.php?deleted=1');
    exit;
}

$events = $db->query("
    SELECT * FROM ywk_event
    ORDER BY tahun DESC, peringkat ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>YWK Event — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display:flex; align-items:center; gap:12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }

        .btn {
            background:#fff; color:#D0021B; border:none;
            border-radius:8px; padding:7px 16px;
            font-size:12px; font-weight:700; cursor:pointer;
            text-decoration:none; display:inline-block;
        }
        .btn:hover { background:#FDECEA; }

        .content { padding:1.5rem; display:flex; flex-direction:column; gap:1rem; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-header {
            display:flex; align-items:center;
            justify-content:space-between; margin-bottom:1rem;
        }
        .card-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em;
        }

        .btn-add {
            background:#D0021B; color:#fff; border:none;
            border-radius:8px; padding:7px 14px;
            font-size:12px; font-weight:600; cursor:pointer;
            text-decoration:none;
        }
        .btn-add:hover { background:#7B0000; }

        .btn-del {
            background:#fff; color:#D0021B;
            border:1px solid #D0021B; border-radius:6px;
            font-size:11px; padding:3px 10px; cursor:pointer;
        }
        .btn-del:hover { background:#FDECEA; }

        table { width:100%; border-collapse:collapse; font-size:13px; }
        th {
            padding:8px 10px; text-align:left;
            font-size:11px; font-weight:700; color:#6b7280;
            background:#f4f5f7; text-transform:uppercase; letter-spacing:0.04em;
        }
        td { padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#374151; vertical-align:middle; }
        tr:hover td { background:#fafafa; }

        .rank-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
        .rank-1 { background:#FDECEA; color:#D0021B; }
        .rank-2 { background:#f4f5f7; color:#555; }
        .rank-3 { background:#EAF3DE; color:#3B6D11; }
        .rank-n { background:#f4f5f7; color:#6b7280; }

        .alert-success { background:#EAF3DE; color:#3B6D11; padding:10px 14px; border-radius:8px; font-size:13px; }

        .thumb {
            width:52px; height:40px; object-fit:cover;
            border-radius:4px; border:1px solid #e5e7eb; display:block;
        }
        .no-foto {
            width:52px; height:40px; background:#f4f5f7;
            border-radius:4px; display:flex;
            align-items:center; justify-content:center; font-size:18px;
        }
        .empty-state { text-align:center; color:#9ca3af; padding:2rem; font-size:13px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="../index.php" class="back-btn">← Back</a>
        <div class="topbar-title">YWK Event Management</div>
    </div>
    <a href="create.php" class="btn-add">+ Tambah Event</a>
</div>

<div class="content">

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert-success">✓ Data event berhasil dihapus.</div>
    <?php endif; ?>
    <?php if (isset($_GET['created'])): ?>
        <div class="alert-success">✓ Data event berhasil ditambahkan.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                Semua Event — <?= count($events) ?> data
            </div>
        </div>

        <?php if (empty($events)): ?>
            <div class="empty-state">
                📭 Belum ada data event. Klik "+ Tambah Event" untuk mulai.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Tahun</th>
                    <th>Peringkat</th>
                    <th>Judul Materi</th>
                    <th>Peserta</th>
                    <th>Departemen</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev):
                    $r   = $ev['peringkat'];
                    $cls = $r==1?'rank-1':($r==2?'rank-2':($r==3?'rank-3':'rank-n'));
                    $lbl = $r==1?'Pemenang 1':($r==2?'Pemenang 2':($r==3?'Pemenang 3':'Peserta'));
                ?>
                <tr>
                    <td>
                        <?php if ($ev['foto']): ?>
                            <img src="/assets/img/...">
                                 class="thumb"
                                 onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="no-foto">📷</div>
                        <?php endif; ?>
                    </td>
                    <td><?= $ev['tahun'] ?></td>
                    <td><span class="rank-badge <?= $cls ?>"><?= $lbl ?></span></td>
                    <td><?= htmlspecialchars($ev['judul_materi'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($ev['peserta']      ?? '—') ?></td>
                    <td><?= htmlspecialchars($ev['departemen']   ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus data ini?')">
                            <input type="hidden" name="delete_id" value="<?= $ev['id'] ?>">
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
</body>
</html>