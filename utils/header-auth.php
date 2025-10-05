<?php
// Authenticated Header (only rendered for logged-in users)
// Expects variables from header-users.php: $asset, $cartCount, $currentUserName, $currentUserInitial, $currentUserImg
// Basic guard to avoid direct access
if (!isset($currentUserName) && !isset($currentUserInitial)) { http_response_code(403); return; }
?>
<header class="sticky top-0 z-50 border-b bg-white">
  <div class="mx-auto px-4 w-full max-w-7xl">
    <div class="flex h-16 items-center justify-between">
      <div class="flex items-center space-x-2">
        <a href="<?= htmlspecialchars($asset('views/users/index')) ?>" class="flex items-center space-x-2">
          <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
            <img src="<?= htmlspecialchars($asset('pictures/Pawhabilin logo.png')) ?>" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
          </div>
          <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
            Pawhabilin
          </span>
        </a>
      </div>

      <nav class="hidden md:flex items-center space-x-6">
        <a href="<?= htmlspecialchars($asset('views/users/index')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">About</a>

        <a href="<?= htmlspecialchars($asset('views/users/become-sitter-logged')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Become a Sitter</a>

        <a href="<?= htmlspecialchars($asset('views/users/buy_products')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Shop</a>

        <div class="relative" id="appointmentsWrapper">
          <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
            Appointments
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
          </button>
          <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
            <div class="py-1">
              <a href="<?= htmlspecialchars($asset('views/users/book_appointment')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Grooming Appointment</a>
              <a href="<?= htmlspecialchars($asset('views/users/book_appointment')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Vet Appointment</a>
            </div>
          </div>
        </div>

        <a href="<?= htmlspecialchars($asset('views/users/subscriptions')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Subscription</a>
        <a href="#support" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Support</a>
      </nav>

      <div class="flex items-center gap-3">
        <!-- Mobile menu toggle -->
        <button id="mobileMenuBtn" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50" aria-label="Open menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <button id="header-cart-button" type="button" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50 transition" title="Cart">
          <i data-lucide="shopping-cart" class="w-5 h-5"></i>
          <span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] font-bold flex items-center justify-center">
            <?= (int)$cartCount ?>
          </span>
        </button>

        <div class="relative" id="userMenuWrapper">
          <button id="userMenuButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="userMenu" class="inline-flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-orange-100 text-orange-700 border border-orange-200 overflow-hidden">
              <?php if ($currentUserImg): ?>
                <?php
                  $imgSrc = $currentUserImg;
                  if (strpos($imgSrc, 'http://') !== 0 && strpos($imgSrc, 'https://') !== 0) {
                      // treat as relative asset path
                      $imgSrc = htmlspecialchars($asset($imgSrc));
                  } else {
                      $imgSrc = htmlspecialchars($imgSrc);
                  }
                ?>
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($currentUserName) ?>" class="w-full h-full object-cover" />
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
              <a href="<?= htmlspecialchars($asset('views/users/profile')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Profile</a>
              <a href="<?= htmlspecialchars($asset('views/users/rewards')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">My Rewards</a>
              <div class="my-1 border-t"></div>
              <a href="<?= htmlspecialchars($asset('views/users/logout')) ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">Logout</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Mobile menu (auth) - right slide drawer -->
  <div id="mobileMenu" class="md:hidden fixed inset-0 z-[60] hidden" aria-hidden="true">
    <div id="mobileMenuOverlay" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-200 ease-out"></div>
    <nav id="mobileMenuPanel" class="absolute right-0 top-0 h-full w-72 max-w-[85%] bg-white shadow-2xl rounded-l-lg transform translate-x-full opacity-0 transition-transform duration-300 ease-out transition-opacity" aria-label="Mobile Navigation">
      <div class="px-4 py-4 space-y-1">
        <a href="<?= htmlspecialchars($asset('views/users/index')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">About</a>
        <a href="<?= htmlspecialchars($asset('views/users/become-sitter-logged')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Become a Sitter</a>
        <a href="<?= htmlspecialchars($asset('views/users/buy_products')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Shop</a>
        <div class="pt-2 pb-1 text-xs uppercase tracking-wide text-gray-400">Appointments</div>
        <a href="<?= htmlspecialchars($asset('views/users/book_appointment')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Grooming Appointment</a>
        <a href="<?= htmlspecialchars($asset('views/users/book_appointment')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Vet Appointment</a>
        <a href="<?= htmlspecialchars($asset('views/users/subscriptions')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Subscription</a>
        <a href="#support" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Support</a>
        <div class="mt-3 border-t"></div>
        <a href="<?= htmlspecialchars($asset('views/users/profile')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Profile</a>
        <a href="<?= htmlspecialchars($asset('views/users/rewards')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">My Rewards</a>
        <a href="<?= htmlspecialchars($asset('views/users/logout')) ?>" class="block px-3 py-3 text-red-600 hover:bg-red-50 rounded">Logout</a>
      </div>
    </nav>
  </div>
</header>
