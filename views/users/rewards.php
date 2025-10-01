<?php
// Unified session + header pattern like other user pages
require_once __DIR__ . '/../../utils/session.php';
session_start_if_needed();

// Existing session structure across app stores user info in $_SESSION['user'] (see get_current_user_session)
$__sessionUser = get_current_user_session();
if (!$__sessionUser) {
    // keep relative path consistent with other views (redirect back to root login)
    header('Location: ../../login.php');
    exit();
}

// Mock user data (in real app, fetch from database)
$user = [
    'name' => 'Maria Santos',
    'email' => 'maria.santos@email.com',
    'points' => 1250,
    'member_since' => '2023-06-15'
];

// Handle form submissions
$success_message = '';
$error_message = '';
$processing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['redeem_code'])) {
        $code = trim($_POST['voucher_code']);
        
        // Simulate code validation
        if (empty($code)) {
            $error_message = 'Please enter a valid reward code.';
        } elseif (strlen($code) < 6) {
            $error_message = 'Reward code must be at least 6 characters long.';
        } else {
            // Mock successful redemption
            $success_message = 'Congratulations! You earned 100 PawPoints with code: ' . htmlspecialchars($code);
            $user['points'] += 100; // Add points for demo
        }
    }
}

// Mock user rewards data
$user_rewards = [
    [
        'id' => 1,
        'title' => '20% Off Grooming Services',
        'description' => 'Get 20% discount on all grooming appointments',
        'type' => 'discount',
        'value' => '20%',
        'points_cost' => 500,
        'expires' => '2024-03-15',
        'status' => 'active',
        'code' => 'GROOM20'
    ],
    [
        'id' => 2,
        'title' => 'Free Pet Toy Bundle',
        'description' => 'Complimentary toy bundle for your furry friend',
        'type' => 'freebie',
        'value' => '₱450',
        'points_cost' => 300,
        'expires' => '2024-02-28',
        'status' => 'used',
        'code' => 'TOYS300'
    ],
    [
        'id' => 3,
        'title' => '₱200 Shop Credit',
        'description' => 'Store credit for any pet supplies purchase',
        'type' => 'credit',
        'value' => '₱200',
        'points_cost' => 400,
        'expires' => '2024-04-10',
        'status' => 'active',
        'code' => 'CREDIT200'
    ],
    [
        'id' => 4,
        'title' => 'Premium Subscription (1 Month)',
        'description' => 'One month of premium features access',
        'type' => 'subscription',
        'value' => '₱299',
        'points_cost' => 600,
        'expires' => '2024-05-01',
        'status' => 'redeemed',
        'code' => 'PREMIUM1M'
    ]
];

// Available rewards to purchase with points
$available_rewards = [
    [
        'id' => 5,
        'title' => '15% Off Vet Consultation',
        'description' => 'Save on your next veterinary appointment',
        'type' => 'discount',
        'value' => '15%',
        'points_cost' => 300,
        'stock' => 'unlimited'
    ],
    [
        'id' => 6,
        'title' => '₱100 Shop Voucher',
        'description' => 'Discount voucher for pet supplies',
        'type' => 'credit',
        'value' => '₱100',
        'points_cost' => 200,
        'stock' => 'unlimited'
    ],
    [
        'id' => 7,
        'title' => 'Premium Pet Food Sample',
        'description' => 'Try premium pet food brands for free',
        'type' => 'freebie',
        'value' => '₱150',
        'points_cost' => 250,
        'stock' => 'limited'
    ],
    [
        'id' => 8,
        'title' => 'Free Pet Health Checkup',
        'description' => 'Complimentary basic health examination',
        'type' => 'service',
        'value' => '₱500',
        'points_cost' => 800,
        'stock' => 'limited'
    ]
];

