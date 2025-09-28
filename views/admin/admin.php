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

// ---- Subscribers (Subscription) Stats + List Preparation ----
// We treat any record in user_subscriptions as a subscriber. Active defined by status 'active' and (no end date or future end date).
$subscriberStats = [ 'total' => 0, 'active' => 0, 'this_month' => 0 ];
$subscribersList = [];
if(isset($connections) && $connections){
    // Total distinct users subscribed at least once
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['total'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Active distinct users
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions WHERE us_status='active' AND (us_end_date IS NULL OR us_end_date >= NOW())")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['active'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Started this month (distinct)
    if($res = mysqli_query($connections, "SELECT COUNT(DISTINCT users_id) c FROM user_subscriptions WHERE YEAR(us_start_date)=YEAR(CURDATE()) AND MONTH(us_start_date)=MONTH(CURDATE())")){
        if($row = mysqli_fetch_assoc($res)) $subscriberStats['this_month'] = (int)$row['c'];
        mysqli_free_result($res);
    }
    // Latest subscription per user for table
    $sqlSubs = "SELECT u.users_id, CONCAT(COALESCE(u.users_firstname,''),' ',COALESCE(u.users_lastname,'')) full_name, u.users_email, us.us_id, us.us_start_date, us.us_end_date, us.us_status
                FROM user_subscriptions us
                JOIN users u ON u.users_id = us.users_id
                INNER JOIN (
                    SELECT users_id, MAX(us_start_date) latest_start
                    FROM user_subscriptions
                    GROUP BY users_id
                ) latest ON latest.users_id = us.users_id AND latest.latest_start = us.us_start_date
                ORDER BY us.us_start_date DESC";
    if($res = mysqli_query($connections, $sqlSubs)){
        while($row = mysqli_fetch_assoc($res)) $subscribersList[] = $row;
        mysqli_free_result($res);
    }
}

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
    <title>Admin Dashboard - Pawhabilin</title>
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

        /* Smooth scaling/transition for sidebar + content shift */
        .sidebar-transition {
            transition: width 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
            transform-origin: left center;
            will-change: width, transform;
        }
        .sidebar-transition:hover {
            transform: scaleX(1.01);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .content-transition {
            transition: margin-left 0.22s ease;
        }

        /* Smooth hover for sidebar nav */
        .sidebar-item {
            position: relative;
            overflow: hidden;
            transition: background-color 0.25s ease, color 0.25s ease, transform 0.2s ease;
        }
        .sidebar-item i {
            transition: transform 0.2s ease, color 0.25s ease;
        }
        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0.375rem; /* ~px-1.5 from left edge */
            top: 18%;
            bottom: 18%;
            width: 3px;
            border-radius: 9999px;
            background: linear-gradient(180deg, #f97316, #f59e0b); /* orange-500 to amber-500 */
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.25s ease;
        }
        .sidebar-item:hover {
            background-color: #fff7ed; /* orange-50 */
            color: #ea580c; /* orange-600 */
        }
        .sidebar-item:hover::before {
            transform: scaleY(1);
        }
        .sidebar-item:hover i {
            color: #ea580c; /* orange-600 */
            transform: translateX(2px);
        }
        /* Preserve active state styling set via JS: show accent bar */
        .sidebar-item.bg-gradient-to-r::before {
            transform: scaleY(1);
            background: linear-gradient(180deg, #f97316, #f59e0b);
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
                        <img src="<?php echo htmlspecialchars(resolveImageUrl('pictures/Pawhabilin logo.png')); ?>" alt="Pawhabilin Logo" class="w-full h-full object-cover">
                    </div>
                    <span class="font-semibold text-orange-600">Pawhabilin</span>
                    <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded-full">Admin</span>
                </div>
                <div id="sidebarLogoCollapsed" class="w-8 h-8 rounded-lg overflow-hidden">
                    <img src="<?php echo htmlspecialchars(resolveImageUrl('pictures/Pawhabilin logo.png')); ?>" alt="Pawhabilin Logo" class="w-full h-full object-cover">
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
                <button onclick="setActiveSection('orders')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="orders">
                    <i data-lucide="shopping-bag" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Orders</span>
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
    <div id="mainContent" class="flex-1 content-transition ml-16 h-screen overflow-y-auto">
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
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold">Product Inventory</h3>
                                    <p class="text-sm text-gray-600">Filter by pet types, category, status and stock</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                        <input id="productsSearch" type="text" placeholder="Search products..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-72" />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Pet Types</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Dog"> Dog</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Cat"> Cat</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Bird"> Bird</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Fish"> Fish</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="petType" value="Small Pet"> Small Pet</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Category</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="food"> Food</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="accessory"> Accessories</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="necessity"> Necessity</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="category" value="toy"> Toys</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Status</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="active" value="1"> Active</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="active" value="0"> Inactive</label>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-2">Stock</p>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="stock" value="in"> In stock (>=1)</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" class="form-checkbox" name="stock" value="out"> Out of stock (0)</label>
                                    </div>
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
                                    <?php
                                    // Server-render products from DB
                                    $rows = [];
                                    if (isset($connections) && $connections) {
                                        $q = "SELECT products_id, products_name, products_pet_type, products_category, products_price, products_stock, products_image_url, products_active FROM products ORDER BY products_created_at DESC, products_id DESC";
                                        if ($res = mysqli_query($connections, $q)) {
                                            while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
                                            mysqli_free_result($res);
                                        }
                                    }
                                    $catLabel = function($c){
                                        switch ($c) {
                                            case 'food': return 'Food';
                                            case 'accessory': return 'Accessories';
                                            case 'necessity': return 'Grooming';
                                            case 'toy': return 'Treats';
                                            default: return htmlspecialchars((string)$c);
                                        }
                                    };
                                    if (empty($rows)):
                                    ?>
                                        <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">No products found.</td></tr>
                                    <?php else: foreach ($rows as $p): ?>
                                        <tr data-id="<?php echo (int)$p['products_id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($p['products_name'])); ?>"
                                            data-pet-type="<?php echo htmlspecialchars($p['products_pet_type'] ?? ''); ?>"
                                            data-category="<?php echo htmlspecialchars($p['products_category'] ?? ''); ?>"
                                            data-active="<?php echo (int)($p['products_active'] ?? 0); ?>"
                                            data-stock="<?php echo is_numeric($p['products_stock']) ? (int)$p['products_stock'] : 0; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                                        <?php if (!empty($p['products_image_url'])): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveImageUrl($p['products_image_url'])); ?>" alt="<?php echo htmlspecialchars($p['products_name']); ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($p['products_name']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $catLabel($p['products_category']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format((float)$p['products_price'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars((string)$p['products_stock']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo (int)$p['products_active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo (int)$p['products_active'] === 1 ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <button class="p-1 text-gray-400 hover:text-gray-600 btn-edit" data-action="edit" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </button>
                                                    <button class="p-1 text-red-400 hover:text-red-600 btn-delete" data-action="delete" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="productsPagination" class="p-4 border-t border-gray-100 flex items-center justify-between hidden">
                            <div id="productsPageInfo" class="text-sm text-gray-600"></div>
                            <div class="flex items-center gap-2">
                                <button id="productsPrev" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Prev</button>
                                <div id="productsPageNums" class="flex items-center gap-1"></div>
                                <button id="productsNext" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section (Delivery Only) -->
                <div id="orders-section" class="space-y-6 hidden">
                    <?php
                    $orders = [];
                    $itemsByTxn = [];
                    if(isset($connections) && $connections){
                        $sql = "SELECT t.transactions_id, t.users_id, u.users_firstname, u.users_lastname, t.transactions_amount, t.transactions_payment_method, t.transactions_created_at,
                                        d.deliveries_delivery_status, d.deliveries_estimated_delivery_date, d.deliveries_actual_delivery_date, d.deliveries_recipient_signature,
                                        l.location_address_line1, l.location_address_line2, l.location_city, l.location_province, l.location_barangay
                                FROM transactions t
                                JOIN users u ON u.users_id = t.users_id
                                LEFT JOIN deliveries d ON d.transactions_id = t.transactions_id
                                LEFT JOIN locations l ON l.location_id = d.location_id
                                WHERE t.transactions_type='product'
                                ORDER BY t.transactions_created_at DESC, t.transactions_id DESC";
                        if($res = mysqli_query($connections,$sql)){
                            while($r = mysqli_fetch_assoc($res)){ $orders[] = $r; }
                            mysqli_free_result($res);
                        }
                        if(!empty($orders)){
                            $ids = array_column($orders,'transactions_id');
                            $idList = implode(',', array_map('intval',$ids));
                            if($idList!==''){
                                $lineSql = "SELECT tp.transactions_id, tp.products_id, tp.tp_quantity, pr.products_name, pr.products_image_url
                                            FROM transaction_products tp
                                            JOIN products pr ON pr.products_id = tp.products_id
                                            WHERE tp.transactions_id IN ($idList)";
                                if($res2 = mysqli_query($connections,$lineSql)){
                                    while($r2 = mysqli_fetch_assoc($res2)){
                                        $tid=(int)$r2['transactions_id'];
                                        if(!isset($itemsByTxn[$tid])) $itemsByTxn[$tid]=[];
                                        $itemsByTxn[$tid][]=$r2;
                                    }
                                    mysqli_free_result($res2);
                                }
                            }
                        }
                    }
                    if(!function_exists('o_e')){ function o_e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); } }
                    ?>
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800"><i data-lucide="shopping-bag" class="w-5 h-5 text-orange-500"></i> Product Orders (Delivery)</h2>
                        <p class="text-sm text-gray-500">All product transactions with delivery details.</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-700 flex items-center gap-1"><i data-lucide="package" class="w-4 h-4 text-blue-500"></i> Orders (<?php echo count($orders); ?>)</h3>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <input id="ordersSearch" type="text" placeholder="Search buyer, item, address..." class="px-3 py-2 text-sm border border-gray-300 rounded-md w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select id="ordersStatusFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Status</option>
                                    <option value="processing">Processing</option>
                                    <option value="out_for_delivery">Out for Delivery</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <select id="ordersPaymentFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Payments</option>
                                    <option value="cod">COD</option>
                                    <option value="gcash">GCash</option>
                                    <option value="maya">Maya</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm" id="ordersTable">
                                <thead class="bg-gray-50 text-[11px] uppercase text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Buyer</th>
                                        <th class="px-4 py-3 text-left font-medium">Order Items</th>
                                        <th class="px-4 py-3 text-left font-medium">Address</th>
                                        <th class="px-4 py-3 text-left font-medium">Payment</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                        <th class="px-4 py-3 text-left font-medium">Estimated</th>
                                        <th class="px-4 py-3 text-left font-medium">Actual</th>
                                        <th class="px-4 py-3 text-left font-medium">Signature</th>
                                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody" class="divide-y divide-gray-100">
                                <?php if(empty($orders)): ?>
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No orders found.</td></tr>
                                <?php else: foreach($orders as $ord): $tid=(int)$ord['transactions_id']; $buyer=trim(($ord['users_firstname']??'').' '.($ord['users_lastname']??'')); $items=$itemsByTxn[$tid]??[]; $status=$ord['deliveries_delivery_status']??''; $addressParts=array_filter([$ord['location_address_line1']??'', $ord['location_barangay']??'', $ord['location_city']??'', $ord['location_province']??'']); $address=implode(', ',$addressParts); $eta=$ord['deliveries_estimated_delivery_date']??''; ?>
                                    <?php $itemsSearch = strtolower(implode(' ', array_map(fn($x)=>$x['products_name'],$items))); ?>
                                    <tr data-buyer="<?php echo o_e(strtolower($buyer )); ?>" data-status="<?php echo o_e($status); ?>" data-payment="<?php echo o_e(strtolower($ord['transactions_payment_method']??'')); ?>" data-address="<?php echo o_e(strtolower($address)); ?>" data-items="<?php echo o_e($itemsSearch); ?>">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-gray-800"><?php echo o_e($buyer ?: 'User #'.$ord['users_id']); ?></div>
                                            <div class="text-[11px] text-gray-500">#<?php echo $tid; ?> • ₱<?php echo number_format((float)$ord['transactions_amount'],2); ?></div>
                                            <div class="text-[10px] text-gray-400"><?php echo o_e(date('Y-m-d H:i',strtotime($ord['transactions_created_at']))); ?></div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <ul class="space-y-1 max-w-[170px]">
                                                <?php foreach($items as $it): ?>
                                                    <li class="flex items-center gap-2 text-xs">
                                                        <span class="text-gray-700 truncate" title="<?php echo o_e($it['products_name']); ?>"><?php echo o_e($it['products_name']); ?></span>
                                                        <span class="text-gray-400">x<?php echo (int)$it['tp_quantity']; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td class="px-4 py-3 text-xs max-w-[220px] truncate" title="<?php echo o_e($address); ?>"><?php echo o_e($address); ?></td>
                                        <td class="px-4 py-3 text-xs"><span class="px-2 py-1 rounded-full bg-orange-50 text-orange-600"><?php echo o_e(strtoupper($ord['transactions_payment_method']??'')); ?></span></td>
                                        <td class="px-4 py-3 text-xs"><?php if($status): ?><span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700"><?php echo o_e(ucwords(str_replace('_',' ',$status))); ?></span><?php endif; ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700"><?php echo o_e($eta); ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700"><?php echo o_e($ord['deliveries_actual_delivery_date'] ?? ''); ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-700">
                                            <?php
                                            $sig = $ord['deliveries_recipient_signature'] ?? '';
                                            $st  = strtolower($ord['deliveries_delivery_status'] ?? '');
                                            if ($sig) {
                                                echo '<span class="text-emerald-600 font-semibold">Received</span>';
                                            } elseif ($st === 'cancelled') {
                                                echo '<span class="text-red-600 font-semibold">Cancelled</span>';
                                            } else {
                                                echo '<span class="text-gray-400">Pending</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-4 py-3 text-xs">
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="text-blue-600 hover:text-blue-700 order-edit-btn" data-id="<?php echo $tid; ?>" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                <form method="post" action="../../controllers/admin/ordercontroller.php" class="inline" onsubmit="return confirm('Delete this order? This cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="transactions_id" value="<?php echo $tid; ?>">
                                                    <button class="text-red-600 hover:text-red-700" title="Delete"><i data-lucide="trash" class="w-4 h-4"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="ordersPagination" class="p-3 border-t border-gray-100 flex items-center justify-between hidden">
                            <div id="ordersPageInfo" class="text-xs text-gray-600"></div>
                            <div class="flex items-center gap-1">
                                <button id="ordersPrev" class="px-2 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50">Prev</button>
                                <div id="ordersPageNums" class="flex items-center gap-1"></div>
                                <button id="ordersNext" class="px-2 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50">Next</button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Edit Modal -->
                    <div id="orderEditModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">Edit Delivery Order</h3>
                                <button type="button" data-close-order class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                            </div>
                            <form method="post" action="../../controllers/admin/ordercontroller.php" class="space-y-4">
                                <input type="hidden" name="action" value="update_delivery">
                                <input type="hidden" name="transactions_id" id="order_edit_tid">
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Status</label>
                                    <select name="deliveries_delivery_status" id="order_edit_status" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm" required>
                                        <option value="processing">Processing</option>
                                        <option value="out_for_delivery">Out for Delivery</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-medium text-gray-600">Estimated Date</label>
                                        <input type="date" name="deliveries_estimated_delivery_date" id="order_edit_eta" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-600">Actual Date</label>
                                        <input type="date" name="deliveries_actual_delivery_date" id="order_edit_actual" class="mt-1 w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                                        <input type="checkbox" name="signature_received" id="order_edit_signature" value="1" class="rounded"> Mark as Received (Signature)
                                    </label>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" data-close-order class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                    <button type="submit" class="px-4 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                </div>
                            </form>
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
                        <button id="openAddSitter" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Sitter
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Pet Sitters Directory</h3>
                                <div class="flex items-center gap-2">
                                    <input id="sittersSearch" type="text" placeholder="Search sitters..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialties</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experience</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="sittersTableBody" class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $sitterRows = [];
                                    if (isset($connections) && $connections) {
                                        $hasVerified = false;
                                        if ($chk = @mysqli_query($connections, "SHOW COLUMNS FROM sitters LIKE 'sitters_verified'")) {
                                            $hasVerified = mysqli_num_rows($chk) > 0; mysqli_free_result($chk);
                                        }
                                        $qs = $hasVerified
                                            ? "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitters_image_url, sitters_active, years_experience, sitters_verified FROM sitters ORDER BY sitters_id DESC"
                                            : "SELECT sitters_id, sitters_name, sitters_bio, sitter_email, sitters_contact, sitter_specialty, sitters_image_url, sitters_active, years_experience, sitters_verified FROM sitters ORDER BY sitters_id DESC";
                                        if ($res = mysqli_query($connections, $qs)) {
                                            while ($r = mysqli_fetch_assoc($res)) { 
                                                if (!isset($r['sitters_verified'])) { $r['sitters_verified'] = 0; }
                                                $sitterRows[] = $r; 
                                            }
                                            mysqli_free_result($res);
                                        }
                                    }
                                    if (empty($sitterRows)):
                                    ?>
                                        <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500">No sitters found.</td></tr>
                                    <?php else: foreach ($sitterRows as $s):
                                        $email = $s['sitter_email'] ?? '';
                                        $phone = $s['sitters_contact'] ?? '';
                                        $yearsExp = isset($s['years_experience']) ? (int)$s['years_experience'] : 0;
                                        $experience = $yearsExp > 0 ? ($yearsExp . ' yrs') : '';
                                        $bioText = trim($s['sitters_bio'] ?? '');
                                        $bioFull = $bioText;
                                        $bioShort = strlen($bioText) > 60 ? substr($bioText, 0, 60) . '…' : $bioText;
                                        $specStr = $s['sitter_specialty'] ?? '';
                                        $specs = array_filter(array_map('trim', explode(',', (string)$specStr)), function($v){ return $v !== ''; });
                                        $specColorMap = [
                                            'dog' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                            'dogs' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                            'cat' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                            'cats' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                            'bird' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            'birds' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            'fish' => 'bg-cyan-100 text-cyan-800 border border-cyan-200',
                                            'fishes' => 'bg-cyan-100 text-cyan-800 border border-cyan-200',
                                            'small pet' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'small pets' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'rabbit' => 'bg-pink-100 text-pink-800 border border-pink-200',
                                            'rabbits' => 'bg-pink-100 text-pink-800 border border-pink-200',
                                            'hamster' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                            'hamsters' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                            'guinea pig' => 'bg-lime-100 text-lime-800 border border-lime-200',
                                            'guinea pigs' => 'bg-lime-100 text-lime-800 border border-lime-200',
                                            'reptile' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                            'reptiles' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                            'ferret' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                            'ferrets' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                            'exotic pet' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                                            'exotic pets' => 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                                        ];
                                    ?>
                                        <tr data-id="<?php echo (int)$s['sitters_id']; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                                        <?php if (!empty($s['sitters_image_url'])): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveImageUrl($s['sitters_image_url'])); ?>" alt="<?php echo htmlspecialchars($s['sitters_name']); ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($s['sitters_name']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <p><?php echo htmlspecialchars($email); ?></p>
                                                    <p class="text-gray-600"><?php echo htmlspecialchars($phone); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php foreach($specs as $sp): $k = strtolower($sp); $cls = $specColorMap[$k] ?? 'bg-gray-50 text-gray-700 border border-gray-200'; ?>
                                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $cls; ?>"><?php echo htmlspecialchars($sp); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php if ($bioFull !== ''): ?>
                                                    <span title="<?php echo htmlspecialchars($bioFull); ?>"><?php echo htmlspecialchars($bioShort); ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($experience ?: '-'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php $verified = isset($s['sitters_verified']) ? (int)$s['sitters_verified'] : 0; ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-1 text-xs rounded-full <?php echo (int)$s['sitters_active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo (int)$s['sitters_active'] === 1 ? 'Active' : 'Inactive'; ?></span>
                                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $verified===1 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo $verified===1 ? 'Verified' : 'Unverified'; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <button class="p-1 text-gray-400 hover:text-gray-600 btn-sitter-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                    <button class="p-1 text-red-400 hover:text-red-600 btn-sitter-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    

                    <!-- Add Sitter Modal -->
                    <div id="addSitterModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">Add Pet Sitter</h3>
                                <button type="button" id="closeAddSitter" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                            </div>
                            <form id="addSitterForm" enctype="multipart/form-data" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sitter Name</label>
                                        <input type="text" name="sitters_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Full name">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="sitters_email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="example@mail.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                        <input type="text" name="sitters_phone" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="09xxxxxxxxx" maxlength="11">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                        <input type="number" name="years_experience" min="0" step="1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="0">
                                    </div>
                                </div>
                                <!-- Specialties: categorized checkboxes with dynamic Other input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Specialties <span class="text-xs text-gray-500 font-normal">(select all that apply)</span></label>
                                    <div id="addSpecialtiesGroup" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Dog" class="rounded border-gray-300"> <span>Dog</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Cat" class="rounded border-gray-300"> <span>Cat</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Bird" class="rounded border-gray-300"> <span>Bird</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Fish" class="rounded border-gray-300"> <span>Fish</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Small Pet" class="rounded border-gray-300"> <span>Small Pet</span></label>
                                        <label class="inline-flex items-center gap-1"><input id="addOtherSpec" type="checkbox" value="Other" class="rounded border-gray-300"> <span>Other</span></label>
                                    </div>
                                    <div id="addOtherWrapper" class="mt-2 hidden">
                                        <input type="text" name="sitters_specialties_extra" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Specify other pets, comma-separated e.g. Iguana, Farm Animals">
                                        <p class="mt-1 text-xs text-gray-500">Separate multiple with commas. These will be saved along with the selected specialties.</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea name="sitters_bio" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Short profile shown to pet owners"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Image</label>
                                    <div class="mt-1 flex items-center gap-4">
                                        <div class="w-20 h-20 rounded-md bg-gray-100 overflow-hidden flex items-center justify-center">
                                            <img id="sitterImagePreview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                                            <i id="sitterImageIcon" data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input id="sitterImageInput" type="file" name="sitters_image" accept="image" class="text-sm">
                                            <button id="clearSitterImage" type="button" class="px-2 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Clear</button>
                                        </div>
                                    </div>
                                </div>
                                    <div class="flex items-center justify-between gap-2 pt-2">
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="sitters_active" value="1" class="rounded border-gray-300"> Active
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="sitters_verified" value="1" class="rounded border-gray-300"> Verified
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="cancelAddSitter" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Sitter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Sitter Modal -->
                    <div id="editSitterModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
                            <div class="flex items-center justify-between p-4 border-b">
                                <h3 class="text-lg font-semibold">Edit Pet Sitter</h3>
                                <button id="closeEditSitter" class="text-gray-500 hover:text-gray-700">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <form id="editSitterForm" enctype="multipart/form-data" class="p-6 space-y-4">
                                <input type="hidden" name="sitters_id" id="edit_sitters_id">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sitter Name</label>
                                        <input type="text" name="sitters_name" id="edit_sitters_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="sitters_email" id="edit_sitters_email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                        <input type="text" name="sitters_phone" id="edit_sitters_phone" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                        <input type="number" name="years_experience" id="edit_years_experience" min="0" step="1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Specialties <span class="text-xs text-gray-500 font-normal">(update selections)</span></label>
                                    <div id="editSpecialtiesGroup" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Dog" class="rounded border-gray-300"> <span>Dog</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Cat" class="rounded border-gray-300"> <span>Cat</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Bird" class="rounded border-gray-300"> <span>Bird</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Fish" class="rounded border-gray-300"> <span>Fish</span></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="sitters_specialty[]" value="Small Pet" class="rounded border-gray-300"> <span>Small Pet</span></label>
                                        <label class="inline-flex items-center gap-1"><input id="editOtherSpec" type="checkbox" value="Other" class="rounded border-gray-300"> <span>Other</span></label>
                                    </div>
                                    <div id="editOtherWrapper" class="mt-2 hidden">
                                        <input type="text" name="sitters_specialties_extra" id="edit_other_specialties" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Other pets, comma-separated">
                                        <p class="mt-1 text-xs text-gray-500">Unlisted pets retained here.</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea name="sitters_bio" id="edit_sitters_bio" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Short profile shown to pet owners"></textarea>
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-2">
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="sitters_active" id="edit_sitters_active" value="1" class="rounded border-gray-300">
                                            <span>Active</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="sitters_verified" id="edit_sitters_verified" value="1" class="rounded border-gray-300">
                                            <span>Verified</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" id="cancelEditSitter" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Update Sitter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        (function(){
                            const openBtn = document.getElementById('openAddSitter');
                            const modal = document.getElementById('addSitterModal');
                            const closeBtn = document.getElementById('closeAddSitter');
                            const cancelBtn = document.getElementById('cancelAddSitter');
                            const form = document.getElementById('addSitterForm');
                            const imgInput = document.getElementById('sitterImageInput');
                            const imgPrev = document.getElementById('sitterImagePreview');
                            const imgIcon = document.getElementById('sitterImageIcon');
                            const clearImg = document.getElementById('clearSitterImage');
                            const tbody = document.getElementById('sittersTableBody');
                            const search = document.getElementById('sittersSearch');
                            // Edit modal refs
                            const editModal = document.getElementById('editSitterModal');
                            const editCloseBtn = document.getElementById('closeEditSitter');
                            const editCancelBtn = document.getElementById('cancelEditSitter');
                            const editForm = document.getElementById('editSitterForm');
                            const editId = document.getElementById('edit_sitters_id');
                            const editName = document.getElementById('edit_sitters_name');
                            const editEmail = document.getElementById('edit_sitters_email');
                            const editPhone = document.getElementById('edit_sitters_phone');
                            const editYears = document.getElementById('edit_years_experience');
                            const editBio = document.getElementById('edit_sitters_bio');
                            const editDescription = document.getElementById('edit_description');
                            const editActive = document.getElementById('edit_sitters_active');
                            const editVerified = document.getElementById('edit_sitters_verified');
                            const editVerifiedText = document.getElementById('edit_sitter_verified_text');
                            // Specialties elements (add)
                            const addOtherSpec = document.getElementById('addOtherSpec');
                            const addOtherWrapper = document.getElementById('addOtherWrapper');
                            // Specialties elements (edit)
                            const editOtherSpec = document.getElementById('editOtherSpec');
                            const editOtherWrapper = document.getElementById('editOtherWrapper');
                            const editOtherInput = document.getElementById('edit_other_specialties');

                            const predefinedSpecs = ['dog','cat','bird','fish','small pet','rabbit','hamster','guinea pig','reptile','ferret','exotic pet'];

                            function toggleAddOther(){
                                if (addOtherSpec && addOtherWrapper) {
                                    addOtherWrapper.classList.toggle('hidden', !addOtherSpec.checked);
                                    if (!addOtherSpec.checked) {
                                        const extra = addOtherWrapper.querySelector('input[name="sitters_specialties_extra"]');
                                        if (extra) extra.value='';
                                    }
                                }
                            }
                            function toggleEditOther(){
                                if (editOtherSpec && editOtherWrapper) {
                                    editOtherWrapper.classList.toggle('hidden', !editOtherSpec.checked);
                                    if (!editOtherSpec.checked && editOtherInput) editOtherInput.value='';
                                }
                            }
                            if (addOtherSpec) addOtherSpec.addEventListener('change', toggleAddOther);
                            if (editOtherSpec) editOtherSpec.addEventListener('change', toggleEditOther);

                            function openEdit(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                            function closeEdit(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); editForm.reset(); }

                            function open(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
                            function close(){ modal.classList.add('hidden'); modal.classList.remove('flex'); form.reset(); resetImage(); }
                            function resetImage(){ imgPrev.src=''; imgPrev.classList.add('hidden'); imgIcon.classList.remove('hidden'); imgInput.value=''; }

                            if (openBtn) openBtn.addEventListener('click', open);
                            if (closeBtn) closeBtn.addEventListener('click', close);
                            if (cancelBtn) cancelBtn.addEventListener('click', close);
                            if (modal) modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });

                            if (imgInput) imgInput.addEventListener('change', (e)=>{
                                const f = e.target.files && e.target.files[0];
                                if (!f) return resetImage();
                                const url = URL.createObjectURL(f);
                                imgPrev.src = url; imgPrev.classList.remove('hidden'); imgIcon.classList.add('hidden');
                            });
                            if (clearImg) clearImg.addEventListener('click', resetImage);
                            if (editCloseBtn) editCloseBtn.addEventListener('click', closeEdit);
                            if (editCancelBtn) editCancelBtn.addEventListener('click', closeEdit);

                            function addr(text){ const td = document.createElement('td'); td.className='px-6 py-4 whitespace-nowrap'; td.innerHTML=text; return td; }
                            function specClass(name){
                                const k = String(name||'').trim().toLowerCase();
                                switch(k){
                                    case 'dog':
                                    case 'dogs': return 'bg-orange-100 text-orange-800 border border-orange-200';
                                    case 'cat':
                                    case 'cats': return 'bg-purple-100 text-purple-800 border border-purple-200';
                                    case 'bird':
                                    case 'birds': return 'bg-blue-100 text-blue-800 border border-blue-200';
                                    case 'fish':
                                    case 'fishes': return 'bg-cyan-100 text-cyan-800 border border-cyan-200';
                                    case 'small pet':
                                    case 'small pets': return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                                    case 'rabbit':
                                    case 'rabbits': return 'bg-pink-100 text-pink-800 border border-pink-200';
                                    case 'hamster':
                                    case 'hamsters': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                    case 'guinea pig':
                                    case 'guinea pigs': return 'bg-lime-100 text-lime-800 border border-lime-200';
                                    case 'reptile':
                                    case 'reptiles': return 'bg-amber-100 text-amber-800 border border-amber-200';
                                    case 'ferret':
                                    case 'ferrets': return 'bg-rose-100 text-rose-800 border border-rose-200';
                                    case 'exotic pet':
                                    case 'exotic pets': return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                                    default: return 'bg-gray-100 text-gray-800 border border-gray-200';
                                }
                            }
                            function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

                            if (form) form.addEventListener('submit', async (e)=>{
                                e.preventDefault();
                                const fd = new FormData(form);
                                fd.append('action','add');
                                try {
                                    const res = await fetch('../../controllers/admin/sittercontroller.php', { method:'POST', body: fd });
                                    const data = await res.json();
                                    if (!data.success){ alert(data.error||data.message||'Failed to add sitter'); return; }
                                    const s = data.item;
                                    const tr = document.createElement('tr');
                                    tr.setAttribute('data-id', String(s.id||''));
                                    // Name with image
                                    const nameHTML = `
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                                ${s.image ? `<img src="${s.image}" alt="${esc(s.name)}" class="w-full h-full object-cover">` : '<i data-lucide="user" class="w-4 h-4 text-gray-400"></i>'}
                                            </div>
                                            <div><p class="font-medium">${esc(s.name)}</p></div>
                                        </div>`;
                                    tr.appendChild(addr(nameHTML));
                                    // Contact
                                    const contactHTML = `<div class=\"space-y-1\"><p>${esc(s.email||'')}</p><p class=\"text-gray-600\">${esc(s.phone||'')}</p></div>`;
                                    tr.appendChild(addr(contactHTML));
                                    // Specialties
                                    const specs = (s.specialties||[]).map(x=>`<span class="px-2 py-1 text-xs rounded-full ${specClass(x)}">${esc(x)}</span>`).join(' ');
                                    tr.appendChild(addr(`<div class="flex flex-wrap gap-1">${specs}</div>`));
                                    // Bio (new column)
                                    const bio = (s.bio||'').trim();
                                    const bioShort = bio.length>60 ? bio.slice(0,60)+'…' : bio;
                                    tr.appendChild(addr(bio ? `<span title="${esc(bio)}">${esc(bioShort)}</span>` : '-'));
                                    // Experience
                                    tr.appendChild(addr(esc((s.years_experience? (String(s.years_experience)+ ' yrs') : (s.experience||'')))))
                                    // Status (merged: Active + Verified)
                                    const active = String(s.active)==='1' || s.active===1 || s.active===true;
                                    const verified = String(s.verified)==='1' || s.verified===1 || s.verified===true;
                                    tr.appendChild(addr(`
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-xs rounded-full ${active?'bg-green-100 text-green-800':'bg-red-100 text-red-800'}">${active?'Active':'Inactive'}</span>
                                            <span class="px-2 py-1 text-xs rounded-full ${verified?'bg-blue-100 text-blue-800':'bg-gray-100 text-gray-800'}">${verified?'Verified':'Unverified'}</span>
                                        </div>`));
                                    // Actions
                                    const actions = document.createElement('td');
                                    actions.className='px-6 py-4 whitespace-nowrap text-sm text-gray-500';
                                    actions.innerHTML = '<div class="flex items-center gap-2"><button class="p-1 text-gray-400 hover:text-gray-600 btn-sitter-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button><button class="p-1 text-red-400 hover:text-red-600 btn-sitter-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button></div>';
                                    tr.appendChild(actions);
                                    tbody.prepend(tr);
                                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                                    close();
                                } catch (err){ console.error(err); alert('Network error adding sitter'); }
                            });

                            if (search) search.addEventListener('input', ()=>{
                                const q = search.value.trim().toLowerCase();
                                [...tbody.querySelectorAll('tr')].forEach(tr=>{
                                    const text = tr.textContent.toLowerCase();
                                    tr.style.display = text.includes(q) ? '' : 'none';
                                });
                            });

                            // Edit/Delete delegation
                            tbody.addEventListener('click', async (e)=>{
                                const editBtn = e.target.closest('.btn-sitter-edit');
                                const delBtn = e.target.closest('.btn-sitter-delete');
                                const tr = e.target.closest('tr');
                                if (!tr) return;
                                const id = tr.getAttribute('data-id');
                                if (editBtn) {
                                    try {
                                        const res = await fetch(`../../controllers/admin/sittercontroller.php?action=get&id=${encodeURIComponent(id)}`);
                                        const data = await res.json();
                                        if (!data.success) { alert(data.error||'Failed to load sitter'); return; }
                                        const raw = data.item || {};
                                        // Normalize fields coming from backend (supports existing schema names)
                                        const norm = {
                                            id: raw.id ?? raw.sitters_id ?? id,
                                            name: raw.name ?? raw.sitters_name ?? '',
                                            email: raw.email ?? raw.sitter_email ?? '',
                                            phone: raw.phone ?? raw.sitters_contact ?? '',
                                            years_experience: raw.years_experience ?? raw.experience ?? 0,
                                            bio: raw.bio ?? raw.sitters_bio ?? '',
                                            active: raw.active ?? raw.sitters_active ?? 0,
                                            verified: raw.verified ?? raw.sitters_verified ?? 0,
                                            verified_text: raw.verified_text ?? raw.sitter_verified_text ?? '',
                                            specialties: (function(){
                                                if (Array.isArray(raw.specialties)) return raw.specialties;
                                                const spec = raw.sitter_specialty || raw.sitters_specialty || '';
                                                if (!spec) return [];
                                                return String(spec).split(/[,|]/).map(s=>s.trim()).filter(Boolean);
                                            })()
                                        };
                                        editId.value = norm.id;
                                        editName.value = norm.name;
                                        editEmail.value = norm.email;
                                        editPhone.value = norm.phone;
                                        editYears.value = norm.years_experience || 0;
                                        editBio.value = norm.bio;
                                        editActive.checked = String(norm.active)==='1' || norm.active===1 || norm.active===true;
                                        if (editVerified) editVerified.checked = String(norm.verified)==='1' || norm.verified===1 || norm.verified===true;
                                        if (editVerifiedText) editVerifiedText.value = norm.verified_text || (String(norm.verified)==='1' ? 'Verified' : '');
                                        // Populate specialties checkboxes in edit form
                                        try {
                                            const specBoxes = editModal.querySelectorAll('#editSpecialtiesGroup input[type="checkbox"][name="sitters_specialty[]"]');
                                            specBoxes.forEach(cb => cb.checked = false);
                                            if (editOtherSpec) editOtherSpec.checked = false;
                                            if (editOtherInput) editOtherInput.value='';
                                            if (editOtherWrapper) editOtherWrapper.classList.add('hidden');
                                            const extras = [];
                                            (norm.specialties||[]).forEach(val => {
                                                const norm = String(val).trim().toLowerCase();
                                                const matched = [...specBoxes].find(cb => cb.value.toLowerCase() === norm);
                                                if (matched) { matched.checked = true; }
                                                else { if (!predefinedSpecs.includes(norm)) extras.push(val); }
                                            });
                                            if (extras.length>0) {
                                                if (editOtherSpec) editOtherSpec.checked = true;
                                                if (editOtherWrapper) editOtherWrapper.classList.remove('hidden');
                                                if (editOtherInput) editOtherInput.value = extras.join(', ');
                                            }
                                        } catch(err){ console.warn('Failed to populate specialties', err); }
                                        if (window.lucide && lucide.createIcons) lucide.createIcons();
                                        openEdit();
                                    } catch(err){ console.error(err); alert('Network error loading sitter'); }
                                } else if (delBtn) {
                                    if (!confirm('Delete this sitter?')) return;
                                    try {
                                        const fd = new FormData();
                                        fd.append('action','delete');
                                        fd.append('sitters_id', id);
                                        const res = await fetch('../../controllers/admin/sittercontroller.php', { method: 'POST', body: fd });
                                        const data = await res.json();
                                        if (!data.success) { alert(data.error||'Failed to delete'); return; }
                                        tr.remove();
                                    } catch(err){ console.error(err); alert('Network error deleting sitter'); }
                                }
                            });

                            if (editForm) editForm.addEventListener('submit', async (e)=>{
                                e.preventDefault();
                                const fd = new FormData(editForm);
                                fd.append('action','update');
                                try {
                                    const res = await fetch('../../controllers/admin/sittercontroller.php', { method:'POST', body: fd });
                                    const data = await res.json();
                                    if (!data.success){ alert(data.error||'Failed to update sitter'); return; }
                                    const s = data.item;
                                    // Update row
                                    const row = tbody.querySelector(`tr[data-id="${CSS.escape(String(s.id))}"]`);
                                    if (row) {
                                        const tds = row.querySelectorAll('td');
                                        // Name cell (0): update image and name
                                        const nameCell = tds[0];
                                        const imgEl = nameCell.querySelector('img');
                                        const iconEl = nameCell.querySelector('i[data-lucide="user"]');
                                        if (s.image) {
                                            if (imgEl) { imgEl.src = s.image; }
                                            else {
                                                const ph = nameCell.querySelector('div.w-10.h-10');
                                                if (ph) ph.innerHTML = `<img src="${s.image}" alt="${s.name}" class="w-full h-full object-cover">`;
                                            }
                                            if (iconEl) iconEl.remove();
                                        } else {
                                            if (imgEl) imgEl.remove();
                                            const holder = nameCell.querySelector('div.w-10.h-10');
                                            if (holder && !holder.querySelector('i[data-lucide="user"]')) holder.innerHTML = '<i data-lucide="user" class="w-4 h-4 text-gray-400"></i>';
                                        }
                                        const nameP = nameCell.querySelector('p.font-medium');
                                        if (nameP) nameP.textContent = s.name||'';
                                        // Contact (1) with years badge
                                        const contactCell = tds[1];
                                        contactCell.innerHTML = `<div class=\"space-y-1\"><p>${esc(s.email||'')}</p><p class=\"text-gray-600\">${esc(s.phone||'')}</p></div>`;
                                        // Specialties (2)
                                        const specsHTML = (s.specialties||[]).map(x=>`<span class="px-2 py-1 text-xs rounded-full ${specClass(x)}">${esc(x)}</span>`).join(' ');
                                        tds[2].innerHTML = `<div class="flex flex-wrap gap-1">${specsHTML}</div>`;
                                        // Bio (3)
                                        const bio2 = (s.bio||'').trim();
                                        const bioShort2 = bio2.length>60 ? bio2.slice(0,60)+'…' : bio2;
                                        tds[3].innerHTML = bio2 ? `<span title="${esc(bio2)}">${esc(bioShort2)}</span>` : '-';
                                        // Experience years (4)
                                        tds[4].textContent = (s.years_experience && Number(s.years_experience)>0) ? `${Number(s.years_experience)} yrs` : (s.experience||'-');
                                        // Status (5) merged: Active + Verified
                                        const isActive2 = (String(s.active)==='1'||s.active===1||s.active===true);
                                        const isVerified2 = (String(s.verified)==='1'||s.verified===1||s.verified===true);
                                        tds[5].innerHTML = `
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 text-xs rounded-full ${ isActive2 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }">${ isActive2 ? 'Active' : 'Inactive' }</span>
                                                <span class="px-2 py-1 text-xs rounded-full ${ isVerified2 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }">${ isVerified2 ? 'Verified' : 'Unverified' }</span>
                                            </div>`;
                                        if (window.lucide && lucide.createIcons) lucide.createIcons();
                                    }
                                    closeEdit();
                                } catch(err){ console.error(err); alert('Network error updating sitter'); }
                            });
                        })();
                    </script>
                </div>

                <!-- Appointments Section -->
                <div id="appointments-section" class="space-y-6 hidden">
                    <?php
                    // Fetch all appointments with address details if available
                    $appointments = [];
                    $counts = ['pet_sitting' => 0, 'grooming' => 0, 'vet' => 0];
                    if (isset($connections) && $connections) {
            $sql = "SELECT a.appointments_id, a.users_id, a.appointments_full_name, a.appointments_email, a.appointments_phone,
                    a.appointments_pet_name, a.appointments_pet_type, a.appointments_pet_breed, a.appointments_pet_age_years,
                    a.appointments_type, a.appointments_date, a.appointments_status,
                    aa.aa_type, aa.aa_address, aa.aa_city, aa.aa_province, aa.aa_postal_code, aa.aa_notes
                                FROM appointments a
                                LEFT JOIN appointment_address aa ON aa.aa_id = a.aa_id
                                ORDER BY a.appointments_date DESC, a.appointments_id DESC";
                        if ($res = mysqli_query($connections, $sql)) {
                            while ($row = mysqli_fetch_assoc($res)) {
                                $appointments[] = $row;
                                $t = $row['appointments_type'];
                                if (isset($counts[$t])) $counts[$t]++;
                            }
                            mysqli_free_result($res);
                        }
                    }

                    function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
                    function type_badge_class($t){
                        switch ($t){
                            case 'pet_sitting': return 'bg-orange-100 text-orange-800 border border-orange-200';
                            case 'grooming': return 'bg-blue-100 text-blue-800 border border-blue-200';
                            case 'vet': return 'bg-green-100 text-green-800 border border-green-200';
                            default: return 'bg-gray-100 text-gray-800 border border-gray-200';
                        }
                    }
                    ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Appointments Management</h1>
                            <p class="text-gray-600 mt-1">Track and manage pet care appointments</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Date:</label>
                                <input type="date" id="apptDateFrom" class="px-2 py-1 border border-gray-300 rounded-md">
                                <span class="text-gray-400">to</span>
                                <input type="date" id="apptDateTo" class="px-2 py-1 border border-gray-300 rounded-md">
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <label class="text-sm text-gray-600">Time:</label>
                                <input type="time" id="apptTimeFrom" class="px-2 py-1 border border-gray-300 rounded-md">
                                <span class="text-gray-400">to</span>
                                <input type="time" id="apptTimeTo" class="px-2 py-1 border border-gray-300 rounded-md">
                            </div>
                            <button id="resetApptFilters" class="ml-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Reset</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-lg font-semibold">All Appointments</h3>
                                    <div class="relative">
                                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                        <input id="appointmentsSearch" type="text" placeholder="Search appointments..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-72" />
                                    </div>
                                </div>
                                <div id="apptTabs" class="flex items-center gap-2">
                                    <button data-appt-filter="all" class="appt-tab px-3 py-1.5 rounded-full border text-sm bg-gray-900 text-white border-gray-900">All</button>
                                    <button data-appt-filter="pet_sitting" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-orange-300 text-orange-700 bg-orange-50">Pet Sitting</button>
                                    <button data-appt-filter="grooming" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-blue-300 text-blue-700 bg-blue-50">Grooming</button>
                                    <button data-appt-filter="vet" class="appt-tab px-3 py-1.5 rounded-full border text-sm border-green-300 text-green-700 bg-green-50">Veterinary</button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full" id="allAppointmentsTable">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody" class="bg-white divide-y divide-gray-200">
                                <?php if (empty($appointments)): ?>
                                    <tr><td colspan="11" class="px-6 py-6 text-center text-gray-500">No appointments found.</td></tr>
                                <?php else: foreach ($appointments as $ap):
                                    $type = (string)($ap['appointments_type'] ?? '');
                                    $aaType = (string)($ap['aa_type'] ?? '');
                                    $addr = trim(implode(', ', array_filter([
                                        $ap['aa_address'] ?? '',
                                        $ap['aa_city'] ?? '',
                                        $ap['aa_province'] ?? '',
                                    ])), ', ');
                                    $typeDisplay = '';
                                    if ($type === 'pet_sitting') {
                                        if ($aaType === 'home-sitting') {
                                            $typeDisplay = 'Home-sitting' . ($addr !== '' ? ' — ' . e($addr) : '');
                                        } elseif ($aaType === 'drop_off') {
                                            $typeDisplay = 'Drop Off';
                                        } else {
                                            $typeDisplay = 'Pet Sitting';
                                        }
                                    } elseif ($type === 'grooming') {
                                        $typeDisplay = 'Grooming';
                                    } elseif ($type === 'vet') {
                                        $typeDisplay = 'Veterinary';
                                    } else {
                                        $typeDisplay = ucfirst(str_replace('_',' ', $type));
                                    }
                                    $dt = $ap['appointments_date'] ?? '';
                                    $iso = '';
                                    if ($dt) { $iso = str_replace(' ', 'T', $dt); }
                                    // Notes: from appointment_address. Column appointments_notes removed.
                                    $notes = trim((string)($ap['aa_notes'] ?? ''));
                                ?>
                                    <tr data-id="<?php echo (int)$ap['appointments_id']; ?>" data-type="<?php echo e($type); ?>" data-status="<?php echo e($ap['appointments_status'] ?? ''); ?>" data-datetime="<?php echo e($iso); ?>" data-search="<?php echo e(strtolower(($ap['appointments_full_name'] ?? '') . ' ' . ($ap['appointments_email'] ?? '') . ' ' . ($ap['appointments_phone'] ?? '') . ' ' . ($ap['appointments_pet_name'] ?? '') . ' ' . ($ap['appointments_pet_type'] ?? '') . ' ' . ($ap['appointments_pet_breed'] ?? ''))); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_full_name'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <div class="space-y-0.5">
                                                <div><?php echo e($ap['appointments_phone'] ?? ''); ?></div>
                                                <div class="text-gray-500 text-xs"><?php echo e($ap['appointments_email'] ?? ''); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_name'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <span class="px-2 py-1 rounded-full <?php echo type_badge_class($ap['appointments_pet_type'] ?? ''); ?>"><?php echo e(ucfirst((string)($ap['appointments_pet_type'] ?? ''))); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_breed'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($ap['appointments_pet_age_years'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <span class="px-2 py-1 rounded-full <?php echo type_badge_class($type); ?>"><?php echo e($typeDisplay); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($dt ? date('M d, Y h:i A', strtotime($dt)) : ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 truncate max-w-xs" title="<?php echo e($notes); ?>"><?php echo e($notes); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <?php $st = (string)($ap['appointments_status'] ?? '');
                                                $cls = $st==='confirmed'?'bg-indigo-100 text-indigo-800 border border-indigo-200':($st==='completed'?'bg-green-100 text-green-800 border border-green-200':($st==='cancelled'?'bg-red-100 text-red-800 border border-red-200':'bg-yellow-100 text-yellow-800 border border-yellow-200'));
                                            ?>
                                            <span class="px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo e(ucfirst($st)); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center gap-2">
                                                <button class="p-1 text-gray-400 hover:text-gray-600 btn-appt-edit" title="Edit"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                <button class="p-1 text-red-400 hover:text-red-600 btn-appt-delete" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
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
                                    <input id="petOwnersSearch" type="text" placeholder="Search owners..." class="px-3 py-2 border border-gray-300 rounded-md w-64">
                                    <button class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" title="No filters available yet">
                                        <i data-lucide="filter" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <?php
                            // Build owners with nested pets
                            $owners = [];
                            if (isset($connections) && $connections) {
                                $sqlOwners = "SELECT u.users_id, u.users_firstname, u.users_lastname, u.users_username, u.users_email, u.users_created_at,
                                                  CASE WHEN EXISTS (
                                                    SELECT 1 FROM user_subscriptions us
                                                    WHERE us.users_id = u.users_id AND us.us_status='active' AND (us.us_end_date IS NULL OR us.us_end_date >= NOW())
                                                  ) THEN 1 ELSE 0 END AS has_subscription
                                              FROM users u
                                              WHERE COALESCE(u.users_role,'0') <> '1'
                                              ORDER BY u.users_created_at DESC, u.users_id DESC";
                                $ownersById = [];
                                if ($res = mysqli_query($connections, $sqlOwners)) {
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $row['pets'] = [];
                                        $owners[] = $row;
                                        $ownersById[(int)$row['users_id']] = &$owners[array_key_last($owners)];
                                    }
                                    mysqli_free_result($res);
                                }
                                if (!empty($ownersById)) {
                                    $ids = implode(',', array_map('intval', array_keys($ownersById)));
                                    $sqlPets = "SELECT users_id, pets_name, pets_species, COALESCE(pets_breed,'') AS pets_breed
                                                FROM pets WHERE users_id IN ($ids) ORDER BY pets_name";
                                    if ($rp = mysqli_query($connections, $sqlPets)) {
                                        while ($p = mysqli_fetch_assoc($rp)) {
                                            $uid = (int)$p['users_id'];
                                            if (isset($ownersById[$uid])) { $ownersById[$uid]['pets'][] = $p; }
                                        }
                                        mysqli_free_result($rp);
                                    }
                                }
                            }
                            ?>

                            <div id="petOwnersList" class="space-y-4">
                                <?php if (!empty($owners)): foreach ($owners as $o): ?>
                                    <?php
                                        $fn = (string)($o['users_firstname'] ?? '');
                                        $ln = (string)($o['users_lastname'] ?? '');
                                        $full = trim($fn . ' ' . $ln);
                                        if ($full === '') $full = (string)($o['users_username'] ?? '');
                                        $memberSince = '';
                                        $createdRaw = (string)($o['users_created_at'] ?? '');
                                        if ($createdRaw !== '') { $memberSince = date('n/j/Y', strtotime($createdRaw)); }
                                        $hasSub = intval($o['has_subscription'] ?? 0) === 1;
                                        $initials = strtoupper(substr($full,0,1));
                                        $searchHay = strtolower($full.' '.($o['users_username']??'').' '.($o['users_email']??''));
                                        foreach (($o['pets'] ?? []) as $pp) { $searchHay .= ' '.strtolower(($pp['pets_name']??'').' '.($pp['pets_species']??'').' '.($pp['pets_breed']??'')); }
                                        $searchHay .= ' '.($hasSub?'active':'none');
                                    ?>
                                    <div class="owner-card bg-white border border-gray-200 rounded-lg p-4" data-search="<?php echo htmlspecialchars($searchHay, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                            <!-- Owner Info -->
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                        <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h4 class="font-semibold truncate"><?php echo htmlspecialchars($full, ENT_QUOTES, 'UTF-8'); ?></h4>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-600">Member since <?php echo htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-sm text-gray-600 flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i><span class="truncate"><?php echo htmlspecialchars((string)($o['users_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                <div>
                                                    <?php if ($hasSub): ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full border border-green-200">Active</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full border border-gray-200">None</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Pets -->
                                            <div class="lg:col-span-2">
                                                <div class="flex items-center gap-2 mb-2 text-sm text-gray-700">
                                                    <i data-lucide="paw-print" class="w-4 h-4"></i>
                                                    <span>Registered Pets (<?php echo count($o['pets'] ?? []); ?>)</span>
                                                </div>
                                                <?php if (!empty($o['pets'])): ?>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <?php foreach ($o['pets'] as $pet): ?>
                                                            <div class="border rounded-lg p-3 bg-gray-50">
                                                                <div class="font-semibold"><?php echo htmlspecialchars((string)$pet['pets_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                <div class="text-sm text-gray-600">Type: <?php echo htmlspecialchars((string)$pet['pets_species'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                <div class="text-sm text-gray-600">Breed: <?php echo htmlspecialchars((string)$pet['pets_breed'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-sm text-gray-500">No registered pets.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; else: ?>
                                    <div class="text-center text-gray-500 py-8">No pet owners found.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <div id="petOwnersPager" class="flex items-center justify-between mt-4">
                                <div id="ownersPageInfo" class="text-sm text-gray-600"></div>
                                <div class="flex items-center gap-2">
                                    <button id="ownersPrev" class="px-3 py-1.5 border rounded-md text-sm disabled:opacity-50">Prev</button>
                                    <div id="ownersPageNums" class="flex items-center gap-1"></div>
                                    <button id="ownersNext" class="px-3 py-1.5 border rounded-md text-sm disabled:opacity-50">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscribers Section (Dynamic) -->
                <div id="subscribers-section" class="space-y-6 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-bold">Subscribers</h2>
                            <p class="text-gray-600 mt-1">Manage user subscription statuses</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="refreshSubscribers" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 text-sm"><i data-lucide="rotate-cw" class="w-4 h-4"></i>Refresh</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600" id="subsTotal"><?= (int)$subscriberStats['total'] ?></div>
                                <div class="text-sm text-gray-600">Total Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600" id="subsActive"><?= (int)$subscriberStats['active'] ?></div>
                                <div class="text-sm text-gray-600">Active Subscribers</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600" id="subsMonth"><?= (int)$subscriberStats['this_month'] ?></div>
                                <div class="text-sm text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i data-lucide="list" class="w-4 h-4 text-gray-500"></i>
                                <h3 class="font-semibold text-gray-700">Subscribers List</h3>
                            </div>
                            <div class="flex items-center gap-3 w-full md:w-auto">
                                <div class="relative flex-1 md:flex-initial">
                                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                    <input id="subsSearch" type="text" placeholder="Search user/email" class="pl-9 pr-3 py-2 text-sm rounded-md border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 w-full" />
                                </div>
                                <select id="subsStatusFilter" class="text-sm border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="py-2 px-3 text-left">User</th>
                                        <th class="py-2 px-3 text-left">Start Date</th>
                                        <th class="py-2 px-3 text-left">End Date</th>
                                        <th class="py-2 px-3 text-left">Status</th>
                                        <th class="py-2 px-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="subsTableBody" class="divide-y divide-gray-100">
                                    <?php if(empty($subscribersList)): ?>
                                        <tr><td colspan="5" class="py-6 px-3 text-center text-gray-500 text-sm" data-empty="1">No subscribers found.</td></tr>
                                    <?php else: foreach($subscribersList as $s):
                                        $status = $s['us_status'];
                                        if($status === 'active' && !empty($s['us_end_date']) && strtotime($s['us_end_date']) < time()) { $status = 'expired'; }
                                        $badgeClass = match($status){
                                            'active' => 'bg-green-100 text-green-700 border-green-300',
                                            'cancelled' => 'bg-red-100 text-red-700 border-red-300',
                                            'expired' => 'bg-gray-200 text-gray-700 border-gray-300',
                                            default => 'bg-gray-100 text-gray-600 border-gray-200'
                                        };
                                    ?>
                                    <tr data-status="<?= htmlspecialchars($status,ENT_QUOTES) ?>" data-user="<?= (int)$s['users_id'] ?>" data-name="<?= htmlspecialchars($s['full_name'] ?: $s['users_email'],ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?>">
                                        <td class="py-2 px-3">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-800 leading-tight"><?= htmlspecialchars($s['full_name'] ?: $s['users_email'],ENT_QUOTES) ?></span>
                                                <span class="text-[11px] text-gray-500 leading-tight"><?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 text-gray-600 text-xs"><?= $s['us_start_date'] ? date('Y-m-d', strtotime($s['us_start_date'])) : '—' ?></td>
                                        <td class="py-2 px-3 text-gray-600 text-xs"><?= $s['us_end_date'] ? date('Y-m-d', strtotime($s['us_end_date'])) : '—' ?></td>
                                        <td class="py-2 px-3">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-medium <?= $badgeClass ?> capitalize">
                                                <i data-lucide="dot" class="w-3 h-3"></i><?= htmlspecialchars($status,ENT_QUOTES) ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <button class="subs-message-btn inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md border border-gray-300 hover:border-orange-400 hover:text-orange-600" data-email="<?= htmlspecialchars($s['users_email'],ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($s['full_name'],ENT_QUOTES) ?>"><i data-lucide="mail" class="w-3 h-3"></i>Message</button>
                                                <button class="subs-delete-btn inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50" data-us="<?= (int)$s['us_id'] ?>"><i data-lucide="trash-2" class="w-3 h-3"></i>Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500">
                            <div id="subsCountSummary">Showing <span id="subsShownCount"></span> of <span id="subsTotalCount"></span></div>
                            <div class="flex items-center gap-2" id="subsPager" style="display:none;">
                                <button id="subsPrev" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Prev</button>
                                <div id="subsPageNums" class="flex items-center gap-1"></div>
                                <button id="subsNext" class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    
    <!-- Edit Appointment Modal -->
    <div id="editAppointmentModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Appointment</h3>
                <button id="editApptClose" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form id="editAppointmentForm" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="appointments_id" id="edit_appt_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="appointments_full_name" id="edit_appt_full_name" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="appointments_email" id="edit_appt_email" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="appointments_phone" id="edit_appt_phone" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pet Name</label>
                        <input type="text" name="appointments_pet_name" id="edit_appt_pet_name" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pet Type</label>
                        <input type="text" name="appointments_pet_type" id="edit_appt_pet_type" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Breed</label>
                        <input type="text" name="appointments_pet_breed" id="edit_appt_pet_breed" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Age (years)</label>
                        <input type="number" step="1" min="0" name="appointments_pet_age_years" id="edit_appt_pet_age" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="appointments_type" id="edit_appt_type" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="pet_sitting">Pet Sitting</option>
                            <option value="grooming">Grooming</option>
                            <option value="vet">Veterinary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                        <input type="datetime-local" name="appointments_date" id="edit_appt_datetime" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="appointments_status" id="edit_appt_status" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="aa_notes" id="edit_appt_notes" rows="3" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="editApptCancel" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Add New Product</h3>
                <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="addProductForm" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="products_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Pet Type</label>
                        <select name="products_pet_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select pet type</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Small Pet">Small Pet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Category</label>
                        <select name="products_category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select category</option>
                            <option value="food">Food</option>
                            <option value="accessories">Accessories</option>
                            <option value="grooming">Grooming</option>
                            <option value="treats">Treats</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Price (₱)</label>
                        <input type="number" name="products_price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Stock</label>
                        <input type="number" name="products_stock" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Description</label>
                    <textarea name="products_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image (jpeg, png)</label>
                        <input type="file" name="products_image" id="products_image" accept="image/jpeg,image/png" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="mt-2 flex items-center gap-3">
                            <div class="w-20 h-20 rounded border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="imagePreview" alt="Preview" class="w-full h-full object-cover hidden" />
                                <span id="imagePlaceholder" class="text-xs text-gray-400">No image</span>
                            </div>
                            <button type="button" id="clearImageBtn" class="text-sm text-gray-600 underline hidden">Remove</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="products_active" name="products_active" class="h-4 w-4 text-orange-600 border-gray-300 rounded" checked>
                        <label for="products_active" class="text-sm text-gray-700">Product Active</label>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Add Product</button>
                    <button type="button" onclick="closeAddProductModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Product</h3>
                <button onclick="closeEditProductModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="editProductForm" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="products_id" id="edit_products_id">
                <input type="hidden" name="current_image_url" id="edit_current_image_url">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="products_name" id="edit_products_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Pet Type</label>
                        <select name="products_pet_type" id="edit_products_pet_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select pet type</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Small Pet">Small Pet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Category</label>
                        <select name="products_category" id="edit_products_category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select category</option>
                            <option value="food">Food</option>
                            <option value="accessories">Accessories</option>
                            <option value="grooming">Grooming</option>
                            <option value="treats">Treats</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Price (₱)</label>
                        <input type="number" name="products_price" id="edit_products_price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Stock</label>
                        <input type="number" name="products_stock" id="edit_products_stock" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Description</label>
                    <textarea name="products_description" id="edit_products_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image (jpeg, png)</label>
                        <input type="file" name="products_image" id="edit_products_image" accept="image/jpeg,image/png" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="mt-2 flex items-center gap-3">
                            <div class="w-20 h-20 rounded border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="edit_imagePreview" alt="Preview" class="w-full h-full object-cover hidden" />
                                <span id="edit_imagePlaceholder" class="text-xs text-gray-400">No image</span>
                            </div>
                            <button type="button" id="edit_clearImageBtn" class="text-sm text-gray-600 underline hidden">Remove</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="edit_products_active" name="products_active" class="h-4 w-4 text-orange-600 border-gray-300 rounded">
                        <label for="edit_products_active" class="text-sm text-gray-700">Product Active</label>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">Update Product</button>
                    <button type="button" onclick="closeEditProductModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50">Cancel</button>
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

        // Global state with persistence of last active section
        let currentActiveSection = (function(){
            try { return localStorage.getItem('admin_active_section') || 'dashboard'; } catch(e){ return 'dashboard'; }
        })();
        let sidebarExpanded = false;
        let sidebarLocked = false;
        let currentTimeFilter = 'monthly';

        // Initialize the app
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            updateChart();
            populateSubscribers();
            initProductFilters();
            // Section isolation logic: ensure only active section's UI is visible
            const sectionIds = ['dashboard','orders','sitters','appointments','owners','subscribers','products'];
            function showSection(id){
                sectionIds.forEach(sid=>{
                    const el = document.getElementById(sid+'-section');
                    if(!el) return;
                    if(sid===id){ el.classList.remove('hidden'); } else { el.classList.add('hidden'); }
                });
                currentActiveSection = id;
                // Persist
                try { localStorage.setItem('admin_active_section', id); } catch(e){}
                // Update sidebar active classes
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('bg-gradient-to-r','from-orange-500','to-amber-600','text-white','shadow-md');
                    item.classList.add('text-gray-700','hover:bg-gray-100');
                });
                const activeItem = document.querySelector(`[data-section="${id}"]`);
                if (activeItem) {
                    activeItem.classList.add('bg-gradient-to-r','from-orange-500','to-amber-600','text-white','shadow-md');
                    activeItem.classList.remove('text-gray-700','hover:bg-gray-100');
                }
            }
            // Bind nav items marked with data-section
            document.querySelectorAll('[data-section]')?.forEach(btn=>{
                btn.addEventListener('click', e=>{
                    const id = btn.getAttribute('data-section');
                    if(id) showSection(id);
                });
            });
            // Initialize to existing or default
            if(!document.getElementById(currentActiveSection+'-section')) currentActiveSection='dashboard';
            showSection(currentActiveSection);
            // Orders edit/delete handlers
            (function initOrders(){
                const editModal = document.getElementById('orderEditModal');
                const form = document.getElementById('orderEditForm');
                const saveBtn = document.getElementById('orderEditSaveBtn');
                const feedback = document.getElementById('orderEditFeedback');
                const tidInput = document.getElementById('order_edit_tid');
                const statusSel = document.getElementById('order_edit_status');
                const etaInput = document.getElementById('order_edit_eta');
                const actualInput = document.getElementById('order_edit_actual');
                const sigInput = document.getElementById('order_edit_signature');
                const closeBtns = document.querySelectorAll('[data-close-order]');

                function open(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                function close(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); form.reset(); feedback.textContent=''; }
                closeBtns.forEach(b=> b.addEventListener('click', close));
                editModal?.addEventListener('click', e=>{ if(e.target===editModal) close(); });

                async function fetchOrder(tid){
                    feedback.textContent='Loading...';
                    try {
                        const res = await fetch(`../../controllers/admin/ordercontroller.php?action=get&transactions_id=${tid}`, {headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if(!data.success) throw new Error(data.error||'Failed');
                        const o = data.order;
                        statusSel.value = o.deliveries_delivery_status || 'processing';
                        etaInput.value = o.deliveries_estimated_delivery_date ? o.deliveries_estimated_delivery_date.substring(0,10) : '';
                        // Leave blank if no actual date yet so submitting without change keeps it NULL
                        actualInput.value = o.deliveries_actual_delivery_date ? o.deliveries_actual_delivery_date.substring(0,10) : '';
                        sigInput.checked = !!parseInt(o.deliveries_recipient_signature||0);
                        feedback.textContent='';
                    } catch(err){
                        feedback.textContent='Error loading order: '+err.message;
                    }
                }

                document.querySelectorAll('.order-edit-btn').forEach(btn => {
                    btn.addEventListener('click', ()=>{
                        const tid = btn.getAttribute('data-id');
                        tidInput.value = tid;
                        open();
                        fetchOrder(tid);
                    });
                });

                form?.addEventListener('submit', async e=>{
                    e.preventDefault();
                    feedback.textContent='Saving...';
                    saveBtn.disabled=true;
                    try {
                        const fd = new FormData(form);
                        const res = await fetch('../../controllers/admin/ordercontroller.php', {method:'POST', body:fd, headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if(!data.success) throw new Error(data.error||'Update failed');
                        feedback.textContent='Saved. Refreshing row...';
                        // Optimistic update of row values
                        const row = document.querySelector(`#ordersTableBody tr td div.text-[11px]`); // fallback not used
                        const tid = tidInput.value;
                        const targetRow = Array.from(document.querySelectorAll('#ordersTableBody tr')).find(r=> r.querySelector('input[name="transactions_id"]')?.value === tid || r.innerHTML.includes(`#${tid}`));
                        if(targetRow){
                            targetRow.setAttribute('data-status', statusSel.value);
                            const statusCell = targetRow.querySelector('td:nth-child(5)');
                            if(statusCell){
                                statusCell.innerHTML = `<span class=\"px-2 py-1 rounded-full bg-gray-100 text-gray-700\">${statusSel.value.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</span>`;
                            }
                            const etaCell = targetRow.querySelector('td:nth-child(6)');
                            if(etaCell) etaCell.textContent = etaInput.value;
                            const actualCell = targetRow.querySelector('td:nth-child(7)');
                            if(actualCell) actualCell.textContent = actualInput.value;
                            const sigCell = targetRow.querySelector('td:nth-child(8)');
                            if(sigCell) sigCell.innerHTML = sigInput.checked? '<span class="text-emerald-600 font-semibold">Received</span>' : '<span class="text-gray-400">Pending</span>';
                        }
                        setTimeout(()=>{ close(); }, 600);
                    } catch(err){
                        feedback.textContent='Error: '+err.message;
                    } finally {
                        saveBtn.disabled=false;
                    }
                });
            })();

            // Orders Section: filtering (product names, status, payment method) + pagination (10/page)
            (function initOrdersSectionFilters(){
                const tbody = document.querySelector('#ordersTableBody');
                if(!tbody) return; // safety
                const search = document.getElementById('ordersSearch'); // search over product names only
                const statusFilter = document.getElementById('ordersStatusFilter');
                const paymentFilter = document.getElementById('ordersPaymentFilter');
                const rows = Array.from(tbody.querySelectorAll('tr[data-status]'));
                const pagWrap = document.getElementById('ordersPagination');
                const prevBtn = document.getElementById('ordersPrev');
                const nextBtn = document.getElementById('ordersNext');
                const pageNums = document.getElementById('ordersPageNums');
                const pageInfo = document.getElementById('ordersPageInfo');
                const pageSize = 10;
                let page = 1;
                let filtered = rows.slice();

                function apply(){
                    const q = (search?.value || '').trim().toLowerCase();
                    const st = (statusFilter?.value || '').toLowerCase();
                    const pm = (paymentFilter?.value || '').toLowerCase();
                    filtered = rows.filter(r => {
                        const rs = (r.dataset.status||'').toLowerCase();
                        const rp = (r.dataset.payment||'').toLowerCase();
                        const items = (r.dataset.items||'').toLowerCase();
                        if(st && rs !== st) return false;
                        if(pm && rp !== pm) return false;
                        if(q && !items.includes(q)) return false; // product names only
                        return true;
                    });
                    page = 1;
                    render();
                }

                function render(){
                    rows.forEach(r => r.classList.add('hidden'));
                    const total = filtered.length;
                    if(total === 0){
                        pagWrap?.classList.add('hidden');
                        if(pageInfo) pageInfo.textContent = 'No orders found';
                        if(pageNums) pageNums.innerHTML='';
                        prevBtn && (prevBtn.disabled = true);
                        nextBtn && (nextBtn.disabled = true);
                        return;
                    }
                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    if(page > totalPages) page = totalPages;
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    filtered.slice(start, end).forEach(r => r.classList.remove('hidden'));

                    pagWrap?.classList.remove('hidden');
                    if(pageInfo){
                        pageInfo.textContent = `Showing ${start+1}-${Math.min(end,total)} of ${total} orders`;
                    }

                    if(pageNums){
                        pageNums.innerHTML='';
                        const totalPagesFinal = Math.ceil(total / pageSize);
                        if(totalPagesFinal > 1){
                            const range = typeof paginationRange === 'function' ? paginationRange(page, totalPagesFinal, 5) : Array.from({length: totalPagesFinal}, (_,i)=>i+1);
                            range.forEach(n => {
                                const btn = document.createElement('button');
                                btn.className = 'px-2 py-1 text-xs rounded-md border';
                                if(n === '...'){
                                    btn.textContent = '...';
                                    btn.disabled = true;
                                    btn.classList.add('opacity-50','cursor-default');
                                } else {
                                    btn.textContent = n;
                                    if(n === page) btn.classList.add('bg-blue-600','text-white','border-blue-600');
                                    btn.addEventListener('click', ()=>{ page = n; render(); });
                                }
                                pageNums.appendChild(btn);
                            });
                        }
                    }

                    if(prevBtn){ prevBtn.disabled = page <= 1; prevBtn.classList.toggle('opacity-50', prevBtn.disabled); }
                    if(nextBtn){ nextBtn.disabled = page >= Math.ceil(total / pageSize); nextBtn.classList.toggle('opacity-50', nextBtn.disabled); }
                }

                // Event bindings
                search?.addEventListener('input', apply);
                statusFilter?.addEventListener('change', apply);
                paymentFilter?.addEventListener('change', apply);
                prevBtn?.addEventListener('click', ()=>{ if(page>1){ page--; render(); }});
                nextBtn?.addEventListener('click', ()=>{ if(page < Math.ceil(filtered.length / pageSize)){ page++; render(); }});

                apply();
            })();

            // Pet Owners directory: search + pagination (10/page)
            (function initOwnersDirectory(){
                const list = document.getElementById('petOwnersList');
                const search = document.getElementById('petOwnersSearch');
                const prev = document.getElementById('ownersPrev');
                const next = document.getElementById('ownersNext');
                const pageInfo = document.getElementById('ownersPageInfo');
                if (!list) return;
                const allCards = Array.from(list.querySelectorAll('.owner-card'));
                let filtered = allCards.slice();
                const pageSizeOwners = 10;
                let page = 1;

                function render(){
                    // hide all
                    allCards.forEach(c => c.style.display = 'none');
                    const total = filtered.length;
                    const totalPages = Math.max(1, Math.ceil(total / pageSizeOwners));
                    if (page > totalPages) page = totalPages;
                    const start = (page - 1) * pageSizeOwners;
                    const end = start + pageSizeOwners;
                    filtered.slice(start, end).forEach(c => c.style.display = '');
                    // controls
                    if (pageInfo) pageInfo.textContent = total ? `Showing ${start+1}-${Math.min(end,total)} of ${total}` : 'No results';
                    if (prev) prev.disabled = page <= 1;
                    if (next) next.disabled = page >= totalPages;
                    const numsWrap = document.getElementById('ownersPageNums');
                    if (numsWrap) {
                        numsWrap.innerHTML='';
                        const pages = paginationRange(page,totalPages,5);
                        pages.forEach(p=>{
                            if(p==='...') { const span=document.createElement('span'); span.textContent='...'; span.className='px-1 text-gray-500'; numsWrap.appendChild(span); }
                            else { const b=document.createElement('button'); b.textContent=p; b.className='px-2.5 py-1 border rounded text-sm '+(p===page?'bg-gray-900 text-white border-gray-900':'hover:bg-gray-100'); b.addEventListener('click',()=>{ page=p; render(); }); numsWrap.appendChild(b);} });
                    }
                }

                function applyFilter(){
                    const q = (search?.value || '').trim().toLowerCase();
                    filtered = q ? allCards.filter(c => (c.getAttribute('data-search')||'').includes(q)) : allCards.slice();
                    page = 1; render();
                }

                if (search) search.addEventListener('input', applyFilter);
                if (prev) prev.addEventListener('click', ()=>{ if (page>1){ page--; render(); }});
                if (next) next.addEventListener('click', ()=>{ page++; render(); });

                applyFilter();
            })();

            // Appointments filtering and actions (single All table)
            (function initAppointments(){
                const table = document.getElementById('allAppointmentsTable');
                const allRows = Array.from(table.querySelectorAll('tbody tr'));
                const tabs = Array.from(document.querySelectorAll('#apptTabs .appt-tab'));
                const search = document.getElementById('appointmentsSearch');
                const dateFrom = document.getElementById('apptDateFrom');
                const dateTo = document.getElementById('apptDateTo');
                const timeFrom = document.getElementById('apptTimeFrom');
                const timeTo = document.getElementById('apptTimeTo');
                const resetBtn = document.getElementById('resetApptFilters');

                let currentService = 'all';

                function matchesFilters(tr){
                    // Service
                    if (currentService !== 'all' && tr.getAttribute('data-type') !== currentService) return false;
                    // Date/time
                    const iso = tr.getAttribute('data-datetime') || '';
                    if (iso) {
                        const [dPart, tPart] = iso.split('T');
                        const tVal = tPart ? tPart.substring(0,5) : '';
                        if (dateFrom && dateFrom.value && dPart < dateFrom.value) return false;
                        if (dateTo && dateTo.value && dPart > dateTo.value) return false;
                        if (timeFrom && timeFrom.value && tVal && tVal < timeFrom.value) return false;
                        if (timeTo && timeTo.value && tVal && tVal > timeTo.value) return false;
                    }
                    // Search
                    const q = (search?.value || '').trim().toLowerCase();
                    if (q) {
                        const hay = tr.getAttribute('data-search') || '';
                        if (!hay.includes(q)) return false;
                    }
                    return true;
                }

                // Appointments filtering + pagination (10/page)
                const apptPageSize = 10;
                let apptPage = 1;

                function visibleRows(){ return allRows.filter(tr => tr.style.display !== 'none'); }

                function paginateAppointments(){
                    const rows = allRows.filter(tr => tr.__matches || false);
                    const total = rows.length;
                    const totalPages = Math.max(1, Math.ceil(total / apptPageSize));
                    if (apptPage > totalPages) apptPage = totalPages;
                    // hide all
                    allRows.forEach(r => r.style.display = 'none');
                    const start = (apptPage - 1) * apptPageSize;
                    const end = start + apptPageSize;
                    rows.slice(start, end).forEach(r => r.style.display = '');
                    // Empty state row when no visible
                    const anyVisible = rows.slice(start,end).length > 0;
                    const colSpan = table.querySelector('thead tr').children.length;
                    let emptyRow = table.querySelector('tbody tr[data-empty]');
                    if (!anyVisible) {
                        if (!emptyRow) {
                            emptyRow = document.createElement('tr');
                            emptyRow.setAttribute('data-empty','1');
                            emptyRow.innerHTML = `<td colspan="${colSpan}" class="px-6 py-6 text-center text-gray-500">No matching appointments.</td>`;
                            table.querySelector('tbody').appendChild(emptyRow);
                        }
                    } else if (emptyRow) {
                        emptyRow.remove();
                    }
                    updateApptPager(total, apptPage, totalPages, start, end);
                }

                function updateApptPager(total, page, totalPages, start, end){
                    let pager = document.getElementById('appointmentsPager');
                    if (!pager) return; // pager may be injected below
                    const info = pager.querySelector('#appointmentsPageInfo');
                    const prev = pager.querySelector('#appointmentsPrev');
                    const next = pager.querySelector('#appointmentsNext');
                    const numsWrap = pager.querySelector('#appointmentsPageNums');
                    if (info) info.textContent = total ? `Showing ${start+1}-${Math.min(end,total)} of ${total}` : 'No results';
                    if (prev) prev.disabled = page <= 1;
                    if (next) next.disabled = page >= totalPages;
                    if (numsWrap) {
                        numsWrap.innerHTML='';
                        const pages = paginationRange(page,totalPages,5);
                        pages.forEach(p=>{ if(p==='...'){ const span=document.createElement('span'); span.textContent='...'; span.className='px-1 text-gray-500'; numsWrap.appendChild(span);} else { const b=document.createElement('button'); b.textContent=p; b.className='px-2.5 py-1 border rounded text-sm '+(p===page?'bg-gray-900 text-white border-gray-900':'hover:bg-gray-100'); b.addEventListener('click',()=>{ apptPage=p; paginateAppointments(); }); numsWrap.appendChild(b);} });
                    }
                }

                function ensureApptPager(){
                    if (document.getElementById('appointmentsPager')) return;
                    const wrapper = table.parentElement?.parentElement; // card body -> table wrapper
                    const pager = document.createElement('div');
                    pager.id = 'appointmentsPager';
                    pager.className = 'flex items-center justify-between px-4 py-3 border-t';
                    pager.innerHTML = `
                        <div id=\"appointmentsPageInfo\" class=\"text-sm text-gray-600\"></div>
                        <div class=\"flex items-center gap-2\">
                            <button id=\"appointmentsPrev\" class=\"px-3 py-1.5 border rounded-md text-sm disabled:opacity-50\">Prev</button>
                            <div id=\"appointmentsPageNums\" class=\"flex items-center gap-1\"></div>
                            <button id=\"appointmentsNext\" class=\"px-3 py-1.5 border rounded-md text-sm disabled:opacity-50\">Next</button>
                        </div>`;
                    wrapper.appendChild(pager);
                    pager.querySelector('#appointmentsPrev').addEventListener('click', ()=>{ if (apptPage>1){ apptPage--; paginateAppointments(); } });
                    pager.querySelector('#appointmentsNext').addEventListener('click', ()=>{ apptPage++; paginateAppointments(); });
                }

                function applyFilters(){
                    let anyVisible = false;
                    allRows.forEach(tr => {
                        const show = matchesFilters(tr);
                        tr.__matches = !!show;
                        if (show) anyVisible = true;
                    });
                    ensureApptPager();
                    apptPage = 1;
                    paginateAppointments();
                }

                tabs.forEach(btn => btn.addEventListener('click', () => {
                    tabs.forEach(b => b.classList.remove('bg-gray-900','text-white','border-gray-900'));
                    tabs.forEach(b => b.classList.remove('ring-1','ring-gray-900'));
                    tabs.forEach(b => b.classList.add('opacity-90'));
                    btn.classList.add('bg-gray-900','text-white','border-gray-900');
                    btn.classList.remove('opacity-90');
                    currentService = btn.getAttribute('data-appt-filter');
                    applyFilters();
                }));

                [search, dateFrom, dateTo, timeFrom, timeTo].forEach(el => { if (el) el.addEventListener('input', applyFilters); });
                [dateFrom, dateTo, timeFrom, timeTo].forEach(el => { if (el) el.addEventListener('change', applyFilters); });
                if (resetBtn) resetBtn.addEventListener('click', () => {
                    if (dateFrom) dateFrom.value = '';
                    if (dateTo) dateTo.value = '';
                    if (timeFrom) timeFrom.value = '';
                    if (timeTo) timeTo.value = '';
                    if (search) search.value = '';
                    currentService = 'all';
                    // activate All tab
                    const allTab = document.querySelector('#apptTabs [data-appt-filter="all"]');
                    if (allTab) allTab.click(); else applyFilters();
                });

                // Actions: edit/delete
                const editModal = document.getElementById('editAppointmentModal');
                const editClose = document.getElementById('editApptClose');
                const editCancel = document.getElementById('editApptCancel');
                const editForm = document.getElementById('editAppointmentForm');
                function openEdit(){ editModal.classList.remove('hidden'); editModal.classList.add('flex'); }
                function closeEdit(){ editModal.classList.add('hidden'); editModal.classList.remove('flex'); editForm.reset(); }
                if (editClose) editClose.addEventListener('click', closeEdit);
                if (editCancel) editCancel.addEventListener('click', closeEdit);
                if (editModal) editModal.addEventListener('click', (e)=>{ if(e.target===editModal) closeEdit(); });

                function fillFormFromRow(tr){
                    const id = tr.getAttribute('data-id');
                    document.getElementById('edit_appt_id').value = id;
                    document.getElementById('edit_appt_full_name').value = tr.children[0].textContent.trim();
                    const phone = tr.children[1].querySelector('div>div:first-child')?.textContent.trim() || '';
                    const email = tr.children[1].querySelector('div>div.text-xs')?.textContent.trim() || '';
                    document.getElementById('edit_appt_phone').value = phone;
                    document.getElementById('edit_appt_email').value = email;
                    document.getElementById('edit_appt_pet_name').value = tr.children[2].textContent.trim();
                    document.getElementById('edit_appt_pet_type').value = tr.getAttribute('data-search')?.split(' ')?.[0] || '';
                    document.getElementById('edit_appt_pet_breed').value = tr.children[4].textContent.trim();
                    document.getElementById('edit_appt_pet_age').value = tr.children[5].textContent.trim();
                    document.getElementById('edit_appt_type').value = tr.getAttribute('data-type') || 'pet_sitting';
                    const iso = tr.getAttribute('data-datetime') || '';
                    document.getElementById('edit_appt_datetime').value = iso;
                    const statusCell = tr.querySelector('td:nth-last-child(2)');
                    const statusText = (statusCell?.innerText || statusCell?.textContent || '').trim().toLowerCase();
                    const stSel = document.getElementById('edit_appt_status');
                    if (stSel) stSel.value = statusText || 'pending';
                    document.getElementById('edit_appt_notes').value = tr.children[8].getAttribute('title') || tr.children[8].textContent.trim();
                }

                table.addEventListener('click', async (e)=>{
                    const editBtn = e.target.closest('.btn-appt-edit');
                    const delBtn = e.target.closest('.btn-appt-delete');
                    const tr = e.target.closest('tr');
                    if (!tr) return;
                    const id = tr.getAttribute('data-id');
                    if (editBtn) {
                        try {
                            // Try to fetch latest record
                            const res = await fetch(`../../controllers/admin/appointmentcontroller.php?action=get&id=${encodeURIComponent(id)}`);
                            let ok = res.ok; let data = null;
                            try { data = await res.json(); } catch(_){}
                            if (ok && data && data.success && data.item) {
                                const a = data.item;
                                document.getElementById('edit_appt_id').value = a.id;
                                document.getElementById('edit_appt_full_name').value = a.full_name||'';
                                document.getElementById('edit_appt_email').value = a.email||'';
                                document.getElementById('edit_appt_phone').value = a.phone||'';
                                document.getElementById('edit_appt_pet_name').value = a.pet_name||'';
                                document.getElementById('edit_appt_pet_type').value = a.pet_type||'';
                                document.getElementById('edit_appt_pet_breed').value = a.pet_breed||'';
                                document.getElementById('edit_appt_pet_age').value = a.pet_age||'';
                                document.getElementById('edit_appt_type').value = a.type||'pet_sitting';
                                document.getElementById('edit_appt_datetime').value = a.datetime||'';
                                document.getElementById('edit_appt_status').value = a.status||'pending';
                                document.getElementById('edit_appt_notes').value = a.notes||'';
                            } else {
                                fillFormFromRow(tr);
                            }
                            openEdit();
                        } catch(err) {
                            fillFormFromRow(tr); openEdit();
                        }
                    } else if (delBtn) {
                        if (!confirm('Delete this appointment? This action cannot be undone.')) return;
                        try {
                            const fd = new FormData();
                            fd.append('action','delete');
                            fd.append('appointments_id', id);
                            const res = await fetch('../../controllers/admin/appointmentcontroller.php', { method:'POST', body: fd });
                            const data = await res.json();
                            if (!data.success) { alert(data.error||'Delete failed'); return; }
                            tr.remove();
                            allRows.splice(allRows.indexOf(tr),1);
                            applyFilters();
                        } catch(err){ alert('Network error deleting'); }
                    }
                });

                if (editForm) editForm.addEventListener('submit', async (e)=>{
                    e.preventDefault();
                    const fd = new FormData(editForm);
                    try {
                        const res = await fetch('../../controllers/admin/appointmentcontroller.php', { method:'POST', body: fd });
                        const data = await res.json();
                        if (!data.success) { alert(data.error||'Update failed'); return; }
                        // Update row inline
                        const id = data.item.id;
                        const row = table.querySelector(`tbody tr[data-id="${CSS.escape(String(id))}"]`);
                        if (row) {
                            row.children[0].textContent = data.item.full_name || '';
                            row.children[1].querySelector('div>div:first-child').textContent = data.item.phone || '';
                            row.children[1].querySelector('div>div.text-xs').textContent = data.item.email || '';
                            row.children[2].textContent = data.item.pet_name || '';
                            row.children[4].textContent = data.item.pet_breed || '';
                            row.children[5].textContent = data.item.pet_age || '';
                            row.setAttribute('data-type', data.item.type || 'pet_sitting');
                            row.setAttribute('data-datetime', data.item.datetime || '');
                            // Date cell
                            row.children[7].textContent = data.item.datetime_fmt || '';
                            // Notes
                            row.children[8].textContent = data.item.notes || '';
                            row.children[8].setAttribute('title', data.item.notes || '');
                            // Status chip (second-to-last cell)
                            const statusCell2 = row.querySelector('td:nth-last-child(2)');
                            if (statusCell2) statusCell2.innerHTML = data.item.status_chip_html;
                        }
                        closeEdit();
                        applyFilters();
                    } catch(err){ alert('Network error updating'); }
                });

                // Initial apply
                applyFilters();
            })();

            // ===== Split Orders (Pickup & Delivery) Filtering / Pagination / Modals =====
            (function initSplitOrders(){
                // Pickup elements
                const pSearch = document.getElementById('pickupOrdersSearch');
                const pStatus = document.getElementById('pickupStatusFilter');
                const pBody = document.getElementById('pickupOrdersTableBody');
                const pRows = Array.from(pBody ? pBody.querySelectorAll('tr[data-buyer]') : []);
                const pPagWrap = document.getElementById('pickupOrdersPagination');
                const pPrev = document.getElementById('pickupOrdersPrev');
                const pNext = document.getElementById('pickupOrdersNext');
                const pNums = document.getElementById('pickupOrdersPageNums');
                const pInfo = document.getElementById('pickupOrdersPageInfo');
                let pPage = 1; const pPageSize = 10; let pFiltered = pRows.slice();

                function filterPickup(){
                    const q = (pSearch?.value||'').trim().toLowerCase();
                    const st = pStatus?.value || '';
                    pFiltered = pRows.filter(r=>{
                        if(!q && !st) return true;
                        const buyer = r.dataset.buyer||'';
                        const status = r.dataset.status||'';
                        const itemsTxt = r.querySelector('td:nth-child(2)')?.innerText.toLowerCase()||'';
                        if(q && !(buyer.includes(q) || itemsTxt.includes(q))) return false;
                        if(st && status !== st) return false;
                        return true;
                    });
                    pPage=1; renderPickup();
                }
                function renderPickup(){
                    pRows.forEach(r=>r.classList.add('hidden'));
                    const total = pFiltered.length;
                    const pages = Math.max(1, Math.ceil(total / pPageSize));
                    if(pPage>pages) pPage=pages;
                    const start=(pPage-1)*pPageSize; const slice=pFiltered.slice(start,start+pPageSize);
                    slice.forEach(r=>r.classList.remove('hidden'));
                    if(total>pPageSize){
                        pPagWrap?.classList.remove('hidden');
                        if(pNums) pNums.innerHTML='';
                        for(let i=1;i<=pages;i++){
                            const b=document.createElement('button');
                            b.textContent=i; b.className='px-2 py-1 text-xs rounded-md '+(i===pPage?'bg-emerald-600 text-white':'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50');
                            b.addEventListener('click',()=>{pPage=i; renderPickup();});
                            pNums.appendChild(b);
                        }
                        if(pPrev) pPrev.onclick=()=>{ if(pPage>1){pPage--; renderPickup();} };
                        if(pNext) pNext.onclick=()=>{ if(pPage<pages){pPage++; renderPickup();} };
                        if(pInfo) pInfo.textContent = `Showing ${start+1}-${start+slice.length} of ${total}`;
                    } else { pPagWrap?.classList.add('hidden'); }
                }
                pSearch?.addEventListener('input', filterPickup);
                pStatus?.addEventListener('change', filterPickup);
                renderPickup();

                // Delivery elements
                const dSearch = document.getElementById('deliveryOrdersSearch');
                const dStatus = document.getElementById('deliveryStatusFilter');
                const dPayment = document.getElementById('deliveryPaymentFilter');
                const dBody = document.getElementById('deliveryOrdersTableBody');
                const dRows = Array.from(dBody ? dBody.querySelectorAll('tr[data-buyer]') : []);
                const dPagWrap = document.getElementById('deliveryOrdersPagination');
                const dPrev = document.getElementById('deliveryOrdersPrev');
                const dNext = document.getElementById('deliveryOrdersNext');
                const dNums = document.getElementById('deliveryOrdersPageNums');
                const dInfo = document.getElementById('deliveryOrdersPageInfo');
                let dPage = 1; const dPageSize = 10; let dFiltered = dRows.slice();
                function filterDelivery(){
                    const q=(dSearch?.value||'').trim().toLowerCase();
                    const st=(dStatus?.value||'').toLowerCase();
                    const pay=(dPayment?.value||'').toLowerCase();
                    dFiltered = dRows.filter(r=>{
                        const status=(r.dataset.status||'').toLowerCase();
                        const payment=(r.dataset.payment||'').toLowerCase();
                        const items=(r.dataset.items||'').toLowerCase();
                        if(st && status!==st) return false;
                        if(pay && payment!==pay) return false;
                        if(q && !(items.includes(q) || status.includes(q) || payment.includes(q))) return false;
                        return true;
                    });
                    dPage=1; renderDelivery();
                }
                function renderDelivery(){
                    dRows.forEach(r=>r.classList.add('hidden'));
                    const total=dFiltered.length; const pages=Math.max(1, Math.ceil(total/dPageSize)); if(dPage>pages) dPage=pages;
                    const start=(dPage-1)*dPageSize; const slice=dFiltered.slice(start,start+dPageSize);
                    slice.forEach(r=>r.classList.remove('hidden'));
                    if(total>dPageSize){
                        dPagWrap?.classList.remove('hidden');
                        if(dNums) dNums.innerHTML='';
                        for(let i=1;i<=pages;i++){
                            const b=document.createElement('button'); b.textContent=i; b.className='px-2 py-1 text-xs rounded-md '+(i===dPage?'bg-blue-600 text-white':'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'); b.addEventListener('click',()=>{dPage=i; renderDelivery();}); dNums.appendChild(b);
                        }
                        if(dPrev) dPrev.onclick=()=>{ if(dPage>1){dPage--; renderDelivery();} };
                        if(dNext) dNext.onclick=()=>{ if(dPage<pages){dPage++; renderDelivery();} };
                        if(dInfo) dInfo.textContent=`Showing ${start+1}-${start+slice.length} of ${total}`;
                    } else { dPagWrap?.classList.add('hidden'); }
                }
                dSearch?.addEventListener('input', filterDelivery);
                dStatus?.addEventListener('change', filterDelivery);
                dPayment?.addEventListener('change', filterDelivery);
                renderDelivery();

                // Modals
                const pickupModal = document.getElementById('pickupEditModal');
                const pickupCloseBtns = pickupModal? pickupModal.querySelectorAll('[data-close-pickup]'):[];
                const deliveryModal = document.getElementById('deliveryEditModal');
                const deliveryCloseBtns = deliveryModal? deliveryModal.querySelectorAll('[data-close-delivery]'):[];
                function openModal(m){ m?.classList.remove('hidden'); m?.classList.add('flex'); }
                function closeModal(m){ m?.classList.add('hidden'); m?.classList.remove('flex'); }
                pickupCloseBtns.forEach(b=>b.addEventListener('click', ()=>closeModal(pickupModal)));
                deliveryCloseBtns.forEach(b=>b.addEventListener('click', ()=>closeModal(deliveryModal)));
                [pickupModal, deliveryModal].forEach(m=> m?.addEventListener('click', e=>{ if(e.target===m) closeModal(m); }));
                window.addEventListener('keydown', e=>{ if(e.key==='Escape'){ closeModal(pickupModal); closeModal(deliveryModal);} });
                document.addEventListener('click', e=>{
                    const pBtn = e.target.closest('[data-edit-pickup]');
                    if(pBtn){
                        const tr = pBtn.closest('tr');
                        const tid = pBtn.getAttribute('data-id');
                        document.getElementById('pickup_edit_tid').value=tid;
                        document.getElementById('pickup_edit_date').value = tr.querySelector('td:nth-child(3)')?.innerText.trim()||'';
                        document.getElementById('pickup_edit_time').value = tr.querySelector('td:nth-child(4)')?.innerText.trim()||'';
                        const st = tr.querySelector('td:nth-child(5) span')?.innerText.trim().toLowerCase().replace(/ /g,'_')||'scheduled';
                        document.getElementById('pickup_edit_status').value = st;
                        openModal(pickupModal);
                    }
                    const dBtn = e.target.closest('[data-edit-delivery]');
                    if(dBtn){
                        const tr = dBtn.closest('tr');
                        const tid = dBtn.getAttribute('data-id');
                        document.getElementById('delivery_edit_tid').value=tid;
                        document.getElementById('delivery_edit_status').value = (tr.querySelector('td:nth-child(4) span')?.innerText.trim().toLowerCase().replace(/ /g,'_'))||'processing';
                        document.getElementById('delivery_edit_eta').value = tr.querySelector('td:nth-child(5)')?.innerText.trim()||'';
                        document.getElementById('delivery_edit_actual').value = tr.querySelector('td:nth-child(6)')?.innerText.trim()||'';
                        const rec = tr.querySelector('td:nth-child(7)')?.innerText.includes('Received');
                        document.getElementById('delivery_edit_signature').checked = !!rec;
                        openModal(deliveryModal);
                    }
                });
            })();
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
            // Include 'orders' so it hides when navigating to other sections
            const sections = ['dashboard', 'products', 'orders', 'sitters', 'appointments', 'pets', 'subscribers'];
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

        // Removed legacy sitters modal helpers; replaced by new inline JS

        // Data population functions
        // Removed populateProducts(); products now server-rendered.

        // Removed populateSitters; sitters are server-rendered

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

        // removed legacy populatePetOwners(); owners are server-rendered and paginated

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
        // Add product form handler (AJAX)
        (function(){
            const form = document.getElementById('addProductForm');
            const imgInput = document.getElementById('products_image');
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            const clearBtn = document.getElementById('clearImageBtn');

            function resetPreview(){
                preview.src = '';
                preview.classList.add('hidden');
                placeholder.classList.remove('hidden');
                clearBtn.classList.add('hidden');
            }

            imgInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    resetPreview();
                }
            });

            clearBtn.addEventListener('click', function(){
                imgInput.value = '';
                resetPreview();
            });

            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(form);
                try {
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=add', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Failed to add product');

                    // Update table without full reload
                    const tbody = document.getElementById('productsTableBody');
                    const p = data.item;
                    const row = document.createElement('tr');
                    row.setAttribute('data-id', String(p.id));
                    row.setAttribute('data-name', (p.name || '').toLowerCase());
                    row.setAttribute('data-pet-type', p.pet_type || '');
                    row.setAttribute('data-category', p.category_value || '');
                    row.setAttribute('data-active', p.active ? '1' : '0');
                    row.setAttribute('data-stock', String(p.stock_int ?? 0));
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg overflow-hidden">
                                    ${p.image ? `<img src="${p.image}" alt="${p.name}" class="w-full h-full object-cover">` : ''}
                                </div>
                                <div><p class="font-medium">${p.name}</p></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${p.category}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${Number(p.price).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${p.stock}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${p.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${p.status === 'active' ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <button class="p-1 text-gray-400 hover:text-gray-600 btn-edit" data-action="edit" title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <button class="p-1 text-red-400 hover:text-red-600 btn-delete" data-action="delete" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>`;
                    tbody.prepend(row);
                    lucide.createIcons();

                    // Re-apply filters after adding
                    applyProductFilters();

                    alert('Product added successfully!');
                    closeAddProductModal();
                    form.reset();
                    resetPreview();
                } catch (err) {
                    alert(err.message);
                }
            });
        })();

        

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            const productModal = document.getElementById('addProductModal');
            const sitterModal = document.getElementById('addSitterModal');
            const editModal = document.getElementById('editProductModal');
            
            if (e.target === productModal) {
                closeAddProductModal();
            }
            if (e.target === sitterModal) {
                closeAddSitterModal();
            }
            if (e.target === editModal) {
                closeEditProductModal();
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

        // Edit Product modal helpers
        function openEditProductModal() {
            const modal = document.getElementById('editProductModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeEditProductModal() {
            const modal = document.getElementById('editProductModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('editProductForm').reset();
            resetEditPreview();
        }
        function resetEditPreview(){
            const preview = document.getElementById('edit_imagePreview');
            const placeholder = document.getElementById('edit_imagePlaceholder');
            const clearBtn = document.getElementById('edit_clearImageBtn');
            preview.src='';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            clearBtn.classList.add('hidden');
        }

        (function initEditModal(){
            const imgInput = document.getElementById('edit_products_image');
            const preview = document.getElementById('edit_imagePreview');
            const placeholder = document.getElementById('edit_imagePlaceholder');
            const clearBtn = document.getElementById('edit_clearImageBtn');
            imgInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file){
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    resetEditPreview();
                }
            });
            clearBtn.addEventListener('click', function(){
                imgInput.value = '';
                resetEditPreview();
            });
        })();

        // Delegate edit/delete buttons
        document.addEventListener('click', async function(e){
            const editBtn = e.target.closest('.btn-edit');
            const delBtn = e.target.closest('.btn-delete');
            if (editBtn) {
                const row = editBtn.closest('tr');
                const id = row?.getAttribute('data-id');
                if (!id) return;
                try {
                    const res = await fetch(`../../controllers/admin/productcontroller.php?action=get&id=${encodeURIComponent(id)}`);
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Failed to fetch product');
                    const p = data.item;
                    // Fill form
                    document.getElementById('edit_products_id').value = p.id;
                    document.getElementById('edit_products_name').value = p.name || '';
                    document.getElementById('edit_products_pet_type').value = p.pet_type || '';
                    // Map enum to input choices for category
                    const inputCatMap = { accessory: 'accessories', necessity: 'grooming', toy: 'treats', food: 'food' };
                    document.getElementById('edit_products_category').value = inputCatMap[p.category_value] || p.category_value || '';
                    document.getElementById('edit_products_price').value = p.price ?? '';
                    document.getElementById('edit_products_stock').value = p.stock ?? '';
                    document.getElementById('edit_products_description').value = p.description || '';
                    document.getElementById('edit_products_active').checked = !!p.active;
                    document.getElementById('edit_current_image_url').value = p.db_image_url || '';
                    const preview = document.getElementById('edit_imagePreview');
                    const placeholder = document.getElementById('edit_imagePlaceholder');
                    const clearBtn = document.getElementById('edit_clearImageBtn');
                    if (p.image) {
                        preview.src = p.image;
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                        clearBtn.classList.remove('hidden');
                    } else {
                        resetEditPreview();
                    }
                    openEditProductModal();
                } catch(err) {
                    alert(err.message);
                }
            } else if (delBtn) {
                const row = delBtn.closest('tr');
                const id = row?.getAttribute('data-id');
                if (!id) return;
                if (!confirm('Delete this product? This action cannot be undone.')) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('products_id', id);
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=delete', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Delete failed');
                    row.remove();
                    applyProductFilters();
                } catch(err) {
                    alert(err.message);
                }
            }
        });

        // Submit edit form
        (function(){
            const form = document.getElementById('editProductForm');
            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(form);
                try {
                    const res = await fetch('../../controllers/admin/productcontroller.php?action=update', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Update failed');
                    const p = data.item;
                    const row = document.querySelector(`#productsTableBody tr[data-id="${p.id}"]`);
                    if (row) {
                        row.setAttribute('data-name', (p.name || '').toLowerCase());
                        row.setAttribute('data-pet-type', p.pet_type || '');
                        row.setAttribute('data-category', p.category_value || '');
                        row.setAttribute('data-active', p.active ? '1' : '0');
                        row.setAttribute('data-stock', String(p.stock_int ?? 0));
                        // Update visible cells
                        const nameEl = row.querySelector('td:nth-child(1) .font-medium');
                        if (nameEl) nameEl.textContent = p.name;
                        const imgEl = row.querySelector('td:nth-child(1) img');
                        if (imgEl && p.image) imgEl.src = p.image;
                        const catEl = row.querySelector('td:nth-child(2)');
                        if (catEl) catEl.textContent = p.category;
                        const priceEl = row.querySelector('td:nth-child(3)');
                        if (priceEl) priceEl.textContent = `₱${Number(p.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        const stockEl = row.querySelector('td:nth-child(4)');
                        if (stockEl) stockEl.textContent = p.stock;
                        const statusChip = row.querySelector('td:nth-child(5) span');
                        if (statusChip) {
                            statusChip.textContent = p.active ? 'Active' : 'Inactive';
                            statusChip.className = `px-2 py-1 text-xs rounded-full ${p.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                        }
                    }
                    applyProductFilters();
                    alert('Product updated successfully!');
                    closeEditProductModal();
                } catch(err) {
                    alert(err.message);
                }
            });
        })();

        // Products filtering and search
        function initProductFilters() {
            const search = document.getElementById('productsSearch');
            const filterInputs = Array.from(document.querySelectorAll('input[name="petType"], input[name="category"], input[name="active"], input[name="stock"]'));

            let debounceTimer;
            function onSearchInput() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => { currentProductsPage = 1; applyProductFilters(); }, 120);
            }

            if (search) search.addEventListener('input', onSearchInput);
            filterInputs.forEach(el => el.addEventListener('change', () => { currentProductsPage = 1; applyProductFilters(); }));

            applyProductFilters();
        }

        function getCheckedValues(name) {
            return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map(el => el.value);
        }

        // Pagination state
        let currentProductsPage = 1;
        const pageSize = 10;

        function applyProductFilters() {
            const searchVal = (document.getElementById('productsSearch')?.value || '').trim().toLowerCase();
            const petTypes = getCheckedValues('petType');
            const categories = getCheckedValues('category');
            const actives = getCheckedValues('active');
            const stockFilters = getCheckedValues('stock');

            const rows = Array.from(document.querySelectorAll('#productsTableBody tr'));
            // Compute which rows match
            const matches = [];
            rows.forEach(row => {
                if (row.querySelector('td')?.getAttribute('colspan') === '6') {
                    // Skip the "No products" row
                    return;
                }

                const name = row.getAttribute('data-name') || '';
                const pet = row.getAttribute('data-pet-type') || '';
                const cat = row.getAttribute('data-category') || '';
                const active = row.getAttribute('data-active') || '';
                const stockVal = parseInt(row.getAttribute('data-stock') || '0', 10);

                let visible = true;

                // Search: show if name includes any letter typed (even single letter)
                if (searchVal && !name.includes(searchVal)) visible = false;

                // Pet type filter
                if (visible && petTypes.length > 0 && !petTypes.includes(pet)) visible = false;

                // Category filter (values match enum values in DB)
                if (visible && categories.length > 0 && !categories.includes(cat)) visible = false;

                // Active filter (row has '1' or '0')
                if (visible && actives.length > 0 && !actives.includes(active)) visible = false;

                // Stock filter
                if (visible && stockFilters.length > 0) {
                    const inSelected = stockFilters.includes('in');
                    const outSelected = stockFilters.includes('out');
                    const isIn = stockVal >= 1;
                    const isOut = stockVal === 0;
                    if (!( (inSelected && isIn) || (outSelected && isOut) )) visible = false;
                }

                if (visible) matches.push(row);
            });

            // Toggle empty state row
            const tbody = document.getElementById('productsTableBody');
            const dataRows = rows.filter(r => !(r.querySelector('td')?.getAttribute('colspan') === '6'));
            const anyVisible = matches.length > 0;
            let emptyRow = tbody.querySelector('tr[data-empty]');
            if (!anyVisible) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.setAttribute('data-empty', '1');
                    emptyRow.innerHTML = '<td colspan="6" class="px-6 py-6 text-center text-gray-500">No products match your filters.</td>';
                    tbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }

            // Apply pagination to matches
            const pagination = document.getElementById('productsPagination');
            const pageInfo = document.getElementById('productsPageInfo');
            const prevBtn = document.getElementById('productsPrev');
            const nextBtn = document.getElementById('productsNext');

            // Hide all rows first
            dataRows.forEach(r => { r.style.display = 'none'; });

            const total = matches.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (currentProductsPage > totalPages) currentProductsPage = totalPages;
            const start = (currentProductsPage - 1) * pageSize;
            const end = start + pageSize;
            const pageRows = matches.slice(start, end);
            pageRows.forEach(r => { r.style.display = ''; });

            const numsWrap = document.getElementById('productsPageNums');
            if (numsWrap) numsWrap.innerHTML = '';
            if (total > pageSize) {
                pagination.classList.remove('hidden');
                pageInfo.textContent = `Page ${currentProductsPage} of ${totalPages} • ${total} item${total === 1 ? '' : 's'}`;
                // numeric buttons range
                const pages = paginationRange(currentProductsPage, totalPages, 5);
                pages.forEach(p => {
                    if (p === '...') {
                        const span = document.createElement('span');
                        span.textContent = '...';
                        span.className = 'px-1 text-gray-500';
                        numsWrap.appendChild(span);
                    } else {
                        const btn = document.createElement('button');
                        btn.textContent = p;
                        btn.className = 'px-2.5 py-1 border rounded text-sm ' + (p === currentProductsPage ? 'bg-gray-900 text-white border-gray-900' : 'hover:bg-gray-100');
                        btn.addEventListener('click', () => { currentProductsPage = p; applyProductFilters(); });
                        numsWrap.appendChild(btn);
                    }
                });
                prevBtn.disabled = currentProductsPage === 1;
                nextBtn.disabled = currentProductsPage === totalPages;
                prevBtn.classList.toggle('opacity-50', prevBtn.disabled);
                nextBtn.classList.toggle('opacity-50', nextBtn.disabled);
            } else { pagination.classList.add('hidden'); }
        }

        // Pagination controls
        document.addEventListener('click', function(e){
            const prev = e.target.closest('#productsPrev');
            const next = e.target.closest('#productsNext');
            if (prev) { if (currentProductsPage > 1) { currentProductsPage--; applyProductFilters(); } }
            if (next) { currentProductsPage++; applyProductFilters(); }
        });
        function paginationRange(current,total,maxButtons){
            const range=[]; if(total<=maxButtons){ for(let i=1;i<=total;i++) range.push(i); return range; }
            const half=Math.floor(maxButtons/2); let start=Math.max(1,current-half); let end=start+maxButtons-1; if(end>total){end=total; start=end-maxButtons+1;} if(start>1){ range.push(1); if(start>2) range.push('...'); } for(let i=start;i<=end;i++) range.push(i); if(end<total){ if(end<total-1) range.push('...'); range.push(total);} return range;
        }

        // ================= Subscribers Filtering & Search (Isolated) =================
        (function initSubscribersFilters(){
            const tbody = document.getElementById('subsTableBody');
            if(!tbody) return; // safety if section removed
            const searchInput = document.getElementById('subsSearch');
            const statusFilter = document.getElementById('subsStatusFilter');
            const shownEl = document.getElementById('subsShownCount');
            const totalEl = document.getElementById('subsTotalCount');
            const pagerWrap = document.getElementById('subsPager');
            const prevBtn = document.getElementById('subsPrev');
            const nextBtn = document.getElementById('subsNext');
            const pageNumsWrap = document.getElementById('subsPageNums');
            const pageSize = 15;
            let page = 1;
            const allRows = Array.from(tbody.querySelectorAll('tr[data-status]'));
            if(totalEl) totalEl.textContent = allRows.length.toString();

            function ensureEmptyRow(){
                let emptyRow = tbody.querySelector('tr[data-empty]');
                if(!emptyRow){
                    emptyRow = document.createElement('tr');
                    emptyRow.setAttribute('data-empty','1');
                    emptyRow.innerHTML = '<td colspan="5" class="py-6 px-3 text-center text-gray-500 text-sm">No subscribers match.</td>';
                }
                return emptyRow;
            }

            function apply(){
                const q = (searchInput?.value || '').trim().toLowerCase();
                const statusVal = statusFilter?.value || '';
                const filtered = allRows.filter(r => {
                    const name = (r.getAttribute('data-name')||'').toLowerCase();
                    const email = (r.getAttribute('data-email')||'').toLowerCase();
                    const st = (r.getAttribute('data-status')||'').toLowerCase();
                    if(statusVal && st !== statusVal) return false;
                    if(q && !(name.includes(q) || email.includes(q))) return false;
                    return true;
                });
                // Hide all first
                allRows.forEach(r => r.style.display='none');
                // Remove existing empty placeholder(s)
                tbody.querySelectorAll('tr[data-empty]')?.forEach(er => er.remove());

                const total = filtered.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                if(page > totalPages) page = totalPages; // clamp
                const start = (page-1)*pageSize;
                const end = start + pageSize;
                const pageRows = filtered.slice(start,end);
                pageRows.forEach(r => r.style.display='');

                if(shownEl) shownEl.textContent = total.toString() === '0' ? '0' : `${start+1}-${start+pageRows.length}`;
                if(totalEl && !totalEl.textContent) totalEl.textContent = allRows.length.toString();
                // If no matches show empty row
                if(total === 0){ tbody.appendChild(ensureEmptyRow()); }

                // Pager visibility
                if(total > pageSize){
                    pagerWrap && (pagerWrap.style.display='flex');
                    const totalPagesReal = Math.ceil(total / pageSize);
                    prevBtn.disabled = page <= 1;
                    nextBtn.disabled = page >= totalPagesReal;
                    prevBtn.classList.toggle('opacity-40', prevBtn.disabled);
                    nextBtn.classList.toggle('opacity-40', nextBtn.disabled);
                    if(pageNumsWrap){
                        pageNumsWrap.innerHTML = '';
                        const nums = paginationRange(page, totalPagesReal, 5);
                        nums.forEach(n => {
                            const btn = document.createElement('button');
                            btn.className = 'px-2 py-1 rounded text-xs border';
                            if(n === '...'){
                                btn.textContent = '...';
                                btn.disabled = true;
                                btn.classList.add('opacity-50');
                            } else {
                                btn.textContent = n;
                                if(n === page){
                                    btn.classList.add('bg-orange-500','text-white','border-orange-500');
                                } else {
                                    btn.classList.add('bg-white','hover:bg-orange-50');
                                    btn.addEventListener('click', ()=>{ page = n; apply(); });
                                }
                            }
                            pageNumsWrap.appendChild(btn);
                        });
                    }
                } else {
                    pagerWrap && (pagerWrap.style.display='none');
                }
            }

            searchInput?.addEventListener('input', () => { page = 1; apply(); });
            statusFilter?.addEventListener('change', () => { page = 1; apply(); });
            prevBtn?.addEventListener('click', () => { if(page>1){ page--; apply(); }});
            nextBtn?.addEventListener('click', () => { page++; apply(); });

            apply();
        })();
    </script>
    <!-- Tailwind safelist (hidden) to ensure dynamic specialty classes are included by CDN JIT -->
    <div class="hidden">
        <span class="bg-orange-100 text-orange-800 border border-orange-200"></span>
        <span class="bg-purple-100 text-purple-800 border border-purple-200"></span>
        <span class="bg-blue-100 text-blue-800 border border-blue-200"></span>
        <span class="bg-cyan-100 text-cyan-800 border border-cyan-200"></span>
        <span class="bg-emerald-100 text-emerald-800 border border-emerald-200"></span>
        <span class="bg-pink-100 text-pink-800 border border-pink-200"></span>
        <span class="bg-yellow-100 text-yellow-800 border border-yellow-200"></span>
        <span class="bg-lime-100 text-lime-800 border border-lime-200"></span>
        <span class="bg-amber-100 text-amber-800 border border-amber-200"></span>
        <span class="bg-rose-100 text-rose-800 border border-rose-200"></span>
        <span class="bg-indigo-100 text-indigo-800 border border-indigo-200"></span>
        <span class="bg-gray-100 text-gray-800 border border-gray-200"></span>
    </div>
</body>
</html>