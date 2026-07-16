<?php
define('ADMIN_SESSION_KEY', 'ywk_admin');
define('ADMIN_DB_HOST', 'localhost');
define('ADMIN_DB_NAME', 'ywk_dashboard');
define('ADMIN_DB_USER', 'root');
define('ADMIN_DB_PASS', '');

function getAdminDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . ADMIN_DB_HOST . ";dbname=" . ADMIN_DB_NAME . ";charset=utf8mb4",
            ADMIN_DB_USER,
            ADMIN_DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION_KEY]);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        // Bangun base URL folder /admin/ secara dinamis agar berfungsi
        // baik saat app di root (/admin/) maupun di subfolder (/ywk-dashboard/admin/),
        // serta dari halaman admin biasa maupun nested (admin/kpi/, admin/event/).
        $script = $_SERVER['SCRIPT_NAME'];
        $pos    = strpos($script, '/admin/');
        $base   = $pos !== false ? substr($script, 0, $pos + strlen('/admin/')) : '/admin/';
        header('Location: ' . $base . 'login.php');
        exit;
    }
}