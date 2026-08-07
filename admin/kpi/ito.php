<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';
$db = getDB();

$months  = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$fy_list = ['fy2025' => 2025, 'fy2026' => 2026, 'fy2027' => 2027];

// FY → 12 periode (Apr fy .. Mar fy+1)
function fyToPeriodes(string $fy): array {
    $year = (int) str_replace('fy', '', $fy);
    $periodes = [];
    for ($m = 4; $m <= 12; $m++) $periodes[] = sprintf('%d-%02d-01', $year, $m);
    for ($m = 1; $m <= 3;  $m++) $periodes[] = sprintf('%d-%02d-01', $year + 1, $m);
    return $periodes;
}

$alert = '';
$alert_type = '';

// ===== HANDLE SAVE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $fy       = $_POST['fy'] ?? 'fy2026';
    $periodes = fyToPeriodes($fy);
    $saved = 0; $failed = 0; $first_error = '';

    try {
        $stmt = $db->prepare("
            INSERT INTO kpi_ito (periode, ito_days, inventory_amount)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE ito_days = VALUES(ito_days), inventory_amount = VALUES(inventory_amount)
        ");

        foreach ($periodes as $i => $periode) {
            $days = trim($_POST['days'][$i] ?? '');
            $amt  = trim($_POST['amount'][$i] ?? '');
            if ($days === '' && $amt === '') continue;   // lewati baris kosong
            try {
                $stmt->execute([
                    $periode,
                    $days !== '' ? (float) $days : null,
                    $amt  !== '' ? (float) $amt  : null,
                ]);
                $saved++;
            } catch (Exception $e) {
                $failed++;
                if ($first_error === '') $first_error = $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $failed++;
        $first_error = $e->getMessage();
    }

    if ($failed > 0) {
        $alert = "⚠️ $saved data berhasil, $failed data gagal"
               . ($first_error ? " — $first_error" : '');
        $alert_type = 'danger';
    } else {
        $alert = "✓ $saved data berhasil disimpan untuk " . strtoupper($fy);
        $alert_type = 'success';
    }
}

// ===== HANDLE DELETE ROW =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_periode'])) {
    try {
        $db->prepare("DELETE FROM kpi_ito WHERE periode = ?")->execute([$_POST['delete_periode']]);
        $alert = '✓ Data berhasil dihapus.';
        $alert_type = 'success';
    } catch (Exception $e) {
        $alert = "❌ Delete error: " . $e->getMessage();
        $alert_type = 'danger';
    }
}

// ===== LOAD EXISTING DATA =====
$view_fy       = $_GET['fy'] ?? 'fy2026';
$periodes_view = fyToPeriodes($view_fy);

$existing = [];
try {
    $stmt2 = $db->prepare("
        SELECT DATE_FORMAT(periode,'%Y-%m-%d') AS p, ito_days, inventory_amount
        FROM kpi_ito
        WHERE periode IN (" . implode(',', array_fill(0, 12, '?')) . ")
        ORDER BY periode ASC
    ");
    $stmt2->execute($periodes_view);
    foreach ($stmt2->fetchAll() as $row) $existing[$row['p']] = $row;
} catch (Exception $e) {}

$filled = 0;
foreach ($periodes_view as $p) {
    if (isset($existing[$p]) && ($existing[$p]['ito_days'] !== null || $existing[$p]['inventory_amount'] !== null)) $filled++;
}
$pct_filled = round($filled / 12 * 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ITO Input — Admin YWK</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #185FA5 0%, #1E90FF 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display:flex; align-items:center; gap:12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }

        .content { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; max-width:1400px; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem;
        }

        .fy-tabs { display:flex; gap:4px; margin-bottom:1rem; flex-wrap:wrap; }
        .fy-tab {
            padding:5px 12px; font-size:11px; font-weight:700;
            border:1.5px solid #e5e7eb; border-radius:20px;
            background:#fff; color:#6b7280; cursor:pointer; text-decoration:none;
            transition:all 0.15s;
        }
        .fy-tab:hover { border-color:#185FA5; color:#185FA5; }
        .fy-tab.active { background:#185FA5; border-color:#185FA5; color:#fff; }

        .input-table { width:100%; border-collapse:collapse; font-size:12px; }
        .input-table th {
            padding:8px 10px; background:#f4f5f7; text-align:center;
            font-size:10px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.04em;
        }
        .input-table th:first-child { text-align:left; min-width:120px; }
        .input-table td { padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center; }
        .input-table td:first-child { text-align:left; font-weight:600; color:#374151; }

        .num-input {
            width:75px; padding:4px 4px; font-size:10px; text-align:center;
            border:1px solid #e5e7eb; border-radius:6px; outline:none;
            transition:border-color 0.15s;
        }
        .num-input:focus { border-color:#185FA5; }
        .num-input.has-value { background:#EAF3DE; border-color:#3B6D11; }

        .btn-save {
            background:#185FA5; color:#fff; border:none; border-radius:8px;
            padding:10px 28px; font-size:13px; font-weight:600; cursor:pointer; margin-top:1rem;
        }
        .btn-save:hover { background:#1E3A5F; }
        .btn-cancel {
            background:#fff; color:#6b7280; border:1px solid #e5e7eb;
            border-radius:8px; padding:10px 20px; font-size:13px;
            text-decoration:none; display:inline-block; margin-top:1rem; margin-left:8px;
        }
        .btn-cancel:hover { background:#f4f5f7; }

        .data-table { width:100%; border-collapse:collapse; font-size:12px; }
        .data-table th {
            padding:8px 10px; background:#f4f5f7; text-align:left;
            font-size:10px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.04em;
        }
        .data-table td { padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#374151; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:#fafafa; }

        .btn-del {
            background:#fff; color:#185FA5; border:1px solid #185FA5;
            border-radius:6px; font-size:10px; padding:2px 8px; cursor:pointer;
        }
        .btn-del:hover { background:#E8F1F5; }

        .alert-success { background:#EAF3DE; color:#3B6D11; padding:10px 14px; border-radius:8px; font-size:13px; }
        .alert-danger  { background:#FCE4EC; color:#C2185B; padding:10px 14px; border-radius:8px; font-size:13px; }

        .progress-bar-wrap { background:#f0f0f0; border-radius:4px; height:6px; margin-top:4px; }
        .progress-bar-fill { height:100%; border-radius:4px; background:#185FA5; transition:width 0.3s ease; }
        .progress-label { font-size:10px; color:#6b7280; margin-top:3px; }

        .unit-note {
            font-size:11px; color:#6b7280; margin-bottom:0.75rem;
            padding:6px 10px; background:#E8F1F5; border-left:3px solid #185FA5;
            border-radius:0 6px 6px 0;
        }

        .row-days { background:#fff; }
        .row-amt  { background:#fef9f0; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Input ITO — Inventory Turn Over</div>
    </div>
    <div style="font-size:12px; color:rgba(255,255,255,0.7);">
        <?= strtoupper($view_fy) ?>
    </div>
</div>

<div class="content">

    <?php if ($alert): ?>
        <div class="alert-<?= $alert_type ?>"><?= $alert ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Input Data ITO</div>

        <!-- FY Tabs -->
        <div class="fy-tabs">
            <?php foreach ($fy_list as $fy_key => $fy_year): ?>
            <a href="ito.php?fy=<?= $fy_key ?>"
               class="fy-tab <?= $view_fy === $fy_key ? 'active' : '' ?>">
                FY<?= $fy_year ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="unit-note">
            💡 <strong>ITO Days</strong> = jumlah hari inventory · <strong>Inventory Amount</strong> = nilai inventory.
            Kosongkan bulan yang belum ada datanya.
        </div>

        <form method="POST">
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="fy" value="<?= $view_fy ?>">

            <div style="overflow-x:auto;">
            <table class="input-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <?php foreach ($months as $m): ?><th><?= $m ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr class="row-days">
                        <td>ITO Days</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="days[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['ito_days'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? ($existing[$p]['ito_days'] ?? '') : '' ?>"
                                   min="0" step="0.01" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="row-amt">
                        <td>Inventory Amount</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="amount[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['inventory_amount'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? ($existing[$p]['inventory_amount'] ?? '') : '' ?>"
                                   min="0" step="0.01" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            </div>

            <div style="margin-top:12px;">
                <div class="progress-label">
                    Progress pengisian: <?= $filled ?>/12 bulan (<?= $pct_filled ?>%)
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= $pct_filled ?>%;"></div>
                </div>
            </div>

            <div>
                <button type="submit" class="btn-save">💾 Simpan Data</button>
                <a href="ito.php?fy=<?= $view_fy ?>" class="btn-cancel">Reset</a>
            </div>
        </form>
    </div>

    <!-- Existing Data -->
    <div class="card">
        <div class="card-title">
            Data Tersimpan — <?= strtoupper($view_fy) ?> (<?= count($existing) ?> dari 12 bulan)
        </div>

        <?php if (empty($existing)): ?>
            <div style="text-align:center; color:#9ca3af; padding:2rem; font-size:13px;">
                📭 Belum ada data untuk <?= strtoupper($view_fy) ?>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>ITO Days</th>
                    <th>Inventory Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing as $p => $row): ?>
                <tr>
                    <td><?= date('M Y', strtotime($p)) ?></td>
                    <td style="font-weight:700;">
                        <?= $row['ito_days'] !== null ? number_format($row['ito_days'], 2) : '—' ?>
                    </td>
                    <td>
                        <?= $row['inventory_amount'] !== null ? number_format($row['inventory_amount'], 2) : '—' ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus data <?= date('M Y', strtotime($p)) ?>?')">
                            <input type="hidden" name="delete_periode" value="<?= $p ?>">
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

<script>
document.querySelectorAll('.num-input').forEach(input => {
    input.addEventListener('input', () => {
        input.classList.toggle('has-value', input.value !== '');
    });
});
</script>
</body>
</html>