function getStatusColor($status) {
    switch ($status) {
        case 'active': return 'bg-green-100 text-green-800 border-green-200';
        case 'used': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'redeemed': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'expired': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

function getTypeIcon($type) {
    switch ($type) {
        case 'discount': return 'percent';
        case 'credit': return 'banknote';
        case 'freebie': return 'gift';
        case 'service': return 'stethoscope';
        case 'subscription': return 'crown';
        default: return 'gift';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards - pawhabilin</title>
    
    <!-- Tailwind CSS v4.0 -->
        <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Focus styles for accessibility */
        .focus-ring:focus {
            outline: 2px solid #f97316;
            outline-offset: 2px;
        }
        
        /* Reward card hover effects */
        .reward-card {
            transition: all 0.3s ease;
        }
        
        .reward-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Points animation */
        .points-counter {
            background: linear-gradient(135deg, #f97316, #d97706);
            background-size: 200% 200%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Code input styling */
        .code-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .mobile-padding {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
    <!-- Inject shared authenticated header (provides dropdown + assets) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mobile-padding">
        <!-- Page Header -->
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
                <a href="/" class="hover:text-gray-700 focus-ring rounded px-1">Home</a>
                <span class="mx-2" aria-hidden="true">→</span>
                <a href="/user/index.php" class="hover:text-gray-700 focus-ring rounded px-1">Dashboard</a>
                <span class="mx-2" aria-hidden="true">→</span>
                <span aria-current="page">Rewards</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl mb-2">My Rewards</h1>
                    <p class="text-gray-600">Redeem codes and claim exclusive rewards for your pets</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="points-counter text-white px-6 py-4 rounded-lg text-center">
                        <div class="text-sm opacity-90">PawPoints Balance</div>
                        <div class="text-2xl font-bold"><?php echo number_format($user['points']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="mb-6 border border-green-200 bg-green-50 p-4 rounded-lg">
            <div class="flex items-center space-x-2">
                <i data-lucide="check-circle" class="h-5 w-5 text-green-600"></i>
                <span class="text-green-700"><?php echo $success_message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="mb-6 border border-red-200 bg-red-50 p-4 rounded-lg">
            <div class="flex items-center space-x-2">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-600"></i>
                <span class="text-red-700"><?php echo $error_message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Redeem Code & Available Rewards -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Redeem Code Section -->
                <div class="bg-white border border-orange-200 rounded-lg p-6">
                    <h2 class="text-xl mb-4 flex items-center">
                        <i data-lucide="gift" class="w-5 h-5 mr-2 text-orange-600"></i>
                        Redeem Reward Code
                    </h2>
                    <p class="text-gray-600 mb-6">Enter your reward code to claim exclusive benefits and earn PawPoints.</p>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="voucher_code" class="block text-sm font-medium mb-2">Reward Code</label>
                            <input 
                                type="text" 
                                id="voucher_code" 
                                name="voucher_code"
                                placeholder="Enter your reward code"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 code-input"
                                maxlength="20"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-1">Codes are case-sensitive and usually 6-20 characters long</p>
                        </div>
                        
                        <button 
                            type="submit" 
                            name="redeem_code"
                            class="w-full md:w-auto bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-6 py-3 rounded-lg font-medium focus-ring transition-all"
                        >
                            <i data-lucide="gift" class="w-4 h-4 inline mr-2"></i>
                            Redeem Code
                        </button>
                    </form>
                </div>

                <!-- Available Rewards -->
                <div class="bg-white border border-orange-200 rounded-lg p-6">
                    <h2 class="text-xl mb-4 flex items-center">
                        <i data-lucide="shopping-bag" class="w-5 h-5 mr-2 text-orange-600"></i>
                        Available Rewards
                    </h2>
                    <p class="text-gray-600 mb-6">Spend your PawPoints to claim these exclusive rewards.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($available_rewards as $reward): ?>
                        <div class="reward-card border border-gray-200 rounded-lg p-4 hover:border-orange-200">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-100 to-amber-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="<?php echo getTypeIcon($reward['type']); ?>" class="w-5 h-5 text-orange-600"></i>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-orange-600"><?php echo $reward['points_cost']; ?> points</div>
                                    <div class="text-xs text-gray-500"><?php echo $reward['stock']; ?></div>
                                </div>
                            </div>
                            
                            <h3 class="font-medium mb-1"><?php echo htmlspecialchars($reward['title']); ?></h3>
                            <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($reward['description']); ?></p>
                            <div class="text-sm font-medium text-gray-800 mb-3">Value: <?php echo $reward['value']; ?></div>
                            
                            <button 
                                <?php echo ($user['points'] < $reward['points_cost']) ? 'disabled' : ''; ?>
                                class="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-3 rounded text-sm font-medium focus-ring transition-colors"
                            >
                                <?php echo ($user['points'] < $reward['points_cost']) ? 'Insufficient Points' : 'Claim Reward'; ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: My Rewards -->
            <div class="space-y-6">
                <!-- Points Summary -->
                <div class="bg-white border border-orange-200 rounded-lg p-6">
                    <h3 class="text-lg mb-4 flex items-center">
                        <i data-lucide="star" class="w-5 h-5 mr-2 text-orange-600"></i>
                        Points Summary
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Balance:</span>
                            <span class="font-medium text-orange-600"><?php echo number_format($user['points']); ?> points</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="text-gray-800"><?php echo date('M Y', strtotime($user['member_since'])); ?></span>
                        </div>
                        <div class="pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                Earn points by booking services, shopping, and referring friends!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- My Active Rewards -->
                <div class="bg-white border border-orange-200 rounded-lg p-6">
                    <h3 class="text-lg mb-4 flex items-center">
                        <i data-lucide="ticket" class="w-5 h-5 mr-2 text-orange-600"></i>
                        My Rewards
                    </h3>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($user_rewards as $reward): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-orange-100 to-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="<?php echo getTypeIcon($reward['type']); ?>" class="w-4 h-4 text-orange-600"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-medium text-sm"><?php echo htmlspecialchars($reward['title']); ?></h4>
                                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($reward['description']); ?></p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium border rounded <?php echo getStatusColor($reward['status']); ?> flex-shrink-0">
                                    <?php echo ucfirst($reward['status']); ?>
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center text-xs text-gray-600 mt-3">
                                <span>Code: <code class="bg-gray-100 px-1 rounded"><?php echo $reward['code']; ?></code></span>
                                <span>Expires: <?php echo date('M j, Y', strtotime($reward['expires'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Earn Points -->
        <div class="mt-12 bg-white border border-orange-200 rounded-lg p-6">
            <h2 class="text-xl mb-4 flex items-center">
                <i data-lucide="help-circle" class="w-5 h-5 mr-2 text-orange-600"></i>
                How to Earn PawPoints
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Book Services</h3>
                    <p class="text-sm text-gray-600">Earn 10 points for every ₱100 spent on pet sitting and grooming services.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="shopping-cart" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Shop Products</h3>
                    <p class="text-sm text-gray-600">Get 5 points for every ₱100 spent on pet supplies and accessories.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-violet-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Refer Friends</h3>
                    <p class="text-sm text-gray-600">Earn 500 points when a friend signs up and completes their first booking.</p>
                </div>
                
                <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-amber-50 rounded-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="star" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="font-medium mb-2">Leave Reviews</h3>
                    <p class="text-sm text-gray-600">Get 50 points for every detailed review you leave for our sitters.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Auto-uppercase code input
        document.getElementById('voucher_code').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>