<?php
define('ADMIN_SESSION_KEY', 'ywk_admin');
define('ADMIN_DB_HOST', 'db.yadin.com');
define('ADMIN_DB_NAME', 'ywk_dashboard');
define('ADMIN_DB_USER', 'sintiara');
define('ADMIN_DB_PASS', 'Yadin.456');

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
        header('Location: /admin/login.php');
        exit;
    }
}