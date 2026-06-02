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
        periode,
        ROUND(AVG(CASE WHEN section IN ('MS1','MS2') THEN actual END), 1) AS machining_actual,
        ROUND(AVG(CASE WHEN section IN ('MS1','MS2') THEN target END), 1) AS machining_target,
        ROUND(AVG(CASE WHEN section = 'Conrod'       THEN actual END), 1) AS assembling_actual,
        ROUND(AVG(CASE WHEN section = 'Conrod'       THEN target END), 1) AS assembling_target
    FROM kpi_operation_ratio
    $where
    GROUP BY periode
    ORDER BY periode ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$labels = $machining_actual = $machining_target = $assembling_actual = $assembling_target = [];

foreach ($rows as $row) {
    $labels[]            = $row['label'];
    $machining_actual[]  = $row['machining_actual']  !== null ? (float) $row['machining_actual']  : null;
    $machining_target[]  = $row['machining_target']  !== null ? (float) $row['machining_target']  : null;
    $assembling_actual[] = $row['assembling_actual'] !== null ? (float) $row['assembling_actual'] : null;
    $assembling_target[] = $row['assembling_target'] !== null ? (float) $row['assembling_target'] : null;
}

echo json_encode([
    'labels'     => $labels,
    'machining'  => ['actual' => $machining_actual,  'target' => $machining_target],
    'assembling' => ['actual' => $assembling_actual, 'target' => $assembling_target]
]);
?>