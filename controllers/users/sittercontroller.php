<?php
// User Sitter Controller: JSON API for creating/updating sitter profiles
// Endpoint examples:
//  - POST /controllers/users/sittercontroller.php?action=save   (create or update based on existing email)

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';

// Helpers
function json_error(int $code, string $msg, array $extra = []) {
	http_response_code($code);
	echo json_encode(['success' => false, 'message' => $msg] + $extra);
	exit;
}
function json_ok(array $data) {
	echo json_encode(['success' => true] + $data);
	exit;
}

	// Ensure new columns are available (idempotent)
	function ensure_sitter_columns(mysqli $conn) {
		@mysqli_query($conn, "ALTER TABLE sitters ADD COLUMN IF NOT EXISTS years_experience INT NULL");
		if (mysqli_errno($conn) === 1064) {
			@mysqli_query($conn, "ALTER TABLE sitters ADD COLUMN years_experience INT NULL");
		}
	}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'save');
$u = get_current_user_session();
$userEmail = $u['users_email'] ?? ($_SESSION['user_email'] ?? '');
$userName = trim(($u['users_firstname'] ?? '') . ' ' . ($u['users_lastname'] ?? '')) ?: ($u['users_username'] ?? ($_SESSION['user_name'] ?? 'Sitter'));

if (!$userEmail) {
	json_error(401, 'Please log in to continue.');
}
if (!isset($connections) || !$connections) {
	json_error(500, 'Database connection not available.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	json_error(405, 'Method not allowed.');
}

// Handle image upload separately
if ($action === 'upload') {
	if (!isset($_FILES['profile_image'])) {
		json_error(400, 'No file uploaded.');
	}

	$file = $_FILES['profile_image'];
	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errMap = [
			UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
			UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
			UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
		];
		$msg = $errMap[$file['error']] ?? ('Upload error code: ' . $file['error']);
		json_error(400, $msg);
	}

	// Validate size (max 5 MB)
	$maxBytes = 5 * 1024 * 1024; // 5MB
	if ($file['size'] > $maxBytes) {
		json_error(400, 'File too large. Maximum size is 5 MB.');
	}

	// Validate mime type
	$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
	$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : mime_content_type($file['tmp_name']);
	if ($finfo) { finfo_close($finfo); }
	$allowed = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/webp' => 'webp'
	];
	if (!isset($allowed[$mime])) {
		json_error(400, 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.');
	}

	// Build destination
	$ext = $allowed[$mime];
	$safeEmail = preg_replace('/[^a-z0-9]+/i', '-', $userEmail);
	$filename = $safeEmail . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
	$uploadDir = dirname(__DIR__, 2) . '/pictures/sitters';
	if (!is_dir($uploadDir)) {
		@mkdir($uploadDir, 0775, true);
	}
	$destPath = $uploadDir . '/' . $filename;

	if (!move_uploaded_file($file['tmp_name'], $destPath)) {
		json_error(500, 'Failed to save uploaded file.');
	}

	// Paths: DB-store relative path, plus web-absolute URL for browser display
	$relativePath = 'pictures/sitters/' . $filename;
	// Compute base prefix like '/Pawhabilin'
	$script = $_SERVER['SCRIPT_NAME'] ?? '';
	$pos = strpos($script, '/controllers/');
	$basePrefix = $pos !== false ? substr($script, 0, $pos) : '';
	$publicUrl = rtrim($basePrefix, '/') . '/' . $relativePath;

	// If sitter exists, update sitters_image_url now; otherwise, just return url
	$updated = false;
	$oldUrl = '';
	if ($stmt = mysqli_prepare($connections, 'SELECT sitters_id, sitters_image_url FROM sitters WHERE sitter_email = ? LIMIT 1')) {
		mysqli_stmt_bind_param($stmt, 's', $userEmail);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $sid, $simg);
		if (mysqli_stmt_fetch($stmt)) {
			$updated = true;
			$oldUrl = (string)$simg;
		}
		mysqli_stmt_close($stmt);
	}

	if ($updated) {
		if ($stmt = mysqli_prepare($connections, 'UPDATE sitters SET sitters_image_url = ? WHERE sitter_email = ?')) {
			mysqli_stmt_bind_param($stmt, 'ss', $relativePath, $userEmail);
			$ok = mysqli_stmt_execute($stmt);
			$err = mysqli_error($connections);
			mysqli_stmt_close($stmt);
			if (!$ok) {
				// Rollback: remove the newly uploaded file to avoid orphan
				if (is_file($destPath)) { @unlink($destPath); }
				json_error(500, 'Failed to update sitter photo.', ['error' => $err]);
			}

			// Cleanup: delete previous image if it exists under pictures/sitters and is different
			if ($oldUrl && $oldUrl !== $relativePath && preg_match('#^pictures/sitters/#', $oldUrl)) {
				$oldPath = dirname(__DIR__, 2) . '/' . ltrim($oldUrl, '/');
				if (is_file($oldPath)) { @unlink($oldPath); }
			}
		} else {
			// Rollback: remove the newly uploaded file if we couldn't prepare update
			if (is_file($destPath)) { @unlink($destPath); }
			json_error(500, 'Failed to prepare database update for photo.');
		}
	}

	json_ok(['message' => 'Image uploaded successfully.', 'url' => $publicUrl, 'path' => $relativePath, 'updated' => $updated]);
}

