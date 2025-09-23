<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: ../shop.php'); exit; }
$csrf = $_POST['csrf'] ?? '';
if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$csrf)){
    header('Location: ../shop.php?err=csrf'); exit;
}
$id = isset($_POST['product_id'])?(int)$_POST['product_id']:0;
if($id>0 && !empty($_SESSION['cart'][$id])){
    unset($_SESSION['cart'][$id]);
}
if(isset($_SESSION['cart']) && empty($_SESSION['cart'])) unset($_SESSION['cart']);
$cartCount = 0; if(!empty($_SESSION['cart'])){ foreach($_SESSION['cart'] as $ci){ $cartCount += (int)$ci['qty']; } }
$isAjax = (isset($_POST['ajax']) && $_POST['ajax']=='1') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json'));
if($isAjax){
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'cartCount'=>$cartCount]);
    exit;
}
$qs = [];
foreach(['q','cat','sort','page'] as $k){ if(isset($_GET[$k]) && $_GET[$k] !== '') $qs[$k] = $_GET[$k]; }
$qs['removed']=1;
header('Location: ../shop.php?'.http_build_query($qs));
exit;