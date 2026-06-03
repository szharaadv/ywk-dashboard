<?php
$year = $_GET['year'] ?? 'all';

$api_params = http_build_query(array_filter([
    'year' => $year !== 'all' ? $year : null,
]));

$page_title = 'YWK DASHBOARD';
$ppm_total  = count(array_filter(
    glob($_SERVER['DOCUMENT_ROOT'] . '/ywk-dashboard/assets/ppm-slides/slide-*') ?: [],
    fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])
)) ?: 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <title>YWK Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.45.2/apexcharts.min.js"></script>
    <style>
        ::-webkit-scrollbar { display: none; }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 #22c55e55; }
            70%  { box-shadow: 0 0 0 6px #22c55e00; }
            100% { box-shadow: 0 0 0 0 #22c55e00; }
        }
        .info-bar {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 5px 1rem;
            flex-shrink: 0;
        }
        .live-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #22c55e; display: inline-block;
            animation: pulse 2s infinite;
        }
        .live-label { font-size: 10px; font-weight: 700; color: #22c55e; }
        .live-date  { font-size: 10px; color: #9ca3af; margin-left: 3px; }
        .issues-label {
            font-size: 10px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .fy-label { font-size: 10px; color: #9ca3af; text-align: right; }

        @keyframes alertPulse {
    0%   { box-shadow: 0 0 0 0 rgba(208,2,27,0.5); }
    50%  { box-shadow: 0 0 0 8px rgba(208,2,27,0); }
    100% { box-shadow: 0 0 0 0 rgba(208,2,27,0); }
}

.kpi-alert-pulse {
    animation: alertPulse 1.5s ease infinite;
    border-color: #D0021B !important;
}

.alert-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    background: linear-gradient(135deg, #7B0000, #D0021B);
    color: #fff;
    z-index: 999;
    padding: 8px 1.5rem;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to   { transform: translateY(0); }
}
    </style>
</head>
<body>

<div class="alert-overlay" id="kpi-alert-overlay"></div>
<div class="dashboard">

    <?php include 'components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- ===== INFO BAR ===== -->
        <div class="info-bar">
            <div style="display:flex;align-items:center;gap:6px;">
                <span class="live-dot" id="live-dot-el"></span>
                <span class="live-label">LIVE</span>
                <span class="live-date" id="live-clock"></span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;
                        flex-wrap:wrap;justify-content:center;">
                <span class="issues-label">TOP ISSUES:</span>
                <div id="top-gaps" style="display:flex;gap:6px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#9ca3af;">Loading...</span>
                </div>
            </div>
            <div class="fy-label">FY<?= date('Y') ?></div>
        </div>

        <!-- ===== 3 COLUMN GRID ===== -->
        <div class="home-grid">

            <!-- KOLOM 1: PPM + KPI Chart -->
            <div class="home-col-kpi">

                <!-- PPM Slideshow -->
                <div class="card" style="flex:1.6; min-height:0; padding:0;
                            display:flex; flex-direction:column; overflow:hidden;">
                    <div class="card-header"
                         style="padding:0.6rem 0.875rem 0.4rem; flex-shrink:0;">
                        <div class="card-title">PPM Performance</div>
                        <a href="pages/ppm_detail.php<?= $api_params ? '?'.$api_params : '' ?>"
                           class="detail-link">Detail →</a>
                    </div>
                    <div id="ppmSlideshow"
                         style="flex:1; min-height:0; padding:0 0.875rem 0.3rem;
                                display:flex; align-items:center;
                                justify-content:center; overflow:hidden;">
                        <img id="ppmSlideImg"
                             src="assets/ppm-slides/slide-01.jpg"
                             alt="PPM Slide 1"
                             style="max-height:100%; max-width:100%;
                                    width:auto; height:auto;
                                    display:block; object-fit:contain;
                                    background:#fff; border-radius:4px;">
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;
                                padding:0.3rem 0.875rem 0.4rem; flex-shrink:0;">
                        <button onclick="ppmPrev()" class="ppm-btn">&#8592;</button>
                        <div style="flex:1; height:3px; background:#e5e7eb; border-radius:2px;">
                        <div id="ppmProgress" style="height:100%;background:#D0021B;border-radius:2px;width:<?= round(1/$ppm_total*100,2) ?>%;transition:width 0.3s ease;"></div>
                        </div>
                        <span id="ppmCounter"
                              style="font-size:10px; color:#9ca3af; white-space:nowrap;">
                            1 / <?= $ppm_total ?>
                        </span>
                        <button onclick="ppmNext()" class="ppm-btn">&#8594;</button>
                    </div>
                </div>

                <!-- KPI YTD Chart -->
                <div class="card" style="flex:1; min-height:0; display:flex;
                            flex-direction:column; padding:0.6rem 0.875rem; overflow:hidden;">
                    <div class="card-header"
                         style="flex-wrap:wrap; gap:4px; margin-bottom:0.3rem; flex-shrink:0;">
                        <div>
                            <div class="card-title">KPI — YTD vs Last FY</div>
                            <div style="font-size:10px; color:#6b7280; margin-top:1px;"
                                 id="ytd-home-subtitle">Loading...</div>
                        </div>
                        <div style="display:flex; flex-direction:column;
                                    gap:3px; align-items:flex-end;">
                            <div class="ytd-kpi-tabs">
                                <button class="ytd-kpi-tab active" data-kpi="operation_ratio">OR</button>
                                <button class="ytd-kpi-tab" data-kpi="safety">Safety</button>
                                <button class="ytd-kpi-tab" data-kpi="fcost">F-Cost</button>
                                <button class="ytd-kpi-tab" data-kpi="quality">Quality</button>
                                <button class="ytd-kpi-tab" data-kpi="productivity">Productivity</button>
                            </div>
                            <div class="ytd-sec-tabs">
                                <button class="ytd-sec-tab active" data-sec="MS1">MS1</button>
                                <button class="ytd-sec-tab" data-sec="MS2">MS2</button>
                                <button class="ytd-sec-tab" data-sec="Conrod">Conrod</button>
                                <button class="ytd-sec-tab" data-sec="HDE">HDE</button>
                            </div>
                        </div>
                        <a href="pages/kpi_detail.php<?= $api_params ? '?'.$api_params : '' ?>"
                           class="detail-link" style="align-self:flex-start;">Detail →</a>
                    </div>
                    <div style="flex:1; position:relative; min-height:0;">
                        <canvas id="chartKPI" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>

            </div>
            <!-- END KOLOM 1 -->

            <!-- KOLOM 2: Kaizen -->
            <div class="home-col-kaizen">
                <div class="card" style="flex:1; min-height:0; overflow:hidden;
                            display:flex; flex-direction:column;">
                    <div class="kaizen-summary-bar" style="flex-shrink:0;">
                        <span class="ksb-label">Kaizen</span>
                        <span class="ksb-stat">
                            Total: <strong id="kaizen-total">—</strong>
                        </span>
                        <span class="ksb-stat">
                            Top Score: <strong id="kaizen-top-score">—</strong>
                        </span>
                        <span class="ksb-stat">
                            Best: <span class="highlight" id="kaizen-best-dept">—</span>
                        </span>
                        <a href="pages/kaizen_detail.php" class="detail-link"
                           style="margin-left:auto;">Detail →</a>
                    </div>
                    <div class="kaizen-top-banner" id="kaizen-top-banner"
                         style="flex-shrink:0;">Loading...</div>
                    <div style="flex:1; min-height:0; overflow:hidden;">
                        <table class="kaizen-table">
                            <thead>
                                <tr>
                                    <th>Kaizen Title</th>
                                    <th>Section</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="kaizen-tbody">
                                <tr>
                                    <td colspan="4"
                                        style="text-align:center; color:#9ca3af; padding:1rem;">
                                        Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- END KOLOM 2 -->

            <!-- KOLOM 3: YWK Event -->
            <div class="home-col-event">
                <div class="card" style="flex:1; min-height:0; overflow:hidden;
                            display:flex; flex-direction:column;">
                    <div class="card-header" style="flex-shrink:0;">
                        <div class="card-title">YWK Event</div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span id="event-tahun-badge"
                                style="font-size:10px; padding:2px 8px; border-radius:20px;
                                        background:#FDECEA; color:#D0021B; font-weight:600;">
                            </span>
                            <a href="pages/event_detail.php" class="detail-link">Detail →</a>
                        </div>
                    </div>

                    <!-- Auto scroll container -->
                    <div id="event-scroll-wrap"
                        style="flex:1; min-height:0; overflow:hidden; position:relative;">
                        <div id="event-cards-grid"
                            style="display:flex; flex-direction:column; gap:6px;
                                    padding-right:2px;">
                            <div style="text-align:center; color:#9ca3af;
                                        padding:1rem; font-size:12px;">Loading...</div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;
                                padding-top:6px; border-top:1px solid #f0f0f0;
                                margin-top:6px; flex-shrink:0;">
                        <div style="background:#f4f5f7; border-radius:8px; padding:6px 10px;">
                            <div style="font-size:9px; color:#6b7280; text-transform:uppercase;
                                        letter-spacing:0.04em;">Total Materi</div>
                            <div style="font-size:18px; font-weight:700; color:#1a1a1a;"
                                id="event-total-materials">—</div>
                        </div>
                        <div style="background:#f4f5f7; border-radius:8px; padding:6px 10px;">
                            <div style="font-size:9px; color:#6b7280; text-transform:uppercase;
                                        letter-spacing:0.04em;">Participants</div>
                            <div style="font-size:18px; font-weight:700; color:#D0021B;"
                                id="event-total-participants">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END KOLOM 3 -->

        </div>
        <!-- END HOME GRID -->

    </div>
</div>

<script>
const API_PARAMS = '<?= $api_params ?>';
const QS = API_PARAMS ? '?' + API_PARAMS : '';
const SEC_COLORS = { MS1:'#185FA5', MS2:'#2e7d32', Conrod:'#854F0B', HDE:'#6B2D8B' };
const RAW_YEAR   = '<?= $year ?>';
const YTD_YEAR   = (RAW_YEAR === 'all' || RAW_YEAR === '') ? 'fy2026' : RAW_YEAR;
const KPI_LABELS = {
    operation_ratio: 'Operation Ratio',
    safety: 'Safety', fcost: 'F-Cost', quality: 'Quality',
    productivity: 'Productivity & Pass Rate'
};

const PUBLIC_API = 'http://productivity-ms.yadin.com/api/public_api.php';
const YEAR_BASE  = new Date().getFullYear();

// Map section tab ke location_id
const SEC_LOC_ID = { MS1:'1', MS2:'2', Conrod:'3', HDE:'4' };
const MONTHS_FY  = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
function calMonthToFiscalIdx(m) { return (m - 4 + 12) % 12; }

fetch('api/overview_summary.php' + QS)
    .then(r => r.json())
    .then(d => {
        const or = d.operation_ratio, sf = d.safety, ql = d.quality, fc = d.fcost;

        if (or && or.actual !== null) {
            document.getElementById('kpi-or-actual').innerHTML = or.actual + '<span>%</span>';
            const diff = (or.actual - or.target).toFixed(1);
            const sign = diff >= 0 ? '+' : '';
            document.getElementById('kpi-or-sub').innerHTML =
                `<span class="${diff>=0?'ok':'bad'}">${sign}${diff}% vs target</span>
                 &nbsp;·&nbsp; ${or.periode}`;
        }
        if (sf && sf.total !== null) {
    if (sf.days_safe > 0 && sf.total === 0) {
        document.getElementById('kpi-sf-actual').innerHTML =
            sf.days_safe + '<span style="font-size:13px;"> hari</span>';
        document.getElementById('kpi-sf-sub').innerHTML =
            `<span class="ok">✓ Zero accident</span> &nbsp;·&nbsp; ${sf.periode}`;
    } else {
        document.getElementById('kpi-sf-actual').innerHTML =
            sf.total + '<span style="font-size:13px;"> accident</span>';
        document.getElementById('kpi-sf-sub').innerHTML =
            `<span class="${sf.total===0?'ok':'bad'}">
                ${sf.total===0?'✓ Zero accident':'⚠ Ada insiden'}
             </span> &nbsp;·&nbsp; ${sf.periode}`;
    }
}
        if (ql && ql.actual !== null) {
            document.getElementById('kpi-ql-actual').innerHTML =
                Number(ql.actual).toLocaleString('id-ID');
            document.getElementById('kpi-ql-sub').innerHTML =
                `<span class="${ql.actual<=ql.target?'ok':'bad'}">
                    ${ql.actual<=ql.target?'✓ On target':'⚠ Over target'}
                 </span> &nbsp;·&nbsp; ${ql.periode}`;
        }
        if (fc && fc.actual !== null) {
            const fcJt = Math.round(fc.actual/1000000);
            const tgJt = Math.round(fc.target/1000000);
            document.getElementById('kpi-fc-actual').innerHTML =
                'Rp ' + fcJt + '<span>jt</span>';
            document.getElementById('kpi-fc-sub').innerHTML =
                `<span class="${fc.actual<=fc.target?'ok':'bad'}">
                    ${fc.actual<=fc.target?'✓ Under target':'⚠ Over target'}
                 </span> &nbsp;·&nbsp; Target Rp${tgJt}jt`;
        }

        const gaps = [];
        if (or?.actual !== null && (or.actual - or.target) < 0)
            gaps.push({ label:'Op.Ratio', val:(or.actual-or.target).toFixed(1)+'%' });
        if (sf?.total > 0)
            gaps.push({ label:'Safety', val:sf.total+' accident' });
        if (ql?.actual !== null && ql.actual > ql.target)
            gaps.push({ label:'Quality', val:'+'+Math.round(ql.actual-ql.target).toLocaleString('id-ID')+' PPM' });
        if (fc?.actual !== null && fc.actual > fc.target)
            gaps.push({ label:'F-Cost', val:'+Rp'+Math.round((fc.actual-fc.target)/1000000).toFixed(0)+'jt' });

        document.getElementById('top-gaps').innerHTML = gaps.length === 0
            ? `<span style="font-size:11px;font-weight:700;color:#22c55e;">✓ All KPI On Target</span>`
            : gaps.map(g => `
                <span style="font-size:11px;font-weight:700;padding:2px 10px;
                             border-radius:20px;background:#FDECEA;color:#D0021B;">
                    ⚠ ${g.label}: ${g.val}
                </span>`).join('');
    }).catch(() => {});

    let apexProdChart = null;
    let ytdChart = null, ytdCache = {}, activeKPI = 'operation_ratio', activeSec = 'MS1';

function ytdTooltipLabel(ctx, kpi) {
    const v = ctx.parsed.y, lbl = ctx.dataset.label ?? '';
    if (kpi==='fcost')           return ` ${lbl}: Rp ${Number(v).toLocaleString('id-ID')}`;
    if (kpi==='operation_ratio') return ` ${lbl}: ${v.toFixed(1)}%`;
    if (kpi==='quality')         return ` ${lbl}: ${Number(v).toLocaleString('id-ID')} PPM`;
    return ` ${lbl}: ${v} case`;
}

function ytdYAxisCb(v, kpi) {
    if (kpi==='fcost')           return 'Rp'+(v/1000000).toFixed(0)+'jt';
    if (kpi==='operation_ratio') return v+'%';
    if (kpi==='quality')         return Number(v).toLocaleString('id-ID');
    return v;
}

function calcYAxis(datasets) {
    const all = datasets.flatMap(ds => ds.data).filter(v => v!==null && v!==undefined);
    if (!all.length) return {};
    const mn = Math.min(...all), mx = Math.max(...all);
    const range = mx - mn || 1, pad = range * 0.15;
    if (activeKPI==='operation_ratio')
        return { min:Math.max(0,Math.floor((mn-pad)/5)*5), max:Math.min(100,Math.ceil((mx+pad)/5)*5) };
    if (activeKPI==='safety') return { min:0, max:Math.ceil(mx+1) };
    const step = Math.pow(10, Math.floor(Math.log10(range)));
    return { min:Math.max(0,Math.floor((mn-pad)/step)*step), max:Math.ceil((mx+pad)/step)*step };
}

function renderYTDChart(json) {
    // Destroy & hide ApexCharts
    if (apexProdChart) { apexProdChart.destroy(); apexProdChart = null; }
    const apexDiv = document.getElementById('apexProdChart');
    if (apexDiv) { apexDiv.style.display = 'none'; }

    // Pastikan canvas Chart.js terlihat
    const canvas = document.getElementById('chartKPI');
    if (canvas) {
        canvas.style.display = 'block';
        canvas.style.width   = '100%';
        canvas.style.height  = '100%';
    }

    const color   = SEC_COLORS[activeSec];
    const curLbl  = json.cur_fy.replace('fy','FY');
    const lastLbl = json.last_fy.replace('fy','FY');
    const typeStr = (activeKPI==='fcost'||activeKPI==='safety') ? 'Kumulatif YTD' : 'Running Avg YTD';

    document.getElementById('ytd-home-subtitle').textContent =
        `${KPI_LABELS[activeKPI]} · ${typeStr} · ${activeSec}`;

    const datasets = [
        {
            label: curLbl+' (Current)',
            data: json.cur[activeSec] ?? [],
            borderColor: color, backgroundColor: color+'20',
            borderWidth: 2.5, pointRadius: 3, pointHoverRadius: 5,
            tension: 0.3, fill: true, spanGaps: false
        },
        {
            label: lastLbl+' (Last FY)',
            data: json.last[activeSec] ?? [],
            borderColor: '#aaa', backgroundColor: 'transparent',
            borderWidth: 2, borderDash: [5,4],
            pointRadius: 3, pointHoverRadius: 5,
            tension: 0.3, fill: false, spanGaps: false
        }
    ];

    const yAxis = calcYAxis(datasets);
    const options = {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true, position: 'top', align: 'end',
                labels: { font:{size:10}, boxWidth:16, padding:8, usePointStyle:true }
            },
            tooltip: { callbacks: { label: ctx => ytdTooltipLabel(ctx, activeKPI) } }
        },
        scales: {
            x: { ticks:{font:{size:9}, maxRotation:0, autoSkip:false}, grid:{color:'#f0f0f0'} },
            y: {
                min: yAxis.min, max: yAxis.max,
                ticks: { font:{size:9}, callback: v => ytdYAxisCb(v, activeKPI) },
                grid: { color:'#f0f0f0' }
            }
        }
    };

    if (ytdChart) {
        ytdChart.data.labels   = json.labels;
        ytdChart.data.datasets = datasets;
        ytdChart.options       = options;
        ytdChart.update();
    } else {
        ytdChart = new Chart(document.getElementById('chartKPI'), {
            type: 'line', data: { labels: json.labels, datasets }, options
        });
    }
}

function loadYTD() {
    if (activeKPI === 'productivity') {
        loadProductivityChart();
        return;
    }
    const key = activeKPI + '_' + YTD_YEAR;
    if (ytdCache[key]) { renderYTDChart(ytdCache[key]); return; }
    document.getElementById('ytd-home-subtitle').textContent = 'Loading...';
    fetch(`api/kpi_ytd.php?kpi=${activeKPI}&year=${YTD_YEAR}`)
        .then(r => r.json())
        .then(json => { ytdCache[key] = json; renderYTDChart(json); })
        .catch(() => {});
}

async function loadProductivityChart() {
    const locId = SEC_LOC_ID[activeSec] ?? '1';
    const cacheKey = `productivity_${activeSec}`;

    document.getElementById('ytd-home-subtitle').textContent =
        `Productivity & Pass Rate · ${activeSec}`;

    if (ytdCache[cacheKey]) {
        renderProductivityChart(ytdCache[cacheKey], activeSec);
        return;
    }

    try {
        const params = new URLSearchParams({
            action: 'productivity',
            year: YEAR_BASE,
            location_id: locId
        });
        const r   = await fetch(`${PUBLIC_API}?${params}`);
        const j   = await r.json();
        if (!j.success) throw new Error('API error');

        // Transform rows → per line_name array 12 bulan
        const rows = j.data.flat;
        const lineMap = {};
        rows.forEach(row => {
            const ln = row.line_name;
            if (!lineMap[ln]) lineMap[ln] = {
                prod25: new Array(12).fill(null),
                prod26: new Array(12).fill(null),
                pass25: new Array(12).fill(null),
                pass26: new Array(12).fill(null),
            };
            const fi = calMonthToFiscalIdx(parseInt(row.month));
            const safeNum = v => { const n=parseFloat(v); return (!v||isNaN(n)||n===0)?null:n; };
            lineMap[ln].prod25[fi] = safeNum(row.prod_2025);
            lineMap[ln].prod26[fi] = safeNum(row.prod_2026);
            lineMap[ln].pass25[fi] = safeNum(row.pass_rate_2025);
            lineMap[ln].pass26[fi] = safeNum(row.pass_rate_2026);
        });

        ytdCache[cacheKey] = lineMap;
        renderProductivityChart(lineMap, activeSec);

    } catch(e) {
        document.getElementById('ytd-home-subtitle').textContent = '⚠ Gagal memuat data productivity';
    }
}

function renderProductivityChart(lineMap, sec) {
    const yb = YEAR_BASE - 1, yc = YEAR_BASE;
    const lineNames = Object.keys(lineMap);

    // Gabung semua line — rata-rata per bulan
    const merged = {
        prod25: new Array(12).fill(null),
        prod26: new Array(12).fill(null),
        pass25: new Array(12).fill(null),
        pass26: new Array(12).fill(null),
    };
    lineNames.forEach(ln => {
        ['prod25','prod26','pass25','pass26'].forEach(k => {
            lineMap[ln][k].forEach((v,i) => {
                if (v !== null) {
                    merged[k][i] = merged[k][i] === null ? v : (merged[k][i] + v) / 2;
                }
            });
        });
    });

    document.getElementById('ytd-home-subtitle').textContent =
        `Productivity & Pass Rate · ${sec} · ${lineNames.length} line`;

    const canvas = document.getElementById('chartKPI');

    // Destroy Chart.js instance kalau ada
    if (ytdChart) {
        ytdChart.destroy();
        ytdChart = null;
    }

    // Ganti canvas dengan div untuk ApexCharts
        const parent = canvas.parentElement;
        if (!document.getElementById('apexProdChart')) {
            const div = document.createElement('div');
            div.id = 'apexProdChart';
            div.style.cssText = 'width:100%;height:100%;';
            parent.appendChild(div);
        }
        // Sembunyikan canvas, tampilkan div apex
        canvas.style.display = 'none';
        canvas.style.width   = '0';
        canvas.style.height  = '0';
        document.getElementById('apexProdChart').style.display = 'block';

    // Destroy ApexCharts instance lama
    if (apexProdChart) {
        apexProdChart.destroy();
        apexProdChart = null;
    }

    // Fiscal timestamps
    function fiscalTs(fi) {
        const m = ((fi + 3) % 12) + 1;
        const y = (m >= 1 && m <= 3) ? yc + 1 : yc;
        return new Date(y, m - 1, 1).getTime();
    }
    const ALL_FY_TS = MONTHS_FY.map((_, fi) => fiscalTs(fi));

    const opts = {
        chart: {
            type: 'line',
            height: '100%',
            fontFamily: "'Segoe UI', sans-serif",
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: false },
        },
        series: [
            { name: `Productivity FY${yc}`, type: 'bar',  data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.prod26[i] })) },
            { name: `Productivity FY${yb}`, type: 'bar',  data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.prod25[i] })) },
            { name: `Pass Rate FY${yc} (%)`, type: 'line', data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.pass26[i] })) },
            { name: `Pass Rate FY${yb} (%)`, type: 'line', data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.pass25[i] })) },
        ],
        colors: ['#1500d1', '#8cbab7', '#F59E0B', '#ff5900'],
        theme: { mode: 'light' },
        grid: { borderColor: 'rgba(60,90,180,.3)', strokeDashArray: 2 },
        legend: {
            show: true, position: 'top', horizontalAlign: 'right',
            fontSize: '9px', markers: { width: 10, height: 10 },
        },
        tooltip: {
            shared: true, intersect: false,
            y: { formatter: (val, { seriesIndex }) => {
                if (val === null || val === undefined) return 'N/A';
                const n = parseFloat(val); if (isNaN(n) || n === 0) return 'N/A';
                return seriesIndex >= 2 ? n.toFixed(2) + '%' : n.toFixed(2);
            }}
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: { fontSize: '9px', colors: '#6b7280' },
                datetimeUTC: false, format: 'MMM',
                rotate: -30, rotateAlways: true,
            },
        },
        plotOptions: { bar: { columnWidth: '80%', borderRadius: 0 } },
        dataLabels: { enabled: false },
        stroke: { width: [0, 0, 2.5, 2], curve: 'smooth' },
        fill: { type: ['solid','solid','solid','solid'], opacity: [0.8, 0.5, 1, 1] },
        markers: { size: [0, 0, 3, 3], strokeWidth: 2, strokeColor: '#fff', showNullDataPoints: false },
        yaxis: [
            {
                title: { text: 'Productivity', style: { fontSize: '8px', color: '#6b7280' } },
                labels: { style: { fontSize: '9px', colors: '#6b7280' }, formatter: v => v != null ? parseFloat(v).toFixed(1) : '' },
                tickAmount: 5,
            },
            { show: false, tickAmount: 5 },
            {
                opposite: true,
                title: { text: 'Pass Rate (%)', style: { fontSize: '8px', color: '#6b7280' } },
                labels: { style: { fontSize: '9px', colors: '#6b7280' }, formatter: v => v != null ? parseFloat(v).toFixed(1) + '%' : '' },
                tickAmount: 5,
            },
            { opposite: true, show: false, tickAmount: 5 },
        ],
    };

    apexProdChart = new ApexCharts(document.getElementById('apexProdChart'), opts);
    apexProdChart.render();
}

