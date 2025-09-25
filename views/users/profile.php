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

// Flash message restore (PRG pattern)
if (isset($_SESSION['flash_message'])) {
    $flashMessage = (string)($_SESSION['flash_message'] ?? '');
    $flashType = (string)($_SESSION['flash_type'] ?? 'success');
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Helper to set flash + redirect to avoid duplicate form resubmission on refresh
function set_flash_and_redirect(string $msg, string $type = 'success') : void {
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = $type;
    header('Location: profile.php');
    exit();
}

// (Removed immediate profile photo upload handler) – photo now processed only when saving profile

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update_profile') && $usersId > 0) {
    $newFirst = trim((string)($_POST['firstName'] ?? ''));
    $newLast = trim((string)($_POST['lastName'] ?? ''));
    $newUser = trim((string)($_POST['username'] ?? ''));
    $newEmail = trim((string)($_POST['email'] ?? ''));

    // Basic validation
    if ($newFirst === '' || $newLast === '' || $newUser === '' || $newEmail === '') {
        set_flash_and_redirect('Please fill in First name, Last name, Username, and Email.', 'error');
    }
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        set_flash_and_redirect('Please provide a valid email address.', 'error');
    }
    if (!isset($connections) || !$connections) {
        set_flash_and_redirect('Database connection is not available.', 'error');
    }

    // Duplicate username/email check
    if ($stmt = mysqli_prepare($connections, 'SELECT users_id FROM users WHERE (users_username = ? OR users_email = ?) AND users_id <> ? LIMIT 1')) {
        mysqli_stmt_bind_param($stmt, 'ssi', $newUser, $newEmail, $usersId);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($r)) {
            mysqli_stmt_close($stmt);
            set_flash_and_redirect('Username or Email already in use.', 'error');
        }
        mysqli_stmt_close($stmt);
    }

    // Handle optional profile image (deferred until save)
    $newImageRel = '';
    if (isset($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_image'];
        $err = (int)($file['error'] ?? UPLOAD_ERR_OK);
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'Image exceeds server size limit.',
                UPLOAD_ERR_FORM_SIZE => 'Image exceeds allowed size.',
                UPLOAD_ERR_PARTIAL => 'Image upload incomplete.',
                UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension.'
            ];
            set_flash_and_redirect($map[$err] ?? 'Image upload failed.', 'error');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            set_flash_and_redirect('Upload not valid.', 'error');
        }
        $size = (int)($file['size'] ?? 0);
        if ($size > 2 * 1024 * 1024) { // 2MB
            set_flash_and_redirect('Image too large (max 2MB).', 'error');
        }
        $origName = (string)($file['name'] ?? '');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowedExt, true)) {
            set_flash_and_redirect('Unsupported image type. Allowed: jpg, jpeg, png, gif, webp.', 'error');
        }
        $uploadDir = realpath(__DIR__ . '/../../pictures');
        if ($uploadDir === false) { $uploadDir = __DIR__ . '/../../pictures'; }
        $userDir = $uploadDir . DIRECTORY_SEPARATOR . 'users';
        if (!is_dir($userDir)) { @mkdir($userDir, 0777, true); }
        $fileName = 'u' . $usersId . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $destAbs = $userDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@move_uploaded_file($tmp, $destAbs)) {
            set_flash_and_redirect('Failed to save uploaded image.', 'error');
        }
        $newImageRel = 'pictures/users/' . $fileName;
        // Remove old file if ours
        $oldImg = (string)($sessionUser['users_image_url'] ?? '');
        if ($oldImg !== '' && strpos($oldImg, 'pictures/users/') === 0) {
            $oldAbs = realpath(__DIR__ . '/../../' . $oldImg);
            if ($oldAbs && is_file($oldAbs)) { @unlink($oldAbs); }
        }
    }

    // Build dynamic update query
    $sql = 'UPDATE users SET users_firstname = ?, users_lastname = ?, users_username = ?, users_email = ?';
    if ($newImageRel !== '') { $sql .= ', users_image_url = ?'; }
    $sql .= ' WHERE users_id = ?';
    if ($stmt = mysqli_prepare($connections, $sql)) {
        if ($newImageRel !== '') {
            mysqli_stmt_bind_param($stmt, 'sssssi', $newFirst, $newLast, $newUser, $newEmail, $newImageRel, $usersId);
        } else {
            mysqli_stmt_bind_param($stmt, 'ssssi', $newFirst, $newLast, $newUser, $newEmail, $usersId);
        }
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user']['users_firstname'] = $newFirst;
            $_SESSION['user']['users_lastname'] = $newLast;
            $_SESSION['user']['users_username'] = $newUser;
            $_SESSION['user']['users_email'] = $newEmail;
            if ($newImageRel !== '') {
                $_SESSION['user']['users_image_url'] = $newImageRel;
            }
            mysqli_stmt_close($stmt);
            set_flash_and_redirect('Profile updated successfully!', 'success');
        } else {
            mysqli_stmt_close($stmt);
            set_flash_and_redirect('Failed to update profile. Please try again.', 'error');
        }
    } else {
        set_flash_and_redirect('Could not prepare update statement.', 'error');
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

// Handle add/edit address for pawdb.locations table (now with PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add_address', 'edit_address'], true) && $usersId > 0) {
    $locId = (int)($_POST['address_id'] ?? 0);
    $label = trim((string)($_POST['address_label'] ?? ''));
    $recipient = trim((string)($_POST['address_recipient_name'] ?? ''));
    $phone = trim((string)($_POST['address_phone'] ?? ''));
    $line1 = trim((string)($_POST['address_line1'] ?? ''));
    $line2 = trim((string)($_POST['address_line2'] ?? ''));
    $barangay = trim((string)($_POST['address_barangay'] ?? ''));
    $city = trim((string)($_POST['address_city'] ?? ''));
    $province = trim((string)($_POST['address_province'] ?? ''));
    $isDefault = isset($_POST['address_is_default']) && $_POST['address_is_default'] === '1' ? 1 : 0;
    $active = isset($_POST['address_active']) && $_POST['address_active'] === '1' ? 1 : 0;

    // Validate required fields
    if ($label === '' || $recipient === '' || $phone === '' || $line1 === '' || $barangay === '' || $city === '' || $province === '') {
        set_flash_and_redirect('Please fill in all required address fields.', 'error');
    } elseif (!isset($connections) || !$connections) {
        set_flash_and_redirect('Database connection is not available.', 'error');
    } else {
        if ($_POST['action'] === 'add_address') {
            // If this address is marked as default, unset previous defaults for this user
            if ($isDefault === 1) {
                if ($stmt = mysqli_prepare($connections, 'UPDATE locations SET location_is_default = 0 WHERE users_id = ?')) {
                    mysqli_stmt_bind_param($stmt, 'i', $usersId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
            // Duplicate address prevention (same label + line1 + barangay + city + province)
            if ($stmt = mysqli_prepare($connections, 'SELECT location_id FROM locations WHERE users_id = ? AND location_label = ? AND location_address_line1 = ? AND location_barangay = ? AND location_city = ? AND location_province = ? LIMIT 1')) {
                mysqli_stmt_bind_param($stmt, 'isssss', $usersId, $label, $line1, $barangay, $city, $province);
                mysqli_stmt_execute($stmt);
                $dupRes = mysqli_stmt_get_result($stmt);
                if (mysqli_fetch_assoc($dupRes)) {
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect('This address already exists.', 'error');
                }
                mysqli_stmt_close($stmt);
            }
        $sql = "INSERT INTO locations (users_id, location_label, location_recipient_name, location_phone, location_address_line1, location_address_line2, location_barangay, location_city, location_province, location_is_default, location_active, location_created_at, location_updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            if ($stmt = mysqli_prepare($connections, $sql)) {
                mysqli_stmt_bind_param($stmt, 'issssssssii', $usersId, $label, $recipient, $phone, $line1, $line2, $barangay, $city, $province, $isDefault, $active);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect('Address added successfully!', 'success');
                } else {
                    $err = 'Failed to add address. Please try again.';
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect($err, 'error');
                }
            } else {
                set_flash_and_redirect('Could not prepare address insert statement.', 'error');
            }
        } elseif ($_POST['action'] === 'edit_address' && $locId > 0) {
            // If setting this address as default, unset other defaults
            if ($isDefault === 1) {
                if ($stmt = mysqli_prepare($connections, 'UPDATE locations SET location_is_default = 0 WHERE users_id = ? AND location_id <> ?')) {
                    mysqli_stmt_bind_param($stmt, 'ii', $usersId, $locId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
            $sql = "UPDATE locations SET location_label=?, location_recipient_name=?, location_phone=?, location_address_line1=?, location_address_line2=?, location_barangay=?, location_city=?, location_province=?, location_is_default=?, location_active=?, location_updated_at=NOW() WHERE location_id=? AND users_id=?";
            if ($stmt = mysqli_prepare($connections, $sql)) {
                mysqli_stmt_bind_param($stmt, 'ssssssssiiii', $label, $recipient, $phone, $line1, $line2, $barangay, $city, $province, $isDefault, $active, $locId, $usersId);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect('Address updated successfully!', 'success');
                } else {
                    $err = 'Failed to update address. Please try again.';
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect($err, 'error');
                }
            } else {
                set_flash_and_redirect('Could not prepare address update statement.', 'error');
            }
        }
    }
}

// Handle delete address (pawdb.location)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'delete_address') && $usersId > 0) {
    $locId = (int)($_POST['address_id'] ?? 0);
    if ($locId > 0) {
        if (isset($connections) && $connections) {
            if ($stmt = mysqli_prepare($connections, 'DELETE FROM locations WHERE location_id = ? AND users_id = ?')) {
                mysqli_stmt_bind_param($stmt, 'ii', $locId, $usersId);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect('Address deleted successfully!', 'success');
                } else {
                    mysqli_stmt_close($stmt);
                    set_flash_and_redirect('Failed to delete address. Please try again.', 'error');
                }
            } else {
                set_flash_and_redirect('Could not prepare delete address statement.', 'error');
            }
        } else {
            set_flash_and_redirect('Database connection is not available.', 'error');
        }
    } else {
        set_flash_and_redirect('Invalid address selected for deletion.', 'error');
    }
}

// Fetch up to 2 addresses from locations (default first)
$userAddresses = [];
if (isset($connections) && $connections && $usersId > 0) {
    if ($stmt = mysqli_prepare($connections, 'SELECT location_id, location_label, location_recipient_name, location_phone, location_address_line1, location_address_line2, location_barangay, location_city, location_province, location_is_default, location_active FROM locations WHERE users_id = ? ORDER BY location_is_default DESC, location_id ASC LIMIT 2')) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $userAddresses[] = $row;
        }
        mysqli_stmt_close($stmt);
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

// Unified product orders derived from transactions + pickups/deliveries + transaction_products
$ordersCount = 0;
$userPickupOrders = [];
$userDeliveryOrders = [];
if (isset($connections) && $connections && $usersId > 0) {
    $sqlOrders = "SELECT t.transactions_id, t.transactions_amount, t.transactions_payment_method, t.transactions_fulfillment_type,
                         t.transactions_created_at,
                         p.pickups_pickup_date, p.pickups_pickup_time, p.pickups_pickup_status,
                         d.deliveries_delivery_status, d.deliveries_estimated_delivery_date, d.deliveries_actual_delivery_date, d.deliveries_recipient_signature,
                         d.location_id,
                         COALESCE(l.location_address_line1, d.deliveries_address) AS full_address_line1,
                         COALESCE(l.location_city, d.deliveries_city) AS full_address_city,
                         COALESCE(l.location_province, '') AS full_address_province
                  FROM transactions t
                  LEFT JOIN pickups p ON p.transactions_id = t.transactions_id
                  LEFT JOIN deliveries d ON d.transactions_id = t.transactions_id
                  LEFT JOIN locations l ON l.location_id = d.location_id
                  WHERE t.users_id = ? AND t.transactions_type = 'product'
                  ORDER BY t.transactions_created_at DESC, t.transactions_id DESC";
    if ($stmt = mysqli_prepare($connections, $sqlOrders)) {
        mysqli_stmt_bind_param($stmt, 'i', $usersId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
        mysqli_stmt_close($stmt);
        if ($rows) {
            $ids = array_map('intval', array_column($rows, 'transactions_id'));
            $idList = implode(',', $ids);
            $lineData = [];
            if ($idList !== '') {
                $lineSql = "SELECT tp.transactions_id, tp.products_id, tp.tp_quantity, pr.products_name, pr.products_price
                            FROM transaction_products tp
                            JOIN products pr ON pr.products_id = tp.products_id
                            WHERE tp.transactions_id IN ($idList)";
                if ($lr = mysqli_query($connections, $lineSql)) {
                    while ($ln = mysqli_fetch_assoc($lr)) { $lineData[] = $ln; }
                    mysqli_free_result($lr);
                }
            }
            $byTxn = [];
            foreach ($lineData as $ln) { $byTxn[(int)$ln['transactions_id']][] = $ln; }
            foreach ($rows as $row) {
                $tid = (int)$row['transactions_id'];
                $row['items'] = $byTxn[$tid] ?? [];
                if (($row['transactions_fulfillment_type'] ?? '') === 'pickup') {
                    $userPickupOrders[] = $row;
                } elseif (($row['transactions_fulfillment_type'] ?? '') === 'delivery') {
                    $userDeliveryOrders[] = $row;
                }
            }
            $ordersCount = count($userPickupOrders) + count($userDeliveryOrders);
        }
    }
}

// Handle order cancellation (pickup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_pickup_order' && $usersId > 0) {
    $tid = (int)($_POST['transactions_id'] ?? 0);
    if ($tid > 0 && isset($connections) && $connections) {
        // Verify ownership & pickup status
        $chkSql = "SELECT p.pickups_pickup_status FROM transactions t JOIN pickups p ON p.transactions_id = t.transactions_id WHERE t.transactions_id = ? AND t.users_id = ? LIMIT 1";
        if ($cs = mysqli_prepare($connections, $chkSql)) {
            mysqli_stmt_bind_param($cs, 'ii', $tid, $usersId);
            mysqli_stmt_execute($cs); $rs = mysqli_stmt_get_result($cs); $ok = false; $stat='';
            if ($rw = mysqli_fetch_assoc($rs)) { $stat = (string)($rw['pickups_pickup_status'] ?? ''); $ok = in_array($stat, ['scheduled','pending'], true); }
            mysqli_stmt_close($cs);
            if ($ok) {
                $upd = mysqli_prepare($connections, "UPDATE pickups SET pickups_pickup_status='cancelled' WHERE transactions_id = ?");
                if ($upd) { mysqli_stmt_bind_param($upd,'i',$tid); mysqli_stmt_execute($upd); mysqli_stmt_close($upd); }
            }
        }
    }
    header('Location: profile.php');
    exit();
}

// Handle order cancellation (delivery)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_delivery_order' && $usersId > 0) {
    $tid = (int)($_POST['transactions_id'] ?? 0);
    if ($tid > 0 && isset($connections) && $connections) {
        $chkSql = "SELECT d.deliveries_delivery_status FROM transactions t JOIN deliveries d ON d.transactions_id = t.transactions_id WHERE t.transactions_id = ? AND t.users_id = ? LIMIT 1";
        if ($cs = mysqli_prepare($connections, $chkSql)) {
            mysqli_stmt_bind_param($cs,'ii',$tid,$usersId);
            mysqli_stmt_execute($cs); $rs = mysqli_stmt_get_result($cs); $ok=false; $stat='';
            if ($rw = mysqli_fetch_assoc($rs)) { $stat = (string)($rw['deliveries_delivery_status'] ?? ''); $ok = in_array($stat, ['processing','pending'], true); }
            mysqli_stmt_close($cs);
            if ($ok) {
                $upd = mysqli_prepare($connections, "UPDATE deliveries SET deliveries_delivery_status='cancelled' WHERE transactions_id = ?");
                if ($upd) { mysqli_stmt_bind_param($upd,'i',$tid); mysqli_stmt_execute($upd); mysqli_stmt_close($upd); }
            }
        }
    }
    header('Location: profile.php');
    exit();
}

