<?php
// Returns JSON details for a single product (for Quick View AJAX)
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../database.php';
require_once __DIR__.'/../models/product.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid product id']);
  exit;
}

$p = product_get_by_id($connections, $id);
if (!$p || empty($p['products_active'])) {
  http_response_code(404);
  echo json_encode(['error' => 'Not found']);
  exit;
}

$stock = (int)($p['products_stock'] ?? 0);
$orig  = isset($p['products_original_price']) ? (float)$p['products_original_price'] : null;
$price = (float)$p['products_price'];
$discountPct = ($orig && $orig > $price) ? round((($orig - $price) / $orig) * 100) : 0;

$out = [
  'id' => (int)$p['products_id'],
  'name' => $p['products_name'],
  'pet_type' => $p['products_pet_type'],
  'description' => $p['products_description'],
  'category' => $p['products_category'],
  'price' => $price,
  'originalPrice' => $orig,
  'discountPercent' => $discountPct,
  'stock' => $stock,
  'image' => $p['products_image_url'],
];

echo json_encode($out);
