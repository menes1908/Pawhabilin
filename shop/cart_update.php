<?php
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/product.php';

header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'method']);
    exit;
}
$csrf = $_POST['csrf'] ?? '';
if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$csrf)){
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'csrf']);
    exit;
}
$id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? -1);
if($id<=0 || $qty < 0){
    echo json_encode(['ok'=>false,'error'=>'bad_params']);
    exit;
}
if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
if($qty===0){
    unset($_SESSION['cart'][$id]);
} else {
    $p = product_get_by_id($connections,$id);
    if(!$p || !$p['products_active']){
        unset($_SESSION['cart'][$id]);
        echo json_encode(['ok'=>false,'error'=>'not_found']);
        exit;
    }
    $stock = (int)($p['products_stock']??0);
    if($stock<=0){
        unset($_SESSION['cart'][$id]);
        echo json_encode(['ok'=>false,'error'=>'out']);
        exit;
    }
    if($qty > $stock) $qty = $stock;
    if(isset($_SESSION['cart'][$id])){
        $_SESSION['cart'][$id]['qty'] = $qty;
        $_SESSION['cart'][$id]['stock'] = $stock;
    } else {
        $_SESSION['cart'][$id] = [
            'id'=>$id,
            'name'=>$p['products_name'],
            'price'=>(float)$p['products_price'],
            'image'=>$p['products_image_url'],
            'qty'=>$qty,
            'stock'=>$stock
        ];
    }
}
$cartCount = 0; $subtotal = 0.0;
foreach($_SESSION['cart'] as $ci){
    $cartCount += (int)$ci['qty'];
    $subtotal += ((float)$ci['price'] * (int)$ci['qty']);
}
$responseItem = isset($_SESSION['cart'][$id])?$_SESSION['cart'][$id]:null;
echo json_encode(['ok'=>true,'cartCount'=>$cartCount,'item'=>$responseItem,'subtotal'=>$subtotal]);
