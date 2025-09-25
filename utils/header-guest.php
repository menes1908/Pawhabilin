<?php
// Guest Header (only rendered for non-authenticated users)
// Expects variables from header-users.php: $asset, $cartCount
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

        <div class="relative" id="petsitterWrapper">
          <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-gray-500 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
            Pet Sitter
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
          </button>
          <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
            <div class="py-1">
              <a href="<?= htmlspecialchars($asset('find-sitters.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Find a Pet Sitter</a>
              <a href="<?= htmlspecialchars($asset('become-sitter.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Become a Sitter</a>
            </div>
          </div>
        </div>

  <a href="<?= htmlspecialchars($asset('shop.php')) ?>" class="text-gray-500 hover:text-gray-900 transition-colors">Shop</a>

        <div class="relative" id="appointmentsWrapper">
          <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-gray-500 hover:text-gray-900 transition-colors inline-flex items-center gap-2">
            Appointments
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
          </button>
          <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown opacity-0 translate-y-2 transition-all duration-200" role="menu" aria-hidden="true">
            <div class="py-1">
              <a href="<?= htmlspecialchars($asset('appointments.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Grooming Appointment</a>
              <a href="<?= htmlspecialchars($asset('appointments.php')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Vet Appointment</a>
            </div>
          </div>
        </div>

  <a href="<?= htmlspecialchars($asset('subscriptions.php')) ?>" class="text-gray-500 hover:text-gray-900 transition-colors">Subscription</a>
        <a href="#support" class="text-gray-500 hover:text-gray-900 transition-colors">Support</a>
      </nav>

      <div class="flex items-center gap-3">
        <button id="header-cart-button" type="button" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50 transition" title="Cart">
          <i data-lucide="shopping-cart" class="w-5 h-5"></i>
          <span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] font-bold flex items-center justify-center">
            <?= (int)$cartCount ?>
          </span>
        </button>

        <a href="<?= htmlspecialchars($asset('login.php')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 hover:bg-orange-50 h-10 px-4 py-2">
          Log In
        </a>
        <a href="<?= htmlspecialchars($asset('registration.php')) ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
          Sign Up
        </a>
      </div>
    </div>
  </div>
</header>
