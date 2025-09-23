<?php
// Copy of root index.php design, adapted for nested path
// Featured sitters data
$featuredSitters = [
    [
        'id' => 1,
        'name' => 'Name Blank',
        'age' => 'Age',
        'rating' => 'Rate',
        'reviews' => 'Reviews',
        'hourlyRate' => 'Hourly Rate',
        'distance' => 'Location',
        'image' => '../../pictures/Pawhabilin logo.png',
        'badges' => ['Background Check', 'Pet First Aid'],
        'available' => true,
        'pets' => ['Dogs', 'Cats']
    ],
    [
        'id' => 2,
        'name' => 'Name Blank',
        'age' => 'Age',
        'rating' => 'Rate',
        'reviews' => 'Reviews',
        'hourlyRate' => 'Hourly Rate',
        'distance' => 'Location',
        'image' => '../../pictures/Pawhabilin logo.png',
        'badges' => ['Veterinary Student', '5+ Years'],
        'available' => false,
        'pets' => ['Dogs', 'Cats', 'Birds']
    ],
    [
        'id' => 3,
        'name' => 'Name Blank',
        'age' => 'Age',
        'rating' => 'Rate',
        'reviews' => 'Reviews',
        'hourlyRate' => 'Hourly Rate',
        'distance' => 'Location',
        'image' => '../../pictures/Pawhabilin logo.png',
        'badges' => ['Student', 'Small Pets'],
        'available' => true,
        'pets' => ['Dogs', 'Cats', 'Rabbits']
    ],
];

// Featured products data
$featuredProducts = [
    [
        'id' => 1,
        'name' => 'Premium Dog Kibble',
        'category' => 'Dog Food',
        'price' => 1299,
        'originalPrice' => 1599,
        'rating' => 4.8,
        'reviews' => 234,
        'image' => '../../pictures/Pawhabilin logo.png',
        'badge' => 'Best Seller',
        'inStock' => true
    ],
    [
        'id' => 2,
        'name' => 'Nutritious Cat Food',
        'category' => 'Cat Food',
        'price' => 899,
        'originalPrice' => null,
        'rating' => 4.9,
        'reviews' => 156,
        'image' => '../../pictures/Pawhabilin logo.png',
        'badge' => 'New',
        'inStock' => true
    ],
    [
        'id' => 3,
        'name' => 'Interactive Dog Toy',
        'category' => 'Toys',
        'price' => 459,
        'originalPrice' => 599,
        'rating' => 4.7,
        'reviews' => 89,
        'image' => '../../pictures/Pawhabilin logo.png',
        'badge' => 'Sale',
        'inStock' => true
    ],
    [
        'id' => 4,
        'name' => 'Durable Pet Collar & Leash Set',
        'category' => 'Accessories',
        'price' => 699,
        'originalPrice' => null,
        'rating' => 4.6,
        'reviews' => 67,
        'image' => '../../pictures/Pawhabilin logo.png',
        'badge' => null,
        'inStock' => true
    ]
];

// Stats data
$stats = [
    ['number' => '8,000+', 'label' => 'Trusted Pet Sitters'],
    ['number' => '25,000+', 'label' => 'Happy Pet Parents'],
    ['number' => '4.9★', 'label' => 'Average Rating'],
    ['number' => '24/7', 'label' => 'Support']
];

