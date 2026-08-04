<?php
$year = $_GET['year'] ?? 'fy2026';

$api_params = http_build_query(array_filter([
    'year' => $year !== 'all' ? $year : null,
]));

$page_title = 'YWK DASHBOARD';
$ppm_total = count(array_filter(
    glob(__DIR__ . '/assets/ppm-slides/slide-*') ?: [],
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
                            <div style="font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; align-self:center;">
                                Productivity & Pass Rate
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
                    <div style="flex:1; min-height:0; overflow-y:auto;">
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

                    <!-- Kaizen Analytics -->
                    <div style="flex-shrink:0; border-top:1px solid #f0f0f0; padding-top:8px; margin-top:6px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; flex-wrap:wrap; gap:4px;">
                            <div style="font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em;">Kaizen Analytics</div>
                                <a href="pages/kaizen_analytics.php" class="detail-link">Detail →</a>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <!-- Filter Tahun -->
                                <select id="kaizen-filter-tahun" onchange="loadKaizenAnalytics()"
                                    style="font-size:10px; border:1px solid #e5e7eb; border-radius:6px;
                                        padding:2px 6px; background:#fff; cursor:pointer; outline:none;">
                                    <option value="2024">2024</option>
                                    <option value="2025" selected>2025</option>
                                    <option value="2026">2026</option>
                                </select>
                                <!-- Filter Bulan -->
                                <div style="display:flex; gap:2px; flex-wrap:wrap;">
                                    <?php
                                    $cur_m = (int)date('n');
                                    $bulan_fy = [4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',
                                                10=>'Oct',11=>'Nov',12=>'Dec',1=>'Jan',2=>'Feb',3=>'Mar'];
                                    foreach ($bulan_fy as $num => $label):
                                    ?>
                                    <button onclick="setKaizenBulan(<?= $num ?>, this)"
                                        class="kaizen-bulan-btn <?= $num == $cur_m ? 'active' : '' ?>"
                                        style="font-size:9px; padding:2px 6px; border-radius:4px; cursor:pointer;
                                            border:1px solid <?= $num == $cur_m ? '#D0021B' : '#e5e7eb' ?>;
                                            background:<?= $num == $cur_m ? '#D0021B' : '#fff' ?>;
                                            color:<?= $num == $cur_m ? '#fff' : '#6b7280' ?>; font-weight:600;">
                                        <?= $label ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <div>
                                <div style="font-size:9px; font-weight:700; color:#374151; margin-bottom:4px; text-align:center;">AWARENESS RATIO</div>
                                <div style="position:relative; height:220px;">
                                    <canvas id="chartAwareness"></canvas>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:9px; font-weight:700; color:#374151; margin-bottom:4px; text-align:center;">CATEGORY TENDENCY</div>
                                <div style="position:relative; height:220px;">
                                    <canvas id="chartCategory"></canvas>
                                </div>
                            </div>
                        </div>
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
                                                    background:#FDECEA; color:#D0021B; font-weight:600;"></span>
                                        <a href="pages/event_detail.php" class="detail-link">Detail →</a>
                                    </div>
                                </div>

                                <div style="flex:1; min-height:0; display:flex; flex-direction:column; gap:6px; overflow:hidden;">

                                    <!-- YWKS -->
                                    <div style="flex:1; min-height:0; display:flex; flex-direction:column;">
                                        <div id="label-ywks"
                                            style="font-size:10px;font-weight:800;color:#7B0000;
                                                    text-transform:uppercase;letter-spacing:.06em;
                                                    padding:4px 0;border-bottom:2px solid #D0021B;
                                                    margin-bottom:6px;flex-shrink:0;">YWKS</div>
                                        <div id="scroll-ywks"
                                            style="flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column;"></div>
                                        <div style="padding-top:6px;border-top:1px solid #f0f0f0;margin-top:6px;flex-shrink:0;">
                                            <div style="background:#f4f5f7;border-radius:8px;padding:6px 10px;">
                                                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;">Participants</div>
                                                <div style="font-size:18px;font-weight:700;color:#D0021B;" id="ywks-participants">—</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- YGP Internal -->
                                    <div style="flex:1; min-height:0; display:flex; flex-direction:column;">
                                        <div id="label-yjp"
                                            style="font-size:10px;font-weight:800;color:#7B0000;
                                                    text-transform:uppercase;letter-spacing:.06em;
                                                    padding:4px 0;border-bottom:2px solid #D0021B;
                                                    margin-bottom:6px;flex-shrink:0;">YGP INTERNAL</div>
                                        <div id="scroll-yjp"
                                              style="flex:1;min-height:0;overflow-x:auto;overflow-y:hidden;
                                                    display:flex;flex-direction:row;gap:8px;align-items:flex-start;"></div>
                                        <div style="padding-top:6px;border-top:1px solid #f0f0f0;
                                                    margin-top:6px;flex-shrink:0;">
                                            <div style="background:#f4f5f7;border-radius:8px;padding:6px 10px;">
                                                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;
                                                            letter-spacing:0.04em;">Participants</div>
                                                <div style="font-size:18px;font-weight:700;color:#D0021B;"
                                                    id="event-total-participants">—</div>
                                            </div>
                                        </div>
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

const PUBLIC_API = (window.location.pathname.includes('/ywk-dashboard/')
    ? '/ywk-dashboard/api/' : '/api/') + 'productivity_proxy.php';
const YEAR_BASE  = new Date().getFullYear();

// Line yang tidak dipakai — dikecualikan dari chart productivity
const EXCLUDED_LINES = ['Test Run Line'];

// Map section tab ke location_id
// location: 1=Connecting Rod, 2=Machine Shop 1, 3=Machine Shop 2, 4=Assembly
const SEC_LOC_ID = { MS1:'2', MS2:'3', Conrod:'1', HDE:'4' };
const MONTHS_FY  = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
function calMonthToFiscalIdx(m) { return (m - 4 + 12) % 12; }

const API_BASE = window.location.pathname.includes('/ywk-dashboard/') 
    ? '/ywk-dashboard/api/' 
    : '/api/';

fetch(API_BASE + 'overview_summary.php' + QS)
    .then(r => r.json())
    .then(d => {
        const or = d.operation_ratio, sf = d.safety, ql = d.quality, fc = d.fcost;

        if (or && or.actual !== null) {
            document.getElementById('kpi-or-actual').innerHTML = or.actual + '<span>%</span>';
            const diff = (or.actual - or.target).toFixed(1);
            const sign = diff >= 0 ? '+' : '';
            document.getElementById('kpi-or-sub').innerHTML =
                `<span class="${diff>=0?'ok':'bad'}">${sign}${diff}% vs target</span>
                 &nbsp;·&nbsp; <span style="font-size:9px;">${or.periode ?? ''}</span>`;
        }
        if (sf && sf.total !== null) {
    if (sf.days_safe > 0 && sf.total === 0) {
        document.getElementById('kpi-sf-actual').innerHTML =
            sf.days_safe + '<span style="font-size:13px;"> day</span>';
        document.getElementById('kpi-sf-sub').innerHTML =
            `<span class="ok">✓ Zero accident</span> &nbsp;·&nbsp; <span style="font-size:9px;">${sf.periode ?? ''}</span>`;
    } else {
        document.getElementById('kpi-sf-actual').innerHTML =
            sf.total + '<span style="font-size:13px;"> accident</span>';
        document.getElementById('kpi-sf-sub').innerHTML =
            `<span class="${sf.total===0?'ok':'bad'}">
                ${sf.total===0?'✓ Zero accident':'⚠ Ada insiden'}
             </span> &nbsp;·&nbsp; <span style="font-size:9px;">${sf.periode ?? ''}</span>`;
    }
}
        if (ql && ql.actual !== null) {
            document.getElementById('kpi-ql-actual').innerHTML =
                Number(ql.actual).toLocaleString('id-ID');
            document.getElementById('kpi-ql-sub').innerHTML =
                `<span class="${ql.actual<=ql.target?'ok':'bad'}">
                    ${ql.actual<=ql.target?'✓ On target':'⚠ Over target'}
                 </span> &nbsp;·&nbsp; <span style="font-size:9px;">${ql.periode ?? ''}</span>`;
        }
        if (fc && (fc.conrod || fc.hde)) {
        const cr = fc.conrod, hde = fc.hde;
        const crLine = cr?.pct !== null
            ? `<div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:10px;color:rgba(255,255,255,0.7);">Conrod</span>
                <span style="font-size:13px;font-weight:700;">
                    ${cr.pct}%
                    <span style="font-size:10px;font-weight:400;margin-left:4px;" class="${cr.status==='ok'?'ok':'bad'}">
                        ${cr.status==='ok'?'✓':'⚠'} target ${cr.target_pct}%
                    </span>
                </span>
            </div>`
            : '';
        const hdeLine = hde?.pct !== null
            ? `<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px;">
                <span style="font-size:10px;color:rgba(255,255,255,0.7);">HDE</span>
                <span style="font-size:13px;font-weight:700;">
                    ${hde.pct}%
                    <span style="font-size:10px;font-weight:400;margin-left:4px;" class="${hde.status==='ok'?'ok':'bad'}">
                        ${hde.status==='ok'?'✓':'⚠'} target ${hde.target_pct}%
                    </span>
                </span>
            </div>`
            : '';

        document.getElementById('kpi-fc-actual').innerHTML =
            `<div style="font-size:11px;font-weight:700;margin-bottom:4px;">F-Cost %</div>
            ${crLine}${hdeLine}`;
        document.getElementById('kpi-fc-sub').innerHTML =
            `<span style="font-size:10px;color:rgba(255,255,255,0.6);">${cr?.periode ?? hde?.periode ?? ''}</span>`;
    }

        const gaps = [];
        if (or?.actual !== null && (or.actual - or.target) < 0)
            gaps.push({ label:'Op.Ratio', val:(or.actual-or.target).toFixed(1)+'%' });
        if (sf?.total > 0)
            gaps.push({ label:'Safety', val:sf.total+' accident' });
        if (ql?.actual !== null && ql.actual > ql.target)
            gaps.push({ label:'Quality', val:'+'+Math.round(ql.actual-ql.target).toLocaleString('id-ID')+' Part Per Million' });
                    gaps.push({ label:'F-Cost Conrod', val: fc.conrod.pct+'% > '+fc.conrod.target_pct+'%' });
        if (fc?.hde?.status === 'over')
            gaps.push({ label:'F-Cost HDE', val: fc.hde.pct+'% > '+fc.hde.target_pct+'%' });

        document.getElementById('top-gaps').innerHTML = gaps.length === 0
            ? `<span style="font-size:11px;font-weight:700;color:#22c55e;">✓ All KPI On Target</span>`
            : gaps.map(g => `
                <span style="font-size:11px;font-weight:700;padding:2px 10px;
                             border-radius:20px;background:#FDECEA;color:#D0021B;">
                    ⚠ ${g.label}: ${g.val}
                </span>`).join('');
    }).catch(() => {});

    let apexProdChart = null;
    let ytdChart = null, ytdCache = {}, activeKPI = 'productivity', activeSec = 'MS1';

function ytdTooltipLabel(ctx, kpi) {
    const v = ctx.parsed.y, lbl = ctx.dataset.label ?? '';
    if (kpi==='fcost')           return ` ${lbl}: Rp ${Number(v).toLocaleString('id-ID')}`;
    if (kpi==='operation_ratio') return ` ${lbl}: ${v.toFixed(1)}%`;
    if (kpi==='quality')         return ` ${lbl}: ${Number(v).toLocaleString('id-ID')} Part per Million`;
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
    fetch(`${API_BASE}kpi_ytd.php?kpi=${activeKPI}&year=${YTD_YEAR}`)
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
            if (EXCLUDED_LINES.includes(ln)) return; // line tidak dipakai (data tidak valid)
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

    const LINE_COUNT = { MS1:5, MS2:7, Conrod:6, HDE:1 };
    document.getElementById('ytd-home-subtitle').textContent =
        `Productivity & Pass Rate · ${sec} · ${LINE_COUNT[sec] ?? lineNames.length} line`;

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
            { name: `Productivity FY${yb}`, type: 'bar',  data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.prod25[i] })) },
            { name: `Productivity FY${yc}`, type: 'bar',  data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.prod26[i] })) },
            { name: `Pass Rate FY${yb} (%)`, type: 'line', data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.pass25[i] })) },
            { name: `Pass Rate FY${yc} (%)`, type: 'line', data: ALL_FY_TS.map((t,i) => ({ x:t, y:merged.pass26[i] })) },
        ],
        colors: ['#8cbab7', '#1500d1', '#ff5900', '#F59E0B'],
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

