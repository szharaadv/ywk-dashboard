<?php
$is_detail  = true;
$page_title = 'KAIZEN ANALYTICS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <title>Kaizen Analytics — YWK Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { display:none; }
        html, body { height:100vh; overflow:hidden; }
        .dashboard { height:100vh; display:flex; flex-direction:column; overflow:hidden; }
        .content-wrapper {
            flex:1; min-height:0; overflow:hidden;
            display:flex; flex-direction:column;
            gap:0.5rem; padding:0.75rem;
        }

        /* Filter bar */
        .ka-filter-bar {
            display:flex; align-items:center; gap:8px;
            flex-shrink:0; flex-wrap:wrap;
        }
        .ka-filter-bar label {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:.06em;
        }
        .ka-year-select {
            font-size:12px; font-weight:600; color:#1a1a1a;
            background:#fff; border:1px solid #e5e7eb;
            border-radius:8px; padding:5px 24px 5px 10px;
            cursor:pointer; outline:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 8px center;
        }
        .ka-bulan-btn {
            font-size:10px; font-weight:700; padding:4px 10px;
            border-radius:6px; cursor:pointer; transition:all .15s;
            border:1px solid #e5e7eb; background:#fff; color:#6b7280;
        }
        .ka-bulan-btn.active {
            background:#D0021B; border-color:#D0021B; color:#fff;
        }

        /* Peak card */
        .ka-peak-card {
            display:flex; align-items:center; gap:10px;
            background:linear-gradient(135deg,#7B0000,#D0021B);
            border-radius:10px; padding:8px 16px; flex-shrink:0;
            margin-left:auto;
        }
        .ka-peak-label { font-size:9px; font-weight:800; color:rgba(255,255,255,.8); text-transform:uppercase; }
        .ka-peak-dept  { font-size:11px; font-weight:700; color:#fff; }
        .ka-peak-val   { font-size:22px; font-weight:800; color:#fbbf24; }

        /* Charts grid */
        .ka-charts-grid {
            display:grid; grid-template-columns:1fr 1fr;
            gap:0.5rem; flex:1; min-height:0;
        }
        .ka-chart-card {
            background:#fff; border:1px solid #e5e7eb;
            border-radius:10px; padding:0.75rem;
            display:flex; flex-direction:column; min-height:0;
        }
        .ka-chart-title {
            font-size:10px; font-weight:700; color:#374151;
            text-transform:uppercase; letter-spacing:.06em;
            margin-bottom:6px; flex-shrink:0;
            display:flex; align-items:center; gap:6px;
        }
        .ka-chart-title::before {
            content:''; width:3px; height:14px;
            background:#D0021B; border-radius:2px; flex-shrink:0;
        }
        .ka-chart-wrap { flex:1; position:relative; min-height:0; }

        /* Modal */
        .ka-modal-overlay {
            display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,0.5);
            align-items:center; justify-content:center;
        }
        .ka-modal {
            background:#fff; border-radius:16px; width:560px;
            max-width:90vw; max-height:80vh; overflow:hidden;
            display:flex; flex-direction:column;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        .ka-modal-header {
            background:#7B0000; color:#fff;
            padding:14px 20px;
            display:flex; align-items:center; justify-content:space-between;
            flex-shrink:0;
        }
        .ka-modal-title { font-size:13px; font-weight:800; }
        .ka-modal-close {
            background:none; border:none; color:#fff;
            font-size:18px; cursor:pointer;
        }
        .ka-modal-rekap {
            display:grid; grid-template-columns:1fr 1fr 1fr;
            border-bottom:1px solid #f0f0f0; flex-shrink:0;
        }
        .ka-modal-rekap-item {
            text-align:center; padding:12px 8px;
            border-right:1px solid #f0f0f0;
        }
        .ka-modal-rekap-item:last-child { border-right:none; }
        .ka-modal-rekap-label {
            font-size:9px; color:#6b7280;
            text-transform:uppercase; margin-bottom:4px;
        }
        .ka-modal-rekap-val { font-size:20px; font-weight:700; }
        .ka-modal-body { overflow-y:auto; flex:1; }
        .ka-modal-table { width:100%; border-collapse:collapse; font-size:12px; }
        .ka-modal-table th {
            padding:8px 16px; text-align:left;
            font-size:10px; color:#6b7280; font-weight:700;
            background:#f4f5f7; position:sticky; top:0;
        }
        .ka-modal-table td {
            padding:8px 16px; border-bottom:1px solid #f0f0f0;
        }
        .ka-rank-badge {
            width:20px; height:20px; border-radius:50%;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:10px; font-weight:700; color:#fff;
        }
    </style>
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Filter Bar -->
        <div class="ka-filter-bar">
            <label>TAHUN:</label>
            <select id="ka-tahun" class="ka-year-select" onchange="loadKaizenAnalytics()">
                <option value="2024">2024</option>
                <option value="2025" selected>2025</option>
                <option value="2026">2026</option>
            </select>

            <?php
            $bulan_list = [
                1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
            ];
            foreach ($bulan_list as $num => $label):
            ?>
            <button class="ka-bulan-btn <?= $num == 8 ? 'active' : '' ?>"
                    id="kabtn-<?= $num ?>"
                    onclick="setKaBulan(<?= $num ?>, this)">
                <?= $label ?>
            </button>
            <?php endforeach; ?>

            <!-- Peak card -->
            <div class="ka-peak-card">
                <div>
                    <div class="ka-peak-label">Peak Performance</div>
                    <div class="ka-peak-dept" id="ka-peak-dept">—</div>
                </div>
                <div class="ka-peak-val" id="ka-peak-val">—</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="ka-charts-grid" style="grid-template-columns:1fr 1fr 1fr;">
            <!-- Awareness Ratio -->
            <div class="ka-chart-card">
                <div class="ka-chart-title">
                    Awareness Ratio
                    <span style="font-size:9px;font-weight:400;color:#9ca3af;margin-left:4px;">
                        *Klik nama departemen untuk detail
                    </span>
                </div>
                <div class="ka-chart-wrap">
                    <canvas id="kaChartAwareness"></canvas>
                </div>
            </div>
            <!-- Category Tendency -->
            <div class="ka-chart-card">
                <div class="ka-chart-title">Category Tendency</div>
                <div class="ka-chart-wrap">
                    <canvas id="kaChartCategory"></canvas>
                </div>
           </div>

            <!-- Top Score Employee -->
            <div class="ka-chart-card">
                <div class="ka-chart-title" style="justify-content:space-between;">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="content:'';width:3px;height:14px;background:#D0021B;border-radius:2px;display:inline-block;"></span>
                        Top Score Employee
                    </span>
                    <button onclick="openKaAllEmployee()"
                        style="font-size:10px;font-weight:700;padding:3px 10px;
                               border-radius:6px;border:1px solid #D0021B;
                               color:#D0021B;background:#fff;cursor:pointer;">
                        Lihat Semua
                    </button>
                </div>
                <div class="ka-chart-wrap">
                    <canvas id="kaChartEmployee"></canvas>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Modal Detail Karyawan -->
<div class="ka-modal-overlay" id="kaModal">
    <div class="ka-modal">
        <div class="ka-modal-header">
            <div class="ka-modal-title" id="kaModalTitle">DETAIL KARYAWAN</div>
            <button class="ka-modal-close" onclick="closeKaModal()">✕</button>
        </div>
        <div class="ka-modal-rekap">
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Jumlah Karyawan</div>
                <div class="ka-modal-rekap-val" id="kaModalTotal">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Karyawan Ikut</div>
                <div class="ka-modal-rekap-val" id="kaModalIkut" style="color:#D0021B;">—</div>
            </div>
            <div class="ka-modal-rekap-item">
                <div class="ka-modal-rekap-label">Persentase</div>
                <div class="ka-modal-rekap-val" id="kaModalPersen" style="color:#22c55e;">—</div>
            </div>
        </div>
        <div class="ka-modal-body">
            <table class="ka-modal-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Rank</th>
                        <th>Nama Karyawan</th>
                        <th style="text-align:center;">Kontribusi</th>
                    </tr>
                </thead>
                <tbody id="kaModalTbody">
                    <tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const KAIZEN_PROXY       = '../api/kaizen_proxy.php';
const KAIZEN_DETAIL_PROXY = '../api/kaizen_detail_proxy.php';

let kaBulanAktif      = 8;
let kaAwarenessChart  = null;
let kaCategoryChart   = null;
let kaEmployeeChart   = null;
let kaAllEmployeeData = [];

function setKaBulan(angka, el) {
    kaBulanAktif = angka;
    document.querySelectorAll('.ka-bulan-btn').forEach(b => {
        b.classList.remove('active');
    });
    el.classList.add('active');
    loadKaizenAnalytics();
}

async function loadKaizenAnalytics() {
    try {
        const tahun = document.getElementById('ka-tahun').value;
        const r = await fetch(`${KAIZEN_PROXY}?bulan=${kaBulanAktif}&tahun=${tahun}`);
        const d = await r.json();

        renderAwarenessChart(d.dept);
        renderCategoryChart(d.radar);
        renderEmployeeChart(d.employee);
        kaAllEmployeeData = d.employee;

        // Peak performance
        const processedValues = d.dept.labels.map((_, i) => {
            const ikut  = d.dept.karyawan_ikut[i]  || 0;
            const total = d.dept.total_karyawan[i] || 1;
            return parseFloat(((ikut / total) * 100).toFixed(1));
        });
        const maxVal = Math.max(...processedValues);
        const maxIdx = processedValues.indexOf(maxVal);
        document.getElementById('ka-peak-dept').textContent = d.dept.labels[maxIdx] ?? '—';
        document.getElementById('ka-peak-val').textContent  = maxVal + '%';

    } catch(e) {
        console.error('Kaizen Analytics error:', e);
    }
}

function renderAwarenessChart(dept) {
    const ctx = document.getElementById('kaChartAwareness');
    if (!ctx) return;
    if (kaAwarenessChart) { kaAwarenessChart.destroy(); kaAwarenessChart = null; }

    const processedValues = dept.labels.map((_, i) => {
        const ikut  = dept.karyawan_ikut[i]  || 0;
        const total = dept.total_karyawan[i] || 1;
        return parseFloat(((ikut / total) * 100).toFixed(1));
    });

    kaAwarenessChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: dept.labels,
            datasets: [{
                label: 'Participation %',
                data: processedValues,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.15)',
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
                const pos   = Chart.helpers.getRelativePosition(event, chart);
                if (scale._pointLabelItems) {
                    scale._pointLabelItems.forEach((item, index) => {
                        if (pos.x >= item.left && pos.x <= item.right &&
                            pos.y >= item.top  && pos.y <= item.bottom) {
                            openKaModal(chart.data.labels[index]);
                        }
                    });
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (ctx) => {
                        const i = ctx.dataIndex;
                        return `${dept.labels[i]}: ${processedValues[i]}% (${dept.karyawan_ikut[i]}/${dept.total_karyawan[i]})`;
                    }
                }}
            },
            scales: {
                r: {
                    min: 0, max: 100,
                    ticks: { font:{size:8}, stepSize:25, callback: v => v+'%', color:'#9ca3af' },
                    pointLabels: { font:{size:8}, color:'#374151' },
                    grid: { color:'rgba(0,0,0,0.08)' },
                    angleLines: { color:'rgba(0,0,0,0.08)' },
                }
            }
        }
    });
}

