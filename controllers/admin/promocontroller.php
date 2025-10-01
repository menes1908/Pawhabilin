<?php
// Promo Controller - handles AJAX actions for promotions (add, toggle, delete, list)
// Response: JSON
header('Content-Type: application/json; charset=UTF-8');
session_start();
require_once __DIR__ . '/../../database.php';

if (!isset($connections) || !$connections) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable']);
    exit;
}

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

function send_json($ok, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $message], $extra));
    exit;
}

function generate_code($length = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // exclude ambiguous chars
    $out = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) { $out .= $chars[random_int(0, $max)]; }
    return $out;
}

// Ensure promotions table exists (lightweight guard) - optional
// mysqli_query($connections, "CREATE TABLE IF NOT EXISTS promotions ( ... )"); // intentionally omitted - schema assumed created.

if ($action === 'add') {
    $fields = ['promo_type','promo_code','promo_name','promo_description','promo_discount_type','promo_discount_value','promo_points_cost','promo_min_purchase_amount','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_starts_at','promo_ends_at','promo_active'];
    $d = [];
    foreach ($fields as $f) { $d[$f] = isset($_POST[$f]) ? trim((string)$_POST[$f]) : null; }

    if ($d['promo_name'] === '') {
        send_json(false, 'Promo name is required.');
    }

    // Normalize numerics
    $nullableFloats = ['promo_discount_value','promo_min_purchase_amount'];
    foreach ($nullableFloats as $nf) {
        if ($d[$nf] === '' || $d[$nf] === null) { $d[$nf] = null; }
        elseif (!is_numeric($d[$nf])) { send_json(false, ucfirst(str_replace('_',' ',$nf)).' must be numeric'); }
        else { $d[$nf] = (float)$d[$nf]; }
    }
    $nullableInts = ['promo_points_cost','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_active'];
    foreach ($nullableInts as $ni) {
        if ($d[$ni] === '' || $d[$ni] === null) { $d[$ni] = null; }
        elseif (!ctype_digit($d[$ni]) ) { send_json(false, ucfirst(str_replace('_',' ',$ni)).' must be an integer'); }
        else { $d[$ni] = (int)$d[$ni]; }
    }

    // Date/time normalization (HTML datetime-local => "YYYY-MM-DDTHH:MM")
    foreach (['promo_starts_at','promo_ends_at'] as $dt) {
        if (!empty($d[$dt])) {
            $d[$dt] = str_replace('T', ' ', $d[$dt]);
            // Basic validation
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $d[$dt])) {
                send_json(false, ucfirst(str_replace('_',' ',$dt)).' has invalid format');
            }
            $d[$dt] .= ':00'; // add seconds for consistency
        } else {
            $d[$dt] = null;
        }
    }

    // Auto-generate code if blank
    if ($d['promo_code'] === '' || $d['promo_code'] === null) {
        $attempts = 0; $code = null;
        do {
            $code = generate_code(8);
            $attempts++;
            $safe = mysqli_prepare($connections, 'SELECT promo_id FROM promotions WHERE promo_code = ? LIMIT 1');
            if ($safe) {
                mysqli_stmt_bind_param($safe, 's', $code);
                mysqli_stmt_execute($safe);
                mysqli_stmt_store_result($safe);
                $exists = mysqli_stmt_num_rows($safe) > 0;
                mysqli_stmt_close($safe);
            } else { $exists = false; }
        } while ($exists && $attempts < 5);
        $d['promo_code'] = $code;
    }

    // Basic logical validation: ends after starts
    if ($d['promo_starts_at'] && $d['promo_ends_at'] && strtotime($d['promo_ends_at']) < strtotime($d['promo_starts_at'])) {
        send_json(false, 'End date must be after start date.');
    }

    $sql = 'INSERT INTO promotions (promo_type,promo_code,promo_name,promo_description,promo_discount_type,promo_discount_value,promo_points_cost,promo_min_purchase_amount,promo_usage_limit,promo_per_user_limit,promo_require_active_subscription,promo_starts_at,promo_ends_at,promo_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $stmt = mysqli_prepare($connections, $sql);
    if (!$stmt) {
        send_json(false, 'Prepare failed: '.mysqli_error($connections));
    }

    // Determine bind types
    // sssss (5) then: discount_value (d or null), points(i), min purchase (d), usage(i), per user(i), require(i), starts(s), ends(s), active(i) -> but we simplified by coercing nulls & using appropriate types
    $types = 'sssss';
    $bindValues = [
        $d['promo_type'],
        $d['promo_code'],
        $d['promo_name'],
        $d['promo_description'],
        $d['promo_discount_type']
    ];

    // For remaining we push with correct defaults
    $discount = $d['promo_discount_value'];
    $points = $d['promo_points_cost'];
    $minPurchase = $d['promo_min_purchase_amount'];
    $usage = $d['promo_usage_limit'];
    $perUser = $d['promo_per_user_limit'];
    $reqSub = $d['promo_require_active_subscription'];
    $starts = $d['promo_starts_at'];
    $ends = $d['promo_ends_at'];
    $active = $d['promo_active'] ?? 1;

    // Add types
    $types .= 'd'; $bindValues[] = $discount;              // discount value
    $types .= 'i'; $bindValues[] = $points;                // points cost
    $types .= 'd'; $bindValues[] = $minPurchase;           // min purchase
    $types .= 'i'; $bindValues[] = $usage;                 // usage limit
    $types .= 'i'; $bindValues[] = $perUser;               // per user limit
    $types .= 'i'; $bindValues[] = $reqSub;                // require subscription
    $types .= 's'; $bindValues[] = $starts;                // starts at
    $types .= 's'; $bindValues[] = $ends;                  // ends at
    $types .= 'i'; $bindValues[] = $active;                // active

    // Replace nulls with appropriate default binding (mysqli doesn't accept null with 'd' or 'i' gracefully unless using references & adjusting) -> convert null to null via references
    // We'll cast numeric nulls to 0 for binding, and store actual NULL with conditional SQL? Easier approach: use parameters and rely on values; but MySQL will store 0 not NULL.
    // To truly store NULL, we could build dynamic SQL. For simplicity keep zeros; adjust if required.

    mysqli_stmt_bind_param($stmt, $types, ...$bindValues);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($connections);
        mysqli_stmt_close($stmt);
        send_json(false, 'Insert failed: '.$err);
    }
    mysqli_stmt_close($stmt);

    $id = mysqli_insert_id($connections);
    $promo = null;
    if ($id) {
        $rs = mysqli_query($connections, 'SELECT * FROM promotions WHERE promo_id='.(int)$id.' LIMIT 1');
        if ($rs) { $promo = mysqli_fetch_assoc($rs); mysqli_free_result($rs); }
    }
    send_json(true, 'Promotion added successfully.', ['promo' => $promo]);
}
elseif ($action === 'toggle') {
    $id = isset($_POST['promo_id']) ? (int)$_POST['promo_id'] : 0;
    $to = isset($_POST['to']) && $_POST['to'] == '1' ? 1 : 0;
    if ($id <= 0) send_json(false, 'Invalid promo id');
    $stmt = mysqli_prepare($connections, 'UPDATE promotions SET promo_active=? WHERE promo_id=?');
    if (!$stmt) send_json(false, 'Prepare failed: '.mysqli_error($connections));
    mysqli_stmt_bind_param($stmt, 'ii', $to, $id);
    if (!mysqli_stmt_execute($stmt)) { $err = mysqli_error($connections); mysqli_stmt_close($stmt); send_json(false, 'Update failed: '.$err); }
    mysqli_stmt_close($stmt);
    send_json(true, 'Promo status updated.', ['promo_id' => $id, 'active' => $to]);
}
elseif ($action === 'delete') {
    $id = isset($_POST['promo_id']) ? (int)$_POST['promo_id'] : 0;
    if ($id <= 0) send_json(false, 'Invalid promo id');
    $stmt = mysqli_prepare($connections, 'DELETE FROM promotions WHERE promo_id=?');
    if (!$stmt) send_json(false, 'Prepare failed: '.mysqli_error($connections));
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) { $err = mysqli_error($connections); mysqli_stmt_close($stmt); send_json(false, 'Delete failed: '.$err); }
    mysqli_stmt_close($stmt);
    send_json(true, 'Promo deleted.', ['promo_id' => $id]);
}
elseif ($action === 'list') {
    $rows = [];
    if ($res = mysqli_query($connections, 'SELECT * FROM promotions ORDER BY promo_created_at DESC')) {
        while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
        mysqli_free_result($res);
    }
    send_json(true, 'OK', ['promotions' => $rows]);
}
elseif ($action === 'get') {
    $id = isset($_GET['promo_id']) ? (int)$_GET['promo_id'] : 0;
    if ($id <= 0) send_json(false, 'Invalid promo id');
    $res = mysqli_query($connections, 'SELECT * FROM promotions WHERE promo_id=' . $id . ' LIMIT 1');
    if ($res && $row = mysqli_fetch_assoc($res)) {
        mysqli_free_result($res);
        send_json(true, 'OK', ['promo' => $row]);
    }
    send_json(false, 'Promo not found');
}
elseif ($action === 'update') {
    $id = isset($_POST['promo_id']) ? (int)$_POST['promo_id'] : 0;
    if ($id <= 0) send_json(false, 'Invalid promo id');
    // Fetch existing to ensure exists
    $existing = null;
    if ($res = mysqli_query($connections, 'SELECT * FROM promotions WHERE promo_id=' . $id . ' LIMIT 1')) {
        $existing = mysqli_fetch_assoc($res); mysqli_free_result($res);
    }
    if (!$existing) send_json(false, 'Promo not found');

    $fields = ['promo_type','promo_code','promo_name','promo_description','promo_discount_type','promo_discount_value','promo_points_cost','promo_min_purchase_amount','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_starts_at','promo_ends_at','promo_active'];
    $d = [];
    foreach ($fields as $f) { $d[$f] = isset($_POST[$f]) ? trim((string)$_POST[$f]) : null; }

    if ($d['promo_name'] === '') send_json(false, 'Promo name is required.');

    // Numeric normalization
    $nullableFloats = ['promo_discount_value','promo_min_purchase_amount'];
    foreach ($nullableFloats as $nf) {
        if ($d[$nf] === '' || $d[$nf] === null) { $d[$nf] = null; }
        elseif (!is_numeric($d[$nf])) { send_json(false, ucfirst(str_replace('_',' ',$nf)).' must be numeric'); }
        else { $d[$nf] = (float)$d[$nf]; }
    }
    $nullableInts = ['promo_points_cost','promo_usage_limit','promo_per_user_limit','promo_require_active_subscription','promo_active'];
    foreach ($nullableInts as $ni) {
        if ($d[$ni] === '' || $d[$ni] === null) { $d[$ni] = null; }
        elseif (!ctype_digit($d[$ni])) { send_json(false, ucfirst(str_replace('_',' ',$ni)).' must be an integer'); }
        else { $d[$ni] = (int)$d[$ni]; }
    }

    foreach (['promo_starts_at','promo_ends_at'] as $dt) {
        if (!empty($d[$dt])) {
            $d[$dt] = str_replace('T', ' ', $d[$dt]);
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $d[$dt])) {
                send_json(false, ucfirst(str_replace('_',' ',$dt)).' has invalid format');
            }
            $d[$dt] .= ':00';
        } else { $d[$dt] = null; }
    }
    if ($d['promo_starts_at'] && $d['promo_ends_at'] && strtotime($d['promo_ends_at']) < strtotime($d['promo_starts_at'])) {
        send_json(false, 'End date must be after start date.');
    }

    // Build dynamic update with NULL support
    $setParts = [];
    $params = [];
    $types = '';
    $mapTypes = [
        'promo_type' => 's','promo_code'=>'s','promo_name'=>'s','promo_description'=>'s','promo_discount_type'=>'s',
        'promo_discount_value'=>'d','promo_points_cost'=>'i','promo_min_purchase_amount'=>'d','promo_usage_limit'=>'i','promo_per_user_limit'=>'i','promo_require_active_subscription'=>'i','promo_starts_at'=>'s','promo_ends_at'=>'s','promo_active'=>'i'
    ];
    foreach ($fields as $f) {
        // We'll store NULL as NULL using prepared statement by binding value or using direct NULL in SQL if null
        if ($d[$f] === null) {
            $setParts[] = "$f = NULL";
        } else {
            $setParts[] = "$f = ?";
            $types .= $mapTypes[$f];
            $params[] = $d[$f];
        }
    }
    if (!$setParts) send_json(false, 'Nothing to update');
    $sql = 'UPDATE promotions SET ' . implode(',', $setParts) . ' WHERE promo_id=?';
    $types .= 'i';
    $params[] = $id;
    $stmt = mysqli_prepare($connections, $sql);
    if (!$stmt) send_json(false, 'Prepare failed: '.mysqli_error($connections));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) { $err = mysqli_error($connections); mysqli_stmt_close($stmt); send_json(false, 'Update failed: '.$err); }
    mysqli_stmt_close($stmt);
    $row = null;
    if ($res2 = mysqli_query($connections, 'SELECT * FROM promotions WHERE promo_id=' . $id . ' LIMIT 1')) { $row = mysqli_fetch_assoc($res2); mysqli_free_result($res2); }
    send_json(true, 'Promotion updated.', ['promo' => $row]);
}
else {
    send_json(false, 'Unsupported action');
}
