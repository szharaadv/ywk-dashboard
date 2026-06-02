<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';
$db = getDB();

$section  = $_GET['section']  ?? 'all';
$status   = $_GET['status']   ?? 'all';
$category = $_GET['category'] ?? 'all';
$sort     = $_GET['sort']     ?? 'score';

$where  = "WHERE 1=1";
$params = [];

if ($section !== 'all') {
    $where .= " AND section = :section";
    $params[':section'] = $section;
}
if ($status !== 'all') {
    $where .= " AND status = :status";
    $params[':status'] = $status;
}
if ($category !== 'all') {
    $where .= " AND category = :category";
    $params[':category'] = $category;
}

$order = match($sort) {
    'score'   => 'score DESC',
    'tanggal' => 'created_at DESC',
    'title'   => 'judul ASC',
    default   => 'score DESC'
};

// List semua kaizen
$stmt = $db->prepare("
    SELECT id, judul, category, section, purposed, no_ip, score, status, created_at
    FROM kaizen_sheet
    $where
    ORDER BY $order
");
$stmt->execute($params);
$list = $stmt->fetchAll();

// Summary stats
$total = $db->query("SELECT COUNT(*) FROM kaizen_sheet")->fetchColumn();

$scores = $db->query("
    SELECT MAX(score) AS top_score, ROUND(AVG(score), 1) AS avg_score
    FROM kaizen_sheet
")->fetch();

$best_dept = $db->query("
    SELECT section, COUNT(*) AS cnt
    FROM kaizen_sheet
    GROUP BY section
    ORDER BY cnt DESC
    LIMIT 1
")->fetch();

$top_kaizen = $db->query("
    SELECT judul, section, purposed, score, category
    FROM kaizen_sheet
    ORDER BY score DESC
    LIMIT 1
")->fetch();

$category_dist = $db->query("
    SELECT category, COUNT(*) AS cnt
    FROM kaizen_sheet
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category
    ORDER BY cnt DESC
")->fetchAll();

// Mapping untuk kompatibilitas dengan frontend lama
$list = array_map(fn($k) => array_merge($k, [
    'pic'       => $k['purposed'],
    'nilai'     => $k['score'],
    'tanggal'   => $k['created_at'],
    'deskripsi' => $k['no_ip'],
]), $list);

if ($top_kaizen) {
    $top_kaizen['pic']   = $top_kaizen['purposed'];
    $top_kaizen['nilai'] = $top_kaizen['score'];
}

echo json_encode([
    'list'          => $list,
    'total'         => (int) $total,
    'top_score'     => $scores['top_score']  ?? null,
    'avg_score'     => $scores['avg_score']  ?? null,
    'best_dept'     => $best_dept['section'] ?? null,
    'top_kaizen'    => $top_kaizen           ?? null,
    'category_dist' => $category_dist
]);
?>