fetch(API_BASE + 'overview_kaizen.php')
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

fetch(API_BASE + 'overview_event.php')
    .then(r => r.json())
    .then(d => {
        const badge = document.getElementById('event-tahun-badge');
        if (badge && d.tahun) badge.textContent = d.tahun;
        document.getElementById('event-total-participants').textContent = d.total_participants ?? '—';

        function rankBadge(rank) {
            const r = parseInt(rank);
            if (isNaN(r) || r <= 0) return { label:'', bg:'#f4f5f7', color:'#6b7280', border:'#e5e7eb' };
            const suffix = (n) => {
                if (n % 100 >= 11 && n % 100 <= 13) return n+'th';
                switch(n%10){case 1:return n+'st';case 2:return n+'nd';case 3:return n+'rd';default:return n+'th';}
            };
            const medal  = r===1?'🥇 ':r===2?'🥈 ':r===3?'🥉 ':'';
            const colors = {
                1:{bg:'#D0021B',color:'#fff',border:'#D0021B'},
                2:{bg:'#374151',color:'#fff',border:'#374151'},
                3:{bg:'#3B6D11',color:'#fff',border:'#3B6D11'},
            };
            const c = colors[r] ?? {bg:'#185FA5',color:'#fff',border:'#185FA5'};
            return { label:`${medal}${suffix(r)} Winner`, ...c };
        }

        function buildSlideshow(containerId, photos) {
            const wrap = document.getElementById(containerId);
            if (!wrap || !photos || !photos.length) return;

            let idx = 0;

            function render() {
                const ev = photos[idx];
                const r  = parseInt(ev.peringkat);
                const suffix = n => {
                    if (n%100>=11&&n%100<=13) return n+'th';
                    switch(n%10){case 1:return n+'st';case 2:return n+'nd';case 3:return n+'rd';default:return n+'th';}
                };
                const medal  = r===1?'🥇 ':r===2?'🥈 ':r===3?'🥉 ':'';
                const colors = {1:{bg:'#D0021B',color:'#fff',border:'#D0021B'},2:{bg:'#374151',color:'#fff',border:'#374151'},3:{bg:'#3B6D11',color:'#fff',border:'#3B6D11'}};
                const c = colors[r] ?? {bg:'#185FA5',color:'#fff',border:'#185FA5'};
                const badgeHtml = !isNaN(r)&&r>0
                    ? `<span style="position:absolute;top:8px;right:8px;font-size:10px;font-weight:700;
                        padding:3px 10px;border-radius:20px;background:${c.bg};color:${c.color};
                        box-shadow:0 1px 4px rgba(0,0,0,0.2);white-space:nowrap;">
                        ${medal}${suffix(r)} Winner</span>` : '';
                const imgHtml = ev.foto
                    ? `<img src="assets/img/${ev.foto}" style="width:100%;height:130px;object-fit:cover;border-radius:8px 8px 0 0;display:block;" onerror="this.style.display='none'">`
                    : `<div style="width:100%;height:130px;background:#f4f5f7;border-radius:8px 8px 0 0;display:flex;align-items:center;justify-content:center;font-size:28px;">📷</div>`;

                wrap.innerHTML = `
                    <div style="border:2px solid ${c.border};border-radius:10px;background:#fff;overflow:hidden;flex-shrink:0;width:100%;">
                        <div style="position:relative;">${imgHtml}${badgeHtml}</div>
                        <div style="padding:8px 10px;">
                            <div style="font-size:12px;font-weight:700;color:#1a1a1a;line-height:1.3;margin-bottom:3px;">${ev.judul_materi??'—'}</div>
                            <div style="font-size:10px;color:#6b7280;">${ev.peserta??'—'}</div>
                            ${ev.departemen?`<div style="font-size:10px;color:#D0021B;font-weight:600;margin-top:2px;">${ev.departemen}</div>`:''}
                        </div>
                        <!-- Progress dots -->
                        <div style="display:flex;align-items:center;gap:4px;padding:4px 10px 8px;justify-content:center;">
                            ${photos.map((_,i)=>`<div style="width:${i===idx?'16px':'6px'};height:6px;border-radius:3px;background:${i===idx?'#D0021B':'#e5e7eb'};transition:all .3s;"></div>`).join('')}
                        </div>
                    </div>`;
            }

            render();

            // Auto slideshow tiap 3 detik
            setInterval(() => {
                idx = (idx + 1) % photos.length;
                render();
            }, 3000);
        }

        const wrapYwks  = document.getElementById('scroll-ywks');
        const labelYwks = document.getElementById('label-ywks');
        const wrapYjp   = document.getElementById('scroll-yjp');
        const labelYjp  = document.getElementById('label-yjp');

        if (d.photos_ywks && d.photos_ywks.length) {
            buildSlideshow('scroll-ywks', d.photos_ywks);
        } else {
            if (labelYwks) labelYwks.style.display = 'none';
            if (wrapYwks)  wrapYwks.style.display  = 'none';
        }

        if (d.photos_yjp && d.photos_yjp.length) {
            buildSlideshow('scroll-yjp', d.photos_yjp);
        } else {
            if (labelYjp) labelYjp.style.display = 'none';
            if (wrapYjp)  wrapYjp.style.display  = 'none';
        }

        // Participants per section
        const elYwks = document.getElementById('ywks-participants');
        const elYjp  = document.getElementById('event-total-participants');
        if (elYwks) elYwks.textContent = d.participants_ywks ?? '—';
        if (elYjp)  elYjp.textContent  = d.participants_yjp  ?? '—';

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
    fetch(API_BASE + 'overview_summary.php' + QS)
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
            if (fc && (fc.conrod || fc.hde)) {
                const cr = fc.conrod, hde = fc.hde;
                const crLine = cr?.pct !== null
                    ? `<div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:10px;color:rgba(255,255,255,0.7);">Conrod</span>
                        <span style="font-size:13px;font-weight:700;">${cr.pct}%
                            <span style="font-size:10px;font-weight:400;margin-left:4px;" class="${cr.status==='ok'?'ok':'bad'}">
                                ${cr.status==='ok'?'✓':'⚠'} target ${cr.target_pct}%
                            </span>
                        </span></div>` : '';
                const hdeLine = hde?.pct !== null
                    ? `<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px;">
                        <span style="font-size:10px;color:rgba(255,255,255,0.7);">HDE</span>
                        <span style="font-size:13px;font-weight:700;">${hde.pct}%
                            <span style="font-size:10px;font-weight:400;margin-left:4px;" class="${hde.status==='ok'?'ok':'bad'}">
                                ${hde.status==='ok'?'✓':'⚠'} target ${hde.target_pct}%
                            </span>
                        </span></div>` : '';
                document.getElementById('kpi-fc-actual').innerHTML =
                    `<div style="font-size:11px;font-weight:700;margin-bottom:4px;">F-Cost %</div>${crLine}${hdeLine}`;
                document.getElementById('kpi-fc-sub').innerHTML =
                    `<span style="font-size:10px;color:rgba(255,255,255,0.6);">${cr?.periode ?? hde?.periode ?? ''}</span>`;
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
    fetch(API_BASE + 'overview_kaizen.php')
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

// ===== KAIZEN ANALYTICS =====
const KAIZEN_API = API_BASE + 'kaizen_proxy.php';
let awarenessChart = null;
let categoryChart  = null;
let kaizenBulanAktif = <?= (int)date('n') ?>;

function setKaizenBulan(angka, el) {
    kaizenBulanAktif = angka;
    document.querySelectorAll('.kaizen-bulan-btn').forEach(b => {
        b.style.background  = '#fff';
        b.style.borderColor = '#e5e7eb';
        b.style.color       = '#6b7280';
    });
    el.style.background  = '#D0021B';
    el.style.borderColor = '#D0021B';
    el.style.color       = '#fff';
    loadKaizenAnalytics();
}

async function loadKaizenAnalytics() {
    try {
        const tahunEl = document.getElementById('kaizen-filter-tahun');
        const bulan   = kaizenBulanAktif;
        const tahun   = tahunEl ? parseInt(tahunEl.value) : new Date().getFullYear();

        const r = await fetch(`${KAIZEN_API}?bulan=${bulan}&tahun=${tahun}`);
        const d = await r.json();
        if (!d || !d.dept || !d.radar) return; // tambahkan ini

        const monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const periodEl = document.getElementById('kaizen-analytics-period');
        if (periodEl) periodEl.textContent = `${monthNames[bulan]} ${tahun}`;

        renderAwarenessChart(d.dept);
        renderCategoryChart(d.radar);

    } catch(e) {
        console.error('Kaizen Analytics error:', e);
    }
}

function renderAwarenessChart(dept) {
    if (!dept || !dept.labels) return;  // tambahkan ini
    const ctx = document.getElementById('chartAwareness');
    if (!ctx) return;
    if (awarenessChart) { awarenessChart.destroy(); awarenessChart = null; }

    const processedValues = dept.labels.map((_, i) => {
        const ikut  = dept.karyawan_ikut[i]  || 0;
        const total = dept.total_karyawan[i] || 1;
        return parseFloat(((ikut / total) * 100).toFixed(1));
    });

    awarenessChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: dept.labels,
            datasets: [{
                label: 'Participation %',
                data: processedValues,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.2)',
                borderWidth: 1.5,
                pointRadius: 2,
                pointBackgroundColor: '#D0021B',
            }]
        },
        options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements, chart) => {
            const scale = chart.scales.r;
            const canvasPos = Chart.helpers.getRelativePosition(event, chart);
            if (scale._pointLabelItems) {
                scale._pointLabelItems.forEach((item, index) => {
                    if (canvasPos.x >= item.left && canvasPos.x <= item.right &&
                        canvasPos.y >= item.top  && canvasPos.y <= item.bottom) {
                        openKaizenDeptDetail(chart.data.labels[index]);
                    }
                });
            }
        },
            scales: {
                r: {
                    min: 0, max: 100,
                    ticks: { font:{size:7}, stepSize:25, callback: v => v+'%', color:'#9ca3af' },
                    pointLabels: { font:{size:7}, color:'#374151' },
                    grid: { color:'rgba(0,0,0,0.08)' },
                    angleLines: { color:'rgba(0,0,0,0.08)' },
                }
            }
        }
    });
}