function renderCategoryChart(radar) {
    const ctx = document.getElementById('kaChartCategory');
    if (!ctx) return;
    if (kaCategoryChart) { kaCategoryChart.destroy(); kaCategoryChart = null; }

    const cleanValues     = radar.values.map(v => Math.round(v));
    const totalAktual     = cleanValues.reduce((a, b) => a + b, 0);
    const processedValues = cleanValues.map(v =>
        totalAktual > 0 ? parseFloat(((v / totalAktual) * 100).toFixed(1)) : 0
    );

    kaCategoryChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: radar.labels,
            datasets: [{
                label: 'Contribution Share',
                data: processedValues,
                borderColor: '#8b0000',
                backgroundColor: 'rgba(139,0,0,0.15)',
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
                    ticks: { font:{size:8}, stepSize:25, callback: v => v+'%', color:'#9ca3af' },
                    pointLabels: { font:{size:9}, color:'#374151' },
                    grid: { color:'rgba(0,0,0,0.08)' },
                    angleLines: { color:'rgba(0,0,0,0.08)' },
                }
            }
        }
    });
}

function closeKaModal() {
    document.getElementById('kaModal').style.display = 'none';
}

async function openKaModal(deptName) {
    const modal = document.getElementById('kaModal');
    modal.style.display = 'flex';
    document.getElementById('kaModalTitle').textContent  = `BREAKDOWN: ${deptName.toUpperCase()}`;
    document.getElementById('kaModalTotal').textContent  = '—';
    document.getElementById('kaModalIkut').textContent   = '—';
    document.getElementById('kaModalPersen').textContent = '—';
    document.getElementById('kaModalTbody').innerHTML    =
        '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Loading...</td></tr>';

    try {
        const tahun = document.getElementById('ka-tahun').value;
        const r = await fetch(
            `${KAIZEN_DETAIL_PROXY}?dept=${encodeURIComponent(deptName)}&bulan=${kaBulanAktif}&tahun=${tahun}`
        );
        const d = await r.json();

        document.getElementById('kaModalTotal').textContent  = d.rekap.total_karyawan;
        document.getElementById('kaModalIkut').textContent   = d.rekap.karyawan_ikut;
        document.getElementById('kaModalPersen').textContent = d.rekap.persentase + '%';

        const tbody = document.getElementById('kaModalTbody');
        if (!d.karyawan || d.karyawan.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada data</td></tr>';
            return;
        }
        tbody.innerHTML = d.karyawan.map((k, i) => `
            <tr>
                <td>
                    <span class="ka-rank-badge" style="background:${i<3?'#D0021B':'#6b7280'}">
                        ${i+1}
                    </span>
                </td>
                <td style="font-weight:600;">${k.nama_karyawan.trim()}</td>
                <td style="text-align:center;font-weight:700;">${k.total_kaizen} Lembar</td>
            </tr>
        `).join('');

    } catch(e) {
        document.getElementById('kaModalTbody').innerHTML =
            '<tr><td colspan="3" style="text-align:center;padding:20px;color:#dc2626;">⚠ Gagal memuat data</td></tr>';
    }
}

