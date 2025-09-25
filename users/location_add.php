<?php
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/location.php';
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method']); exit; }
if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'csrf']); exit; }
$user_id = $_SESSION['user']['users_id'] ?? null; if(!$user_id){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
$payload = [
    'label'=>trim($_POST['label'] ?? ''),
    'recipient_name'=>trim($_POST['recipient_name'] ?? ''),
    'phone'=>trim($_POST['phone'] ?? ''),
    'line1'=>trim($_POST['line1'] ?? ''),
    'line2'=>trim($_POST['line2'] ?? ''),
    'barangay'=>trim($_POST['barangay'] ?? ''),
    'city'=>trim($_POST['city'] ?? ''),
    'province'=>trim($_POST['province'] ?? ''),
    'is_default'=>!empty($_POST['is_default'])?1:0
];
// Basic validation
foreach(['recipient_name','line1','city','province'] as $req){ if($payload[$req]===''){ echo json_encode(['ok'=>false,'error'=>'missing_'.$req]); exit; } }
$loc = location_insert($connections,$user_id,$payload);
if(!$loc){ echo json_encode(['ok'=>false,'error'=>'db']); exit; }
// Return all for re-render
$all = location_get_all_by_user($connections,$user_id);
// Provide simplified serialized addresses for client
$addresses = array_map(function($r){
    return [
        'id'=>$r['location_id'],
        'label'=>$r['location_label'],
        'recipient'=>$r['location_recipient_name'],
        'phone'=>$r['location_phone'],
        'full'=>trim($r['location_address_line1'].' '.($r['location_address_line2']??'').' '.($r['location_barangay']??'').' '.$r['location_city'].' '.$r['location_province']),
        'is_default'=>(int)$r['location_is_default']
    ];
}, $all);
echo json_encode(['ok'=>true,'location'=>$loc,'locations'=>$addresses]);
