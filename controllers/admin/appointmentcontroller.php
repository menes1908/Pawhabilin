<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';

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

if ($action === 'update') {
    $id = intval($_POST['appointments_id'] ?? 0);
    if ($id <= 0) json_out(false, ['error'=>'Invalid id']);

    $full = trim($_POST['appointments_full_name'] ?? '');
    $email = trim($_POST['appointments_email'] ?? '');
    $phone = trim($_POST['appointments_phone'] ?? '');
    $petName = trim($_POST['appointments_pet_name'] ?? '');
    $petType = trim($_POST['appointments_pet_type'] ?? '');
    $breed = trim($_POST['appointments_pet_breed'] ?? '');
    $age = trim($_POST['appointments_pet_age_years'] ?? '');
    $type = trim($_POST['appointments_type'] ?? 'pet_sitting');
    $dtIso = trim($_POST['appointments_date'] ?? '');
    $status = trim($_POST['appointments_status'] ?? 'pending');
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
    ]]);
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
