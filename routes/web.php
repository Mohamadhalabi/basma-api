<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\InvoiceController;

// ============ ADMIN ROUTES ============
// Filament admin panel stays at /admin (already configured)
Route::get('/admin/invoice/{order}', [InvoiceController::class, 'download'])
    ->name('invoice.download')
    ->middleware('auth');

// ============ STOREFRONT ROUTES ============

// Public routes
Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/products/{slug}', [StorefrontController::class, 'productDetail'])->name('products.show');

// Auth routes (no auth middleware)
Route::get('/login', [StorefrontController::class, 'showLogin'])->name('customer.login');
Route::get('/register', [StorefrontController::class, 'showRegister'])->name('customer.register');

// Cart (public, but uses frontend storage)
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');

// Protected routes (customer logged in via Sanctum tokens in API)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
    Route::get('/account/profile', [StorefrontController::class, 'account'])->name('account.profile');
    Route::get('/account/orders', [StorefrontController::class, 'orders'])->name('account.orders');
});

// Fallback: redirect any admin root to admin panel
Route::redirect('/admin', '/admin', 301);
