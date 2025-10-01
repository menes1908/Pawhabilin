<?php
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';

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

function category_label_from_value($val) {
	switch ($val) {
		case 'food': return 'Food';
		case 'accessory': return 'Accessories';
		case 'necessity': return 'Grooming';
		case 'toy': return 'Treats';
		default: return $val;
	}
}

function map_input_category_to_enum($input) {
	$input = strtolower(trim((string)$input));
	$map = [
		'food' => 'food',
		'accessories' => 'accessory',
		'grooming' => 'necessity',
		'treats' => 'toy',
		'accessory' => 'accessory',
		'necessity' => 'necessity',
		'toy' => 'toy',
	];
	return $map[$input] ?? 'food';
}

function web_path_from_db($dbPath) {
	if (!$dbPath) return null;
	if (preg_match('/^https?:/i', $dbPath)) return $dbPath;
	if (strpos($dbPath, '../') === 0) return $dbPath;
	if (strpos($dbPath, '/') === 0) return $dbPath;
	return '../../' . ltrim($dbPath, '/');
}

// GET single product details
if ($method === 'GET' && $action === 'get') {
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if ($id <= 0) {
		json_response(['success' => false, 'error' => 'Invalid ID'], 400);
	}
	global $connections;
	if (!$connections) json_response(['success' => false, 'error' => 'Database error'], 500);
	$sql = "SELECT products_id, products_name, products_pet_type, products_description, products_category, products_price, products_stock, products_image_url, products_active FROM products WHERE products_id = ? LIMIT 1";
	if (!$stmt = mysqli_prepare($connections, $sql)) {
		json_response(['success' => false, 'error' => 'Failed to prepare query'], 500);
	}
	mysqli_stmt_bind_param($stmt, 'i', $id);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	$row = $res ? mysqli_fetch_assoc($res) : null;
	mysqli_stmt_close($stmt);
	if (!$row) json_response(['success' => false, 'error' => 'Not found'], 404);

	json_response([
		'success' => true,
		'item' => [
			'id' => (int)$row['products_id'],
			'name' => $row['products_name'],
			'pet_type' => $row['products_pet_type'],
			'description' => $row['products_description'],
			'category_value' => $row['products_category'],
			'category' => category_label_from_value($row['products_category']),
			'price' => (float)$row['products_price'],
			'stock' => $row['products_stock'],
			'stock_int' => is_numeric($row['products_stock']) ? (int)$row['products_stock'] : 0,
			'active' => (int)$row['products_active'] === 1 ? 1 : 0,
			'status' => ((int)$row['products_active'] === 1 ? 'active' : 'inactive'),
			'db_image_url' => $row['products_image_url'],
			'image' => web_path_from_db($row['products_image_url'])
		]
	]);
}

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

	// Audit log: product addition
	log_admin_action($connections, 'additions', [
		'target' => 'product',
		'target_id' => (string)$newId,
		'details' => [
			'message' => 'Added product',
			'fields_changed' => ['name','pet_type','description','category','price','stock','active','image']
		],
		'previous' => null,
		'new' => [
			'id' => $newId,
			'name' => $name,
			'pet_type' => $petType,
			'description' => $description,
			'category' => $category,
			'price' => $priceVal,
			'stock' => $stock,
			'active' => $active,
			'db_image_url' => $dbImagePath
		]
	]);

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

