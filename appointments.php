<?php
require_once __DIR__ . '/utils/session.php';
session_start_if_needed();

// Require login before booking: redirect appropriately on submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']);
    if (!$isLoggedIn) {
        header('Location: login.php?redirect=' . rawurlencode('views/users/book_appointment.php'));
        exit;
    }
    header('Location: views/users/book_appointment.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment - pawhabilin</title>
    
    <!-- Tailwind CSS v4.0 -->
    <script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="globals.css">
    
    <!-- Google Fonts - La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=La+Belle+Aurore&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.6); }
        }
        
        @keyframes slide-in-up {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-3deg); }
            75% { transform: rotate(3deg); }
        }
        
        @keyframes bounce-in {
            0% { transform: scale(0.3) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.05) rotate(5deg); }
            70% { transform: scale(0.9) rotate(-2deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        @keyframes heart-beat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 0.8; }
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(2) {
            animation-delay: -2s;
        }
        
        .floating-element:nth-child(3) {
            animation-delay: -4s;
        }
        
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        .slide-in-up {
            animation: slide-in-up 0.6s ease-out forwards;
        }
        
        .gradient-bg {
            background: linear-gradient(-45deg, #f97316, #fb923c, #fbbf24, #f59e0b);
            background-size: 400% 400%;
            animation: gradient-shift 8s ease infinite;
        }
        
        .service-card {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.8s ease;
            z-index: 1;
        }
        
        .service-card:hover::before {
            left: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-20px) scale(1.03) rotate(2deg);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        }
        
        .service-image {
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover .service-image {
            transform: scale(1.1) rotate(-2deg);
        }
        
        .service-icon {
            transition: all 0.4s ease;
            position: relative;
            z-index: 2;
        }
        
        .service-card:hover .service-icon {
            transform: scale(1.3) rotate(15deg);
            animation: wiggle 0.6s ease-in-out;
        }
        
        .service-price {
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .service-card:hover .service-price {
            transform: scale(1.1);
            animation: heart-beat 1s ease-in-out infinite;
        }
        
        .floating-badge {
            animation: bounce-in 0.8s ease-out;
            position: relative;
            z-index: 3;
        }
        
        .service-card:hover .floating-badge {
            animation: sparkle 2s ease-in-out infinite;
        }
        
        .morphing-border {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .morphing-border::after {
            content: '';
            position: absolute;
            inset: 0;
            padding: 3px;
            background: linear-gradient(45deg, #f97316, #fb923c, #fbbf24, #f59e0b, #f97316);
            background-size: 300% 300%;
            border-radius: inherit;
            animation: gradient-shift 4s ease infinite;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: xor;
            z-index: -1;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .feature-item {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .feature-item:hover {
            transform: translateX(10px);
            color: #f97316;
        }
        
        .feature-item::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #fbbf24);
            transition: width 0.3s ease;
            transform: translateY(-50%);
        }
        
        .feature-item:hover::before {
            width: 6px;
        }
        
        .booking-form {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: #fbbf24;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.3);
        }
        
        .sign-in-prompt {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        /* Scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #f97316, #fb923c);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #ea580c, #f97316);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .service-card:hover {
                transform: translateY(-10px) scale(1.02);
            }
            
            .service-card:hover .service-image {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <?php include __DIR__ . '/utils/header.php'; ?>

    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden gradient-bg">
        <!-- Floating background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-20 left-10 opacity-20">
                <i data-lucide="paw-print" class="w-24 h-24 text-white transform rotate-12"></i>
            </div>
            <div class="floating-element absolute top-40 right-20 opacity-20">
                <i data-lucide="heart" class="w-16 h-16 text-white transform -rotate-12"></i>
            </div>
            <div class="floating-element absolute bottom-20 left-1/4 opacity-20">
                <i data-lucide="stethoscope" class="w-20 h-20 text-white transform rotate-45"></i>
            </div>
            <div class="floating-element absolute top-1/2 right-1/4 opacity-15">
                <i data-lucide="scissors" class="w-18 h-18 text-white transform -rotate-45"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="space-y-8 slide-in-up">
                    <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium glass-effect">
                        <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                        Professional Pet Care Services
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-shadow">
                        Expert Care for Your
                        <span class="block brand-font text-5xl md:text-7xl lg:text-8xl text-yellow-200">Beloved Companions</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
                        From comprehensive veterinary care to professional grooming and trusted pet sitting, 
                        we provide exceptional services that keep your furry family members healthy, happy, and loved.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center pt-8">
                        <button onclick="scrollToBooking()" class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-semibold transition-all duration-300 bg-white text-orange-600 hover:bg-orange-50 h-14 px-8 transform hover:scale-105 hover:shadow-2xl">
                            <i data-lucide="calendar-plus" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300"></i>
                            Book Your Appointment
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </button>
                        
                        <button class="group inline-flex items-center justify-center gap-3 whitespace-nowrap rounded-full text-lg font-medium transition-all duration-300 border-2 border-white text-white hover:bg-white hover:text-orange-600 h-14 px-8 transform hover:scale-105">
                            <i data-lucide="play" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                            Watch Our Story
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Overview -->
    <section class="py-20 bg-white relative">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="heart" class="w-3 h-3 mr-1"></i>
                    Our Services
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                        Complete Pet Care Solutions
                    </span>
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Choose from our comprehensive range of professional services designed to keep your pets 
                    healthy, beautiful, and well-cared for in every aspect of their lives.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <!-- Veterinary Care -->
                <div class="service-card morphing-border bg-gradient-to-br from-red-50 to-pink-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        Most Popular
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1625321171045-1fea4ac688e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx2ZXRlcmluYXJ5JTIwY2FyZSUyMGRvZyUyMGNoZWNrdXB8ZW58MXx8fHwxNzU4NjEwNjA5fDA&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Veterinary Care" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-red-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="stethoscope" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Veterinary Care</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Comprehensive medical care from routine wellness exams to emergency treatments. 
                            Our licensed veterinarians provide expert diagnosis, treatment, and preventive care.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Comprehensive Health Examinations</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Vaccinations & Immunizations</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Emergency Medical Care</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Advanced Diagnostic Services</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Surgical Procedures</span>
                            </div>
                        </div>
                        
                        <div class="text-center service-price">
                            <div class="text-3xl font-bold text-red-600 mb-1">₱800+</div>
                            <div class="text-sm text-gray-500">Starting from</div>
                        </div>
                    </div>
                </div>

                <!-- Grooming Services -->
                <div class="service-card morphing-border bg-gradient-to-br from-blue-50 to-indigo-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        Premium
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1644675443401-ea4c14bad0e6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBkb2clMjBncm9vbWluZyUyMHNhbG9ufGVufDF8fHx8MTc1ODYxMDYxNHww&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Professional Grooming" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-blue-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="scissors" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Professional Grooming</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Expert grooming services to keep your pets looking and feeling their absolute best. 
                            From basic hygiene to full luxury spa treatments with premium products.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Full-Service Bathing & Drying</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Professional Hair Cutting & Styling</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Nail Trimming & Paw Care</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Ear Cleaning & Dental Hygiene</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Premium Spa Treatments</span>
                            </div>
                        </div>
                        
                        <div class="text-center service-price">
                            <div class="text-3xl font-bold text-blue-600 mb-1">₱600+</div>
                            <div class="text-sm text-gray-500">Starting from</div>
                        </div>
                    </div>
                </div>

                <!-- Pet Sitting -->
                <div class="service-card morphing-border bg-gradient-to-br from-green-50 to-emerald-50 p-8 text-center group cursor-pointer relative">
                    <div class="floating-badge absolute -top-3 -right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        24/7 Care
                    </div>
                    
                    <!-- Service Image -->
                    <div class="service-image w-full h-48 rounded-2xl overflow-hidden mb-6 relative">
                        <img src="https://images.unsplash.com/photo-1668522907255-62950845ff46?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBzaXR0ZXIlMjBwbGF5aW5nJTIwd2l0aCUyMGRvZ3N8ZW58MXx8fHwxNzU4NjEwNjE4fDA&ixlib=rb-4.1.0&q=80&w=1080" 
                             alt="Pet Sitting & Care" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-green-500/20 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="service-icon w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="users" class="w-8 h-8 text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Pet Sitting & Care</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Trusted, loving care when you can't be there. Our certified pet sitters provide 
                            personalized attention, ensuring your pets feel safe, loved, and entertained.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>In-Home Pet Sitting Services</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Daily Dog Walking & Exercise</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Overnight Pet Boarding</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Live Updates & Photo Reports</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-700 feature-item">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span>Emergency Contact Available</span>
                            </div>
                        </div>
                        
                        <div class="text-center service-price">
                            <div class="text-3xl font-bold text-green-600 mb-1">₱200+</div>
                            <div class="text-sm text-gray-500">Per hour</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-20 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-white/80 text-orange-600 border-orange-200 mb-6">
                        <i data-lucide="award" class="w-3 h-3 mr-1"></i>
                        Why Choose pawhabilin
                    </div>
                    
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        Trusted by <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">25,000+</span> Pet Families
                    </h2>
                    
                    <p class="text-xl text-gray-700 mb-8 leading-relaxed">
                        We've built our reputation on providing exceptional care, professional service, 
                        and genuine love for all pets. Here's what makes us the Philippines' most trusted pet care platform.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">Licensed & Certified Professionals</h3>
                                <p class="text-gray-600">All our veterinarians, groomers, and pet sitters are fully licensed with years of experience and ongoing training.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">24/7 Emergency Support</h3>
                                <p class="text-gray-600">Round-the-clock emergency services and support hotline for urgent pet care needs and peace of mind.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="heart-handshake" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-2">Personalized Care Plans</h3>
                                <p class="text-gray-600">Every pet receives individualized attention and care plans tailored to their specific needs, personality, and health requirements.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-orange-600 mb-2">500+</div>
                                <div class="text-sm text-gray-600">Certified Professionals</div>
                            </div>
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2">75k+</div>
                                <div class="text-sm text-gray-600">Happy Appointments</div>
                            </div>
                        </div>
                        <div class="space-y-6 pt-12">
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">4.9★</div>
                                <div class="text-sm text-gray-600">Average Rating</div>
                            </div>
                            <div class="glass-effect rounded-2xl p-6 text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-2">24/7</div>
                                <div class="text-sm text-gray-600">Emergency Support</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating pet image -->
                    <div class="absolute -top-8 -right-8 floating-element">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-2xl">
                            <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGRvZyUyMG93bmVyJTIwaHVnZ2luZ3xlbnwxfHx8fDE3NTY0NTIxMjl8MA&ixlib=rb-4.1.0&q=80&w=1080" alt="Happy pet" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-orange-50 text-orange-600 border-orange-200 mb-6">
                    <i data-lucide="message-circle" class="w-3 h-3 mr-1"></i>
                    Testimonials
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    What Pet Parents Say About Us
                </h2>
                <p class="text-xl text-muted-foreground max-w-3xl mx-auto">
                    Don't just take our word for it. Here's what our satisfied customers have to say about their experience with pawhabilin.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-8 border border-orange-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            M
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Maria Santos</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The veterinary care my Golden Retriever received was exceptional. Dr. Rivera was so gentle and thorough during the examination. The booking process was seamless and I trust pawhabilin completely with my pets."</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            J
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">John Cruz</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"Amazing grooming service! My Shih Tzu looks absolutely fantastic and smells amazing. The professional groomers really know how to handle dogs and make them feel comfortable throughout the entire process."</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 border border-green-100 hover:shadow-lg transition-all duration-300 hover:-translate-y-2">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            A
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Ana Reyes</h4>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"The pet sitting service is absolutely incredible. My two Persian cats were so well cared for while I was away on vacation. I received daily updates with photos and videos. Highly recommended for peace of mind!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking-section" class="py-20 bg-gradient-to-br from-gray-900 via-orange-900 to-amber-900 relative overflow-hidden">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"%23ffffff\">&3Cg fill-opacity=\"0.3\">&3Cpath d=\"M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.373zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83z\"/%3E&3C/g>&3C/svg>');"></div>
        </div>
        
        <!-- Floating elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-20 left-10 opacity-20">
                <i data-lucide="calendar-heart" class="w-16 h-16 text-white transform rotate-12"></i>
            </div>
            <div class="floating-element absolute bottom-20 right-20 opacity-20">
                <i data-lucide="clock" class="w-14 h-14 text-white transform -rotate-12"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <div class="inline-flex items-center rounded-full border border-white/20 px-6 py-2 text-sm font-medium glass-effect text-white mb-6">
                    <i data-lucide="calendar-heart" class="w-4 h-4 mr-2"></i>
                    Book Your Appointment
                </div>
                
                <h2 class="text-4xl md:text-6xl font-bold text-white mb-6 text-shadow">
                    Ready to Care for Your
                    <span class="block brand-font text-5xl md:text-7xl text-amber-300">Furry Family?</span>
                </h2>
                
                <p class="text-xl text-white/90 max-w-3xl mx-auto mb-12">
                    Schedule your appointment today and give your beloved pet the professional care they deserve. 
                    Our expert team is ready to provide exceptional service tailored to your pet's unique needs.
                </p>
            </div>

            <!-- Sign In Prompt -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="sign-in-prompt rounded-3xl p-8 text-center">
                    <div class="flex items-center justify-center mb-4">
                        <i data-lucide="lock" class="w-8 h-8 text-amber-600 mr-3"></i>
                        <h3 class="text-2xl font-bold text-amber-900">Sign In Required</h3>
                    </div>
                    <p class="text-amber-800 mb-6 text-lg">
                        To ensure the best care for your pet and to manage your appointments, 
                        please sign in to your pawhabilin account or create a new one.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="login.php" class="inline-flex items-center justify-center gap-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-300 transform hover:scale-105">
                            <i data-lucide="log-in" class="w-5 h-5"></i>
                            Sign In to Book
                        </a>
                        <a href="login.php" class="inline-flex items-center justify-center gap-3 bg-white text-orange-600 font-semibold py-3 px-8 rounded-xl hover:bg-orange-50 transition-all duration-300 transform hover:scale-105">
                            <i data-lucide="user-plus" class="w-5 h-5"></i>
                            Create Account
                        </a>
                    </div>
                </div>
            </div>

            <!-- Preview Form (Disabled) -->
            <div class="max-w-4xl mx-auto">
                <div class="booking-form rounded-3xl p-8 md:p-12 relative">
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50 rounded-3xl flex items-center justify-center">
                        <div class="text-center text-white">
                            <i data-lucide="lock" class="w-16 h-16 mx-auto mb-4 opacity-80"></i>
                            <h3 class="text-2xl font-bold mb-2">Please Sign In to Continue</h3>
                            <p class="text-white/80">Access this form after signing in to your account</p>
                        </div>
                    </div>
                    
                    <form class="space-y-8 opacity-30">
                        <!-- Service Selection -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="heart" class="w-6 h-6 text-orange-400"></i>
                                Select Your Service
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="veterinary" class="sr-only" disabled>
                                    <div class="form-input rounded-2xl p-6 text-center">
                                        <i data-lucide="stethoscope" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Veterinary Care</h4>
                                        <p class="text-white/70 text-sm">Health checkups & medical care</p>
                                        <div class="text-red-400 font-bold mt-2">₱800+</div>
                                    </div>
                                </label>
                                
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="grooming" class="sr-only" disabled>
                                    <div class="form-input rounded-2xl p-6 text-center">
                                        <i data-lucide="scissors" class="w-12 h-12 text-blue-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Professional Grooming</h4>
                                        <p class="text-white/70 text-sm">Bathing, styling & nail care</p>
                                        <div class="text-blue-400 font-bold mt-2">₱600+</div>
                                    </div>
                                </label>
                                
                                <label class="cursor-pointer group">
                                    <input type="radio" name="service" value="pet-sitting" class="sr-only" disabled>
                                    <div class="form-input rounded-2xl p-6 text-center">
                                        <i data-lucide="users" class="w-12 h-12 text-green-400 mx-auto mb-4"></i>
                                        <h4 class="font-semibold text-white mb-2">Pet Sitting & Care</h4>
                                        <p class="text-white/70 text-sm">In-home care & boarding</p>
                                        <div class="text-green-400 font-bold mt-2">₱200/hr</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="user" class="w-6 h-6 text-orange-400"></i>
                                Your Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Full Name *</label>
                                    <input type="text" disabled class="w-full px-4 py-3 rounded-lg form-input text-white placeholder-white/50" placeholder="Enter your full name">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Email Address *</label>
                                    <input type="email" disabled class="w-full px-4 py-3 rounded-lg form-input text-white placeholder-white/50" placeholder="Enter your email">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Phone Number *</label>
                                    <input type="tel" disabled class="w-full px-4 py-3 rounded-lg form-input text-white placeholder-white/50" placeholder="Enter your phone number">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Address</label>
                                    <input type="text" disabled class="w-full px-4 py-3 rounded-lg form-input text-white placeholder-white/50" placeholder="Enter your address">
                                </div>
                            </div>
                        </div>

                        <!-- Pet Information -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="paw-print" class="w-6 h-6 text-orange-400"></i>
                                Pet Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Pet Name *</label>
                                    <input type="text" disabled class="w-full px-4 py-3 rounded-lg form-input text-white placeholder-white/50" placeholder="Enter your pet's name">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Pet Type *</label>
                                    <select disabled class="w-full px-4 py-3 rounded-lg form-input text-white">
                                        <option value="">Select pet type</option>
                                        <option value="dog">Dog</option>
                                        <option value="cat">Cat</option>
                                        <option value="bird">Bird</option>
                                        <option value="rabbit">Rabbit</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Details -->
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <i data-lucide="calendar" class="w-6 h-6 text-orange-400"></i>
                                Appointment Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white font-medium mb-2">Preferred Date *</label>
                                    <input type="date" disabled class="w-full px-4 py-3 rounded-lg form-input text-white">
                                </div>
                                <div>
                                    <label class="block text-white font-medium mb-2">Preferred Time *</label>
                                    <select disabled class="w-full px-4 py-3 rounded-lg form-input text-white">
                                        <option value="">Select time</option>
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center pt-6">
                            <button type="button" disabled class="inline-flex items-center justify-center gap-3 rounded-full text-lg font-semibold bg-gray-500 text-white h-16 px-12 cursor-not-allowed">
                                <i data-lucide="calendar-check" class="w-6 h-6"></i>
                                Book My Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (shared) -->
    <?php include __DIR__ . '/utils/footer.php'; ?>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('slide-in-up');
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            document.querySelectorAll('.service-card, .glass-effect').forEach(el => {
                observer.observe(el);
            });
        });

        // Smooth scroll to booking section
        function scrollToBooking() {
            document.getElementById('booking-section').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Parallax effect for background elements
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating-element');
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.1 + (index * 0.05);
                const yPos = -(scrolled * speed);
                element.style.transform = `translate3d(0, ${yPos}px, 0)`;
            });
        });

        // Add click listeners to service cards for demo purposes
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('click', function() {
                scrollToBooking();
            });
        });
    </script>
</body>
</html>
