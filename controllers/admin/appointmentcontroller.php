<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';

// Ensure points tables & required columns exist (self-healing migration)
function ensure_points_schema($conn){
    if(!$conn) return;
    // Create tables if missing (matching expected columns)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_points_balance (users_id INT PRIMARY KEY, upb_points INT NOT NULL DEFAULT 0, upb_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // Add missing columns defensively (older installs may lack them)
    // Suppress errors if they already exist
    @mysqli_query($conn, "ALTER TABLE user_points_balance ADD COLUMN upb_points INT NOT NULL DEFAULT 0 AFTER users_id");
    @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_points INT NOT NULL DEFAULT 0 AFTER users_id");
    @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_reason VARCHAR(100) AFTER upl_points");
    @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_source_type VARCHAR(50) AFTER upl_reason");
    @mysqli_query($conn, "ALTER TABLE user_points_ledger ADD COLUMN upl_source_id INT AFTER upl_source_type");
}

function json_out($ok, $payload = []){
    echo json_encode($ok ? array_merge(['success'=>true], $payload) : array_merge(['success'=>false], $payload));
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if (!$action) json_out(false, ['error'=>'No action provided']);

if (!isset($connections) || !$connections) json_out(false, ['error'=>'DB connection missing']);

if ($action === 'get') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) json_out(false, ['error'=>'Invalid id']);
    $sql = "SELECT a.*, aa.aa_notes FROM appointments a LEFT JOIN appointment_address aa ON aa.aa_id=a.aa_id WHERE a.appointments_id=? LIMIT 1";
    if ($stmt = mysqli_prepare($connections, $sql)){
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (!$row) json_out(false, ['error'=>'Not found']);
        $dt = $row['appointments_date'] ?? '';
        $iso = $dt ? str_replace(' ', 'T', $dt) : '';
        $item = [
            'id' => (int)$row['appointments_id'],
            'full_name' => $row['appointments_full_name'] ?? '',
            'email' => $row['appointments_email'] ?? '',
            'phone' => $row['appointments_phone'] ?? '',
            'pet_name' => $row['appointments_pet_name'] ?? '',
            'pet_type' => $row['appointments_pet_type'] ?? '',
            'pet_breed' => $row['appointments_pet_breed'] ?? '',
            'pet_age' => $row['appointments_pet_age_years'] ?? '',
            'type' => $row['appointments_type'] ?? 'pet_sitting',
            'datetime' => $iso,
            'datetime_fmt' => $dt ? date('M d, Y h:i A', strtotime($dt)) : '',
            'status' => $row['appointments_status'] ?? 'pending',
            'notes' => $row['aa_notes'] ?? ''
        ];
        json_out(true, ['item'=>$item]);
    }
    json_out(false, ['error'=>'Query failed']);
}

if ($action === 'award_status') {
    // Return list of appointment IDs that have ledger entries (points awarded)
    $ids = [];
    // Ensure ledger table exists quietly
    @mysqli_query($connections, "CREATE TABLE IF NOT EXISTS user_points_ledger (upl_id INT AUTO_INCREMENT PRIMARY KEY, users_id INT NOT NULL, upl_points INT NOT NULL, upl_reason VARCHAR(100), upl_source_type VARCHAR(50), upl_source_id INT, upl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_source(users_id,upl_source_type,upl_source_id), KEY idx_user(users_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    if ($res = mysqli_query($connections, "SELECT DISTINCT upl_source_id FROM user_points_ledger WHERE upl_source_type='appointment'")) {
        while($r = mysqli_fetch_row($res)) { $ids[] = (int)$r[0]; }
        mysqli_free_result($res);
    }
    json_out(true, ['awarded'=>$ids]);
}