function renderCategoryChart(radar) {
    const ctx = document.getElementById('chartCategory');
    if (!ctx) return;
    if (categoryChart) { categoryChart.destroy(); categoryChart = null; }

    const cleanValues     = radar.values.map(v => Math.round(v));
    const totalAktual     = cleanValues.reduce((a, b) => a + b, 0);
    const processedValues = cleanValues.map(v =>
        totalAktual > 0 ? parseFloat(((v / totalAktual) * 100).toFixed(1)) : 0
    );

    categoryChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: radar.labels,
            datasets: [{
                label: 'Contribution Share',
                data: processedValues,
                borderColor: '#8b0000',
                backgroundColor: 'rgba(139,0,0,0.2)',
                borderWidth: 1.5,
                pointRadius: 2,
                pointBackgroundColor: '#8b0000',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (ctx) => {
                        const i = ctx.dataIndex;
                        return `${radar.labels[i]}: ${cleanValues[i]} / ${totalAktual} (${processedValues[i]}%)`;
                    }
                }}
            },
            scales: {
                r: {
                    min: 0, max: 100,
                    ticks: { font:{size:7}, stepSize:25, callback: v => v+'%', color:'#9ca3af' },
                    pointLabels: { font:{size:8}, color:'#374151' },
                    grid: { color:'rgba(0,0,0,0.08)' },
                    angleLines: { color:'rgba(0,0,0,0.08)' },
                }
            }
        }
    });
}

