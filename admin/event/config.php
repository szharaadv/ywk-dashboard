<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';

$db  = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun         = (int)($_POST['tahun'] ?? date('Y'));
    $participants_ywks = (int)($_POST['participants_ywks'] ?? 0);
    $participants_yjp  = (int)($_POST['participants_yjp']  ?? 0);

    // Upsert YWKS
    $db->prepare("
        INSERT INTO ywk_event_config (tahun, jenis_event, participants)
        VALUES (?, 'YWKS', ?)
        ON DUPLICATE KEY UPDATE participants = VALUES(participants)
    ")->execute([$tahun, $participants_ywks]);

    // Upsert YJP
    $db->prepare("
        INSERT INTO ywk_event_config (tahun, jenis_event, participants)
        VALUES (?, 'YJP', ?)
        ON DUPLICATE KEY UPDATE participants = VALUES(participants)
    ")->execute([$tahun, $participants_yjp]);

    $msg = 'success';
}

// Ambil semua tahun dari ywk_event
$years = $db->query("SELECT DISTINCT tahun FROM ywk_event ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);

// Ambil config semua tahun
$configs = [];
$rows = $db->query("SELECT tahun, jenis_event, participants FROM ywk_event_config ORDER BY tahun DESC")->fetchAll();
foreach ($rows as $r) {
    $configs[$r['tahun']][$r['jenis_event']] = $r['participants'];
}

$selected_tahun = (int)($_GET['tahun'] ?? ($years[0] ?? date('Y')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Event Config — Admin YWK</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .topbar {
            background:linear-gradient(135deg,#7B0000,#D0021B);
            padding:12px 1.5rem;
            display:flex; align-items:center; justify-content:space-between;
        }
        .topbar-left { display:flex; align-items:center; gap:12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }
        .content { padding:1.5rem; max-width:500px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.5rem; margin-bottom:1rem; }
        .section-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em;
            margin-bottom:1rem; padding-bottom:6px;
            border-bottom:1px solid #f0f0f0;
        }
        .form-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; display:block; }
        .form-control {
            width:100%; padding:9px 12px; border:1px solid #e5e7eb;
            border-radius:8px; font-size:13px; margin-bottom:14px;
            outline:none; font-family:inherit; background:#fff;
        }
        .form-control:focus { border-color:#D0021B; }
        .btn {
            background:#D0021B; color:#fff; border:none;
            border-radius:8px; padding:10px 24px;
            font-size:13px; font-weight:600; cursor:pointer; width:100%;
        }
        .btn:hover { background:#7B0000; }
        .alert-success {
            background:#EAF3DE; color:#3B6D11;
            padding:10px 14px; border-radius:8px;
            font-size:13px; margin-bottom:1rem;
        }
        .year-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:1rem; }
        .year-tab {
            font-size:11px; font-weight:600; padding:4px 12px;
            border-radius:6px; border:1.5px solid #e5e7eb;
            background:#fff; color:#6b7280; cursor:pointer;
            text-decoration:none;
        }
        .year-tab.active { background:#D0021B; border-color:#D0021B; color:#fff; }
        .section-badge {
            display:inline-block; padding:2px 10px; border-radius:20px;
            font-size:10px; font-weight:700; margin-bottom:8px;
        }
        .badge-ywks { background:#FDECEA; color:#D0021B; }
        .badge-yjp  { background:#E8F0FD; color:#185FA5; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-left">
        <a href="index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Event Participants Config</div>
    </div>
</div>

<div class="content">

    <?php if ($msg === 'success'): ?>
        <div class="alert-success">✓ Data participants berhasil disimpan!</div>
    <?php endif; ?>

    <!-- Year tabs -->
    <div class="year-tabs">
        <?php foreach ($years as $y): ?>
        <a href="?tahun=<?= $y ?>"
           class="year-tab <?= $y == $selected_tahun ? 'active' : '' ?>">
            <?= $y ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="section-title">Set Participants — Tahun <?= $selected_tahun ?></div>

        <form method="POST">
            <input type="hidden" name="tahun" value="<?= $selected_tahun ?>">

            <span class="section-badge badge-ywks">YWKS</span>
            <label class="form-label">Jumlah Participants YWKS</label>
            <input type="number" name="participants_ywks" class="form-control"
                   value="<?= $configs[$selected_tahun]['YWKS'] ?? 0 ?>"
                   min="0" placeholder="0">

            <span class="section-badge badge-yjp">YGP Internal</span>
            <label class="form-label">Jumlah Participants YGP Internal</label>
            <input type="number" name="participants_yjp" class="form-control"
                   value="<?= $configs[$selected_tahun]['YGP'] ?? 0 ?>"
                   min="0" placeholder="0">

            <button type="submit" class="btn">💾 Simpan</button>
        </form>
    </div>

    <!-- Summary semua tahun -->
    <div class="card">
        <div class="section-title">Rekap Semua Tahun</div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f4f5f7;">
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:#6b7280;">Tahun</th>
                    <th style="padding:8px 10px;text-align:center;font-size:11px;color:#6b7280;">YWKS</th>
                    <th style="padding:8px 10px;text-align:center;font-size:11px;color:#6b7280;">YGP Internal</th>
                    <th style="padding:8px 10px;text-align:center;font-size:11px;color:#6b7280;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($years as $y): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:8px 10px;font-weight:700;"><?= $y ?></td>
                    <td style="padding:8px 10px;text-align:center;color:#D0021B;font-weight:700;">
                        <?= $configs[$y]['YWKS'] ?? 0 ?>
                    </td>
                    <td style="padding:8px 10px;text-align:center;color:#185FA5;font-weight:700;">
                        <?= $configs[$y]['YJP'] ?? 0 ?>
                    </td>
                    <td style="padding:8px 10px;text-align:center;">
                        <a href="?tahun=<?= $y ?>" style="font-size:11px;color:#D0021B;text-decoration:none;font-weight:600;">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>