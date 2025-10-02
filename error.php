<?php
// Guest-only error page. If a user session exists, forward to logged-in 403 variant.
session_start();
if (!empty($_SESSION['users_id'])) {
    header('Location: views/users/error403.php');
    exit;
}
$redirectTarget = 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Pawhabilin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .brand-font {
            font-family: 'La Belle Aurore', cursive;
        }

        /* Paw print animation */
        @keyframes pawPrint {
            0%, 100% {
                transform: scale(0) rotate(0deg);
                opacity: 0;
            }
            50% {
                transform: scale(1) rotate(180deg);
                opacity: 0.3;
            }
        }

        .paw-print {
            position: absolute;
            animation: pawPrint 3s infinite;
            font-size: 2rem;
            color: #f97316;
            pointer-events: none;
        }

        .paw-print:nth-child(1) { top: 10%; left: 15%; animation-delay: 0s; }
        .paw-print:nth-child(2) { top: 20%; right: 20%; animation-delay: 0.5s; }
        .paw-print:nth-child(3) { bottom: 25%; left: 10%; animation-delay: 1s; }
        .paw-print:nth-child(4) { top: 60%; right: 15%; animation-delay: 1.5s; }
        .paw-print:nth-child(5) { bottom: 15%; right: 25%; animation-delay: 2s; }
        .paw-print:nth-child(6) { top: 40%; left: 20%; animation-delay: 2.5s; }

        /* Error code bounce */
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) scale(1);
            }
            40% {
                transform: translateY(-30px) scale(1.05);
            }
            60% {
                transform: translateY(-15px) scale(1.02);
            }
        }

        .error-code {
            animation: bounce 2s infinite;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .error-code:hover {
            animation: shake 0.5s infinite;
        }

        /* Shake animation on hover */
        @keyframes shake {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            25% { transform: translateX(-10px) rotate(-5deg); }
            75% { transform: translateX(10px) rotate(5deg); }
        }

        /* Dog guard animation */
        @keyframes guardDog {
            0%, 100% {
                transform: translateX(0) rotate(0deg);
            }
            25% {
                transform: translateX(-5px) rotate(-2deg);
            }
            75% {
                transform: translateX(5px) rotate(2deg);
            }
        }

        .guard-dog {
            animation: guardDog 2s ease-in-out infinite;
        }

        /* Button glow effect */
        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(249, 115, 22, 0.3), 0 0 40px rgba(249, 115, 22, 0.2);
            }
            50% {
                box-shadow: 0 0 30px rgba(249, 115, 22, 0.5), 0 0 60px rgba(249, 115, 22, 0.3);
            }
        }

        .glow-button {
            animation: glow 2s ease-in-out infinite;
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .float {
            animation: float 3s ease-in-out infinite;
        }

        /* Background gradient animation */
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .animated-gradient {
            background: linear-gradient(135deg, #fff5eb, #fed7aa, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        /* Pulse animation */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }

        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Interactive paw button */
        .paw-button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .paw-button::before {
            content: "🐾";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 3rem;
            opacity: 0;
            transition: all 0.5s ease;
        }

        .paw-button:hover::before {
            transform: translate(-50%, -50%) scale(3);
            opacity: 0.1;
        }

        .paw-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 40px rgba(249, 115, 22, 0.4);
        }

        .paw-button:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Text reveal animation */
        @keyframes textReveal {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .text-reveal {
            animation: textReveal 0.6s ease forwards;
        }

        .text-reveal:nth-child(1) { animation-delay: 0.2s; opacity: 0; }
        .text-reveal:nth-child(2) { animation-delay: 0.4s; opacity: 0; }
        .text-reveal:nth-child(3) { animation-delay: 0.6s; opacity: 0; }

        /* Collar badge rotation */
        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .rotating {
            animation: rotate 20s linear infinite;
        }
    </style>
</head>
<body class="animated-gradient min-h-screen flex items-center justify-center p-4 relative overflow-y-auto">
    
    <!-- Floating paw prints -->
    <div class="paw-print">🐾</div>
    <div class="paw-print">🐾</div>
    <div class="paw-print">🐾</div>
    <div class="paw-print">🐾</div>
    <div class="paw-print">🐾</div>
    <div class="paw-print">🐾</div>

    <!-- Main content container -->
    <div class="max-w-4xl w-full relative z-10">
        
        <!-- Logo -->
        <div class="text-center mb-8 text-reveal">
            <div class="inline-flex items-center space-x-3 bg-white/80 backdrop-blur-sm px-6 py-3 rounded-full shadow-lg border border-orange-200">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-amber-500 rounded-lg flex items-center justify-center rotating">
                    <span class="text-2xl">🐾</span>
                </div>
                <h1 class="brand-font text-3xl bg-gradient-to-r from-orange-500 to-amber-600 bg-clip-text text-transparent">
                    pawhabilin
                </h1>
            </div>
        </div>

        <!-- Error Card -->
        <div class="bg-white/90 backdrop-blur-lg rounded-3xl shadow-2xl border-2 border-orange-200 overflow-hidden">
            
            <!-- Guard Dog Header -->
            <div class="bg-gradient-to-r from-orange-500 to-amber-600 p-8 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-4 left-4 text-6xl">🦴</div>
                    <div class="absolute bottom-4 right-4 text-6xl">🦴</div>
                    <div class="absolute top-1/2 left-1/4 text-4xl transform -translate-y-1/2">🐕</div>
                    <div class="absolute top-1/2 right-1/4 text-4xl transform -translate-y-1/2">🐕</div>
                </div>
                
                <div class="relative">
                    <div class="guard-dog text-8xl mb-4 inline-block">🐕‍🦺</div>
                    <div class="error-code inline-block">
                        <h2 class="text-white text-9xl font-bold mb-2 drop-shadow-lg" style="font-family: 'Courier New', monospace;">
                            403
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-12 text-center">
                
                <!-- Main Message -->
                <div class="mb-8 space-y-4">
                    <h3 class="text-4xl font-bold text-gray-800 text-reveal">
                        Woof! Access Denied
                    </h3>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed text-reveal">
                        Our guard dog is protecting this area! 🐾
                    </p>
                    <p class="text-lg text-gray-500 text-reveal">
                        This section is only accessible to <span class="font-bold text-orange-600">Admin</span> members. 
                        If you're a regular user or guest, please return to the homepage.
                    </p>
                </div>

                <!-- Interactive Elements -->
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div class="bg-orange-50 border-2 border-orange-200 rounded-xl px-6 py-4 float">
                        <div class="text-3xl mb-2">🚫</div>
                        <p class="text-sm font-semibold text-gray-700">No Admin Access</p>
                    </div>
                    <div class="bg-amber-50 border-2 border-amber-200 rounded-xl px-6 py-4 float" style="animation-delay: 0.2s;">
                        <div class="text-3xl mb-2">🔒</div>
                        <p class="text-sm font-semibold text-gray-700">Protected Area</p>
                    </div>
                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl px-6 py-4 float" style="animation-delay: 0.4s;">
                        <div class="text-3xl mb-2">👮</div>
                        <p class="text-sm font-semibold text-gray-700">Authorization Required</p>
                    </div>
                </div>

                <!-- Divider with paws -->
                <div class="flex items-center justify-center space-x-4 my-8">
                    <span class="text-2xl pulse">🐾</span>
                    <div class="h-px bg-gradient-to-r from-transparent via-orange-300 to-transparent flex-1 max-w-xs"></div>
                    <span class="text-2xl pulse" style="animation-delay: 0.5s;">🐾</span>
                    <div class="h-px bg-gradient-to-r from-transparent via-orange-300 to-transparent flex-1 max-w-xs"></div>
                    <span class="text-2xl pulse" style="animation-delay: 1s;">🐾</span>
                </div>

                <!-- Homepage Button -->
                <div class="space-y-4">
                    <a href="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>" class="paw-button glow-button inline-flex items-center space-x-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white px-10 py-5 rounded-full font-semibold text-lg shadow-xl hover:shadow-2xl transition-all duration-300">
                        <span>🏠</span>
                        <span>Go to Homepage</span>
                        <span>→</span>
                    </a>
                    
                    <p class="text-sm text-gray-500 mt-4">
                        Click above to return to safety! 🐶
                    </p>
                </div>

                <!-- Additional Info -->
                <div class="mt-12 pt-8 border-t border-orange-200">
                    <p class="text-sm text-gray-600 mb-3">
                        <span class="font-semibold">💡 Tip:</span> If you believe you should have admin access, please contact the system administrator.
                    </p>
                    <div class="inline-flex items-center space-x-2 text-xs text-gray-500">
                        <span>🔐</span>
                        <span>Your activity has been logged for security purposes</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Fun Message -->
        <div class="text-center mt-8 text-reveal">
            <div class="inline-flex items-center space-x-3 bg-white/70 backdrop-blur-sm px-6 py-3 rounded-full shadow-lg border border-orange-100">
                <span class="text-xl">🐾</span>
                <p class="text-sm text-gray-600">Don't worry, you're still a good boy/girl!</p>
                <span class="text-xl">🐾</span>
            </div>
        </div>
    </div>

    <!-- Easter egg: Click counter -->
    <script>
        let clickCount = 0;
        const errorCode = document.querySelector('.error-code');
        const guardDog = document.querySelector('.guard-dog');
        
        errorCode.addEventListener('click', function() {
            clickCount++;
            
            // Add fun reactions
            if (clickCount === 5) {
                guardDog.textContent = '🐕';
                setTimeout(() => { guardDog.textContent = '🐕‍🦺'; }, 500);
            }
            if (clickCount === 10) {
                guardDog.textContent = '🐶';
                setTimeout(() => { guardDog.textContent = '🐕‍🦺'; }, 500);
            }
            if (clickCount === 15) {
                // Create a bark sound effect (visual)
                const bark = document.createElement('div');
                bark.textContent = 'WOOF!';
                bark.style.position = 'fixed';
                bark.style.top = '50%';
                bark.style.left = '50%';
                bark.style.transform = 'translate(-50%, -50%)';
                bark.style.fontSize = '4rem';
                bark.style.fontWeight = 'bold';
                bark.style.color = '#f97316';
                bark.style.opacity = '0';
                bark.style.transition = 'all 0.5s ease';
                bark.style.zIndex = '9999';
                bark.style.pointerEvents = 'none';
                document.body.appendChild(bark);
                
                setTimeout(() => {
                    bark.style.opacity = '1';
                    bark.style.fontSize = '6rem';
                }, 10);
                
                setTimeout(() => {
                    bark.style.opacity = '0';
                }, 500);
                
                setTimeout(() => {
                    bark.remove();
                }, 1000);
                
                clickCount = 0; // Reset
            }
        });

        // Add particle effect on mouse move
        document.addEventListener('mousemove', function(e) {
            if (Math.random() > 0.95) { // 5% chance on mouse move
                const particle = document.createElement('div');
                particle.textContent = '🐾';
                particle.style.position = 'fixed';
                particle.style.left = e.clientX + 'px';
                particle.style.top = e.clientY + 'px';
                particle.style.fontSize = '1rem';
                particle.style.pointerEvents = 'none';
                particle.style.zIndex = '9999';
                particle.style.opacity = '0.7';
                particle.style.transition = 'all 1s ease';
                document.body.appendChild(particle);
                
                setTimeout(() => {
                    particle.style.transform = 'translateY(-50px)';
                    particle.style.opacity = '0';
                }, 10);
                
                setTimeout(() => {
                    particle.remove();
                }, 1000);
            }
        });
    </script>
</body>
</html>
