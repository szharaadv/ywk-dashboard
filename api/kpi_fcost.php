<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$year = $_GET['year'] ?? 'all';

// ===== Tentukan FY =====
$fy_map = [
    'fy2025' => ['label' => 'FY2025', 'start' => '2025-04-01', 'end' => '2026-03-31'],
    'fy2026' => ['label' => 'FY2026', 'start' => '2026-04-01', 'end' => '2027-03-31'],
];

if ($year === 'all') {
    $latest = $db->query("SELECT MAX(periode) FROM kpi_fcost")->fetchColumn();
    if ($latest) {
        $latestMonth = (int)(new DateTime($latest))->format('n');
        $latestYear  = (int)(new DateTime($latest))->format('Y');
        $cur_fy_key  = ($latestYear > 2026 || ($latestYear === 2026 && $latestMonth >= 4))
                     ? 'fy2026' : 'fy2025';
    } else {
        $cur_fy_key = 'fy2026';
    }
} else {
    $cur_fy_key = $year;
}

$prev_fy_key  = $cur_fy_key === 'fy2026' ? 'fy2025' : null;
$cur_fy_info  = $fy_map[$cur_fy_key]  ?? $fy_map['fy2026'];
$prev_fy_info = $prev_fy_key ? ($fy_map[$prev_fy_key] ?? null) : null;
$cur_fy_label  = $cur_fy_info['label'];
$prev_fy_label = $prev_fy_info ? $prev_fy_info['label'] : 'FY2025';

$sections = ['Conrod', 'HDE'];

// ===== Query current FY =====
$stmt = $db->prepare("
    SELECT DATE_FORMAT(periode, '%b %Y') AS label, section, actual, target, sales
FROM kpi_fcost
WHERE periode BETWEEN ? AND ?
ORDER BY periode ASC, section ASC
");
$stmt->execute([$cur_fy_info['start'], $cur_fy_info['end']]);
$rows = $stmt->fetchAll();

// ===== Query prev FY =====
$rows_prev = [];
if ($prev_fy_info) {
    $stmt2 = $db->prepare("
        SELECT DATE_FORMAT(periode, '%b %Y') AS label, section, actual, target, sales
        FROM kpi_fcost
        WHERE periode BETWEEN ? AND ?
        ORDER BY periode ASC, section ASC
    ");
    $stmt2->execute([$prev_fy_info['start'], $prev_fy_info['end']]);
    $rows_prev = $stmt2->fetchAll();
}

// ===== Build labels Apr–Mar =====
$fy_year = (int) str_replace('fy', '', $cur_fy_key);
$labels  = [];
for ($m = 4; $m <= 12; $m++) $labels[] = date('M Y', mktime(0,0,0,$m,1,$fy_year));
for ($m = 1; $m <= 3;  $m++) $labels[] = date('M Y', mktime(0,0,0,$m,1,$fy_year+1));

$prev_fy_year = $fy_year - 1;
$labels_prev  = [];
for ($m = 4; $m <= 12; $m++) $labels_prev[] = date('M Y', mktime(0,0,0,$m,1,$prev_fy_year));
for ($m = 1; $m <= 3;  $m++) $labels_prev[] = date('M Y', mktime(0,0,0,$m,1,$prev_fy_year+1));

// ===== Helper =====
function buildFcostSection(array $rows, array $labels, array $sections): array {
    $data = [];
    foreach ($sections as $section) {
        $actual = []; $target = []; $sales = []; $pct = [];
        foreach ($labels as $label) {
            $found = false;
            foreach ($rows as $row) {
                if ($row['label'] === $label && $row['section'] === $section) {
                    $act  = $row['actual'] !== null ? (float)$row['actual'] : null;
                    $tgt  = $row['target'] !== null ? (float)$row['target'] : null;
                    $sal  = $row['sales']  !== null ? (float)$row['sales']  : null;
                    $p    = ($sal && $sal > 0) ? round($act / $sal * 100, 4) : null;
                    $actual[] = $act; $target[] = $tgt;
                    $sales[]  = $sal; $pct[]    = $p;
                    $found = true; break;
                }
            }
            if (!$found) { $actual[] = null; $target[] = null; $sales[] = null; $pct[] = null; }
        }
        $data[$section] = [
            'actual' => $actual,
            'target' => $target,
            'sales'  => $sales,
            'pct'    => $pct,        // persentase Cost Reject/Sales
            'pct_target' => ($section === 'Conrod') ? 0.50 : 0.15,
        ];
    }
    return $data;
}

$data      = buildFcostSection($rows,      $labels,      $sections);
$data_prev = $prev_fy_info
           ? buildFcostSection($rows_prev, $labels_prev, $sections)
           : null;

// Cek ada data prev tidak
$has_prev = false;
if ($data_prev) {
    foreach ($sections as $s) {
        $vals = array_filter($data_prev[$s]['actual'] ?? [], fn($v) => $v !== null);
        if (count($vals)) { $has_prev = true; break; }
    }
}

echo json_encode([
    'labels'    => $labels,
    'data'      => $data,
    'data_prev' => $data_prev,
    'compare'   => $has_prev,
    'cur_fy'    => $cur_fy_label,
    'prev_fy'   => $prev_fy_label,
]);
?>