<?php
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/location.php';
require_once __DIR__ . '/../models/product.php';
header('Content-Type: application/json');

// Delivery-only order placement endpoint (pickup removed)
if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method']); exit;
}
if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')){
    http_response_code(403); echo json_encode(['ok'=>false,'error'=>'csrf']); exit;
}
$user_id = $_SESSION['user']['users_id'] ?? ($_SESSION['users_id'] ?? null);
if(!$user_id){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
$cart = $_SESSION['cart'] ?? [];
if(empty($cart)){ echo json_encode(['ok'=>false,'error'=>'empty_cart']); exit; }
// Always delivery now
$payment = $_POST['payment_method'] ?? 'cod';
if(!in_array($payment,['cod','gcash','maya'],true)){ echo json_encode(['ok'=>false,'error'=>'bad_payment']); exit; }
$location_id = (int)($_POST['location_id'] ?? 0);
$client_amount = isset($_POST['client_amount']) && $_POST['client_amount'] !== '' ? (float)$_POST['client_amount'] : null;

// Re-validate items & compute total
$productTotal = 0.0; $itemsValidated=[]; $conn=$connections;
foreach($cart as $pid=>$ci){
    $p = product_get_by_id($conn,(int)$pid);
    if(!$p || !$p['products_active']){ echo json_encode(['ok'=>false,'error'=>'product_missing','product_id'=>$pid]); exit; }
    $stock = (int)$p['products_stock'];
    $qty = (int)$ci['qty'];
    if($qty<1) $qty=1; if($qty>$stock) { echo json_encode(['ok'=>false,'error'=>'stock_changed','product_id'=>$pid,'available'=>$stock]); exit; }
    $price = (float)$p['products_price'];
    $productTotal += $price * $qty;
    $itemsValidated[] = ['id'=>$pid,'qty'=>$qty,'price'=>$price];
}
// Subscription discount assumption 10%
$discountRate = 0.10; $hasDiscount=false;
// Minimal subscription check (needs proper model if extended)
$res = $conn->query("SELECT 1 FROM user_subscriptions us JOIN subscriptions s ON s.subscriptions_id=us.subscriptions_id WHERE us.users_id=".(int)$user_id." AND us.us_status='active' LIMIT 1");
if($res && $res->num_rows>0){ $hasDiscount=true; }
$discountAmount = $hasDiscount ? $productTotal * $discountRate : 0.0;
$deliveryFee = 50.0; // fixed delivery fee (always delivery)
$grandTotal = round($productTotal - $discountAmount + $deliveryFee,2);
if(in_array($payment,['gcash','maya'],true)){
    if($client_amount===null || abs($client_amount - $grandTotal) > 0.009){ echo json_encode(['ok'=>false,'error'=>'amount_mismatch','expected'=>$grandTotal]); exit; }
}
// Delivery validation
if($location_id<=0){ echo json_encode(['ok'=>false,'error'=>'no_location']); exit; }
$loc = location_get_by_id_for_user($conn,$user_id,$location_id);
if(!$loc){ echo json_encode(['ok'=>false,'error'=>'invalid_location']); exit; }
// Insert transaction
// Insert transaction (schema has no fulfillment column now)
$stmt = $conn->prepare("INSERT INTO transactions (users_id, transactions_amount, transactions_type, transactions_payment_method) VALUES (?,?,?,?)");
$type='product';
$stmt->bind_param('idss',$user_id,$grandTotal,$type,$payment);
if(!$stmt->execute()){ echo json_encode(['ok'=>false,'error'=>'db_transaction']); exit; }
$tid = $conn->insert_id;
// Insert line items
$tpStmt = $conn->prepare("INSERT INTO transaction_products (transactions_id, products_id, tp_quantity) VALUES (?,?,?)");
foreach($itemsValidated as $it){
    $qStr = (string)$it['qty'];
    $tpStmt->bind_param('iis',$tid,$it['id'],$qStr);
    if(!$tpStmt->execute()){ echo json_encode(['ok'=>false,'error'=>'db_line']); exit; }
}
// Create delivery record
$est = (new DateTime('today +2 days'))->format('Y-m-d');
$dStmt = $conn->prepare("INSERT INTO deliveries (transactions_id, location_id, deliveries_estimated_delivery_date) VALUES (?,?,?)");
$dStmt->bind_param('iis',$tid,$location_id,$est);
if(!$dStmt->execute()){ echo json_encode(['ok'=>false,'error'=>'db_delivery']); exit; }
// Clear cart
unset($_SESSION['cart']);
echo json_encode(['ok'=>true,'transaction_id'=>$tid,'total'=>$grandTotal,'delivery_fee'=>$deliveryFee,'discount'=>$discountAmount]);
