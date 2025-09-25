<?php
// Reusable Header Wrapper
// - Decides which header to render based on session state
// - Provides shared helpers and a single JS initializer to avoid duplication

require_once __DIR__ . '/session.php';
session_start_if_needed();

// Resolve paths depending on where this partial is included
if (!isset($basePrefix)) { $basePrefix = ''; }
$asset = function (string $path) use ($basePrefix): string {
	$path = ltrim($path, '/');
	$prefix = rtrim($basePrefix, '/');
	return ($prefix === '' ? '' : $prefix . '/') . $path;
};

// User context
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);

// Cart count
if (!isset($cartCount)) {
	$cartCount = 0;
	if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
		foreach ($_SESSION['cart'] as $c) { $cartCount += (int)($c['qty'] ?? 0); }
	}
}

// Render specific header based on auth state (prevents guest from ever seeing user menu markup)
if ($currentUser) {
	include __DIR__ . '/header-auth.php';
} else {
	include __DIR__ . '/header-guest.php';
}
?>

<script>
// Initialize header behaviors once per page (shared for both guest/auth variants)
(function(){
    if (window.__pawHeaderInit) return; // prevent duplicate init
    window.__pawHeaderInit = true;

    // Lucide icons refresh
    try { if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons(); } catch(e) {}
    try { document.addEventListener('DOMContentLoaded', function(){ if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons(); }, { once: true }); } catch(e) {}

    function initDropdown(opts){
        var wrapper = document.getElementById(opts.wrapperId);
        var btn = document.getElementById(opts.buttonId);
        var menu = document.getElementById(opts.menuId);
        if(!wrapper || !btn || !menu) return;
        var chevron = btn.querySelector('i[data-lucide="chevron-down"]');
        var persist = false; var hoverTimeout = null;
        function setOpen(open){
            if(open){
                menu.classList.add('open');
                menu.classList.remove('opacity-0');
                menu.classList.remove('translate-y-2');
                menu.setAttribute('aria-hidden','false');
                btn.setAttribute('aria-expanded','true');
                if(chevron) chevron.style.transform='rotate(180deg)';
            } else {
                menu.classList.remove('open');
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
    initDropdown({ wrapperId: 'appointmentsWrapper', buttonId: 'appointmentsButton', menuId: 'appointmentsMenu' });
    initDropdown({ wrapperId: 'petsitterWrapper', buttonId: 'petsitterButton', menuId: 'petsitterMenu' });
    initDropdown({ wrapperId: 'userMenuWrapper', buttonId: 'userMenuButton', menuId: 'userMenu' });

    // Cart button behavior: open drawer if available, else navigate appropriately
    var cartBtn = document.getElementById('header-cart-button');
    if (cartBtn) {
        cartBtn.addEventListener('click', function(){
            try { if (typeof window.toggleCart === 'function') { window.toggleCart(); return; } } catch(e) {}
            window.location.href = '<?= htmlspecialchars(($currentUser ? $asset('views/users/buy_products.php') : $asset('shop.php'))) ?>';
        });
    }
})();
</script>
