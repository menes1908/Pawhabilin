<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../database.php';

session_start_if_needed();
$sessionUser = get_current_user_session();
if (!$sessionUser) {
    $redirect = urlencode('views/users/profile.php');
    header('Location: ../../login.php?redirect=' . $redirect);
    exit();
}

$usersId = (int)($sessionUser['users_id'] ?? 0);
$flashMessage = '';
$flashType = 'success';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update_profile') && $usersId > 0) {
    $newFirst = trim((string)($_POST['firstName'] ?? ''));
    $newLast = trim((string)($_POST['lastName'] ?? ''));
    $newUser = trim((string)($_POST['username'] ?? ''));
    $newEmail = trim((string)($_POST['email'] ?? ''));

    if ($newFirst === '' || $newLast === '' || $newUser === '' || $newEmail === '') {
        $flashMessage = 'Please fill in First name, Last name, Username, and Email.';
        $flashType = 'error';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $flashMessage = 'Please provide a valid email address.';
        $flashType = 'error';
    } elseif (!isset($connections) || !$connections) {
        $flashMessage = 'Database connection is not available.';
        $flashType = 'error';
    } else {
        // Check duplicates for username and email excluding current user
        $dupErr = '';
        if ($stmt = mysqli_prepare($connections, 'SELECT users_id FROM users WHERE (users_username = ? OR users_email = ?) AND users_id <> ? LIMIT 1')) {
            mysqli_stmt_bind_param($stmt, 'ssi', $newUser, $newEmail, $usersId);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            if (mysqli_fetch_assoc($r)) {
                $dupErr = 'Username or Email already in use.';
            }
            mysqli_stmt_close($stmt);
        }

        if ($dupErr !== '') {
            $flashMessage = $dupErr;
            $flashType = 'error';
        } else {
            if ($stmt = mysqli_prepare($connections, 'UPDATE users SET users_firstname = ?, users_lastname = ?, users_username = ?, users_email = ? WHERE users_id = ?')) {
                mysqli_stmt_bind_param($stmt, 'ssssi', $newFirst, $newLast, $newUser, $newEmail, $usersId);
                if (mysqli_stmt_execute($stmt)) {
                    // Update session
                    $_SESSION['user']['users_firstname'] = $newFirst;
                    $_SESSION['user']['users_lastname'] = $newLast;
                    $_SESSION['user']['users_username'] = $newUser;
                    $_SESSION['user']['users_email'] = $newEmail;
                    $flashMessage = 'Profile updated successfully!';
                    $flashType = 'success';
                    // Refresh local variables
                    $sessionUser = $_SESSION['user'];
                } else {
                    $flashMessage = 'Failed to update profile. Please try again.';
                    $flashType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $flashMessage = 'Could not prepare update statement.';
                $flashType = 'error';
            }
        }
    }
}
// Handle pet registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'add_pet') && $usersId > 0) {
    $pname = trim((string)($_POST['pet_name'] ?? ''));
    $pspecies = trim((string)($_POST['pet_species'] ?? ''));
    $pspecies_other = trim((string)($_POST['pet_species_other'] ?? ''));
    $pbreed = trim((string)($_POST['pet_breed'] ?? ''));
    $pgender = trim((string)($_POST['pet_gender'] ?? ''));

    if ($pname === '' || $pspecies === '' || $pgender === '') {
        $flashMessage = 'Please fill in Pet Name, Species, and Gender.';
        $flashType = 'error';
    } else {
        if (strtolower($pspecies) === 'other') {
            if ($pspecies_other === '') {
                $flashMessage = 'Please specify the pet species.';
                $flashType = 'error';
            } else {
                $pspecies = $pspecies_other;
            }
        }
        $pgender = in_array($pgender, ['male','female','unknown'], true) ? $pgender : 'unknown';
        if ($flashType !== 'error') {
            if (isset($connections) && $connections) {
                if ($stmt = mysqli_prepare($connections, 'INSERT INTO pets (users_id, pets_name, pets_species, pets_breed, pets_gender) VALUES (?, ?, ?, ?, ?)')) {
                    mysqli_stmt_bind_param($stmt, 'issss', $usersId, $pname, $pspecies, $pbreed, $pgender);
                    if (mysqli_stmt_execute($stmt)) {
                        $flashMessage = 'Pet added successfully!';
                        $flashType = 'success';
                    } else {
                        $flashMessage = 'Failed to add pet. Please try again.';
                        $flashType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $flashMessage = 'Could not prepare pet insert statement.';
                    $flashType = 'error';
                }
            } else {
                $flashMessage = 'Database connection is not available.';
                $flashType = 'error';
            }
        }
    }
}

// Handle pet edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'edit_pet') && $usersId > 0) {
    $petId = (int)($_POST['pet_id'] ?? 0);
    $pname = trim((string)($_POST['pet_name'] ?? ''));
    $pspecies = trim((string)($_POST['pet_species'] ?? ''));
    $pspecies_other = trim((string)($_POST['pet_species_other'] ?? ''));
    $pbreed = trim((string)($_POST['pet_breed'] ?? ''));
    $pgender = trim((string)($_POST['pet_gender'] ?? ''));

    if ($petId <= 0 || $pname === '' || $pspecies === '' || $pgender === '') {
        $flashMessage = 'Please fill in Pet Name, Species, and Gender.';
        $flashType = 'error';
    } else {
        if (strtolower($pspecies) === 'other') {
            if ($pspecies_other === '') {
                $flashMessage = 'Please specify the pet species.';
                $flashType = 'error';
            } else {
                $pspecies = $pspecies_other;
            }
        }
        $pgender = in_array($pgender, ['male','female','unknown'], true) ? $pgender : 'unknown';
        if ($flashType !== 'error') {
            if (isset($connections) && $connections) {
                if ($stmt = mysqli_prepare($connections, 'UPDATE pets SET pets_name = ?, pets_species = ?, pets_breed = ?, pets_gender = ? WHERE pets_id = ? AND users_id = ?')) {
                    mysqli_stmt_bind_param($stmt, 'ssssii', $pname, $pspecies, $pbreed, $pgender, $petId, $usersId);
                    if (mysqli_stmt_execute($stmt)) {
                        $flashMessage = 'Pet updated successfully!';
                        $flashType = 'success';
                    } else {
                        $flashMessage = 'Failed to update pet. Please try again.';
                        $flashType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $flashMessage = 'Could not prepare pet update statement.';
                    $flashType = 'error';
                }
            } else {
                $flashMessage = 'Database connection is not available.';
                $flashType = 'error';
            }
        }
    }
}

