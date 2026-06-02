<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

function getLastPeriode($db, $table, $where, $params, $actual_col = 'actual', $allow_zero = false) {
    $zero_check = $allow_zero ? '' : "AND $actual_col != 0";
    $stmt = $db->prepare("
        SELECT MAX(periode) AS lp 
        FROM $table 
        $where 
        AND $actual_col IS NOT NULL
        $zero_check
    ");
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row['lp'] ?? null;
}

$year = $_GET['year'] ?? 'all';

$where  = "WHERE section != 'HDE'";
$params = [];

$fyRange = getFYRange($year);
if ($fyRange) {
    $where .= " AND periode BETWEEN :fy_start AND :fy_end";
    $params[':fy_start'] = $fyRange['start'];
    $params[':fy_end']   = $fyRange['end'];
}

// ===== OPERATION RATIO =====
$lp_or = getLastPeriode($db, 'kpi_operation_ratio', $where, $params, 'actual');
$or = ['actual' => null, 'target' => null, 'periode' => null];
if ($lp_or) {
    $stmt = $db->prepare("
        SELECT ROUND(AVG(actual), 1) AS actual, ROUND(AVG(target), 1) AS target
        FROM kpi_operation_ratio
        WHERE periode = :lp AND section != 'HDE'
    ");
    $stmt->execute([':lp' => $lp_or]);
    $row = $stmt->fetch();
    $or  = [
        'actual'  => (float) ($row['actual'] ?? 0),
        'target'  => (float) ($row['target'] ?? 0),
        'periode' => date('M Y', strtotime($lp_or))
    ];
}

// ===== SAFETY =====
// allow_zero = true karena zero accident (minor=0) adalah data valid
// ===== SAFETY =====
// Hitung hari tanpa accident dari tanggal terakhir accident
$last_accident_date = '2025-05-28'; // ← ganti sesuai tanggal terakhir accident
$today       = new DateTime();
$last_acc    = new DateTime($last_accident_date);
$days_safe   = (int) $today->diff($last_acc)->days;

$lp_sf = getLastPeriode($db, 'kpi_safety', $where, $params, 'minor', true);
$sf = [
    'total'      => 0,
    'target'     => 0,
    'periode'    => null,
    'days_safe'  => $days_safe,
];
if ($lp_sf) {
    $stmt = $db->prepare("
        SELECT SUM(minor + significant + fatality) AS total_accident, SUM(target) AS target
        FROM kpi_safety
        WHERE periode = :lp AND section != 'HDE'
    ");
    $stmt->execute([':lp' => $lp_sf]);
    $row  = $stmt->fetch();
    $total = (int) ($row['total_accident'] ?? 0);
    $sf   = [
        'total'     => $total,
        'target'    => (int) ($row['target'] ?? 0),
        'periode'   => date('M Y', strtotime($lp_sf)),
        'days_safe' => $total === 0 ? $days_safe : 0,
    ];
}

// ===== QUALITY =====
$lp_ql = getLastPeriode($db, 'kpi_quality', $where, $params, 'reject_inhouse');
$ql = ['actual' => null, 'target' => null, 'periode' => null];
if ($lp_ql) {
    $stmt = $db->prepare("
        SELECT ROUND(AVG(reject_inhouse), 0) AS actual, ROUND(AVG(reject_target), 0) AS target
        FROM kpi_quality
        WHERE periode = :lp AND section != 'HDE'
    ");
    $stmt->execute([':lp' => $lp_ql]);
    $row = $stmt->fetch();
    $ql  = [
        'actual'  => (float) ($row['actual'] ?? 0),
        'target'  => (float) ($row['target'] ?? 0),
        'periode' => date('M Y', strtotime($lp_ql))
    ];
}

// ===== F-COST =====
$lp_fc = getLastPeriode($db, 'kpi_fcost', $where, $params, 'actual');
$fc = ['actual' => null, 'target' => null, 'periode' => null];
if ($lp_fc) {
    $stmt = $db->prepare("
        SELECT ROUND(AVG(actual), 0) AS actual, ROUND(AVG(target), 0) AS target
        FROM kpi_fcost
        WHERE periode = :lp AND section != 'HDE'
    ");
    $stmt->execute([':lp' => $lp_fc]);
    $row = $stmt->fetch();
    $fc  = [
        'actual'  => (int) ($row['actual'] ?? 0),
        'target'  => (int) ($row['target'] ?? 0),
        'periode' => date('M Y', strtotime($lp_fc))
    ];
}

echo json_encode([
    'operation_ratio' => $or,
    'safety'          => $sf,
    'quality'         => $ql,
    'fcost'           => $fc
]);
?>