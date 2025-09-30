<?php
// Access control redirect logic for subscription pages
require_once __DIR__ . '/utils/session.php';
session_start_if_needed();
$__currentUser = get_current_user_session();
// If a logged-in user lands on the guest subscriptions page, send them to the user version
if ($__currentUser) {
    header('Location: views/users/subscriptions.php');
    exit; // Ensure no further output
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans - pawhabilin</title>
    
    <!-- Tailwind CSS v4.0 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="globals.css">
    
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
        
        .sparkle-animation {
            animation: sparkle 2s ease-in-out infinite;
        }
        
        .sparkle-animation:nth-child(2) {
            animation-delay: -0.5s;
        }
        
        .sparkle-animation:nth-child(3) {
            animation-delay: -1s;
        }
        
        .pricing-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .pricing-card:hover::before {
            left: 100%;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .premium-glow {
            box-shadow: 0 0 30px rgba(251, 146, 60, 0.3);
        }
        
        .premium-card {
            background: linear-gradient(135deg, #f97316, #fb923c, #fbbf24);
            color: white;
        }
        
        .premium-card .pricing-amount {
            color: white;
        }
        
        .free-card {
            background: linear-gradient(135deg, #f9fafb, #ffffff, #f3f4f6);
            border: 2px solid #e5e7eb;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-check {
            color: #10b981;
        }
        
        .feature-cross {
            color: #ef4444;
        }
        
        .crown-icon {
            color: #fbbf24;
            filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.5));
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
        
        @media (max-width: 768px) {
            .pricing-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header (shared guest/auth) -->
    <?php $basePrefix = ''; include __DIR__ . '/utils/header.php'; ?>

    <!-- Hero Section -->
    <section class="relative py-16 overflow-hidden gradient-bg">
        <!-- Floating background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-20 left-10 opacity-20">
                <i data-lucide="crown" class="w-20 h-20 text-white transform rotate-12 sparkle-animation"></i>
            </div>
            <div class="floating-element absolute top-32 right-20 opacity-20">
                <i data-lucide="star" class="w-16 h-16 text-white transform -rotate-12 sparkle-animation"></i>
            </div>
            <div class="floating-element absolute bottom-20 left-1/4 opacity-20">
                <i data-lucide="sparkles" class="w-18 h-18 text-white transform rotate-45 sparkle-animation"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="space-y-6 slide-in-up">
                    <!-- Sign In Call-to-Action -->
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-3 text-lg font-medium glass-effect">
                        <i data-lucide="lock" class="w-5 h-5 mr-3"></i>
                        Sign in to unlock premium pet care features
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold">
                        Choose Your
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl text-yellow-200">Pet Care Plan</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        Join thousands of pet parents who trust pawhabilin for exceptional pet care. 
                        From basic essentials to luxury services, we have the perfect plan for every pet family.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center pt-6">
                        <a href="registration.php" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-white text-orange-600 hover:bg-orange-50 h-14 px-8 transform hover:scale-105 hover:shadow-2xl">
                            <i data-lucide="user-plus" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                            Sign Up for Premium
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                        
                        <a href="login.php" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-medium transition-all duration-300 border-2 border-white text-white hover:bg-white hover:text-orange-600 h-14 px-8 transform hover:scale-105">
                            <i data-lucide="log-in" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                            Already a Member? Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Plans -->
    <section class="py-20 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="crown" class="w-3 h-3 mr-1"></i>
                    Subscription Plans
                </div>
                
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Perfect Plans for Every Pet Family
                    </span>
                </h2>
                
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Whether you're a new pet parent or an experienced pet lover, 
                    we have the right subscription to meet your furry friend's needs.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <!-- Free Plan -->
                <div class="pricing-card free-card rounded-3xl p-8 relative">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="heart" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-800 mb-2">Free Plan</h3>
                        <p class="text-gray-600 mb-6">Perfect for getting started with basic pet care</p>
                        <div class="pricing-amount">
                            <span class="text-5xl font-bold text-gray-800">₱0</span>
                            <span class="text-xl text-gray-600">/month</span>
                        </div>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check" class="w-5 h-5 feature-check"></i>
                            <span class="text-gray-700">Basic pet profile creation</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="check" class="w-5 h-5 feature-check"></i>
                            <span class="text-gray-700">Browse available sitters</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="check" class="w-5 h-5 feature-check"></i>
                            <span class="text-gray-700">Basic appointment booking</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="check" class="w-5 h-5 feature-check"></i>
                            <span class="text-gray-700">Access to shop (basic products)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="x" class="w-5 h-5 feature-cross"></i>
                            <span class="text-gray-500 line-through">Priority booking</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="x" class="w-5 h-5 feature-cross"></i>
                            <span class="text-gray-500 line-through">Premium sitter access</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="x" class="w-5 h-5 feature-cross"></i>
                            <span class="text-gray-500 line-through">24/7 premium support</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="x" class="w-5 h-5 feature-cross"></i>
                            <span class="text-gray-500 line-through">Exclusive premium products</span>
                        </div>
                    </div>

                    <a href="registration.php" class="w-full inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-4 px-6 rounded-xl transition-all duration-300 text-center">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>
                        Sign Up to Subscribe
                    </a>
                </div>

                <!-- Premium Plan -->
                <div class="pricing-card premium-card rounded-3xl p-8 relative premium-glow">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <div class="bg-yellow-400 text-yellow-900 px-6 py-2 rounded-full font-bold text-sm flex items-center gap-2">
                            <i data-lucide="crown" class="w-4 h-4 crown-icon"></i>
                            Most Popular
                        </div>
                    </div>
                    
                    <div class="text-center mb-8 pt-4">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="crown" class="w-8 h-8 text-yellow-300"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2">Premium Plan</h3>
                        <p class="text-white/80 mb-6">Ultimate pet care experience with exclusive benefits</p>
                        <div class="pricing-amount">
                            <span class="text-5xl font-bold text-white">₱299</span>
                            <span class="text-xl text-white/80">/month</span>
                        </div>
                        <div class="text-sm text-white/70 mt-2">
                            Save ₱600 annually with yearly plan
                        </div>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check" class="w-5 h-5 text-green-300"></i>
                            <span class="text-white">Everything in Free Plan</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="zap" class="w-5 h-5 text-yellow-300"></i>
                            <span class="text-white">Priority booking & scheduling</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="star" class="w-5 h-5 text-yellow-300"></i>
                            <span class="text-white">Access to premium verified sitters</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="phone" class="w-5 h-5 text-green-300"></i>
                            <span class="text-white">24/7 premium customer support</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="package" class="w-5 h-5 text-blue-300"></i>
                            <span class="text-white">20% discount on all shop products</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="crown" class="w-5 h-5 text-yellow-300"></i>
                            <span class="text-white">Exclusive premium products access</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="calendar-heart" class="w-5 h-5 text-pink-300"></i>
                            <span class="text-white">Free monthly health checkup</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="shield" class="w-5 h-5 text-green-300"></i>
                            <span class="text-white">Premium pet insurance coverage</span>
                        </div>
                    </div>

                    <a href="registration.php" class="w-full inline-flex items-center justify-center bg-white text-orange-600 font-semibold py-4 px-6 rounded-xl hover:bg-orange-50 transition-all duration-300 transform hover:scale-105 pulse-glow text-center">
                        <i data-lucide="crown" class="w-5 h-5 mr-2"></i>
                        Sign Up to Subscribe
                    </a>
                </div>
            </div>

            <!-- Money-back guarantee -->
            <div class="text-center mt-12">
                <div class="inline-flex items-center gap-3 bg-white/80 backdrop-blur-sm rounded-full px-8 py-4 border border-green-200">
                    <i data-lucide="shield-check" class="w-6 h-6 text-green-600"></i>
                    <span class="font-semibold text-gray-800">30-day money-back guarantee</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Premium Benefits Detail -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Why Choose 
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent brand-font">
                        Premium?
                    </span>
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Discover the exclusive benefits and premium features that will transform 
                    your pet care experience into something extraordinary.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Priority Booking -->
                <div class="text-center p-8 bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl border border-orange-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="zap" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Priority Booking</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Skip the wait and get instant access to the best appointment slots. 
                        Your pet's needs come first with priority scheduling.
                    </p>
                </div>

                <!-- Premium Sitters -->
                <div class="text-center p-8 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="star" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Premium Verified Sitters</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Access our elite network of highly-rated, background-checked pet sitters 
                        with specialized training and certifications.
                    </p>
                </div>

                <!-- 24/7 Support -->
                <div class="text-center p-8 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="phone" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">24/7 Premium Support</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Round-the-clock dedicated support team ready to help with any pet care 
                        emergencies or questions, day or night.
                    </p>
                </div>

                <!-- Exclusive Discounts -->
                <div class="text-center p-8 bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl border border-purple-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="package" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">20% Shop Discount</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Save money on all pet products, food, toys, and accessories with exclusive 
                        premium member discounts on every purchase.
                    </p>
                </div>

                <!-- Health Checkups -->
                <div class="text-center p-8 bg-gradient-to-br from-pink-50 to-rose-50 rounded-2xl border border-pink-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="calendar-heart" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Free Monthly Checkup</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Complimentary monthly health checkups with certified veterinarians 
                        to keep your pet healthy and happy year-round.
                    </p>
                </div>

                <!-- Insurance Coverage -->
                <div class="text-center p-8 bg-gradient-to-br from-teal-50 to-cyan-50 rounded-2xl border border-teal-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="shield" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Pet Insurance Coverage</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Comprehensive pet insurance coverage included in your premium plan, 
                        protecting your pet and your wallet from unexpected medical costs.
                    </p>
                </div>
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
            document.querySelectorAll('.pricing-card').forEach(el => {
                observer.observe(el);
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
    </script>

    <!-- Header initializes icons and dropdowns -->
</body>
</html>