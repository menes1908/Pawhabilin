<?php
session_start();
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database.php';

$email = '';
$success_message = '';

// Show success after registration
if (isset($_GET['registered'])) {
    if (isset($_GET['sitter'])) {
        $success_message = 'Your sitter account has been created successfully. Please sign in to continue.';
    } else {
        $success_message = 'Your account has been created successfully. Please sign in to continue.';
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error_message = 'Please fill in all fields.';
    } elseif (!$connections) {
        $error_message = 'Database connection failed.';
    } else {
    $stmt = mysqli_prepare($connections, 'SELECT users_id, users_firstname, users_lastname, users_username, users_email, users_image_url, users_password_hash, users_role FROM users WHERE users_email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                // Compare plain text password as per current storage
                if ($password === $row['users_password_hash']) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user'] = [
                        'users_id' => (int)$row['users_id'],
                        'users_firstname' => (string)$row['users_firstname'],
                        'users_lastname' => (string)$row['users_lastname'],
                        'users_username' => (string)$row['users_username'],
                        'users_email' => (string)$row['users_email'],
                        'users_image_url' => (string)($row['users_image_url'] ?? ''),
                        'users_role' => (string)$row['users_role'],
                    ];

                    // Role-based redirect: 1 => admin dashboard, else user dashboard
                    $role = (string)$row['users_role'];
                    if ($role === '1' || (int)$role === 1) {
                        header('Location: views/admin/admin.php');
                    } else {
                        $redirect = isset($_GET['redirect']) ? (string)$_GET['redirect'] : '';
                        if ($redirect !== '') {
                            header('Location: ' . $redirect);
                        } else {
                            header('Location: views/users/index.php');
                        }
                    }
                    exit();
                } else {
                    $error_message = 'Invalid email or password.';
                }
            } else {
                $error_message = 'Invalid email or password.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - pawhabilin</title>
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
        
        .animate-blob {
            animation: blob 7s infinite;
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

        /* Social button hover effects */
        .social-btn {
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-1px);
        }

        .social-btn:hover .social-icon {
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
        }

        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #fed7aa 0%, #fef3c7 50%, #fef9c3 100%);
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
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
            background: #dcfce7; /* emerald-100 */
            border: 1px solid #86efac; /* emerald-300 */
            color: #16a34a; /* green-600 */
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="min-h-screen gradient-bg relative overflow-x-hidden overflow-y-auto">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Floating Paw Prints -->
        <div class="absolute top-20 left-20 opacity-20 animate-pulse">
            <i data-lucide="paw-print" class="w-16 h-16 text-orange-300 transform rotate-12"></i>
        </div>
        <div class="absolute top-40 right-32 opacity-20 animate-pulse delay-1000">
            <i data-lucide="paw-print" class="w-12 h-12 text-amber-300 transform -rotate-12"></i>
        </div>
        <div class="absolute bottom-40 left-16 opacity-20 animate-pulse delay-2000">
            <i data-lucide="paw-print" class="w-20 h-20 text-orange-200 transform rotate-45"></i>
        </div>
        <div class="absolute bottom-20 right-20 opacity-20 animate-pulse delay-3000">
            <i data-lucide="paw-print" class="w-14 h-14 text-amber-200 transform -rotate-45"></i>
        </div>
        
        <!-- Floating Hearts -->
        <div class="absolute top-60 left-40 opacity-30 animate-bounce">
            <i data-lucide="heart" class="w-8 h-8 text-red-300"></i>
        </div>
        <div class="absolute bottom-60 right-40 opacity-30 animate-bounce delay-1000">
            <i data-lucide="heart" class="w-6 h-6 text-pink-300"></i>
        </div>

        <!-- Gradient Orbs -->
        <div class="absolute top-0 left-0 w-72 h-72 bg-gradient-to-br from-orange-200 to-amber-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-gradient-to-br from-yellow-200 to-orange-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-20 w-72 h-72 bg-gradient-to-br from-amber-200 to-yellow-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
                        <img src="./pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                    </div>
                    <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </span>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="index" class="text-muted-foreground hover:text-foreground transition-colors">About</a>
                    <!-- Pet Sitter Dropdown -->
                    <div class="relative" id="petsitterWrapper">
                        
                        <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Pet Sitter
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="find-sitters" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Find a Pet Sitter</a>
                                <a href="views/users/become_sitter" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Become a Sitter</a>
                            </div>
                        </div>
                    </div>

                    <a href="shop" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    
                    
                    <div class="relative" id="appointmentsWrapper">
                        <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Appointments
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="views/users/book_grooming" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                                <a href="views/users/book_appointment" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
                            </div>
                        </div>
                    </div>

                    <a href="views/users/subscriptions" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>

                    
                    <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="login" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                        Log In
                    </a>
                    <a href="registration" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
                        Sign Up
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-4rem)] p-4">
        <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left Side - Illustration -->
            <div class="hidden lg:block space-y-8">
                <div class="space-y-4">
                    <div class="inline-flex items-center px-3 py-1 bg-white/80 text-orange-600 border border-orange-200 rounded-full text-sm">
                        <i data-lucide="shield" class="w-3 h-3 mr-1"></i>
                        Trusted & Secure
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-bold">
                        <span class="block text-gray-800">Welcome back to</span>
                        <span class="flex items-baseline gap-px">
                            <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">P</span>
                            <img src="pictures/logo%20web.png" alt="Pawhabilin logo" class="h-[1em] w-[1em] object-contain shrink-0" />
                            <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">whabilin</span>
                        </span>
                    </h1>
                    <p class="text-xl text-gray-600">
                        Your trusted pet care community is waiting for you
                    </p>
                </div>

                <!-- Pet Care Stats -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="glass rounded-2xl p-6 border border-orange-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                        <div class="text-3xl font-bold text-orange-600 mb-2">8,000+</div>
                        <div class="text-sm text-gray-600">Trusted Pet Sitters</div>
                    </div>
                    <div class="glass rounded-2xl p-6 border border-amber-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                        <div class="text-3xl font-bold text-amber-600 mb-2">25,000+</div>
                        <div class="text-sm text-gray-600">Happy Pet Parents</div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="relative">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden group">
                        <img 
                            src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080" 
                            alt="Happy pet owner"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                    </div>
                    <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-lg border border-orange-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="paw-print" class="w-6 h-6 text-orange-500"></i>
                            <span class="font-semibold text-gray-800">Join the pack!</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="w-full max-w-md mx-auto lg:mx-0">
                <div class="overflow-hidden glass border-0 shadow-2xl rounded-2xl">
                    <!-- Card Header with Gradient -->
                    <div class="bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 p-8 text-white relative overflow-hidden">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative z-10 text-center space-y-3">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto backdrop-blur-sm">
                                <i data-lucide="user" class="w-8 h-8 text-white"></i>
                            </div>
                            <h2 class="text-2xl font-bold">Welcome Back!</h2>
                            <p class="text-orange-100">Sign in to your pet care account</p>
                        </div>
                        
                        <!-- Decorative Paw Prints -->
                        <div class="absolute top-4 right-4 opacity-20">
                            <i data-lucide="paw-print" class="w-8 h-8 text-white transform rotate-12"></i>
                        </div>
                        <div class="absolute bottom-4 left-4 opacity-20">
                            <i data-lucide="paw-print" class="w-6 h-6 text-white transform -rotate-12"></i>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                                    <span><?php echo htmlspecialchars($success_message); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="error-message">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Social Login Buttons -->
                        <div class="space-y-3">
                            <button class="social-btn w-full h-12 border border-gray-200 hover:border-orange-300 hover:bg-orange-50 rounded-lg transition-all duration-300 group bg-white">
                                <div class="flex items-center justify-center gap-3">
                                    <i data-lucide="chrome" class="social-icon w-5 h-5 transition-transform duration-300"></i>
                                    <span>Continue with Google</span>
                                </div>
                            </button>
                            
                            <button class="social-btn w-full h-12 border border-gray-200 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-all duration-300 group bg-white">
                                <div class="flex items-center justify-center gap-3">
                                    <i data-lucide="facebook" class="social-icon w-5 h-5 text-blue-600 transition-transform duration-300"></i>
                                    <span>Continue with Facebook</span>
                                </div>
                            </button>
                        </div>

                        <!-- Divider -->
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white text-gray-600">or continue with email</span>
                            </div>
                        </div>

                        <!-- Login Form -->
                        <form method="POST" action="login" class="space-y-5" id="loginForm">
                            <!-- Email Field -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Email Address</label>
                                <div class="form-group relative">
                                    <i data-lucide="mail" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-colors duration-300"></i>
                                    <input
                                        name="email"
                                        type="email"
                                        placeholder="Enter your email"
                                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                        class="form-input w-full pl-12 h-12 border border-gray-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 rounded-lg transition-all duration-300 outline-none"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Password</label>
                                <div class="form-group relative">
                                    <i data-lucide="lock" class="form-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 transition-colors duration-300"></i>
                                    <input
                                        name="password"
                                        type="password"
                                        id="passwordField"
                                        placeholder="Enter your password"
                                        class="form-input w-full pl-12 pr-12 h-12 border border-gray-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 rounded-lg transition-all duration-300 outline-none"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onclick="togglePassword()"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors duration-300"
                                    >
                                        <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input 
                                        type="checkbox" 
                                        name="remember_me"
                                        class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2"
                                    />
                                    <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors duration-300">Remember me</span>
                                </label>
                                <a 
                                    href="forgot-password" 
                                    class="text-sm text-orange-600 hover:text-orange-700 hover:underline transition-all duration-300"
                                >
                                    Forgot password?
                                </a>
                            </div>

                            <!-- Login Button -->
                            <button
                                type="submit"
                                id="loginBtn"
                                class="w-full h-12 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed group"
                            >
                                <div class="flex items-center justify-center gap-2" id="btnContent">
                                    <span>Sign In</span>
                                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                                </div>
                            </button>
                        </form>

                        <!-- Sign Up Link -->
                        <div class="text-center pt-4 border-t border-gray-100">
                            <p class="text-sm text-gray-600">
                                New to pawhabilin? 
                                <a 
                                    href="registration" 
                                    class="text-orange-600 hover:text-orange-700 font-semibold hover:underline transition-all duration-300"
                                >
                                    Create an account
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
                            <span>Secure Login</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i data-lucide="heart" class="w-4 h-4 text-red-500"></i>
                            <span>Trusted by 25K+</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i data-lucide="paw-print" class="w-4 h-4 text-orange-500"></i>
                            <span>Pet Care Experts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Password visibility toggle
        function togglePassword() {
            const passwordField = document.getElementById('passwordField');
            const eyeIcon = document.getElementById('eyeIcon');
            
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

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnContent = document.getElementById('btnContent');
            
            // Show loading state
            loginBtn.disabled = true;
            btnContent.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="spinner"></div>
                    <span>Signing in...</span>
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

        // Animate elements on page load
        window.addEventListener('load', function() {
            const animatedElements = document.querySelectorAll('[class*="animate-"]');
            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Social login button interactions
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Add click animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // You can add actual social login logic here
                console.log('Social login clicked');
            });
        });

        // Refresh icons after any dynamic content changes
        setInterval(() => {
            lucide.createIcons();
        }, 1000);

        // ===== PAW PRINT BALLOON CLICK EFFECT =====
        // Array of paw colors for variety
        const pawColors = ['orange', 'amber', 'red', 'pink', 'yellow'];
        
        // Add click event listener to the entire document
        document.addEventListener('click', function(e) {
            createPawBalloon(e.clientX, e.clientY);
        });

        // Function to create paw print balloon effect
        function createPawBalloon(x, y) {
            // Create multiple paw prints for a burst effect
            const pawCount = Math.floor(Math.random() * 3) + 2; // 2-4 paws
            
            for (let i = 0; i < pawCount; i++) {
                setTimeout(() => {
                    const paw = document.createElement('div');
                    paw.innerHTML = '<i data-lucide="paw-print"></i>';
                    paw.className = `paw-balloon ${pawColors[Math.floor(Math.random() * pawColors.length)]}`;
                    
                    // Random offset from click position
                    const offsetX = (Math.random() - 0.5) * 100;
                    const offsetY = (Math.random() - 0.5) * 50;
                    
                    paw.style.left = (x + offsetX) + 'px';
                    paw.style.top = (y + offsetY) + 'px';
                    
                    // Add random rotation
                    const rotation = Math.floor(Math.random() * 360);
                    paw.style.transform = `rotate(${rotation}deg)`;
                    
                    document.body.appendChild(paw);
                    
                    // Initialize Lucide icons for the new paw
                    lucide.createIcons();
                    
                    // Remove the paw after animation completes
                    setTimeout(() => {
                        if (paw.parentNode) {
                            paw.parentNode.removeChild(paw);
                        }
                    }, 2500);
                }, i * 100); // Stagger the paw creation
            }
        }

        // Special effects for specific elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add extra paw effects for interactive elements
            const interactiveElements = document.querySelectorAll('button, .social-btn, a[href], input');
            
            interactiveElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    // Prevent double paw creation from document listener
                    e.stopPropagation();
                    
                    // Create more elaborate paw effect for interactive elements
                    const rect = this.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    
                    // Create extra paws for interactive elements
                    createPawBalloon(centerX, centerY);
                    
                    // Add extra paws around the element
                    setTimeout(() => {
                        createPawBalloon(centerX - 30, centerY - 20);
                    }, 200);
                    
                    setTimeout(() => {
                        createPawBalloon(centerX + 30, centerY - 20);
                    }, 400);
                });
            });

            // Add special celebration effect for the login button
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                loginBtn.addEventListener('click', function(e) {
                    // Create a celebration burst of paws
                    const rect = this.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    
                    // Create a circle of paws
                    for (let i = 0; i < 8; i++) {
                        setTimeout(() => {
                            const angle = (i * 45) * (Math.PI / 180);
                            const distance = 60;
                            const pawX = centerX + Math.cos(angle) * distance;
                            const pawY = centerY + Math.sin(angle) * distance;
                            createPawBalloon(pawX, pawY);
                        }, i * 100);
                    }
                });
            }
        });

        // Add paw trail effect on mouse movement (optional - can be enabled)
        let mouseTrailEnabled = false; // Set to true to enable mouse trail
        let lastMouseTime = 0;
        
        if (mouseTrailEnabled) {
            document.addEventListener('mousemove', function(e) {
                const now = Date.now();
                // Only create paw trail every 500ms to avoid too many elements
                if (now - lastMouseTime > 500) {
                    // Random chance to create a paw (20% chance)
                    if (Math.random() < 0.2) {
                        createPawBalloon(e.clientX, e.clientY);
                    }
                    lastMouseTime = now;
                }
            });
        }
    </script>
    <script>
        // Dropdown initializer copied from index.php
        (function() {
            function initDropdown({ wrapperId, buttonId, menuId }) {
                const wrapper = document.getElementById(wrapperId);
                const btn = document.getElementById(buttonId);
                const menu = document.getElementById(menuId);
                const chevron = btn && btn.querySelector('i[data-lucide="chevron-down"]');
                let persist = false;
                let hoverTimeout = null;

                if (!wrapper || !btn || !menu) return;

                function setOpen(open) {
                    if (open) {
                        menu.classList.add('open');
                        menu.classList.remove('opacity-0');
                        menu.classList.remove('translate-y-2');
                        menu.setAttribute('aria-hidden', 'false');
                        btn.setAttribute('aria-expanded', 'true');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        menu.classList.remove('open');
                        menu.classList.add('opacity-0');
                        menu.classList.add('translate-y-2');
                        menu.setAttribute('aria-hidden', 'true');
                        btn.setAttribute('aria-expanded', 'false');
                        if (chevron) chevron.style.transform = '';
                    }
                }

                wrapper.addEventListener('mouseenter', function() {
                    if (hoverTimeout) clearTimeout(hoverTimeout);
                    setOpen(true);
                });

                wrapper.addEventListener('mouseleave', function() {
                    if (persist) return;
                    hoverTimeout = setTimeout(function() { setOpen(false); }, 150);
                });

                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    persist = !persist;
                    setOpen(persist);
                });

                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target)) {
                        persist = false;
                        setOpen(false);
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        persist = false;
                        setOpen(false);
                    }
                });

                setOpen(false);
            }

            // Initialize both dropdowns
            initDropdown({ wrapperId: 'appointmentsWrapper', buttonId: 'appointmentsButton', menuId: 'appointmentsMenu' });
            initDropdown({ wrapperId: 'petsitterWrapper', buttonId: 'petsitterButton', menuId: 'petsitterMenu' });
        })();
    </script>
</body>
</html>