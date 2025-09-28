<?php
// Start session and load DB + helpers
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';

// Resolve current user from unified session shape (utils/session.php) or fallbacks
$u = get_current_user_session();
$user = [
    'id'      => (int)($u['users_id'] ?? ($_SESSION['user_id'] ?? 0)),
    'name'    => $u ? trim(($u['users_firstname'] ?? '') . ' ' . ($u['users_lastname'] ?? '')) ?: ($u['users_username'] ?? '') : ($_SESSION['user_name'] ?? ''),
    'email'   => $u['users_email'] ?? ($_SESSION['user_email'] ?? ''),
    'phone'   => $_SESSION['user_phone'] ?? ($u['users_phone'] ?? ''),
    'address' => $_SESSION['user_address'] ?? ($u['users_address'] ?? ''),
    'profile_image' => $_SESSION['user_image'] ?? ($u['users_image_url'] ?? '')
];

// Enforce auth: guests shouldn't access this page
if (!$user['email']) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to create your sitter profile.']);
    exit;
}

// Load existing sitter by email (try with new columns; fallback if not available)
$existing_sitter = null;
if (isset($connections) && $connections) {
    $query_new = "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active, years_experience, sitters_verified FROM sitters WHERE sitter_email = ? LIMIT 1";
    $stmt = @mysqli_prepare($connections, $query_new);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $user['email']);
        mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sid, $sname, $sbio, $semail, $scontact, $sspec, $sexp, $simg, $sactive, $syears, $sverified);
        if (mysqli_stmt_fetch($stmt)) {
            $existing_sitter = [
                'db_id'      => (int)$sid,
                'sitter_id'  => 'PS' . str_pad((string)$sid, 6, '0', STR_PAD_LEFT),
                'name'       => (string)$sname,
                'contact'    => (string)$scontact,
                'specialty'  => $sspec !== '' ? array_map('trim', explode(',', (string)$sspec)) : [],
                'experience' => (string)$sexp,
                'years'      => (int)$syears,
                'bio'        => (string)$sbio,
                'image_url'  => (string)$simg,
                'active'     => (int)$sactive,
                'verified'   => isset($sverified) ? (int)$sverified : 0
            ];
        }
        mysqli_stmt_close($stmt);
    } else {
        // Fallback without new columns
        if ($stmt2 = mysqli_prepare($connections, "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active FROM sitters WHERE sitter_email = ? LIMIT 1")) {
            mysqli_stmt_bind_param($stmt2, 's', $user['email']);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_bind_result($stmt2, $sid, $sname, $sbio, $semail, $scontact, $sspec, $sexp, $simg, $sactive);
            if (mysqli_stmt_fetch($stmt2)) {
                $existing_sitter = [
                    'db_id'      => (int)$sid,
                    'sitter_id'  => 'PS' . str_pad((string)$sid, 6, '0', STR_PAD_LEFT),
                    'name'       => (string)$sname,
                    'contact'    => (string)$scontact,
                    'specialty'  => $sspec !== '' ? array_map('trim', explode(',', (string)$sspec)) : [],
                    'experience' => (string)$sexp,
                    'years'      => 0,
                    'bio'        => (string)$sbio,
                    'image_url'  => (string)$simg,
                    'active'     => (int)$sactive,
                    'verified'   => 0
                ];
            }
            mysqli_stmt_close($stmt2);
        }
    }
}

$is_update_mode = $existing_sitter !== null;

