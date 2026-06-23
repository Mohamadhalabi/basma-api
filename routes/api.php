<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\AddressController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by RouteServiceProvider which applies the "api"
| middleware group. Make something great!
|
*/

// ============ PUBLIC ROUTES (No Auth Required) ============

// Product endpoints
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/categories', [ProductController::class, 'categories'])->name('api.products.categories');
    Route::get('/manufacturers', [ProductController::class, 'manufacturers'])->name('api.products.manufacturers');
    Route::get('/{product}', [ProductController::class, 'show'])->name('api.products.show');
});

// Auth endpoints
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
});

// ============ PROTECTED ROUTES (Auth Required) ============
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('profile', [AuthController::class, 'profile'])->name('api.auth.profile');
    });

    // Cart & Orders
    Route::prefix('cart')->group(function () {
        Route::post('calculate-price', [CartController::class, 'calculatePrice'])->name('api.cart.calculate');
        Route::post('create-order', [CartController::class, 'createOrder'])->name('api.cart.create-order');
    });

    // Customer orders
    Route::get('orders', [CartController::class, 'orders'])->name('api.orders');

    // Addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index'])->name('api.addresses.index');
        Route::post('/', [AddressController::class, 'store'])->name('api.addresses.store');
        Route::put('/{address}', [AddressController::class, 'update'])->name('api.addresses.update');
        Route::delete('/{address}', [AddressController::class, 'destroy'])->name('api.addresses.destroy');
    });
});

// Default user endpoint
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
