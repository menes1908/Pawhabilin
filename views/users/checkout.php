<?php
if(session_status()===PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__,2).'/database.php';
require_once dirname(__DIR__,2).'/models/location.php';
require_once dirname(__DIR__,2).'/models/product.php';
require_once dirname(__DIR__,2).'/utils/session.php';
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); $csrf=$_SESSION['csrf'];
$user = get_current_user_session();
if(!$user){ header('Location: ../../login.php?redirect=views/users/checkout.php'); exit; }
$user_id = $user['users_id'];
$cart = $_SESSION['cart'] ?? [];
if(empty($cart)){ header('Location: buy_products.php'); exit; }
// Build cart items with latest data
$cartItems = []; $subtotal=0; $itemCount=0;
foreach($cart as $pid=>$ci){ $p=product_get_by_id($connections,(int)$pid); if(!$p) continue; $qty=(int)$ci['qty']; $price=(float)$p['products_price']; $line=$price*$qty; $subtotal+=$line; $itemCount+=$qty; $cartItems[]=['id'=>$pid,'name'=>$p['products_name'],'price'=>$price,'quantity'=>$qty,'image'=>$p['products_image_url']]; }
$hasDiscount=false; $discountRate=0.10; $res=$connections->query("SELECT 1 FROM user_subscriptions us JOIN subscriptions s ON s.subscriptions_id=us.subscriptions_id WHERE us.users_id=".(int)$user_id." AND us.us_status='active' LIMIT 1"); if($res && $res->num_rows>0) $hasDiscount=true; $discount=$hasDiscount ? $subtotal*$discountRate : 0; $deliveryFee=50; $total=$subtotal-$discount+$deliveryFee; $locations=location_get_all_by_user($connections,$user_id);
function h($v){return htmlspecialchars($v??'',ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Checkout - Pawhabilin</title>
<meta name="description" content="Complete your purchase securely on Pawhabilin." />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../globals.css" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<style>
/* Adopt design from mock (checkout copy) */
@keyframes gradientShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.progress-step{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e5e7eb;color:#6b7280;transition:.3s}.progress-step.active{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff}.progress-step.completed{background:#10b981;color:#fff}.progress-connector{width:32px;height:2px;background:#e5e7eb;margin:0 8px}.progress-connector.completed{background:#10b981}
.cart-item{background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;transition:.3s;position:relative;overflow:hidden}
.cart-item:hover{border-color:#f97316;box-shadow:0 4px 12px rgba(0,0,0,.05);transform:translateY(-2px)}
.quantity-controls{display:flex;align-items:center;gap:8px}.quantity-btn{width:32px;height:32px;border-radius:50%;border:2px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}.quantity-btn:hover{border-color:#f97316;background:#fef7f0}
.quantity-input{width:64px;height:32px;text-align:center;border:1px solid #e5e7eb;border-radius:6px}.quantity-input:focus{outline:none;border-color:#f97316;box-shadow:0 0 0 2px rgba(249,115,22,.1)}
.payment-option{border:2px solid #e5e7eb;border-radius:14px;padding:20px;cursor:pointer;transition:.25s;background:#f3f4f6;position:relative;display:block}
.payment-option:hover{border-color:#d1d5db;background:#eef0f2}
.payment-option:focus-within{outline:3px solid rgba(249,115,22,.45);outline-offset:2px}
.payment-option.selected{border-color:#f97316;background:#fff;box-shadow:0 6px 18px -4px rgba(249,115,22,.25),0 0 0 3px rgba(249,115,22,.25);transform:translateY(-2px)}
.payment-option .font-semibold{font-size:1.125rem;line-height:1.25rem}
.payment-option .text-sm{font-size:.95rem;line-height:1.15rem}
.payment-option .w-10.h-10{box-shadow:0 2px 6px rgba(0,0,0,.08)}
.fulfillment-pills label{position:relative;padding:14px 20px;border:2px solid #e5e7eb;border-radius:14px;background:#f3f4f6;display:flex;align-items:center;gap:10px;font-size:1.05rem;line-height:1.3;font-weight:600;transition:.25s}
.fulfillment-pills label:hover{background:#eef0f2;border-color:#d1d5db}
.fulfillment-pills input[type=radio]{width:20px;height:20px;border:2px solid #9ca3af;border-radius:50%;appearance:none;display:inline-flex;align-items:center;justify-content:center;position:relative;background:#fff;cursor:pointer;transition:.25s}
.fulfillment-pills input[type=radio]:focus{outline:3px solid rgba(249,115,22,.45);outline-offset:2px}
.fulfillment-pills input[type=radio]:checked{border-color:#f97316;background:#fff}
.fulfillment-pills input[type=radio]:checked::after{content:'';width:10px;height:10px;border-radius:50%;background:#f97316;display:block}
.fulfillment-pills label.active{border-color:#f97316;background:#fff;box-shadow:0 4px 14px -4px rgba(249,115,22,.35)}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;opacity:0;visibility:hidden;transition:.3s}.modal-overlay.show{opacity:1;visibility:visible}
.modal-content{background:#fff;border-radius:16px;padding:24px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;transform:scale(.9) translateY(20px);transition:.3s}.modal-overlay.show .modal-content{transform:scale(1) translateY(0)}
.success-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:1200;backdrop-filter:saturate(140%) blur(2px)}
.success-overlay.show{display:flex}
.success-notification{background:#fff;border-radius:20px;padding:40px 42px;text-align:center;box-shadow:0 22px 48px -8px rgba(0,0,0,.35),0 4px 14px -2px rgba(0,0,0,.12);width:min(460px,90%);opacity:0;transform:translateY(60px) scale(.9);will-change:transform,opacity}
.success-overlay.show .success-notification{animation:modalBounceIn .7s cubic-bezier(.22,1.25,.36,1) forwards}
.success-overlay.show .success-notification:focus{outline:none}
@keyframes modalBounceIn{0%{opacity:0;transform:translateY(60px) scale(.9)}55%{opacity:1;transform:translateY(-10px) scale(1.02)}75%{transform:translateY(6px) scale(.995)}100%{opacity:1;transform:translateY(0) scale(1)}}
.success-notification h3{font-size:1.9rem;line-height:1.15}
.success-notification p{font-size:1rem}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:8px;border:none;cursor:pointer;transition:.2s;text-decoration:none;font-weight:500}.btn-primary{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff}.btn-primary:hover{background:linear-gradient(135deg,#ea580c,#dc2626);box-shadow:0 4px 12px rgba(249,115,22,.3)}.btn-secondary{background:#fff;color:#374151;border:1px solid #e5e7eb}.btn-secondary:hover{background:#f9fafb}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600}.badge-primary{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
<header class="bg-white border-b border-gray-200 sticky top-0 z-40">
 <div class="max-w-7xl mx-auto px-4">
    <div class="flex h-16 items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-lg overflow-hidden">
                <div class="w-full h-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center"><i data-lucide="paw-print" class="w-6 h-6 text-white"></i></div>
            </div>
            <span class="text-xl font-semibold">pawhabilin</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="relative">
                <i data-lucide="shopping-cart" class="w-6 h-6 text-gray-600"></i>
                <div class="absolute -top-2 -right-2 w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center text-xs text-white font-bold"><?php echo $itemCount; ?></div>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm"><?php echo strtoupper(substr($user['users_name']??$user['users_email'],0,1)); ?></div>
                <span class="font-medium text-gray-700">Hi, <?php echo h(explode(' ',$user['users_name']??$user['users_email'])[0]); ?>!</span>
            </div>
        </div>
    </div>
 </div>
</header>
<main class="max-w-7xl mx-auto px-4 py-8">
 <!-- Progress Steps -->
 <div class="progress-steps flex items-center justify-center mb-8">
    <div class="flex items-center"><div class="progress-step active" id="step-1"><i data-lucide="shopping-cart" class="w-4 h-4"></i></div><div class="progress-connector"></div><div class="progress-step" id="step-2"><i data-lucide="credit-card" class="w-4 h-4"></i></div></div>
 </div>
 <div class="flex justify-center mb-8"><div class="flex items-center space-x-16 text-sm text-gray-600"><span id="step-1-label" class="font-medium text-orange-600">Cart</span><span id="step-2-label">Checkout</span></div></div>
 <!-- Cart Step -->
 <div id="cart-step-content" class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
     <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
         <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3"><div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center"><i data-lucide="shopping-cart" class="w-6 h-6 text-white"></i></div><div><h2 class="text-2xl font-bold">Your Cart</h2><p class="text-gray-600"><?php echo $itemCount; ?> items in your cart</p></div></div>
            <?php if($hasDiscount): ?><div class="badge badge-primary"><i data-lucide="star" class="w-3 h-3"></i> Subscriber 10% OFF</div><?php endif; ?>
         </div>
         <div class="space-y-4" id="cart-items-wrapper">
            <?php if(empty($cartItems)): ?>
                <div class="text-center py-16"><div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i data-lucide="shopping-cart" class="w-12 h-12 text-gray-400"></i></div><h3 class="text-xl font-semibold mb-2">Your cart is empty</h3><p class="text-gray-600 mb-6">Add some amazing pet products to get started!</p><a href="buy_products.php" class="btn btn-primary"><i data-lucide="package" class="w-4 h-4"></i> Continue Shopping</a></div>
            <?php else: foreach($cartItems as $item): $img=$item['image']; if($img && !preg_match('/^https?:/i',$img)) $img='../../'.ltrim($img,'/'); ?>
                <div class="cart-item" data-product-id="<?php echo (int)$item['id']; ?>">
                    <div class="flex gap-4">
                        <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100"><?php if($img): ?><img src="<?php echo h($img); ?>" class="w-full h-full object-cover" /><?php endif; ?></div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-2"><h3 class="font-semibold text-lg"><?php echo h($item['name']); ?></h3><button class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-full" onclick="removeItem(<?php echo (int)$item['id']; ?>)"><i data-lucide=trash-2 class="w-4 h-4"></i></button></div>
                            <div class="flex items-center justify-between">
                                <div class="space-y-2"><div class="text-2xl font-bold text-orange-600">₱<?php echo number_format($item['price'],2); ?></div><div class="text-sm text-gray-600">Subtotal: <span class="font-semibold text-orange-600 line-total">₱<?php echo number_format($item['price']*$item['quantity'],2); ?></span></div></div>
                                <div class="quantity-controls"><button class="quantity-btn" onclick="changeQty(<?php echo (int)$item['id']; ?>,-1)"><i data-lucide=minus class="w-3 h-3"></i></button><input type="number" class="quantity-input" min="1" value="<?php echo (int)$item['quantity']; ?>" onchange="setQty(<?php echo (int)$item['id']; ?>,this.value)"><button class="quantity-btn" onclick="changeQty(<?php echo (int)$item['id']; ?>,1)"><i data-lucide=plus class="w-3 h-3"></i></button></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
         </div>
         <?php if(!empty($cartItems)): ?>
         <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
             <a href="buy_products.php" class="btn btn-secondary"><i data-lucide=arrow-left class="w-4 h-4"></i> Continue Shopping</a>
             <button onclick="showCheckoutStep()" class="btn btn-primary">Proceed to Checkout <i data-lucide=arrow-right class="w-4 h-4"></i></button>
         </div>
         <?php endif; ?>
        </div>
     </div>
     <div class="lg:col-span-1">
        <div class="sticky top-6"><div class="bg-white rounded-xl border border-gray-200 p-6" id="cart-summary-box">
            <h3 class="text-xl font-semibold flex items-center gap-2 mb-6"><i data-lucide=package class="w-5 h-5 text-orange-500"></i> Order Summary</h3>
            <div class="space-y-3" id="summary-lines">
                <div class="flex justify-between"><span>Subtotal (<?php echo $itemCount; ?> items)</span><span class="font-semibold" id="sum-subtotal">₱<?php echo number_format($subtotal,2); ?></span></div>
                <?php if($hasDiscount): ?><div class="flex justify-between text-green-600" id="sum-discount"><span class="flex items-center gap-1"><i data-lucide=star class="w-4 h-4"></i> Subscriber Discount (10%)</span><span>-₱<?php echo number_format($discount,2); ?></span></div><?php endif; ?>
                <div class="flex justify-between"><span>Delivery Fee</span><span id="sum-delivery">₱<?php echo number_format($deliveryFee,2); ?></span></div>
                <div class="border-t pt-3 flex justify-between items-center"><span class="text-lg font-bold">Total</span><span class="text-2xl font-bold text-orange-600" id="sum-total">₱<?php echo number_format($total,2); ?></span></div>
            </div>
            <div class="pt-4 text-sm flex items-center gap-2 text-green-600"><i data-lucide=truck class="w-4 h-4"></i><span>Estimated delivery: 2-3 days</span></div>
        </div></div>
     </div>
    </div>
 </div>
 <!-- Checkout Step -->
 <div id="checkout-step-content" class="max-w-6xl mx-auto hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
     <div class="lg:col-span-2 space-y-6">
        <div class="flex items-center gap-3"><button onclick="showCartStep()" class="p-2 hover:bg-gray-100 rounded-full"><i data-lucide=arrow-left class="w-5 h-5"></i></button><div><h2 class="text-2xl font-bold">Order Confirmation</h2><p class="text-gray-600">Review your order and complete purchase</p></div></div>
        <!-- Fulfillment Selection -->
        <div class="bg-white rounded-xl border border-gray-200 p-6" id="fulfillment-box">
            <h3 class="text-2xl font-bold flex items-center gap-3 mb-5 tracking-tight"><i data-lucide=truck class="w-7 h-7 text-orange-500"></i> Fulfillment Method</h3>
            <p class="text-gray-600 mb-4 text-sm md:text-base">Choose how you'd like to receive your order.</p>
            <div class="fulfillment-pills flex flex-wrap gap-4 items-stretch">
                <label class="cursor-pointer" data-mode="delivery">
                    <input type="radio" name="fulfillment" value="delivery" class="fulfillment-radio" checked />
                    <span>Delivery</span>
                </label>
                <label class="cursor-pointer" data-mode="pickup">
                    <input type="radio" name="fulfillment" value="pickup" class="fulfillment-radio" />
                    <span>Pickup (In-Store)</span>
                </label>
                <div class="basis-full pl-1 pt-1 text-sm md:text-base text-gray-600 font-medium tracking-tight">
                    <span class="inline-flex items-center gap-1"><i data-lucide=info class="w-4 h-4 text-orange-500"></i> Selecting <strong>Pickup</strong> removes the delivery fee.</span>
                </div>
            </div>
            <div id="pickup-fields" class="hidden mt-5 grid md:grid-cols-3 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm md:text-base font-semibold mb-1">Pickup Date *</label>
                    <input type="date" id="pickup_date" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400" />
                </div>
                <div>
                    <label class="block text-sm md:text-base font-semibold mb-1">Pickup Time *</label>
                    <input type="time" id="pickup_time" step="3600" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400" />
                </div>
                <div class="text-xs md:text-sm text-gray-500 leading-snug md:col-span-1 sm:col-span-2 mt-1">
                    Allowed window: <span class="font-medium text-gray-700">08:00 - 16:00</span> (hourly slots).
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6" id="delivery-address-box">
            <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold flex items-center gap-2"><i data-lucide=map-pin class="w-5 h-5 text-blue-500"></i> Delivery Address</h3><button onclick="showAddressModal()" class="btn btn-secondary text-orange-600 border-orange-300"><i data-lucide=plus class="w-4 h-4"></i> Add Address</button></div>
            <div id="address-list-wrapper">
                <?php if($locations): $defaultLoc=null; foreach($locations as $l){ if($l['location_is_default']){$defaultLoc=$l; break;} } if(!$defaultLoc) $defaultLoc=$locations[0]; $full=trim($defaultLoc['location_address_line1'].' '.($defaultLoc['location_address_line2']??'').', '.($defaultLoc['location_barangay']??'').', '.$defaultLoc['location_city'].', '.$defaultLoc['location_province']); ?>
                    <div class="address-card bg-gradient-to-br from-blue-100 to-blue-200 border-2 border-blue-400 rounded-lg p-4" data-location-id="<?php echo (int)$defaultLoc['location_id']; ?>">
                        <div class="flex justify-between items-start"><div><div class="flex items-center gap-2 mb-2"><span class="badge badge-primary">Address</span><?php if($defaultLoc['location_is_default']): ?><span class="badge" style="background:#dcfce7;color:#166534">Default</span><?php endif; ?></div><h4 class="font-semibold text-gray-800"><?php echo h($defaultLoc['location_recipient_name']); ?></h4><p class="text-sm text-gray-600"><?php echo h($defaultLoc['location_phone']); ?></p><p class="text-sm text-gray-700 mt-1 leading-snug"><?php echo h($full); ?></p></div></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500"><i data-lucide=map-pin class="w-12 h-12 mx-auto mb-4 opacity-50"></i><p>No delivery address saved</p><button onclick="showAddressModal()" class="btn btn-primary mt-4">Add Address</button></div>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-3">(Delivery only) You can still change address or choose pickup later.</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6"><h3 class="text-lg font-semibold flex items-center gap-2 mb-4"><i data-lucide=package class="w-5 h-5 text-orange-500"></i> Order Items</h3><div class="space-y-4" id="confirm-items">
            <?php foreach($cartItems as $item): $img=$item['image']; if($img && !preg_match('/^https?:/i',$img)) $img='../../'.ltrim($img,'/'); ?>
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg"><div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100"><?php if($img): ?><img src="<?php echo h($img); ?>" class="w-full h-full object-cover" /><?php endif; ?></div><div class="flex-1"><h4 class="font-semibold"><?php echo h($item['name']); ?></h4><div class="flex items-center justify-between mt-1"><span class="text-sm text-gray-600">Qty: <?php echo (int)$item['quantity']; ?></span><span class="font-semibold text-orange-600">₱<?php echo number_format($item['price']*$item['quantity'],2); ?></span></div></div></div>
            <?php endforeach; ?>
        </div></div>
        <div class="bg-white rounded-xl border border-gray-200 p-6"><h3 class="text-lg font-semibold flex items-center gap-2 mb-4"><i data-lucide=credit-card class="w-5 h-5 text-green-500"></i> Payment Method</h3><div class="space-y-4" id="payment-options">
            <label class="payment-option selected"><input type="radio" name="payment_method" value="cod" class="sr-only" checked><div class="flex items-center gap-4"><div class="w-5 h-5 border-2 border-green-500 rounded-full flex items-center justify-center"><div class="w-2 h-2 bg-green-500 rounded-full"></div></div><div class="flex items-center gap-3"><div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i data-lucide=package class="w-5 h-5 text-green-600"></i></div><div><div class="font-semibold">Cash on Delivery</div><div class="text-sm text-gray-600">Pay when your order arrives</div></div></div></div></label>
            <label class="payment-option"><input type="radio" name="payment_method" value="gcash" class="sr-only"><div class="flex items-center gap-4"><div class="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center"><div class="w-2 h-2 bg-gray-300 rounded-full hidden"></div></div><div class="flex items-center gap-3"><div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">G</div><div><div class="font-semibold">GCash</div><div class="text-sm text-gray-600">Digital wallet payment</div></div></div></div></label>
            <label class="payment-option"><input type="radio" name="payment_method" value="maya" class="sr-only"><div class="flex items-center gap-4"><div class="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center"><div class="w-2 h-2 bg-gray-300 rounded-full hidden"></div></div><div class="flex items-center gap-3"><div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">M</div><div><div class="font-semibold">PayMaya</div><div class="text-sm text-gray-600">Digital wallet payment</div></div></div></div></label>
            <div class="hidden" id="payment-amount-section"><div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg"><label class="block text-base font-semibold text-yellow-800">Payment Amount (Must be exact)</label><input type="number" step="0.01" id="client_amount" class="mt-2 w-full border-yellow-300 rounded-md text-base px-3 py-2" placeholder="₱<?php echo number_format($total,2); ?>" /><p class="text-sm text-yellow-700 mt-2">Please enter exactly ₱<?php echo number_format($total,2); ?></p><div class="hidden text-sm text-red-600 mt-1" id="payment-error">Amount must match total.</div></div></div>
        </div></div>
     </div>
     <div class="lg:col-span-1"><div class="sticky top-6"><div class="bg-white rounded-xl border border-gray-200 p-6" id="confirm-summary-box"><h3 class="text-lg font-semibold mb-4">Order Summary</h3><div class="space-y-3"><div class="flex justify-between"><span>Subtotal</span><span id="c-subtotal">₱<?php echo number_format($subtotal,2); ?></span></div><?php if($hasDiscount): ?><div class="flex justify-between text-green-600"><span>Subscriber Discount</span><span id="c-discount">-₱<?php echo number_format($discount,2); ?></span></div><?php endif; ?><div class="flex justify-between"><span>Delivery Fee</span><span id="c-delivery">₱<?php echo number_format($deliveryFee,2); ?></span></div><div class="border-t pt-3 flex justify-between font-bold text-lg"><span>Total</span><span class="text-orange-600" id="c-total">₱<?php echo number_format($total,2); ?></span></div></div><div class="p-4 bg-green-50 rounded-lg border border-green-200 mt-4"><div class="flex items-center gap-2 text-green-700 mb-2"><i data-lucide=truck class="w-4 h-4"></i><span class="font-semibold">Estimated Delivery</span></div><p class="text-green-600 text-sm"><?php echo date('M j, Y',strtotime('+2 days')); ?> - <?php echo date('M j, Y',strtotime('+3 days')); ?></p></div><button onclick="placeOrder()" id="place-order-btn" class="btn btn-primary w-full mt-6"><i data-lucide=check-circle class="w-5 h-5"></i> Place Order</button><p id="order-error" class="text-xs text-red-600 mt-3 hidden"></p></div></div></div>
    </div>
 </div>
</main>
<!-- Address Modal -->
<div class="modal-overlay" id="address-modal">
 <div class="modal-content">
    <div class="flex items-center justify-between mb-6"><h3 class="text-2xl font-bold flex items-center gap-2"><i data-lucide=map-pin class="w-6 h-6 text-orange-500"></i> Add Delivery Address</h3><button onclick="closeAddressModal()" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide=x class="w-5 h-5"></i></button></div>
    <form id="add-address-form" class="space-y-4">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Label</label><select name="label" class="w-full border rounded-md px-3 py-2"><option value="Home">Home</option><option value="Office">Office</option></select></div><div><label class="block text-sm font-medium mb-1">Recipient Name *</label><input name="recipient_name" required class="w-full border rounded-md px-3 py-2" value="<?php echo h($user['users_name']??''); ?>" /></div></div>
        <div><label class="block text-sm font-medium mb-1">Phone</label><input name="phone" class="w-full border rounded-md px-3 py-2" /></div>
        <div><label class="block text-sm font-medium mb-1">Address Line 1 *</label><input name="line1" required class="w-full border rounded-md px-3 py-2" /></div>
        <div><label class="block text-sm font-medium mb-1">Address Line 2</label><input name="line2" class="w-full border rounded-md px-3 py-2" /></div>
        <div class="grid grid-cols-3 gap-4"><div><label class="block text-sm font-medium mb-1">Barangay</label><input name="barangay" class="w-full border rounded-md px-3 py-2" /></div><div><label class="block text-sm font-medium mb-1">City *</label><input name="city" required class="w-full border rounded-md px-3 py-2" /></div><div><label class="block text-sm font-medium mb-1">Province *</label><input name="province" required class="w-full border rounded-md px-3 py-2" /></div></div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" /> <span>Set as default</span></label>
        <div class="flex gap-3 pt-2"><button type="submit" class="btn btn-primary flex-1"><i data-lucide=plus class="w-4 h-4"></i> Add Address</button><button type="button" onclick="closeAddressModal()" class="btn btn-secondary flex-1">Cancel</button></div>
        <p id="address-form-error" class="text-xs text-red-600 hidden"></p>
    </form>
 </div>
</div>
<!-- Success Overlay -->
<div class="success-overlay" id="success-overlay" aria-modal="true" role="dialog">
    <div class="success-notification" role="document">
         <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner"><i data-lucide=check class="w-10 h-10 text-white"></i></div>
         <h3 class="font-extrabold text-gray-800 mb-3">Order Successful</h3>
         <p class="text-gray-600 mb-8" id="success-message">Your order has been confirmed.</p>
         <button onclick="backToShop()" id="success-back-btn" class="btn btn-primary w-full text-base py-4 font-semibold"><i data-lucide=shopping-bag class="w-5 h-5"></i> Back to Shopping</button>
    </div>
</div>
<script>
const CSRF = <?php echo json_encode($csrf); ?>;
const SUBTOTAL_INIT = <?php echo json_encode($subtotal); ?>;
let currentStep=1;
document.addEventListener('DOMContentLoaded',()=>{if(window.lucide) lucide.createIcons();initPayment();updateStepDisplay();});
function showCartStep(){currentStep=1;document.getElementById('cart-step-content').classList.remove('hidden');document.getElementById('checkout-step-content').classList.add('hidden');updateStepDisplay();}
function showCheckoutStep(){currentStep=2;document.getElementById('cart-step-content').classList.add('hidden');document.getElementById('checkout-step-content').classList.remove('hidden');updateStepDisplay();}
function updateStepDisplay(){const step1=document.getElementById('step-1'),step2=document.getElementById('step-2'),connector=document.querySelector('.progress-connector'),s1l=document.getElementById('step-1-label'),s2l=document.getElementById('step-2-label');if(currentStep===1){step1.classList.add('active');step1.classList.remove('completed');step2.classList.remove('active','completed');connector.classList.remove('completed');s1l.classList.add('text-orange-600','font-medium');s2l.classList.remove('text-orange-600','font-medium');}else{step1.classList.add('completed');step1.classList.remove('active');step2.classList.add('active');connector.classList.add('completed');s1l.classList.remove('text-orange-600','font-medium');s2l.classList.add('text-orange-600','font-medium');}}
function changeQty(id,delta){const input=document.querySelector(`[data-product-id="${id}"] .quantity-input`);if(!input)return;let v=parseInt(input.value||'1',10)+delta;if(v<1) v=1;setQty(id,v);}function setQty(id,v){v=parseInt(v,10);if(isNaN(v)||v<1) v=1;updateQtyRequest(id,v);}async function updateQtyRequest(id,qty){const fd=new FormData();fd.append('csrf',CSRF);fd.append('product_id',id);fd.append('qty',qty);const res=await fetch('../../shop/cart_update.php',{method:'POST',body:fd});const data=await res.json();if(!data.ok){toast('Update failed','error');return;}if(!data.item){document.querySelector(`[data-product-id="${id}"]`)?.remove();}else{const row=document.querySelector(`[data-product-id="${id}"]`);if(row){row.querySelector('.quantity-input').value=data.item.qty;const price=parseFloat(data.item.price);row.querySelector('.line-total').textContent='₱'+(price*data.item.qty).toFixed(2);} }recalcSummary(data.subtotal);}async function removeItem(id){const fd=new FormData();fd.append('csrf',CSRF);fd.append('product_id',id);fd.append('qty',0);const res=await fetch('../../shop/cart_update.php',{method:'POST',body:fd});const data=await res.json();if(data.ok){document.querySelector(`[data-product-id="${id}"]`)?.remove();recalcSummary(data.subtotal);if(!data.item && data.cartCount===0){location.href='buy_products.php';}}}
function currentFulfillment(){return document.querySelector('input[name="fulfillment"]:checked')?.value||'delivery';}
function recalcSummary(subtotal){
    const hasDiscountEl=document.getElementById('sum-discount');
    let discount=0; if(hasDiscountEl) discount=parseFloat(<?php echo $hasDiscount? '0.10':'0'; ?>)*subtotal;
    const delivery = currentFulfillment()==='delivery'?50:0; // pickup => 0
    const total = subtotal - discount + delivery;
    ['sum-subtotal','c-subtotal'].forEach(id=>{const el=document.getElementById(id);if(el) el.textContent='₱'+subtotal.toFixed(2);});
    if(document.getElementById('sum-total')) document.getElementById('sum-total').textContent='₱'+total.toFixed(2);
    if(document.getElementById('c-total')) document.getElementById('c-total').textContent='₱'+total.toFixed(2);
    if(document.getElementById('c-discount')) document.getElementById('c-discount').textContent='-₱'+discount.toFixed(2);
    if(document.getElementById('sum-discount')) document.getElementById('sum-discount').querySelector('span:last-child').textContent='-₱'+discount.toFixed(2);
    if(document.getElementById('sum-delivery')) document.getElementById('sum-delivery').textContent='₱'+delivery.toFixed(2);
    if(document.getElementById('c-delivery')) document.getElementById('c-delivery').textContent='₱'+delivery.toFixed(2);
    const ca=document.getElementById('client_amount');
    if(ca){ca.setAttribute('placeholder','₱'+total.toFixed(2));const helper=ca.parentElement?.querySelector('p.text-sm.text-yellow-700');if(helper) helper.innerHTML='Please enter exactly <strong>₱'+total.toFixed(2)+'</strong>';}
}
// Fulfillment radio logic
document.addEventListener('DOMContentLoaded',()=>{
    const radios=document.querySelectorAll('.fulfillment-radio');
    const pickup=document.getElementById('pickup-fields');
    const addressBox=document.getElementById('delivery-address-box');
    const pd=document.getElementById('pickup_date');
    const pt=document.getElementById('pickup_time');
    if(pd){const today=new Date();const y=today.getFullYear();const m=String(today.getMonth()+1).padStart(2,'0');const d=String(today.getDate()).padStart(2,'0');pd.min=`${y}-${m}-${d}`;pd.value=`${y}-${m}-${d}`;}
    if(pt){pt.value='10:00';}
    radios.forEach(r=>r.addEventListener('change',()=>{
        // visual active state
        document.querySelectorAll('.fulfillment-pills label').forEach(l=>l.classList.remove('active'));
        const lbl=r.closest('label'); if(lbl) lbl.classList.add('active');
        if(r.value==='pickup'&&r.checked){pickup.classList.remove('hidden');addressBox.classList.add('opacity-40','pointer-events-none');}
        if(r.value==='delivery'&&r.checked){pickup.classList.add('hidden');addressBox.classList.remove('opacity-40','pointer-events-none');}
        recalcSummary(SUBTOTAL_INIT);
    }));
    // set initial active
    const init=document.querySelector('.fulfillment-pills input[type=radio]:checked'); if(init){const lbl=init.closest('label'); if(lbl) lbl.classList.add('active');}
});
function initPayment(){
    document.querySelectorAll('input[name="payment_method"]').forEach(r=>{
        r.addEventListener('change',()=>{
            document.querySelectorAll('.payment-option').forEach(c=>c.classList.remove('selected'));
            const card=r.closest('.payment-option'); if(card){card.classList.add('selected');card.scrollIntoView({block:'nearest',behavior:'smooth'});} 
            const sec=document.getElementById('payment-amount-section');
            if(r.value==='gcash'||r.value==='maya'){sec.classList.remove('hidden');} else {sec.classList.add('hidden');}
            // Add focus ring manually for accessibility
            if(card) card.focus?.();
        });
        // keyboard accessibility
        const card=r.closest('.payment-option'); if(card){card.tabIndex=0;card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();r.checked=true;r.dispatchEvent(new Event('change'));}});}    
    });
}
function showAddressModal(){document.getElementById('address-modal').classList.add('show');}
function closeAddressModal(){document.getElementById('address-modal').classList.remove('show');}
document.getElementById('add-address-form').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);try{const res=await fetch('../../users/location_add.php',{method:'POST',body:fd});const data=await res.json();if(!data.ok) throw new Error(data.error||'Error');renderAddresses(data.locations);closeAddressModal();toast('Address added','success');e.target.reset();}catch(err){const el=document.getElementById('address-form-error');el.textContent=err.message;el.classList.remove('hidden');}});
function renderAddresses(list){if(!list.length){document.getElementById('address-list-wrapper').innerHTML='<div class="text-center py-8 text-gray-500"><i data-lucide=map-pin class="w-12 h-12 mx-auto mb-4 opacity-50"></i><p>No addresses saved</p></div>';return;}const def=list.find(l=>l.is_default)||list[0];document.getElementById('address-list-wrapper').innerHTML=`<div class=\"address-card bg-gradient-to-br from-blue-100 to-blue-200 border-2 border-blue-400 rounded-lg p-4\" data-location-id=\"${def.id}\"><div class=\"flex justify-between items-start\"><div><div class=\"flex items-center gap-2 mb-2\"><span class=\"badge badge-primary\">Address</span>${def.is_default?'<span class=\"badge\" style=\"background:#dcfce7;color:#166534\">Default</span>':''}</div><h4 class=\"font-semibold text-gray-800\">${escapeHtml(def.recipient)}</h4><p class=\"text-sm text-gray-600\">${escapeHtml(def.phone||'')}</p><p class=\"text-sm text-gray-700 mt-1 leading-snug\">${escapeHtml(def.full)}</p></div></div></div>`;if(window.lucide) lucide.createIcons();}
async function placeOrder(){const payment=document.querySelector('input[name="payment_method"]:checked')?.value||'cod';const fulfillment=currentFulfillment();const locationBox=document.querySelector('[data-location-id]');const locationId=locationBox?locationBox.getAttribute('data-location-id'):'';const clientAmtEl=document.getElementById('client_amount');const clientAmount=clientAmtEl && !clientAmtEl.closest('.hidden')?clientAmtEl.value:'';if((payment==='gcash'||payment==='maya') && clientAmount===''){showOrderError('Enter exact amount');return;}if(fulfillment==='delivery' && !locationId){showOrderError('Please add/select a delivery address');return;}const pd=document.getElementById('pickup_date')?.value;const pt=document.getElementById('pickup_time')?.value; if(fulfillment==='pickup'){ if(!pd||!pt){showOrderError('Pickup date & time required');return;} }
 const fd=new FormData();fd.append('csrf',CSRF);fd.append('fulfillment',fulfillment);if(locationId && fulfillment==='delivery') fd.append('location_id',locationId);fd.append('payment_method',payment);if(clientAmount) fd.append('client_amount',clientAmount);if(fulfillment==='pickup'){fd.append('pickup_date',pd);fd.append('pickup_time',pt+':00');}
 togglePlaceBtn(true);try{const res=await fetch('../../shop/order_place.php',{method:'POST',body:fd});const data=await res.json();if(!data.ok) throw new Error(mapOrderError(data));showSuccess(data.transaction_id,data.total);}catch(e){showOrderError(e.message||'Order failed');}finally{togglePlaceBtn(false);} }
function mapOrderError(d){switch(d.error){case'amount_mismatch':return 'Amount does not match total';case'no_location':return 'Please add an address first';case'stock_changed':return 'Stock changed for a product. Refresh cart.';default:return 'Order failed';}}
function togglePlaceBtn(dis){const b=document.getElementById('place-order-btn');if(!b)return;b.disabled=dis;b.innerHTML=dis?'<div class="loading-spinner w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Processing...':'<i data-lucide=check-circle class="w-5 h-5"></i> Place Order';if(window.lucide) lucide.createIcons();}
function showOrderError(msg){const el=document.getElementById('order-error');el.textContent=msg;el.classList.remove('hidden');toast(msg,'error');}
function showSuccess(tid,total){
    const ov=document.getElementById('success-overlay');
    const msg=document.getElementById('success-message');
    msg.innerHTML=`Transaction <span class=\"font-mono bg-gray-100 px-2 py-0.5 rounded\">#${tid}</span><br>Total Paid: ₱${Number(total).toFixed(2)}`;
    ov.classList.add('show');
    document.body.style.overflow='hidden';
    setTimeout(()=>document.getElementById('success-back-btn')?.focus(),60);
    toast('Order placed','success');
    // prevent closing by outside click
    ov.addEventListener('click',e=>{if(e.target===ov){e.stopPropagation();}}, {once:true});
    document.addEventListener('keydown',escCloseSuccess);
}
function escCloseSuccess(e){if(e.key==='Escape'){closeSuccessModal();}}
function backToShop(){location.href='buy_products.php';}
function closeSuccessModal(){const ov=document.getElementById('success-overlay');ov.classList.remove('show');document.body.style.overflow='';document.removeEventListener('keydown',escCloseSuccess);} 
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');});
function toast(msg,type='success'){let c=document.getElementById('toast-container');if(!c){c=document.createElement('div');c.id='toast-container';c.className='fixed top-4 right-4 z-[1200] flex flex-col gap-2';document.body.appendChild(c);}const div=document.createElement('div');div.className=`px-4 py-3 rounded-lg text-sm shadow font-medium ${type==='success'?'bg-green-600 text-white':type==='error'?'bg-red-600 text-white':'bg-blue-600 text-white'}`;div.textContent=msg;c.appendChild(div);setTimeout(()=>{div.style.opacity='0';setTimeout(()=>div.remove(),300);},2500);}function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]||c));}
</script>
</body>
</html>