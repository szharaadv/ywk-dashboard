<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dept  = isset($_GET['dept'])  ? $_GET['dept']  : '';
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 8;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 2025;

$url = "http://kaizen-dashboard.yadin.com/get_detail_karyawan.php"
     . "?dept=" . urlencode($dept)
     . "&bulan={$bulan}&tahun={$tahun}";

$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode(['error' => 'Failed to fetch data']);
    exit;
}

echo $response;
?>