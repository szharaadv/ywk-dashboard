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
    for ($m = 4; $m <= 12; $m++) $periodes[] = sprintf('%d-%02d-01', $year, $m);
    for ($m = 1; $m <= 3; $m++)  $periodes[] = sprintf('%d-%02d-01', $year + 1, $m);
    return $periodes;
}

$alert = ''; $alert_type = '';

// ===== HANDLE SAVE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $fy      = $_POST['fy']      ?? 'fy2026';
    $section = $_POST['section'] ?? 'MS1';
    $periodes = fyToPeriodes($fy);

    $stmt = $db->prepare("
        INSERT INTO kpi_safety (periode, section, minor, significant, fatality, target)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            minor       = VALUES(minor),
            significant = VALUES(significant),
            fatality    = VALUES(fatality),
            target      = VALUES(target)
    ");

    $saved = 0;
    foreach ($periodes as $i => $periode) {
        $minor       = $_POST['minor'][$i]       ?? '';
        $significant = $_POST['significant'][$i] ?? '';
        $fatality    = $_POST['fatality'][$i]    ?? '';
        $target      = $_POST['target'][$i]      ?? '';

        if ($minor==='' && $significant==='' && $fatality==='' && $target==='') continue;

        $stmt->execute([
            $periode, $section,
            $minor       !== '' ? (int)$minor       : null,
            $significant !== '' ? (int)$significant : null,
            $fatality    !== '' ? (int)$fatality    : null,
            $target      !== '' ? (int)$target      : null,
        ]);
        $saved++;
    }

    $alert      = "✓ $saved data berhasil disimpan untuk $section - " . strtoupper($fy);
    $alert_type = 'success';
}

// ===== HANDLE DELETE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_periode'])) {
    $db->prepare("DELETE FROM kpi_safety WHERE periode = ? AND section = ?")
       ->execute([$_POST['delete_periode'], $_POST['delete_section']]);
    $alert = '✓ Data berhasil dihapus.'; $alert_type = 'success';
}

// ===== LOAD EXISTING =====
$view_fy      = $_GET['fy']      ?? 'fy2026';
$view_section = $_GET['section'] ?? 'MS1';
$periodes_view = fyToPeriodes($view_fy);

