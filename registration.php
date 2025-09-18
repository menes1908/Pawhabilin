<?php
session_start();
require_once __DIR__ . '/database.php';

$errors = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

$values = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['first_name'] = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $values['last_name'] = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $values['username'] = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $values['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($values['first_name'] === '') {
        $errors['first_name'] = 'First name is required.';
    }
    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Last name is required.';
    }
    if ($values['username'] === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_\.\-]{3,30}$/', $values['username'])) {
        $errors['username'] = 'Use 3-30 chars: letters, numbers, _ . - only.';
    }
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $policy = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (!preg_match($policy, $password)) {
        $errors['password'] = 'Min 8 chars with 1 uppercase, 1 lowercase, 1 number, 1 special.';
    }
    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    $hasErrors = array_filter($errors, fn($e) => $e !== '');

    if (!$hasErrors) {
        if (!$connections) {
            $errors['email'] = 'Database connection failed.';
        } else {
            $stmt = mysqli_prepare($connections, 'SELECT users_id, users_username, users_email FROM users WHERE users_username = ? OR users_email = ? LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $values['username'], $values['email']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    if (strcasecmp($row['users_username'], $values['username']) === 0) {
                        $errors['username'] = 'This username is already taken.';
                    }
                    if (strcasecmp($row['users_email'], $values['email']) === 0) {
                        $errors['email'] = 'This email is already registered.';
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors['email'] = 'Could not prepare user lookup.';
            }
        }
    }

    $hasErrors = array_filter($errors, fn($e) => $e !== '');

    if (!$hasErrors) {
        $hash = $password; 
        $role = '0';
        $stmt = mysqli_prepare($connections, 'INSERT INTO users (users_firstname, users_lastname, users_username, users_email, users_password_hash, users_role) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssss', $values['first_name'], $values['last_name'], $values['username'], $values['email'], $hash, $role);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['user_registered'] = true;
                $_SESSION['user_email'] = $values['email'];
                $_SESSION['user_name'] = $values['first_name'];
                header('Location: login.php?registered=1');
                exit();
            } else {
                $errors['email'] = 'Registration failed. Please try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors['email'] = 'Could not prepare registration.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join pawhabilin - Create Your Account</title>
    <link rel="stylesheet" href="styles/globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <style>
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(3deg); }
            75% { transform: rotate(-3deg); }
        }
        
        .animate-blob {
            animation: blob 7s infinite;
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .animate-wiggle {
            animation: wiggle 2s ease-in-out infinite;
        }
        
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        
        .animation-delay-4000 {
            animation-delay: 4s;
        }

        .delay-1000 {
            animation-delay: 1s;
        }

        .delay-2000 {
            animation-delay: 2s;
        }

        .delay-3000 {
            animation-delay: 3s;
        }

        /* Loading spinner */
        .spinner {
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Paw Print Balloon Animation */
        .paw-balloon {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            color: #f97316;
            font-size: 24px;
            opacity: 0;
            transform: scale(0);
            animation: pawBalloon 2.5s ease-out forwards;
        }

        @keyframes pawBalloon {
            0% {
                opacity: 0;
                transform: scale(0) rotate(0deg);
            }
            10% {
                opacity: 1;
                transform: scale(1.2) rotate(10deg);
            }
            20% {
                transform: scale(1) rotate(-5deg);
            }
            100% {
                opacity: 0;
                transform: scale(0.8) translateY(-150px) rotate(15deg);
            }
        }

        /* Different paw colors for variety */
        .paw-balloon.orange { color: #f97316; }
        .paw-balloon.amber { color: #d97706; }
        .paw-balloon.red { color: #dc2626; }
        .paw-balloon.pink { color: #ec4899; }
        .paw-balloon.yellow { color: #eab308; }
        .paw-balloon.green { color: #16a34a; }
        .paw-balloon.blue { color: #2563eb; }
        .paw-balloon.purple { color: #9333ea; }

        /* User type cards */
        .user-type-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .user-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.15);
        }

        .user-type-card.selected {
            border-color: #f97316;
            background: linear-gradient(135deg, #fed7aa 0%, #fef3c7 100%);
        }

        .user-type-card.selected .card-icon {
            color: #f97316;
            transform: scale(1.1);
        }

        /* Form field focus effects */
        .form-group {
            position: relative;
        }

        .form-input {
            transition: all 0.3s ease;
        }

        .form-input:focus + .form-icon {
            color: #f97316;
            transform: scale(1.1);
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            background: #e5e7eb;
        }

        .password-strength.weak {
            background: linear-gradient(to right, #ef4444 40%, #e5e7eb 40%);
        }

        .password-strength.medium {
            background: linear-gradient(to right, #f59e0b 70%, #e5e7eb 70%);
        }

        .password-strength.strong {
            background: linear-gradient(to right, #10b981 100%, #e5e7eb 100%);
        }

        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #fed7aa 0%, #fef3c7 30%, #fef9c3 60%, #ecfdf5 100%);
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Error message styling */
        .error-message {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        /* Success message styling */
        .success-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        /* Progress indicator */
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
            background: white;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            border-color: #f97316;
            background: #f97316;
            color: white;
        }

        .progress-step.completed {
            border-color: #10b981;
            background: #10b981;
            color: white;
        }

        .progress-line {
            height: 2px;
            background: #e5e7eb;
            flex: 1;
            transition: all 0.3s ease;
        }

        .progress-line.completed {
            background: #10b981;
        }

        /* Floating pet icons */
        .floating-pet {
            position: absolute;
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }

        /* Terms checkbox styling */
        .terms-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #f97316;
        }
    </style>
</head>
<body class="min-h-screen gradient-bg relative overflow-x-hidden overflow-y-auto">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Floating Paw Prints -->
        <div class="absolute top-20 left-20 opacity-15 animate-pulse">
            <i data-lucide="paw-print" class="w-16 h-16 text-orange-300 transform rotate-12"></i>
        </div>
        <div class="absolute top-40 right-32 opacity-15 animate-float delay-1000">
            <i data-lucide="paw-print" class="w-12 h-12 text-amber-300 transform -rotate-12"></i>
        </div>
        <div class="absolute bottom-40 left-16 opacity-15 animate-wiggle delay-2000">
            <i data-lucide="paw-print" class="w-20 h-20 text-green-200 transform rotate-45"></i>
        </div>
        <div class="absolute bottom-20 right-20 opacity-15 animate-pulse delay-3000">
            <i data-lucide="paw-print" class="w-14 h-14 text-blue-200 transform -rotate-45"></i>
        </div>
        
        <!-- Floating Hearts and Stars -->
        <div class="absolute top-60 left-40 opacity-20 animate-bounce">
            <i data-lucide="heart" class="w-8 h-8 text-red-300"></i>
        </div>
        <div class="absolute bottom-60 right-40 opacity-20 animate-bounce delay-1000">
            <i data-lucide="star" class="w-6 h-6 text-yellow-300"></i>
        </div>
        <div class="absolute top-80 right-60 opacity-20 animate-float delay-2000">
            <i data-lucide="heart" class="w-5 h-5 text-pink-300"></i>
        </div>

        <!-- Floating Pet Icons -->
        <div class="floating-pet top-32 left-40 delay-1000">
            <span class="text-4xl">🐕</span>
        </div>
        <div class="floating-pet top-64 right-20 delay-3000">
            <span class="text-3xl">🐱</span>
        </div>
        <div class="floating-pet bottom-32 left-60 delay-2000">
            <span class="text-3xl">🐦</span>
        </div>
        <div class="floating-pet bottom-64 right-80 delay-4000">
            <span class="text-2xl">🐰</span>
        </div>

        <!-- Gradient Orbs -->
        <div class="absolute top-0 left-0 w-72 h-72 bg-gradient-to-br from-orange-200 to-amber-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-gradient-to-br from-yellow-200 to-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-20 w-72 h-72 bg-gradient-to-br from-blue-200 to-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 border-b glass">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <a href="index" class="flex items-center space-x-2 group">
                    <div class="w-10 h-10 rounded-lg overflow-hidden transform group-hover:rotate-12 transition-transform duration-300">
                        <img src="pictures/Pawhabilin logo.png" alt="pawhabilin Logo" class="w-full h-full object-cover" />
                    </div>
                    <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </span>
                </a>
                
                <div class="flex items-center gap-3">
                    <span class="text-gray-600">Already have an account?</span>
                    <a href="login.php" class="inline-flex items-center px-4 py-2 border border-orange-200 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-300">
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-4rem)] p-4 py-8">
        <div class="w-full max-w-7xl grid lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left Side - Welcome & Features -->
            <div class="hidden lg:block space-y-8">
                <div class="space-y-6">
                    <div class="inline-flex items-center px-4 py-2 bg-white/80 text-orange-600 border border-orange-200 rounded-full">
                        <i data-lucide="star" class="w-4 h-4 mr-2"></i>
                        <span class="font-medium">Join 25,000+ Happy Pet Parents</span>
                    </div>
                    
                    <h1 class="text-4xl lg:text-5xl font-bold leading-tight">
                        <span class="block text-gray-800">Welcome to the</span>
                        <span class="block bg-gradient-to-r from-orange-600 via-amber-600 to-green-600 bg-clip-text text-transparent">
                            Pawhabilin Family
                        </span>
                    </h1>
                    
                    <p class="text-xl text-gray-600 leading-relaxed">
                        Create your account and discover trusted pet care in your neighborhood. 
                        Your furry friends deserve the best care possible.
                    </p>
                </div>

                <!-- Features List -->
                <div class="space-y-6">
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="shield" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Verified Pet Sitters</h3>
                            <p class="text-gray-600">All sitters are background-checked and verified for your peace of mind</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="heart" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Loving Care</h3>
                            <p class="text-gray-600">Your pets will receive personalized attention and love while you're away</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-sky-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="clock" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">24/7 Support</h3>
                            <p class="text-gray-600">Our team is always here to help when you need assistance</p>
                        </div>
                    </div>
                </div>

                
            </div>

            <!-- Right Side - Registration Form -->
            <div class="w-full max-w-lg mx-auto lg:mx-0">
                <div class="glass border-0 shadow-2xl rounded-3xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 p-8 text-white relative overflow-hidden">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative z-10 text-center space-y-3">
                            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto backdrop-blur-sm">
                                <i data-lucide="user-plus" class="w-10 h-10 text-white"></i>
                            </div>
                            <h2 class="text-3xl font-bold">Join pawhabilin!</h2>
                            <p class="text-orange-100 text-lg">Create your account in just a few steps</p>
                        </div>
                        
                        <!-- Decorative Elements -->
                        <div class="absolute top-4 right-4 opacity-20">
                            <i data-lucide="paw-print" class="w-8 h-8 text-white transform rotate-12"></i>
                        </div>
                        <div class="absolute bottom-4 left-4 opacity-20">
                            <i data-lucide="heart" class="w-6 h-6 text-white transform -rotate-12"></i>
                        </div>
                        <div class="absolute top-1/2 left-8 opacity-15">
                            <i data-lucide="star" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <?php if (isset($error_message)): ?>
                            <div class="error-message">
                                <i data-lucide="alert-circle" class="w-4 h-4 inline mr-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Social Registration Buttons -->
                        <div class="space-y-3">
                            <button class="w-full h-12 border border-gray-200 hover:border-orange-300 hover:bg-orange-50 rounded-lg transition-all duration-300 group bg-white">
                                <div class="flex items-center justify-center gap-3">
                                    <i data-lucide="chrome" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                                    <span class="font-medium">Continue with Google</span>
                                </div>
                            </button>
                            
                            <button class="w-full h-12 border border-gray-200 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-all duration-300 group bg-white">
                                <div class="flex items-center justify-center gap-3">
                                    <i data-lucide="facebook" class="w-5 h-5 text-blue-600 group-hover:scale-110 transition-transform duration-300"></i>
                                    <span class="font-medium">Continue with Facebook</span>
                                </div>
                            </button>
                        </div>

                        <!-- Divider -->
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white text-gray-600 font-medium">or create account with email</span>
                            </div>
                        </div>

                        <!-- Registration Form -->
                        <form method="POST" action="registration.php" class="space-y-5" id="registrationForm" novalidate>
                            <!-- First Name -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="first_name">First Name *</label>
                                <div class="form-group relative">
                                    <i data-lucide="user" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        id="first_name"
                                        name="first_name"
                                        type="text"
                                        placeholder="Enter your first name"
                                        value="<?php echo htmlspecialchars($values['first_name'] ?? ''); ?>"
                                        class="form-input w-full pl-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                    />
                                </div>
                                <?php if (!empty($errors['first_name'])): ?>
                                    <div class="text-xs text-red-500"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Last Name -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="last_name">Last Name *</label>
                                <div class="form-group relative">
                                    <i data-lucide="user" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        id="last_name"
                                        name="last_name"
                                        type="text"
                                        placeholder="Enter your last name"
                                        value="<?php echo htmlspecialchars($values['last_name'] ?? ''); ?>"
                                        class="form-input w-full pl-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                    />
                                </div>
                                <?php if (!empty($errors['last_name'])): ?>
                                    <div class="text-xs text-red-500"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Username -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="username">Username *</label>
                                <div class="form-group relative">
                                    <i data-lucide="at-sign" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        id="username"
                                        name="username"
                                        type="text"
                                        placeholder="Choose a username"
                                        value="<?php echo htmlspecialchars($values['username'] ?? ''); ?>"
                                        class="form-input w-full pl-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                    />
                                </div>
                                <?php if (!empty($errors['username'])): ?>
                                    <div class="text-xs text-red-500"><?php echo htmlspecialchars($errors['username']); ?></div>
                                <?php else: ?>
                                    <div class="text-xs text-gray-500">3-30 chars. Letters, numbers, underscore, dot, or hyphen.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="email">Email Address *</label>
                                <div class="form-group relative">
                                    <i data-lucide="mail" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        placeholder="Enter your email address"
                                        value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                                        class="form-input w-full pl-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                    />
                                </div>
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="text-xs text-red-500"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Password -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="passwordField">Password *</label>
                                <div class="form-group relative">
                                    <i data-lucide="lock" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        name="password"
                                        type="password"
                                        id="passwordField"
                                        placeholder="Create a strong password"
                                        class="form-input w-full pl-12 pr-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                        oninput="checkPasswordStrength(this.value)"
                                    />
                                    <button
                                        type="button"
                                        onclick="togglePassword('passwordField', 'eyeIcon1')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors duration-300"
                                    >
                                        <i data-lucide="eye" id="eyeIcon1" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <?php if (!empty($errors['password'])): ?>
                                    <div class="text-xs text-red-500"><?php echo htmlspecialchars($errors['password']); ?></div>
                                <?php else: ?>
                                    <div class="text-xs text-gray-500" id="passwordHint">Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-gray-700" for="confirmPasswordField">Confirm Password *</label>
                                <div class="form-group relative">
                                    <i data-lucide="lock" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-all duration-300"></i>
                                    <input
                                        name="confirm_password"
                                        type="password"
                                        id="confirmPasswordField"
                                        placeholder="Confirm your password"
                                        class="form-input w-full pl-12 pr-12 h-12 border border-gray-200 rounded-lg transition-all duration-300 outline-none bg-white"
                                        required
                                        oninput="checkPasswordMatch()"
                                    />
                                    <button
                                        type="button"
                                        onclick="togglePassword('confirmPasswordField', 'eyeIcon2')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors duration-300"
                                    >
                                        <i data-lucide="eye" id="eyeIcon2" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <div class="text-xs text-red-500" id="passwordMatchHint"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                                <?php else: ?>
                                    <div class="text-xs text-gray-500" id="passwordMatchHint"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Register Button -->
                            <button
                                type="submit"
                                id="registerBtn"
                                class="w-full h-12 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed group"
                            >
                                <div class="flex items-center justify-center gap-2" id="btnContent">
                                    <span>Create My Account</span>
                                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                                </div>
                            </button>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center pt-4 border-t border-gray-100">
                            <p class="text-sm text-gray-600">
                                Already have an account? 
                                <a 
                                    href="login.php" 
                                    class="text-orange-600 hover:text-orange-700 font-semibold hover:underline transition-all duration-300"
                                >
                                    Sign in here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Trust Indicators -->
                <div class="mt-8 text-center space-y-4">
                    <div class="flex items-center justify-center gap-6 text-sm text-gray-600">
                        <div class="flex items-center gap-1">
                            <i data-lucide="shield" class="w-4 h-4 text-green-500"></i>
                            <span>Secure Registration</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i data-lucide="heart" class="w-4 h-4 text-red-500"></i>
                            <span>Join 25K+ Members</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i data-lucide="paw-print" class="w-4 h-4 text-orange-500"></i>
                            <span>Pet Care Community</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // User type selection
        function selectUserType(type, element) {
            // Remove selected class from all cards
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Update radio button
            element.querySelector('input[type="radio"]').checked = true;
            
            // Refresh icons
            lucide.createIcons();
        }

        // Initialize first card as selected
        document.addEventListener('DOMContentLoaded', function() {
            const firstCard = document.querySelector('.user-type-card');
            if (firstCard) {
                firstCard.classList.add('selected');
            }
        });

        // Password visibility toggle
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordField.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            
            // Refresh the icon
            lucide.createIcons();
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthIndicator = document.getElementById('passwordStrength');
            const hintElement = document.getElementById('passwordHint');
            
            let strength = 0;
            let hints = [];
            
            if (password.length >= 8) strength++;
            else hints.push('at least 8 characters');
            
            if (/[a-z]/.test(password)) strength++;
            else hints.push('lowercase letter');
            
            if (/[A-Z]/.test(password)) strength++;
            else hints.push('uppercase letter');
            
            if (/\d/.test(password)) strength++;
            else hints.push('number');
            
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            else hints.push('special character');
            
            // Update strength indicator
            strengthIndicator.className = 'password-strength';
            if (strength <= 2) {
                strengthIndicator.classList.add('weak');
                hintElement.textContent = 'Weak password. Add: ' + hints.slice(0, 2).join(', ');
                hintElement.className = 'text-xs text-red-500';
            } else if (strength <= 3) {
                strengthIndicator.classList.add('medium');
                hintElement.textContent = 'Medium password. Consider adding: ' + hints.slice(0, 1).join(', ');
                hintElement.className = 'text-xs text-yellow-600';
            } else {
                strengthIndicator.classList.add('strong');
                hintElement.textContent = 'Strong password! 🎉';
                hintElement.className = 'text-xs text-green-600';
            }
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('passwordField').value;
            const confirmPassword = document.getElementById('confirmPasswordField').value;
            const hintElement = document.getElementById('passwordMatchHint');
            
            if (confirmPassword === '') {
                hintElement.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                hintElement.textContent = 'Passwords match! ✓';
                hintElement.className = 'text-xs text-green-600';
            } else {
                hintElement.textContent = 'Passwords do not match';
                hintElement.className = 'text-xs text-red-500';
            }
        }

        // Form submission with loading state
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const registerBtn = document.getElementById('registerBtn');
            const btnContent = document.getElementById('btnContent');
            
            // Show loading state
            registerBtn.disabled = true;
            btnContent.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="spinner"></div>
                    <span>Creating Account...</span>
                </div>
            `;
        });

        // Add focus effects to form inputs
        const formInputs = document.querySelectorAll('.form-input');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.form-icon').style.color = '#f97316';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('.form-icon').style.color = '#9ca3af';
                }
            });
        });

        // ===== PAW PRINT BALLOON CLICK EFFECT =====
        const pawColors = ['orange', 'amber', 'red', 'pink', 'yellow', 'green', 'blue', 'purple'];
        
        // Add click event listener to the entire document
        document.addEventListener('click', function(e) {
            createPawBalloon(e.clientX, e.clientY);
        });

        // Function to create paw print balloon effect
        function createPawBalloon(x, y) {
            const pawCount = Math.floor(Math.random() * 4) + 2; // 2-5 paws for registration
            
            for (let i = 0; i < pawCount; i++) {
                setTimeout(() => {
                    const paw = document.createElement('div');
                    paw.innerHTML = '<i data-lucide="paw-print"></i>';
                    paw.className = `paw-balloon ${pawColors[Math.floor(Math.random() * pawColors.length)]}`;
                    
                    const offsetX = (Math.random() - 0.5) * 120;
                    const offsetY = (Math.random() - 0.5) * 60;
                    
                    paw.style.left = (x + offsetX) + 'px';
                    paw.style.top = (y + offsetY) + 'px';
                    
                    const rotation = Math.floor(Math.random() * 360);
                    paw.style.transform = `rotate(${rotation}deg)`;
                    
                    document.body.appendChild(paw);
                    
                    lucide.createIcons();
                    
                    setTimeout(() => {
                        if (paw.parentNode) {
                            paw.parentNode.removeChild(paw);
                        }
                    }, 2500);
                }, i * 150);
            }
        }

        // Special effects for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const interactiveElements = document.querySelectorAll('button, .user-type-card, a[href], input');
            
            interactiveElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const rect = this.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    
                    createPawBalloon(centerX, centerY);
                    
                    // Extra effects for special elements
                    if (this.id === 'registerBtn') {
                        // Registration celebration
                        for (let i = 0; i < 12; i++) {
                            setTimeout(() => {
                                const angle = (i * 30) * (Math.PI / 180);
                                const distance = 80;
                                const pawX = centerX + Math.cos(angle) * distance;
                                const pawY = centerY + Math.sin(angle) * distance;
                                createPawBalloon(pawX, pawY);
                            }, i * 100);
                        }
                    }
                });
            });
        });

        // Refresh icons periodically
        setInterval(() => {
            lucide.createIcons();
        }, 1000);
    </script>
</body>
</html>