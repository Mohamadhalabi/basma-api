@extends('layout.app')

@section('title', 'بصمة - متجر قطع غيار السيارات')

@section('content')

<!-- Hero Section -->
<div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl text-white p-8 md:p-12 mb-12 shadow-lg mt-4">
    <div class="max-w-2xl">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">مرحباً بك في بصمة</h2>
        <p class="text-lg mb-6 text-green-50">
            متخصصون في توفير أفضل قطع غيار السيارات والأجهزة المتخصصة بأسعار تنافسية
        </p>
        <a href="{{ route('products.index') }}" class="inline-block bg-white text-green-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            تسوق الآن
        </a>
    </div>
</div>

<!-- Categories Section -->
<section class="mb-16">
    <h2 class="section-title">اختر من أقسامنا</h2>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6" id="categories-container">
        <!-- Loading State -->
        <div class="col-span-full text-center py-12">
            <div class="inline-block">
                <i class="fas fa-spinner fa-spin text-green-600 text-3xl"></i>
                <p class="text-gray-500 mt-2">جاري التحميل...</p>
            </div>
        </div>
    </div>
</section>

<!-- Products by Category Section -->
<section id="products-section">
    <!-- Will be populated by Alpine.js -->
</section>

<style>
    .product-image-container {
        @apply w-full aspect-square overflow-hidden bg-gray-200;
    }

    .product-image {
        @apply w-full h-full object-cover group-hover:scale-110 transition duration-500;
    }
    .category-icon {
        @apply w-20 h-20 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-green-600 text-3xl mb-2;
    }
    
    .category-card:hover .category-icon {
        @apply from-green-200 to-green-300 shadow-lg;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Fetch categories
        const categoriesResponse = await fetch('/api/products/categories');
        const categories = await categoriesResponse.json();
        
        if (categories && categories.length > 0) {
            renderCategories(categories);
            renderProductsByCategory(categories);
        }
    } catch (error) {
        console.error('Error loading data:', error);
        document.getElementById('categories-container').innerHTML = `
            <div class="col-span-full text-center py-12 text-red-500">
                <i class="fas fa-exclamation-circle text-3xl"></i>
                <p class="mt-2">حدث خطأ في تحميل البيانات</p>
            </div>
        `;
    }
});

// Category icons mapping
const categoryIcons = {
    'remotes': 'fa-remote',
    'key': 'fa-key',
    'remote': 'fa-remote',
    'knife': 'fa-knife',
    'tools': 'fa-tools',
    'machine': 'fa-cog',
    'device': 'fa-microchip',
    'equipment': 'fa-wrench',
    'default': 'fa-cube'
};

function getCategoryIcon(categoryName) {
    for (const [key, icon] of Object.entries(categoryIcons)) {
        if (categoryName.toLowerCase().includes(key)) {
            return icon;
        }
    }
    return categoryIcons.default;
}

function renderCategories(categories) {
    const container = document.getElementById('categories-container');
    
    if (!categories || categories.length === 0) {
        container.innerHTML = '<p class="col-span-full text-center text-gray-500">لا توجد أقسام متاحة</p>';
        return;
    }
    
    container.innerHTML = categories.map(category => {
        const icon = getCategoryIcon(category.name);
        return `
            <a href="{{ route('products.index') }}?category=${category.id}" 
               class="category-card p-6 flex flex-col items-center gap-2 group"
               title="${category.name}">
                <div class="category-icon group-hover:scale-110 transition duration-300">
                    <i class="fas ${icon}"></i>
                </div>
                <h3 class="text-center font-semibold text-gray-900 text-sm line-clamp-2 h-10 flex items-center justify-center">
                    ${category.name}
                </h3>
                <span class="text-xs text-gray-500 group-hover:text-green-600 transition font-medium">
                    عرض المنتجات
                    <i class="fas fa-arrow-left ml-1"></i>
                </span>
            </a>
        `;
    }).join('');
}

