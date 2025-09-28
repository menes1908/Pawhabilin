<?php
require_once __DIR__ . '/../../utils/session.php';
session_start_if_needed();

// DB connection
require_once __DIR__ . '/../../database.php';

// Require login to access this page
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: ../../login.php?redirect=' . rawurlencode('views/users/book_appointment.php'));
    exit;
}

// Current user and pets list (for registered pet selection)
$currentUser = get_current_user_session();
$users_id = isset($currentUser['users_id']) ? (int)$currentUser['users_id'] : 0;
$userPets = [];
if ($users_id > 0 && isset($connections) && $connections) {
    if ($stmt = mysqli_prepare($connections, 'SELECT pets_id, pets_name, pets_species, pets_breed FROM pets WHERE users_id = ? ORDER BY pets_name')) {
        mysqli_stmt_bind_param($stmt, 'i', $users_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $userPets[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Simple flash messaging (success only for PRG)
$success = null;
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Generate one-time form token on initial GET (to prevent duplicate submissions)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (empty($_SESSION['form_token'])) {
        try {
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            // Fallback if random_bytes not available
            $_SESSION['form_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $service = trim($_POST['service'] ?? ''); // 'grooming' | 'vet' | 'pet_sitting'
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $petName = trim($_POST['petName'] ?? '');
    $petType = trim($_POST['petType'] ?? ''); // dog|cat|...
    $breed = trim($_POST['breed'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $appointmentDate = trim($_POST['appointmentDate'] ?? ''); // YYYY-MM-DD
    $appointmentTime = trim($_POST['appointmentTime'] ?? ''); // HH:MM
    $specialRequests = trim($_POST['specialRequests'] ?? '');

    // Registered pet option
    $useRegisteredPet = (($_POST['useRegisteredPet'] ?? '') === '1');
    $selectedPetId = (int)($_POST['selectedPetId'] ?? 0);

    // Pet sitting specific
    $sittingMode = trim($_POST['sittingMode'] ?? ''); // 'home' | 'dropoff' (only when pet-sitting)
    $sitAddress = trim($_POST['sit_address'] ?? '');
    $sitCity = trim($_POST['sit_city'] ?? '');
    $sitProvince = trim($_POST['sit_province'] ?? '');
    $sitPostal = trim($_POST['sit_postal'] ?? '');
    $sitNotes = trim($_POST['sit_notes'] ?? '');

    $errors = [];
    // Base validations
    if ($service === '') $errors[] = 'Please select a service.';
    if ($fullName === '') $errors[] = 'Full Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid Email is required.';
    if ($phone === '') $errors[] = 'Phone Number is required.';
    // If using a registered pet, fetch and override pet fields server-side
    if ($useRegisteredPet) {
        if ($selectedPetId <= 0) {
            $errors[] = 'Please select a registered pet.';
        } else {
            if ($users_id > 0 && isset($connections) && $connections) {
                if ($stmt = mysqli_prepare($connections, 'SELECT pets_name, pets_species, pets_breed FROM pets WHERE pets_id = ? AND users_id = ? LIMIT 1')) {
                    mysqli_stmt_bind_param($stmt, 'ii', $selectedPetId, $users_id);
                    mysqli_stmt_execute($stmt);
                    $r = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($r)) {
                        $petName = (string)$row['pets_name'];
                        $species = strtolower((string)$row['pets_species']);
                        // Normalize species to appointments enum: dog|cat|bird|fish|other
                        $map = ['dog' => 'dog', 'cat' => 'cat', 'bird' => 'bird', 'fish' => 'fish'];
                        $petType = $map[$species] ?? 'other';
                        $breed = (string)$row['pets_breed'];
                    } else {
                        $errors[] = 'Selected pet not found.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = 'Could not verify selected pet.';
                }
            } else {
                $errors[] = 'Not authorized to use a registered pet.';
            }
        }
    }

    if ($petName === '') $errors[] = 'Pet Name is required.';
    // Normalize user-entered pet type to allowed enum
    $allowedPetTypes = ['dog','cat','bird','fish','other'];
    $petType = strtolower($petType);
    if (!in_array($petType, $allowedPetTypes, true)) {
        $petType = 'other';
    }
    if ($petType === '') $errors[] = 'Pet Type is required.';
    if ($appointmentDate === '') $errors[] = 'Appointment Date is required.';
    if ($appointmentTime === '') $errors[] = 'Appointment Time is required.';

    // Pet sitting validations
    if ($service === 'pet_sitting') {
        if ($sittingMode === '') $errors[] = 'Please choose Home-sitting or Drop Off.';
        if ($sittingMode === 'home') {
            if ($sitAddress === '') $errors[] = 'Address is required for Home-sitting.';
            if ($sitCity === '') $errors[] = 'City is required for Home-sitting.';
            if ($sitProvince === '') $errors[] = 'Province is required for Home-sitting.';
            if ($sitPostal === '') $errors[] = 'Postal Code is required for Home-sitting.';
        }
    }

    // Combine date/time to datetime
    $appointments_date = null;
    if ($appointmentDate && $appointmentTime) {
        $appointments_date = $appointmentDate . ' ' . $appointmentTime . ':00';
    }

    // One-time token check (prevents duplicate/double submit)
    $postedToken = $_POST['form_token'] ?? '';
    if (!isset($_SESSION['form_token']) || !is_string($postedToken) || !hash_equals($_SESSION['form_token'], $postedToken)) {
        $errors[] = 'This form was already submitted or is invalid. Please reload the page and try again.';
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
    } else {
        // Determine appointment type mapping to DB enum
        // appointments.appointments_type enum('grooming','vet','pet_sitting') NOT NULL
        $validTypes = ['grooming','vet','pet_sitting'];
        if (!in_array($service, $validTypes, true)) {
            $error = 'Invalid service selection.';
        } else {
            $apptType = $service;
        }

        if (!empty($error)) {
            // early stop if invalid service
        } else {

        // User id from session (already set above)
        if ($users_id <= 0) {
            $error = 'You must be logged in to book an appointment.';
        } else {
            // Use transaction for consistency
            mysqli_begin_transaction($connections);
            try {

                // Insert appointment (aa_id null for now) per new schema columns
                $stmt = mysqli_prepare($connections, "INSERT INTO appointments (users_id, appointments_full_name, appointments_email, appointments_phone, appointments_pet_name, appointments_pet_type, appointments_pet_breed, appointments_pet_age_years, appointments_type, appointments_date, sitters_id, aa_id, appointments_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, 'pending')");
                mysqli_stmt_bind_param($stmt, 'isssssssss', $users_id, $fullName, $email, $phone, $petName, $petType, $breed, $age, $apptType, $appointments_date);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to create appointment: ' . mysqli_error($connections));
                }
                $appointments_id = mysqli_insert_id($connections);
                mysqli_stmt_close($stmt);

                // If pet-sitting: create appointment_address and link back
                if ($service === 'pet_sitting') {
                    $aa_type = ($sittingMode === 'home') ? 'home-sitting' : 'drop_off';
                    $stmt = mysqli_prepare($connections, "INSERT INTO appointment_address (appointments_id, aa_type, aa_address, aa_city, aa_province, aa_postal_code, aa_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'issssss', $appointments_id, $aa_type, $sitAddress, $sitCity, $sitProvince, $sitPostal, $sitNotes);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to save appointment address: ' . mysqli_error($connections));
                    }
                    $aa_id = mysqli_insert_id($connections);
                    mysqli_stmt_close($stmt);

                    // Link aa_id in appointments
                    $stmt = mysqli_prepare($connections, "UPDATE appointments SET aa_id = ? WHERE appointments_id = ?");
                    mysqli_stmt_bind_param($stmt, 'ii', $aa_id, $appointments_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to link appointment address: ' . mysqli_error($connections));
                    }
                    mysqli_stmt_close($stmt);
                }

                mysqli_commit($connections);

                // Invalidate the token so the same payload cannot be resubmitted
                unset($_SESSION['form_token']);

                // Set flash message and redirect (PRG pattern) to avoid duplicate submission on refresh
                $_SESSION['flash_success'] = "Appointment booked! We'll contact you within 24 hours to confirm.";
                header('Location: ' . basename($_SERVER['PHP_SELF']));
                exit;
            } catch (Throwable $tx) {
                mysqli_rollback($connections);
                $error = 'Sorry, something went wrong while saving your appointment. Please try again.';
                error_log('Book appointment error: ' . $tx->getMessage());
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment - pawhabilin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.6); }
        }
        
        @keyframes slide-in-up {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-3deg); }
            75% { transform: rotate(3deg); }
        }
        
        @keyframes bounce-in {
            0% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.05) rotate(5deg); }
            70% { transform: scale(0.9) rotate(-2deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        @keyframes heart-beat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 0.8; }
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(2) {
            animation-delay: -2s;
        }
        
        .floating-element:nth-child(3) {
            animation-delay: -4s;
        }
        
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        .slide-in-up {
            animation: slide-in-up 0.6s ease-out forwards;
        }
        
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradient-shift 8s ease infinite;
        }
        
        .service-card {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.8s ease;
            z-index: 1;
        }
        
        .service-card:hover::before {
            left: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-20px) scale(1.03) rotate(2deg);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        }
        
        .service-image {
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover .service-image {
            transform: scale(1.1) rotate(-2deg);
        }
        
        .service-icon {
            transition: all 0.4s ease;
            position: relative;
            z-index: 2;
        }
        
        .service-card:hover .service-icon {
            transform: scale(1.3) rotate(15deg);
            animation: wiggle 0.6s ease-in-out;
        }
        
        .service-price {
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .service-card:hover .service-price {
            transform: scale(1.1);
            animation: heart-beat 1s ease-in-out infinite;
        }
        
        .floating-badge {
            animation: bounce-in 0.8s ease-out;
            position: relative;
            z-index: 3;
        }
        
        .service-card:hover .floating-badge {
            animation: sparkle 2s ease-in-out infinite;
        }
        
        .morphing-border {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .morphing-border::after {
            content: '';
            position: absolute;
            inset: 0;
            padding: 3px;
            background: linear-gradient(45deg, #f97316, #fb923c, #fbbf24, #f59e0b, #f97316);
            background-size: 300% 300%;
            border-radius: inherit;
            animation: gradient-shift 4s ease infinite;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: xor;
            z-index: -1;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .feature-item {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .feature-item:hover {
            transform: translateX(10px);
            color: #f97316;
        }
        
        .feature-item::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #fbbf24);
            transition: width 0.3s ease;
            transform: translateY(-50%);
        }
        
        .feature-item:hover::before {
            width: 6px;
        }
        
        .booking-form {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: #fbbf24;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.3);
        }
        
        .sign-in-prompt {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            animation: pulse-glow 3s ease-in-out infinite;
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
            .service-card:hover {
                transform: translateY(-10px) scale(1.02);
            }
            
            .service-card:hover .service-image {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<?php
require_once __DIR__ . '/../../utils/session.php';
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);
?>
<body class="min-h-screen bg-gray-50">
    <!-- Header (shared include) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
    <div class="fixed top-4 right-4 z-50 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg shadow-lg fade-out">
        <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="fixed top-4 right-4 z-50 bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg shadow-lg fade-out">
        <div class="flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden gradient-bg">
        <!-- Floating background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-20 left-10 opacity-20">
                <i data-lucide="paw-print" class="w-24 h-24 text-white transform rotate-12"></i>
            </div>
            <div class="floating-element absolute top-40 right-20 opacity-20">
                <i data-lucide="heart" class="w-16 h-16 text-white transform -rotate-12"></i>
            </div>
            <div class="floating-element absolute bottom-20 left-1/4 opacity-20">
                <i data-lucide="stethoscope" class="w-20 h-20 text-white transform rotate-45"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="space-y-8 slide-in-up">
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium glass-effect">
                        <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                        Professional Pet Care Services
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-shadow">
                        Expert Care for Your
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl text-yellow-200">Beloved Companions</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        From routine veterinary checkups to professional grooming and trusted pet sitting, 
                        we provide comprehensive care services that keep your furry family members healthy and happy.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center pt-8">
                        <button onclick="scrollToBooking()" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-white text-orange-600 hover:bg-orange-50 h-14 px-8 transform hover:scale-105 hover:shadow-2xl">
                            <i data-lucide="calendar-plus" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                            Book Your Appointment
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </button>
                        
                        <button class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-medium transition-all duration-300 border-2 border-white text-white hover:bg-white hover:text-orange-600 h-14 px-8 transform hover:scale-105">
                            <i data-lucide="play" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                            Watch Our Story
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Overview -->
    <section class="py-20 bg-white relative">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="heart" class="w-3 h-3 mr-1"></i>
                    Our Services
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Complete Pet Care Solutions
                    </span>
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Choose from our comprehensive range of professional services designed to keep your pets 
                    healthy, beautiful, and well-cared for in every aspect of their lives.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <!-- Veterinary Care -->
                <div class="service-card morphing-border bg-gradient-to-br from-red-50 to-pink-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        Most Popular
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1625321171045-1fea4ac688e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx2ZXRlcmluYXJ5JTIwY2FyZSUyMGRvZyUyMGNoZWNrdXB8ZW58MXx8fHwxNzU4NjEwNjA5fDA&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Veterinary Care" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-red-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="stethoscope" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Veterinary Care</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Comprehensive medical care from routine wellness exams to emergency treatments. 
                            Our licensed veterinarians provide expert diagnosis, treatment, and preventive care.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Comprehensive Health Examinations</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Vaccinations & Immunizations</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Emergency Medical Care</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Advanced Diagnostic Services</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Surgical Procedures</span>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>

                <!-- Grooming Services -->
                <div class="service-card morphing-border bg-gradient-to-br from-blue-50 to-indigo-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        Premium
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1644675443401-ea4c14bad0e6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBkb2clMjBncm9vbWluZyUyMHNhbG9ufGVufDF8fHx8MTc1ODYxMDYxNHww&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Professional Grooming" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-blue-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="scissors" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Professional Grooming</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Expert grooming services to keep your pets looking and feeling their absolute best. 
                            From basic hygiene to full luxury spa treatments with premium products.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Full-Service Bathing & Drying</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Professional Hair Cutting & Styling</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Nail Trimming & Paw Care</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Ear Cleaning & Dental Hygiene</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Premium Spa Treatments</span>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>

                <!-- Pet Sitting -->
                <div class="service-card morphing-border bg-gradient-to-br from-green-50 to-emerald-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        24/7 Care
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1668522907255-62950845ff46?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBzaXR0ZXIlMjBwbGF5aW5nJTIwd2l0aCUyMGRvZ3N8ZW58MXx8fHwxNzU4NjEwNjE4fDA&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Pet Sitting & Care" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-green-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="users" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Pet Sitting & Care</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Trusted, loving care when you can't be there. Our certified pet sitters provide 
                            personalized attention, ensuring your pets feel safe, loved, and entertained.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>In-Home Pet Sitting Services</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Daily Dog Walking & Exercise</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Overnight Pet Boarding</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Live Updates & Photo Reports</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Emergency Contact Available</span>
                            </div>
                        </div>
                        
                    
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-20 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                        <i data-lucide="award" class="w-3 h-3 mr-1"></i>
                        Why Choose pawhabilin
                    </div>
                    
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        Trusted by <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">25,000+</span> Pet Families
                    </h2>
                    
                    <p class="text-xl text-gray-700 mb-8 leading-relaxed">
                        We've built our reputation on providing exceptional care, professional service, 
                        and genuine love for all pets. Here's what makes us different.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">Licensed & Certified Professionals</h3>
                                <p class="text-gray-600">All our veterinarians and groomers are fully licensed with years of experience in pet care.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">24/7 Emergency Support</h3>
                                <p class="text-gray-600">Round-the-clock emergency services and support for urgent pet care needs.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="heart-handshake" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">Personalized Care Plans</h3>
                                <p class="text-gray-600">Every pet receives individualized attention and care tailored to their specific needs.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-orange-600 mb-2">500+</div>
                                <div class="text-sm text-gray-600">Certified Professionals</div>
                            </div>
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2">50k+</div>
                                <div class="text-sm text-gray-600">Happy Appointments</div>
                            </div>
                        </div>
                        <div class="space-y-6 pt-12">
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">4.9★</div>
                                <div class="text-sm text-gray-600">Average Rating</div>
                            </div>
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-2">24/7</div>
                                <div class="text-sm text-gray-600">Emergency Support</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating pet image -->
                    <div class="absolute -top-8 -right-8 floating-element">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-2xl">
                            <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080" alt="Happy pet" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="message-circle" class="w-3 h-3 mr-1"></i>
                    Testimonials
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    What Pet Parents Say
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Don't just take our word for it. Here's what our happy customers have to say about our services.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-8 border border-orange-100">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            M
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Maria Santos</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The veterinary care my dog received was exceptional. Dr. Rivera was so gentle and thorough during the examination. I trust pawhabilin completely with my pets."</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-100">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            J
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">John Cruz</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"Amazing grooming service! My Golden Retriever looks and smells fantastic. The staff really knows how to handle dogs and make them comfortable."</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 border border-green-100">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            A
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Ana Reyes</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The pet sitting service is incredible. My cats were so well cared for while I was away. I received daily updates and photos. Highly recommended!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking-section" class="py-20 bg-gradient-to-br from-gray-900 via-orange-900 to-amber-900 relative overflow-hidden">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"%23ffffff\">&3Cg fill-opacity=\"0.3\">&3Cpath d=\"M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.373zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83z\"/%3E&3C/g>&3C/svg>');"></div>
        </div>
        
        <!-- Floating elements to match design -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-20 left-10 opacity-20">
                <i data-lucide="calendar-heart" class="w-16 h-16 text-white transform rotate-12"></i>
            </div>
            <div class="floating-element absolute bottom-20 right-20 opacity-20">
                <i data-lucide="clock" class="w-14 h-14 text-white transform -rotate-12"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium glass-effect text-white mb-6">
                    <i data-lucide="calendar-heart" class="w-4 h-4 mr-2"></i>
                    Book Your Appointment
                </div>
                
                <h2 class="text-4xl md:text-6xl font-bold text-white mb-6 text-shadow">
                    Ready to Care for Your
                    <span class="block brand-font text-5xl md:text-7xl text-amber-300">Furry Family?</span>
                </h2>
                
                <p class="text-xl text-white/90 max-w-3xl mx-auto mb-12">
                    Schedule your appointment today and give your beloved pet the professional care they deserve. 
                    Our expert team is ready to provide exceptional service tailored to your pet's unique needs.
                </p>
            </div>

            <!-- Booking Form -->
            <div class="max-w-4xl mx-auto">
                <div class="booking-form rounded-3xl p-8 md:p-12">
                    <form class="space-y-8" action="" method="POST">
                        <?php
                        // Ensure token exists when rendering the form
                        if (empty($_SESSION['form_token'])) {
                            try { $_SESSION['form_token'] = bin2hex(random_bytes(32)); }
                            catch (Throwable $e) { $_SESSION['form_token'] = bin2hex(openssl_random_pseudo_bytes(32)); }
                        }
                        ?>
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token'] ?? ''); ?>" />
                        <!-- Service Selection -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="heart" class="w-6 h-6 text-orange-400"></i>
                                Select Your Service
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="vet" class="sr-only" required>
                                    <div class="bg-white/10 border-2 border-white/20 rounded-2xl p-6 text-center transition-all duration-300 group-hover:bg-white/20 group-hover:border-red-400 group-hover:scale-105">
                                        <i data-lucide="stethoscope" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Veterinary Care</h4>
                                        <p class="text-white/70 text-sm">Health checkups & medical care</p>
                                        
                                    </div>
                                </label>
                                
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="grooming" class="sr-only" required>
                                    <div class="bg-white/10 border-2 border-white/20 rounded-2xl p-6 text-center transition-all duration-300 group-hover:bg-white/20 group-hover:border-blue-400 group-hover:scale-105">
                                        <i data-lucide="scissors" class="w-12 h-12 text-blue-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Professional Grooming</h4>
                                        <p class="text-white/70 text-sm">Bathing, styling & nail care</p>
                                        
                                    </div>
                                </label>
                                
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="pet_sitting" class="sr-only" required>
                                    <div class="bg-white/10 border-2 border-white/20 rounded-2xl p-6 text-center transition-all duration-300 group-hover:bg-white/20 group-hover:border-green-400 group-hover:scale-105">
                                        <i data-lucide="users" class="w-12 h-12 text-green-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Pet Sitting & Care</h4>
                                        <p class="text-white/70 text-sm">In-home care & boarding</p>
                                        
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="user" class="w-6 h-6 text-orange-400"></i>
                                Your Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Full Name *</label>
                                    <input type="text" name="fullName" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['fullName'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Email Address *</label>
                                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Phone Number *</label>
                                    <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="11">
                                </div>
                            </div>
                        </div>

                        <!-- Pet Information -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="paw-print" class="w-6 h-6 text-orange-400"></i>
                                Pet Information
                            </h3>
                            <!-- Use Registered Pet Option -->
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="useRegisteredPet" class="text-white font-medium">Use Registered Pet</label>
                                    <input type="hidden" name="useRegisteredPet" id="useRegisteredPetHidden" value="<?php echo isset($_POST['useRegisteredPet']) ? htmlspecialchars($_POST['useRegisteredPet']) : '0'; ?>">
                                    <button type="button" id="useRegisteredPetToggle" class="relative inline-flex h-6 w-11 items-center rounded-full transition <?php echo (($_POST['useRegisteredPet'] ?? '0') === '1') ? 'bg-green-500' : 'bg-white/20'; ?>">
                                        <span class="sr-only">Toggle use registered pet</span>
                                        <span class="inline-block h-5 w-5 transform rounded-full bg-white transition translate-x-<?php echo (($_POST['useRegisteredPet'] ?? '0') === '1') ? '6' : '1'; ?>"></span>
                                    </button>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-white font-medium mb-2">Select Pet</label>
                                    <select name="selectedPetId" id="selectedPetId" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300 <?php echo (($_POST['useRegisteredPet'] ?? '0') === '1') ? '' : 'opacity-50 pointer-events-none'; ?>">
                                        <option value="">Choose your pet</option>
                                        <?php foreach (($userPets ?? []) as $p): ?>
                                            <option class="text-gray-800" value="<?php echo (int)$p['pets_id']; ?>" <?php echo ((int)($_POST['selectedPetId'] ?? 0) === (int)$p['pets_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['pets_name'] . ' — ' . ($p['pets_species'] ?? '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Pet Name *</label>
                                    <input type="text" name="petName" id="petName" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter your pet's name" value="<?php echo htmlspecialchars($_POST['petName'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Pet Type *</label>
                                    <select name="petType" id="petType" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300">
                                        <option value="" class="text-gray-800">Select pet type</option>
                                        <option value="dog" class="text-gray-800" <?php echo (($_POST['petType'] ?? '') === 'dog') ? 'selected' : ''; ?>>Dog</option>
                                        <option value="cat" class="text-gray-800" <?php echo (($_POST['petType'] ?? '') === 'cat') ? 'selected' : ''; ?>>Cat</option>
                                        <option value="bird" class="text-gray-800" <?php echo (($_POST['petType'] ?? '') === 'bird') ? 'selected' : ''; ?>>Bird</option>
                                        <option value="fish" class="text-gray-800" <?php echo (($_POST['petType'] ?? '') === 'fish') ? 'selected' : ''; ?>>Fish</option>
                                        <option value="other" class="text-gray-800" <?php echo (($_POST['petType'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Breed</label>
                                    <input type="text" name="breed" id="breed" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter breed" value="<?php echo htmlspecialchars($_POST['breed'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Age</label>
                                    <input type="text" name="age" id="age" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Enter age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Pet Sitting Options (conditional) -->
                        <div id="petSittingOptions" class="hidden">
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="home" class="w-6 h-6 text-orange-400"></i>
                                Pet Sitting Mode
                            </h3>
                            <input type="hidden" name="sittingMode" id="sittingModeInput" value="<?php echo htmlspecialchars($_POST['sittingMode'] ?? ''); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <label class="cursor-pointer group">
                                    <input type="radio" name="sittingModeRadio" value="home" class="sr-only">
                                    <div class="bg-white/10 border-2 border-white/20 rounded-2xl p-6 text-center transition-all duration-300 group-hover:bg-white/20 group-hover:border-orange-400 group-hover:scale-105">
                                        <i data-lucide="home" class="w-10 h-10 text-orange-400 mx-auto mb-2"></i>
                                        <h4 class="font-semibold text-white mb-1">Home-sitting</h4>
                                        <p class="text-white/70 text-sm">We go to your home</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="sittingModeRadio" value="dropoff" class="sr-only">
                                    <div class="bg-white/10 border-2 border-white/20 rounded-2xl p-6 text-center transition-all duration-300 group-hover:bg-white/20 group-hover:border-green-400 group-hover:scale-105">
                                        <i data-lucide="building-2" class="w-10 h-10 text-green-400 mx-auto mb-2"></i>
                                        <h4 class="font-semibold text-white mb-1">Drop Off</h4>
                                        <p class="text-white/70 text-sm">Bring to sitter facility</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Address fields (only when Home-sitting) -->
                            <div id="homeAddressFields" class="hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-white font-medium mb-2">Address *</label>
                                        <input type="text" name="sit_address" id="sit_address" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Street, Barangay" value="<?php echo htmlspecialchars($_POST['sit_address'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-white font-medium mb-2">City *</label>
                                        <input type="text" name="sit_city" id="sit_city" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="City/Municipality" value="<?php echo htmlspecialchars($_POST['sit_city'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-white font-medium mb-2">Postal Code *</label>
                                        <input type="text" name="sit_postal" id="sit_postal" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="e.g., 1600" value="<?php echo htmlspecialchars($_POST['sit_postal'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-white font-medium mb-2">Province *</label>
                                        <input type="text" name="sit_province" id="sit_province" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="e.g., Batangas" value="<?php echo htmlspecialchars($_POST['sit_province'] ?? ''); ?>">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-white font-medium mb-2">Notes</label>
                                        <input type="text" name="sit_notes" id="sit_notes" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" placeholder="Any instructions or notes" value="<?php echo htmlspecialchars($_POST['sit_notes'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Details -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="calendar" class="w-6 h-6 text-orange-400"></i>
                                Appointment Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Preferred Date *</label>
                                    <input type="date" name="appointmentDate" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300" value="<?php echo htmlspecialchars($_POST['appointmentDate'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Preferred Time *</label>
                                    <select name="appointmentTime" required class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white focus:bg-white/20 focus:border-orange-400 focus:outline-none transition-all duration-300">
                                        <option value="" class="text-gray-800">Select time</option>
                                        <option value="09:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '09:00') ? 'selected' : ''; ?>>9:00 AM</option>
                                        <option value="10:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '10:00') ? 'selected' : ''; ?>>10:00 AM</option>
                                        <option value="11:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '11:00') ? 'selected' : ''; ?>>11:00 AM</option>
                                        <option value="14:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '14:00') ? 'selected' : ''; ?>>2:00 PM</option>
                                        <option value="15:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '15:00') ? 'selected' : ''; ?>>3:00 PM</option>
                                        <option value="16:00" class="text-gray-800" <?php echo (($_POST['appointmentTime'] ?? '') === '16:00') ? 'selected' : ''; ?>>4:00 PM</option>
                                    </select>
                                </div>
                            </div>
                        </div>


                        <!-- Submit Button -->
                        <div class="text-center pt-6">
                            <button type="submit" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-16 px-12 transform hover:scale-105 hover:shadow-2xl pulse-glow">
                                <i data-lucide="calendar-check" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                                Book My Appointment
                                <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                            </button>
                            <p class="text-white/70 text-sm mt-4">
                                We'll confirm your appointment within 24 hours
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080" alt="pawhabilin Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="text-xl font-semibold brand-font">pawhabilin</span>
                    </div>
                    <p class="text-gray-400">
                        The Philippines' most trusted pet care platform providing comprehensive services for your beloved pets.
                    </p>
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
                    <h4 class="font-semibold">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="index.php" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="animal_sitting.php" class="hover:text-white transition-colors">Find a Sitter</a></li>
                        <li><a href="buy_products.php" class="hover:text-white transition-colors">Shop</a></li>
                        <li><a href="book_appointment.php" class="hover:text-white transition-colors">Book Appointment</a></li>
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
                        <li class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            24/7 Emergency Support
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
            
            // Auto-hide success/error messages after 5 seconds
            const messages = document.querySelectorAll('.fixed.top-4.right-4');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-10px)';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });
            
            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('slide-in-up');
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            document.querySelectorAll('.service-card, .glass-effect').forEach(el => {
                observer.observe(el);
            });

            // Registered pet toggle and autofill
            const petsData = <?php echo json_encode(array_map(function($p){
                return [
                    'id' => (int)$p['pets_id'],
                    'name' => (string)$p['pets_name'],
                    'species' => (string)$p['pets_species'],
                    'breed' => (string)($p['pets_breed'] ?? ''),
                ];
            }, $userPets)); ?>;
            const toggleBtn = document.getElementById('useRegisteredPetToggle');
            const toggleHidden = document.getElementById('useRegisteredPetHidden');
            const selectPet = document.getElementById('selectedPetId');
            const petNameEl = document.getElementById('petName');
            const petTypeEl = document.getElementById('petType');
            const breedEl = document.getElementById('breed');
            const ageEl = document.getElementById('age');

            function setRegisteredMode(on){
                toggleHidden.value = on ? '1' : '0';
                if (on) {
                    toggleBtn.classList.remove('bg-white/20');
                    toggleBtn.classList.add('bg-green-500');
                    selectPet.classList.remove('opacity-50','pointer-events-none');
                    const knob = toggleBtn.querySelector('span.inline-block');
                    if (knob) { knob.classList.remove('translate-x-1'); knob.classList.add('translate-x-6'); }
                    if (petTypeEl) petTypeEl.setAttribute('disabled','disabled');
                } else {
                    toggleBtn.classList.add('bg-white/20');
                    toggleBtn.classList.remove('bg-green-500');
                    selectPet.classList.add('opacity-50','pointer-events-none');
                    selectPet.value = '';
                    petNameEl.removeAttribute('readonly');
                    breedEl.removeAttribute('readonly');
                    ageEl.removeAttribute('readonly');
                    const knob = toggleBtn.querySelector('span.inline-block');
                    if (knob) { knob.classList.add('translate-x-1'); knob.classList.remove('translate-x-6'); }
                    if (petTypeEl) petTypeEl.removeAttribute('disabled');
                }
            }

            function normalizeSpeciesToEnum(species){
                const s = String(species || '').toLowerCase();
                if (['dog','cat','bird','fish'].includes(s)) return s;
                return 'other';
            }

            function fillFromPet(pet){
                if (!pet) return;
                petNameEl.value = pet.name || '';
                breedEl.value = pet.breed || '';
                // age is not stored in pets table; leave as-is for user to set
                const norm = normalizeSpeciesToEnum(pet.species);
                petTypeEl.value = norm;
                petNameEl.setAttribute('readonly','readonly');
                breedEl.setAttribute('readonly','readonly');
                // allow changing age freely
            }

            if (toggleBtn && toggleHidden && selectPet) {
                toggleBtn.addEventListener('click', function(){
                    const isOn = toggleHidden.value === '1';
                    setRegisteredMode(!isOn);
                });
                selectPet.addEventListener('change', function(){
                    const id = parseInt(this.value || '0', 10);
                    const pet = petsData.find(p => p.id === id);
                    if (pet) fillFromPet(pet);
                });
                // Initialize from server-posted state
                setRegisteredMode((toggleHidden.value || '0') === '1');
                if (selectPet.value) {
                    const id = parseInt(selectPet.value || '0', 10);
                    const pet = petsData.find(p => p.id === id);
                    if (pet) fillFromPet(pet);
                }
            }
        });

        // Smooth scroll to booking section
        function scrollToBooking() {
            document.getElementById('booking-section').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Form handling
        document.querySelector('form').addEventListener('submit', function(e) {
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>Processing...</span>
                </div>
            `;
            submitBtn.disabled = true;
            
            // Let the form submit naturally to PHP
        });

        // Radio button selection handling
        (function(){
            const petSittingOptions = document.getElementById('petSittingOptions');
            const homeAddressFields = document.getElementById('homeAddressFields');
            const modeHidden = document.getElementById('sittingModeInput');

            function toggleServiceUI(){
                const selected = document.querySelector('input[name="service"]:checked');
                const isPetSitting = selected && selected.value === 'pet_sitting';
                if (isPetSitting) {
                    petSittingOptions.classList.remove('hidden');
                } else {
                    petSittingOptions.classList.add('hidden');
                    homeAddressFields.classList.add('hidden');
                    modeHidden.value = '';
                    ['sit_address','sit_city','sit_province','sit_postal'].forEach(id=>{ const el = document.getElementById(id); if (el) el.removeAttribute('required'); });
                    document.querySelectorAll('input[name="sittingModeRadio"]').forEach(r=>{ r.checked = false; });
                    document.querySelectorAll('input[name="sittingModeRadio"]').forEach(r => {
                        const card = r.closest('label').querySelector('div');
                        card.classList.remove('bg-white/30','border-orange-400','border-green-400','scale-105');
                        card.classList.add('bg-white/10','border-white/20');
                    });
                }
            }

            document.querySelectorAll('input[name="service"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remove active state from all service cards
                    document.querySelectorAll('input[name="service"]').forEach(r => {
                        const card = r.closest('label').querySelector('div');
                        card.classList.remove('bg-white/30', 'border-orange-400', 'scale-105');
                        card.classList.add('bg-white/10', 'border-white/20');
                    });
                    
                    // Add active state to selected card
                    if (this.checked) {
                        const card = this.closest('label').querySelector('div');
                        card.classList.remove('bg-white/10', 'border-white/20');
                        card.classList.add('bg-white/30', 'border-orange-400', 'scale-105');
                    }
                    toggleServiceUI();
                });
            });

            // Sitting mode selection
            document.querySelectorAll('input[name="sittingModeRadio"]').forEach(radio => {
                radio.addEventListener('change', function(){
                    document.querySelectorAll('input[name="sittingModeRadio"]').forEach(r => {
                        const card = r.closest('label').querySelector('div');
                        card.classList.remove('bg-white/30', 'border-orange-400', 'border-green-400', 'scale-105');
                        card.classList.add('bg-white/10', 'border-white/20');
                    });
                    if (this.checked) {
                        const card = this.closest('label').querySelector('div');
                        card.classList.remove('bg-white/10', 'border-white/20');
                        card.classList.add('bg-white/30', this.value === 'home' ? 'border-orange-400' : 'border-green-400', 'scale-105');
                        if (modeHidden) modeHidden.value = this.value;
                    }
                    if (this.value === 'home') {
                        homeAddressFields.classList.remove('hidden');
                        ['sit_address','sit_city','sit_province','sit_postal'].forEach(id=>{ const el = document.getElementById(id); if (el) el.setAttribute('required','required'); });
                    } else {
                        homeAddressFields.classList.add('hidden');
                        ['sit_address','sit_city','sit_province','sit_postal'].forEach(id=>{ const el = document.getElementById(id); if (el) el.removeAttribute('required'); });
                    }
                });
            });

            // Initialize from server values
            document.addEventListener('DOMContentLoaded', function(){
                toggleServiceUI();
                if (modeHidden && modeHidden.value) {
                    const r = document.querySelector(`input[name="sittingModeRadio"][value="${modeHidden.value}"]`);
                    if (r) {
                        r.checked = true;
                        const card = r.closest('label').querySelector('div');
                        card.classList.remove('bg-white/10', 'border-white/20');
                        card.classList.add('bg-white/30', modeHidden.value === 'home' ? 'border-orange-400' : 'border-green-400', 'scale-105');
                        if (modeHidden.value === 'home') {
                            homeAddressFields.classList.remove('hidden');
                            ['sit_address','sit_city','sit_province','sit_postal'].forEach(id=>{ const el = document.getElementById(id); if (el) el.setAttribute('required','required'); });
                        }
                    }
                }
            });
        })();

        // Restore selected service on page load (if form was submitted with errors)
        <?php if (isset($_POST['service'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedService = '<?php echo $_POST['service']; ?>';
            const radio = document.querySelector(`input[name="service"][value="${selectedService}"]`);
            if (radio) {
                radio.checked = true;
                const card = radio.closest('label').querySelector('div');
                card.classList.remove('bg-white/10', 'border-white/20');
                card.classList.add('bg-white/30', 'border-orange-400', 'scale-105');
            }
        });
        <?php endif; ?>

        // Parallax effect for background elements
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating-element');
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.1 + (index * 0.05);
                const yPos = -(scrolled * speed);
                element.style.transform = `translate3d(0, ${yPos}px, 0)`;
            });
        });

        // Simple profile dropdown toggle
        (function(){
            const wrapper = document.getElementById('userMenu');
            const btn = document.getElementById('userMenuBtn');
            const menu = document.getElementById('userMenuMenu');
            if (!wrapper || !btn || !menu) return;
            let open = false;
            function setOpen(state){
                open = state;
                if (open) {
                    menu.classList.remove('opacity-0');
                    menu.classList.remove('translate-y-2');
                    menu.setAttribute('aria-hidden', 'false');
                } else {
                    menu.classList.add('opacity-0');
                    menu.classList.add('translate-y-2');
                    menu.setAttribute('aria-hidden', 'true');
                }
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            setOpen(false);
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                setOpen(!open);
            });
            document.addEventListener('click', function(e){
                if (!wrapper.contains(e.target)) setOpen(false);
            });
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') setOpen(false);
            });
        })();

        // Blocked appointment date: fetch from server + localStorage fallback and validate user selection
        (function(){
            const BLOCK_KEY = 'appointments_block_date';
            const API_URL = '../../controllers/admin/blockdate.php';
            const dateInput = document.querySelector('input[name="appointmentDate"]');
            if(!dateInput) return;
            let currentBlocked = null;
            try { currentBlocked = localStorage.getItem(BLOCK_KEY); } catch(e){}
            function ensureNotice(msg){
                let n = document.getElementById('blockedDateNotice');
                if(!n){
                    n = document.createElement('div');
                    n.id='blockedDateNotice';
                    n.className='mb-4 p-4 rounded-md border border-red-300 bg-red-50 text-red-700 text-sm';
                    const form = dateInput.closest('form');
                    if(form && form.parentNode) form.parentNode.insertBefore(n, form);
                }
                n.textContent = msg;
                return n;
            }
            function removeNotice(){ const n=document.getElementById('blockedDateNotice'); if(n) n.remove(); }
            function disableForm(disabled){
                const form = dateInput.closest('form'); if(!form) return; const submits=form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submits.forEach(b=>{ b.disabled = disabled; b.classList.toggle('opacity-60',disabled); b.classList.toggle('cursor-not-allowed',disabled); });
            }
            function todayStr(){ return new Date().toISOString().substring(0,10); }
            function checkTodayBlock(){
                if(currentBlocked && currentBlocked === todayStr()){
                    ensureNotice('Appointments are temporarily closed for today. Please select another date.');
                    disableForm(true);
                } else { removeNotice(); disableForm(false); }
            }
            function validateSelection(){
                if(!currentBlocked) return;
                if(dateInput.value === currentBlocked){
                    ensureNotice('Selected date ('+currentBlocked+') is closed. Please choose another date.');
                    disableForm(true);
                } else {
                    checkTodayBlock(); // revert to baseline state
                }
            }
            dateInput.addEventListener('change', validateSelection);
            // Initial local check
            checkTodayBlock();
            // Sync from server
            fetch(API_URL + '?_=' + Date.now())
                .then(r=> r.ok ? r.json(): null)
                .then(data=>{
                    if(data && data.blocked_date){
                        currentBlocked = data.blocked_date;
                        try { localStorage.setItem(BLOCK_KEY, currentBlocked); } catch(e){}
                    } else {
                        currentBlocked = null;
                        try { localStorage.removeItem(BLOCK_KEY); } catch(e){}
                    }
                    checkTodayBlock();
                    validateSelection();
                })
                .catch(()=>{});
        })();
    </script>
</body>
</html>