<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$year = $_GET['year'] ?? 'fy2026';

$where  = "WHERE section != 'HDE'";
$params = [];

$fyRange = getFYRange($year);
if ($fyRange) {
    $where  .= " AND periode BETWEEN :fy_start AND :fy_end";
    $params[':fy_start'] = $fyRange['start'];
    $params[':fy_end']   = $fyRange['end'];
}

// Helper: ambil MAX periode yang ada data (non-null, non-zero)
function getLastPeriode($db, $table, $where, $params, $col = 'actual', $allow_zero = false) {
    $zc = $allow_zero ? '' : "AND $col != 0";
    $stmt = $db->prepare("SELECT MAX(periode) AS lp FROM $table $where AND $col IS NOT NULL $zc");
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row['lp'] ?? null;
}

// Helper: format range label "Apr 2026 – Jun 2026"
function rangeLabel($start, $end) {
    if (!$start || !$end) return null;
    $s = date('M Y', strtotime($start));
    $e = date('M Y', strtotime($end));
    return $s === $e ? $s : "$s – $e";
}

// ── OPERATION RATIO (YTD avg) ──────────────────────────────────────────────
$lp_or = getLastPeriode($db, 'kpi_operation_ratio', $where, $params, 'actual');
$or    = ['actual' => null, 'target' => null, 'periode' => null, 'bulan' => 0];
if ($lp_or) {
    // Ambil semua bulan dari awal FY s/d bulan terakhir
    $w2 = $where . " AND periode <= :lp_or";
    $p2 = array_merge($params, [':lp_or' => $lp_or]);
    $stmt = $db->prepare("
        SELECT
            ROUND(AVG(actual), 1) AS actual,
            ROUND(AVG(target), 1) AS target,
            COUNT(DISTINCT periode) AS bulan,
            MIN(periode) AS first_p
        FROM kpi_operation_ratio $w2
    ");
    $stmt->execute($p2);
    $row = $stmt->fetch();
    $or  = [
        'actual'  => $row['actual'] !== null ? (float)$row['actual'] : null,
        'target'  => $row['target'] !== null ? (float)$row['target'] : null,
        'periode' => rangeLabel($row['first_p'], $lp_or),
        'bulan'   => (int)$row['bulan'],
    ];
}

// ── SAFETY (YTD total accident + days safe) ────────────────────────────────
$last_accident_date = '2025-05-28';
$today     = new DateTime();
$last_acc  = new DateTime($last_accident_date);
$days_safe = (int)$today->diff($last_acc)->days;

$lp_sf = getLastPeriode($db, 'kpi_safety', $where, $params, 'minor', true);
$sf    = ['total' => 0, 'target' => 0, 'periode' => null, 'days_safe' => $days_safe, 'bulan' => 0];
if ($lp_sf) {
    $w2 = $where . " AND periode <= :lp_sf";
    $p2 = array_merge($params, [':lp_sf' => $lp_sf]);
    $stmt = $db->prepare("
        SELECT
            SUM(minor + significant + fatality) AS total_accident,
            SUM(target) AS target,
            COUNT(DISTINCT periode) AS bulan,
            MIN(periode) AS first_p
        FROM kpi_safety $w2
    ");
    $stmt->execute($p2);
    $row   = $stmt->fetch();
    $total = (int)($row['total_accident'] ?? 0);
    $sf    = [
        'total'     => $total,
        'target'    => (int)($row['target'] ?? 0),
        'periode'   => rangeLabel($row['first_p'], $lp_sf),
        'days_safe' => $total === 0 ? $days_safe : 0,
        'bulan'     => (int)$row['bulan'],
    ];
}

// ── QUALITY (YTD avg reject inhouse) ──────────────────────────────────────
$lp_ql = getLastPeriode($db, 'kpi_quality', $where, $params, 'reject_inhouse');
$ql    = ['actual' => null, 'target' => null, 'periode' => null, 'bulan' => 0];
if ($lp_ql) {
    $w2 = $where . " AND periode <= :lp_ql";
    $p2 = array_merge($params, [':lp_ql' => $lp_ql]);
    $stmt = $db->prepare("
        SELECT
            ROUND(AVG(reject_inhouse), 0) AS actual,
            ROUND(AVG(reject_target),  0) AS target,
            COUNT(DISTINCT periode) AS bulan,
            MIN(periode) AS first_p
        FROM kpi_quality $w2
    ");
    $stmt->execute($p2);
    $row = $stmt->fetch();
    $ql  = [
        'actual'  => $row['actual'] !== null ? (float)$row['actual'] : null,
        'target'  => $row['target'] !== null ? (float)$row['target'] : null,
        'periode' => rangeLabel($row['first_p'], $lp_ql),
        'bulan'   => (int)$row['bulan'],
    ];
}

// ── F-COST (YTD total) ────────────────────────────────────────────────────
$lp_fc = getLastPeriode($db, 'kpi_fcost', $where, $params, 'actual');
$fc    = ['actual' => null, 'target' => null, 'periode' => null, 'bulan' => 0];
if ($lp_fc) {
    $w2 = $where . " AND periode <= :lp_fc";
    $p2 = array_merge($params, [':lp_fc' => $lp_fc]);
    $stmt = $db->prepare("
        SELECT
            ROUND(SUM(actual) / COUNT(DISTINCT periode), 0) AS actual,
            ROUND(AVG(target), 0) AS target,
            COUNT(DISTINCT periode) AS bulan,
            MIN(periode) AS first_p
        FROM kpi_fcost $w2
    ");
    $stmt->execute($p2);
    $row = $stmt->fetch();
    $fc  = [
        'actual'  => $row['actual'] !== null ? (int)$row['actual'] : null,
        'target'  => $row['target'] !== null ? (int)$row['target'] : null,
        'periode' => rangeLabel($row['first_p'], $lp_fc),
        'bulan'   => (int)$row['bulan'],
    ];
}

echo json_encode([
    'operation_ratio' => $or,
    'safety'          => $sf,
    'quality'         => $ql,
    'fcost'           => $fc,
    'fy'              => $year,
]);
