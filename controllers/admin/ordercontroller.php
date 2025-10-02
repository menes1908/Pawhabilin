<?php
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';
// Reuse ensure_points_schema from appointment awarding logic (inline copy to avoid cross-file include changes)
if(!function_exists('ensure_points_schema_orders')){
    function ensure_points_schema_orders($conn){
        if(!$conn) return;
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_points_balance (users_id INT PRIMARY KEY, upb_points INT NOT NULL DEFAULT 0, upb_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        @mysqli_query($conn, "ALTER TABLE user_points_balance ADD COLUMN upb_points INT NOT NULL DEFAULT 0 AFTER users_id");
        @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_points INT NOT NULL DEFAULT 0 AFTER users_id");
        @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_reason VARCHAR(100) AFTER upl_points");
        @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_source_type VARCHAR(50) AFTER upl_reason");
        @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_source_id INT AFTER upl_source_type");
    }
}

header_remove('X-Powered-By');

function redirect_back($msg = '', $type='success') {
    $loc = '../../views/admin/admin.php?section=orders';
    if ($msg !== '') {
        $loc .= '&msg=' . urlencode($msg) . '&type=' . urlencode($type);
    }
    header("Location: $loc");
    exit;
}

function json_out($data, $status=200){
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json');

if(!$connections){
    $msg = 'DB connection error';
    $isJson ? json_out(['success'=>false,'error'=>$msg],500) : redirect_back($msg,'error');
}

if ($action === 'delete') {
    $tid = isset($_POST['transactions_id']) ? (int)$_POST['transactions_id'] : 0;
    if ($tid <= 0) {
        $msg = 'Invalid transaction id';
        return $isJson ? json_out(['success'=>false,'error'=>$msg],400) : redirect_back($msg,'error');
    }
    // Delete line items first to maintain FK integrity
    mysqli_query($connections, "DELETE FROM transaction_products WHERE transactions_id=$tid");
    mysqli_query($connections, "DELETE FROM deliveries WHERE transactions_id=$tid");
    mysqli_query($connections, "DELETE FROM transactions WHERE transactions_id=$tid");
    $ok = mysqli_affected_rows($connections) >= 0; // if transaction row existed it will be removed
    // Audit: order deletion
    log_admin_action($connections, 'updates', [
        'target' => 'order',
        'target_id' => (string)$tid,
        'details' => ['message' => 'Deleted order'],
        'previous' => ['transactions_id' => $tid],
        'new' => null
    ]);
    if($isJson) json_out(['success'=>$ok]);
    redirect_back($ok? 'Order deleted':'Nothing deleted');
}

if ($action === 'update_delivery') {
    $tid = isset($_POST['transactions_id']) ? (int)$_POST['transactions_id'] : 0;
    if ($tid <= 0) {
        $msg='Invalid transaction id';
        return $isJson ? json_out(['success'=>false,'error'=>$msg],400) : redirect_back($msg,'error');
    }
    $status = trim($_POST['deliveries_delivery_status'] ?? '');
    $allowedStatus = ['processing','out_for_delivery','delivered','cancelled'];
    if(!in_array($status,$allowedStatus,true)) $status='processing';
    $eta = $_POST['deliveries_estimated_delivery_date'] ?? '';
    $actualInput = $_POST['deliveries_actual_delivery_date'] ?? '';
    $signature = isset($_POST['signature_received']) ? 1 : 0;

    // Fetch current actual & signature so we don't overwrite with empty
    $curActual = null; $curSig = 0;
    if($rsCur = mysqli_query($connections, "SELECT deliveries_actual_delivery_date, deliveries_recipient_signature FROM deliveries WHERE transactions_id=$tid LIMIT 1")){
        if($rowCur = mysqli_fetch_assoc($rsCur)){
            $curActual = $rowCur['deliveries_actual_delivery_date'];
            $curSig = (int)$rowCur['deliveries_recipient_signature'];
        }
        mysqli_free_result($rsCur);
    }

    // Determine final actual date: keep existing unless admin provided a new date OR status set to delivered with no existing actual and no user signature yet.
    $finalActual = $curActual; // default preserve
    $actualInput = trim($actualInput);
    if($actualInput !== '') {
        $finalActual = $actualInput; // admin explicitly set
    } else {
        // if admin leaves blank and status moved to delivered AND there is a signature checkbox (admin marks received) then set to today if not already set
        if($status === 'delivered' && $signature && !$curActual){
            $finalActual = date('Y-m-d');
        }
    }

    // If signature checkbox not ticked, but existing signature exists, keep it; else pass 0
    if(!$signature && $curSig){
        $signature = $curSig; // preserve previous signature flag (may be 1 with string)
    }

    // Ensure delivery row exists
    $existsRes = mysqli_query($connections, "SELECT deliveries_id FROM deliveries WHERE transactions_id=$tid LIMIT 1");
    if ($existsRes && mysqli_num_rows($existsRes) === 0) {
        // create minimal delivery record
        mysqli_query($connections, "INSERT INTO deliveries (transactions_id, deliveries_delivery_status) VALUES ($tid,'$status')");
    }
    if($existsRes) mysqli_free_result($existsRes);

    $stmt = mysqli_prepare($connections, "UPDATE deliveries SET deliveries_delivery_status=?, deliveries_estimated_delivery_date=?, deliveries_actual_delivery_date=?, deliveries_recipient_signature=? WHERE transactions_id=?");
    mysqli_stmt_bind_param($stmt,'sssii',$status,$eta,$finalActual,$signature,$tid);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    // Audit: order/delivery update
    log_admin_action($connections, 'updates', [
        'target' => 'order',
        'target_id' => (string)$tid,
        'details' => [
            'message' => 'Updated delivery status',
            'fields_changed' => ['deliveries_delivery_status','deliveries_estimated_delivery_date','deliveries_actual_delivery_date','deliveries_recipient_signature']
        ],
        'previous' => [
            'deliveries_actual_delivery_date' => $curActual,
            'deliveries_recipient_signature' => $curSig
        ],
        'new' => [
            'deliveries_delivery_status' => $status,
            'deliveries_estimated_delivery_date' => $eta,
            'deliveries_actual_delivery_date' => $finalActual,
            'deliveries_recipient_signature' => $signature
        ]
    ]);
    // Points awarding: only when status results in delivered with a signature (received) and first time (no prior ledger)
    $awarded_points = 0; $new_balance = null; $award_ok = false;
    if($status === 'delivered' && $signature){
        // Fetch transaction amount & user id
        if($tx = mysqli_query($connections, "SELECT users_id, transactions_amount FROM transactions WHERE transactions_id=$tid LIMIT 1")){
            if($txr = mysqli_fetch_assoc($tx)){
                $uid = (int)$txr['users_id']; $amount = (float)$txr['transactions_amount'];
                if($uid>0 && $amount>0){
                    // Check active subscription
                    $has_sub=false; if($rs=mysqli_query($connections, "SELECT 1 FROM user_subscriptions WHERE users_id=$uid AND us_status='active' AND (us_end_date IS NULL OR us_end_date >= NOW()) LIMIT 1")){ if(mysqli_fetch_row($rs)) $has_sub=true; mysqli_free_result($rs); }
                    if($has_sub){
                        // Compute award (10 points per 100 pesos) integer floor
                        $calc = (int)floor($amount / 100) * 10;
                        if($calc>0){
                            ensure_points_schema_orders($connections);
                            // Insert IGNORE ledger to avoid duplicates (source_type='order')
                            if($ins = mysqli_prepare($connections, "INSERT IGNORE INTO user_points_ledger (users_id,upl_points,upl_reason,upl_source_type,upl_source_id) VALUES (?,?,?,?,?)")){
                                $reason='Order Received'; $stype='order'; $src=$tid; $pts=$calc; mysqli_stmt_bind_param($ins,'iissi',$uid,$pts,$reason,$stype,$src);
                                if(mysqli_stmt_execute($ins)){
                                    if(mysqli_stmt_affected_rows($ins)===1){
                                        mysqli_query($connections, "INSERT INTO user_points_balance (users_id,upb_points) VALUES ($uid,$pts) ON DUPLICATE KEY UPDATE upb_points=upb_points+VALUES(upb_points)");
                                        if($rb = mysqli_query($connections, "SELECT upb_points FROM user_points_balance WHERE users_id=$uid LIMIT 1")){
                                            if($rbrow = mysqli_fetch_assoc($rb)){ $new_balance=(int)$rbrow['upb_points']; $awarded_points=$pts; $award_ok=true; }
                                            mysqli_free_result($rb);
                                        }
                                    } else {
                                        // duplicate award attempt - fetch existing balance
                                        if($rb2 = mysqli_query($connections, "SELECT upb_points FROM user_points_balance WHERE users_id=$uid LIMIT 1")){
                                            if($rb2row = mysqli_fetch_assoc($rb2)){ $new_balance=(int)$rb2row['upb_points']; }
                                            mysqli_free_result($rb2);
                                        }
                                    }
                                }
                                mysqli_stmt_close($ins);
                            }
                        }
                    }
                }
            }
            mysqli_free_result($tx);
        }
    }
    if($isJson) json_out(['success'=> $affected>=0, 'points_awarded'=>$awarded_points, 'new_points_balance'=>$new_balance]);
    redirect_back('Order updated');
}

if ($action === 'get' && isset($_GET['transactions_id'])) {
    $tid = (int)$_GET['transactions_id'];
    if ($tid<=0) $tid=0;
    $sql = "SELECT t.transactions_id, t.transactions_amount, t.transactions_payment_method, t.transactions_created_at,
                   d.deliveries_delivery_status, d.deliveries_estimated_delivery_date, d.deliveries_actual_delivery_date, d.deliveries_recipient_signature
            FROM transactions t
            LEFT JOIN deliveries d ON d.transactions_id=t.transactions_id
            WHERE t.transactions_id=$tid LIMIT 1";
    $res = mysqli_query($connections,$sql);
    $row = $res? mysqli_fetch_assoc($res):null;
    if($res) mysqli_free_result($res);
    $row ? json_out(['success'=>true,'order'=>$row]) : json_out(['success'=>false,'error'=>'Not found'],404);
}

// Default fallback
if($isJson) json_out(['success'=>false,'error'=>'Unsupported action'],400);
redirect_back('Unsupported action','error');
?>