document.querySelectorAll('.ytd-kpi-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ytd-kpi-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeKPI = btn.dataset.kpi;
        loadYTD();
    });
});

function updateSecTabs() {
    document.querySelectorAll('.ytd-sec-tab').forEach(b => {
        const isActive = b.dataset.sec === activeSec;
        b.classList.toggle('active', isActive);
        b.style.background  = isActive ? SEC_COLORS[b.dataset.sec] : '#fff';
        b.style.borderColor = isActive ? SEC_COLORS[b.dataset.sec] : '#e5e7eb';
        b.style.color       = isActive ? '#fff' : '#6b7280';
    });
}

document.querySelectorAll('.ytd-sec-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        activeSec = btn.dataset.sec;
        updateSecTabs();
        if (activeKPI === 'productivity') {
            loadProductivityChart();
        } else {
            const key = activeKPI + '_' + YTD_YEAR;
            if (ytdCache[key]) renderYTDChart(ytdCache[key]);
            else loadYTD();
        }
    });
    btn.addEventListener('mouseenter', () => {
        if (btn.dataset.sec !== activeSec) {
            btn.style.borderColor = SEC_COLORS[btn.dataset.sec];
            btn.style.color = SEC_COLORS[btn.dataset.sec];
        }
    });
    btn.addEventListener('mouseleave', () => {
        if (btn.dataset.sec !== activeSec) {
            btn.style.borderColor = '#e5e7eb';
            btn.style.color = '#6b7280';
        }
    });
});

