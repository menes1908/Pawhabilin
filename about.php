<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About - pawhabilin</title>
  <link rel="stylesheet" href="styles/globals.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gray-50">
  <!-- Simple header consistent with index.php -->
  <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
    <div class="container mx-auto px-4">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
            <img src="./pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
          </div>
          <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
            Pawhabilin
          </span>
        </div>

        <nav class="hidden md:flex items-center space-x-8">
          <div class="relative" id="petsitterWrapper">
            <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
              Pet Sitter
              <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
            </button>
            <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
              <div class="py-1">
                <a href="find-sitters" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Find a Pet Sitter</a>
                <a href="views/users/become_sitter.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Become a Sitter</a>
              </div>
            </div>
          </div>

          <a href="shop" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>

          <div class="relative" id="appointmentsWrapper">
            <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
              Appointments
              <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
            </button>
            <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200" role="menu" aria-hidden="true">
              <div class="py-1">
                <a href="views/users/book_grooming.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                <a href="views/users/book_appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
              </div>
            </div>
          </div>

          <a href="views/users/subscriptions.php" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>
          <a href="index.php" class="text-muted-foreground hover:text-foreground transition-colors">About</a>
          <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
        </nav>

        <div class="flex items-center gap-3">
          <a href="login" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            Log In
          </a>
          <a href="registration" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
            Sign Up
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="container mx-auto px-4 py-12">
    <div class="max-w-3xl mx-auto space-y-6">
      <h1 class="text-3xl lg:text-4xl font-bold">About pawhabilin</h1>
      <p class="text-gray-600">We connect pet parents with trusted, verified sitters across the Philippines. Our mission is to make quality pet care accessible, reliable, and stress-free.</p>
      <div class="grid sm:grid-cols-3 gap-6 pt-4">
        <div class="rounded-lg border bg-white p-6 shadow-sm">
          <div class="text-2xl font-bold text-orange-600 mb-2">8,000+</div>
          <div class="text-sm text-gray-600">Trusted Pet Sitters</div>
        </div>
        <div class="rounded-lg border bg-white p-6 shadow-sm">
          <div class="text-2xl font-bold text-amber-600 mb-2">25,000+</div>
          <div class="text-sm text-gray-600">Happy Pet Parents</div>
        </div>
        <div class="rounded-lg border bg-white p-6 shadow-sm">
          <div class="text-2xl font-bold text-yellow-600 mb-2">24/7</div>
          <div class="text-sm text-gray-600">Support</div>
        </div>
      </div>
    </div>
  </main>

  <script>
    if (window.lucide) { lucide.createIcons(); }
    (function() {
      function initDropdown({ wrapperId, buttonId, menuId }) {
        const wrapper = document.getElementById(wrapperId);
        const btn = document.getElementById(buttonId);
        const menu = document.getElementById(menuId);
        const chevron = btn && btn.querySelector('i[data-lucide="chevron-down"]');
        let persist = false;
        let hoverTimeout = null;
        if (!wrapper || !btn || !menu) return;
        function setOpen(open) {
          if (open) {
            menu.classList.add('open');
            menu.classList.remove('opacity-0');
            menu.classList.remove('translate-y-2');
            menu.setAttribute('aria-hidden', 'false');
            btn.setAttribute('aria-expanded', 'true');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
          } else {
            menu.classList.remove('open');
            menu.classList.add('opacity-0');
            menu.classList.add('translate-y-2');
            menu.setAttribute('aria-hidden', 'true');
            btn.setAttribute('aria-expanded', 'false');
            if (chevron) chevron.style.transform = '';
          }
        }
        wrapper.addEventListener('mouseenter', function() {
          if (hoverTimeout) clearTimeout(hoverTimeout);
          setOpen(true);
        });
        wrapper.addEventListener('mouseleave', function() {
          if (persist) return;
          hoverTimeout = setTimeout(function() { setOpen(false); }, 150);
        });
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          persist = !persist;
          setOpen(persist);
        });
        document.addEventListener('click', function(e) {
          if (!wrapper.contains(e.target)) {
            persist = false;
            setOpen(false);
          }
        });
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            persist = false;
            setOpen(false);
          }
        });
        setOpen(false);
      }
      initDropdown({ wrapperId: 'appointmentsWrapper', buttonId: 'appointmentsButton', menuId: 'appointmentsMenu' });
      initDropdown({ wrapperId: 'petsitterWrapper', buttonId: 'petsitterButton', menuId: 'petsitterMenu' });
    })();
  </script>
</body>
</html>
