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
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Filter Tahun -->
        <div class="card" style="padding:0.75rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-size:12px; color:#6b7280; font-weight:600;">Tahun:</span>
                <select id="f-tahun" onchange="applyEventFilter()"
                    style="font-size:12px; border:1px solid #e5e7eb;
                           border-radius:6px; padding:5px 10px;">
                    <option value="">Semua Tahun</option>
                </select>
                <span id="ev-count"
                      style="font-size:11px; color:#6b7280; margin-left:auto;"></span>
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
                <div class="metric-label">Total Departements</div>
                <div class="metric-value" id="ev-total-dept">—</div>
                <div class="metric-sub">Participating</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">1st Winner</div>
                <div class="metric-value" id="ev-winner-name"
                     style="font-size:14px; color:#D0021B;">—</div>
                <div class="metric-sub" id="ev-winner-dept">—</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Event Year</div>
                <div class="metric-value" id="ev-tahun"
                     style="color:#854F0B;">—</div>
                <div class="metric-sub">YWK Event</div>
            </div>
        </div>

        <!-- Card Grid Gallery -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Gallery & Pemenang</div>
                <span id="ev-gallery-counter"
                      style="font-size:11px; color:#6b7280;"></span>
            </div>
            <div id="ev-winner-grid"
                 style="display:grid;
                        grid-template-columns:repeat(4, minmax(0,1fr));
                        gap:10px;">
                <div style="grid-column:1/-1; text-align:center;
                            color:#9ca3af; padding:2rem; font-size:13px;">
                    Loading...
                </div>
            </div>
        </div>

        <!-- Semua Materi -->
        <div class="card" style="padding:1rem 1.25rem 0.5rem;">
            <div class="card-header" style="margin-bottom:0.75rem;">
                <div class="card-title">Semua Materi</div>
            </div>
        </div>

        <div id="ev-grid"
             style="display:grid;
                    grid-template-columns:repeat(3, minmax(0,1fr));
                    gap:1rem;">
            <div style="grid-column:1/-1; text-align:center;
                        color:#9ca3af; padding:2rem;">
                Loading...
            </div>
        </div>

    </div>
</div>

<style>
/* ===== EV CARD ===== */
.ev-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ev-card-img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    background: #f4f5f7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #ccc;
}