updateSecTabs();
loadYTD();

fetch('api/overview_kaizen.php')
    .then(r => r.json())
    .then(d => {
        document.getElementById('kaizen-total').textContent     = d.total     ?? '—';
        document.getElementById('kaizen-top-score').textContent = d.top_score ?? '—';
        document.getElementById('kaizen-best-dept').textContent = d.best_dept ?? '—';

        const banner = document.getElementById('kaizen-top-banner');
        if (d.top_kaizen) {
            banner.style.background = '#7B0000';
            banner.style.color      = '#fff';
            banner.textContent =
                `Top Kaizen: "${d.top_kaizen.judul}" — ${d.top_kaizen.pic} — Score ${d.top_kaizen.nilai}`;
        } else {
            banner.style.background = '#f4f5f7';
            banner.style.color      = '#9ca3af';
            banner.textContent      = 'Belum ada data kaizen';
        }

        const statusClass = s =>
            ({'approved':'status-approved','implemented':'status-implemented','open':'status-open'})
            [s?.toLowerCase()] ?? 'status-open';

        const tbody = document.getElementById('kaizen-tbody');
        if (!d.top5 || d.top5.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4"
                style="text-align:center;color:#9ca3af;padding:1rem;">
                Belum ada data</td></tr>`;
            return;
        }
        tbody.innerHTML = d.top5.map(k => `
            <tr>
                <td class="title-col">${k.judul}</td>
                <td>${k.section ?? '—'}</td>
                <td class="score-col">${k.nilai ?? '—'}</td>
                <td><span class="status-badge ${statusClass(k.status)}">${k.status ?? '—'}</span></td>
            </tr>`).join('');
    }).catch(() => {});

fetch('api/overview_event.php')
    .then(r => r.json())
    .then(d => {
        const badge = document.getElementById('event-tahun-badge');
        if (badge && d.tahun) badge.textContent = d.tahun;
        document.getElementById('event-total-materials').textContent    = d.total_materi       ?? '—';
        document.getElementById('event-total-participants').textContent = d.total_participants ?? '—';

        const grid = document.getElementById('event-cards-grid');
        if (!d.photos || d.photos.length === 0) {
            grid.innerHTML = `<div style="text-align:center;color:#9ca3af;
                padding:1rem;font-size:12px;">Belum ada data event</div>`;
            return;
        }

        function rankBadge(rank) {
            const r = parseInt(rank);
            if (r===1) return { label:'🥇 1st Winner', bg:'#D0021B',   color:'#fff',    border:'#D0021B' };
            if (r===2) return { label:'🥈 2nd Winner', bg:'#374151',   color:'#fff',    border:'#374151' };
            if (r===3) return { label:'🥉 3rd Winner', bg:'#3B6D11',   color:'#fff',    border:'#3B6D11' };
            if (r===4) return { label:'4th Winner', bg:'#3B6D11',   color:'#fff',    border:'#3B6D11' };
            if (r===5) return { label:'5th Winner', bg:'#3B6D11',   color:'#fff',    border:'#3B6D11' };
            if (r===6) return { label:'6th Winner', bg:'#3B6D11',   color:'#fff',    border:'#3B6D11' };

            return          { label:'',    bg:'#f4f5f7',   color:'#6b7280', border:'#e5e7eb' };
        }

        // Tampilkan SEMUA foto (bukan slice 3)
        const cards = d.photos.map(ev => {
            const rb = rankBadge(ev.peringkat);
            const imgHtml = ev.foto
                ? `<img src="assets/img/${ev.foto}"
                        style="width:100%; height:120px; object-fit:cover;
                               border-radius:8px 8px 0 0; display:block;"
                        onerror="this.style.display='none'">`
                : `<div style="width:100%;height:120px;background:#f4f5f7;
                               border-radius:8px 8px 0 0;display:flex;
                               align-items:center;justify-content:center;
                               font-size:28px;">📷</div>`;
            return `
                <div style="border:2px solid ${rb.border}; border-radius:10px;
                            background:#fff; overflow:hidden; flex-shrink:0;">
                    <div style="position:relative;">
                        ${imgHtml}
                        <span style="position:absolute; top:8px; right:8px;
                                     font-size:10px; font-weight:700;
                                     padding:3px 10px; border-radius:20px;
                                     background:${rb.bg}; color:${rb.color};
                                     box-shadow:0 1px 4px rgba(0,0,0,0.2);">
                            ${rb.label}
                        </span>
                    </div>
                    <div style="padding:8px 10px;">
                        <div style="font-size:12px; font-weight:700; color:#1a1a1a;
                                    line-height:1.3; margin-bottom:3px;">
                            ${ev.judul_materi ?? '—'}
                        </div>
                        <div style="font-size:10px; color:#6b7280;">
                            ${ev.peserta ?? '—'}
                        </div>
                        ${ev.departemen
                            ? `<div style="font-size:10px; color:#D0021B; margin-top:2px;
                                           font-weight:600;">
                                   ${ev.departemen}
                               </div>`
                            : ''}
                    </div>
                </div>`;
        }).join('');

        grid.innerHTML = cards;

        // ===== AUTO SCROLL =====
        const wrap = document.getElementById('event-scroll-wrap');
        let scrollPos  = 0;
        let scrollDir  = 1;
        let scrollPaused = false;

        // Pause saat hover
        wrap.addEventListener('mouseenter', () => scrollPaused = true);
        wrap.addEventListener('mouseleave', () => scrollPaused = false);

        // Tunggu semua gambar load dulu baru mulai scroll
const allImgs = grid.querySelectorAll('img');
let loadedCount = 0;
const totalImgs = allImgs.length;

function startAutoScroll() {
    let scrollPos = 0;
    let scrollDir = 1;
    let pauseTimer = null;

    function autoScroll() {
        if (scrollPaused) return;

        const maxScroll = grid.scrollHeight - wrap.clientHeight;
        if (maxScroll <= 0) return;

        scrollPos += scrollDir * 0.6; // kecepatan scroll

        if (scrollPos >= maxScroll) {
            scrollDir = -1;
            // Pause 1.5 detik di bawah sebelum balik
            scrollPaused = true;
            setTimeout(() => { scrollPaused = false; }, 1500);
        } else if (scrollPos <= 0) {
            scrollDir = 1;
            // Pause 1.5 detik di atas sebelum turun lagi
            scrollPaused = true;
            setTimeout(() => { scrollPaused = false; }, 1500);
        }

        scrollPos = Math.max(0, Math.min(maxScroll, scrollPos));
        wrap.scrollTop = scrollPos;
    }

    setInterval(autoScroll, 16);
}

if (totalImgs === 0) {
    startAutoScroll();
} else {
    allImgs.forEach(img => {
        if (img.complete) {
            loadedCount++;
            if (loadedCount === totalImgs) startAutoScroll();
        } else {
            img.addEventListener('load',  () => { loadedCount++; if (loadedCount === totalImgs) startAutoScroll(); });
            img.addEventListener('error', () => { loadedCount++; if (loadedCount === totalImgs) startAutoScroll(); });
        }
    });
}
    }).catch(() => {});

