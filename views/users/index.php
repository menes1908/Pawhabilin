<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - pawhabilin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <link href="../styles/globals.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .quick-action-card:hover::before {
            left: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stats-counter {
            animation: countUp 2s ease-out;
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .floating-paw {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-paw:nth-child(2) {
            animation-delay: -2s;
        }
        
        .floating-paw:nth-child(3) {
            animation-delay: -4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        .notification-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="min-h-screen bg-background">
    <!-- Header -->
   <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
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
                        <a id="petsitterButton" href="/Pawhabilin/views/users/findsitters.php" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Find a Sitter
                         </a>
                    </div>

                    <a href="/Pawhabilin/views/users/buy_products.php" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    
                    
                    <div class="relative" id="appointmentsWrapper">
                        <button id="appointmentsButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="appointmentsMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Appointments
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 hidden" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="models/appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                                <a href="views/users/book_appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
                            </div>
                        </div>
                    </div>

                    <a href="subscriptions.php" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>

                    
                    <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
                </nav>
                    <button onclick="window.location.href='../user-profile.php'" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            J
                        </div>
                        <span class="text-sm font-medium text-gray-700 hidden sm:block">John Doe</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
     <section class="py-16 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <div class="space-y-6">
                    <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200">
                        <i data-lucide="sparkles" class="w-3 h-3 mr-1"></i>
                        Pet Care Marketplace
                    </div>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Everything Your Pet Needs
                    </h1>
                    
                    <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto">
                        Discover premium pet products and trusted pet sitters all in one place. From toys to professional care, we've got your furry friends covered.
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
                                    placeholder="Search products, sitters, or services..." 
                                    id="global-search"
                                    class="flex h-12 w-full rounded-md border border-gray-200 bg-transparent px-3 py-1 pl-10 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-orange-500 focus-visible:border-orange-500 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                />
                            </div>
                            <button onclick="performSearch()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-12 px-8">
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
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-8 px-3" data-category="all">All Items</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="products">
                        <i data-lucide="package" class="w-4 h-4 mr-1"></i>
                        Products
                    </button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="sitters">
                        <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                        Pet Sitters
                    </button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="food">Pet Food</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="toys">Toys</button>
                    <button class="category-btn inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent h-8 px-3" data-category="accessories">Accessories</button>
                </div>

                <!-- Sort and View Controls -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div class="text-sm text-muted-foreground" id="items-count">
                        Showing all marketplace items
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Sort Dropdown -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">Sort by:</span>
                            <select id="sort-select" onchange="sortItems()" class="flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1 w-40">
                                <option value="featured">Featured</option>
                                <option value="name">Name A-Z</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="rating">Highest Rated</option>
                            </select>
                        </div>

                        <!-- View Mode Toggle -->
                        <div class="flex border rounded-lg overflow-hidden">
                            <button id="grid-view-btn" onclick="setViewMode('grid')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-orange-500 hover:bg-orange-600 text-white h-8 px-3">
                                <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                            </button>
                            <button id="list-view-btn" onclick="setViewMode('list')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground bg-transparent h-8 px-3">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marketplace Grid -->
            <div id="marketplace-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Products and Sitters will be inserted here by JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden text-center py-12">
                <i data-lucide="search-x" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No items found</h3>
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
                    Get the latest deals on pet products and discover new trusted sitters in your area.
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
                    Need Help Finding What You Need?
                </h2>
                <p class="text-lg opacity-90">
                    Our pet care experts are here to help you find the perfect products and sitters for your furry friends. 
                    Get personalized recommendations today!
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-10 px-4 py-2 text-base font-semibold">
                        <i data-lucide="message-circle" class="w-4 h-4 mr-2"></i>
                        Chat with Expert
                    </button>
                    <button onclick="window.location.href='../appointment.php'" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-white text-white hover:bg-white hover:text-orange-600 bg-transparent h-10 px-4 py-2 text-base">
                        <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                        Book Appointment
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center text-sm opacity-80 pt-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        +63 912 345 6789
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        hello@pawhabilin.com
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
                    <h4 class="font-semibold">Marketplace</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Pet Products</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Sitters</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Food</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Toys & Accessories</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Services</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="../appointment.php" class="hover:text-white transition-colors">Book Appointments</a></li>
                        <li><a href="../find-sitter.php" class="hover:text-white transition-colors">Find a Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pet Training</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Veterinary Care</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Account</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="../user-profile.php" class="hover:text-white transition-colors">My Profile</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Order History</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Favorites</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Settings</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2025 pawhabilin Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    
    <!-- Product Purchase Modal -->
    <div id="purchaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md mx-4 w-full">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="shopping-cart" class="w-8 h-8 text-blue-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Add to Cart</h3>
                <p class="text-muted-foreground" id="product-modal-desc">Product details and pricing</p>
            </div>
            
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <div class="flex items-center gap-3">
                        <button onclick="decreaseQuantity()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">-</button>
                        <input type="number" id="quantity" value="1" min="1" class="w-20 text-center flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm">
                        <button onclick="increaseQuantity()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">+</button>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total:</span>
                        <span id="total-price" class="text-orange-600">₱0</span>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closePurchaseModal()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-3 flex-1">
                    Cancel
                </button>
                <button onclick="addToCart()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-9 px-3 flex-1">
                    Add to Cart
                </button>
            </div>
        </div>
    </div>

    <!-- Sitter Hire Modal -->
    <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md mx-4 w-full">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="users" class="w-8 h-8 text-green-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hire Pet Sitter</h3>
                <p class="text-muted-foreground" id="sitter-modal-desc">Sitter details and rates</p>
            </div>
            
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                    <select id="service-type" class="flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1">
                        <option value="sitting">Pet Sitting</option>
                        <option value="walking">Dog Walking</option>
                        <option value="boarding">Pet Boarding</option>
                        <option value="daycare">Pet Daycare</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" id="service-date" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (hours)</label>
                        <input type="number" id="service-duration" value="4" min="1" max="24" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                    <textarea id="special-instructions" rows="3" placeholder="Any special care instructions..." class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"></textarea>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Estimated Cost:</span>
                        <span id="estimated-cost" class="text-green-600">₱0</span>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeHireModal()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-3 flex-1">
                    Cancel
                </button>
                <button onclick="hireSitter()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white h-9 px-3 flex-1">
                    Book Now
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentFilter = 'all';
        let currentSort = 'featured';
        let currentView = 'grid';
        let selectedProduct = null;
        let selectedSitter = null;

        // Mock data - Combined products and sitters
        const marketplaceItems = [
            // Products (from shop.php design)
            {
                id: 'p1',
                type: 'product',
                category: 'food',
                name: 'Premium Dog Kibble - Adult Formula',
                price: 1299,
                originalPrice: 1599,
                rating: 4.8,
                reviews: 234,
                image: 'https://images.unsplash.com/photo-1572950947301-fb417712da10?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcmVtaXVtJTIwZG9nJTIwZm9vZCUyMGtpYmJsZXxlbnwxfHx8fDE3NTY1NDM3MTV8MA&ixlib=rb-4.1.0&q=80&w=1080',
                badge: 'Best Seller',
                inStock: true,
                description: 'High-quality protein formula for adult dogs with chicken and rice.',
                brand: 'PetNutrition Pro',
                weight: '15kg'
            },
            {
                id: 's1',
                type: 'sitter',
                category: 'sitters',
                name: 'Mari Santos',
                price: 200,
                priceUnit: 'per hour',
                rating: 5.0,
                reviews: 87,
                image: 'https://images.unsplash.com/photo-1727681200723-9513e4e3c394?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBwZXQlMjBzaXR0ZXIlMjB3aXRoJTIwZG9nfGVufDF8fHx8MTc1NjQ1MjEyOXww&ixlib=rb-4.1.0&q=80&w=1080',
                description: 'Experienced pet sitter with 7 years experience',
                location: 'Cebu City',
                specialties: ['Dogs', 'Cats', 'Birds'],
                badge: 'Top Rated',
                inStock: true
            },
            {
                id: 'p2',
                type: 'product',
                category: 'food',
                name: 'Nutritious Cat Food - Salmon & Tuna',
                price: 899,
                originalPrice: null,
                rating: 4.9,
                reviews: 156,
                image: 'https://images.unsplash.com/photo-1734654901149-02a9a5f7993b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBmb29kJTIwYm93bHxlbnwxfHx8fDE3NTY1MDk4MzR8MA&ixlib=rb-4.1.0&q=80&w=1080',
                badge: 'New',
                inStock: true,
                description: 'Premium wet food with real salmon and tuna, rich in omega-3.',
                brand: 'FelineFresh',
                weight: '400g x 12 cans'
            },
            {
                id: 'p3',
                type: 'product',
                category: 'toys',
                name: 'Interactive Puzzle Dog Toy',
                price: 459,
                originalPrice: 599,
                rating: 4.7,
                reviews: 89,
                image: 'https://images.unsplash.com/photo-1659700097688-f26bf79735af?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjB0b3klMjByb3BlJTIwYmFsbHxlbnwxfHx8fDE3NTY0Njk1Mjd8MA&ixlib=rb-4.1.0&q=80&w=1080',
                badge: 'Sale',
                inStock: true,
                description: 'Mental stimulation toy that challenges your dog while dispensing treats.',
                brand: 'SmartPaws',
                weight: '500g'
            },
            {
                id: 's2',
                type: 'sitter',
                category: 'sitters',
                name: 'Anna Cruz',
                price: 180,
                priceUnit: 'per hour',
                rating: 4.9,
                reviews: 63,
                image: 'https://images.unsplash.com/photo-1608582175768-61fefde475a9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx5b3VuZyUyMHdvbWFuJTIwd2Fsa2luZyUyMGRvZ3N8ZW58MXx8fHwxNzU2NDUyMTI5fDA&ixlib=rb-4.1.0&q=80&w=1080',
                description: 'Loving pet caregiver with 5 years experience',
                location: 'Manila',
                specialties: ['Dogs', 'Cats'],
                inStock: true
            },
            {
                id: 'p4',
                type: 'product',
                category: 'accessories',
                name: 'Durable Pet Collar & Leash Set',
                price: 699,
                originalPrice: null,
                rating: 4.6,
                reviews: 67,
                image: 'https://images.unsplash.com/photo-1577447278822-37801be21738?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBjb2xsYXIlMjBsZWFzaHxlbnwxfHx8fDE3NTY0NDcwNjZ8MA&ixlib=rb-4.1.0&q=80&w=1080',
                badge: null,
                inStock: true,
                description: 'Adjustable nylon collar with matching 6ft leash, perfect for daily walks.',
                brand: 'WalkSafe',
                weight: '200g'
            },
            
        ];

        // Initialize the marketplace
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            renderMarketplace();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            // Category filter buttons
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    filterCategory(this.dataset.category);
                });
            });

            // Service duration change
            document.getElementById('service-duration').addEventListener('input', updateEstimatedCost);

            // Search on Enter
            document.getElementById('global-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }

        // Render marketplace items using shop.php design
        function renderMarketplace() {
            const grid = document.getElementById('marketplace-grid');
            let filteredItems = filterAndSortItems();
            
            if (filteredItems.length === 0) {
                document.getElementById('empty-state').classList.remove('hidden');
                grid.classList.add('hidden');
                return;
            }

            document.getElementById('empty-state').classList.add('hidden');
            grid.classList.remove('hidden');

            // Update items count
            document.getElementById('items-count').textContent = `Showing ${filteredItems.length} items`;

            // Render cards based on view mode
            if (currentView === 'grid') {
                grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
                grid.innerHTML = filteredItems.map(item => createGridCard(item)).join('');
            } else {
                grid.className = 'space-y-4';
                grid.innerHTML = filteredItems.map(item => createListCard(item)).join('');
            }

            lucide.createIcons();
        }

        // Create grid card (shop.php style)
        function createGridCard(item) {
            if (item.type === 'product') {
                return `
                    <div class="group relative rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        ${item.badge ? `<div class="absolute top-3 left-3 z-10 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${getBadgeStyles(item.badge)}">${item.badge}</div>` : ''}
                        ${!item.inStock ? '<div class="absolute top-3 right-3 z-10 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-red-100 text-red-800">Out of Stock</div>' : ''}
                        
                        <div class="aspect-square overflow-hidden">
                            <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        </div>
                        
                        <div class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="font-semibold leading-none tracking-tight mb-2">${item.name}</h3>
                                    <p class="text-sm text-muted-foreground">${item.description}</p>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                    <span class="text-sm font-medium">${item.rating}</span>
                                    <span class="text-sm text-muted-foreground">(${item.reviews})</span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl font-bold">₱${item.price.toLocaleString()}</span>
                                            ${item.originalPrice ? `<span class="text-sm text-muted-foreground line-through">₱${item.originalPrice.toLocaleString()}</span>` : ''}
                                        </div>
                                        ${item.brand ? `<p class="text-xs text-muted-foreground">${item.brand}</p>` : ''}
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button onclick="buyProduct('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white h-9 px-3 flex-1 ${!item.inStock ? 'opacity-50 cursor-not-allowed' : ''}" ${!item.inStock ? 'disabled' : ''}>
                                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                        ${item.inStock ? 'Add to Cart' : 'Out of Stock'}
                                    </button>
                                    <button onclick="addToWishlist('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">
                                        <i data-lucide="heart" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Sitter card
                return `
                    <div class="group relative rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        ${item.badge ? `<div class="absolute top-3 left-3 z-10 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${getBadgeStyles(item.badge)}">${item.badge}</div>` : ''}
                        
                        <div class="aspect-square overflow-hidden">
                            <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        </div>
                        
                        <div class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="font-semibold leading-none tracking-tight mb-2">${item.name}</h3>
                                    <p class="text-sm text-muted-foreground">${item.description}</p>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                    <span class="text-sm font-medium">${item.rating}</span>
                                    <span class="text-sm text-muted-foreground">(${item.reviews})</span>
                                </div>
                                
                                <div class="flex flex-wrap gap-1 mb-2">
                                    ${item.specialties.map(specialty => 
                                        `<span class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">${specialty}</span>`
                                    ).join('')}
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl font-bold">₱${item.price.toLocaleString()}</span>
                                            <span class="text-sm text-muted-foreground">/${item.priceUnit}</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                                            ${item.location}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button onclick="hirePetSitter('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white h-9 px-3 flex-1">
                                        <i data-lucide="users" class="w-4 h-4"></i>
                                        Hire Now
                                    </button>
                                    <button onclick="addToFavorites('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">
                                        <i data-lucide="heart" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Create list card
        function createListCard(item) {
            if (item.type === 'product') {
                return `
                    <div class="group relative rounded-lg border bg-card text-card-foreground shadow-sm p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="flex gap-6">
                            <div class="w-32 h-32 rounded-lg overflow-hidden flex-shrink-0 relative">
                                ${item.badge ? `<div class="absolute top-2 left-2 z-10 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${getBadgeStyles(item.badge)}">${item.badge}</div>` : ''}
                                <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            </div>
                            <div class="flex-1 space-y-3">
                                <div>
                                    <h3 class="text-lg font-semibold leading-none tracking-tight mb-2">${item.name}</h3>
                                    <p class="text-sm text-muted-foreground">${item.description}</p>
                                    ${item.brand ? `<p class="text-xs text-muted-foreground mt-1">Brand: ${item.brand}</p>` : ''}
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                        <span class="text-sm font-medium">${item.rating}</span>
                                        <span class="text-sm text-muted-foreground">(${item.reviews})</span>
                                    </div>
                                    ${!item.inStock ? '<span class="text-sm text-red-600 font-medium">Out of Stock</span>' : ''}
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-bold">₱${item.price.toLocaleString()}</span>
                                        ${item.originalPrice ? `<span class="text-sm text-muted-foreground line-through">₱${item.originalPrice.toLocaleString()}</span>` : ''}
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="buyProduct('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white h-9 px-4 ${!item.inStock ? 'opacity-50 cursor-not-allowed' : ''}" ${!item.inStock ? 'disabled' : ''}>
                                            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                            ${item.inStock ? 'Add to Cart' : 'Out of Stock'}
                                        </button>
                                        <button onclick="addToWishlist('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">
                                            <i data-lucide="heart" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Sitter list card
                return `
                    <div class="group relative rounded-lg border bg-card text-card-foreground shadow-sm p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="flex gap-6">
                            <div class="w-32 h-32 rounded-lg overflow-hidden flex-shrink-0 relative">
                                ${item.badge ? `<div class="absolute top-2 left-2 z-10 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${getBadgeStyles(item.badge)}">${item.badge}</div>` : ''}
                                <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            </div>
                            <div class="flex-1 space-y-3">
                                <div>
                                    <h3 class="text-lg font-semibold leading-none tracking-tight mb-2">${item.name}</h3>
                                    <p class="text-sm text-muted-foreground">${item.description}</p>
                                    <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        ${item.location}
                                    </p>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                        <span class="text-sm font-medium">${item.rating}</span>
                                        <span class="text-sm text-muted-foreground">(${item.reviews})</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        ${item.specialties.map(specialty => 
                                            `<span class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">${specialty}</span>`
                                        ).join('')}
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-bold">₱${item.price.toLocaleString()}</span>
                                        <span class="text-sm text-muted-foreground">/${item.priceUnit}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="hirePetSitter('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white h-9 px-4">
                                            <i data-lucide="users" class="w-4 h-4"></i>
                                            Hire Now
                                        </button>
                                        <button onclick="addToFavorites('${item.id}')" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 w-9">
                                            <i data-lucide="heart" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Get badge styles based on badge type
        function getBadgeStyles(badge) {
            switch (badge.toLowerCase()) {
                case 'best seller':
                    return 'bg-orange-100 text-orange-800';
                case 'new':
                    return 'bg-green-100 text-green-800';
                case 'sale':
                    return 'bg-red-100 text-red-800';
                case 'premium':
                    return 'bg-purple-100 text-purple-800';
                case 'top rated':
                    return 'bg-yellow-100 text-yellow-800';
                case 'verified pro':
                    return 'bg-blue-100 text-blue-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        // Filter and sort items
        function filterAndSortItems() {
            let filteredItems = marketplaceItems;

            // Apply category filter
            if (currentFilter !== 'all') {
                filteredItems = marketplaceItems.filter(item => {
                    if (currentFilter === 'products') return item.type === 'product';
                    if (currentFilter === 'sitters') return item.type === 'sitter';
                    return item.category === currentFilter;
                });
            }

            // Apply sorting
            filteredItems = sortItemsArray(filteredItems, currentSort);

            return filteredItems;
        }

        // Filter by category
        function filterCategory(category) {
            currentFilter = category;
            
            // Update active filter button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.className = btn.className.replace('bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white', 'border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent');
            });
            
            const activeBtn = document.querySelector(`[data-category="${category}"]`);
            if (activeBtn) {
                activeBtn.className = activeBtn.className.replace('border border-orange-200 text-orange-600 hover:bg-orange-50 bg-transparent', 'bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white');
            }
            
            renderMarketplace();
        }

        // Sort items
        function sortItems() {
            currentSort = document.getElementById('sort-select').value;
            renderMarketplace();
        }

        function sortItemsArray(items, sortBy) {
            switch (sortBy) {
                case 'name':
                    return [...items].sort((a, b) => a.name.localeCompare(b.name));
                case 'price-low':
                    return [...items].sort((a, b) => a.price - b.price);
                case 'price-high':
                    return [...items].sort((a, b) => b.price - a.price);
                case 'rating':
                    return [...items].sort((a, b) => b.rating - a.rating);
                default:
                    return items;
            }
        }

        // Set view mode
        function setViewMode(mode) {
            currentView = mode;
            
            // Update buttons
            const gridBtn = document.getElementById('grid-view-btn');
            const listBtn = document.getElementById('list-view-btn');
            
            if (mode === 'grid') {
                gridBtn.className = gridBtn.className.replace('hover:bg-accent hover:text-accent-foreground bg-transparent', 'bg-orange-500 hover:bg-orange-600 text-white');
                listBtn.className = listBtn.className.replace('bg-orange-500 hover:bg-orange-600 text-white', 'hover:bg-accent hover:text-accent-foreground bg-transparent');
            } else {
                listBtn.className = listBtn.className.replace('hover:bg-accent hover:text-accent-foreground bg-transparent', 'bg-orange-500 hover:bg-orange-600 text-white');
                gridBtn.className = gridBtn.className.replace('bg-orange-500 hover:bg-orange-600 text-white', 'hover:bg-accent hover:text-accent-foreground bg-transparent');
            }
            
            renderMarketplace();
        }

        // Search functionality
        function performSearch() {
            const query = document.getElementById('global-search').value.toLowerCase();
            if (!query) return;
            
            // TODO: Implement search functionality
            console.log('Searching for:', query);
            alert(`Searching for: "${query}". This will be implemented in a future update.`);
        }

        // PRODUCT FUNCTIONS (Function placeholders for future implementation)
        
        // Buy product function
        function buyProduct(productId) {
            selectedProduct = marketplaceItems.find(item => item.id === productId);
            if (!selectedProduct || !selectedProduct.inStock) return;
            
            document.getElementById('product-modal-desc').textContent = `${selectedProduct.name} - ₱${selectedProduct.price.toLocaleString()}`;
            updateTotalPrice();
            document.getElementById('purchaseModal').classList.remove('hidden');
            document.getElementById('purchaseModal').classList.add('flex');
        }

        // Add to cart function (placeholder)
        function addToCart() {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            // TODO: Implement add to cart functionality
            console.log('Adding to cart:', {
                product: selectedProduct,
                quantity: quantity,
                total: selectedProduct.price * quantity
            });
            
            alert(`Added ${quantity}x ${selectedProduct.name} to cart! This will be implemented in a future update.`);
            closePurchaseModal();
        }

        // Add to wishlist function (placeholder)
        function addToWishlist(productId) {
            // TODO: Implement wishlist functionality
            console.log('Adding to wishlist:', productId);
            alert('Added to wishlist! This feature will be implemented in a future update.');
        }

        // SITTER FUNCTIONS (Function placeholders for future implementation)
        
        // Hire pet sitter function
        function hirePetSitter(sitterId) {
            selectedSitter = marketplaceItems.find(item => item.id === sitterId);
            if (!selectedSitter) return;
            
            document.getElementById('sitter-modal-desc').textContent = `${selectedSitter.name} - ₱${selectedSitter.price} per hour`;
            updateEstimatedCost();
            document.getElementById('hireModal').classList.remove('hidden');
            document.getElementById('hireModal').classList.add('flex');
        }

        // Book sitter function (placeholder)
        function hireSitter() {
            const serviceType = document.getElementById('service-type').value;
            const serviceDate = document.getElementById('service-date').value;
            const duration = parseInt(document.getElementById('service-duration').value);
            const instructions = document.getElementById('special-instructions').value;
            
            // TODO: Implement sitter booking functionality
            console.log('Booking sitter:', {
                sitter: selectedSitter,
                serviceType: serviceType,
                date: serviceDate,
                duration: duration,
                instructions: instructions,
                cost: selectedSitter.price * duration
            });
            
            alert(`Booking request sent to ${selectedSitter.name}! This will be implemented in a future update.`);
            closeHireModal();
        }

        // Add to favorites function (placeholder)
        function addToFavorites(sitterId) {
            // TODO: Implement favorites functionality
            console.log('Adding to favorites:', sitterId);
            alert('Added to favorites! This feature will be implemented in a future update.');
        }

        // MODAL FUNCTIONS
        
        function closePurchaseModal() {
            document.getElementById('purchaseModal').classList.add('hidden');
            document.getElementById('purchaseModal').classList.remove('flex');
            document.getElementById('quantity').value = 1;
        }

        function closeHireModal() {
            document.getElementById('hireModal').classList.add('hidden');
            document.getElementById('hireModal').classList.remove('flex');
            document.getElementById('service-duration').value = 4;
            document.getElementById('special-instructions').value = '';
        }

        function increaseQuantity() {
            const input = document.getElementById('quantity');
            input.value = parseInt(input.value) + 1;
            updateTotalPrice();
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateTotalPrice();
            }
        }

        function updateTotalPrice() {
            if (!selectedProduct) return;
            const quantity = parseInt(document.getElementById('quantity').value);
            const total = selectedProduct.price * quantity;
            document.getElementById('total-price').textContent = `₱${total.toLocaleString()}`;
        }

        function updateEstimatedCost() {
            if (!selectedSitter) return;
            const duration = parseInt(document.getElementById('service-duration').value) || 4;
            const cost = selectedSitter.price * duration;
            document.getElementById('estimated-cost').textContent = `₱${cost.toLocaleString()}`;
        }
    </script>
</body>
</html>