<?php
session_start();
require_once '../config.php';
requireAdminLogin();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ito_template.csv"');
header('Cache-Control: max-age=0');

// BOM supaya Excel membaca UTF-8 dengan benar
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header
fputcsv($out, ['Periode (YYYY-MM)', 'ITO Days', 'Inventory Amount']);

// Contoh data — Periode format YYYY-MM, satu baris per bulan (boleh lintas tahun, mulai 2022)
fputcsv($out, ['2022-04', '85', '70']);
fputcsv($out, ['2022-05', '83', '72']);
fputcsv($out, ['2022-06', '80', '71']);

// Baris kosong untuk diisi
for ($i = 0; $i < 20; $i++) {
    fputcsv($out, ['', '', '']);
}

fclose($out);
exit;
