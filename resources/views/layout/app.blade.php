<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'بصمة - Basma') | متجر قطع غيار السيارات</title>
    <meta name="description" content="@yield('meta_description', 'متجر بصمة لقطع غيار السيارات الأصلية والأجهزة المتخصصة')">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Font: Cairo for Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        :root {
            --primary-color: #1f7c3f;
            --primary-dark: #165a30;
            --primary-light: #86efac;
        }
        
        body {
            background-color: #f9fafb;
            scroll-behavior: smooth;
        }
        
        /* Fix for sticky header - ensure proper scrolling */
        html {
            scroll-padding-top: 200px;
        }
        
        .btn-primary {
            @apply px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-300 font-semibold;
        }
        
        .product-card {
            @apply bg-white rounded-lg shadow-md hover:shadow-2xl transition duration-300 overflow-hidden;
        }
        
        .section-title {
            @apply text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center;
        }
    </style>
    
    @yield('extra_styles')
</head>
<body class="bg-gray-50">
    
    <!-- Header Top Bar -->
    <div class="bg-gray-900 text-white py-2 text-sm fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex gap-6">
                <a href="tel:+966" class="hover:text-green-400 transition">
                    <i class="fas fa-phone ml-2"></i>+966 (نداء مجاني)
                </a>
                <a href="mailto:info@basma.sa" class="hover:text-green-400 transition">
                    <i class="fas fa-envelope ml-2"></i>info@basma.sa
                </a>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('customer.login') }}" class="hover:text-green-400 transition">
                    <i class="fas fa-user ml-2"></i>تسجيل الدخول
                </a>
                <a href="{{ route('customer.register') }}" class="hover:text-green-400 transition">
                    <i class="fas fa-user-plus ml-2"></i>حساب جديد
                </a>
            </div>
        </div>
    </div>
    
    <!-- Header Main -->
    <header class="bg-white shadow-md fixed w-full top-10 z-40">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center gap-8">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-md">
                        ب
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-xl font-bold text-gray-900">بصمة</h1>
                        <p class="text-xs text-green-600 font-semibold">Basma</p>
                    </div>
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="flex-1 hidden md:block max-w-lg">
                <form action="{{ route('products.index') }}" method="GET" class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="ابحث عن المنتجات..."
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-600 transition bg-gray-50"
                    >
                    <button type="submit" class="absolute left-3 top-3 text-gray-500 hover:text-green-600 transition">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                </form>
            </div>
            
            <!-- Cart Icon -->
            <div class="relative flex items-center">
                <a href="{{ route('cart.index') }}" class="text-gray-700 hover:text-green-600 text-2xl relative transition">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="absolute -top-3 -left-3 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold" id="cart-count">
                        0
                    </span>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Green Navigation Bar -->
    <nav class="bg-green-600 text-white shadow-lg fixed w-full top-32 z-40">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex gap-8">
                    <a href="{{ route('home') }}" class="hover:text-green-100 transition font-semibold text-sm md:text-base">الرئيسية</a>
                    <a href="{{ route('products.index') }}" class="hover:text-green-100 transition font-semibold text-sm md:text-base">كل المنتجات</a>
                    <a href="#" class="hover:text-green-100 transition font-semibold text-sm md:text-base">من نحن</a>
                    <a href="#" class="hover:text-green-100 transition font-semibold text-sm md:text-base">اتصل بنا</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content - Add top padding to account for fixed header -->
    <main class="pt-56 container mx-auto px-4 py-8">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-green-600 rounded flex items-center justify-center">ب</div>
                        بصمة
                    </h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        متخصصون في توفير أجود قطع غيار السيارات والأجهزة المتخصصة لصناعة المفاتيح والقطع.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold mb-4">روابط سريعة</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition">الرئيسية</a></li>
                        <li><a href="{{ route('products.index') }}" class="hover:text-white transition">المنتجات</a></li>
                        <li><a href="#" class="hover:text-white transition">من نحن</a></li>
                        <li><a href="#" class="hover:text-white transition">الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div>
                    <h4 class="text-lg font-bold mb-4">الأقسام</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('products.index') }}" class="hover:text-white transition">كل المنتجات</a></li>
                        <li><a href="#" class="hover:text-white transition">الأقسام</a></li>
                        <li><a href="#" class="hover:text-white transition">التصنيفات</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-bold mb-4">تواصل معنا</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex gap-2">
                            <i class="fas fa-phone text-green-600 mt-1"></i>
                            <a href="tel:+966" class="hover:text-white transition">+966 (نداء مجاني)</a>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-envelope text-green-600 mt-1"></i>
                            <a href="mailto:info@basma.sa" class="hover:text-white transition">info@basma.sa</a>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-map-marker-alt text-green-600 mt-1"></i>
                            <span>المملكة العربية السعودية</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8">
                <p class="text-center text-gray-400 text-sm">
                    &copy; 2026 بصمة - Basma. جميع الحقوق محفوظة.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart count from localStorage
            updateCartCount();
        });
        
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((total, item) => total + item.quantity, 0);
            document.getElementById('cart-count').textContent = count;
        }
        
        // Listen for cart updates
        window.addEventListener('cartUpdated', updateCartCount);
    </script>
    
    @yield('extra_scripts')
</body>
</html>
