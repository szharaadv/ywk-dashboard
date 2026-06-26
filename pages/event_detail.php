<?php
$tahun = $_GET['tahun'] ?? '';

$is_detail  = true;
$page_title = 'YWK EVENT';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YWK Event — Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Override global overflow untuk halaman ini */
        html, body { overflow:auto !important; height:auto !important; min-height:100vh; }
        .dashboard  { overflow:auto !important; height:auto !important; }
        .content-wrapper { overflow:auto !important; height:auto !important; }

        /* ===== WINNER GRID CARD ===== */
        .winner-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.15s;
            position: relative;
        }
        .winner-card:hover { box-shadow: 0 2px 12px rgba(208,2,27,0.10); }
        .winner-card.rank-1 { border-top: 3px solid #D0021B; }
        .winner-card.rank-2 { border-top: 3px solid #888; }
        .winner-card.rank-3 { border-top: 3px solid #3B6D11; }

        .winner-card img {
            width: 100%; height: 120px;
            object-fit: cover; display: block;
        }
        .winner-card-no-img {
            width: 100%; height: 120px;
            background: #f4f5f7;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
        }
        .winner-card-body {
            padding: 10px 12px; flex: 1;
            display: flex; flex-direction: column; gap: 3px;
        }

        .winner-badge {
            font-size: 11px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            display: inline-block; width: fit-content;
            margin-bottom: 6px; text-transform: uppercase;
            letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .winner-badge.rank-1 { background:#D0021B; color:#fff; box-shadow:0 4px 8px rgba(208,2,27,0.3); font-size:12px; }
        .winner-badge.rank-2 { background:#4b5563; color:#fff; }
        .winner-badge.rank-3 { background:#3B6D11; color:#fff; }
        .winner-badge.rank-n { background:#e5e7eb; color:#6b7280; }

        .winner-title  { font-size:12px; font-weight:700; color:#1a1a1a; line-height:1.3; }
        .winner-meta   { font-size:11px; color:#6b7280; margin-top:2px; }
        .winner-dept   { font-size:11px; color:#D0021B; margin-top:1px; }

        .ranking-ribbon {
            position: absolute; top: 8px; right: 8px;
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-weight: 700; font-size: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 10;
        }
        .ranking-ribbon.rank-1 { background:#D0021B; color:#fff; }
        .ranking-ribbon.rank-2 { background:#4b5563; color:#fff; }
        .ranking-ribbon.rank-3 { background:#3B6D11; color:#fff; }

        /* ===== PARTICIPANT CARD ===== */
        .participant-card {
            background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            padding:12px; text-align:center; transition:all 0.15s; cursor:pointer;
        }
        .participant-card:hover {
            border-color:#D0021B;
            box-shadow:0 2px 8px rgba(208,2,27,0.1);
            transform:translateY(-2px);
        }
        .participant-avatar {
            width:50px; height:50px; border-radius:50%;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-weight:700; font-size:18px; margin:0 auto 8px;
        }
        .participant-name  { font-size:12px; font-weight:600; color:#1a1a1a; margin-bottom:4px; }
        .participant-dept  { font-size:10px; color:#6b7280; }
        .participant-count { font-size:9px; color:#9ca3af; margin-top:4px; }

        @media (max-width: 1024px) {
            #ev-winner-grid { grid-template-columns:repeat(auto-fit,minmax(150px,1fr)) !important; }
            .metrics-row    { grid-template-columns:repeat(2,1fr) !important; }
        }
        @media (max-width: 640px) {
            #ev-winner-grid { grid-template-columns:1fr !important; }
            .metrics-row    { grid-template-columns:1fr !important; gap:8px !important; }
        }
    </style>
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Filters -->
        <div class="card" style="padding:0.75rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-size:12px; color:#6b7280; font-weight:600;">Filter:</span>

                <select id="f-tahun" onchange="applyEventFilter()"
                    style="font-size:12px;border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;cursor:pointer;">
                    <option value="">Semua Tahun</option>
                </select>

                <select id="f-section" onchange="filterBySection()"
                    style="font-size:12px;border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;cursor:pointer;">
                    <option value="">Semua Section</option>
                    <option value="MS1">MS1</option>
                    <option value="MS2">MS2</option>
                    <option value="Conrod">Conrod</option>
                    <option value="HDE">HDE</option>
                </select>

                <select id="f-rank" onchange="filterByRank()"
                    style="font-size:12px;border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;cursor:pointer;">
                    <option value="">Semua Ranking</option>
                    <option value="1">🥇 1st Winner</option>
                    <option value="2">🥈 2nd Winner</option>
                    <option value="3">🥉 3rd Winner</option>
                </select>

                <span id="ev-count" style="font-size:11px;color:#6b7280;margin-left:auto;"></span>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">Total Materials</div>
                <div class="metric-value" id="ev-total-materi">—</div>
                <div class="metric-sub">Competed</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Participates</div>
                <div class="metric-value" id="ev-total-dept">—</div>
                <div class="metric-sub">Participating</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">1st Winner</div>
                <div class="metric-value" id="ev-winner-name" style="font-size:14px;color:#D0021B;">—</div>
                <div class="metric-sub" id="ev-winner-dept">—</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Event Year</div>
                <div class="metric-value" id="ev-tahun" style="color:#854F0B;">—</div>
                <div class="metric-sub">YWK Event</div>
            </div>
        </div>

        <!-- Participants -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Peserta Aktif</div>
                <span id="ev-participant-counter" style="font-size:11px;color:#6b7280;"></span>
            </div>
            <div id="ev-participant-grid"
                 style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;">
                <div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:1rem;font-size:13px;">Loading...</div>
            </div>
        </div>

        <!-- Gallery -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gallery & Pemenang</div>
                <span id="ev-gallery-counter" style="font-size:11px;color:#6b7280;"></span>
            </div>
            <div id="ev-winner-grid"
                 style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;grid-auto-flow:dense;">
                <div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:2rem;font-size:13px;">Loading...</div>
            </div>
        </div>

    </div>
</div>

<script>
function applyEventFilter() {
    const tahun = document.getElementById('f-tahun').value;
    const params = new URLSearchParams();
    if (tahun) params.set('tahun', tahun);
    window.location.href = 'event_detail.php?' + params.toString();
}

const CURRENT_TAHUN = '<?= $tahun ?>';
const API_QS = CURRENT_TAHUN ? '?tahun=' + CURRENT_TAHUN : '';

function getRankInfo(rank) {
    const r = parseInt(rank);
    if (r === 1) return { text:'🥇 1st Winner', cls:'rank-1', badgeCls:'rank-1' };
    if (r === 2) return { text:'🥈 2nd Winner', cls:'rank-2', badgeCls:'rank-2' };
    if (r === 3) return { text:'🥉 3rd Winner', cls:'rank-3', badgeCls:'rank-3' };
    return { text:'Participant', cls:'', badgeCls:'rank-n' };
}

fetch('../api/event_data.php' + API_QS)
    .then(r => r.json())
    .then(d => {

        // Participants
        const participants = {};
        d.list.forEach(ev => {
            if (!ev.peserta) return;
            const dept = ev.departemen ?? 'N/A';
            if (!participants[ev.peserta]) {
                participants[ev.peserta] = { name:ev.peserta, dept:dept, count:0, projects:[] };
            }
            participants[ev.peserta].count++;
            participants[ev.peserta].projects.push(ev.judul_materi);
        });

        const pGrid = document.getElementById('ev-participant-grid');
        const pList = Object.values(participants);
        if (pList.length === 0) {
            pGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:1rem;">Belum ada peserta</div>';
        } else {
            document.getElementById('ev-participant-counter').textContent = pList.length + ' peserta';
            const colors = ['#667eea','#764ba2','#f093fb','#4facfe','#00f2fe','#43e97b','#fa709a','#fee140'];
            pGrid.innerHTML = pList.map(p => {
                const initials = p.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
                const bg = colors[p.name.charCodeAt(0) % colors.length];
                return `<div class="participant-card" title="${p.projects.join(', ')}">
                    <div class="participant-avatar" style="background:linear-gradient(135deg,${bg},#764ba2);">${initials}</div>
                    <div class="participant-name">${p.name}</div>
                    <div class="participant-dept">${p.dept}</div>
                    <div class="participant-count">${p.count} project${p.count>1?'s':''}</div>
                </div>`;
            }).join('');
        }

        // Year dropdown
        const sel = document.getElementById('f-tahun');
        d.years.forEach(y => {
            const opt = document.createElement('option');
            opt.value = y; opt.textContent = y;
            if (String(y) === String(CURRENT_TAHUN)) opt.selected = true;
            sel.appendChild(opt);
        });

        // Summary
        document.getElementById('ev-total-materi').textContent = d.total_materi ?? '—';
        document.getElementById('ev-total-dept').textContent   = d.total_dept   ?? '—';
        document.getElementById('ev-tahun').textContent        = CURRENT_TAHUN || (d.years[0] ?? '—');
        document.getElementById('ev-count').textContent        = d.list.length + ' materi';
        if (d.winner) {
            document.getElementById('ev-winner-name').textContent = d.winner.judul_materi ?? '—';
            document.getElementById('ev-winner-dept').textContent = d.winner.departemen   ?? '—';
        }

        // Gallery
        const wGrid = document.getElementById('ev-winner-grid');
        if (!d.list || !d.list.length) {
            wGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:2rem;">Belum ada data event</div>';
            return;
        }

        document.getElementById('ev-gallery-counter').textContent = d.list.length + ' materi';

        wGrid.innerHTML = d.list.map(ev => {
            const { text, cls, badgeCls } = getRankInfo(ev.peringkat);
            const rankIcon = ev.peringkat==1?'🥇':ev.peringkat==2?'🥈':ev.peringkat==3?'🥉':'';
            const imgHtml  = ev.foto
                ? `<img src="../assets/img/${ev.foto}" alt="${ev.judul_materi??''}"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                   <div class="winner-card-no-img" style="display:none;">📷</div>`
                : `<div class="winner-card-no-img">📷</div>`;

            return `<div class="winner-card ${cls}" data-rank="${ev.peringkat}" data-section="${ev.departemen??''}">
                ${rankIcon?`<div class="ranking-ribbon rank-${ev.peringkat}">${rankIcon}</div>`:''}
                ${imgHtml}
                <div class="winner-card-body">
                    <span class="winner-badge ${badgeCls}">${text}</span>
                    <div class="winner-title">${ev.judul_materi??'—'}</div>
                    <div class="winner-meta">${ev.peserta??'—'}</div>
                    ${ev.departemen?`<div class="winner-dept">${ev.departemen}</div>`:''}
                    ${ev.deskripsi?`<div class="winner-meta" style="margin-top:4px;color:#9ca3af;font-size:10px;">${ev.deskripsi}</div>`:''}
                </div>
            </div>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('ev-winner-grid').innerHTML =
            '<div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:2rem;">Gagal memuat data</div>';
    });

function filterBySection() { applyFilters(document.getElementById('f-section').value, document.getElementById('f-rank').value); }
function filterByRank()    { applyFilters(document.getElementById('f-section').value, document.getElementById('f-rank').value); }

function applyFilters(section='', rank='') {
    const wGrid = document.getElementById('ev-winner-grid');
    let visible = 0;
    Array.from(wGrid.querySelectorAll('.winner-card')).forEach(card => {
        let show = true;
        if (rank    && card.getAttribute('data-rank')    !== rank)      show = false;
        if (section && !(card.getAttribute('data-section')||'').includes(section)) show = false;
        card.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });
    document.getElementById('ev-count').textContent = visible + ' materi';
}
</script>
</body>
</html>