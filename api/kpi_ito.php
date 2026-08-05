<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

// year param, mis. "fy2026". Default fy2026.
$year    = $_GET['year'] ?? 'fy2026';
$curYear = (int) str_replace('fy', '', $year);
if ($curYear < 2000) $curYear = 2026;
$lastYear = $curYear - 1;
$curFy    = 'fy' . $curYear;
$lastFy   = 'fy' . $lastYear;

$months = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];

// Ambil data 1 FY (Apr fyYear .. Mar fyYear+1) → array 12 bulan fiscal
function itoFiscalArrays($db, $fyYear) {
    $start = sprintf('%d-04-01', $fyYear);
    $end   = sprintf('%d-03-31', $fyYear + 1);
    $stmt  = $db->prepare("
        SELECT DATE_FORMAT(periode,'%Y-%m') AS ym, ito_days, inventory_amount
        FROM kpi_ito
        WHERE periode BETWEEN ? AND ?
    ");
    $stmt->execute([$start, $end]);

    $days = array_fill(0, 12, null);
    $amt  = array_fill(0, 12, null);
    foreach ($stmt->fetchAll() as $r) {
        $m  = (int) substr($r['ym'], 5, 2);
        $fi = ($m - 4 + 12) % 12;               // Apr=0 … Mar=11
        $days[$fi] = $r['ito_days'] !== null ? (float) $r['ito_days'] : null;
        $amt[$fi]  = $r['inventory_amount'] !== null ? (float) $r['inventory_amount'] : null;
    }
    return ['days' => $days, 'amt' => $amt];
}

$cur  = itoFiscalArrays($db, $curYear);
$last = itoFiscalArrays($db, $lastYear);

echo json_encode([
    'cur_fy'  => $curFy,
    'last_fy' => $lastFy,
    'labels'  => $months,
    'days'    => [ $curFy => $cur['days'], $lastFy => $last['days'] ],
    'amt'     => [ $curFy => $cur['amt'],  $lastFy => $last['amt']  ],
]);
