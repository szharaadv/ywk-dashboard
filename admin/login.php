<?php
session_start();
require_once 'config.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $db   = getAdminDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION[ADMIN_SESSION_KEY] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
        ];
        header('Location: index.php');
        exit;
    }

    $error = 'Username atau password salah.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login — YWK Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .box {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 2rem;
            width: 360px;
        }
        .brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .brand-title {
            font-size: 18px;
            font-weight: 700;
            color: #7B0000;
        }
        .brand-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 12px;
            outline: none;
        }
        .form-control:focus { border-color: #D0021B; }
        .btn {
            width: 100%;
            padding: 10px;
            background: #D0021B;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover { background: #7B0000; }
        .alert {
            background: #FDECEA;
            color: #7B0000;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="box">
    <div class="brand">
        <div class="brand-title">YWK Admin Panel</div>
        <div class="brand-sub">Dashboard Management System</div>
    </div>
    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control"
               placeholder="Username admin" required autofocus>
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" required>
        <button type="submit" class="btn">Login</button>
    </form>
</div>
</body>
</html>