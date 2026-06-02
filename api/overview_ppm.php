<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

$year = $_GET['year'] ?? 'all';

$where  = "WHERE 1=1";
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
        ROUND(AVG(ppm_actual), 1) AS avg_actual,
        ROUND(AVG(ppm_target), 1) AS avg_target
    FROM ppm_data
    $where
    GROUP BY periode
    ORDER BY periode ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$labels = $actual = $target = [];

foreach ($rows as $row) {
    $labels[] = $row['label'];
    $actual[] = (float) $row['avg_actual'];
    $target[] = (float) $row['avg_target'];
}

$vals = array_filter($actual, fn($v) => $v !== null && $v > 0);
$summary = [
    'latest' => count($actual) ? end($actual) : null,
    'avg'    => count($vals)   ? round(array_sum($vals) / count($vals), 1) : null,
    'best'   => count($vals)   ? min($vals) : null,
    'worst'  => count($vals)   ? max($vals) : null
];

echo json_encode([
    'labels'  => $labels,
    'actual'  => $actual,
    'target'  => $target,
    'summary' => $summary
]);
?>