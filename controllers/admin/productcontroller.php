<?php
require_once __DIR__ . '/../../database.php';

header_remove('X-Powered-By');

function json_response($data, $status = 200) {
	http_response_code($status);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

function ensure_products_dir() {
	$root = realpath(__DIR__ . '/../../');
	$dir = $root . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . 'products';
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	return $dir;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_REQUEST['action'] ?? '';

if ($method === 'POST' && ($action === 'add' || isset($_POST['add_product']))) {
	// Collect and validate inputs
	$name = trim($_POST['products_name'] ?? '');
	$petType = trim($_POST['products_pet_type'] ?? '');
	$description = trim($_POST['products_description'] ?? '');
	$categoryInput = strtolower(trim($_POST['products_category'] ?? ''));
	$price = $_POST['products_price'] ?? '';
	$stock = trim((string)($_POST['products_stock'] ?? ''));
	$active = isset($_POST['products_active']) ? 1 : 0;

	if ($name === '' || $price === '') {
		json_response(['success' => false, 'error' => 'Product name and price are required.'], 400);
	}

	// Map requested categories to DB enum values
	$categoryMap = [
		'food' => 'food',
		'accessories' => 'accessory',
		'grooming' => 'necessity',
		'treats' => 'toy',
		// Also accept enum values directly
		'accessory' => 'accessory',
		'necessity' => 'necessity',
		'toy' => 'toy',
	];
	$category = $categoryMap[$categoryInput] ?? 'food';

	// Handle image upload
	$dbImagePath = null; // path saved in DB e.g., pictures/products/file.jpg
	$webImagePath = null; // path usable from admin.php e.g., ../../pictures/products/file.jpg

	if (!empty($_FILES['products_image']) && is_uploaded_file($_FILES['products_image']['tmp_name'])) {
		$file = $_FILES['products_image'];
		$dir = ensure_products_dir();

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$allowed = ['jpg','jpeg','png'];
		if (!in_array($ext, $allowed, true)) {
			json_response(['success' => false, 'error' => 'Invalid image type. Only JPG/PNG allowed.'], 400);
		}

		// MIME check
		$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
		$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : $file['type'];
		if ($finfo) finfo_close($finfo);
		$allowedMimes = ['image/jpeg','image/png'];
		if (!in_array($mime, $allowedMimes, true)) {
			json_response(['success' => false, 'error' => 'Invalid image content.'], 400);
		}

		$safeBase = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
		$filename = $safeBase . '-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
		$dest = $dir . DIRECTORY_SEPARATOR . $filename;
		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			json_response(['success' => false, 'error' => 'Failed to save uploaded image.'], 500);
		}

		$dbImagePath = 'pictures/products/' . $filename;
		$webImagePath = '../../' . $dbImagePath; // for use from views/admin/admin.php
	}

	// Insert into DB
	global $connections;
	if (!$connections) {
		json_response(['success' => false, 'error' => 'Database connection error.'], 500);
	}

	$sql = "INSERT INTO products (products_name, products_pet_type, products_description, products_category, products_price, products_stock, products_image_url, products_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
	if (!$stmt = mysqli_prepare($connections, $sql)) {
		json_response(['success' => false, 'error' => 'Failed to prepare query.'], 500);
	}
	$priceVal = (float)$price;
	mysqli_stmt_bind_param($stmt, 'ssssdssi', $name, $petType, $description, $category, $priceVal, $stock, $dbImagePath, $active);
	$ok = mysqli_stmt_execute($stmt);
	if (!$ok) {
		$err = mysqli_error($connections);
		mysqli_stmt_close($stmt);
		json_response(['success' => false, 'error' => 'Insert failed: ' . $err], 500);
	}
	$newId = mysqli_insert_id($connections);
	mysqli_stmt_close($stmt);

	// Build user-facing category label mirroring user input capitalization
	$labelMap = [
		'food' => 'Food',
		'accessories' => 'Accessories',
		'grooming' => 'Grooming',
		'treats' => 'Treats',
		'accessory' => 'Accessories',
		'necessity' => 'Grooming',
		'toy' => 'Treats'
	];
	$label = $labelMap[$categoryInput] ?? 'Food';

	json_response([
		'success' => true,
		'item' => [
			'id' => $newId,
			'name' => $name,
			'category' => $label,
			'category_value' => $category,
			'price' => $priceVal,
			'stock' => is_numeric($stock) ? (int)$stock : $stock,
			'stock_int' => is_numeric($stock) ? (int)$stock : 0,
			'status' => $active ? 'active' : 'inactive',
			'active' => $active,
			'pet_type' => $petType,
			'image' => $webImagePath,
			'db_image_url' => $dbImagePath
		]
	]);
}

// Fallback for unsupported requests
json_response(['success' => false, 'error' => 'Unsupported request'], 400);
