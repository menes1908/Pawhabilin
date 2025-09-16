<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Pet Sitter - pawhabilin</title>
    <link rel="stylesheet" href="styles/globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <style>
        .brand-font {
            font-family: 'La Belle Aurore', cursive;
        }
        
        /* Filter Panel Styles */
        .filter-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .filter-section {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .filter-section:last-child {
            border-bottom: none;
        }
        
        /* Experience Slider */
        .experience-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: linear-gradient(to right, #f97316, #d97706);
            outline: none;
        }
        
        .experience-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f97316, #d97706);
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .experience-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f97316, #d97706);
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        /* Age Range Slider */
        .age-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            outline: none;
        }
        
        .age-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #6b7280;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .age-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #6b7280;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Availability Grid */
        .availability-grid {
            display: grid;
            grid-template-columns: auto repeat(7, 1fr);
            gap: 8px;
            font-size: 12px;
        }
        
        .availability-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 4px;
            text-align: center;
        }
        
        .availability-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #f97316;
        }
        
        /* Custom Checkbox Styles */
        .custom-checkbox {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        
        .custom-checkbox input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .custom-checkbox .checkmark {
            width: 18px;
            height: 18px;
            background-color: #fff;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            margin-right: 8px;
            transition: all 0.2s ease;
        }
        
        .custom-checkbox input[type="checkbox"]:checked + .checkmark {
            background-color: #f97316;
            border-color: #f97316;
        }
        
        .custom-checkbox .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .custom-checkbox input[type="checkbox"]:checked + .checkmark:after {
            display: block;
        }
        
        /* Rate Input */
        .rate-input {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }
        
        .rate-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        /* Filter Button Styles */
        .filter-btn {
            background: linear-gradient(135deg, #f97316, #d97706);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2);
        }
        
        .filter-btn:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        
        .filter-btn.secondary {
            background: #fff;
            color: #374151;
            border: 2px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn.secondary:hover {
            border-color: #f97316;
            color: #f97316;
            transform: translateY(-1px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .availability-grid {
                font-size: 10px;
                gap: 4px;
            }
            
            .availability-cell {
                padding: 6px 2px;
            }
            
            .filter-panel {
                margin: 0 16px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
     <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-24 h-24 rounded-lg overflow-hidden flex items-center justify-center" style="width:77px; height:77px;">
                        <img src="./pictures/Pawhabilin logo.png" alt="Pawhabilin Logo" class="w-full h-full object-contain" />
                    </div>
                    <a href="index.php" class="text-xl font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent" style="font-family: 'La Lou Big', cursive;">
                        Pawhabilin
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <button onclick="window.location.href='find-sitter.php'" class="...">Find a Pet Sitter</button>
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


    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filter Sidebar -->
            <div class="lg:w-1/3">
                <div class="filter-panel sticky top-24">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Find Your Perfect Pet Sitter</h2>
                        <p class="text-gray-600 text-sm">Use filters to find the ideal match for your pet</p>
                    </div>
                    
                    <!-- Experience Filter -->
                    <div class="filter-section">
                        <h3 class="font-semibold text-gray-800 mb-4">Experience</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>0 years</span>
                                <span>10+ years</span>
                            </div>
                            <input type="range" min="0" max="10" value="2" class="experience-slider" id="experienceRange">
                            <div class="text-center">
                                <span class="text-orange-600 font-medium" id="experienceValue">2+ years of experience</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Verification Filter -->
                    <div class="filter-section">
                        <h3 class="font-semibold text-gray-800 mb-4">Verification</h3>
                        <div class="space-y-3">
                            <label class="custom-checkbox">
                                <input type="checkbox" name="verification[]" value="background-check">
                                <span class="checkmark"></span>
                                <span class="text-sm text-gray-700">Background Check Verified</span>
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" name="verification[]" value="id-verified">
                                <span class="checkmark"></span>
                                <span class="text-sm text-gray-700">Valid ID Verified</span>
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" name="verification[]" value="phone-verified">
                                <span class="checkmark"></span>
                                <span class="text-sm text-gray-700">Phone Number Verified</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- More Filters -->
                    <div class="filter-section">
                        <button class="w-full text-left" onclick="toggleMoreFilters()">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-gray-800">More Filters</h3>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="moreFiltersIcon">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        
                        <div id="moreFiltersContent" class="mt-4 space-y-6 hidden">
                            <!-- Availability -->
                            <div>
                                <h4 class="font-medium text-gray-700 mb-3">Availability</h4>
                                <div class="availability-grid">
                                    <div class="availability-cell font-medium text-gray-600"></div>
                                    <div class="availability-cell font-medium text-gray-600">Mo</div>
                                    <div class="availability-cell font-medium text-gray-600">Tu</div>
                                    <div class="availability-cell font-medium text-gray-600">We</div>
                                    <div class="availability-cell font-medium text-gray-600">Th</div>
                                    <div class="availability-cell font-medium text-gray-600">Fr</div>
                                    <div class="availability-cell font-medium text-gray-600">Sa</div>
                                    <div class="availability-cell font-medium text-gray-600">Su</div>
                                    
                                    <div class="availability-cell font-medium text-gray-600">Morning</div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="mon-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="tue-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="wed-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="thu-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="fri-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sat-morning"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sun-morning"></div>
                                    
                                    <div class="availability-cell font-medium text-gray-600">Afternoon</div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="mon-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="tue-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="wed-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="thu-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="fri-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sat-afternoon"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sun-afternoon"></div>
                                    
                                    <div class="availability-cell font-medium text-gray-600">Evening</div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="mon-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="tue-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="wed-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="thu-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="fri-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sat-evening"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sun-evening"></div>
                                    
                                    <div class="availability-cell font-medium text-gray-600">Night</div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="mon-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="tue-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="wed-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="thu-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="fri-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sat-night"></div>
                                    <div class="availability-cell"><input type="checkbox" class="availability-checkbox" name="availability[]" value="sun-night"></div>
                                </div>
                            </div>
                            
                            <!-- Age Range -->
                            <div>
                                <h4 class="font-medium text-gray-700 mb-3">Age</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm text-gray-600">
                                        <span>18 years</span>
                                        <span>95+ years</span>
                                    </div>
                                    <input type="range" min="18" max="95" value="35" class="age-slider" id="ageRange">
                                    <div class="text-center">
                                        <span class="text-gray-700 font-medium" id="ageValue">Up to 35 years old</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Maximum Rate -->
                            <div>
                                <label class="font-medium text-gray-700 mb-3 block">Maximum rate per hour</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                    <input type="number" placeholder="Enter maximum rate" class="rate-input w-full pl-8" min="0" max="5000" step="50">
                                </div>
                            </div>
                            
                            <!-- Extra Information -->
                            <div>
                                <h4 class="font-medium text-gray-700 mb-3">Extra Information</h4>
                                <div class="space-y-3">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="extra[]" value="first-aid">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">First aid certification</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="extra[]" value="non-smoker">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Non-smoker</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="extra[]" value="has-children">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Has children</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="extra[]" value="driving-license">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Has a driving license</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="extra[]" value="has-car">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Has a car</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Spoken Languages -->
                            <div>
                                <h4 class="font-medium text-gray-700 mb-3">Spoken Language</h4>
                                <div class="space-y-3">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="english">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">English</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="korean">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Korean</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="filipino">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Filipino</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="spanish">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Spanish</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="tagalog">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Tagalog</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="german">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">German</span>
                                    </label>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="languages[]" value="cebuano">
                                        <span class="checkmark"></span>
                                        <span class="text-sm text-gray-700">Cebuano</span>
                                    </label>
                                    <button class="text-orange-600 text-sm hover:text-orange-700 transition-colors mt-2">Show all</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results Button -->
                    <div class="p-6">
                        <button class="filter-btn w-full">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Show Results
                        </button>
                        <button class="filter-btn secondary w-full mt-3">
                            Clear All Filters
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Results Section -->
            <div class="lg:w-2/3">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Pet Sitters Near You</h1>
                            <p class="text-gray-600">Found 127 pet sitters in your area</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Sort by:</span>
                            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option>Recommended</option>
                                <option>Highest Rated</option>
                                <option>Lowest Price</option>
                                <option>Nearest</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sample Sitter Cards -->
                <div class="space-y-6">
                    <?php
                    $sitters = [
                        [
                            'name' => 'Maria Santos',
                            'age' => 24,
                            'rating' => 4.9,
                            'reviews' => 127,
                            'rate' => 15,
                            'distance' => '0.8 km away',
                            'image' => 'https://images.unsplash.com/photo-1727681200723-9513e4e3c394?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBwZXQlMjBzaXR0ZXIlMjB3aXRoJTIwZG9nfGVufDF8fHx8MTc1NjQ1MjEyOXww&ixlib=rb-4.1.0&q=80&w=1080',
                            'badges' => ['Background Check', 'Pet First Aid'],
                            'available' => true,
                            'bio' => 'Experienced pet sitter with a passion for animal care. I have 3+ years of experience caring for dogs and cats of all sizes.',
                            'languages' => ['English', 'Filipino', 'Tagalog'],
                            'experience' => '3+ years'
                        ],
                        [
                            'name' => 'Anna Cruz',
                            'age' => 28,
                            'rating' => 5.0,
                            'reviews' => 89,
                            'rate' => 18,
                            'distance' => '1.2 km away',
                            'image' => 'https://images.unsplash.com/photo-1608582175768-61fefde475a9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx5b3VuZyUyMHdvbWFuJTIwd2Fsa2luZyUyMGRvZ3N8ZW58MXx8fHwxNzU2NDUyMTI5fDA&ixlib=rb-4.1.0&q=80&w=1080',
                            'badges' => ['Veterinary Student', '5+ Years'],
                            'available' => false,
                            'bio' => 'Veterinary student with extensive knowledge in animal behavior and health. Specializing in senior pets and special needs animals.',
                            'languages' => ['English', 'Korean', 'Filipino'],
                            'experience' => '5+ years'
                        ],
                        [
                            'name' => 'Sophie Lee',
                            'age' => 22,
                            'rating' => 4.8,
                            'reviews' => 156,
                            'rate' => 16,
                            'distance' => '2.1 km away',
                            'image' => 'https://images.unsplash.com/photo-1576761525241-e5c3b8202cf8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx5b3VuZyUyMHdvbWFuJTIwYmFieXNpdHRlciUyMHNtaWxpbmd8ZW58MXx8fHwxNzU2NDUxNzQ4fDA&ixlib=rb-4.1.0&q=80&w=1080',
                            'badges' => ['Student', 'Small Pets'],
                            'available' => true,
                            'bio' => 'University student who loves spending time with pets. Great with small dogs, cats, and rabbits. Very reliable and caring.',
                            'languages' => ['English', 'Cebuano', 'Filipino'],
                            'experience' => '2+ years'
                        ]
                    ];
                    
                    foreach ($sitters as $sitter) {
                        echo '<div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">';
                        echo '<div class="md:flex">';
                        
                        // Image section
                        echo '<div class="md:w-1/3 relative">';
                        echo '<div class="aspect-square md:aspect-auto md:h-full overflow-hidden">';
                        echo '<img src="' . $sitter['image'] . '" alt="' . $sitter['name'] . '" class="w-full h-full object-cover">';
                        echo '</div>';
                        
                        // Availability badge
                        if ($sitter['available']) {
                            echo '<div class="absolute top-3 left-3">';
                            echo '<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-medium">';
                            echo '<span class="w-2 h-2 bg-white rounded-full inline-block mr-1"></span>Available';
                            echo '</span>';
                            echo '</div>';
                        } else {
                            echo '<div class="absolute top-3 left-3">';
                            echo '<span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">Busy</span>';
                            echo '</div>';
                        }
                        
                        // Heart button
                        echo '<button class="absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all">';
                        echo '<svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>';
                        echo '</svg>';
                        echo '</button>';
                        echo '</div>';
                        
                        // Content section
                        echo '<div class="md:w-2/3 p-6">';
                        echo '<div class="flex justify-between items-start mb-4">';
                        echo '<div>';
                        echo '<h3 class="text-xl font-semibold text-gray-800">' . $sitter['name'] . ', ' . $sitter['age'] . '</h3>';
                        echo '<p class="text-gray-600 flex items-center mt-1">';
                        echo '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>';
                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>';
                        echo '</svg>';
                        echo $sitter['distance'];
                        echo '</p>';
                        echo '</div>';
                        echo '<div class="text-right">';
                        echo '<div class="text-2xl font-bold text-orange-600">₱' . $sitter['rate'] . '</div>';
                        echo '<div class="text-sm text-gray-600">per hour</div>';
                        echo '</div>';
                        echo '</div>';
                        
                        // Rating
                        echo '<div class="flex items-center mb-4">';
                        echo '<div class="flex items-center">';
                        echo '<svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20">';
                        echo '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>';
                        echo '</svg>';
                        echo '<span class="text-lg font-semibold ml-1">' . $sitter['rating'] . '</span>';
                        echo '<span class="text-gray-600 ml-2">(' . $sitter['reviews'] . ' reviews)</span>';
                        echo '</div>';
                        echo '</div>';
                        
                        // Bio
                        echo '<p class="text-gray-700 mb-4">' . $sitter['bio'] . '</p>';
                        
                        // Badges and info
                        echo '<div class="space-y-3 mb-4">';
                        echo '<div class="flex flex-wrap gap-2">';
                        foreach ($sitter['badges'] as $badge) {
                            echo '<span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">' . $badge . '</span>';
                        }
                        echo '</div>';
                        
                        echo '<div class="flex items-center gap-4 text-sm text-gray-600">';
                        echo '<span><strong>Experience:</strong> ' . $sitter['experience'] . '</span>';
                        echo '<span><strong>Languages:</strong> ' . implode(', ', $sitter['languages']) . '</span>';
                        echo '</div>';
                        echo '</div>';
                        
                        // Action buttons
                        echo '<div class="flex gap-3">';
                        echo '<button class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-3 px-6 rounded-lg font-medium transition-all flex items-center justify-center">';
                        echo '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>';
                        echo '</svg>';
                        echo 'Message';
                        echo '</button>';
                        echo '<button class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-medium hover:border-orange-500 hover:text-orange-600 transition-all">';
                        echo 'View Profile';
                        echo '</button>';
                        echo '</div>';
                        
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <!-- Pagination -->
                <div class="mt-12 flex justify-center">
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-2 text-gray-500 hover:text-gray-700 transition-colors">Previous</button>
                        <button class="px-3 py-2 bg-orange-500 text-white rounded-lg">1</button>
                        <button class="px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">2</button>
                        <button class="px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">3</button>
                        <span class="px-3 py-2 text-gray-500">...</span>
                        <button class="px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">8</button>
                        <button class="px-3 py-2 text-gray-500 hover:text-gray-700 transition-colors">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Experience slider functionality
        const experienceSlider = document.getElementById('experienceRange');
        const experienceValue = document.getElementById('experienceValue');
        
        experienceSlider.addEventListener('input', function() {
            const value = this.value;
            if (value == 0) {
                experienceValue.textContent = 'No experience required';
            } else if (value >= 10) {
                experienceValue.textContent = '10+ years of experience';
            } else {
                experienceValue.textContent = value + '+ years of experience';
            }
        });
        
        // Age slider functionality
        const ageSlider = document.getElementById('ageRange');
        const ageValue = document.getElementById('ageValue');
        
        ageSlider.addEventListener('input', function() {
            const value = this.value;
            if (value >= 95) {
                ageValue.textContent = '95+ years old';
            } else {
                ageValue.textContent = 'Up to ' + value + ' years old';
            }
        });
        
        // More filters toggle
        function toggleMoreFilters() {
            const content = document.getElementById('moreFiltersContent');
            const icon = document.getElementById('moreFiltersIcon');
            
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }
        
        // Form submission handling
        document.querySelector('.filter-btn').addEventListener('click', function() {
            // Here you would typically submit the form or make an AJAX request
            alert('Filters applied! Results will be updated.');
        });
        
        // Clear filters
        document.querySelector('.filter-btn.secondary').addEventListener('click', function() {
            // Reset all form inputs
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[type="range"]').forEach(slider => {
                if (slider.id === 'experienceRange') {
                    slider.value = 0;
                    experienceValue.textContent = 'No experience required';
                } else if (slider.id === 'ageRange') {
                    slider.value = 95;
                    ageValue.textContent = '95+ years old';
                }
            });
            document.querySelectorAll('input[type="number"]').forEach(input => input.value = '');
        });
    </script>
</body>
</html>