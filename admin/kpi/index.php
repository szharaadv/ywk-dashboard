<?php
session_start();
require_once '../config.php';
requireAdminLogin();
require_once __DIR__ . '/../../config/db.php';
$db = getDB();

$total_or  = $db->query("SELECT COUNT(*) FROM kpi_operation_ratio WHERE YEAR(periode) >= 2026")->fetchColumn();
$total_ito = $db->query("SELECT COUNT(*) FROM kpi_ito")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KPI Input — Admin YWK</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .topbar {
            background: linear-gradient(135deg, #7B0000 0%, #D0021B 100%);
            padding: 12px 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display:flex; align-items:center; gap:12px; }
        .back-btn { font-size:12px; color:rgba(255,255,255,0.75); text-decoration:none; }
        .back-btn:hover { color:#fff; }
        .topbar-title { font-size:15px; font-weight:700; color:#fff; }
        .content { padding:1.5rem; display:flex; flex-direction:column; gap:1rem; }
        .menu-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .menu-card {
            background:#fff; border:1px solid #e5e7eb; border-radius:12px;
            padding:1.5rem; text-decoration:none; display:flex;
            align-items:flex-start; gap:1rem; border-top:3px solid #D0021B;
            transition:box-shadow 0.15s;
        }
        .menu-card:hover { box-shadow:0 2px 8px rgba(208,2,27,0.1); }
        .menu-icon {
            width:44px; height:44px; background:#FDECEA; border-radius:10px;
            display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;
        }
        .menu-title { font-size:14px; font-weight:700; color:#1a1a1a; margin-bottom:4px; }
        .menu-sub   { font-size:12px; color:#6b7280; }
        .stat-badge {
            display:inline-block; margin-top:8px; font-size:11px; font-weight:700;
            background:#FDECEA; color:#D0021B; padding:2px 8px; border-radius:20px;
        }
        .info-card {
            background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem;
        }
        .info-title {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;
        }
        .info-row {
            display:flex; justify-content:space-between; align-items:center;
            font-size:12px; color:#374151; padding:6px 0; border-bottom:1px solid #f0f0f0;
        }
        .info-row:last-child { border-bottom:none; }
        .info-val { font-weight:600; color:#1a1a1a; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-left">
        <a href="../index.php" class="back-btn">← Back</a>
        <div class="topbar-title">KPI Data Input</div>
    </div>
</div>

<div class="content">
    <div class="menu-grid">
        <a href="or.php" class="menu-card">
            <div class="menu-icon">📊</div>
            <div>
                <div class="menu-title">Operation Ratio</div>
                <div class="menu-sub">Input data OR per section per bulan</div>
                <div class="stat-badge"><?= $total_or ?> data FY2026</div>
            </div>
        </a>
        <a href="safety.php" class="menu-card">
            <div class="menu-icon">🛡️</div>
            <div>
                <div class="menu-title">Safety</div>
                <div class="menu-sub">Input data safety per section per bulan</div>
                <div class="stat-badge">Input Safety</div>
            </div>
        </a>
        <!-- F-Cost -->
        <a href="fcost.php" class="menu-card">
            <div class="menu-icon">💰</div>
            <div>
                <div class="menu-title">Failure Cost</div>
                <div class="menu-sub">Input data F-Cost per section per bulan</div>
                <div class="stat-badge">Input F-Cost</div>
            </div>
        </a>

        <!-- Quality -->
        <a href="quality.php" class="menu-card">
            <div class="menu-icon">🔍</div>
            <div>
                <div class="menu-title">Quality</div>
                <div class="menu-sub">Input data Quality per section per bulan</div>
                <div class="stat-badge">Input Quality</div>
            </div>
        </a>

        <!-- ITO -->
        <a href="ito.php" class="menu-card">
            <div class="menu-icon">📦</div>
            <div>
                <div class="menu-title">ITO — Inventory Turn Over</div>
                <div class="menu-sub">Input ITO Days &amp; Inventory Amount per bulan</div>
                <div class="stat-badge"><?= $total_ito ?> data</div>
            </div>
        </a>
    </div>

    <div class="info-card">
        <div class="info-title">Info</div>
        <div class="info-row">
            <span>Format Periode</span>
            <span class="info-val">FY2026 = Apr 2026 – Mar 2027</span>
        </div>
        <div class="info-row">
            <span>Sections</span>
            <span class="info-val">MS1, MS2, Conrod, HDE</span>
        </div>
        <div class="info-row">
            <span>Dashboard URL</span>
            <span class="info-val">
                <a href="/index.php" style="color:#D0021B;" target="_blank">
                    Buka Dashboard →
                </a>
            </span>
        </div>
    </div>
</div>
</body>
</html>