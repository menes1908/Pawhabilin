<?php
// Start session to potentially check login status (future enhancement)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Accessories Shop - Pawhabilin</title>
    <meta name="description" content="Shop premium pet accessories, toys, collars, leashes, grooming tools, and apparel for your beloved pets at pawhabilin.">
    <meta name="keywords" content="pet accessories, dog collars, pet toys, leashes, grooming tools, pet apparel, pawhabilin">
    
        <!-- Global variables & custom base styles -->
        <link rel="stylesheet" href="globals.css">

        <!-- Tailwind CSS (CDN) - added because utilities were not rendering without a build step -->
        <script>
            // Tailwind CDN configuration mapping custom CSS variable tokens to color utilities
            tailwind = {
                config: {
                    theme: {
                        extend: {
                            colors: {
                                background: 'var(--color-background)',
                                foreground: 'var(--color-foreground)',
                                card: 'var(--color-card)',
                                'card-foreground': 'var(--color-card-foreground)',
                                popover: 'var(--color-popover)',
                                'popover-foreground': 'var(--color-popover-foreground)',
                                primary: 'var(--color-primary)',
                                'primary-foreground': 'var(--color-primary-foreground)',
                                secondary: 'var(--color-secondary)',
                                'secondary-foreground': 'var(--color-secondary-foreground)',
                                muted: 'var(--color-muted)',
                                'muted-foreground': 'var(--color-muted-foreground)',
                                accent: 'var(--color-accent)',
                                'accent-foreground': 'var(--color-accent-foreground)',
                                destructive: 'var(--color-destructive)',
                                'destructive-foreground': 'var(--color-destructive-foreground)',
                                border: 'var(--color-border)',
                                input: 'var(--color-input)',
                                ring: 'var(--color-ring)',
                                sidebar: 'var(--color-sidebar)',
                                'sidebar-foreground': 'var(--color-sidebar-foreground)',
                                'sidebar-primary': 'var(--color-sidebar-primary)',
                                'sidebar-primary-foreground': 'var(--color-sidebar-primary-foreground)',
                                'sidebar-accent': 'var(--color-sidebar-accent)',
                                'sidebar-accent-foreground': 'var(--color-sidebar-accent-foreground)',
                                'sidebar-border': 'var(--color-sidebar-border)',
                                'sidebar-ring': 'var(--color-sidebar-ring)'
                            },
                            borderColor: {
                                DEFAULT: 'var(--color-border)'
                            }
                        }
                    }
                }
            };
        </script>
        <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Custom animations and shop-specific styles */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 0 30px rgba(249, 115, 22, 0.5); }
        }
        
        @keyframes slide-in-up {
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
        
        @keyframes paw-bounce {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        @keyframes cart-bounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
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
        
        /* PawGrid™ Layout */
        .paw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            padding: 20px 0;
        }
        
        .product-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(15px);
            border: 2px solid rgba(249, 115, 22, 0.1);
            border-radius: 20px;
            padding: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .product-card:hover::before {
            left: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px) scale(1.03);
            border-color: rgba(249, 115, 22, 0.3);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.2);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 16px;
            position: relative;
            background: linear-gradient(45deg, #f9fafb, #f3f4f6);
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .paw-print-hover {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: rgba(249, 115, 22, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }
        
        .product-card:hover .paw-print-hover {
            opacity: 1;
            transform: scale(1);
            animation: paw-bounce 0.6s ease-in-out;
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-badge.new {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .product-badge.bundle {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        /* Variant Swatches */
        .variant-swatches {
            display: flex;
            gap: 8px;
            margin: 12px 0;
            flex-wrap: wrap;
        }
        
        .variant-swatch {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .variant-swatch.paw-shape {
            border-radius: 45% 40% 45% 40%;
            transform: rotate(45deg);
        }
        
        .variant-swatch.bone-shape {
            border-radius: 50px 20px 50px 20px;
        }
        
        .variant-swatch:hover {
            border-color: #f97316;
            transform: scale(1.1);
        }
        
        .variant-swatch.active {
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
        }
        
        /* Sticky Mini Cart */
        .mini-cart {
            position: fixed;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            z-index: 1000;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
        }
        
        .mini-cart:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 25px rgba(249, 115, 22, 0.5);
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            animation: cart-bounce 0.3s ease;
        }
        
        /* Quick View Drawer */
        .quick-view-drawer {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 500px;
            height: 100vh;
            background: white;
            z-index: 1001;
            transition: right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow-y: auto;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.2);
        }
        
        .quick-view-drawer.open {
            right: 0;
        }
        
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .drawer-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        
        /* Category Filter */
        .category-tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .category-tab {
            padding: 12px 24px;
            border-radius: 25px;
            background: white;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .category-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .category-tab:hover::before {
            left: 100%;
        }
        
        .category-tab:hover {
            border-color: #f97316;
            color: #f97316;
        }
        
        .category-tab.active {
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-color: #f97316;
        }
        
        /* Bundle Deal Cards */
        .bundle-card {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }
        
        .bundle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.3);
        }
        
        .bundle-ribbon {
            position: absolute;
            top: 20px;
            right: -30px;
            background: #ef4444;
            color: white;
            padding: 8px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        /* Search and Filter */
        .search-container {
            position: relative;
            max-width: 400px;
            margin: 0 auto 32px;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .search-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .paw-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 16px;
            }
            
            .mini-cart {
                width: 50px;
                height: 50px;
                right: 15px;
            }
            
            .cart-count {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }
            
            .quick-view-drawer {
                width: 100%;
                max-width: 100%;
            }
            
            .category-tabs {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 8px;
            }
            
            .category-tab {
                flex-shrink: 0;
            }
        }
        
        /* Loading Animation */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, transparent 37%, #f0f0f0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
    <!-- Header (revised per request: centered, larger, no wishlist, icon-only cart) -->
    <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
        <div class="mx-auto px-4 w-full max-w-7xl">
            <div class="flex h-20 items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2 group">
                    <div class="w-20 h-20 rounded-lg overflow-hidden flex items-center justify-center transition-transform duration-300 hover:rotate-12" style="width:77px; height:77px;">
                        <img src="./pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                    </div>
                    <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent brand-font">
                        Pawhabilin
                    </span>
                </a>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-muted-foreground hover:text-foreground transition-colors">About</a>
                    <!-- Pet Sitter Dropdown -->
                    <div class="relative" id="petsitterWrapper">
                        <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Pet Sitter
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>
                        <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="find-sitters.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Find a Pet Sitter</a>
                                <a href="views/users/become_sitter.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Become a Sitter</a>
                            </div>
                        </div>
                    </div>

                    <a href="shop.php" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    
                    <div class="relative" id="appointmentsWrapper">
                        <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Appointments
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>
                        <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="book_appointments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                                <a href="appointments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
                            </div>
                        </div>
                    </div>

                    <a href="subscriptions.php" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>
                    <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
                </nav>

                <div class="flex items-center gap-4">
                    <button onclick="toggleCart()" class="relative inline-flex items-center justify-center w-11 h-11 rounded-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring transition-all">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <span id="cart-count" class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 bg-red-500 text-[11px] leading-5 font-semibold rounded-full flex items-center justify-center">0</span>
                    </button>
                    <a href="login.php" class="hidden lg:inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-accent hover:text-accent-foreground h-10 px-4">
                        Log In
                    </a>
                    <a href="registration.php" class="hidden lg:inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-10 px-5">
                        Sign Up
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Floating background elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="floating-element absolute top-20 left-10 opacity-20">
            <i data-lucide="paw-print" class="w-16 h-16 text-orange-300 transform rotate-12"></i>
        </div>
        <div class="floating-element absolute top-40 right-20 opacity-20">
            <i data-lucide="gift" class="w-12 h-12 text-amber-300 transform -rotate-12"></i>
        </div>
        <div class="floating-element absolute bottom-40 left-16 opacity-20">
            <i data-lucide="heart" class="w-14 h-14 text-orange-200 transform rotate-45"></i>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative py-16 overflow-hidden gradient-bg">
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="space-y-8 slide-in-up">
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium bg-white/10 backdrop-blur-sm">
                        <i data-lucide="shopping-bag" class="w-4 h-4 mr-2"></i>
                        Premium Pet Accessories
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold">
                        Everything Your Pet
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl text-amber-200">Needs & Loves</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        Discover our curated collection of premium pet accessories, toys, apparel, and essentials. 
                        From stylish collars to interactive toys, we have everything to keep your pets happy and healthy.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center pt-8">
                        <button onclick="scrollToProducts()" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-white text-orange-600 hover:bg-orange-50 h-14 px-8 transform hover:scale-105 hover:shadow-2xl">
                            <i data-lucide="shopping-cart" class="w-6 h-6 group-hover:scale-110 transition-transform duration-300"></i>
                            Shop Now
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </button>
                        
                        <button class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-medium transition-all duration-300 border-2 border-white text-white hover:bg-white hover:text-orange-600 h-14 px-8 transform hover:scale-105">
                            <i data-lucide="gift" class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300"></i>
                            View Bundles
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bundle Deals Section -->
    <section class="py-16 bg-white relative z-10">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="gift" class="w-3 h-3 mr-1"></i>
                    Special Bundles
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Bundle & Save
                    </span>
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">
                    Get everything your pet needs with our specially curated bundles. Save up to 30% compared to buying items individually.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Puppy Starter Kit -->
                <div class="bundle-card">
                    <div class="bundle-ribbon">Save 25%</div>
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="dog" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Puppy Starter Kit</h3>
                        <p class="text-gray-600 mb-4">Everything you need for your new puppy</p>
                        <div class="text-center">
                            <span class="text-3xl font-bold text-orange-600">₱2,499</span>
                            <span class="text-lg text-gray-500 line-through ml-2">₱3,299</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Premium Puppy Collar</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Adjustable Leash</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Interactive Chew Toys (3x)</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Food & Water Bowls</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Training Treats</span>
                        </div>
                    </div>
                    <button onclick="addBundleToCart('puppy-starter')" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Add Bundle to Cart
                    </button>
                </div>

                <!-- Cat Care Combo -->
                <div class="bundle-card">
                    <div class="bundle-ribbon">Save 30%</div>
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="cat" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Cat Care Combo</h3>
                        <p class="text-gray-600 mb-4">Complete care package for your feline friend</p>
                        <div class="text-center">
                            <span class="text-3xl font-bold text-purple-600">₱1,899</span>
                            <span class="text-lg text-gray-500 line-through ml-2">₱2,699</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Designer Cat Collar</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Interactive Feather Toy</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Scratching Post</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Catnip Toys (5x)</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Grooming Kit</span>
                        </div>
                    </div>
                    <button onclick="addBundleToCart('cat-care')" class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Add Bundle to Cart
                    </button>
                </div>

                <!-- Grooming Essentials -->
                <div class="bundle-card">
                    <div class="bundle-ribbon">Save 20%</div>
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="scissors" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Grooming Essentials</h3>
                        <p class="text-gray-600 mb-4">Professional grooming tools for home care</p>
                        <div class="text-center">
                            <span class="text-3xl font-bold text-blue-600">₱3,199</span>
                            <span class="text-lg text-gray-500 line-through ml-2">₱3,999</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Professional Brush Set</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Nail Clippers</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Pet Shampoo & Conditioner</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Microfiber Towels (2x)</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Ear Cleaning Solution</span>
                        </div>
                    </div>
                    <button onclick="addBundleToCart('grooming-essentials')" class="w-full bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Add Bundle to Cart
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products-section" class="py-16 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 relative z-10">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="paw-print" class="w-3 h-3 mr-1"></i>
                    Shop by Category
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Premium Pet Accessories
                    </span>
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto mb-8">
                    Browse our carefully curated collection of high-quality accessories, toys, and essentials for your beloved pets.
                </p>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search for products..." onkeyup="filterProducts()">
                <i data-lucide="search" class="search-icon w-5 h-5"></i>
            </div>

            <!-- Category Tabs -->
            <div class="category-tabs">
                <button class="category-tab active" onclick="filterByCategory('all')" data-category="all">
                    <i data-lucide="grid-3x3" class="w-4 h-4 mr-2 inline"></i>
                    All Products
                </button>
                <button class="category-tab" onclick="filterByCategory('collars')" data-category="collars">
                    <i data-lucide="circle" class="w-4 h-4 mr-2 inline"></i>
                    Collars & Leashes
                </button>
                <button class="category-tab" onclick="filterByCategory('toys')" data-category="toys">
                    <i data-lucide="gamepad-2" class="w-4 h-4 mr-2 inline"></i>
                    Toys & Entertainment
                </button>
                <button class="category-tab" onclick="filterByCategory('apparel')" data-category="apparel">
                    <i data-lucide="shirt" class="w-4 h-4 mr-2 inline"></i>
                    Pet Apparel
                </button>
                <button class="category-tab" onclick="filterByCategory('grooming')" data-category="grooming">
                    <i data-lucide="scissors" class="w-4 h-4 mr-2 inline"></i>
                    Grooming Tools
                </button>
                <button class="category-tab" onclick="filterByCategory('feeding')" data-category="feeding">
                    <i data-lucide="bowl" class="w-4 h-4 mr-2 inline"></i>
                    Feeding & Water
                </button>
            </div>

            <!-- Products Grid (PawGrid™ Layout) -->
            <div id="products-grid" class="paw-grid">
                <!-- Products will be dynamically loaded here -->
            </div>

            <!-- Load More Button -->
            <div class="text-center mt-12">
                <button onclick="loadMoreProducts()" class="inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-14 px-8 transform hover:scale-105">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    Load More Products
                </button>
            </div>
        </div>
    </section>

    <!-- Mini Cart -->
    <div class="mini-cart" onclick="toggleCart()">
        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
        <div class="cart-count" id="mini-cart-count">0</div>
    </div>

    <!-- Quick View Drawer -->
    <div class="drawer-overlay" id="drawer-overlay" onclick="closeQuickView()"></div>
    <div class="quick-view-drawer" id="quick-view-drawer">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">Quick View</h3>
                <button onclick="closeQuickView()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        <div id="quick-view-content" class="p-6">
            <!-- Quick view content will be dynamically loaded here -->
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-12 bg-gray-900 text-white relative z-10">
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
                        The Philippines' most trusted pet care platform providing premium accessories and comprehensive services for your beloved pets.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Shop Categories</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Collars & Leashes</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Toys & Entertainment</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Apparel</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Grooming Tools</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Services</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="book_appointments.php" class="hover:text-white transition-colors">Book Appointment</a></li>
                        <li><a href="find-sitter.php" class="hover:text-white transition-colors">Find Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Care Tips</a></li>
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
                            shop@pawhabilin.com
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                            Cebu City, Philippines
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="truck" class="w-4 h-4"></i>
                            Free Delivery ₱1,500+
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2025 pawhabilin Philippines. All rights reserved. | Secure Shopping | 30-Day Returns</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            loadProducts();
            
            // Add slide-in animation to elements
            const animatedElements = document.querySelectorAll('.slide-in-up');
            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Product data (in a real application, this would come from a database)
        const products = [
            {
                id: 1,
                name: "Premium Leather Dog Collar",
                category: "collars",
                price: 899,
                originalPrice: 1299,
                image: "https://images.unsplash.com/photo-1608848461950-0fe51dfc41cb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsZWF0aGVyJTIwZG9nJTIwY29sbGFyfGVufDF8fHx8MTc1ODYxMDgyOXww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Sale",
                rating: 4.8,
                variants: [
                    { type: "color", value: "#8B4513", name: "Brown" },
                    { type: "color", value: "#000000", name: "Black" },
                    { type: "color", value: "#654321", name: "Tan" }
                ],
                description: "Handcrafted premium leather collar with adjustable sizing and durable hardware."
            },
            {
                id: 2,
                name: "Interactive Puzzle Toy",
                category: "toys",
                price: 599,
                image: "https://images.unsplash.com/photo-1605034313761-73ea4a0cfbf3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbnRlcmFjdGl2ZSUyMGRvZyUyMHRveXxlbnwxfHx8fDE3NTg2MTA4NDF8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "New",
                rating: 4.9,
                variants: [
                    { type: "size", value: "S", name: "Small" },
                    { type: "size", value: "M", name: "Medium" },
                    { type: "size", value: "L", name: "Large" }
                ],
                description: "Mental stimulation puzzle toy that keeps your dog engaged and mentally active."
            },
            {
                id: 3,
                name: "Cozy Pet Sweater",
                category: "apparel",
                price: 799,
                image: "https://images.unsplash.com/photo-1583337130417-3346a1be7dee?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBzd2VhdGVyJTIwZG9nfGVufDF8fHx8MTc1ODYxMDg0N3ww&ixlib=rb-4.1.0&q=80&w=1080",
                rating: 4.7,
                variants: [
                    { type: "color", value: "#FF69B4", name: "Pink" },
                    { type: "color", value: "#87CEEB", name: "Blue" },
                    { type: "color", value: "#98FB98", name: "Green" }
                ],
                description: "Soft and warm sweater perfect for keeping your pet comfortable in cooler weather."
            },
            {
                id: 4,
                name: "Professional Grooming Kit",
                category: "grooming",
                price: 1299,
                image: "https://images.unsplash.com/photo-1576201836106-db1758fd1c97?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBncm9vbWluZyUyMGtpdHxlbnwxfHx8fDE3NTg2MTA4NTJ8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Bundle",
                rating: 4.9,
                variants: [
                    { type: "type", value: "basic", name: "Basic Kit" },
                    { type: "type", value: "premium", name: "Premium Kit" }
                ],
                description: "Complete grooming kit with professional-grade tools for home pet care."
            },
            {
                id: 5,
                name: "Stainless Steel Food Bowl",
                category: "feeding",
                price: 399,
                image: "https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzdGFpbmxlc3MlMjBzdGVlbCUyMGRvZyUyMGJvd2x8ZW58MXx8fHwxNzU4NjEwODU4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                rating: 4.6,
                variants: [
                    { type: "size", value: "S", name: "Small (1 cup)" },
                    { type: "size", value: "M", name: "Medium (2 cups)" },
                    { type: "size", value: "L", name: "Large (3 cups)" }
                ],
                description: "Durable stainless steel bowl that's easy to clean and won't retain odors."
            },
            {
                id: 6,
                name: "Retractable Dog Leash",
                category: "collars",
                price: 699,
                originalPrice: 899,
                image: "https://images.unsplash.com/photo-1601758174493-a2ead1d6c15e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxyZXRyYWN0YWJsZSUyMGRvZyUyMGxlYXNofGVufDF8fHx8MTc1ODYxMDg2M3ww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Sale",
                rating: 4.5,
                variants: [
                    { type: "length", value: "3m", name: "3 meters" },
                    { type: "length", value: "5m", name: "5 meters" }
                ],
                description: "High-quality retractable leash with comfortable grip and reliable locking mechanism."
            }
        ];

        let cart = [];
        let currentCategory = 'all';
        let displayedProducts = 6;

        // Load products based on current filters
        function loadProducts() {
            const filteredProducts = currentCategory === 'all' 
                ? products 
                : products.filter(product => product.category === currentCategory);
            
            const productsToShow = filteredProducts.slice(0, displayedProducts);
            const productsGrid = document.getElementById('products-grid');
            
            productsGrid.innerHTML = productsToShow.map(product => createProductCard(product)).join('');
            lucide.createIcons();
        }

        // Create product card HTML
        function createProductCard(product) {
            const discountPercentage = product.originalPrice 
                ? Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)
                : 0;

            return `
                <div class="product-card" data-category="${product.category}" data-name="${product.name.toLowerCase()}">
                    ${product.badge ? `<div class="product-badge ${product.badge.toLowerCase()}">${product.badge}</div>` : ''}
                    <div class="product-image">
                        <img src="${product.image}" alt="${product.name}" loading="lazy">
                        <div class="paw-print-hover">
                            <i data-lucide="eye" class="w-4 h-4 text-white"></i>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-1">${product.name}</h3>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex items-center">
                                    ${'★'.repeat(Math.floor(product.rating))}${'☆'.repeat(5-Math.floor(product.rating))}
                                </div>
                                <span class="text-sm text-gray-600">(${product.rating})</span>
                            </div>
                        </div>
                        
                        ${product.variants ? `
                        <div class="variant-swatches">
                            ${product.variants.slice(0, 3).map((variant, index) => `
                                <div class="variant-swatch ${variant.type === 'color' ? '' : 'bone-shape'} ${index === 0 ? 'active' : ''}" 
                                     style="${variant.type === 'color' ? `background-color: ${variant.value}` : 'background-color: #f3f4f6'}"
                                     title="${variant.name}">
                                </div>
                            `).join('')}
                        </div>
                        ` : ''}
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xl font-bold text-orange-600">₱${product.price.toLocaleString()}</span>
                                ${product.originalPrice ? `
                                    <span class="text-sm text-gray-500 line-through ml-2">₱${product.originalPrice.toLocaleString()}</span>
                                ` : ''}
                            </div>
                            ${discountPercentage > 0 ? `
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-semibold">
                                    ${discountPercentage}% OFF
                                </span>
                            ` : ''}
                        </div>
                        
                        <div class="flex gap-2">
                            <button onclick="openQuickView(${product.id})" 
                                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                Quick View
                            </button>
                            <button onclick="addToCart(${product.id})" 
                                    class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Filter products by category
        function filterByCategory(category) {
            currentCategory = category;
            displayedProducts = 6;
            
            // Update active tab
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-category="${category}"]`).classList.add('active');
            
            loadProducts();
        }

        // Filter products by search
        function filterProducts() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const productName = card.dataset.name;
                const shouldShow = productName.includes(searchTerm) && 
                                 (currentCategory === 'all' || card.dataset.category === currentCategory);
                
                card.style.display = shouldShow ? 'block' : 'none';
            });
        }

        // Load more products
        function loadMoreProducts() {
            displayedProducts += 6;
            loadProducts();
        }

        // Add product to cart
        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            if (product) {
                const existingItem = cart.find(item => item.id === productId);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({ ...product, quantity: 1 });
                }
                updateCartUI();
                showNotification(`${product.name} added to cart!`, 'success');
            }
        }

        // Add bundle to cart
        function addBundleToCart(bundleType) {
            const bundles = {
                'puppy-starter': { name: 'Puppy Starter Kit', price: 2499, id: 'bundle-1' },
                'cat-care': { name: 'Cat Care Combo', price: 1899, id: 'bundle-2' },
                'grooming-essentials': { name: 'Grooming Essentials', price: 3199, id: 'bundle-3' }
            };
            
            const bundle = bundles[bundleType];
            if (bundle) {
                const existingItem = cart.find(item => item.id === bundle.id);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({ ...bundle, quantity: 1, image: 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080' });
                }
                updateCartUI();
                showNotification(`${bundle.name} added to cart!`, 'success');
            }
        }

        // Update cart UI
        function updateCartUI() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = totalItems;
            document.getElementById('mini-cart-count').textContent = totalItems;
            
            // Animate cart icon
            const cartIcon = document.querySelector('.mini-cart');
            cartIcon.style.animation = 'cart-bounce 0.3s ease';
            setTimeout(() => {
                cartIcon.style.animation = '';
            }, 300);
        }

        // Open quick view
        function openQuickView(productId) {
            const product = products.find(p => p.id === productId);
            if (product) {
                const content = `
                    <div class="space-y-6">
                        <div class="aspect-square w-full rounded-lg overflow-hidden bg-gray-100">
                            <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">
                        </div>
                        
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">${product.name}</h3>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="flex items-center text-yellow-400">
                                    ${'★'.repeat(Math.floor(product.rating))}${'☆'.repeat(5-Math.floor(product.rating))}
                                </div>
                                <span class="text-sm text-gray-600">(${product.rating} rating)</span>
                            </div>
                            
                            <p class="text-gray-600 mb-4">${product.description}</p>
                            
                            <div class="flex items-center gap-4 mb-6">
                                <span class="text-2xl font-bold text-orange-600">₱${product.price.toLocaleString()}</span>
                                ${product.originalPrice ? `
                                    <span class="text-lg text-gray-500 line-through">₱${product.originalPrice.toLocaleString()}</span>
                                ` : ''}
                            </div>
                            
                            ${product.variants ? `
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-800 mb-3">Available Options:</h4>
                                <div class="flex gap-2 flex-wrap">
                                    ${product.variants.map(variant => `
                                        <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:border-orange-500 hover:text-orange-500 transition-colors">
                                            ${variant.name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                            ` : ''}
                            
                            <button onclick="addToCart(${product.id}); closeQuickView();" 
                                    class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('quick-view-content').innerHTML = content;
                document.getElementById('quick-view-drawer').classList.add('open');
                document.getElementById('drawer-overlay').classList.add('open');
                document.body.style.overflow = 'hidden';
                lucide.createIcons();
            }
        }

        // Close quick view
        function closeQuickView() {
            document.getElementById('quick-view-drawer').classList.remove('open');
            document.getElementById('drawer-overlay').classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        // Toggle cart
        function toggleCart() {
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'info');
                return;
            }
            
                const cartItems = cart.map(item => `
                    <div class="flex items-center gap-3 p-3 border-b group">
                        <img src="${item.image}" alt="${item.name}" class="w-12 h-12 object-cover rounded" />
                        <div class="flex-1">
                            <h4 class="font-medium">${item.name}</h4>
                            <p class="text-sm text-gray-600">Qty: ${item.quantity} × ₱${item.price.toLocaleString()}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <button onclick="decrementItem('${item.id}')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">-</button>
                                <button onclick="incrementItem('${item.id}')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">+</button>
                                <button onclick="removeFromCart('${item.id}')" class="ml-auto inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            const cartContent = `
                <div class="space-y-4">
                    <h3 class="text-xl font-bold">Shopping Cart</h3>
                    <div class="max-h-60 overflow-y-auto">
                        ${cartItems}
                    </div>
                    <div class="pt-4 border-t">
                        <div class="flex justify-between items-center mb-4">
                            <span class="font-semibold">Total: ₱${total.toLocaleString()}</span>
                        </div>
                        <button onclick="proceedToCheckout()" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('quick-view-content').innerHTML = cartContent;
            document.getElementById('quick-view-drawer').classList.add('open');
            document.getElementById('drawer-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        // Scroll to products section
        function scrollToProducts() {
            document.getElementById('products-section').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'info' ? 'bg-blue-500 text-white' :
                'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i data-lucide="${type === 'success' ? 'check-circle' : type === 'info' ? 'info' : 'alert-circle'}" class="w-5 h-5"></i>
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

        // Close quick view on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQuickView();
            }
        });

        // Proceed to checkout handler
        function proceedToCheckout() {
            window.location.href = 'login.php?redirect=shop.php';
        }

        // Cart item quantity helpers
        function incrementItem(id) {
            const item = cart.find(i => i.id == id);
            if (item) { item.quantity += 1; updateCartUI(); toggleCart(); }
        }
        function decrementItem(id) {
            const item = cart.find(i => i.id == id);
            if (item) {
                item.quantity -= 1;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id != id);
                }
                updateCartUI();
                if (cart.length === 0) {
                    closeQuickView();
                } else {
                    toggleCart();
                }
            }
        }
        function removeFromCart(id) {
            cart = cart.filter(i => i.id != id);
            updateCartUI();
            if (cart.length === 0) {
                closeQuickView();
            } else {
                toggleCart();
            }
            showNotification('Item removed from cart', 'info');
        }
                // Dropdown behavior (fix): open on hover, toggle on click
                (function initDropdowns(){
                    function initDropdown(wrapperId, buttonId, menuId){
                        const wrapper = document.getElementById(wrapperId);
                        const button = document.getElementById(buttonId);
                        const menu = document.getElementById(menuId);
                        const chevron = button && button.querySelector('i[data-lucide="chevron-down"]');
                        if(!wrapper||!button||!menu) return; 
                        let persist = false; let hideTimer;
                        function setOpen(open){
                            if(open){
                                menu.classList.add('open');
                                menu.setAttribute('aria-hidden','false');
                                if(chevron) chevron.classList.add('rotate-180');
                            } else {
                                menu.classList.remove('open');
                                menu.setAttribute('aria-hidden','true');
                                if(chevron) chevron.classList.remove('rotate-180');
                            }
                        }
                        setOpen(false);
                        wrapper.addEventListener('mouseenter',()=>{clearTimeout(hideTimer); setOpen(true);});
                        wrapper.addEventListener('mouseleave',()=>{ if(!persist){ hideTimer=setTimeout(()=>setOpen(false),150);} });
                        button.addEventListener('click',(e)=>{ e.stopPropagation(); persist=!persist; setOpen(persist); });
                        document.addEventListener('click',(e)=>{ if(!wrapper.contains(e.target)){ persist=false; setOpen(false);} });
                        document.addEventListener('keydown',(e)=>{ if(e.key==='Escape'){ persist=false; setOpen(false);} });
                    }
                    initDropdown('petsitterWrapper','petsitterButton','petsitterMenu');
                    initDropdown('appointmentsWrapper','appointmentsButton','appointmentsMenu');
                })();
    </script>
</body>
</html>