<?php
// Reusable Header Wrapper (shared)
// Decides which header to render based on auth state. Keeps guest and user markup fully separate.

require_once __DIR__ . '/session.php';
session_start_if_needed();

if (!isset($basePrefix)) { $basePrefix = ''; }
$asset = function (string $path) use ($basePrefix): string {
	$path = ltrim($path, '/');
	$prefix = rtrim($basePrefix, '/');
	return ($prefix === '' ? '' : $prefix . '/') . $path;
};

$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);

if (!isset($cartCount)) {
	$cartCount = 0;
	if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
		foreach ($_SESSION['cart'] as $c) { $cartCount += (int)($c['qty'] ?? 0); }
	}
}

if ($currentUser) {
	include __DIR__ . '/header-auth.php';
} else {
	include __DIR__ . '/header-guest.php';
}
?>

<script>
(function(){
    if (window.__pawHeaderInit) return;
    window.__pawHeaderInit = true;
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

    var cartBtn = document.getElementById('header-cart-button');
    if (cartBtn) {
        cartBtn.addEventListener('click', function(){
            try { if (typeof window.toggleCart === 'function') { window.toggleCart(); return; } } catch(e) {}
            window.location.href = '<?= htmlspecialchars(($currentUser ? $asset('views/users/buy_products') : $asset('shops'))) ?>';
        });
    }
})();
</script>