if ($action === 'update') {
    $id = intval($_POST['appointments_id'] ?? 0);
    if ($id <= 0) json_out(false, ['error'=>'Invalid id']);

    // Fetch previous state (users_id + status) to detect status transition
    $prev_status = null; $appt_user_id = null;
    if ($stmt0 = mysqli_prepare($connections, "SELECT users_id, appointments_status FROM appointments WHERE appointments_id=? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt0,'i',$id);
        if (mysqli_stmt_execute($stmt0)) {
            mysqli_stmt_bind_result($stmt0,$uid_prev,$status_prev);
            if (mysqli_stmt_fetch($stmt0)) { $appt_user_id = (int)$uid_prev; $prev_status = $status_prev; }
        }
        mysqli_stmt_close($stmt0);
    }

    $full = trim($_POST['appointments_full_name'] ?? '');
    $email = trim($_POST['appointments_email'] ?? '');
    $phone = trim($_POST['appointments_phone'] ?? '');
    $petName = trim($_POST['appointments_pet_name'] ?? '');
    $petType = trim($_POST['appointments_pet_type'] ?? '');
    $breed = trim($_POST['appointments_pet_breed'] ?? '');
    $age = trim($_POST['appointments_pet_age_years'] ?? '');
    $type = trim($_POST['appointments_type'] ?? 'pet_sitting');
    $dtIso = trim($_POST['appointments_date'] ?? '');
    // Normalize status to lowercase for consistent comparisons (UI may send 'Completed')
    $status = strtolower(trim($_POST['appointments_status'] ?? 'pending'));
    $aa_notes = trim($_POST['aa_notes'] ?? '');

    $dt = $dtIso ? str_replace('T', ' ', $dtIso) : null;

    // Update appointments
    $sql = "UPDATE appointments SET appointments_full_name=?, appointments_email=?, appointments_phone=?, appointments_pet_name=?, appointments_pet_type=?, appointments_pet_breed=?, appointments_pet_age_years=?, appointments_type=?, appointments_date=?, appointments_status=? WHERE appointments_id=?";
    if ($stmt = mysqli_prepare($connections, $sql)){
        mysqli_stmt_bind_param($stmt, 'ssssssssssi', $full, $email, $phone, $petName, $petType, $breed, $age, $type, $dt, $status, $id);
        if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); json_out(false, ['error'=>'Update failed']); }
        mysqli_stmt_close($stmt);
    } else { json_out(false, ['error'=>'Prepare failed']); }

    // Update aa_notes if exists
    $sql2 = "UPDATE appointment_address aa INNER JOIN appointments a ON a.aa_id=aa.aa_id SET aa.aa_notes=? WHERE a.appointments_id=?";
    if ($stmt2 = mysqli_prepare($connections, $sql2)) {
        mysqli_stmt_bind_param($stmt2, 'si', $aa_notes, $id);
        mysqli_stmt_execute($stmt2); // ignore failure if no aa row
        mysqli_stmt_close($stmt2);
    }

    $fmt = $dt ? date('M d, Y h:i A', strtotime($dt)) : '';
    // Status chip html same style as view
    $cls = $status==='confirmed' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : ($status==='completed' ? 'bg-green-100 text-green-800 border border-green-200' : ($status==='cancelled' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-yellow-100 text-yellow-800 border border-yellow-200'));
    $chip = '<span class="px-2 py-1 rounded-full '.$cls.'">'.ucfirst($status).'</span>';

    // Audit: appointment update
    log_admin_action($connections, 'updates', [
        'target' => 'appointment',
        'target_id' => (string)$id,
        'details' => [
            'message' => 'Updated appointment',
            'fields_changed' => ['full_name','email','phone','pet_name','pet_type','pet_breed','pet_age','type','datetime','status','notes']
        ],
        'previous' => null,
        'new' => [
            'full_name' => $full,
            'email' => $email,
            'phone' => $phone,
            'pet_name' => $petName,
            'pet_type' => $petType,
            'pet_breed' => $breed,
            'pet_age' => $age,
            'type' => $type,
            'datetime' => $dtIso,
            'status' => $status,
            'notes' => $aa_notes
        ]
    ]);

    // Points awarding (30) if status transitioned to completed for a subscribed user
    $awarded = false; $award_points = 30; $new_balance = null;
    if ($status === 'completed' && $prev_status !== 'completed' && $appt_user_id) {
        // Check active subscription requirement
        $has_sub = false;
        if ($res_sub = mysqli_query($connections, "SELECT 1 FROM user_subscriptions WHERE users_id=".(int)$appt_user_id." AND us_status='active' AND (us_end_date IS NULL OR us_end_date >= NOW()) LIMIT 1")) {
            if (mysqli_fetch_row($res_sub)) $has_sub = true; mysqli_free_result($res_sub);
        }
        if ($has_sub) {
            // Ensure schema consistent
            ensure_points_schema($connections);
            // Insert ledger entry (IGNORE duplicates to prevent double-award)
            if ($stmtL = mysqli_prepare($connections, "INSERT IGNORE INTO user_points_ledger (users_id,upl_points,upl_reason,upl_source_type,upl_source_id) VALUES (?,?,?,?,?)")) {
                $reason = 'Appointment Completed'; $stype='appointment'; $srcId=$id; $pts=$award_points; $uid_ins=$appt_user_id;
                mysqli_stmt_bind_param($stmtL,'iissi',$uid_ins,$pts,$reason,$stype,$srcId);
                if (mysqli_stmt_execute($stmtL)) {
                    if (mysqli_stmt_affected_rows($stmtL) === 1) {
                        // Upsert balance
                        mysqli_query($connections, "INSERT INTO user_points_balance (users_id, upb_points) VALUES ($uid_ins, $pts) ON DUPLICATE KEY UPDATE upb_points = upb_points + VALUES(upb_points)");
                        // Fetch new balance
                        if ($resB = mysqli_query($connections, "SELECT upb_points FROM user_points_balance WHERE users_id=$uid_ins LIMIT 1")) {
                            if ($rowB = mysqli_fetch_assoc($resB)) { $new_balance = (int)$rowB['upb_points']; $awarded = true; }
                            mysqli_free_result($resB);
                        }
                    } else {
                        // Duplicate (already awarded)
                        if ($resB2 = mysqli_query($connections, "SELECT upb_points FROM user_points_balance WHERE users_id=$uid_ins LIMIT 1")) { if ($rowB2 = mysqli_fetch_assoc($resB2)) { $new_balance = (int)$rowB2['upb_points']; } mysqli_free_result($resB2); }
                    }
                }
                mysqli_stmt_close($stmtL);
            }
        }
    }

    json_out(true, ['item'=>[
        'id'=>$id,
        'full_name'=>$full,
        'email'=>$email,
        'phone'=>$phone,
        'pet_name'=>$petName,
        'pet_type'=>$petType,
        'pet_breed'=>$breed,
        'pet_age'=>$age,
        'type'=>$type,
        'datetime'=>$dtIso,
        'datetime_fmt'=>$fmt,
        'status'=>$status,
        'status_chip_html'=>$chip,
        'notes'=>$aa_notes
    ], 'points_awarded'=>$awarded? $award_points:0, 'new_points_balance'=>$new_balance]);
}

if ($action === 'delete') {
    $id = intval($_POST['appointments_id'] ?? 0);
    if ($id <= 0) json_out(false, ['error'=>'Invalid id']);
    // Optional: delete address row as well if orphaned. For safety, just delete appointment.
    $sql = "DELETE FROM appointments WHERE appointments_id=?";
    if ($stmt = mysqli_prepare($connections, $sql)){
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); json_out(false, ['error'=>'Delete failed']); }
        mysqli_stmt_close($stmt);
        // Audit: appointment deletion
        log_admin_action($connections, 'updates', [
            'target' => 'appointment',
            'target_id' => (string)$id,
            'details' => ['message' => 'Deleted appointment'],
            'previous' => ['appointments_id' => $id],
            'new' => null
        ]);
        json_out(true, ['message'=>'Deleted']);
    }
    json_out(false, ['error'=>'Prepare failed']);
}

json_out(false, ['error'=>'Unknown action']);