$existing = [];
$stmt2 = $db->prepare("
    SELECT DATE_FORMAT(periode,'%Y-%m-%d') AS p,
           minor, significant, fatality, target
    FROM kpi_safety
    WHERE section = ? AND periode IN (" . implode(',', array_fill(0,12,'?')) . ")
    ORDER BY periode ASC
");
$stmt2->execute(array_merge([$view_section], $periodes_view));
foreach ($stmt2->fetchAll() as $row) $existing[$row['p']] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Safety Input — Admin YWK</title>
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
        .content { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; max-width:1400px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-title { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem; }
        .fy-tabs, .section-tabs { display:flex; gap:4px; margin-bottom:1rem; }
        .fy-tab, .section-tab {
            padding:5px 12px; font-size:11px; font-weight:700;
            border:1.5px solid #e5e7eb; border-radius:20px;
            background:#fff; color:#6b7280; cursor:pointer; text-decoration:none; transition:all 0.15s;
        }
        .fy-tab:hover    { border-color:#185FA5; color:#185FA5; }
        .fy-tab.active   { background:#185FA5; border-color:#185FA5; color:#fff; }
        .section-tab:hover  { border-color:#D0021B; color:#D0021B; }
        .section-tab.active { background:#D0021B; border-color:#D0021B; color:#fff; }
        .input-table { width:100%; border-collapse:collapse; font-size:12px; }
        .input-table th {
            padding:8px 6px; background:#f4f5f7; text-align:center;
            font-size:10px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.04em;
        }
        .input-table th:first-child { text-align:left; width:120px; }
        .input-table td { padding:5px 4px; border-bottom:1px solid #f0f0f0; text-align:center; }
        .input-table td:first-child { text-align:left; font-weight:600; color:#374151; padding-left:6px; }
        .num-input {
            width:52px; padding:4px 4px; font-size:12px; text-align:center;
            border:1px solid #e5e7eb; border-radius:5px; outline:none;
        }
        .num-input:focus { border-color:#D0021B; }
        .num-input.has-value { background:#EAF3DE; border-color:#3B6D11; }
        .btn-save {
            background:#D0021B; color:#fff; border:none; border-radius:8px;
            padding:9px 24px; font-size:13px; font-weight:600; cursor:pointer; margin-top:1rem;
        }
        .btn-save:hover { background:#7B0000; }
        .btn-cancel {
            background:#fff; color:#6b7280; border:1px solid #e5e7eb;
            border-radius:8px; padding:9px 16px; font-size:13px;
            text-decoration:none; display:inline-block; margin-top:1rem; margin-left:8px;
        }
        .alert-success { background:#EAF3DE; color:#3B6D11; padding:10px 14px; border-radius:8px; font-size:13px; }
        .alert-danger  { background:#FDECEA; color:#7B0000; padding:10px 14px; border-radius:8px; font-size:13px; }
        .data-table { width:100%; border-collapse:collapse; font-size:12px; }
        .data-table th {
            padding:8px 10px; background:#f4f5f7; text-align:left;
            font-size:10px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.04em;
        }
        .data-table td { padding:7px 10px; border-bottom:1px solid #f0f0f0; color:#374151; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:#fafafa; }
        .btn-del {
            background:#fff; color:#D0021B; border:1px solid #D0021B;
            border-radius:6px; font-size:10px; padding:2px 8px; cursor:pointer;
        }
        .btn-del:hover { background:#FDECEA; }
        .badge-ok  { background:#EAF3DE; color:#3B6D11; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
        .badge-bad { background:#FDECEA; color:#D0021B; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
        .progress-bar-wrap { background:#f0f0f0; border-radius:4px; height:6px; margin-top:4px; }
        .progress-bar-fill { height:100%; border-radius:4px; background:#D0021B; transition:width 0.3s ease; }
        .progress-label { font-size:10px; color:#6b7280; margin-top:3px; }
        .row-label { display:flex; align-items:center; gap:6px; }
        .row-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-left">
        <a href="index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Input Safety</div>
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
        <div class="card-title">Input Data Safety</div>

        <!-- FY Tabs -->
        <div class="fy-tabs">
            <?php foreach ($fy_list as $fy_key => $fy_year): ?>
            <a href="safety.php?fy=<?= $fy_key ?>&section=<?= $view_section ?>"
               class="fy-tab <?= $view_fy === $fy_key ? 'active' : '' ?>">
                FY<?= $fy_year ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Section Tabs -->
        <div class="section-tabs">
            <?php foreach ($sections as $sec): ?>
            <a href="safety.php?fy=<?= $view_fy ?>&section=<?= $sec ?>"
               class="section-tab <?= $view_section === $sec ? 'active' : '' ?>">
                <?= $sec ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="fy" value="<?= $view_fy ?>">
            <input type="hidden" name="section" value="<?= $view_section ?>">

            <table class="input-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <?php foreach ($months as $m): ?>
                        <th><?= $m ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows_input = [
                        'minor'       => ['label'=>'Minor',       'color'=>'#854F0B'],
                        'significant' => ['label'=>'Significant', 'color'=>'#D0021B'],
                        'fatality'    => ['label'=>'Fatality',    'color'=>'#501313'],
                        'target'      => ['label'=>'Target',      'color'=>'#6b7280'],
                    ];
                    foreach ($rows_input as $field => $meta):
                    ?>
                    <tr>
                        <td>
                            <div class="row-label">
                                <div class="row-dot" style="background:<?= $meta['color'] ?>;"></div>
                                <?= $meta['label'] ?>
                            </div>
                        </td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="<?= $field ?>[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p][$field] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) && $existing[$p][$field] !== null ? $existing[$p][$field] : '' ?>"
                                   min="0" step="1" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $filled = 0;
            foreach ($periodes_view as $p) {
                if (isset($existing[$p])) $filled++;
            }
            $pct = round($filled / 12 * 100);
            ?>
            <div style="margin-top:12px;">
                <div class="progress-label">Progress pengisian: <?= $filled ?>/12 bulan (<?= $pct ?>%)</div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                </div>
            </div>

            <div>
                <button type="submit" class="btn-save">💾 Simpan Data</button>
                <a href="safety.php?fy=<?= $view_fy ?>&section=<?= $view_section ?>"
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
                    <th>Minor</th>
                    <th>Significant</th>
                    <th>Fatality</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing as $p => $row):
                    $total = ($row['minor']??0) + ($row['significant']??0) + ($row['fatality']??0);
                    $isOk  = $total === 0;
                ?>
                <tr>
                    <td><?= date('M Y', strtotime($p)) ?></td>
                    <td><?= $view_section ?></td>
                    <td><?= $row['minor']       ?? '—' ?></td>
                    <td style="font-weight:700; color:<?= ($row['significant']??0)>0 ? '#D0021B' : '#1a1a1a' ?>;">
                        <?= $row['significant'] ?? '—' ?>
                    </td>
                    <td style="font-weight:700; color:<?= ($row['fatality']??0)>0 ? '#501313' : '#1a1a1a' ?>;">
                        <?= $row['fatality']    ?? '—' ?>
                    </td>
                    <td><?= $row['target']      ?? '—' ?></td>
                    <td>
                        <span class="<?= $isOk ? 'badge-ok' : 'badge-bad' ?>">
                            <?= $isOk ? '✓ Zero Accident' : '⚠ Ada Insiden' ?>
                        </span>
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