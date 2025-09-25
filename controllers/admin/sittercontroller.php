<?php
require_once __DIR__ . '/../../database.php';

header_remove('X-Powered-By');

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function ensure_sitters_dir() {
    $root = realpath(__DIR__ . '/../../');
    $dir = $root . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . 'sitters';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function web_path_from_db($dbPath) {
    if (!$dbPath) return null;
    if (preg_match('/^https?:/i', $dbPath)) return $dbPath;
    if (strpos($dbPath, '../') === 0) return $dbPath;
    if (strpos($dbPath, '/') === 0) return $dbPath;
    return '../../' . ltrim($dbPath, '/');
}

function delete_local_image_if_applicable($dbPath) {
    if (!$dbPath) return;
    if (preg_match('/^https?:/i', $dbPath)) return;
    $root = realpath(__DIR__ . '/../../');
    $full = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($dbPath, '/'));
    $picturesDir = $root . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . 'sitters' . DIRECTORY_SEPARATOR;
    if (strpos($full, $picturesDir) === 0 && file_exists($full)) @unlink($full);
}

// Ensure new sitter columns exist (idempotent)
function ensure_sitter_columns_admin(mysqli $conn) {
    @mysqli_query($conn, "ALTER TABLE sitters ADD COLUMN IF NOT EXISTS years_experience INT NULL");
    if (mysqli_errno($conn) === 1064) {
        @mysqli_query($conn, "ALTER TABLE sitters ADD COLUMN years_experience INT NULL");
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_REQUEST['action'] ?? '';

// Fetch sitter details
if ($method === 'GET' && $action === 'get') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) json_response(['success' => false, 'error' => 'Invalid sitter id'], 400);
    global $connections;
    if (!$connections) json_response(['success' => false, 'error' => 'Database connection error.'], 500);
    ensure_sitter_columns_admin($connections);
    $sql = "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active, years_experience FROM sitters WHERE sitters_id = ? LIMIT 1";
    if (!$stmt = mysqli_prepare($connections, $sql)) json_response(['success' => false, 'error' => 'Failed to prepare select.'], 500);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$row) json_response(['success' => false, 'error' => 'Sitter not found'], 404);
    $specStr = $row['sitter_specialty'] ?? '';
    $specs = array_values(array_filter(array_map('trim', explode(',', (string)$specStr)), function($v){ return $v !== ''; }));
    json_response([
        'success' => true,
        'item' => [
            'id' => (int)$row['sitters_id'],
            'name' => $row['sitters_name'],
            'bio' => $row['sitters_bio'],
            'email' => $row['sitter_email'],
            'phone' => $row['sitters_contact'],
            'experience' => $row['sitter_experience'],
            'years_experience' => isset($row['years_experience']) ? (int)$row['years_experience'] : 0,
            'specialties' => $specs,
            'specialties_str' => $specStr,
            'active' => (int)$row['sitters_active'],
            'db_image_url' => $row['sitters_image_url'],
            'image' => web_path_from_db($row['sitters_image_url'])
        ]
    ]);
}