async function renderProductsByCategory(categories) {
    const productsSection = document.getElementById('products-section');
    
    try {
        // Fetch products for each category
        const allProducts = await fetch('/api/products?per_page=100').then(r => r.json());
        
        if (!allProducts.data || allProducts.data.length === 0) {
            productsSection.innerHTML = '<p class="col-span-full text-center text-gray-500">لا توجد منتجات متاحة</p>';
            return;
        }
        
        // Group products by category
        let html = '';
        
        for (const category of categories.slice(0, 4)) {
            const categoryProducts = allProducts.data.filter(p => 
                p.categories && p.categories.some(c => c.id === category.id)
            );
            
            if (categoryProducts.length > 0) {
                html += `
                    <div class="mb-16">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900">منتجات ${category.name}</h3>
                            <a href="{{ route('products.index') }}?category=${category.id}" class="text-green-600 hover:text-green-700 font-semibold flex items-center gap-2">
                                عرض الكل
                                <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                            ${categoryProducts.slice(0, 8).map(product => renderProductCard(product)).join('')}
                        </div>
                    </div>
                `;
            }
        }
        
        productsSection.innerHTML = html;
    } catch (error) {
        console.error('Error loading products:', error);
        productsSection.innerHTML = '<p class="col-span-full text-center text-red-500">خطأ في تحميل المنتجات</p>';
    }
}

function renderProductCard(product) {
    const imageUrl = product.gallery && product.gallery.length > 0 
        ? product.gallery[0].card
        : 'https://via.placeholder.com/400x400?text=No+Image';
    
    const price = product.default_price ? (product.default_price / 100).toFixed(2) : '0.00';
    const stockStatus = product.stock_quantity > 0 ? 'متوفر' : 'غير متوفر';
    const badgeColor = product.stock_quantity > 0 ? 'bg-green-500' : 'bg-red-500';
    
    return `
        <div class="product-card group relative overflow-hidden h-full flex flex-col">
            <!-- Stock Badge -->
            <span class="absolute top-3 right-3 ${badgeColor} text-white px-3 py-1 rounded-full text-xs font-bold z-10">
                ${stockStatus}
            </span>
            
            <!-- Image -->
            <a href="/products/${product.slug}" class="block overflow-hidden bg-gray-200 relative flex-shrink-0 product-image-container">
                <img 
                    src="${imageUrl}" 
                    alt="${product.title}"
                    class="product-image"
                    onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'"
                >
            </a>
            
            <!-- Content -->
            <div class="p-4 flex flex-col flex-grow">
                <!-- SKU -->
                <p class="text-xs text-gray-500 mb-2 font-semibold">SKU: ${product.sku}</p>
                
                <!-- Title -->
                <a href="/products/${product.slug}">
                    <h4 class="product-title text-gray-900 font-bold text-sm line-clamp-2 mb-2 hover:text-green-600 transition">
                        ${product.title}
                    </h4>
                </a>
                
                <!-- Price -->
                <p class="product-price text-green-600 font-bold text-lg mb-auto">
                    ${price} <span class="text-sm">ريال</span>
                </p>
                
                <!-- Add to Cart Button -->
                <button 
                    onclick="addToCart(${product.id}, '${product.title}', ${product.default_price})"
                    class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition font-semibold text-sm flex items-center justify-center gap-2"
                    ${product.stock_quantity === 0 ? 'disabled opacity-50 cursor-not-allowed' : ''}
                >
                    <i class="fas fa-shopping-cart"></i>
                    أضف للسلة
                </button>
            </div>
        </div>
    `;
}

function addToCart(productId, title, price) {
    // Get cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.product_id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            product_id: productId,
            title: title,
            price: price,
            quantity: 1
        });
    }
    
    // Save to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Dispatch custom event to update cart count
    window.dispatchEvent(new Event('cartUpdated'));
    
    // Show notification
    showNotification('تم إضافة المنتج للسلة');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-pulse';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

@endsection