.ev-card-body {
    padding: 0.875rem 1rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ev-card-rank {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
    width: fit-content;
    margin-bottom: 4px;
}

.ev-rank-1 { background: #FDECEA; color: #D0021B; }
.ev-rank-2 { background: #f4f5f7; color: #444;    }
.ev-rank-3 { background: #EAF3DE; color: #3B6D11; }
.ev-rank-n { background: #f4f5f7; color: #6b7280; }

.ev-card-title { font-size:13px; font-weight:600; color:#1a1a1a; }
.ev-card-meta  { font-size:11px; color:#6b7280; }

/* ===== WINNER GRID CARD ===== */
.winner-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.15s;
}

.winner-card:hover {
    box-shadow: 0 2px 12px rgba(208,2,27,0.10);
}

.winner-card.rank-1 { border-top: 3px solid #D0021B; }
.winner-card.rank-2 { border-top: 3px solid #888;    }
.winner-card.rank-3 { border-top: 3px solid #3B6D11; }

.winner-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.winner-card-no-img {
    width: 100%;
    height: 120px;
    background: #f4f5f7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.winner-card-body {
    padding: 10px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.winner-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
    width: fit-content;
    margin-bottom: 5px;
}

.winner-badge.rank-1 { background: #FDECEA; color: #D0021B; }
.winner-badge.rank-2 { background: #f4f5f7; color: #555;    }
.winner-badge.rank-3 { background: #EAF3DE; color: #3B6D11; }
.winner-badge.rank-n { background: #f4f5f7; color: #6b7280; }

.winner-title {
    font-size: 12px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.3;
}

.winner-meta {
    font-size: 11px;
    color: #6b7280;
    margin-top: 2px;
}

.winner-dept {
    font-size: 11px;
    color: #D0021B;
    margin-top: 1px;
}
</style>

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
    if (r === 1) return { text: '🥇 1st Winner', cls: 'rank-1', badgeCls: 'rank-1' };
    if (r === 2) return { text: '🥈 2nd Winner', cls: 'rank-2', badgeCls: 'rank-2' };
    if (r === 3) return { text: '🥉 3rd Winner', cls: 'rank-3', badgeCls: 'rank-3' };
    return { text: 'Participant', cls: '', badgeCls: 'rank-n' };
}

function getRankCardClass(rank) {
    if (rank == 1) return 'ev-rank-1';
    if (rank == 2) return 'ev-rank-2';
    if (rank == 3) return 'ev-rank-3';
    return 'ev-rank-n';
}

// ===== FETCH DATA =====
fetch('../api/event_data.php' + API_QS)
    .then(r => r.json())
    .then(d => {

        // Populate year dropdown
        const sel = document.getElementById('f-tahun');
        d.years.forEach(y => {
            const opt = document.createElement('option');
            opt.value       = y;
            opt.textContent = y;
            if (String(y) === String(CURRENT_TAHUN)) opt.selected = true;
            sel.appendChild(opt);
        });

        // Summary cards
        document.getElementById('ev-total-materi').textContent =
            d.total_materi ?? '—';
        document.getElementById('ev-total-dept').textContent =
            d.total_dept ?? '—';
        document.getElementById('ev-tahun').textContent =
            CURRENT_TAHUN || (d.years[0] ?? '—');

        if (d.winner) {
            document.getElementById('ev-winner-name').textContent =
                d.winner.judul_materi ?? '—';
            document.getElementById('ev-winner-dept').textContent =
                d.winner.departemen ?? '—';
        }

        document.getElementById('ev-count').textContent =
            d.list.length + ' materi';

        // ===== WINNER GRID =====
        const wGrid = document.getElementById('ev-winner-grid');

        if (!d.list || d.list.length === 0) {
            wGrid.innerHTML = `
                <div style="grid-column:1/-1; text-align:center;
                            color:#9ca3af; padding:2rem; font-size:13px;">
                    Belum ada data event
                </div>`;
        } else {
            document.getElementById('ev-gallery-counter').textContent =
                d.list.length + ' materi';

            wGrid.innerHTML = d.list.map(ev => {
                const { text, cls, badgeCls } = getRankInfo(ev.peringkat);
                const imgHtml = ev.foto
                    ? `<img src="../assets/img/${ev.foto}"
                            alt="${ev.judul_materi ?? ''}"
                            onerror="this.style.display='none';
                                     this.nextElementSibling.style.display='flex';">
                       <div class="winner-card-no-img" style="display:none;">📷</div>`
                    : `<div class="winner-card-no-img">📷</div>`;

                return `
                    <div class="winner-card ${cls}">
                        ${imgHtml}
                        <div class="winner-card-body">
                            <span class="winner-badge ${badgeCls}">${text}</span>
                            <div class="winner-title">${ev.judul_materi ?? '—'}</div>
                            <div class="winner-meta">${ev.peserta ?? '—'}</div>
                            ${ev.departemen
                                ? `<div class="winner-dept">${ev.departemen}</div>`
                                : ''}
                            ${ev.deskripsi
                                ? `<div class="winner-meta"
                                        style="margin-top:4px; color:#9ca3af;">
                                        ${ev.deskripsi}</div>`
                                : ''}
                        </div>
                    </div>`;
            }).join('');
        }

        // ===== MATERI GRID (semua) =====
        const grid = document.getElementById('ev-grid');

        if (!d.list || d.list.length === 0) {
            grid.innerHTML = `
                <div style="grid-column:1/-1; text-align:center;
                            color:#9ca3af; padding:2rem;">
                    Belum ada data event
                </div>`;
            return;
        }

        grid.innerHTML = d.list.map(ev => {
            const rankClass = getRankCardClass(ev.peringkat);
            const rankText  = getRankInfo(ev.peringkat).text;

            const imgHtml = ev.foto
                ? `<img src="../assets/img/${ev.foto}"
                        class="ev-card-img" style="display:block;"
                        onerror="this.style.display='none';
                                 this.nextElementSibling.style.display='flex';">
                   <div class="ev-card-img" style="display:none;">📷</div>`
                : `<div class="ev-card-img">📷</div>`;

            return `
                <div class="ev-card">
                    ${imgHtml}
                    <div class="ev-card-body">
                        <span class="ev-card-rank ${rankClass}">${rankText}</span>
                        <div class="ev-card-title">${ev.judul_materi ?? '—'}</div>
                        <div class="ev-card-meta">${ev.peserta ?? '—'}</div>
                        <div class="ev-card-meta"
                             style="color:#D0021B;">${ev.departemen ?? '—'}</div>
                        ${ev.deskripsi
                            ? `<div class="ev-card-meta"
                                    style="margin-top:4px; color:#9ca3af;">
                                    ${ev.deskripsi}</div>`
                            : ''}
                    </div>
                </div>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('ev-winner-grid').innerHTML =
            `<div style="grid-column:1/-1; text-align:center;
                color:#9ca3af; padding:2rem;">Gagal memuat data</div>`;
        document.getElementById('ev-grid').innerHTML =
            `<div style="grid-column:1/-1; text-align:center;
                color:#9ca3af; padding:2rem;">Gagal memuat data</div>`;
    });
</script>
</body>
</html>