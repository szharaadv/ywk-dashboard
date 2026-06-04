<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
requireAdminLogin();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="kaizen_template.csv"');
header('Cache-Control: max-age=0');

// BOM untuk Excel bisa baca UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['Kaizen Theme', 'Category', 'Section', 'Purposed', 'No. IP', 'Score', 'Status']);

// Contoh data
fputcsv($out, [
    'Membuat Tempat untuk Part yang sedang Check Quality',
    'Quality', 'QC Incoming', 'Daffa', '1/YWK/536/12/25', '5', 'Implemented'
]);
fputcsv($out, [
    'Tabel Std Install Kabel Alternator',
    'Productivity', 'Fabrikasi', 'Iskandar', '2/YWK/537/8/25', '7', 'Implemented'
]);

// Baris kosong untuk diisi
for ($i = 0; $i < 10; $i++) {
    fputcsv($out, ['', '', '', '', '', '', '']);
}

fclose($out);
exit;