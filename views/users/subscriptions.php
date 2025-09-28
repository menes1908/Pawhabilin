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
        
        .current-plan-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        @media (max-width: 768px) {
            .pricing-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="sticky top-0 z-50 border-b bg-white/80 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <a href="../index.php" class="flex items-center space-x-2 group">
                    <div class="w-10 h-10 rounded-lg overflow-hidden transform group-hover:rotate-12 transition-transform duration-300">
                        <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080" alt="pawhabilin Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="text-xl font-semibold brand-font">pawhabilin</span>
                </a>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="../index.php" class="text-muted-foreground hover:text-foreground transition-colors">Home</a>
                    <a href="../find-sitter.php" class="text-muted-foreground hover:text-foreground transition-colors">Find Sitter</a>
                    <a href="../shop.php" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    <a href="../appointment.php" class="text-muted-foreground hover:text-foreground transition-colors">Appointments</a>
                    <a href="subscription.php" class="text-orange-600 font-medium">Subscription</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="../user-profile.php" class="hidden sm:inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground h-9 px-3">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        Profile
                    </a>
                    <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-9 px-4">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </header>

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
                    <!-- User Greeting -->
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-3 text-lg font-medium glass-effect">
                        <i data-lucide="heart" class="w-5 h-5 mr-3"></i>
                        Hello, User! Welcome to your pet care journey
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold">
                        Choose Your
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl text-yellow-200">Pet Care Plan</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        Unlock premium features and give your beloved pets the best care possible. 
                        From basic essentials to luxury services, we have the perfect plan for every pet family.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Current Plan Status -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 mb-12">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-green-800">Current Plan: Free</h3>
                                <p class="text-green-600">You're currently enjoying our basic pet care features</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-700">₱0</div>
                            <div class="text-sm text-green-600">per month</div>
                        </div>
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
                    <div class="current-plan-badge">
                        <i data-lucide="check" class="w-3 h-3 mr-1 inline"></i>
                        Current Plan
                    </div>
                    
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

                    <button disabled class="w-full bg-gray-200 text-gray-500 font-semibold py-4 px-6 rounded-xl cursor-not-allowed transition-all duration-300">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>
                        Current Plan
                    </button>
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

                    <button onclick="upgradeToPremium()" class="w-full bg-white text-orange-600 font-semibold py-4 px-6 rounded-xl hover:bg-orange-50 transition-all duration-300 transform hover:scale-105 pulse-glow">
                        <i data-lucide="crown" class="w-5 h-5 mr-2 inline"></i>
                        Upgrade to Premium
                    </button>
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
                    Why Upgrade to 
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

    <!-- Testimonials from Premium Users -->
    <section class="py-20 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="heart" class="w-3 h-3 mr-1"></i>
                    Premium Member Stories
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    What Premium Members Say
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Hear from pet parents who upgraded to premium and transformed their pet care experience.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            L
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Lisa Chen</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="crown" class="w-4 h-4 text-yellow-500"></i>
                                <span class="text-sm text-orange-600 font-medium">Premium Member</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The priority booking is a game-changer! I can always get appointments when I need them, and the premium sitters are incredibly professional. Worth every peso!"</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            M
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Mark Rodriguez</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="crown" class="w-4 h-4 text-yellow-500"></i>
                                <span class="text-sm text-orange-600 font-medium">Premium Member</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The 24/7 support saved my dog's life during an emergency. The premium insurance covered everything. I can't imagine going back to the free plan!"</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            S
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Sarah Nakamura</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="crown" class="w-4 h-4 text-yellow-500"></i>
                                <span class="text-sm text-orange-600 font-medium">Premium Member</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The monthly checkups and shop discounts have saved me so much money. My cats are healthier than ever, and I love the exclusive premium products!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Frequently Asked Questions
                </h2>
                <p class="text-xl text-gray-700">
                    Got questions about our premium subscription? We've got answers.
                </p>
            </div>

            <div class="space-y-6">
                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Can I cancel my premium subscription anytime?</h3>
                    <p class="text-gray-600">Yes! You can cancel your premium subscription at any time. You'll continue to have premium access until the end of your billing period.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">What happens to my data if I downgrade?</h3>
                    <p class="text-gray-600">All your pet profiles and booking history will be preserved. You'll simply lose access to premium features but retain all your basic functionality.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Is the pet insurance coverage comprehensive?</h3>
                    <p class="text-gray-600">Yes! Our premium pet insurance covers accidents, illnesses, and routine care up to ₱50,000 annually per pet, with no deductible.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">How do I access premium sitters?</h3>
                    <p class="text-gray-600">Once you upgrade, you'll see a "Premium" badge on select sitters in your search results. These sitters have undergone additional background checks and training.</p>
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
                    <h4 class="font-semibold">Account</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="../user-profile.php" class="hover:text-white transition-colors">My Profile</a></li>
                        <li><a href="subscription.php" class="hover:text-white transition-colors">Subscription</a></li>
                        <li><a href="../shop.php" class="hover:text-white transition-colors">Shop</a></li>
                        <li><a href="../appointment.php" class="hover:text-white transition-colors">Book Appointment</a></li>
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
                            24/7 Premium Support
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

        // Upgrade to Premium function
        function upgradeToPremium() {
            // Show loading state
            const upgradeBtn = document.querySelector('button[onclick="upgradeToPremium()"]');
            const originalContent = upgradeBtn.innerHTML;
            upgradeBtn.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <div class="w-5 h-5 border-2 border-orange-600 border-t-transparent rounded-full animate-spin"></div>
                    <span>Processing...</span>
                </div>
            `;
            upgradeBtn.disabled = true;
            
            // Simulate upgrade process
            setTimeout(() => {
                // Show success message
                alert('🎉 Welcome to Premium! Your subscription has been activated successfully. You now have access to all premium features!');
                
                // Update UI to show premium status
                updateUIToPremium();
                
                upgradeBtn.innerHTML = originalContent;
                upgradeBtn.disabled = false;
            }, 2000);
        }

        // Update UI to show premium status
        function updateUIToPremium() {
            // Update current plan section
            const currentPlanSection = document.querySelector('.bg-gradient-to-r.from-green-50');
            if (currentPlanSection) {
                currentPlanSection.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center">
                                <i data-lucide="crown" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-orange-800">Current Plan: Premium</h3>
                                <p class="text-orange-600">You're enjoying all premium pet care features</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-orange-700">₱299</div>
                            <div class="text-sm text-orange-600">per month</div>
                        </div>
                    </div>
                `;
                currentPlanSection.className = 'bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-2xl p-6 mb-12';
            }

            // Update plan badges
            const freePlanBadge = document.querySelector('.current-plan-badge');
            if (freePlanBadge) {
                freePlanBadge.remove();
            }

            // Add premium badge to premium card
            const premiumCard = document.querySelector('.premium-card');
            if (premiumCard && !premiumCard.querySelector('.current-plan-badge')) {
                const badge = document.createElement('div');
                badge.className = 'current-plan-badge';
                badge.innerHTML = '<i data-lucide="check" class="w-3 h-3 mr-1 inline"></i>Current Plan';
                premiumCard.appendChild(badge);
            }

            // Update buttons
            const freeBtn = document.querySelector('button[disabled]');
            if (freeBtn) {
                freeBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>Previous Plan';
            }

            const premiumBtn = document.querySelector('button[onclick="upgradeToPremium()"]');
            if (premiumBtn) {
                premiumBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>Current Plan';
                premiumBtn.disabled = true;
                premiumBtn.className = 'w-full bg-green-100 text-green-700 font-semibold py-4 px-6 rounded-xl cursor-not-allowed';
                premiumBtn.removeAttribute('onclick');
            }

            // Reinitialize icons
            lucide.createIcons();
        }

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
</body>
</html>