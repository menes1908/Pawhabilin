<?php
// Simple block date persistence (file-based) so all admins & users share state.
// NOTE: Keeps changes minimal; no DB schema changes.
// GET  => returns JSON { blocked_date: 'YYYY-MM-DD' | null }
// POST => set date (date=YYYY-MM-DD) or clear (date='')

header('Content-Type: application/json');

$storageDir = __DIR__ . '/../../data';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0777, true);
}
$file = $storageDir . '/blocked_date.json';

function read_blocked($file){
    if (!file_exists($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false) return null;
    $j = json_decode($raw, true);
    if (!is_array($j)) return null;
    $d = $j['blocked_date'] ?? null;
    if ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    if ($date === '') {
        // clear
        @unlink($file);
        echo json_encode(['blocked_date' => null]);
        exit;
    }
    // Validate format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }
    // Save
    $payload = json_encode(['blocked_date' => $date, 'updated_at' => date('c')]);
    @file_put_contents($file, $payload);
    echo json_encode(['blocked_date' => $date]);
    exit;
}

// GET
$blocked = read_blocked($file);
http_response_code(200);
if ($blocked) {
    echo json_encode(['blocked_date' => $blocked]);
} else {
    echo json_encode(['blocked_date' => null]);
}