const PPM_TOTAL = <?= $ppm_total ?>;
let ppmCurrent  = 1;

function ppmGoTo(n) {
    if (n > PPM_TOTAL) n = 1;
    if (n < 1) n = PPM_TOTAL;
    ppmCurrent = n;
    const pad = String(ppmCurrent).padStart(2,'0');
    document.getElementById('ppmSlideImg').src = `assets/ppm-slides/slide-${pad}.jpg`;
    document.getElementById('ppmCounter').textContent = `${ppmCurrent} / ${PPM_TOTAL}`;
    document.getElementById('ppmProgress').style.width =
        `${(ppmCurrent/PPM_TOTAL*100).toFixed(2)}%`;
}

function ppmNext() { ppmGoTo(ppmCurrent+1); }
function ppmPrev() { ppmGoTo(ppmCurrent-1); }

let ppmTimer = setInterval(() => ppmGoTo(ppmCurrent+1), 5000);
document.getElementById('ppmSlideshow')?.addEventListener('mouseenter', () => clearInterval(ppmTimer));
document.getElementById('ppmSlideshow')?.addEventListener('mouseleave', () => {
    ppmTimer = setInterval(() => ppmGoTo(ppmCurrent+1), 5000);
});
document.addEventListener('keydown', e => {
    if (e.key==='ArrowRight') ppmNext();
    if (e.key==='ArrowLeft')  ppmPrev();
});

