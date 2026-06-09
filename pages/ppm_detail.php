<?php
$is_detail  = true;
$page_title = 'PPM DETAIL';
$total_slides = count(array_filter(
    glob(__DIR__ . '/../assets/ppm-slides/slide-*') ?: [],
    fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])
)) ?: 1;
$current = max(1, min($total_slides, (int)($_GET['slide'] ?? 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <title>PPM Detail — YWK Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        ::-webkit-scrollbar { display: none; }
        html, body { height: 100vh; overflow: hidden; }
        .dashboard { height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .content-wrapper {
            padding: 0.4rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Info bar -->
        <div style="display:flex; align-items:center; justify-content:space-between;
                    flex-wrap:wrap; gap:8px; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:13px; font-weight:700; color:#1a3a5c;">
                    PPM Performance Slides
                </span>
                <span style="width:1px; height:14px; background:#e5e7eb; display:inline-block;"></span>
                <span style="font-size:11px; color:#6b7280;">
                    <?= date('F Y') ?> · Manufacturing QC YWK
                </span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:11px; color:#9ca3af;"><?= $total_slides ?> slides</span>
                <a href="/assets/ppm-slides/slide-<?= str_pad($current, 2, '0', STR_PAD_LEFT) ?>.jpg"
                   download
                   style="font-size:11px; color:#185FA5; text-decoration:none;
                          font-weight:500; background:#E6F1FB;
                          padding:4px 10px; border-radius:6px;">
                    ↓ Download
                </a>
            </div>
        </div>

        <!-- Main area: Slide viewer + Thumbnail -->
        <div style="display:grid; grid-template-columns:1fr 160px;
                    gap:0.4rem; flex:1; min-height:0; overflow:hidden;">

            <!-- Slide Viewer -->
            <div class="card" style="padding:0; overflow:hidden;
                         display:flex; flex-direction:column; min-height:0;">

                <!-- Slide Image -->
                <div style="flex:1; background:#fff; display:flex;
                            align-items:center; justify-content:center;
                            position:relative; min-height:0; overflow:hidden;">
                    <img id="mainSlide"
                         src="/ywk-dashboard/assets/ppm-slides/slide-<?= str_pad($current, 2, '0', STR_PAD_LEFT) ?>.jpg"
                         alt="PPM Slide <?= $current ?>"
                         style="max-width:100%; max-height:100%;
                                object-fit:contain; display:block;">

                    <!-- Prev button -->
                    <button onclick="goTo(current - 1)"
                            style="position:absolute; left:10px; top:50%;
                                   transform:translateY(-50%);
                                   background:rgba(26,58,92,0.7);
                                   color:#fff; border:none; border-radius:8px;
                                   width:36px; height:52px; font-size:18px;
                                   cursor:pointer; z-index:10;">
                        &#8592;
                    </button>

                    <!-- Next button -->
                    <button onclick="goTo(current + 1)"
                            style="position:absolute; right:10px; top:50%;
                                   transform:translateY(-50%);
                                   background:rgba(26,58,92,0.7);
                                   color:#fff; border:none; border-radius:8px;
                                   width:36px; height:52px; font-size:18px;
                                   cursor:pointer; z-index:10;">
                        &#8594;
                    </button>
                </div>

                <!-- Progress bar -->
                <div style="height:3px; background:#e5e7eb; flex-shrink:0;">
                    <div id="progressBar"
                         style="height:100%; background:#D0021B; border-radius:0;
                                transition:width 0.3s ease;
                                width:<?= round($current/$total_slides*100, 2) ?>%;">
                    </div>
                </div>

                <!-- Controls bar -->
                <div style="display:flex; align-items:center; justify-content:space-between;
                            padding:6px 1rem; border-top:1px solid #f0f0f0; flex-shrink:0;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button onclick="goTo(1)"
                                style="font-size:10px; padding:3px 8px;
                                       border:1px solid #e5e7eb; border-radius:5px;
                                       background:#f4f5f7; cursor:pointer; color:#6b7280;">
                            |◀ First
                        </button>
                        <button onclick="goTo(current - 1)" class="ppm-btn">◀ Prev</button>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:11px; color:#6b7280;">Slide</span>
                        <input type="number" id="slideInput"
                               value="<?= $current ?>" min="1" max="<?= $total_slides ?>"
                               onchange="goTo(parseInt(this.value))"
                               style="width:46px; font-size:11px; text-align:center;
                                      border:1px solid #e5e7eb; border-radius:5px;
                                      padding:3px 4px;">
                        <span style="font-size:11px; color:#6b7280;">of <?= $total_slides ?></span>
                    </div>

                    <div style="display:flex; align-items:center; gap:6px;">
                        <button onclick="goTo(current + 1)" class="ppm-btn">Next ▶</button>
                        <button onclick="goTo(<?= $total_slides ?>)"
                                style="font-size:10px; padding:3px 8px;
                                       border:1px solid #e5e7eb; border-radius:5px;
                                       background:#f4f5f7; cursor:pointer; color:#6b7280;">
                            Last ▶|
                        </button>
                    </div>
                </div>

            </div>

            <!-- Thumbnail Strip — vertikal di kanan -->
            <div style="overflow-y:auto; display:flex; flex-direction:column;
                        gap:5px; padding-right:2px;">
                <?php for ($i = 1; $i <= $total_slides; $i++):
                    $pad = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $isActive = $i === $current;
                ?>
                <div onclick="goTo(<?= $i ?>)"
                     id="thumb-<?= $i ?>"
                     style="cursor:pointer; border-radius:6px; overflow:hidden;
                            border:2px solid <?= $isActive ? '#D0021B' : '#e5e7eb' ?>;
                            flex-shrink:0; position:relative; transition:border-color 0.15s;">
                    <img src="/ywk-dashboard/assets/ppm-slides/slide-<?= $pad ?>.jpg"
                         alt="Slide <?= $i ?>"
                         style="width:100%; height:90px; object-fit:cover; display:block;">
                    <div style="position:absolute; bottom:0; left:0; right:0;
                                background:rgba(0,0,0,0.5);
                                font-size:9px; color:#fff;
                                text-align:center; padding:2px 0;">
                        <?= $i ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

        </div>

    </div>
</div>

<script>
let current = <?= $current ?>;
const TOTAL = <?= $total_slides ?>;

function goTo(n) {
    n = Math.max(1, Math.min(TOTAL, n));
    if (n === current) return;
    current = n;
    const pad = String(n).padStart(2, '0');

    document.getElementById('mainSlide').src =
    `/ywk-dashboard/assets/ppm-slides/slide-${pad}.jpg`;
    document.getElementById('progressBar').style.width =
        `${(n / TOTAL * 100).toFixed(2)}%`;
    document.getElementById('slideInput').value = n;

    document.querySelectorAll('[id^="thumb-"]').forEach(el => {
        const idx = parseInt(el.id.replace('thumb-', ''));
        el.style.borderColor = idx === n ? '#D0021B' : '#e5e7eb';
    });

    const activeThumb = document.getElementById(`thumb-${n}`);
    if (activeThumb) {
        activeThumb.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
    }

    const url = new URL(window.location);
    url.searchParams.set('slide', n);
    window.history.replaceState({}, '', url);
}

document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goTo(current + 1);
    if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   goTo(current - 1);
    if (e.key === 'Home') goTo(1);
    if (e.key === 'End')  goTo(TOTAL);
});
</script>
</body>
</html>