// Tutup modal klik backdrop
document.getElementById('kaModal').addEventListener('click', function(e) {
    if (e.target === this) closeKaModal();
});

function renderEmployeeChart(employee) {
    const ctx = document.getElementById('kaChartEmployee');
    if (!ctx) return;
    if (kaEmployeeChart) { kaEmployeeChart.destroy(); kaEmployeeChart = null; }

    if (!employee || !employee.labels || employee.labels.length === 0) {
        ctx.parentElement.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:20px;font-size:11px;">Belum ada data</div>';
        return;
    }

    const top5Labels = employee.labels.slice(0, 5).map(l => l.trim());
    const top5Values = employee.values.slice(0, 5);

    kaEmployeeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top5Labels,
            datasets: [{
                data: top5Values,
                backgroundColor: '#8b0000',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: ctx => ` ${ctx.parsed.y} Poin`
                }}
            },
            scales: {
                x: {
                    display: false,
                    ticks: { font:{size:9} }
                },
                y: {
                    beginAtZero: true,
                    ticks: { font:{size:9} },
                    grid: { color:'#f0f0f0' }
                }
            }
        },
        plugins: [{
            id: 'nameLabels',
            afterDraw(chart) {
                const ctx = chart.ctx;
                chart.data.datasets.forEach((dataset, i) => {
                    chart.getDatasetMeta(i).data.forEach((bar, index) => {
                        const label = chart.data.labels[index];
                        ctx.save();
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 9px Segoe UI';
                        ctx.textAlign = 'center';
                        ctx.translate(bar.x, bar.y + 10);
                        ctx.rotate(-Math.PI / 2);
                        ctx.fillText(label, 0, 0);
                        ctx.restore();
                    });
                });
            }
        }]
    });
}

function openKaAllEmployee() {
    if (!kaAllEmployeeData || !kaAllEmployeeData.labels) return;

    document.getElementById('kaModalTitle').textContent = 'LEADERBOARD SELURUH KARYAWAN';
    document.getElementById('kaModal').style.display = 'flex';

    document.getElementById('kaModalTotal').textContent  = kaAllEmployeeData.labels.length;
    document.getElementById('kaModalIkut').textContent   = '—';
    document.getElementById('kaModalPersen').textContent = '—';

    const tbody = document.getElementById('kaModalTbody');
    if (kaAllEmployeeData.labels.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada data</td></tr>';
        return;
    }

    tbody.innerHTML = kaAllEmployeeData.labels.map((name, i) => `
        <tr>
            <td>
                <span class="ka-rank-badge" style="background:${i<3?'#D0021B':'#6b7280'}">
                    ${i+1}
                </span>
            </td>
            <td style="font-weight:600;">${name.trim()}</td>
            <td style="text-align:center;font-weight:700;">${kaAllEmployeeData.values[i]} Poin</td>
        </tr>
    `).join('');
}

// Load saat halaman buka
loadKaizenAnalytics();
</script>
</body>
</html>