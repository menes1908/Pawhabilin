<?php
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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
        'image' => 'Pawhabilin logo.png',
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

// Dog logo from the figma asset
$dogLogo = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTIwIDM4QzMwLjQ5MzQgMzggMzkgMjkuNDkzNCAzOSAyMEMzOSAxMC41MDY2IDMwLjQ5MzQgMiAyMCAyQzEzLjc5NzQgMiA4LjM4ODk0IDUuNjU2ODUgNS42NDAzNyAxMC45NDI3QzQuNTgyODUgMTIuODMxOSA0IDEzLjg2OTUgNCAyMEM0IDI5LjQ5MzQgMTIuNTA2NiAzOCAyMCAzOFoiIGZpbGw9IiNGRkI4NzAiLz4KPGNpcmNsZSBjeD0iMTUiIGN5PSIxNyIgcj0iMiIgZmlsbD0iIzAwMDAwMCIvPgo8Y2lyY2xlIGN4PSIyNSIgY3k9IjE3IiByPSIyIiBmaWxsPSIjMDAwMDAwIi8+CjxwYXRoIGQ9Ik0xNiAyNEMyMCAyOCAyNCAyNCAyOCAyMCIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetSits - Find the Perfect Pet Sitter Near You</title>
    <link rel="stylesheet" href="styles/globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
                        <img src="Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                    </div>
                    <span class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </span>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#featured-sitters" class="text-muted-foreground hover:text-foreground transition-colors">Find a Sitter</a>
                    <a href="#become-sitter" class="text-muted-foreground hover:text-foreground transition-colors">Become a Sitter</a>
                    <a href="#shop" class="text-muted-foreground hover:text-foreground transition-colors">Shop</a>
                    <a href="#support" class="text-muted-foreground hover:text-foreground transition-colors">Support</a>
                </nav>

                <div class="flex items-center gap-3">
                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                        Log In
                    </button>
                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
                        Sign Up
                    </button>
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

                <!-- Search Bar -->
                <div class="max-w-2xl mx-auto">
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 shadow-lg bg-white/80 backdrop-blur-sm border-0">
                        <form class="flex flex-col md:flex-row gap-4" method="GET" action="search.php">
                            <div class="flex-1 relative">
                                <i data-lucide="map-pin" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4"></i>
                                <input 
                                    type="text" 
                                    name="location"
                                    placeholder="Enter your location" 
                                    class="flex h-12 w-full rounded-md border border-gray-200 bg-background px-3 py-2 pl-10 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 focus:border-orange-500"
                                />
                            </div>
                            <div class="flex-1 relative">
                                <i data-lucide="calendar" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4"></i>
                                <input 
                                    type="text" 
                                    name="date"
                                    placeholder="When do you need care?" 
                                    class="flex h-12 w-full rounded-md border border-gray-200 bg-background px-3 py-2 pl-10 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 focus:border-orange-500"
                                />
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-12 px-8 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                Find Sitters
                            </button>
                        </form>
                    </div>
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
                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 flex items-center gap-2">
                    View All <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($featuredSitters as $sitter): ?>
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
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
                                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-green-500 hover:bg-green-600 text-white">
                                        <div class="w-2 h-2 bg-white rounded-full mr-1"></div>
                                        Available
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground">
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
                                            <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground">
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
                                    <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
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
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
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
                                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 
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
                                    <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-destructive text-destructive-foreground">
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
                        <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary text-secondary-foreground w-fit">
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
                            <img src="Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-xl font-semibold brand-font">pawhabilin</span>
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

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2025 <span class="brand-font">pawhabilin</span> Philippines. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>