<?php
$is_detail  = true;
$page_title = 'KAIZEN ANALYTICS';

$bulan_fy = [
    4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep',
    10=>'Oct', 11=>'Nov', 12=>'Dec', 1=>'Jan', 2=>'Feb', 3=>'Mar'
];
$cur_month = (int)date('n');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <title>Kaizen Analytics — YWK Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        ::-webkit-scrollbar { display:none; }
        html, body { height:100vh; overflow:hidden; background:#f4f5f7; }
        .dashboard { height:100vh; display:flex; flex-direction:column; overflow:hidden; }
        .content-wrapper {
            flex:1; min-height:0; overflow:hidden;
            display:flex; flex-direction:column;
            gap:0.5rem; padding:0.6rem 0.75rem;
        }

        /* Header */
        .ka-header { display:flex; align-items:center; justify-content:space-between; flex-shrink:0; gap:12px; }
        .ka-header-center { text-align:center; flex:1; }
        .ka-header-title  { font-size:20px; font-weight:800; color:#1a1a1a; letter-spacing:.02em; }
        .ka-header-title span { font-weight:400; }

        /* Peak card */
        .ka-peak-card {
            display:flex; align-items:center; gap:10px;
            background:linear-gradient(135deg,#7B0000,#D0021B);
            border-radius:10px; padding:8px 16px; flex-shrink:0; min-width:180px;
        }
        .ka-peak-label { font-size:9px; font-weight:800; color:rgba(255,255,255,.75); text-transform:uppercase; letter-spacing:.05em; line-height:1.3; }
        .ka-peak-dept  { font-size:11px; font-weight:700; color:#fff; }
        .ka-peak-val   { font-size:24px; font-weight:800; color:#fbbf24; line-height:1; white-space:nowrap; }

        /* Filter bar */
        .ka-filter-bar { display:flex; align-items:center; justify-content:center; gap:4px; flex-shrink:0; flex-wrap:wrap; }
        .ka-filter-label { font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.06em; margin-right:2px; }
        .ka-year-select {
            font-size:12px; font-weight:600; color:#1a1a1a;
            background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            padding:5px 24px 5px 10px; cursor:pointer; outline:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 8px center; margin-right:6px;
        }
        .ka-bulan-btn { font-size:11px; font-weight:600; padding:5px 13px; border-radius:6px; cursor:pointer; transition:all .15s; border:1px solid #e5e7eb; background:#fff; color:#6b7280; }
        .ka-bulan-btn.active { background:#7B0000; border-color:#7B0000; color:#fff; }
        .ka-bulan-btn:hover:not(.active) { border-color:#D0021B; color:#D0021B; }

        /* Charts grid — 4 kolom */
        .ka-charts-grid {
            display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr;
            gap:0.6rem; flex:0 0 auto; height:360px;
        }
        .ka-chart-card {
            background:#fff; border:1.5px solid #e5e7eb; border-top:3px solid #D0021B;
            border-radius:12px; padding:0.9rem;
            display:flex; flex-direction:column; min-height:0;
            box-shadow:0 1px 4px rgba(0,0,0,0.04);
        }
        .ka-chart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; flex-shrink:0; }
        .ka-chart-title {
            font-size:11px; font-weight:800; color:#1a1a1a;
            text-transform:uppercase; letter-spacing:.07em;
            display:flex; align-items:center; gap:6px;
        }
        .ka-chart-title::before { content:''; width:3px; height:14px; background:#D0021B; border-radius:2px; flex-shrink:0; }
        .ka-chart-sub { font-size:9px; font-weight:400; color:#9ca3af; margin-left:2px; text-transform:none; letter-spacing:0; }
        .ka-chart-wrap { flex:1; position:relative; min-height:0; }

        /* Detail table */
        .ka-table-card {
            background:#fff; border:1.5px solid #e5e7eb; border-top:3px solid #D0021B;
            border-radius:12px; flex:1; min-height:0; overflow:hidden;
            display:flex; flex-direction:column;
            box-shadow:0 1px 4px rgba(0,0,0,0.04);
        }
        .ka-table-header { padding:8px 14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:6px; flex-shrink:0; }
        .ka-table-header-title {
            font-size:11px; font-weight:800; color:#1a1a1a;
            text-transform:uppercase; letter-spacing:.07em;
            display:flex; align-items:center; gap:6px;
        }
        .ka-table-header-title::before { content:''; width:3px; height:14px; background:#D0021B; border-radius:2px; flex-shrink:0; }
        .ka-table-scroll { overflow-y:auto; flex:1; min-height:0; }
        .ka-detail-table { width:100%; border-collapse:collapse; font-size:11px; }
        .ka-detail-table th {
            padding:6px 12px; text-align:left; position:sticky; top:0;
            font-size:10px; color:#6b7280; font-weight:700;
            background:#f4f5f7; border-bottom:1px solid #e5e7eb; white-space:nowrap;
        }
        .ka-detail-table td { padding:6px 12px; border-bottom:1px solid #f5f5f5; color:#1a1a1a; }
        .ka-detail-table tr:last-child td { border-bottom:none; }
        .ka-detail-table tr:hover td { background:#fafafa; }
        .ka-detail-table td:first-child { color:#9ca3af; font-weight:600; width:40px; }
        .ka-score-badge { display:inline-block; padding:1px 8px; border-radius:20px; font-size:10px; font-weight:700; }
        .ka-score-badge.good { background:#EAF3DE; color:#3B6D11; }
        .ka-score-badge.zero { background:#f4f5f7; color:#9ca3af; }

        /* Detail button */
        .btn-detail-perf {
            font-size:10px; font-weight:700; padding:3px 10px;
            border-radius:6px; border:1.5px solid #D0021B;
            color:#fff; background:#D0021B; cursor:pointer; transition:all .15s;
        }
        .btn-detail-perf:hover { background:#7B0000; border-color:#7B0000; }
        .lihat-semua-btn {
            font-size:10px; font-weight:700; padding:4px 12px;
            border-radius:6px; border:1.5px solid #D0021B;
            color:#D0021B; background:#fff; cursor:pointer; transition:all .15s;
        }
        .lihat-semua-btn:hover { background:#D0021B; color:#fff; }

        /* Modal */
        .ka-modal-overlay { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
        .ka-modal { background:#fff; border-radius:16px; width:600px; max-width:90vw; max-height:80vh; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
        .ka-modal-header { background:#7B0000; color:#fff; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
        .ka-modal-title { font-size:13px; font-weight:800; }
        .ka-modal-close { background:none; border:none; color:#fff; font-size:18px; cursor:pointer; }
        .ka-modal-rekap { display:grid; grid-template-columns:1fr 1fr 1fr; border-bottom:1px solid #f0f0f0; flex-shrink:0; }
        .ka-modal-rekap-item { text-align:center; padding:12px 8px; border-right:1px solid #f0f0f0; }
        .ka-modal-rekap-item:last-child { border-right:none; }
        .ka-modal-rekap-label { font-size:9px; color:#6b7280; text-transform:uppercase; margin-bottom:4px; }
        .ka-modal-rekap-val { font-size:20px; font-weight:700; }
        .ka-modal-body { overflow-y:auto; flex:1; }
        .ka-modal-table { width:100%; border-collapse:collapse; font-size:12px; }
        .ka-modal-table th { padding:8px 16px; text-align:left; font-size:10px; color:#6b7280; font-weight:700; background:#f4f5f7; position:sticky; top:0; }
        .ka-modal-table td { padding:8px 16px; border-bottom:1px solid #f0f0f0; }
        .ka-rank-badge { width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#fff; }
    </style>
</head>
<body>
<div class="dashboard">
    <?php include '../components/topbar.php'; ?>
    <div class="content-wrapper">

        <!-- Header -->
        <div class="ka-header">
            <div style="min-width:120px;"></div>
            <div class="ka-header-center">
                <div class="ka-header-title"><b>KAIZEN</b> <span>ANALYTICS SYSTEM</span></div>
            </div>
            <div class="ka-peak-card">
                <div>
                    <div class="ka-peak-label">Peak Performance</div>
                    <div class="ka-peak-dept" id="ka-peak-dept">—</div>
                </div>
                <div class="ka-peak-val" id="ka-peak-val">—</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="ka-filter-bar">
            <span class="ka-filter-label">Fiscal Year:</span>
            <select id="ka-tahun" class="ka-year-select" onchange="loadKaizenAnalytics()">
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026" selected>2026</option>
            </select>
            <?php foreach ($bulan_fy as $num => $label): ?>
            <button class="ka-bulan-btn <?= $num == $cur_month ? 'active' : '' ?>"
                    id="kabtn-<?= $num ?>"
                    onclick="setKaBulan(<?= $num ?>, this)">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Charts: 4 kolom -->
        <div class="ka-charts-grid">

            <!-- Awareness Ratio -->
            <div class="ka-chart-card">
                <div class="ka-chart-header">
                    <div class="ka-chart-title">Awareness Ratio <span class="ka-chart-sub">*Klik dept untuk detail</span></div>
                </div>
                <div class="ka-chart-wrap"><canvas id="kaChartAwareness"></canvas></div>
            </div>

            <!-- Category Tendency -->
            <div class="ka-chart-card">
                <div class="ka-chart-header">
                    <div class="ka-chart-title">Category Tendency</div>
                </div>
                <div class="ka-chart-wrap"><canvas id="kaChartCategory"></canvas></div>
            </div>

            <!-- Top Kaizen Idea -->
            <div class="ka-chart-card">
                <div class="ka-chart-header">
                    <div class="ka-chart-title">Top Kaizen Idea</div>
                </div>
                <div class="ka-chart-wrap"><canvas id="kaChartIdea"></canvas></div>
            </div>

            <!-- Top Performance -->
            <div class="ka-chart-card">
                <div class="ka-chart-header">
                    <div class="ka-chart-title">Top Performance</div>
                    <button class="btn-detail-perf" onclick="openModalTopPerf()">&#9776; Detail</button>
                </div>
                <div class="ka-chart-wrap"><canvas id="kaChartPerformance"></canvas></div>
            </div>

        </div>

        <!-- Data Detail Kaizen Table -->
        <div class="ka-table-card">
            <div class="ka-table-header">
                <div class="ka-table-header-title">Data Detail Kaizen</div>
            </div>
            <div class="ka-table-scroll">
                <table class="ka-detail-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Employee</th>
                            <th>Dept</th>
                            <th style="text-align:center;">Score</th>
                        </tr>
                    </thead>
                    <tbody id="kaDetailTbody">
                        <tr><td colspan="6" style="text-align:center;padding:16px;color:#9ca3af;">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal Dept Detail -->
<div class="ka-modal-overlay" id="kaModalDept">
    <div class="ka-modal">
        <div class="ka-modal-header">
            <div class="ka-modal-title" id="kaModalDeptTitle">DETAIL KARYAWAN</div>
            <button class="ka-modal-close" onclick="closeModal('kaModalDept')">✕</button>
        </div>
        <div class="ka-modal-rekap">
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Jumlah Karyawan</div>
                <div class="ka-modal-rekap-val" id="kaModalDeptTotal">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Karyawan Ikut</div>
                <div class="ka-modal-rekap-val" id="kaModalDeptIkut" style="color:#D0021B;">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Persentase</div>
                <div class="ka-modal-rekap-val" id="kaModalDeptPersen" style="color:#22c55e;">—</div>
            </div>
        </div>
        <div class="ka-modal-body">
            <table class="ka-modal-table">
                <thead><tr><th style="width:60px;">Rank</th><th>Nama Karyawan</th><th style="text-align:center;">Kontribusi</th></tr></thead>
                <tbody id="kaModalDeptTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Top Performance Detail -->
<div class="ka-modal-overlay" id="kaModalTopPerf">
    <div class="ka-modal">
        <div class="ka-modal-header">
            <div class="ka-modal-title">TOP PERFORMANCE — SEMUA KARYAWAN</div>
            <button class="ka-modal-close" onclick="closeModal('kaModalTopPerf')">✕</button>
        </div>
        <div class="ka-modal-rekap">
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Total Karyawan Submit</div>
                <div class="ka-modal-rekap-val" id="perfTotalSubmit" style="color:#D0021B;">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Total Submission</div>
                <div class="ka-modal-rekap-val" id="perfTotalSubmisi">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Total Nilai</div>
                <div class="ka-modal-rekap-val" id="perfTotalNilai" style="color:#22c55e;">—</div>
            </div>
        </div>
        <div class="ka-modal-body">
            <table class="ka-modal-table">
                <thead><tr><th style="width:60px;">Rank</th><th>Nama Karyawan</th><th style="text-align:center;">Jumlah Submit</th><th style="text-align:center;">Total Nilai</th></tr></thead>
                <tbody id="kaModalTopPerfTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const KAIZEN_PROXY        = '../api/kaizen_proxy.php';
const KAIZEN_DETAIL_PROXY = '../api/kaizen_detail_proxy.php';

let kaBulanAktif     = <?= $cur_month ?>;
let kaCharts         = {};
let masterTopPerf    = [];

function setKaBulan(angka, el) {
    kaBulanAktif = angka;
    document.querySelectorAll('.ka-bulan-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    loadKaizenAnalytics();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function destroyChart(id) {
    if (kaCharts[id]) { kaCharts[id].destroy(); delete kaCharts[id]; }
}

async function loadKaizenAnalytics() {
    try {
        const tahun = document.getElementById('ka-tahun').value;
        const r     = await fetch(`${KAIZEN_PROXY}?bulan=${kaBulanAktif}&tahun=${tahun}`);
        const d     = await r.json();

        renderAwareness(d.dept);
        renderCategory(d.radar);
        renderTopIdea(d.employee);
        renderTopPerformance(d.top_performance);
        renderDetailTable(d.kaizen_list ?? []);
        masterTopPerf = d.top_performance ?? null;

        // Peak performance
        const pvals = d.dept.labels.map((_, i) => {
            const ikut  = d.dept.karyawan_ikut[i]  || 0;
            const total = d.dept.total_karyawan[i]  || 1;
            return parseFloat(((ikut / total) * 100).toFixed(1));
        });
        const maxVal = Math.max(...pvals);
        const maxIdx = pvals.indexOf(maxVal);
        document.getElementById('ka-peak-dept').textContent = d.dept.labels[maxIdx] ?? '—';
        document.getElementById('ka-peak-val').textContent  = maxVal + '%';

    } catch(e) { console.error('Kaizen error:', e); }
}

// ─── AWARENESS RADAR ─────────────────────────────────────────────────────────
function renderAwareness(dept) {
    destroyChart('awareness');
    const ctx = document.getElementById('kaChartAwareness');
    if (!ctx) return;

    const vals = dept.labels.map((_, i) => {
        const ikut  = dept.karyawan_ikut[i]  || 0;
        const total = dept.total_karyawan[i]  || 1;
        return parseFloat(((ikut / total) * 100).toFixed(1));
    });

    kaCharts['awareness'] = new Chart(ctx, {
        type: 'radar',
        data: { labels: dept.labels, datasets: [{ label:'Participation %', data:vals, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.2)', borderWidth:2, pointRadius:2.5, pointBackgroundColor:'#ef4444' }] },
        options: {
            responsive:true, maintainAspectRatio:false,
            onClick: (event, elements, chart) => {
                const pos   = Chart.helpers.getRelativePosition(event, chart);
                const scale = chart.scales.r;
                if (scale._pointLabelItems) {
                    scale._pointLabelItems.forEach((item, index) => {
                        if (pos.x >= item.left && pos.x <= item.right && pos.y >= item.top && pos.y <= item.bottom)
                            openModalDept(chart.data.labels[index]);
                    });
                }
            },
            plugins: {
                legend: { display:false },
                tooltip: { callbacks: { label: (ctx) => {
                    const i = ctx.dataIndex;
                    return `${dept.labels[i]}: ${vals[i]}% (${dept.karyawan_ikut[i]}/${dept.total_karyawan[i]})`;
                }}}
            },
            scales: { r: { min:0, max:100, ticks:{ font:{size:8}, stepSize:20, callback:v=>v+'%', color:'#9ca3af', backdropColor:'transparent' }, pointLabels:{ font:{size:8}, color:'#374151' }, grid:{ color:'rgba(0,0,0,0.07)' }, angleLines:{ color:'rgba(0,0,0,0.07)' } } }
        }
    });
}

// ─── CATEGORY RADAR ──────────────────────────────────────────────────────────
function renderCategory(radar) {
    destroyChart('category');
    const ctx = document.getElementById('kaChartCategory');
    if (!ctx) return;

    const clean  = radar.values.map(v => Math.round(v));
    const total  = clean.reduce((a,b) => a+b, 0);
    const pct    = clean.map(v => total > 0 ? parseFloat(((v/total)*100).toFixed(1)) : 0);

    kaCharts['category'] = new Chart(ctx, {
        type: 'radar',
        data: { labels: radar.labels, datasets: [{ label:'Share', data:pct, borderColor:'#8b0000', backgroundColor:'rgba(139,0,0,0.15)', borderWidth:1.5, pointRadius:2.5, pointBackgroundColor:'#8b0000' }] },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{display:false}, tooltip:{ callbacks:{ label:(ctx)=>{ const i=ctx.dataIndex; return `${radar.labels[i]}: ${clean[i]}/${total} (${pct[i]}%)`; }}} },
            scales: { r: { min:0, max:100, ticks:{ font:{size:8}, stepSize:20, callback:v=>v+'%', color:'#9ca3af', backdropColor:'transparent' }, pointLabels:{ font:{size:9}, color:'#374151' }, grid:{ color:'rgba(0,0,0,0.07)' }, angleLines:{ color:'rgba(0,0,0,0.07)' } } }
        }
    });
}

// ─── TOP KAIZEN IDEA BAR ─────────────────────────────────────────────────────
function renderTopIdea(employee) {
    destroyChart('idea');
    // Restore canvas kalau sebelumnya diganti innerHTML
    const wrap = document.getElementById('kaChartIdea')?.parentElement ?? document.querySelector('#kaChartIdea')?.closest('.ka-chart-wrap');
    const wrapEl = document.querySelectorAll('.ka-chart-wrap')[2];
    if (!document.getElementById('kaChartIdea')) {
        wrapEl.innerHTML = '<canvas id="kaChartIdea"></canvas>';
    }
    const ctx = document.getElementById('kaChartIdea');
    if (!ctx) return;

    const labels = (employee?.labels ?? []).slice(0,5).map(l => l.replace(/\r\n|\r|\n/g,'').trim());
    const values = (employee?.values ?? []).slice(0,5);

    if (!labels.length) {
        wrapEl.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:40px;font-size:12px;">Belum ada data</div>';
        return;
    }

    kaCharts['idea'] = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data:values, backgroundColor:'#8b0000', borderRadius:5 }] },
        options: {
            responsive:true, maintainAspectRatio:false,
            scales: { x:{ display:false }, y:{ beginAtZero:true, suggestedMax: Math.max(...values)+15, ticks:{font:{size:9}}, grid:{color:'#f0f0f0'} } },
            plugins: {
                legend:{ display:false },
                tooltip:{ callbacks:{ label: ctx => ` ${ctx.parsed.y} Poin` } },
                datalabels: { anchor:'start', align:'end', color:'#fff', font:{weight:'bold',size:10}, rotation:-90, formatter:(v,ctx) => ctx.chart.data.labels[ctx.dataIndex] }
            }
        },
        plugins: [ChartDataLabels, {
            id: 'scoreAbove',
            afterDraw(chart) {
                const ctx2 = chart.ctx;
                chart.getDatasetMeta(0).data.forEach((bar, i) => {
                    const val = chart.data.datasets[0].data[i];
                    ctx2.save();
                    ctx2.fillStyle = '#1e293b'; ctx2.font = 'bold 11px Segoe UI';
                    ctx2.textAlign = 'center'; ctx2.textBaseline = 'bottom';
                    ctx2.fillText(val, bar.x, bar.y - 2);
                    ctx2.restore();
                });
            }
        }]
    });
}

// ─── TOP PERFORMANCE BAR ─────────────────────────────────────────────────────
function renderTopPerformance(tp) {
    destroyChart('performance');
    // Restore canvas kalau sebelumnya diganti innerHTML
    const wrapEl = document.querySelectorAll('.ka-chart-wrap')[3];
    if (!document.getElementById('kaChartPerformance')) {
        wrapEl.innerHTML = '<canvas id="kaChartPerformance"></canvas>';
    }
    const ctx = document.getElementById('kaChartPerformance');
    if (!ctx) return;

    if (!tp || !tp.labels || !tp.labels.length) {
        wrapEl.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:40px;font-size:12px;">Belum ada data</div>';
        return;
    }

    const labels      = tp.labels.slice(0,5).map(l => l.replace(/\r\n|\r|\n/g,'').trim());
    const values      = tp.values.slice(0,5);
    const submissions = (tp.submissions ?? []).slice(0,5);

    kaCharts['performance'] = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data:values, backgroundColor:'#8b0000', borderRadius:5 }] },
        options: {
            responsive:true, maintainAspectRatio:false,
            scales: { x:{ display:false }, y:{ beginAtZero:true, suggestedMax: Math.max(...values)+15, ticks:{font:{size:9}}, grid:{color:'#f0f0f0'} } },
            plugins: {
                legend:{ display:false },
                tooltip:{ callbacks:{
                    title: items => labels[items[0].dataIndex],
                    label: item  => [`Total Nilai: ${values[item.dataIndex]}`, `Jumlah Submit: ${submissions[item.dataIndex] ?? '—'}x`]
                }},
                datalabels: { anchor:'start', align:'end', color:'#fff', font:{weight:'bold',size:10}, rotation:-90, formatter:(v,ctx) => ctx.chart.data.labels[ctx.dataIndex] }
            }
        },
        plugins: [ChartDataLabels, {
            id: 'scoreAbovePerf',
            afterDraw(chart) {
                const ctx2 = chart.ctx;
                chart.getDatasetMeta(0).data.forEach((bar, i) => {
                    const val = chart.data.datasets[0].data[i];
                    ctx2.save();
                    ctx2.fillStyle = '#1e293b'; ctx2.font = 'bold 11px Segoe UI';
                    ctx2.textAlign = 'center'; ctx2.textBaseline = 'bottom';
                    ctx2.fillText(val, bar.x, bar.y - 2);
                    ctx2.restore();
                });
            }
        }]
    });
}

// ─── DETAIL TABLE ─────────────────────────────────────────────────────────────
function renderDetailTable(list) {
    const tbody = document.getElementById('kaDetailTbody');
    if (!tbody) return;
    if (!list || !list.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#9ca3af;">Belum ada data kaizen bulan ini</td></tr>';
        return;
    }
    tbody.innerHTML = list.map((item, i) => {
        const score    = parseInt(item.total_score ?? 0);
        const badgeCls = score > 0 ? 'good' : 'zero';
        const employee = (item.nama_karyawan ?? '—').replace(/\r\n|\r|\n/g,'').trim();
        return `<tr>
            <td>${i+1}</td>
            <td style="font-weight:600;">${item.judul_kaizen ?? '—'}</td>
            <td><span style="font-size:10px;font-weight:700;color:#D0021B;">${item.nama_category ?? '—'}</span></td>
            <td>${employee}</td>
            <td style="color:#6b7280;">${item.nama_dept ?? '—'}</td>
            <td style="text-align:center;"><span class="ka-score-badge ${badgeCls}">${score}</span></td>
        </tr>`;
    }).join('');
}

// ─── MODAL DEPT ───────────────────────────────────────────────────────────────
async function openModalDept(deptName) {
    const modal = document.getElementById('kaModalDept');
    modal.style.display = 'flex';
    document.getElementById('kaModalDeptTitle').textContent  = `BREAKDOWN: ${deptName.toUpperCase()}`;
    document.getElementById('kaModalDeptTotal').textContent  = '—';
    document.getElementById('kaModalDeptIkut').textContent   = '—';
    document.getElementById('kaModalDeptPersen').textContent = '—';
    document.getElementById('kaModalDeptTbody').innerHTML    = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Loading...</td></tr>';

    try {
        const tahun = document.getElementById('ka-tahun').value;
        const r = await fetch(`${KAIZEN_DETAIL_PROXY}?dept=${encodeURIComponent(deptName)}&bulan=${kaBulanAktif}&tahun=${tahun}`);
        const d = await r.json();

        document.getElementById('kaModalDeptTotal').textContent  = d.rekap.total_karyawan;
        document.getElementById('kaModalDeptIkut').textContent   = d.rekap.karyawan_ikut;
        document.getElementById('kaModalDeptPersen').textContent = d.rekap.persentase + '%';

        const tbody = document.getElementById('kaModalDeptTbody');
        if (!d.karyawan || !d.karyawan.length) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada data</td></tr>';
            return;
        }
        tbody.innerHTML = d.karyawan.map((k, i) => `
            <tr>
                <td><span class="ka-rank-badge" style="background:${i<3?'#D0021B':'#6b7280'}">${i+1}</span></td>
                <td style="font-weight:600;">${k.nama_karyawan.trim()}</td>
                <td style="text-align:center;font-weight:700;">${k.total_kaizen} Lembar</td>
            </tr>`).join('');
    } catch(e) {
        document.getElementById('kaModalDeptTbody').innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#dc2626;">⚠ Gagal memuat</td></tr>';
    }
}

// ─── MODAL TOP PERFORMANCE ────────────────────────────────────────────────────
function openModalTopPerf() {
    const modal = document.getElementById('kaModalTopPerf');
    modal.style.display = 'flex';
    const tbody = document.getElementById('kaModalTopPerfTbody');

    if (!masterTopPerf || !masterTopPerf.labels || !masterTopPerf.labels.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada data</td></tr>';
        document.getElementById('perfTotalSubmit').textContent  = '0';
        document.getElementById('perfTotalSubmisi').textContent = '0';
        document.getElementById('perfTotalNilai').textContent   = '0';
        return;
    }

    const submissions = masterTopPerf.submissions ?? masterTopPerf.labels.map(() => 0);
    let totalSubmisi = 0, totalNilai = 0;
    masterTopPerf.labels.forEach((_, i) => {
        totalSubmisi += submissions[i] ?? 0;
        totalNilai   += masterTopPerf.values[i] ?? 0;
    });

    document.getElementById('perfTotalSubmit').textContent  = masterTopPerf.labels.length;
    document.getElementById('perfTotalSubmisi').textContent = totalSubmisi;
    document.getElementById('perfTotalNilai').textContent   = totalNilai;

    const medal = ['🥇','🥈','🥉'];
    tbody.innerHTML = masterTopPerf.labels.map((name, i) => `
        <tr>
            <td><span class="ka-rank-badge" style="background:${i<3?'#D0021B':'#6b7280'}">${i+1}</span> ${medal[i]??''}</td>
            <td style="font-weight:600;">${name.trim()}</td>
            <td style="text-align:center;">${submissions[i] ?? 0}x submit</td>
            <td style="text-align:center;font-weight:700;color:#D0021B;">${masterTopPerf.values[i]} Poin</td>
        </tr>`).join('');
}

// Close modal on overlay click
['kaModalDept','kaModalTopPerf'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// ─── INIT ─────────────────────────────────────────────────────────────────────
loadKaizenAnalytics();
</script>
</body>
</html>