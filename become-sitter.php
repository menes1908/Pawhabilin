<?php
// Handle sitter application as a user registration (saves to `users`)
@session_start();
require_once __DIR__ . '/database.php';

$regErrors = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];
$regValues = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => ''
];
$regSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sitter_register'])) {
    $isAjax = (
        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
    );
    $regValues['first_name'] = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $regValues['last_name'] = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $regValues['username'] = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $regValues['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($regValues['first_name'] === '') { $regErrors['first_name'] = 'First name is required.'; }
    if ($regValues['last_name'] === '') { $regErrors['last_name'] = 'Last name is required.'; }
    if ($regValues['username'] === '') {
        $regErrors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_\.\-]{3,30}$/', $regValues['username'])) {
        $regErrors['username'] = 'Use 3-30 chars: letters, numbers, _ . - only.';
    }
    if ($regValues['email'] === '' || !filter_var($regValues['email'], FILTER_VALIDATE_EMAIL)) {
        $regErrors['email'] = 'Enter a valid email address.';
    }

    $policy = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
    if ($password === '') { $regErrors['password'] = 'Password is required.'; }
    elseif (!preg_match($policy, $password)) { $regErrors['password'] = 'Min 8 chars with 1 uppercase, 1 lowercase, 1 number, 1 special.'; }
    if ($confirm_password === '') { $regErrors['confirm_password'] = 'Confirm your password.'; }
    elseif ($password !== $confirm_password) { $regErrors['confirm_password'] = 'Passwords do not match.'; }

    $hasErrors = array_filter($regErrors, fn($e) => $e !== '');
    if (!$hasErrors) {
        if (!$connections) {
            $regErrors['email'] = 'Database connection failed.';
        } else {
            $stmt = mysqli_prepare($connections, 'SELECT users_id, users_username, users_email FROM users WHERE users_username = ? OR users_email = ? LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $regValues['username'], $regValues['email']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    if (strcasecmp($row['users_username'], $regValues['username']) === 0) { $regErrors['username'] = 'Username already taken.'; }
                    if (strcasecmp($row['users_email'], $regValues['email']) === 0) { $regErrors['email'] = 'Email already registered.'; }
                }
                mysqli_stmt_close($stmt);
            } else {
                $regErrors['email'] = 'Could not prepare user lookup.';
            }
        }
    }

    $hasErrors = array_filter($regErrors, fn($e) => $e !== '');
    if (!$hasErrors) {
        $hash = $password; // Mirror existing registration behavior
        $role = '0';
        $stmt = mysqli_prepare($connections, 'INSERT INTO users (users_firstname, users_lastname, users_username, users_email, users_password_hash, users_role) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssss', $regValues['first_name'], $regValues['last_name'], $regValues['username'], $regValues['email'], $hash, $role);
            if (mysqli_stmt_execute($stmt)) {
                $regSuccess = true;
                $_SESSION['user_registered'] = true;
                $_SESSION['user_email'] = $regValues['email'];
                $_SESSION['user_name'] = $regValues['first_name'];
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'ok' => true,
                        'redirect' => 'login.php?registered=1&sitter=1'
                    ]);
                    exit();
                } else {
                    header('Location: login.php?registered=1&sitter=1');
                    exit();
                }
            } else {
                $regErrors['email'] = 'Application failed to save. Please try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $regErrors['email'] = 'Could not prepare application save.';
        }
    }

    // If AJAX request and there are errors, return JSON without reloading
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'errors' => $regErrors,
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Pet Sitter - pawhabilin</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Join pawhabilin as a professional pet sitter. Earn money caring for pets, set your own schedule, and be part of the Philippines' most trusted pet care community.">
    <meta name="keywords" content="pet sitter job, pet care career, dog walker, pet sitting Philippines, earn money pet sitting">
    
    <!-- Tailwind CSS & site globals -->
    <link rel="stylesheet" href="globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
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
        
        @keyframes bounce-gentle {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
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
        
        .bounce-gentle {
            animation: bounce-gentle 2s ease-in-out infinite;
        }
        
        .benefit-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .benefit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .benefit-card:hover::before {
            left: 100%;
        }
        
        .benefit-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            transition: all 0.3s ease;
        }
        
        .benefit-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .morphing-border {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .morphing-border::before {
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
        }
        
        /* Step indicator */
        .step-indicator {
            position: relative;
        }
        
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50px;
            width: 40px;
            height: 2px;
            background: linear-gradient(to right, #f97316, #fb923c);
        }
        
        .step-indicator:last-child::after {
            display: none;
        }
        
        /* Progress bar for requirements */
        .progress-bar {
            background: linear-gradient(to right, #10b981, #059669);
            height: 8px;
            border-radius: 4px;
            transition: width 0.5s ease;
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
        
        /* Form styles */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .form-label {
            position: absolute;
            top: 12px;
            left: 16px;
            color: #6b7280;
            transition: all 0.3s ease;
            pointer-events: none;
            background: #fff;
            padding: 0 4px;
        }
        
        .form-input:focus + .form-label,
        .form-input:not(:placeholder-shown) + .form-label {
            top: -8px;
            left: 12px;
            font-size: 12px;
            color: #f97316;
            font-weight: 600;
        }
        
        /* Service selection */
        .service-option {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }
        
        .service-option:hover {
            border-color: #f97316;
            background: #fef7f0;
        }
        
        .service-option.selected {
            border-color: #f97316;
            background: #fef7f0;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .step-indicator::after {
                display: none;
            }
        }
        
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <?php $basePrefix = ''; include __DIR__ . '/utils/header.php'; ?>

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
                <i data-lucide="users" class="w-20 h-20 text-white transform rotate-45"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="space-y-8 slide-in-up">
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium glass-effect">
                        <i data-lucide="briefcase" class="w-4 h-4 mr-2"></i>
                        Professional Pet Care Opportunity
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-shadow">
                        Become a Professional
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl">Pet Sitter</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        Turn your love for animals into a rewarding career. Join the Philippines' most trusted pet care 
                        platform and start earning while caring for adorable pets in your community.
                    </p>
                    
                    <div class="flex justify-center items-center pt-8">
                        <a href="#application-form" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-white text-orange-600 hover:bg-orange-50 h-14 px-8 transform hover:scale-105 hover:shadow-2xl">
                            <i data-lucide="user-check" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                            Start Your Application
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white relative">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="gift" class="w-3 h-3 mr-1"></i>
                    Why Join pawhabilin
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Amazing Benefits
                    </span>
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Join thousands of pet sitters who have turned their passion for animals into a flexible, 
                    rewarding career with pawhabilin.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                <!-- Flexible Schedule -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-blue-50 to-indigo-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="clock" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Flexible Schedule</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Work when you want, how you want. Set your own availability and choose the bookings 
                        that fit your lifestyle perfectly.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Set your own hours</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Choose your clients</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Work part-time or full-time</span>
                        </div>
                    </div>
                </div>

                <!-- Great Earnings -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-green-50 to-emerald-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="trending-up" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Great Earnings</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Earn competitive rates for doing what you love. Top sitters earn up to ₱50,000+ per month 
                        with our transparent pricing system.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>₱200-800 per hour</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Weekly payouts</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Tips and bonuses</span>
                        </div>
                    </div>
                </div>

                <!-- Professional Support -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-purple-50 to-violet-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-purple-500 to-violet-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="headphones" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-1">Professional Support</h3>
                    <div class="inline-flex items-center text-xs font-semibold text-orange-600 bg-orange-50 border border-orange-200 rounded-full px-2.5 py-0.5 mb-4">Coming Soon</div>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Our enhanced support and training hub is in progress. Core help is available today, with more resources rolling out soon.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>24/7 customer support</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Free training resources</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Insurance coverage</span>
                        </div>
                    </div>
                </div>

                <!-- Build Your Business -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-orange-50 to-red-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="building" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Build Your Business</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Grow your client base with our marketing tools, build lasting relationships, 
                        and create a sustainable pet care business.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Personal profile & branding</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Client review system</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Marketing support</span>
                        </div>
                    </div>
                </div>

                <!-- Safe & Secure -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-teal-50 to-cyan-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="shield-check" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Safe & Secure</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        All pet owners are verified, payments are secure, and you're covered by our 
                        comprehensive insurance policy.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Verified pet owners</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Secure payment system</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Liability protection</span>
                        </div>
                    </div>
                </div>

                <!-- Community Support -->
                <div class="benefit-card morphing-border bg-gradient-to-br from-pink-50 to-rose-50 p-8 text-center group cursor-pointer">
                    <div class="feature-icon w-20 h-20 bg-gradient-to-br from-pink-500 to-rose-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="users" class="w-10 h-10 text-white"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Community Support</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Join a community of passionate pet sitters, share experiences, get advice, 
                        and grow together in the pet care industry.
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Sitter community forums</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Mentorship programs</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Regular meetups & events</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="map" class="w-3 h-3 mr-1"></i>
                    How It Works
                </div>
                
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Your Journey to Becoming a
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Pet Care Pro</span>
                </h2>
                
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Sign up as a user, log in, then click “Become a Sitter Now” to complete your personal information.
                </p>
            </div>
            
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Step 1 -->
                    <div class="step-indicator text-center group">
                        <div class="glass-effect rounded-3xl p-8 hover:shadow-lg transition-all duration-300 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                                <span class="text-2xl font-bold text-white">1</span>
                            </div>
                            <h3 class="text-xl font-bold mb-4">Create a User Account</h3>
                            <p class="text-gray-600 leading-relaxed">Sign up to become a user on pawhabilin so you can access the sitter application.</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="step-indicator text-center group">
                        <div class="glass-effect rounded-3xl p-8 hover:shadow-lg transition-all duration-300 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                                <span class="text-2xl font-bold text-white">2</span>
                            </div>
                            <h3 class="text-xl font-bold mb-4">Login and Apply</h3>
                            <p class="text-gray-600 leading-relaxed">Login, then click “Become a Sitter Now.” You’ll be guided to the sitter form.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="step-indicator text-center group">
                        <div class="glass-effect rounded-3xl p-8 hover:shadow-lg transition-all duration-300 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                                <span class="text-2xl font-bold text-white">3</span>
                            </div>
                            <h3 class="text-xl font-bold mb-4">Add Personal Info</h3>
                            <p class="text-gray-600 leading-relaxed">Complete your personal information and experience so we can review your application.</p>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="step-indicator text-center group">
                        <div class="glass-effect rounded-3xl p-8 hover:shadow-lg transition-all duration-300 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                                <span class="text-2xl font-bold text-white">4</span>
                            </div>
                            <h3 class="text-xl font-bold mb-4">In Review</h3>
                            <p class="text-gray-600 leading-relaxed">We’ll review your details. Once approved, you can start accepting bookings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="clipboard-check" class="w-3 h-3 mr-1"></i>
                    Requirements
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    What You Need to Get Started
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    We maintain high standards to ensure the best care for pets. Here are the requirements 
                    to become a pawhabilin pet sitter.
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Basic Requirements -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-100">
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="user-check" class="w-6 h-6 text-blue-600"></i>
                            Basic Requirements
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Age 18 or older</p>
                                    <p class="text-sm text-gray-600">Must be legally eligible to work</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Valid government ID</p>
                                    <p class="text-sm text-gray-600">Driver's license, passport, or national ID</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Clean background check</p>
                                    <p class="text-sm text-gray-600">We'll help you complete this process</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Smartphone with internet</p>
                                    <p class="text-sm text-gray-600">For app access and communication</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preferred Qualifications -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 border border-green-100">
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="star" class="w-6 h-6 text-green-600"></i>
                            Preferred Qualifications
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <i data-lucide="plus-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Pet care experience</p>
                                    <p class="text-sm text-gray-600">Professional or personal experience preferred</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="plus-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Pet first aid certification</p>
                                    <p class="text-sm text-gray-600">We offer free training if you don't have it</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="plus-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">Transportation</p>
                                    <p class="text-sm text-gray-600">Car, motorcycle, or reliable public transport</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="plus-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                                <div>
                                    <p class="font-medium">References</p>
                                    <p class="text-sm text-gray-600">Professional or personal references helpful</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Progress Indicator -->
                <div class="mt-12 bg-gray-50 rounded-2xl p-8">
                    <h3 class="text-xl font-bold mb-6 text-center">Application Review Process</h3>
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold mb-2">1</div>
                            <p class="font-medium">Application Submitted</p>
                            <p class="text-sm text-gray-600">Instant confirmation</p>
                        </div>
                        <div class="hidden md:block flex-1 h-1 bg-gray-300 mx-4"></div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold mb-2">2</div>
                            <p class="font-medium">Background Check</p>
                            <p class="text-sm text-gray-600">1-2 business days</p>
                        </div>
                        <div class="hidden md:block flex-1 h-1 bg-gray-300 mx-4"></div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold mb-2">3</div>
                            <p class="font-medium">Profile Setup</p>
                            <p class="text-sm text-gray-600">30 minutes</p>
                        </div>
                        <div class="hidden md:block flex-1 h-1 bg-gray-300 mx-4"></div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold mb-2">4</div>
                            <p class="font-medium">Go Live!</p>
                            <p class="text-sm text-gray-600">Start earning</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Success Stories (temporarily hidden) -->
    <!--
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="trophy" class="w-3 h-3 mr-1"></i>
                    Success Stories
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Meet Our Top Pet Sitters
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Real stories from real sitters who have built successful pet care businesses with pawhabilin.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                

    <!-- Application Form -->
    <section id="application-form" class="py-20 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="clipboard-pen" class="w-3 h-3 mr-1"></i>
                    Application Form
                </div>
                
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Ready to Join Our
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Pet Care Team?</span>
                </h2>
                
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Fill out this application to start your journey as a professional pet sitter. 
                    We'll review your application and get back to you within 2-3 business days.
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <?php if (!empty(array_filter($regErrors))) { ?>
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 p-4">
                        Please fix the errors below and submit again.
                    </div>
                <?php } ?>
                <form class="glass-effect rounded-3xl p-8 md:p-12 space-y-8" method="POST" action="">
                    <input type="hidden" name="sitter_register" value="1">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="user" class="w-6 h-6 text-orange-500"></i>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <input type="text" name="first_name" value="<?=htmlspecialchars($regValues['first_name'])?>" class="form-input" placeholder=" " required>
                                <label class="form-label">First Name *</label>
                                <?php if ($regErrors['first_name']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['first_name']?></div><?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="text" name="last_name" value="<?=htmlspecialchars($regValues['last_name'])?>" class="form-input" placeholder=" " required>
                                <label class="form-label">Last Name *</label>
                                <?php if ($regErrors['last_name']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['last_name']?></div><?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="text" name="username" value="<?=htmlspecialchars($regValues['username'])?>" class="form-input" placeholder=" " required>
                                <label class="form-label">Username *</label>
                                <?php if ($regErrors['username']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['username']?></div><?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" value="<?=htmlspecialchars($regValues['email'])?>" class="form-input" placeholder=" " required>
                                <label class="form-label">Email Address *</label>
                                <?php if ($regErrors['email']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['email']?></div><?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="password" name="password" class="form-input" placeholder=" " required>
                                <label class="form-label">Password *</label>
                                <?php if ($regErrors['password']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['password']?></div><?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="password" name="confirm_password" class="form-input" placeholder=" " required>
                                <label class="form-label">Confirm Password *</label>
                                <?php if ($regErrors['confirm_password']) { ?><div class="mt-1 text-sm text-red-600"><?=$regErrors['confirm_password']?></div><?php } ?>
                            </div>
                        </div>
                        <p class="mt-6 text-sm text-gray-600">Additional sitter-specific info will be enabled soon. For now, create your sitter account using the fields above.</p>
                    </div>

                    <!-- Services Offered (coming soon) -->
                    <!-- <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="heart" class="w-6 h-6 text-orange-500"></i>
                            Services You'd Like to Offer
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="service-option" onclick="toggleService(this, 'pet-sitting')">
                                <input type="checkbox" name="services[]" value="pet-sitting" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="home" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Pet Sitting</h4>
                                        <p class="text-sm text-gray-600">In-home pet care while owners are away</p>
                                        <p class="text-sm font-medium text-orange-600">₱200-400/hour</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-option" onclick="toggleService(this, 'dog-walking')">
                                <input type="checkbox" name="services[]" value="dog-walking" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="footprints" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Dog Walking</h4>
                                        <p class="text-sm text-gray-600">Daily walks and exercise for dogs</p>
                                        <p class="text-sm font-medium text-orange-600">₱300-500/hour</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-option" onclick="toggleService(this, 'pet-grooming')">
                                <input type="checkbox" name="services[]" value="pet-grooming" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="scissors" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Pet Grooming</h4>
                                        <p class="text-sm text-gray-600">Bathing, brushing, and styling</p>
                                        <p class="text-sm font-medium text-orange-600">₱600-1200/session</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-option" onclick="toggleService(this, 'overnight-care')">
                                <input type="checkbox" name="services[]" value="overnight-care" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="moon" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Overnight Care</h4>
                                        <p class="text-sm text-gray-600">24-hour care and supervision</p>
                                        <p class="text-sm font-medium text-orange-600">₱1500-3000/night</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-option" onclick="toggleService(this, 'pet-transport')">
                                <input type="checkbox" name="services[]" value="pet-transport" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="car" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Pet Transportation</h4>
                                        <p class="text-sm text-gray-600">Vet visits, grooming appointments</p>
                                        <p class="text-sm font-medium text-orange-600">₱500-1000/trip</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-option" onclick="toggleService(this, 'pet-training')">
                                <input type="checkbox" name="services[]" value="pet-training" class="hidden">
                                <div class="flex items-center gap-4">
                                    <i data-lucide="graduation-cap" class="w-8 h-8 text-orange-500"></i>
                                    <div>
                                        <h4 class="font-semibold">Basic Pet Training</h4>
                                        <p class="text-sm text-gray-600">Obedience and behavior training</p>
                                        <p class="text-sm font-medium text-orange-600">₱800-1500/session</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Experience (coming soon) -->
                    <!-- <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="award" class="w-6 h-6 text-orange-500"></i>
                            Experience & Qualifications
                        </h3>
                        <div class="space-y-6">
                            <div class="form-group">
                                <select name="experience" class="form-input" required>
                                    <option value="" disabled selected hidden></option>
                                    <option value="no-experience">No professional experience</option>
                                    <option value="personal">Personal pet ownership only</option>
                                    <option value="1-2-years">1-2 years professional experience</option>
                                    <option value="3-5-years">3-5 years professional experience</option>
                                    <option value="5-plus-years">5+ years professional experience</option>
                                </select>
                                <label class="form-label">Pet Care Experience *</label>
                            </div>
                            
                            <div class="form-group">
                                <textarea name="experienceDescription" class="form-input" rows="4" placeholder=" "></textarea>
                                <label class="form-label">Describe your experience with pets</label>
                            </div>
                            
                            <div class="form-group">
                                <input type="text" name="certifications" class="form-input" placeholder=" ">
                                <label class="form-label">Certifications (Pet First Aid, etc.)</label>
                            </div>
                        </div>
                    </div> -->

                    <!-- Availability (coming soon) -->
                    <!-- <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="calendar" class="w-6 h-6 text-orange-500"></i>
                            Availability
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <select name="availability" class="form-input" required>
                                    <option value="" disabled selected hidden></option>
                                    <option value="part-time-weekends">Part-time (Weekends only)</option>
                                    <option value="part-time-evenings">Part-time (Evenings)</option>
                                    <option value="part-time-flexible">Part-time (Flexible)</option>
                                    <option value="full-time">Full-time</option>
                                </select>
                                <label class="form-label">Availability Type *</label>
                            </div>
                            
                            <div class="form-group">
                                <input type="number" name="hoursPerWeek" class="form-input" min="5" max="60" placeholder=" ">
                                <label class="form-label">Hours per week</label>
                            </div>
                        </div>
                    </div> -->

                    <!-- References (coming soon) -->
                    <!-- <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="users" class="w-6 h-6 text-orange-500"></i>
                            References (Optional)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-semibold">Reference 1</h4>
                                <div class="form-group">
                                    <input type="text" name="reference1Name" class="form-input" placeholder=" ">
                                    <label class="form-label">Full Name</label>
                                </div>
                                <div class="form-group">
                                    <input type="tel" name="reference1Phone" class="form-input" placeholder=" ">
                                    <label class="form-label">Phone Number</label>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="reference1Relationship" class="form-input" placeholder=" ">
                                    <label class="form-label">Relationship</label>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <h4 class="font-semibold">Reference 2</h4>
                                <div class="form-group">
                                    <input type="text" name="reference2Name" class="form-input" placeholder=" ">
                                    <label class="form-label">Full Name</label>
                                </div>
                                <div class="form-group">
                                    <input type="tel" name="reference2Phone" class="form-input" placeholder=" ">
                                    <label class="form-label">Phone Number</label>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="reference2Relationship" class="form-input" placeholder=" ">
                                    <label class="form-label">Relationship</label>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Additional Information (coming soon) -->
                    <!-- <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i data-lucide="file-text" class="w-6 h-6 text-orange-500"></i>
                            Additional Information
                        </h3>
                        <div class="space-y-6">
                            <div class="form-group">
                                <textarea name="motivation" class="form-input" rows="4" placeholder=" " required></textarea>
                                <label class="form-label">Why do you want to become a pet sitter? *</label>
                            </div>
                            
                            <div class="form-group">
                                <textarea name="specialSkills" class="form-input" rows="3" placeholder=" "></textarea>
                                <label class="form-label">Special skills or qualifications</label>
                            </div>
                            
                            <div class="form-group">
                                <select name="hasTransportation" class="form-input" required>
                                    <option value="" disabled selected hidden></option>
                                    <option value="yes-car">Yes, I have a car</option>
                                    <option value="yes-motorcycle">Yes, I have a motorcycle</option>
                                    <option value="public-transport">I use public transportation</option>
                                    <option value="no-transport">No reliable transportation</option>
                                </select>
                                <label class="form-label">Do you have reliable transportation? *</label>
                            </div>
                        </div>
                    </div> -->

                    <!-- Agreement (coming soon) -->
                    <!-- <div class="bg-white/60 rounded-xl p-6">
                        <div class="space-y-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="agreeTerms" class="w-5 h-5 text-orange-500 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 mt-1" required>
                                <span class="text-sm">
                                    I agree to pawhabilin's <a href="#" class="text-orange-600 hover:underline">Terms of Service</a> 
                                    and <a href="#" class="text-orange-600 hover:underline">Privacy Policy</a> *
                                </span>
                            </label>
                            
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="agreeBackground" class="w-5 h-5 text-orange-500 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 mt-1" required>
                                <span class="text-sm">
                                    I consent to a background check and verification process *
                                </span>
                            </label>
                            
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="agreeMarketing" class="w-5 h-5 text-orange-500 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 mt-1">
                                <span class="text-sm">
                                    I'd like to receive updates about pet sitter opportunities and tips
                                </span>
                            </label>
                        </div>
                    </div> -->

                    <!-- Submit Button -->
                    <div class="text-center pt-6">
                        <button type="submit" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-16 px-12 transform hover:scale-105 hover:shadow-2xl pulse-glow">
                            <i data-lucide="send" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                            Submit Application
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </button>
                        <p class="text-gray-600 text-sm mt-4">
                            We'll review your application and get back to you within 2-3 business days
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer (shared) -->
    <?php include __DIR__ . '/utils/footer.php'; ?>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
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
            document.querySelectorAll('.benefit-card, .glass-effect').forEach(el => {
                observer.observe(el);
            });
        });

        // Service selection toggle
        function toggleService(element, service) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            element.classList.toggle('selected', checkbox.checked);
        }

        // Removed earnings calculator

        // AJAX form submit: no page refresh on validation errors
        const appForm = document.querySelector('#application-form form');
        if (appForm) {
            const submitBtn = appForm.querySelector('button[type="submit"]');
            const errorBanner = document.createElement('div');
            errorBanner.className = 'mb-6 hidden rounded-xl border border-red-200 bg-red-50 text-red-700 p-4';
            errorBanner.textContent = 'Please fix the errors below and submit again.';
            appForm.parentElement.insertBefore(errorBanner, appForm);

            function setFieldError(name, message) {
                const input = appForm.querySelector(`[name="${name}"]`);
                if (!input) return;
                input.style.borderColor = message ? '#ef4444' : '#e5e7eb';
                let hint = input.parentElement.querySelector('.field-error');
                if (!hint) {
                    hint = document.createElement('div');
                    hint.className = 'field-error mt-1 text-sm text-red-600';
                    input.parentElement.appendChild(hint);
                }
                hint.textContent = message || '';
                hint.style.display = message ? '' : 'none';
            }

            appForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-70');
                }

                // Clear old errors
                ['first_name','last_name','username','email','password','confirm_password']
                    .forEach(n => setFieldError(n, ''));
                errorBanner.classList.add('hidden');

                try {
                    const formData = new FormData(appForm);
                    // Ensure sitter_register is present
                    formData.set('sitter_register', '1');
                    const res = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });
                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        const errs = (data && data.errors) ? data.errors : {};
                        Object.entries(errs).forEach(([k,v]) => { if (v) setFieldError(k, v); });
                        errorBanner.classList.remove('hidden');
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } catch (err) {
                    errorBanner.textContent = 'Something went wrong. Please try again.';
                    errorBanner.classList.remove('hidden');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-70');
                    }
                }
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

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

        // Form validation feedback
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        });
    </script>
</body>
</html>