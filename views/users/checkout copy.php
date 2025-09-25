<?php
// Mock data for design preview
$cartItems = [
    [
        'id' => 1,
        'name' => 'Premium Dog Kibble',
        'price' => 1299,
        'quantity' => 2,
        'image' => 'https://images.unsplash.com/photo-1572950947301-fb417712da10?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcmVtaXVtJTIwZG9nJTIwZm9vZCUyMGtpYmJsZXxlbnwxfHx8fDE3NTY1NDM3MTV8MA&ixlib=rb-4.1.0&q=80&w=1080'
    ],
    [
        'id' => 2,
        'name' => 'Nutritious Cat Food',
        'price' => 899,
        'quantity' => 1,
        'image' => 'https://images.unsplash.com/photo-1734654901149-02a9a5f7993b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBmb29kJTIwYm93bHxlbnwxfHx8fDE3NTY1MDk4MzR8MA&ixlib=rb-4.1.0&q=80&w=1080'
    ],
    [
        'id' => 3,
        'name' => 'Interactive Dog Toy',
        'price' => 459,
        'quantity' => 3,
        'image' => 'https://images.unsplash.com/photo-1659700097688-f26bf79735af?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjB0b3klMjByb3BlJTIwYmFsbHxlbnwxfHx8fDE3NTY0Njk1Mjd8MA&ixlib=rb-4.1.0&q=80&w=1080'
    ]
];

$user = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+63 912 345 6789',
    'isSubscribed' => true
];

$savedAddress = [
    'id' => 1,
    'type' => 'Home',
    'name' => 'John Doe',
    'phone' => '+63 912 345 6789',
    'addressLine1' => '123 Main Street, Subdivision Name',
    'addressLine2' => 'Near the park, blue gate',
    'barangay' => 'Lahug',
    'city' => 'Cebu City',
    'province' => 'Cebu',
    'isDefault' => true
];

// Calculate totals
$subtotal = 0;
$totalItems = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalItems += $item['quantity'];
}