// (Removed legacy user_addresses fetch block to prevent overriding addresses from locations)
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
            /* Added: allow modal to scroll when taller than viewport */
            max-height: 90vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
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
                
                <div class="stats-card rounded-2xl p-6 cursor-pointer" onclick="openStats('orders')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">My Orders</p>
                            <p class="text-3xl font-bold text-amber-600"><?php echo (int)$ordersCount; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                            <i data-lucide="shopping-bag" class="w-6 h-6 text-amber-600"></i>
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

            <!-- Removed old horizontal address row -->

            <!-- Address Modal -->
            <div id="addressModal" class="fixed inset-0 z-50 hidden">
                <div class="modal-overlay absolute inset-0" onclick="closeAddressModal()"></div>
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="modal-content relative w-full max-w-md rounded-3xl p-8 max-h-[90vh] overflow-y-auto">
                        <button onclick="closeAddressModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors duration-300">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800" id="addressModalTitle">Add Address</h3>
                        </div>
                        <form id="addressForm" method="post" class="space-y-4">
                            <input type="hidden" name="action" id="addressFormAction" value="add_address" />
                            <input type="hidden" name="address_id" id="addressIdField" value="" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Label *</label>
                                <div class="grid grid-cols-3 gap-3 mb-3" id="addressLabelChoices">
                                    <button type="button" data-label="Home" class="label-choice active flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-orange-400 bg-orange-50 text-orange-700 font-semibold hover:bg-orange-100 transition">
                                        <i data-lucide="home" class="w-5 h-5"></i>
                                        <span class="text-xs">Home</span>
                                    </button>
                                    <button type="button" data-label="Office" class="label-choice flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-dashed border-gray-300 bg-white text-gray-600 font-medium hover:border-orange-400 hover:text-orange-600 hover:bg-orange-50 transition">
                                        <i data-lucide="building" class="w-5 h-5"></i>
                                        <span class="text-xs">Office</span>
                                    </button>
                                    <button type="button" data-label="Other" class="label-choice flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-dashed border-gray-300 bg-white text-gray-600 font-medium hover:border-orange-400 hover:text-orange-600 hover:bg-orange-50 transition">
                                        <i data-lucide="tag" class="w-5 h-5"></i>
                                        <span class="text-xs">Other</span>
                                    </button>
                                </div>
                                <input type="hidden" id="addressLabel" name="address_label" value="Home" />
                                <input type="text" id="addressLabelCustom" class="input-field w-full px-4 py-3 rounded-lg outline-none hidden" placeholder="Enter custom label">
                                <p class="text-xs text-gray-500">Choose a label type. Select Other to provide a custom label.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Name *</label>
                                <input type="text" id="addressRecipientName" name="address_recipient_name" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Full Name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                <input type="text" id="addressPhone" name="address_phone" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Phone Number" maxlength="11">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
                                <input type="text" id="addressLine1" name="address_line1" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Street, Building, etc.">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                                <input type="text" id="addressLine2" name="address_line2" class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Apartment, Unit, etc.">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Barangay *</label>
                                <input type="text" id="addressBarangay" name="address_barangay" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Barangay">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                <input type="text" id="addressCity" name="address_city" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="City">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province *</label>
                                <input type="text" id="addressProvince" name="address_province" required class="input-field w-full px-4 py-3 rounded-lg outline-none" placeholder="Province">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="addressIsDefault" name="address_is_default" value="1" class="mr-2">
                                <label for="addressIsDefault" class="text-sm text-gray-700">Set as default</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="addressActive" name="address_active" value="1" class="mr-2" checked>
                                <label for="addressActive" class="text-sm text-gray-700">Active</label>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="button" onclick="closeAddressModal()" class="flex-1 py-3 px-6 rounded-lg font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-300">
                                    Cancel
                                </button>
                                <button type="submit" class="btn-primary flex-1 py-3 px-6 rounded-lg font-semibold">
                                    <span id="addressSubmitBtnText">Save Address</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

    <form id="addressHiddenForm" method="post" class="hidden">
        <input type="hidden" name="action" value="" />
        <input type="hidden" name="address_id" value="" />
        <input type="hidden" name="address_label" value="" />
        <input type="hidden" name="address_recipient_name" value="" />
        <input type="hidden" name="address_phone" value="" />
        <input type="hidden" name="address_line1" value="" />
        <input type="hidden" name="address_line2" value="" />
        <input type="hidden" name="address_barangay" value="" />
        <input type="hidden" name="address_city" value="" />
        <input type="hidden" name="address_province" value="" />
        <input type="hidden" name="address_is_default" value="" />
        <input type="hidden" name="address_active" value="" />
    </form>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Profile Information -->
                <div>
                    <div class="profile-card rounded-3xl p-8">
                        <h2 class="text-2xl font-bold text-center mb-6 flex items-center justify-center gap-3">
                            <i data-lucide="user" class="w-6 h-6 text-orange-600"></i>
                            Profile Information
                        </h2>
                        
                        <!-- Profile Picture -->
                        <div class="text-center mb-8">
                            <div class="profile-picture relative" onclick="changeProfilePicture()" title="Click to change profile picture">
                                <?php $currentImg = (string)($sessionUser['users_image_url'] ?? ''); $resolvedImg = $currentImg !== '' ? ('../../' . ltrim($currentImg, '/')) : ''; ?>
                                <?php if ($resolvedImg !== ''): ?>
                                    <img id="profile-picture-img" src="<?php echo htmlspecialchars($resolvedImg); ?>" alt="Profile Image" class="w-full h-full object-cover rounded-full" />
                                    <span id="profile-initials" class="hidden"><?php echo htmlspecialchars($initials); ?></span>
                                <?php else: ?>
                                    <img id="profile-picture-img" src="" alt="Profile Image" class="w-full h-full object-cover rounded-full hidden" />
                                    <span id="profile-initials"><?php echo htmlspecialchars($initials); ?></span>
                                <?php endif; ?>
                                <input type="file" id="profile-pic-input" name="profile_image" form="profileForm" accept="image/*" class="hidden" onchange="handleProfilePicChange(event)">
                                <!-- Removed in-circle Edit badge to keep image clean -->
                            </div>
                            <button type="button" onclick="changeProfilePicture()" class="mt-3 text-orange-600 hover:text-orange-700 font-medium text-sm flex items-center gap-2 mx-auto transition-colors duration-300">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                                Change Photo
                            </button>
                            <p class="mt-2 text-[11px] text-gray-500">PNG, JPG, GIF, WEBP up to 2MB. (Will apply after you click Save Changes)</p>
                        </div>
                        
                        <!-- Profile Details -->
                        <form id="profileForm" method="post" enctype="multipart/form-data" class="space-y-6">
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

                <!-- Addresses (Middle Column) -->
                <div>
                    <div class="profile-card rounded-3xl p-8 h-full">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold flex items-center gap-3">
                                <i data-lucide="map-pin" class="w-6 h-6 text-orange-600"></i>
                                Delivery Addresses
                            </h2>
                            <button type="button" onclick="openAddressModal(0)" class="btn-primary py-2 px-4 rounded-lg font-semibold flex items-center gap-2 text-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add
                            </button>
                        </div>
                        <?php if (count($userAddresses) === 0): ?>
                            <div class="border-2 border-dashed border-orange-300 rounded-xl p-6 text-center text-orange-600 bg-orange-50/50">
                                <p class="font-semibold mb-2">No addresses saved</p>
                                <p class="text-sm text-orange-700 mb-4">Add your first delivery address.</p>
                                <button type="button" onclick="openAddressModal(0)" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium shadow">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Add Address
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($userAddresses as $addr): ?>
                                    <div class="group relative border border-orange-200 rounded-xl p-4 bg-orange-50/70 hover:bg-orange-50 transition shadow-sm">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1 font-semibold text-orange-700 text-lg">
                                                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                                                    <span><?php echo htmlspecialchars($addr['location_label'] ?? 'Address'); ?></span>
                                                    <?php if ($addr['location_is_default']): ?>
                                                        <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-base text-gray-700">
                                                    <span class="font-medium"><?php echo htmlspecialchars($addr['location_recipient_name'] ?? ''); ?></span>
                                                    <?php if ($addr['location_phone']): ?>
                                                        <span class="ml-2 text-sm text-gray-500"><?php echo htmlspecialchars($addr['location_phone']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1 leading-relaxed">
                                                    <?php
                                                        $parts = [
                                                            $addr['location_address_line1'] ?? '',
                                                            $addr['location_address_line2'] ?? '',
                                                            $addr['location_barangay'] ?? '',
                                                            $addr['location_city'] ?? '',
                                                            $addr['location_province'] ?? ''
                                                        ];
                                                        echo htmlspecialchars(implode(', ', array_filter($parts)));
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition">
                                                <button type="button" class="p-2 rounded-lg bg-white shadow border border-gray-200 text-blue-600 hover:text-blue-800" title="Edit" onclick="openAddressModal(<?php echo (int)$addr['location_id']; ?>)">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <form method="post" onsubmit="return confirm('Delete this address?');">
                                                    <input type="hidden" name="action" value="delete_address" />
                                                    <input type="hidden" name="address_id" value="<?php echo (int)$addr['location_id']; ?>" />
                                                    <button type="submit" class="p-2 rounded-lg bg-white shadow border border-gray-200 text-red-600 hover:text-red-700" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($userAddresses) < 2): ?>
                                    <button type="button" onclick="openAddressModal(0)" class="w-full border-2 border-dashed border-orange-300 rounded-xl p-4 flex items-center justify-center gap-2 text-orange-700 hover:bg-orange-50 transition text-sm font-medium">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add Another Address
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <p class="mt-6 text-[11px] text-gray-500">Showing up to 2 addresses. Set one as default for faster checkout.</p>
                    </div>
                </div>

                <!-- Pet Management -->
                <div>
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
                        
                        <!-- Pets Grid (scrollable when 4+ pets) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 <?php echo count($userPets) > 3 ? 'max-h-[28rem] overflow-y-auto pr-2 custom-scroll' : ''; ?>" id="petsGrid">
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
                                    <div class="pet-card rounded-2xl p-6 relative flex flex-col" data-id="<?php echo (int)($pet['pets_id'] ?? 0); ?>" data-name="<?php echo htmlspecialchars($pet['pets_name'] ?? '', ENT_QUOTES); ?>" data-species="<?php echo htmlspecialchars($pet['pets_species'] ?? '', ENT_QUOTES); ?>" data-breed="<?php echo htmlspecialchars($pet['pets_breed'] ?? '', ENT_QUOTES); ?>" data-gender="<?php echo htmlspecialchars((string)($pet['pets_gender'] ?? 'unknown'), ENT_QUOTES); ?>">
                                        <div class="pet-avatar">
                                            <i data-lucide="<?php echo $icon; ?>" class="w-8 h-8 pet-type-icon"></i>
                                        </div>
                                        <div class="text-center">
                                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($pet['pets_name'] ?? ''); ?></h3>
                                            <p class="text-orange-600 font-medium mb-1"><?php echo htmlspecialchars($pet['pets_breed'] ?? ''); ?></p>
                                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(ucfirst($species)); ?> • <?php echo htmlspecialchars(ucfirst((string)($pet['pets_gender'] ?? 'unknown'))); ?></p>
                                        </div>
                                        <div class="mt-auto pt-2 flex justify-end gap-3">
                                            <button class="action-btn text-blue-600 hover:text-blue-700" title="Edit" type="button" onclick="openEditPet(this)">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <button class="action-btn text-red-600 hover:text-red-700" title="Delete" type="button" onclick="confirmDeletePet(this)">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
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
            <div class="modal-content relative w-full max-w-md rounded-3xl p-8 max-h-[90vh] overflow-y-auto">
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

            // Seed global orders data (pickup & delivery)
            window.userPickupOrders = <?php echo json_encode($userPickupOrders ?? []); ?>;
            window.userDeliveryOrders = <?php echo json_encode($userDeliveryOrders ?? []); ?>;
        });

        // Global variables
        let editingPetId = null;
        let isEditingProfile = false;

        // Profile picture functions
        function changeProfilePicture() {
            if (!isEditingProfile) { alert('Click Edit Profile first to change your photo.'); return; }
            document.getElementById('profile-pic-input').click();
        }

        function handleProfilePicChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!/image\/(png|jpe?g|gif|webp)/i.test(file.type)) { alert('Unsupported file type. Use PNG, JPG, GIF, or WEBP.'); event.target.value=''; return; }
            if (file.size > 2 * 1024 * 1024) { alert('Image too large. Max 2MB.'); event.target.value=''; return; }
            const reader = new FileReader();
            reader.onload = e => {
                const imgEl = document.getElementById('profile-picture-img');
                const initialsEl = document.getElementById('profile-initials');
                if (imgEl) { imgEl.src = e.target.result; imgEl.classList.remove('hidden'); }
                if (initialsEl) { initialsEl.classList.add('hidden'); }
            };
            reader.readAsDataURL(file);
            // No auto-submit – file sent when saving profile
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
                // Previously closed only the pet modal; now close all.
                closePetModal();
                closeAddressModal();
                closeStats();
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
                                <button class="action-btn text-red-600 hover:text-red-700" title="Delete" type="button" onclick="confirmDeletePet(this)"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        `;
                        content.appendChild(row);
                    });
                }
            } else if (which === 'orders') {
                title.textContent = 'My Orders';
                const totalOrders = (window.userPickupOrders?.length || 0) + (window.userDeliveryOrders?.length || 0);
                subtitle.textContent = totalOrders + ' total';
                iconEl.setAttribute('data-lucide','shopping-bag');
                const tabs=document.createElement('div');
                tabs.className='flex gap-4 mb-4 border-b';
                tabs.innerHTML=`<button id="ordersTabPickup" class="px-3 py-2 text-sm font-medium border-b-2 border-orange-500 text-orange-600">Pickups (${(window.userPickupOrders||[]).length})</button>
                                 <button id="ordersTabDelivery" class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-orange-600">Deliveries (${(window.userDeliveryOrders||[]).length})</button>`;
                content.appendChild(tabs);
                const wrap=document.createElement('div'); content.appendChild(wrap);
                function renderPickup(){
                    wrap.innerHTML='';
                    if(!window.userPickupOrders || window.userPickupOrders.length===0){ wrap.innerHTML='<div class="text-center text-gray-600">No pickup orders.</div>'; return; }
                    const tbl=document.createElement('table'); tbl.className='min-w-full divide-y divide-gray-200 text-xs';
                    tbl.innerHTML=`<thead class='bg-gray-50'><tr>
                        <th class='px-3 py-2 text-left'>Product Name</th>
                        <th class='px-3 py-2 text-left'>Qty</th>
                        <th class='px-3 py-2 text-left'>Price</th>
                        <th class='px-3 py-2 text-left'>Pickup Date</th>
                        <th class='px-3 py-2 text-left'>Pickup Time</th>
                        <th class='px-3 py-2 text-left'>Payment</th>
                        <th class='px-3 py-2 text-left'>Status</th>
                        <th class='px-3 py-2 text-left'>Cancel</th>
                    </tr></thead><tbody></tbody>`;
                    const tb=tbl.querySelector('tbody');
                    window.userPickupOrders.forEach(o=>{
                        (o.items||[]).forEach(it=>{
                            const canCancel=['scheduled','pending'].includes(String(o.pickups_pickup_status||''));
                            const tr=document.createElement('tr');
                            tr.innerHTML=`<td class='px-3 py-2'>${escapeHtml(it.products_name||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(it.tp_quantity||'')}</td>
                                          <td class='px-3 py-2'>₱${Number(it.products_price||0).toFixed(2)}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.pickups_pickup_date||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.pickups_pickup_time||'')}</td>
                                          <td class='px-3 py-2 uppercase'>${escapeHtml(o.transactions_payment_method||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.pickups_pickup_status||'')}</td>
                                          <td class='px-3 py-2'>${canCancel?`<button class='text-red-600 hover:underline' onclick='cancelPickup(${Number(o.transactions_id)})'>Cancel</button>`:''}</td>`;
                            tb.appendChild(tr);
                        });
                    });
                    wrap.appendChild(tbl);
                }
                function renderDelivery(){
                    wrap.innerHTML='';
                    if(!window.userDeliveryOrders || window.userDeliveryOrders.length===0){ wrap.innerHTML='<div class="text-center text-gray-600">No delivery orders.</div>'; return; }
                    const tbl=document.createElement('table'); tbl.className='min-w-full divide-y divide-gray-200 text-xs';
                    tbl.innerHTML=`<thead class='bg-gray-50'><tr>
                        <th class='px-3 py-2 text-left'>Product Name</th>
                        <th class='px-3 py-2 text-left'>Qty</th>
                        <th class='px-3 py-2 text-left'>Price</th>
                        <th class='px-3 py-2 text-left'>Location</th>
                        <th class='px-3 py-2 text-left'>Status</th>
                        <th class='px-3 py-2 text-left'>Estimated Date</th>
                        <th class='px-3 py-2 text-left'>Actual Date</th>
                        <th class='px-3 py-2 text-left'>Payment</th>
                        <th class='px-3 py-2 text-left'>Remarks</th>
                        <th class='px-3 py-2 text-left'>Cancel</th>
                    </tr></thead><tbody></tbody>`;
                    const tb=tbl.querySelector('tbody');
                    window.userDeliveryOrders.forEach(o=>{
                        const loc=[o.full_address_line1||'', o.full_address_city||'', o.full_address_province||''].filter(Boolean).join(', ');
                        (o.items||[]).forEach(it=>{
                            const canCancel=['processing','pending'].includes(String(o.deliveries_delivery_status||''));
                            const remarks=o.deliveries_recipient_signature?'<span class="text-emerald-600 font-medium">Received</span>':'<span class="text-gray-500">Pending</span>';
                            const tr=document.createElement('tr');
                            tr.innerHTML=`<td class='px-3 py-2'>${escapeHtml(it.products_name||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(it.tp_quantity||'')}</td>
                                          <td class='px-3 py-2'>₱${Number(it.products_price||0).toFixed(2)}</td>
                                          <td class='px-3 py-2'>${escapeHtml(loc)}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.deliveries_delivery_status||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.deliveries_estimated_delivery_date||'')}</td>
                                          <td class='px-3 py-2'>${escapeHtml(o.deliveries_actual_delivery_date||'')}</td>
                                          <td class='px-3 py-2 uppercase'>${escapeHtml(o.transactions_payment_method||'')}</td>
                                          <td class='px-3 py-2'>${remarks}</td>
                                          <td class='px-3 py-2'>${canCancel?`<button class='text-red-600 hover:underline' onclick='cancelDelivery(${Number(o.transactions_id)})'>Cancel</button>`:''}</td>`;
                            tb.appendChild(tr);
                        });
                    });
                    wrap.appendChild(tbl);
                }
                renderPickup();
                tabs.querySelector('#ordersTabPickup').addEventListener('click',()=>{
                    tabs.querySelector('#ordersTabPickup').classList.add('border-orange-500','text-orange-600');
                    tabs.querySelector('#ordersTabDelivery').classList.remove('border-orange-500','text-orange-600');
                    renderPickup();
                });
                tabs.querySelector('#ordersTabDelivery').addEventListener('click',()=>{
                    tabs.querySelector('#ordersTabDelivery').classList.add('border-orange-500','text-orange-600');
                    tabs.querySelector('#ordersTabPickup').classList.remove('border-orange-500','text-orange-600');
                    renderDelivery();
                });
            } else if (which === 'appointments') {
                title.textContent = 'My Appointments';
                subtitle.textContent = '<?php echo (int)$bookedCount; ?> total';
                iconEl.setAttribute('data-lucide', 'calendar');
                const appts = window.userApptsData || <?php echo json_encode($userAppointmentsDetailed ?? []); ?>;
                if (!appts || appts.length === 0) {
                    content.innerHTML = '<div class="text-center text-gray-600">No appointments found.</div>';
                } else {
                    // Table for appointments
                    const table = document.createElement('table');
                    table.className = 'min-w-full divide-y divide-gray-200';
                    table.innerHTML = `
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sitting Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody id="userApptsBody" class="bg-white divide-y divide-gray-200"></tbody>
                    `;
                    const tbody = table.querySelector('tbody');
                    appts.forEach(a => {
                        const d = a.appointments_date ? new Date(String(a.appointments_date).replace(' ','T')) : null;
                        const niceDate = d ? d.toLocaleString() : '';
                        const status = String(a.appointments_status||'');
                        let chip = `<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">${escapeHtml(status)}</span>`;
                        if (status==='pending') chip = '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                        if (status==='completed') chip = '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Completed</span>';
                        if (status==='cancelled') chip = '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Cancelled</span>';
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="px-4 py-3 font-medium text-gray-800">${escapeHtml(a.appointments_pet_name || '')}</td>
                            <td class="px-4 py-3">${typeLabel(a.appointments_type)}</td>
                            <td class="px-4 py-3">${a.appointments_type==='pet_sitting' ? escapeHtml(sittingTypeLabel(aa)) : '-'}</td>
                            <td class="px-4 py-3">${a.appointments_type==='pet_sitting' ? escapeHtml(fullAddress(aa)) : '-'}</td>
                            <td class="px-4 py-3">${escapeHtml(niceDate)}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(notes)}</td>
                            <td class="px-4 py-3">${statusChip(a.appointments_status)}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                ${canCancel ? `<button class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50" onclick="confirmCancelAppt(${Number(a.appointments_id)})">Cancel</button>` : ''}
                                ${canDelete ? `<button class=\"px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50" onclick=\"confirmDeleteAppt(${Number(a.appointments_id)})\">Delete</button>` : ''}
                            </td>`;
                        tbody.appendChild(tr);
                    });
                    content.appendChild(table);
                }
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

        // Address modal logic
        function openAddressModal(addressId) {
            let addr = null;
            if (addressId && window.addressesData) {
                addr = window.addressesData.find(a => Number(a.location_id) === Number(addressId));
            }
            document.getElementById('addressModalTitle').textContent = addr ? 'Edit Address' : 'Add Address';
            document.getElementById('addressFormAction').value = addr ? 'edit_address' : 'add_address';
            document.getElementById('addressIdField').value = addr ? addr.location_id : '';
            const labelVal = addr ? (addr.location_label || 'Home') : 'Home';
            document.getElementById('addressLabel').value = labelVal;
            // Sync label cards
            const choiceWrap = document.getElementById('addressLabelChoices');
            const customInput = document.getElementById('addressLabelCustom');
            if (choiceWrap) {
                choiceWrap.querySelectorAll('.label-choice').forEach(btn=>{
                    btn.classList.remove('active','border-orange-400','bg-orange-50','text-orange-700');
                    btn.classList.add('border-dashed','border-gray-300','text-gray-600','bg-white');
                    if(btn.getAttribute('data-label')===labelVal){
                        btn.classList.add('active','border-orange-400','bg-orange-50','text-orange-700');
                        btn.classList.remove('border-dashed','border-gray-300','text-gray-600','bg-white');
                    }
                });
            }
            if (labelVal === 'Other') {
                customInput.classList.remove('hidden');
                customInput.value = addr ? addr.location_label : '';
            } else if (customInput) {
                customInput.classList.add('hidden');
                customInput.value='';
            }
            document.getElementById('addressRecipientName').value = addr ? addr.location_recipient_name : '';
            document.getElementById('addressPhone').value = addr ? addr.location_phone : '';
            document.getElementById('addressLine1').value = addr ? addr.location_address_line1 : '';
            document.getElementById('addressLine2').value = addr ? addr.location_address_line2 : '';
            document.getElementById('addressBarangay').value = addr ? addr.location_barangay : '';
            document.getElementById('addressCity').value = addr ? addr.location_city : '';
            document.getElementById('addressProvince').value = addr ? addr.location_province : '';
            document.getElementById('addressIsDefault').checked = addr ? !!Number(addr.location_is_default) : false;
            document.getElementById('addressActive').checked = addr ? !!Number(addr.location_active) : true;
            document.getElementById('addressSubmitBtnText').textContent = addr ? 'Save Changes' : 'Add Address';
            document.getElementById('addressModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeAddressModal() {
            document.getElementById('addressModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        window.addressesData = <?php echo json_encode($userAddresses ?? []); ?>;

        // Address form submit handler
        document.addEventListener('DOMContentLoaded', function() {
            // ...existing code...
            lucide.createIcons();
            // Label choice logic
            const choiceWrap = document.getElementById('addressLabelChoices');
            const hiddenLabel = document.getElementById('addressLabel');
            const customInput = document.getElementById('addressLabelCustom');
            if (choiceWrap && hiddenLabel) {
                choiceWrap.addEventListener('click', e=>{
                    const btn = e.target.closest('.label-choice');
                    if(!btn) return;
                    const val = btn.getAttribute('data-label');
                    choiceWrap.querySelectorAll('.label-choice').forEach(c=>{
                        c.classList.remove('active','border-orange-400','bg-orange-50','text-orange-700');
                        c.classList.add('border-dashed','border-gray-300','text-gray-600','bg-white');
                    });
                    btn.classList.add('active','border-orange-400','bg-orange-50','text-orange-700');
                    btn.classList.remove('border-dashed','border-gray-300','text-gray-600','bg-white');
                    hiddenLabel.value = val;
                    if(val==='Other'){
                        customInput.classList.remove('hidden');
                        customInput.focus();
                    } else {
                        if(customInput){customInput.classList.add('hidden'); customInput.value='';}
                    }
                });
                if(customInput){
                    customInput.addEventListener('input',()=>{
                        if(!customInput.classList.contains('hidden')){
                            hiddenLabel.value = customInput.value.trim()!==''? customInput.value.trim(): 'Other';
                        }
                    });
                }
            }

            document.getElementById('addressForm').onsubmit = function(e) {
                e.preventDefault();
                var form = document.getElementById('addressHiddenForm');
                form.action.value = document.getElementById('addressFormAction').value;
                form.address_id.value = document.getElementById('addressIdField').value;
                form.address_label.value = document.getElementById('addressLabel').value;
                form.address_recipient_name.value = document.getElementById('addressRecipientName').value;
                form.address_phone.value = document.getElementById('addressPhone').value;
                form.address_line1.value = document.getElementById('addressLine1').value;
                form.address_line2.value = document.getElementById('addressLine2').value;
                form.address_barangay.value = document.getElementById('addressBarangay').value;
                form.address_city.value = document.getElementById('addressCity').value;
                form.address_province.value = document.getElementById('addressProvince').value;
                form.address_is_default.value = document.getElementById('addressIsDefault').checked ? '1' : '0';
                form.address_active.value = document.getElementById('addressActive').checked ? '1' : '0';
                form.submit();
            };
        });

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

        // Cancel pickup order
        function cancelPickup(tid){
            if(!tid) return; if(!confirm('Cancel this pickup order?')) return;
            const fd=new FormData(); fd.append('action','cancel_pickup_order'); fd.append('transactions_id',tid);
            fetch('profile.php',{method:'POST',body:fd}).then(()=>location.reload());
        }
        // Cancel delivery order
        function cancelDelivery(tid){
            if(!tid) return; if(!confirm('Cancel this delivery order?')) return;
            const fd=new FormData(); fd.append('action','cancel_delivery_order'); fd.append('transactions_id',tid);
            fetch('profile.php',{method:'POST',body:fd}).then(()=>location.reload());
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