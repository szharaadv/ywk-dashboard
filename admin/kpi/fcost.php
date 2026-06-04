<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';
$db = getDB();

$sections = ['MS1', 'MS2', 'Conrod', 'HDE'];
$months   = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$fy_list  = ['fy2025' => 2025, 'fy2026' => 2026, 'fy2027' => 2027];

function fyToPeriodes(string $fy): array {
    $year = (int) str_replace('fy', '', $fy);
    $periodes = [];
    for ($m = 4; $m <= 12; $m++) {
        $periodes[] = sprintf('%d-%02d-01', $year, $m);
    }
    for ($m = 1; $m <= 3; $m++) {
        $periodes[] = sprintf('%d-%02d-01', $year + 1, $m);
    }
    return $periodes;
}

$alert = '';
$alert_type = '';

// ===== HANDLE SAVE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $fy      = $_POST['fy']      ?? 'fy2026';
    $section = $_POST['section'] ?? 'MS1';
    $periodes = fyToPeriodes($fy);

    $saved = 0;
    $stmt = $db->prepare("
        INSERT INTO kpi_fcost (periode, section, actual, target)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE actual = VALUES(actual), target = VALUES(target)
    ");

    foreach ($periodes as $i => $periode) {
        $actual = $_POST['actual'][$i] ?? '';
        $target = $_POST['target'][$i] ?? '';

        if ($actual === '' && $target === '') continue;

        $stmt->execute([
            $periode,
            $section,
            $actual !== '' ? (float)$actual : null,
            $target !== '' ? (float)$target : null,
        ]);
        $saved++;
    }

    $alert      = "✓ $saved data berhasil disimpan untuk $section - " . strtoupper($fy);
    $alert_type = 'success';
}

// ===== HANDLE DELETE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_periode'])) {
    $periode = $_POST['delete_periode'];
    $section = $_POST['delete_section'];
    $db->prepare("DELETE FROM kpi_fcost WHERE periode = ? AND section = ?")
       ->execute([$periode, $section]);
    $alert      = '✓ Data berhasil dihapus.';
    $alert_type = 'success';
}

// ===== LOAD EXISTING DATA =====
$view_fy      = $_GET['fy']      ?? 'fy2026';
$view_section = $_GET['section'] ?? 'MS1';
$periodes_view = fyToPeriodes($view_fy);

