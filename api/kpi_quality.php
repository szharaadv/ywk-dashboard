<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$year = $_GET['year'] ?? 'all';

// ===== Tentukan FY label =====
$fy_map = [
    'fy2025' => ['label' => 'FY2025', 'start' => '2025-04-01', 'end' => '2026-03-31'],
    'fy2026' => ['label' => 'FY2026', 'start' => '2026-04-01', 'end' => '2027-03-31'],
];

// Deteksi FY aktif berdasarkan data terbaru kalau year=all
if ($year === 'all') {
    $latest = $db->query("SELECT MAX(periode) FROM kpi_quality")->fetchColumn();
    if ($latest) {
        $latestDate = new DateTime($latest);
        $latestMonth = (int)$latestDate->format('n');
        $latestYear  = (int)$latestDate->format('Y');
        // FY2026 = Apr 2026 – Mar 2027
        if ($latestYear > 2026 || ($latestYear === 2026 && $latestMonth >= 4)) {
            $cur_fy_key = 'fy2026';
        } else {
            $cur_fy_key = 'fy2025';
        }
    } else {
        $cur_fy_key = 'fy2026';
    }
} else {
    $cur_fy_key = $year;
}

// Prev FY
$prev_fy_key = $cur_fy_key === 'fy2026' ? 'fy2025' : null;

$cur_fy_info  = $fy_map[$cur_fy_key]  ?? $fy_map['fy2026'];
$prev_fy_info = $prev_fy_key ? ($fy_map[$prev_fy_key] ?? null) : null;

$cur_fy_label  = $cur_fy_info['label'];
$prev_fy_label = $prev_fy_info ? $prev_fy_info['label'] : 'FY2025';

// ===== Query current FY =====
$stmt = $db->prepare("
    SELECT DATE_FORMAT(periode, '%b %Y') AS label,
           section, reject_inhouse, reject_target, customer_claim, claim_target
    FROM kpi_quality
    WHERE periode BETWEEN ? AND ?
    ORDER BY periode ASC, section ASC
");
$stmt->execute([$cur_fy_info['start'], $cur_fy_info['end']]);
$rows = $stmt->fetchAll();

// ===== Query prev FY (untuk compare) =====
$rows_prev = [];
if ($prev_fy_info) {
    $stmt2 = $db->prepare("
        SELECT DATE_FORMAT(periode, '%b %Y') AS label,
               section, reject_inhouse, reject_target, customer_claim, claim_target
        FROM kpi_quality
        WHERE periode BETWEEN ? AND ?
        ORDER BY periode ASC, section ASC
    ");
    $stmt2->execute([$prev_fy_info['start'], $prev_fy_info['end']]);
    $rows_prev = $stmt2->fetchAll();
}

$sections = ['MS1', 'MS2', 'Conrod', 'HDE'];

// ===== Build labels dari current FY =====
// Paksa urutan Apr–Mar supaya konsisten
$month_order = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$fy_year     = (int) str_replace('fy', '', $cur_fy_key);
$labels = [];
for ($m = 4; $m <= 12; $m++) {
    $labels[] = date('M Y', mktime(0,0,0,$m,1,$fy_year));
}
for ($m = 1; $m <= 3; $m++) {
    $labels[] = date('M Y', mktime(0,0,0,$m,1,$fy_year+1));
}

// ===== Helper build data per section =====
function buildQualitySection(array $rows, array $labels, array $sections): array {
    $data = [];
    foreach ($sections as $section) {
        $reject_inhouse = []; $reject_target = [];
        $customer_claim = []; $claim_target  = [];
        foreach ($labels as $label) {
            $found = false;
            foreach ($rows as $row) {
                if ($row['label'] === $label && $row['section'] === $section) {
                    $reject_inhouse[] = $row['reject_inhouse'] !== null ? (float)$row['reject_inhouse'] : null;
                    $reject_target[]  = $row['reject_target']  !== null ? (float)$row['reject_target']  : null;
                    $customer_claim[] = $row['customer_claim'] !== null ? (float)$row['customer_claim'] : null;
                    $claim_target[]   = $row['claim_target']   !== null ? (float)$row['claim_target']   : null;
                    $found = true; break;
                }
            }
            if (!$found) {
                $reject_inhouse[] = null; $reject_target[]  = null;
                $customer_claim[] = null; $claim_target[]   = null;
            }
        }
        $data[$section] = [
            'reject_inhouse' => $reject_inhouse,
            'reject_target'  => $reject_target,
            'customer_claim' => $customer_claim,
            'claim_target'   => $claim_target,
            'no_target'      => false,  // ← ALL sections show target now
        ];
    }
    return $data;
}

// Prev labels (Apr–Mar FY sebelumnya)
$prev_fy_year = $fy_year - 1;
$labels_prev  = [];
for ($m = 4; $m <= 12; $m++) $labels_prev[] = date('M Y', mktime(0,0,0,$m,1,$prev_fy_year));
for ($m = 1; $m <= 3;  $m++) $labels_prev[] = date('M Y', mktime(0,0,0,$m,1,$prev_fy_year+1));

$data      = buildQualitySection($rows,      $labels,      $sections);
$data_prev = $prev_fy_info
           ? buildQualitySection($rows_prev, $labels_prev, $sections)
           : null;

// Cek apakah ada data prev
$has_prev = false;
if ($data_prev) {
    foreach ($sections as $s) {
        $vals = array_filter($data_prev[$s]['reject_inhouse'] ?? [], fn($v) => $v !== null);
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