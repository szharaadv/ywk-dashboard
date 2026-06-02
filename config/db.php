<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ywk_dashboard');

// Helper: convert FY param ke range tanggal
function getFYRange($fy) {
    if ($fy === 'fy2025') {
        return ['start' => '2025-04-01', 'end' => '2026-03-31'];
    }
    if ($fy === 'fy2026') {
        return ['start' => '2026-04-01', 'end' => '2027-03-31'];
    }
    return null;
}


function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}
?>