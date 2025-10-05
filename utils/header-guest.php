<?php
// Guest Header (only rendered for non-authenticated users)
// Expects variables from header-users.php: $asset, $cartCount
?>
<header class="sticky top-0 z-50 border-b bg-white">
  <div class="mx-auto px-4 w-full max-w-7xl">
    <div class="flex h-16 items-center justify-between">
      <div class="flex items-center space-x-2">
        <a href="<?= htmlspecialchars($asset('index')) ?>" class="flex items-center space-x-2">
          <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
            <img src="<?= htmlspecialchars($asset('pictures/Pawhabilin logo.png')) ?>" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
          </div>
          <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
            Pawhabilin
          </span>
        </a>
      </div>

      <nav class="hidden md:flex items-center space-x-6">
        <a href="<?= htmlspecialchars($asset('index')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">About</a>

        <a href="<?= htmlspecialchars($asset('become-sitter')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Become a Sitter</a>

        <a href="<?= htmlspecialchars($asset('shops')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Shop</a>

        <div class="relative" id="appointmentsWrapper">
          <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
            Appointments
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
          </button>
          <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
            <div class="py-1">
              <a href="<?= htmlspecialchars($asset('appointments')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Grooming Appointment</a>
              <a href="<?= htmlspecialchars($asset('appointments')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Vet Appointment</a>
            </div>
          </div>
        </div>

  <a href="<?= htmlspecialchars($asset('subscriptions')) ?>" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Subscription</a>
        <a href="#support" class="px-2 py-2 text-base md:text-sm lg:text-base text-gray-600 hover:text-gray-900 transition-colors">Support</a>
      </nav>

      <div class="flex items-center gap-2 md:gap-3">
        <button id="header-cart-button" type="button" class="relative inline-flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50 transition" title="Cart">
          <i data-lucide="shopping-cart" class="w-5 h-5"></i>
          <span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] font-bold flex items-center justify-center">
            <?= (int)$cartCount ?>
          </span>
        </button>

        <a href="<?= htmlspecialchars($asset('login')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs md:text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 hover:bg-orange-50 px-3 py-2 md:px-4 md:py-2">
          Log In
        </a>
        <a href="<?= htmlspecialchars($asset('registration')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs md:text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 px-3 py-2 md:px-4 md:py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
          Sign Up
        </a>

        <!-- Mobile menu toggle: rightmost, after Sign Up -->
        <button id="mobileMenuBtn" class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50" aria-label="Open menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>
    </div>
  </div>
  <!-- Mobile menu (guest) - right slide drawer -->
  <div id="mobileMenu" class="md:hidden fixed inset-0 z-[60] hidden" aria-hidden="true">
    <div id="mobileMenuOverlay" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-300 ease-in-out"></div>
    <nav id="mobileMenuPanel" class="absolute right-0 top-0 h-full w-72 max-w-[85%] bg-white shadow-2xl rounded-l-lg transform translate-x-full opacity-0 transition-transform duration-300 ease-in-out transition-opacity" aria-label="Mobile Navigation">
      <div class="px-4 py-4 space-y-1">
        <a href="<?= htmlspecialchars($asset('index')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">About</a>
        <a href="<?= htmlspecialchars($asset('become-sitter')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Become a Sitter</a>
        <a href="<?= htmlspecialchars($asset('shops')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Shop</a>
        <div class="pt-2 pb-1 text-xs uppercase tracking-wide text-gray-400">Appointments</div>
        <a href="<?= htmlspecialchars($asset('appointments')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Grooming Appointment</a>
        <a href="<?= htmlspecialchars($asset('appointments')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Vet Appointment</a>
        <a href="<?= htmlspecialchars($asset('subscriptions')) ?>" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Subscription</a>
        <a href="#support" class="block px-3 py-3 text-gray-700 hover:bg-gray-50 rounded">Support</a>
        <div class="mt-3 border-t"></div>
        <div class="flex gap-2">
          <a href="<?= htmlspecialchars($asset('login')) ?>" class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Log In</a>
          <a href="<?= htmlspecialchars($asset('registration')) ?>" class="flex-1 text-center px-3 py-2 rounded-md bg-orange-500 text-white hover:bg-orange-600">Sign Up</a>
        </div>
      </div>
    </nav>
  </div>
</header>
