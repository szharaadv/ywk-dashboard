<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/ywk-dashboard/config/db.php';
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
        INSERT INTO kpi_quality (periode, section, reject_inhouse, reject_target, customer_claim, claim_target)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            reject_inhouse  = VALUES(reject_inhouse),
            reject_target   = VALUES(reject_target),
            customer_claim  = VALUES(customer_claim),
            claim_target    = VALUES(claim_target)
    ");

    foreach ($periodes as $i => $periode) {
        $reject_inhouse = $_POST['reject_inhouse'][$i] ?? '';
        $reject_target  = $_POST['reject_target'][$i]  ?? '';
        $customer_claim = $_POST['customer_claim'][$i] ?? '';
        $claim_target   = $_POST['claim_target'][$i]   ?? '';

        if ($reject_inhouse === '' && $reject_target === '' && $customer_claim === '' && $claim_target === '') continue;

        $stmt->execute([
            $periode,
            $section,
            $reject_inhouse !== '' ? (float)$reject_inhouse : null,
            $reject_target  !== '' ? (float)$reject_target  : null,
            $customer_claim !== '' ? (int)$customer_claim   : null,
            $claim_target   !== '' ? (int)$claim_target     : null,
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
    $db->prepare("DELETE FROM kpi_quality WHERE periode = ? AND section = ?")
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
    SELECT DATE_FORMAT(periode,'%Y-%m-%d') AS p,
           reject_inhouse, reject_target, customer_claim, claim_target
    FROM kpi_quality
    WHERE section = ? AND periode IN (" . implode(',', array_fill(0, 12, '?')) . ")
    ORDER BY periode ASC
");
$stmt2->execute(array_merge([$view_section], $periodes_view));
foreach ($stmt2->fetchAll() as $row) {
    $existing[$row['p']] = $row;
}

// Progress: hitung bulan yang ada reject_inhouse terisi
$filled = 0;
foreach ($periodes_view as $p) {
    if (isset($existing[$p]) && $existing[$p]['reject_inhouse'] !== null) $filled++;
}
$pct = round($filled / 12 * 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Quality Input — Admin YWK</title>
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

        .content { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; max-width:1300px; }

        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
        .card-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem;
        }
        .sub-title {
            font-size:12px; font-weight:700; color:#374151;
            margin: 1rem 0 0.5rem; padding: 6px 10px;
            background:#f4f5f7; border-left: 3px solid #D0021B;
            border-radius: 0 6px 6px 0;
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

        .input-table { width:100%; border-collapse:collapse; font-size:12px; margin-bottom:0.5rem; }
        .input-table th {
            padding:8px 10px; background:#f4f5f7; text-align:center;
            font-size:10px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.04em;
        }
        .input-table th:first-child { text-align:left; min-width:140px; }
        .input-table td { padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center; }
        .input-table td:first-child { text-align:left; font-weight:600; color:#374151; }

        .num-input {
            width:64px; padding:5px 6px; font-size:12px; text-align:center;
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

        .divider { border:none; border-top:2px dashed #e5e7eb; margin:1rem 0; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="index.php" class="back-btn">← Back</a>
        <div class="topbar-title">Input Quality KPI</div>
    </div>
    <div style="font-size:12px; color:rgba(255,255,255,0.7);">
        Section: <?= $view_section ?> · <?= strtoupper($view_fy) ?>
    </div>
</div>

<div class="content">

    <?php if ($alert): ?>
        <div class="alert-<?= $alert_type ?>"><?= $alert ?></div>
    <?php endif; ?>

    <!-- Input Form -->
    <div class="card">
        <div class="card-title">Input Data Quality</div>

        <!-- FY Tabs -->
        <div class="fy-tabs">
            <?php foreach ($fy_list as $fy_key => $fy_year): ?>
            <a href="quality.php?fy=<?= $fy_key ?>&section=<?= $view_section ?>"
               class="fy-tab <?= $view_fy === $fy_key ? 'active' : '' ?>">
                FY<?= $fy_year ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Section Tabs -->
        <div class="section-tabs">
            <?php foreach ($sections as $sec): ?>
            <a href="quality.php?fy=<?= $view_fy ?>&section=<?= $sec ?>"
               class="section-tab <?= $view_section === $sec ? 'active' : '' ?>">
                <?= $sec ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="fy" value="<?= $view_fy ?>">
            <input type="hidden" name="section" value="<?= $view_section ?>">

            <!-- Sub-tabel 1: Reject In House / PPM -->
            <div class="sub-title">📊 REJECT IN HOUSE / PPM</div>
            <table class="input-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <?php foreach ($months as $m): ?><th><?= $m ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Target (PPM)</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="reject_target[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['reject_target'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['reject_target'] : '' ?>"
                                   min="0" step="1" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Reject In House (PPM)</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="reject_inhouse[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['reject_inhouse'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['reject_inhouse'] : '' ?>"
                                   min="0" step="1" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>

            <hr class="divider">

            <!-- Sub-tabel 2: Customer Claim / Cases -->
            <div class="sub-title">📋 CUSTOMER CLAIM / CASES</div>
            <table class="input-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <?php foreach ($months as $m): ?><th><?= $m ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Target (Cases)</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="claim_target[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['claim_target'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['claim_target'] : '' ?>"
                                   min="0" step="1" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Customer Claim (Cases)</td>
                        <?php foreach ($periodes_view as $i => $p): ?>
                        <td>
                            <input type="number" name="customer_claim[]"
                                   class="num-input <?= isset($existing[$p]) && $existing[$p]['customer_claim'] !== null ? 'has-value' : '' ?>"
                                   value="<?= isset($existing[$p]) ? $existing[$p]['customer_claim'] : '' ?>"
                                   min="0" step="1" placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>

            <!-- Progress -->
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
                <a href="quality.php?fy=<?= $view_fy ?>&section=<?= $view_section ?>"
                   class="btn-cancel">Reset</a>
            </div>
        </form>
    </div>

    <!-- Existing Data Table -->
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
                    <th>Reject In House (PPM)</th>
                    <th>Target PPM</th>
                    <th>Status Reject</th>
                    <th>Customer Claim</th>
                    <th>Target Claim</th>
                    <th>Status Claim</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing as $p => $row):
                    $okReject = $row['reject_inhouse'] !== null && $row['reject_target'] !== null
                              && $row['reject_inhouse'] <= $row['reject_target'];
                    $okClaim  = $row['customer_claim'] !== null && $row['claim_target'] !== null
                              && $row['customer_claim'] <= $row['claim_target'];
                ?>
                <tr>
                    <td><?= date('M Y', strtotime($p)) ?></td>
                    <td><?= $view_section ?></td>
                    <td style="font-weight:700;"><?= $row['reject_inhouse'] ?? '—' ?></td>
                    <td><?= $row['reject_target'] ?? '—' ?></td>
                    <td>
                        <?php if ($row['reject_inhouse'] !== null && $row['reject_target'] !== null): ?>
                        <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
                                     background:<?= $okReject ? '#EAF3DE' : '#FDECEA' ?>;
                                     color:<?= $okReject ? '#3B6D11' : '#D0021B' ?>;">
                            <?= $okReject ? '✓ On Target' : '⚠ Off Target' ?>
                        </span>
                        <?php else: ?><span style="font-size:10px;color:#9ca3af;">—</span><?php endif; ?>
                    </td>
                    <td style="font-weight:700;"><?= $row['customer_claim'] ?? '—' ?></td>
                    <td><?= $row['claim_target'] ?? '—' ?></td>
                    <td>
                        <?php if ($row['customer_claim'] !== null && $row['claim_target'] !== null): ?>
                        <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
                                     background:<?= $okClaim ? '#EAF3DE' : '#FDECEA' ?>;
                                     color:<?= $okClaim ? '#3B6D11' : '#D0021B' ?>;">
                            <?= $okClaim ? '✓ On Target' : '⚠ Off Target' ?>
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