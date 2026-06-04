<?php
session_start();
require_once 'config.php';
requireAdminLogin();
require_once __DIR__ . '/../config/db.php';

$db           = getDB();
$total_slides = count(array_filter(glob($_SERVER['DOCUMENT_ROOT'] . '/ywk-dashboard/assets/ppm-slides/slide-*') ?: [], fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])));
$total_event  = $db->query("SELECT COUNT(*) FROM ywk_event")->fetchColumn();
$total_kaizen = $db->query("SELECT COUNT(*) FROM kaizen_sheet")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin — YWK Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 14px 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-title { font-size: 16px; font-weight: 700; color: #fff; }
        .topbar-sub   { font-size: 11px; color: rgba(255,255,255,0.6); margin-top:2px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .logout-btn {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 5px 12px;
            border-radius: 6px;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .pwd-btn {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            background: rgba(255,255,255,0.15);
            padding: 5px 12px;
            border-radius: 6px;
        }
        .pwd-btn:hover { background: rgba(255,255,255,0.25); color: #fff; }

        .content { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .menu-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: border-color 0.15s;
            border-top: 3px solid #D0021B;
        }
        .menu-card:hover { border-color: #D0021B; box-shadow: 0 2px 8px rgba(208,2,27,0.1); }

        .menu-icon {
            width: 44px; height: 44px;
            background: #FDECEA;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .menu-title { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
        .menu-sub   { font-size: 12px; color: #6b7280; }
        .stat-badge {
            display: inline-block; margin-top: 8px;
            font-size: 11px; font-weight: 700;
            background: #FDECEA; color: #D0021B;
            padding: 2px 8px; border-radius: 20px;
        }

        .info-card {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 12px; padding: 1rem 1.25rem;
        }
        .info-title {
            font-size: 11px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;
        }
        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; color: #374151;
            padding: 6px 0; border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-val { font-weight: 600; color: #1a1a1a; }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-title">YWK Admin Panel</div>
        <div class="topbar-sub">
            Logged in as: <?= htmlspecialchars($_SESSION[ADMIN_SESSION_KEY]['name']) ?>
        </div>
    </div>
    <div class="topbar-right">
        <a href="ganti_password.php" class="pwd-btn">🔑 Ganti Password</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="content">

    <div class="menu-grid">
        <a href="ppm/index.php" class="menu-card">
            <div class="menu-icon">🖼️</div>
            <div>
                <div class="menu-title">PPM Slides</div>
                <div class="menu-sub">Upload & replace slide JPG untuk PPM Performance panel</div>
                <div class="stat-badge"><?= $total_slides ?> slide aktif</div>
            </div>
        </a>
        <a href="kpi/index.php" class="menu-card">
            <div class="menu-icon">📈</div>
            <div>
                <div class="menu-title">KPI Data Input</div>
                <div class="menu-sub">Input data OR, Safety, F-Cost, Quality per section per bulan</div>
                <div class="stat-badge">FY2026</div>
            </div>
        </a>
        <a href="event/index.php" class="menu-card">
            <div class="menu-icon">🏆</div>
            <div>
                <div class="menu-title">YWK Event</div>
                <div class="menu-sub">Kelola data event, pemenang, dan foto-foto YWK</div>
                <div class="stat-badge"><?= $total_event ?> materi</div>
            </div>
        </a>
        <a href="kaizen/index.php" class="menu-card">
    <div class="menu-icon">💡</div>
    <div>
        <div class="menu-title">Kaizen Sheet</div>
        <div class="menu-sub">
            Upload data kaizen via Excel & kelola kaizen sheet
        </div>
        <div class="stat-badge">
            <?php
            $total_kaizen = $db->query("SELECT COUNT(*) FROM kaizen_sheet")->fetchColumn();
            echo $total_kaizen;
            ?> kaizen
        </div>
    </div>
</a>
    </div>

    <div class="info-card">
        <div class="info-title">Info Sistem</div>
        <div class="info-row">
            <span>PPM Slides folder</span>
            <span class="info-val">/assets/ppm-slides/</span>
        </div>
        <div class="info-row">
            <span>Event foto folder</span>
            <span class="info-val">/assets/img/</span>
        </div>
        <div class="info-row">
            <span>Total PPM Slides</span>
            <span class="info-val"><?= $total_slides ?> slide</span>
        </div>
        <div class="info-row">
            <span>Total Event Data</span>
            <span class="info-val"><?= $total_event ?> materi</span>
        </div>
        <div class="info-row">
            <span>Total Kaizen</span>
            <span class="info-val"><?= $total_kaizen ?> kaizen</span>
        </div>
        <div class="info-row">
            <span>Dashboard URL</span>
            <span class="info-val">
                <a href="/ywk-dashboard/index.php" style="color:#D0021B;" target="_blank">
                    Buka Dashboard →
                </a>
            </span>
        </div>
        <div class="info-row">
            <span>Admin Panel URL</span>
            <span class="info-val">/ywk-dashboard/admin/</span>
        </div>
    </div>

</div>
</body>
</html>