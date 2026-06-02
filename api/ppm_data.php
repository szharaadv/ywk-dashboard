<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

$year = $_GET['year'] ?? 'all';

$where  = "WHERE section != 'HDE'";
$params = [];

$fyRange = getFYRange($year);
if ($fyRange) {
    $where .= " AND periode BETWEEN :fy_start AND :fy_end";
    $params[':fy_start'] = $fyRange['start'];
    $params[':fy_end']   = $fyRange['end'];
}

$stmt = $db->prepare("
    SELECT
        DATE_FORMAT(periode, '%b %Y') AS label,
        section,
        ppm_actual,
        ppm_target
    FROM ppm_data
    $where
    ORDER BY periode ASC, section ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sections = ['MS1', 'MS2', 'Conrod', 'HDE'];
$labels   = [];

foreach ($rows as $row) {
    if (!in_array($row['label'], $labels)) {
        $labels[] = $row['label'];
    }
}

$data = [];
foreach ($sections as $sec) {
    $actual = [];
    $target = [];
    foreach ($labels as $label) {
        $found = false;
        foreach ($rows as $row) {
            if ($row['label'] === $label && $row['section'] === $sec) {
                $actual[] = (float) $row['ppm_actual'];
                $target[] = (float) $row['ppm_target'];
                $found    = true;
                break;
            }
        }
        if (!$found) {
            $actual[] = null;
            $target[] = null;
        }
    }
    $data[$sec] = ['actual' => $actual, 'target' => $target];
}

$summary = [];
foreach ($sections as $sec) {
    $vals = array_filter($data[$sec]['actual'], fn($v) => $v !== null);
    $summary[$sec] = count($vals) > 0 ? [
        'latest' => end($data[$sec]['actual']),
        'avg'    => round(array_sum($vals) / count($vals), 1),
        'best'   => min($vals),
        'worst'  => max($vals)
    ] : null;
}

echo json_encode([
    'labels'  => $labels,
    'data'    => $data,
    'summary' => $summary
]);
?>