function closeKaizenModal() {
    document.getElementById('modalKaizenDept').style.display = 'none';
}

async function openKaizenDeptDetail(deptName) {
    const modal = document.getElementById('modalKaizenDept');
    modal.style.display = 'flex';

    document.getElementById('modalKaizenTitle').textContent =
        `BREAKDOWN DETAIL KARYAWAN: ${deptName.toUpperCase()}`;
    document.getElementById('modalKaizenTotal').textContent = '—';
    document.getElementById('modalKaizenIkut').textContent  = '—';
    document.getElementById('modalKaizenPersen').textContent = '—';
    document.getElementById('modalKaizenTbody').innerHTML =
        '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Loading...</td></tr>';

    try {
        const tahunEl = document.getElementById('kaizen-filter-tahun');
        const tahun   = tahunEl ? parseInt(tahunEl.value) : new Date().getFullYear();

        const r = await fetch(
            `${API_BASE}kaizen_detail_proxy.php?dept=${encodeURIComponent(deptName)}&bulan=${kaizenBulanAktif}&tahun=${tahun}`
        );
        const d = await r.json();

        document.getElementById('modalKaizenTotal').textContent = d.rekap.total_karyawan;
        document.getElementById('modalKaizenIkut').textContent  = d.rekap.karyawan_ikut;
        document.getElementById('modalKaizenPersen').textContent = d.rekap.persentase + '%';

        const tbody = document.getElementById('modalKaizenTbody');
        if (!d.karyawan || d.karyawan.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada data</td></tr>';
            return;
        }
        tbody.innerHTML = d.karyawan.map((k, i) => `
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:8px 16px;">
                    <span style="background:${i<3?'#D0021B':'#6b7280'};color:#fff;
                                 border-radius:50%; width:20px; height:20px;
                                 display:inline-flex; align-items:center; justify-content:center;
                                 font-size:10px; font-weight:700;">${i+1}</span>
                </td>
                <td style="padding:8px 16px; font-weight:600;">${k.nama_karyawan.trim()}</td>
                <td style="padding:8px 16px; text-align:center; font-weight:700;">${k.total_kaizen} Lembar</td>
            </tr>
        `).join('');

    } catch(e) {
        document.getElementById('modalKaizenTbody').innerHTML =
            '<tr><td colspan="3" style="text-align:center;padding:20px;color:#dc2626;">⚠ Gagal memuat data</td></tr>';
    }
}