require_once __DIR__ . '/../../utils/session.php';
$currentUser = get_current_user_session();
$currentUserName = user_display_name($currentUser);
$currentUserInitial = user_initial($currentUser);
$currentUserImg = user_image_url($currentUser);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawhabilin - Because Every Paw Deserves a Promise.</title>
    <link rel="stylesheet" href="../globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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

    <!-- Hero Section -->
    <section class="py-16 lg:py-24 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <div class="space-y-6">
                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200">
                        <i data-lucide="paw-print" class="w-3 h-3 mr-1"></i>
                        Philippines' #1 Pet Sitting Platform
                    </div>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Find the Perfect Pet Sitter Near You
                    </h1>
                    
                    <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto">
                        Connect with trusted, verified pet sitters in your neighborhood. Your furry friends deserve the best care.
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-3xl mx-auto pt-8">
                    <?php foreach ($stats as $stat): ?>
                        <div class="text-center">
                            <div class="text-2xl md:text-3xl font-bold text-orange-600"><?php echo htmlspecialchars($stat['number']); ?></div>
                            <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($stat['label']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Sitters -->
    <section class="py-20">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-12">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-bold mb-2">Featured Pet Sitters</h2>
                    <p class="text-muted-foreground">Trusted pet care providers near you</p>
                </div>
                <button onclick="window.location.href='../../find-sitters.php'" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 flex items-center gap-2">
                    View All <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($featuredSitters as $sitter): ?>
                    <div class="rounded-lg bg-card text-card-foreground shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                        <div class="relative">
                            <div class="aspect-[4/3] overflow-hidden">
                                <img
                                    src="<?php echo htmlspecialchars($sitter['image']); ?>"
                                    alt="<?php echo htmlspecialchars($sitter['name']); ?>"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="absolute top-3 left-3">
                                <?php if ($sitter['available']): ?>
                                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-green-500 hover:bg-green-600 text-white">
                                        <div class="w-2 h-2 bg-white rounded-full mr-1"></div>
                                        Available
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground">
                                        Busy
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 absolute top-3 right-3 w-8 h-8 p-0">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($sitter['name']); ?>, <?php echo $sitter['age']; ?></h3>
                                        <p class="text-sm text-muted-foreground flex items-center gap-1">
                                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                                            <?php echo htmlspecialchars($sitter['distance']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold">₱<?php echo $sitter['hourlyRate']; ?>/hr</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                        <span class="font-medium"><?php echo $sitter['rating']; ?></span>
                                    </div>
                                    <span class="text-sm text-muted-foreground">(<?php echo $sitter['reviews']; ?> reviews)</span>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($sitter['badges'] as $badge): ?>
                                            <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground">
                                                <?php echo htmlspecialchars($badge); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="paw-print" class="w-3 h-3 text-muted-foreground"></i>
                                        <span class="text-xs text-muted-foreground">
                                            <?php echo htmlspecialchars(implode(', ', $sitter['pets'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-3 flex-1">
                                        <i data-lucide="message-circle" class="w-4 h-4 mr-1"></i>
                                        Message
                                    </button>
                                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                        View Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Shop Section -->
    <section id="shop" class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <div class="space-y-2">
                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground w-fit">
                        <i data-lucide="package" class="w-3 h-3 mr-1"></i>
                        Pet Shop
                    </div>
                    <h2 class="text-2xl lg:text-3xl font-bold">Pet Essentials</h2>
                    <p class="text-muted-foreground max-w-xl mx-auto">
                        Quality pet products delivered to your door
                    </p>
                </div>
            </div>

            <!-- Products Grid - Show only 4 products -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <?php foreach (array_slice($featuredProducts, 0, 4) as $product): ?>
                    <div class="rounded-lg bg-card text-card-foreground shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                        <div class="relative">
                            <div class="aspect-square overflow-hidden">
                                <img
                                    src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            
                            <!-- Product Badge -->
                            <?php if ($product['badge']): ?>
                                <div class="absolute top-2 left-2">
                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 
                                    <?php 
                                    echo $product['badge'] === 'Sale' ? 'bg-red-500 hover:bg-red-600 text-white' :
                                         ($product['badge'] === 'New' ? 'bg-green-500 hover:bg-green-600 text-white' :
                                          ($product['badge'] === 'Best Seller' ? 'bg-blue-500 hover:bg-blue-600 text-white' :
                                           ($product['badge'] === 'Popular' ? 'bg-purple-500 hover:bg-purple-600 text-white' :
                                            'bg-yellow-500 hover:bg-yellow-600 text-white')));
                                    ?>">
                                        <?php echo htmlspecialchars($product['badge']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Stock Status -->
                            <?php if (!$product['inStock']): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-destructive text-destructive-foreground">
                                        Out of Stock
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="font-semibold text-sm mb-1 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    
                                    <div class="flex items-center gap-1 mb-2">
                                        <i data-lucide="star" class="w-3 h-3 fill-yellow-400 text-yellow-400"></i>
                                        <span class="text-xs font-medium"><?php echo $product['rating']; ?></span>
                                        <span class="text-xs text-muted-foreground">(<?php echo $product['reviews']; ?>)</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-1">
                                        <span class="font-bold text-orange-600">₱<?php echo number_format($product['price']); ?></span>
                                        <?php if ($product['originalPrice']): ?>
                                            <span class="text-xs text-muted-foreground line-through">₱<?php echo number_format($product['originalPrice']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white h-8 px-3 <?php echo !$product['inStock'] ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo !$product['inStock'] ? 'disabled' : ''; ?>>
                                    <i data-lucide="shopping-cart" class="w-3 h-3 mr-1"></i>
                                    <?php echo $product['inStock'] ? 'Add to Cart' : 'Out of Stock'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Compact Shop CTA -->
            <div class="text-center">
                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
                    View All Products
                </button>
            </div>
        </div>
    </section>

    <!-- Trust & Safety -->
    <section class="py-20">
                        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground w-fit">
                            <i data-lucide="shield" class="w-3 h-3 mr-1"></i>
                            Trust & Safety
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-bold">Your Pet's Safety is Our Priority</h2>
                        <p class="text-lg text-muted-foreground">
                            Every pet sitter on our platform goes through comprehensive verification including background checks, identity verification, and pet care training.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Background Checks</div>
                                <div class="text-sm text-muted-foreground">100% verified</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="award" class="w-5 h-5 text-orange-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Pet Care Training</div>
                                <div class="text-sm text-muted-foreground">Certified sitters</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="shield" class="w-5 h-5 text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Insurance Covered</div>
                                <div class="text-sm text-muted-foreground">Full protection</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-5 h-5 text-purple-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold">24/7 Support</div>
                                <div class="text-sm text-muted-foreground">Always here</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="aspect-square rounded-2xl overflow-hidden">
                        <img
                            src="https://images.unsplash.com/photo-1596653048850-7918adea48b0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZ3MlMjBwbGF5aW5nJTIwcGFya3xlbnwxfHx8fDE3NTY0NDQ2OTh8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                            alt="Happy dogs playing"
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-orange-600 to-amber-700">
        <div class="container mx-auto px-4 text-center">
            <div class="max-w-3xl mx-auto space-y-8 text-white">
                <h2 class="text-3xl lg:text-4xl font-bold">
                    Ready to Find Your Perfect Pet Sitter?
                </h2>
                <p class="text-lg opacity-90">
                    Join thousands of pet parents who trust <span class="brand-font">pawhabilin</span> for their pet care needs. 
                    Start your search today and find the perfect match for your furry family.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-11 px-8 bg-secondary text-secondary-foreground hover:bg-secondary/80 font-semibold">
                        Find a Pet Sitter
                    </button>
                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-white text-white hover:bg-white hover:text-orange-600 h-11 px-8">
                        Become a Pet Sitter
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center text-sm opacity-80 pt-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        (02) 8123-4567
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        hello@pawhabilin.ph
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
                        <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
                            <img src="../../pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </span>
                    </div>
                    <p class="text-gray-400">
                        The Philippines' most trusted pet sitting platform connecting pet parents with verified pet care providers.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">For Pet Parents</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Find a Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">How it Works</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Safety</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pricing</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">For Pet Sitters</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Become a Pet Sitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Sitter Resources</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Background Check</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Earnings</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Trust & Safety</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Community</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 text-center text-gray-400">
                <p>&copy; 2025 <span class="brand-font">pawhabilin</span> Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
    <script>
        // Generic dropdown initializer: supports hover open and click-to-persist
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

            // Initialize dropdowns
            initDropdown({ wrapperId: 'appointmentsWrapper', buttonId: 'appointmentsButton', menuId: 'appointmentsMenu' });
            initDropdown({ wrapperId: 'petsitterWrapper', buttonId: 'petsitterButton', menuId: 'petsitterMenu' });
            initDropdown({ wrapperId: 'userMenu', buttonId: 'userMenuBtn', menuId: 'userMenuMenu' });
        })();
    </script>
</body>
</html>
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
    <script>
        // Dropdown initializer (hover open + click to persist), matching landing page
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

            // Initialize dropdowns
            initDropdown({ wrapperId: 'appointmentsWrapper', buttonId: 'appointmentsButton', menuId: 'appointmentsMenu' });
            initDropdown({ wrapperId: 'petsitterWrapper', buttonId: 'petsitterButton', menuId: 'petsitterMenu' });
        })();
    </script>
</body>
</html>