<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - pawhabilin</title>
    <!-- Tailwind CSS CDN for testing -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom Tailwind config for font if needed -->
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              'brand': ['\'La Belle Aurore\', cursive'],
            },
            colors: {
              background: '#fff',
              'muted-foreground': '#6b7280',
              foreground: '#111827',
              accent: '#f3f4f6',
              secondary: '#f59e42',
              'secondary-foreground': '#fff',
              destructive: '#ef4444',
              'destructive-foreground': '#fff',
            },
          },
        },
      }
    </script>
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link href="../globals.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<?php
require_once __DIR__ . '/../../utils/session.php';
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);
?>
</head>
<body class="min-h-screen bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-background/80 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
                        <img src="../../pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                    </div>
                    <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </span>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-muted-foreground hover:text-foreground transition-colors">About</a>
                    <!-- Pet Sitter Dropdown -->
                    <div class="relative" id="petsitterWrapper">
                        
                        <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Pet Sitter
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 opacity-0 translate-y-2" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="animal_sitting.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Find a Pet Sitter</a>
                                <a href="become_sitter.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Become a Sitter</a>
                            </div>
                        </div>
                    </div>

                    <a href="buy_products.php" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    
                    
                    <div class="relative" id="appointmentsWrapper">
                        <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Appointments
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 opacity-0 translate-y-2" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="book_appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                                <a href="book_appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
                            </div>
                        </div>
                    </div>

                    <a href="subscriptions.php" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>

                    
                    <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
                </nav>

                <div class="flex items-center gap-3">
                    <?php if ($currentUser): ?>
                        <div class="text-right hidden sm:block">
                            <div class="text-xs text-gray-500">Profile</div>
                            <div class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($currentUserName); ?></div>
                        </div>
                        <div class="relative" id="userMenu">
                            <button id="userMenuBtn" type="button" aria-haspopup="true" aria-expanded="false" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-br from-orange-400 to-amber-500 text-white font-semibold text-sm flex items-center justify-center">
                                    <?php if ($currentUserImg): ?>
                                        <img src="<?php echo htmlspecialchars($currentUserImg); ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($currentUserInitial); ?>
                                    <?php endif; ?>
                                </div>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                            </button>
                            <div id="userMenuMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 opacity-0 translate-y-2 z-50" aria-hidden="true">
                                <div class="py-1">
                                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="profile.php#rewards" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Rewards</a>
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="../../login.php" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            Log In
                        </a>
                        <a href="../../registration.php" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
                            Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Everything Your Pet Needs
                    </h1>
                    
                    <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto">
                        Premium pet products delivered to your door. From nutritious food to fun toys, we have everything to keep your furry friends happy and healthy.
                    </p>
                </div>

                <!-- Search Bar -->
                <div class="max-w-2xl mx-auto">
                    <div class="rounded-lg border bg-white/80 backdrop-blur-sm shadow-lg p-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1 relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4"></i>
                                <input 
                                    type="text" 
                                    placeholder="Search for products..." 
                                    id="product-search"
                                    class="flex h-12 w-full rounded-md border border-gray-200 bg-transparent px-3 py-1 pl-10 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-orange-500 focus-visible:border-orange-500 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                />
                            </div>
                            <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-12 px-8">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters and Products -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <!-- Category Filters -->
            <div class="mb-8">
                <div class="flex flex-wrap gap-2 mb-6" id="category-filters">
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-8 px-3" data-category="All">All</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Dog Food">Dog Food</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Cat Food">Cat Food</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Toys">Toys</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Accessories">Accessories</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Grooming">Grooming</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Treats">Treats</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Health & Wellness">Health & Wellness</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="Training">Training</button>
                </div>

                <!-- Sort and View Controls -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div class="text-sm text-muted-foreground" id="product-count">
                        Showing 12 products
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Sort Dropdown -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">Sort by:</span>
                            <select id="sort-select" class="flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1 w-40">
                                <option value="featured">Featured</option>
                                <option value="name">Name A-Z</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="rating">Rating</option>
                            </select>
                        </div>

                        <!-- View Mode Toggle -->
                        <div class="flex border rounded-lg overflow-hidden">
                            <button id="grid-view-btn" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-orange-500 hover:bg-orange-600 text-white h-8 px-3">
                                <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                            </button>
                            <button id="list-view-btn" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground bg-transparent h-8 px-3">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Products will be inserted here by JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden text-center py-12">
                <i data-lucide="package" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No products found</h3>
                <p class="text-gray-500">Try adjusting your filters or search terms.</p>
            </div>
        </div>
    </section>

    <!-- Newsletter Signup -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center space-y-6">
                <h2 class="text-3xl font-bold">Stay Updated</h2>
                <p class="text-muted-foreground">
                    Get the latest deals and new product announcements delivered to your inbox.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                    <input type="email" placeholder="Enter your email" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm flex-1" />
                    <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-9 px-4 py-2">
                        Subscribe
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-orange-600 to-amber-700">
        <div class="container mx-auto px-4 text-center">
            <div class="max-w-3xl mx-auto space-y-8 text-white">
                <h2 class="text-3xl lg:text-4xl font-bold">
                    Need Help Choosing?
                </h2>
                <p class="text-lg opacity-90">
                    Our pet care experts are here to help you find the perfect products for your furry friends. 
                    Get personalized recommendations today!
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-10 px-4 py-2 text-base font-semibold">
                        Chat with Expert
                    </button>
                    <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-white text-white hover:bg-white hover:text-orange-600 bg-transparent h-10 px-4 py-2 text-base">
                        Find a Pet Sitter
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center text-sm opacity-80 pt-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        (02) 8123-4567
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        shop@pawhabilin.ph
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080" alt="pawhabilin Logo" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-xl font-semibold brand-font">pawhabilin</span>
                    </div>
                    <p class="text-gray-400">
                        The Philippines' most trusted pet sitting platform and pet product store.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Shop</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Dog Products</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cat Products</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Accessories</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Health & Wellness</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Services</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="animal_sitting.php" class="hover:text-white transition-colors">Find a Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Become a Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Training</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Veterinary Care</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Shipping Info</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Returns</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2025 pawhabilin Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Product data
        const allProducts = [
            {
                id: 1,
                name: "Premium Dog Kibble - Adult Formula",
                category: "Dog Food",
                price: 1299,
                originalPrice: 1599,
                rating: 4.8,
                reviews: 234,
                image: "https://images.unsplash.com/photo-1572950947301-fb417712da10?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcmVtaXVtJTIwZG9nJTIwZm9vZCUyMGtpYmJsZXxlbnwxfHx8fDE3NTY1NDM3MTV8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Best Seller",
                inStock: true,
                description: "High-quality protein formula for adult dogs with chicken and rice.",
                brand: "PetNutrition Pro",
                weight: "15kg"
            },
            {
                id: 2,
                name: "Nutritious Cat Food - Salmon & Tuna",
                category: "Cat Food",
                price: 899,
                originalPrice: null,
                rating: 4.9,
                reviews: 156,
                image: "https://images.unsplash.com/photo-1734654901149-02a9a5f7993b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBmb29kJTIwYm93bHxlbnwxfHx8fDE3NTY1MDk4MzR8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "New",
                inStock: true,
                description: "Premium wet food with real salmon and tuna, rich in omega-3.",
                brand: "FelineFresh",
                weight: "400g x 12 cans"
            },
            {
                id: 3,
                name: "Interactive Puzzle Dog Toy",
                category: "Toys",
                price: 459,
                originalPrice: 599,
                rating: 4.7,
                reviews: 89,
                image: "https://images.unsplash.com/photo-1659700097688-f26bf79735af?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjB0b3klMjByb3BlJTIwYmFsbHxlbnwxfHx8fDE3NTY0Njk1Mjd8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Sale",
                inStock: true,
                description: "Mental stimulation toy that challenges your dog while dispensing treats.",
                brand: "SmartPaws",
                weight: "500g"
            },
            {
                id: 4,
                name: "Durable Pet Collar & Leash Set",
                category: "Accessories",
                price: 699,
                originalPrice: null,
                rating: 4.6,
                reviews: 67,
                image: "https://images.unsplash.com/photo-1577447278822-37801be21738?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBjb2xsYXIlMjBsZWFzaHxlbnwxfHx8fDE3NTY0NDcwNjZ8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: null,
                inStock: true,
                description: "Adjustable nylon collar with matching 6ft leash, perfect for daily walks.",
                brand: "WalkSafe",
                weight: "200g"
            },
            {
                id: 5,
                name: "Healthy Training Treats",
                category: "Treats",
                price: 329,
                originalPrice: 399,
                rating: 4.8,
                reviews: 143,
                image: "https://images.unsplash.com/photo-1714846624589-bae6531e86e6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjB0cmVhdHMlMjBzbmFja3N8ZW58MXx8fHwxNzU2NDQ3MDY2fDA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Popular",
                inStock: false,
                description: "Low-calorie training treats made with natural ingredients and real meat.",
                brand: "HealthyPaws",
                weight: "200g"
            },
            {
                id: 6,
                name: "Professional Grooming Brush Set",
                category: "Grooming",
                price: 799,
                originalPrice: null,
                rating: 4.9,
                reviews: 98,
                image: "https://images.unsplash.com/photo-1625279138876-8910c2af9a30?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBncm9vbWluZyUyMGJydXNofGVufDF8fHx8MTc1NjQzODM3N3ww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Premium",
                inStock: true,
                description: "Complete grooming set with slicker brush, pin brush, and nail clippers.",
                brand: "GroomPro",
                weight: "300g"
            },
            {
                id: 7,
                name: "Puppy Starter Kit",
                category: "Accessories",
                price: 1899,
                originalPrice: 2399,
                rating: 4.7,
                reviews: 76,
                image: "https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Bundle",
                inStock: true,
                description: "Everything you need for your new puppy: bed, toys, bowls, and more.",
                brand: "PuppyLife",
                weight: "2kg"
            },
            {
                id: 8,
                name: "Cat Scratching Post Tower",
                category: "Toys",
                price: 2299,
                originalPrice: null,
                rating: 4.8,
                reviews: 123,
                image: "https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBzY3JhdGNoaW5nJTIwcG9zdHxlbnwxfHx8fDE3NTY1NDM3MTV8MA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Premium",
                inStock: true,
                description: "Multi-level scratching post with sisal rope and cozy perches.",
                brand: "CatHaven",
                weight: "8kg"
            },
            {
                id: 9,
                name: "Joint Health Supplements",
                category: "Health & Wellness",
                price: 1199,
                originalPrice: null,
                rating: 4.6,
                reviews: 89,
                image: "https://images.unsplash.com/photo-1471193945509-9ad0617afabf?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBzdXBwbGVtZW50cyUyMHBpbGxzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Vet Recommended",
                inStock: true,
                description: "Glucosamine and chondroitin supplements for joint health and mobility.",
                brand: "VetHealth",
                weight: "90 tablets"
            },
            {
                id: 10,
                name: "Training Clicker Set",
                category: "Training",
                price: 199,
                originalPrice: 249,
                rating: 4.5,
                reviews: 234,
                image: "https://images.unsplash.com/photo-1551717743-49959800b1f6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjB0cmFpbmluZyUyMGNsaWNrZXJ8ZW58MXx8fHwxNzU2NTQzNzE1fDA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Beginner Friendly",
                inStock: true,
                description: "Professional training clickers with wrist strap and training guide.",
                brand: "TrainRight",
                weight: "50g"
            },
            {
                id: 11,
                name: "Premium Cat Litter",
                category: "Accessories",
                price: 599,
                originalPrice: null,
                rating: 4.7,
                reviews: 167,
                image: "https://images.unsplash.com/photo-1545529468-42764ef8c85f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBsaXR0ZXIlMjBib3h8ZW58MXx8fHwxNzU2NTQzNzE1fDA&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Odor Control",
                inStock: true,
                description: "Clumping clay litter with advanced odor control technology.",
                brand: "CleanPaws",
                weight: "10kg"
            },
            {
                id: 12,
                name: "Dental Chew Sticks",
                category: "Treats",
                price: 449,
                originalPrice: 529,
                rating: 4.8,
                reviews: 198,
                image: "https://images.unsplash.com/photo-1588943211346-0908a1fb0b01?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjBkZW50YWwlMjBjaGV3fGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080",
                badge: "Dental Health",
                inStock: true,
                description: "Natural dental chews that help reduce tartar and freshen breath.",
                brand: "DentalCare",
                weight: "300g"
            }
        ];

        // State variables
        let selectedCategory = "All";
        let sortBy = "featured";
        let viewMode = "grid";
        let cartItems = 0;
        let searchQuery = "";

        // Helper functions
        function getBadgeClass(badge) {
            const badgeClasses = {
                "Sale": "bg-red-500 hover:bg-red-600",
                "New": "bg-green-500 hover:bg-green-600", 
                "Best Seller": "bg-blue-500 hover:bg-blue-600",
                "Popular": "bg-purple-500 hover:bg-purple-600",
                "Bundle": "bg-pink-500 hover:bg-pink-600",
                "Vet Recommended": "bg-teal-500 hover:bg-teal-600",
                "Dental Health": "bg-cyan-500 hover:bg-cyan-600",
                "Beginner Friendly": "bg-indigo-500 hover:bg-indigo-600",
                "Odor Control": "bg-violet-500 hover:bg-violet-600",
                "Premium": "bg-yellow-500 hover:bg-yellow-600"
            };
            return badgeClasses[badge] || "bg-yellow-500 hover:bg-yellow-600";
        }

        function formatPrice(price) {
            return `₱${price.toLocaleString()}`;
        }

        function createProductCard(product, isListView = false) {
            const cardClass = isListView ? 'flex' : '';
            const imageContainerClass = isListView ? 'w-48 flex-shrink-0' : '';
            const imageClass = isListView ? 'h-48' : 'aspect-square';
            const contentClass = isListView ? 'flex-1' : '';
            const titleClass = isListView ? 'text-lg' : 'text-sm';
            
            return `
                <div class="overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1 bg-white border border-gray-200 rounded-lg ${cardClass}">
                    <div class="relative ${imageContainerClass}">
                        <div class="${imageClass} overflow-hidden">
                            <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwdXBweSUyMGtpdCUyMGFjY2Vzc29yaWVzfGVufDF8fHx8MTc1NjU0MzcxNXww&ixlib=rb-4.1.0&q=80&w=1080'" />
                        </div>
                        
                        ${product.badge ? `
                            <div class="absolute top-3 left-3">
                                <div class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ${getBadgeClass(product.badge)} text-white">
                                    ${product.badge}
                                </div>
                            </div>
                        ` : ''}

                        ${!product.inStock ? `
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <div class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-destructive text-destructive-foreground">Out of Stock</div>
                            </div>
                        ` : ''}

                        <button class="absolute top-3 right-3 w-8 h-8 p-0 bg-white/80 hover:bg-white inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground">
                            <i data-lucide="heart" class="w-4 h-4"></i>
                        </button>
                    </div>
                    
                    <div class="p-4 ${contentClass}">
                        <div class="space-y-3">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">${product.brand}</div>
                                <h3 class="font-semibold ${titleClass} mb-2 line-clamp-2">${product.name}</h3>
                                
                                ${isListView ? `<p class="text-sm text-gray-600 mb-3 line-clamp-2">${product.description}</p>` : ''}
                                
                                <div class="flex items-center gap-1 mb-2">
                                    <i data-lucide="star" class="w-3 h-3 fill-yellow-400 text-yellow-400"></i>
                                    <span class="text-xs font-medium">${product.rating}</span>
                                    <span class="text-xs text-muted-foreground">(${product.reviews})</span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-orange-600">${formatPrice(product.price)}</span>
                                        ${product.originalPrice ? `<span class="text-xs text-muted-foreground line-through">${formatPrice(product.originalPrice)}</span>` : ''}
                                    </div>
                                    ${isListView ? `<div class="text-xs text-gray-500">${product.weight}</div>` : ''}
                                </div>
                            </div>

                            <button 
                                class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 h-8 px-3 ${!product.inStock ? 'opacity-50 cursor-not-allowed' : ''}"
                                ${!product.inStock ? 'disabled' : ''}
                                onclick="addToCart(${product.id})"
                            >
                                <i data-lucide="shopping-cart" class="w-3 h-3 mr-1"></i>
                                ${product.inStock ? "Add to Cart" : "Out of Stock"}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function filterProducts() {
            return allProducts.filter(product => {
                const matchesCategory = selectedCategory === "All" || product.category === selectedCategory;
                const matchesSearch = searchQuery === "" || 
                    product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                    product.brand.toLowerCase().includes(searchQuery.toLowerCase()) ||
                    product.category.toLowerCase().includes(searchQuery.toLowerCase());
                
                return matchesCategory && matchesSearch;
            });
        }

        function sortProducts(products) {
            const sorted = [...products];
            
            switch (sortBy) {
                case "price-low":
                    return sorted.sort((a, b) => a.price - b.price);
                case "price-high":
                    return sorted.sort((a, b) => b.price - a.price);
                case "rating":
                    return sorted.sort((a, b) => b.rating - a.rating);
                case "name":
                    return sorted.sort((a, b) => a.name.localeCompare(b.name));
                default:
                    return sorted;
            }
        }

        function renderProducts() {
            const filteredProducts = filterProducts();
            const sortedProducts = sortProducts(filteredProducts);
            
            const productsGrid = document.getElementById('products-grid');
            const emptyState = document.getElementById('empty-state');
            const productCount = document.getElementById('product-count');
            
            // Update product count
            let countText = `Showing ${sortedProducts.length} products`;
            if (selectedCategory !== "All") {
                countText += ` in ${selectedCategory}`;
            }
            productCount.textContent = countText;
            
            if (sortedProducts.length === 0) {
                productsGrid.innerHTML = '';
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
                
                // Update grid classes based on view mode
                productsGrid.className = viewMode === "grid" 
                    ? "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                    : "flex flex-col gap-4";
                
                productsGrid.innerHTML = sortedProducts.map(product => 
                    createProductCard(product, viewMode === "list")
                ).join('');
            }
            
            // Re-initialize Lucide icons
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function updateCategoryButtons() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(btn => {
                const category = btn.getAttribute('data-category');
                if (category === selectedCategory) {
                    btn.className = 'category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-8 px-3';
                } else {
                    btn.className = 'category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3';
                }
            });
        }

        function updateViewButtons() {
            const gridBtn = document.getElementById('grid-view-btn');
            const listBtn = document.getElementById('list-view-btn');
            
            if (viewMode === "grid") {
                gridBtn.className = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-orange-500 hover:bg-orange-600 text-white h-8 px-3';
                listBtn.className = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground bg-transparent h-8 px-3';
            } else {
                listBtn.className = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-orange-500 hover:bg-orange-600 text-white h-8 px-3';
                gridBtn.className = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground bg-transparent h-8 px-3';
            }
        }

        function addToCart(productId) {
            cartItems++;
            const cartBadge = document.getElementById('cart-badge');
            cartBadge.textContent = cartItems;
            cartBadge.classList.remove('hidden');
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Category filter buttons
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    selectedCategory = this.getAttribute('data-category');
                    updateCategoryButtons();
                    renderProducts();
                });
            });

            // Sort dropdown
            document.getElementById('sort-select').addEventListener('change', function() {
                sortBy = this.value;
                renderProducts();
            });

            // View mode buttons
            document.getElementById('grid-view-btn').addEventListener('click', function() {
                viewMode = "grid";
                updateViewButtons();
                renderProducts();
            });

            document.getElementById('list-view-btn').addEventListener('click', function() {
                viewMode = "list";
                updateViewButtons();
                renderProducts();
            });

            // Search functionality
            document.getElementById('product-search').addEventListener('input', function() {
                searchQuery = this.value;
                renderProducts();
            });

            // Initialize Lucide icons
            if (window.lucide) {
                lucide.createIcons();
            }

            // Initial render
            renderProducts();
        });
    </script>
    <script>
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
            initDropdown({ wrapperId: 'userMenu', buttonId: 'userMenuBtn', menuId: 'userMenuMenu' });
        })();
    </script>
</body>
</html>