if ($method === 'POST' && $action === 'add') {
    $name = trim($_POST['sitters_name'] ?? '');
    $bio = trim($_POST['sitters_bio'] ?? '');
    $email = trim($_POST['sitters_email'] ?? '');
    $phone = trim($_POST['sitters_phone'] ?? '');
    $experience = trim($_POST['sitter_experience'] ?? '');
    $years = isset($_POST['years_experience']) ? intval($_POST['years_experience']) : 0;
    $specialties = $_POST['sitters_specialty'] ?? ($_POST['sitters_specialties'] ?? []);
    if (!is_array($specialties)) $specialties = [];
    $extrasRaw = trim($_POST['sitters_specialties_extra'] ?? '');
    $extras = [];
    if ($extrasRaw !== '') {
        foreach (explode(',', $extrasRaw) as $x) {
            $t = trim($x);
            if ($t !== '') $extras[] = $t;
        }
    }
    $allSpecs = array_values(array_filter(array_map(function($s){ return trim($s); }, array_merge($specialties, $extras)), function($s){ return $s !== ''; }));
    $specStr = implode(', ', $allSpecs);
    $active = isset($_POST['sitters_active']) ? 1 : 0;

    if ($name === '') json_response(['success' => false, 'error' => 'Sitter name is required.'], 400);

    // Handle image upload
    $dbImagePath = null; $webImagePath = null;
    if (!empty($_FILES['sitters_image']) && is_uploaded_file($_FILES['sitters_image']['tmp_name'])) {
        $file = $_FILES['sitters_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if (!in_array($ext, $allowed, true)) json_response(['success' => false, 'error' => 'Invalid image type. Only JPG/PNG allowed.'], 400);
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : $file['type'];
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png'], true)) json_response(['success' => false, 'error' => 'Invalid image content.'], 400);
        $dir = ensure_sitters_dir();
        $safeBase = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = $safeBase . '-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) json_response(['success' => false, 'error' => 'Failed to save uploaded image.'], 500);
        $dbImagePath = 'pictures/sitters/' . $filename;
        $webImagePath = '../../' . $dbImagePath;
    }

    global $connections;
    if (!$connections) json_response(['success' => false, 'error' => 'Database connection error.'], 500);
    ensure_sitter_columns_admin($connections);

    // Keep sitter_experience populated for compatibility with older views
    $experienceToStore = $experience;
    $sql = "INSERT INTO sitters (years_experience, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if (!$stmt = mysqli_prepare($connections, $sql)) json_response(['success' => false, 'error' => 'Failed to prepare insert.'], 500);
    mysqli_stmt_bind_param($stmt, 'isssssssi', $years, $name, $bio, $email, $phone, $specStr, $experienceToStore, $dbImagePath, $active);
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $err = mysqli_error($connections);
        mysqli_stmt_close($stmt);
        json_response(['success' => false, 'error' => 'Insert failed: ' . $err], 500);
    }
    $newId = mysqli_insert_id($connections);
    mysqli_stmt_close($stmt);

    json_response([
        'success' => true,
        'item' => [
            'id' => $newId,
            'name' => $name,
            'bio' => $bio,
            'email' => $email,
            'phone' => $phone,
            'experience' => $experienceToStore,
            'years_experience' => $years,
            'specialties' => $allSpecs,
            'specialties_str' => $specStr,
            'active' => $active,
            'status' => $active ? 'active' : 'inactive',
            'db_image_url' => $dbImagePath,
            'image' => $webImagePath
        ]
    ]);
}

