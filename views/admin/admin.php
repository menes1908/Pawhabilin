<?php
session_start();
require_once __DIR__ . '/../../database.php';

$admin = [
    'users_id' => null,
    'users_firstname' => '',
    'users_lastname' => '',
    'users_username' => '',
    'users_email' => '',
    'users_image_url' => ''
];

$adminId = isset($_SESSION['users_id']) ? intval($_SESSION['users_id']) : null;
if (!$adminId && !empty($_SESSION['users_email'])) {
    if ($stmt = mysqli_prepare($connections, "SELECT users_id FROM users WHERE users_email = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 's', $_SESSION['users_email']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $foundId);
        if (mysqli_stmt_fetch($stmt)) { $adminId = intval($foundId); }
        mysqli_stmt_close($stmt);
    }
}

if ($adminId) {
    if ($stmt = mysqli_prepare($connections, "SELECT users_id, users_firstname, users_lastname, users_username, users_email, users_image_url, users_role FROM users WHERE users_id = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'i', $adminId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $fn, $ln, $un, $em, $img, $role);
        if (mysqli_stmt_fetch($stmt)) {
            $admin = [
                'users_id' => $uid,
                'users_firstname' => $fn,
                'users_lastname' => $ln,
                'users_username' => $un,
                'users_email' => $em,
                'users_image_url' => $img ?? ''
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

// If still no admin id, fallback to first admin-role user
if (!$adminId) {
    if ($stmt = mysqli_prepare($connections, "SELECT users_id, users_firstname, users_lastname, users_username, users_email, users_image_url FROM users WHERE users_role = '1' ORDER BY users_id ASC LIMIT 1")) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $fn, $ln, $un, $em, $img);
        if (mysqli_stmt_fetch($stmt)) {
            $admin = [
                'users_id' => $uid,
                'users_firstname' => $fn,
                'users_lastname' => $ln,
                'users_username' => $un,
                'users_email' => $em,
                'users_image_url' => $img ?? ''
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

$admin_fullname = trim(($admin['users_firstname'] ?? '') . ' ' . ($admin['users_lastname'] ?? '')) ?: 'Admin User';
$admin_initial = strtoupper(substr(($admin['users_firstname'] ?? '') !== '' ? $admin['users_firstname'] : ($admin['users_username'] ?? 'A'), 0, 1));

function resolveImageUrl($path) {
    if (!$path) return '';
    if (preg_match('/^https?:/i', $path)) return $path;
    if (strpos($path, '/') === 0) return $path;
    if (strpos($path, '../') === 0) return $path;
    return '../../' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - pawhabilin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link href="styles/globals.css" rel="stylesheet">
    
    <style>
        .chart-bar {
            transition: all 0.3s ease;
        }
        
        .chart-bar:hover {
            transform: scaleY(1.1);
        }
        
        .stats-card {
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Admin Dashboard Container -->
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed left-0 top-0 h-full bg-white border-r border-gray-200 sidebar-transition z-40 w-16" 
             onmouseenter="expandSidebar()" onmouseleave="collapseSidebar()">
            
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <div id="sidebarLogoExpanded" class="hidden items-center gap-2 px-4">
                    <div class="w-8 h-8 rounded-lg overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="pawhabilin Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="font-semibold text-orange-600">pawhabilin</span>
                    <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded-full">Admin</span>
                </div>
                <div id="sidebarLogoCollapsed" class="w-8 h-8 rounded-lg overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="pawhabilin Logo" class="w-full h-full object-contain">
                </div>
            </div>

            <!-- Toggle Lock Button -->
            <div id="sidebarToggle" class="hidden px-4 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Lock Sidebar</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="sidebarLock" class="sr-only peer" onchange="toggleSidebarLock()">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-2 space-y-1">
                <button onclick="setActiveSection('dashboard')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="dashboard">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Dashboard</span>
                </button>
                <button onclick="setActiveSection('products')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="products">
                    <i data-lucide="package" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Products</span>
                </button>
                <button onclick="setActiveSection('sitters')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="sitters">
                    <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Pet Sitters</span>
                </button>
                <button onclick="setActiveSection('appointments')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="appointments">
                    <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Appointments</span>
                </button>
                <button onclick="setActiveSection('pets')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="pets">
                    <i data-lucide="paw-print" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Pet Owners</span>
                </button>
                <button onclick="setActiveSection('subscribers')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="subscribers">
                    <i data-lucide="bell" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Subscribers</span>
                </button>
                <button onclick="setActiveSection('settings')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="settings">
                    <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Settings</span>
                </button>
            </nav>

            <!-- Admin Info -->
            <div id="adminInfo" class="hidden absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold text-sm overflow-hidden">
                        <?php if (!empty($admin['users_image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($admin['users_image_url']); ?>" alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars($admin_initial); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($admin['users_username'] ?: $admin_fullname); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($admin['users_email'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div id="mainContent" class="flex-1 content-transition ml-16">
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebarLock()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="menu" class="w-4 h-4"></i>
                    </button>
                    <div class="relative w-80">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                        <input type="text" placeholder="Search anything..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button class="relative p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs flex items-center justify-center text-white font-semibold">3</div>
                    </button>
                    <button class="p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                    </button>
                    <div id="profileMenuWrapper" class="relative">
                        <button id="profileButton" class="w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold focus:outline-none bg-gradient-to-br from-orange-400 to-amber-500 overflow-hidden">
                            <?php if (!empty($admin['users_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars(resolveImageUrl($admin['users_image_url'])); ?>" alt="Avatar" class="w-full h-full object-cover object-center">
                            <?php else: ?>
                                <?php echo htmlspecialchars($admin_initial); ?>
                            <?php endif; ?>
                        </button>
                        <div id="profileMenu" class="profile-menu hidden absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                            <div class="p-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                    <?php if (!empty($admin['users_image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars(resolveImageUrl($admin['users_image_url'])); ?>" alt="Admin Avatar" class="w-full h-full object-cover object-center">
                                    <?php else: ?>
                                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($admin_initial); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($admin_fullname); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($admin['users_email'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="p-2">
                                <a href="../users/logout.php" class="block w-full text-left px-3 py-2 rounded-md text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="space-y-6">
                    <!-- Dashboard Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                                Dashboard Overview
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Welcome back! Here's what's happening with pawhabilin today.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="timeFilter" onchange="updateTimeFilter()" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="annual">Annual</option>
                            </select>
                            <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition-all duration-200">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="stats-card bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-orange-600 font-medium">Total Revenue</p>
                                        <p id="totalRevenue" class="text-2xl font-bold text-orange-700">₱342,680</p>
                                        <p class="text-xs text-orange-600 mt-1">+15.7% from last period</p>
                                    </div>
                                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                                        <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-blue-600 font-medium">Transactions</p>
                                        <p id="totalTransactions" class="text-2xl font-bold text-blue-700">1,247</p>
                                        <p class="text-xs text-blue-600 mt-1">Active bookings</p>
                                    </div>
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                        <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-green-600 font-medium">Pet Sitters</p>
                                        <p class="text-2xl font-bold text-green-700">2</p>
                                        <p class="text-xs text-green-600 mt-1">Active providers</p>
                                    </div>
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card bg-gradient-to-br from-purple-50 to-violet-50 border border-purple-200 rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-purple-600 font-medium">Pet Owners</p>
                                        <p class="text-2xl font-bold text-purple-700">2</p>
                                        <p class="text-xs text-purple-600 mt-1">Registered users</p>
                                    </div>
                                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                                        <i data-lucide="paw-print" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Revenue Chart -->
                        <div class="bg-white rounded-lg border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold">Revenue Trends</h3>
                                <p class="text-sm text-gray-600">Monthly revenue overview</p>
                            </div>
                            <div class="p-6">
                                <div id="revenueChart" class="h-64 flex items-end justify-between space-x-2">
                                    <!-- Chart bars will be generated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold">Recent Activity</h3>
                                <p class="text-sm text-gray-600">Latest platform activities</p>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-100 text-orange-600">
                                            <i data-lucide="paw-print" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Pet sitting booking</p>
                                            <p class="text-xs text-gray-600">John Doe - Buddy</p>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">confirmed</span>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-blue-100 text-blue-600">
                                            <i data-lucide="heart" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Grooming booking</p>
                                            <p class="text-xs text-gray-600">Jane Smith - Whiskers</p>
                                        </div>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">pending</span>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-100 text-green-600">
                                            <i data-lucide="activity" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">Vet booking</p>
                                            <p class="text-xs text-gray-600">Mike Johnson - Max</p>
                                        </div>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">completed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Products Management</h1>
                            <p class="text-gray-600 mt-1">Manage your pet care products and inventory</p>
                        </div>
                        <button onclick="openAddProductModal()" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Product
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Product Inventory</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Search products..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Products will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sitters Section -->
                <div id="sitters-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Pet Sitters Management</h1>
                            <p class="text-gray-600 mt-1">Manage registered pet sitters and their profiles</p>
                        </div>
                        <button onclick="openAddSitterModal()" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Sitter
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Pet Sitters Directory</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Search sitters..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sitter</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experience</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialties</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="sittersTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Sitters will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Appointments Section -->
                <div id="appointments-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Appointments Management</h1>
                            <p class="text-gray-600 mt-1">Track and manage pet care appointments</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <select class="px-3 py-2 border border-gray-300 rounded-md w-40">
                                <option value="all">All Services</option>
                                <option value="pet-sitting">Pet Sitting</option>
                                <option value="grooming">Grooming</option>
                                <option value="vet">Veterinary</option>
                            </select>
                            <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                New Appointment
                            </button>
                        </div>
                    </div>

                    <!-- Service Type Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                                    <i data-lucide="paw-print" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-orange-700">Pet Sitting</p>
                                    <p class="text-sm text-orange-600">1 appointments</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i data-lucide="heart" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-blue-700">Grooming</p>
                                    <p class="text-sm text-blue-600">1 appointments</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-green-700">Veterinary</p>
                                    <p class="text-sm text-green-600">1 appointments</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">All Appointments</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Search appointments..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sitter</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Appointments will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pet Owners Section -->
                <div id="pets-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Pet Owners & Pets</h1>
                            <p class="text-gray-600 mt-1">Manage registered pet owners and their beloved pets</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Pet Owners Directory</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Search owners..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div id="petOwnersContainer" class="space-y-4">
                                <!-- Pet owners will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscribers Section -->
                <div id="subscribers-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Newsletter Subscribers</h1>
                            <p class="text-gray-600 mt-1">Manage newsletter subscriptions and email marketing</p>
                        </div>
                        <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            Send Newsletter
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">2</div>
                                <div class="text-sm text-gray-600">Total Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">2</div>
                                <div class="text-sm text-gray-600">Active Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">1</div>
                                <div class="text-sm text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Subscriber List</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Search subscribers..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribe Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="subscribersTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Subscribers will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Add New Product</h3>
                <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="addProductForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="productName" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Select category</option>
                        <option value="Dog Food">Dog Food</option>
                        <option value="Cat Food">Cat Food</option>
                        <option value="Toys">Toys</option>
                        <option value="Accessories">Accessories</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                        <input type="number" name="price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                        <input type="number" name="stock" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Add Product</button>
                    <button type="button" onclick="closeAddProductModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Sitter Modal -->
    <div id="addSitterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Add New Pet Sitter</h3>
                <button onclick="closeAddSitterModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="addSitterForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="sitterName" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="sitterEmail" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="sitterPhone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Experience (years)</label>
                    <input type="number" name="experience" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialties</label>
                    <input type="text" name="specialties" placeholder="e.g., Dogs, Cats, Birds" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Add Sitter</button>
                    <button type="button" onclick="closeAddSitterModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mock data
        const transactionData = {
            daily: {
                revenue: 15420,
                transactions: 45,
                growth: 12.5,
                chartData: [
                    { name: 'Mon', value: 2400 },
                    { name: 'Tue', value: 1398 },
                    { name: 'Wed', value: 9800 },
                    { name: 'Thu', value: 3908 },
                    { name: 'Fri', value: 4800 },
                    { name: 'Sat', value: 3800 },
                    { name: 'Sun', value: 4300 }
                ]
            },
            weekly: {
                revenue: 87550,
                transactions: 312,
                growth: 8.3,
                chartData: [
                    { name: 'W1', value: 24000 },
                    { name: 'W2', value: 13980 },
                    { name: 'W3', value: 23800 },
                    { name: 'W4', value: 25770 }
                ]
            },
            monthly: {
                revenue: 342680,
                transactions: 1247,
                growth: 15.7,
                chartData: [
                    { name: 'Jan', value: 24000 },
                    { name: 'Feb', value: 13980 },
                    { name: 'Mar', value: 29800 },
                    { name: 'Apr', value: 33908 },
                    { name: 'May', value: 48000 },
                    { name: 'Jun', value: 38000 },
                    { name: 'Jul', value: 43000 },
                    { name: 'Aug', value: 35000 },
                    { name: 'Sep', value: 42000 },
                    { name: 'Oct', value: 38000 },
                    { name: 'Nov', value: 45000 },
                    { name: 'Dec', value: 41992 }
                ]
            },
            annual: {
                revenue: 4112160,
                transactions: 14964,
                growth: 22.1,
                chartData: [
                    { name: '2021', value: 2400000 },
                    { name: '2022', value: 3200000 },
                    { name: '2023', value: 3800000 },
                    { name: '2024', value: 4112160 }
                ]
            }
        };

        const mockProducts = [
            {
                id: 1,
                name: "Premium Dog Kibble",
                category: "Dog Food",
                price: 1299,
                stock: 45,
                status: "active",
                image: "https://images.unsplash.com/photo-1572950947301-fb417712da10?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcmVtaXVtJTIwZG9nJTIwZm9vZCUyMGtpYmJsZXxlbnwxfHx8fDE3NTY1NDM3MTV8MA&ixlib=rb-4.1.0&q=80&w=1080"
            },
            {
                id: 2,
                name: "Nutritious Cat Food",
                category: "Cat Food",
                price: 899,
                stock: 23,
                status: "active",
                image: "https://images.unsplash.com/photo-1734654901149-02a9a5f7993b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYXQlMjBmb29kJTIwYm93bHxlbnwxfHx8fDE3NTY1MDk4MzR8MA&ixlib=rb-4.1.0&q=80&w=1080"
            },
            {
                id: 3,
                name: "Interactive Dog Toy",
                category: "Toys",
                price: 459,
                stock: 0,
                status: "out_of_stock",
                image: "https://images.unsplash.com/photo-1659700097688-f26bf79735af?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkb2clMjB0b3klMjByb3BlJTIwYmFsbHxlbnwxfHx8fDE3NTY0Njk1Mjd8MA&ixlib=rb-4.1.0&q=80&w=1080"
            }
        ];

        const mockSitters = [
            {
                id: 1,
                name: "Mari Santos",
                email: "mari@email.com",
                phone: "+63 912 345 6789",
                rating: 5.0,
                reviews: 24,
                location: "Cebu City",
                experience: "7 years",
                specialties: ["Dogs", "Cats", "Birds"],
                status: "active",
                image: "https://images.unsplash.com/photo-1727681200723-9513e4e3c394?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBwZXQlMjBzaXR0ZXIlMjB3aXRoJTIwZG9nfGVufDF8fHx8MTc1NjQ1MjEyOXww&ixlib=rb-4.1.0&q=80&w=1080"
            },
            {
                id: 2,
                name: "Anna Cruz",
                email: "anna@email.com",
                phone: "+63 917 234 5678",
                rating: 4.8,
                reviews: 18,
                location: "Manila",
                experience: "5 years",
                specialties: ["Dogs", "Cats"],
                status: "active",
                image: "https://images.unsplash.com/photo-1608582175768-61fefde475a9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx5b3VuZyUyMHdvbWFuJTIwd2Fsa2luZyUyMGRvZ3N8ZW58MXx8fHwxNzU2NDUyMTI5fDA&ixlib=rb-4.1.0&q=80&w=1080"
            }
        ];

        const mockAppointments = [
            {
                id: 1,
                petOwner: "John Doe",
                petName: "Buddy",
                sitter: "Mari Santos",
                service: "pet-sitting",
                date: "2025-01-25",
                time: "09:00 AM",
                duration: "4 hours",
                status: "confirmed",
                amount: 800
            },
            {
                id: 2,
                petOwner: "Jane Smith",
                petName: "Whiskers",
                sitter: "Anna Cruz",
                service: "grooming",
                date: "2025-01-26",
                time: "02:00 PM",
                duration: "2 hours",
                status: "pending",
                amount: 650
            },
            {
                id: 3,
                petOwner: "Mike Johnson",
                petName: "Max",
                sitter: "Mari Santos",
                service: "vet",
                date: "2025-01-24",
                time: "11:00 AM",
                duration: "1 hour",
                status: "completed",
                amount: 1200
            }
        ];

        const mockPetOwners = [
            {
                id: 1,
                name: "John Doe",
                email: "john@email.com",
                phone: "+63 912 345 6789",
                pets: [
                    { name: "Buddy", type: "Dog", breed: "Golden Retriever", age: 3 },
                    { name: "Luna", type: "Cat", breed: "Persian", age: 2 }
                ],
                joinDate: "2024-03-15",
                totalBookings: 12,
                status: "active"
            },
            {
                id: 2,
                name: "Jane Smith",
                email: "jane@email.com",
                phone: "+63 917 234 5678",
                pets: [
                    { name: "Whiskers", type: "Cat", breed: "Maine Coon", age: 4 }
                ],
                joinDate: "2024-06-20",
                totalBookings: 8,
                status: "active"
            }
        ];

        const mockSubscribers = [
            {
                id: 1,
                email: "subscriber1@email.com",
                subscribeDate: "2024-12-01",
                status: "active",
                source: "website"
            },
            {
                id: 2,
                email: "subscriber2@email.com",
                subscribeDate: "2024-11-28",
                status: "active",
                source: "social_media"
            }
        ];

        // Global state
        let currentActiveSection = 'dashboard';
        let sidebarExpanded = false;
        let sidebarLocked = false;
        let currentTimeFilter = 'monthly';

        // Initialize the app
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            updateChart();
            populateProducts();
            populateSitters();
            populateAppointments();
            populatePetOwners();
            populateSubscribers();
        });

        // Sidebar functions
        function expandSidebar() {
            if (!sidebarLocked) {
                sidebarExpanded = true;
                updateSidebarState();
            }
        }

        function collapseSidebar() {
            if (!sidebarLocked) {
                sidebarExpanded = false;
                updateSidebarState();
            }
        }

        function toggleSidebarLock() {
            sidebarLocked = !sidebarLocked;
            document.getElementById('sidebarLock').checked = sidebarLocked;
            updateSidebarState();
        }

        function updateSidebarState() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const logoExpanded = document.getElementById('sidebarLogoExpanded');
            const logoCollapsed = document.getElementById('sidebarLogoCollapsed');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminInfo = document.getElementById('adminInfo');
            const sidebarLabels = document.querySelectorAll('.sidebar-label');

            if (sidebarExpanded || sidebarLocked) {
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-64');
                mainContent.classList.remove('ml-16');
                mainContent.classList.add('ml-64');
                logoExpanded.classList.remove('hidden');
                logoExpanded.classList.add('flex');
                logoCollapsed.classList.add('hidden');
                sidebarToggle.classList.remove('hidden');
                adminInfo.classList.remove('hidden');
                sidebarLabels.forEach(label => label.classList.remove('hidden'));
            } else {
                sidebar.classList.add('w-16');
                sidebar.classList.remove('w-64');
                mainContent.classList.add('ml-16');
                mainContent.classList.remove('ml-64');
                logoExpanded.classList.add('hidden');
                logoExpanded.classList.remove('flex');
                logoCollapsed.classList.remove('hidden');
                sidebarToggle.classList.add('hidden');
                adminInfo.classList.add('hidden');
                sidebarLabels.forEach(label => label.classList.add('hidden'));
            }
        }

        // Section navigation
        function setActiveSection(section) {
            // Hide all sections
            const sections = ['dashboard', 'products', 'sitters', 'appointments', 'pets', 'subscribers'];
            sections.forEach(s => {
                document.getElementById(`${s}-section`).classList.add('hidden');
            });

            // Show active section
            document.getElementById(`${section}-section`).classList.remove('hidden');

            // Update sidebar active state
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('bg-gradient-to-r', 'from-orange-500', 'to-amber-600', 'text-white', 'shadow-md');
                item.classList.add('text-gray-700', 'hover:bg-gray-100');
            });

            const activeItem = document.querySelector(`[data-section="${section}"]`);
            if (activeItem) {
                activeItem.classList.add('bg-gradient-to-r', 'from-orange-500', 'to-amber-600', 'text-white', 'shadow-md');
                activeItem.classList.remove('text-gray-700', 'hover:bg-gray-100');
            }

            currentActiveSection = section;
        }

        // Time filter functions
        function updateTimeFilter() {
            const select = document.getElementById('timeFilter');
            currentTimeFilter = select.value;
            updateChart();
            updateStats();
        }

        function updateStats() {
            const data = transactionData[currentTimeFilter];
            document.getElementById('totalRevenue').textContent = `₱${data.revenue.toLocaleString()}`;
            document.getElementById('totalTransactions').textContent = data.transactions.toLocaleString();
        }

        function updateChart() {
            const data = transactionData[currentTimeFilter];
            const chartContainer = document.getElementById('revenueChart');
            const maxValue = Math.max(...data.chartData.map(d => d.value));

            chartContainer.innerHTML = data.chartData.map((item, index) => {
                const height = (item.value / maxValue) * 200;
                const minHeight = 4;
                const actualHeight = Math.max(height, minHeight);

                return `
                    <div class="flex flex-col items-center space-y-2 flex-1">
                        <div class="w-full bg-gradient-to-t from-orange-500 to-amber-400 rounded-t chart-bar" 
                             style="height: ${actualHeight}px; min-height: 4px;"></div>
                        <span class="text-xs text-gray-600">${item.name}</span>
                    </div>
                `;
            }).join('');
        }

        // Modal functions
        function openAddProductModal() {
            document.getElementById('addProductModal').classList.remove('hidden');
            document.getElementById('addProductModal').classList.add('flex');
        }

        function closeAddProductModal() {
            document.getElementById('addProductModal').classList.add('hidden');
            document.getElementById('addProductModal').classList.remove('flex');
            document.getElementById('addProductForm').reset();
        }

        function openAddSitterModal() {
            document.getElementById('addSitterModal').classList.remove('hidden');
            document.getElementById('addSitterModal').classList.add('flex');
        }

        function closeAddSitterModal() {
            document.getElementById('addSitterModal').classList.add('hidden');
            document.getElementById('addSitterModal').classList.remove('flex');
            document.getElementById('addSitterForm').reset();
        }

        // Data population functions
        function populateProducts() {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = mockProducts.map(product => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden">
                                <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p class="font-medium">${product.name}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${product.price.toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.stock}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full ${product.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${product.status === 'active' ? 'Active' : 'Out of Stock'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-red-400 hover:text-red-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        function populateSitters() {
            const tbody = document.getElementById('sittersTableBody');
            tbody.innerHTML = mockSitters.map(sitter => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                <img src="${sitter.image}" alt="${sitter.name}" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p class="font-medium">${sitter.name}</p>
                                <p class="text-sm text-gray-600">${sitter.location}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-1">
                            <p class="text-sm">${sitter.email}</p>
                            <p class="text-sm text-gray-600">${sitter.phone}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            <span>${sitter.rating}</span>
                            <span class="text-sm text-gray-600">(${sitter.reviews})</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${sitter.experience}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex gap-1">
                            ${sitter.specialties.map(specialty => `
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">${specialty}</span>
                            `).join('')}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-red-400 hover:text-red-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        function populateAppointments() {
            const tbody = document.getElementById('appointmentsTableBody');
            tbody.innerHTML = mockAppointments.map(appointment => {
                const serviceIcon = appointment.service === 'pet-sitting' ? 'paw-print' : 
                                   appointment.service === 'grooming' ? 'heart' : 'activity';
                const serviceColor = appointment.service === 'pet-sitting' ? 'text-orange-500' : 
                                    appointment.service === 'grooming' ? 'text-blue-500' : 'text-green-500';
                
                return `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="font-medium">${appointment.petOwner}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.petName}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <i data-lucide="${serviceIcon}" class="w-4 h-4 ${serviceColor}"></i>
                            <span class="capitalize">${appointment.service.replace('-', ' ')}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.sitter}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <p class="text-sm">${appointment.date}</p>
                            <p class="text-xs text-gray-600">${appointment.time}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${appointment.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full ${
                            appointment.status === 'confirmed' ? 'bg-blue-100 text-blue-800' :
                            appointment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            appointment.status === 'completed' ? 'bg-gray-100 text-gray-800' :
                            'bg-red-100 text-red-800'
                        }">
                            ${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
            lucide.createIcons();
        }

        function populatePetOwners() {
            const container = document.getElementById('petOwnersContainer');
            container.innerHTML = mockPetOwners.map(owner => `
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Owner Info -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    ${owner.name.split(' ').map(n => n[0]).join('')}
                                </div>
                                <div>
                                    <h4 class="font-semibold">${owner.name}</h4>
                                    <p class="text-sm text-gray-600">
                                        Member since ${new Date(owner.joinDate).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-gray-600"></i>
                                    <span>${owner.email}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="w-4 h-4 text-gray-600"></i>
                                    <span>${owner.phone}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div>
                                    <span class="font-medium">${owner.totalBookings}</span>
                                    <span class="text-gray-600"> bookings</span>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                            </div>
                        </div>

                        <!-- Pets Info -->
                        <div class="lg:col-span-2">
                            <h5 class="font-medium mb-3 flex items-center gap-2">
                                <i data-lucide="paw-print" class="w-4 h-4"></i>
                                Registered Pets (${owner.pets.length})
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                ${owner.pets.map(pet => `
                                    <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h6 class="font-medium">${pet.name}</h6>
                                            <span class="px-2 py-1 bg-gray-200 text-gray-800 text-xs rounded-full">
                                                ${pet.age} years old
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Type:</strong> ${pet.type}</p>
                                            <p><strong>Breed:</strong> ${pet.breed}</p>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        function populateSubscribers() {
            const tbody = document.getElementById('subscribersTableBody');
            tbody.innerHTML = mockSubscribers.map(subscriber => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${subscriber.email}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(subscriber.subscribeDate).toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">
                            ${subscriber.source.replace('_', ' ')}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-400 hover:text-gray-600">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </button>
                            <button class="p-1 text-red-400 hover:text-red-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        // Form handlers
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle product addition here
            alert('Product added successfully!');
            closeAddProductModal();
        });

        document.getElementById('addSitterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle sitter addition here
            alert('Sitter added successfully!');
            closeAddSitterModal();
        });

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            const productModal = document.getElementById('addProductModal');
            const sitterModal = document.getElementById('addSitterModal');
            
            if (e.target === productModal) {
                closeAddProductModal();
            }
            if (e.target === sitterModal) {
                closeAddSitterModal();
            }
        });

        // Profile menu interactions
        (function(){
            const btn = document.getElementById('profileButton');
            const menu = document.getElementById('profileMenu');
            const wrapper = document.getElementById('profileMenuWrapper');
            if (!btn || !menu || !wrapper) return;
            function toggleMenu(show){
                const shouldShow = typeof show === 'boolean' ? show : menu.classList.contains('hidden');
                if (shouldShow){
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
            }
            btn.addEventListener('click', (e)=>{
                e.stopPropagation();
                toggleMenu();
            });
            document.addEventListener('click', (e)=>{
                if (!wrapper.contains(e.target)) toggleMenu(false);
            });
            document.addEventListener('keydown', (e)=>{
                if (e.key === 'Escape') toggleMenu(false);
            });
        })();

        // Edit profile modal removed; no helper functions needed
    </script>
</body>
</html>