// Tutup modal kalau klik backdrop
const modalKaizenEl = document.getElementById('modalKaizenDept');
if (modalKaizenEl) {
    modalKaizenEl.addEventListener('click', function(e) {
        if (e.target === this) closeKaizenModal();
    });
}

loadKaizenAnalytics();

</script>

<!-- Modal Kaizen Dept Detail -->
<div id="modalKaizenDept" style="display:none; position:fixed; inset:0; z-index:9999;
     background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:560px; max-width:90vw;
                max-height:80vh; overflow:hidden; display:flex; flex-direction:column;
                box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <!-- Header -->
        <div id="modalKaizenHeader" style="background:#7B0000; color:#fff;
             padding:14px 20px; display:flex; align-items:center; justify-content:space-between;">
            <div style="font-size:13px; font-weight:800;" id="modalKaizenTitle">DETAIL KARYAWAN</div>
            <button onclick="closeKaizenModal()"
                style="background:none; border:none; color:#fff; font-size:18px; cursor:pointer;">✕</button>
        </div>
        <!-- Rekap -->
        <div id="modalKaizenRekap"
             style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0;
                    border-bottom:1px solid #f0f0f0; flex-shrink:0;">
            <div style="text-align:center; padding:12px 8px; border-right:1px solid #f0f0f0;">
                <div style="font-size:9px; color:#6b7280; text-transform:uppercase; margin-bottom:4px;">Jumlah Karyawan</div>
                <div style="font-size:20px; font-weight:700;" id="modalKaizenTotal">—</div>
                <div style="font-size:10px; color:#6b7280;">Orang</div>
            </div>
            <div style="text-align:center; padding:12px 8px; border-right:1px solid #f0f0f0;">
                <div style="font-size:9px; color:#6b7280; text-transform:uppercase; margin-bottom:4px;">Karyawan Ikut</div>
                <div style="font-size:20px; font-weight:700; color:#D0021B;" id="modalKaizenIkut">—</div>
                <div style="font-size:10px; color:#6b7280;">Orang</div>
            </div>
            <div style="text-align:center; padding:12px 8px;">
                <div style="font-size:9px; color:#6b7280; text-transform:uppercase; margin-bottom:4px;">Persentase</div>
                <div style="font-size:20px; font-weight:700; color:#22c55e;" id="modalKaizenPersen">—</div>
            </div>
        </div>
        <!-- Tabel -->
        <div style="overflow-y:auto; flex:1;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#f4f5f7; position:sticky; top:0;">
                        <th style="padding:8px 16px; text-align:left; font-size:10px; color:#6b7280; font-weight:700;">Rank</th>
                        <th style="padding:8px 16px; text-align:left; font-size:10px; color:#6b7280; font-weight:700;">Nama Karyawan</th>
                        <th style="padding:8px 16px; text-align:center; font-size:10px; color:#6b7280; font-weight:700;">Kontribusi Kaizen</th>
                    </tr>
                </thead>
                <tbody id="modalKaizenTbody">
                    <tr><td colspan="3" style="text-align:center; padding:20px; color:#9ca3af;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>