// Handle form submission: create/update sitter row
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($connections) || !$connections) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection not available.']);
        exit;
    }

    // Gather inputs
    $contact   = trim($_POST['sitters_contact'] ?? '');
    $specialty = $_POST['sitter_specialty'] ?? [];
    if (!is_array($specialty)) { $specialty = []; }
    $specialty_str = implode(', ', array_map('trim', $specialty));
    $experience_txt = trim($_POST['sitter_experience'] ?? '');
    $image_url = trim($_POST['sitters_image_url'] ?? '');

    // Optional extra fields: fold them into bio for now
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

    // Basic validation
    if ($contact === '' || $experience_txt === '' || $specialty_str === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please complete required fields: contact, specialties, and experience.']);
        exit;
    }

    // Name & email from session user
    $s_name = $user['name'] ?: 'Sitter';
    $s_email = $user['email'];

    // Insert or update
    if ($is_update_mode) {
        $sql = "UPDATE sitters SET sitters_name = ?, sitters_bio = ?, sitter_email = ?, sitters_contact = ?, sitter_specialty = ?, sitter_experience = ?, sitters_image_url = ? WHERE sitters_id = ?";
        if ($stmt = mysqli_prepare($connections, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sssssssi', $s_name, $bio, $s_email, $contact, $specialty_str, $experience_txt, $image_url, $existing_sitter['db_id']);
            $ok = mysqli_stmt_execute($stmt);
            $err = mysqli_error($connections);
            mysqli_stmt_close($stmt);
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update sitter profile.', 'error' => $err]);
                exit;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Sitter profile updated successfully!',
                'sitter_id' => 'PS' . str_pad((string)$existing_sitter['db_id'], 6, '0', STR_PAD_LEFT)
            ]);
            exit;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement.']);
        exit;
    } else {
        $sql = "INSERT INTO sitters (sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitter_experience, sitters_image_url, sitters_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        if ($stmt = mysqli_prepare($connections, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sssssss', $s_name, $bio, $s_email, $contact, $specialty_str, $experience_txt, $image_url);
            $ok = mysqli_stmt_execute($stmt);
            $new_id = $ok ? mysqli_insert_id($connections) : 0;
            $err = mysqli_error($connections);
            mysqli_stmt_close($stmt);
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create sitter profile.', 'error' => $err]);
                exit;
            }
            // Mark session flag for convenience (non-authoritative)
            $_SESSION['is_sitter'] = true;
            echo json_encode([
                'success' => true,
                'message' => 'Sitter profile created successfully!',
                'sitter_id' => 'PS' . str_pad((string)$new_id, 6, '0', STR_PAD_LEFT)
            ]);
            exit;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare insert statement.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_update_mode ? 'Update' : 'Create'; ?> Your Sitter Profile - pawhabilin</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Complete your pet sitter profile on pawhabilin. Join the Philippines' most trusted pet care community as a professional sitter.">
    <meta name="keywords" content="pet sitter profile, join pawhabilin, pet care professional, Philippines pet sitting">
    
    <!-- Tailwind CSS v4.0 -->
    <link rel="stylesheet" href="globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* PawSteps™ Wizard Styles */
        @keyframes pawStep {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        @keyframes slideInFromRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideInFromLeft {
            from { transform: translateX(-100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.6); }
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .paw-step {
            animation: pawStep 2s ease-in-out infinite;
        }
        
        .slide-in-right {
            animation: slideInFromRight 0.6s ease-out forwards;
        }
        
        .slide-in-left {
            animation: slideInFromLeft 0.6s ease-out forwards;
        }
        
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradient-shift 8s ease infinite;
        }
        
        /* Glass morphism effects */
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Profile reuse banner */
        .profile-banner {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #0ea5e9;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #0284c7, #0369a1);
        }
        
        /* Specialty tags */
        .specialty-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .specialty-tag::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .specialty-tag:hover::before {
            left: 100%;
        }
        
        .specialty-tag:hover {
            border-color: #f97316;
            background: #fef7f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }
        
        .specialty-tag.selected {
            background: #fef7f0;
            border-color: #f97316;
            color: #ea580c;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
        }
        
        .specialty-tag.selected .specialty-icon {
            color: #f97316;
        }
        
        /* Removed sticky action bar */
        
        /* Form enhancements */
        .form-group {
            position: relative;
            margin-bottom: 24px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
            transform: translateY(-2px);
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        /* Experience level indicators */
        .experience-level {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .experience-level:hover {
            border-color: #f97316;
            background: #fef7f0;
            transform: translateY(-2px);
        }
        
        .experience-level.selected {
            border-color: #f97316;
            background: #fef7f0;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }
        
        .experience-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #d1d5db;
            transition: all 0.3s ease;
        }
        
        .experience-level.selected .experience-indicator {
            background: #f97316;
        }
        
        /* Progress indicators */
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            transition: transform 0.25s ease, background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
        }
        .progress-step i { transition: transform 0.25s ease; }
        .progress-step:hover { background: #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .progress-step:focus-visible { outline: 2px solid #f97316; outline-offset: 2px; }
        
        .progress-step.active {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            transform: scale(1.08);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.25);
        }
        .progress-step.active i { transform: scale(1.08); }
        
        .progress-step.completed {
            background: #10b981;
            color: white;
        }
        
        .progress-connector {
            height: 3px;
            background: #e5e7eb;
            flex: 1;
            margin: 0 10px;
            border-radius: 9999px;
            transition: background-color 0.25s ease;
        }
        
        .progress-connector.completed {
            background: #10b981;
        }
        
        /* Image upload area */
        .image-upload {
            border: 2px dashed #d1d5db;
            border-radius: 16px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .image-upload:hover {
            border-color: #f97316;
            background: #fef7f0;
        }
        
        .image-upload.has-image {
            border-style: solid;
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        /* Success animations */
        @keyframes successBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .success-bounce {
            animation: successBounce 0.6s ease-in-out;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .form-input {
                padding: 14px 16px;
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            .specialty-tag {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .glass-card {
                margin: 0 16px;
                border-radius: 20px;
            }
        }
        
        /* Loading states */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #f97316;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Ladder style for Terms & Agreement */
        .ladder { position: relative; border-left: 3px solid #fde68a; padding-left: 16px; }
        .ladder-item { position: relative; }
        .ladder-dot { position: absolute; left: -9px; top: 10px; width: 10px; height: 10px; background: linear-gradient(135deg, #f97316, #fb923c); border: 2px solid #fff; border-radius: 9999px; box-shadow: 0 0 0 2px #fde68a; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
   <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Progress Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                <!-- Header text -->
                <div class="md:max-w-2xl">
                    <div class="inline-flex items-center rounded-full border border-orange-200 px-6 py-2 text-sm font-medium bg-orange-50 text-orange-600 mb-4">
                        <i data-lucide="paw-print" class="w-4 h-4 mr-2 paw-step"></i>
                        PawSteps™ Sitter Onboarding
                    </div>

                    <h1 class="text-4xl md:text-5xl font-bold mb-3">
                        <?php if ($is_update_mode): ?>
                            Update Your
                            <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Sitter Profile</span>
                        <?php else: ?>
                            Become a Professional
                            <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Pet Sitter</span>
                        <?php endif; ?>
                    </h1>

                    <p class="text-lg md:text-xl text-gray-600">
                        <?php if ($is_update_mode): ?>
                            Update your sitter information to keep your profile current and attract more pet parents.
                        <?php else: ?>
                            You're signed in! We'll use your profile details for your sitter account.
                            Just provide your sitter-specific information below.
                        <?php endif; ?>
                    </p>
                </div>

                
            </div>
        </div>

        <!-- Combined Profile Banner (Profile at top, then Account Information with note at bottom) -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="profile-banner p-6">
                <?php if ($is_update_mode): ?>
                <!-- Sitter Profile Header Row -->
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full overflow-hidden bg-orange-100 text-orange-700 flex items-center justify-center text-xl font-bold">
                            <?php if (!empty($existing_sitter['image_url'])): ?>
                                <?php
                                    $img = $existing_sitter['image_url'];
                                    $isAbsolute = preg_match('/^https?:\/\//i', $img);
                                    $script = $_SERVER['SCRIPT_NAME'] ?? '';
                                    $pos = strpos($script, '/views/');
                                    $basePrefix = $pos !== false ? substr($script, 0, $pos) : '';
                                    $src = $isAbsolute ? $img : rtrim($basePrefix, '/') . '/' . ltrim($img, '/');
                                ?>
                                <img src="<?php echo htmlspecialchars($src); ?>" alt="Sitter Photo" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php $initial = strtoupper(substr(trim($user['name'] ?: 'S'), 0, 1)); echo htmlspecialchars($initial); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name'] ?: 'Sitter'); ?></h2>
                            <div class="mt-1 text-sm text-gray-500">
                                Sitter ID: <span class="font-medium"><?php echo htmlspecialchars($existing_sitter['sitter_id']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php $isVerified = (int)($existing_sitter['verified'] ?? 0) === 1; ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $isVerified ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-700 border'; ?>">
                            <i data-lucide="<?php echo $isVerified ? 'shield-check' : 'shield'; ?>" class="w-3 h-3"></i>
                            <?php echo $isVerified ? 'Verified Sitter' : 'Unverified'; ?>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium <?php echo ($existing_sitter['active'] ?? 1) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border'; ?>">
                            <i data-lucide="activity" class="w-3 h-3"></i>
                            <?php echo ($existing_sitter['active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                        <?php if (!empty($existing_sitter['years']) && (int)$existing_sitter['years'] > 0): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200">
                            <i data-lucide="award" class="w-3 h-3"></i>
                            <?php echo (int)$existing_sitter['years']; ?> year<?php echo ((int)$existing_sitter['years'] === 1) ? '' : 's'; ?> experience
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sitter Summary (Specialties + Bio) -->
                <div class="grid grid-cols-1 gap-4 mb-6">
                    <div class="p-4 rounded-xl border border-blue-100 bg-white/80">
                        <div class="text-xs text-blue-600 mb-2">Specialties</div>
                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($existing_sitter['specialty'])): foreach ($existing_sitter['specialty'] as $sp): ?>
                                <span class="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded-full text-xs font-medium"><?php echo htmlspecialchars($sp); ?></span>
                            <?php endforeach; else: ?>
                                <span class="text-sm text-gray-500">No specialties listed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-blue-100 bg-white/80">
                        <div class="text-xs text-blue-600 mb-1">Bio</div>
                        <div class="text-gray-800"><?php echo nl2br(htmlspecialchars(($existing_sitter['bio'] ?? '') !== '' ? $existing_sitter['bio'] : 'No bio provided.')); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Account Information Section -->
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-blue-700 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        Your Account Information
                    </h2>
                    <div class="inline-flex items-center rounded-full border border-blue-200 px-3 py-1 text-xs font-medium bg-blue-50 text-blue-600">
                        <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                        Verified Account
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-4 border border-blue-100">
                        <div class="text-sm text-blue-600 font-medium mb-1">Email Address</div>
                        <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-4 border border-blue-100">
                        <div class="text-sm text-blue-600 font-medium mb-1">Phone Number</div>
                        <div class="font-semibold text-gray-800"><?php echo htmlspecialchars(($is_update_mode && !empty($existing_sitter['contact'])) ? $existing_sitter['contact'] : $user['phone']); ?></div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-700">
                        <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                        This information is automatically linked to your sitter profile. To update these details, 
                        <a href="profile.php" class="underline hover:no-underline font-medium">visit your profile page</a>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Sitter Application Form -->
        <?php /* Removed separate overview since it's now combined into the banner above */ ?>

        <div class="max-w-4xl mx-auto" id="edit-profile-start">
            <!-- Steps at the top of edit section -->
            <div class="mb-6" id="form-progress">
                <div class="flex items-center gap-4 justify-between">
                    <div class="flex items-center gap-3">
                        <div class="progress-step active" data-step="1">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step" data-step="2">
                            <i data-lucide="heart" class="w-5 h-5"></i>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step" data-step="3">
                            <i data-lucide="camera" class="w-5 h-5"></i>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step" data-step="4">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="flex-1 mx-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="progress-fill" class="h-2 bg-gradient-to-r from-orange-500 to-amber-600 rounded-full transition-all duration-300" style="width: 25%"></div>
                        </div>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <span class="font-medium">Step <span id="current-step">1</span> of 4</span>
                    </div>
                </div>
            </div>
            <form id="sitter-form" class="space-y-8">
                <?php if ($is_update_mode): ?>
                    <input type="hidden" name="sitters_contact" value="<?php echo htmlspecialchars($existing_sitter['contact'] ?: $user['phone']); ?>">
                <?php endif; ?>
                <!-- Step 1: Contact Information -->
                <?php if (!$is_update_mode): ?>
                <div class="glass-card rounded-3xl p-8 slide-in-left" id="step-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center text-white">
                            <i data-lucide="phone" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Contact Information</h3>
                            <p class="text-gray-600">How can pet parents reach you for bookings?</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="form-group">
                            <label class="form-label">Primary Contact Number *</label>
                            <input 
                                type="tel" 
                                name="sitters_contact" 
                                class="form-input" 
                                value="<?php echo $existing_sitter ? htmlspecialchars($existing_sitter['contact']) : htmlspecialchars($user['phone']); ?>"
                                placeholder="Enter your primary contact number"
                                required
                            >
                            <p class="text-sm text-gray-500 mt-2">
                                This will be your main contact for pet parents. We recommend using the same number as your account.
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alternative Contact (Optional)</label>
                            <input 
                                type="tel" 
                                name="alternative_contact" 
                                class="form-input" 
                                placeholder="Enter alternative contact number"
                            >
                            <p class="text-sm text-gray-500 mt-2">
                                Backup contact number for emergencies or when you're unavailable.
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Preferred Contact Method</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="experience-level">
                                    <input type="radio" name="contact_method" value="phone" class="sr-only" checked>
                                    <div class="experience-indicator"></div>
                                    <div>
                                        <div class="font-medium">Phone Call</div>
                                        <div class="text-sm text-gray-600">Direct calls</div>
                                    </div>
                                </label>
                                <label class="experience-level">
                                    <input type="radio" name="contact_method" value="sms" class="sr-only">
                                    <div class="experience-indicator"></div>
                                    <div>
                                        <div class="font-medium">SMS/Text</div>
                                        <div class="text-sm text-gray-600">Text messages</div>
                                    </div>
                                </label>
                                <label class="experience-level">
                                    <input type="radio" name="contact_method" value="app" class="sr-only">
                                    <div class="experience-indicator"></div>
                                    <div>
                                        <div class="font-medium">In-App Chat</div>
                                        <div class="text-sm text-gray-600">Through pawhabilin</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-8">
                        <button type="button" class="next-step px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2">
                            <span>Next: Pet Specialties</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step 2: Pet Specialties -->
                <div class="glass-card rounded-3xl p-8 slide-in-right hidden" id="step-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white">
                            <i data-lucide="heart" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Pet Care Specialties</h3>
                            <p class="text-gray-600">What types of pets do you love caring for?</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="form-group">
                            <label class="form-label">Select Your Pet Specialties *</label>
                            <p class="text-sm text-gray-500 mb-4">Choose all the types of pets you're comfortable caring for:</p>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="specialty-grid">
                                <?php 
                                $specialties = [
                                    'Dogs' => 'dog',
                                    'Cats' => 'cat', 
                                    'Birds' => 'bird',
                                    'Fish' => 'fish',
                                    'Rabbits' => 'rabbit',
                                    'Hamsters' => 'hamster',
                                    'Guinea Pigs' => 'guinea-pig',
                                    'Reptiles' => 'lizard',
                                    'Ferrets' => 'ferret',
                                    'Exotic Pets' => 'sparkles'
                                ];
                                
                                $selected_specialties = $existing_sitter ? $existing_sitter['specialty'] : [];
                                
                                foreach ($specialties as $name => $icon): 
                                    $is_selected = in_array($name, $selected_specialties);
                                ?>
                                <div class="specialty-tag <?php echo $is_selected ? 'selected' : ''; ?>" data-specialty="<?php echo $name; ?>">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 specialty-icon"></i>
                                    <span><?php echo $name; ?></span>
                                    <input type="checkbox" name="sitter_specialty[]" value="<?php echo $name; ?>" class="hidden" <?php echo $is_selected ? 'checked' : ''; ?>>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        
                    </div>

                    <div class="flex justify-end mt-8">
                        <button type="button" class="next-step px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2">
                            <span>Next: Experience & Photo</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Experience & Profile Photo -->
                <div class="glass-card rounded-3xl p-8 slide-in-left hidden" id="step-3">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white">
                            <i data-lucide="award" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Experience & Profile</h3>
                            <p class="text-gray-600">Tell us about your pet care experience and add your photo</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Experience Section -->
                        <div class="space-y-6">
                            <div class="form-group">
                                <label class="form-label">Bio *</label>
                                <textarea 
                                    name="sitters_bio" 
                                    class="form-input" 
                                    rows="6" 
                                    placeholder="Introduce yourself to pet parents... (e.g., 'Hello! I’m Jane, a caring sitter who loves dogs and cats. I provide safe walks, playtime, and attentive care. I’m patient, responsible, and happy to send photo updates!')"
                                    required
                                ><?php echo $existing_sitter ? htmlspecialchars($existing_sitter['bio'] ?? '') : ''; ?></textarea>
                                <p class="text-sm text-gray-500 mt-2">
                                    Share your personality, what you offer, and why you're a great sitter.
                                </p>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Years of Experience (number)</label>
                                <input type="number" name="years_experience" min="0" step="1" class="form-input" placeholder="e.g., 3" value="<?php echo isset($existing_sitter['years']) ? (int)$existing_sitter['years'] : ''; ?>" />
                                <p class="text-sm text-gray-500 mt-2">Enter a whole number. If none, leave as 0.</p>
                            </div>
                        </div>

                        <!-- Profile Photo Section -->
                        <div class="space-y-6">
                            <div class="form-group">
                                <label class="form-label">Profile Photo</label>
                                <div class="image-upload <?php echo $existing_sitter && $existing_sitter['image_url'] ? 'has-image' : ''; ?>" id="image-upload-area">
                                    <input type="file" name="profile_image" id="profile-image-input" class="hidden" accept="image/*">
                                    <div id="upload-placeholder" class="<?php echo $existing_sitter && $existing_sitter['image_url'] ? 'hidden' : ''; ?>">
                                        <i data-lucide="camera" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-gray-700 mb-2">Upload Your Photo</h4>
                                        <p class="text-sm text-gray-500 mb-4">Add a friendly photo to help pet parents get to know you</p>
                                        <button type="button" id="trigger-upload" class="px-6 py-2 bg-gradient-to-r from-orange-500 to-amber-600 text-white rounded-lg font-medium">
                                            Choose Photo
                                        </button>
                                    </div>
                                    <div id="image-preview" class="<?php echo $existing_sitter && $existing_sitter['image_url'] ? '' : 'hidden'; ?>">
                                        <?php if ($existing_sitter && $existing_sitter['image_url']): ?>
                                        <?php
                                            $img2 = $existing_sitter['image_url'];
                                            $isAbs2 = preg_match('/^https?:\/\//i', $img2);
                                            $script2 = $_SERVER['SCRIPT_NAME'] ?? '';
                                            $pos2 = strpos($script2, '/views/');
                                            $basePrefix2 = $pos2 !== false ? substr($script2, 0, $pos2) : '';
                                            $src2 = $isAbs2 ? $img2 : rtrim($basePrefix2, '/') . '/' . ltrim($img2, '/');
                                        ?>
                                        <img src="<?php echo htmlspecialchars($src2); ?>" alt="Profile preview" class="w-32 h-32 rounded-full object-cover mx-auto mb-4">
                                        <?php endif; ?>
                                        <p class="text-sm text-green-600 font-medium">
                                            <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>
                                            Photo uploaded successfully
                                        </p>
                                        <button type="button" id="change-photo" class="text-sm text-orange-600 hover:underline mt-2">
                                            Change photo
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="sitters_image_url" id="sitters-image-url" value="<?php echo $existing_sitter ? htmlspecialchars($existing_sitter['image_url']) : ''; ?>">
                            </div>

                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <h4 class="font-semibold text-amber-800 flex items-center gap-2 mb-2">
                                    <i data-lucide="lightbulb" class="w-4 h-4"></i>
                                    Photo Tips
                                </h4>
                                <ul class="text-sm text-amber-700 space-y-1">
                                    <li>• Use a clear, well-lit photo of yourself</li>
                                    <li>• Smile and look friendly and approachable</li>
                                    <li>• Photos with pets are great!</li>
                                    <li>• Avoid group photos or sunglasses</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-8">
                        <button type="button" class="prev-step px-8 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg transition-all duration-300 hover:border-orange-500 hover:text-orange-600 flex items-center gap-2">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Back</span>
                        </button>
                        <button type="button" class="next-step px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2">
                            <span>Review & Submit</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div class="glass-card rounded-3xl p-8 slide-in-right hidden" id="step-4">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-full flex items-center justify-center text-white">
                            <i data-lucide="eye" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Review Your Profile</h3>
                            <p class="text-gray-600">Double-check your information before submitting</p>
                        </div>
                    </div>

                    <!-- Sitter Profile Preview -->
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-6 border-2 border-orange-200 mb-8" id="profile-preview">
                        <div class="flex items-center gap-6 mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white text-2xl font-bold overflow-hidden" id="preview-avatar">
                                <?php if (!empty($existing_sitter['image_url'])): ?>
                                    <?php
                                        $imgR = $existing_sitter['image_url'];
                                        $isAbsR = preg_match('/^https?:\/\//i', $imgR);
                                        $scriptR = $_SERVER['SCRIPT_NAME'] ?? '';
                                        $posR = strpos($scriptR, '/views/');
                                        $basePrefixR = $posR !== false ? substr($scriptR, 0, $posR) : '';
                                        $srcR = $isAbsR ? $imgR : rtrim($basePrefixR, '/') . '/' . ltrim($imgR, '/');
                                    ?>
                                    <img src="<?php echo htmlspecialchars($srcR); ?>" alt="Profile" class="w-full h-full object-cover rounded-full">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p class="text-orange-600 font-medium">Professional Pet Sitter</p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        <?php echo htmlspecialchars($user['address']); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                        <span id="preview-contact"><?php echo htmlspecialchars($user['phone']); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                    <i data-lucide="heart" class="w-4 h-4 text-orange-500"></i>
                                    Pet Specialties
                                </h4>
                                <div class="flex flex-wrap gap-2" id="preview-specialties">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                    <i data-lucide="award" class="w-4 h-4 text-orange-500"></i>
                                    Experience Level
                                </h4>
                                <div class="text-gray-700" id="preview-experience-level">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-orange-500"></i>
                                About Me
                            </h4>
                            <div class="text-gray-700 bg-white/60 rounded-lg p-4" id="preview-experience">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-700">
                                <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>
                                <strong>Sitter ID:</strong> <span id="preview-sitter-id"><?php echo $existing_sitter ? $existing_sitter['sitter_id'] : 'PS' . str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Terms and Agreement -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-green-500"></i>
                            Terms & Agreement
                        </h4>
                        <div class="ladder space-y-4">
                            <div class="ladder-item">
                                <span class="ladder-dot"></span>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="agree_terms" required>
                                    <span class="checkmark"></span>
                                    <span class="text-sm">I agree to pawhabilin's <a href="#" class="text-orange-600 hover:underline">Terms of Service</a> and <a href="#" class="text-orange-600 hover:underline">Sitter Guidelines</a></span>
                                </label>
                            </div>
                            <div class="ladder-item">
                                <span class="ladder-dot"></span>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="agree_background" required>
                                    <span class="checkmark"></span>
                                    <span class="text-sm">I consent to background verification and identity confirmation</span>
                                </label>
                            </div>
                            <div class="ladder-item">
                                <span class="ladder-dot"></span>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="agree_liability" required>
                                    <span class="checkmark"></span>
                                    <span class="text-sm">I understand the liability terms and insurance coverage provided by pawhabilin</span>
                                </label>
                            </div>
                            <div class="ladder-item">
                                <span class="ladder-dot"></span>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="agree_marketing">
                                    <span class="checkmark"></span>
                                    <span class="text-sm">I'd like to receive updates about sitter opportunities and platform news</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" class="prev-step px-8 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg transition-all duration-300 hover:border-orange-500 hover:text-orange-600 flex items-center gap-2">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Back</span>
                        </button>
                        <div class="flex gap-4">
                            <button type="button" id="save-draft" class="px-8 py-3 border-2 border-orange-500 text-orange-600 font-semibold rounded-lg transition-all duration-300 hover:bg-orange-50 flex items-center gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Draft</span>
                            </button>
                            <button type="submit" id="submit-profile" class="px-12 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-bold rounded-lg transition-all duration-300 transform hover:scale-105 pulse-glow flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                <span><?php echo $is_update_mode ? 'Update Profile' : 'Become a Sitter'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-3xl p-8 max-w-md mx-4 text-center success-bounce">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check" class="w-12 h-12 text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-4">
                <?php echo $is_update_mode ? 'Profile Updated!' : 'Welcome to the Team!'; ?>
            </h3>
            <p class="text-gray-600 mb-6" id="success-message">
                <!-- Populated by JavaScript -->
            </p>
            <div class="flex gap-3">
                <a href="profile.php" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-300">
                    View Profile
                </a>
                <a href="index.php" class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white rounded-lg hover:from-orange-600 hover:to-amber-700 transition-all duration-300">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            initializeSitterForm();
            var editBtn = document.getElementById('edit-profile-btn');
            if (editBtn) {
                editBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    const anchor = document.getElementById('edit-profile-start');
                    if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        });

    let currentStep = <?php echo $is_update_mode ? '2' : '1'; ?>;
        const totalSteps = 4;

    function initializeSitterForm() {
            // Update mode detection
            const isUpdateMode = <?php echo $is_update_mode ? 'true' : 'false'; ?>;
            
            // Initialize step navigation
            setupStepNavigation();
            // Prevent accidental form submits via Enter key on intermediate steps
            setupEnterKeyProgression();
            
            // Initialize specialty selection
            setupSpecialtySelection();
            
            // Initialize experience level selection
            setupExperienceLevelSelection();
            
            // Initialize image upload
            setupImageUpload();
            
            // Initialize form validation
            setupFormValidation();
            
            // Sticky actions were removed; no setup needed
            
            // Auto-save functionality
            setupAutoSave();
            
            // Initialize preview updates
            setupPreviewUpdates();
            
            // Populate existing data if in update mode and ensure correct step visibility
            if (isUpdateMode) {
                // Force the UI to start on Step 2 (Pet Care Specialties) for edit mode
                currentStep = 2;
                const s2 = document.getElementById('step-2');
                if (s2) s2.classList.remove('hidden');
                const s1 = document.getElementById('step-1');
                if (s1) s1.classList.add('hidden');
                // Make sure other steps are hidden until navigated to
                const s3 = document.getElementById('step-3');
                const s4 = document.getElementById('step-4');
                if (s3) s3.classList.add('hidden');
                if (s4) s4.classList.add('hidden');
                populateExistingData();
                // Reflect progress and labels
                updateProgress();
                updateProgressBar();
            }
        }

        function setupStepNavigation() {
            // Next step buttons
            document.querySelectorAll('.next-step').forEach(button => {
                button.addEventListener('click', (e) => {
                    // Ensure no implicit form submission
                    e.preventDefault();
                    if (validateCurrentStep()) {
                        nextStep();
                    }
                });
            });

            // Previous step buttons
            document.querySelectorAll('.prev-step').forEach(button => {
                button.addEventListener('click', (e) => {
                    // Ensure no implicit form submission
                    e.preventDefault();
                    prevStep();
                });
            });

            // Toolbar navigation
            const tbNext = document.getElementById('toolbar-next');
            if (tbNext) tbNext.addEventListener('click', () => {
                if (currentStep < totalSteps) {
                    if (validateCurrentStep()) nextStep();
                } else {
                    const submit = document.getElementById('submit-profile');
                    if (submit) submit.click();
                }
            });

            // Allow clicking on completed steps to navigate back
            document.querySelectorAll('.progress-step').forEach(stepEl => {
                stepEl.setAttribute('tabindex', '0');
                stepEl.addEventListener('click', () => {
                    const target = parseInt(stepEl.getAttribute('data-step'));
                    if (!isNaN(target) && target <= currentStep) {
                        setCurrentStep(target);
                    }
                });
                stepEl.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const target = parseInt(stepEl.getAttribute('data-step'));
                        if (!isNaN(target) && target <= currentStep) {
                            setCurrentStep(target);
                        }
                    }
                });
            });
        }

        function setCurrentStep(step) {
            if (step < 1 || step > totalSteps) return;
            // Hide current
            const currentEl = document.getElementById(`step-${currentStep}`);
            if (currentEl) currentEl.classList.add('hidden');
            // Update index
            currentStep = step;
            // Show target
            const targetEl = document.getElementById(`step-${currentStep}`);
            if (targetEl) targetEl.classList.remove('hidden');
            // Update visuals
            updateProgress();
            updateProgressBar();
            // Scroll to top for visual continuity
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

    function nextStep() { if (currentStep < totalSteps) setCurrentStep(currentStep + 1); }

        function prevStep() {
            if (currentStep > 1) {
                // Hide current step
                document.getElementById(`step-${currentStep}`).classList.add('hidden');
                
                // Show previous step
                currentStep--;
                const prevStepElement = document.getElementById(`step-${currentStep}`);
                prevStepElement.classList.remove('hidden');
                
                // Update progress
                updateProgress();
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Update progress bar visual
                updateProgressBar();
            }
        }

        // Prevent Enter key from submitting the form before the final step
        function setupEnterKeyProgression() {
            const form = document.getElementById('sitter-form');
            if (!form) return;
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const target = e.target;
                    const tag = (target && target.tagName) ? target.tagName.toUpperCase() : '';
                    const isTextarea = tag === 'TEXTAREA';
                    const isButton = tag === 'BUTTON';
                    const isSubmitButton = isButton && (target.type === 'submit');
                    // Allow Enter in textarea and on the final submit button
                    if (isTextarea || isSubmitButton) return;
                    // If not on final step, prevent default submit and go to next step if valid
                    if (currentStep < totalSteps) {
                        e.preventDefault();
                        if (validateCurrentStep()) nextStep();
                    }
                }
            });
        }

        function updateProgress() {
            // Update progress steps
            for (let i = 1; i <= totalSteps; i++) {
                const step = document.querySelector(`[data-step="${i}"]`);
                const connector = step.nextElementSibling;
                
                if (i < currentStep) {
                    step.classList.add('completed');
                    step.classList.remove('active');
                    if (connector && connector.classList.contains('progress-connector')) {
                        connector.classList.add('completed');
                    }
                } else if (i === currentStep) {
                    step.classList.add('active');
                    step.classList.remove('completed');
                } else {
                    step.classList.remove('active', 'completed');
                    if (connector && connector.classList.contains('progress-connector')) {
                        connector.classList.remove('completed');
                    }
                }
            }
        }

        function updateProgressBar() {
            const progressFill = document.getElementById('progress-fill');
            const currentStepSpan = document.getElementById('current-step');
            const percentage = (currentStep / totalSteps) * 100;
            if (progressFill) progressFill.style.width = percentage + '%';
            if (currentStepSpan) currentStepSpan.textContent = String(currentStep);
        }

        function setupSpecialtySelection() {
            document.querySelectorAll('.specialty-tag').forEach(tag => {
                tag.addEventListener('click', () => {
                    const checkbox = tag.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    tag.classList.toggle('selected', checkbox.checked);
                    
                    // Update preview
                    updateSpecialtyPreview();
                });
            });
        }

        function setupExperienceLevelSelection() {
            // Contact method selection
            document.querySelectorAll('input[name="contact_method"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateExperienceLevelSelection('contact_method');
                });
            });

            // Experience years selection
            document.querySelectorAll('input[name="experience_years"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateExperienceLevelSelection('experience_years');
                    updateExperiencePreview();
                });
            });

            // Pet sizes selection
            document.querySelectorAll('input[name="pet_sizes[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateExperienceLevelSelection('pet_sizes');
                });
            });
        }

        function updateExperienceLevelSelection(groupName) {
            document.querySelectorAll(`input[name="${groupName}"], input[name="${groupName}[]"]`).forEach(input => {
                const label = input.closest('.experience-level');
                if (label) {
                    label.classList.toggle('selected', input.checked);
                }
            });
        }

        function setupImageUpload() {
            const uploadArea = document.getElementById('image-upload-area');
            const fileInput = document.getElementById('profile-image-input');
            const placeholder = document.getElementById('upload-placeholder');
            const preview = document.getElementById('image-preview');
            const changePhotoBtn = document.getElementById('change-photo');
            const triggerUpload = document.getElementById('trigger-upload');
            const hiddenUrl = document.getElementById('sitters-image-url');

            uploadArea.addEventListener('click', (e) => {
                // Avoid triggering when clicking on the preview actions
                if (e.target.closest('#image-preview')) return;
                fileInput.click();
            });
            if (triggerUpload) {
                triggerUpload.addEventListener('click', (e) => {
                    e.stopPropagation();
                    fileInput.click();
                });
            }
            
            changePhotoBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadProfileImage(file, { preview, placeholder, uploadArea, hiddenUrl });
                }
            });
        }

        async function uploadProfileImage(file, ctx) {
            const { preview, placeholder, uploadArea, hiddenUrl } = ctx;
            // Basic client-side validation
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload an image (JPG, PNG, GIF, WEBP).');
                return;
            }
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('Image too large. Max 5MB.');
                return;
            }

            // Show temporary loading state
            const loader = document.createElement('div');
            loader.className = 'mt-2 text-sm text-gray-500 flex items-center gap-2';
            loader.innerHTML = '<span class="loading-spinner"></span><span>Uploading photo…</span>';
            preview.classList.remove('hidden');
            preview.appendChild(loader);

            try {
                const formData = new FormData();
                formData.append('profile_image', file);
                const resp = await fetch('../../controllers/users/sittercontroller.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                const data = await resp.json();
                if (!data.success) throw new Error(data.message || 'Upload failed');

                // Update preview
                const img = preview.querySelector('img') || document.createElement('img');
                img.src = data.url; // absolute URL for browser display
                img.className = 'w-32 h-32 rounded-full object-cover mx-auto mb-4';
                img.alt = 'Profile preview';
                if (!preview.querySelector('img')) {
                    preview.insertBefore(img, preview.firstChild);
                }

                // Update UI
                placeholder.classList.add('hidden');
                uploadArea.classList.add('has-image');
                hiddenUrl.value = data.path || data.url; // store relative path if provided
                updateAvatarPreview(data.url);
            } catch (err) {
                alert('Upload error: ' + err.message);
            } finally {
                if (loader && loader.parentNode) loader.parentNode.removeChild(loader);
            }
        }

        function updateAvatarPreview(imageSrc) {
            const previewAvatar = document.getElementById('preview-avatar');
            previewAvatar.innerHTML = `<img src="${imageSrc}" alt="Profile" class="w-full h-full rounded-full object-cover">`;
        }

        function setupFormValidation() {
            const form = document.getElementById('sitter-form');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!validateAllSteps()) {
                    return;
                }
                
                await submitForm();
            });
        }

        function validateCurrentStep() {
            const currentStepElement = document.getElementById(`step-${currentStep}`);
            const requiredFields = currentStepElement.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                    
                    // Remove error styling after user starts typing
                    field.addEventListener('input', function() {
                        this.style.borderColor = '#e5e7eb';
                    }, { once: true });
                }
            });
            
            // Special validation for step 2 (specialties)
            if (currentStep === 2) {
                const selectedSpecialties = document.querySelectorAll('input[name="sitter_specialty[]"]:checked');
                if (selectedSpecialties.length === 0) {
                    isValid = false;
                    alert('Please select at least one pet specialty.');
                }
            }
            
            return isValid;
        }

        function validateAllSteps() {
            // Validate all required fields across all steps
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                }
            });
            
            // Check specialties
            const selectedSpecialties = document.querySelectorAll('input[name="sitter_specialty[]"]:checked');
            if (selectedSpecialties.length === 0) {
                isValid = false;
                alert('Please select at least one pet specialty.');
            }
            
            // Check terms agreement
            const termsCheckboxes = document.querySelectorAll('input[name^="agree_"]:not([name="agree_marketing"])');
            termsCheckboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                alert('Please complete all required fields and agree to the terms.');
            }
            
            return isValid;
        }

        async function submitForm() {
            const submitBtn = document.getElementById('submit-profile');
            const originalContent = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = `
                <div class="loading-spinner"></div>
                <span>Processing...</span>
            `;
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(document.getElementById('sitter-form'));
                
                const response = await fetch('../../controllers/users/sittercontroller.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal(result);
                } else {
                    throw new Error(result.message || 'Something went wrong');
                }
                
            } catch (error) {
                alert('Error: ' + error.message);
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        }

        function showSuccessModal(result) {
            const modal = document.getElementById('success-modal');
            const message = document.getElementById('success-message');
            
            message.textContent = result.message + ` Your Sitter ID is: ${result.sitter_id}`;
            modal.classList.remove('hidden');
            
            // Confetti effect (simple version)
            setTimeout(() => {
                window.location.href = 'profile.php';
            }, 3000);
        }

        // Removed setupStickyActions

        function setupAutoSave() {
            // Auto-save functionality
            let autoSaveTimer;
            
            document.getElementById('sitter-form').addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(saveFormData, 2000);
            });
            
            // Manual save buttons
            const btnSave = document.getElementById('save-draft');
            if (btnSave) btnSave.addEventListener('click', saveFormData);
            const tbSave = document.getElementById('toolbar-save');
            if (tbSave) tbSave.addEventListener('click', saveFormData);
        }

        function saveFormData() {
            const formData = new FormData(document.getElementById('sitter-form'));
            // Save to localStorage as backup
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('sitter_form_draft', JSON.stringify(data));
            
            // Show save confirmation
            showSaveConfirmation();
        }

        function showSaveConfirmation() {
            // Create a temporary notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.innerHTML = '<i data-lucide="check" class="w-4 h-4 inline mr-2"></i>Draft saved';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }

        function setupPreviewUpdates() {
            // Update preview as user types
            document.querySelectorAll('input, textarea, select').forEach(input => {
                input.addEventListener('input', updatePreview);
                input.addEventListener('change', updatePreview);
            });
        }

        function updatePreview() {
            updateContactPreview();
            updateSpecialtyPreview();
            updateExperiencePreview();
        }

        function updateContactPreview() {
            const contactInput = document.querySelector('input[name="sitters_contact"]');
            const previewContact = document.getElementById('preview-contact');
            
            if (contactInput && previewContact) {
                previewContact.textContent = contactInput.value || '<?php echo htmlspecialchars($user['phone']); ?>';
            }
        }

        function updateSpecialtyPreview() {
            const selectedSpecialties = document.querySelectorAll('input[name="sitter_specialty[]"]:checked');
            const previewContainer = document.getElementById('preview-specialties');
            
            previewContainer.innerHTML = '';
            
            selectedSpecialties.forEach(checkbox => {
                const badge = document.createElement('span');
                badge.className = 'px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium';
                badge.textContent = checkbox.value;
                previewContainer.appendChild(badge);
            });
            
            if (selectedSpecialties.length === 0) {
                previewContainer.innerHTML = '<span class="text-gray-500 italic">No specialties selected</span>';
            }
        }

        function updateExperiencePreview() {
            const experienceTextarea = document.querySelector('textarea[name="sitters_bio"]');
            const yearsInput = document.querySelector('input[name="years_experience"]');
            
            const previewExperience = document.getElementById('preview-experience');
            const previewLevel = document.getElementById('preview-experience-level');
            
            if (experienceTextarea && previewExperience) {
                previewExperience.textContent = experienceTextarea.value || 'No bio provided';
            }
            if (previewLevel) {
                previewLevel.textContent = yearsInput && yearsInput.value !== '' ? `${yearsInput.value} year(s) of experience` : 'Years of experience not provided';
            }
        }

        function populateExistingData() {
            // This would be populated with actual data in a real implementation
            // For now, we'll use the PHP data that's already in the form
            updatePreview();
        }

        // Initialize everything when the page loads
    updateProgress();
    updateProgressBar();
    </script>
</body>
</html>