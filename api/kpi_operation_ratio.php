<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$year = $_GET['year'] ?? 'all';

$sections = ['MS1', 'MS2', 'Conrod', 'HDE'];

function getORRows($db, $fyRange) {
    if (!$fyRange) return [];
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(periode, '%b %Y') AS label,
            periode,
            section,
            actual,
            target
        FROM kpi_operation_ratio
        WHERE (actual IS NOT NULL OR target IS NOT NULL)
          AND periode BETWEEN :fy_start AND :fy_end
        ORDER BY periode ASC, section ASC
    ");
    $stmt->execute([':fy_start' => $fyRange['start'], ':fy_end' => $fyRange['end']]);
    return $stmt->fetchAll();
}

function remapToMonths($rows, $sections) {
    $month_map = [4=>0,5=>1,6=>2,7=>3,8=>4,9=>5,10=>6,11=>7,12=>8,1=>9,2=>10,3=>11];
    $data = [];
    foreach ($sections as $sec) {
        $actual = array_fill(0, 12, null);
        $target = array_fill(0, 12, null);
        foreach ($rows as $row) {
            if ($row['section'] !== $sec) continue;
            $m   = (int) date('n', strtotime($row['periode']));
            $idx = $month_map[$m] ?? null;
            if ($idx === null) continue;
            $actual[$idx] = $row['actual'] !== null ? (float) $row['actual'] : null;
            $target[$idx] = $row['target'] !== null ? (float) $row['target'] : null;
        }
        $data[$sec] = ['actual' => $actual, 'target' => $target];
    }
    return $data;
}

function buildDataWithLabels($rows, $sections) {
    $labels = [];
    foreach ($rows as $row) {
        if (!in_array($row['label'], $labels)) $labels[] = $row['label'];
    }
    $data = [];
    foreach ($sections as $sec) {
        $actual = []; $target = [];
        foreach ($labels as $label) {
            $found = false;
            foreach ($rows as $row) {
                if ($row['label'] === $label && $row['section'] === $sec) {
                    $actual[] = $row['actual'] !== null ? (float) $row['actual'] : null;
                    $target[] = $row['target'] !== null ? (float) $row['target'] : null;
                    $found = true; break;
                }
            }
            if (!$found) { $actual[] = null; $target[] = null; }
        }
        $data[$sec] = ['actual' => $actual, 'target' => $target];
    }
    return ['labels' => $labels, 'data' => $data];
}

$month_labels = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$compareFY    = in_array($year, ['all', 'fy2026', '']);

if ($compareFY) {
    $rows25  = getORRows($db, getFYRange('fy2025'));
    $rows26  = getORRows($db, getFYRange('fy2026'));
    $data25  = remapToMonths($rows25, $sections);
    $data26  = remapToMonths($rows26, $sections);

    echo json_encode([
        'labels'    => $month_labels,
        'data'      => $data26,
        'data_prev' => $data25,
        'compare'   => true,
        'cur_fy'    => 'FY2026',
        'prev_fy'   => 'FY2025',
    ]);
} else {
    $result = buildDataWithLabels(getORRows($db, getFYRange($year)), $sections);
    echo json_encode([
        'labels'  => $result['labels'],
        'data'    => $result['data'],
        'compare' => false,
    ]);
}
?>