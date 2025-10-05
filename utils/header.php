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

    // Mobile menu toggle (guest/auth shared)
    var mobileBtn = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileOverlay = document.getElementById('mobileMenuOverlay');
    var mobilePanel = document.getElementById('mobileMenuPanel');
    function openMobileMenu(){
        if(!mobileMenu || !mobilePanel) return;
        mobileMenu.classList.remove('hidden');
        requestAnimationFrame(function(){
            if(mobileOverlay){ mobileOverlay.classList.remove('opacity-0'); mobileOverlay.classList.add('opacity-100'); }
            mobilePanel.classList.remove('translate-x-full');
            mobilePanel.classList.add('translate-x-0');
            try { mobilePanel.classList.remove('opacity-0'); } catch(e){}
            mobileMenu.setAttribute('aria-hidden','false');
            try { document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden'); } catch(e){}
        });
    }
    function closeMobileMenu(){
        if(!mobileMenu || !mobilePanel) return;
        if(mobileOverlay){ mobileOverlay.classList.remove('opacity-100'); mobileOverlay.classList.add('opacity-0'); }
        mobilePanel.classList.add('translate-x-full');
        mobilePanel.classList.remove('translate-x-0');
        try { mobilePanel.classList.add('opacity-0'); } catch(e){}
        mobileMenu.setAttribute('aria-hidden','true');
        setTimeout(function(){
            if(mobileMenu.getAttribute('aria-hidden')==='true') mobileMenu.classList.add('hidden');
            try { document.documentElement.classList.remove('overflow-hidden'); document.body.classList.remove('overflow-hidden'); } catch(e){}
        }, 250);
    }
    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function(e){
            e.stopPropagation();
            var isHidden = mobileMenu.classList.contains('hidden') || mobileMenu.getAttribute('aria-hidden')==='true';
            if (isHidden) openMobileMenu(); else closeMobileMenu();
        });
        if(mobileOverlay){ mobileOverlay.addEventListener('click', closeMobileMenu); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMobileMenu(); });
        // Close when clicking any link in the panel
        if(mobilePanel){ mobilePanel.addEventListener('click', function(e){ var a=e.target.closest('a'); if(a) closeMobileMenu(); }); }
    }
})();
</script>

