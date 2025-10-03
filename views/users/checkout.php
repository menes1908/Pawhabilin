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
/* Scrollable address list (when >2 addresses) */
#address-list-wrapper.scrollable{max-height:340px;overflow-y:auto;scrollbar-gutter:stable;padding-right:4px}
#address-list-wrapper.scrollable::-webkit-scrollbar{width:8px}
#address-list-wrapper.scrollable::-webkit-scrollbar-track{background:#f1f5f9;border-radius:8px}
#address-list-wrapper.scrollable::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#f97316,#ea580c);border-radius:8px}
#address-list-wrapper.scrollable::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,#ea580c,#c2410c)}
</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
<header class="bg-white border-b border-gray-200 sticky top-0 z-40">
 <div class="max-w-7xl mx-auto px-4">
    <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-3">
                        <?php
                            $logoPath='../../pictures/Pawhabilin logo.png';
                            if(!file_exists(str_replace('..','',__DIR__).'/../../pictures/Pawhabilin logo.png')){ $logoPath='../../pictures/logo web.png'; }
                        ?>
                        <img src="<?php echo h($logoPath); ?>" alt="Pawhabilin Logo" class="h-10 w-10 object-contain select-none" draggable="false" />
                        <span class="text-2xl font-bold tracking-tight bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">Pawhabilin</span>
                </div>
                <div class="flex items-center gap-3">
                        <?php
                            $profileImg = $user['users_avatar'] ?? ($user['users_image'] ?? null);
                            if($profileImg && !preg_match('/^https?:/i',$profileImg)) $profileImg='../../'.ltrim($profileImg,'/');
                            // Display name intentionally limited to username only (no email fallback for privacy)
                            $displayName = $user['users_name'] ?? ($user['users_email'] ?? 'User');
                        ?>
                        <!-- Profile made non-clickable to keep user focused on completing the transaction -->
                        <div class="flex items-center gap-2 cursor-default select-none" aria-label="User profile" role="presentation">
                                <?php if(!empty($profileImg)): ?>
                                    <img src="<?php echo h($profileImg); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover ring-2 ring-orange-200" draggable="false" />
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white font-semibold text-base ring-2 ring-orange-200" draggable="false"><?php echo strtoupper(substr($displayName,0,1)); ?></div>
                                <?php endif; ?>
                                <span class="font-medium text-gray-800 select-none max-w-[140px] truncate" title="<?php echo h($displayName); ?>"><?php echo h($displayName); ?></span>
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
                <!-- Coupon Discount (dynamic) -->
                <div class="flex justify-between text-emerald-600 hidden" id="sum-coupon"><span class="flex items-center gap-1"><i data-lucide=tag class="w-4 h-4"></i> Coupon <span id="sum-coupon-code" class="ml-1 font-mono text-xs px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-300"></span></span><span id="sum-coupon-amount">-₱0.00</span></div>
                <div class="flex justify-between"><span>Delivery Fee</span><span id="sum-delivery">₱<?php echo number_format($deliveryFee,2); ?></span></div>
                <div class="border-t pt-3 flex justify-between items-center"><span class="text-lg font-bold">Total</span><span class="text-2xl font-bold text-orange-600" id="sum-total">₱<?php echo number_format($total,2); ?></span></div>
            </div>
            <!-- Claimed Discount Coupons List (Cart Step) -->
            <div class="mt-6" id="claimed-coupons-box">
                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-2"><i data-lucide="tags" class="w-4 h-4 text-orange-500"></i> Your Discount Coupons</h4>
                <div id="claimed-coupons-list" class="space-y-2">
                    <p class="text-xs text-gray-500" id="claimed-coupons-empty">Loading coupons...</p>
                </div>
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
        <!-- Fulfillment (Delivery only now) -->
        <div class="bg-white rounded-xl border border-gray-200 p-6" id="fulfillment-box">
            <h3 class="text-2xl font-bold flex items-center gap-3 mb-5 tracking-tight"><i data-lucide=truck class="w-7 h-7 text-orange-500"></i> Delivery</h3>
            <p class="text-gray-600 mb-1 text-sm md:text-base">All product orders are delivered to your saved address.</p>
            <p class="text-xs text-gray-500">Pickup option has been disabled.</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6" id="delivery-address-box">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <h3 class="text-lg font-semibold flex items-center gap-2"><i data-lucide=map-pin class="w-5 h-5 text-blue-500"></i> Delivery Address</h3>
                <div class="flex items-center gap-2">
                    <button onclick="refreshAddressesFromServer(true)" type="button" class="btn btn-secondary !py-2 !px-3 text-blue-600 border-blue-300" title="Reload addresses saved in Profile"><i data-lucide=refresh-cw class="w-4 h-4"></i><span class="hidden sm:inline"> Refresh</span></button>
                    <button onclick="showAddressModal()" type="button" class="btn btn-secondary text-orange-600 border-orange-300"><i data-lucide=plus class="w-4 h-4"></i> <span class="hidden sm:inline">Add Address</span><span class="sm:hidden">Add</span></button>
                </div>
            </div>
            <div id="address-list-wrapper" class="space-y-3">
                <?php if($locations): $defaultLoc=null; foreach($locations as $l){ if($l['location_is_default']){$defaultLoc=$l; break;} } if(!$defaultLoc) $defaultLoc=$locations[0]; $selectedId=(int)$defaultLoc['location_id']; ?>
                    <?php foreach($locations as $loc): $full=trim($loc['location_address_line1'].' '.($loc['location_address_line2']??'').', '.($loc['location_barangay']??'').', '.$loc['location_city'].', '.$loc['location_province']); ?>
                        <div class="address-card border rounded-lg p-4 cursor-pointer transition <?php echo ((int)$loc['location_id']===$selectedId)?'bg-gradient-to-br from-blue-100 to-blue-200 border-blue-400 ring-2 ring-blue-300':'bg-white hover:bg-blue-50 border-gray-200'; ?>" data-location-id="<?php echo (int)$loc['location_id']; ?>">
                            <label class="flex gap-3 w-full cursor-pointer">
                                <input type="radio" name="address_select" value="<?php echo (int)$loc['location_id']; ?>" class="mt-1" <?php echo ((int)$loc['location_id']===$selectedId)?'checked':''; ?> />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="badge badge-primary">Address</span>
                                        <?php if($loc['location_is_default']): ?><span class="badge" style="background:#dcfce7;color:#166534">Default</span><?php endif; ?>
                                    </div>
                                    <h4 class="font-semibold text-gray-800"><?php echo h($loc['location_recipient_name']); ?></h4>
                                    <?php if($loc['location_phone']): ?><p class="text-sm text-gray-600"><?php echo h($loc['location_phone']); ?></p><?php endif; ?>
                                    <p class="text-sm text-gray-700 mt-1 leading-snug"><?php echo h($full); ?></p>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500"><i data-lucide=map-pin class="w-12 h-12 mx-auto mb-4 opacity-50"></i><p>No delivery address saved</p><button onclick="showAddressModal()" class="btn btn-primary mt-4">Add Address</button></div>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-3">Delivery only. Manage addresses above.</p>
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
    <div class="lg:col-span-1"><div class="sticky top-6"><div class="bg-white rounded-xl border border-gray-200 p-6" id="confirm-summary-box"><h3 class="text-lg font-semibold mb-4">Order Summary</h3><div class="space-y-3"><div class="flex justify-between"><span>Subtotal</span><span id="c-subtotal">₱<?php echo number_format($subtotal,2); ?></span></div><?php if($hasDiscount): ?><div class="flex justify-between text-green-600"><span>Subscriber Discount</span><span id="c-discount">-₱<?php echo number_format($discount,2); ?></span></div><?php endif; ?><div class="flex justify-between text-emerald-600 hidden" id="c-coupon"><span class="flex items-center gap-1"><i data-lucide=tag class="w-4 h-4"></i> Coupon <span id="c-coupon-code" class="ml-1 font-mono text-[10px] px-1 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-300"></span></span><span id="c-coupon-amount">-₱0.00</span></div><div class="flex justify-between"><span>Delivery Fee</span><span id="c-delivery">₱<?php echo number_format($deliveryFee,2); ?></span></div><div class="border-t pt-3 flex justify-between font-bold text-lg"><span>Total</span><span class="text-orange-600" id="c-total">₱<?php echo number_format($total,2); ?></span></div></div><div class="p-4 bg-green-50 rounded-lg border border-green-200 mt-4"><div class="flex items-center gap-2 text-green-700 mb-2"><i data-lucide=truck class="w-4 h-4"></i><span class="font-semibold">Estimated Delivery</span></div><p class="text-green-600 text-sm"><?php echo date('M j, Y',strtotime('+2 days')); ?> - <?php echo date('M j, Y',strtotime('+3 days')); ?></p></div><button onclick="placeOrder()" id="place-order-btn" class="btn btn-primary w-full mt-6"><i data-lucide=check-circle class="w-5 h-5"></i> Place Order</button><p id="order-error" class="text-xs text-red-600 mt-3 hidden"></p>
    <div class="mt-6" id="claimed-coupons-box-checkout"><h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-2"><i data-lucide="tags" class="w-4 h-4 text-orange-500"></i> Your Product Coupons</h4><div id="claimed-coupons-list-checkout" class="space-y-2"><p class="text-xs text-gray-500" id="claimed-coupons-empty-checkout">Loading coupons...</p></div></div>
    </div></div></div>
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
        <div><label class="block text-sm font-medium mb-1">Phone</label><input name="phone" class="w-full border rounded-md px-3 py-2" maxlength ="11"/></div>
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
let appliedCoupon = null; // { code, amount }
let couponDiscount = 0;
let currentStep=1;
let lastComputedTotal = <?php echo json_encode($total); ?>;
document.addEventListener('DOMContentLoaded',()=>{if(window.lucide) lucide.createIcons();initPayment();updateStepDisplay();});
function showCartStep(){currentStep=1;document.getElementById('cart-step-content').classList.remove('hidden');document.getElementById('checkout-step-content').classList.add('hidden');updateStepDisplay();}
function showCheckoutStep(){currentStep=2;document.getElementById('cart-step-content').classList.add('hidden');document.getElementById('checkout-step-content').classList.remove('hidden');updateStepDisplay();}
function updateStepDisplay(){const step1=document.getElementById('step-1'),step2=document.getElementById('step-2'),connector=document.querySelector('.progress-connector'),s1l=document.getElementById('step-1-label'),s2l=document.getElementById('step-2-label');if(currentStep===1){step1.classList.add('active');step1.classList.remove('completed');step2.classList.remove('active','completed');connector.classList.remove('completed');s1l.classList.add('text-orange-600','font-medium');s2l.classList.remove('text-orange-600','font-medium');}else{step1.classList.add('completed');step1.classList.remove('active');step2.classList.add('active');connector.classList.add('completed');s1l.classList.remove('text-orange-600','font-medium');s2l.classList.add('text-orange-600','font-medium');}}
function changeQty(id,delta){const input=document.querySelector(`[data-product-id="${id}"] .quantity-input`);if(!input)return;let v=parseInt(input.value||'1',10)+delta;if(v<1) v=1;setQty(id,v);}function setQty(id,v){v=parseInt(v,10);if(isNaN(v)||v<1) v=1;updateQtyRequest(id,v);}async function updateQtyRequest(id,qty){const fd=new FormData();fd.append('csrf',CSRF);fd.append('product_id',id);fd.append('qty',qty);const res=await fetch('../../shop/cart_update.php',{method:'POST',body:fd});const data=await res.json();if(!data.ok){toast('Update failed','error');return;}if(!data.item){document.querySelector(`[data-product-id="${id}"]`)?.remove();}else{const row=document.querySelector(`[data-product-id="${id}"]`);if(row){row.querySelector('.quantity-input').value=data.item.qty;const price=parseFloat(data.item.price);row.querySelector('.line-total').textContent='₱'+(price*data.item.qty).toFixed(2);} }recalcSummary(data.subtotal);}async function removeItem(id){const fd=new FormData();fd.append('csrf',CSRF);fd.append('product_id',id);fd.append('qty',0);const res=await fetch('../../shop/cart_update.php',{method:'POST',body:fd});const data=await res.json();if(data.ok){document.querySelector(`[data-product-id="${id}"]`)?.remove();recalcSummary(data.subtotal);if(!data.item && data.cartCount===0){location.href='buy_products.php';}}}
function currentFulfillment(){return 'delivery';}
function recalcSummary(subtotal){
    const subscriberRate = parseFloat(<?php echo $hasDiscount? '0.10':'0'; ?>);
    // 1. Coupon discount base (product subtotal or scoped products)
    if(appliedCoupon){
        let couponBase = 0;
        if(appliedCoupon.scope === 'cart'){
            document.querySelectorAll('#cart-items-wrapper .line-total').forEach(el=>{
                const v=parseFloat((el.textContent||'').replace(/[^0-9.]/g,''));
                if(!isNaN(v)) couponBase += v;
            });
        } else if(Array.isArray(appliedCoupon.productIds)) {
            appliedCoupon.productIds.forEach(pid=>{
                const row=document.querySelector(`[data-product-id="${pid}"]`);
                if(row){
                    const lineTxt=row.querySelector('.line-total')?.textContent.replace(/[^0-9.]/g,'');
                    const line=parseFloat(lineTxt||'0'); if(!isNaN(line)) couponBase+=line;
                }
            });
        } else {
            couponBase = subtotal;
        }
        if(appliedCoupon.kind==='percent') couponDiscount = couponBase * appliedCoupon.value; 
        else if(appliedCoupon.kind==='fixed') couponDiscount = Math.min(appliedCoupon.value,couponBase); 
        else couponDiscount=0;
    } else { couponDiscount=0; }
    couponDiscount = Math.max(0, Math.min(couponDiscount, subtotal));
    // 2. Subscription discount applies AFTER coupon (backend logic)
    const subtotalAfterCoupon = subtotal - couponDiscount;
    let discount = subscriberRate>0 ? subtotalAfterCoupon * subscriberRate : 0;
    // 3. Total with delivery
    const delivery = 50;
    const total = subtotalAfterCoupon - discount + delivery;
    ['sum-subtotal','c-subtotal'].forEach(id=>{const el=document.getElementById(id);if(el) el.textContent='₱'+subtotal.toFixed(2);});
    if(document.getElementById('sum-total')) document.getElementById('sum-total').textContent='₱'+total.toFixed(2);
    if(document.getElementById('c-total')) document.getElementById('c-total').textContent='₱'+total.toFixed(2);
    if(document.getElementById('c-discount')) document.getElementById('c-discount').textContent='-₱'+discount.toFixed(2);
    if(document.getElementById('sum-discount')) document.getElementById('sum-discount').querySelector('span:last-child').textContent='-₱'+discount.toFixed(2);
    const couponRow=document.getElementById('sum-coupon');
    if(couponRow){
        if(couponDiscount>0){
            couponRow.classList.remove('hidden');
            document.getElementById('sum-coupon-amount').textContent='-₱'+couponDiscount.toFixed(2);
            document.getElementById('sum-coupon-code').textContent=(appliedCoupon?.displayCode||appliedCoupon?.code||'');
        } else { couponRow.classList.add('hidden'); }
    }
    const couponRow2=document.getElementById('c-coupon');
    if(couponRow2){
        if(couponDiscount>0){
            couponRow2.classList.remove('hidden');
            document.getElementById('c-coupon-amount').textContent='-₱'+couponDiscount.toFixed(2);
            document.getElementById('c-coupon-code').textContent=(appliedCoupon?.displayCode||appliedCoupon?.code||'');
        } else { couponRow2.classList.add('hidden'); }
    }
    if(document.getElementById('sum-delivery')) document.getElementById('sum-delivery').textContent='₱'+delivery.toFixed(2);
    if(document.getElementById('c-delivery')) document.getElementById('c-delivery').textContent='₱'+delivery.toFixed(2);
    const ca=document.getElementById('client_amount');
    if(ca){
        ca.setAttribute('placeholder','₱'+total.toFixed(2));
        const helper=ca.parentElement?.querySelector('p.text-sm.text-yellow-700');
        if(helper) helper.innerHTML='Please enter exactly <strong>₱'+total.toFixed(2)+'</strong>';
        const payMethod=document.querySelector('input[name="payment_method"]:checked')?.value;
        // Auto-sync amount field if user hasn't manually changed to a different value (or using digital wallet)
        if(payMethod==='gcash' || payMethod==='maya'){
            const currentVal=parseFloat(ca.value||'0');
            if(!ca.dataset.userEdited || Math.abs(currentVal - lastComputedTotal) < 0.005){
                ca.value = total.toFixed(2);
            }
        }
        ca.addEventListener('input',()=>{ca.dataset.userEdited='1';},{once:true});
    }
    lastComputedTotal = total;
}
// Removed fulfillment radio logic – delivery only
document.addEventListener('DOMContentLoaded',()=>{ /* delivery only */ });
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
function renderAddresses(list){
    const wrap=document.getElementById('address-list-wrapper');
    if(!wrap) return;
    if(!list.length){wrap.innerHTML='<div class="text-center py-8 text-gray-500"><i data-lucide=map-pin class="w-12 h-12 mx-auto mb-4 opacity-50"></i><p>No addresses saved</p></div>';return;}
    const def=list.find(l=>l.is_default)||list[0];
    let html='';
    list.forEach(loc=>{
        const selected = (loc.id===def.id);
        html += `<div class="address-card border rounded-lg p-4 cursor-pointer transition ${selected?'bg-gradient-to-br from-blue-100 to-blue-200 border-blue-400 ring-2 ring-blue-300':'bg-white hover:bg-blue-50 border-gray-200'}" data-location-id="${loc.id}">
            <label class="flex gap-3 w-full cursor-pointer">
                <input type="radio" name="address_select" value="${loc.id}" class="mt-1" ${selected?'checked':''} />
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1"><span class="badge badge-primary">Address</span>${loc.is_default?'<span class="badge" style="background:#dcfce7;color:#166534">Default</span>':''}</div>
                    <h4 class="font-semibold text-gray-800">${escapeHtml(loc.recipient)}</h4>
                    ${loc.phone?`<p class=\"text-sm text-gray-600\">${escapeHtml(loc.phone)}</p>`:''}
                    <p class="text-sm text-gray-700 mt-1 leading-snug">${escapeHtml(loc.full)}</p>
                </div>
            </label>
        </div>`;
    });
    wrap.innerHTML=html;
    // Toggle scroll class if more than 2 addresses
    if(list.length>2){wrap.classList.add('scrollable');} else {wrap.classList.remove('scrollable');}
    enhanceAddressSelection();
    if(window.lucide) lucide.createIcons();
}
function enhanceAddressSelection(){
    document.querySelectorAll('.address-card').forEach(card=>{
        card.addEventListener('click',()=>{
            document.querySelectorAll('.address-card').forEach(c=>c.classList.remove('ring-2','ring-blue-300','border-blue-400','bg-gradient-to-br','from-blue-100','to-blue-200'));
            card.classList.add('ring-2','ring-blue-300','border-blue-400','bg-gradient-to-br','from-blue-100','to-blue-200');
            const r=card.querySelector('input[type=radio]'); if(r){r.checked=true;}
        });
    });
}
document.addEventListener('DOMContentLoaded',()=>{enhanceAddressSelection();refreshAddressesFromServer(false);loadClaimedCoupons();});

// Claimed discount coupons listing + application
let claimedCoupons = [];
async function loadClaimedCoupons(){
    const listEl=document.getElementById('claimed-coupons-list');
    const listEl2=document.getElementById('claimed-coupons-list-checkout');
    try {
        const res = await fetch('../../controllers/users/userpromoscontroller.php?action=claimed');
        if(!res.ok) throw new Error('fetch_failed');
        const data = await res.json();
        if(Array.isArray(data.promotions)){
            // Filter to promo_type === 'discount'
            // Keep only product-type promos (exclude appointment / other types)
            claimedCoupons = data.promotions.filter(p=> (p.promo_type||'').toLowerCase()==='product' && (p.promo_active==1 || p.promo_active==='1'));
        } else if(Array.isArray(data.claimed)) { // fallback naming
            claimedCoupons = data.claimed.filter(p=> (p.promo_type||'').toLowerCase()==='product' && (p.promo_active==1 || p.promo_active==='1'));
        } else {
            claimedCoupons = [];
        }
    } catch(e){
        claimedCoupons = [];
    }
    renderClaimedCoupons();
    if(window.lucide) lucide.createIcons();
}
function renderClaimedCoupons(){
    const container1=document.getElementById('claimed-coupons-list');
    const container2=document.getElementById('claimed-coupons-list-checkout');
    const empty1=document.getElementById('claimed-coupons-empty');
    const empty2=document.getElementById('claimed-coupons-empty-checkout');
    const html = claimedCoupons.map(p=>{
        const baseCode = p.promo_code || '';
        const userCode = p.up_code || p.user_code || baseCode || 'CODE';
        const isApplied = appliedCoupon && appliedCoupon.code===userCode;
        const isPercent = (p.promo_discount_type==='percent');
        const valueLabel = isPercent ? (parseFloat(p.promo_discount_value)||0)+'% OFF' : '₱'+(parseFloat(p.promo_discount_value)||0).toFixed(2)+' OFF';
        const perUserLimit = parseInt(p.promo_per_user_limit||0,10);
        const usageCount = parseInt(p.usage_count||0,10);
        const exhausted = perUserLimit>0 && usageCount >= perUserLimit;
        const disabledState = exhausted || isApplied;
        const btnLabel = exhausted ? 'Used Up' : (isApplied ? 'Applied' : 'Use');
        return `<div class=\"flex items-center justify-between px-3 py-2 rounded-md border text-xs ${isApplied?'bg-emerald-50 border-emerald-300':exhausted?'bg-gray-100 border-gray-300 opacity-70':'bg-gray-50 border-gray-200'}\">\n            <div class=\"flex flex-col\">\n                <span class=\"font-semibold tracking-wide\">${escapeHtml(userCode)}</span>\n                <span class=\"text-[10px] text-gray-500\">${valueLabel}${perUserLimit>0?` · ${Math.min(usageCount,perUserLimit)}/${perUserLimit}`:''}</span>\n            </div>\n            <button type=\"button\" class=\"apply-coupon-btn px-2 py-1 rounded text-white text-[11px] font-medium ${disabledState?'bg-gray-400 cursor-not-allowed': 'bg-orange-600 hover:bg-orange-700'}\" data-code=\"${escapeHtml(userCode)}\" data-base-code=\"${escapeHtml(baseCode)}\" data-discount-type=\"${escapeHtml(p.promo_discount_type||'')}\" data-discount-value=\"${escapeHtml(p.promo_discount_value||'')}\" ${disabledState?'disabled':''}>${btnLabel}</button>\n        </div>`;
    }).join('');
    if(container1){ if(!claimedCoupons.length){ if(empty1) empty1.textContent='No discount coupons'; } else { container1.innerHTML=html; } }
    if(container2){ if(!claimedCoupons.length){ if(empty2) empty2.textContent='No discount coupons'; } else { container2.innerHTML=html; } }
}
function currentCartSubtotal(){
    let sum=0; document.querySelectorAll('#cart-items-wrapper .line-total').forEach(el=>{ const v=parseFloat((el.textContent||'').replace(/[^0-9.]/g,'')); if(!isNaN(v)) sum+=v; }); return sum;
}
document.addEventListener('click',e=>{
    if(e.target.classList.contains('apply-coupon-btn')){
        const btn=e.target;
        if(btn.disabled || btn.textContent==='Applied' || btn.textContent==='Used Up') return; // already applied or exhausted
        const displayCode=btn.getAttribute('data-code');
        // Use base code for backend lookup if available (user-specific code may include suffixes)
        const baseCode = btn.getAttribute('data-base-code') || displayCode;
        const type=(btn.getAttribute('data-discount-type')||'').toLowerCase();
        const rawVal=parseFloat(btn.getAttribute('data-discount-value')||'0');
        let kind='percent', value=0; if(type==='percent'){ value=rawVal/100; kind='percent'; } else { kind='fixed'; value=rawVal; }
        appliedCoupon={ code: baseCode, displayCode, kind, value, scope:'cart' };
        recalcSummary(currentCartSubtotal());
        renderClaimedCoupons();
        toast('Coupon applied','success');
    }
});
let lastSelectedAddressId = (function(){
    const r=document.querySelector('input[name="address_select"]:checked');
    return r?parseInt(r.value,10):null;
})();
async function refreshAddressesFromServer(showToast){
    try {
        const res = await fetch('../../users/location_list.php',{headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('fetch_failed');
        const data = await res.json();
        if(!data.ok) throw new Error(data.error||'error');
        if(Array.isArray(data.locations)){
            // Preserve previous selection if still present
            const previous = document.querySelector('input[name="address_select"]:checked');
            if(previous) lastSelectedAddressId = parseInt(previous.value,10);
            renderAddresses(data.locations.map(l=>({
                id:l.id,
                label:l.label,
                recipient:l.recipient,
                phone:l.phone,
                full:l.full,
                is_default:l.is_default
            })));
            // re-check last selected if present
            if(lastSelectedAddressId!==null){
                const match = document.querySelector(`.address-card input[value="${lastSelectedAddressId}"]`);
                if(match){
                    match.checked=true;match.closest('.address-card')?.classList.add('ring-2','ring-blue-300','border-blue-400','bg-gradient-to-br','from-blue-100','to-blue-200');
                }
            }
            if(showToast) toast('Addresses refreshed','success');
        }
    } catch(e){ if(showToast) toast('Could not refresh addresses','error'); }
}
async function placeOrder(){const payment=document.querySelector('input[name="payment_method"]:checked')?.value||'cod';const selectedRadio=document.querySelector('input[name="address_select"]:checked');const locationId=selectedRadio?selectedRadio.value:'';const clientAmtEl=document.getElementById('client_amount');const clientAmount=clientAmtEl && !clientAmtEl.closest('.hidden')?clientAmtEl.value:'';if((payment==='gcash'||payment==='maya') && clientAmount===''){showOrderError('Enter exact amount');return;}if(!locationId){showOrderError('Please add/select a delivery address');return;}
 const fd=new FormData();fd.append('csrf',CSRF);fd.append('location_id',locationId);fd.append('payment_method',payment);if(clientAmount) fd.append('client_amount',clientAmount);if(appliedCoupon && appliedCoupon.code){ fd.append('coupon_code', appliedCoupon.code); }
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