<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Proxy same-origin untuk API productivity (HTTP) agar tidak kena blokir
// mixed-content saat dashboard diakses via HTTPS. Browser hanya bicara ke
// dashboard ini; PHP (server-side) yang mengambil API HTTP di bawah.
$base = 'http://productivity-ms.yadin.com/api/public_api.php';
$qs   = $_SERVER['QUERY_STRING'] ?? '';
$url  = $base . ($qs !== '' ? '?' . $qs : '');

$ctx = stream_context_create([
    'http' => ['timeout' => 8, 'ignore_errors' => true],
]);

$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Gagal mengambil data productivity dari server upstream',
    ]);
    exit;
}

echo $response;