$discount = $user['isSubscribed'] ? $subtotal * 0.1 : 0;
$deliveryFee = 50;
$total = $subtotal - $discount + $deliveryFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - pawhabilin Pet Shop</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Complete your pet supplies purchase at pawhabilin. Secure checkout with multiple payment options.">
    <meta name="keywords" content="pet supplies checkout, pawhabilin shop, pet food delivery Philippines">
    
    <!-- Tailwind CSS v4.0 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Checkout-specific animations and styles */
        @keyframes pawBounce {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }
        
        @keyframes slideInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.6); }
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .paw-bounce {
            animation: pawBounce 2s ease-in-out infinite;
        }
        
        .slide-in-up {
            animation: slideInUp 0.6s ease-out forwards;
        }
        
        .pulse-glow {
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
        }
        
        /* Quantity controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            border-color: #f97316;
            background: #fef7f0;
            transform: scale(1.05);
        }
        
        .quantity-input {
            width: 64px;
            height: 32px;
            text-align: center;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #fff;
            transition: border-color 0.2s ease;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
        }
        
        /* Progress steps */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .progress-step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .progress-step.active {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
        }
        
        .progress-step.completed {
            background: #10b981;
            color: white;
        }
        
        .progress-connector {
            width: 32px;
            height: 2px;
            background: #e5e7eb;
            margin: 0 8px;
        }
        
        .progress-connector.completed {
            background: #10b981;
        }
        
        /* Cart item cards */
        .cart-item {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.05), transparent);
            transition: left 0.6s ease;
        }
        
        .cart-item:hover {
            border-color: #f97316;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .cart-item:hover::before {
            left: 100%;
        }
        
        /* Address card */
        .address-card {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 16px;
            position: relative;
        }
        
        .address-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #2563eb, #1d4ed8);
        }
        
        /* Payment options */
        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .payment-option:hover {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .payment-option.selected {
            border-color: #10b981;
            background: #f0fdf4;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
        }
        
        .modal-overlay.show .modal-content {
            transform: scale(1) translateY(0);
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            color: #374151;
        }
        
        .form-input,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        /* Success notification */
        .success-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .success-notification.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Loading spinner */
        .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #f97316;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Badge styles */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .badge-green {
            background: #dcfce7;
            color: #166534;
        }
        
        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ea580c, #dc2626);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        
        .btn-secondary {
            background: #fff;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .btn-outline {
            background: transparent;
            color: #f97316;
            border: 1px solid #f97316;
        }
        
        .btn-outline:hover {
            background: #fef7f0;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .modal-content {
                padding: 20px;
                width: 95%;
            }
            
            .cart-item {
                padding: 16px;
            }
            
            .quantity-btn {
                width: 28px;
                height: 28px;
            }
            
            .quantity-input {
                width: 56px;
                height: 28px;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-lg overflow-hidden">
                        <div class="w-full h-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center">
                            <i data-lucide="paw-print" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                    <span class="text-xl font-semibold brand-font">pawhabilin</span>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <i data-lucide="shopping-cart" class="w-6 h-6 text-gray-600"></i>
                        <div class="absolute -top-2 -right-2 w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center text-xs text-white font-bold">
                            <?php echo $totalItems; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <span class="font-medium text-gray-700">Hi, <?php echo explode(' ', $user['name'])[0]; ?>!</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="flex items-center">
                <div class="progress-step active" id="step-1">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </div>
                <div class="progress-connector"></div>
                <div class="progress-step" id="step-2">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                </div>
            </div>
        </div>
        
        <!-- Step Labels -->
        <div class="flex justify-center mb-8">
            <div class="flex items-center space-x-16 text-sm text-gray-600">
                <span id="step-1-label" class="font-medium text-orange-600">Cart</span>
                <span id="step-2-label">Checkout</span>
            </div>
        </div>

        <!-- Cart Step -->
        <div id="cart-step-content" class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 slide-in-up">
                        <!-- Cart Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center">
                                    <i data-lucide="shopping-cart" class="w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold">Your Cart</h2>
                                    <p class="text-gray-600"><?php echo $totalItems; ?> items in your cart</p>
                                </div>
                            </div>
                            
                            <?php if ($user['isSubscribed']): ?>
                            <div class="badge badge-primary">
                                <i data-lucide="star" class="w-3 h-3"></i>
                                Subscriber 10% OFF
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Cart Items -->
                        <div class="space-y-4">
                            <?php if (empty($cartItems)): ?>
                            <div class="text-center py-16">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-400"></i>
                                </div>
                                <h3 class="text-xl font-semibold mb-2">Your cart is empty</h3>
                                <p class="text-gray-600 mb-6">Add some amazing pet products to get started!</p>
                                <a href="shop.php" class="btn btn-primary">
                                    <i data-lucide="package" class="w-4 h-4"></i>
                                    Continue Shopping
                                </a>
                            </div>
                            <?php else: ?>
                            <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                                <div class="flex gap-4">
                                    <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover">
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <button class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-full transition-colors duration-200" onclick="removeItem(<?php echo $item['id']; ?>)">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div class="space-y-2">
                                                <div class="text-2xl font-bold text-orange-600">
                                                    ₱<?php echo number_format($item['price']); ?>
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    Subtotal: <span class="font-semibold text-orange-600">
                                                        ₱<?php echo number_format($item['price'] * $item['quantity']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Quantity Controls -->
                                            <div class="quantity-controls">
                                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                    <i data-lucide="minus" class="w-3 h-3"></i>
                                                </button>
                                                <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                    <i data-lucide="plus" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Cart Actions -->
                        <?php if (!empty($cartItems)): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <a href="shop.php" class="btn btn-secondary">
                                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                    Continue Shopping
                                </a>
                                
                                <button onclick="showCheckoutStep()" class="btn btn-primary pulse-glow">
                                    Proceed to Checkout
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="text-xl font-semibold flex items-center gap-2 mb-6">
                                <i data-lucide="package" class="w-5 h-5 text-orange-500"></i>
                                Order Summary
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span>Subtotal (<?php echo $totalItems; ?> items)</span>
                                        <span class="font-semibold">₱<?php echo number_format($subtotal); ?></span>
                                    </div>
                                    
                                    <?php if ($discount > 0): ?>
                                    <div class="flex justify-between text-green-600">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="star" class="w-4 h-4"></i>
                                            Subscriber Discount (10%)
                                        </span>
                                        <span class="font-semibold">-₱<?php echo number_format($discount); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span>Delivery Fee</span>
                                        <span class="font-semibold">₱<?php echo $deliveryFee; ?></span>
                                    </div>
                                    
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-bold">Total</span>
                                            <span class="text-2xl font-bold text-orange-600">₱<?php echo number_format($total); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pt-4 space-y-3">
                                    <div class="flex items-center gap-2 text-sm text-green-600">
                                        <i data-lucide="truck" class="w-4 h-4"></i>
                                        <span>Estimated delivery: 2-3 days</span>
                                    </div>
                                </div>
                                
                                <?php if (!$user['isSubscribed']): ?>
                                <div class="p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg border border-orange-200">
                                    <div class="flex items-center gap-2 text-orange-700 mb-2">
                                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                                        <span class="font-semibold">Save with Subscription!</span>
                                    </div>
                                    <p class="text-sm text-orange-600 mb-3">
                                        Get 10% off this order and exclusive benefits.
                                    </p>
                                    <button class="btn btn-outline text-orange-600 border-orange-300 hover:bg-orange-50" style="font-size: 12px; padding: 6px 12px;">
                                        Learn More
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Step -->
        <div id="checkout-step-content" class="max-w-6xl mx-auto hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Header -->
                    <div class="flex items-center gap-3">
                        <button onclick="showCartStep()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </button>
                        <div>
                            <h2 class="text-2xl font-bold">Order Confirmation</h2>
                            <p class="text-gray-600">Review your order and complete purchase</p>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-5 h-5 text-blue-500"></i>
                                Delivery Address
                            </h3>
                            <button onclick="showAddressModal()" class="btn btn-outline text-orange-600 border-orange-300 hover:bg-orange-50">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add Address
                            </button>
                        </div>
                        
                        <?php if ($savedAddress): ?>
                        <div class="address-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="badge badge-blue"><?php echo $savedAddress['type']; ?></span>
                                        <?php if ($savedAddress['isDefault']): ?>
                                        <span class="badge badge-green">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($savedAddress['name']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($savedAddress['phone']); ?></p>
                                    <p class="text-sm text-gray-700 mt-1">
                                        <?php echo htmlspecialchars($savedAddress['addressLine1']); ?><br>
                                        <?php if ($savedAddress['addressLine2']): ?>
                                            <?php echo htmlspecialchars($savedAddress['addressLine2']); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($savedAddress['barangay']) . ', ' . htmlspecialchars($savedAddress['city']) . ', ' . htmlspecialchars($savedAddress['province']); ?>
                                    </p>
                                </div>
                                <button class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                            <p>No delivery address selected</p>
                            <button onclick="showAddressModal()" class="btn btn-primary mt-4">
                                Add Address
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold flex items-center gap-2 mb-4">
                            <i data-lucide="package" class="w-5 h-5 text-orange-500"></i>
                            Order Items
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                                <div class="w-16 h-16 rounded-lg overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-sm text-gray-600">Qty: <?php echo $item['quantity']; ?></span>
                                        <span class="font-semibold text-orange-600">
                                            ₱<?php echo number_format($item['price'] * $item['quantity']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold flex items-center gap-2 mb-4">
                            <i data-lucide="credit-card" class="w-5 h-5 text-green-500"></i>
                            Payment Method
                        </h3>
                        <div class="space-y-4">
                            <!-- Cash on Delivery -->
                            <label class="payment-option selected">
                                <input type="radio" name="payment" value="cod" checked class="sr-only">
                                <div class="flex items-center gap-4">
                                    <div class="w-5 h-5 border-2 border-green-500 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="package" class="w-5 h-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold">Cash on Delivery</div>
                                            <div class="text-sm text-gray-600">Pay when your order arrives</div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- GCash -->
                            <label class="payment-option">
                                <input type="radio" name="payment" value="gcash" class="sr-only">
                                <div class="flex items-center gap-4">
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-gray-300 rounded-full hidden"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                            G
                                        </div>
                                        <div>
                                            <div class="font-semibold">GCash</div>
                                            <div class="text-sm text-gray-600">Digital wallet payment</div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- PayMaya -->
                            <label class="payment-option">
                                <input type="radio" name="payment" value="paymaya" class="sr-only">
                                <div class="flex items-center gap-4">
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-gray-300 rounded-full hidden"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                            M
                                        </div>
                                        <div>
                                            <div class="font-semibold">PayMaya</div>
                                            <div class="text-sm text-gray-600">Digital wallet payment</div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Payment Amount Input (Hidden by default) -->
                            <div class="hidden" id="payment-amount-section">
                                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <label class="form-label text-yellow-800">Payment Amount (Must be exact)</label>
                                    <input type="number" step="0.01" class="form-input border-yellow-300 focus:border-yellow-500" placeholder="₱<?php echo number_format($total); ?>">
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Please enter exactly ₱<?php echo number_format($total); ?>
                                    </p>
                                    <div class="hidden text-sm text-red-600 mt-1" id="payment-error">
                                        Payment amount must be exactly ₱<?php echo number_format($total); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
                            <div class="space-y-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span>Subtotal</span>
                                        <span>₱<?php echo number_format($subtotal); ?></span>
                                    </div>
                                    
                                    <?php if ($discount > 0): ?>
                                    <div class="flex justify-between text-green-600">
                                        <span>Subscriber Discount</span>
                                        <span>-₱<?php echo number_format($discount); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span>Delivery Fee</span>
                                        <span>₱<?php echo $deliveryFee; ?></span>
                                    </div>
                                    
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between font-bold text-lg">
                                            <span>Total</span>
                                            <span class="text-orange-600">₱<?php echo number_format($total); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex items-center gap-2 text-green-700 mb-2">
                                        <i data-lucide="truck" class="w-4 h-4"></i>
                                        <span class="font-semibold">Estimated Delivery</span>
                                    </div>
                                    <p class="text-green-600 text-sm">
                                        <?php echo date('M j, Y', strtotime('+2 days')); ?> - <?php echo date('M j, Y', strtotime('+3 days')); ?>
                                    </p>
                                </div>
                                
                                <button onclick="placeOrder()" class="btn btn-primary w-full pulse-glow" style="height: 48px; font-size: 18px;">
                                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Address Modal -->
    <div class="modal-overlay" id="address-modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-6 h-6 text-orange-500"></i>
                    Add Delivery Address
                </h3>
                <button onclick="closeAddressModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-300">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="address-form" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Address Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select type</option>
                            <option value="Home">Home</option>
                            <option value="Office">Office</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Recipient Name *</label>
                        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">House/Unit/Lot Number/Street *</label>
                    <input type="text" name="address_line1" class="form-input" placeholder="123 Main Street, Subdivision Name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <input type="text" name="address_line2" class="form-input" placeholder="Near landmarks, special instructions...">
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Barangay *</label>
                        <input type="text" name="barangay" class="form-input" placeholder="Barangay" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-input" placeholder="City" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Province *</label>
                        <input type="text" name="province" class="form-input" placeholder="Province" required>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_default" id="set-default" class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2">
                    <label for="set-default" class="text-sm text-gray-700">Set as default address</label>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn btn-primary flex-1">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Address
                    </button>
                    <button type="button" onclick="closeAddressModal()" class="btn btn-secondary flex-1">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Success Notification -->
    <div class="success-notification" id="success-notification">
        <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="check" class="w-8 h-8 text-white"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h3>
        <p class="text-gray-600 mb-6" id="success-message">
            Your order has been confirmed and will be processed soon. You'll receive updates via email and SMS.
        </p>
        <div class="space-y-3">
            <button onclick="backToShop()" class="btn btn-primary w-full">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                Back to Shop
            </button>
            <button onclick="closeSuccessModal()" class="btn btn-secondary w-full">
                View Order Details
            </button>
        </div>
    </div>

    <script>
        let currentStep = 1; // 1: Cart, 2: Checkout
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            initializePaymentMethods();
            updateStepDisplay();
        });

        // Step navigation functions
        function showCartStep() {
            currentStep = 1;
            document.getElementById('cart-step-content').classList.remove('hidden');
            document.getElementById('checkout-step-content').classList.add('hidden');
            updateStepDisplay();
        }

        function showCheckoutStep() {
            currentStep = 2;
            document.getElementById('cart-step-content').classList.add('hidden');
            document.getElementById('checkout-step-content').classList.remove('hidden');
            updateStepDisplay();
        }

        function updateStepDisplay() {
            // Update progress steps
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            const connector = document.querySelector('.progress-connector');
            const step1Label = document.getElementById('step-1-label');
            const step2Label = document.getElementById('step-2-label');

            if (currentStep === 1) {
                step1.classList.add('active');
                step1.classList.remove('completed');
                step2.classList.remove('active', 'completed');
                connector.classList.remove('completed');
                step1Label.classList.add('text-orange-600', 'font-medium');
                step1Label.classList.remove('text-gray-600');
                step2Label.classList.add('text-gray-600');
                step2Label.classList.remove('text-orange-600', 'font-medium');
            } else {
                step1.classList.add('completed');
                step1.classList.remove('active');
                step2.classList.add('active');
                connector.classList.add('completed');
                step1Label.classList.remove('text-orange-600', 'font-medium');
                step1Label.classList.add('text-gray-600');
                step2Label.classList.add('text-orange-600', 'font-medium');
                step2Label.classList.remove('text-gray-600');
            }
        }

        // Quantity management
        function updateQuantity(productId, newQuantity) {
            if (newQuantity < 1) return;
            
            // This would connect to your API
            console.log('Update quantity:', productId, newQuantity);
            
            // For design demo, just update the display
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            if (cartItem) {
                const quantityInput = cartItem.querySelector('.quantity-input');
                quantityInput.value = newQuantity;
                
                // Update subtotal (simplified for demo)
                updateCartTotals();
            }
        }

        function removeItem(productId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            // This would connect to your API
            console.log('Remove item:', productId);
            
            // For design demo, remove the element
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            if (cartItem) {
                cartItem.remove();
                updateCartTotals();
            }
        }

        function updateCartTotals() {
            // This would recalculate totals based on current cart state
            console.log('Update cart totals');
        }

        // Address modal management
        function showAddressModal() {
            document.getElementById('address-modal').classList.add('show');
        }

        function closeAddressModal() {
            document.getElementById('address-modal').classList.remove('show');
        }

        // Address form submission
        document.getElementById('address-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const addressData = {
                type: formData.get('type'),
                name: formData.get('name'),
                phone: formData.get('phone'),
                address_line1: formData.get('address_line1'),
                address_line2: formData.get('address_line2'),
                barangay: formData.get('barangay'),
                city: formData.get('city'),
                province: formData.get('province'),
                is_default: formData.get('is_default') === 'on'
            };
            
            // This would connect to your API
            console.log('Add address:', addressData);
            
            // For design demo, just close modal
            closeAddressModal();
            e.target.reset();
        });

        // Payment method management
        function initializePaymentMethods() {
            const paymentOptions = document.querySelectorAll('input[name="payment"]');
            const paymentAmountSection = document.getElementById('payment-amount-section');
            
            paymentOptions.forEach(option => {
                option.addEventListener('change', function() {
                    // Update visual selection
                    paymentOptions.forEach(opt => {
                        const card = opt.closest('.payment-option');
                        const radio = card.querySelector('.w-5.h-5 div');
                        if (opt.checked) {
                            card.classList.add('selected');
                            if (radio) radio.classList.remove('hidden');
                        } else {
                            card.classList.remove('selected');
                            if (radio) radio.classList.add('hidden');
                        }
                    });
                    
                    // Show/hide payment amount section
                    if (this.value === 'gcash' || this.value === 'paymaya') {
                        paymentAmountSection.classList.remove('hidden');
                    } else {
                        paymentAmountSection.classList.add('hidden');
                    }
                });
            });
        }

        // Order placement
        function placeOrder() {
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            const placeOrderBtn = document.querySelector('[onclick="placeOrder()"]');
            
            // Show loading state
            const originalContent = placeOrderBtn.innerHTML;
            placeOrderBtn.innerHTML = `
                <div class="loading-spinner"></div>
                <span>Processing...</span>
            `;
            placeOrderBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // Show success notification
                document.getElementById('success-notification').classList.add('show');
                
                // Reset button
                placeOrderBtn.innerHTML = originalContent;
                placeOrderBtn.disabled = false;
                
                console.log('Order placed with payment method:', paymentMethod);
            }, 3000);
        }

        // Navigation functions
        function backToShop() {
            window.location.href = 'shop.php';
        }

        function closeSuccessModal() {
            document.getElementById('success-notification').classList.remove('show');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>