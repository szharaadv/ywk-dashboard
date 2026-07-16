<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="kaizen_template.csv"');
header('Cache-Control: max-age=0');

// BOM untuk Excel bisa baca UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header row (1 baris rata — 12 kolom)
fputcsv($out, [
    'NO', 'NO IP', 'NAMA PEMBUAT', 'IDE YANG DIUSULKAN',
    'Productivity', 'Cost Down', 'Quality', 'Safety', '3S-3T',
    'DEPT', 'Realisasi', 'TOTAL SCORE'
]);

// Contoh data
// Beri tanda "V" pada kolom kategori yang sesuai (boleh lebih dari satu).
// Kolom "Realisasi" = status (Implemented / Approved / Open).
fputcsv($out, [
    '1', '1/YWK/536/12/25', 'Daffa', 'Membuat Tempat untuk Part yang sedang Check Quality',
    '', '', 'V', 'V', '',
    'QC Incoming', 'Implemented', '5'
]);
fputcsv($out, [
    '2', '2/YWK/537/8/25', 'Iskandar', 'Tabel Std Install Kabel Alternator',
    'V', 'V', '', '', 'V',
    'Fabrikasi', 'Implemented', '7'
]);

// Baris kosong untuk diisi
for ($i = 0; $i < 10; $i++) {
    fputcsv($out, array_fill(0, 12, ''));
}

fclose($out);
exit;