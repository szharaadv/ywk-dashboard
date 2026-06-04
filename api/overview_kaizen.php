<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';
$db = getDB();

// Summary
$summary = $db->query("
    SELECT
        COUNT(*)              AS total,
        MAX(score)            AS top_score,
        ROUND(AVG(score), 1)  AS avg_score
    FROM kaizen_sheet
")->fetch();

// Best dept
$best_dept = $db->query("
    SELECT section, COUNT(*) AS cnt
    FROM kaizen_sheet
    GROUP BY section
    ORDER BY cnt DESC
    LIMIT 1
")->fetch();

// Top kaizen
$top_kaizen = $db->query("
    SELECT judul, section, purposed, score, status, category
    FROM kaizen_sheet
    ORDER BY score DESC
    LIMIT 1
")->fetch();

// All kaizen list
$top5 = $db->query("
    SELECT judul, section, purposed, score, status, category
    FROM kaizen_sheet
    ORDER BY score DESC
")->fetchAll();

// Mapping kompatibilitas frontend
if ($top_kaizen) {
    $top_kaizen['pic']   = $top_kaizen['purposed'];
    $top_kaizen['nilai'] = $top_kaizen['score'];
}

$top5 = array_map(fn($k) => array_merge($k, [
    'pic'   => $k['purposed'],
    'nilai' => $k['score'],
]), $top5);

echo json_encode([
    'total'      => (int) ($summary['total'] ?? 0),
    'top_score'  => $summary['top_score']    ?? null,
    'avg_score'  => $summary['avg_score']    ?? null,
    'best_dept'  => $best_dept['section']    ?? null,
    'top_kaizen' => $top_kaizen              ?? null,
    'top5'       => $top5
]);
?>