// Update sitter
if ($method === 'POST' && $action === 'update') {
    $id = isset($_POST['sitters_id']) ? intval($_POST['sitters_id']) : 0;
    if ($id <= 0) json_response(['success' => false, 'error' => 'Invalid sitter id.'], 400);

    $name = trim($_POST['sitters_name'] ?? '');
    $bio = trim($_POST['sitters_bio'] ?? '');
    $email = trim($_POST['sitters_email'] ?? '');
    $phone = trim($_POST['sitters_phone'] ?? '');
    $experience = trim($_POST['sitter_experience'] ?? '');
    $years = isset($_POST['years_experience']) ? intval($_POST['years_experience']) : 0;
    $specialties = $_POST['sitters_specialty'] ?? ($_POST['sitters_specialties'] ?? []);
    if (!is_array($specialties)) $specialties = [];
    $extrasRaw = trim($_POST['sitters_specialties_extra'] ?? '');
    $extras = [];
    if ($extrasRaw !== '') {
        foreach (explode(',', $extrasRaw) as $x) { $t = trim($x); if ($t !== '') $extras[] = $t; }
    }
    $allSpecs = array_values(array_filter(array_map('trim', array_merge($specialties, $extras)), function($s){ return $s !== ''; }));
    $specStr = implode(', ', $allSpecs);
    $active = isset($_POST['sitters_active']) ? 1 : 0;
    $removeImage = isset($_POST['remove_image']) ? 1 : 0;

    global $connections;
    if (!$connections) json_response(['success' => false, 'error' => 'Database connection error.'], 500);
    ensure_sitter_columns_admin($connections);

    // Get current image
    $cur = null; $curImg = null;
    if ($stmt = mysqli_prepare($connections, "SELECT sitters_image_url FROM sitters WHERE sitters_id = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $curImg);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    $dbImagePath = $curImg; $webImagePath = web_path_from_db($curImg);
    // Image upload/replace
    if (!empty($_FILES['sitters_image']) && is_uploaded_file($_FILES['sitters_image']['tmp_name'])) {
        $file = $_FILES['sitters_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : $file['type'];
        if ($finfo) finfo_close($finfo);
        if (!in_array($ext, $allowed, true) || !in_array($mime, ['image/jpeg','image/png'], true)) {
            json_response(['success' => false, 'error' => 'Invalid image upload.'], 400);
        }
        $dir = ensure_sitters_dir();
        $safeBase = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = $safeBase . '-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) json_response(['success' => false, 'error' => 'Failed to save uploaded image.'], 500);
        // Delete old local image if applicable
        if ($curImg) delete_local_image_if_applicable($curImg);
        $dbImagePath = 'pictures/sitters/' . $filename;
        $webImagePath = '../../' . $dbImagePath;
    } elseif ($removeImage && $curImg) {
        // Remove image
        delete_local_image_if_applicable($curImg);
        $dbImagePath = null; $webImagePath = null;
    }

    $experienceToStore = $experience;
    $sql = "UPDATE sitters SET years_experience = ?, sitters_name = ?, sitters_bio = ?, sitter_email = ?, sitters_contact = ?, sitter_specialty = ?, sitter_experience = ?, sitters_image_url = ?, sitters_active = ? WHERE sitters_id = ?";
    if (!$stmt = mysqli_prepare($connections, $sql)) json_response(['success' => false, 'error' => 'Failed to prepare update.'], 500);
    mysqli_stmt_bind_param($stmt, 'isssssssii', $years, $name, $bio, $email, $phone, $specStr, $experienceToStore, $dbImagePath, $active, $id);
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) { $err = mysqli_error($connections); mysqli_stmt_close($stmt); json_response(['success' => false, 'error' => 'Update failed: ' . $err], 500); }
    mysqli_stmt_close($stmt);

    json_response([
        'success' => true,
        'item' => [
            'id' => $id,
            'name' => $name,
            'bio' => $bio,
            'email' => $email,
            'phone' => $phone,
            'experience' => $experienceToStore,
            'years_experience' => $years,
            'specialties' => $allSpecs,
            'specialties_str' => $specStr,
            'active' => $active,
            'status' => $active ? 'active' : 'inactive',
            'db_image_url' => $dbImagePath,
            'image' => $webImagePath
        ]
    ]);
}

// Delete sitter
if ($method === 'POST' && $action === 'delete') {
    $id = isset($_POST['sitters_id']) ? intval($_POST['sitters_id']) : 0;
    if ($id <= 0) json_response(['success' => false, 'error' => 'Invalid sitter id.'], 400);
    global $connections;
    if (!$connections) json_response(['success' => false, 'error' => 'Database connection error.'], 500);
    // Get image
    $curImg = null;
    if ($stmt = mysqli_prepare($connections, "SELECT sitters_image_url FROM sitters WHERE sitters_id = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $curImg);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
    // Delete row
    if ($stmt = mysqli_prepare($connections, "DELETE FROM sitters WHERE sitters_id = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if (!$ok) json_response(['success' => false, 'error' => 'Delete failed.'], 500);
    } else {
        json_response(['success' => false, 'error' => 'Failed to prepare delete.'], 500);
    }
    // Remove image file
    if ($curImg) delete_local_image_if_applicable($curImg);
    json_response(['success' => true]);
}

json_response(['success' => false, 'error' => 'Unsupported request'], 400);