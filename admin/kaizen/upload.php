<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$mode = $_POST['mode'] ?? 'append';

if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak valid atau tidak diupload']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['excel']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Format file harus CSV. Simpan Excel sebagai CSV dulu.']);
    exit;
}

// Baca CSV
$rows   = [];
$handle = fopen($_FILES['excel']['tmp_name'], 'r');

if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Gagal membaca file']);
    exit;
}

// Skip header row
$header = fgetcsv($handle, 1000, ',');

// Coba deteksi delimiter — kadang Excel export pakai semicolon
if ($header && count($header) === 1) {
    rewind($handle);
    $header = fgetcsv($handle, 1000, ';');
    $delimiter = ';';
} else {
    $delimiter = ',';
}

while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
    if (count($data) >= 1 && !empty(trim($data[0] ?? ''))) {
        $rows[] = $data;
    }
}
fclose($handle);

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada data valid. Pastikan format CSV benar dan ada data di bawah header.']);
    exit;
}

$db = getDB();

// Replace: hapus semua data lama
if ($mode === 'replace') {
    $db->exec("DELETE FROM kaizen_sheet");
}

$stmt = $db->prepare("
    INSERT INTO kaizen_sheet (judul, category, section, purposed, no_ip, score, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$inserted = 0;
$errors   = [];

foreach ($rows as $i => $row) {
    $judul    = trim($row[0] ?? '');
    $category = trim($row[1] ?? '');
    $section  = trim($row[2] ?? '');
    $purposed = trim($row[3] ?? '');
    $no_ip    = trim($row[4] ?? '');
    $score    = is_numeric(trim($row[5] ?? '')) ? (float) trim($row[5]) : null;
    $status   = trim($row[6] ?? 'Implemented');

    if (empty($judul)) continue;

    try {
        $stmt->execute([$judul, $category, $section, $purposed, $no_ip, $score, $status]);
        $inserted++;
    } catch (Exception $e) {
        $errors[] = "Baris " . ($i + 2) . ": " . $e->getMessage();
    }
}

echo json_encode([
    'success'  => true,
    'inserted' => $inserted,
    'errors'   => $errors,
    'message'  => "$inserted data kaizen berhasil disimpan"
        . ($mode === 'replace' ? ' (data lama dihapus)' : ' (ditambahkan)')
]);