// ===== REAL-TIME CLOCK =====
function updateClock() {
    const now  = new Date();
    const date = now.toLocaleDateString('id-ID', {
        day:'2-digit', month:'short', year:'numeric'
    });
    const time = now.toLocaleTimeString('id-ID', {
        hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
    const el = document.getElementById('live-clock');
    if (el) el.textContent = date + ' · ' + time;
}
updateClock();
setInterval(updateClock, 1000);

// ===== AUTO REFRESH DATA =====
const REFRESH_INTERVAL = 10 * 60 * 1000; // 10 menit

function refreshAllData() {
    // Refresh KPI Summary + Top Gaps
    fetch('api/overview_summary.php' + QS)
        .then(r => r.json())
        .then(d => {
            const or = d.operation_ratio, sf = d.safety,
                  ql = d.quality,         fc = d.fcost;

            if (or && or.actual !== null) {
                document.getElementById('kpi-or-actual').innerHTML =
                    or.actual + '<span>%</span>';
                const diff = (or.actual - or.target).toFixed(1);
                const sign = diff >= 0 ? '+' : '';
                document.getElementById('kpi-or-sub').innerHTML =
                    `<span class="${diff>=0?'ok':'bad'}">${sign}${diff}% vs target</span>
                     &nbsp;·&nbsp; ${or.periode}`;
            }
            if (sf && sf.total !== null) {
                document.getElementById('kpi-sf-actual').innerHTML =
                    sf.total + '<span style="font-size:13px;"> accident</span>';
                document.getElementById('kpi-sf-sub').innerHTML =
                    `<span class="${sf.total===0?'ok':'bad'}">
                        ${sf.total===0?'✓ Zero accident':'⚠ Ada insiden'}
                     </span> &nbsp;·&nbsp; ${sf.periode}`;
            }
            if (ql && ql.actual !== null) {
                document.getElementById('kpi-ql-actual').innerHTML =
                    Number(ql.actual).toLocaleString('id-ID');
                document.getElementById('kpi-ql-sub').innerHTML =
                    `<span class="${ql.actual<=ql.target?'ok':'bad'}">
                        ${ql.actual<=ql.target?'✓ On target':'⚠ Over target'}
                     </span> &nbsp;·&nbsp; ${ql.periode}`;
            }
            if (fc && fc.actual !== null) {
                const fcJt = Math.round(fc.actual/1000000);
                const tgJt = Math.round(fc.target/1000000);
                document.getElementById('kpi-fc-actual').innerHTML =
                    'Rp ' + fcJt + '<span>jt</span>';
                document.getElementById('kpi-fc-sub').innerHTML =
                    `<span class="${fc.actual<=fc.target?'ok':'bad'}">
                        ${fc.actual<=fc.target?'✓ Under target':'⚠ Over target'}
                     </span> &nbsp;·&nbsp; Target Rp${tgJt}jt`;
            }

            // Refresh top gaps
            const gaps = [];
            if (or?.actual !== null && (or.actual - or.target) < 0)
                gaps.push({ label:'Op.Ratio', val:(or.actual-or.target).toFixed(1)+'%' });
            if (sf?.total > 0)
                gaps.push({ label:'Safety', val:sf.total+' accident' });
            if (ql?.actual !== null && ql.actual > ql.target)
                gaps.push({ label:'Quality', val:'+'+Math.round(ql.actual-ql.target).toLocaleString('id-ID')+' PPM' });
            if (fc?.actual !== null && fc.actual > fc.target)
                gaps.push({ label:'F-Cost', val:'+Rp'+Math.round((fc.actual-fc.target)/1000000).toFixed(0)+'jt' });

            document.getElementById('top-gaps').innerHTML = gaps.length === 0
                ? `<span style="font-size:11px;font-weight:700;color:#22c55e;">✓ All KPI On Target</span>`
                : gaps.map(g => `
                    <span style="font-size:11px;font-weight:700;padding:2px 10px;
                                 border-radius:20px;background:#FDECEA;color:#D0021B;">
                        ⚠ ${g.label}: ${g.val}
                    </span>`).join('');

            // Check alert — kalau ada gap, trigger visual alert
            checkKPIAlert(gaps);
        }).catch(() => {});

    // Refresh Kaizen
    fetch('api/overview_kaizen.php')
        .then(r => r.json())
        .then(d => {
            document.getElementById('kaizen-total').textContent     = d.total     ?? '—';
            document.getElementById('kaizen-top-score').textContent = d.top_score ?? '—';
            document.getElementById('kaizen-best-dept').textContent = d.best_dept ?? '—';
        }).catch(() => {});

    // Refresh YTD chart cache clear & reload
    ytdCache = {};
    loadYTD();

    // Tampilkan indikator refresh
    showRefreshIndicator();
}

function showRefreshIndicator() {
    const dot = document.getElementById('live-dot-el');
    if (!dot) return;
    dot.style.background = '#185FA5';
    setTimeout(() => { dot.style.background = '#22c55e'; }, 1000);
}

setInterval(refreshAllData, REFRESH_INTERVAL);

// ===== KPI ALERT VISUAL =====
function checkKPIAlert(gaps) {
    const overlay = document.getElementById('kpi-alert-overlay');
    const infoBar = document.querySelector('.info-bar');

    if (gaps.length > 0) {
        // Tampilkan overlay alert
        overlay.style.display = 'block';
        overlay.innerHTML = `⚠ KPI ALERT: ${gaps.map(g => g.label + ' ' + g.val).join(' | ')}`;

        // Pulse info bar merah
        infoBar?.classList.add('kpi-alert-pulse');

        // Sembunyikan overlay setelah 5 detik
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 5000);
    } else {
        overlay.style.display = 'none';
        infoBar?.classList.remove('kpi-alert-pulse');
    }
}

</script>
</body>
</html>