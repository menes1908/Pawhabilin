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
                        <button id="petsitterButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="petsitterMenu" class="text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-2">
                            Pet Sitter
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"></i>
                        </button>
                        <div id="petsitterMenu" class="absolute left-0 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 hidden" role="menu" aria-hidden="true">
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

                        <div id="appointmentsMenu" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 nav-dropdown transition-all duration-200 hidden" role="menu" aria-hidden="true">
                            <div class="py-1">
                                <a href="models/appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Grooming Appointment</a>
                                <a href="views/users/book_appointment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Vet Appointment</a>
                            </div>
                        </div>
                    </div>

                    <a href="views/users/subscriptions.php" class="text-muted-foreground hover:text-foreground transition-colors">Subscription</a>

                    
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
    <main class="container mx-auto px-4 py-8 relative overflow-hidden">
        <!-- Background Decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-paw absolute top-20 right-20 opacity-5">
                <i data-lucide="paw-print" class="w-16 h-16 text-orange-300"></i>
            </div>
            <div class="floating-paw absolute bottom-40 left-20 opacity-5">
                <i data-lucide="paw-print" class="w-12 h-12 text-amber-300"></i>
            </div>
            <div class="floating-paw absolute top-40 left-40 opacity-5">
                <i data-lucide="heart" class="w-10 h-10 text-orange-200"></i>
            </div>
        </div>

        <div class="relative z-10 space-y-8">
            <!-- Welcome Section -->
            <div class="dashboard-card bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 rounded-2xl p-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium mb-4">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            Welcome back, John!
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-4">
                            Your Pet Care Hub
                        </h1>
                        <p class="text-orange-100 text-lg mb-6">
                            Everything you need to keep your furry friends happy and healthy, all in one place.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <button onclick="window.location.href='../find-sitter.php'" class="bg-white text-orange-600 px-6 py-3 rounded-lg font-semibold hover:bg-orange-50 transition-all duration-200 flex items-center gap-2">
                                <i data-lucide="users" class="w-5 h-5"></i>
                                Find a Sitter
                            </button>
                            <button onclick="window.location.href='../appointment.php'" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-orange-600 transition-all duration-200 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                                Book Appointment
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                            <div class="stats-counter text-2xl font-bold mb-1">12</div>
                            <div class="text-orange-100 text-sm">Total Bookings</div>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                            <div class="stats-counter text-2xl font-bold mb-1">3</div>
                            <div class="text-orange-100 text-sm">Registered Pets</div>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                            <div class="stats-counter text-2xl font-bold mb-1">5.0</div>
                            <div class="text-orange-100 text-sm">Rating</div>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                            <div class="stats-counter text-2xl font-bold mb-1">₱2.4k</div>
                            <div class="text-orange-100 text-sm">Total Spent</div>
                        </div>
                    </div>
                </div>
                
                <!-- Decorative elements -->
                <div class="absolute top-4 right-4 opacity-20">
                    <i data-lucide="paw-print" class="w-12 h-12 text-white transform rotate-12"></i>
                </div>
                <div class="absolute bottom-4 left-4 opacity-20">
                    <i data-lucide="heart" class="w-8 h-8 text-white transform -rotate-12"></i>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Shop Products -->
                    <div class="quick-action-card bg-white rounded-2xl p-6 border border-gray-200 cursor-pointer group" onclick="window.location.href='../shop.php'">
                        <div class="text-center space-y-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-indigo-200 rounded-full mx-auto flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="shopping-cart" class="w-8 h-8 text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Shop Products</h3>
                                <p class="text-gray-600 text-sm">Browse premium pet food, toys, and accessories</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="package" class="w-4 h-4"></i>
                                    <span>500+ Products</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                    <span>Free Delivery</span>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white py-3 rounded-lg font-semibold transition-all duration-300 group-hover:shadow-lg">
                                Browse Shop
                            </button>
                        </div>
                    </div>

                    <!-- Hire a Sitter -->
                    <div class="quick-action-card bg-white rounded-2xl p-6 border border-gray-200 cursor-pointer group" onclick="window.location.href='../find-sitter.php'">
                        <div class="text-center space-y-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-emerald-200 rounded-full mx-auto flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="users" class="w-8 h-8 text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Hire a Sitter</h3>
                                <p class="text-gray-600 text-sm">Find trusted pet sitters in your area</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="star" class="w-4 h-4"></i>
                                    <span>5-Star Rated</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                                    <span>Verified Sitters</span>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white py-3 rounded-lg font-semibold transition-all duration-300 group-hover:shadow-lg">
                                Find Sitters
                            </button>
                        </div>
                    </div>

                    <!-- Book Appointment -->
                    <div class="quick-action-card bg-white rounded-2xl p-6 border border-gray-200 cursor-pointer group" onclick="window.location.href='../appointment.php'">
                        <div class="text-center space-y-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-pink-200 rounded-full mx-auto flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="calendar" class="w-8 h-8 text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Book Appointment</h3>
                                <p class="text-gray-600 text-sm">Schedule grooming or vet appointments</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    <span>Same Day Available</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                    <i data-lucide="award" class="w-4 h-4"></i>
                                    <span>Expert Care</span>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white py-3 rounded-lg font-semibold transition-all duration-300 group-hover:shadow-lg">
                                Book Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Orders -->
                <div class="lg:col-span-2">
                    <div class="dashboard-card bg-white rounded-2xl border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800">Recent Orders & Bookings</h3>
                                <a href="#" class="text-orange-600 hover:text-orange-700 text-sm font-medium">View All</a>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <!-- Order Item 1 -->
                                <div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800">Premium Dog Kibble</h4>
                                        <p class="text-sm text-gray-600">Ordered on Jan 20, 2025</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-800">₱1,299</div>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Delivered</span>
                                    </div>
                                </div>

                                <!-- Order Item 2 -->
                                <div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800">Pet Sitting - Mari Santos</h4>
                                        <p class="text-sm text-gray-600">Jan 25, 2025 at 9:00 AM</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-800">₱800</div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Confirmed</span>
                                    </div>
                                </div>

                                <!-- Order Item 3 -->
                                <div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i data-lucide="scissors" class="w-6 h-6 text-purple-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800">Grooming Appointment</h4>
                                        <p class="text-sm text-gray-600">Jan 22, 2025 at 2:00 PM</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-800">₱1,200</div>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Completed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile & Quick Links -->
                <div class="space-y-6">
                    <!-- Profile Card -->
                    <div class="dashboard-card bg-white rounded-2xl border border-gray-200 cursor-pointer" onclick="window.location.href='../user-profile.php'">
                        <div class="p-6">
                            <div class="text-center space-y-4">
                                <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full mx-auto flex items-center justify-center text-white text-2xl font-semibold">
                                    J
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">John Doe</h3>
                                    <p class="text-gray-600 text-sm">Pet Parent since March 2024</p>
                                </div>
                                <div class="flex items-center justify-center gap-1">
                                    <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                    <span class="font-medium">5.0</span>
                                    <span class="text-gray-500 text-sm">(24 reviews)</span>
                                </div>
                                <button class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                                    View Profile
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- My Pets Card -->
                    <div class="dashboard-card bg-white rounded-2xl border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i data-lucide="paw-print" class="w-5 h-5 text-orange-500"></i>
                                My Pets
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="dog" class="w-5 h-5 text-amber-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800">Buddy</div>
                                        <div class="text-sm text-gray-600">Golden Retriever, 3 years</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                                    <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="cat" class="w-5 h-5 text-pink-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800">Luna</div>
                                        <div class="text-sm text-gray-600">Persian Cat, 2 years</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="bird" class="w-5 h-5 text-blue-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800">Charlie</div>
                                        <div class="text-sm text-gray-600">Parrot, 5 years</div>
                                    </div>
                                </div>
                            </div>
                            <button onclick="window.location.href='../user-profile.php'" class="w-full mt-4 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm">
                                Manage Pets
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="dashboard-card bg-white rounded-2xl border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">This Month</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-blue-500"></i>
                                        <span class="text-sm text-gray-600">Appointments</span>
                                    </div>
                                    <span class="font-semibold">3</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="shopping-bag" class="w-4 h-4 text-green-500"></i>
                                        <span class="text-sm text-gray-600">Orders</span>
                                    </div>
                                    <span class="font-semibold">5</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 text-orange-500"></i>
                                        <span class="text-sm text-gray-600">Spent</span>
                                    </div>
                                    <span class="font-semibold">₱4,299</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="heart" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-sm text-gray-600">Favorites</span>
                                    </div>
                                    <span class="font-semibold">8</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action Section -->
            <div class="dashboard-card bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-2xl p-8 text-white text-center">
                <div class="max-w-2xl mx-auto">
                    <h2 class="text-3xl font-bold mb-4">Loving Your pawhabilin Experience?</h2>
                    <p class="text-blue-100 mb-6">Share the love with fellow pet parents and earn rewards for every successful referral!</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-all duration-200 flex items-center justify-center gap-2">
                            <i data-lucide="share" class="w-5 h-5"></i>
                            Refer Friends
                        </button>
                        <button class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-all duration-200 flex items-center justify-center gap-2">
                            <i data-lucide="gift" class="w-5 h-5"></i>
                            View Rewards
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 rounded-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="pawhabilin Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="text-xl font-semibold brand-font">pawhabilin</span>
                    </div>
                    <p class="text-gray-400">Your trusted pet care community for happier, healthier pets.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="../shop.php" class="hover:text-white transition-colors">Shop</a></li>
                        <li><a href="../find-sitter.php" class="hover:text-white transition-colors">Find Sitter</a></li>
                        <li><a href="../appointment.php" class="hover:text-white transition-colors">Appointments</a></li>
                        <li><a href="../user-profile.php" class="hover:text-white transition-colors">My Profile</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Safety Tips</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Connect</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            +63 912 345 6789
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            hello@pawhabilin.com
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                            Cebu City, Philippines
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 pawhabilin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });

        // Add some interactive effects
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Animate stats on page load
        function animateStats() {
            const statsCounters = document.querySelectorAll('.stats-counter');
            statsCounters.forEach(counter => {
                const finalValue = counter.textContent;
                const isNumber = !isNaN(finalValue);
                
                if (isNumber) {
                    let currentValue = 0;
                    const increment = finalValue / 50;
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            counter.textContent = finalValue;
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.floor(currentValue);
                        }
                    }, 40);
                }
            });
        }

        // Run stats animation after page loads
        setTimeout(animateStats, 500);

        // Dropdown logic for Pet Sitter and Appointments
        function setupDropdown(buttonId, menuId) {
            const button = document.getElementById(buttonId);
            const menu = document.getElementById(menuId);

            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const expanded = menu.classList.contains('hidden');
                document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.add('hidden'));
                menu.classList.toggle('hidden', !expanded);
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                menu.setAttribute('aria-hidden', expanded ? 'false' : 'true');
            });
        }

        setupDropdown('petsitterButton', 'petsitterMenu');
        setupDropdown('appointmentsButton', 'appointmentsMenu');

        // Hide dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.nav-dropdown').forEach(menu => {
                if (!menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    // update ARIA attributes
                    const btn = menu.parentElement.querySelector('button');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                    menu.setAttribute('aria-hidden', 'true');
                }
            });
        });

        // Prevent closing when clicking inside dropdown
        document.querySelectorAll('.nav-dropdown').forEach(menu => {
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>