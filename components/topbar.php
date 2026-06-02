<?php
$is_detail  = isset($is_detail) && $is_detail === true;
$page_title = $page_title ?? 'YWK DASHBOARD';
$year       = $_GET['year'] ?? 'all';
?>
<header class="topbar">
    <div class="topbar-inner">
        <div style="display:flex; align-items:center; gap:14px;">
            <?php if ($is_detail): ?>
                <a href="../index.php" class="back-btn">← Back</a>
            <?php endif; ?>

            <!-- Logo -->
            <img src="<?= $is_detail ? '../' : '' ?>assets/img/yanmar-logo.png"
                 alt="Yanmar"
                 style="height:30px; width:auto;">

            <!-- Divider -->
            <div style="width:1px; height:30px;
                        background:rgba(255,255,255,0.25);"></div>

            <!-- Title -->
            <div>
                <div class="topbar-title"><?= $page_title ?></div>
                <div class="topbar-subtitle">Manufacturing YWK Dashboard</div>
            </div>
        </div>

        <div class="topbar-controls">
            <label>Year:</label>
            <select id="filterYear" onchange="applyFilter()">
                <option value="all"    <?= $year==='all'    ? 'selected' : '' ?>>All Year</option>
                <option value="fy2025" <?= $year==='fy2025' ? 'selected' : '' ?>>FY2025</option>
                <option value="fy2026" <?= $year==='fy2026' ? 'selected' : '' ?>>FY2026</option>
            </select>
        </div>
    </div>

    <?php if (!$is_detail): ?>
    <div class="kpi-cards-hero">
        <div class="kpi-glass">
            <div class="kpi-glass-label">Operation Ratio</div>
            <div class="kpi-glass-value" id="kpi-or-actual">—<span>%</span></div>
            <div class="kpi-glass-sub" id="kpi-or-sub">Loading...</div>
        </div>
        <div class="kpi-glass">
            <div class="kpi-glass-label">Safety</div>
            <div class="kpi-glass-value" id="kpi-sf-actual">
                —<span style="font-size:13px;"> accident</span>
            </div>
            <div class="kpi-glass-sub" id="kpi-sf-sub">Loading...</div>
        </div>
        <div class="kpi-glass">
            <div class="kpi-glass-label">Quality (PPM avg)</div>
            <div class="kpi-glass-value" id="kpi-ql-actual">—</div>
            <div class="kpi-glass-sub" id="kpi-ql-sub">Loading...</div>
        </div>
        <div class="kpi-glass">
            <div class="kpi-glass-label">F-Cost</div>
            <div class="kpi-glass-value" id="kpi-fc-actual">—</div>
            <div class="kpi-glass-sub" id="kpi-fc-sub">Loading...</div>
        </div>
    </div>
    <?php endif; ?>

</header>

<script>
function applyFilter() {
    const year     = document.getElementById('filterYear').value;
    const base     = window.location.pathname;
    const existing = new URLSearchParams(window.location.search);
    const params   = new URLSearchParams();

    existing.forEach((val, key) => {
        if (key !== 'year' && key !== 'month') {
            params.set(key, val);
        }
    });

    if (year !== 'all') params.set('year', year);

    const qs = params.toString();
    window.location.href = base + (qs ? '?' + qs : '');
}
</script>