if ($action !== 'save') {
	json_error(400, 'Unsupported action.');
}

// Gather inputs
$contact   = trim($_POST['sitters_contact'] ?? '');
$specialty = $_POST['sitter_specialty'] ?? [];
if (!is_array($specialty)) { $specialty = []; }
$specialty_str = implode(', ', array_map('trim', $specialty));
$bio_text = trim($_POST['sitters_bio'] ?? ($_POST['description'] ?? ''));
$years_experience = isset($_POST['years_experience']) ? (int)$_POST['years_experience'] : 0;
$image_url = trim($_POST['sitters_image_url'] ?? '');

// Optional extras → bio
$experience_years = trim($_POST['experience_years'] ?? '');
$pet_sizes = $_POST['pet_sizes'] ?? [];
$special_care = $_POST['special_care'] ?? [];
$alt_contact = trim($_POST['alternative_contact'] ?? '');
$contact_method = trim($_POST['contact_method'] ?? '');
$bio_parts = [];
if ($experience_years !== '') { $bio_parts[] = "Years of experience: $experience_years"; }
if (!empty($pet_sizes) && is_array($pet_sizes)) { $bio_parts[] = 'Pet sizes: ' . implode(', ', array_map('trim', $pet_sizes)); }
if (!empty($special_care) && is_array($special_care)) { $bio_parts[] = 'Special care: ' . implode(', ', array_map('trim', $special_care)); }
if ($alt_contact !== '') { $bio_parts[] = "Alt contact: $alt_contact"; }
if ($contact_method !== '') { $bio_parts[] = "Preferred contact: $contact_method"; }
$bio = implode(' | ', $bio_parts);

// Validate
if ($contact === '' || $bio_text === '' || $specialty_str === '') {
	json_error(400, 'Please complete required fields: contact, specialties, and bio.');
}

// Ensure schema has new columns
ensure_sitter_columns($connections);

// Lookup existing sitter by email
$existing = null;
if ($stmt = mysqli_prepare($connections, "SELECT sitters_id FROM sitters WHERE sitter_email = ? LIMIT 1")) {
	mysqli_stmt_bind_param($stmt, 's', $userEmail);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $sid);
	if (mysqli_stmt_fetch($stmt)) {
		$existing = (int)$sid;
	}
	mysqli_stmt_close($stmt);
}

if ($existing) {
	$sql = "UPDATE sitters SET years_experience = ?, sitters_name = ?, sitters_bio = ?, sitter_email = ?, sitters_contact = ?, sitter_specialty = ?, sitter_experience = ?, sitters_image_url = ? WHERE sitters_id = ?";
	if ($stmt = mysqli_prepare($connections, $sql)) {
		$experience_for_compat = $bio_text; // keep older column populated with bio
		mysqli_stmt_bind_param($stmt, 'isssssssi', $years_experience, $userName, $bio_text, $userEmail, $contact, $specialty_str, $experience_for_compat, $image_url, $existing);
		$ok = mysqli_stmt_execute($stmt);
		$err = mysqli_error($connections);
		mysqli_stmt_close($stmt);
		if (!$ok) { json_error(500, 'Failed to update sitter profile.', ['error' => $err]); }
		json_ok(['message' => 'Sitter profile updated successfully!', 'sitter_id' => 'PS' . str_pad((string)$existing, 6, '0', STR_PAD_LEFT)]);
	}
	json_error(500, 'Failed to prepare update statement.');
}

// Insert new
$sql = "INSERT INTO sitters (years_experience, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
if ($stmt = mysqli_prepare($connections, $sql)) {
	$experience_for_compat = $bio_text;
	mysqli_stmt_bind_param($stmt, 'isssssss', $years_experience, $userName, $bio_text, $userEmail, $contact, $specialty_str, $experience_for_compat, $image_url);
	$ok = mysqli_stmt_execute($stmt);
	$new_id = $ok ? mysqli_insert_id($connections) : 0;
	$err = mysqli_error($connections);
	mysqli_stmt_close($stmt);
	if (!$ok) { json_error(500, 'Failed to create sitter profile.', ['error' => $err]); }
	$_SESSION['is_sitter'] = true;
	json_ok(['message' => 'Sitter profile created successfully!', 'sitter_id' => 'PS' . str_pad((string)$new_id, 6, '0', STR_PAD_LEFT)]);
}

json_error(500, 'Failed to prepare insert statement.');
