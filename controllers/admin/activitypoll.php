<?php
require_once __DIR__ . '/../../database.php';
header('Content-Type: application/json');
if(!isset($connections) || !$connections){ echo json_encode(['success'=>false,'error'=>'DB']); exit; }
// Accept since parameter (unix ms) to only return newer records
$since = isset($_GET['since']) ? (int)$_GET['since'] : 0; // client stores ms
// We'll map each domain to a simple query returning id, label, created_at epoch ms
$tables = [
  'appointment' => ["SELECT appointments_id id, appointments_full_name name, UNIX_TIMESTAMP(appointments_created_at)*1000 ts FROM appointments"],
  'order' => ["SELECT transactions_id id, CONCAT('Order #',transactions_id) name, UNIX_TIMESTAMP(transactions_created_at)*1000 ts FROM transactions WHERE transactions_type='product'"],
  'user' => ["SELECT users_id id, CONCAT(users_username) name, UNIX_TIMESTAMP(users_created_at)*1000 ts FROM users WHERE users_role='0'"],
  'subscriber' => ["SELECT us_id id, CONCAT('Sub #',us_id) name, UNIX_TIMESTAMP(us_start_date)*1000 ts FROM user_subscriptions"],
  'sitter' => ["SELECT sitters_id id, sitters_name name, UNIX_TIMESTAMP(sitters_created_at)*1000 ts FROM sitters"],
  'pet' => ["SELECT pets_id id, pets_name name, UNIX_TIMESTAMP(pets_created_at)*1000 ts FROM pets"],
];
$results = [];
$latest = $since;
foreach($tables as $type=>$queries){
  foreach($queries as $sql){
    if($res = mysqli_query($connections,$sql)){
      while($row = mysqli_fetch_assoc($res)){
        $ts = (int)$row['ts'];
        if($ts <= $since) continue; // skip old
        if($ts > $latest) $latest = $ts;
        $results[] = [
          'type' => $type,
          'id' => (int)$row['id'],
          'label' => $row['name'],
          'ts' => $ts
        ];
      }
      mysqli_free_result($res);
    }
  }
}
// Sort by timestamp desc
usort($results, function($a,$b){ return $b['ts'] <=> $a['ts']; });
// Limit payload
if(count($results) > 50) $results = array_slice($results,0,50);
echo json_encode(['success'=>true,'latest'=>$latest,'items'=>$results]);
