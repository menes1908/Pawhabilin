<?php
// Ensure session & persistent auth restoration before any output
require_once __DIR__ . '/../../utils/session.php';
session_start_if_needed();
require_once __DIR__ . '/../../utils/auth_persist.php'; // silent auto-restore if cookie present
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawhabilin - Because Every Paw Deserves a Promise.</title>
    <link rel="stylesheet" href="../../globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<style>
        /* Enhanced animations and effects */
        .hero-float {
            animation: heroFloat 6s ease-in-out infinite;
        }
        
        .hero-float-delayed {
            animation: heroFloat 6s ease-in-out infinite 2s;
        }
        
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.6; }
            50% { transform: translateY(-30px) rotate(5deg); opacity: 1; }
        }
        
        .feature-card {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transform-style: preserve-3d;
        }
        
        .feature-card:hover {
            transform: translateY(-8px) rotateX(2deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .step-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .step-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .step-card:hover:before {
            left: 100%;
        }
        
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(249, 115, 22, 0.15);
        }
        
        .cta-button {
            background: linear-gradient(135deg, #f97316, #d97706);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .cta-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .cta-button:hover:before {
            left: 100%;
        }
        
        .cta-button:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4);
        }
        
        .value-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .value-card:hover {
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.8));
            transform: translateY(-3px);
        }
        
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
        }
        
        .image-container img {
            transition: transform 0.4s ease;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .feature-card:hover {
                transform: translateY(-4px);
            }
            
            .step-card:hover {
                transform: translateY(-2px);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 relative overflow-x-hidden">
    <!-- Floating Background Elements -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="hero-float absolute top-32 left-16 opacity-20">
            <i data-lucide="paw-print" class="w-16 h-16 text-orange-300"></i>
        </div>
        <div class="hero-float-delayed absolute top-20 right-20 opacity-15">
            <i data-lucide="heart" class="w-12 h-12 text-amber-300"></i>
        </div>
        <div class="hero-float absolute bottom-32 left-1/4 opacity-10">
            <i data-lucide="paw-print" class="w-24 h-24 text-orange-400"></i>
        </div>
        <div class="hero-float-delayed absolute bottom-40 right-1/3 opacity-25">
            <i data-lucide="heart" class="w-10 h-10 text-amber-400"></i>
        </div>
    </div>

    <!-- Header (users wrapper to match become-sitter-logged) -->
    <?php $basePrefix = '../..'; include __DIR__ . '/../../utils/header-users.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
        <!-- Hero Section -->
        <section class="text-center mb-20">
            <div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm rounded-full px-6 py-3 border border-orange-200 mb-8">
                <i data-lucide="info" class="w-5 h-5 text-orange-600"></i>
                <span class="text-orange-600 font-medium">About Pawhabilin</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl mb-6">
                <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                    Caring for Pets,<br>Made Simple
                </span>
            </h1>
            
            <p class="text-2xl text-gray-700 max-w-4xl mx-auto leading-relaxed mb-12">
                Pawhabilin connects pet owners with trusted sitters and essential pet services all in one platform.
            </p>
            
            <!-- Hero Image -->
            <div class="image-container max-w-4xl mx-auto mb-12">
                <img 
                    src="https://images.unsplash.com/photo-1509205477838-a534e43a849f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMHBldHMlMjBkb2clMjBjYXQlMjB0b2dldGhlcnxlbnwxfHx8fDE3NTkyMDg4ODh8MA&ixlib=rb-4.1.0&q=80&w=1080" 
                    alt="Happy pets together" 
                    class="w-full h-80 md:h-96 object-cover shadow-2xl"
                />
            </div>
        </section>

        <!-- Mission Statement -->
        <section class="mb-20">
            <div class="bg-white/90 backdrop-blur-sm rounded-3xl p-12 border border-orange-200 shadow-lg text-center">
                <h2 class="text-4xl mb-6">
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                        Our Mission
                    </span>
                </h2>
                <p class="text-xl text-gray-700 max-w-4xl mx-auto leading-relaxed">
                    Our mission is to make pet care stress-free by providing a reliable, easy-to-use platform for pet owners and sitters. 
                    We believe every pet deserves the best care, and every pet parent deserves peace of mind.
                </p>
            </div>
        </section>

        <!-- What is Pawhabilin -->
        <section class="mb-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl mb-6">
                        <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                            What is Pawhabilin?
                        </span>
                    </h2>
                    <div class="space-y-6 text-lg text-gray-700 leading-relaxed">
                        <p>
                            Pawhabilin is an all-in-one pet care platform where you can book sitters, schedule grooming, 
                            find vet services, and shop for pet essentials.
                        </p>
                        <p>
                            Whether you're going on vacation, need someone to walk your dog, or want to pamper your pet 
                            with professional grooming, we've got you covered. Our platform brings together trusted pet 
                            care professionals and quality services in one convenient place.
                        </p>
                        <p>
                            From emergency vet visits to routine care, premium pet products to personalized sitting 
                            services Pawhabilin is your complete pet care companion.
                        </p>
                    </div>
                </div>
                <div class="image-container">
                    <img 
                        src="https://images.unsplash.com/photo-1563460716037-460a3ad24ba9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZXQlMjBzaXR0ZXIlMjBjYXJpbmclMjBhbmltYWxzfGVufDF8fHx8MTc1OTIwODg5MXww&ixlib=rb-4.1.0&q=80&w=1080" 
                        alt="Pet sitter caring for animals" 
                        class="w-full h-80 object-cover shadow-xl"
                    />
                </div>
            </div>
        </section>

        <!-- Key Features -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl mb-4">
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                        Key Features
                    </span>
                </h2>
            /* CTA styles removed for logged-in homepage */
                    <h3 class="text-xl mb-3">Pet Services</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Grooming and vet appointments. Schedule professional care services with certified providers.</p>
                </div>
                
                <!-- Shop -->
                <div class="feature-card bg-white/90 backdrop-blur-sm rounded-2xl p-8 border border-orange-200 shadow-lg text-center">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i data-lucide="shopping-bag" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl mb-3">Shop</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Buy pet accessories and essentials. Quality products delivered right to your door.</p>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl mb-4">
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                        Why Choose Pawhabilin?
                    </span>
                </h2>
                <p class="text-xl text-gray-600">The features that make us different</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="value-card rounded-2xl p-8 border border-orange-200 shadow-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mb-6">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-xl mb-3">Trusted Sitters</h3>
                    <p class="text-gray-600 leading-relaxed">All sitters undergo background checks and verification. Browse verified profiles with real reviews from pet parents.</p>
                </div>
                
                <div class="value-card rounded-2xl p-8 border border-orange-200 shadow-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mb-6">
                        <i data-lucide="smartphone" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-xl mb-3">Easy Booking</h3>
                    <p class="text-gray-600 leading-relaxed">Simple, intuitive platform with secure payments. Book services in just a few clicks with instant confirmation.</p>
                </div>
                
                <div class="value-card rounded-2xl p-8 border border-orange-200 shadow-lg">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center mb-6">
                        <i data-lucide="package" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-xl mb-3">All-in-One Convenience</h3>
                    <p class="text-gray-600 leading-relaxed">Everything your pet needs in one place. From sitting to shopping, grooming to vet care we've got it all.</p>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl mb-4">
                    <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                        How It Works
                    </span>
                </h2>
                <p class="text-xl text-gray-600">Getting started is simple</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="step-card bg-white/90 backdrop-blur-sm rounded-2xl p-8 border border-orange-200 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <span class="text-2xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-xl mb-4">Sign Up or Log In</h3>
                    <p class="text-gray-600 leading-relaxed">Create your account in minutes. Add your pet's details and preferences to get personalized recommendations.</p>
                </div>
                
                <!-- Step 2 -->
                <div class="step-card bg-white/90 backdrop-blur-sm rounded-2xl p-8 border border-orange-200 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl mb-4">Choose a Service</h3>
                    <p class="text-gray-600 leading-relaxed">Browse sitters, book appointments, shop for products, or become a sitter yourself. The choice is yours!</p>
                </div>
                
                <!-- Step 3 -->
                <div class="step-card bg-white/90 backdrop-blur-sm rounded-2xl p-8 border border-orange-200 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl mb-4">Enjoy Peace of Mind</h3>
                    <p class="text-gray-600 leading-relaxed">Confirm your booking and relax. Your pet is in good hands with our trusted care providers.</p>
                </div>
            </div>
        </section>

        <!-- Community & Values -->
        <section class="mb-20">
            <div class="bg-gradient-to-r from-orange-100 via-amber-50 to-yellow-100 rounded-3xl p-12 border border-orange-200">
                <div class="text-center mb-12">
                    <h2 class="text-4xl mb-4">
                        <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                            Our Community & Values
                        </span>
                    </h2>
                    <p class="text-xl text-gray-700 max-w-3xl mx-auto leading-relaxed">
                        At Pawhabilin, we're built on trust, safety, and an unwavering love for pets. Our community of pet parents 
                        and caregivers share the same values: providing the best possible care for our furry family members.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="heart" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-xl mb-3">Love & Care</h3>
                        <p class="text-gray-600">Every interaction is driven by genuine love and care for pets and their well-being.</p>
                    </div>
                    
                    <div>
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="shield" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-xl mb-3">Trust & Safety</h3>
                        <p class="text-gray-600">Rigorous verification processes and safety measures ensure peace of mind for everyone.</p>
                    </div>
                    
                    <div>
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="users" class="w-8 h-8 text-white"></i>
                        </div>
                        <h3 class="text-xl mb-3">Community</h3>
                        <p class="text-gray-600">Building connections between pet lovers who understand the special bond we share with our pets.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA removed for logged-in view -->
            <!-- CTA removed for logged-in view -->
    </div>

    <!-- Footer (shared) -->
    <?php include __DIR__ . '/../../utils/footer.php'; ?>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all sections for scroll animations
        document.querySelectorAll('section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(section);
        });
        
        // CTA button interactions
        document.querySelectorAll('.cta-button').forEach(button => {
            button.addEventListener('click', function() {
                // Add click animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
            // CTA interactions removed for logged-in view
    </script>
</body>
</html>