<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$year = $_GET['year'] ?? 'all';

$sections     = ['MS1', 'MS2', 'Conrod', 'HDE'];
$month_labels = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$month_map    = [4=>0,5=>1,6=>2,7=>3,8=>4,9=>5,10=>6,11=>7,12=>8,1=>9,2=>10,3=>11];

function getSafetyRows($db, $fyRange) {
    if (!$fyRange) return [];
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(periode, '%b %Y') AS label,
               periode, section, minor, significant, fatality, target
        FROM kpi_safety
        WHERE periode BETWEEN :fy_start AND :fy_end
        ORDER BY periode ASC, section ASC
    ");
    $stmt->execute([':fy_start' => $fyRange['start'], ':fy_end' => $fyRange['end']]);
    return $stmt->fetchAll();
}

function remapSafetyToMonths($rows, $sections, $month_map) {
    $data = [];
    foreach ($sections as $sec) {
        $minor = $significant = $fatality = $target = array_fill(0, 12, null);
        foreach ($rows as $row) {
            if ($row['section'] !== $sec) continue;
            $m   = (int) date('n', strtotime($row['periode']));
            $idx = $month_map[$m] ?? null;
            if ($idx === null) continue;
            $minor[$idx]       = $row['minor']       !== null ? (int)$row['minor']       : null;
            $significant[$idx] = $row['significant'] !== null ? (int)$row['significant'] : null;
            $fatality[$idx]    = $row['fatality']    !== null ? (int)$row['fatality']    : null;
            $target[$idx]      = $row['target']      !== null ? (int)$row['target']      : null;
        }
        $data[$sec] = [
            'minor'       => $minor,
            'significant' => $significant,
            'fatality'    => $fatality,
            'target'      => $target,
        ];
    }
    return $data;
}

$compareFY = in_array($year, ['all', 'fy2026', '']);

if ($compareFY) {
    $rows26 = getSafetyRows($db, getFYRange('fy2026'));
    $rows25 = getSafetyRows($db, getFYRange('fy2025'));
    $data26 = remapSafetyToMonths($rows26, $sections, $month_map);
    $data25 = remapSafetyToMonths($rows25, $sections, $month_map);

    echo json_encode([
        'labels'    => $month_labels,
        'data'      => $data26,
        'data_prev' => $data25,
        'compare'   => true,
        'cur_fy'    => 'FY2026',
        'prev_fy'   => 'FY2025',
    ]);
} else {
    $fyRange = getFYRange($year);
    $rows    = getSafetyRows($db, $fyRange);
    $data    = remapSafetyToMonths($rows, $sections, $month_map);

    // Buat labels dari data yang ada
    $labels = [];
    foreach ($rows as $row) {
        if (!in_array($row['label'], $labels)) $labels[] = $row['label'];
    }

    echo json_encode([
        'labels'  => $month_labels,
        'data'    => $data,
        'compare' => false,
    ]);
}
?>