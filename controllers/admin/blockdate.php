<?php
// Simple block date persistence (file-based) so all admins & users share state.
// NOTE: Keeps changes minimal; no DB schema changes.
// GET  => returns JSON { blocked_date: 'YYYY-MM-DD' | null, blocked_range: {start:'YYYY-MM-DD', end:'YYYY-MM-DD'}|null }
// POST =>
//   - single date: date=YYYY-MM-DD
//   - range: start=YYYY-MM-DD & end=YYYY-MM-DD
//   - clear: (no date & no start)

header('Content-Type: application/json');

$storageDir = __DIR__ . '/../../data';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0777, true);
}
$file = $storageDir . '/blocked_date.json';

function read_blocked($file){
    if (!file_exists($file)) return ['blocked_date'=>null,'blocked_range'=>null];
    $raw = @file_get_contents($file);
    if ($raw === false) return ['blocked_date'=>null,'blocked_range'=>null];
    $j = json_decode($raw, true);
    if (!is_array($j)) return ['blocked_date'=>null,'blocked_range'=>null];
    $single = null; $range = null;
    if(isset($j['blocked_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $j['blocked_date'])) $single = $j['blocked_date'];
    if(isset($j['blocked_range']) && is_array($j['blocked_range'])){
        $r = $j['blocked_range'];
        if(isset($r['start'],$r['end']) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$r['start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$r['end'])){
            $range = ['start'=>$r['start'],'end'=>$r['end']];
        }
    }
    return ['blocked_date'=>$single,'blocked_range'=>$range];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    $start = trim($_POST['start'] ?? '');
    $end = trim($_POST['end'] ?? '');
    if ($date==='' && $start==='') { // clear
        @unlink($file);
        echo json_encode(['blocked_date'=>null,'blocked_range'=>null]);
        exit;
    }
    if ($date !== '') { // single date mode
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid date format']);
            exit;
        }
        $payload = json_encode(['blocked_date'=>$date,'blocked_range'=>null,'updated_at'=>date('c')]);
        @file_put_contents($file,$payload);
        echo json_encode(['blocked_date'=>$date,'blocked_range'=>null]);
        exit;
    }
    // range mode
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)) {
        http_response_code(400);
        echo json_encode(['error'=>'Invalid range format']);
        exit;
    }
    if ($end < $start) {
        http_response_code(400);
        echo json_encode(['error'=>'End date cannot be before start date']);
        exit;
    }
    $payload = json_encode(['blocked_date'=>null,'blocked_range'=>['start'=>$start,'end'=>$end],'updated_at'=>date('c')]);
    @file_put_contents($file,$payload);
    echo json_encode(['blocked_date'=>null,'blocked_range'=>['start'=>$start,'end'=>$end]]);
    exit;
}

// GET
$blocked = read_blocked($file);
http_response_code(200);
echo json_encode($blocked);
