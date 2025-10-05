<?php
// Handles add-to-cart POST from shops page
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../database.php';
require_once __DIR__.'/../models/product.php';
// If user not logged in, redirect to login with redirect to authenticated shop (buy_products.php)
if (empty($_SESSION['user'])) {
    // Preserve intended action (product + qty) via query params optionally if desired later
    $target = 'views/users/buy_products';
    // Basic protection: only allow relative internal redirect
    $redir = $target;
    header('Location: ../login?redirect=' . urlencode($redir));
    exit;
}

function redirect_with($params){
    // Preserve existing query parameters (search/category/sort/page) when redirecting back
    $keep = ['q','cat','sort','page'];
    foreach($keep as $k){ if(isset($_GET[$k]) && $_GET[$k] !== '') $params[$k] = $_GET[$k]; }
    $base = '../shops';
    header('Location: '.$base.'?'.http_build_query($params));
    exit;
}

if($_SERVER['REQUEST_METHOD']!=='POST') redirect_with(['err'=>'badid']);

$csrf = $_POST['csrf'] ?? '';
if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$csrf)) redirect_with(['err'=>'csrf']);

$id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
if($id<=0 || $qty<=0) redirect_with(['err'=>'badid']);

$p = product_get_by_id($connections,$id);
if(!$p || !$p['products_active']) redirect_with(['err'=>'notfound']);
$stock = (int)($p['products_stock']??0);
if($stock <=0) redirect_with(['err'=>'out']);
if($qty > $stock) $qty = $stock; // clamp

// Initialize cart in session
if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

// cart item key by product id
if(isset($_SESSION['cart'][$id])){
    $_SESSION['cart'][$id]['qty'] += $qty;
    if($_SESSION['cart'][$id]['qty'] > $stock) $_SESSION['cart'][$id]['qty'] = $stock;
} else {
    $_SESSION['cart'][$id] = [
        'id' => $id,
        'name' => $p['products_name'],
        'price' => (float)$p['products_price'],
        'image' => $p['products_image_url'],
        'qty' => $qty,
        'stock' => $stock
    ];
}

$cartCount = 0; foreach($_SESSION['cart'] as $ci){ $cartCount += (int)$ci['qty']; }
$isAjax = (isset($_POST['ajax']) && $_POST['ajax']=='1') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json'));
if($isAjax){
    header('Content-Type: application/json');
    echo json_encode([
        'ok'=>true,
        'cartCount'=>$cartCount,
        'item'=>$_SESSION['cart'][$id]
    ]);
    exit;
}

redirect_with(['added'=>1]);
