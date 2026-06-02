<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

$tahun = $_GET['tahun'] ?? null;

$where  = "WHERE 1=1";
$params = [];

if ($tahun) {
    $where .= " AND tahun = :tahun";
    $params[':tahun'] = $tahun;
}

// List semua materi
$stmt = $db->prepare("
    SELECT id, tahun, judul_materi, peserta, departemen,
           peringkat, foto, deskripsi
    FROM ywk_event
    $where
    ORDER BY tahun DESC, peringkat ASC
");
$stmt->execute($params);
$list = $stmt->fetchAll();

// Available years
$stmt2 = $db->query("
    SELECT DISTINCT tahun
    FROM ywk_event
    ORDER BY tahun DESC
");
$years = array_column($stmt2->fetchAll(), 'tahun');

// Summary
$stmt3 = $db->prepare("
    SELECT
        COUNT(*)            AS total_materi,
        COUNT(DISTINCT departemen) AS total_dept
    FROM ywk_event
    $where
");
$stmt3->execute($params);
$summary = $stmt3->fetch();

// Top winner (peringkat = 1)
$stmt4 = $db->prepare("
    SELECT judul_materi, peserta, departemen, foto
    FROM ywk_event
    $where AND peringkat = 1
    ORDER BY tahun DESC
    LIMIT 1
");

// Fix: rebuild where for peringkat filter
$where2  = "WHERE peringkat = 1";
$params2 = [];
if ($tahun) {
    $where2 .= " AND tahun = :tahun";
    $params2[':tahun'] = $tahun;
}

$stmt4 = $db->prepare("
    SELECT judul_materi, peserta, departemen, foto
    FROM ywk_event
    $where2
    ORDER BY tahun DESC
    LIMIT 1
");
$stmt4->execute($params2);
$winner = $stmt4->fetch();

echo json_encode([
    'list'          => $list,
    'years'         => $years,
    'total_materi'  => (int) ($summary['total_materi'] ?? 0),
    'total_dept'    => (int) ($summary['total_dept']   ?? 0),
    'winner'        => $winner ?? null
]);
?>