// Update existing product
if ($method === 'POST' && $action === 'update') {
	$id = isset($_POST['products_id']) ? (int)$_POST['products_id'] : 0;
	if ($id <= 0) json_response(['success' => false, 'error' => 'Invalid product ID'], 400);

	$name = trim($_POST['products_name'] ?? '');
	$petType = trim($_POST['products_pet_type'] ?? '');
	$description = trim($_POST['products_description'] ?? '');
	$category = map_input_category_to_enum($_POST['products_category'] ?? '');
	$price = (float)($_POST['products_price'] ?? 0);
	$stock = trim((string)($_POST['products_stock'] ?? ''));
	$active = isset($_POST['products_active']) ? 1 : 0;
	$currentImage = trim($_POST['current_image_url'] ?? '');

	// Fetch previous product state for audit
	$prev = null;
	if ($stmtPrev = mysqli_prepare($connections, "SELECT products_name, products_pet_type, products_description, products_category, products_price, products_stock, products_image_url, products_active FROM products WHERE products_id=? LIMIT 1")) {
		mysqli_stmt_bind_param($stmtPrev, 'i', $id);
		mysqli_stmt_execute($stmtPrev);
		$resPrev = mysqli_stmt_get_result($stmtPrev);
		$prev = $resPrev ? mysqli_fetch_assoc($resPrev) : null;
		mysqli_stmt_close($stmtPrev);
	}

	if ($name === '') json_response(['success' => false, 'error' => 'Product name is required.'], 400);

	// Handle optional new image
	$dbImagePath = $currentImage !== '' ? $currentImage : null;
	$webImagePath = web_path_from_db($dbImagePath);

	$uploadedNew = false;
	if (!empty($_FILES['products_image']) && is_uploaded_file($_FILES['products_image']['tmp_name'])) {
		$file = $_FILES['products_image'];
		$dir = ensure_products_dir();
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$allowed = ['jpg','jpeg','png'];
		if (!in_array($ext, $allowed, true)) json_response(['success' => false, 'error' => 'Invalid image type.'], 400);
		$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
		$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : $file['type'];
		if ($finfo) finfo_close($finfo);
		if (!in_array($mime, ['image/jpeg','image/png'], true)) json_response(['success' => false, 'error' => 'Invalid image content.'], 400);
		$safeBase = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
		$filename = $safeBase . '-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
		$dest = $dir . DIRECTORY_SEPARATOR . $filename;
		if (!move_uploaded_file($file['tmp_name'], $dest)) json_response(['success' => false, 'error' => 'Failed to save uploaded image.'], 500);
		// Delete old if in our products dir
		if ($currentImage && strpos($currentImage, 'pictures/products/') === 0) {
			@unlink(realpath(__DIR__ . '/../../' . $currentImage));
		}
		$dbImagePath = 'pictures/products/' . $filename;
		$webImagePath = '../../' . $dbImagePath;
		$uploadedNew = true;
	}

	global $connections;
	if (!$connections) json_response(['success' => false, 'error' => 'Database connection error.'], 500);

	$sql = "UPDATE products SET products_name=?, products_pet_type=?, products_description=?, products_category=?, products_price=?, products_stock=?, products_image_url=?, products_active=? WHERE products_id=?";
	if (!$stmt = mysqli_prepare($connections, $sql)) {
		json_response(['success' => false, 'error' => 'Failed to prepare update.'], 500);
	}
	mysqli_stmt_bind_param($stmt, 'ssssdssii', $name, $petType, $description, $category, $price, $stock, $dbImagePath, $active, $id);
	$ok = mysqli_stmt_execute($stmt);
	if (!$ok) {
		$err = mysqli_error($connections);
		mysqli_stmt_close($stmt);
		json_response(['success' => false, 'error' => 'Update failed: ' . $err], 500);
	}
	mysqli_stmt_close($stmt);

	// Audit logs for updates
	$prevPrice = $prev ? (float)$prev['products_price'] : null;
	$prevStock = $prev ? $prev['products_stock'] : null;
	$changes = [];
	if ($prev) {
		if ((string)$prev['products_name'] !== (string)$name) $changes[] = 'name';
		if ((string)$prev['products_pet_type'] !== (string)$petType) $changes[] = 'pet_type';
		if ((string)$prev['products_description'] !== (string)$description) $changes[] = 'description';
		if ((string)$prev['products_category'] !== (string)$category) $changes[] = 'category';
		if ((float)$prev['products_price'] !== (float)$price) $changes[] = 'price';
		if ((string)$prev['products_stock'] !== (string)$stock) $changes[] = 'stock';
		if ((string)($prev['products_image_url'] ?? '') !== (string)($dbImagePath ?? '')) $changes[] = 'image';
		if ((int)$prev['products_active'] !== (int)$active) $changes[] = 'active';
	}

	// General update log
	log_admin_action($connections, 'updates', [
		'target' => 'product',
		'target_id' => (string)$id,
		'details' => [
			'message' => 'Updated product',
			'fields_changed' => $changes
		],
		'previous' => $prev,
		'new' => [
			'name' => $name,
			'pet_type' => $petType,
			'description' => $description,
			'category' => $category,
			'price' => $price,
			'stock' => $stock,
			'active' => $active,
			'products_image_url' => $dbImagePath
		]
	]);

	// Specific price and stock change logs
	if ($prev && (float)$prevPrice !== (float)$price) {
		log_admin_action($connections, 'price_changes', [
			'target' => 'product',
			'target_id' => (string)$id,
			'details' => ['message' => 'Price changed'],
			'previous' => ['price' => (float)$prevPrice],
			'new' => ['price' => (float)$price]
		]);
	}
	if ($prev && (string)$prevStock !== (string)$stock) {
		log_admin_action($connections, 'stock_changes', [
			'target' => 'product',
			'target_id' => (string)$id,
			'details' => ['message' => 'Stock changed'],
			'previous' => ['stock' => $prevStock],
			'new' => ['stock' => $stock]
		]);
	}

	json_response([
		'success' => true,
		'item' => [
			'id' => $id,
			'name' => $name,
			'pet_type' => $petType,
			'description' => $description,
			'category_value' => $category,
			'category' => category_label_from_value($category),
			'price' => $price,
			'stock' => is_numeric($stock) ? (int)$stock : $stock,
			'stock_int' => is_numeric($stock) ? (int)$stock : 0,
			'active' => $active,
			'status' => $active ? 'active' : 'inactive',
			'db_image_url' => $dbImagePath,
			'image' => $webImagePath
		]
	]);
}

// Delete product
if ($method === 'POST' && $action === 'delete') {
	$id = isset($_POST['products_id']) ? (int)$_POST['products_id'] : 0;
	if ($id <= 0) json_response(['success' => false, 'error' => 'Invalid product ID'], 400);
	global $connections;
	if (!$connections) json_response(['success' => false, 'error' => 'Database error'], 500);

	// Fetch image path for cleanup
	$img = null;
	if ($stmt = mysqli_prepare($connections, "SELECT products_image_url FROM products WHERE products_id=?")) {
		mysqli_stmt_bind_param($stmt, 'i', $id);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $imgUrl);
		if (mysqli_stmt_fetch($stmt)) { $img = $imgUrl; }
		mysqli_stmt_close($stmt);
	}

	if ($stmt = mysqli_prepare($connections, "DELETE FROM products WHERE products_id=?")) {
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$ok = mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		if (!$ok) json_response(['success' => false, 'error' => 'Delete failed'], 500);
	} else {
		json_response(['success' => false, 'error' => 'Failed to prepare delete'], 500);
	}

	if ($img && strpos($img, 'pictures/products/') === 0) {
		@unlink(realpath(__DIR__ . '/../../' . $img));
	}

	// Audit log: product deletion
	log_admin_action($connections, 'updates', [
		'target' => 'product',
		'target_id' => (string)$id,
		'details' => ['message' => 'Deleted product'],
		'previous' => ['products_image_url' => $img],
		'new' => null
	]);

	json_response(['success' => true]);
}

// Fallback for unsupported requests
json_response(['success' => false, 'error' => 'Unsupported request'], 400);
