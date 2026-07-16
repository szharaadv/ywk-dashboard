<?php
$section  = $_GET['section']  ?? 'all';
$status   = $_GET['status']   ?? 'all';
$sort     = $_GET['sort']     ?? 'nilai';
$category = $_GET['category'] ?? 'all';

$is_detail  = true;
$page_title = 'KAIZEN SHEET';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaizen Detail — YWK Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        html, body { height:100vh; overflow:hidden; }
        .dashboard { height:100vh; display:flex; flex-direction:column; overflow:hidden; }
        .content-wrapper { flex:1; min-height:0; overflow:hidden; display:flex; flex-direction:column; gap:0.5rem; padding:0.75rem; }
    </style>
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Summary Cards -->
        <div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">Total Kaizen</div>
                <div class="metric-value" id="k-total">—</div>
                <div class="metric-sub">All sections</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Top Score</div>
                <div class="metric-value" id="k-top-score" style="color:#854F0B;">—</div>
                <div class="metric-sub" id="k-top-owner">—</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Avg Score</div>
                <div class="metric-value" id="k-avg-score">—</div>
                <div class="metric-sub">Keseluruhan</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Most Active Dept</div>
                <div class="metric-value" id="k-best-dept" style="color:#2e7d32; font-size:18px;">—</div>
                <div class="metric-sub">Terbanyak kaizen</div>
            </div>
        </div>

        <!-- Top Kaizen Banner -->
        <div class="kaizen-top-banner" id="k-top-banner">
            Loading top kaizen...
        </div>

        <!-- Filter Bar -->
        <div class="card" style="padding: 0.75rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-size:12px; color:#6b7280; font-weight:600;">Filter:</span>

                <select id="f-section" onchange="applyKaizenFilter()"
                    style="font-size:12px; border:1px solid #e5e7eb; border-radius:6px; padding:5px 10px;">
                    <option value="all"    <?= $section==='all'    ?'selected':'' ?>>All Section</option>
                    <option value="MS1"    <?= $section==='MS1'    ?'selected':'' ?>>MS1</option>
                    <option value="MS2"    <?= $section==='MS2'    ?'selected':'' ?>>MS2</option>
                    <option value="Conrod" <?= $section==='Conrod' ?'selected':'' ?>>Conrod</option>
                </select>

                <select id="f-status" onchange="applyKaizenFilter()"
                    style="font-size:12px; border:1px solid #e5e7eb; border-radius:6px; padding:5px 10px;">
                    <option value="all"         <?= $status==='all'         ?'selected':'' ?>>All Status</option>
                    <option value="approved"    <?= $status==='approved'    ?'selected':'' ?>>Approved</option>
                    <option value="implemented" <?= $status==='implemented' ?'selected':'' ?>>Implemented</option>
                    <option value="open"        <?= $status==='open'        ?'selected':'' ?>>Open</option>
                </select>

                <select id="f-category" onchange="applyKaizenFilter()"
                    style="font-size:12px; border:1px solid #e5e7eb; border-radius:6px; padding:5px 10px;">
                    <option value="all"          <?= $category==='all'          ?'selected':'' ?>>All Category</option>
                    <option value="Productivity" <?= $category==='Productivity' ?'selected':'' ?>>Productivity</option>
                    <option value="Cost Down"    <?= $category==='Cost Down'    ?'selected':'' ?>>Cost Down</option>
                    <option value="Quality"      <?= $category==='Quality'      ?'selected':'' ?>>Quality</option>
                    <option value="Safety"       <?= $category==='Safety'       ?'selected':'' ?>>Safety</option>
                    <option value="3S-3T"        <?= $category==='3S-3T'        ?'selected':'' ?>>3S-3T</option>
                </select>

                <select id="f-sort" onchange="applyKaizenFilter()"
                    style="font-size:12px; border:1px solid #e5e7eb; border-radius:6px; padding:5px 10px;">
                    <option value="nilai"   <?= $sort==='nilai'   ?'selected':'' ?>>Sort: Score</option>
                    <option value="tanggal" <?= $sort==='tanggal' ?'selected':'' ?>>Sort: Date</option>
                    <option value="title"   <?= $sort==='title'   ?'selected':'' ?>>Sort: Title</option>
                </select>

                <span id="k-count" style="font-size:11px; color:#6b7280; margin-left:auto;"></span>
            </div>
        </div>

        <!-- Kaizen Table -->
        <div class="card" style="padding:0; flex:1; min-height:0; overflow-y:auto;">
            <table class="kaizen-table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="padding-left:1.25rem;">#</th>
                        <th>Kaizen Title</th>
                        <th>Category</th>
                        <th>Section</th>
                        <th>Purposed</th>
                        <th>No. IP</th>
                        <th>Score</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="k-tbody">
                    <tr>
                        <td colspan="8"
                            style="text-align:center; color:#9ca3af; padding:2rem;">
                            Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function applyKaizenFilter() {
    const section  = document.getElementById('f-section').value;
    const status   = document.getElementById('f-status').value;
    const category = document.getElementById('f-category').value;
    const sort     = document.getElementById('f-sort').value;
    const params   = new URLSearchParams({ section, status, category, sort });
    window.location.href = 'kaizen_detail.php?' + params.toString();
}

