<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db   = getDB();
$kpi  = $_GET['kpi']  ?? 'operation_ratio';
$year = $_GET['year'] ?? 'fy2026';

$fyMap = [
    'fy2025' => ['cur'=>['start'=>'2025-04-01','end'=>'2026-03-31'],'last'=>['start'=>'2024-04-01','end'=>'2025-03-31']],
    'fy2026' => ['cur'=>['start'=>'2026-04-01','end'=>'2027-03-31'],'last'=>['start'=>'2025-04-01','end'=>'2026-03-31']],
];

$fyKey = array_key_exists($year, $fyMap) ? $year : 'fy2026';
$curFY = $fyMap[$fyKey]['cur'];
$lastFY= $fyMap[$fyKey]['last'];

$sections  = ['MS1','MS2','Conrod','HDE'];
$fy_months = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];

$tableMap = [
    'operation_ratio' => ['table'=>'kpi_operation_ratio','col'=>'actual',        'type'=>'avg'],
    'fcost'           => ['table'=>'kpi_fcost',          'col'=>'actual',        'type'=>'cum'],
    'safety'          => ['table'=>'kpi_safety',         'col'=>'safety_total',  'type'=>'cum'],
    'quality'         => ['table'=>'kpi_quality',        'col'=>'reject_inhouse','type'=>'avg'],
];

if (!array_key_exists($kpi, $tableMap)) {
    echo json_encode(['error'=>'Invalid KPI']); exit;
}

$cfg   = $tableMap[$kpi];
$table = $cfg['table'];
$col   = $cfg['col'];
$type  = $cfg['type'];

function fetchRows($db, $kpi, $table, $col, $fyRange) {
    $params = [':fy_start'=>$fyRange['start'], ':fy_end'=>$fyRange['end']];

    if ($kpi === 'safety') {
        $sql = "
            SELECT DATE_FORMAT(periode,'%b') AS mon,
                   MONTH(periode) AS mon_num,
                   section,
                   (COALESCE(minor,0)+COALESCE(significant,0)+COALESCE(fatality,0)) AS val
            FROM kpi_safety
            WHERE periode BETWEEN :fy_start AND :fy_end
            ORDER BY periode ASC
        ";
    } else {
        $sql = "
            SELECT DATE_FORMAT(periode,'%b') AS mon,
                   MONTH(periode) AS mon_num,
                   section,
                   `$col` AS val
            FROM `$table`
            WHERE periode BETWEEN :fy_start AND :fy_end
              AND `$col` IS NOT NULL
            ORDER BY periode ASC
        ";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function buildYTD($rows, $sections, $type, $fyStart) {
    $startMon = (int) date('n', strtotime($fyStart));

    // Urutan bulan FY (Apr=4 ... Mar=3)
    $orderedMonths = [];
    for ($i = 0; $i < 12; $i++) {
        $orderedMonths[] = (($startMon - 1 + $i) % 12) + 1;
    }

    // Index data per section per bulan
    $bySection = [];
    foreach ($rows as $row) {
        $sec = $row['section'];
        $mon = (int)$row['mon_num'];
        $val = $row['val'] !== null ? (float)$row['val'] : null;
        $bySection[$sec][$mon] = $val;
    }

    $result = [];
    foreach ($sections as $sec) {
        $ytd     = [];
        $cumSum  = 0;
        $count   = 0;
        $started = false;

        foreach ($orderedMonths as $mon) {
            // Kalau bulan ini tidak ada data
            if (!isset($bySection[$sec][$mon])) {
                // Kalau belum pernah ada data sama sekali → null
                // Kalau sudah pernah ada data tapi bulan ini kosong → null (future)
                $ytd[] = null;
                continue;
            }

            $val     = $bySection[$sec][$mon];
            $started = true;

            if ($type === 'cum') {
                $cumSum += $val;
                $ytd[]   = round($cumSum, 2);
            } else {
                // Running average — hanya hitung bulan yang ada datanya
                $count++;
                $cumSum += $val;
                $ytd[]   = round($cumSum / $count, 2);
            }
        }
        $result[$sec] = $ytd;
    }
    return $result;
}

$curRows  = fetchRows($db, $kpi, $table, $col, $curFY);
$lastRows = fetchRows($db, $kpi, $table, $col, $lastFY);

$curYTD  = buildYTD($curRows,  $sections, $type, $curFY['start']);
$lastYTD = buildYTD($lastRows, $sections, $type, $lastFY['start']);

echo json_encode([
    'labels'  => $fy_months,
    'cur_fy'  => $fyKey,
    'last_fy' => 'fy'.((int)substr($fyKey,2)-1),
    'type'    => $type,
    'cur'     => $curYTD,
    'last'    => $lastYTD,
]);
?>