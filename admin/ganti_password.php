<?php
session_start();
require_once 'config.php';
requireAdminLogin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama       = trim($_POST['password_lama']       ?? '');
    $password_baru       = trim($_POST['password_baru']       ?? '');
    $password_konfirmasi = trim($_POST['password_konfirmasi'] ?? '');

    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = 'Semua field harus diisi.';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = 'Password baru dan konfirmasi tidak cocok.';
    } else {
        $db   = getAdminDB();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION[ADMIN_SESSION_KEY]['id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password_lama, $user['password'])) {
            $error = 'Password lama salah.';
        } else {
            $hash  = password_hash($password_baru, PASSWORD_BCRYPT);
            $stmt2 = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt2->execute([$hash, $user['id']]);
            $success = 'Password berhasil diganti!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ganti Password — YWK Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 14px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-title { font-size: 16px; font-weight: 700; color: #fff; }
        .topbar-sub   { font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 2px; }
        .back-btn {
            font-size: 12px; color: rgba(255,255,255,0.8);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 5px 12px; border-radius: 6px;
        }
        .back-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }

        .content {
            padding: 1.5rem;
            display: flex; justify-content: center;
        }
        .box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            width: 100%; max-width: 400px;
        }
        .box-title {
            font-size: 15px; font-weight: 700;
            color: #1a1a1a; margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .form-label {
            font-size: 12px; font-weight: 600;
            color: #374151; margin-bottom: 4px; display: block;
        }
        .form-control {
            width: 100%; padding: 9px 12px;
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 13px; margin-bottom: 12px; outline: none;
        }
        .form-control:focus { border-color: #D0021B; }
        .btn {
            width: 100%; padding: 10px;
            background: #D0021B; color: #fff;
            border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn:hover { background: #7B0000; }
        .alert-error {
            background: #FDECEA; color: #7B0000;
            font-size: 12px; padding: 8px 12px;
            border-radius: 6px; margin-bottom: 12px;
        }
        .alert-success {
            background: #ECFDF5; color: #065f46;
            font-size: 12px; padding: 8px 12px;
            border-radius: 6px; margin-bottom: 12px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-title">Ganti Password</div>
        <div class="topbar-sub">
            Logged in as: <?= htmlspecialchars($_SESSION[ADMIN_SESSION_KEY]['name']) ?>
        </div>
    </div>
    <a href="index.php" class="back-btn">← Kembali</a>
</div>

<div class="content">
    <div class="box">
        <div class="box-title">🔑 Ganti Password Admin</div>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="form-label">Password Lama</label>
            <input type="password" name="password_lama"
                   class="form-control" placeholder="••••••••" required>

            <label class="form-label">Password Baru</label>
            <input type="password" name="password_baru"
                   class="form-control" placeholder="Min. 6 karakter" required>

            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="password_konfirmasi"
                   class="form-control" placeholder="Ulangi password baru" required>

            <button type="submit" class="btn">Ganti Password</button>
        </form>
    </div>
</div>
</body>
</html>