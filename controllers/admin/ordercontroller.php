<?php
require_once __DIR__ . '/../../database.php';

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
    $actual = $_POST['deliveries_actual_delivery_date'] ?? '';
    $signature = isset($_POST['signature_received']) ? 1 : 0;

    // Ensure delivery row exists
    $existsRes = mysqli_query($connections, "SELECT deliveries_id FROM deliveries WHERE transactions_id=$tid LIMIT 1");
    if ($existsRes && mysqli_num_rows($existsRes) === 0) {
        // create minimal delivery record
        mysqli_query($connections, "INSERT INTO deliveries (transactions_id, deliveries_delivery_status) VALUES ($tid,'$status')");
    }
    if($existsRes) mysqli_free_result($existsRes);

    $stmt = mysqli_prepare($connections, "UPDATE deliveries SET deliveries_delivery_status=?, deliveries_estimated_delivery_date=?, deliveries_actual_delivery_date=?, deliveries_recipient_signature=? WHERE transactions_id=?");
    mysqli_stmt_bind_param($stmt,'sssii',$status,$eta,$actual,$signature,$tid);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if($isJson) json_out(['success'=> $affected>=0]);
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