$existing = [];
$stmt2 = $db->prepare("
    SELECT DATE_FORMAT(periode,'%Y-%m-%d') AS p, actual, target
    FROM kpi_fcost
    WHERE section = ? AND periode IN (" . implode(',', array_fill(0, 12, '?')) . ")
    ORDER BY periode ASC
");
$stmt2->execute(array_merge([$view_section], $periodes_view));
foreach ($stmt2->fetchAll() as $row) {
    $existing[$row['p']] = $row;
}

$filled = 0;
foreach ($periodes_view as $p) {
    if (isset($existing[$p]) && $existing[$p]['actual'] !== null) $filled++;
}
$pct = round($filled / 12 * 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>F-Cost Input — Admin YWK</title>
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

        .content { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; max-width:1200px; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem;
        }

        .section-tabs, .fy-tabs { display:flex; gap:4px; margin-bottom:1rem; }
        .section-tab {
            padding:6px 14px; font-size:11px; font-weight:700;
            border:1.5px solid #e5e7eb; border-radius:20px;
            background:#fff; color:#6b7280; cursor:pointer; text-decoration:none;
            transition:all 0.15s;
        }
        .section-tab:hover { border-color:#D0021B; color:#D0021B; }
        .section-tab.active { background:#D0021B; border-color:#D0021B; color:#fff; }
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
        .input-table th:first-child { text-align:left; }
        .input-table td { padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center; }
        .input-table td:first-child { text-align:left; font-weight:600; color:#374151; }

        .num-input {
            width:72px; padding:5px 6px; font-size:12px; text-align:center;
            border:1px solid #e5e7eb; border-radius:6px; outline:none;
            transition:border-color 0.15s;
        }
        .num-input:focus { border-color:#D0021B; }
        .num-input.has-value { background:#EAF3DE; border-color:#3B6D11; }

        .btn-save {
            background:#D0021B; color:#fff; border:none; border-radius:8px;
            padding:10px 28px; font-size:13px; font-weight:600; cursor:pointer;
            margin-top:1rem;
        }
        .btn-save:hover { background:#7B0000; }
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
            background:#fff; color:#D0021B; border:1px solid #D0021B;
            border-radius:6px; font-size:10px; padding:2px 8px; cursor:pointer;
        }
        .btn-del:hover { background:#FDECEA; }

        .alert-success { background:#EAF3DE; color:#3B6D11; padding:10px 14px; border-radius:8px; font-size:13px; }
        .alert-danger  { background:#FDECEA; color:#7B0000; padding:10px 14px; border-radius:8px; font-size:13px; }

        .progress-bar-wrap { background:#f0f0f0; border-radius:4px; height:6px; margin-top:4px; }
        .progress-bar-fill { height:100%; border-radius:4px; background:#D0021B; transition:width 0.3s ease; }
        .progress-label { font-size:10px; color:#6b7280; margin-top:3px; }

        .unit-note {
            font-size:11px; color:#6b7280; margin-bottom:0.75rem;
            padding:6px 10px; background:#fffbeb; border-left:3px solid #f59e0b;
            border-radius:0 6px 6px 0;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Input Failure Cost (F-Cost)</div>
    </div>
    <div style="font-size:12px; color:rgba(255,255,255,0.7);">
        Section: <?= $view_section ?> · <?= strtoupper($view_fy) ?>
    </div>
</div>

<div class="content">

    <?php if ($alert): ?>
        <div class="alert-<?= $alert_type ?>"><?= $alert ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Input Data F-Cost</div>

        <!-- FY Tabs -->
        <div class="fy-tabs">
            <?php foreach ($fy_list as $fy_key => $fy_year): ?>
            <a href="fcost.php?fy=<?= $fy_key ?>&section=<?= $view_section ?>"
               class="fy-tab <?= $view_fy === $fy_key ? 'active' : '' ?>">
                FY<?= $fy_year ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Section Tabs -->
        <div class="section-tabs">
            <?php foreach ($sections as $sec): ?>
            <a href="fcost.php?fy=<?= $view_fy ?>&section=<?= $sec ?>"
               class="section-tab <?= $view_section === $sec ? 'active' : '' ?>">
                <?= $sec ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="unit-note">💡 Masukkan nilai F-Cost dalam satuan yang konsisten (contoh: Juta Yen atau %). Sesuaikan label target di bawah jika perlu.</div>

        <form method="POST">
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="fy" value="<?= $view_fy ?>">
            <input type="hidden" name="section" value="<?= $view_section ?>">

            <table class="input-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <?php foreach ($months as $m): ?><th><?= $m ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Target F-Cost</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="target[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['target'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['target'] : '' ?>"
                                   min="0" step="0.01" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Actual F-Cost</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="actual[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['actual'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['actual'] : '' ?>"
                                   min="0" step="0.01" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:12px;">
                <div class="progress-label">
                    Progress pengisian: <?= $filled ?>/12 bulan (<?= $pct ?>%)
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                </div>
            </div>

            <div>
                <button type="submit" class="btn-save">💾 Simpan Data</button>
                <a href="fcost.php?fy=<?= $view_fy ?>&section=<?= $view_section ?>"
                   class="btn-cancel">Reset</a>
            </div>
        </form>
    </div>

    <!-- Existing Data -->
    <div class="card">
        <div class="card-title">
            Data Tersimpan — <?= $view_section ?> · <?= strtoupper($view_fy) ?>
            (<?= count($existing) ?> dari 12 bulan)
        </div>

        <?php if (empty($existing)): ?>
            <div style="text-align:center; color:#9ca3af; padding:2rem; font-size:13px;">
                📭 Belum ada data untuk <?= $view_section ?> - <?= strtoupper($view_fy) ?>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Section</th>
                    <th>Actual</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing as $p => $row):
                    // F-Cost: ON target = actual <= target (biaya lebih rendah = lebih baik)
                    $isOk = $row['actual'] !== null && $row['target'] !== null
                          && $row['actual'] <= $row['target'];
                ?>
                <tr>
                    <td><?= date('M Y', strtotime($p)) ?></td>
                    <td><?= $view_section ?></td>
                    <td style="font-weight:700;"><?= $row['actual'] !== null ? number_format($row['actual'],2) : '—' ?></td>
                    <td><?= $row['target'] !== null ? number_format($row['target'],2) : '—' ?></td>
                    <td>
                        <?php if ($row['actual'] !== null && $row['target'] !== null): ?>
                        <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
                                     background:<?= $isOk ? '#EAF3DE' : '#FDECEA' ?>;
                                     color:<?= $isOk ? '#3B6D11' : '#D0021B' ?>;">
                            <?= $isOk ? '✓ On Target' : '⚠ Off Target' ?>
                        </span>
                        <?php else: ?><span style="font-size:10px;color:#9ca3af;">—</span><?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus data <?= date('M Y', strtotime($p)) ?>?')">
                            <input type="hidden" name="delete_periode" value="<?= $p ?>">
                            <input type="hidden" name="delete_section" value="<?= $view_section ?>">
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