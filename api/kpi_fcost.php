<?php
error_reporting(0);
ini_set('display_errors', '0');
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
ob_clean();

try {
    $db = new PDO('mysql:host=localhost;dbname=ywk_dashboard;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
}

$year = $_GET['year'] ?? 'all';
$sections = ['Conrod', 'HDE'];
$month_labels = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$month_map = [4=>0, 5=>1, 6=>2, 7=>3, 8=>4, 9=>5, 10=>6, 11=>7, 12=>8, 1=>9, 2=>10, 3=>11];
$pct_target = ['Conrod' => 0.50, 'HDE' => 0.15];

if (!function_exists('getFYRange')) {
    function getFYRange($fy) {
        $year = (int) str_replace('fy', '', $fy);
        return ['start' => "$year-04-01", 'end' => ($year + 1) . "-03-31"];
    }
}

function getFcostRows($db, $fyRange) {
    if (!$fyRange) return [];
    $stmt = $db->prepare("
        SELECT periode, section, actual, sales
        FROM kpi_fcost
        WHERE periode BETWEEN :fy_start AND :fy_end
        ORDER BY periode ASC
    ");
    $stmt->execute([':fy_start' => $fyRange['start'], ':fy_end' => $fyRange['end']]);
    return $stmt->fetchAll();
}

function remapFcostToMonths($rows, $sections, $month_map, $pct_target) {
    $data = [];
    foreach ($sections as $sec) {
        $pct    = array_fill(0, 12, null);
        $actual = array_fill(0, 12, null);
        $sales  = array_fill(0, 12, null);
        
        foreach ($rows as $row) {
            if ($row['section'] !== $sec) continue;
            $m = (int) date('n', strtotime($row['periode']));
            $idx = $month_map[$m] ?? null;
            if ($idx === null) continue;
            
            $actualVal = $row['actual'] !== null ? (float)$row['actual'] : null;
            $salesVal  = $row['sales']  !== null ? (float)$row['sales']  : null;
            
            $actual[$idx] = $actualVal;
            $sales[$idx]  = $salesVal;
            
            if ($actualVal > 0 && $salesVal > 0) {
                $pct[$idx] = round(($actualVal / $salesVal) * 100, 2);
            }
        }
        
        $data[$sec] = [
            'pct'        => array_values($pct),
            'pct_target' => $pct_target[$sec],
            'actual'     => array_values($actual),
            'sales'      => array_values($sales),
        ];
    }
    return $data;
}

try {
    if ($year === 'all' || $year === '') {
        $rows26 = getFcostRows($db, getFYRange('fy2026'));
        $rows25 = getFcostRows($db, getFYRange('fy2025'));
        
        $data26 = remapFcostToMonths($rows26, $sections, $month_map, $pct_target);
        $data25 = remapFcostToMonths($rows25, $sections, $month_map, $pct_target);
        
        echo json_encode([
            'labels' => $month_labels,
            'data' => $data26,
            'data_prev' => $data25,
            'compare' => true,
            'cur_fy' => 'FY2026',
            'prev_fy' => 'FY2025',
        ]);
    } else {
        $fyRange = getFYRange($year);
        $rows = getFcostRows($db, $fyRange);
        $data = remapFcostToMonths($rows, $sections, $month_map, $pct_target);
        $fyLabel = 'FY' . str_replace('fy', '', $year);
        
        echo json_encode(['labels' => $month_labels, 'data' => $data, 'compare' => false, 'cur_fy' => strtoupper($fyLabel)]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>