<?php
$year       = $_GET['year'] ?? 'all';
$active_kpi = $_GET['kpi']  ?? 'operation_ratio';

$kpi_tabs = [
    'operation_ratio'       => 'Operation Ratio',
    'safety'                => 'Safety',
    'fcost'                 => 'F-Cost',
    'quality'               => 'Quality',
    'productivity_passrate' => 'Productivity & Pass Rate',
];

if (!array_key_exists($active_kpi, $kpi_tabs)) $active_kpi = 'operation_ratio';

$is_detail  = true;
$page_title = 'KPI DETAIL — ' . strtoupper($kpi_tabs[$active_kpi]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <title>KPI Detail — YWK Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { display:none; }
        html, body { height:100vh; overflow:hidden; }
        .dashboard { height:100vh; display:flex; flex-direction:column; overflow:hidden; }

        .content-wrapper {
            padding:0.4rem 0.75rem;
            display:flex; flex-direction:column;
            gap:0.4rem; flex:1; min-height:0; overflow:hidden;
        }

        /* Metric cards */
        .metrics-row {
            display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
            gap:8px; flex-shrink:0;
        }
        .metric-card {
            background:#fff; border:1px solid #e5e7eb;
            border-radius:10px; padding:0.6rem 1rem;
            border-top:3px solid #e5e7eb; transition:opacity .2s;
        }
        .metric-card.ms1    { border-top-color:#185FA5; }
        .metric-card.ms2    { border-top-color:#2e7d32; }
        .metric-card.conrod { border-top-color:#854F0B; }
        .metric-card.hde    { border-top-color:#6B2D8B; }
        .metric-card.dimmed { opacity:.3; }
        .metric-label { font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
        .metric-value { font-size:24px; font-weight:700; color:#1a1a1a; line-height:1; }
        .metric-sub   { font-size:10px; margin-top:4px; }
        .metric-trend { font-size:10px; margin-top:3px; font-weight:600; }
        .metric-split { display:flex; align-items:stretch; gap:0; margin-top:6px; }
        .metric-split-left  { flex:1; min-width:0; }
        .metric-split-divider { width:1px; background:#e5e7eb; margin:0 12px; flex-shrink:0; }
        .metric-split-right { flex:1; min-width:0; }
        .metric-split-label { font-size:9px; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
        .metric-split-val   { font-size:20px; font-weight:700; color:#1a1a1a; line-height:1.1; }
        .metric-split-val.pass-rate { font-size:18px; color:#185FA5; }

        /* Section grid */
        .section-grid {
            display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
            gap:0.4rem; flex:1; min-height:0;
        }
        .section-grid .card {
            border-top:3px solid #e5e7eb;
            display:flex; flex-direction:column;
            min-height:0; overflow:hidden;
            transition:opacity .2s;
        }
        .section-grid .card.dimmed { opacity:.3; pointer-events:none; }
        .section-grid .card:nth-child(1) { border-top-color:#185FA5; }
        .section-grid .card:nth-child(2) { border-top-color:#2e7d32; }
        .section-grid .card:nth-child(3) { border-top-color:#854F0B; }
        .section-grid .card:nth-child(4) { border-top-color:#6B2D8B; }

        /* Chart.js fill (mode KPI) */
        .chart-fill { flex:1; position:relative; min-height:0; overflow:hidden; }
        .chart-fill canvas { width:100%!important; height:100%!important; }

        /* Prod fill (mode Productivity) — flex column, scrollable */
        .prod-fill {
            flex:1;
            min-height:0;
            overflow-y:auto;
            overflow-x:hidden;
            display:flex;
            flex-direction:column;
            gap:8px;
            padding:4px 6px 8px 6px;
        }
        .prod-fill::-webkit-scrollbar { display:none; }

        /* Card header */
        .card-header-wrap {
            flex-shrink:0;
            padding:0.6rem 0.875rem 0.3rem 0.875rem;
        }
        .card-title { font-size:11px; font-weight:700; color:#1a3a5c; text-transform:uppercase; letter-spacing:.06em; }
        .badge-section { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; }
        .section-status-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; display:none; }
        .section-status-badge.on-target  { display:inline-block; background:#EAF3DE; color:#3B6D11; }
        .section-status-badge.off-target { display:inline-block; background:#FDECEA; color:#D0021B; }

        /* KPI mini tabel */
        .kpi-mini-table { width:100%; border-collapse:collapse; font-size:8px; }
        .kpi-mini-table th {
            text-align:center; padding:2px 4px; background:#f4f5f7;
            color:#6b7280; font-weight:700; border-bottom:1px solid #e5e7eb; min-width:28px;
        }
        .kpi-mini-table th:first-child { text-align:left; padding-left:6px; width:80px; }
        .kpi-mini-table td { text-align:center; padding:2px 4px; border-bottom:1px solid #f0f0f0; color:#1a1a1a; }
        .kpi-mini-table td:first-child { text-align:left; padding-left:6px; }

        /* Prod mini tabel */
        .prod-mini-table { width:100%; border-collapse:collapse; font-size:8px; }
        .prod-mini-table th {
            background:#f7f6f2; color:#8a8780; font-weight:700;
            padding:3px 4px; text-align:center; border-bottom:1px solid #e3e1d9;
            white-space:nowrap; min-width:28px;
        }
        .prod-mini-table th:first-child { text-align:left; padding-left:8px; min-width:110px; }
        .prod-mini-table td { padding:2px 4px; text-align:center; border-bottom:1px solid #f0efe9; color:#1a1916; }
        .prod-mini-table td:first-child { padding-left:8px; }
        .prod-mini-table tr:last-child td { border-bottom:none; }
        .row-badge { display:inline-flex; align-items:center; gap:5px; white-space:nowrap; }
        .row-badge-dot { width:8px; height:8px; border-radius:2px; flex-shrink:0; }

        /* Legend pills */
        .legend-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex-shrink:0; }
        .legend-pill { display:flex; align-items:center; gap:5px; font-size:10px; font-weight:600; color:#374151; }
        .lpill-dot  { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
        .lpill-line { width:16px; height:2px; flex-shrink:0; }

        /* Error */
        .error-msg {
            background:#fef2f2; border:1px solid #fca5a5; color:#dc2626;
            border-radius:8px; padding:8px 14px; font-size:11px; font-weight:600; flex-shrink:0;
        }

        /* Prod line card */
        .prod-line-card {
            flex-shrink:0;
            border:1.5px solid #e5e7eb;
            border-radius:10px;
            overflow:hidden;
            background:#fff;
        }
        .prod-line-card-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:6px 10px;
            background:#f7f6f2;
            border-bottom:1px solid #e5e7eb;
        }
        .prod-line-card-title {
            font-size:9px; font-weight:800; text-transform:uppercase;
            letter-spacing:.06em; color:#374151;
        }
        .prod-line-card-badge {
            font-size:9px; font-weight:700; padding:1px 7px;
            border-radius:20px;
        }
        .prod-line-card-body { padding:4px 6px; }
        .prod-apex-wrap { height:160px; width:100%; }
        .prod-table-scroll { overflow-x:auto; margin-top:4px; }
        .prod-table-scroll::-webkit-scrollbar { display:none; }

        /* line-filter & selector */
        .line-filter-wrap { display:flex; align-items:center; gap:8px; }
        .line-filter-wrap label {
            font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:.06em;
        }
        .line-filter-wrap select {
            font-size:12px; font-weight:600; color:#1a1a1a;
            background:#fff; border:1px solid #e5e7eb;
            border-radius:8px; padding:6px 28px 6px 10px;
            cursor:pointer; outline:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 8px center;
            min-width:200px; transition:border-color .15s;
        }
        .line-filter-wrap select:focus { border-color:#D0021B; }

        /* table area in KPI mode */
        .kpi-table-wrap { flex-shrink:0; margin-top:4px; overflow-x:auto; padding:0 0.875rem 0.5rem; }
    </style>
</head>
<body>
<div class="dashboard">

    <?php include '../components/topbar.php'; ?>

    <div class="content-wrapper">

        <!-- Selector row -->
        <div style="display:flex; align-items:center; gap:6px; flex-shrink:0; flex-wrap:wrap;">
            <label style="font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em;">KPI:</label>
            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                <?php foreach ($kpi_tabs as $key => $label): ?>
                <button onclick="document.getElementById('kpiSelect').value='<?= $key ?>'; onKpiChange();"
                        id="kpibtn-<?= $key ?>"
                        style="font-size:11px; font-weight:700; padding:5px 12px;
                            border-radius:20px; cursor:pointer; transition:all .15s;
                            border:1.5px solid <?= $active_kpi === $key ? '#D0021B' : '#e5e7eb' ?>;
                            background:<?= $active_kpi === $key ? '#D0021B' : '#fff' ?>;
                            color:<?= $active_kpi === $key ? '#fff' : '#6b7280' ?>;"
                        onmouseover="if(this.style.background!='rgb(208, 2, 27)'){this.style.borderColor='#D0021B';this.style.color='#D0021B';}"
                        onmouseout="if(this.style.background!='rgb(208, 2, 27)'){this.style.borderColor='#e5e7eb';this.style.color='#6b7280';}">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Line filter -->
            <div class="line-filter-wrap" id="lineFilterWrap" style="display:none">
                <label>LINE:</label>
                <select id="lineFilter" onchange="onLineChange()">
                    <option value="">— Semua Line —</option>
                </select>
            </div>

            <!-- Legend pills -->
            <div class="legend-bar" id="legendBar" style="display:none">
                <div class="legend-pill"><div class="lpill-dot" style="background:#1500d1"></div>Productivity FY26</div>
                <div class="legend-pill"><div class="lpill-dot" style="background:#8cbab7"></div>Productivity FY25</div>
                <div class="legend-pill"><div class="lpill-line" style="background:#F59E0B"></div>Pass Rate FY26</div>
                <div class="legend-pill"><div class="lpill-line" style="background:#ff5900"></div>Pass Rate FY25</div>
            </div>

            <!-- Hidden select -->
            <select id="kpiSelect" style="display:none;">
                <?php foreach ($kpi_tabs as $key => $label): ?>
                <option value="<?= $key ?>" <?= $active_kpi === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Metric cards -->
        <div class="metrics-row" id="metricsRow" style="display:grid;">
            <?php foreach (['ms1'=>'MS1','ms2'=>'MS2','conrod'=>'Conrod','hde'=>'HDE'] as $id => $label): ?>
            <div class="metric-card <?= $id ?>" id="mcard-<?= $id ?>">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <div class="metric-label" id="metric-<?= $id ?>-label"><?= $label ?></div>
                    <div style="font-size:20px;line-height:1;" id="metric-<?= $id ?>-emoji">➖</div>
                </div>
                <div id="metric-<?= $id ?>-normal">
                    <div class="metric-value" id="metric-<?= $id ?>">—</div>
                    <div class="metric-sub"   id="metric-<?= $id ?>-sub"></div>
                    <div class="metric-trend" id="metric-<?= $id ?>-trend"></div>
                </div>
                <div id="metric-<?= $id ?>-split" style="display:none;">
                    <div class="metric-split">
                        <div class="metric-split-left">
                            <div class="metric-split-label">Productivity avg</div>
                            <div class="metric-split-val" id="metric-<?= $id ?>-prodval">—</div>
                        </div>
                        <div class="metric-split-divider"></div>
                        <div class="metric-split-right">
                            <div class="metric-split-label">Pass Rate avg</div>
                            <div class="metric-split-val pass-rate" id="metric-<?= $id ?>-passrate">—%</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="errorBanner" class="error-msg" style="display:none;"></div>

        <!-- Section grid -->
        <div class="section-grid" id="sectionGrid">
            <?php
            $sec_styles = [
                'MS1'    => ['bg'=>'#E8F0FD','color'=>'#185FA5'],
                'MS2'    => ['bg'=>'#EAF3DE','color'=>'#2e7d32'],
                'Conrod' => ['bg'=>'#F5EFE6','color'=>'#854F0B'],
                'HDE'    => ['bg'=>'#F3E8F5','color'=>'#6B2D8B'],
            ];
            foreach ($sec_styles as $sec => $s):
                $sid = strtolower($sec);
            ?>
            <div class="card" id="card-<?= $sid ?>">
                <!-- Card header — selalu ada, flex-shrink:0 -->
                <div class="card-header-wrap">
                    <div class="card-header" style="margin-bottom:0;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div class="card-title" id="ctitle-<?= $sid ?>"><?= $sec ?></div>
                            <span id="badge-<?= $sid ?>" class="section-status-badge"></span>
                        </div>
                        <span class="badge-section" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;">Section</span>
                    </div>
                </div>

                <!-- Chart fill (KPI mode) -->
                <div class="chart-fill" id="chartfill-<?= $sid ?>">
                    <canvas id="chart<?= $sec ?>"></canvas>
                </div>

                <!-- Table (KPI mode) -->
                <div class="kpi-table-wrap" id="table-<?= $sid ?>">
                    <table class="kpi-mini-table">
                        <thead>
                            <tr>
                                <th>Index</th>
                                <?php foreach (['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'] as $m): ?>
                                <th><?= $m ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody id="tbody-<?= $sid ?>">
                            <tr><td colspan="13" style="text-align:center;color:#9ca3af;padding:4px">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
// ─── CONFIG ──────────────────────────────────────────────────────────────────
const PUBLIC_API = '../api/productivity_proxy.php';
// Line yang tidak dipakai — dikecualikan dari chart productivity
const EXCLUDED_LINES = ['Test Run Line'];
const MONTHS_FY  = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
const SEC_COLOR  = { MS1:'#185FA5', MS2:'#2e7d32', Conrod:'#854F0B', HDE:'#6B2D8B' };
const C          = { prod26:'#1500d1', prod25:'#8cbab7', pass26:'#F59E0B', pass25:'#ff5900' };
const YEAR_BASE  = new Date().getFullYear();
const LOC_CARD   = { '1':'ms1', '2':'ms2', '3':'conrod', '4':'hde' };

function fiscalIdxToCalMonth(fi) { return ((fi+3)%12)+1; }
function calMonthToFiscalIdx(m)  { return (m-4+12)%12; }
function fiscalTs(fi) {
    const m = fiscalIdxToCalMonth(fi);
    const y = (m>=1&&m<=3) ? YEAR_BASE+1 : YEAR_BASE;
    return new Date(y,m-1,1).getTime();
}
const ALL_FY_TS = MONTHS_FY.map((_,fi) => fiscalTs(fi));

// ─── STATE ───────────────────────────────────────────────────────────────────
let activeKpi      = '<?= $active_kpi ?>';
let selectedLineId = '';
let allLocations   = [];
let kpiCharts      = {};
let apexCharts     = {};
let scrollTimers   = [];

// ─── HELPERS ─────────────────────────────────────────────────────────────────
function safeNum(v) {
    if (v===null||v===undefined||v==='') return null;
    const n=parseFloat(v); return (isNaN(n)||n===0)?null:n;
}
function zipPairs(ts,vals) { return ts.map((t,i)=>({x:t,y:vals[i]??null})); }
function countNonNull(arr) { return arr.filter(v=>v!==null&&!isNaN(v)).length; }
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function getLastNonNull(arr) {
    if (!arr) return null;
    for (let i=arr.length-1;i>=0;i--)
        if (arr[i]!==null&&arr[i]!==undefined) return arr[i];
    return null;
}
function getPrevNonNull(arr) {
    if (!arr) return null;
    let found=0;
    for (let i=arr.length-1;i>=0;i--)
        if (arr[i]!==null&&arr[i]!==undefined&&++found===2) return arr[i];
    return null;
}

// ─── FORMAT ──────────────────────────────────────────────────────────────────
function fmtKpi(val,kpi) {
    if (val===null||val===undefined) return '—';
    if (kpi==='fcost')           return val.toFixed(2)+'%';
    if (kpi==='operation_ratio') return val+'%';
    if (kpi==='quality')         return Number(val).toLocaleString('id-ID')+' PPM';
    if (kpi==='safety')          return val+' case';
    return val;
}
function getSubLabel(actual,target,kpi) {
    if (actual===null||actual===undefined) return '';
    if (kpi==='safety')
        return actual===0?'<span style="color:#3B6D11">✓ No accident</span>'
                         :'<span style="color:#D0021B">⚠ Ada insiden</span>';
    if (kpi==='fcost'||kpi==='quality')
        return actual<=target?'<span style="color:#3B6D11">Di bawah batas</span>'
                             :'<span style="color:#D0021B">Melebihi batas</span>';
    const diff=parseFloat((actual-target).toFixed(1)),sign=diff>=0?'+':'';
    const color=diff>=0?'#3B6D11':'#D0021B';
    const u=kpi==='operation_ratio'?'%':'';
    return `<span style="color:${color}">${sign}${diff}${u} vs target</span>`;
}
function getTrendLabel(cur,prev,kpi) {
    if (cur===null||prev===null) return '';
    const diff=cur-prev;
    if (diff===0) return `<span style="color:#6b7280">0 vs bulan lalu</span>`;
    const isGood=(kpi==='fcost'||kpi==='quality')?diff<0:diff>0;
    const color=isGood?'#3B6D11':'#D0021B', sign=diff>0?'+':'';
    let diffStr='';
    if (kpi==='fcost')                diffStr=sign+diff.toFixed(2)+'%';
    else if (kpi==='operation_ratio') diffStr=sign+diff.toFixed(1)+'%';
    else if (kpi==='quality')         diffStr=sign+Math.round(diff).toLocaleString('id-ID')+' PPM';
    else if (kpi==='safety')          diffStr=sign+Math.round(diff)+' case';
    else                              diffStr=sign+diff;
    return `<span style="color:${color};font-weight:700;">${diffStr}</span>`;
}
function setSectionBadge(sid,actual,target,kpi) {
    const el=document.getElementById('badge-'+sid); if(!el) return;
    if(actual===null||target===null){el.className='section-status-badge';return;}
    const good=kpi==='fcost'||kpi==='quality'?actual<=target:kpi==='safety'?actual===0:actual>=target;
    el.textContent=good?(kpi==='fcost'||kpi==='quality'?'✓ Di Bawah Batas':'✓ On Target')
                       :(kpi==='fcost'||kpi==='quality'?'⚠ Melebihi Batas':'⚠ Off Target');
    el.className='section-status-badge '+(good?'on-target':'off-target');
}
function setMetricCard(id,value,sub,trend) {
    const v=document.getElementById('metric-'+id);
    const s=document.getElementById('metric-'+id+'-sub');
    const t=document.getElementById('metric-'+id+'-trend');
    if(v) v.textContent=value??'—';
    if(s) s.innerHTML=sub??'';
    if(t) t.innerHTML=trend??'';
}
function setMetricEmoji(id,actual,target,kpi) {
    const el=document.getElementById('metric-'+id+'-emoji'); if(!el) return;
    if(actual===null||actual===undefined){el.textContent='➖';return;}
    if(kpi==='safety'){el.textContent=actual===0?'🛡️':'🚨';return;}
    if(kpi==='fcost'||kpi==='quality'){el.textContent=actual<=target?'✅':'🔴';return;}
    if(kpi==='operation_ratio'){
        const diff=actual-target;
        el.textContent=diff>=2?'🏆':diff>=0?'✅':diff>=-3?'⚠️':'🔴'; return;
    }
    el.textContent='➖';
}
function clearMetricCards() {
    ['ms1','ms2','conrod','hde'].forEach(id=>setMetricCard(id,'—','',''));
}

// ─── KPI CHANGE ──────────────────────────────────────────────────────────────
function onKpiChange() {
    activeKpi = document.getElementById('kpiSelect').value;

    // Reset semua cards jadi visible dulu
    ['ms1','ms2','conrod','hde'].forEach(s => {
        const card=document.getElementById('card-'+s);
        const mcard=document.getElementById('mcard-'+s);
        if(card)  { card.style.display=''; card.style.opacity='1'; card.style.pointerEvents=''; }
        if(mcard) { mcard.style.display='block'; mcard.style.opacity='1'; mcard.style.pointerEvents=''; }
    });

    // Clear location cache saat pindah dari Productivity
    if (activeKpi !== 'productivity_passrate') {
        allLocations = [];
        selectedLineId = '';
        document.getElementById('lineFilter').innerHTML = '<option value="">— Semua Line —</option>';
    }

    // Update button styles
    document.querySelectorAll('[id^="kpibtn-"]').forEach(btn => {
        const isActive = btn.id === 'kpibtn-' + activeKpi;
        btn.style.background  = isActive ? '#D0021B' : '#fff';
        btn.style.borderColor = isActive ? '#D0021B' : '#e5e7eb';
        btn.style.color       = isActive ? '#fff'    : '#6b7280';
    });

    destroyAllCharts();
    stopScrollTimers();

    if (activeKpi === 'productivity_passrate') {
        showProdUI();
        loadProductivity();
    } else {
        showKpiUI();
        loadKpi();
    }
}

function onLineChange() {
    selectedLineId = document.getElementById('lineFilter').value;
    applyLineDim();
}

// ─── UI TOGGLE ───────────────────────────────────────────────────────────────
function showProdUI() {
    document.getElementById('lineFilterWrap').style.display = 'flex';
    document.getElementById('legendBar').style.display      = 'flex';
    document.getElementById('metricsRow').style.display     = 'grid';
    document.getElementById('metricsRow').style.gridTemplateColumns = 'repeat(4,minmax(0,1fr))';
    document.getElementById('sectionGrid').style.gridTemplateColumns = 'repeat(4,minmax(0,1fr))';

    ['ms1','ms2','conrod','hde'].forEach(id => {
        const card=document.getElementById('card-'+id);
        const mcard=document.getElementById('mcard-'+id);
        if(card)  card.style.display = '';
        if(mcard) mcard.style.display = 'block';
        const norm=document.getElementById('metric-'+id+'-normal');
        const splt=document.getElementById('metric-'+id+'-split');
        if(norm) norm.style.display='block';
        if(splt) splt.style.display='none';
    });
}

function showKpiUI() {
    document.getElementById('lineFilterWrap').style.display = 'none';
    document.getElementById('legendBar').style.display      = 'none';
    document.getElementById('metricsRow').style.display     = 'grid';

    ['ms1','ms2','conrod','hde'].forEach(id => {
        const norm=document.getElementById('metric-'+id+'-normal');
        const splt=document.getElementById('metric-'+id+'-split');
        if(norm) norm.style.display='block';
        if(splt) splt.style.display='none';
    });

    // F-Cost: hanya Conrod & HDE
    ['ms1','ms2','conrod','hde'].forEach(sid => {
        const show = activeKpi !== 'fcost' || sid === 'conrod' || sid === 'hde';
        const card=document.getElementById('card-'+sid);
        const mcard=document.getElementById('mcard-'+sid);
        if(card)  card.style.display  = show ? '' : 'none';
        if(mcard) mcard.style.display = show ? 'block' : 'none';
    });

    const isFcost = activeKpi === 'fcost';
    document.getElementById('metricsRow').style.gridTemplateColumns =
        isFcost ? 'repeat(2,minmax(0,1fr))' : 'repeat(4,minmax(0,1fr))';
    document.getElementById('sectionGrid').style.gridTemplateColumns =
        isFcost ? 'repeat(2,minmax(0,1fr))' : 'repeat(4,minmax(0,1fr))';

    ['ms1','ms2','conrod','hde'].forEach(sid => {
        document.getElementById('card-'+sid)?.classList.remove('dimmed');
        document.getElementById('mcard-'+sid)?.classList.remove('dimmed');
    });

    // Reset label metric cards
    const labelMap = { ms1:'MS1', ms2:'MS2', conrod:'Conrod', hde:'HDE' };
    Object.entries(labelMap).forEach(([sid,lbl]) => {
        const ml=document.querySelector('#mcard-'+sid+' .metric-label');
        if(ml) ml.textContent=lbl;
        const emojiEl=document.getElementById('metric-'+sid+'-emoji');
        if(emojiEl) emojiEl.style.display='';
    });

    // Restore chart-fill & table untuk KPI mode
    const mths = MONTHS_FY.map(m=>`<th>${m}</th>`).join('');
    ['MS1','MS2','Conrod','HDE'].forEach(sec => {
        const sid=sec.toLowerCase();

        // Restore chartfill ke class chart-fill
        const fill=document.getElementById('chartfill-'+sid);
        if(fill) {
            fill.className='chart-fill';
            fill.removeAttribute('style');
            fill.innerHTML=`<canvas id="chart${sec}"></canvas>`;
        }

        // Restore table
        const tableDiv=document.getElementById('table-'+sid);
        if(tableDiv) {
            tableDiv.className='kpi-table-wrap';
            tableDiv.removeAttribute('style');
            tableDiv.innerHTML=`
                <table class="kpi-mini-table">
                    <thead><tr><th>Index</th>${mths}</tr></thead>
                    <tbody id="tbody-${sid}">
                        <tr><td colspan="13" style="text-align:center;color:#9ca3af;padding:4px">Loading…</td></tr>
                    </tbody>
                </table>`;
        }

        const t=document.getElementById('ctitle-'+sid);
        if(t) t.textContent=sec;
        const badge=document.getElementById('badge-'+sid);
        if(badge) badge.className='section-status-badge';
    });
}

// ─── DIM ─────────────────────────────────────────────────────────────────────
function applyLineDim() {
    if (!selectedLineId) {
        Object.values(LOC_CARD).forEach(sid => {
            document.getElementById('card-'+sid)?.classList.remove('dimmed');
            document.getElementById('mcard-'+sid)?.classList.remove('dimmed');
        });
        return;
    }
    Object.entries(LOC_CARD).forEach(([lid,sid]) => {
        const active = lid === String(selectedLineId);
        document.getElementById('card-'+sid)?.classList.toggle('dimmed',!active);
        document.getElementById('mcard-'+sid)?.classList.toggle('dimmed',!active);
    });
}

// ─── DESTROY ─────────────────────────────────────────────────────────────────
function destroyAllCharts() {
    Object.values(kpiCharts).forEach(c=>c&&c.destroy&&c.destroy());
    kpiCharts={};
    Object.values(apexCharts).forEach(c=>c&&c.destroy&&c.destroy());
    apexCharts={};
}
function stopScrollTimers() {
    scrollTimers.forEach(t=>clearInterval(t));
    scrollTimers=[];
}

// ─── PRODUCTIVITY API ────────────────────────────────────────────────────────
async function fetchLocations() {
    try {
        const ctrl=new AbortController();
        const tid=setTimeout(()=>ctrl.abort(),5000);
        const r=await fetch(`${PUBLIC_API}?action=locations`,{signal:ctrl.signal});
        clearTimeout(tid);
        if(!r.ok) throw new Error(`HTTP ${r.status}`);
        const j=await r.json();
        return j.success?j.data:[];
    } catch(e) { console.warn('fetchLocations:',e.message); return []; }
}

async function fetchProdRows() {
    try {
        const params=new URLSearchParams({action:'productivity',year:YEAR_BASE});
        if(selectedLineId) params.append('location_id',selectedLineId);
        const ctrl=new AbortController();
        const tid=setTimeout(()=>ctrl.abort(),8000);
        const r=await fetch(`${PUBLIC_API}?${params}`,{signal:ctrl.signal});
        clearTimeout(tid);
        if(!r.ok) throw new Error(`HTTP ${r.status}`);
        const j=await r.json();
        if(!j.success) throw new Error(j.error||'API error');
        return j.data?.flat||[];
    } catch(e) { console.warn('fetchProdRows:',e.message); return []; }
}

// ─── TRANSFORM ───────────────────────────────────────────────────────────────
function transformProdRows(rows) {
    const lm={};
    rows.forEach(row=>{
        const lid=String(row.location_id), ln=row.line_name;
        if(EXCLUDED_LINES.includes(ln)) return; // line tidak dipakai (data tidak valid)
        if(!lm[lid]) lm[lid]={};
        if(!lm[lid][ln]) lm[lid][ln]={
            prod25:new Array(12).fill(null), prod26:new Array(12).fill(null),
            pass25:new Array(12).fill(null), pass26:new Array(12).fill(null),
        };
        const fi=calMonthToFiscalIdx(parseInt(row.month));
        lm[lid][ln].prod25[fi]=safeNum(row.prod_2025);
        lm[lid][ln].prod26[fi]=safeNum(row.prod_2026);
        lm[lid][ln].pass25[fi]=safeNum(row.pass_rate_2025);
        lm[lid][ln].pass26[fi]=safeNum(row.pass_rate_2026);
    });
    return lm;
}

// ─── APEX OPTIONS ────────────────────────────────────────────────────────────
function buildApexOptions(chartId,ld) {
    const yb=YEAR_BASE-1, yc=YEAR_BASE;
    const mSz=[0,0,countNonNull(ld.pass25)<=1?4:0,countNonNull(ld.pass26)<=1?4:0];
    return {
        chart:{id:chartId,fontFamily:"'Segoe UI',sans-serif",background:'transparent',
               height:160,type:'line',toolbar:{show:false},zoom:{enabled:false},
               animations:{enabled:false},redrawOnParentResize:true},
        series:[
            {name:`Productivity FY${yb}`, type:'bar',  data:zipPairs(ALL_FY_TS,ld.prod25)},
            {name:`Productivity FY${yc}`, type:'bar',  data:zipPairs(ALL_FY_TS,ld.prod26)},
            {name:`Pass Rate FY${yb} (%)`,type:'line', data:zipPairs(ALL_FY_TS,ld.pass25)},
            {name:`Pass Rate FY${yc} (%)`,type:'line', data:zipPairs(ALL_FY_TS,ld.pass26)},
        ],
        colors:[C.prod25,C.prod26,C.pass25,C.pass26],
        theme:{mode:'light'},
        grid:{borderColor:'rgba(60,90,180,.4)',strokeDashArray:2,
              padding:{top:0,right:0,bottom:0,left:0}},
        legend:{show:false},
        tooltip:{theme:'light',shared:true,intersect:false,style:{fontSize:'10px'},
                 y:{formatter:(val,{seriesIndex})=>{
                     if(val===null||val===undefined) return 'N/A';
                     const n=parseFloat(val); if(isNaN(n)||n===0) return 'N/A';
                     return seriesIndex>=2?n.toFixed(2)+'%':n.toFixed(2);
                 }}},
        xaxis:{type:'datetime',categories:ALL_FY_TS,tickAmount:6,
               labels:{style:{fontSize:'8px',colors:'#6b7280'},datetimeUTC:false,
                       format:'MMM',rotate:-30,rotateAlways:true},
               axisTicks:{color:'rgba(60,90,180,.15)'}},
        markers:{size:mSz,strokeWidth:2,strokeColor:'#fff',showNullDataPoints:false},
        plotOptions:{bar:{columnWidth:'80%',borderRadius:0}},
        dataLabels:{enabled:false},
        stroke:{width:[0,0,2.5,2],curve:'smooth'},
        fill:{type:['solid','solid','solid','solid'],opacity:[0.8,0.5,1,1]},
        yaxis:[
            {title:{text:'Productivity',style:{fontSize:'8px',color:'#6b7280'}},
             labels:{style:{fontSize:'8px',colors:'#6b7280'},
                     formatter:v=>v!=null?parseFloat(v).toFixed(1):''},tickAmount:5},
            {show:false,tickAmount:5},
            {opposite:true,title:{text:'Pass Rate (%)',style:{fontSize:'8px',color:'#6b7280'}},
             labels:{style:{fontSize:'8px',colors:'#6b7280'},
                     formatter:v=>v!=null?parseFloat(v).toFixed(1)+'%':''},tickAmount:5},
            {opposite:true,show:false,tickAmount:5},
        ],
    };
}

// ─── PROD TABLE ──────────────────────────────────────────────────────────────
function buildProdTable(ld) {
    const yb=YEAR_BASE-1, yc=YEAR_BASE;
    const fmt=v=>(v===null||v===undefined||v===0)?'—':parseFloat(v).toFixed(2);
    const rows=[
        {label:`Prod FY${yb}`,      dot:C.prod25, data:ld.prod25, bold:false},
        {label:`Prod FY${yc}`,      dot:C.prod26, data:ld.prod26, bold:true},
        {label:`Pass Rate FY${yb}`, dot:C.pass25, data:ld.pass25, bold:false},
        {label:`Pass Rate FY${yc}`, dot:C.pass26, data:ld.pass26, bold:true},
    ];
    const ths=MONTHS_FY.map(m=>`<th>${m}</th>`).join('');
    const trs=rows.map(r=>`<tr>
        <td><span class="row-badge">
            <span class="row-badge-dot" style="background:${r.dot}"></span>
            <span style="font-weight:${r.bold?'700':'400'}">${r.label}</span>
        </span></td>
        ${r.data.map(v=>`<td style="font-weight:${r.bold?'700':'400'}">${fmt(v)}</td>`).join('')}
    </tr>`).join('');
    return `<table class="prod-mini-table">
        <thead><tr><th>Index</th>${ths}</tr></thead>
        <tbody>${trs}</tbody>
    </table>`;
}

// ─── LOAD PRODUCTIVITY ───────────────────────────────────────────────────────
async function loadProductivity() {
    showProdUI();
    clearMetricCards();
    document.getElementById('errorBanner').style.display='none';
    destroyAllCharts();
    stopScrollTimers();

    try {
        // Fetch locations
        if (!allLocations.length) {
            allLocations = await fetchLocations();
            const sel=document.getElementById('lineFilter');
            sel.innerHTML='<option value="">— Semua Line —</option>';
            allLocations.forEach(loc=>{
                sel.innerHTML+=`<option value="${loc.id}">${escH(loc.name)}</option>`;
            });
        }

        // Fetch rows
        const rows = await fetchProdRows();

        if (!rows || !rows.length) {
            document.getElementById('errorBanner').style.display='block';
            document.getElementById('errorBanner').textContent=
                '📭 Data productivity tidak tersedia. Pastikan server productivity-ms.yadin.com accessible.';
            // Render empty state per card
            for (const loc of allLocations) {
                const lid=String(loc.id);
                const sid=LOC_CARD[lid]??null;
                if(!sid) continue;
                setupProdFill(lid, sid, loc.name);
                const fill=document.getElementById('chartfill-'+sid);
                if(fill) fill.innerHTML='<div style="text-align:center;color:#9ca3af;padding:20px;font-size:11px;">⏳ Data tidak tersedia</div>';
            }
            return;
        }

        const ldMap = transformProdRows(rows);

        for (const loc of allLocations) {
            const lid=String(loc.id);
            const sid=LOC_CARD[lid]??null;
            if(!sid) continue;

            const lineMap=ldMap[lid]||{};
            const lineNames=Object.keys(lineMap);

            // Update card title
            const ct=document.getElementById('ctitle-'+sid);
            if(ct) ct.textContent=loc.name.toUpperCase();

            // Update border color
            const locColors=['#185FA5','#2e7d32','#854F0B','#6B2D8B'];
            const locIdx=allLocations.findIndex(l=>String(l.id)===lid);
            const cardEl=document.getElementById('card-'+sid);
            if(cardEl&&locIdx>=0) cardEl.style.borderTopColor=locColors[locIdx];

            // Update metric card
            const allP26=Object.values(lineMap).flatMap(ld=>ld.prod26).filter(v=>v!==null);
            const allR26=Object.values(lineMap).flatMap(ld=>ld.pass26).filter(v=>v!==null);
            const avgP=allP26.length?(allP26.reduce((a,b)=>a+b,0)/allP26.length).toFixed(2):'—';
            const avgR=allR26.length?(allR26.reduce((a,b)=>a+b,0)/allR26.length).toFixed(2):'—';
            setMetricCard(sid, avgP, escH(loc.name), '');
            const ml=document.querySelector('#mcard-'+sid+' .metric-label');
            if(ml) ml.textContent=loc.name.toUpperCase();
            const emojiEl=document.getElementById('metric-'+sid+'-emoji');
            if(emojiEl) emojiEl.style.display='none';

            // Setup prod fill container (mengganti chart-fill)
            setupProdFill(lid, sid, loc.name);

            const scrollWrap=document.getElementById(`prodscroll-${lid}`);
            if(!scrollWrap) continue;

            if(!lineNames.length) {
                scrollWrap.innerHTML='<div style="text-align:center;color:#9ca3af;padding:16px;font-size:11px;">📭 Tidak ada data</div>';
                continue;
            }

            // Render tiap line
            for (const [idx,lineName] of lineNames.entries()) {
                // Assembly: hanya tampilkan "Assembly Line"
                if(lid==='4' && lineName!=='Assembly Line') continue;

                const ld=lineMap[lineName];
                const chartId=`apex-${lid}-${idx}`;

                const lastR=ld.pass26.filter(v=>v!==null).slice(-1)[0]??null;
                const badgeOk=lastR!==null&&lastR>=95;
                const badgeColor=badgeOk?'#EAF3DE':'#FDECEA';
                const badgeTextColor=badgeOk?'#3B6D11':'#D0021B';
                const badgeLabel=lastR!==null?lastR.toFixed(1)+'% Pass':'No Data';

                const block=document.createElement('div');
                block.className='prod-line-card';
                block.innerHTML=`
                    <div class="prod-line-card-header">
                        <div class="prod-line-card-title">${escH(lineName)}</div>
                        <span class="prod-line-card-badge" style="background:${badgeColor};color:${badgeTextColor};">
                            ${badgeLabel}
                        </span>
                    </div>
                    <div class="prod-line-card-body">
                        <div class="prod-apex-wrap" id="${chartId}"></div>
                        <div class="prod-table-scroll" id="ptable-${lid}-${idx}"></div>
                    </div>`;
                scrollWrap.appendChild(block);

                document.getElementById(`ptable-${lid}-${idx}`).innerHTML=buildProdTable(ld);

                // Tunggu DOM settle lalu render chart
                await new Promise(r=>setTimeout(r,80));

                const el=document.getElementById(chartId);
                if(!el) { console.warn(`[APEX] Not found: ${chartId}`); continue; }

                // Force explicit dimensions
                el.style.height='160px';
                el.style.width='100%';
                el.style.display='block';

                // Reflow
                void el.offsetHeight;
                await new Promise(r=>setTimeout(r,30));

                try {
                    const opts=buildApexOptions(chartId,ld);
                    const chart=new ApexCharts(el,opts);
                    await chart.render();
                    apexCharts[chartId]=chart;
                } catch(err) {
                    console.error(`[APEX] Error ${chartId}:`,err);
                }
            }

            // Auto scroll
            await startAutoScroll(lid);
        }

        applyLineDim();

    } catch(e) {
        document.getElementById('errorBanner').style.display='block';
        document.getElementById('errorBanner').textContent='⚠ Gagal memuat data: '+e.message;
        console.error(e);
    }
}

// Setup prod fill — ganti chartfill & hide table
function setupProdFill(lid, sid, locName) {
    // Hide KPI table
    const tableDiv=document.getElementById('table-'+sid);
    if(tableDiv) tableDiv.style.display='none';

    // Badge reset
    const badge=document.getElementById('badge-'+sid);
    if(badge) badge.className='section-status-badge';

    // Ganti chartfill jadi prod-fill
    const fill=document.getElementById('chartfill-'+sid);
    if(fill) {
        fill.className='prod-fill';
        fill.removeAttribute('style');
        fill.innerHTML=`<div id="prodscroll-${lid}" style="display:contents;"></div>`;
        // display:contents biar prod-line-card langsung jadi children dari prod-fill
    }
}

// Auto scroll per location
async function startAutoScroll(lid) {
    const fill=document.getElementById('chartfill-' + LOC_CARD[lid]);
    if(!fill) return;

    await new Promise(r=>setTimeout(r,300));

    const maxScroll=fill.scrollHeight-fill.clientHeight;
    if(maxScroll<=0) return;

    const readTimeSec=Math.min(40,Math.max(5,(maxScroll/200)*3));
    const pxPerTick=maxScroll/(readTimeSec*1000/30);

    let pos=0, goingDown=true, pauseTicks=0;

    fill.addEventListener('mouseenter',()=>{pauseTicks=9999;});
    fill.addEventListener('mouseleave',()=>{pauseTicks=0;});

    const t=setInterval(()=>{
        if(pauseTicks>0){pauseTicks--;return;}
        if(goingDown){
            pos=Math.min(pos+pxPerTick,maxScroll);
            fill.scrollTop=pos;
            if(pos>=maxScroll){goingDown=false;pauseTicks=Math.round(2000/30);}
        } else {
            pos=Math.max(pos-pxPerTick*4,0);
            fill.scrollTop=pos;
            if(pos<=0){goingDown=true;pauseTicks=Math.round(1000/30);}
        }
    },30);
    scrollTimers.push(t);
}

// ─── KPI CHART.JS ────────────────────────────────────────────────────────────
function calcYAxis(datasets,kpi) {
    const all=datasets.flatMap(d=>d.data).filter(v=>v!==null&&v!==undefined);
    if(!all.length) return {};
    const mn=Math.min(...all),mx=Math.max(...all),rng=mx-mn||1,pad=rng*.15;
    if(kpi==='operation_ratio') return {min:Math.max(0,Math.floor((mn-pad)/5)*5),max:Math.min(100,Math.ceil((mx+pad)/5)*5)};
    if(kpi==='safety') return {min:0,max:Math.ceil(mx+1)};
    const step=Math.pow(10,Math.floor(Math.log10(rng)));
    return {min:Math.max(0,Math.floor((mn-pad)/step)*step),max:Math.ceil((mx+pad)/step)*step};
}

function buildKpiDatasets(json,section,kpi) {
    const color=SEC_COLOR[section]||'#185FA5';
    const data=json.data, dataPrev=json.data_prev, compare=json.compare;
    const yearStr='<?= $year ?>';
    const curFY=json.cur_fy??(yearStr!=='all'?yearStr.toUpperCase():'FY2026');
    const prevFY=json.prev_fy??'FY2025';
    if(!data[section]) return [];

    if(kpi==='operation_ratio') {
        const ds=[
            {label:`Actual ${curFY}`,data:data[section].actual??[],borderColor:color,backgroundColor:color+'18',borderWidth:2.5,pointRadius:4,pointHoverRadius:6,tension:.3,fill:true,spanGaps:false},
            {label:`Target ${curFY}`,data:data[section].target??[],borderColor:'#e53935',borderWidth:1.5,borderDash:[5,4],pointRadius:0,tension:0,fill:false,spanGaps:true,backgroundColor:'transparent'},
        ];
        if(compare&&dataPrev&&dataPrev[section])
            ds.push({label:`Actual ${prevFY}`,data:dataPrev[section].actual??[],borderColor:'#b0b0b0',backgroundColor:'transparent',borderWidth:1.5,borderDash:[3,3],pointRadius:2,pointHoverRadius:4,tension:.3,fill:false,spanGaps:false});
        return ds;
    }
    if(kpi==='fcost') {
        const tgtPct=data[section].pct_target??0.15;
        const ds=[
            {label:`Actual ${curFY}`,data:data[section].pct??[],borderColor:color,backgroundColor:color+'18',borderWidth:2.5,pointRadius:4,pointHoverRadius:6,tension:.3,fill:true,spanGaps:false},
            {label:`Target ${curFY}`,data:new Array(12).fill(tgtPct),borderColor:'#e53935',borderWidth:1.5,borderDash:[5,4],pointRadius:0,tension:0,fill:false,spanGaps:true,backgroundColor:'transparent'},
        ];
        if(compare&&dataPrev&&dataPrev[section])
            ds.push({label:`Actual ${prevFY}`,data:dataPrev[section].pct??[],borderColor:'#b0b0b0',backgroundColor:'transparent',borderWidth:1.5,borderDash:[3,3],pointRadius:2,pointHoverRadius:4,tension:.3,fill:false,spanGaps:false});
        return ds;
    }
    if(kpi==='safety') {
        const ds=[
            {label:'Minor',       data:data[section].minor??[],       borderColor:'#854F0B',borderWidth:2,  pointRadius:3,tension:.3,spanGaps:false},
            {label:'Significant', data:data[section].significant??[], borderColor:'#D0021B',borderWidth:2.5,pointRadius:3,tension:.3,spanGaps:false},
            {label:'Fatality',    data:data[section].fatality??[],    borderColor:'#501313',borderWidth:2,  pointRadius:3,tension:.3,spanGaps:false},
        ];
        if(compare&&dataPrev&&dataPrev[section])
            ds.push({label:`Minor ${prevFY}`,data:dataPrev[section].minor??[],borderColor:'#b0b0b0',backgroundColor:'transparent',borderWidth:1.5,borderDash:[3,3],pointRadius:2,tension:.3,fill:false,spanGaps:false});
        return ds;
    }
    if(kpi==='quality') {
        const ds=[];
        ds.push({label:`Actual ${curFY}`,data:data[section].reject_inhouse??[],borderColor:color,backgroundColor:color+'18',borderWidth:2.5,pointRadius:4,pointHoverRadius:6,tension:.3,fill:true,spanGaps:false});
        if(!(data[section].no_target??false))
            ds.push({label:'Batas Maximum',data:data[section].reject_target??[],borderColor:'#e53935',borderWidth:1.5,borderDash:[5,4],pointRadius:0,tension:0,fill:false,spanGaps:true,backgroundColor:'transparent'});
        if(compare&&dataPrev&&dataPrev[section])
            ds.push({label:`Actual ${prevFY}`,data:dataPrev[section].reject_inhouse??[],borderColor:'#b0b0b0',backgroundColor:'transparent',borderWidth:1.5,borderDash:[3,3],pointRadius:2,pointHoverRadius:4,tension:.3,fill:false,spanGaps:false});
        return ds;
    }
    return [];
}

function makeKpiChartOpts(kpi,datasets) {
    const yAxis=calcYAxis(datasets,kpi);
    return {
        responsive:true, maintainAspectRatio:false,
        plugins:{
            legend:{display:true,position:'top',align:'end',labels:{font:{size:9},boxWidth:18,padding:6,usePointStyle:true}},
            tooltip:{callbacks:{label:ctx=>{
                const v=ctx.parsed.y, l=ctx.dataset.label??'';
                if(kpi==='fcost')           return ` ${l}: ${v?.toFixed(2)}%`;
                if(kpi==='operation_ratio') return ` ${l}: ${v}%`;
                if(kpi==='quality')         return ` ${l}: ${Number(v).toLocaleString('id-ID')} PPM`;
                return ` ${l}: ${Number(v).toLocaleString('id-ID')}`;
            }}}
        },
        scales:{
            x:{ticks:{font:{size:9},maxRotation:0,autoSkip:true,maxTicksLimit:12},grid:{color:'#f0f0f0'}},
            y:{
                min:kpi==='fcost'?0:yAxis.min,
                max:kpi==='fcost'?null:yAxis.max,
                ticks:{font:{size:9},callback:v=>{
                    if(kpi==='fcost')           return v?.toFixed(2)+'%';
                    if(kpi==='operation_ratio') return v+'%';
                    if(kpi==='quality')         return Number(v).toLocaleString('id-ID');
                    return v;
                }},
                grid:{color:'#f0f0f0'}
            }
        }
    };
}

function fillKpiTable(json,section,kpi) {
    const sid=section.toLowerCase();
    const tbody=document.getElementById('tbody-'+sid);
    const color=SEC_COLOR[section];
    const data=json.data, dataPrev=json.data_prev, compare=json.compare;
    const yearStr='<?= $year ?>';
    const curFY=json.cur_fy??(yearStr!=='all'?yearStr.toUpperCase():'FY2026');
    const prevFY=json.prev_fy??'FY2025';
    if(!tbody||!data[section]) return;

    const fmt=(v,ok)=>{
        const k=ok??kpi;
        if(v===null||v===undefined) return '<span style="color:#d1d5db">—</span>';
        if(k==='operation_ratio') return v.toFixed(1)+'%';
        if(k==='fcost_pct')       return v.toFixed(2)+'%';
        if(k==='fcost_nominal')   return 'Rp'+Number(v).toLocaleString('id-ID');
        if(k==='quality')         return Number(v).toLocaleString('id-ID');
        return v;
    };
    const mkRow=(lbl,arr,dot,bold=false,ok=null)=>{
        const cells=arr.map(v=>`<td style="text-align:center;padding:2px 4px;border-bottom:1px solid #f0f0f0;font-weight:${bold?'700':'400'};color:${v===null?'#d1d5db':'#1a1a1a'}">${fmt(v,ok)}</td>`).join('');
        return `<tr>
            <td style="padding:2px 6px;border-bottom:1px solid #f0f0f0;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                <span style="width:8px;height:8px;border-radius:50%;background:${dot};display:inline-block;flex-shrink:0"></span>
                <span style="font-weight:600;color:#374151">${lbl}</span>
            </td>${cells}</tr>`;
    };

    let rows='';
    if(kpi==='operation_ratio') {
        rows+=mkRow(`Target ${curFY}`,data[section].target??[],'#e53935');
        rows+=mkRow(`Actual ${curFY}`,data[section].actual??[],color,true);
        if(compare&&dataPrev&&dataPrev[section])
            rows+=mkRow(`Actual ${prevFY}`,dataPrev[section].actual??[],'#b0b0b0');
    } else if(kpi==='fcost') {
        const pct=data[section].pct_target??0.15;
        rows+=mkRow(`Target (%)`,new Array(12).fill(pct),'#e53935',false,'fcost_pct');
        rows+=mkRow(`Actual (%)`,data[section].pct??[],color,true,'fcost_pct');
        rows+=mkRow(`Cost Reject`,data[section].actual??[],'#b0b0b0',false,'fcost_nominal');
        rows+=mkRow(`Sales`,data[section].sales??[],'#9ca3af',false,'fcost_nominal');
        if(compare&&dataPrev&&dataPrev[section])
            rows+=mkRow(`Actual % ${prevFY}`,dataPrev[section].pct??[],'#d1d5db',false,'fcost_pct');
    } else if(kpi==='quality') {
        const noTarget=data[section].no_target??false;
        if(!noTarget) rows+=mkRow(`Target ${curFY}`,data[section].reject_target??[],'#e53935');
        rows+=mkRow(`Actual ${curFY}`,data[section].reject_inhouse??[],color,true);
        if(compare&&dataPrev&&dataPrev[section])
            rows+=mkRow(`Actual ${prevFY}`,dataPrev[section].reject_inhouse??[],'#b0b0b0');
    } else if(kpi==='safety') {
        rows+=mkRow('Minor',      data[section].minor??[],      '#854F0B');
        rows+=mkRow('Significant',data[section].significant??[],'#D0021B',true);
        rows+=mkRow('Fatality',   data[section].fatality??[],   '#501313');
    }

    tbody.innerHTML=rows||'<tr><td colspan="13" style="text-align:center;color:#9ca3af;padding:4px">No data</td></tr>';
}

// ─── LOAD KPI ────────────────────────────────────────────────────────────────
async function loadKpi() {
    showKpiUI();
    clearMetricCards();
    document.getElementById('errorBanner').style.display='none';
    destroyAllCharts();

    const year='<?= $year ?>';
    const url=`../api/kpi_${activeKpi}.php`+(year!=='all'?`?year=${year}`:'');

    try {
        const r=await fetch(url);
        const json=await r.json();
        const {labels,data}=json;
        const sections=activeKpi==='fcost'?['Conrod','HDE']:['MS1','MS2','Conrod','HDE'];

        sections.forEach(sec=>{
            const sid=sec.toLowerCase();
            if(!data[sec]) return;

            let lastVal=null,lastTarget=null,prevVal=null;
            if(activeKpi==='operation_ratio'){
                lastVal=getLastNonNull(data[sec].actual);
                lastTarget=getLastNonNull(data[sec].target);
                prevVal=getPrevNonNull(data[sec].actual);
            } else if(activeKpi==='fcost'){
                lastVal=getLastNonNull(data[sec].pct);
                lastTarget=data[sec].pct_target??0.15;
                prevVal=getPrevNonNull(data[sec].pct);
            } else if(activeKpi==='safety'){
                const m=getLastNonNull(data[sec].minor),s=getLastNonNull(data[sec].significant),f=getLastNonNull(data[sec].fatality);
                lastVal=(m===null&&s===null&&f===null)?null:(m??0)+(s??0)+(f??0);
                lastTarget=0;
            } else if(activeKpi==='quality'){
                lastVal=getLastNonNull(data[sec].reject_inhouse);
                lastTarget=data[sec].no_target?null:getLastNonNull(data[sec].reject_target);
                prevVal=getPrevNonNull(data[sec].reject_inhouse);
            }

            setMetricCard(sid,fmtKpi(lastVal,activeKpi),getSubLabel(lastVal,lastTarget,activeKpi),getTrendLabel(lastVal,prevVal,activeKpi));
            setSectionBadge(sid,lastVal,lastTarget,activeKpi);
            setMetricEmoji(sid,lastVal,lastTarget,activeKpi);

            const canvas=document.getElementById('chart'+sec);
            if(!canvas) return;
            const datasets=buildKpiDatasets(json,sec,activeKpi);
            if(!datasets.length) return;
            kpiCharts['chart'+sec]=new Chart(canvas,{
                type:'line', data:{labels,datasets},
                options:makeKpiChartOpts(activeKpi,datasets)
            });
            fillKpiTable(json,sec,activeKpi);
        });
    } catch(e) {
        document.getElementById('errorBanner').style.display='block';
        document.getElementById('errorBanner').textContent='⚠ Gagal memuat data: '+e.message;
        console.error(e);
    }
}

// ─── INIT ────────────────────────────────────────────────────────────────────
(async()=>{
    if(activeKpi==='productivity_passrate'){
        showProdUI();
        await loadProductivity();
    } else {
        showKpiUI();
        await loadKpi();
    }
})();
</script>
</body>
</html>