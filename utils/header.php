<?php
// Reusable Header Partial
// - Logged-in users: shop-style header with cart button and user menu
// - Guests: index-style header with Login / Sign Up buttons
// Usage: before including, optionally set $basePrefix = '../..' from nested pages
//        to resolve links/images correctly. Defaults to ''.

// Ensure session helpers are available
require_once __DIR__ . '/session.php';
session_start_if_needed();

// Resolve paths depending on where this partial is included
if (!isset($basePrefix)) { $basePrefix = ''; }
$asset = function (string $path) use ($basePrefix): string {
	// Normalize and prefix without duplicate slashes
	$path = ltrim($path, '/');
	$prefix = rtrim($basePrefix, '/');
	return ($prefix === '' ? '' : $prefix . '/') . $path;
};

// User context
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);

// Cart count (respect caller-provided $cartCount; else derive from session)
if (!isset($cartCount)) {
	$cartCount = 0;
	if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
		foreach ($_SESSION['cart'] as $c) { $cartCount += (int)($c['qty'] ?? 0); }
	}
}

?>
<header class="sticky top-0 z-50 border-b bg-white/80 backdrop-blur-sm">
	<div class="mx-auto px-4 w-full max-w-7xl">
		<div class="flex h-16 items-center justify-between">
			<div class="flex items-center space-x-2">
				<a href="<?= htmlspecialchars($asset('index.php')) ?>" class="flex items-center space-x-2">
					<div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
						<img src="<?= htmlspecialchars($asset('pictures/Pawhabilin logo.png')) ?>" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
					</div>
					<span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
						Pawhabilin
					</span>
				</a>
			</div>

			<nav class="hidden md:flex items-center space-x-8">
				<a href="<?= htmlspecialchars($asset('index.php')) ?>" class="text-gray-500 hover:text-gray-900 transition-colors">About</a>

				<!-- Pet Sitter Dropdown -->
				<div class="relative" id="petsitterWrapper">
					<button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-gray-500 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
						Pet Sitter
						<i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
					</button>

					<div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
						<div class="py-1">
							<a href="<?= htmlspecialchars($asset('find-sitters.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Find Sitters</a>
							<a href="#how-it-works" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">How it Works</a>
							<a href="#safety" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Safety</a>
						</div>
					</div>
				</div>

				<a href="<?= htmlspecialchars($asset('shop.php')) ?>" class="text-gray-500 hover:text-gray-900 transition-colors">Shop</a>

				<!-- Appointments Dropdown -->
				<div class="relative" id="appointmentsWrapper">
					<button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-gray-500 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
						Appointments
						<i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
					</button>

					<div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
						<div class="py-1">
							<a href="#book" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Book</a>
							<a href="#manage" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Manage</a>
						</div>
					</div>
				</div>

				<a href="<?= htmlspecialchars($asset('subscriptions.php')) ?>" class="text-gray-500 hover:text-gray-900 transition-colors">Subscription</a>
				<a href="#support" class="text-gray-500 hover:text-gray-900 transition-colors">Support</a>
			</nav>

			<div class="flex items-center gap-3">
				<?php if ($currentUser): ?>
					<!-- Cart Button (icon-only) -->
					<button id="header-cart-button" type="button" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50 transition" title="Cart">
						<i data-lucide="shopping-cart" class="w-5 h-5"></i>
						<span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] font-bold flex items-center justify-center">
							<?= (int)$cartCount ?>
						</span>
					</button>

					<!-- User Menu -->
					<div class="relative" id="userMenuWrapper">
						<button id="userMenuButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="userMenu" class="inline-flex items-center gap-2">
							<span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-orange-100 text-orange-700 border border-orange-200 overflow-hidden">
								<?php if ($currentUserImg): ?>
									<img src="<?= htmlspecialchars($currentUserImg) ?>" alt="<?= htmlspecialchars($currentUserName) ?>" class="w-full h-full object-cover" />
								<?php else: ?>
									<span class="text-sm font-semibold"><?= htmlspecialchars($currentUserInitial) ?></span>
								<?php endif; ?>
							</span>
							<span class="hidden sm:block max-w-[140px] truncate text-sm text-gray-700">
								<?= htmlspecialchars($currentUserName) ?>
							</span>
							<i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
						</button>

						<div id="userMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
							<div class="py-1">
								<a href="<?= htmlspecialchars($asset('views/users/profile.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Profile</a>
								<a href="<?= htmlspecialchars($asset('views/users/index.php')) ?>#orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">My Orders</a>
								<div class="my-1 border-t"></div>
								<a href="<?= htmlspecialchars($asset('views/users/logout.php')) ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">Logout</a>
							</div>
						</div>
					</div>
				<?php else: ?>
					<!-- Guest actions (from index.php header) -->
					<a href="<?= htmlspecialchars($asset('login.php')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 hover:bg-orange-50 h-10 px-4 py-2">
						Log In
					</a>
					<a href="<?= htmlspecialchars($asset('registration.php')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
						Sign Up
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>

<script>
// Initialize header behaviors once per page
(function(){
	if (window.__pawHeaderInit) return; // prevent duplicate init
	window.__pawHeaderInit = true;

	// Lucide icons refresh (if available)
	try { if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons(); } catch(e) {}
	// Refresh once DOM is fully parsed to catch icons outside header
	try {
		document.addEventListener('DOMContentLoaded', function(){
			if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
		}, { once: true });
	} catch(e) {}

	// Dropdown helper
	function initDropdown(wrapperId, buttonId, menuId){
		var wrapper = document.getElementById(wrapperId);
		var btn = document.getElementById(buttonId);
		var menu = document.getElementById(menuId);
		if(!wrapper || !btn || !menu) return;
		var chevron = btn.querySelector('i[data-lucide="chevron-down"]');
		var persist = false; var hoverTimeout = null;
		function setOpen(open){
			if(open){
				menu.classList.remove('opacity-0');
				menu.classList.remove('translate-y-2');
				menu.setAttribute('aria-hidden','false');
				btn.setAttribute('aria-expanded','true');
				if(chevron) chevron.style.transform='rotate(180deg)';
			} else {
				menu.classList.add('opacity-0');
				menu.classList.add('translate-y-2');
				menu.setAttribute('aria-hidden','true');
				btn.setAttribute('aria-expanded','false');
				if(chevron) chevron.style.transform='';
			}
		}
		wrapper.addEventListener('mouseenter', function(){ if(hoverTimeout) clearTimeout(hoverTimeout); setOpen(true); });
		wrapper.addEventListener('mouseleave', function(){ if(persist) return; hoverTimeout=setTimeout(function(){ setOpen(false); }, 150); });
		btn.addEventListener('click', function(e){ e.stopPropagation(); persist = !persist; setOpen(persist); });
		document.addEventListener('click', function(e){ if(!wrapper.contains(e.target)){ persist=false; setOpen(false); }});
		document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ persist=false; setOpen(false); }});
		setOpen(false);
	}
	initDropdown('petsitterWrapper','petsitterButton','petsitterMenu');
	initDropdown('appointmentsWrapper','appointmentsButton','appointmentsMenu');

	// Cart button behavior: open drawer if available, else navigate to Shop
	var cartBtn = document.getElementById('header-cart-button');
	if (cartBtn) {
		cartBtn.addEventListener('click', function(){
			try {
				if (typeof window.toggleCart === 'function') { window.toggleCart(); return; }
			} catch(e) {}
			window.location.href = '<?= htmlspecialchars($asset('shop.php')) ?>';
		});
	}
})();
</script>

