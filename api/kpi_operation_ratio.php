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
$sections = ['MS1', 'MS2', 'Conrod', 'HDE'];
$month_labels = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
$month_map = [4=>0, 5=>1, 6=>2, 7=>3, 8=>4, 9=>5, 10=>6, 11=>7, 12=>8, 1=>9, 2=>10, 3=>11];

if (!function_exists('getFYRange')) {
    function getFYRange($fy) {
        $year = (int) str_replace('fy', '', $fy);
        return ['start' => "$year-04-01", 'end' => ($year + 1) . "-03-31"];
    }
}

function getOrRows($db, $fyRange) {
    if (!$fyRange) return [];
    $stmt = $db->prepare("
        SELECT periode, section, actual, target
        FROM kpi_operation_ratio
        WHERE periode BETWEEN :fy_start AND :fy_end
        ORDER BY periode ASC
    ");
    $stmt->execute([':fy_start' => $fyRange['start'], ':fy_end' => $fyRange['end']]);
    return $stmt->fetchAll();
}

function remapOrToMonths($rows, $sections, $month_map) {
    $data = [];
    foreach ($sections as $sec) {
        $actual = array_fill(0, 12, null);
        $target = array_fill(0, 12, null);
        
        foreach ($rows as $row) {
            if ($row['section'] !== $sec) continue;
            $m = (int) date('n', strtotime($row['periode']));
            $idx = $month_map[$m] ?? null;
            if ($idx === null) continue;
            
            $actual[$idx] = $row['actual'] !== null ? (float)$row['actual'] : null;
            $target[$idx] = $row['target'] !== null ? (float)$row['target'] : null;
        }
        
        $data[$sec] = [
            'actual' => array_values($actual),
            'target' => array_values($target),
        ];
    }
    return $data;
}

try {
    if ($year === 'all' || $year === '') {
        $rows26 = getOrRows($db, getFYRange('fy2026'));
        $rows25 = getOrRows($db, getFYRange('fy2025'));
        
        $data26 = remapOrToMonths($rows26, $sections, $month_map);
        $data25 = remapOrToMonths($rows25, $sections, $month_map);
        
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
        $rows = getOrRows($db, $fyRange);
        $data = remapOrToMonths($rows, $sections, $month_map);
        $fyLabel = 'FY' . str_replace('fy', '', $year);
        
        echo json_encode(['labels' => $month_labels, 'data' => $data, 'compare' => false, 'cur_fy' => strtoupper($fyLabel)]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>