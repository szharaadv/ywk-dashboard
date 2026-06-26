<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$db = getDB();

// Tahun terakhir
$stmt = $db->query("SELECT MAX(tahun) AS last_tahun FROM ywk_event");
$last = $stmt->fetch();
$last_tahun = $last['last_tahun'] ?? null;

if (!$last_tahun) {
    echo json_encode([
        'total_materi'       => 0,
        'total_participants' => 0,
        'winner'             => null,
        'photos'             => []
    ]);
    exit;
}

// Summary tahun terakhir
$stmt5 = $db->prepare("
    SELECT foto, judul_materi, peringkat, peserta, departemen
    FROM ywk_event
    WHERE tahun = :tahun
    AND foto IS NOT NULL
    AND foto != ''
    ORDER BY peringkat ASC
    LIMIT 10
");
$stmt5->execute([':tahun' => $last_tahun]);
$photos = $stmt5->fetchAll();

// Total peserta unik
$stmt3 = $db->prepare("
    SELECT COUNT(DISTINCT peserta) AS total_participants
    FROM ywk_event
    WHERE tahun = :tahun
");
$stmt3->execute([':tahun' => $last_tahun]);
$participants = $stmt3->fetch();

// Winner
$stmt4 = $db->prepare("
    SELECT judul_materi, peserta, departemen, foto
    FROM ywk_event
    WHERE tahun = :tahun AND peringkat = 1
    LIMIT 1
");
$stmt4->execute([':tahun' => $last_tahun]);
$winner = $stmt4->fetch();

// Semua peserta dengan foto — tampil sebanyak jumlah participant
// YWKS photos
$stmtYwks = $db->prepare("
    SELECT foto, judul_materi, peringkat, peserta, departemen, jenis_event
    FROM ywk_event
    WHERE tahun = :tahun
    AND foto IS NOT NULL AND foto != ''
    AND jenis_event = 'YWKS'
    ORDER BY peringkat ASC
");
$stmtYwks->execute([':tahun' => $last_tahun]);
$photos_ywks = $stmtYwks->fetchAll();

// YJP Internal photos
$stmtYjp = $db->prepare("
    SELECT foto, judul_materi, peringkat, peserta, departemen, jenis_event
    FROM ywk_event
    WHERE tahun = :tahun
    AND foto IS NOT NULL AND foto != ''
    AND jenis_event = 'YJP'
    ORDER BY peringkat ASC
");
$stmtYjp->execute([':tahun' => $last_tahun]);
$photos_yjp = $stmtYjp->fetchAll();

// Ambil participants per section dari config
$stmtCfg = $db->prepare("
    SELECT jenis_event, participants
    FROM ywk_event_config
    WHERE tahun = :tahun
");
$stmtCfg->execute([':tahun' => $last_tahun]);
$configs = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);

echo json_encode([
    'tahun'              => $last_tahun,
    'total_materi'       => (int) ($summary['total_materi'] ?? 0),
    'total_participants' => (int) ($participants['total_participants'] ?? 0),
    'winner'             => $winner ?? null,
    'photos'             => array_merge($photos_ywks, $photos_yjp),
    'photos_ywks'        => $photos_ywks,
    'photos_yjp'         => $photos_yjp,
    'participants_ywks'  => (int) ($configs['YWKS'] ?? 0),
    'participants_yjp'   => (int) ($configs['YJP']  ?? 0),
]);
?>