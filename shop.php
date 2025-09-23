<?php
// Refactored: connect products to database using model (removes dummy JS products)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/database.php';
require_once __DIR__.'/models/product.php';
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES,'UTF-8'); }
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); $csrf=$_SESSION['csrf'];
$q=trim($_GET['q']??''); $cat=$_GET['cat']??''; $sort=$_GET['sort']??'new'; $page=(int)($_GET['page']??1);
// Derive cart count from session (after server add)
$cartCount = 0; if(!empty($_SESSION['cart']) && is_array($_SESSION['cart'])){ foreach($_SESSION['cart'] as $c){ $cartCount += (int)($c['qty']??0); } }
$filters=['q'=>$q,'cat'=>$cat,'page'=>$page,'limit'=>12,'sort'=>$sort];
$res=product_fetch_paginated($connections,$filters); $items=$res['items']; $total=$res['total']; $pages=$res['pages']; $page=$res['page'];
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
            cursor: pointer;
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
            z-index: 10050; /* elevated above header */
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
            z-index: 10040; /* just below drawer */
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            height: 52px;
            flex: 0 0 auto; /* prevent shrinking so horizontal alignment is consistent */
        }
        #categoryTabsRow {
            display: flex;
            flex-wrap: nowrap;
            gap: 12px;
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
        }
        #categoryTabsRow::-webkit-scrollbar { height: 8px; }
        #categoryTabsRow::-webkit-scrollbar-track { background: transparent; }
        #categoryTabsRow::-webkit-scrollbar-thumb { background: linear-gradient(45deg,#f97316,#fb923c); border-radius:4px; }
        
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
    <header class="sticky top-0 z-40 border-b bg-background/80 backdrop-blur-sm">
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
                        <span id="cart-count" class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 bg-red-500 text-[11px] leading-5 font-semibold rounded-full flex items-center justify-center"><?php echo (int)$cartCount; ?></span>
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

    <!-- Bundle Deals Section (UI only; buttons currently show notification) -->
    <section class="py-16 bg-white relative z-10">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="gift" class="w-3 h-3 mr-1"></i>
                    Special Bundles
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">Bundle & Save</span>
                </h2>
                <p class="text-xl text-gray-700 max-w-3xl mx-auto">Get everything your pet needs with our specially curated bundles. Save more vs buying individually.</p>
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
                    <div class="space-y-2 mb-6 text-sm text-gray-700">
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Premium Puppy Collar</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Adjustable Leash</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Interactive Chew Toys (3x)</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Food & Water Bowls</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Training Treats</span></div>
                    </div>
                    <button type="button" onclick="showNotification('Bundle checkout coming soon','info')" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">Add Bundle</button>
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
                    <div class="space-y-2 mb-6 text-sm text-gray-700">
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Designer Cat Collar</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Interactive Feather Toy</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Scratching Post</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Catnip Toys (5x)</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Grooming Kit</span></div>
                    </div>
                    <button type="button" onclick="showNotification('Bundle checkout coming soon','info')" class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">Add Bundle</button>
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
                    <div class="space-y-2 mb-6 text-sm text-gray-700">
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Professional Brush Set</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Nail Clippers</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Pet Shampoo & Conditioner</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Microfiber Towels (2x)</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i><span>Ear Cleaning Solution</span></div>
                    </div>
                    <button type="button" onclick="showNotification('Bundle checkout coming soon','info')" class="w-full bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">Add Bundle</button>
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

                        <!-- Search + Category (server-side) -->
                        <div class="search-container" style="margin-bottom:28px;">
                            <form method="get" style="position:relative;display:flex;flex-direction:column;gap:18px;align-items:center;">
                                <div id="categoryTabsRow">
                                    <?php
                                        $catLabels=[''=>'All Products','food'=>'Feeding & Water','accessory'=>'Collars & Leashes','toy'=>'Toys & Entertainment','necessity'=>'Grooming Tools'];
                                        $catIcons=[''=>'grid-3x3','food'=>'bowl','accessory'=>'circle','toy'=>'gamepad-2','necessity'=>'scissors'];
                                        $activeCat = $cat === '' ? 'all' : $cat;
                                        foreach($catLabels as $k=>$label){
                                            $icon=$catIcons[$k] ?? 'paw-print';
                                            $dataCat = $k==='' ? 'all' : $k;
                                            $activeClass = $activeCat === $dataCat ? 'active' : '';
                                            echo '<button type="button" data-cat="'.h($dataCat).'" class="category-tab '.h($activeClass).'">'
                                                .'<i data-lucide="'.h($icon).'" class="w-4 h-4"></i>'
                                                .h($label).'</button>';
                                        }
                                    ?>
                                </div>
                                <div style="width:100%;max-width:420px;position:relative;">
                                    <?php if($cat!==''): ?><input type="hidden" name="cat" value="<?= h($cat) ?>" /><?php endif; ?>
                                    <input id="search-input" type="text" name="q" class="search-input" placeholder="Search for products..." value="<?= h($q) ?>" />
                                    <i data-lucide="search" class="search-icon w-5 h-5"></i>
                                </div>
                                <div style="display:flex;gap:10px;align-items:center;">
                                    <label style="font-size:12px;color:#6b7280;">Sort:</label>
                                    <select name="sort" onchange="this.form.submit()" style="padding:10px 14px;border:2px solid #e5e7eb;border-radius:25px;font-size:14px;">
                                        <?php $opts=['new'=>'Newest','price_asc'=>'Price ↑','price_desc'=>'Price ↓','name_asc'=>'Name A-Z','name_desc'=>'Name Z-A','stock_desc'=>'Stock']; foreach($opts as $k=>$label){ $sel=$sort===$k?'selected':''; echo '<option value="'.h($k).'" '.$sel.'>'.h($label).'</option>'; } ?>
                                    </select>
                                    <button type="submit" style="display:none">Apply</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="paw-grid">
                            <?php if($total===0): ?>
                                <p class="text-center col-span-full text-sm text-gray-500">No products found.</p>
                            <?php else: foreach($items as $p): $stock=(int)($p['products_stock']??0); $img=$p['products_image_url']??''; if($img && !preg_match('/^https?:/i',$img)) $img=ltrim($img,'/'); $disabled=$stock<=0; ?>
                                <div class="product-card" data-product-id="<?= (int)$p['products_id'] ?>" data-category="<?= h($p['products_category']) ?>" data-name="<?= strtolower(h($p['products_name'])) ?>">
                                    <?php if($stock<=0): ?><div class="product-badge" style="background:linear-gradient(135deg,#6b7280,#374151)">Out</div><?php endif; ?>
                                    <div class="product-image">
                                        <?php if($img): ?><img src="<?= h($img) ?>" alt="<?= h($p['products_name']) ?>" loading="lazy"><?php else: ?><span style="font-size:12px;color:#6b7280;display:flex;align-items:center;justify-content:center;height:100%;">No Image</span><?php endif; ?>
                                        <div class="paw-print-hover" onclick="openQuickViewDb(<?= (int)$p['products_id'] ?>)"><i data-lucide="eye" class="w-4 h-4 text-white"></i></div>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800 mb-1"><?= h($p['products_name']) ?></h3>
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500"><?= h(product_category_label($p['products_category'])) ?></div>
                                                <span class="text-xs text-gray-400">Stock: <?= $stock ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xl font-bold text-orange-600">₱<?= number_format((float)$p['products_price'],2) ?></span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" data-qv="<?= (int)$p['products_id'] ?>" class="quick-view-btn flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm">
                                                <i data-lucide="eye" class="w-4 h-4"></i> Quick View
                                            </button>
                                            <form method="post" action="shop/cart_add.php" class="flex gap-2 ajax-cart-add" data-product-id="<?= (int)$p['products_id'] ?>" style="flex:1;">
                                                <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                                                <input type="hidden" name="product_id" value="<?= (int)$p['products_id'] ?>" />
                                                <input type="number" name="qty" value="1" min="1" max="<?= max(1,$stock) ?>" class="w-20 rounded-lg border border-gray-300 px-2 py-2 text-sm" <?= $disabled?'disabled':''; ?> />
                                                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm" <?= $disabled?'disabled':''; ?>><i data-lucide="shopping-cart" class="w-4 h-4"></i> Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php if($pages>1): ?>
                            <div class="mt-10 flex flex-wrap gap-2 justify-center text-sm">
                                <?php $qs=[]; if($q!=='') $qs['q']=$q; if($cat!=='') $qs['cat']=$cat; if($sort!=='new') $qs['sort']=$sort; for($i=1;$i<=$pages;$i++){ $qs['page']=$i; $href='?'.http_build_query($qs); $active=$i===$page?'background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;border-color:#f97316':'background:#fff;color:#374151;'; echo '<a style="padding:10px 16px;border:2px solid #fcd9b6;border-radius:14px;font-weight:500;'.$active.'" href="'.h($href).'">'.$i.'</a>'; } ?>
                            </div>
                        <?php endif; ?>

    <!-- Cleaned JS (legacy static products removed) -->
    <script>
    document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); ensureDrawerRoot(); });

    // Quick View (DB powered)
    async function openQuickViewDb(id){
        // Show drawer immediately with loading skeleton for responsiveness
        const drawer=document.getElementById('quick-view-drawer');
        const overlay=document.getElementById('drawer-overlay');
        const contentEl=document.getElementById('quick-view-content');
        // Richer skeleton adapted from design reference
        contentEl.innerHTML=`<div class="space-y-6 animate-pulse">
            <div class="aspect-square w-full rounded-xl bg-gradient-to-br from-gray-200 to-gray-300"></div>
            <div class="h-7 bg-gray-200 rounded w-3/4"></div>
            <div class="flex gap-3">
                <div class="h-5 bg-gray-200 rounded w-24"></div>
                <div class="h-5 bg-gray-200 rounded w-16"></div>
            </div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>
            <div class="h-12 bg-gray-200 rounded-lg"></div>
        </div>`;
    ensureDrawerRoot();
    drawer.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden';
        try {
            const res = await fetch('product_view.php?id='+encodeURIComponent(id), {headers:{'Accept':'application/json'}});
            if(!res.ok) throw new Error('Not found');
            const p = await res.json();
            renderQuickView(p);
        } catch(e){
            contentEl.innerHTML='<div class="p-4 text-sm text-red-600">Unable to load product details. Please try again later.</div>';
        }
    }
    function renderQuickView(p){
        const full=Math.floor(p.rating||0); const empty=5-full;
        const variants=Array.isArray(p.variants)?p.variants:[];
        const discount=(p.originalPrice&&p.originalPrice>p.price)?Math.round(((p.originalPrice-p.price)/p.originalPrice)*100):0;
        const content=`<div class="space-y-6">
            <div class=\"aspect-square w-full rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center\">${p.image?`<img src=\"${p.image}\" alt=\"${escapeHtml(p.name)}\" class=\"w-full h-full object-cover\">`:'<div class=\"text-xs text-gray-400\">No Image</div>'}</div>
            <div>
                <h3 class=\"text-2xl font-bold text-gray-800 mb-2\">${escapeHtml(p.name)}</h3>
                ${p.rating?`<div class=\\"flex items-center gap-2 mb-4\\"><div class=\\"flex items-center text-yellow-400\\">${'★'.repeat(full)}${'☆'.repeat(empty)}</div><span class=\\"text-sm text-gray-600\\">(${p.rating.toFixed(1)} rating)</span></div>`:''}
                <p class=\"text-gray-600 mb-4 whitespace-pre-line\">${escapeHtml(p.description||'No description.')}</p>
                <div class=\"flex items-center gap-4 mb-6\">\n                    <span class=\"text-2xl font-bold text-orange-600\">₱${numberFormat(p.price)}</span>\n                    ${p.originalPrice&&p.originalPrice>p.price?`<span class=\\"text-lg text-gray-500 line-through\\">₱${numberFormat(p.originalPrice)}</span>`:''}\n                    ${discount>0?`<span class=\\"text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-semibold\\">${discount}% OFF</span>`:''}\n                </div>
                ${variants.length?`<div class=\\"mb-6\\"><h4 class=\\"font-medium text-gray-800 mb-3\\">Available Options:</h4><div class=\\"flex gap-2 flex-wrap\\">${variants.map((v,i)=>`<button type=\\"button\\" data-variant-index=\\"${i}\\" class=\\"px-3 py-2 border border-gray-300 rounded-lg text-sm hover:border-orange-500 hover:text-orange-500 transition-colors variant-btn\\">${escapeHtml(v.name||v.value||'Option')}</button>`).join('')}</div></div>`:''}
                <form method=\"post\" action=\"shop/cart_add.php\" class=\"space-y-4 ajax-cart-add\" data-product-id=\"${p.id}\">\n                    <input type=\"hidden\" name=\"csrf\" value=\"<?= h($csrf) ?>\" />\n                    <input type=\"hidden\" name=\"product_id\" value=\"${p.id}\" />\n                    <div class=\"flex items-center gap-3\">\n                        <span class=\"text-xs font-medium text-gray-500\">Quantity</span>\n                        <div class=\"inline-flex items-center rounded-full border border-gray-300 overflow-hidden bg-white shadow-sm\">\n                            <button type=\"button\" class=\"qty-btn px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 text-sm\" data-delta=\"-1\" aria-label=\"Decrease quantity\">-</button>\n                            <input name=\"qty\" value=\"1\" min=\"1\" max=\"${p.stock||99}\" type=\"number\" class=\"w-12 text-center text-sm border-0 focus:ring-0 focus:outline-none hide-number-spinner\" />\n                            <button type=\"button\" class=\"qty-btn px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 text-sm\" data-delta=\"1\" aria-label=\"Increase quantity\">+</button>\n                        </div>\n                        ${p.stock?`<span class=\\"text-[10px] text-gray-400\\">Stock: ${p.stock}</span>`:''}\n                    </div>\n                    <button type=\"submit\" class=\"w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center gap-2\">\n                        <i data-lucide=\"shopping-cart\" class=\"w-5 h-5\"></i> Add to Cart\n                    </button>\n                </form>
            </div>
        </div>`;
        document.getElementById('quick-view-content').innerHTML=content;
        document.getElementById('quick-view-drawer').classList.add('open');
        document.getElementById('drawer-overlay').classList.add('open');
        document.body.style.overflow='hidden';
        if(window.lucide) lucide.createIcons();
        // Variant button active state handling (simplified)
        document.querySelectorAll('#quick-view-content .variant-btn').forEach(btn=>{
            btn.addEventListener('click',()=>{
                btn.parentElement.querySelectorAll('.variant-btn').forEach(b=>b.classList.remove('border-orange-500','text-orange-500'));
                btn.classList.add('border-orange-500','text-orange-500');
            });
        });
        // Quantity pill handlers
        const qtyInput = document.querySelector('#quick-view-content input[name="qty"]');
        document.querySelectorAll('#quick-view-content .qty-btn').forEach(qb=>{
            qb.addEventListener('click',()=>{
                if(!qtyInput) return; const delta=parseInt(qb.getAttribute('data-delta')||'0',10); let v=parseInt(qtyInput.value||'1',10); if(isNaN(v)||v<1) v=1; v+=delta; const max=parseInt(qtyInput.getAttribute('max')||'99',10); if(v<1) v=1; if(v>max) v=max; qtyInput.value=v; });
        });
        if(qtyInput){ qtyInput.addEventListener('wheel',e=>{ e.preventDefault(); }, {passive:false}); }
    }
    function escapeHtml(str){return (str||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}
    function numberFormat(n){return (n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});}
    function closeQuickView(){document.getElementById('quick-view-drawer').classList.remove('open');document.getElementById('drawer-overlay').classList.remove('open');document.body.style.overflow='auto';}
    function ensureDrawerRoot(){
        let overlay=document.getElementById('drawer-overlay');
        let drawer=document.getElementById('quick-view-drawer');
        if(!overlay){
            overlay=document.createElement('div');
            overlay.id='drawer-overlay';
            overlay.className='drawer-overlay';
            overlay.addEventListener('click',closeQuickView);
            document.body.appendChild(overlay);
        }
        if(!drawer){
            drawer=document.createElement('div');
            drawer.id='quick-view-drawer';
            drawer.className='quick-view-drawer';
            drawer.innerHTML=`<div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-xl font-bold flex items-center gap-2"><i data-lucide="eye" class="w-5 h-5"></i> Quick View</h3>
                <button type="button" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Close Quick View" onclick="closeQuickView()"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div><div id="quick-view-content" class="p-6"></div>`;
            document.body.appendChild(drawer);
        } else {
            // Ensure header bar exists (in case of earlier markup changes)
            if(!drawer.querySelector('#quick-view-content')){
                drawer.innerHTML=`<div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-xl font-bold flex items-center gap-2"><i data-lucide="eye" class="w-5 h-5"></i> Quick View</h3>
                    <button type="button" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Close Quick View" onclick="closeQuickView()"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div><div id="quick-view-content" class="p-6"></div>`;
            }
        }
        // Boost z-index directly to avoid any stacking context issues
        drawer.style.zIndex='10050';
        overlay.style.zIndex='10040';
    }

    // Minimal cart stub (legacy JS cart removed)
    // Build cart data from server (embedded in page for this request)
    const serverCart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
    function toggleCart(){
        const items = Object.values(serverCart||{});
        if(!items.length){ showNotification('Your cart is empty','info'); return; }
        let total = 0;
        const rows = items.map(it=>{
            const line = (it.price*it.qty); total += line; return `<div class=\"flex items-center gap-3 p-3 border-b group\">
                ${it.image?`<img src=\"${it.image}\" class=\"w-12 h-12 object-cover rounded\" alt=\"${escapeHtml(it.name)}\">`:`<div class=\\"w-12 h-12 flex items-center justify-center text-xs text-gray-400 bg-gray-100 rounded\\">No Img</div>`}
                <div class=\"flex-1\">
                    <h4 class=\"font-medium text-sm\">${escapeHtml(it.name)}</h4>
                    <p class=\"text-xs text-gray-600\">₱${numberFormat(it.price)} × ${it.qty}</p>
                </div>
                <form method=\"post\" action=\"shop/cart_remove.php\" class=\"opacity-0 group-hover:opacity-100 transition-opacity\">
                    <input type=\"hidden\" name=\"csrf\" value=\"<?= h($csrf) ?>\" />
                    <input type=\"hidden\" name=\"product_id\" value=\"${it.id}\" />
                    <button type=\"submit\" class=\"text-red-500 hover:text-red-600 text-xs font-semibold flex items-center gap-1\"><i data-lucide=\"trash-2\" class=\"w-4 h-4\"></i>Remove</button>
                </form>
            </div>`;
        }).join('');
        const html = `<div class="space-y-4">
            <h3 class="text-xl font-bold">Cart</h3>
            <div class="max-h-64 overflow-y-auto">${rows}</div>
            <div class="pt-2 flex justify-between font-semibold"><span>Total:</span><span>₱${numberFormat(total)}</span></div>
            <button onclick="proceedToCheckout()" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all">Checkout</button>
        </div>`;
        document.getElementById('quick-view-content').innerHTML=html;
        document.getElementById('quick-view-drawer').classList.add('open');
        document.getElementById('drawer-overlay').classList.add('open');
        document.body.style.overflow='hidden';
    }
    function proceedToCheckout(){window.location.href='login.php?redirect=shop.php';}

    // AJAX Cart Handling
    function initAjaxCart(){
        document.querySelectorAll('form.ajax-cart-add').forEach(f=>{
            if(f.dataset.bound) return; f.dataset.bound='1';
            f.addEventListener('submit', async (e)=>{
                e.preventDefault();
                const fd=new FormData(f); fd.append('ajax','1');
                try{
                    const res = await fetch(f.action,{method:'POST',body:fd,headers:{'Accept':'application/json'}});
                    if(!res.ok) throw new Error();
                    const data = await res.json();
                    if(data.ok){
                        updateCartCount(data.cartCount);
                        showNotification('Added to cart','success');
                        // Update in-memory serverCart for drawer
                        serverCart[data.item.id]=data.item;
                    } else {
                        showNotification('Add failed','error');
                    }
                }catch(err){ showNotification('Add failed','error'); }
            });
        });

        // Delegate removal inside drawer
        document.addEventListener('submit', async (e)=>{
            const form = e.target;
            if(form.matches('form[action="shop/cart_remove.php"]')){
                e.preventDefault();
                const fd=new FormData(form); fd.append('ajax','1');
                try{
                    const res = await fetch(form.action,{method:'POST',body:fd,headers:{'Accept':'application/json'}});
                    if(!res.ok) throw new Error();
                    const data = await res.json();
                    if(data.ok){
                        // Remove from local cart memory
                        const pid = form.querySelector('[name="product_id"]').value;
                        delete serverCart[pid];
                        updateCartCount(data.cartCount);
                        if(Object.keys(serverCart).length===0){
                            // Close drawer if last item removed
                            closeQuickView();
                            showNotification('Cart is now empty','info');
                        } else {
                            showNotification('Removed from cart','info');
                            // Re-render drawer with remaining items
                            if(document.getElementById('quick-view-drawer').classList.contains('open')){
                                toggleCart();
                            }
                        }
                    } else {
                        showNotification('Remove failed','error');
                    }
                }catch(err){ showNotification('Remove failed','error'); }
            }
        });
    }
    function updateCartCount(n){ const el=document.getElementById('cart-count'); if(el) el.textContent=n; }
    document.addEventListener('DOMContentLoaded',initAjaxCart);
    // Delegate Quick View button clicks
    document.addEventListener('click',e=>{
        const btn = e.target.closest('.quick-view-btn');
        if(btn){
            const id = btn.getAttribute('data-qv');
            if(id) openQuickViewDb(id);
        }
    });

    // Client-side live filtering (enhances server-side rendered list; no reload needed)
    (function initLiveFiltering(){
        const searchInput = document.getElementById('search-input');
        const categoryTabs = document.querySelectorAll('#categoryTabsRow .category-tab[data-cat]');
        const originalActive = document.querySelector('#categoryTabsRow .category-tab.active');
        let activeCategory = originalActive ? originalActive.getAttribute('data-cat') : 'all';

        function applyFilter(){
            const q = (searchInput ? searchInput.value.trim().toLowerCase() : '');
            const cards = document.querySelectorAll('.product-card[data-name]');
            let visibleCount = 0;
            cards.forEach(card=>{
                const name = card.getAttribute('data-name') || '';
                const cat = card.getAttribute('data-category') || '';
                const matchCat = (activeCategory==='all') || (cat===activeCategory);
                const matchText = !q || name.indexOf(q) !== -1;
                if(matchCat && matchText){
                    card.style.display='';
                    visibleCount++;
                } else {
                    card.style.display='none';
                }
            });
            const emptyMsgId='no-client-filter-results';
            let emptyEl=document.getElementById(emptyMsgId);
            if(visibleCount===0){
                if(!emptyEl){
                    emptyEl=document.createElement('div');
                    emptyEl.id=emptyMsgId;
                    emptyEl.className='col-span-full text-center text-sm text-gray-500 mt-6';
                    emptyEl.textContent='No products match your filters.';
                    const grid=document.querySelector('.paw-grid');
                    if(grid) grid.appendChild(emptyEl);
                }
            } else if(emptyEl){ emptyEl.remove(); }
        }

        if(searchInput){
            searchInput.addEventListener('input',()=>{
                applyFilter();
            });
        }

        categoryTabs.forEach(tab=>{
            tab.addEventListener('click', ()=>{
                categoryTabs.forEach(t=>t.classList.remove('active'));
                tab.classList.add('active');
                activeCategory = tab.getAttribute('data-cat') || 'all';
                applyFilter();
                const params = new URLSearchParams(window.location.search);
                if(activeCategory==='all') params.delete('cat'); else params.set('cat', activeCategory);
                if(searchInput && searchInput.value.trim()) params.set('q', searchInput.value.trim()); else params.delete('q');
                const newUrl = window.location.pathname + (params.toString()?('?'+params.toString()):'');
                window.history.replaceState({}, '', newUrl);
            });
        });

        // Normalize data-name to lower-case once (server already attempted lower-case but ensure)
        document.querySelectorAll('.product-card[data-name]').forEach(c=>{
            c.setAttribute('data-name', (c.getAttribute('data-name')||'').toLowerCase());
        });
        applyFilter();
    })();

    // Show notification (updated implementation)
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
        if(window.lucide) lucide.createIcons();
        setTimeout(()=> notification.classList.remove('translate-x-full'), 80);
        setTimeout(()=> { notification.classList.add('translate-x-full'); setTimeout(()=> notification.remove(), 300); }, 3000);
    }

    // Escape key closes drawer
    document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeQuickView(); });

    // Dropdown behavior
    (function initDropdowns(){
        function initDropdown(wrapperId, buttonId, menuId){
            const wrapper=document.getElementById(wrapperId);
            const button=document.getElementById(buttonId);
            const menu=document.getElementById(menuId);
            const chevron=button && button.querySelector('i[data-lucide="chevron-down"]');
            if(!wrapper||!button||!menu) return; let persist=false; let hideTimer;
            function setOpen(o){ if(o){menu.classList.add('open');menu.setAttribute('aria-hidden','false'); if(chevron) chevron.classList.add('rotate-180');} else {menu.classList.remove('open');menu.setAttribute('aria-hidden','true'); if(chevron) chevron.classList.remove('rotate-180');} }
            setOpen(false);
            wrapper.addEventListener('mouseenter',()=>{clearTimeout(hideTimer); setOpen(true);});
            wrapper.addEventListener('mouseleave',()=>{ if(!persist){ hideTimer=setTimeout(()=>setOpen(false),150);} });
            button.addEventListener('click',e=>{ e.stopPropagation(); persist=!persist; setOpen(persist); });
            document.addEventListener('click',e=>{ if(!wrapper.contains(e.target)){ persist=false; setOpen(false);} });
            document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ persist=false; setOpen(false);} });
        }
        initDropdown('petsitterWrapper','petsitterButton','petsitterMenu');
        initDropdown('appointmentsWrapper','appointmentsButton','appointmentsMenu');
    })();
    </script>
</body>
</html>