const API_QS = '?section=<?= urlencode($section) ?>&status=<?= urlencode($status) ?>&category=<?= urlencode($category) ?>&sort=<?= urlencode($sort) ?>';

fetch('../api/kaizen_data.php' + API_QS)
    .then(r => r.json())
    .then(d => {
        document.getElementById('k-total').textContent     = d.total     ?? '—';
        document.getElementById('k-top-score').textContent = d.top_score ?? '—';
        document.getElementById('k-avg-score').textContent = d.avg_score ?? '—';
        document.getElementById('k-best-dept').textContent = d.best_dept ?? '—';

        if (d.top_kaizen) {
            document.getElementById('k-top-owner').textContent = d.top_kaizen.pic ?? '—';
            document.getElementById('k-top-banner').textContent =
                `Top Kaizen: "${d.top_kaizen.judul}" — ${d.top_kaizen.pic} — Score ${d.top_kaizen.nilai}`;
        } else {
            document.getElementById('k-top-banner').textContent = 'Belum ada data kaizen';
        }

        document.getElementById('k-count').textContent = d.list.length + ' kaizen ditampilkan';

        const tbody = document.getElementById('k-tbody');
        if (!d.list || d.list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:2rem;">Belum ada data</td></tr>';
            return;
        }

        const statusClass = s => ({
            'approved':    'status-approved',
            'implemented': 'status-implemented',
            'open':        'status-open'
        })[s?.toLowerCase()] ?? 'status-open';

        const categoryColor = c => ({
            'Productivity': '#185FA5',
            'Cost Down':    '#854F0B',
            'Quality':      '#2e7d32',
            'Safety':       '#D0021B',
            '3S-3T':        '#6B2D8B',
        })[c] ?? '#6b7280';

        tbody.innerHTML = d.list.map((k, i) => `
            <tr>
                <td style="padding-left:1.25rem; color:#9ca3af;">${i + 1}</td>
                <td class="title-col">${k.judul}</td>
                <td>
                    ${k.category
                        ? k.category.split(',').map(c => c.trim()).filter(Boolean).map(c =>
                            `<span style="display:inline-block;font-size:10px;font-weight:700;padding:2px 8px;
                                          border-radius:20px;margin:1px 2px;
                                          background:${categoryColor(c)}18;color:${categoryColor(c)};">
                                ${c}</span>`).join('')
                        : '—'}
                </td>
                <td>${k.section ?? '—'}</td>
                <td>${k.pic ?? '—'}</td>
                <td style="color:#6b7280;font-size:11px;">${k.deskripsi ?? '—'}</td>
                <td class="score-col">${k.nilai ?? '—'}</td>
                <td><span class="status-badge ${statusClass(k.status)}">${k.status ?? '—'}</span></td>
            </tr>
        `).join('');
    })
    .catch(() => {
        document.getElementById('k-tbody').innerHTML =
            '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:2rem;">Gagal memuat data</td></tr>';
    });
</script>
</body>
</html>