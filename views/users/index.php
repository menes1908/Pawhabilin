<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - pawhabilin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    <link href="styles/globals.css" rel="stylesheet">
    <style>
        .sidebar-transition {
            transition: width 300ms ease-in-out;
        }
        .content-transition {
            transition: margin-left 300ms ease-in-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        .profile-card {
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .edit-mode input:not(:disabled),
        .edit-mode textarea:not(:disabled) {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .edit-mode input:focus:not(:disabled),
        .edit-mode textarea:focus:not(:disabled) {
            border-color: #f97316;
            ring-color: rgba(249, 115, 22, 0.2);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- User Profile Container -->
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
                <button onclick="setActiveSection('profile')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 bg-gradient-to-r from-orange-500 to-amber-600 text-white shadow-md" data-section="profile">
                    <i data-lucide="user" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">My Profile</span>
                </button>
                <button onclick="setActiveSection('bookings')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="bookings">
                    <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">My Bookings</span>
                </button>
                <button onclick="setActiveSection('pets')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="pets">
                    <i data-lucide="paw-print" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">My Pets</span>
                </button>
                <button onclick="setActiveSection('favorites')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="favorites">
                    <i data-lucide="heart" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Favorites</span>
                </button>
                <button onclick="setActiveSection('messages')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="messages">
                    <i data-lucide="message-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Messages</span>
                </button>
                <button onclick="setActiveSection('settings')" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-gray-700 hover:bg-gray-100" data-section="settings">
                    <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="sidebar-label font-medium hidden">Settings</span>
                </button>
            </nav>

            <!-- User Info -->
            <div id="adminInfo" class="hidden absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        J
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">John Doe</p>
                        <p class="text-xs text-gray-500 truncate">john@email.com</p>
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
                    <div class="flex items-center gap-2">
                        <i data-lucide="user" class="w-5 h-5 text-orange-500"></i>
                        <span class="font-semibold text-gray-800">My Profile</span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button class="relative p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs flex items-center justify-center text-white font-semibold">2</div>
                    </button>
                    <button class="p-2 rounded-md hover:bg-gray-100">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                    </button>
                    <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold">
                        J
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <!-- Profile Section -->
                <div id="profile-section" class="space-y-6">
                    <!-- Profile Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                My Profile
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Manage your personal information and account settings
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button id="editButton" onclick="toggleEditMode()" class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition-all duration-200">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                                Request Update
                            </button>
                            <button id="saveButton" onclick="saveProfile()" class="hidden bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition-all duration-200">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Save Changes
                            </button>
                            <button id="cancelButton" onclick="cancelEdit()" class="hidden border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 flex items-center gap-2 transition-all duration-200">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Profile Cards -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Profile Image Card -->
                        <div class="lg:col-span-1">
                            <div class="profile-card bg-white rounded-lg border border-gray-200 p-6">
                                <div class="text-center space-y-4">
                                    <div class="relative inline-block">
                                        <div class="w-32 h-32 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white text-4xl font-semibold mx-auto">
                                            J
                                        </div>
                                        <button class="absolute bottom-2 right-2 w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white hover:bg-orange-600 transition-colors">
                                            <i data-lucide="camera" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">John Doe</h3>
                                        <p class="text-gray-600">Pet Owner</p>
                                        <div class="flex items-center justify-center gap-1 mt-2">
                                            <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                            <span class="text-sm font-medium">4.9</span>
                                            <span class="text-sm text-gray-600">(24 reviews)</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-orange-600">12</div>
                                            <div class="text-sm text-gray-600">Bookings</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-purple-600">3</div>
                                            <div class="text-sm text-gray-600">Pets</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Information Card -->
                        <div class="lg:col-span-2">
                            <div class="profile-card bg-white rounded-lg border border-gray-200 p-6">
                                <div class="space-y-6">
                                    <div class="border-b border-gray-200 pb-4">
                                        <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                            <i data-lucide="user" class="w-5 h-5 text-orange-500"></i>
                                            Personal Information
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1">Basic information about yourself</p>
                                    </div>

                                    <form id="profileForm" class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                                <input type="text" id="firstName" value="John" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                                <input type="text" id="lastName" value="Doe" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Email Address 
                                                    <span class="text-orange-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <i data-lucide="mail" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                                    <input type="email" id="email" value="john@email.com" disabled class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">This will be used for login credentials</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Mobile Number 
                                                    <span class="text-orange-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <i data-lucide="phone" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                                                    <input type="tel" id="phone" value="+63 912 345 6789" disabled class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">This will be used for login credentials</p>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                            <input type="date" id="dateOfBirth" value="1990-05-15" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                            <textarea id="address" rows="3" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">123 Pet Street, Cebu City, Philippines 6000</textarea>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                                <select id="gender" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                                    <option value="male" selected>Male</option>
                                                    <option value="female">Female</option>
                                                    <option value="other">Other</option>
                                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                                                <input type="text" value="March 15, 2024" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact Card -->
                    <div class="profile-card bg-white rounded-lg border border-gray-200 p-6">
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i data-lucide="shield" class="w-5 h-5 text-red-500"></i>
                                    Emergency Contact
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">Contact person in case of emergency</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <input type="text" id="emergencyName" value="Jane Doe" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                                    <input type="text" id="emergencyRelation" value="Sister" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" id="emergencyPhone" value="+63 917 234 5678" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="emergencyEmail" value="jane@email.com" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Security Card -->
                    <div class="profile-card bg-white rounded-lg border border-gray-200 p-6">
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i data-lucide="lock" class="w-5 h-5 text-green-500"></i>
                                    Account Security
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">Manage your password and security settings</p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Password</p>
                                            <p class="text-sm text-gray-600">Last updated 2 months ago</p>
                                        </div>
                                    </div>
                                    <button class="text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                                        Change Password
                                    </button>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="smartphone" class="w-5 h-5 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Two-Factor Authentication</p>
                                            <p class="text-sm text-gray-600">Not enabled</p>
                                        </div>
                                    </div>
                                    <button class="text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                                        Enable 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other sections (hidden by default) -->
                <div id="bookings-section" class="space-y-6 hidden">
                    <div>
                        <h1 class="text-3xl font-bold">My Bookings</h1>
                        <p class="text-gray-600 mt-1">Track your past and upcoming pet care bookings</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                        <i data-lucide="calendar" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No bookings yet</h3>
                        <p class="text-gray-600 mb-4">Start booking pet care services to see them here</p>
                        <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-6 py-2 rounded-md">
                            Browse Sitters
                        </button>
                    </div>
                </div>

                <div id="pets-section" class="space-y-6 hidden">
                    <div>
                        <h1 class="text-3xl font-bold">My Pets</h1>
                        <p class="text-gray-600 mt-1">Manage your beloved pets' information</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                        <i data-lucide="paw-print" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No pets added yet</h3>
                        <p class="text-gray-600 mb-4">Add your pets to find the perfect sitter for them</p>
                        <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-6 py-2 rounded-md">
                            Add Pet
                        </button>
                    </div>
                </div>

                <div id="favorites-section" class="space-y-6 hidden">
                    <div>
                        <h1 class="text-3xl font-bold">Favorite Sitters</h1>
                        <p class="text-gray-600 mt-1">Your trusted pet care providers</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                        <i data-lucide="heart" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No favorites yet</h3>
                        <p class="text-gray-600 mb-4">Save your favorite sitters for quick booking</p>
                        <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-6 py-2 rounded-md">
                            Find Sitters
                        </button>
                    </div>
                </div>

                <div id="messages-section" class="space-y-6 hidden">
                    <div>
                        <h1 class="text-3xl font-bold">Messages</h1>
                        <p class="text-gray-600 mt-1">Chat with your pet sitters</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                        <i data-lucide="message-circle" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No messages yet</h3>
                        <p class="text-gray-600 mb-4">Start a conversation with a pet sitter</p>
                        <button class="bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white px-6 py-2 rounded-md">
                            Browse Sitters
                        </button>
                    </div>
                </div>

                <div id="settings-section" class="space-y-6 hidden">
                    <div>
                        <h1 class="text-3xl font-bold">Account Settings</h1>
                        <p class="text-gray-600 mt-1">Manage your account preferences</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-800">Notification Preferences</h4>
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Email Notifications</p>
                                        <p class="text-sm text-gray-600">Receive updates via email</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">SMS Notifications</p>
                                        <p class="text-sm text-gray-600">Receive updates via SMS</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Update Request Modal -->
    <div id="updateRequestModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                    Update Request Sent
                </h3>
                <button onclick="closeUpdateModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-green-600 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">Request Submitted Successfully</p>
                            <p class="text-sm text-green-700 mt-1">
                                Your profile update request has been sent to the admin team. You will receive a notification once your changes are approved and your login credentials are updated.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <p class="font-medium mb-2">What happens next?</p>
                    <ul class="space-y-1 text-sm">
                        <li class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-3 h-3 text-gray-400"></i>
                            Admin review (24-48 hours)
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i>
                            Email notification when approved
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="key" class="w-3 h-3 text-gray-400"></i>
                            Login credentials updated automatically
                        </li>
                    </ul>
                </div>
                <button onclick="closeUpdateModal()" class="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white py-2 rounded-md">
                    Got it
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global state
        let currentActiveSection = 'profile';
        let sidebarExpanded = false;
        let sidebarLocked = false;
        let isEditMode = false;

        // Original values for reset
        const originalValues = {
            email: 'john@email.com',
            phone: '+63 912 345 6789'
        };

        // Initialize the app
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
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
            const sections = ['profile', 'bookings', 'pets', 'favorites', 'messages', 'settings'];
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

        // Edit mode functions
        function toggleEditMode() {
            isEditMode = true;
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            const profileForm = document.getElementById('profileForm');

            // Show/hide buttons
            editButton.classList.add('hidden');
            saveButton.classList.remove('hidden');
            cancelButton.classList.remove('hidden');

            // Enable only email and phone fields
            document.getElementById('email').disabled = false;
            document.getElementById('phone').disabled = false;

            // Add edit mode class for styling
            profileForm.classList.add('edit-mode');

            // Update icons
            lucide.createIcons();
        }

        function cancelEdit() {
            isEditMode = false;
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            const profileForm = document.getElementById('profileForm');

            // Show/hide buttons
            editButton.classList.remove('hidden');
            saveButton.classList.add('hidden');
            cancelButton.classList.add('hidden');

            // Disable fields and reset values
            document.getElementById('email').disabled = true;
            document.getElementById('phone').disabled = true;
            document.getElementById('email').value = originalValues.email;
            document.getElementById('phone').value = originalValues.phone;

            // Remove edit mode class
            profileForm.classList.remove('edit-mode');

            // Update icons
            lucide.createIcons();
        }

        function saveProfile() {
            const newEmail = document.getElementById('email').value;
            const newPhone = document.getElementById('phone').value;

            // Basic validation
            if (!newEmail || !newPhone) {
                alert('Please fill in all required fields.');
                return;
            }

            if (!isValidEmail(newEmail)) {
                alert('Please enter a valid email address.');
                return;
            }

            if (!isValidPhone(newPhone)) {
                alert('Please enter a valid phone number.');
                return;
            }

            // Simulate saving process
            const saveButton = document.getElementById('saveButton');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...';
            saveButton.disabled = true;

            setTimeout(() => {
                // Reset button
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;

                // Update original values
                originalValues.email = newEmail;
                originalValues.phone = newPhone;

                // Exit edit mode
                cancelEdit();

                // Show success modal
                showUpdateModal();

                // Update icons
                lucide.createIcons();
            }, 2000);
        }

        function showUpdateModal() {
            document.getElementById('updateRequestModal').classList.remove('hidden');
            document.getElementById('updateRequestModal').classList.add('flex');
        }

        function closeUpdateModal() {
            document.getElementById('updateRequestModal').classList.add('hidden');
            document.getElementById('updateRequestModal').classList.remove('flex');
        }

        // Validation functions
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidPhone(phone) {
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            return phoneRegex.test(phone);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('updateRequestModal');
            if (e.target === modal) {
                closeUpdateModal();
            }
        });

        // Prevent form submission on enter in edit mode
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (isEditMode) {
                saveProfile();
            }
        });
    </script>
</body>
</html>