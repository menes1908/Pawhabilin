<?php
// Logged-in Subscriptions page (functional)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/database.php';
require_once dirname(__DIR__, 2) . '/models/subscription.php';
require_once dirname(__DIR__, 2) . '/utils/session.php';

$user = get_current_user_session();
if (!$user) { header('Location: ../../login.php?redirect=views/users/subscriptions.php'); exit; }

// Ensure at least one plan exists and fetch active status
$plan = subscription_get_or_create_default_plan($connections) ?? null;
$activeSub = subscription_get_active_for_user($connections, (int)$user['users_id']);
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans - Pawhabilin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../globals.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
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
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

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
                        Hello, <?php echo h(user_display_name($user) ?: 'User'); ?>! Welcome to your pet care journey
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
                <div id="current-plan-card" class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 mb-12">
                    <div class="flex items-center justify-between">
                            <?php if ($activeSub): ?>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center">
                                    <i data-lucide="crown" class="w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-orange-800">Current Plan: Premium</h3>
                                    <p class="text-orange-600">Renews on <?php echo h(date('M d, Y', strtotime((string)$activeSub['us_end_date']))); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-orange-700">₱<?php echo number_format((float)$activeSub['subscriptions_price'], 2); ?></div>
                                <div class="text-sm text-orange-600">per month</div>
                            </div>
                            <?php else: ?>
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
                            <?php endif; ?>
                    </div>
                    <?php if ($activeSub): ?>
                    <div id="manage-plan-row" class="mt-4 pt-4 border-t border-green-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <p class="text-sm text-green-800">Manage your plan or cancel anytime. Cancelling ends premium access immediately.</p>
                        <button id="cancel-btn" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                            <i data-lucide="x-circle" class="w-5 h-5"></i>
                            Cancel Subscription
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

        <!-- One-step Checkout -->
        <section class="py-16 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
            <div class="container mx-auto px-4 max-w-5xl">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold bg-white/80 text-orange-600 border-orange-200 mb-6">
                        <i data-lucide="crown" class="w-3 h-3 mr-1"></i>
                        Premium Subscription
                    </div>
                    <h2 class="text-4xl md:text-5xl font-bold mb-3">
                        <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">One‑step checkout</span>
                    </h2>
                    <p class="text-gray-700 max-w-2xl mx-auto">Choose a payment method, review your plan, and confirm your subscription. No hidden fees.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <!-- Left: Payment methods and details -->
                    <div class="lg:col-span-2 space-y-6">
                                <!-- Choose Plan Strip -->
                                <div class="bg-white rounded-2xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-gray-700">Choose plan</div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        <button type="button" disabled class="inline-flex items-center gap-2 px-3 py-2 rounded-full border <?php echo $activeSub ? 'border-gray-200 text-gray-500 bg-gray-50 cursor-not-allowed' : 'border-green-200 text-green-700 bg-green-50 cursor-not-allowed'; ?>">
                                            <i data-lucide="heart" class="w-4 h-4"></i>
                                            Free
                                            <?php if (!$activeSub): ?>
                                                <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-green-600 text-white">Current</span>
                                            <?php endif; ?>
                                        </button>
                                                        <button id="premium-pill" type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border <?php echo $activeSub ? 'border-orange-300 ring-2 ring-orange-300 bg-orange-50 text-orange-700' : 'border-orange-300 ring-2 ring-orange-300 bg-orange-50 text-orange-700'; ?>">
                                            <i data-lucide="crown" class="w-4 h-4"></i>
                                            Premium
                                            <?php if ($activeSub): ?>
                                                <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-green-600 text-white">Current</span>
                                            <?php else: ?>
                                                <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-orange-600 text-white">Selected</span>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                                <div class="mt-3 text-sm text-gray-600">
                                                    <?php echo h($plan['subscriptions_description'] ?? 'Premium Plan: Priority booking, premium sitters, support, and discounts'); ?>
                                                </div>
                                                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-700">
                                                                    <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-amber-500"></i><span>Priority booking</span></div>
                                                                    <div class="flex items-center gap-2"><i data-lucide="star" class="w-4 h-4 text-amber-500"></i><span>Premium sitters</span></div>
                                                                    <div class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 text-emerald-600"></i><span>24/7 support</span></div>
                                                                    <div class="flex items-center gap-2"><i data-lucide="badge-percent" class="w-4 h-4 text-blue-600"></i><span>Shop discounts</span></div>
                                                                </div>
                                </div>

                        <!-- Payment Method Selection -->
                        <div class="bg-white rounded-2xl border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold mb-4">Payment method</h3>
                            <div id="method-list" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <button type="button" data-method="card" class="method-card group border rounded-xl p-4 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="credit-card" class="w-5 h-5 text-gray-600 group-[.active]:text-orange-600"></i>
                                        <span class="font-medium">Card</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Visa, Mastercard</div>
                                </button>
                                <button type="button" data-method="gcash" class="method-card group border rounded-xl p-4 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                                    <div class="flex items-center gap-2">
                                        <img alt="GCash" src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/GCash_Logo.svg/1280px-GCash_Logo.svg.png" class="h-5"/>
                                        <span class="font-medium">GCash</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Pay with mobile</div>
                                </button>
                                <button type="button" data-method="paypal" class="method-card group border rounded-xl p-4 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                                    <div class="flex items-center gap-2">
                                        <img alt="PayPal" src="https://www.paypalobjects.com/webstatic/icon/pp258.png" class="h-5"/>
                                        <span class="font-medium">PayPal</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Wallet login</div>
                                </button>
                                <button type="button" data-method="applepay" class="method-card group border rounded-xl p-4 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="apple" class="w-5 h-5 text-gray-600"></i>
                                        <span class="font-medium">Apple Pay</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Quick checkout</div>
                                </button>
                                <button type="button" data-method="bank_transfer" class="method-card group border rounded-xl p-4 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building" class="w-5 h-5 text-gray-600"></i>
                                        <span class="font-medium">Bank Transfer</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Manual transfer</div>
                                </button>
                            </div>
                        </div>

                        <!-- Payment Details Form (dynamic) -->
                        <div class="bg-white rounded-2xl border border-gray-200 p-6" id="details-card">
                            <h3 class="text-lg font-semibold mb-4">Payment details</h3>
                                            <form id="subscribe-form" onsubmit="return subscribe(event)" class="space-y-4">
                                <input type="hidden" name="action" value="subscribe"/>
                                <input type="hidden" name="plan_id" value="<?php echo (int)($plan['subscriptions_id'] ?? 0); ?>"/>
                                <input type="hidden" name="payment_method" id="payment_method" value="card"/>
                                                <input type="hidden" name="billing_cycle" id="billing_cycle" value="monthly"/>

                                <!-- Card fields -->
                                <div data-fields="card" class="method-fields">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                            <input type="text" inputmode="numeric" placeholder="1234 5678 9012 3456" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-300" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label>
                                                <input type="text" placeholder="MM/YY" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-300" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                                <input type="password" placeholder="123" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-300" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- GCash fields -->
                                <div data-fields="gcash" class="method-fields hidden">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                            <input type="tel" placeholder="09xxxxxxxxx" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-300" maxlength="11" />
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" class="px-3 py-2 border rounded-lg text-sm">Send OTP</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- PayPal notice -->
                                <div data-fields="paypal" class="method-fields hidden">
                                    <div class="text-sm text-gray-600">You will be redirected to PayPal to complete your purchase.</div>
                                </div>

                                <!-- Apple Pay notice -->
                                <div data-fields="applepay" class="method-fields hidden">
                                    <div class="text-sm text-gray-600">Use your Apple device to confirm the payment.</div>
                                </div>

                                <!-- Bank Transfer notice -->
                                <div data-fields="bank_transfer" class="method-fields hidden">
                                    <div class="text-sm text-gray-600">Transfer to our bank account. We will activate your plan once confirmed.</div>
                                </div>

                                <!-- Terms -->
                                <label class="flex items-center gap-2 text-sm text-gray-700 pt-2">
                                    <input id="terms" type="checkbox" class="accent-orange-600"/>
                                    <span>I agree to the <a class="underline" href="#" target="_blank">Terms</a> and <a class="underline" href="#" target="_blank">Privacy Policy</a>.</span>
                                </label>

                                <p id="subscribe-error" class="hidden text-red-600 text-sm"></p>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Billing summary -->
                                <aside class="bg-white rounded-2xl border border-gray-200 p-6 lg:sticky lg:top-6">
                        <h3 class="text-lg font-semibold mb-4">Billing summary</h3>
                        <div class="space-y-3 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600">Plan</span><span class="font-medium">Premium</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Price</span><span class="font-semibold"><span id="billing-price">₱<?php echo number_format((float)($plan['subscriptions_price'] ?? 299), 2); ?></span>/<span id="billing-period">month</span></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Next billing</span><span id="billing-next" class="font-medium"><?php echo h(date('M d, Y', strtotime('+30 days'))); ?></span></div>
                        </div>
                        <div class="mt-6">
                                        <button id="confirm-btn" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white hover:from-orange-600 hover:to-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                                Confirm & Subscribe
                            </button>
                            <p class="text-[11px] text-gray-500 mt-3 flex items-center gap-1"><i data-lucide="shield" class="w-3 h-3"></i> Secure checkout. SSL protected.</p>
                        </div>
                    </aside>
                </div>
            </div>

            <!-- Mobile sticky bar -->
                    <div class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 p-3 flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs text-gray-500">Premium</div>
                            <div class="text-base font-semibold"><span id="mobile-price">₱<?php echo number_format((float)($plan['subscriptions_price'] ?? 299), 2); ?></span>/<span id="mobile-period">month</span></div>
                </div>
                <button id="confirm-btn-mobile" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white">
                    Confirm & Subscribe
                </button>
            </div>
        </section>

                <!-- Premium Plan Summary Modal -->
                <div id="plan-modal" class="hidden fixed inset-0 z-[10000]">
                    <div class="absolute inset-0 bg-black/40"></div>
                    <div class="relative max-w-lg mx-auto mt-24 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold">Premium plan</h3>
                            <button id="plan-modal-close" class="p-1 rounded hover:bg-gray-100"><i data-lucide="x" class="w-5 h-5"></i></button>
                        </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                                    <i data-lucide="crown" class="w-4 h-4 text-amber-500"></i>
                                    <span><?php echo h($plan['subscriptions_description'] ?? 'Premium Plan: Priority booking, premium sitters, support, and discounts'); ?></span>
                                </div>
                                        <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-700 mb-4">
                                            <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-amber-500"></i><span>Priority booking</span></div>
                                            <div class="flex items-center gap-2"><i data-lucide="star" class="w-4 h-4 text-amber-500"></i><span>Premium sitters</span></div>
                                            <div class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 text-emerald-600"></i><span>24/7 support</span></div>
                                            <div class="flex items-center gap-2"><i data-lucide="badge-percent" class="w-4 h-4 text-blue-600"></i><span>Shop discounts</span></div>
                                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <button id="cycle-monthly" class="cycle-btn ring-2 ring-orange-400 border border-orange-300 bg-orange-50 text-orange-700 rounded-xl p-4 text-left">
                                <div class="text-xs text-gray-600">Monthly</div>
                                <div class="text-lg font-bold">₱<?php echo number_format((float)($plan['subscriptions_price'] ?? 299), 2); ?></div>
                            </button>
                            <button id="cycle-yearly" class="cycle-btn border border-gray-200 rounded-xl p-4 text-left relative">
                                <div class="text-xs text-gray-600">Yearly</div>
                                <div class="text-lg font-bold">₱<?php echo number_format(max(0, (float)($plan['subscriptions_price'] ?? 299) * 12 - 600), 2); ?></div>
                                <span class="absolute -top-2 right-2 text-[10px] px-2 py-0.5 rounded-full bg-emerald-600 text-white">Save ₱600</span>
                            </button>
                        </div>
                        <p id="yearly-note" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3">Yearly billing will be enabled soon. For now, monthly is available.</p>
                        <div class="flex justify-end gap-2">
                            <button id="plan-cancel" class="px-4 py-2 border rounded-lg">Cancel</button>
                            <button id="plan-apply" class="px-4 py-2 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white">Apply</button>
                        </div>
                    </div>
                </div>

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
                        <li><a href="profile.php" class="hover:text-white transition-colors">My Profile</a></li>
                        <li><a href="subscriptions.php" class="hover:text-white transition-colors">Subscription</a></li>
                        <li><a href="../../shop.php" class="hover:text-white transition-colors">Shop</a></li>
                        <li><a href="../../appointments.php" class="hover:text-white transition-colors">Book Appointment</a></li>
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
        const PLAN_ID = <?php echo json_encode((int)($plan['subscriptions_id'] ?? 0)); ?>;
        function toast(message, type='success'){
            let c=document.getElementById('toast-container'); if(!c){ c=document.createElement('div'); c.id='toast-container'; c.className='fixed top-4 right-4 z-[11000] flex flex-col gap-2 items-end pointer-events-none'; document.body.appendChild(c); }
            const n=document.createElement('div'); n.className=`pointer-events-auto px-4 py-3 rounded-lg shadow text-sm ${type==='success'?'bg-green-600 text-white':type==='error'?'bg-red-600 text-white':'bg-blue-600 text-white'}`; n.textContent=message; c.appendChild(n); setTimeout(()=>{ n.style.opacity='0'; setTimeout(()=>n.remove(),250); }, 2200);
        }
        function showBanner(message){
            let b=document.getElementById('inline-banner');
            if(!b){
                b=document.createElement('div');
                b.id='inline-banner';
                b.className='container mx-auto px-4 max-w-4xl';
                const wrap=document.createElement('div');
                wrap.className='bg-green-50 border border-green-200 text-green-800 rounded-2xl p-4 mb-6 flex items-center gap-2';
                wrap.innerHTML=`<i data-lucide="check-circle" class="w-5 h-5"></i><span id="inline-banner-text"></span>`;
                b.appendChild(wrap);
                const parent=document.querySelector('section.py-12.bg-white .container .max-w-4xl');
                if(parent){ parent.insertBefore(b, parent.firstChild); }
            }
            const t=b.querySelector('#inline-banner-text');
            if(t){ t.textContent=message; }
            if(window.lucide) lucide.createIcons();
        }
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
            
            // Observe elements for animation (legacy cards removed; keep placeholder for other animated elements if present)
            document.querySelectorAll('.pricing-card').forEach(el => { try{ observer.observe(el); }catch{} });

            // Cancel subscription control
            const cancelBtn = document.getElementById('cancel-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', async () => {
                    if (!confirm('Cancel subscription now? This will end your premium access immediately.')) return;
                    cancelBtn.disabled = true;
                    const old = cancelBtn.innerHTML;
                    cancelBtn.innerHTML = '<div class="flex items-center gap-2"><div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></div><span>Processing...</span></div>';
                    try {
                        const fd = new FormData();
                        fd.append('action', 'cancel');
                        const res = await fetch('../../controllers/users/subscriptioncontroller.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (!res.ok || !data.ok) throw new Error(data.error || 'Cancellation failed');
                        toast('Subscription cancelled', 'success');
                        // Update UI to Free without reload
                        updateUIToFree();
                        showBanner('Your plan was cancelled');
                    } catch (e) {
                        toast(e.message || 'Cancellation failed', 'error');
                    } finally {
                        cancelBtn.disabled = false;
                        cancelBtn.innerHTML = old;
                    }
                });
            }
        });

        async function subscribe(ev){
            ev.preventDefault();
            const form = document.getElementById('subscribe-form');
            const err = document.getElementById('subscribe-error');
            const confirmBtn = document.getElementById('confirm-btn');
            const confirmBtnM = document.getElementById('confirm-btn-mobile');
            const terms = document.getElementById('terms');
            if(terms && !terms.checked){ err.textContent='Please agree to the Terms and Privacy Policy.'; err.classList.remove('hidden'); return false; }
            err.classList.add('hidden'); err.textContent='';
            const method = document.getElementById('payment_method')?.value || 'card';
            const setLoading = (on)=>{
                [confirmBtn, confirmBtnM].forEach(btn=>{ if(!btn) return; btn.disabled = !!on; btn.dataset.old = btn.dataset.old || btn.innerHTML; btn.innerHTML = on ? '<div class="flex items-center justify-center gap-2"><div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div><span>Processing your payment…</span></div>' : btn.dataset.old; });
            };
            setLoading(true);
            try {
                const fd = new FormData(form);
                const res = await fetch('../../controllers/users/subscriptioncontroller.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.error || 'Subscription failed');
                // Show success overlay (same UX pattern as cart checkout)
                showSubSuccess(data.transaction_id || '', data.amount || 0, data.end_at || '');
            } catch(e){
                err.textContent = e.message || 'Something went wrong';
                err.classList.remove('hidden');
                toast('Subscription failed', 'error');
            } finally {
                setLoading(false);
            }
            return false;
        }

    // Update UI to show premium status (legacy fallback not used; we reload after subscribe)
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

            const premiumBtn = document.getElementById('subscribe-btn');
            if (premiumBtn) {
                premiumBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>Current Plan';
                premiumBtn.disabled = true;
                premiumBtn.className = 'w-full bg-green-100 text-green-700 font-semibold py-4 px-6 rounded-xl cursor-not-allowed';
            }

            // Reinitialize icons
            lucide.createIcons();
        }

        function updateUIToFree(){
            // Update current plan card
            const card = document.getElementById('current-plan-card');
            if(card){
                card.className = 'bg-white border border-green-200 rounded-2xl p-6 mb-12';
                card.innerHTML = `
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
                </div>`;
            }

            // Remove manage row if exists
            const manageRow = document.getElementById('manage-plan-row');
            if(manageRow && manageRow.parentElement){ manageRow.remove(); }

            // Update free card badge/button
            const freeCard = document.getElementById('free-card');
            if(freeCard){
                if(!freeCard.querySelector('.current-plan-badge')){
                    const badge = document.createElement('div');
                    badge.className = 'current-plan-badge';
                    badge.innerHTML = '<i data-lucide="check" class="w-3 h-3 mr-1 inline"></i>Current Plan';
                    freeCard.appendChild(badge);
                }
                const btns = freeCard.querySelectorAll('button');
                btns.forEach(b=>{ b.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 mr-2 inline"></i>Current Plan'; b.disabled = true; b.className = 'w-full bg-gray-200 text-gray-500 font-semibold py-4 px-6 rounded-xl cursor-not-allowed transition-all duration-300'; });
            }

            // Update premium card button to allow subscribe again
            const premiumCard = document.getElementById('premium-card');
            if(premiumCard){
                const disabledBtn = premiumCard.querySelector('button[disabled]');
                if(disabledBtn){
                    disabledBtn.remove();
                    // Rebuild the subscribe form from server defaults
                    const form = document.createElement('form');
                    form.id = 'subscribe-form';
                    form.className = 'w-full';
                    form.setAttribute('onsubmit','return subscribe(event)');
                    form.innerHTML = `
                        <input type="hidden" name="action" value="subscribe" />
                        <input type="hidden" name="plan_id" value="${PLAN_ID}" />
                        <div class="space-y-4">
                            <div class="bg-white/10 rounded-lg p-4">
                                <label class="block text-sm font-semibold mb-2 text-white/90">Payment Method</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="flex items-center gap-2 text-white/90">
                                        <input type="radio" name="payment_method" value="cod" checked /> <span>COD</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-white/90">
                                        <input type="radio" name="payment_method" value="gcash" /> <span>GCash</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-white/90">
                                        <input type="radio" name="payment_method" value="maya" /> <span>Maya</span>
                                    </label>
                                </div>
                            </div>
                            <button id="subscribe-btn" type="submit" class="w-full bg-white text-orange-600 font-semibold py-4 px-6 rounded-xl hover:bg-orange-50 transition-all duration-300 transform hover:scale-105 pulse-glow">
                                <i data-lucide="crown" class="w-5 h-5 mr-2 inline"></i>
                                Upgrade to Premium
                            </button>
                            <p id="subscribe-error" class="hidden text-red-200 text-sm"></p>
                        </div>`;
                    premiumCard.appendChild(form);
                }
                // Remove any existing 'Current Plan' badge from premium card
                const badge = premiumCard.querySelector('.current-plan-badge');
                if(badge) badge.remove();
            }

            if(window.lucide) lucide.createIcons();
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

        // Payment method card selection & form toggle
        (function(){
            const list = document.getElementById('method-list');
            const methodInput = document.getElementById('payment_method');
            const fields = document.querySelectorAll('.method-fields');
            const billingCycle = document.getElementById('billing_cycle');
            const PREMIUM_MONTHLY = <?php echo json_encode((float)($plan['subscriptions_price'] ?? 299)); ?>;
            const PREMIUM_YEARLY = <?php echo json_encode(max(0, (float)($plan['subscriptions_price'] ?? 299) * 12 - 600)); ?>;
            function updateBillingUI(cycle){
                const priceEl = document.getElementById('billing-price');
                const periodEl = document.getElementById('billing-period');
                const nextEl = document.getElementById('billing-next');
                const mobPrice = document.getElementById('mobile-price');
                const mobPeriod = document.getElementById('mobile-period');
                if(cycle === 'yearly'){
                    priceEl.textContent = `₱${(PREMIUM_YEARLY).toFixed(2)}`;
                    periodEl.textContent = 'year';
                    nextEl.textContent = new Date(new Date().setFullYear(new Date().getFullYear()+1)).toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
                    if(mobPrice) mobPrice.textContent = `₱${(PREMIUM_YEARLY).toFixed(2)}`;
                    if(mobPeriod) mobPeriod.textContent = 'year';
                } else {
                    priceEl.textContent = `₱${(PREMIUM_MONTHLY).toFixed(2)}`;
                    periodEl.textContent = 'month';
                    nextEl.textContent = new Date(new Date().setMonth(new Date().getMonth()+1)).toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
                    if(mobPrice) mobPrice.textContent = `₱${(PREMIUM_MONTHLY).toFixed(2)}`;
                    if(mobPeriod) mobPeriod.textContent = 'month';
                }
                if(billingCycle) billingCycle.value = cycle;
            }
            function select(method){
                if(!methodInput) return;
                methodInput.value = method;
                document.querySelectorAll('.method-card').forEach(el=>{
                    if(el.getAttribute('data-method') === method){
                        el.classList.add('ring-2','ring-orange-400','border-orange-400');
                    } else {
                        el.classList.remove('ring-2','ring-orange-400','border-orange-400');
                    }
                });
                fields.forEach(f=>{
                    f.classList.toggle('hidden', f.getAttribute('data-fields') !== method);
                });
                if(window.lucide) lucide.createIcons();
            }
            if(list){
                list.addEventListener('click', (e)=>{
                    const btn = e.target.closest('.method-card');
                    if(!btn) return;
                    const method = btn.getAttribute('data-method');
                    if(method) select(method);
                });
                // Initialize default selection
                select(methodInput?.value || 'card');
            }

            // Hook up confirm buttons to submit the same form
            const confirmBtn = document.getElementById('confirm-btn');
            const confirmBtnM = document.getElementById('confirm-btn-mobile');
            const form = document.getElementById('subscribe-form');
            [confirmBtn, confirmBtnM].forEach(b=>{ if(b){ b.addEventListener('click', ()=>{ form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); }); } });

            // Plan modal logic
            const premiumPill = document.getElementById('premium-pill');
            const planModal = document.getElementById('plan-modal');
            const planClose = document.getElementById('plan-modal-close');
            const planCancel = document.getElementById('plan-cancel');
            const planApply = document.getElementById('plan-apply');
            const cycMonthly = document.getElementById('cycle-monthly');
            const cycYearly = document.getElementById('cycle-yearly');
            const yearlyNote = document.getElementById('yearly-note');

            let selectedCycle = 'monthly';
            function openModal(){ planModal.classList.remove('hidden'); }
            function closeModal(){ planModal.classList.add('hidden'); }
            function markCycle(){
                [cycMonthly,cycYearly].forEach(el=>el.classList.remove('ring-2','ring-orange-400','border-orange-300','bg-orange-50','text-orange-700'));
                if(selectedCycle==='monthly'){
                    cycMonthly.classList.add('ring-2','ring-orange-400','border-orange-300','bg-orange-50','text-orange-700');
                    yearlyNote.classList.add('hidden');
                    confirmBtn?.removeAttribute('disabled');
                    confirmBtnM?.removeAttribute('disabled');
                } else {
                    cycYearly.classList.add('ring-2','ring-orange-400','border-orange-300','bg-orange-50','text-orange-700');
                    yearlyNote.classList.add('hidden');
                }
            }
            premiumPill?.addEventListener('click', openModal);
            planClose?.addEventListener('click', closeModal);
            planCancel?.addEventListener('click', closeModal);
            cycMonthly?.addEventListener('click', ()=>{ selectedCycle='monthly'; markCycle(); });
            cycYearly?.addEventListener('click', ()=>{ selectedCycle='yearly'; markCycle(); });
            planApply?.addEventListener('click', ()=>{ updateBillingUI(selectedCycle); closeModal(); });
            // Initialize
            updateBillingUI('monthly');
            markCycle();
        })();
    </script>

        <!-- Success Overlay (match cart checkout pattern) -->
        <style>
            .sub-success-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:1200;backdrop-filter:saturate(140%) blur(2px)}
            .sub-success-overlay.show{display:flex}
            .sub-success-notification{background:#fff;border-radius:20px;padding:40px 42px;text-align:center;box-shadow:0 22px 48px -8px rgba(0,0,0,.35),0 4px 14px -2px rgba(0,0,0,.12);width:min(460px,90%);opacity:0;transform:translateY(60px) scale(.9);will-change:transform,opacity}
            .sub-success-overlay.show .sub-success-notification{animation:subModalBounceIn .7s cubic-bezier(.22,1.25,.36,1) forwards}
            @keyframes subModalBounceIn{0%{opacity:0;transform:translateY(60px) scale(.9)}55%{opacity:1;transform:translateY(-10px) scale(1.02)}75%{transform:translateY(6px) scale(.995)}100%{opacity:1;transform:translateY(0) scale(1)}}
        </style>
        <div class="sub-success-overlay" id="sub-success-overlay" aria-modal="true" role="dialog">
            <div class="sub-success-notification" role="document">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner"><i data-lucide="check" class="w-10 h-10 text-white"></i></div>
                <h3 class="font-extrabold text-gray-800 mb-3 text-2xl">Subscription Active</h3>
                <div class="text-gray-700 mb-6 text-sm" id="sub-success-message"></div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6 text-left text-sm">
                    <div class="p-3 rounded-lg bg-green-50 border border-green-200"><div class="text-[11px] text-green-700 font-semibold mb-1">Plan</div><div class="font-bold text-green-900">Premium</div></div>
                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-200"><div class="text-[11px] text-amber-700 font-semibold mb-1">Amount</div><div class="font-bold text-amber-900" id="sub-success-amount">—</div></div>
                    <div class="p-3 rounded-lg bg-blue-50 border border-blue-200"><div class="text-[11px] text-blue-700 font-semibold mb-1">Next Billing</div><div class="font-bold text-blue-900" id="sub-success-next">—</div></div>
                </div>
                <div class="flex gap-3">
                    <a href="subscriptions.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg btn-primary text-white"><i data-lucide="settings" class="w-5 h-5"></i> Manage Subscription</a>
                    <a href="buy_products.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg btn-secondary"><i data-lucide="shopping-bag" class="w-5 h-5"></i> Go to Shop</a>
                </div>
            </div>
        </div>
        <script>
            function showSubSuccess(tid, amount, next){
                const ov=document.getElementById('sub-success-overlay');
                const msg=document.getElementById('sub-success-message');
                const amtEl=document.getElementById('sub-success-amount');
                const nextEl=document.getElementById('sub-success-next');
                const amountStr = '₱'+Number(amount||0).toFixed(2);
                amtEl.textContent = amountStr;
                nextEl.textContent = next ? new Date(next).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}) : '—';
                msg.innerHTML = `${tid?`Transaction <span class=\"font-mono bg-gray-100 px-2 py-0.5 rounded\">#${tid}</span><br>`:''}Your premium access is now active.`;
                ov.classList.add('show');
                document.body.style.overflow='hidden';
                setTimeout(()=>{ try{ document.querySelector('#sub-success-overlay a')?.focus(); }catch{} }, 80);
                toast('Subscription activated','success');
                if(window.lucide) lucide.createIcons();
            }
            function closeSubSuccess(){const ov=document.getElementById('sub-success-overlay');ov.classList.remove('show');document.body.style.overflow='';}
            // Prevent closing by outside click
            document.getElementById('sub-success-overlay')?.addEventListener('click',e=>{ if(e.target.id==='sub-success-overlay'){ e.stopPropagation(); } });
            document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ closeSubSuccess(); } });
        </script>
</body>
</html>