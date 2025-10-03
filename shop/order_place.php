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
$appliedCouponCode = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
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
// Delivery validation
if($location_id<=0){ echo json_encode(['ok'=>false,'error'=>'no_location']); exit; }
$loc = location_get_by_id_for_user($conn,$user_id,$location_id);
if(!$loc){ echo json_encode(['ok'=>false,'error'=>'invalid_location']); exit; }
// Optional: coupon application (product-type promo) before inserting transaction
$appliedPromo = null; $appliedDiscount = 0.0; $promoRedemptionId = null;
if($appliedCouponCode !== ''){
    // Ensure promotions table has the code
    $safe = mysqli_prepare($conn, "SELECT promo_id, promo_type, promo_discount_type, promo_discount_value, promo_per_user_limit, promo_usage_limit FROM promotions WHERE promo_code=? AND promo_active=1 LIMIT 1");
    if($safe){
        mysqli_stmt_bind_param($safe,'s',$appliedCouponCode);
        mysqli_stmt_execute($safe);
        $resPromo = mysqli_stmt_get_result($safe);
        if($resPromo && $pr = mysqli_fetch_assoc($resPromo)){
            // Only allow product promos here
            if(strtolower($pr['promo_type'])==='product'){
                $appliedPromo = $pr;
                // Enforce per-user limit using promotion_redemptions table
                @mysqli_query($conn,"CREATE TABLE IF NOT EXISTS promotion_redemptions (redemption_id BIGINT AUTO_INCREMENT PRIMARY KEY, promo_id INT NOT NULL, code_id BIGINT NULL, users_id INT NOT NULL, transactions_id INT NULL, appointment_id INT NULL, pr_status ENUM('reserved','applied','cancelled') NOT NULL DEFAULT 'reserved', pr_discount_amount DECIMAL(10,2) NULL, pr_points_spent INT NULL, pr_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, pr_applied_at DATETIME NULL, KEY idx_promo(promo_id), KEY idx_user(promo_id,users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                // Count usage by this user
                $limitUser = $pr['promo_per_user_limit'];
                if($limitUser !== null && $limitUser !== '' && ctype_digit((string)$limitUser)){
                    $limitUser = (int)$limitUser; if($limitUser>0){
                        $cntRes = mysqli_query($conn, "SELECT COUNT(*) c FROM promotion_redemptions WHERE promo_id=".(int)$pr['promo_id']." AND users_id=".(int)$user_id." AND pr_status='applied'");
                        $cntRow = $cntRes? mysqli_fetch_assoc($cntRes):['c'=>0]; if($cntRes) mysqli_free_result($cntRes);
                        if((int)$cntRow['c'] >= $limitUser){
                            // Exceeded per-user limit -> ignore coupon
                            $appliedPromo = null;
                        }
                    }
                }
                // TODO: global usage limit can be enforced similarly if required
            }
        }
        if($resPromo) mysqli_free_result($resPromo);
        mysqli_stmt_close($safe);
    }
    if($appliedPromo){
        // Calculate discount on productTotal (before subscription discount) using promo discount type
        if($appliedPromo['promo_discount_type']==='percent'){
            $appliedDiscount = round($productTotal * ((float)$appliedPromo['promo_discount_value']/100),2);
        } elseif($appliedPromo['promo_discount_type']==='fixed') { // 'fixed' matches schema enum
            $appliedDiscount = min(round((float)$appliedPromo['promo_discount_value'],2), $productTotal);
        }
    }
}
// Reapply subscription discount after coupon (business choice: apply subscriber after coupon or before; using after-coupon base)
if($appliedPromo){
    $productTotalAfterCoupon = max($productTotal - $appliedDiscount,0);
    $discountAmount = $hasDiscount ? $productTotalAfterCoupon * $discountRate : 0.0;
    $grandTotal = round($productTotalAfterCoupon - $discountAmount + $deliveryFee,2);
}

// Move client amount validation AFTER all discounts (coupon + subscription) so user pays the displayed discounted total
if(in_array($payment,['gcash','maya'],true)){
    if($client_amount===null || abs($client_amount - $grandTotal) > 0.009){ echo json_encode(['ok'=>false,'error'=>'amount_mismatch','expected'=>$grandTotal]); exit; }
}
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
// Record redemption if coupon applied and within limit
if($appliedPromo){
    $stmtRedeem = mysqli_prepare($conn,'INSERT INTO promotion_redemptions (promo_id, users_id, transactions_id, pr_status, pr_discount_amount, pr_applied_at) VALUES (?,?,?,?,?,NOW())');
    if($stmtRedeem){
        $status='applied'; $discVal=$appliedDiscount; mysqli_stmt_bind_param($stmtRedeem,'iiisd',$appliedPromo['promo_id'],$user_id,$tid,$status,$discVal);
        mysqli_stmt_execute($stmtRedeem); $promoRedemptionId = mysqli_insert_id($conn); mysqli_stmt_close($stmtRedeem);
    }
}
echo json_encode(['ok'=>true,'transaction_id'=>$tid,'total'=>$grandTotal,'delivery_fee'=>$deliveryFee,'discount'=>$discountAmount,'coupon_discount'=>$appliedDiscount,'promo_id'=>$appliedPromo['promo_id']??null,'coupon_code'=>$appliedCouponCode]);