// Handle pet delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'delete_pet') && $usersId > 0) {
    $petId = (int)($_POST['pet_id'] ?? 0);
    if ($petId > 0) {
        if (isset($connections) && $connections) {
            if ($stmt = mysqli_prepare($connections, 'DELETE FROM pets WHERE pets_id = ? AND users_id = ?')) {
                mysqli_stmt_bind_param($stmt, 'ii', $petId, $usersId);
                if (mysqli_stmt_execute($stmt)) {
                    $flashMessage = 'Pet deleted successfully!';
                    $flashType = 'success';
                } else {
                    $flashMessage = 'Failed to delete pet. Please try again.';
                    $flashType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $flashMessage = 'Could not prepare pet delete statement.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'Database connection is not available.';
            $flashType = 'error';
        }
    } else {
        $flashMessage = 'Invalid pet selected for deletion.';
        $flashType = 'error';
    }
}

// Handle appointment cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'cancel_appointment') && $usersId > 0) {
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    if ($apptId > 0) {
        if (isset($connections) && $connections) {
            if ($stmt = mysqli_prepare($connections, "UPDATE appointments SET appointments_status = 'cancelled' WHERE appointments_id = ? AND users_id = ? AND (appointments_status = 'pending' OR appointments_status = 'confirmed')")) {
                mysqli_stmt_bind_param($stmt, 'ii', $apptId, $usersId);
                if (mysqli_stmt_execute($stmt)) {
                    $flashMessage = 'Appointment cancelled successfully!';
                    $flashType = 'success';
                } else {
                    $flashMessage = 'Failed to cancel appointment. Please try again.';
                    $flashType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $flashMessage = 'Could not prepare appointment cancel statement.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'Database connection is not available.';
            $flashType = 'error';
        }
    } else {
        $flashMessage = 'Invalid appointment selected for cancellation.';
        $flashType = 'error';
    }
}

// Handle appointment delete (only if already cancelled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'delete_appointment') && $usersId > 0) {
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    if ($apptId > 0) {
        if (isset($connections) && $connections) {
            mysqli_begin_transaction($connections);
            try {
                if ($stmt = mysqli_prepare($connections, 'DELETE FROM appointment_address WHERE appointments_id = ?')) {
                    mysqli_stmt_bind_param($stmt, 'i', $apptId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                if ($stmt = mysqli_prepare($connections, "DELETE FROM appointments WHERE appointments_id = ? AND users_id = ? AND appointments_status = 'cancelled'")) {
                    mysqli_stmt_bind_param($stmt, 'ii', $apptId, $usersId);
                    mysqli_stmt_execute($stmt);
                    $affected = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);
                    if ($affected <= 0) {
                        throw new Exception('No matching cancelled appointment to delete.');
                    }
                } else {
                    throw new Exception('Could not prepare appointment delete statement.');
                }
                mysqli_commit($connections);
                $flashMessage = 'Appointment deleted successfully!';
                $flashType = 'success';
            } catch (Throwable $ex) {
                mysqli_rollback($connections);
                $flashMessage = 'Failed to delete appointment. Please try again.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'Database connection is not available.';
            $flashType = 'error';
        }
    } else {
        $flashMessage = 'Invalid appointment selected for deletion.';
        $flashType = 'error';
    }
}

$firstName = (string)($sessionUser['users_firstname'] ?? '');
$lastName = (string)($sessionUser['users_lastname'] ?? '');
$username = (string)($sessionUser['users_username'] ?? '');
$email = (string)($sessionUser['users_email'] ?? '');

$memberSince = '';
$subscriptionStatus = 'None';
$subscriptionDate = '';
if (isset($connections) && $connections && $usersId > 0) {
    if ($stmt = mysqli_prepare($connections, 'SELECT users_created_at FROM users WHERE users_id = ? LIMIT 1')) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $createdAt = (string)($row['users_created_at'] ?? '');
            if ($createdAt !== '') {
                $memberSince = substr($createdAt, 0, 10);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Determine subscription (active) and its start date
if (isset($connections) && $connections && $usersId > 0) {
    $sqlSub = "SELECT us.us_status, us.us_start_date FROM user_subscriptions us WHERE us.users_id = ? AND us.us_status = 'active' AND (us.us_end_date IS NULL OR us.us_end_date >= NOW()) ORDER BY us.us_start_date DESC LIMIT 1";
    if ($stmt = mysqli_prepare($connections, $sqlSub)) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $subscriptionStatus = 'Active';
            $subscriptionDate = date('M d, Y', strtotime((string)$row['us_start_date']));
        }
        mysqli_stmt_close($stmt);
    }
}

$displayName = trim(($firstName . ' ' . $lastName)) !== '' ? trim($firstName . ' ' . $lastName) : ($username !== '' ? $username : $email);
$initials = '';
if ($firstName !== '' || $lastName !== '') {
    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
} else {
    $from = $username !== '' ? $username : $email;
    $initials = strtoupper(substr($from, 0, 2));
}
$memberYear = $memberSince !== '' ? date('Y', strtotime($memberSince)) : '';
$userPets = [];
if (isset($connections) && $connections && $usersId > 0) {
    if ($stmt = mysqli_prepare($connections, 'SELECT pets_id, pets_name, pets_species, pets_breed, pets_gender FROM pets WHERE users_id = ? ORDER BY pets_created_at DESC')) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $userPets[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch appointments for this user
$userAppointments = [];
$bookedAppointments = [];
if (isset($connections) && $connections && $usersId > 0) {
    if ($stmt = mysqli_prepare($connections, "SELECT a.appointments_id, a.appointments_type, a.appointments_date, a.appointments_status, p.pets_name FROM appointments a LEFT JOIN pets p ON a.pets_id = p.pets_id WHERE a.users_id = ? ORDER BY a.appointments_date DESC")) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $userAppointments[] = $row;
            $status = (string)($row['appointments_status'] ?? 'pending');
            if (in_array($status, ['pending','confirmed'], true)) {
                $bookedAppointments[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
$userAppointmentsDetailed = [];
if (isset($connections) && $connections && $usersId > 0) {
    $sql = "SELECT a.appointments_id, a.appointments_type, a.appointments_date, a.appointments_status,
                    a.appointments_full_name, a.appointments_email, a.appointments_phone,
                    a.appointments_pet_name, a.appointments_pet_type, a.appointments_pet_breed, a.appointments_pet_age_years,
                    aa.aa_type, aa.aa_address, aa.aa_city, aa.aa_province, aa.aa_postal_code, aa.aa_notes
            FROM appointments a
            LEFT JOIN appointment_address aa ON aa.aa_id = a.aa_id
            WHERE a.users_id = ?
            ORDER BY a.appointments_date DESC, a.appointments_id DESC";
    if ($stmt = mysqli_prepare($connections, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $userAppointmentsDetailed[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
$petsCount = count($userPets);
$bookedCount = count($bookedAppointments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Pawhabilin</title>
    <!-- Tailwind CSS v4.0 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
    /* Custom animations */
    @keyframes float {
            50% { transform: translateY(-15px) rotate(3deg); }
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-2deg); }
            75% { transform: rotate(2deg); }
        }
        
        @keyframes bounce-in {
            0% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.05) rotate(5deg); }
            70% { transform: scale(0.9) rotate(-2deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        @keyframes pet-card-hover {
            0% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-5px) scale(1.02); }
            100% { transform: translateY(-10px) scale(1.03); }
        }
        
        .floating-element {
            animation: float 4s ease-in-out infinite;
        }
        
        .floating-element:nth-child(2) {
            animation-delay: -1.5s;
        }
        
        .floating-element:nth-child(3) {
            animation-delay: -3s;
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        .slide-in-up {
            animation: slide-in-up 0.6s ease-out forwards;
        }
        
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradient-shift 6s ease infinite;
        }
        
        .profile-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.8));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .pet-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(15px);
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .pet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .pet-card:hover::before {
            left: 100%;
        }
        
        .pet-card:hover {
            animation: pet-card-hover 0.6s ease-out forwards;
            border-color: rgba(249, 115, 22, 0.3);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.2);
        }
        
        .pet-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f97316, #fb923c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
            margin: 0 auto 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pet-card:hover .pet-avatar {
            transform: scale(1.1) rotate(5deg);
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f97316, #fb923c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 36px;
            margin: 0 auto 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.3);
        }
        
        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(249, 115, 22, 0.4);
        }
        
        .profile-picture::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: linear-gradient(45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 300% 300%;
            animation: gradient-shift 3s ease infinite;
            z-index: -1;
        }
        
        .add-pet-card {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px dashed #f59e0b;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 280px;
        }
        
        .add-pet-card:hover {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-color: #d97706;
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 30px rgba(251, 191, 36, 0.3);
        }
        
        .add-pet-card:hover .add-icon {
            transform: scale(1.2) rotate(90deg);
            color: white;
        }
        
        .add-pet-card:hover .add-text {
            color: white;
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .input-field {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(249, 115, 22, 0.2);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316, #fb923c);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ea580c, #f97316);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
        }
        
        .pet-type-icon {
            transition: all 0.3s ease;
        }
        
        .pet-card:hover .pet-type-icon {
            animation: wiggle 0.6s ease-in-out;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: currentColor;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .action-btn:hover::before {
            opacity: 0.1;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #f97316, #fb923c);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #ea580c, #f97316);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .pet-card {
                margin-bottom: 20px;
            }
            
            .profile-picture {
                width: 100px;
                height: 100px;
                font-size: 30px;
            }
            
            .pet-avatar {
                width: 60px;
                height: 60px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
    <!-- Header (shared include) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

    <!-- Floating background elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="floating-element absolute top-20 left-10 opacity-20">
            <i data-lucide="paw-print" class="w-16 h-16 text-orange-300 transform rotate-12"></i>
        </div>
        <div class="floating-element absolute top-40 right-20 opacity-20">
            <i data-lucide="heart" class="w-12 h-12 text-amber-300 transform -rotate-12"></i>
        </div>
        <div class="floating-element absolute bottom-40 left-16 opacity-20">
            <i data-lucide="star" class="w-14 h-14 text-orange-200 transform rotate-45"></i>
        </div>
    </div>

    <!-- Main Content -->
    <main class="relative z-10 py-8">
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Page Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-6 py-2 text-sm font-medium text-orange-600 mb-4">
                    <i data-lucide="user-circle" class="w-4 h-4 mr-2"></i>
                    My Profile
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    Welcome back,
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent brand-font">
                        <?php echo htmlspecialchars($displayName); ?>
                    </span>
                </h1>
                <p class="text-xl text-gray-700 max-w-2xl mx-auto">
                    Manage your profile information and take care of your beloved pets
                </p>
            </div>

            <?php if ($flashMessage !== ''): ?>
                <div class="max-w-2xl mx-auto mb-6">
                    <div class="rounded-lg px-4 py-3 <?php echo $flashType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                        <?php echo htmlspecialchars($flashMessage); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="stats-card rounded-2xl p-6 cursor-pointer" onclick="openStats('pets')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Total Pets</p>
                            <p class="text-3xl font-bold text-orange-600"><?php echo (int)$petsCount; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <i data-lucide="paw-print" class="w-6 h-6 text-orange-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card rounded-2xl p-6 cursor-pointer" onclick="openStats('appointments')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Appointments</p>
                            <p id="appointmentsCountNum" class="text-3xl font-bold text-blue-600"><?php echo (int)$bookedCount; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Member Since</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo htmlspecialchars($memberYear !== '' ? $memberYear : ''); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Profile Information -->
                <div class="lg:col-span-1">
                    <div class="profile-card rounded-3xl p-8">
                        <h2 class="text-2xl font-bold text-center mb-6 flex items-center justify-center gap-3">
                            <i data-lucide="user" class="w-6 h-6 text-orange-600"></i>
                            Profile Information
                        </h2>
                        
                        <!-- Profile Picture -->
                        <div class="text-center mb-8">
                            <div class="profile-picture" onclick="changeProfilePicture()" title="Click to change profile picture">
                                <span id="profile-initials"><?php echo htmlspecialchars($initials); ?></span>
                                <input type="file" id="profile-pic-input" accept="image/*" style="display: none;" onchange="handleProfilePicChange(event)">
                            </div>
                            <button onclick="changeProfilePicture()" class="text-orange-600 hover:text-orange-700 font-medium text-sm flex items-center gap-2 mx-auto transition-colors duration-300">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                                Change Photo
                            </button>
                        </div>
                        
                        <!-- Profile Details -->
                        <form id="profileForm" method="post" class="space-y-6">
                            <input type="hidden" name="action" value="update_profile" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>
                                        First Name
                                    </label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" class="input-field w-full px-4 py-3 rounded-lg outline-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>
                                        Last Name
                                    </label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" class="input-field w-full px-4 py-3 rounded-lg outline-none" readonly>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="at-sign" class="w-4 h-4 inline mr-2"></i>
                                    Username
                                </label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" class="input-field w-full px-4 py-3 rounded-lg outline-none" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="mail" class="w-4 h-4 inline mr-2"></i>
                                    Email Address
                                </label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="input-field w-full px-4 py-3 rounded-lg outline-none" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="star" class="w-4 h-4 inline mr-2"></i>
                                    Subscription
                                </label>
                                <div class="w-full px-4 py-3 rounded-lg bg-white/70 border border-orange-200 text-sm flex items-center justify-between">
                                    <span><?php echo htmlspecialchars($subscriptionStatus, ENT_QUOTES, 'UTF-8'); ?><?php if($subscriptionStatus==='Active' && $subscriptionDate!==''): ?> — since <?php echo htmlspecialchars($subscriptionDate, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></span>
                                    <?php if($subscriptionStatus==='None'): ?>
                                        <a href="subscriptions.php" class="text-orange-600 hover:underline text-xs font-medium">Upgrade</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="w-4 h-4 inline mr-2"></i>
                                    Member Since
                                </label>
                                <input type="text" id="memberSince" value="<?php echo htmlspecialchars($memberSince); ?>" class="input-field w-full px-4 py-3 rounded-lg outline-none" readonly>
                            </div>
                            
                            <button onclick="editProfile()" id="editProfileBtn" class="btn-primary w-full py-3 px-6 rounded-lg font-semibold flex items-center justify-center gap-3" type="button">
                                <i data-lucide="edit" class="w-5 h-5"></i>
                                Edit Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Pet Management -->
                <div class="lg:col-span-2">
                    <div class="profile-card rounded-3xl p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-2xl font-bold flex items-center gap-3">
                                <i data-lucide="paw-print" class="w-6 h-6 text-orange-600"></i>
                                My Pets
                            </h2>
                            <button onclick="showAddPetModal()" class="btn-primary py-2 px-4 rounded-lg font-semibold flex items-center gap-2 text-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add Pet
                            </button>
                        </div>
                        
                        <!-- Pets Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="petsGrid">
                            <?php if (count($userPets) === 0): ?>
                                <div class="col-span-1 md:col-span-2 text-center text-gray-600">No pets registered yet. Add one!</div>
                            <?php else: ?>
                                <?php foreach ($userPets as $pet): ?>
                                    <?php
                                        $species = strtolower((string)($pet['pets_species'] ?? ''));
                                        $icon = 'paw-print';
                                        if (strpos($species, 'dog') !== false) $icon = 'dog';
                                        elseif (strpos($species, 'cat') !== false) $icon = 'cat';
                                        elseif (strpos($species, 'bird') !== false) $icon = 'bird';
                                        elseif (strpos($species, 'fish') !== false) $icon = 'fish';
                                    ?>
                                    <div class="pet-card rounded-2xl p-6 relative" data-id="<?php echo (int)($pet['pets_id'] ?? 0); ?>" data-name="<?php echo htmlspecialchars($pet['pets_name'] ?? '', ENT_QUOTES); ?>" data-species="<?php echo htmlspecialchars($pet['pets_species'] ?? '', ENT_QUOTES); ?>" data-breed="<?php echo htmlspecialchars($pet['pets_breed'] ?? '', ENT_QUOTES); ?>" data-gender="<?php echo htmlspecialchars((string)($pet['pets_gender'] ?? 'unknown'), ENT_QUOTES); ?>">
                                        <div class="absolute top-4 right-4 flex gap-2">
                                            <button class="action-btn text-blue-600 hover:text-blue-700" title="Edit" type="button" onclick="openEditPet(this)">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <button class="action-btn text-red-600 hover:text-red-700" title="Delete" type="button" onclick="confirmDeletePet(this)">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        <div class="pet-avatar">
                                            <i data-lucide="<?php echo $icon; ?>" class="w-8 h-8 pet-type-icon"></i>
                                        </div>
                                        <div class="text-center">
                                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($pet['pets_name'] ?? ''); ?></h3>
                                            <p class="text-orange-600 font-medium mb-1"><?php echo htmlspecialchars($pet['pets_breed'] ?? ''); ?></p>
                                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(ucfirst($species)); ?> • <?php echo htmlspecialchars(ucfirst((string)($pet['pets_gender'] ?? 'unknown'))); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Add New Pet Card -->
                            <div class="add-pet-card rounded-2xl" onclick="showAddPetModal()">
                                <div class="text-center">
                                    <i data-lucide="plus" class="w-12 h-12 text-amber-600 mb-4 add-icon transition-all duration-300"></i>
                                    <h3 class="text-xl font-bold text-amber-700 add-text transition-colors duration-300">Add New Pet</h3>
                                    <p class="text-amber-600 mt-2 add-text transition-colors duration-300">Register a new family member</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Pet Modal -->
    <div id="petModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0" onclick="closePetModal()"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content relative w-full max-w-md rounded-3xl p-8">
                <button onclick="closePetModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors duration-300">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="paw-print" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">Add New Pet</h3>
                    <p class="text-gray-600 mt-2">Fill in your pet's information</p>
                </div>
                
                <form id="petForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" id="petFormAction" value="add_pet" />
                    <input type="hidden" name="pet_id" id="petIdField" value="" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
                        <input type="text" id="petName" name="pet_name" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Enter pet name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Species *</label>
                        <select id="petType" name="pet_species" required class="input-field w-full px-4 py-3 rounded-lg outline-none" onchange="toggleSpeciesOther(this.value)">
                            <option value="">Select pet species</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Other">Other (specify)</option>
                        </select>
                    </div>
                    <div id="speciesOtherWrapper" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specify Species *</label>
                        <input type="text" id="petSpeciesOther" name="pet_species_other" class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="e.g., Rabbit">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Breed</label>
                        <input type="text" id="petBreed" name="pet_breed" class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Enter breed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Gender *</label>
                        <select id="petGender" name="pet_gender" required class="input-field w-full px-4 py-3 rounded-lg outline-none">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="unknown">Unknown</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closePetModal()" class="flex-1 py-3 px-6 rounded-lg font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-300">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary flex-1 py-3 px-6 rounded-lg font-semibold">
                            <span id="submitBtnText">Add Pet</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Pet Form -->
    <form id="deletePetForm" method="post" class="hidden">
        <input type="hidden" name="action" value="delete_pet" />
        <input type="hidden" name="pet_id" id="deletePetId" value="" />
    </form>

    <!-- Hidden Cancel Appointment Form -->
    <form id="cancelApptForm" method="post" class="hidden">
        <input type="hidden" name="action" value="cancel_appointment" />
        <input type="hidden" name="appointment_id" id="cancelApptId" value="" />
    </form>

    <!-- Hidden Delete Appointment Form -->
    <form id="deleteApptForm" method="post" class="hidden">
        <input type="hidden" name="action" value="delete_appointment" />
        <input type="hidden" name="appointment_id" id="deleteApptId" value="" />
    </form>

    <!-- Stats Modal -->
    <div id="statsModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0" onclick="closeStats()"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content relative w-full max-w-4xl rounded-3xl p-8">
                <button onclick="closeStats()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors duration-300">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i id="statsIcon" data-lucide="list" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800" id="statsTitle">Details</h3>
                    <p class="text-gray-600 mt-2" id="statsSubtitle"></p>
                </div>
                <div id="statsContent" class="space-y-4 max-h-[60vh] overflow-y-auto">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-12 bg-gray-900 text-white relative z-10">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg overflow-hidden">
                            <img src="../../pictures/Pawhabilin logo.png" alt="pawhabilin Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="text-xl font-semibold brand-font">pawhabilin</span>
                    </div>
                    <p class="text-gray-400">
                        The Philippines' most trusted pet care platform providing comprehensive services for your beloved pets.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Account</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="profile.php" class="hover:text-white transition-colors">My Profile</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Subscription</a></li>
                        <li><a href="book_appointment.php" class="hover:text-white transition-colors">Appointments</a></li>
                        <li><a href="buy_products.php" class="hover:text-white transition-colors">Shop</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Services</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Veterinary Care</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Grooming</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Sitting</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Emergency Care</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            +63 912 345 6789
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            hello@pawhabilin.com
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                            Cebu City, Philippines
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2025 pawhabilin Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Add slide-in animation to cards
            const cards = document.querySelectorAll('.profile-card, .pet-card, .stats-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('slide-in-up');
                }, index * 100);
            });

            // Seed global appointments data and update real-time count
            window.userApptsData = <?php echo json_encode($userAppointmentsDetailed ?? []); ?>;
            const numEl = document.getElementById('appointmentsCountNum');
            if (numEl && Array.isArray(window.userApptsData)) {
                const count = window.userApptsData.filter(a => ['pending','confirmed'].includes(String(a.appointments_status||''))).length;
                numEl.textContent = String(count);
            }
        });

        // Global variables
        let editingPetId = null;
        let isEditingProfile = false;

        // Profile picture functions
        function changeProfilePicture() {
            document.getElementById('profile-pic-input').click();
        }

        function handleProfilePicChange(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePic = document.querySelector('.profile-picture');
                    profilePic.style.backgroundImage = `url(${e.target.result})`;
                    profilePic.style.backgroundSize = 'cover';
                    profilePic.style.backgroundPosition = 'center';
                    profilePic.innerHTML = ''; // Remove initials
                };
                reader.readAsDataURL(file);
            }
        }

        // Profile editing functions
        function editProfile() {
            const inputs = ['firstName', 'lastName', 'username', 'email'];
            const editBtn = document.getElementById('editProfileBtn');
            
            if (!isEditingProfile) {
                // Enable editing
                inputs.forEach(id => {
                    document.getElementById(id).readOnly = false;
                    document.getElementById(id).classList.add('border-orange-300');
                });
                editBtn.innerHTML = '<i data-lucide="save" class="w-5 h-5"></i> Save Changes';
                isEditingProfile = true;
                lucide.createIcons();
            } else {
                // Submit form to update on server
                document.getElementById('profileForm').submit();
            }
        }

        // Pet modal functions
        function showAddPetModal() {
            editingPetId = null;
            document.getElementById('modalTitle').textContent = 'Add New Pet';
            document.getElementById('submitBtnText').textContent = 'Add Pet';
            const form = document.getElementById('petForm');
            form.reset();
            document.getElementById('petFormAction').value = 'add_pet';
            document.getElementById('petIdField').value = '';
            toggleSpeciesOther('');
            document.getElementById('petModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePetModal() {
            document.getElementById('petModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            editingPetId = null;
        }

        function openEditPet(btn) {
            const card = btn.closest('.pet-card');
            if (!card) return;
            const petId = card.getAttribute('data-id');
            const name = card.getAttribute('data-name') || '';
            const species = card.getAttribute('data-species') || '';
            const breed = card.getAttribute('data-breed') || '';
            const gender = card.getAttribute('data-gender') || 'unknown';

            editingPetId = petId;
            document.getElementById('modalTitle').textContent = 'Edit Pet';
            document.getElementById('submitBtnText').textContent = 'Save Changes';

            document.getElementById('petName').value = name;

            const petTypeSelect = document.getElementById('petType');
            const normalized = species.trim().toLowerCase();
            // If species isn't one of preset options, set to Other and show text
            const preset = ['dog','cat','bird','fish'];
            if (preset.includes(normalized)) {
                petTypeSelect.value = normalized.charAt(0).toUpperCase() + normalized.slice(1);
                toggleSpeciesOther(petTypeSelect.value);
                document.getElementById('petSpeciesOther').value = '';
            } else {
                petTypeSelect.value = 'Other';
                toggleSpeciesOther('Other');
                document.getElementById('petSpeciesOther').value = species;
            }

            document.getElementById('petBreed').value = breed;
            document.getElementById('petGender').value = gender;

            document.getElementById('petFormAction').value = 'edit_pet';
            document.getElementById('petIdField').value = petId;

            document.getElementById('petModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function confirmDeletePet(btn) {
            const card = btn.closest('.pet-card');
            if (!card) return;
            const petId = card.getAttribute('data-id');
            const name = card.getAttribute('data-name') || 'this pet';
            if (!petId) return;
            if (confirm(`Delete ${name}? This cannot be undone.`)) {
                const form = document.getElementById('deletePetForm');
                document.getElementById('deletePetId').value = petId;
                form.submit();
            }
        }

        // Form submission
        function toggleSpeciesOther(val) {
            const wrap = document.getElementById('speciesOtherWrapper');
            if (!wrap) return;
            if (val.toLowerCase() === 'other') {
                wrap.classList.remove('hidden');
                document.getElementById('petSpeciesOther').required = true;
            } else {
                wrap.classList.add('hidden');
                document.getElementById('petSpeciesOther').required = false;
                document.getElementById('petSpeciesOther').value = '';
            }
        }

        // Notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}" class="w-5 h-5"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            lucide.createIcons();
            
            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePetModal();
            }
        });

        // Stats modal functions
        function openStats(which) {
            const modal = document.getElementById('statsModal');
            const title = document.getElementById('statsTitle');
            const subtitle = document.getElementById('statsSubtitle');
            const iconEl = document.getElementById('statsIcon');
            const content = document.getElementById('statsContent');
            content.innerHTML = '';

            if (which === 'pets') {
                title.textContent = 'Registered Pets';
                subtitle.textContent = '<?php echo (int)$petsCount; ?> total';
                iconEl.setAttribute('data-lucide', 'paw-print');
                const pets = <?php echo json_encode($userPets ?? []); ?>;
                if (!pets || pets.length === 0) {
                    content.innerHTML = '<div class="text-center text-gray-600">No pets registered.</div>';
                } else {
                    pets.forEach(p => {
                        const sp = (p.pets_species || '').toString();
                        const breed = (p.pets_breed || '').toString();
                        const gender = (p.pets_gender || '').toString();
                        const row = document.createElement('div');
                        row.className = 'rounded-xl border p-4 flex items-center justify-between';
                        row.innerHTML = `
                            <div>
                                <div class="font-semibold text-gray-800">${escapeHtml(p.pets_name || '')}</div>
                                <div class="text-sm text-gray-600">${escapeHtml(sp)}${breed ? ' • ' + escapeHtml(breed) : ''}${gender ? ' • ' + escapeHtml(gender) : ''}</div>
                            </div>
                            <div class="flex gap-2">
                                <button class="action-btn text-blue-600 hover:text-blue-700" title="Edit" type="button" onclick="openEditPetFromList(${Number(p.pets_id)})"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <button class="action-btn text-red-600 hover:text-red-700" title="Delete" type="button" onclick="deleteFromList(${Number(p.pets_id)})"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>`;
                        content.appendChild(row);
                    });
                }
            } else if (which === 'appointments') {
                title.textContent = 'Appointments';
                subtitle.textContent = 'Your full appointment history with filters';
                iconEl.setAttribute('data-lucide', 'calendar');
                const appts = (window.userApptsData && Array.isArray(window.userApptsData)) ? window.userApptsData : <?php echo json_encode($userAppointmentsDetailed ?? []); ?>;
                window.userApptsData = appts;
                const wrapper = document.createElement('div');
                wrapper.className = 'space-y-4';

                const controls = document.createElement('div');
                controls.className = 'flex flex-col gap-3';
                controls.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-lg font-semibold">All Appointments</h4>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                            <input id="userApptSearch" type="text" placeholder="Search..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-80" />
                        </div>
                    </div>
                    <div id="userApptTabs" class="flex items-center gap-2">
                        <button data-appt-filter="all" class="u-appt-tab px-3 py-1.5 rounded-full border text-sm bg-gray-900 text-white border-gray-900">All</button>
                        <button data-appt-filter="pet_sitting" class="u-appt-tab px-3 py-1.5 rounded-full border text-sm border-orange-300 text-orange-700 bg-orange-50">Pet Sitting</button>
                        <button data-appt-filter="grooming" class="u-appt-tab px-3 py-1.5 rounded-full border text-sm border-blue-300 text-blue-700 bg-blue-50">Grooming</button>
                        <button data-appt-filter="vet" class="u-appt-tab px-3 py-1.5 rounded-full border text-sm border-green-300 text-green-700 bg-green-50">Veterinary</button>
                    </div>`;

                wrapper.appendChild(controls);

                const tableWrap = document.createElement('div');
                tableWrap.className = 'overflow-x-auto';
                tableWrap.innerHTML = `
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sitting Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody id="userApptsBody" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>`;
                wrapper.appendChild(tableWrap);

                content.appendChild(wrapper);

                function statusChip(st){
                    const s = String(st||'');
                    if (s==='pending') return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                    if (s==='confirmed') return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Confirmed</span>';
                    if (s==='cancelled') return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Cancelled</span>';
                    if (s==='completed') return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Completed</span>';
                    return `<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">${escapeHtml(s)}</span>`;
                }

                function typeLabel(t){
                    const tt = String(t||'');
                    if (tt==='pet_sitting') return 'Pet Sitting';
                    if (tt==='grooming') return 'Grooming';
                    if (tt==='vet') return 'Veterinary';
                    return escapeHtml(tt.replace('_',' '));
                }

                function sittingTypeLabel(aa){
                    const mode = (aa && aa.aa_type) ? String(aa.aa_type) : '';
                    if (!mode) return '-';
                    return mode === 'home-sitting' ? 'Home-sitting' : (mode === 'drop_off' ? 'Drop Off' : mode);
                }

                function fullAddress(aa){
                    const mode = (aa && aa.aa_type) ? String(aa.aa_type) : '';
                    if (mode !== 'home-sitting') return '-';
                    const parts = [aa.aa_address, aa.aa_city, aa.aa_province, aa.aa_postal_code]
                        .map(v => (v||'').trim())
                        .filter(Boolean);
                    return parts.length ? parts.join(', ') : '-';
                }

                function renderRows(list){
                    const tbody = document.getElementById('userApptsBody');
                    tbody.innerHTML = '';
                    if (!list || list.length===0){
                        const tr = document.createElement('tr');
                        const td = document.createElement('td');
                        td.colSpan = 8;
                        td.className = 'px-4 py-6 text-center text-gray-600';
                        td.textContent = 'No appointments found.';
                        tr.appendChild(td);
                        tbody.appendChild(tr);
                        return;
                    }
                    list.forEach(a=>{
                        const d = a.appointments_date ? new Date(String(a.appointments_date).replace(' ','T')) : null;
                        const nice = d ? d.toLocaleString() : '';
                        const aa = {
                            aa_type: a.aa_type || '',
                            aa_address: a.aa_address || '',
                            aa_city: a.aa_city || '',
                            aa_province: a.aa_province || '',
                            aa_postal_code: a.aa_postal_code || '',
                            aa_notes: a.aa_notes || ''
                        };
                        const notes = aa.aa_notes || '';
                        const canCancel = ['pending','confirmed'].includes(String(a.appointments_status||''));
                        const canDelete = String(a.appointments_status||'') === 'cancelled';
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-type', String(a.appointments_type||''));
                        tr.setAttribute('data-search', `${(a.appointments_pet_name||'')} ${(a.appointments_pet_type||'')} ${(a.appointments_pet_breed||'')} ${(a.appointments_type||'')} ${nice} ${notes} ${(a.appointments_status||'')}`.toLowerCase());
                        tr.innerHTML = `
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">${escapeHtml(a.appointments_pet_name || '')}</div>
                                <div class="text-sm text-gray-600">${escapeHtml(a.appointments_pet_type || '')}${a.appointments_pet_breed? ' • '+escapeHtml(a.appointments_pet_breed):''}${a.appointments_pet_age_years? ' • '+escapeHtml(String(a.appointments_pet_age_years)+'y'):''}</div>
                            </td>
                            <td class="px-4 py-3">${typeLabel(a.appointments_type)}</td>
                            <td class="px-4 py-3">${a.appointments_type==='pet_sitting' ? escapeHtml(sittingTypeLabel(aa)) : '-'}</td>
                            <td class="px-4 py-3">${a.appointments_type==='pet_sitting' ? escapeHtml(fullAddress(aa)) : '-'}</td>
                            <td class="px-4 py-3">${escapeHtml(nice)}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(notes)}</td>
                            <td class="px-4 py-3">${statusChip(a.appointments_status)}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                ${canCancel ? `<button class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50" onclick="confirmCancelAppt(${Number(a.appointments_id)})">Cancel</button>` : ''}
                                ${canDelete ? `<button class=\"px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50\" onclick=\"confirmDeleteAppt(${Number(a.appointments_id)})\">Delete</button>` : ''}
                            </td>`;
                        tbody.appendChild(tr);
                    });
                }

                // Initial render
                renderRows(appts);
                if (window.lucide && lucide.createIcons) lucide.createIcons();

                // Filters
                let currentFilter = 'all';
                const tabs = controls.querySelectorAll('.u-appt-tab');
                const searchInput = controls.querySelector('#userApptSearch');

                function applyFilters(){
                    const term = (searchInput.value||'').toLowerCase();
                    const filtered = appts.filter(a => (currentFilter==='all' || String(a.appointments_type||'')===currentFilter));
                    const filtered2 = term ? filtered.filter(a => {
                        const d = a.appointments_date ? new Date(String(a.appointments_date).replace(' ','T')) : null;
                        const nice = d ? d.toLocaleString() : '';
                        const aa = {
                            aa_type: a.aa_type || '',
                            aa_address: a.aa_address || '',
                            aa_city: a.aa_city || '',
                            aa_province: a.aa_province || '',
                            aa_postal_code: a.aa_postal_code || '',
                            aa_notes: a.aa_notes || ''
                        };
                        const notes = aa.aa_notes || '';
                        const hay = `${(a.appointments_pet_name||'')} ${(a.appointments_pet_type||'')} ${(a.appointments_pet_breed||'')} ${(a.appointments_type||'')} ${nice} ${notes} ${(a.appointments_status||'')}`.toLowerCase();
                        return hay.includes(term);
                    }) : filtered;
                    renderRows(filtered2);
                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                }

                tabs.forEach(btn=>{
                    btn.addEventListener('click', () => {
                        tabs.forEach(b=> b.classList.remove('bg-gray-900','text-white','border-gray-900'));
                        tabs.forEach(b=> b.classList.add('bg-white'));
                        btn.classList.add('bg-gray-900','text-white','border-gray-900');
                        currentFilter = btn.getAttribute('data-appt-filter') || 'all';
                        applyFilters();
                    });
                });
                searchInput.addEventListener('input', applyFilters);
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function closeStats() {
            document.getElementById('statsModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function confirmCancelAppt(id){
            if (!id) return;
            const container = document.createElement('div');
            container.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-white border border-gray-200 w-80';
            container.innerHTML = `
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Cancel appointment?</p>
                        <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
                        <div class="mt-3 flex justify-end gap-2">
                            <button class="px-3 py-1.5 rounded-md border" id="ccNo">No</button>
                            <button class="px-3 py-1.5 rounded-md bg-red-600 text-white" id="ccYes">Yes, cancel</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(container);
            if (window.lucide && lucide.createIcons) lucide.createIcons();
            container.querySelector('#ccNo').addEventListener('click', ()=>{
                document.body.removeChild(container);
            });
            container.querySelector('#ccYes').addEventListener('click', ()=>{
                // Submit via AJAX to keep UI live
                const form = document.getElementById('cancelApptForm');
                if (!form) return;
                const fd = new FormData(form);
                fd.set('appointment_id', String(id));
                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(() => {
                        // Update local dataset
                        if (Array.isArray(window.userApptsData)) {
                            window.userApptsData = window.userApptsData.map(a => {
                                if (Number(a.appointments_id) === Number(id)) {
                                    return { ...a, appointments_status: 'cancelled' };
                                }
                                return a;
                            });
                            // Re-render if modal is on appointments
                            const open = document.getElementById('statsModal');
                            if (open && !open.classList.contains('hidden')) {
                                openStats('appointments');
                            }
                        }
                        // Update the real-time appointments count (pending+confirmed)
                        const count = Array.isArray(window.userApptsData)
                            ? window.userApptsData.filter(a => ['pending','confirmed'].includes(String(a.appointments_status||''))).length
                            : null;
                        const numEl = document.getElementById('appointmentsCountNum');
                        if (numEl && count !== null) numEl.textContent = String(count);
                        document.body.removeChild(container);
                        showNotification('Appointment cancelled', 'success');
                    })
                    .catch(() => {
                        document.body.removeChild(container);
                        showNotification('Failed to cancel appointment', 'error');
                    });
            });
        }

        function confirmDeleteAppt(id){
            if (!id) return;
            const container = document.createElement('div');
            container.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-white border border-gray-200 w-80';
            container.innerHTML = `
                <div class="flex items-start gap-3">
                    <i data-lucide="trash-2" class="w-5 h-5 text-red-600 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Delete appointment?</p>
                        <p class="text-sm text-gray-600 mt-1">Only cancelled appointments can be deleted. This cannot be undone.</p>
                        <div class="mt-3 flex justify-end gap-2">
                            <button class="px-3 py-1.5 rounded-md border" id="cdNo">No</button>
                            <button class="px-3 py-1.5 rounded-md bg-red-600 text-white" id="cdYes">Yes, delete</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(container);
            if (window.lucide && lucide.createIcons) lucide.createIcons();
            container.querySelector('#cdNo').addEventListener('click', ()=>{
                document.body.removeChild(container);
            });
            container.querySelector('#cdYes').addEventListener('click', ()=>{
                const form = document.getElementById('deleteApptForm');
                if (!form) return;
                const fd = new FormData(form);
                fd.set('appointment_id', String(id));
                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(() => {
                        if (Array.isArray(window.userApptsData)) {
                            window.userApptsData = window.userApptsData.filter(a => Number(a.appointments_id) !== Number(id));
                            const numEl = document.getElementById('appointmentsCountNum');
                            if (numEl) {
                                const count = window.userApptsData.filter(a => ['pending','confirmed'].includes(String(a.appointments_status||''))).length;
                                numEl.textContent = String(count);
                            }
                        }
                        const modal = document.getElementById('statsModal');
                        if (modal && !modal.classList.contains('hidden')) openStats('appointments');
                        document.body.removeChild(container);
                        showNotification('Appointment deleted', 'success');
                    })
                    .catch(() => {
                        document.body.removeChild(container);
                        showNotification('Failed to delete appointment', 'error');
                    });
            });
        }

        // Helpers for list actions
        function openEditPetFromList(id) {
            // Find card with matching data-id and reuse existing editor
            const card = document.querySelector(`.pet-card[data-id="${id}"] .action-btn[title="Edit"]`);
            if (card) openEditPet(card);
        }
        function deleteFromList(id) {
            const card = document.querySelector(`.pet-card[data-id="${id}"] .action-btn[title="Delete"]`);
            if (card) confirmDeletePet(card);
        }

        // Basic HTML escaper
        function escapeHtml(s) {
            return String(s)
                .replaceAll('&','&amp;')
                .replaceAll('<','&lt;')
                .replaceAll('>','&gt;')
                .replaceAll('"','&quot;')
                .replaceAll("'",'&#039;');
        }

        // Parallax effect for floating elements
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating-element');
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.05 + (index * 0.02);
                const yPos = -(scrolled * speed);
                element.style.transform = `translate3d(0, ${yPos}px, 0)`;
            });
        });
    </script>
</body>
</html>