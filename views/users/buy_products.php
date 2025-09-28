<?php
// Refactored: connect products to database using model (removes dummy JS products)
if (session_status() === PHP_SESSION_NONE) session_start();
// Use project root for includes (this file is in views/users)
require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/models/product.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES,'UTF-8'); }
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); $csrf=$_SESSION['csrf'];
$q=trim($_GET['q']??''); $cat=$_GET['cat']??''; $sort=$_GET['sort']??'new'; $page=(int)($_GET['page']??1);
// Current user context for header
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);
// Derive cart count from session (after server add)
$cartCount = 0; if(!empty($_SESSION['cart']) && is_array($_SESSION['cart'])){ foreach($_SESSION['cart'] as $c){ $cartCount += (int)($c['qty']??0); } }
$filters=['q'=>$q,'cat'=>$cat,'page'=>$page,'limit'=>12,'sort'=>$sort,'include_inactive'=>true];
$res=product_fetch_paginated($connections,$filters); $items=$res['items']; $total=$res['total']; $pages=$res['pages']; $page=$res['page'];
// If partial request (?partial=1) return only the products grid + pagination fragments for AJAX
if(($_GET['partial']??'')==='1'){
    ob_start();
    ?>
    <div class="paw-grid" id="products-grid">
        <?php if($total===0): ?>
            <p class="text-center col-span-full text-sm text-gray-500">No products found.</p>
    <?php else: foreach($items as $p): $stock=(int)($p['products_stock']??0); $img=$p['products_image_url']??''; if($img && !preg_match('/^https?:/i',$img)) $img='../../'.ltrim($img,'/'); $inactive = !(int)($p['products_active'] ?? 0); $disabled = $stock<=0 || $inactive; ?>
            <div class="product-card relative" data-product-id="<?= (int)$p['products_id'] ?>" data-category="<?= h($p['products_category']) ?>" data-name="<?= strtolower(h($p['products_name'])) ?>">
                <?php if($stock<=0): ?>
                    <div class="product-badge" style="background:linear-gradient(135deg,#6b7280,#374151)">Out</div>
                <?php endif; ?>
                <div class="product-image">
                    <?php if($img): ?><img src="<?= h($img) ?>" alt="<?= h($p['products_name']) ?>" loading="lazy"><?php else: ?><span style="font-size:12px;color:#6b7280;display:flex;align-items:center;justify-content:center;height:100%;">No Image</span><?php endif; ?>
                    <div class="paw-print-hover" onclick="openQuickViewDb(<?= (int)$p['products_id'] ?>)"><i data-lucide="eye" class="w-4 h-4 text-white"></i></div>
                </div>
                <div class="space-y-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1"><?= h($p['products_name']) ?></h3>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200 text-[10px]">
                                <i data-lucide="tags" class="w-3 h-3"></i>
                                <?= h(product_category_label($p['products_category'])) ?>
                            </span>
                            <?php if(!empty($p['products_pet_type'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px]">
                                    <i data-lucide="paw-print" class="w-3 h-3"></i>
                                    <?= h($p['products_pet_type']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="text-[10px] text-gray-400">Stock: <?= $stock ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $inactive ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-green-100 text-green-700 border border-green-300' ?>"><?= $inactive ? 'Not Available' : 'Available' ?></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xl font-bold text-orange-600">₱<?= number_format((float)$p['products_price'],2) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $inactive ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-green-100 text-green-700 border border-green-300' ?>">
                            <?= $inactive ? 'Not Available' : 'Available' ?>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" data-qv="<?= (int)$p['products_id'] ?>" class="quick-view-btn flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm">
                            <i data-lucide="eye" class="w-4 h-4"></i> Quick View
                        </button>
                        <form method="post" action="../../shop/cart_add.php" class="flex gap-2 ajax-cart-add" data-product-id="<?= (int)$p['products_id'] ?>" style="flex:1;">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                            <input type="hidden" name="product_id" value="<?= (int)$p['products_id'] ?>" />
                            <input type="number" name="qty" value="1" min="1" max="<?= max(1,$stock) ?>" class="w-20 rounded-lg border border-gray-300 px-2 py-2 text-sm" <?= $disabled?'disabled':''; ?> />
                            <button type="submit" class="flex-1 <?= $disabled?'bg-gray-300 cursor-not-allowed':'bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700' ?> text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm" <?= $disabled?'disabled':''; ?>>
                                <i data-lucide="shopping-cart" class="w-4 h-4"></i> <?= $inactive ? 'Unavailable' : 'Add' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <div id="pagination-wrapper">
    <?php if($pages>1): ?>
        <div class="mt-10 flex flex-wrap gap-2 justify-center text-sm">
            <?php $qs=[]; if($q!=='') $qs['q']=$q; if($cat!=='') $qs['cat']=$cat; if($sort!=='new') $qs['sort']=$sort; for($i=1;$i<=$pages;$i++){ $qs['page']=$i; $href='?'.http_build_query($qs); $active=$i===$page?'background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;border-color:#f97316':'background:#fff;color:#374151;'; echo '<a class="page-link" data-page="'.h($i).'" style="padding:10px 16px;border:2px solid #fcd9b6;border-radius:14px;font-weight:500;'.$active.'" href="'.h($href).'">'.$i.'</a>'; } ?>
        </div>
    <?php endif; ?>
    </div>
    <?php
    echo ob_get_clean();
    exit;
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
    <link rel="stylesheet" href="../../globals.css">

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
        .animated-gradient {
            background-size: 200% 200%;
            animation: gradient-shift 14s ease infinite;
        }
        /* Guided hero background per provided snippet */
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradient-shift 6s ease infinite;
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
        
        /* Old design core card styles restored */
        .paw-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap:24px; padding:20px 0; }
        .product-card { background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(255,255,255,.85)); backdrop-filter:blur(15px); border:2px solid rgba(249,115,22,.1); border-radius:20px; padding:20px; transition:all .4s cubic-bezier(.175,.885,.32,1.275); position:relative; overflow:hidden; }
        .product-card::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent); transition:left .5s ease; }
        .product-card:hover::before { left:100%; }
        .product-card:hover { transform:translateY(-10px) scale(1.03); border-color:rgba(249,115,22,.3); box-shadow:0 20px 40px rgba(249,115,22,.2); }
        .product-image { width:100%; height:200px; border-radius:15px; overflow:hidden; margin-bottom:16px; position:relative; background:linear-gradient(45deg,#f9fafb,#f3f4f6); }
        .product-image img { width:100%; height:100%; object-fit:cover; transition:transform .4s ease; }
        .product-card:hover .product-image img { transform:scale(1.1); }
        .paw-print-hover { position:absolute; top:10px; right:10px; width:30px; height:30px; background:rgba(249,115,22,.9); border-radius:50%; display:flex; align-items:center; justify-content:center; opacity:0; transform:scale(0); transition:all .3s ease; cursor:pointer; }
        .product-card:hover .paw-print-hover { opacity:1; transform:scale(1); animation:paw-bounce .6s ease-in-out; }
        .product-badge { position:absolute; top:15px; left:15px; background:linear-gradient(135deg,#6b7280,#374151); color:#fff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        
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
        
    .category-tabs { display:flex; gap:14px; margin:20px 0 32px; flex-wrap:wrap; justify-content:center; overflow:visible; padding-bottom:6px; width:100%; }
    .category-tabs::-webkit-scrollbar { height:8px; }
    .category-tabs::-webkit-scrollbar-track { background:transparent; }
    .category-tabs::-webkit-scrollbar-thumb { background:linear-gradient(45deg,#f97316,#fb923c); border-radius:4px; }
        .category-tab { padding:14px 32px; border-radius:25px; background:#fff; border:2px solid #e5e7eb; cursor:pointer; transition:all .3s ease; font-weight:500; position:relative; overflow:hidden; display:inline-flex; align-items:center; line-height:1.15; justify-content:center; flex:1 1 220px; min-width:220px; font-size:15px; }
        @media (min-width: 768px){
            .category-tabs{ flex-wrap:nowrap; }
            .category-tab{ flex:1 1 0; min-width:180px; }
        }
        @media (min-width: 1280px){
            .category-tab{ min-width:220px; }
        }
        @media (max-width:640px){
            .category-tab { padding:12px 20px; font-size:13px; }
        }
    .category-tab i { margin-right:8px; }
        .category-tab::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(249,115,22,.1),transparent); transition:left .5s ease; }
        .category-tab:hover::before { left:100%; }
        .category-tab:hover { border-color:#f97316; color:#f97316; }
        .category-tab.active { background:linear-gradient(135deg,#f97316,#fb923c); color:#fff; border-color:#f97316; }
    /* Search + Sort bar */
    .filter-bar { width:100%; display:flex; gap:16px; align-items:center; justify-content:center; flex-wrap:wrap; }
    .search-wrapper { position:relative; flex:1 1 340px; max-width:480px; }
    .search-wrapper .search-input { width:100%; }
    .sort-wrapper { display:flex; align-items:center; gap:8px; }
    .sort-wrapper select { padding:10px 14px; border:2px solid #e5e7eb; border-radius:25px; font-size:14px; background:#fff; }
    @media (max-width:640px){ .filter-bar { flex-direction:column; align-items:stretch; } .sort-wrapper { justify-content:flex-start; } }
        
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
            width: 100%;
            max-width: 1000px;
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
    <!-- Header (shared include) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>


    <!-- Hero Section (updated per provided guide) -->
    <section id="shop-hero" class="relative py-16 overflow-hidden gradient-bg">
        <!-- Floating background elements (scoped to hero) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
            <div class="absolute top-20 left-10 opacity-20" data-parallax="0.18">
                <div class="floating-element">
                    <i data-lucide="paw-print" class="w-16 h-16 text-orange-200 rotate-12"></i>
                </div>
            </div>
            <div class="absolute top-40 right-20 opacity-20" data-parallax="0.12">
                <div class="floating-element">
                    <i data-lucide="gift" class="w-12 h-12 text-amber-200 -rotate-12"></i>
                </div>
            </div>
            <div class="absolute bottom-20 left-16 opacity-20" data-parallax="0.15">
                <div class="floating-element">
                    <i data-lucide="heart" class="w-14 h-14 text-orange-100 rotate-45"></i>
                </div>
            </div>
        </div>
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
                            <form method="get" style="position:relative;display:flex;flex-direction:column;gap:12px;align-items:stretch;width:100%;max-width:1000px;">
                                <div class="filter-bar">
                                    <div class="search-wrapper">
                                        <input type="hidden" name="cat" id="cat-input" value="<?= h($cat) ?>" />
                                        <input id="search-input" type="text" name="q" class="search-input" placeholder="Search for products..." value="<?= h($q) ?>" />
                                        <i data-lucide="search" class="search-icon w-5 h-5"></i>
                                    </div>
                                    <div class="sort-wrapper">
                                        <label style="font-size:12px;color:#6b7280;">Sort:</label>
                                        <select name="sort" onchange="this.form.submit()">
                                            <?php $opts=['new'=>'Newest','price_asc'=>'Price ↑','price_desc'=>'Price ↓','name_asc'=>'Name A-Z','name_desc'=>'Name Z-A','stock_desc'=>'Stock']; foreach($opts as $k=>$label){ $sel=$sort===$k?'selected':''; echo '<option value="'.h($k).'" '.$sel.'>'.h($label).'</option>'; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="category-tabs" id="category-tabs">
                                    <?php
                                        // Using the requested design/order; mapped to existing backend categories
                                        $tabs = [
                                            ['key'=>'',          'data'=>'all',       'label'=>'All Products',        'icon'=>'grid-3x3'],
                                            ['key'=>'accessory', 'data'=>'accessory', 'label'=>'Collars & Leashes',   'icon'=>'circle'],
                                            ['key'=>'toy',       'data'=>'toy',       'label'=>'Toys & Entertainment','icon'=>'gamepad-2'],
                                            ['key'=>'necessity', 'data'=>'necessity', 'label'=>'Grooming Tools',      'icon'=>'scissors'],
                                            ['key'=>'food',      'data'=>'food',      'label'=>'Feeding & Water',     'icon'=>'bowl'],
                                        ];
                                        $activeData = $cat === '' ? 'all' : $cat;
                                        foreach($tabs as $t){
                                            $isActive = ($activeData === $t['data']) ? 'active' : '';
                                            echo '<button type="button" data-cat="'.h($t['data']).'" class="category-tab '.h($isActive).'">'
                                                .'<i data-lucide="'.h($t['icon']).'" class="w-4 h-4 mr-2 inline"></i>'
                                                .h($t['label']).'</button>';
                                        }
                                    ?>
                                </div>
                                <button type="submit" style="display:none">Apply</button>
                            </form>
                        </div>
                        
                        <div id="products-wrapper">
                        <div class="paw-grid" id="products-grid">
                            <?php if($total===0): ?>
                                <p class="text-center col-span-full text-sm text-gray-500">No products found.</p>
                            <?php else: foreach($items as $p): $stock=(int)($p['products_stock']??0); $img=$p['products_image_url']??''; if($img && !preg_match('/^https?:/i',$img)) $img='../../'.ltrim($img,'/'); $disabled=$stock<=0; ?>
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
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200 text-[10px]">
                                                    <i data-lucide="tags" class="w-3 h-3"></i>
                                                    <?= h(product_category_label($p['products_category'])) ?>
                                                </span>
                                                <?php if(!empty($p['products_pet_type'])): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px]">
                                                        <i data-lucide="paw-print" class="w-3 h-3"></i>
                                                        <?= h($p['products_pet_type']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-[10px] text-gray-400">Stock: <?= $stock ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xl font-bold text-orange-600">₱<?= number_format((float)$p['products_price'],2) ?></span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" data-qv="<?= (int)$p['products_id'] ?>" class="quick-view-btn flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm">
                                                <i data-lucide="eye" class="w-4 h-4"></i> Quick View
                                            </button>
                                            <form method="post" action="../../shop/cart_add.php" class="flex gap-2 ajax-cart-add" data-product-id="<?= (int)$p['products_id'] ?>" style="flex:1;">
                                                <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                                                <input type="hidden" name="product_id" value="<?= (int)$p['products_id'] ?>" />
                                                <input type="number" name="qty" value="1" min="1" max="<?= max(1,$stock) ?>" class="w-20 rounded-lg border border-gray-300 px-2 py-2 text-sm" <?= $disabled?'disabled':''; ?> />
                                                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm" <?= $disabled?'disabled':''; ?>><i data-lucide="shopping-cart" class="w-4 h-4"></i> Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div><!-- /paw-grid -->
                        <div id="pagination-wrapper">
                        <?php if($pages>1): ?>
                            <div class="mt-10 flex flex-wrap gap-2 justify-center text-sm">
                                <?php $qs=[]; if($q!=='') $qs['q']=$q; if($cat!=='') $qs['cat']=$cat; if($sort!=='new') $qs['sort']=$sort; for($i=1;$i<=$pages;$i++){ $qs['page']=$i; $href='?'.http_build_query($qs); $active=$i===$page?'background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;border-color:#f97316':'background:#fff;color:#374151;'; echo '<a class="page-link" data-page="'.h($i).'" style="padding:10px 16px;border:2px solid #fcd9b6;border-radius:14px;font-weight:500;'.$active.'" href="'.h($href).'">'.$i.'</a>'; } ?>
                            </div>
                        <?php endif; ?>
                        </div><!-- /pagination-wrapper -->
                        </div><!-- /products-wrapper -->

    <!-- Cleaned JS (legacy static products removed) -->
    <script>
    document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); ensureDrawerRoot(); });

    // Ensure relative DB image paths work in this page (views/users/*) by prefixing project base
    const IMAGE_BASE='../../';
    function normalizeImageUrl(u){
        if(!u) return '';
        // keep absolute or already-prefixed paths
        if(/^https?:\/\//i.test(u) || /^data:/i.test(u) || /^blob:/i.test(u) || /^(\.\.\/|\.\/)/.test(u) || u.startsWith(IMAGE_BASE)) return u;
        return IMAGE_BASE + u.replace(/^\/+/, '');
    }

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
    try{ setDrawerTitle('Quick View'); }catch(_){}
    drawer.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden';
        try {
            // Fetch product JSON details from server endpoint
            const res = await fetch('../../shop/product_view.php?id='+encodeURIComponent(id), {headers:{'Accept':'application/json'}});
            if(!res.ok) throw new Error('Not found');
            const p = await res.json();
            renderQuickView(p);
        } catch(e){
            contentEl.innerHTML='<div class="p-4 text-sm text-red-600">Unable to load product details. Please try again later.</div>';
        }
    }
    function renderQuickView(p){
        // Map backend categories to display labels (client-side)
        const catLabelMap={
            'accessory':'Collars & Leashes',
            'toy':'Toys & Entertainment',
            'necessity':'Grooming Tools',
            'food':'Feeding & Water'
        };
        const catLabel = catLabelMap[p.category] || (p.category || '').toString();
        // Rating, badge, and variant options removed (columns dropped from DB)
        const discount=(typeof p.discountPercent==='number')?p.discountPercent:((p.originalPrice&&p.originalPrice>p.price)?Math.round(((p.originalPrice-p.price)/p.originalPrice)*100):0);
    const outOfStock = !p.stock || p.stock<=0;
    const inactive = (p.active===0) || (p.products_active===0) || (!p.active && p.products_active===0);
        const imgSrc = normalizeImageUrl(p.image); // NEW: normalize DB image path
        const content=`<div class="space-y-6">
            <div class=\"aspect-square w-full rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center\">${imgSrc?`<img src=\"${imgSrc}\" alt=\"${escapeHtml(p.name)}\" class=\"w-full h-full object-cover\">`:'<div class=\"text-xs text-gray-400\">No Image</div>'}</div>
            <div>
                <h3 class=\"text-2xl font-bold text-gray-800 mb-2\">${escapeHtml(p.name)}</h3>
                <div class=\"flex flex-wrap items-center gap-2 mb-3 text-xs\">
                    ${catLabel?`<span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-200\"><i data-lucide=\"tags\" class=\"w-3 h-3\"></i>${escapeHtml(catLabel)}</span>`:''}
                    ${p.pet_type?`<span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200\"><i data-lucide=\"paw-print\" class=\"w-3 h-3\"></i>${escapeHtml(p.pet_type)}</span>`:''}
                    ${typeof p.stock!=='undefined'?`<span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200\"><i data-lucide=\"boxes\" class=\"w-3 h-3\"></i>Stock: ${p.stock}</span>`:''}
                    <span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold ${inactive?'bg-red-100 text-red-700 border border-red-300':'bg-green-100 text-green-700 border border-green-300'}\">${inactive?'Not Available':'Available'}</span>
                    ${discount>0?`<span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-200\"><i data-lucide=\"badge-percent\" class=\"w-3 h-3\"></i>${discount}% OFF</span>`:''}
                </div>
                <p class=\"text-gray-600 mb-4 whitespace-pre-line\">${escapeHtml(p.description||'No description.')}</p>
                <div class=\"flex items-center gap-4 mb-6\">\n                    <span class=\"text-2xl font-bold text-orange-600\">₱${numberFormat(p.price)}</span>\n                    ${p.originalPrice&&p.originalPrice>p.price?`<span class=\\"text-lg text-gray-500 line-through\\">₱${numberFormat(p.originalPrice)}</span>`:''}\n                    ${discount>0?`<span class=\\"text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-semibold\\">${discount}% OFF</span>`:''}\n                </div>
                <form method=\"post\" action=\"../../shop/cart_add.php\" class=\"space-y-4 ajax-cart-add\" data-product-id=\"${p.id}\">\n                    <input type=\"hidden\" name=\"csrf\" value=\"<?= h($csrf) ?>\" />\n                    <input type=\"hidden\" name=\"product_id\" value=\"${p.id}\" />\n                    <div class=\"flex items-center gap-3\">\n                        <span class=\"text-xs font-medium text-gray-500\">Quantity</span>\n                        <div class=\"inline-flex items-center rounded-full border border-gray-300 overflow-hidden bg-white shadow-sm\">\n                            <button type=\"button\" class=\"qty-btn px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 text-sm\" data-delta=\"-1\" aria-label=\"Decrease quantity\" ${outOfStock||inactive?'disabled':''}>-</button>\n                            <input name=\"qty\" value=\"1\" min=\"1\" max=\"${p.stock||0}\" type=\"number\" class=\"w-12 text-center text-sm border-0 focus:ring-0 focus:outline-none hide-number-spinner\" ${outOfStock||inactive?'disabled':''} />\n                            <button type=\"button\" class=\"qty-btn px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 text-sm\" data-delta=\"1\" aria-label=\"Increase quantity\" ${outOfStock||inactive?'disabled':''}>+</button>\n                        </div>\n                        ${p.stock?`<span class=\\"text-[10px] text-gray-400\\">Stock: ${p.stock}</span>`:'<span class=\\"text-[10px] text-red-500\\">Out of stock</span>'} <span class=\"px-2 py-0.5 rounded-full text-[10px] font-semibold ${inactive?'bg-red-100 text-red-700 border border-red-300':'bg-green-100 text-green-700 border border-green-300'}\">${inactive?'Not Available':'Available'}</span>\n                    </div>\n                    <button type=\"submit\" class=\"w-full ${outOfStock||inactive?'bg-gray-300 cursor-not-allowed':'bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700'} text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center gap-2\" ${outOfStock||inactive?'disabled':''}>\n                        <i data-lucide=\"shopping-cart\" class=\"w-5 h-5\"></i> ${(outOfStock||inactive)?'Unavailable':'Add to Cart'}\n                    </button>\n                </form>
            </div>
        </div>`;
        document.getElementById('quick-view-content').innerHTML=content;
        document.getElementById('quick-view-drawer').classList.add('open');
        document.getElementById('drawer-overlay').classList.add('open');
        document.body.style.overflow='hidden';
        if(window.lucide) lucide.createIcons();
    // Bind AJAX cart handlers for newly injected form
    try{ initAjaxCart(); }catch(_){}
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
                <h3 id="drawer-title" class="text-xl font-bold flex items-center gap-2"><i data-lucide="eye" class="w-5 h-5"></i> Quick View</h3>
                <div class="flex items-center gap-2">
                    <button type="button" id="drawer-cart-btn" class="relative p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Open Cart" onclick="openCartFromHeader()">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <span id="drawer-cart-count" class="absolute -top-1 -right-1 min-w-[18px] h-4 px-1 bg-red-500 text-[10px] leading-4 font-semibold rounded-full text-white text-center">0</span>
                    </button>
                    <button type="button" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Close Quick View" onclick="closeQuickView()"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
            </div>
            <div id="drawer-notification-slot" class="px-6 py-3 hidden"></div>
            <div id="quick-view-content" class="p-6"></div>`;
            document.body.appendChild(drawer);
        } else {
            // Ensure header bar exists (in case of earlier markup changes)
            if(!drawer.querySelector('#quick-view-content')){
                drawer.innerHTML=`<div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 id="drawer-title" class="text-xl font-bold flex items-center gap-2"><i data-lucide="eye" class="w-5 h-5"></i> Quick View</h3>
                    <div class="flex items-center gap-2">
                        <button type="button" id="drawer-cart-btn" class="relative p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Open Cart" onclick="openCartFromHeader()">
                            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                            <span id="drawer-cart-count" class="absolute -top-1 -right-1 min-w-[18px] h-4 px-1 bg-red-500 text-[10px] leading-4 font-semibold rounded-full text-white text-center">0</span>
                        </button>
                        <button type="button" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Close Quick View" onclick="closeQuickView()"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                </div>
                <div id="drawer-notification-slot" class="px-6 py-3 hidden"></div>
                <div id="quick-view-content" class="p-6"></div>`;
            }
        }
        // Boost z-index directly to avoid any stacking context issues
        drawer.style.zIndex='10050';
        overlay.style.zIndex='10040';
        if(window.lucide) lucide.createIcons();
        try{ syncDrawerCartCount(); }catch(_){}
    }

    function setDrawerTitle(t){ const el=document.getElementById('drawer-title'); if(el) el.textContent=t; }
    function openCartFromHeader(){ toggleCart(); }
    function syncDrawerCartCount(){
        const main=document.getElementById('cart-count');
        const v = main ? (main.textContent||'0') : '0';
        const el = document.getElementById('drawer-cart-count');
        if(el) el.textContent = v;
    }
    function showDrawerNotification(message, type='success'){
        const drawer=document.getElementById('quick-view-drawer');
        if(!drawer || !drawer.classList.contains('open')) return;
        const slot = document.getElementById('drawer-notification-slot');
        if(!slot) return;
        const color = type==='success' ? 'bg-green-100 text-green-800 border-green-200' : (type==='info' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-red-100 text-red-800 border-red-200');
        const icon = type==='success' ? 'check-circle' : (type==='info' ? 'info' : 'alert-circle');
        slot.innerHTML = `<div class="flex items-center gap-2 px-3 py-2 border rounded-md ${color}"><i data-lucide="${icon}" class="w-4 h-4"></i><span class="text-sm">${message}</span></div>`;
        slot.classList.remove('hidden');
        if(window.lucide) lucide.createIcons();
        clearTimeout(slot._timer);
        slot._timer = setTimeout(()=>{ slot.classList.add('hidden'); slot.innerHTML=''; }, 2200);
    }

    // Minimal cart stub (legacy JS cart removed)
    // Build cart data from server (embedded in page for this request)
    const serverCart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
    // Normalize any session item images immediately
    for(const k in serverCart){ if(serverCart[k] && serverCart[k].image){ serverCart[k].image = normalizeImageUrl(serverCart[k].image); } }

    function toggleCart(){
        const items = Object.values(serverCart||{});
        let contentHtml = '';
        if(!items.length){
            contentHtml = `
                <div class="py-10 text-center text-gray-600">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center"><i data-lucide="shopping-cart" class="w-7 h-7 text-gray-400"></i></div>
                    <h4 class="text-lg font-semibold mb-1">Your cart is empty</h4>
                    <p class="text-sm text-gray-500 mb-6">Add items to get started.</p>
                    <button type="button" onclick="closeQuickView()" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border text-gray-700 hover:bg-gray-50">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Continue shopping
                    </button>
                </div>
            `;
        } else {
            let total = 0;
            const rows = items.map(it=>{
                const line = (it.price*it.qty); total += line;
                const img = normalizeImageUrl(it.image); // NEW: normalize image before rendering
                return `<div class="flex items-center gap-3 p-3 border-b group" data-cart-line="${it.id}">
                    ${img?`<img src="${img}" class="w-12 h-12 object-cover rounded" alt="${escapeHtml(it.name)}">`:`<div class=\"w-12 h-12 flex items-center justify-center text-xs text-gray-400 bg-gray-100 rounded\">No Img</div>`}
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-sm truncate">${escapeHtml(it.name)}</h4>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="inline-flex items-center rounded-full border border-gray-300 bg-white overflow-hidden shadow-sm">
                                <button type="button" class="qty-cart-btn px-2 text-gray-600 hover:bg-gray-100" data-delta="-1" data-pid="${it.id}">-</button>
                                <input type="number" class="w-10 text-center text-xs border-0 focus:ring-0 focus:outline-none cart-line-qty" value="${it.qty}" min="1" data-pid="${it.id}" />
                                <button type="button" class="qty-cart-btn px-2 text-gray-600 hover:bg-gray-100" data-delta="1" data-pid="${it.id}">+</button>
                            </div>
                            <span class="text-[11px] text-gray-500">₱${numberFormat(it.price)}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="text-xs font-semibold">₱${numberFormat(line)}</span>
                        <form method="post" action="../../shop/cart_remove.php" class="opacity-0 group-hover:opacity-100 transition-opacity"> 
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                            <input type="hidden" name="product_id" value="${it.id}" />
                            <button type="submit" class="text-red-500 hover:text-red-600 text-[10px] font-semibold flex items-center gap-1"><i data-lucide="trash-2" class="w-3 h-3"></i>Remove</button>
                        </form>
                    </div>
                </div>`;
            }).join('');
            contentHtml = `<div class="space-y-4">
                <h3 class="text-xl font-bold">Cart</h3>
                <div class="max-h-64 overflow-y-auto">${rows}</div>
                <div class="pt-2 flex justify-between font-semibold"><span>Total:</span><span id="drawer-cart-total">₱${numberFormat(total)}</span></div>
                <button onclick="proceedToCheckout()" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-6 rounded-lg transition-all">Checkout</button>
            </div>`;
        }
        try{ setDrawerTitle('Cart'); }catch(_){ }
        document.getElementById('quick-view-content').innerHTML = contentHtml;
        document.getElementById('quick-view-drawer').classList.add('open');
        document.getElementById('drawer-overlay').classList.add('open');
        document.body.style.overflow='hidden';
        if(window.lucide) lucide.createIcons();
        bindCartQtyControls();
    }
    // Limit removal notifications to the drawer; re-render drawer (with inline empty state)
    document.addEventListener('submit', async (e)=>{
        const form = e.target;
        if(form.matches('form[action="../../shop/cart_remove.php"]')){
            e.preventDefault();
            const fd=new FormData(form); fd.append('ajax','1');
            try{
                const res = await fetch(form.action,{method:'POST',body:fd,headers:{'Accept':'application/json'}});
                if(!res.ok) throw new Error('Request failed');
                const data = await res.json();
                if(data.ok){
                    const pid = form.querySelector('[name="product_id"]').value;
                    delete serverCart[pid];
                    updateCartCount(data.cartCount);
                    showDrawerNotification('Removed from cart','info');
                    if(document.getElementById('quick-view-drawer').classList.contains('open')){
                        toggleCart();
                    }
                } else {
                    showDrawerNotification('Remove failed','error');
                }
            }catch(err){ showDrawerNotification('Remove failed','error'); }
        }
    });

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
                        showDrawerNotification('Added to cart','success');
                        // Update in-memory serverCart for drawer (normalize image path)
                        if(data.item){
                            data.item.image = normalizeImageUrl(data.item.image);
                            serverCart[data.item.id]=data.item;
                        }
                    } else {
                        showNotification('Add failed','error');
                        showDrawerNotification('Add failed','error');
                    }
                }catch(err){ showNotification('Add failed','error'); }
            });
        });
    }
    function updateCartCount(n){ const el=document.getElementById('cart-count'); if(el) el.textContent=n; try{ syncDrawerCartCount(); }catch(_){} }
    function proceedToCheckout(){ window.location.href='checkout.php'; }
    async function cartUpdateQty(productId, newQty){
        const fd=new FormData(); fd.append('csrf','<?= h($csrf) ?>'); fd.append('product_id',productId); fd.append('qty',newQty);
        try{
            const res = await fetch('../../shop/cart_update.php',{method:'POST',body:fd});
            const data = await res.json();
            if(!data.ok) throw new Error(data.error||'fail');
            if(newQty===0){ delete serverCart[productId]; }
            else if(data.item){ serverCart[productId]=data.item; }
            updateCartCount(data.cartCount);
            // Recompute totals quickly without full rebuild (or rebuild for simplicity)
            toggleCart();
        }catch(err){ showNotification('Update failed','error'); }
    }
    function bindCartQtyControls(){
        document.querySelectorAll('.qty-cart-btn').forEach(btn=>{
            btn.addEventListener('click',()=>{
                const pid=btn.getAttribute('data-pid');
                const delta=parseInt(btn.getAttribute('data-delta'),10)||0;
                const input=document.querySelector(`input.cart-line-qty[data-pid="${pid}"]`);
                if(!input) return; let v=parseInt(input.value||'1',10); v+=delta; if(v<1) v=0; // zero removes
                cartUpdateQty(pid,v);
            });
        });
        document.querySelectorAll('input.cart-line-qty').forEach(inp=>{
            inp.addEventListener('change',()=>{
                const pid=inp.getAttribute('data-pid'); let v=parseInt(inp.value||'1',10); if(isNaN(v)||v<1) v=1; cartUpdateQty(pid,v);
            });
        });
    }
    document.addEventListener('DOMContentLoaded',initAjaxCart);
    // Rebind ajax cart after dynamic grid loads
    document.addEventListener('ajax:products-updated',()=>{ initAjaxCart(); });
    // Delegate Quick View button clicks
    document.addEventListener('click',e=>{
        const btn = e.target.closest('.quick-view-btn');
        if(btn){
            const id = btn.getAttribute('data-qv');
            if(id) openQuickViewDb(id);
        }
    });

    // Debounced server-side search submit
    (function initDebouncedSearch(){
        const input=document.getElementById('search-input');
        if(!input) return;
        let timer; const form=input.closest('form');
        input.addEventListener('input',()=>{
            clearTimeout(timer);
            timer=setTimeout(()=>{ if(form) form.submit(); }, 450); // 450ms debounce
        });
        // Submit on Enter immediately
        input.addEventListener('keydown',e=>{ if(e.key==='Enter'){ clearTimeout(timer); if(form) form.submit(); }});
    })();

    // Category tabs + pagination AJAX (no full refresh)
    (function initAjaxCategoryAndPagination(){
        const form=document.querySelector('.search-container form');
        const catInput=document.getElementById('cat-input');
        const productsWrapper=document.getElementById('products-wrapper');
        function serialize(){ const fd=new FormData(form); return new URLSearchParams(fd).toString(); }
        function setActive(cat){ document.querySelectorAll('.category-tab').forEach(b=>{ b.classList.toggle('active', (b.getAttribute('data-cat')||'')===cat || (cat==='' && (b.getAttribute('data-cat')||'')==='all')); }); }
        async function fetchAndRender(params){
            try {
                productsWrapper.classList.add('opacity-50');
                const url=window.location.pathname+'?'+params+'&partial=1';
                const res=await fetch(url,{headers:{'X-Requested-With':'fetch'}});
                const html=await res.text();
                // Expect server returns only inner HTML fragments (grid + pagination)
                const temp=document.createElement('div'); temp.innerHTML=html;
                const newGrid=temp.querySelector('#products-grid');
                const newPagination=temp.querySelector('#pagination-wrapper');
                if(newGrid && document.getElementById('products-grid')) document.getElementById('products-grid').replaceWith(newGrid);
                if(newPagination && document.getElementById('pagination-wrapper')) document.getElementById('pagination-wrapper').replaceWith(newPagination);
                if(window.lucide) lucide.createIcons();
                document.dispatchEvent(new CustomEvent('ajax:products-updated'));
                productsWrapper.classList.remove('opacity-50');
            } catch(e){ productsWrapper.classList.remove('opacity-50'); console.error(e); }
        }
        function updateUrl(params){ const newUrl=window.location.pathname+'?'+params; window.history.replaceState({},'',newUrl); }
        function onCategoryClick(e){ const btn=e.target.closest('.category-tab'); if(!btn) return; const val=btn.getAttribute('data-cat')||''; const normalized=(val==='all')?'':val; if(catInput) catInput.value=normalized; setActive(val); const params=serialize(); updateUrl(params); fetchAndRender(params); }
        function onPaginationClick(e){ const link=e.target.closest('.page-link'); if(!link) return; e.preventDefault(); const page=link.getAttribute('data-page'); const fd=new FormData(form); fd.set('page', page); const params=new URLSearchParams(fd).toString(); updateUrl(params); fetchAndRender(params); }
        document.getElementById('category-tabs')?.addEventListener('click',onCategoryClick);
        document.addEventListener('click',onPaginationClick);
    })();

    // Show notification (updated implementation)
    function showNotification(message, type = 'success') {
        // Ladder (stacked) toasts container
        let container = document.getElementById('toast-container');
        if(!container){
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-[11000] flex flex-col gap-2 items-end pointer-events-none';
            document.body.appendChild(container);
        }
        const notification = document.createElement('div');
        notification.className = `p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full pointer-events-auto ${
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
        container.appendChild(notification);
        if(window.lucide) lucide.createIcons();
        // Limit stacked toasts to 5
        while(container.children.length > 5){ container.removeChild(container.firstElementChild); }
        requestAnimationFrame(()=>{ notification.classList.remove('translate-x-full'); });
        setTimeout(()=> { notification.classList.add('translate-x-full'); setTimeout(()=> notification.remove(), 300); }, 3000);
    }

    // Escape key closes drawer
    document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeQuickView(); });

    // Lightweight parallax for hero floating elements
    (function initHeroParallax(){
        const hero = document.getElementById('shop-hero');
        if(!hero) return;
        const elems = hero.querySelectorAll('[data-parallax]');
        let ticking = false;
        function onScroll(){
            if(ticking) return; ticking = true;
            requestAnimationFrame(()=>{
                const rect = hero.getBoundingClientRect();
                const viewport = window.innerHeight || document.documentElement.clientHeight;
                // Only compute when hero is on screen
                if(rect.bottom >= 0 && rect.top <= viewport){
                    const progress = 1 - Math.min(Math.max((rect.top + rect.height/2) / (viewport + rect.height/2), 0), 1);
                    elems.forEach(el=>{
                        const factor = parseFloat(el.getAttribute('data-parallax')||'0.1');
                        const y = (progress - 0.5) * 40 * factor; // max ~±20px scaled
                        el.style.transform = `translate3d(0, ${y.toFixed(2)}px, 0)`;
                    });
                }
                ticking = false;
            });
        }
        document.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();

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
    // header-users.php already initializes dropdowns
        initDropdown('userMenu','userMenuBtn','userMenuMenu');
    })();
    </script>
</body>
</html>